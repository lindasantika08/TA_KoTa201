<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\UrlGenerator;
use App\Models\Assessment;
use App\Observers\AssessmentObserver;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        If(env('APP_ENV') !== 'local') { 
            URL::forceScheme('https');
        }

        Assessment::observe(AssessmentObserver::class);
    }
}
