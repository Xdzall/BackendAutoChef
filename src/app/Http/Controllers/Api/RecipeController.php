<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use App\Models\Category;
use App\Models\Country;
use App\Models\Ingredients;
use App\Models\Step;
use App\Models\Unit;
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
            'category_id' => 'nullable|exists:category,id',
            'new_category' => 'nullable|string|unique:category,name_category',
            'country_id' => 'nullable|exists:country,id',
            'new_country' => 'nullable|string|unique:country,name_country',
            'ingredients' => 'required|json',
            'steps' => 'required|json',
        ]);

        $categoryId = $request->category_id;
        if ($request->filled('new_category')) {
            $category = Category::create(['name_category' => $request->new_category]);
            $categoryId = $category->id;
        }

        $countryId = $request->country_id;
        if ($request->filled('new_country')) {
            $country = Country::create(['name_country' => $request->new_country]);
            $countryId = $country->id;
        }

        if (!$categoryId || !$countryId) {
            return response()->json([
                'message' => 'Kategori dan Negara harus diisi atau dibuat baru.'
            ], 422);
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('resep-images', 's3');
            $imageUrl = Storage::disk('s3')->url($path);
        }

        $recipe = Recipe::create([
            'name_recipe' => $validatedData['name_recipe'],
            'image_url' => $imageUrl,
            'cooking_time_minutes' => $validatedData['cooking_time_minutes'],
            'category_id' => $categoryId,
            'country_id' => $countryId,
        ]);

        $stepsData = json_decode($request->steps, true); 
            $stepsBatch = [];
            foreach ($stepsData as $index => $instruction) {
                if (!empty($instruction)) {
                    $stepsBatch[] = [
                        'step_number' => $index + 1,
                        'instruction' => $instruction
                    ];
                }
            }
            if (!empty($stepsBatch)) {
                $recipe->steps()->createMany($stepsBatch);
            }

            // F. SIMPAN INGREDIENTS (Mirip Seeder: firstOrCreate + attach)
            $ingredientsData = json_decode($request->ingredients, true);

            foreach ($ingredientsData as $ing) {
                // $ing structure from React: { ingredient_id: "...", amount: "...", unit_id: "..." }
                
                // Logic: Jika user mengirim ID angka, pakai itu. 
                // Jika logic React Anda memungkinkan kirim teks baru, kita bisa pakai firstOrCreate.
                // Untuk keamanan, kita asumsikan ID dulu, tapi kalau null kita skip.
                
                $ingId = $ing['ingredient_id'] ?? null;
                $unitId = $ing['unit_id'] ?? null;
                
                // Jika ID bahan valid
                if ($ingId) {
                    $recipe->ingredients()->attach($ingId, [
                        'amount' => $ing['amount'],
                        'unit_id' => $unitId,
                        'notes' => $ing['notes'] ?? null,
                    ]);
                }
            }

        return new RecipeResource($recipe);
    }

    public function getCategories()
    {
        // Mengambil semua data kategori
        return response()->json(Category::all());
    }

    public function getCountries()
    {
        // Mengambil semua data negara
        return response()->json(Country::all());
    }
    public function getIngredients()
    {
        return response()->json(Ingredients::all());
    }

    public function getUnits()
    {
        return response()->json(Unit::all());
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