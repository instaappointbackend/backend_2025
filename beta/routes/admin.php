<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\KycController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\FAQController;
use App\Http\Controllers\Admin\BusinessCategoryController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\AdminPayoutController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PayoutController;
use App\Http\Controllers\Admin\PayoutApiController;
use App\Http\Controllers\Admin\AdminOfferController;

use Illuminate\Support\Facades\Route;

// Admin Auth Routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Guest Routes
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AuthController::class, 'sendOtp'])->name('send.otp');
        Route::get('/verify-otp', [AuthController::class, 'showOtpForm'])->name('otp.form');
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify.otp');
    });

    // Authenticated Admin Routes
    Route::middleware(['admin'])->group(function () {
        // Dashboard access
        Route::middleware(['permission:dashboard_view_dashboard'])->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        });

        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // User Management
        Route::middleware(['permission:users_view_users'])->group(function () {
            Route::middleware(['permission:users_create_users'])->group(function () {
                Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
                Route::post('/users', [UserController::class, 'store'])->name('users.store');
            });

            Route::middleware(['permission:users_edit_users'])->group(function () {
                Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
                Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
                Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
            });

            Route::middleware(['permission:users_delete_users'])->group(function () {
                Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
            });
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
            Route::get('/vendors', [UserController::class, 'vendors'])->name('users.vendors');
            Route::get('/customers', [UserController::class, 'customers'])->name('users.customers');


        });

        // KYC Management
        Route::middleware(['permission:kyc_view_kyc_submissions'])->group(function () {
            Route::get('/kyc', [KycController::class, 'index'])->name('kyc.index');
            Route::get('/kyc/{kycDocument}', [KycController::class, 'show'])->name('kyc.show');
            Route::get('/kyc-attachments/{kycDocument}/{type}', [KycController::class, 'downloadAttachment'])->name('kyc.download');
            Route::get('/pending-kyc', [KycController::class, 'pending'])->name('kyc.pending');
            Route::get('/verified-kyc', [KycController::class, 'verified'])->name('kyc.verified');

            Route::middleware(['permission:kyc_approve_kyc'])->group(function () {
                Route::post('/kyc/{kycDocument}/verify', [KycController::class, 'verify'])->name('kyc.verify');
                Route::post('/kyc/{id}/approve', [KycController::class, 'approve'])->name('kyc.approve');
            });

            Route::middleware(['permission:kyc_reject_kyc'])->group(function () {
                Route::post('/kyc/{id}/reject', [KycController::class, 'reject'])->name('kyc.reject');
            });

            Route::middleware(['permission:kyc_create_kyc'])->group(function () {
                Route::get('/kyc/create', [KycController::class, 'create'])->name('kyc.create');
                Route::post('/kyc', [KycController::class, 'store'])->name('kyc.store');
            });

            Route::middleware(['permission:kyc_edit_kyc'])->group(function () {
                Route::get('/kyc/{kycDocument}/edit', [KycController::class, 'edit'])->name('kyc.edit');
                Route::put('/kyc/{kycDocument}', [KycController::class, 'update'])->name('kyc.update');
            });

            Route::middleware(['permission:kyc_delete_kyc'])->group(function () {
                Route::delete('/kyc/{kycDocument}', [KycController::class, 'destroy'])->name('kyc.destroy');
            });
        });

        // Service Management
        Route::middleware(['permission:services_view_services'])->group(function () {
            Route::get('/services', [ServiceController::class, 'index'])->name('services.index');


            Route::middleware(['permission:services_create_services'])->group(function () {
                Route::get('/services/create', [ServiceController::class, 'create'])->name('services.create');
                Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
            });

            Route::middleware(['permission:services_edit_services'])->group(function () {
                Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
                Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
            });

            Route::middleware(['permission:services_delete_services'])->group(function () {
                Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
            });
            Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
        });

        // Appointment Management
        Route::middleware(['permission:appointments_view_appointments'])->group(function () {
            Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');

            Route::middleware(['permission:appointments_manage_calendar'])->group(function () {
                Route::get('/calendar', [AppointmentController::class, 'calendar'])->name('appointments.calendar');
            });

            Route::middleware(['permission:appointments_create_appointments'])->group(function () {
                Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
                Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
            });

            Route::middleware(['permission:appointments_edit_appointments'])->group(function () {
                Route::get('/appointments/{appointment}/edit', [AppointmentController::class, 'edit'])->name('appointments.edit');
                Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
                Route::patch('/appointments/{appointment}/status/{status}', [AppointmentController::class, 'status'])->name('appointments.status')->where('status', 'pending|confirmed|completed|cancelled');
            });

            Route::middleware(['permission:appointments_delete_appointments'])->group(function () {
                Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');
            });
            Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');

        });

        // Content Management
        // Blogs
        Route::middleware(['permission:content_manage_blogs'])->group(function () {
            Route::resource('blogs', BlogController::class);
        });

        // FAQs
        Route::middleware(['permission:content_manage_faqs'])->group(function () {
            Route::resource('faqs', FAQController::class);
        });

        // Business Categories
        Route::middleware(['permission:business_categories_view'])->group(function () {
            Route::get('/business-categories', [BusinessCategoryController::class, 'index'])->name('business-categories.index');


            Route::middleware(['permission:business_categories_create'])->group(function () {
                Route::get('/business-categories/create', [BusinessCategoryController::class, 'create'])->name('business-categories.create');
                Route::post('/business-categories', [BusinessCategoryController::class, 'store'])->name('business-categories.store');
            });

            Route::middleware(['permission:business_categories_edit'])->group(function () {
                Route::get('/business-categories/{businessCategory}/edit', [BusinessCategoryController::class, 'edit'])->name('business-categories.edit');
                Route::put('/business-categories/{businessCategory}', [BusinessCategoryController::class, 'update'])->name('business-categories.update');
            });

            Route::middleware(['permission:business_categories_delete'])->group(function () {
                Route::delete('/business-categories/{businessCategory}', [BusinessCategoryController::class, 'destroy'])->name('business-categories.destroy');
            });
            Route::get('/business-categories/{businessCategory}', [BusinessCategoryController::class, 'show'])->name('business-categories.show');
        });

        // Settings
        Route::middleware(['permission:settings_view_settings'])->group(function () {
            Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');

            Route::middleware(['permission:settings_update_settings'])->group(function () {
                Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
                Route::post('/settings/test-sms', [SettingController::class, 'testSms'])->name('settings.test-sms');
                Route::post('/settings/test-email', [SettingController::class, 'testEmail'])->name('settings.test-email');
            });
        });

        // Pages
        Route::middleware(['permission:content_manage_pages'])->group(function () {
            Route::resource('pages', PageController::class);
        });

        // Newsletter
        Route::middleware(['permission:newsletters_view_newsletters'])->group(function () {
            Route::get('/newsletters', [NewsletterController::class, 'index'])->name('newsletters.index');
            Route::get('/newsletters/{newsletter}', [NewsletterController::class, 'show'])->name('newsletters.show');

            Route::middleware(['permission:newsletters_export_newsletters'])->group(function () {
                Route::get('newsletters/export', [NewsletterController::class, 'export'])->name('newsletters.export');
            });

            Route::middleware(['permission:newsletters_manage_newsletters'])->group(function () {
                Route::get('/newsletters/create', [NewsletterController::class, 'create'])->name('newsletters.create');
                Route::post('/newsletters', [NewsletterController::class, 'store'])->name('newsletters.store');
                Route::get('/newsletters/{newsletter}/edit', [NewsletterController::class, 'edit'])->name('newsletters.edit');
                Route::put('/newsletters/{newsletter}', [NewsletterController::class, 'update'])->name('newsletters.update');
                Route::delete('/newsletters/{newsletter}', [NewsletterController::class, 'destroy'])->name('newsletters.destroy');
                Route::post('newsletters/bulk-action', [NewsletterController::class, 'bulkAction'])->name('newsletters.bulk-action');
            });
        });

        // Contacts
        Route::middleware(['permission:contacts_view_contacts'])->group(function () {
            Route::get('contacts', [ContactController::class, 'index'])->name('contacts.index');
            Route::get('contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
            Route::get('contacts/{contact}/reply', [ContactController::class, 'reply'])->name('contacts.reply');
            Route::post('contacts/{contact}/send-reply', [ContactController::class, 'sendReply'])->name('contacts.send-reply');
            Route::put('contacts/{contact}/status', [ContactController::class, 'updateStatus'])->name('contacts.update-status');
            Route::put('contacts/{contact}/toggle-status', [ContactController::class, 'toggleStatus'])->name('contacts.toggle-status');
            Route::post('contacts/bulk-action', [ContactController::class, 'bulkAction'])->name('contacts.bulk-action');
            Route::delete('contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
        });

        // Admin offers routes
        Route::middleware(['permission:offers_view_offers'])->group(function () {
            Route::get('/offers', [AdminOfferController::class, 'index'])->name('offers.index');
            Route::get('/offers/{offer}', [AdminOfferController::class, 'show'])->name('offers.show');

            Route::middleware(['permission:offers_create_offers'])->group(function () {
                Route::get('/offers/create', [AdminOfferController::class, 'create'])->name('offers.create');
                Route::post('/offers', [AdminOfferController::class, 'store'])->name('offers.store');
            });

            Route::middleware(['permission:offers_edit_offers'])->group(function () {
                Route::get('/offers/{offer}/edit', [AdminOfferController::class, 'edit'])->name('offers.edit');
                Route::put('/offers/{offer}', [AdminOfferController::class, 'update'])->name('offers.update');
                Route::patch('/offers/{offer}/toggle-status', [AdminOfferController::class, 'toggleStatus'])->name('offers.toggle-status');
            });

            Route::middleware(['permission:offers_delete_offers'])->group(function () {
                Route::delete('/offers/{offer}', [AdminOfferController::class, 'destroy'])->name('offers.destroy');
            });
        });

        // Admin Payout Management
        Route::middleware(['permission:payouts_view_payouts'])->group(function () {
            Route::get('/payouts', [PayoutController::class, 'index'])->name('payouts.index');
            Route::get('/payouts/show/{payoutRequest}', [PayoutController::class, 'show'])->name('payouts.show');
            Route::get('/payouts/provider/{userId}', [PayoutController::class, 'providerEarnings'])->name('payouts.provider_earnings');

            Route::middleware(['permission:payouts_export_payouts'])->group(function () {
                Route::get('/payouts/export', [PayoutController::class, 'export'])->name('payouts.export');
                Route::get('/payouts/reports', [PayoutController::class, 'reports'])->name('payouts.reports');
            });

            Route::middleware(['permission:payouts_process_payouts'])->group(function () {
                Route::post('/payouts/status/{payoutRequest}', [PayoutController::class, 'updateStatus'])->name('payouts.status');
                Route::post('/payouts/batch', [PayoutController::class, 'batchProcess'])->name('payouts.batch');
                Route::get('/payouts/pending', [PayoutController::class, 'findPendingPayouts'])->name('payouts.pending');
                Route::post('/payouts/create-manual', [PayoutController::class, 'createManualPayout'])->name('payouts.create_manual');
            });

            // Admin API routes for payouts
            Route::get('/pending-payouts', [PayoutApiController::class, 'getPendingPayouts'])->name('api.get_pending_payouts');
        });

        // Payment Management
        Route::middleware(['permission:payments_view_payments'])->group(function () {
            Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');


            Route::middleware(['permission:payments_export_payments'])->group(function () {
                Route::get('/payments/reports', [PaymentController::class, 'reports'])->name('payments.reports');
                Route::get('/payments/export', [PaymentController::class, 'export'])->name('payments.export');
            });

            Route::middleware(['permission:payments_process_payments'])->group(function () {
                Route::get('/payments/{payment}/edit', [PaymentController::class, 'edit'])->name('payments.edit');
                Route::put('/payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
                Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
            });
            Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        });

        // Refund Management
        Route::middleware(['permission:refunds_view_refunds'])->group(function () {
            Route::get('/refunds', [\App\Http\Controllers\Admin\RefundController::class, 'index'])->name('refunds.index');
            
            Route::middleware(['permission:refunds_export_refunds'])->group(function () {
                Route::get('/refunds/export', [\App\Http\Controllers\Admin\RefundController::class, 'exportRefunds'])->name('refunds.export');
            });

            Route::get('/refunds/{refund}', [\App\Http\Controllers\Admin\RefundController::class, 'show'])->name('refunds.show');
            Route::get('/refunds/{refund}/receipt', [\App\Http\Controllers\Admin\RefundController::class, 'generateReceipt'])->name('refunds.receipt');

            Route::middleware(['permission:refunds_process_refunds'])->group(function () {
                Route::post('/refunds/{refund}/process', [\App\Http\Controllers\Admin\RefundController::class, 'processRefund'])->name('refunds.process');
            });
        });

        // Reports Management
        Route::middleware(['permission:reports_view_reports'])->group(function () {
            Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/appointments', [ReportController::class, 'appointments'])->name('reports.appointments');
            Route::get('/reports/users', [ReportController::class, 'users'])->name('reports.users');
            Route::get('/reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
            Route::get('/reports/payouts', [ReportController::class, 'payouts'])->name('reports.payouts');

            // API endpoints for chart data
            Route::get('/reports/api/appointment-chart-data', [ReportController::class, 'getAppointmentChartData'])->name('reports.api.appointment_chart_data');
            Route::get('/reports/api/user-chart-data', [ReportController::class, 'getUserChartData'])->name('reports.api.user_chart_data');
            Route::get('/reports/api/revenue-chart-data', [ReportController::class, 'getRevenueChartData'])->name('reports.api.revenue_chart_data');
            Route::get('/reports/api/payout-chart-data', [ReportController::class, 'getPayoutChartData'])->name('reports.api.payout_chart_data');

            // Export endpoints
            Route::middleware(['permission:reports_export_reports'])->group(function () {
                Route::get('/reports/appointments/export', [ReportController::class, 'exportAppointments'])->name('reports.appointments.export');
                Route::get('/reports/users/export', [ReportController::class, 'exportUsers'])->name('reports.users.export');
                Route::get('/reports/revenue/export', [ReportController::class, 'exportRevenue'])->name('reports.revenue.export');
                Route::get('/reports/payouts/export', [ReportController::class, 'exportPayouts'])->name('reports.payouts.export');
            });
        });

        // Role Management
        Route::middleware(['permission:roles_view_roles'])->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');

            Route::middleware(['permission:roles_create_roles'])->group(function () {
                Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
                Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
            });

            Route::middleware(['permission:roles_edit_roles'])->group(function () {
                Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
                Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
                Route::post('/roles/{role}/assign-users', [RoleController::class, 'assignUsers'])->name('roles.assign-users');
                Route::delete('/roles/{role}/users/{user}', [RoleController::class, 'removeUser'])->name('roles.remove-user');
            });

            Route::middleware(['permission:roles_delete_roles'])->group(function () {
                Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
            });
            Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');

        });

        // Permission Management
        Route::middleware(['permission:permissions_view_permissions'])->group(function () {
            Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');

            Route::middleware(['permission:permissions_create_permissions'])->group(function () {
                Route::get('/permissions/create', [PermissionController::class, 'create'])->name('permissions.create');
                Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
                Route::get('/permissions/bulk-create', [PermissionController::class, 'bulkCreate'])->name('permissions.bulk-create');
                Route::post('/permissions/bulk-store', [PermissionController::class, 'bulkStore'])->name('permissions.bulk-store');
            });

            Route::middleware(['permission:permissions_edit_permissions'])->group(function () {
                Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
                Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
            });

            Route::middleware(['permission:permissions_delete_permissions'])->group(function () {
                Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');
            });
            Route::get('/permissions/{permission}', [PermissionController::class, 'show'])->name('permissions.show');

        });
    });
});
