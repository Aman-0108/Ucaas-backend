<?php

use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\StripeController;
use App\Mail\TestMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('reset-password/{token}', [ForgotPasswordController::class, 'reset'])->name('password.reset');

Route::get('/payment-success', [StripeController::class, 'paymentSuccess']);

Route::get('/send-email', function () {
    // Dispatch the email using the TestMail Mailable class
    Mail::to('tusharsubhramondal@gmail.com')->send(new TestMail());

    return "Email sent successfully!";
});




