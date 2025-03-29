<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\OmdbService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(OmdbService::class, function ($app) {
            return new OmdbService();
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