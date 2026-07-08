<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Oracle connection is initialized lazily and should not crash the app boot.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    }
}
