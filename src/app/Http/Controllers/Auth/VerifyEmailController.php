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
    public function __invoke(Request $request) // Hapus type-hint :RedirectResponse agar fleksibel
{
    $user = \App\Models\User::find($request->route('id'));

    // ... validasi user not found ...

    // Proses Verifikasi
    if (!$user->hasVerifiedEmail()) {
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }
    }

    // Cek apakah request datang dari API (Aplikasi) atau Browser
    if ($request->wantsJson()) {
        return response()->json(['message' => 'Email verified successfully']);
    }

    // Jika dari browser (fallback), tampilkan view bridging yang kemarin
    return view('openapp');
}
}