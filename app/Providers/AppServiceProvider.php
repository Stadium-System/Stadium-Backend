<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;


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
        $this->configureRoutes();
        $this->configureRateLimiting();
    }

    protected function configureRoutes()
    {
        Route::middleware('api')
            ->prefix('api/v1/general')
            ->name('general.')
            ->group(base_path('routes/api/generalApi.php'));

        Route::middleware('api')
            ->prefix('api/v1/admin')
            ->name('admin.')
            ->group(base_path('routes/api/adminApi.php'));

        Route::middleware('api')
            ->prefix('api/v1')
            ->name('user.')
            ->group(base_path('routes/api/userApi.php'));

        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }

    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(100)->by($request->user()->id)
                : Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->input('phone_number'));
        });

        RateLimiter::for('check-phone-number', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

    }
}
