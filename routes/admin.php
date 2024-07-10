<?php

use App\Http\Controllers\Api\Admin\DocumentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DidRateController;
use App\Http\Controllers\Api\DidVendorController;
use App\Http\Controllers\Api\DomainController;
use App\Http\Controllers\Api\FeatureController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TimezoneController;
use Illuminate\Support\Facades\Route;

// Guest mode
Route::group(['middleware' => 'guest'], function () {
    // Auth
    Route::controller(AuthController::class)->group(function () {
        Route::post('login', 'login');
        Route::post('register', 'register');
    });
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {

    Route::controller(AuthController::class)->group(function () {
        // To logout the authenticated user
        Route::get('logout', 'logout');
    });

    // All timezones
    Route::get('timezones/{Account?}', [TimezoneController::class, 'index']);

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

    // Document
    Route::controller(DocumentController::class)->group(function () {
        // To get all the documents
        Route::get('documents', 'index');

        // To store new document
        Route::post('document/store', 'store');

        // To get the particular document by Id
        Route::get('document/details/{id}', 'show');

        // To update the particular document by Id
        Route::put('document/update/{id}', 'update');

        // To destroy the document by Id
        Route::delete('document/destroy/{id}', 'destroy');
    });

    // Role
    Route::controller(RoleController::class)->group(function () {
        // To get all the roles
        Route::get('roles', 'index');

        // To store new role
        Route::post('role/store', 'store');

        // To update the particular role by Id
        Route::put('role/{id}', 'update');

        // To destroy the role by Id
        Route::delete('role/{id}', 'destroy');

        // To get the particular role by Id
        Route::get('role/{id}', 'show');
    });

    // Permission
    Route::controller(PermissionController::class)->group(function () {
        Route::get('permission', 'index');
        // Route::post('assign-permission-role', 'assignPermissionToRole');
        // Route::post('set-default-permission-for-role', 'setDefaultPermissionForRole');

        // Route::post('set-user-permision', 'setUserPermission');
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

    // DID
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
