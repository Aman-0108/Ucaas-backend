<?php

namespace App\Providers;

use App\Services\FreeSwitchService;
use Illuminate\Support\ServiceProvider;
use App\Services\SSHService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(SSHService::class, function ($app) {
            return new SSHService(
                config('services.ssh.host'),
                config('services.ssh.username'),
                config('services.ssh.password')
            );
        });

        $this->app->singleton(FreeSwitchService::class, function ($app) {
            return new FreeSwitchService(
                config('services.freeswitch.host'),
                config('services.freeswitch.port'),
                config('services.freeswitch.password')
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
