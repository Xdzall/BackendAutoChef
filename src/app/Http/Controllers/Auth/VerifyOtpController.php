<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VerifyOtpController extends Controller
{
    /**
     * Verifikasi OTP yang dikirim ke email pengguna.
     * Jika valid, kembalikan temporary token untuk reset password.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json([
                'message' => 'Kode OTP tidak ditemukan. Silakan minta OTP baru.',
            ], 422);
        }

        // Cek apakah OTP sudah expired (15 menit)
        if (now()->diffInMinutes($record->created_at) > 15) {
            // Hapus OTP yang sudah expired
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            return response()->json([
                'message' => 'Kode OTP sudah kedaluwarsa. Silakan minta OTP baru.',
            ], 422);
        }

        // Verifikasi OTP (disimpan sebagai hash)
        if (!Hash::check($request->otp, $record->token)) {
            return response()->json([
                'message' => 'Kode OTP salah.',
            ], 422);
        }

        // OTP valid - generate temporary reset token
        $resetToken = Str::random(64);

        // Update token di database dengan reset token baru
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->update([
                'token' => Hash::make($resetToken),
                'created_at' => now(),
            ]);

        return response()->json([
            'message' => 'OTP berhasil diverifikasi.',
            'reset_token' => $resetToken,
        ]);
    }
}
