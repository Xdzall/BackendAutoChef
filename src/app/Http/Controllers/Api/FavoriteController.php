<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Menampilkan daftar semua resep yang difavoritkan oleh user yang sedang login.
     */
    public function index(Request $request)
    {
        // Mengambil user yang sedang login
        $user = $request->user();

        // Mengambil resep yang difavoritkan oleh user
        // Kita eager load 'category', 'country', dan 'ingredients' agar performa cepat
        $favorites = $user->favorites()
                        //   ->with(['category', 'country', 'ingredients.pivot.unit'])
                          ->with(['country'])
                          ->paginate(10);

        // Kita bisa menggunakan kembali RecipeResource karena bentuk datanya sama (Resep)
        return RecipeResource::collection($favorites);
    }

    /**
     * Menambah atau menghapus resep dari favorit (Toggle).
     */
    public function toggleFavorite(Request $request, Recipe $recipe)
    {
        $user = $request->user();

        // Melakukan toggle (attach jika belum ada, detach jika sudah ada)
        $result = $user->favorites()->toggle($recipe->id);

        // Mengecek hasil toggle untuk memberikan pesan yang sesuai
        if (count($result['attached']) > 0) {
            return response()->json([
                'message' => 'Resep berhasil ditambahkan ke favorit.',
                'is_favorited' => true
            ], 201); // 201 Created
        } else {
            return response()->json([
                'message' => 'Resep berhasil dihapus dari favorit.',
                'is_favorited' => false
            ], 200); // 200 OK
        }
    }
}