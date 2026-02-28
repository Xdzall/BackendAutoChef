<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ingredients;
use Illuminate\Http\Request;

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
        return response()->json(Ingredients::all());
    }
}