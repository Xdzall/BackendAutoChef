<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class NewPasswordController extends Controller
{
    /**
     * Reset password menggunakan token dari verifikasi OTP.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Cari record di password_reset_tokens
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json([
                'message' => 'Token reset password tidak valid.',
            ], 422);
        }

        // Cek apakah token sudah expired (15 menit dari saat OTP diverifikasi)
        if (now()->diffInMinutes($record->created_at) > 15) {
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            return response()->json([
                'message' => 'Token sudah kedaluwarsa. Silakan ulangi proses reset password.',
            ], 422);
        }

        // Verifikasi token
        if (!Hash::check($request->token, $record->token)) {
            return response()->json([
                'message' => 'Token reset password tidak valid.',
            ], 422);
        }

        // Cari user dan update password
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        $user->forceFill([
            'password' => Hash::make($request->string('password')),
            'remember_token' => Str::random(60),
        ])->save();

        // Hapus token dari database
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        event(new PasswordReset($user));

        return response()->json([
            'message' => 'Password berhasil direset.',
        ]);
    }
}
