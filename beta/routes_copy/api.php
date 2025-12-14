<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AppSettingController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\ComboServiceController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\BusinessCategoryController;
use App\Http\Controllers\Api\KycController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\TeamMemberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FAQController;
use App\Http\Controllers\Api\WorkingHoursController;
use App\Http\Controllers\Api\HolidayController;
use App\Http\Controllers\Api\TimeSlotController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\AppointmentSettingsController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\PayoutController;
use App\Http\Controllers\Api\CustomerController;
use Illuminate\Support\Facades\Validator;
Route::post('/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
Route::get('/pages/{slug}', [AppSettingController::class, 'getPage']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/upload-kyc', [KycController::class, 'uploadKycDocuments']);
    Route::get('/check-kyc-status', [KycController::class, 'checkKycStatus']);

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'getProfile']);
    Route::post('/update-profile', [ProfileController::class, 'updateProfile']);
    Route::delete('/delete-account', [ProfileController::class, 'deleteAccount']);

    Route::get('/get-business-categories', [BusinessCategoryController::class, 'index']);
    // Blog Management
    Route::apiResource('blogs', BlogController::class);


    Route::apiResource('faqs', FAQController::class);

    // Team Member
    Route::apiResource('team-members', TeamMemberController::class);
    Route::get('app-settings', [AppSettingController::class, 'index']);

    // Working Hours
    Route::get('working-hours', [WorkingHoursController::class, 'index']);
    Route::post('working-hours', [WorkingHoursController::class, 'store']);
    Route::put('working-hours/{dayOfWeek}', [WorkingHoursController::class, 'update']);
    Route::post('working-hours/copy-to-all/{dayOfWeek}', [WorkingHoursController::class, 'copyToAll']);
    Route::post('working-hours/reset', [WorkingHoursController::class, 'resetToDefault']);

    // Holidays
    Route::get('holidays', [HolidayController::class, 'index']);
    Route::post('holidays', [HolidayController::class, 'store']);
    Route::get('holidays/{id}', [HolidayController::class, 'show']);
    Route::put('holidays/{id}', [HolidayController::class, 'update']);
    Route::delete('holidays/{id}', [HolidayController::class, 'destroy']);
    Route::get('future-holidays', [HolidayController::class, 'future']);
    Route::get('recurring-holidays', [HolidayController::class, 'recurring']);

    // Time Slots
    Route::get('time-slots', [TimeSlotController::class, 'index']);
    Route::post('time-slots/generate', [TimeSlotController::class, 'generate']);
    Route::put('time-slots/{id}', [TimeSlotController::class, 'update']);
    Route::post('time-slots/batch-update', [TimeSlotController::class, 'batchUpdate']);

    // Services
    Route::get('services', [ServiceController::class, 'index']);
    Route::post('services', [ServiceController::class, 'store']);
    Route::get('services/{id}', [ServiceController::class, 'show']);
    Route::put('services/{id}', [ServiceController::class, 'update']);
    Route::delete('services/{id}', [ServiceController::class, 'destroy']);
    Route::post('services/{id}/toggle-status', [ServiceController::class, 'toggleStatus']);
    Route::get('active-services', [ServiceController::class, 'active']);

    // Combo Services
    Route::get('combo-services', [ComboServiceController::class, 'index']);
    Route::post('combo-services', [ComboServiceController::class, 'store']);
    Route::get('combo-services/{id}', [ComboServiceController::class, 'show']);
    Route::put('combo-services/{id}', [ComboServiceController::class, 'update']);
    Route::delete('combo-services/{id}', [ComboServiceController::class, 'destroy']);
    Route::post('combo-services/{id}/toggle-status', [ComboServiceController::class, 'toggleStatus']);
    Route::get('combo-services/active', [ComboServiceController::class, 'active']);



    // Appointments
    Route::get('appointments', [AppointmentController::class, 'index']);
    Route::post('appointments', [AppointmentController::class, 'store']);
    Route::get('appointments/{id}', [AppointmentController::class, 'show']);
    Route::put('appointments/{id}', [AppointmentController::class, 'update']);
    Route::delete('appointments/{id}', [AppointmentController::class, 'destroy']);

    // Appointment status updates
    Route::post('appointments/{id}/cancel', [AppointmentController::class, 'cancel']);
    Route::post('appointments/{id}/confirm', [AppointmentController::class, 'confirm']);
    Route::post('appointments/{id}/complete', [AppointmentController::class, 'complete']);
    Route::post('appointments/{id}/reschedule', [AppointmentController::class, 'reschedule']);

    // Refund and cancellation policy routes
    Route::get('appointments/{id}/refund-policy', [AppointmentController::class, 'getRefundPolicy']);
    Route::get('refunds/history', [AppointmentController::class, 'getRefundHistory']);
    Route::get('refunds/statistics', [AppointmentController::class, 'getRefundStatistics']);

    // Admin refund management routes
    Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
        Route::get('refunds', [App\Http\Controllers\Admin\RefundController::class, 'getRefunds']);
        Route::get('refunds/{id}', [App\Http\Controllers\Admin\RefundController::class, 'show']);
        Route::post('refunds/{id}/process', [App\Http\Controllers\Admin\RefundController::class, 'processRefund']);
        Route::get('refunds/statistics/admin', [App\Http\Controllers\Admin\RefundController::class, 'getStatistics']);
        Route::get('refunds/export/csv', [App\Http\Controllers\Admin\RefundController::class, 'exportRefunds']);
        Route::get('refunds/{id}/receipt', [App\Http\Controllers\Admin\RefundController::class, 'generateReceipt']);
    });

    // Appointment payment
    Route::post('appointments/{id}/payment', [AppointmentController::class, 'updatePaymentStatus']);

    // Appointment utilities
    Route::get('appointments/by-date', [AppointmentController::class, 'getAppointmentsByDate']);
    Route::get('appointments-stats', [AppointmentController::class, 'stats']);

    // Payment routes
    Route::post('payments/save', [PaymentController::class, 'savePayment']);
    Route::get('payments/verify/{transactionId}', [PaymentController::class, 'verifyPayment']);
    Route::get('payments/history', [PaymentController::class, 'getPaymentHistory']);
    Route::get('payments/appointment/{appointmentId}', [PaymentController::class, 'getPaymentByAppointment']);
    Route::post('payments/{paymentId}/refund', [PaymentController::class, 'initiateRefund']);

    // Provider routes with booking capabilities
    Route::post('providers/{id}/book', [ProviderController::class, 'bookAppointment']);
    // Appointment Settings
    Route::get('appointment-settings', [AppointmentSettingsController::class, 'index']);
    Route::put('appointment-settings', [AppointmentSettingsController::class, 'update']);
    Route::post('appointment-settings/reset', [AppointmentSettingsController::class, 'resetToDefault']);

    // Provider Offer Routes
    Route::get('/offers', [OfferController::class, 'index']);
    Route::get('/offers/active', [OfferController::class, 'active']);
    Route::post('/offers', [OfferController::class, 'store']);
    Route::get('/offers/{id}', [OfferController::class, 'show']);
    Route::put('/offers/{id}', [OfferController::class, 'update']);
    Route::delete('/offers/{id}', [OfferController::class, 'destroy']);
    Route::post('/offers/{id}/toggle-status', [OfferController::class, 'toggleStatus']);

    // Admin Offer Management (admin only)
    Route::get('/admin-offers', [OfferController::class, 'adminOffers']);

    // Global Offer Access (customer-facing routes)
    Route::get('/global-offers', [OfferController::class, 'globalOffers']);
    Route::get('/global-offers/{id}', [OfferController::class, 'globalOffers']);
    Route::get('/global-offers/{id}/{sid}', [OfferController::class, 'globalOffers']);
    Route::post('/offers/validate-coupon', [OfferController::class, 'validateCoupon']);
    Route::post('/offers/apply-coupon', [OfferController::class, 'applyCoupon']);

    Route::apiResource('contacts', ContactController::class)
        ->only(['index', 'store', 'show', 'destroy']);

    Route::apiResource('contact-supports', ContactController::class)
        ->only(['index', 'store', 'show', 'destroy']);


    // search endpoints
    Route::get('/search', [App\Http\Controllers\Api\SearchController::class, 'search']);
    Route::get('/search/suggestions', [App\Http\Controllers\Api\SearchController::class, 'getSuggestions']);
    Route::get('/search/recent', [App\Http\Controllers\Api\SearchController::class, 'getRecentSearches']);
    Route::post('/search/save', [App\Http\Controllers\Api\SearchController::class, 'saveSearch']);
    Route::delete('/search/clear', [App\Http\Controllers\Api\SearchController::class, 'clearRecentSearches']);

    Route::get('/providers-favorites', [ProviderController::class, 'getFavorites']);

    // Get providers by category
    Route::get('providers/category/{categoryId}', [ProviderController::class, 'getProvidersByCategory'])
        ->name('providers.by-category');


