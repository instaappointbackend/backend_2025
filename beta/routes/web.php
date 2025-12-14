<?php

use App\Http\Controllers\Api\ReminderController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PhonePeController;


Route::get('/clear-cache', function() {
    $exitCode = Artisan::call('optimize:clear');
    // return what you want
});
Route::get('/maintenance/dump-autoload', [App\Http\Controllers\MaintenanceController::class, 'dumpAutoload']);


// PhonePe Payment Mobile App  Routes
Route::get('/phonepe-bridge', [App\Http\Controllers\Api\PhonePeBridgeController::class, 'showPaymentPage'])->name('phonepe.bridge');
Route::post('/phonepe-bridge/process', [App\Http\Controllers\Api\PhonePeBridgeController::class, 'processPhonePePayment'])->name('phonepe.bridge.process');


Route::get('/phonepe-bridge/callback', [App\Http\Controllers\Api\PhonePeBridgeController::class, 'handleBridgeCallback'])->name('phonepe.bridge.callback')->withoutMiddleware(['web']);
Route::post('/phonepe-bridge/callback', [App\Http\Controllers\Api\PhonePeBridgeController::class, 'handleBridgeCallback'])->name('phonepe.bridge.callback')->withoutMiddleware(['web']);
Route::post('/phonepe-bridge/webhook', [App\Http\Controllers\Api\PhonePeBridgeController::class, 'handleWebhook'])->name('phonepe.bridge.webhook')->withoutMiddleware(['web', 'csrf']);

Route::get('/phonepe-app-bridge/{transactionId}', [App\Http\Controllers\Api\PhonePeBridgeController::class, 'showAppBridge'])->name('phonepe.app.bridge');



// PhonePe Payment website  Routes
Route::get('/payment', [PhonePeController::class, 'showPaymentForm'])->name('phonepe.form');
Route::post('/payment/process', [PhonePeController::class, 'processPayment'])->name('phonepe.process');
// Important: These routes should be excluded from CSRF protection
Route::get('/payment/callback', [PhonePeController::class, 'handleCallback'])->name('phonepe.callback')->withoutMiddleware(['web']);
Route::post('/payment/callback', [PhonePeController::class, 'handleCallback'])->name('phonepe.callback')->withoutMiddleware(['web']);
Route::post('/payment/webhook', [PhonePeController::class, 'handleWebhook'])->name('phonepe.webhook')->withoutMiddleware(['web']);





// Home page
Route::get('/', [HomeController::class, 'index'])->name('home');
// Contact form submission
Route::post('contact/submit', [App\Http\Controllers\ContactController::class, 'submit'])->name('contact.submit');
// Newsletter subscription
Route::post('newsletter/subscribe', [App\Http\Controllers\NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('newsletter/unsubscribe/{email?}', [App\Http\Controllers\NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

Route::get('/linkstorage', function () {
    Artisan::call('storage:link');
});

Route::get('{page:slug}', [\App\Http\Controllers\HomeController::class, 'show'])
    ->where('page', '(?!admin|login|register|password|home|dashboard).*')
    ->name('page.show');



// Include admin routes
require __DIR__.'/admin.php';
