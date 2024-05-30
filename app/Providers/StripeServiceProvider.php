<?php

namespace App\Providers;

use App\Models\PaymentGateway;
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

            $secret = '';

            if ($gateway) {
                $secret = $gateway->api_secret;
            } else {
                $secret = config('srevices.stripe.api_secret');
            }

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
