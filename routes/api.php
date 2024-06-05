<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AccountDetailsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CallCentreController;
use App\Http\Controllers\Api\ChannelHangupController;
use App\Http\Controllers\Api\DialplanController;
use App\Http\Controllers\Api\DomainController;
use App\Http\Controllers\Api\ExtensionController;
use App\Http\Controllers\Api\FeatureController;
use App\Http\Controllers\Api\FollowmeController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\FreeSwitchController;
use App\Http\Controllers\Api\GatewayController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\InboundRoutingController;
use App\Http\Controllers\Api\OutboundRoutingController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\PaymentGatewayController;
use App\Http\Controllers\Api\RinggroupController;
use App\Http\Controllers\Api\RinggroupdestinationController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\Sofia\SipProfileController;
use App\Http\Controllers\Api\Sofia\SipProfileDomainController;
use App\Http\Controllers\Api\Sofia\SipProfileSettingController;
use App\Http\Controllers\Api\Sofia\SofiaGlobalSettingController;
use App\Http\Controllers\Api\TimezoneController;
use App\Http\Controllers\Api\UidController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\StripeController;
use App\Http\Controllers\Api\CommioController;
use App\Http\Controllers\Api\TfnController;
use App\Http\Controllers\Api\DidRateController;
use App\Http\Controllers\Api\DidVendorController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\StripeControllerc;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/', function (Request $request) {
    return response()->json('unauthenticated', 401);
})->name('login');

// Guest mode
Route::group(['middleware' => 'guest'], function () {
    // All Packages
    Route::get('packages-all', [PackageController::class, 'index']);
    Route::get('free/package/details/{id}', [PackageController::class, 'show']);

    // All timezones
    Route::get('timezones/{Account?}', [TimezoneController::class, 'index']);

    Route::controller(LeadController::class)->group(function () {
        // To get all the leads
        Route::get('leads', 'index');

        // To create new lead 
        Route::post('lead-store', 'store');
    });

    // User Auth
    Route::controller(AuthController::class)->group(function () {
        Route::group(['prefix' => 'auth'], function () {
            Route::post('login', 'login');
            Route::post('register', 'register');
        });

        Route::post('/forgot-password', 'sendResetLinkEmail')->name('password.request');
        Route::post('verifyOTP', 'verifyOTP');
        // Route::post('reset-password','reset')->name('password.reset');
    });

    // Payment Controller
    Route::controller(PaymentController::class)->group(function () {
        Route::post('pay', 'pay')->name('company.pay');
    });

    // Invoice
    Route::controller(InvoiceController::class)->group(function () {
        Route::post('generate-invoice', 'generateInvoicet')->name('generate.invoice');
    });
});

// Only for account
Route::middleware(['auth:sanctum', 'company'])->group(function () {
    Route::group(['prefix' => 'auth'], function () {
        Route::get('account', [AccountController::class, 'account']);
        Route::get('account-logout', [AccountController::class, 'logout']);
    });
});

