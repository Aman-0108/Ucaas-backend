<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\WalletRecharge;
use App\Mail\NewUserMail;
use App\Mail\RechargeSuccessfull;
use App\Models\Account;
use App\Models\CardDetail;
use App\Models\Lead;
use App\Models\Package;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    protected $stripeController;

    public function __construct(StripeController $stripeController)
    {
        $this->stripeController = $stripeController;
    }

    // All Payments
    public function index(Request $request)
    {
        $payments = Payment::with(['account']);

        // Check if the request contains an 'account_id' parameter
        if ($request->has('account_id')) {
            // If 'account' parameter is provided, filter domains by account ID
            $payments->where('account_id', $request->account_id);
        }

        // COMING FROM GLOBAL CONFIG
        $ROW_PER_PAGE = config('globals.PAGINATION.ROW_PER_PAGE');

        // Execute the query to fetch domains
        $payments = $payments->orderBy('id', 'asc')->paginate($ROW_PER_PAGE);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $payments,
            'message' => 'Successfully fetched.'
        ];

        // Return a JSON response containing the list of domains
        return response()->json($response, Response::HTTP_OK);
    }

    // payment
    public function pay(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'string|required|min:5',
                'lead_id' => 'required|numeric|exists:leads,id',
                'type' => 'required|string|max:20',
                'card_number' => 'required|numeric|digits_between:14,16',
                'exp_month' => 'required|digits_between:1,2|numeric|between:1,12',
                'exp_year' => 'required|numeric|digits:4',
                'cvc' => 'required|numeric|digits_between:3,4',
                'fullname' => 'required|string',
                'contact_no' => 'required|string',
                'email' => 'required|string',
                'address' => 'required|string',
                'zip' => 'required|string',
                'city' => 'required|string',
                'state' => 'required|string',
                'country' => 'required|string',
                'save_card' => 'required|boolean'
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a 403 Forbidden response with validation errors
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Additional layer of security to check 
        if (!is_valid_email($request->email)) {
            // Prepare a success response with the stored account data
            $response = [
                'status' => false,
                'message' => 'Mail exchange is not available'
            ];

            // Return a JSON response with the success message and stored account data
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Find the lead by ID
        $lead = Lead::find($request->lead_id);

        $package = Package::find($lead->package_id);

        $amount = intval($package->offer_price);

        $stripePaymentinput = $request->only(['card_number', 'exp_month', 'exp_year', 'cvc', 'type']);

        $billingInput = $request->only(['fullname', 'contact_no', 'email', 'address', 'zip', 'city', 'state', 'country']);

        // Create payment method using Stripe
        $paymentMethodResponse = $this->stripeController->createPaymentMethod($stripePaymentinput);

        $paymentMethodContent = $paymentMethodResponse->getContent();
        $responseData = json_decode($paymentMethodContent, true);

        if (isset($responseData['error'])) {
            // Handle the error related to incorrect card number
            $response = [
                'status' => false,
                'error' => $responseData['error'],
            ];
            return response()->json($response, Response::HTTP_FORBIDDEN);
        } else {
            $paaymentMethodSuccess = $responseData['success'];

            $paymentId = $paaymentMethodSuccess['id'];
        }

        $leadId = $lead->id;

        // Define metadata
        $metadata = [
            'lead_id' => $leadId,
            // Add more metadata fields as needed
        ];

        // Create payment intent for the recharge transaction
        $transactionId = $this->stripeController->createPaymentIntent($amount, $paymentId, $metadata);

        // If transaction is successful
        if ($transactionId) {

            DB::beginTransaction();

            $lead->company_status = 1;
            // Remove unnecessary properties from the lead object
            unset($lead->id, $lead->created_at, $lead->updated_at);

            // Add Account
            $accountController = new AccountController($this->stripeController);
            $account = $accountController->createAccount($lead->toArray());

            $accountId = $account->id;

            // Add Billing Address
            $billingAddress = new BillingAddressController();
            $billingResult = $billingAddress->addData($accountId, $billingInput);

            // Add user
            $userController = new UserController();
            $userController->createUser($account);

            // Add Card Details
            $cardInput = [
                'name' => $request->name,
                'card_number' => $request->card_number,
                'exp_month' => $request->exp_month,
                'exp_year' => $request->exp_year,
                'cvc' => $request->cvc,
            ];

            if ($request->save_card == 1) {
                $cardInput['save_card'] = 1;
            }

            $cardController = new CardController();
            $card = $cardController->saveCard($accountId, $cardInput);

            // Add Payments
            $payment = $this->addPayment($billingResult->id, $accountId, $card->id, $amount, $transactionId, $package->subscription_type);

            // Add Subscription
            $subscriptionController = new SubscriptionController();
            $subscriptionController->createSubscription($accountId, $package, $transactionId);

            $userCredentials = [
                'company_name' => $account->company_name,
                'email' => $account->email,
                'username' => $account->company_name,
                'password' => $account->company_name,
                'dynamicUrl' => '',
                'transactionId' => $transactionId,
                'pdfPath' => $payment->invoice_url
            ];

            // Send mail to account holder with invoice
            // Mail::to($account->email)->send(new NewUserMail($userCredentials));

            DB::commit();

            $response = [
                'status' => true,
                'data' => $payment,
                'message' => 'You have paid successfully'
            ];
            // Return a JSON response with the list of accounts with status(200)
            return response()->json($response, Response::HTTP_OK);
        } else {
            $response = [
                'status' => false,
                'message' => 'Something went wrong.'
            ];

            // Return a JSON response with the list of accounts with status(200)
            return response()->json($response, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Adds a new payment record for an account.
     *
     * This function records payment details in the database, including the amount, transaction ID,
     * subscription type, and other relevant information. It then generates an invoice for the payment
     * and updates the payment record with the generated invoice URL.
     *
     * @param $accountId int The ID of the account for which the payment is added.
     * @param $amount float The total amount of the payment.
     * @param $transactionId int|string The ID of the transaction associated with the payment.
     * @param $subscriptionType string The type of subscription associated with the payment.
     * @return Payment The newly created payment object.
     */
    public function addPayment($billingAddressId, $accountId, $cardId, $amount, $transactionId, $subscriptionType = null)
    {
        // Record transaction details in the database
        $inputData = [
            'account_id' => $accountId,
            'card_id' => $cardId,
            'billing_address_id' => $billingAddressId,
            'amount_total' => $amount,
            'amount_subtotal' => $amount,
            'stripe_session_id' => $transactionId,
            'transaction_id' => $transactionId,
            'payment_gateway' => 'Stripe',
            'transaction_type' => 'new',
            'subscription_type' => $subscriptionType,
            'payment_method_options' => 'card',
            'currency' => 'usd',
            'payment_status' => 'complete',
            'transaction_date' => date("Y-m-d H:i:s"),
            // 'invoice_url' => $pdfUrl
        ];

        // Create a new payment record
        $payment = Payment::create($inputData);

        // Generate Invoice
        $invoice = new InvoiceController();
        $invoiceData = $invoice->generateInvoice($transactionId);

        // Update payment record with invoice URL
        $payment->invoice_url = $invoiceData['pdfPath'];
        $payment->save();

        // Return the newly created payment object
        return $payment;
    }

    // walletRecharge
    public function walletRecharge(Request $request)
    {
        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'card_id' => [
                    'required',
                    Rule::exists('card_details', 'id')->where(function ($query) use ($request) {
                        $query->where('id', $request->card_id)
                            ->where('account_id', $request->account_id);
                    })
                ],
                'address_id' => [
                    'required',
                    Rule::exists('billing_addresses', 'id')->where(function ($query) use ($request) {
                        $query->where('id', $request->address_id)
                            ->where('account_id', $request->account_id);
                    })
                ],
                'amount' => 'required|numeric|between:0,9999999.99',
                'cvc' => [
                    'required',
                    'digits:3',
                    Rule::exists('card_details')->where(function ($query) use ($request) {
                        $query->where('id', $request->card_id)
                            ->where('cvc', $request->cvc);
                    })
                ]
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {

            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = $validator->errors();

            return responseHelper($type, $status, $msg, Response::HTTP_FORBIDDEN);
        }

        // Extract input data
        $amount = $request->amount;

        $card = CardDetail::find($request->card_id);

        $input = [
            // 'account_id' => $card->account_id,
            // 'amount' => $amount,
            'card_number' => $card->card_number,
            'exp_month' =>  $card->exp_month,
            'exp_year' =>  $card->exp_year,
            'cvc' =>  $card->cvc,
            'type' => 'card'
        ];

        // Create payment method using Stripe
        $paymentMethodResponse = $this->stripeController->createPaymentMethod($input);

        $paymentMethodContent = $paymentMethodResponse->getContent();
        $responseData = json_decode($paymentMethodContent, true);

        if (isset($responseData['error'])) {
            // Handle the error related to incorrect card number
            $response = [
                'status' => false,
                'error' => $responseData['error'],
            ];
            return response()->json($response, Response::HTTP_FORBIDDEN);
        } else {
            $paaymentMethodSuccess = $responseData['success'];

            $paymentId = $paaymentMethodSuccess['id'];
        }

        // Define metadata
        $metadata = [
            'cause' => 'wallet recharge',
            // Add more metadata fields as needed
        ];

        // Create payment intent for the recharge transaction
        $transactionId = $this->stripeController->createPaymentIntent($amount, $paymentId, $metadata);

        // If transaction is successful
        if ($transactionId) {
            // Add payments
            $payment = $this->addPayment($request->address_id, $request->account_id, $card->id, $amount, $transactionId);

            // Add Balance
            $accountController = new AccountController($this->stripeController);
            $accountResult = $accountController->addOrUpdateBalance($request->account_id, $amount, $paymentId, $transactionId);

            $account = Account::find($request->account_id);

            $maildata = [
                'company_name' => $account->company_name,
                'email' => $account->email,
                'amount' => $payment->amount_total,
                'transactionId' => $transactionId,
                'pdfPath' => $payment->invoice_url,
                'transaction_date' => $payment->transaction_date
            ];

            // Send mail to account holder with invoice
            WalletRecharge::dispatch($maildata);
            // Mail::to($account->email)->send(new RechargeSuccessfull($maildata));

            // Success response
            $type = config('enums.RESPONSE.SUCCESS'); // Response type (success)
            $status = true; // Operation status (success)
            $msg = 'You have added balance successfully'; // Success message

            // Return a JSON response with HTTP status code 200 (OK)
            return responseHelper($type, $status, $msg, Response::HTTP_OK);
        } else {

            $type = config('enums.RESPONSE.ERROR'); // Response type (error)
            $status = false; // Operation status (failed)
            $msg = 'Something went wrong.'; // Detailed error messages

            return responseHelper($type, $status, $msg, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
