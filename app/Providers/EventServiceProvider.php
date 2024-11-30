<?php

namespace App\Providers;

use App\Events\Account\AccountCredentialCreated;
use App\Events\AccountCreated;
use App\Events\CallRecorded;
use App\Events\ChannelHangupComplete;
use App\Events\Conference;
use App\Events\FreeswitchEvent;
use App\Events\FreeSwitchShutDown;
use App\Events\FsCallEvent;
use App\Events\ExtensionRegistration;
use App\Listeners\Account\AccountCredentialCreatedNotification;
use App\Listeners\ChannelHangupCompleteListener;
use App\Listeners\ConferenceListner;
use App\Listeners\FreeswitchListner;
use App\Listeners\FreeSwitchShutDownListener;
use App\Listeners\FsCallListener;
use App\Listeners\SendAccountCreatedNotification;
use App\Listeners\ExtensionRegistrationListner;
use App\Listeners\HandleCallRecording;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        FreeswitchEvent::class => [
            FreeswitchListner::class
        ],

        ExtensionRegistration::class => [
            ExtensionRegistrationListner::class,
        ],

        FreeSwitchShutDown::class => [
            FreeSwitchShutDownListener::class,
        ],

        FsCallEvent::class => [
            FsCallListener::class,
        ],

        ChannelHangupComplete::class => [
            ChannelHangupCompleteListener::class
        ],

        CallRecorded::class => [
            HandleCallRecording::class
        ],

        Conference::class => [
            ConferenceListner::class
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
