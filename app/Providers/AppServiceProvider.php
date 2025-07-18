<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
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
        // Define a macro for making authenticated calls to the middleware
        Http::macro('withApiToken', function () {
            $token = session('api_token');

            // This will automatically add the "Authorization: Bearer <token>" header
            // and set the base URL for the request.
            return Http::withToken($token)
                ->baseUrl(config('services.middleware.url'));
        });
    }
}
