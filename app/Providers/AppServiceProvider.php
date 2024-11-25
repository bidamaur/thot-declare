<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\DatabaseConnection;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DatabaseConnection::class, function ($app) {
            return new DatabaseConnection();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        
    }
}
