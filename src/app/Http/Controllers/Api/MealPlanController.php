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
                    // ✅ Bungkus resep dengan RecipeResource
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

        // Ambil nama hari dari frontend
        $day = strtolower(trim($validated['day']));

        // Cari meal plan berdasarkan user + plan_name (hari)
        $mealPlan = MealPlan::where('user_id', $user->id)
            ->where('plan_name', $day)
            ->first();

        // Jika belum ada, buat baru
        if (!$mealPlan) {
            $mealPlan = MealPlan::create([
                'user_id' => $user->id,
                'plan_name' => $day,
                'description' => 'Rencana makan untuk hari ' . ucfirst($day),
            ]);
        }

        foreach ($validated['recipes'] as $r) {
            MealPlanRecipe::updateOrCreate(
                [
                    'meal_plan_id' => $mealPlan->id,
                    'recipe_id' => $r['recipe_id'],
                ],
                [
                    'quantity' => $r['quantity'] ?? 1, // default 1 porsi
                ]
            );
        }

        return response()->json([
            'message' => 'Meal plan saved successfully',
            'meal_plan' => $mealPlan->load('recipes.recipe'),
        ]);
    }



    public function destroy($day, $recipeId)
    {
        $user = Auth::user();

        // Cari meal plan berdasarkan plan_name (hari)
        $mealPlan = MealPlan::where('user_id', $user->id)
            ->where('plan_name', strtolower($day))
            ->firstOrFail();

        // Hapus resep dari meal plan (jika ada)
        $deleted = $mealPlan->recipes()->where('recipe_id', $recipeId)->delete();

        if ($deleted) {
            return response()->json(['message' => 'Recipe removed from meal plan']);
        }

        return response()->json(['message' => 'Recipe not found in this meal plan'], 404);
    }


    public function weeklyIngredients()
    {
        $user = Auth::user();

        $mealPlans = MealPlan::with('recipes.recipe.ingredients')
            ->where('user_id', $user->id)
            ->get();

        $ingredients = collect();

        foreach ($mealPlans as $mealPlan) {
            foreach ($mealPlan->recipes as $mealRecipe) {
                $multiplier = $mealRecipe->quantity ?? 1;

                foreach ($mealRecipe->recipe->ingredients as $ingredient) {
                    $ingredient->pivot->amount = ($ingredient->pivot->amount ?? 0) * $multiplier;
                    $ingredients->push($ingredient);
                }
            }
        }

        $grouped = $ingredients->groupBy('id')->map(function ($items) {
            $first = $items->first();
            $totalAmount = $items->sum(fn($item) => $item->pivot->amount ?? 0);

            return [
                'id_bahan' => $first->id,
                'nama_bahan' => $first->name_ingredients,
                'detail_bahan' => [
                    'jumlah' => $totalAmount,
                    'satuan' => $first->pivot->unit_id
                        ? \App\Models\Unit::find($first->pivot->unit_id)?->abbreviation ?? 'N/A'
                        : 'N/A',
                    'catatan' => $first->pivot->notes,
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
