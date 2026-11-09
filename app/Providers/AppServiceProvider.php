<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Number;

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
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return rtrim((string) config('app.frontend_url'), '/').
                '/reset-password?token='.$token.
                '&email='.urlencode($notifiable->getEmailForPasswordReset());
        });

        // Agar intl extension load na ho toh Number::format ko bypass karein
        if (!extension_loaded('intl')) {
            \Illuminate\Support\Number::macro('format', function ($number, $locale = null, $options = []) {
                return number_format($number, 2);
            });
        }
    }
}