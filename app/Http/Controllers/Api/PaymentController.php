<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

// use Stripe\Subscription;
use Stripe\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
        $paymentResponse = $this->stripeController->createPaymentMethod($input);

        $paymentContent = $paymentResponse->getContent();
        $responseData = json_decode($paymentContent, true);

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

            $accountId = $account->id;

            $this->createUser($account);

            $this->saveCard($request, $accountId);

            $payment = $this->addPayment($accountId, $amount, $transactionId, $package->subscription_type);

            $this->createSubscription($package, $transactionId, $accountId);

            DB::commit();

            // Send mail to account holder with invoice
            // Mail::to($account->email)->send(new SendInvoice($invoiceData));

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

    public function createAccount($lead)
    {
        unset($lead->id, $lead->created_at, $lead->updated_at);

        $lead = $lead->toArray();

        $account = Account::create($lead);

        $account->company_status = 'Payment Completed';
        $account->save();

        return $account;
    }

    public function createUser($account)
    {
        $parts = explode('@', $account->email);
        $name = $parts[0]; // 'test'

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

        User::create($userCredentials);
    }

    public function saveCard($request, $accountId)
    {
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

    public function createSubscription($package, $transactionId, $accountId)
    {
        $currentDate = Carbon::now();

        if ($package->subscription_type == 'monthly') {
            $endDate = $currentDate->addMonth()->format('Y-m-d H:i:s');
        }

        if ($package->subscription_type == 'annually') {
            $endDate = $currentDate->addYear()->format('Y-m-d H:i:s');
        }

        // subscription insert
        $subscriptionData = [
            'transaction_id' => $transactionId,
            'account_id' => $accountId,
            'package_id' => $package->id,
            'start_date' => date("Y-m-d H:i:s"),
            'end_date' => $endDate,
            'status' => 'active'
        ];

        Subscription::create($subscriptionData);
    }

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

        $payment = Payment::create($inputData);

        // Generate Invoice
        $invoice = new InvoiceController();
        $invoiceData = $invoice->generateInvoice($transactionId);

        $payment->invoice_url = $invoiceData['pdfPath'];
        $payment->save();

        return $payment;
    }
}