// Payment routes
    Route::post('payments/save', [PaymentController::class, 'savePayment']);
    Route::get('payments/verify/{transactionId}', [PaymentController::class, 'verifyPayment']);
    Route::get('payments/history', [PaymentController::class, 'getPaymentHistory']);
    Route::get('payments/appointment/{appointmentId}', [PaymentController::class, 'getPaymentByAppointment']);
    Route::post('payments/{paymentId}/refund', [PaymentController::class, 'initiateRefund']);

// New vendor/provider payment routes
    Route::get('payments/provider', [PaymentController::class, 'getProviderPayments']);
    Route::get('payments/provider/stats', [PaymentController::class, 'getProviderPaymentStats']);
    Route::get('payments/provider/export', [PaymentController::class, 'exportProviderPayments']);

// Support both POST and PUT for updating payment status
    Route::post('payments/{paymentId}/status', [PaymentController::class, 'updatePaymentStatus']);
    Route::put('payments/{paymentId}/status', [PaymentController::class, 'updatePaymentStatus']);

// New routes for detailed payment info and receipt generation
    Route::get('payments/{paymentId}', [PaymentController::class, 'getPaymentDetails']);
    Route::get('payments/{paymentId}/receipt', [PaymentController::class, 'generateReceipt']);
    // Customer management
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);
    Route::get('/customers/{id}/appointments', [CustomerController::class, 'getCustomerAppointments']);
    Route::get('/customers/{id}/stats', [CustomerController::class, 'getCustomerStats']);


    Route::get('/user-locations', [App\Http\Controllers\Api\UserLocationController::class, 'index']);
    Route::post('/user-locations', [App\Http\Controllers\Api\UserLocationController::class, 'store']);
    Route::post('/user-locations/{id}/used', [App\Http\Controllers\Api\UserLocationController::class, 'markAsUsed']);
    Route::post('/user-locations/{id}/favorite', [App\Http\Controllers\Api\UserLocationController::class, 'toggleFavorite']);
    Route::delete('/user-locations/{id}', [App\Http\Controllers\Api\UserLocationController::class, 'destroy']);
    Route::delete('/user-locations', [App\Http\Controllers\Api\UserLocationController::class, 'clearAll']);

    Route::post('/notification/token', [NotificationController::class, 'registerToken']);
    Route::get('/notifications', [NotificationController::class, 'getNotifications']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);

    // Update the payment/phonepe/bridge-url route in api.php
    Route::get('/payment/phonepe/bridge-url', function (Request $request) {
        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required|integer|exists:appointments,id',
            'return_scheme' => 'required|string',
            'dev_server' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // Store dev server details in session if provided
        if ($request->has('dev_server') && !empty($request->dev_server)) {
            session(['dev_server' => $request->dev_server]);
            Log::info('Stored dev server in session: ' . $request->dev_server);
        }

        // Create a return URL for deep linking back to the app
        $returnUrl = '';

        // Handle special case for Expo development
        if ($request->return_scheme === 'expo-dev' && $request->has('dev_server')) {
            // Format: exp://192.168.1.5:8081/--/payment/callback
            $returnUrl = 'exp://' . $request->dev_server . '/--/payment/callback';
            Log::info('Created Expo dev return URL: ' . $returnUrl);
        } else {
            // Standard app scheme format
            $returnUrl = $request->return_scheme . '://payment/callback';
            Log::info('Created standard app return URL: ' . $returnUrl);
        }

        // Generate the bridge URL
        $bridgeUrl = route('phonepe.bridge', [
            'appointment_id' => $request->appointment_id,
            'app_return_url' => $returnUrl
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bridge URL generated successfully',
            'data' => [
                'bridge_url' => $bridgeUrl,
                'return_url' => $returnUrl
            ]
        ]);
    })->name('api.payment.phonepe.bridge-url');

    Route::prefix('providers')->group(function () {
        // Add these new routes
        Route::get('/popular-nearby', [ProviderController::class, 'getPopularNearbyProviders']);
        Route::get('/nearby', [ProviderController::class, 'getNearbyProviders']);
        // Public routes
        Route::get('/{id}', [ProviderController::class, 'show']);


        Route::get('/{id}/team-members', [ProviderController::class, 'getTeamMembers']);
        Route::get('/{id}/blogs', [ProviderController::class, 'getBlogs']);
        Route::get('/{id}/blog/{blogId}', [ProviderController::class, 'getBlog']);
        Route::get('/{id}/reviews', [ProviderController::class, 'getReviews']);
        Route::get('/{id}/availability', [ProviderController::class, 'getAvailability']);
        Route::get('/{id}/settings', [ProviderController::class, 'getSettings']);

        // Protected routes (require authentication)
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/{id}/combo-services', [ProviderController::class, 'getComboServices']);
            Route::get('/{id}/services', [ProviderController::class, 'getServices']);
            Route::get('/{id}/service/{sid}', [ProviderController::class, 'getService']);
            Route::post('/{id}/reviews', [ProviderController::class, 'submitReview']);
            Route::get('/{id}/favorite/check', [ProviderController::class, 'checkFavorite']);
            Route::post('/{id}/favorite/toggle', [ProviderController::class, 'toggleFavorite']);
            Route::post('/{id}/book', [ProviderController::class, 'bookAppointment']);

        });
    });


    // Bank Accounts
    Route::get('/bank-accounts', [PayoutController::class, 'getBankAccounts']);
    Route::post('/bank-accounts', [PayoutController::class, 'addBankAccount']);
    Route::put('/bank-accounts/{id}', [PayoutController::class, 'updateBankAccount']);
    Route::delete('/bank-accounts/{id}', [PayoutController::class, 'deleteBankAccount']);
    Route::post('/bank-accounts/{id}/set-default', [PayoutController::class, 'setDefaultBankAccount']);

