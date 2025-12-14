<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MealPlan;
use App\Models\MealPlanRecipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\RecipeResource;
use App\Http\Resources\IngredientResource;
use App\Http\Resources\WeeklyIngredientResource;

class MealPlanController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $plans = MealPlan::with('recipes.recipe.country')
            ->where('user_id', $user->id)
            ->get()
            ->groupBy('plan_name');

        $result = [];

        foreach ($plans as $day => $planList) {
            $recipes = [];

            foreach ($planList as $plan) {
                foreach ($plan->recipes as $mealRecipe) {
                    $recipes[] = new RecipeResource($mealRecipe->recipe);
                }
            }

            $result[$day] = [
                'day' => ucfirst($day),
                'recipes' => $recipes
            ];
        }

        return response()->json($result);
    }



    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'day' => 'required|string',
            'recipes' => 'required|array',
            'recipes.*.recipe_id' => 'required|exists:recipe,id',
            'recipes.*.quantity' => 'nullable|integer|min:1',
        ]);

        $day = strtolower(trim($validated['day']));

        $mealPlan = MealPlan::firstOrCreate(
            [
                'user_id' => $user->id,
                'plan_name' => $day,
            ],
            [
                'description' => 'Rencana makan untuk hari ' . ucfirst($day),
            ]
        );

        foreach ($validated['recipes'] as $r) {
            MealPlanRecipe::updateOrCreate(
                [
                    'meal_plan_id' => $mealPlan->id,
                    'recipe_id' => $r['recipe_id'],
                ],
                [
                    'quantity' => $r['quantity'] ?? 1,
                ]
            );
        }

        $statusCode = $mealPlan->wasRecentlyCreated ? 201 : 200;

        return response()->json([
            'message' => 'Meal plan saved successfully'
        ], $statusCode);
    }




    public function destroy($day, $recipeId)
    {
        $user = Auth::user();

        $mealPlan = MealPlan::where('user_id', $user->id)
            ->where('plan_name', strtolower($day))
            ->firstOrFail();

        $deleted = $mealPlan->recipes()->where('recipe_id', $recipeId)->delete();

        if ($deleted) {
            return response()->json(['message' => 'Recipe removed from meal plan']);
        }

        return response()->json(['message' => 'Recipe not found in this meal plan'], 404);
    }


    public function weeklyIngredients()
    {
        $user = Auth::user();

        $mealPlans = MealPlan::with('recipes.recipe.ingredients') // Eager load unit sekalian biar cepat
            ->where('user_id', $user->id)
            ->get();

        $summary = [];

        foreach ($mealPlans as $mealPlan) {
            foreach ($mealPlan->recipes as $mealRecipe) {
                $multiplier = $mealRecipe->quantity ?? 1;

                foreach ($mealRecipe->recipe->ingredients as $ingredient) {
                    $id = $ingredient->id;
                    
                    // Ambil nilai asli, jangan diubah
                    $originalAmount = $ingredient->pivot->amount ?? 0;
                    $calculatedAmount = $originalAmount * $multiplier;

                    // Jika bahan belum ada di summary, inisialisasi
                    if (!isset($summary[$id])) {
                        $summary[$id] = [
                            'ingredient' => $ingredient, // Simpan referensi objek untuk ambil nama/satuan nanti
                            'total_amount' => 0
                        ];
                    }

                    // Akumulasi jumlahnya
                    $summary[$id]['total_amount'] += $calculatedAmount;
                }
            }
        }

        // Ubah format array menjadi Collection agar bisa masuk ke Resource
        $grouped = collect($summary)->map(function ($item) {
            $ing = $item['ingredient'];
            
            return [
                'id_bahan' => $ing->id,
                'nama_bahan' => $ing->name_ingredients,
                'detail_bahan' => [
                    'jumlah' => $item['total_amount'], // Gunakan hasil akumulasi kita
                    'satuan' => $ing->unit 
                        ? $ing->unit->abbreviation 
                        : (\App\Models\Unit::find($ing->pivot->unit_id)?->abbreviation ?? 'N/A'),
                ],
            ];
        })->values();

        return WeeklyIngredientResource::collection($grouped);
    }

    public function ingredients($day)
    {
        $user = Auth::user();

        $mealPlan = MealPlan::with('recipes.recipe.ingredients')
            ->where('user_id', $user->id)
            ->where('plan_name', strtolower($day))
            ->first();

        if (!$mealPlan) {
            return response()->json(['message' => 'No meal plan found for this day'], 404);
        }

        $ingredients = collect();

        foreach ($mealPlan->recipes as $mealRecipe) {
            $multiplier = $mealRecipe->quantity ?? 1;

            foreach ($mealRecipe->recipe->ingredients as $ingredient) {
                $ingredient->pivot->amount = ($ingredient->pivot->amount ?? 0) * $multiplier;
                $ingredients->push($ingredient);
            }
        }

        return IngredientResource::collection($ingredients);
    }

}
