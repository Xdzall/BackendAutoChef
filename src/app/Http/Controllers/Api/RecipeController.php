<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use App\Models\Category;
use App\Models\Country;
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

    // --- FITUR CREATE (BUAT RESEP BARU) ---
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

        // Simpan Langkah-langkah
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

        // Simpan Bahan-bahan
        $ingredientsData = json_decode($request->ingredients, true);
        foreach ($ingredientsData as $ing) {
            $ingId = $ing['ingredient_id'] ?? null;
            $unitId = $ing['unit_id'] ?? null;
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
        return response()->json(Category::all());
    }

    public function getByCategory($categoryId)
    {
        $category = Category::find($categoryId);
        
        if (!$category) {
            return response()->json([
                'message' => 'Kategori tidak ditemukan'
            ], 404);
        }
        $recipes = Recipe::where('category_id', $categoryId)->get();
        return RecipeResource::collection($recipes);
    }

    public function getCountries()
    {
        return response()->json(Country::all());
    }
    
    public function show(Recipe $recipe)
    {
        $recipe->load(['steps', 'ingredients.nutrition']);
        return new RecipeResource($recipe);
    }

    public function getRecipeList()
    {
        // Hanya mengambil kolom id dan name_recipe agar prosesnya sangat cepat
        // 'name_recipe as nama_resep' digunakan agar formatnya sesuai dengan ekspektasi React
        $recipes = Recipe::select('id', 'name_recipe as nama_resep')
                         ->orderBy('name_recipe', 'asc')
                         ->get();

        return response()->json([
            'data' => $recipes
        ]);
    }

    // --- FITUR UPDATE (MENGEDIT RESEP YANG ADA) ---
    public function update(Request $request, Recipe $recipe)
    {
        $request->validate([
            'name_recipe' => 'sometimes|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'cooking_time_minutes' => 'sometimes|integer',
            'category_id' => 'nullable|exists:category,id',
            'new_category' => 'nullable|string|unique:category,name_category',
            'country_id' => 'nullable|exists:country,id',
            'new_country' => 'nullable|string|unique:country,name_country',
            'ingredients' => 'nullable|json',
            'steps' => 'nullable|json',
        ]);

        // 1. Update Kategori (Jika ada perubahan / buat baru)
        $categoryId = $request->category_id ?? $recipe->category_id;
        if ($request->filled('new_category')) {
            $category = Category::create(['name_category' => $request->new_category]);
            $categoryId = $category->id;
        }

        // 2. Update Negara (Jika ada perubahan / buat baru)
        $countryId = $request->country_id ?? $recipe->country_id;
        if ($request->filled('new_country')) {
            $country = Country::create(['name_country' => $request->new_country]);
            $countryId = $country->id;
        }

        // 3. Update Gambar (Jika user mengupload gambar baru)
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('resep-images', 's3');
            $recipe->image_url = Storage::disk('s3')->url($path);
        }

        // 4. Update data resep inti
        if ($request->has('name_recipe')) $recipe->name_recipe = $request->name_recipe;
        if ($request->has('cooking_time_minutes')) $recipe->cooking_time_minutes = $request->cooking_time_minutes;
        $recipe->category_id = $categoryId;
        $recipe->country_id = $countryId;
        $recipe->save();

        // 5. Update Langkah-langkah
        if ($request->has('steps')) {
            $stepsData = json_decode($request->steps, true);
            if (is_array($stepsData)) {
                $recipe->steps()->delete(); // Hapus langkah lama
                $stepsBatch = [];
                foreach ($stepsData as $index => $instruction) {
                    $text = is_array($instruction) ? ($instruction['instruction'] ?? '') : $instruction;
                    if (!empty($text)) {
                        $stepsBatch[] = [
                            'step_number' => $index + 1,
                            'instruction' => $text
                        ];
                    }
                }
                if (!empty($stepsBatch)) {
                    $recipe->steps()->createMany($stepsBatch); // Masukkan langkah baru
                }
            }
        }

        // 6. Update Bahan-bahan menggunakan sync()
        if ($request->has('ingredients')) {
            $ingredientsData = json_decode($request->ingredients, true);
            if (is_array($ingredientsData)) {
                $syncData = [];
                foreach ($ingredientsData as $ing) {
                    $ingId = $ing['ingredient_id'] ?? null;
                    if ($ingId) {
                        // Susun array untuk sync tabel pivot ingredients_details
                        $syncData[$ingId] = [
                            'amount' => $ing['amount'],
                            'unit_id' => $ing['unit_id'],
                            'notes' => $ing['notes'] ?? null,
                        ];
                    }
                }
                // sync otomatis menghapus bahan lama yang tak ada di array, dan menambah yang baru
                $recipe->ingredients()->sync($syncData); 
            }
        }

        $recipe->load(['steps', 'ingredients.nutrition']);
        return response()->json([
            'message' => 'Resep berhasil diperbarui!',
            'data' => new RecipeResource($recipe)
        ]);
    }

    public function destroy(Recipe $recipe)
    {
        $recipe->delete();
        return response()->noContent();
    }
}