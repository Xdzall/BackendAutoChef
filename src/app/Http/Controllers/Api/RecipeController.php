<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecipeController extends Controller
{
    /**
     * Menampilkan daftar semua resep.
     */
    public function index(Request $request) // <-- Tambahkan (Request $request)
    {
        // Mulai query builder
        $query = Recipe::query();

        // Cek apakah ada parameter 'search' di request
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            
            // Tambahkan kondisi WHERE LIKE untuk mencari resep
            // Ini akan mencari resep yang namanya mengandung searchTerm
            $query->whereRaw('LOWER(name_recipe) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
        }

        // Ambil resep dengan pagination dari query yang sudah difilter
        $recipes = $query->paginate(100);
        
        // Bungkus hasilnya dengan RecipeResource
        return RecipeResource::collection($recipes);
    }

    public function recommendations()
    {
        // Ambil 9 resep secara acak dari database
        $randomRecipes = Recipe::inRandomOrder()->limit(9)->get();

        // Kembalikan menggunakan Resource yang sama agar format JSON konsisten
        // Kita tidak perlu ->items() agar frontend tahu ini bukan paginasi
        return RecipeResource::collection($randomRecipes);
    }

    /**
     * Menyimpan resep baru ke database.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name_recipe' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Gambar opsional
            'cooking_time_minutes' => 'required|integer',
            // Tambahkan validasi untuk langkah dan bahan jika perlu
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
        
        // TODO: Tambahkan logika untuk menyimpan langkah dan bahan yang berelasi

        return new RecipeResource($recipe);
    }

    /**
     * Menampilkan satu resep secara detail.
     */
    public function show(Recipe $recipe)
    {
        // Muat relasi steps dan ingredients sebelum dikirim
        $recipe->load(['steps', 'ingredients']);
        
        return new RecipeResource($recipe);
    }

    /**
     * Memperbarui resep yang sudah ada.
     */
    public function update(Request $request, Recipe $recipe)
    {
        // TODO: Tambahkan logika update resep
        // Anda bisa menggunakan logika yang mirip dengan method store()

        return new RecipeResource($recipe);
    }

    /**
     * Menghapus resep.
     */
    public function destroy(Recipe $recipe)
    {
        // TODO: Tambahkan logika untuk menghapus gambar dari MinIO jika ada
        
        $recipe->delete();
        
        return response()->noContent(); // Respons 204 No Content
    }
}