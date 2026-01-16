<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Api\ReminderController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PhonePeController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WebBlogController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AppointmentPayments\PhonePeBridgeController;
use App\Http\Controllers\Api\AppointmentPayments\RazorpayMobileController;
use App\Http\Controllers\RazorpayTestController;

Route::get('/clear-cache', function () {
    $exitCode = Artisan::call('optimize:clear');
    // return what you want
});
Route::get('/run-migration', function (Request $request) {

    // Optional security token check
    if ($request->query('key') !== env('MIGRATE_KEY')) {
        abort(403, 'Unauthorized.');
    }

    Artisan::call('migrate', ['--force' => true]);

    return "Migration executed successfully.";
});
Route::get('/maintenance/dump-autoload', [App\Http\Controllers\MaintenanceController::class, 'dumpAutoload']);


/*
// PhonePe Payment Mobile App  Routes
Route::get('/phonepe-bridge', [App\Http\Controllers\Api\AppointmentPayments\PhonePeBridgeController::class, 'showPaymentPage'])->name('phonepe.bridge');
Route::post('/phonepe-bridge/process', [App\Http\Controllers\Api\AppointmentPayments\PhonePeBridgeController::class, 'processPhonePePayment'])->name('phonepe.bridge.process');


Route::get('/phonepe-bridge/callback', [App\Http\Controllers\Api\AppointmentPayments\PhonePeBridgeController::class, 'handleBridgeCallback'])->name('phonepe.bridge.callback')->withoutMiddleware(['web']);
Route::post('/phonepe-bridge/callback', [App\Http\Controllers\Api\AppointmentPayments\PhonePeBridgeController::class, 'handleBridgeCallback'])->name('phonepe.bridge.callback')->withoutMiddleware(['web']);
Route::post('/phonepe-bridge/webhook', [App\Http\Controllers\Api\AppointmentPayments\PhonePeBridgeController::class, 'handleWebhook'])->name('phonepe.bridge.webhook')->withoutMiddleware(['web', 'csrf']);

Route::get('/phonepe-app-bridge/{transactionId}', [App\Http\Controllers\Api\AppointmentPayments\PhonePeBridgeController::class, 'showAppBridge'])->name('phonepe.app.bridge');
*/
Route::prefix('phonepe-bridge')->name('phonepe.bridge.')->group(function () {

    // Show payment page (GET)
    Route::get('/', [PhonePeBridgeController::class, 'showPaymentPage'])->name('page');

    // Process payment (POST)
    Route::post('/process', [PhonePeBridgeController::class, 'processPhonePePayment'])->name('process');

    // Callback route (GET or POST from PhonePe after payment)
    Route::match(['get', 'post'], '/callback', [PhonePeBridgeController::class, 'handleBridgeCallback'])
        ->withoutMiddleware(['web']) // disable CSRF for external requests
        ->name('payment.callback');

    // Webhook route (silent notification from PhonePe)
    Route::post('/webhook', [PhonePeBridgeController::class, 'handleWebhook'])
        ->withoutMiddleware(['web', 'csrf']) // disable CSRF for external requests
        ->name('webhook');

    // App bridge route (for mobile app redirect with transaction ID)
    Route::get('/app-bridge/{transactionId}', [PhonePeBridgeController::class, 'showAppBridge'])
        ->name('app');
});


// PhonePe Payment website  Routes
Route::middleware('web')->prefix('subscription')->name('subscription.')
    ->controller(SubscriptionController::class)
    ->group(function () {
        Route::get('/', 'index')->name('form');
        Route::post('/payment/process', 'subscribe')->name('process');
        //Route::post('/phonepe/payment/process', 'subscribe')->name('phonepe.pprocess');
        Route::get('/razorpay/checkout', 'razorpayCheckout')->name('razorpay.checkout');
        Route::get('/payment/status/{status}', 'subscriptionStatus')->name('status');

        Route::post('/razorpay/payment-failed', 'razorpayPaymentFailed')->name('razorpay.failed');
    });

