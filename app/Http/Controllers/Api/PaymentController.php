<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\WalletRecharge;
use App\Mail\NewUserMail;
use App\Mail\RechargeSuccessfull;
use App\Models\Account;
use App\Models\BillingAddress;
use App\Models\CardDetail;
use App\Models\Lead;
use App\Models\Package;
use App\Models\Payment;
use App\Models\TransactionDetail;
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

            $description = 'New Package ordered.';

            // Add Payments
            $payment = $this->addPayment($billingResult, $accountId, $card, $paymentMode, $amount, $transactionId, $description, $package->subscription_type);

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
     * Initiates wallet recharge process.
     *
     * @param Request $request The HTTP request containing recharge details.
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure.
     */
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
                if ($card->default) {
                    // Prepare payment input based on card_id presence
                    $stripePaymentinput = [
                        'card_number' => $card->card_number,
                        'exp_month' =>  $card->exp_month,
                        'exp_year' =>  $card->exp_year,
                        'cvc' =>  $card->cvc,
                        'type' => 'card'
                    ];
                } else {
                    $type = config('enums.RESPONSE.ERROR'); // Response type (error)
                    $status = false; // Operation status (failed)
                    $msg = 'Your card is inactive.'; // Detailed error messages

                    // Return CVV validation error response
                    return responseHelper($type, $status, $msg, Response::HTTP_BAD_REQUEST);
                }
            }
        } else {
            $stripePaymentinput = [
                'card_number' => $request->card_number,
                'exp_month' =>  $request->exp_month,
                'exp_year' =>  $request->exp_year,
                'cvc' =>  $request->cvc,
                'type' => 'card'
            ];
        }

        // Extract input data for Stripe payment
        $amount = $request->amount;

        $paymentMode = 'card';

        // Create payment method using Stripe
        $paymentMethodResponse = $this->checkPaymentMethod($stripePaymentinput);

        // Handle payment method creation failure
        if (!$paymentMethodResponse['status']) {
            $response = [
                'status' => false,
                'error' => $paymentMethodResponse['error'],
            ];
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        $paymentId = $paymentMethodResponse['paymentId'];

        // Define metadata for the transaction
        $metadata = [
            'cause' => ($request->has('paymentfor')) ? 'New Did Buy' : 'wallet recharge',
            'account_id' => $request->account_id
            // Add more metadata fields as needed
        ];

        // Create payment intent for the recharge transaction
        $transactionId = $this->stripeController->createPaymentIntent($amount, $paymentId, $metadata);

        // If transaction is successful
        if ($transactionId) {
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

            $description = ($request->has('paymentfor')) ? 'New Did Buy' : 'Wallet balance added';

            $billingAddress = BillingAddress::find($request->address_id);

            if (!$request->has('paymentfor')) {
                return $this->dispatchAfterPayment($billingAddress, $amount, $paymentId, $transactionId, $request, $card, $paymentMode, $description);
            }

            return $this->dispatchAfterDidPayment($billingAddress, $amount, $paymentId, $transactionId, $request, $card, $paymentMode, $description);
        } else {
            // Handle common server error if transaction fails
            return commonServerError();
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

        $paymentMode = 'card';

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

            $request->merge(['address_id' => $billingResult->id]);

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

            $description = 'Wallet balance added';

            // Dispatch tasks after successful payment
            return $this->dispatchAfterPayment($billingResult, $request->amount, $paymentId, $transactionId, $request, $card, $paymentMode, $description);
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
    protected function dispatchAfterPayment($billingAddress, $amount, $paymentId, $transactionId, $request, $card, $paymentMode, $description)
    {
        // Add payment record
        $payment = $this->addPayment($billingAddress, $request->account_id, $card, $paymentMode, $amount, $transactionId, $description);

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
    public function addPayment($billingAddress, $accountId, $card, $paymentMode, $amount, $transactionId, $description = null, $subscriptionType = null)
    {
        // Record transaction details in the database
        $inputData = [
            'account_id' => $accountId,
            'amount_total' => $amount,
            'amount_subtotal' => $amount,
            'stripe_session_id' => $transactionId,
            'transaction_id' => $transactionId,
            'payment_gateway' => 'Stripe',
            'transaction_type' => 'new',
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

    protected function dispatchAfterDidPayment($billingAddress, $amount, $paymentId, $transactionId, $request, $card, $paymentMode, $description)
    {
        // Add payment record
        $payment = $this->addPayment($billingAddress, $request->account_id, $card, $paymentMode, $amount, $transactionId, $description);

        // Prepare success response
        $type = config('enums.RESPONSE.SUCCESS'); // Response type (success)
        $status = true; // Operation status (success)
        $msg = 'success'; // Success message
        $data = $payment;

        // mail send 

        // Return a JSON response with HTTP status code 200 (OK)
        return responseHelper($type, $status, $msg, Response::HTTP_OK, $data);
    }
}
