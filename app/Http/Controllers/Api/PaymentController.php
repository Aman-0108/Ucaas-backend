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
                    Rule::exists('card_details', 'id')->where(function ($query) use ($request) {
                        $query->where('id', $request->card_id)
                            ->where('account_id', $request->account_id);
                    })
                ],
                'name' => [
                    'required_unless:card_id,' . $request->card_id,
                    'string',
                    'min:5'
                ],
                'type' => 'required_unless:card_id,' . $request->card_id . '|string|max:20',
                'card_number' => 'required_unless:card_id,' . $request->card_id . '|numeric|digits_between:14,16',
                'exp_month' => 'required_unless:card_id,' . $request->card_id . '|digits_between:1,2|numeric|between:1,12',
                'exp_year' => 'required_unless:card_id,' . $request->card_id . '|numeric|digits:4',
                'address_id' => [
                    'required',
                    Rule::exists('billing_addresses', 'id')->where(function ($query) use ($request) {
                        $query->where('id', $request->address_id)
                            ->where('account_id', $request->account_id);
                    })
                ],
                'amount' => 'required|numeric|between:0,9999999.99',
                'cvc' => [
                    'required_unless:card_id,' . $request->card_id,
                    'digits:3',
                    Rule::exists('card_details')->where(function ($query) use ($request) {
                        $query->where('id', $request->card_id)
                            ->where('cvc', $request->cvc);
                    })
                ],
                'save_card' => 'required_unless:card_id,' . $request->card_id . '|boolean'
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

        if ($request->has('card_id')) {
            $card = CardDetail::find($request->card_id);

            $stripePaymentinput = [
                'card_number' => $card->card_number,
                'exp_month' =>  $card->exp_month,
                'exp_year' =>  $card->exp_year,
                'cvc' =>  $card->cvc,
                'type' => 'card'
            ];
        } else {
            $stripePaymentinput = [
                'card_number' => $request->card_number,
                'exp_month' =>  $request->exp_month,
                'exp_year' =>  $request->exp_year,
                'cvc' =>  $request->cvc,
                'type' => 'card'
            ];
        }

        $paymentMethodResponse = $this->checkPaymentMethod($stripePaymentinput);

        // Create payment method using Stripe
        if (!$paymentMethodResponse['status']) {
            $response = [
                'status' => false,
                'error' => $paymentMethodResponse['error'],
            ];
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        $paymentId = $paymentMethodResponse['paymentId'];

        // Define metadata
        $metadata = [
            'cause' => 'wallet recharge',
            'account_id' => $request->account_id
            // Add more metadata fields as needed
        ];

        // Create payment intent for the recharge transaction
        $transactionId = $this->stripeController->createPaymentIntent($amount, $paymentId, $metadata);

        // If transaction is successful
        if ($transactionId) {

            if (!$request->has('card_id')) {
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
                $card = $cardController->saveCard($request->account_id, $cardInput);
            }

            return $this->dispatchAfterPayment($amount, $paymentId, $transactionId, $request, $card);
        } else {
            // common server error
            commonServerError();
        }
    }

    /**
     * Recharge account with fresh payment details.
     *
     * This function validates incoming request data, processes payment method validation,
     * creates a payment intent, adds billing address and optionally saves card details,
     * and dispatches tasks after a successful payment.
     *
     * @param Illuminate\Http\Request $request The HTTP request object.
     * @return \Illuminate\Http\JsonResponse
     */
    public function rechargeWithFreshDetails(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'name' => 'string|required|min:5',
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
                'save_card' => 'boolean',
                'amount' => 'required|numeric|between:0,9999999.99'
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // Return a 403 Forbidden response with validation errors
            $response = [
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Validate email format using custom function
        if (!is_valid_email($request->email)) {
            // Return a 404 Not Found response if email format is invalid
            $response = [
                'status' => false,
                'message' => 'Invalid email format'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare payment method input for Stripe
        $stripePaymentInput = $request->only(['card_number', 'exp_month', 'exp_year', 'cvc', 'type']);
        $paymentMethodResponse = $this->checkPaymentMethod($stripePaymentInput);

        // Check payment method validation response
        if (!$paymentMethodResponse['status']) {
            // Return a 403 Forbidden response with payment method validation error            
            $response = [
                'status' => false,
                'error' => $paymentMethodResponse['error'],
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve payment ID from payment method validation response
        $paymentId = $paymentMethodResponse['paymentId'];

        // Define metadata for payment intent        
        $metadata = [
            'cause' => 'wallet recharge',
            'account_id' => $request->account_id
            // Add more metadata fields as needed
        ];

        // Create payment intent for the recharge transaction
        $transactionId = $this->stripeController->createPaymentIntent($request->amount, $paymentId, $metadata);

        // Check if payment intent creation was successful
        if ($transactionId) {
            // Add billing address
            $billingAddressController = new BillingAddressController();
            $billingResult = $billingAddressController->addData($request->account_id, $request->only(['fullname', 'contact_no', 'email', 'address', 'zip', 'city', 'state', 'country']));

            // Save card details if requested
            $cardInput = [
                'name' => $request->name,
                'card_number' => $request->card_number,
                'exp_month' => $request->exp_month,
                'exp_year' => $request->exp_year,
                'cvc' => $request->cvc,
            ];

            if ($request->save_card) {
                $cardInput['save_card'] = 1;
            }

            $cardController = new CardController();
            $card = $cardController->saveCard($request->account_id, $cardInput);

            // Dispatch tasks after successful payment
            return $this->dispatchAfterPayment($request->amount, $paymentId, $transactionId, $request, $card);
        } else {
            // Handle common server error if payment intent creation failed
            return commonServerError();
        }
    }

    /**
     * Check and create payment method using Stripe.
     *
     * This function interacts with Stripe through a controller to create a payment method
     * and handles the response accordingly.
     *
     * @param Illuminate\Http\Request $request The HTTP request containing payment method details.
     * @return array An array containing the status of the operation and relevant data.
     */
    protected function checkPaymentMethod($request)
    {
        // Create payment method using Stripe        
        $paymentMethodResponse = $this->stripeController->createPaymentMethod($request);

        // Extract content from response
        $paymentMethodContent = $paymentMethodResponse->getContent();
        $responseData = json_decode($paymentMethodContent, true);

        // Check for errors in the response
        if (isset($responseData['error'])) {
            // Handle the error case
            $response = [
                'status' => false,
                'error' => $responseData['error'],
            ];

            return $response;
        } else {
            // Handle the success case
            $paymentMethodSuccess = $responseData['success'];

            $response = [
                'status' => true,
                'paymentId' => $paymentMethodSuccess['id'], // Assuming 'id' is the key for payment ID
            ];

            return $response;
        }
    }

    /**
     * Dispatch tasks after successful payment.
     *
     * This function performs several actions after a successful payment:
     * - Adds a payment record.
     * - Updates the account balance.
     * - Sends an email to the account holder with invoice details.
     * - Returns a success response.
     *
     * @param float $amount The amount of the payment.
     * @param string $paymentId The ID of the payment.
     * @param string $transactionId The transaction ID associated with the payment.
     * @param Illuminate\Http\Request $request The HTTP request object.
     * @param object $card The card object associated with the payment.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function dispatchAfterPayment($amount, $paymentId, $transactionId, $request, $card)
    {
        // Add payment record
        $payment = $this->addPayment($request->address_id, $request->account_id, $card->id, $amount, $transactionId);

        // Update account balance
        $accountController = new AccountController($this->stripeController);
        $accountResult = $accountController->addOrUpdateBalance($request->account_id, $amount, $paymentId, $transactionId);

        // Retrieve account details
        $account = Account::find($request->account_id);

        // Prepare data for email notification
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

        // Prepare success response
        $type = config('enums.RESPONSE.SUCCESS'); // Response type (success)
        $status = true; // Operation status (success)
        $msg = 'You have added balance successfully'; // Success message

        // Return a JSON response with HTTP status code 200 (OK)
        return responseHelper($type, $status, $msg, Response::HTTP_OK);
    }
}
