<?php

namespace App\Providers;

use App\Models\Insurance;
use App\Observers\InsuranceObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        // The local dev tunnel (ngrok) rewrites the Host header to the
        // internal Herd domain, which would otherwise leak into generated
        // URLs. Force APP_URL so links sent to Telegram stay publicly reachable.
        URL::forceRootUrl(config('app.url'));

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Insurance::observe(InsuranceObserver::class);
    }
}
