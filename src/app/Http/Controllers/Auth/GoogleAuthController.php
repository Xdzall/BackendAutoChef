<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Google_Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    /**
     * Login atau register menggunakan Google ID Token dari mobile app.
     *
     * Flow:
     * 1. Mobile app melakukan Google Sign-In dan mendapat ID Token
     * 2. Mobile app mengirim ID Token ke endpoint ini
     * 3. Backend memverifikasi token menggunakan Google API
     * 4. Backend mencari/membuat user dan mengembalikan Sanctum token
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        // Verifikasi Google ID Token
        $googleUser = $this->verifyGoogleToken($request->id_token);

        if (!$googleUser) {
            return response()->json([
                'message' => 'Token Google tidak valid atau sudah kedaluwarsa.',
            ], 401);
        }

        $googleId = $googleUser['sub'];
        $email = $googleUser['email'];
        $name = $googleUser['name'] ?? explode('@', $email)[0];
        $avatar = $googleUser['picture'] ?? null;

        // Cari user berdasarkan google_id
        $user = User::where('google_id', $googleId)->first();

        if (!$user) {
            // Cari user berdasarkan email (mungkin sudah register manual)
            $user = User::where('email', $email)->first();

            if ($user) {
                // Hubungkan akun Google ke user yang sudah ada
                $user->update(['google_id' => $googleId]);
            } else {
                // Buat user baru
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'google_id' => $googleId,
                    'password' => null,
                    'email_verified_at' => now(), // Google sudah memverifikasi email
                ]);

                // Assign default role
                $user->assignRole('user');
            }
        }

        // Pastikan email terverifikasi untuk user Google
        if (is_null($user->email_verified_at)) {
            $user->update(['email_verified_at' => now()]);
        }

        // Buat Sanctum token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_url' => $user->profile_photo_url,
            ],
        ]);
    }

    /**
     * Verifikasi Google ID Token menggunakan Google API Client.
     */
    private function verifyGoogleToken(string $idToken): ?array
    {
        try {
            $client = new Google_Client([
                'client_id' => config('services.google.client_id'),
            ]);

            $payload = $client->verifyIdToken($idToken);

            if (!$payload) {
                return null;
            }

            // Pastikan email sudah terverifikasi oleh Google
            if (!($payload['email_verified'] ?? false)) {
                return null;
            }

            return $payload;
        } catch (\Exception $e) {
            Log::error('Google token verification failed: ' . $e->getMessage());
            return null;
        }
    }
}
