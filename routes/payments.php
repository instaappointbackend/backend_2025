<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AppointmentPayments\PhonePeBridgeController;
use App\Http\Controllers\Api\AppointmentPayments\RazorpayMobileController;
use App\Http\Controllers\PhonePeController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\RazorpayTestController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

/*
|--------------------------------------------------------------------------
| PAYMENT ROUTES
|--------------------------------------------------------------------------
| All PhonePe, Razorpay, Subscription, Webhook & Callback routes live here
*/

/*
|--------------------------------------------------------------------------
| PhonePe – Mobile App Bridge
|--------------------------------------------------------------------------
*/

Route::prefix('phonepe-bridge')->name('phonepe.bridge.')->group(function () {

    Route::get('/', [PhonePeBridgeController::class, 'showPaymentPage'])->name('page');
    Route::post('/process', [PhonePeBridgeController::class, 'processPhonePePayment'])->name('process');

    Route::match(['get', 'post'], '/callback', [PhonePeBridgeController::class, 'handleBridgeCallback'])
        ->withoutMiddleware(['web'])
        ->name('callback');

    Route::post('/webhook', [PhonePeBridgeController::class, 'handleWebhook'])
        ->withoutMiddleware(['web', 'csrf'])
        ->name('webhook');

    Route::get('/app-bridge/{transactionId}', [PhonePeBridgeController::class, 'showAppBridge'])
        ->name('app');
});

/*
|--------------------------------------------------------------------------
| PhonePe – Website Payments
|--------------------------------------------------------------------------
*/
Route::prefix('payment')->name('phonepe.')->group(function () {

    Route::get('/', [PhonePeController::class, 'showPaymentForm'])->name('form');
    Route::post('/process', [PhonePeController::class, 'processPayment'])->name('process');

    Route::match(['get', 'post'], '/callback', [PhonePeController::class, 'handleCallback'])
        ->withoutMiddleware(['web'])
        ->name('callback');

    Route::post('/webhook', [PhonePeController::class, 'handleWebhook'])
        ->withoutMiddleware(['web'])
        ->name('webhook');
});

/*
|--------------------------------------------------------------------------
| Subscriptions (Web + Callbacks)
|--------------------------------------------------------------------------
*/
Route::middleware('web')->prefix('subscription')->name('subscription.')
    ->controller(SubscriptionController::class)
    ->group(function () {
        Route::get('/', 'index')->name('form');
        Route::post('/payment/process', 'subscribe')->name('process');
        Route::get('/razorpay/checkout', 'razorpayCheckout')->name('razorpay.checkout');
        Route::get('/payment/status/{status}', 'subscriptionStatus')->name('status');
        Route::post('/razorpay/payment-failed', 'razorpayPaymentFailed')->name('razorpay.failed');
    });

Route::prefix('subscription')->name('subscription.')
    ->withoutMiddleware('web')
    ->group(function () {
        Route::post('/payment/callback', [SubscriptionController::class, 'callback'])->name('callback');
        Route::get('/check-payment-status/{merchantTransactionId}', [SubscriptionController::class, 'checkStatus'])->name('checkStatus');
        Route::post('/webhook', [SubscriptionController::class, 'webhook'])->name('webhook')->withoutMiddleware([VerifyCsrfToken::class]);;
    });

// Route::post('/subscription/webhook', [SubscriptionController::class, 'webhook'])
//     ->name('subscription.webhook')
//     ->withoutMiddleware('web')->withoutMiddleware([VerifyCsrfToken::class]);


/*
|--------------------------------------------------------------------------
| Razorpay – Webhooks & Testing
|--------------------------------------------------------------------------
*/
Route::post('razorpay/webhook', [RazorpayMobileController::class, 'handleWebhook'])
    ->withoutMiddleware(['web'])
    ->name('razorpay.webhook');

Route::prefix('razorpay/test')->group(function () {

    Route::get('api-tester', [RazorpayTestController::class, 'showApiTestPage'])
        ->name('razorpay.test.api-tester');

    Route::get('/', [RazorpayTestController::class, 'showTestPage'])
        ->name('razorpay.test.page');

    Route::match(['get', 'post'], 'checkout', [RazorpayTestController::class, 'createOrderAndPay'])
        ->name('razorpay.test.checkout');

    Route::post('callback', [RazorpayTestController::class, 'handleCallback'])
        ->name('razorpay.test.callback');
});
