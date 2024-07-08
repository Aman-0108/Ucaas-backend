<?php

use App\Http\Controllers\Api\Admin\DocumentController;
use App\Http\Controllers\Api\AuthController;
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

    // Feature
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
});
