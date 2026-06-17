<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;

class GenerateRecipeVectors extends Command
{
    // Nama command yang akan dipanggil di terminal atau observer
    protected $signature = 'recipe:generate-vectors';

    // Deskripsi command
    protected $description = 'Generate TF-IDF vectors for all recipes and save to recipe_vectors table';

    public function handle()
    {
        $this->info('Memulai kalkulasi TF-IDF...');

        // 1. Ambil semua resep beserta relasi kategori dan bahan
        // (Sesuaikan nama relasi jika perlu, sesuai dengan model Recipe)
        $recipes = Recipe::with(['category', 'ingredients'])->get();
        $totalDocs = $recipes->count();

        if ($totalDocs === 0) {
            $this->warn('Tidak ada data resep ditemukan.');
            return;
        }

        $corpus = [];
        $documentFrequency = [];

        // 2. Tahap Tokenisasi dan menghitung Document Frequency (DF)
        foreach ($recipes as $recipe) {
            // Kumpulkan teks: Nama resep + Kategori + Nama Bahan
            $text = $recipe->name_recipe . ' ';
            if ($recipe->category) {
                $text .= $recipe->category->name_category . ' ';
            }
            foreach ($recipe->ingredients as $ingredient) {
                $text .= ($ingredient->name_ingredients) . ' ';
            }

            // Bersihkan teks (huruf kecil, hilangkan tanda baca) dan pecah jadi array kata (token)
            $text = strtolower($text);
            preg_match_all('/\w+/u', $text, $matches);
            $tokens = $matches[0];

            // Hitung Term Frequency (TF) mentah untuk resep ini
            $termFrequency = array_count_values($tokens);
            $corpus[$recipe->id] = $termFrequency;

            // Hitung Document Frequency (DF): berapa banyak dokumen yang mengandung kata ini
            $uniqueTokens = array_unique($tokens);
            foreach ($uniqueTokens as $token) {
                if (!isset($documentFrequency[$token])) {
                    $documentFrequency[$token] = 0;
                }
                $documentFrequency[$token]++;
            }
        }

        // 3. Tahap Kalkulasi TF-IDF
        $vectorsToInsert = [];
        
        foreach ($corpus as $recipeId => $termFrequency) {
            $vector = [];
            foreach ($termFrequency as $term => $tfCount) {
                // Rumus IDF: log10( Total Dokumen / Dokumen yang mengandung Term )
                $df = $documentFrequency[$term];
                $idf = log10($totalDocs / $df);
                
                // Bobot TF-IDF
                $weight = $tfCount * $idf;
                
                if ($weight > 0) {
                    $vector[$term] = round($weight, 4);
                }
            }

            $vectorsToInsert[] = [
                'recipe_id' => $recipeId,
                'vector' => json_encode($vector),
            ];
        }

        // 4. Simpan ke Database
        DB::beginTransaction();
        try {
            DB::table('recipe_vectors')->truncate(); // Kosongkan data lama
            
            // Chunk insert agar memori tidak penuh jika data ribuan
            $chunks = array_chunk($vectorsToInsert, 500);
            foreach ($chunks as $chunk) {
                DB::table('recipe_vectors')->insert($chunk);
            }
            
            DB::commit();
            $this->info('Berhasil mengenerate vektor TF-IDF untuk ' . $totalDocs . ' resep!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Gagal menyimpan vektor: ' . $e->getMessage());
        }
    }
}
