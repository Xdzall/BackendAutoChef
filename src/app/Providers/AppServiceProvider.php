<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use App\Models\Recipe;
use App\Observers\RecipeObserver;

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
        // Custom URL untuk Verifikasi Email
        VerifyEmail::createUrlUsing(function ($notifiable) {
            $backendUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );

            // Ganti domain backend (APP_URL) dengan domain frontend (FRONTEND_URL)
            return str_replace(
                config('app.url'),
                config('app.frontend_url'),
                $backendUrl
            );
        });

        Recipe::observe(RecipeObserver::class);
    }
}