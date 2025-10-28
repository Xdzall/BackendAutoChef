<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\Recipe;
use App\Models\Ingredients;
use App\Models\Unit;
use App\Models\Country;

class ContohResepSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $recipes = [
                [
                    'name' => 'Sate Ayam Madura',
                    'image' => 'sate-ayam.jpg',
                    'country_name' => 'Indonesia',
                    'cooking_time_minutes' => 45,
                    'ingredients' => [
                        ['name' => 'Daging Ayam Fillet', 'amount' => 500, 'unit' => 'gr', 'notes' => 'Potong dadu'],
                        ['name' => 'Kacang Tanah', 'amount' => 150, 'unit' => 'gr', 'notes' => 'Goreng dan haluskan'],
                        ['name' => 'Kecap Manis', 'amount' => 5, 'unit' => 'sdm', 'notes' => null],
                        ['name' => 'Bawang Merah', 'amount' => 8, 'unit' => 'siung', 'notes' => 'Untuk bumbu dan taburan'],
                    ],
                    'steps' => [
                        'Potong daging ayam bentuk dadu, tusuk dengan tusukan sate.',
                        'Haluskan bumbu: kacang tanah, bawang merah, bawang putih, kemiri, dan cabai.',
                        'Tumis bumbu halus hingga harum, tambahkan kecap manis, air, dan garam. Masak hingga mengental.',
                        'Lumuri sate dengan sebagian bumbu, diamkan 15 menit.',
                        'Bakar sate di atas bara api hingga matang sambil diolesi sisa bumbu.',
                        'Sajikan sate dengan sisa bumbu kacang, irisan bawang merah, dan lontong.',
                    ],
                ],
                [
                    'name' => 'Nasi Goreng Spesial',
                    'image' => 'nasi-goreng.jpg',
                    'country_name' => 'Indonesia',
                    'cooking_time_minutes' => 30,
                    'ingredients' => [
                        ['name' => 'Nasi Putih', 'amount' => 500, 'unit' => 'gr', 'notes' => 'Dingin lebih baik'],
                        ['name' => 'Telur Ayam', 'amount' => 2, 'unit' => 'butir', 'notes' => 'Kocok lepas'],
                        ['name' => 'Kecap Manis', 'amount' => 2, 'unit' => 'sdm', 'notes' => null],
                        ['name' => 'Bawang Putih', 'amount' => 3, 'unit' => 'siung', 'notes' => 'Cincang halus'],
                    ],
                    'steps' => [
                        'Panaskan minyak, tumis bawang putih hingga harum.',
                        'Masukkan telur, orak-arik hingga matang.',
                        'Masukkan nasi, kecap manis, garam, dan lada. Aduk hingga rata.',
                        'Masak hingga nasi goreng matang dan harum, sajikan hangat.',
                    ],
                ],
            ];

            foreach ($recipes as $data) {
                $filePath = database_path('seeders/images/' . $data['image']);

                if (!file_exists($filePath)) {
                    $this->command->error("❌ File gambar tidak ditemukan: {$data['image']}");
                    continue;
                }

                // Upload ke MinIO
                $path = Storage::disk('s3')->putFile('recipe-images', new File($filePath), 'public');
                if (!$path) {
                    $this->command->error("⚠️ Gagal upload gambar {$data['name']} ke MinIO.");
                    continue;
                }

                $imageUrl = Storage::disk('s3')->url($path);

                // Buat atau ambil country
                $country = Country::firstOrCreate(['name_country' => $data['country_name']]);

                // Buat resep
                $recipe = Recipe::create([
                    'name_recipe' => $data['name'],
                    'image_url' => $imageUrl,
                    'cooking_time_minutes' => $data['cooking_time_minutes'],
                    'category_id' => null,
                    'country_id' => $country->id,
                ]);

                $this->command->info("✅ Resep \"{$data['name']}\" berhasil dibuat (Negara: {$data['country_name']}).");

                // Tambah bahan
                foreach ($data['ingredients'] as $item) {
                    $ingredient = Ingredients::firstOrCreate(['name_ingredients' => $item['name']]);
                    $unit = Unit::where('abbreviation', $item['unit'])->first();

                    $recipe->ingredients()->attach([
                        $ingredient->id => [
                            'amount' => $item['amount'],
                            'unit_id' => $unit?->id,
                            'notes' => $item['notes'],
                        ],
                    ]);
                }

                // Tambah langkah
                $steps = [];
                foreach ($data['steps'] as $i => $step) {
                    $steps[] = ['step_number' => $i + 1, 'instruction' => $step];
                }
                $recipe->steps()->createMany($steps);

                $this->command->info("🧂 Bahan & langkah untuk {$data['name']} berhasil ditambahkan.\n");
            }
        });
    }
}