// Routes without web middleware (e.g. payment gateway callbacks)
Route::prefix('subscription')->name('subscription.')
    ->withoutMiddleware('web')
    ->group(function () {
        Route::post('/payment/callback', [SubscriptionController::class, 'callback'])->name('callback');
        Route::get('/check-payment-status/{merchantTransactionId}', [SubscriptionController::class, 'checkStatus'])->name('checkStatus');
        Route::post('/webhook', [SubscriptionController::class, 'webhook'])->name('webhook');
    });

// PhonePe Payment website  Routes
Route::prefix('payment')->name('phonepe.')->group(function () {

    // Show payment form (GET)
    Route::get('/', [PhonePeController::class, 'showPaymentForm'])->name('form');

    // Process payment (POST)
    Route::post('/process', [PhonePeController::class, 'processPayment'])->name('process');

    // Callback route (GET or POST from PhonePe after payment)
    Route::match(['get', 'post'], '/callback', [PhonePeController::class, 'handleCallback'])
        ->withoutMiddleware(['web']) // disable CSRF for external requests
        ->name('callback');

    // Webhook route (silent background notification from PhonePe)
    Route::post('/webhook', [PhonePeController::class, 'handleWebhook'])
        ->withoutMiddleware(['web']) // disable CSRF for external requests
        ->name('webhook');
});

// Razorpay Webhook (Public - No Auth)
Route::post('razorpay/webhook', [RazorpayMobileController::class, 'handleWebhook'])
    ->withoutMiddleware(['web'])
    ->name('razorpay.webhook');

Route::prefix('razorpay/test')->group(function () {

    // API Testing Dashboard - Interactive UI to test all APIs
    // Visit: http://yourapp.com/razorpay/test/api-tester
    Route::get('api-tester', [RazorpayTestController::class, 'showApiTestPage'])
        ->name('razorpay.test.api-tester');

    // Simple Test Page - Shows basic payment form
    // Visit: http://yourapp.com/razorpay/test?appointment_id=1
    Route::get('/', [RazorpayTestController::class, 'showTestPage'])
        ->name('razorpay.test.page');

    // Create Order and Show Checkout (Web version that simulates mobile flow)
    // This opens Razorpay checkout like mobile SDK would
    Route::post('checkout', [RazorpayTestController::class, 'createOrderAndPay'])
        ->name('razorpay.test.checkout');

    Route::get('checkout', [RazorpayTestController::class, 'createOrderAndPay'])
        ->name('razorpay.test.checkout.get');

    // Callback after payment (simulates mobile app receiving payment response)
    Route::post('callback', [RazorpayTestController::class, 'handleCallback'])
        ->name('razorpay.test.callback');
});




// Home page
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/download-mobile', [HomeController::class, 'downloadApk'])->name('downloadApk');

//Blogs
Route::get('/blogs', [WebBlogController::class, 'blogs'])->name('blogs');
Route::get('/blog/{slug}', [WebBlogController::class, 'blog'])->name('blog');
Route::post('/blog/read/{slug}', [WebBlogController::class, 'incrementReadCount'])->name('blog.read');

// Contact form submission
Route::post('contact/submit', [App\Http\Controllers\ContactController::class, 'submit'])->name('contact.submit');
// Newsletter subscription
Route::post('newsletter/subscribe', [App\Http\Controllers\NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('newsletter/unsubscribe/{email?}', [App\Http\Controllers\NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

Route::get('/linkstorage', function () {
    Artisan::call('storage:link');
});

// Include admin routes
require __DIR__ . '/admin.php';

// Include vendor routes
require __DIR__ . '/vendor.php';

// ⚠️ ALWAYS LAST
Route::get('{page:slug}', [\App\Http\Controllers\HomeController::class, 'show'])
    ->where('page', '(?!admin|login|register|password|home|dashboard).*')
    ->name('page.show');
