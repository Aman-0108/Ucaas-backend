<?php

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
    // All timezones
    Route::get('timezones/{Account?}', [TimezoneController::class, 'index']);
});
