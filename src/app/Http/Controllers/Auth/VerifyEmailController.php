<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /**
     * Verifikasi email pengguna dari link yang dikirim.
     */
    public function __invoke(Request $request)
    {
        $user = User::find($request->route('id'));

        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'User tidak ditemukan.'], 404);
            }
            return view('openapp', ['status' => 'error', 'message' => 'User tidak ditemukan.']);
        }

        // Validasi hash email
        if (!hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Link verifikasi tidak valid.'], 403);
            }
            return view('openapp', ['status' => 'error', 'message' => 'Link verifikasi tidak valid.']);
        }

        // Proses verifikasi
        if ($user->hasVerifiedEmail()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Email sudah terverifikasi sebelumnya.']);
            }
            return view('openapp', ['status' => 'already_verified', 'message' => 'Email sudah terverifikasi.']);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Email berhasil diverifikasi.']);
        }

        // Redirect ke deep link atau tampilkan halaman bridging
        return view('openapp', ['status' => 'success', 'message' => 'Email berhasil diverifikasi!']);
    }
}