// Payout Requests
    Route::get('/payout-requests', [PayoutController::class, 'getPayoutRequests']);
    Route::post('/payout-requests', [PayoutController::class, 'createPayoutRequest']);
    Route::post('/payout-requests/{id}/cancel', [PayoutController::class, 'cancelPayoutRequest']);
    Route::get('/earnings-summary', [PayoutController::class, 'getEarningsSummary']);



    Route::get('/notifications/token/status', [NotificationController::class, 'getTokenStatus']);
    Route::post('/notifications/test-appointment', [NotificationController::class, 'testAppointmentNotification']);

    // Payment receipt routes
    Route::get('payments/{paymentId}/receipt/download', [PaymentController::class, 'downloadReceiptPDF']);
    Route::get('payments/{paymentId}/receipt/share', [PaymentController::class, 'shareReceiptPDF']);

// Appointment receipt routes
    Route::get('appointments/{appointmentId}/receipt/download', [PaymentController::class, 'downloadAppointmentReceiptPDF']);
    Route::get('appointments/{appointmentId}/receipt/share', [PaymentController::class, 'shareAppointmentReceiptPDF']);

    // Reminder Routes
    Route::apiResource('reminders', ReminderController::class);
    Route::post('reminders/{id}/complete', [ReminderController::class, 'markAsCompleted']);
    Route::post('reminders/{id}/cancel', [ReminderController::class, 'markAsCancelled']);
    Route::get('reminders-stats', [ReminderController::class, 'stats']);
    Route::get('reminders-by-target', [ReminderController::class, 'getByTarget']);



// Review Routes
    Route::prefix('reviews')->group(function () {
        Route::post('/', [ReviewController::class, 'store']);
        Route::put('/{id}', [ReviewController::class, 'update']);
        Route::delete('/{id}', [ReviewController::class, 'destroy']);
        Route::get('/user', [ReviewController::class, 'getUserReviews']);
        Route::get('/provider/{providerId}', [ReviewController::class, 'getProviderReviews']);
        Route::get('/provider/{providerId}/stats', [ReviewController::class, 'getProviderStats']);
        Route::get('/service/{serviceId}', [ReviewController::class, 'getServiceReviews']);
        Route::get('/combo-service/{comboServiceId}', [ReviewController::class, 'getComboServiceReviews']);
        Route::get('/appointment/{appointmentId}', [ReviewController::class, 'getAppointmentReview']);
        Route::get('/can-review/{appointmentId}', [ReviewController::class, 'canReview']);
        Route::post('/{id}/respond', [ReviewController::class, 'respondToReview']);
    });
});

Route::get('/notification/test', [NotificationController::class, 'testNotification']);
Route::get('/getAllToken', [NotificationController::class, 'getAllToken']);
