<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\CreditPurchaseRepository;
use App\Repositories\UsageTrackingRepository;
use App\Repositories\SubscriptionRepository;
use App\Services\CreditPurchaseService;
use App\Services\UsageTrackingService;
use App\Services\SubscriptionService;
use App\Services\UserSetupService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Repositories
        $this->app->singleton(CreditPurchaseRepository::class, function ($app) {
            return new CreditPurchaseRepository();
        });

        $this->app->singleton(UsageTrackingRepository::class, function ($app) {
            return new UsageTrackingRepository();
        });

        $this->app->singleton(SubscriptionRepository::class, function ($app) {
            return new SubscriptionRepository();
        });

        // Register Services
        $this->app->singleton(CreditPurchaseService::class, function ($app) {
            return new CreditPurchaseService(
                $app->make(CreditPurchaseRepository::class),
                $app->make(SubscriptionRepository::class)
            );
        });

        $this->app->singleton(UsageTrackingService::class, function ($app) {
            return new UsageTrackingService(
                $app->make(UsageTrackingRepository::class),
                $app->make(SubscriptionRepository::class)
            );
        });

        $this->app->singleton(SubscriptionService::class, function ($app) {
            return new SubscriptionService(
                $app->make(SubscriptionRepository::class),
                $app->make(UsageTrackingRepository::class)
            );
        });

        // Register User Setup Service
        $this->app->singleton(UserSetupService::class, function ($app) {
            return new UserSetupService(
                $app->make(SubscriptionRepository::class),
                $app->make(CreditPurchaseRepository::class),
                $app->make(UsageTrackingRepository::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}