Route::group(['middleware' => ['auth:sanctum']], function () {

    // Auth
    Route::controller(AuthController::class)->group(function () {
        // To logout the authenticated user
        Route::get('logout', 'logout');

        // To get the authenticated user
        Route::get('user', 'user');

        // Change Password
        Route::post('change-password', 'changePassword');

        // Reset Password
        Route::post('reset-password', 'reset');
    });

    // User
    Route::controller(UserController::class)->group(function () {
        // search
        Route::get('user/search/{query?}', 'search');

        // To create user
        Route::post('user/create', 'create');

        // To check username is available or not
        Route::post('check/username', 'checkUserName');

        // All users
        Route::get('user/all', 'users');

        // Get user Data by Id
        Route::get('user/{id}', 'show');

        // To update the particular user by Id
        Route::put('user/{id}', 'update');
    });

    // Role
    Route::controller(RoleController::class)->group(function () {
        // To get all the roles
        Route::get('roles', 'index');

        Route::middleware(['adminOrCompany'])->group(function () {
            // To store new role
            Route::post('role/store', 'store');

            // To update the particular role by Id
            Route::put('role/{id}', 'update');

            // To destroy the role by Id
            Route::delete('role/{id}', 'destroy');
        });

        // To get the particular role by Id
        Route::get('role/{id}', 'show');
    });

    // Domain
    Route::controller(DomainController::class)->group(function () {
        // search
        Route::get('domain/search/{query?}', 'search');

        // To get all the domians
        Route::get('domains/{Account?}', 'index');

        // To store new domain
        Route::post('domain/store', 'store');

        // To get the particular domian by Id
        Route::get('domain/{id}', 'show');

        // To update the particular domain by Id
        Route::put('domain/{id}', 'update');

        // To destroy the domain by Id
        Route::delete('domain/{id}', 'destroy');
    });

    // Gateway
    Route::controller(GatewayController::class)->group(function () {
        // To get all the gateways
        Route::get('gateways', 'index');

        // To store new gateway
        Route::post('gateway/store', 'store');

        // To get the particular gateway by Id
        Route::get('gateway/{id}', 'show');

        // To update the particular gateway by Id
        Route::put('gateway/{id}', 'update');

        // To destroy the getway by Id
        Route::delete('gateway/{id}', 'destroy');
    });

    // Groups
    Route::controller(GroupController::class)->group(function () {
        // To get all the groups
        Route::get('groups', 'index');

        // To store new group
        Route::post('group/store', 'store');

        // To get the particular group by Id
        Route::get('group/{id}', 'show');

        // To update the particular group by Id
        Route::put('group/{id}', 'update');

        // To destroy the group by Id
        Route::delete('group/{id}', 'destroy');
    });

    // Timezone
    Route::controller(TimezoneController::class)->group(function () {
        // To get all the timezones
        Route::get('auth/timezones/{Account?}', 'index');

        // To store new timezone
        Route::post('timezone/store', 'store');

        // To get the particular timezone by Id
        Route::get('timezone/{id}', 'show');

        // To update the particular timezone by Id
        Route::put('timezone/{id}', 'update');

        // To destroy the timezone by Id
        Route::delete('timezone/{id}', 'destroy');
    });

    // UID
    Route::controller(UidController::class)->group(function () {
        // To get all the uids
        Route::get('uids', 'index');

        // To store new Uid
        Route::post('uid/store', 'store');

        // To get the particular Uid by Id
        Route::get('uid/{id}', 'show');

        // To update the particular UId by Id
        Route::put('uid/{id}', 'update');

        // To destroy the Uid by Id
        Route::delete('uid/{id}', 'destroy');
    });

    // Account
    Route::controller(AccountController::class)->group(function () {
        // To get all the accounts
        Route::get('accounts', 'index');

        // To store new account
        Route::post('account/store', 'store');

        // To get the particular account by Id
        Route::get('account/{id}', 'show');

        // To update the particular account by Id
        Route::put('account/{id}', 'update');

        // To destroy the account by Id
        Route::delete('account/{id}', 'destroy');

        // document verification
        Route::post('document-verify', 'postDocumentVerify');

        // Payment Verification
        Route::post('payment-verify', 'postPaymentVerify');
    });

    // Account Details
    Route::controller(AccountDetailsController::class)->group(function () {

        Route::middleware(['company'])->group(function () {
            // To store new account
            Route::post('account-detail/store', 'store');

            // To update the particular account details by Id
            Route::post('account-detail/update', 'update');

            // To destroy the account by Id
            Route::delete('account-detail/destroy/{id}', 'destroy');
        });

        // To get all the accounts
        Route::get('account-details', 'index');

        // To get the particular account by Id
        Route::get('account-detail/account/{id}', 'show');
    });

    // Extension
    Route::controller(ExtensionController::class)->group(function () {
        // search
        Route::get('extension/search/{query?}', 'search');

        // To get all the accounts
        Route::get('extensions', 'index');

        // To store new account
        Route::post('extension/store', 'store');

        // To store new account
        Route::post('extension/assign', 'assign');

        // To get the particular extension by Id
        Route::get('extension/{id}', 'show');

        // To update the particular extension by Id
        Route::put('extension/{id}', 'update');
    });

    Route::controller(RinggroupController::class)->group(function () {
        Route::get('ringgroup', 'index');
        Route::post('ringgroup/store', 'store');
        Route::get('ringgroup/{id}', 'show');
        Route::put('ringgroup/{id}', 'update');
    });

    Route::controller(RinggroupdestinationController::class)->group(function () {
        // Route::post('ringgroupdestination/storeDestination', 'store');
        Route::delete('ringgroupdestination/{id}', 'destroy');
    });

    // Follow Me controller associated with extension
    Route::controller(FollowmeController::class)->group(function () {
        // To store new details
        Route::post('extension/details/store', 'store');

        // To update the particular details by Id
        // Route::put('extension/details/{id}', 'update');

        // To destroy the details by Id
        Route::delete('extension/details/{id}', 'destroy');
    });

    // Dialplan
    Route::controller(DialplanController::class)->group(function () {
        // To get all the dialplans
        Route::get('dialplans', 'index');

        // To store new dialplan
        Route::post('dialplan/store', 'store');

        // To get the particular dialplan by Id
        Route::get('dialplan/{id}', 'show');

        // To update the particular dialplan by Id
        Route::put('dialplan/{id}', 'update');

        // To destroy the dialplan by Id
        Route::delete('dialplan/{id}', 'destroy');
    });

    // Sip Profile
    Route::controller(SipProfileController::class)->group(function () {
        // To get all the sip profiles
        Route::get('sip-profile', 'index');

        // To store new sip profiles
        //  Route::post('sip-profile/store', 'store');

        // To get the particular sip profile by Id
        Route::get('sip-profile/{id}', 'show');

        // To update the particular sip profile by Id
        //  Route::put('sip-profile/{id}', 'update');

        // To destroy the sip profile by Id
        Route::delete('sip-profile/{id}', 'destroy');
    });

    // Sip Profile Domain
    Route::controller(SipProfileDomainController::class)->group(function () {
        // To get all the sip profile domain
        Route::get('sip-profile-domain', 'index');

        // To store new sip profile domain
        Route::post('sip-profile-domain/store', 'store');

        // To get the particular sip profile domain by Id
        Route::get('sip-profile-domain/{id}', 'show');

        // To update the particular sip profile domain by Id
        Route::put('sip-profile-domain/{id}', 'update');

        // To destroy the sip profile domain by Id
        Route::delete('sip-profile-domain/{id}', 'destroy');
    });

    // Sip Profile Settings
    Route::controller(SipProfileSettingController::class)->group(function () {
        // To get all the sip profile settings
        Route::get('sip-profile-settings', 'index');

        // To store new sip profile settings
        //  Route::post('sip-profile-settings/store', 'store');

        // To get the particular sip profile settings by Id
        Route::get('sip-profile-settings/{id}', 'show');

        // To update the particular sip profile settings by Id
        //  Route::put('sip-profile-settings/{id}', 'update');

        // To destroy the sip profile settings by Id
        Route::delete('sip-profile-settings/{id}', 'destroy');
    });

    // Sofia Global settings
    Route::controller(SofiaGlobalSettingController::class)->group(function () {
        // To get all the sofia global settings
        Route::get('sofia-global-settings', 'index');

        // To store new sofia global settings
        Route::post('sofia-global-settings/store', 'store');

        // To get the particular sofia global settings by Id
        Route::get('sofia-global-settings/{id}', 'show');

        // To update the particular sofia global settings by Id
        Route::put('sofia-global-settings/{id}', 'update');

        // To destroy the sofia global settings by Id
        Route::delete('sofia-global-settings/{id}', 'destroy');
    });

    // Inbound Routing
    Route::controller(InboundRoutingController::class)->group(function () {
        // To get all the inbound routing
        Route::get('inbound/routings', 'index');

        // To store new inbound routing
        Route::post('inbound/routing/store', 'store');

        // To get the particular inbound routing by Id
        Route::get('inbound/routing/{id}', 'show');

        // To update the particular inbound routing by Id
        Route::put('inbound/routing/{id}', 'update');

        // To destroy the inbound routing by Id
        Route::delete('inbound/routing/{id}', 'destroy');
    });

    // Outbound Routing
    Route::controller(OutboundRoutingController::class)->group(function () {
        // To get all the outbound routing
        Route::get('outbound/routings', 'index');

        // To store new outbound routing
        Route::post('outbound/routing/store', 'store');

        // To get the particular outbound routing by Id
        Route::get('outbound/routing/{id}', 'show');

        // To update the particular outbound routing by Id
        Route::put('outbound/routing/{id}', 'update');

        // To destroy the outbound routing by Id
        Route::delete('outbound/routing/{id}', 'destroy');
    });

    // Freeswitch
    Route::controller(FreeSwitchController::class)->group(function () {
        Route::get('freeswitch/status', 'status');
        Route::get('freeswitch/sofiaStatus', 'sofiaStatus');
        Route::get('freeswitch/showRegistrations', 'showRegistrations');
        Route::get('freeswitch/reloadacl', 'reloadacl');
        Route::get('freeswitch/reloadXml', 'reloadXml');
        Route::post('freeswitch/call', 'call');
        // Route::get('freeswitch/shutDown', 'shutDown');
    });

    // CDR
    Route::controller(ChannelHangupController::class)->group(function () {
        // To get all the cdrs
        Route::get('cdr', 'index');

        Route::get('call-details', 'callDetailsByUserId');

        Route::get('call-record-file-download', 'getFileByPath');
    });

    // Package
    Route::controller(PackageController::class)->group(function () {
        // To get all the packages
        Route::get('packages', 'index');

        // To store new package
        Route::post('package/store', 'store');

        // To get the particular package by Id
        Route::get('package/details/{id}', 'show');

        // To update the particular package by Id
        Route::put('package/{id}', 'update');

        // To destroy the package by Id
        Route::delete('package/{id}', 'destroy');
    });

    // Feature
    Route::controller(FeatureController::class)->group(function () {
        // To get all the feature
        Route::get('features', 'index');

        // To store new feature
        Route::post('feature/store', 'store');

        // To get the particular feature by Id
        Route::get('feature/details/{id}', 'show');

        // To update the particular feature by Id
        Route::put('feature/update/{id}', 'update');

        // To destroy the feature by Id
        Route::delete('feature/destroy/{id}', 'destroy');
    });

    // Payment Gateway
    Route::controller(PaymentGatewayController::class)->group(function () {
        // To get all the payment-gateways
        Route::get('payment-gateways', 'index');

        // To store new payment-gateway
        Route::post('payment-gateway/store', 'store');

        // To get the particular payment-gateway by Id
        Route::get('payment-gateway/details/{id}', 'show');

        // To update the particular payment-gateway by Id
        Route::put('payment-gateway/update/{id}', 'update');

        // To destroy the payment-gateway by Id
        Route::delete('payment-gateway/destroy/{id}', 'destroy');
    });

    // Payment Controller
    Route::controller(PaymentController::class)->group(function () {
        Route::get('all-payments', 'index');
    });

    // DID Related
    Route::group(['prefix' => 'did'], function () {
        // Vendor
        Route::controller(DidVendorController::class)->group(function () {
            Route::get('vendors', 'index');
            Route::post('vendor/store', 'store');
            Route::put('vendor/update/{id}', 'update');
            Route::delete('vendor/destroy/{id}', 'destroy');
            Route::get('vendor/show/{id}', 'show');
        });

        // Rate Chart
        Route::controller(DidRateController::class)->group(function () {
            Route::get('rates', 'index');
            Route::post('rate/store', 'store');
            Route::put('rate/update/{id}', 'update');
            Route::delete('rate/destroy/{id}', 'destroy');
            Route::get('rate/show/{id}/{rateType}', 'show');
        });
    });
});

