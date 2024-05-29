<?php

namespace App\Providers;

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
            return new StripeClient(env('STRIPE_SECRET_T'));
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
