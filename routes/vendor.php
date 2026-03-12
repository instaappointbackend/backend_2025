<?php

use App\Http\Controllers\Vendor\KycController;
use App\Http\Controllers\Vendor\RegisterController;
use Illuminate\Support\Facades\Route;

// Vendor Routes
Route::prefix('vendor')->name('vendor.')->group(function () {

    // dd('here');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('registerForm');
    Route::get('/register/success', [RegisterController::class, 'registrationSuccess'])
        ->name('vendor.registration.success');
    Route::post('/register', [RegisterController::class, 'store'])->name('vendorRegister');

    Route::get('/kyc', [KycController::class, 'showKycForm'])->name('kycForm');
    Route::post('/kyc', [KycController::class, 'uploadKycDocuments'])->name('vendorKyc');

    // dd('here');
});
