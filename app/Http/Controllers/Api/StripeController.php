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
use Stripe\Webhook;

class StripeController extends Controller
{
    protected $stripe;

    public function __construct(StripeClient $stripe)
    {
        $this->stripe = $stripe;
    }

    // live
    public function createPaymentMethod($request)
    {
        try {
            $inputData = [
                'type' => 'card',
                'card' => [
                    'number' => $request['card_number'],
                    'exp_month' => $request['exp_month'],
                    'exp_year' => $request['exp_year'],
                    'cvc' => $request['cvc'],
                    //'token' => 'tok_visa'
                ],
            ];

            $paymentMethod = $this->stripe->paymentMethods->create($inputData);

            return response()->json(['success' => $paymentMethod], 200);
        } catch (\Exception $e) {
            // Handle error
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // live
    public function createPaymentIntent($amount, $paymentId, $metadata)
    {
        $paymentData = [
            'confirm' => true,
            'amount' => $amount * 100, // Amount in cents
            'currency' => 'usd',
            'payment_method' => $paymentId,
            'automatic_payment_methods' => [
                'enabled' => 'true',
                'allow_redirects' => 'never'
            ],
            'metadata' => $metadata,
        ];

        $intent = $this->stripe->paymentIntents->create($paymentData);

        return $intent->id;
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
            'restrictions' => ['completed_sessions' => ['limit' => 1]],
            // 'after_completion' => [
            //     'type' => 'redirect',
            //     'redirect' => ['url' => url('payment-success')],
            // ]
        ]);

        return $paymentLinks;
    }

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
        echo 'accepted';
        Log::info($request->all());
    }

    public function paymentCancel(Request $request)
    {
        echo 'cancel';
        Log::info($request->all());
    }

    public function createPaymentIntentForClient($type, $accountId, $amount)
    {
        // Create a Payment Intent
        $paymentIntent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'usd',
            // 'metadata' => $metadata,
            // Add more parameters as needed
        ]);

        return $paymentIntent->client_secret;
    }

    // other
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
}
