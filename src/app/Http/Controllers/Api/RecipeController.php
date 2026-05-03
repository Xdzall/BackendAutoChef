<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use App\Models\Category;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        $query = Recipe::query();

        if ($request->has('search')) {
            $searchTerm = strtolower($request->input('search'));
            
            // 1. Tokenisasi kata pencarian mentah dari user
            $rawSearchTokens = explode(' ', $searchTerm);
            
            // 2. Ambil semua vektor resep
            $recipeVectors = DB::table('recipe_vectors')->get();
            
            // 3. Bangun "Kamus Vocabulary" dari seluruh kata yang ada di vektor database
            $vocabulary = [];
            foreach ($recipeVectors as $rv) {
                $vectorData = json_decode($rv->vector, true);
                if (is_array($vectorData)) {
                    foreach (array_keys($vectorData) as $word) {
                        $vocabulary[$word] = true;
                    }
                }
            }
            $vocabulary = array_keys($vocabulary);

            // 4. Proses Koreksi Typo menggunakan Levenshtein Distance
            $correctedTokens = [];
            foreach ($rawSearchTokens as $token) {
                $closestWord = $token;
                $shortestDistance = -1;
                
                // Logika Toleransi: 
                // Jika panjang kata <= 4 huruf, toleransi salah ketik maksimal 1 huruf.
                // Jika > 4 huruf, toleransi maksimal 2 huruf.
                $tolerance = strlen($token) <= 4 ? 1 : 2;

                // Jika kata sudah persis ada di kamus, tidak perlu dikoreksi
                if (in_array($token, $vocabulary)) {
                    $correctedTokens[] = $token;
                    continue; 
                }

                // Jika tidak ada, cari kata dengan ejaan terdekat di kamus
                foreach ($vocabulary as $word) {
                    // Fungsi bawaan PHP untuk menghitung jarak perbedaan string
                    $distance = levenshtein($token, $word);
                    
                    if ($distance <= $tolerance) {
                        if ($shortestDistance < 0 || $distance < $shortestDistance) {
                            $closestWord = $word;
                            $shortestDistance = $distance;
                        }
                    }
                }
                $correctedTokens[] = $closestWord; // Masukkan kata hasil koreksi
            }

            // 5. Buat Query Vector berdasarkan token yang SUDAH dikoreksi
            $queryVector = array_count_values($correctedTokens);
            $similarities = [];

            // 6. Hitung Cosine Similarity seperti biasa
            foreach ($recipeVectors as $rv) {
                $vectorData = json_decode($rv->vector, true);
                $score = $this->calculateCosineSimilarity($queryVector, $vectorData);
                
                if ($score > 0) {
                    $similarities[$rv->recipe_id] = $score;
                }
            }

            // 7. Urutkan berdasarkan skor tertinggi (descending)
            arsort($similarities);
            $matchedIds = array_keys($similarities);

            if (empty($matchedIds)) {
                return RecipeResource::collection([]);
            }

            // 8. Terapkan hasil pencarian ke query utama (dengan array_position untuk PostgreSQL)
            $idString = implode(',', $matchedIds);
            $query->whereIn('id', $matchedIds)
                ->orderByRaw("array_position(ARRAY[{$idString}]::bigint[], id)");
                
            $recipes = $query->paginate(100);
            return RecipeResource::collection($recipes);
        }

        $recipes = $query->paginate(100);
        return RecipeResource::collection($recipes);
    }

    public function recommendations(Request $request)
    {
        $user = $request->user();
        
        // Jika user belum login atau belum punya favorit, kembalikan rekomendasi default/populer
        if (!$user || $user->favorites()->count() == 0) {
            $randomRecipes = Recipe::inRandomOrder()->limit(9)->get();
            return RecipeResource::collection($randomRecipes);
        }

        // 1. Ambil ID resep favorit user
        $favoriteRecipeIds = $user->favorites()->pluck('recipe.id')->toArray();

        // 2. Ambil vektor dari resep-resep favorit untuk membentuk "User Profile Vector"
        $favoriteVectors = DB::table('recipe_vectors')
            ->whereIn('recipe_id', $favoriteRecipeIds)
            ->get();

        $userProfileVector = [];
        foreach ($favoriteVectors as $rv) {
            $vectorData = json_decode($rv->vector, true);
            foreach ($vectorData as $term => $weight) {
                if (!isset($userProfileVector[$term])) {
                    $userProfileVector[$term] = 0;
                }
                $userProfileVector[$term] += $weight; // Menggabungkan bobot
            }
        }

        // 3. Ambil vektor resep lain yang BELUM difavoritkan user
        $otherRecipeVectors = DB::table('recipe_vectors')
            ->whereNotIn('recipe_id', $favoriteRecipeIds)
            ->get();

        $similarities = [];

        // 4. Hitung kedekatan User Profile Vector dengan resep-resep lain
        foreach ($otherRecipeVectors as $rv) {
            $vectorData = json_decode($rv->vector, true);
            $score = $this->calculateCosineSimilarity($userProfileVector, $vectorData);
            $similarities[$rv->recipe_id] = $score;
        }

        // 5. Urutkan berdasarkan skor tertinggi dan ambil 9 teratas
        arsort($similarities);
        $topRecommendationIds = array_slice(array_keys($similarities), 0, 9);

        if (empty($topRecommendationIds)) {
            return RecipeResource::collection(Recipe::inRandomOrder()->limit(9)->get());
        }

        $idString = implode(',', $topRecommendationIds);
        $recommendedRecipes = Recipe::whereIn('id', $topRecommendationIds)
            ->orderByRaw("array_position(ARRAY[{$idString}]::bigint[], id)")
            ->get();

        return RecipeResource::collection($recommendedRecipes);
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

    private function calculateCosineSimilarity(array $vecA, array $vecB)
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        $allKeys = array_unique(array_merge(array_keys($vecA), array_keys($vecB)));

        foreach ($allKeys as $key) {
            $valA = $vecA[$key] ?? 0.0;
            $valB = $vecB[$key] ?? 0.0;

            $dotProduct += ($valA * $valB);
            $normA += pow($valA, 2);
            $normB += pow($valB, 2);
        }

        if ($normA == 0 || $normB == 0) return 0;

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}