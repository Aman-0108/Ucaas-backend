<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SendInvoice;
use App\Models\Account;
use App\Models\CardDetail;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Customer;
// use Stripe\Subscription;
use Stripe\PaymentMethod;
use Carbon\Carbon;

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
        // Decrypt the encrypted ID
        if (isset($request->account_id)) {
            $accountId = Crypt::decrypt($request->account_id);
            // Modify the request data to set account_id to 1
            $request->merge(['account_id' => $accountId]);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'string|required|min:5',
                'package_id' => 'required|exists:packages,id',
                'account_id' => 'required|numeric|exists:accounts,id',
                // 'amount' => 'required|numeric|between:0,9999999.99',
                'type' => 'required|string|max:20',
                'card_number' => 'required|numeric|digits_between:14,16',
                'exp_month' => 'required|digits_between:1,2|numeric|between:1,12',
                'exp_year' => 'required|numeric|digits:4',
                'cvc' => 'required|numeric|digits_between:3,4',
                'transaction_type' => 'required|string',
                'subscription_type' => 'required|in:annually,monthly'
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

        // Find the account by ID
        $account = Account::find($accountId);

        // Check if the account exists
        if (!$account) {
            // If the account is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Account not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $package = Package::find($account->package_id);

        if ($request->package_id != $package->id) {
            // If the account is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'No package found for this account.'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $amount = intval($package->offer_price);

        $input = $request->only(['card_number', 'exp_month', 'exp_year', 'cvc', 'type']);

        // Update or create a record based on the provided conditions
        CardDetail::updateOrCreate(
            ['account_id' => $accountId, 'card_number' => $request->card_number], // Conditions to check if the record exists
            [
                'name' => $request->name,
                'exp_month' => $request->exp_month,
                'exp_year' => $request->exp_year,
                'cvc' => $request->cvc
            ] // Data to update or create
        );

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

        // Create payment intent for the recharge transaction
        $transactionId = $this->stripeController->createPaymentIntent($accountId, $amount, $paymentId);

        // If transaction is successful
        if ($transactionId) {
            // Record transaction details in the database
            $accountInput = [
                'account_id' => $accountId,
                'amount_total' => $amount,
                'amount_subtotal' => $amount,
                'stripe_session_id' => $transactionId,
                'transaction_id' => $transactionId,
                'payment_gateway' => 'Stripe',
                'transaction_type' => $request->transaction_type,
                'subscription_type' => $request->subscription_type,
                'payment_method_options' => 'card',
                'currency' => 'usd',
                'payment_status' => 'complete',
                'transaction_date' => date("Y-m-d H:i:s"),
                // 'invoice_url' => $pdfUrl
            ];

            $payment = Payment::create($accountInput);

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
                'account_id' => $account->id,
                'package_id' => $package->id,
                'start_date' => date("Y-m-d H:i:s"),
                'end_date' => $endDate,
                'status' => 'active'
            ];

            Subscription::create($subscriptionData);

            // Generate Invoice
            $invoice = new InvoiceController();
            $invoiceData = $invoice->generateInvoice($transactionId);

            // Send mail to account holder with invoice
            // Mail::to($account->email)->send(new SendInvoice($invoiceData));

            $payment->invoice_url = $invoiceData['pdfPath'];
            $payment->save();

            // update account's payment_url 
            $account->payment_url = null;
            $account->save();

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

    // public function paymentProcess(Request $request)
    // {
    //     require 'vendor/autoload.php';
    //     Stripe::setApiKey(env('STRIPE_SECRET'));
    //     \Stripe\Stripe::setApiKey("sk_test_51PEmi7SCkaZRbc5C8GZwiHKsEvB1CJZDFeocYcbeWWEyh8f5wLYGF7F9J9gdCnciFHsGyhMZzCx1rOmFLzuBBI1z00H5M2JEp9");


    //     //create payment method
    //     $paymentmethod = \Stripe\PaymentMethod::create([
    //         'type' => 'card',
    //         'card' => [
    //             'number' => '4242424242424242',
    //             'exp_month' => 8,
    //             'exp_year' => 2026,
    //             'cvc' => '314',
    //         ],
    //     ]);
    //     echo $paymentmethodId = $paymentmethod->id;
    //     exit;

    //     $paymentIntent = \Stripe\PaymentIntent::create([
    //         'amount' => $request->amount,
    //         'currency' => 'usd',
    //         'payment_method' => $paymentmethodId,
    //         // 'automatic_payment_methods' => ['enabled' => true],
    //         'automatic_payment_methods' => [
    //             'enabled' => 'true',
    //             'allow_redirects' => 'never'
    //         ],
    //         //'automatic_payment_methods' => ['allow_redirects' => 'never'],
    //         // 'confirm' => true,
    //     ]);

    //     // print_r($paymentIntent); exit;

    //     $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntent->id);
    //     // $paymentIntent->capture();

    //     $paymentIntent->confirm();

    //     return response()->json($paymentIntent);
    //     //$paymentIntent->client_secret($paymentIntent->client_secret);
    //     // client_secret' => $paymentIntent->client_secret
    //     // return response()->json($paymentIntent);

    //     // $token = \Stripe\Token::create([
    //     //     'card' => [
    //     //         'number' => $request->card_number,
    //     //         'exp_month' => $request->expiry_month,
    //     //         'exp_year' => $request->expiry_year,
    //     //         'cvc' => $request->cvc,
    //     //     ],
    //     // ]);
    //     // $gettoken = $token->id;

    //     // try {
    //     //     $charge = \Stripe\Charge::create([
    //     //         'amount' => 1000, 
    //     //         'currency' => 'inr',
    //     //         'source' => $gettoken, 
    //     //         'description' => 'Example Charge',
    //     //         'capture' => false,
    //     //     ]);

    //     //     print_r($charge); exit;
    //     //     //return redirect()->back()->with('success', 'Payment Successful');
    //     // } catch (\Exception $ex) {
    //     //     print_r($ex->getMessage()); exit;
    //     //     //return redirect()->back()->with('error', $ex->getMessage());
    //     // }
    // }


    // public function subscriptionProcess(Request $request)
    // {
    //     // Set your Stripe API Key
    //     Stripe::setApiKey(env('STRIPE_SECRET'));

    //     $token = \Stripe\Token::create([
    //         'card' => [
    //             'number' => $request->card_number,
    //             'exp_month' => $request->expiry_month,
    //             'exp_year' => $request->expiry_year,
    //             'cvc' => $request->cvc,
    //         ],
    //     ]);

    //     $gettoken = $token->id;

    //     // Get the authenticated user
    //     //$user = Auth::user();

    //     // Create a new customer in Stripe
    //     $customer = Customer::create([
    //         'email' => 'azharislam21@gmail.com',
    //         'source' => $gettoken, // Assuming user has a default payment method
    //     ]);

    //     $plan = \Stripe\Product::create(array(
    //         "name" => "planABC",
    //     ));

    //     $price = \Stripe\Price::create([
    //         'product' => $plan->id, // ID of the product to associate the price with
    //         'unit_amount' => $request->amount, // Amount in cents (or the smallest currency unit)
    //         'currency' => 'usd', // Currency (e.g., USD, EUR)
    //         'recurring' => ['interval' => 'month'], // Interval of the subscription (e.g., month, year)
    //         'nickname' => 'Standard Monthly', // Name of the price
    //     ]);

    //     // Access the created price object
    //     //echo 'Price created: ' . $price->id;

    //     // Create a subscription for the customer
    //     $subscription = Subscription::create([
    //         'customer' => $customer->id,
    //         'items' => [
    //             [
    //                 'price' => $price->id, // Stripe Price ID for the selected plan
    //             ],
    //         ],
    //     ]);

    //     //create payment method
    //     $paymentmethod = \Stripe\PaymentMethod::create([
    //         'type' => 'card',
    //         'card' => [
    //             'number' => '4242424242424242',
    //             'exp_month' => 8,
    //             'exp_year' => 2026,
    //             'cvc' => '314',
    //         ],
    //     ]);
    //     $paymentmethodId = $paymentmethod->id;

    //     $paymentIntent = \Stripe\PaymentIntent::create([
    //         'amount' => $request->amount, // Amount in cents
    //         'currency' => 'usd',
    //         'automatic_payment_methods' => ['enabled' => 1],
    //         'automatic_payment_methods' => ['allow_redirects' => 'never'],
    //         'confirm' => true,
    //         'payment_method' => $paymentmethodId,
    //         'customer' => $customer->id,
    //     ]);





    //     print_r($paymentIntent);
    //     exit;


    //     // $stripe = new \Stripe\StripeClient('sk_test_51PEmi7SCkaZRbc5C8GZwiHKsEvB1CJZDFeocYcbeWWEyh8f5wLYGF7F9J9gdCnciFHsGyhMZzCx1rOmFLzuBBI1z00H5M2JEp9');
    //     // $stripe->paymentIntents->confirm(
    //     //     'pi_3MtweELkdIwHu7ix0Dt0gF2H',
    //     //     [
    //     //       'payment_method' => 'pm_card_visa',
    //     //       'return_url' => 'https://www.example.com',
    //     //     ]
    //     //   );

    //     //   Confirm the PaymentIntent
    //     //   $paymentIntentconf = \Stripe\PaymentIntent::retrieve($paymentIntent->id);
    //     //   $paymentIntentconf['payment_method'] = $paymentmethodId;
    //     //   $paymentIntentconf['return_url'] = 'https://www.google.com';
    //     //   $paymentIntentconf->confirm();

    //     $stripepaymentintentconf = new \Stripe\StripeClient('sk_test_51PEmi7SCkaZRbc5C8GZwiHKsEvB1CJZDFeocYcbeWWEyh8f5wLYGF7F9J9gdCnciFHsGyhMZzCx1rOmFLzuBBI1z00H5M2JEp9');
    //     $paymentIntConf = $stripepaymentintentconf->paymentIntents->confirm(
    //         $paymentIntent->id,
    //         [
    //             'payment_method' => $paymentmethodId,
    //             'return_url' => 'https://www.example.com',
    //             // 'automatic_payment_methods' => 'enabled',
    //         ]
    //     );

    //     print_r($paymentIntConf);
    //     // exit;  print_r($paymentmethod); 
    //     // exit;

    // }
}
