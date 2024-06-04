<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SendInvoice;
use App\Models\Account;
use App\Models\CardDetail;
use App\Models\Lead;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Illuminate\Support\Facades\Validator;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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

        // Find the lead by ID
        $lead = Lead::find($request->lead_id);

        // Check if the lead exists
        if (!$lead) {
            // If the lead is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Lead not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $package = Package::find($lead->package_id);

        $amount = intval($package->offer_price);

        $input = $request->only(['card_number', 'exp_month', 'exp_year', 'cvc', 'type']);

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

        $leadId = $lead->id;

        // Create payment intent for the recharge transaction
        $transactionId = $this->stripeController->createPaymentIntent($leadId, $amount, $paymentId);

        // If transaction is successful
        if ($transactionId) {

            DB::beginTransaction();

            $account = $this->createAccount($lead);

            $user = $this->createUser($account);

            $accountId = $account->id;            

            $this->saveCard($request, $accountId);

            $payment = $this->addPayment($accountId, $amount, $transactionId, $package->subscription_type);

            $this->createSubscription($package, $transactionId, $accountId);

            DB::commit();

            // Send mail to account holder with invoice
            Mail::to($user->email)->send(new SendInvoice($invoiceData));

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
     * Creates a new account using lead information.
     * @param $lead object The lead object containing information for creating the account.
     * @return Account The newly created account object with company_status set to 'Payment Completed'.
     */
    public function createAccount($lead)
    {
        // Remove unnecessary properties from the lead object
        unset($lead->id, $lead->created_at, $lead->updated_at);

        // Convert the lead object to an array
        $lead = $lead->toArray();

        // Create a new account using lead information
        $account = Account::create($lead);

        // Set the company_status to '1'
        $account->company_status = '1';

        // Save changes
        $account->save();

        // Return the newly created account
        return $account;
    }

    /**
     * Creates a new user associated with the provided account.
     *
     * This function extracts the name and email from the account's email address,
     * generates user credentials based on the account information, and then creates a new user.
     * The user's password is hashed using the company name of the account.
     *
     * @param $account object The account object to associate the new user with.
     * @return void
     */
    public function createUser($account)
    {
        // Extract name from email address
        $parts = explode('@', $account->email);
        $name = $parts[0]; // 'test'

        // Generate user credentials
        $userCredentials = [
            'name' => $name,
            'email' => $account->email,
            'username' => $account->company_name,
            'password' => Hash::make($account->company_name),
            'timezone_id' => $account->timezone_id,
            'status' => 'E',
            'usertype' => 'Company',
            'socket_status' => 'offline',
            'account_id' => $account->id
        ];

        // Create a new user with the generated credentials
        $user = User::create($userCredentials);
        
        return $user;
    }

    /**
     * Saves card details associated with an account.
     *
     * This function updates or creates a new record in the CardDetail model based on the provided request data.
     * 
     * @param $request Illuminate\Http\Request The request object containing card details.
     * @param $accountId int The ID of the account associated with the card details.
     * @return void
     */
    public function saveCard($request, $accountId)
    {
        // Update or create card details based on account_id and card_number
        CardDetail::updateOrCreate(
            ['account_id' => $accountId, 'card_number' => $request->card_number], // Conditions to check if the record exists
            [
                'name' => $request->name,
                'exp_month' => $request->exp_month,
                'exp_year' => $request->exp_year,
                'cvc' => $request->cvc
            ] // Data to update or create
        );
    }

    /**
     * Creates a new subscription for an account based on the provided package information.
     *
     * This function generates subscription data based on the package type (monthly or annually),
     * sets the start date to the current date and calculates the end date accordingly.
     * It then creates a new subscription record associated with the provided account and package.
     *
     * @param $package object The package object containing subscription type and other details.
     * @param $transactionId int|string The ID of the transaction associated with the subscription.
     * @param $accountId int The ID of the account for which the subscription is created.
     * @return void
     */
    public function createSubscription($package, $transactionId, $accountId)
    {
        // Get current date
        $currentDate = Carbon::now();

        // Calculate end date based on package subscription type
        if ($package->subscription_type == 'monthly') {
            $endDate = $currentDate->addMonth()->format('Y-m-d H:i:s');
        } elseif ($package->subscription_type == 'annually') {
            $endDate = $currentDate->addYear()->format('Y-m-d H:i:s');
        }

        // Subscription data to be inserted
        $subscriptionData = [
            'transaction_id' => $transactionId,
            'account_id' => $accountId,
            'package_id' => $package->id,
            'start_date' => date("Y-m-d H:i:s"),
            'end_date' => $endDate,
            'status' => 'active'
        ];

        // Create a new subscription record
        Subscription::create($subscriptionData);
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
    public function addPayment($accountId, $amount, $transactionId, $subscriptionType)
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
}
