<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider; // Explicitly imported here

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
        // Force HTTPS if the request comes from your Tailscale Funnel domain
        if (str_contains(request()->getHost(), '.ts.net')) {
            URL::forceScheme('https');
        }
    }
}
