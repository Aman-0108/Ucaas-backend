<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\NewUser;
use App\Jobs\WalletRecharge;
use App\Mail\NewUserMail;
use App\Mail\RechargeSuccessfull;
use App\Models\Account;
use App\Models\BillingAddress;
use App\Models\CardDetail;
use App\Models\Lead;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\TransactionDetail;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    // All Payments
    public function index(Request $request)
    {
        $payments = Payment::with(['paymentDetails']);
        // $payments = Payment::query();

        // Check if the request contains an 'account_id' parameter
        if ($request->has('account_id')) {
            // If 'account' parameter is provided, filter domains by account ID
            $payments->where('account_id', $request->account_id);
        }

        // COMING FROM GLOBAL CONFIG
        $ROW_PER_PAGE = config('globals.PAGINATION.ROW_PER_PAGE');

        // Execute the query to fetch domains
        $payments = $payments->orderBy('id', 'desc')->paginate($ROW_PER_PAGE);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $payments,
            'message' => 'Successfully fetched.'
        ];

        // Return a JSON response containing the list of domains
        return response()->json($response, Response::HTTP_OK);
    }

    // payment for new account
    public function paymentForNewAccount(Request $request)
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

        $paymentMode = 'card';
        $transaction_type = 'debit';
        $payment_gateway = '';

        // check peyment gateway options
        $checkGateway = checkPaymentGateway();

        if (!$checkGateway) {
            return response()->json([
                'status' => false,
                'error' => 'Payment Gateway configuration error'
            ], 400);
        }

        $transactionId = '';

        if ($checkGateway == 'Stripe') {

            $payment_gateway = 'Stripe';

            $stripe = App::make(StripeController::class);

            // Create payment method using Stripe
            $paymentMethodResponse = $stripe->createPaymentMethod($stripePaymentinput);

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
            $transactionId = $stripe->createPaymentIntent($amount, $paymentId, $metadata);
        }

        // If transaction is successful
        if ($transactionId) {

            DB::beginTransaction();

            $lead->company_status = 1;
            // Remove unnecessary properties from the lead object
            unset($lead->id, $lead->created_at, $lead->updated_at);

            // Add Account
            $accountController = new AccountController();
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

            $description = 'New Package ordered.';

            // Add Payments
            $payment = $this->addPayment($transaction_type, $payment_gateway, $billingResult, $accountId, $card, $paymentMode, $amount, $transactionId, $description, $package->subscription_type);

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
            NewUser::dispatch($userCredentials);

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
     * Initiates wallet recharge process.
     *
     * @param Request $request The HTTP request containing recharge details.
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure.
     */
    public function walletRecharge(Request $request)
    {
        $userId = $request->user()->id;
        // Define metadata for the transaction
        $metadata = [
            'cause' => 'wallet recharge',
            'account_id' => $request->account_id
            // Add more metadata fields as needed
        ];

        $description = 'Wallet balance added';

        $paymentMode = 'card';
        $transaction_type = 'debit';

        $payment_gateway = checkPaymentGateway();

        if (!$payment_gateway) {
            return response()->json([
                'status' => false,
                'error' => 'Payment Gateway configuration error'
            ], 400);
        }

        $amount = $request->amount;

        $paymentResponse = $this->pay($request, $metadata);

        // Extract content from response
        $paymentResponse = $paymentResponse->getContent();
        $responseData = json_decode($paymentResponse, true);

        // If transaction is successful
        if ($responseData['status']) {

            $walletDataDetail = [
                'created_by'  => $userId,
                'account_id' => $request->account_id,
                'amount' => $amount,
                'transaction_type' => 'credit',
                'invoice_url'  => null,
                'descriptor'  => $description,
            ];

            WalletTransaction::create($walletDataDetail);

            $transactionId = $responseData['transactionId'];

            if ($request->has('card_id')) {
                // card details
                $card = CardDetail::where(['id' => $request->card_id, 'account_id' => $request->account_id, 'cvc' => $request->cvc])->first();
            } else {
                $card = [
                    'name' => $request->name,
                    'card_number' => $request->card_number,
                    'exp_month' => $request->exp_month,
                    'exp_year' => $request->exp_year,
                    'cvc' => $request->cvc,
                ];

                $card = json_decode(json_encode($card));
            }

            if ($request->has('address_id')) {
                $billingAddress = BillingAddress::find($request->address_id);
            } else {
                $billingAddresInputs = [
                    'fullname' => $request->fullname,
                    'contact_no' => $request->contact_no,
                    'email' => $request->email,
                    'address' => $request->address,
                    'zip' => $request->zip,
                    'city' => $request->city,
                    'state' => $request->state,
                    'country' => $request->country
                ];

                $billingAddress = json_decode(json_encode($billingAddresInputs));
            }

            // Add payment record
            $payment = $this->addPayment($transaction_type, $payment_gateway, $billingAddress, $request->account_id, $card, $paymentMode, $amount, $transactionId, $description);

            // Update account balance
            $accountController = new AccountController();
            $accountController->addOrUpdateBalance($request->account_id, $amount, $payment['id'], $transactionId);

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
        } else {
            // Handle common server error if transaction fails            
            if ($responseData['error']) {
                return response()->json([
                    'satus' => false,
                    'error' => $responseData['error']
                ], 400);
            } else {
                return commonServerError();
            }
        }
    }

    public function pay(Request $request, $metadata)
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
                    Rule::exists('billing_addresses', 'id')->where(function ($query) use ($request) {
                        $query->where('id', $request->address_id)
                            ->where('account_id', $request->account_id);
                    })
                ],
                'fullname' => 'required_unless:address_id,' . $request->address_id . '|string',
                'contact_no' => 'required_unless:address_id,' . $request->address_id . '|string',
                'email' => 'required_unless:address_id,' . $request->address_id . '|string',
                'address' => 'required_unless:address_id,' . $request->address_id . '|string',
                'zip' => 'required_unless:address_id,' . $request->address_id . '|string',
                'city' => 'required_unless:address_id,' . $request->address_id . '|string',
                'state' => 'required_unless:address_id,' . $request->address_id . '|string',
                'country' => 'required_unless:address_id,' . $request->address_id . '|string',
                'amount' => 'required|numeric|between:0,9999999.99',
                'cvc' => [
                    'required',
                    'digits:3',
                ],
                'save_card' => 'required_unless:card_id,' . $request->card_id . '|boolean',
                'paymentfor' => 'string'
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {

            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = $validator->errors();

            return responseHelper($type, $status, $msg, Response::HTTP_FORBIDDEN);
        }

        // Validate CVV if card_id is present
        if ($request->has('card_id')) {
            $card = CardDetail::where(['id' => $request->card_id, 'cvc' => $request->cvc])->first();

            if (!$card) {
                $type = config('enums.RESPONSE.ERROR'); // Response type (error)
                $status = false; // Operation status (failed)
                $msg = 'CVV is invalid.'; // Detailed error messages

                // Return CVV validation error response
                return responseHelper($type, $status, $msg, Response::HTTP_BAD_REQUEST);
            } else {
                // Prepare payment input based on card_id presence
                $paymentInput = [
                    'card_number' => $card->card_number,
                    'exp_month' =>  $card->exp_month,
                    'exp_year' =>  $card->exp_year,
                    'cvc' =>  $card->cvc,
                    'type' => 'card'
                ];
            }
        } else {
            $paymentInput = [
                'card_number' => $request->card_number,
                'exp_month' =>  $request->exp_month,
                'exp_year' =>  $request->exp_year,
                'cvc' =>  $request->cvc,
                'type' => 'card'
            ];
        }

        // Extract input data for Stripe payment
        $amount = $request->amount;

        // check peyment gateway options
        $checkGateway = checkPaymentGateway();

        if (!$checkGateway) {
            return response()->json([
                'status' => false,
                'error' => 'Payment Gateway configuration error'
            ], 400);
        }

        $transactionId = '';

        if ($checkGateway == 'Stripe') {

            $stripe = App::make(StripeController::class);

            // Create payment method using Stripe
            $paymentMethodResponse = $this->checkPaymentMethod($paymentInput);

            // Handle payment method creation failure
            if (!$paymentMethodResponse['status']) {
                $response = [
                    'status' => false,
                    'error' => $paymentMethodResponse['error'],
                ];
                return response()->json($response, Response::HTTP_FORBIDDEN);
            }

            $paymentId = $paymentMethodResponse['paymentId'];

            // Create payment intent for the recharge transaction
            $transactionId = $stripe->createPaymentIntent($amount, $paymentId, $metadata);
        }

        // end

        if (empty($transactionId)) {
            $response = [
                'status' => false,
                'error' => 'something went wrong with payment configuration.',
            ];
            return response()->json($response);
        }

        // If card is not saved, save it
        if (!$request->has('card_id')) {
            $cardInput = [
                'name' => $request->name,
                'card_number' => $request->card_number,
                'exp_month' => $request->exp_month,
                'exp_year' => $request->exp_year,
                'cvc' => $request->cvc,
            ];

            // Optionally save card based on user input
            if ($request->save_card == 1) {
                $cardInput['save_card'] = 1;
            }

            // Save card details using CardController
            $cardController = new CardController();
            $card = $cardController->saveCard($request->account_id, $cardInput);
        }

        // If address is new
        if (!$request->has('address_id')) {
            $billingAddresInputs = [
                'fullname' => $request->fullname,
                'contact_no' => $request->contact_no,
                'email' => $request->email,
                'address' => $request->address,
                'zip' => $request->zip,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country
            ];

            // $billingAddress = json_decode(json_encode($billingAddresInputs));
            $billingAddressController = new BillingAddressController();
            $billingAddressController->addData($request->account_id, $billingAddresInputs);
        }

        $response = [
            'status' => true,
            'transactionId' => $transactionId,
            'message' => 'Payment successfull.'
        ];

        return response()->json($response);
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
    public function addPayment($transaction_type, $payment_gateway, $billingAddress, $accountId, $card, $paymentMode, $amount, $transactionId, $description = null, $subscriptionType = null)
    {
        // Record transaction details in the database
        $inputData = [
            'account_id' => $accountId,
            'amount_total' => $amount,
            'amount_subtotal' => $amount,
            'stripe_session_id' => $transactionId,
            'transaction_id' => $transactionId,
            'payment_gateway' => $payment_gateway,
            'transaction_type' => $transaction_type,
            'subscription_type' => $subscriptionType,
            'payment_method_options' => $paymentMode,
            'currency' => 'usd',
            'payment_status' => 'completed',
            'transaction_date' => date("Y-m-d H:i:s"),
            'description' => $description
            // 'invoice_url' => $pdfUrl
        ];

        // Create a new payment record
        $payment = Payment::create($inputData);

        $transactionDetails = [
            'payment_id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'amount_total' => $payment->amount_total,
            'amount_subtotal' => $payment->amount_subtotal,
            'payment_status' => $payment->payment_status,
            'transaction_date' => $payment->transaction_date,
            'name' => $card->name,
            'card_number' => $card->card_number,
            'exp_month' => $card->exp_month,
            'exp_year' => $card->exp_year,
            'cvc' => $card->cvc,
            'fullname' => $billingAddress->fullname,
            'contact_no' => $billingAddress->contact_no,
            'email' => $billingAddress->email,
            'address' => $billingAddress->address,
            'zip' => $billingAddress->zip,
            'city' => $billingAddress->city,
            'state' => $billingAddress->state,
            'country' => $billingAddress->country,
            'description' => $description,
            'payment_mode' => $paymentMode,

        ];

        // Create a new transaction detail entry using the TransactionDetail model.       
        TransactionDetail::create($transactionDetails);

        // Generate Invoice
        $invoice = new InvoiceController();
        $invoiceData = $invoice->generateInvoice($transactionId);

        // Update payment record with invoice URL
        $payment->invoice_url = $invoiceData['pdfPath'];
        $payment->save();

        // Return the newly created payment object
        return $payment;
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
        // check peyment gateway options
        $checkGateway = checkPaymentGateway();

        if (!$checkGateway) {
            $response = [
                'status' => false,
                'error' => 'Payment gateway is not set properly.'
            ];

            return $response;
        }

        if ($checkGateway == 'Stripe') {
            $stripe = App::make(StripeController::class);
            // Create payment method using Stripe        
            $paymentMethodResponse = $stripe->createPaymentMethod($request);

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
        } else {
            $response = [
                'status' => false,
                'error' => 'Payment Gateway configuration error on payment method.'
            ];

            return $response;
        }
    }
}
