<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request; // <-- TAMBAHKAN IMPORT INI

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(Request $request): RedirectResponse // <-- UBAH TYPE-HINT DI SINI
    {
        // Logika di bawah ini perlu sedikit disesuaikan karena $request->user() tidak akan berfungsi
        $user = \App\Models\User::find($request->route('id'));

        if (! $user) {
            // Handle kasus jika user tidak ditemukan
            return redirect(config('app.frontend_url').'/login?error=user_not_found');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(
                config('app.frontend_url').'/dashboard?verified=1'
            );
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->intended(
            config('app.frontend_url').'/dashboard?verified=1'
        );
    }
}