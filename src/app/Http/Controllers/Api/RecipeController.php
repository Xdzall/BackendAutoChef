<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        $query = Recipe::query();

        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            
            $query->whereRaw('LOWER(name_recipe) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
        }

        $recipes = $query->paginate(100);
        
        return RecipeResource::collection($recipes);
    }

    public function recommendations()
    {
        $randomRecipes = Recipe::inRandomOrder()->limit(9)->get();

        return RecipeResource::collection($randomRecipes);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name_recipe' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'cooking_time_minutes' => 'required|integer',
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('resep-images', 's3');
            $imageUrl = Storage::disk('s3')->url($path);
        }

        $recipe = Recipe::create([
            'name_recipe' => $validatedData['name_recipe'],
            'image_url' => $imageUrl,
            'cooking_time_minutes' => $validatedData['cooking_time_minutes'],
        ]);

        return new RecipeResource($recipe);
    }

    public function show(Recipe $recipe)
    {
        $recipe->load(['steps', 'ingredients']);
        
        return new RecipeResource($recipe);
    }

    public function update(Request $request, Recipe $recipe)
    {

        return new RecipeResource($recipe);
    }


    public function destroy(Recipe $recipe)
    {
        
        $recipe->delete();
        
        return response()->noContent();
    }
}