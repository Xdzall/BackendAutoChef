<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ingredients;
use Illuminate\Http\Request;
use App\Models\NutritionIngredient;

class IngredientController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name_ingredients' => 'required|string|unique:ingredients,name_ingredients',
        ]);

        $ingredient = Ingredients::create([
            'name_ingredients' => $request->name_ingredients
        ]);

        return response()->json(['message' => 'Bahan berhasil ditambahkan', 'data' => $ingredient], 201);
    }

    public function getIngredients()
    {
        // PERBAIKAN 1: Return variabel $ingredients agar relasi nutrisinya ikut terkirim ke website
        $ingredients = Ingredients::with('nutrition')->get();
        return response()->json($ingredients);
    }

    public function updateNutrition(Request $request, $id)
    {
        // PERBAIKAN 2: 'fat_grams' diubah menjadi 'fats_grams' agar sesuai dengan kolom database
        $request->validate([
            'calories' => 'required|numeric',
            'protein_grams' => 'required|numeric',
            'carbohydrates_grams' => 'required|numeric',
            'fats_grams' => 'required|numeric', 
            'fiber_grams' => 'required|numeric',
            'weight_per_piece' => 'nullable|numeric',
        ]);

        $ingredient = Ingredients::findOrFail($id);

        $nutrition = NutritionIngredient::updateOrCreate(
            ['ingredient_id' => $id],
            [
                'calories' => $request->calories,
                'protein_grams' => $request->protein_grams,
                'carbohydrates_grams' => $request->carbohydrates_grams,
                'fats_grams' => $request->fats_grams, // Diperbaiki di sini
                'fiber_grams' => $request->fiber_grams,
                'weight_per_piece' => $request->weight_per_piece,
                'last_updated' => now(),
            ]
        );

        return response()->json(['message' => 'Nutrisi berhasil diperbarui', 'data' => $nutrition], 200);
    }

}