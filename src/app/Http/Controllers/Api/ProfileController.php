<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

    $request->validate([
        'name' => ['required','string','max:255'],
        'password' => ['nullable', 'confirmed', Password::default()],
        'current_password' => ['nullable', 'required_with:password', 'current_password'],
    ]);
    $user->name = $request->name;
    if ($request->filled('password')){
        if (!Hash::check($request->current_password, $user->password)) {
            return resposnse()->json([
                'message' => 'Password lama yang anda masukkan salah.',
                'errors' => ['current_password' => ['Password salah.']]
            ], 422);
        }
        $user->password = Hash::make($request->password);
    }
    $user->save();

    return response()->json([
        'message' => 'Profil berhasil diperbarui.',
        'user' => $user
    ]);
    }

    public function show(Request $request)
    {
        return response()->json($request->user());
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:2048'],
        ]);
        $user = $request->user();

        if ($request->hasFile('photo')) {

            if ($user->profile_photo_path) {
                Storage::disk('s3')->delete($user->profile_photo_path);
            }

            $path = $request->file('photo')->store('profile-photos', 's3');
            $user->profile_photo_path = $path;
            $user->save();
        }

        return response()->json([
            'message' => 'Foto profil berhasil diperbarui.',
            'profile_photo_url' => $user->profile_photo_url
        ]);
    }
}
