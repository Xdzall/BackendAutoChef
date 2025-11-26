<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, $id, $hash)
    {
        // Cek apakah link valid
        if (! $request->hasValidSignature()) {
            return response()->json(['message' => 'Invalid or expired verification link'], 403);
        }

        $user = User::findOrFail($id);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        // ✅ Arahkan ke halaman HTML yang akan handle deep link / Play Store
        return redirect()->away(env('APP_URL') . '/openapp.html?verified=true');
    }
}

// class EmailVerificationController extends Controller
// {
//     public function verify(Request $request)
//     {
//         if (! $request->hasValidSignature()) {
//             return response()->json(['message' => 'Invalid or expired link'], 400);
//         }

//         $user = User::findOrFail($request->route('id'));

//         if (! $user->hasVerifiedEmail()) {
//             $user->markEmailAsVerified();
//         }

//         // Jika masih tahap development (pakai ngrok)
//         if (app()->environment('local') || app()->environment('AutoChefDev')) {
//             // Langsung arahkan ke deep link aplikasi
//             return redirect()->away('autochef://email/verify?id=' . $user->id);
//         }

//         // Kalau di production baru arahkan ke halaman fallback /openapp
//         return redirect()->away(env('FRONTEND_URL') . '/openapp');
//     }


//}
