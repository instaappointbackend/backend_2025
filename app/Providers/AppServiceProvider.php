<?php

namespace App\Providers;

use App\Services\NotificationService;
use App\Services\PaymentGateways\Contracts\PaymentGatewayInterface;
use App\Services\PaymentGateways\PhonePeService;
use App\Services\PaymentGateways\RazorpayService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService;
        });

        $this->app->bind(PaymentGatewayInterface::class, function ($app) {

            return match (request('payment_gateway')) {
                'razorpay' => $app->make(RazorpayService::class),
                default => $app->make(PhonePeService::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();
    }
}
