<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword; // <-- Ini untuk Reset Password
use Illuminate\Auth\Notifications\VerifyEmail;   // <-- TAMBAHKAN INI (Untuk Verifikasi Email)
use Illuminate\Support\Facades\URL;              // <-- TAMBAHKAN INI (Dibutuhkan oleh VerifyEmail)
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
        // Kode Anda yang sudah ada (JANGAN DIHAPUS)
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        // ---------------------------------------------------

        // Kode baru untuk Verifikasi Email (TAMBAHKAN DI BAWAHNYA)
        VerifyEmail::createUrlUsing(function ($notifiable) {
            // Buat URL verifikasi backend seperti biasa
            $backendUrl = URL::temporarySignedRoute(
                'verification.verify', // Nama route default Laravel
                now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );

            // Ganti domain backend (APP_URL) dengan domain frontend (FRONTEND_URL)
            $frontendUrl = str_replace(
                config(''),        // URL dari env: APP_URL
                env('FRONTEND_URL'),      // URL dari env: FRONTEND_URL
                $backendUrl
            );

            return $frontendUrl;
        });
    }
}