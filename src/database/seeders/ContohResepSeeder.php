<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\Recipe;
use App\Models\Ingredients;
use App\Models\Unit;

class ContohResepSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            
            $filePath = database_path('seeders/images/sate-ayam.jpg');

            if (!file_exists($filePath)) {
                $this->command->error('File gambar contoh tidak ditemukan di: ' . $filePath);
                return;
            }

            $path = Storage::disk('s3')->putFile('recipe-images', new File($filePath), 'public');
            $url = Storage::disk('s3')->url($path);

            
            if (!$path) {
                $this->command->error('Gagal meng-upload file ke MinIO.');
                return;
            }

            $imageUrl = Storage::disk('s3')->url($path);
            $this->command->info('Gambar berhasil di-upload ke MinIO: ' . $imageUrl);

            $recipe = Recipe::create([
                'name_recipe' => 'Sate Ayam Madura',          
                'image_url' => $imageUrl,                     
                'cooking_time_minutes' => 45,            
                'category_id' => null,                     
                'country_id' => null,
            ]);
            $this->command->info('Resep "Sate Ayam Madura" berhasil dibuat.');
            
            $dagingAyam = Ingredients::firstOrCreate(['name_ingredients' => 'Daging Ayam Fillet']);
            $kacangTanah = Ingredients::firstOrCreate(['name_ingredients' => 'Kacang Tanah']);
            $kecapManis = Ingredients::firstOrCreate(['name_ingredients' => 'Kecap Manis']);
            $bawangMerah = Ingredients::firstOrCreate(['name_ingredients' => 'Bawang Merah']);

            $gram = Unit::where('abbreviation', 'gr')->first();
            $sdm = Unit::where('abbreviation', 'sdm')->first();
            $siung = Unit::where('abbreviation', 'siung')->first();

            $recipe->ingredients()->attach([
                $dagingAyam->id => ['amount' => 500, 'unit_id' => $gram->id, 'notes' => 'Potong dadu'],
                $kacangTanah->id => ['amount' => 150, 'unit_id' => $gram->id, 'notes' => 'Goreng dan haluskan'],
                $kecapManis->id => ['amount' => 5, 'unit_id' => $sdm->id, 'notes' => null],
                $bawangMerah->id => ['amount' => 8, 'unit_id' => $siung->id, 'notes' => 'Untuk bumbu dan taburan'],
            ]);
            $this->command->info('Bahan-bahan berhasil ditambahkan.');

            $recipe->steps()->createMany([
                ['step_number' => 1, 'instruction' => 'Potong daging ayam bentuk dadu, tusuk dengan tusukan sate.'],
                ['step_number' => 2, 'instruction' => 'Haluskan bumbu: kacang tanah, bawang merah, bawang putih, kemiri, dan cabai.'],
                ['step_number' => 3, 'instruction' => 'Tumis bumbu halus hingga harum, tambahkan kecap manis, air, dan garam. Masak hingga mengental.'],
                ['step_number' => 4, 'instruction' => 'Lumuri sate dengan sebagian bumbu, diamkan 15 menit.'],
                ['step_number' => 5, 'instruction' => 'Bakar sate di atas bara api hingga matang sambil diolesi sisa bumbu.'],
                ['step_number' => 6, 'instruction' => 'Sajikan sate dengan sisa bumbu kacang, irisan bawang merah, dan lontong.'],
            ]);
            $this->command->info('Langkah-langkah memasak berhasil ditambahkan.');
        });
    }
}