<?php

namespace App\Providers;

use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class StripeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(StripeClient::class, function ($app) {

            $gateway = PaymentGateway::where('status', 'active')->first();

            $secret = ($gateway) ? $gateway->api_secret : config('services.stripe.api_secret');

            return new StripeClient($secret);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
