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
                [
                    'name' => 'Udang Saus Padang',
                    'image' => 'udang-saus-padang.jpg',
                    'country_name' => 'Indonesia',
                    'cooking_time_minutes' => 60,
                    'ingredients' => [
                        ['name' => 'Udang', 'amount' => 350, 'unit' => 'gr', 'notes' => 'Goreng sebentar 1 menit'],
                        ['name' => 'Jagung Manis', 'amount' => 2, 'unit' => 'buah', 'notes' => 'Sudah dimasak'],
                        ['name' => 'Tomat', 'amount' => 1, 'unit' => 'buah', 'notes' => 'Potong-potong'],
                        ['name' => 'Cabe Hijau', 'amount' => 2, 'unit' => 'buah', 'notes' => 'Iris serong'],
                        ['name' => 'Cabe Merah Besar', 'amount' => 2, 'unit' => 'buah', 'notes' => 'Iris serong'],
                        ['name' => 'Bawang Bombay', 'amount' => 1, 'unit' => 'buah', 'notes' => 'Iris'],
                        ['name' => 'Daun Bawang', 'amount' => 1, 'unit' => 'batang', 'notes' => 'Iris'],
                        ['name' => 'Telur', 'amount' => 1, 'unit' => 'butir', 'notes' => 'Kocok lepas'],
                        ['name' => 'Air', 'amount' => 500, 'unit' => 'ml', 'notes' => 'Kurang lebih'],
                        ['name' => 'Larutan Maizena', 'amount' => 1, 'unit' => 'sdt', 'notes' => 'Opsional, jika ingin lebih kental'],

                        ['name' => 'Cabe Merah Keriting', 'amount' => 8, 'unit' => 'buah', 'notes' => 'Untuk bumbu halus'],
                        ['name' => 'Jahe', 'amount' => 1, 'unit' => 'ruas jari', 'notes' => 'Untuk bumbu halus'],
                        ['name' => 'Bawang Putih', 'amount' => 3, 'unit' => 'siung', 'notes' => 'Untuk bumbu halus'],
                        ['name' => 'Bawang Merah', 'amount' => 5, 'unit' => 'siung', 'notes' => 'Untuk bumbu halus'],

                        ['name' => 'Sasa Saus Tomat', 'amount' => 1, 'unit' => 'sdm', 'notes' => 'Untuk bahan saus'],
                        ['name' => 'Sasa Saus Sambal Asli', 'amount' => 2, 'unit' => 'sdm', 'notes' => 'Untuk bahan saus'],
                        ['name' => 'Saus Tiram', 'amount' => 1.5, 'unit' => 'sdm', 'notes' => 'Untuk bahan saus'],
                        ['name' => 'Minyak Wijen', 'amount' => 1, 'unit' => 'sdm', 'notes' => 'Untuk bahan saus'],
                        ['name' => 'Kecap Inggris', 'amount' => 1, 'unit' => 'sdm', 'notes' => 'Untuk bahan saus'],
                        ['name' => 'Kecap Ikan', 'amount' => 1, 'unit' => 'sdm', 'notes' => 'Untuk bahan saus'],
                        ['name' => 'Garam', 'amount' => 0.5, 'unit' => 'sdt', 'notes' => 'Untuk bahan saus'],
                        ['name' => 'Gula Pasir', 'amount' => 2, 'unit' => 'sdm', 'notes' => 'Untuk bahan saus'],
                        ['name' => 'Lada Bubuk', 'amount' => 0.5, 'unit' => 'sdt', 'notes' => 'Untuk bahan saus'],
                        ['name' => 'Sasa Bubuk MSG', 'amount' => 0.5, 'unit' => 'sdt', 'notes' => 'Untuk bahan saus'],
                    ],
                    'steps' => [
                        'Blender bahan-bahan untuk bumbu halus, sisihkan.',
                        'Campurkan semua bahan saus dalam satu wadah, aduk rata.',
                        'Goreng udang selama 1 menit, angkat dan tiriskan.',
                        'Tumis bumbu halus hingga harum, kemudian masukkan campuran bahan saus.',
                        'Masukkan bawang bombay dan masak hingga sedikit layu.',
                        'Tambahkan air secukupnya dan biarkan mendidih.',
                        'Masukkan kocokan telur sambil diaduk agar berserabut.',
                        'Masukkan jagung manis dan udang, masak kembali selama 2-3 menit.',
                        'Masukkan cabe merah, cabe hijau, tomat, dan daun bawang, aduk rata kembali.',
                        'Jika dirasa kurang kental, tambahkan larutan maizena. Koreksi rasa dan sajikan.',
                    ],
                ],
                [
                    'name' => 'Ayam Kecap Mentega',
                    'image' => 'ayam-kecap-mentega.jpg',
                    'country_name' => 'Indonesia',
                    'cooking_time_minutes' => 55,
                    'ingredients' => [
                        ['name' => 'Ayam', 'amount' => 0.5, 'unit' => 'ekor', 'notes' => 'Potong-potong kecil'],
                        ['name' => 'Bawang Putih Halus', 'amount' => 2, 'unit' => 'siung', 'notes' => 'Untuk marinasi'],
                        ['name' => 'Garam', 'amount' => 1.5, 'unit' => 'sdt', 'notes' => null],
                        ['name' => 'Minyak Goreng', 'amount' => 500, 'unit' => 'ml', 'notes' => 'Untuk menggoreng ayam'],

                        ['name' => 'Bawang Putih', 'amount' => 4, 'unit' => 'siung', 'notes' => 'Memarkan lalu cincang halus'],
                        ['name' => 'Bawang Bombay', 'amount' => 0.5, 'unit' => 'buah', 'notes' => 'Iris memanjang'],
                        ['name' => 'Kecap Manis', 'amount' => 3, 'unit' => 'sdm', 'notes' => null],
                        ['name' => 'Kecap Inggris', 'amount' => 2, 'unit' => 'sdm', 'notes' => null],
                        ['name' => 'Air', 'amount' => 50, 'unit' => 'ml', 'notes' => null],
                        ['name' => 'Gula Pasir', 'amount' => 0.5, 'unit' => 'sdt', 'notes' => null],
                        ['name' => 'Merica Bubuk', 'amount' => 1, 'unit' => 'sdt', 'notes' => null],
                        ['name' => 'Mentega', 'amount' => 3, 'unit' => 'sdm', 'notes' => null],
                    ],
                    'steps' => [
                        'Cuci bersih ayam lalu baluri seluruh permukaannya dengan bawang putih halus dan garam. Marinasi (diamkan) selama 1 jam di dalam kulkas.',
                        'Goreng ayam yang telah dimarinasi hingga matang. Angkat, tiriskan, dan sisihkan.',
                        'Panaskan 2 sdm minyak, tumis bawang putih cincang hingga harum.',
                        'Masukkan kecap manis dan kecap inggris. Aduk rata.',
                        'Tuangkan air lalu aduk rata agar tidak gosong.',
                        'Bumbui dengan garam, gula pasir, dan merica. Aduk hingga rata.',
                        'Masukkan ayam yang sudah digoreng dan irisan bawang bombay. Aduk hingga rata.',
                        'Terakhir, masukkan mentega. Masak hingga meletup-letup dan bumbu meresap. Angkat.',
                        'Siap disajikan selagi hangat.',
                    ],
                ],
                [
                    'name' => 'Tongkol Suwir',
                    'image' => 'tongkol-suwir.jpg',
                    'country_name' => 'Indonesia',
                    'cooking_time_minutes' => 40,
                    'ingredients' => [
                        ['name' => 'Ikan Tongkol', 'amount' => 250, 'unit' => 'gr', 'notes' => 'Bisa pindang atau potongan segar'],
                        ['name' => 'Kemangi', 'amount' => 1, 'unit' => 'ikat', 'notes' => 'Petiki daunnya'],
                        ['name' => 'Saus Tiram', 'amount' => 1, 'unit' => 'sdm', 'notes' => null],
                        ['name' => 'Gula Pasir', 'amount' => 0.5, 'unit' => 'sdt', 'notes' => 'Untuk memberi rasa manis'],
                        ['name' => 'Lada Bubuk', 'amount' => 0.25, 'unit' => 'sdt', 'notes' => null],
                        ['name' => 'Sasa Bumbu Ekstrak Daging Sapi', 'amount' => 1, 'unit' => 'sdt', 'notes' => 'Untuk baluran dan bumbu'],
                        ['name' => 'Air', 'amount' => 50, 'unit' => 'ml', 'notes' => 'Sedikit saja'],
                        ['name' => 'Minyak Goreng', 'amount' => 30, 'unit' => 'ml', 'notes' => 'Untuk menggoreng dan menumis'],
                        ['name' => 'Cabai Rawit', 'amount' => 10, 'unit' => 'buah', 'notes' => 'Untuk bumbu halus'],
                        ['name' => 'Cabai Merah Keriting', 'amount' => 5, 'unit' => 'buah', 'notes' => 'Untuk bumbu halus'],
                        ['name' => 'Bawang Merah', 'amount' => 5, 'unit' => 'siung', 'notes' => 'Untuk bumbu halus'],
                        ['name' => 'Bawang Putih', 'amount' => 3, 'unit' => 'siung', 'notes' => 'Untuk bumbu halus'],
                        ['name' => 'Lengkuas', 'amount' => 2, 'unit' => 'cm', 'notes' => 'Untuk bumbu halus'],
                        ['name' => 'Serai', 'amount' => 1, 'unit' => 'batang', 'notes' => 'Ambil bagian putihnya'],
                    ],
                    'steps' => [
                    'Baluri ikan tongkol dengan setengah bagian Sasa Bumbu Ekstrak Daging Sapi, lalu goreng hingga matang. Angkat dan suwir-suwir dagingnya.',
                    'Haluskan semua bahan bumbu halus (cabai, bawang, lengkuas, serai) dengan blender atau ulekan.',
                    'Panaskan sedikit minyak, tumis bumbu halus hingga harum dan matang.',
                    'Tambahkan sedikit air, lalu masukkan saus tiram, gula, lada, dan sisa Sasa Bumbu Ekstrak Daging Sapi. Aduk rata.',
                    'Masukkan ikan tongkol yang sudah disuwir, aduk hingga bumbu tercampur rata dan meresap.',
                    'Terakhir, masukkan daun kemangi, aduk sebentar hingga sedikit layu.',
                    'Koreksi rasa, angkat, dan nikmati bersama nasi putih hangat.',
                    ],
                ],
                [
                    'name' => 'Telur Dadar Krispi',
                    'image' => 'telur-dadar-krispi.jpg',
                    'country_name' => 'Indonesia',
                    'cooking_time_minutes' => 20,
                    'ingredients' => [
                        ['name' => 'Telur Ayam', 'amount' => 2, 'unit' => 'butir', 'notes' => null],
                        ['name' => 'Garam', 'amount' => 0.25, 'unit' => 'sdt', 'notes' => null],
                        ['name' => 'Sasa Tepung Bumbu Kentucky', 'amount' => 100, 'unit' => 'gr', 'notes' => 'Untuk adonan basah dan kering'],
                        ['name' => 'Air', 'amount' => 50, 'unit' => 'ml', 'notes' => 'Untuk adonan basah'],
                        ['name' => 'Minyak Goreng', 'amount' => 200, 'unit' => 'ml', 'notes' => 'Untuk menggoreng'],
                    ],
                    'steps' => [ 'Dalam mangkuk, campurkan telur dan garam, lalu kocok lepas.', 'Panaskan sedikit minyak di teflon, buat telur dadar tipis hingga matang. Angkat.', 'Potong telur dadar yang sudah matang menjadi 4 bagian atau sesuai selera.', 'Siapkan dua wadah: satu untuk adonan basah (campurkan Sasa Tepung Bumbu Kentucky dengan air secukupnya), satu lagi untuk tepung kering.', 'Celupkan setiap potongan telur ke dalam adonan basah hingga seluruh permukaan tertutup.', 'Balurkan potongan telur ke dalam tepung kering sambil sedikit diremas agar tepung menempel sempurna.', 'Panaskan minyak yang cukup banyak, goreng telur yang sudah ditepungi hingga matang dan berwarna kuning kecoklatan.', 'Angkat, tiriskan, dan sajikan segera.', ],
                ],

                [
                    'name' => 'Udang Saus Mentega',
                    'image' => 'udang-saus-mentega.jpg',
                    'country_name' => 'Indonesia',
                    'cooking_time_minutes' => 35,
                    'ingredients' => [
                        ['name' => 'Udang Kupas', 'amount' => 250, 'unit' => 'gr', 'notes' => 'Belah punggung dan buang kotorannya'],
                        ['name' => 'Blue Band Serbaguna', 'amount' => 4, 'unit' => 'sdm', 'notes' => 'Atau mentega/margarin lain'],
                        ['name' => 'Bawang Bombay', 'amount' => 1, 'unit' => 'buah', 'notes' => 'Iris tipis'],
                        ['name' => 'Bawang Merah', 'amount' => 3, 'unit' => 'siung', 'notes' => 'Cincang halus'],
                        ['name' => 'Bawang Putih', 'amount' => 2, 'unit' => 'siung', 'notes' => 'Cincang halus'],
                        ['name' => 'Daun Bawang', 'amount' => 1, 'unit' => 'batang', 'notes' => 'Pisahkan bagian putih dan hijau'],
                        ['name' => 'Jahe', 'amount' => 3, 'unit' => 'cm', 'notes' => 'Geprek'],
                        ['name' => 'Tomat', 'amount' => 1, 'unit' => 'buah', 'notes' => 'Buang biji dan potong kecil'],
                        ['name' => 'Kecap Manis', 'amount' => 2, 'unit' => 'sdm', 'notes' => null],
                        ['name' => 'Kecap Asin', 'amount' => 1, 'unit' => 'sdm', 'notes' => null],
                        ['name' => 'Saus Tiram', 'amount' => 1, 'unit' => 'sdm', 'notes' => null],
                        ['name' => 'Saus Tomat', 'amount' => 1, 'unit' => 'sdm', 'notes' => null],
                    ],
                    'steps' => [ 'Cuci bersih udang yang sudah dikupas, belah punggungnya dan buang kotorannya. Sisihkan.', 'Lelehkan Blue Band Serbaguna dalam wajan, goreng udang hingga setengah matang atau berubah warna. Angkat dan sisihkan udang.', 'Gunakan wajan dan sisa mentega yang sama untuk menumis irisan bawang bombay, bagian putih daun bawang, bawang merah cincang, bawang putih cincang, tomat, dan jahe geprek.', 'Tumis semua bumbu hingga layu dan harum.', 'Masukkan kecap manis, kecap asin, saus tiram, saus tomat, dan kecap inggris. Aduk hingga semua saus tercampur rata.', 'Koreksi rasa, sesuaikan jika perlu.', 'Masukkan kembali udang yang sudah digoreng dan irisan daun bawang bagian hijau.', 'Aduk rata dan masak hingga bumbu meresap sempurna ke dalam udang.', 'Angkat dan sajikan udang saus mentega selagi hangat.', ],
                ],

                [
                    'name' => 'Bola-Bola Tahu Ayam Krispi',
                    'image' => 'bola-bola-tahu-ayam-krispi.jpg',
                    'country_name' => 'Indonesia',
                    'cooking_time_minutes' => 40,
                    'ingredients' => [
                        ['name' => 'Tahu Putih', 'amount' => 500, 'unit' => 'gr', 'notes' => 'Haluskan'],
                        ['name' => 'Palmia Margarin Serbaguna', 'amount' => 6, 'unit' => 'sdm', 'notes' => '3 sdm menumis, 3 sdm menggoreng'],
                        ['name' => 'Daging Ayam Cincang', 'amount' => 150, 'unit' => 'gr', 'notes' => null],
                        ['name' => 'Wortel', 'amount' => 100, 'unit' => 'gr', 'notes' => 'Parut halus'],
                        ['name' => 'Tepung Maizena', 'amount' => 100, 'unit' => 'gr', 'notes' => 'Untuk adonan'],
                        ['name' => 'Tepung Roti', 'amount' => 200, 'unit' => 'gr', 'notes' => 'Untuk baluran'],
                        ['name' => 'Telur', 'amount' => 3, 'unit' => 'butir', 'notes' => '1 untuk adonan, 2 untuk pencelup'],
                        ['name' => 'Daun Bawang', 'amount' => 1, 'unit' => 'batang', 'notes' => 'Iris halus'],
                        ['name' => 'Minyak Sayur', 'amount' => 500, 'unit' => 'ml', 'notes' => 'Untuk menggoreng'],
                        ['name' => 'Bawang Merah', 'amount' => 5, 'unit' => 'siung', 'notes' => 'Untuk bumbu halus'],
                        ['name' => 'Bawang Putih', 'amount' => 3, 'unit' => 'siung', 'notes' => 'Untuk bumbu halus'],
                        ['name' => 'Lada', 'amount' => 0.5, 'unit' => 'sdt', 'notes' => 'Untuk bumbu halus'],
                        ['name' => 'Garam', 'amount' => 1, 'unit' => 'sdt', 'notes' => 'Untuk bumbu halus'],
                    ],
                    'steps' => [ 'Haluskan tahu dalam sebuah wadah besar.', 'Siapkan bumbu halus: ulek atau blender bawang merah, bawang putih, lada, dan garam.', 'Panaskan 3 sdm Palmia Margarin, tumis bumbu halus hingga harum.', 'Masukkan ayam cincang dan wortel parut ke dalam tumisan. Aduk dan masak hingga matang, lalu angkat.', 'Campurkan tumisan bumbu ke dalam tahu yang sudah dihaluskan. Aduk rata.', 'Tambahkan 1 butir telur, tepung maizena, dan irisan daun bawang ke dalam adonan. Aduk kembali hingga semua bahan tercampur sempurna.', 'Bentuk adonan menjadi bola-bola dengan ukuran sesuai selera hingga adonan habis.', 'Siapkan dua wadah: satu untuk 2 butir telur kocok, dan satu lagi untuk tepung roti.', 'Celupkan setiap bola tahu ke dalam kocokan telur, lalu gulingkan dan balur dengan tepung roti hingga tertutup rata.', 'Panaskan minyak sayur bersama 3 sdm sisa Palmia Margarin dengan api sedang.', 'Goreng bola-bola tahu hingga berwarna kuning kecoklatan dan matang.', 'Angkat, tiriskan, dan hidangkan selagi hangat dengan saus sambal atau mayones.', ],
                ],

                [
                    'name' => 'Martabak Telur Mini',
                    'image' => 'martabak-telur-mini.jpg',
                    'country_name' => 'Indonesia',
                    'cooking_time_minutes' => 35,
                    'ingredients' => [
                        ['name' => 'Kulit Lumpia', 'amount' => 20, 'unit' => 'lembar', 'notes' => null],
                        ['name' => 'Daging Cincang', 'amount' => 400, 'unit' => 'gr', 'notes' => null],
                        ['name' => 'Telur', 'amount' => 6, 'unit' => 'butir', 'notes' => 'Kocok lepas'],
                        ['name' => 'Daun Bawang', 'amount' => 6, 'unit' => 'batang', 'notes' => 'Iris halus'],
                        ['name' => 'Bawang Bombay', 'amount' => 125, 'unit' => 'gr', 'notes' => 'Cincang halus'],
                        ['name' => 'Bumbu Kari Bubuk', 'amount' => 1, 'unit' => 'sdt', 'notes' => null],
                        ['name' => 'Lada Bubuk', 'amount' => 0.25, 'unit' => 'sdt', 'notes' => null],
                        ['name' => 'Garam', 'amount' => 0.25, 'unit' => 'sdt', 'notes' => null],
                        ['name' => 'Margarin', 'amount' => 6, 'unit' => 'sdm', 'notes' => '2 sdm menumis, 4 sdm menggoreng'],
                        ['name' => 'Minyak Goreng', 'amount' => 100, 'unit' => 'ml', 'notes' => 'Untuk menggoreng'],
                    ],
                    'steps' => [ 'Panaskan 2 sdm margarin, tumis bawang bombai hingga harum dan layu.', 'Masukkan daging cincang ke dalam tumisan, aduk hingga daging berubah warna.', 'Taburkan bumbu kari, lada bubuk, dan garam. Aduk rata dan masak hingga daging matang dan mengering. Angkat dan sisihkan.', 'Dalam wadah terpisah, kocok lepas telur. Masukkan tumisan daging yang sudah dingin dan irisan daun bawang, aduk rata.', 'Ambil selembar kulit lumpia, letakkan dua sendok makan adonan isi di tengahnya.', 'Lipat kulit lumpia hingga menutupi isian, bentuk seperti amplop.', 'Panaskan 4 sdm margarin dan minyak goreng dalam wajan datar dengan api kecil.', 'Goreng martabak hingga kering dan berwarna kuning kecokelatan di kedua sisinya.', 'Angkat, tiriskan, dan hidangkan selagi hangat dengan acar mentimun dan cabai rawit.', ],
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
