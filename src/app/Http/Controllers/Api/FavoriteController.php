<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $favorites = $user->favorites()
                        //   ->with(['category', 'country', 'ingredients.pivot.unit'])
                          ->with(['country'])
                          ->paginate(10);

        return RecipeResource::collection($favorites);
    }

    public function toggleFavorite(Request $request, Recipe $recipe)
    {
        $user = $request->user();

        $result = $user->favorites()->toggle($recipe->id);

        if (count($result['attached']) > 0) {
            return response()->json([
                'message' => 'Resep berhasil ditambahkan ke favorit.',
                'is_favorited' => true
            ], 201);
        } else {
            return response()->json([
                'message' => 'Resep berhasil dihapus dari favorit.',
                'is_favorited' => false
            ], 200);
        }
    }
}