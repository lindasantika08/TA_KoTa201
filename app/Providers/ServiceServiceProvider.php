<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AssessmentNotificationService;

class ServiceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AssessmentNotificationService::class, function ($app) {
            return new AssessmentNotificationService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
