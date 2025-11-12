<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;


class EmailVerificationController extends Controller
{
    public function verify(Request $request)
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['message' => 'Invalid or expired link'], 400);
        }

        $user = User::findOrFail($request->route('id'));

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        // Jika mode production, arahkan ke halaman web /openapp
        if (app()->environment('production')) {
            return redirect()->away(env('FRONTEND_URL') . '/openapp');
        }

        // Jika mode local/dev, arahkan langsung ke deep link Flutter
        return redirect()->away('autochef://email/verify?id=' . $user->id);
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
