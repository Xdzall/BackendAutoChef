<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\VerifyOtpController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware('guest')
    ->name('register');

// --- Login via Google ---
Route::post('/auth/google', GoogleAuthController::class)
    ->middleware(['throttle:10,1'])
    ->name('auth.google');

// --- Email Verifikasi ---
Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

// Kirim ulang email verifikasi (tanpa auth, cukup kirim email)
Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['throttle:3,1'])
    ->name('verification.send');

// --- Lupa Password (OTP-based) ---
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('password.email');

Route::post('/verify-otp', VerifyOtpController::class)
    ->middleware(['guest', 'throttle:5,1'])
    ->name('password.verify-otp');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('password.store');

// --- Logout ---
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
