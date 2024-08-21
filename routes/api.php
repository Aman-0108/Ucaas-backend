<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AccountDetailsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingAddressController;
use App\Http\Controllers\Api\CallCentreController;
use App\Http\Controllers\Api\CallRateController;
use App\Http\Controllers\Api\CardController;
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
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DiddetailsController;
use App\Http\Controllers\Api\TfnController;
use App\Http\Controllers\Api\DidRateController;
use App\Http\Controllers\Api\DidVendorController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\SoundController;
use App\Http\Controllers\Api\StripeControllerc;
use App\Http\Controllers\Api\WalletTransactionController;
use App\Http\Controllers\S3Controller;
use App\Http\Controllers\UtilityController;
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
    Route::get('timezones', [TimezoneController::class, 'index']);

    // To create new lead 
    Route::post('lead-store', [LeadController::class, 'store']);

    // To create new payment 
    Route::post('pay', [PaymentController::class, 'paymentForNewAccount']);

    // To generate invoice
    Route::post('generate-invoice', [InvoiceController::class, 'generateInvoice']);

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
        Route::prefix('user')->middleware('permission')->name('user.')->group(function () {
            // search
            Route::get('search/{query?}', 'search')->name('search');

            // To create user
            Route::post('create', 'create')->name('add');

            // All users
            Route::get('all', 'users')->name('browse');

            // Get user Data by Id
            Route::get('{id}', 'show')->name('read');

            // To update the particular user by Id
            Route::put('{id}', 'update')->name('edit');
        });

        // To check username is available or not
        Route::post('check/username', 'checkUserName');
    });

    // Account
    Route::controller(AccountController::class)->group(function () {

        Route::prefix('account')->middleware('permission')->name('account.')->group(function () {
            // To get all the accounts
            Route::get('all', 'index')->name('browse');

            // To get the particular account by Id
            Route::get('{id}', 'show')->name('read');

            // To update the particular account by Id
            Route::put('{id}', 'update')->name('edit');

            // To store new account
            Route::post('store', 'store')->name('add');

            // To destroy the account by Id
            Route::delete('{id}', 'destroy')->name('delete');
        });

        // Payment Verification
        Route::post('payment-verify', 'postPaymentVerify');

        // document verification
        Route::post('document-verify', 'postDocumentVerify');
    });

    // Billing Address
    Route::controller(BillingAddressController::class)->group(function () {
        Route::prefix('billing-address')->middleware('permission')->name('billingaddress.')->group(function () {
            // To get all the addresses
            Route::get('all', 'index')->name('browse');

            // To get the particular address by Id
            Route::get('show/{id}', 'show')->name('read');

            // To update the particular address by Id
            Route::put('update/{id}', 'update')->name('edit');

            // To store new address
            Route::post('store', 'store')->name('add');

            // To destroy the address by Id
            Route::delete('destroy/{id}', 'destroy')->name('delete');
        });

        // Set status
        Route::post('billing-address/set-default-address', 'setDefault');
    });

    // Card Controller
    Route::controller(CardController::class)->group(function () {
        Route::prefix('card')->middleware('permission')->name('carddetail.')->group(function () {
            // To list all cards
            Route::get('all', 'index')->name('browse');

            // Set status
            Route::post('set-default-card', 'setDefault')->name('edit');

            // To add a new card 
            Route::post('add', 'create')->name('add');

            // To destroy the card by Id
            Route::delete('destroy/{id}', 'destroy')->name('delete');
        });
    });

    // Role
    Route::controller(RoleController::class)->group(function () {
        Route::prefix('role')->middleware('permission')->name('role.')->group(function () {
            // To get all the roles
            Route::get('all', 'index')->name('browse');

            // To get the particular role by Id
            Route::get('{id}', 'show')->name('read');

            // To update the particular role by Id
            Route::put('{id}', 'update')->name('edit');

            // To store new role
            Route::post('store', 'store')->name('add');

            // To destroy the role by Id
            Route::delete('{id}', 'destroy')->name('delete');
        });
    });

    // Domain
    Route::controller(DomainController::class)->group(function () {
        Route::prefix('domain')->middleware('permission')->name('domain.')->group(function () {
            // search
            Route::get('search/{query?}', 'search')->name('search');

            // To get all the domians
            Route::get('domains/{Account?}', 'index')->name('browse');

            // To store new domain
            Route::post('store', 'store')->name('add');

            // To get the particular domian by Id
            Route::get('{id}', 'show')->name('read');

            // To update the particular domain by Id
            Route::put('{id}', 'update')->name('edit');

            // To destroy the domain by Id
            Route::delete('{id}', 'destroy')->name('delete');
        });
    });

    // Gateway
    Route::controller(GatewayController::class)->group(function () {
        Route::prefix('gateway')->middleware('permission')->name('gateway.')->group(function () {
            // To get all the gateways
            Route::get('all', 'index')->name('browse');

            // To get the particular gateway by Id
            Route::get('{id}', 'show')->name('read');

            // To update the particular gateway by Id
            Route::put('{id}', 'update')->name('edit');

            // To store new gateway
            Route::post('store', 'store')->name('add');

            // To destroy the getway by Id
            Route::delete('{id}', 'destroy')->name('delete');
        });
    });

    // Timezone
    Route::controller(TimezoneController::class)->group(function () {
        Route::prefix('timezone')->middleware('permission')->name('timezone.')->group(function () {
            // To get all the timezones
            Route::get('all/{Account?}', 'index')->name('browse');

            // To get the particular timezone by Id
            Route::get('{id}', 'show')->name('read');

            // To update the particular timezone by Id
            Route::put('{id}', 'update')->name('edit');

            // To store new timezone
            Route::post('store', 'store')->name('add');

            // To destroy the timezone by Id
            Route::delete('{id}', 'destroy')->name('delete');
        });
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
        Route::prefix('extension')->middleware('permission')->name('extension.')->group(function () {
            // search
            Route::get('search/{query?}', 'search')->name('search');

            // To get all the accounts
            Route::get('all', 'index')->name('browse');

            // To store new account
            Route::post('assign', 'assign');

            // To get the particular extension by Id
            Route::get('{id}', 'show')->name('read');

            // To update the particular extension by Id
            Route::put('{id}', 'update')->name('edit');

            // To store new account
            Route::post('store', 'store')->name('add');
        });
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
        Route::prefix('dialplan')->middleware('permission')->name('dialplan.')->group(function () {
            // To get all the dialplans
            Route::get('all', 'index')->name('browse');

            // To get the particular dialplan by Id
            Route::get('{id}', 'show')->name('read');

            // To update the particular dialplan by Id
            Route::put('{id}', 'update')->name('edit');

            // To store new dialplan
            Route::post('store', 'store')->name('add');

            // To destroy the dialplan by Id
            Route::delete('{id}', 'destroy')->name('delete');
        });
    });

    // Sip Profile
    Route::controller(SipProfileController::class)->group(function () {
        Route::prefix('sip-profile')->middleware('permission')->name('sipprofile.')->group(function () {
            // To get all the sip profiles
            Route::get('all', 'index')->name('browse');

            // To get the particular sip profile by Id
            Route::get('{id}', 'show')->name('read');

            // To update the particular sip profile by Id
            //  Route::put('{id}', 'update')->name('edit');

            // To store new sip profiles
            //  Route::post('store', 'store')->name('add');

            // To destroy the sip profile by Id
            Route::delete('{id}', 'destroy')->name('delete');
        });
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
        Route::prefix('freeswitch')->group(function () {
            // check running status
            Route::get('status', 'status');

            // Allows to get the status of the sofia
            Route::get('sofiaStatus', 'sofiaStatus');

            // Allows to get the registrations
            Route::get('showRegistrations', 'showRegistrations');

            // Allows to reload the ACL
            Route::get('reloadacl', 'reloadacl');

            // Allows to reload the XML config of FreeSwitch
            Route::get('reloadXml', 'reloadXml');

            // Make a call
            Route::post('call', 'call');

            // kill a call
            Route::get('call-kill/{uuid}', 'callKill');

            // barge a call
            Route::get('call-barge/{uuid}', 'barge');

            // eavesdrop a call
            Route::get('call-eavesdrop/{uuid}', 'eavesdrop');

            // intercept a call
            Route::get('call-intercept/{uuid}', 'intercept');

            // hangup a call
            // Route::get('freeswitch/shutDown', 'shutDown');

            // check active extension on server
            Route::get('checkActiveExtensionOnServer', 'checkActiveExtensionOnServer');
        });
    });

    // CDR
    Route::controller(ChannelHangupController::class)->group(function () {
        // To get all the cdrs
        Route::get('cdr', 'index');

        // To get the particular call details by user Id
        Route::get('call-details', 'callDetailsByUserId');

        // To download the call record file
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
        Route::get('payments/all', 'index');
        Route::post('wallet-recharge', 'walletRecharge');
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

        // DID 
        Route::controller(DiddetailsController::class)->group(function () {
            Route::get('all', 'index');
            Route::delete('destroy/{id}', 'destroy');
        });
    });

    Route::controller(TfnController::class)->group(function () {
        Route::post('getActiveDidVendor', 'getActiveDidVendor');
        Route::post('searchTfn', 'searchTfn');
        Route::post('purchaseTfn', 'purchaseTfn');
    });

    Route::controller(WalletTransactionController::class)->group(function () {
        Route::get('transaction/wallet', 'index');
    });

    // Audio related
    Route::controller(SoundController::class)->group(function () {
        Route::prefix('sound')->middleware('permission')->name('sound.')->group(function () {
            // To get all the accounts
            Route::get('all', 'index')->name('browse');

            // To get the particular account by Id
            Route::get('{id}', 'show')->name('read');

            // To update the particular account by Id
            Route::put('{id}', 'update')->name('edit');

            // To store new account
            Route::post('store', 'store')->name('add');

            // To destroy the account by Id
            Route::delete('{id}', 'destroy')->name('delete');
        });
    });

    // contact
    Route::controller(ContactController::class)->group(function () {
        Route::prefix('contact')->name('contact.')->group(function () {
            // To get all the addresses
            Route::get('all', 'index')->name('browse');

            // To get the particular address by Id
            Route::get('show/{id}', 'show')->name('read');

            // To update the particular address by Id
            Route::put('update/{id}', 'update')->name('edit');

            // To store new address
            Route::post('store', 'store')->name('add');

            // To destroy the address by Id
            Route::delete('destroy/{id}', 'destroy')->name('delete');
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

Route::controller(PermissionController::class)->group(function () {
    Route::get('permission', 'index');
    Route::post('assign-permission-role', 'assignPermissionToRole');
    Route::post('set-default-permission-for-role', 'setDefaultPermissionForRole');

    Route::post('set-user-permision', 'setUserPermission');
});

Route::controller(UtilityController::class)->group(function () {
    Route::post('check-mx', 'checkMailExchangeserver');
    Route::post('get-ip-from-host', 'getIpFromHost');
});

Route::post('/s3/presigned-url', [S3Controller::class, 'getPresignedUrl']);
