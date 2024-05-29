<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Stripe\PaymentIntent;
use Stripe\StripeClient;
use Stripe\Stripe;
use Stripe\Token;
use Stripe\Webhook;

class StripeControllerc extends Controller
{
    protected $stripe;

    public function __construct(StripeClient $stripe)
    {
        $this->stripe = $stripe;
    }


    // create product
    public function createProduct(Request $request)
    {
        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string',
                'nickname' => 'required|string',
                'unit_amount' => 'required|numeric'
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

        // Create the product
        $product = $this->stripe->products->create([
            'name' => $request->input('name'),
            'type' => 'service', // Use 'service' for one-time purchase products
        ]);

        // Create the price
        $price = $this->stripe->prices->create([
            'unit_amount' => $request->input('unit_amount'), // Price in cents
            'currency' => 'usd',
            'product' => $product->id, // Product ID
            'nickname' => $request->input('nickname'), // Optional: nickname for the price
        ]);

        $response = [
            'status' => true,
            'product' => $product,
            'price' => $price
        ];

        return response()->json($response, 200);
    }

    public function createPaymentMethod(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET_T'));
        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
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

        try {
            $paymentMethod = $this->stripe->paymentMethods->create([
                'type' => 'card',
                'card' => [
                    'number' => $request->card_number,
                    'exp_month' => $request->exp_month,
                    'exp_year' => $request->exp_year,
                    'cvc' => $request->cvc,
                ],
            ]);

            // $paymentMethod = Token::create([
            //     'card' => [
            //         'number' => '4242424242424242',
            //         'exp_month' => 12,
            //         'exp_year' => 2024,
            //         'cvc' => '123',
            //     ],
            // ]);

            return response()->json(['payment_method' => $paymentMethod]);
        } catch (\Exception $e) {
            // Handle error
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createCustomer(Request $request)
    {
        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string',
                'email' => 'required|email'
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

        // Retrieve the validated input
        $validated = $validator->validated();

        try {
            $customer = $this->stripe->customers->create($validated);

            // Prepare a success response 
            $response = [
                'status' => true,
                'data' => $customer,
                'message' => 'Success'
            ];

            // Return a JSON response  with status(200)
            return response()->json($response, Response::HTTP_OK);
        } catch (\Exception $e) {
            // Handle error
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createPlan(Request $request)
    {
        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'amount' => 'required|numeric',
                'currency' => 'required|string',
                'interval' => 'required',
                'interval_count' => 'required|numeric',
                'nickname' => 'required',
                'name' => 'required'
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

        try {
            $plan = $this->stripe->plans->create([
                'amount' => $request->amount * 100,
                'currency' => $request->currency,
                'interval' => $request->interval,
                'interval_count' => $request->interval_count,
                'product' => [
                    'name' => $request->name,
                ],
                'nickname' => $request->nickname,
            ]);

            return response()->json(['plan' => $plan]);
        } catch (\Exception $e) {
            // Handle error
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createSubscription(Request $request)
    {
        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'customer' => 'required',
                'price' => 'required|string',
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

        try {
            $subscription = $this->stripe->subscriptions->create([
                'customer' => $request->customer,
                'items' => [
                    ['price' => $request->price], // Price ID obtained from Stripe dashboard
                ],
            ]);

            return response()->json(['subscription' => $subscription]);
        } catch (\Exception $e) {
            // Handle error
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createPaymentIntent()
    {
        // Set your secret API key
        Stripe::setApiKey(env('STRIPE_SECRET_T'));

        $intent = PaymentIntent::create([
            'customer' => 'cus_Q6tqUlzgWyTx6z',
            // 'setup_future_usage' => 'off_session',
            'confirm' => true,
            'amount' => 800, // Amount in cents
            'currency' => 'usd',
            'payment_method' => 'pm_card_threeDSecureOptional',
            // 'payment_method' => 'pm_1PGfMsSCkaZRbc5C9fgErKE4',
            'automatic_payment_methods' => [
                'enabled' => 'true',
                'allow_redirects' => 'never'
            ],
            // Add additional parameters as needed
        ]);

        $payment_intent_id = $intent->id;
        // $payment_intent_id = 'pi_3PGcq0SCFDXQWXZU0FKGsIyb';

        // try {
        //     $intent = PaymentIntent::retrieve($payment_intent_id);

        //     // Confirm the PaymentIntent to initiate another payment attempt       
        //     $intent->confirm();

        //     return response()->json($intent);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => $th->getMessage()
        //     ], Response::HTTP_INTERNAL_SERVER_ERROR);
        // }
    }

    // create payment link (live)

    public function createPaymentLink($userId, $packageId)
    {
        $package = Package::find($packageId);

        $packageName = $package->name;
        $price = $package->offer_price;
        $description = $package->description;

        // Create the product
        $product = $this->stripe->products->create([
            'name' => $packageName,
            'type' => 'service', // Use 'service' for one-time purchase products
        ]);

        // Create the price
        $price = $this->stripe->prices->create([
            'unit_amount' => intval($price), // Price in cents
            'currency' => 'usd',
            'product' => $product->id, // Product ID
            'nickname' => '', // Optional: nickname for the price
        ]);

        $paymentLinks = $this->stripe->paymentLinks->create([
            'line_items' => [
                [
                    'price' => $price->id,
                    'quantity' => 1,
                ],
            ],
            'metadata' => [
                'account_id' => $userId,
                'description' => $description
            ],
            // 'after_completion' => [
            //     'type' => 'redirect',
            //     'redirect' => ['url' => url('payment-success')],
            // ]
        ]);

        return $paymentLinks;
    }

      // live (one time use link)
    // public function createPaymentLink($userId, $packageId)
    // {
    //     Stripe::setApiKey(env('STRIPE_SECRET_T'));

    //     $package = Package::find($packageId);

    //     $packageName = $package->name;
    //     $price = $package->offer_price;
    //     $description = $package->description;

    //     // Create the product
    //     $product = $this->stripe->products->create([
    //         'name' => $packageName,
    //         'type' => 'service', // Use 'service' for one-time purchase products
    //     ]);

    //     // Create the price
    //     $price = $this->stripe->prices->create([
    //         'unit_amount' => intval($price), // Price in cents
    //         'currency' => 'usd',
    //         'product' => $product->id, // Product ID
    //         'nickname' => '', // Optional: nickname for the price
    //     ]);     

    //     $session = Session::create([
    //         'payment_method_types' => ['card'],
    //         'line_items' => [
    //             [
    //                 'price' => $price->id,
    //                 'quantity' => 1,
    //             ],
    //         ],
    //         'mode' => 'payment',
    //         'success_url' => route('payment.success'),
    //         // 'cancel_url' => route('payment.cancel'),
    //         // 'payment_intent_data' => [
    //         //     'description' => 'Your Payment Description',
    //         // ],
    //         'metadata' => [
    //             'account_id' => $userId,
    //             'description' => $description
    //         ],
    //     ]);

    //     return $session;
    // }

    public function handleWebhook(Request $request)
    {
        // Set your Stripe API key
        // Stripe::setApiKey(config('services.stripe.secret'));
        $endpoint_secret = 'whsec_eb64345e2b7b56a56c43857484bbe856b0009299daf48361c50a7f3f0d0913df';
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info($event);
        // Handle the event based on its type
        switch ($event->type) {
            case 'checkout.session.completed':
                // Handle successful payment event
                $session = $event->data->object;
                // Process the successful payment, update your database, etc.
                break;
                // Add more cases for other event types if needed
        }

        return response()->json(['status' => 'success']);
    }

    // redirect after payment Success
    public function paymentSuccess(Request $request)
    {
        Log::info($request->all());
        echo 'accepted';
    }
}