Route::controller(CallCentreController::class)->group(function () {
    Route::get('call-center-queues', 'index');
    Route::post('call-center-queue/store', 'store');
    Route::put('call-center-queue/update/{id}', 'update');

    Route::delete('call-center-agent/destroy/{id}', 'callCentreAgentDelete');
});

// Stripe
Route::controller(StripeController::class)->group(function () {
    Route::post('create-customer', 'createCustomer')->name('create.stripe.customer');
    Route::post('create-product', 'createProduct')->name('create.stripe.product');
    Route::post('/create-plan', 'createPlan')->name('create.stripe.plan');
    Route::post('/create-subscription', 'createSubscription');
    Route::post('/create-payment-method', 'createPaymentMethod');
    Route::get('/createPaymentIntent', 'createPaymentIntent');
    Route::get('/create-payment-link', 'createPaymentLinks');
    Route::post('/stripe/webhook', 'handleWebhook');
    Route::get('/payment-success', 'paymentSuccess')->name('payment.success');
    Route::get('/payment-cancel', 'paymentcancel')->name('payment.cancel');
});

/*
|--------------------------------------------------------------------------
| API Routes For External things
|--------------------------------------------------------------------------
|
|
*/

// Freeswitch Block
Route::get('/freeswitch/dir/list', [ExtensionController::class, 'directories'])->name('dir.pbx.all');
Route::get('/freeswitch/dir/add', [ExtensionController::class, 'addDirectory'])->name('dir.pbx.add');
Route::get('/freeswitch/dir/remove', [ExtensionController::class, 'removeDirectory'])->name('dir.pbx.remove');
Route::get('/freeswitch/xml', [ExtensionController::class, 'checkCallStatus'])->name('dir.pbx.xml');
Route::post('/freeswitch/events', [UserController::class, 'events'])->name('pbx.events');


Route::controller(PaymentController::class)->group(function () {
    Route::post('stripeProcess', 'paymentProcess');
    Route::post('subscriptionProcess', 'subscriptionProcess');
});

Route::controller(CommioController::class)->group(function () {
    Route::post('searchDid', 'searchDid_commio');
});

Route::controller(TfnController::class)->group(function () {
    Route::post('getActiveDidVendor', 'getActiveDidVendor');
    Route::post('searchTfn', 'searchTfn');
    Route::post('purchaseTfn', 'purchaseTfn');
});



// Route::get('/ws', [UserController::class, 'socket']);
