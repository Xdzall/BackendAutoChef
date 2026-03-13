<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;


class SatuanSeeder extends Seeder
{
    public function run(): void
    {
        // DB::table('unit')->truncate();

        $units = [
    ['name_unit' => 'Gram', 'abbreviation' => 'gr', 'type_unit' => 'weight', 'conversion_to_grams' => 1.0000],
    ['name_unit' => 'Kilogram', 'abbreviation' => 'kg', 'type_unit' => 'weight', 'conversion_to_grams' => 1000.0000],
    ['name_unit' => 'Mililiter', 'abbreviation' => 'ml', 'type_unit' => 'volume', 'conversion_to_grams' => 1.0000],
    ['name_unit' => 'Sendok Makan', 'abbreviation' => 'sdm', 'type_unit' => 'volume', 'conversion_to_grams' => 15.0000],
    ['name_unit' => 'Sendok Teh', 'abbreviation' => 'sdt', 'type_unit' => 'volume', 'conversion_to_grams' => 5.0000],
    ['name_unit' => 'Buah', 'abbreviation' => 'buah', 'type_unit' => 'quantity', 'conversion_to_grams' => null], 
    ['name_unit' => 'Siung', 'abbreviation' => 'siung', 'type_unit' => 'quantity', 'conversion_to_grams' => null], 
    ['name_unit' => 'Batang', 'abbreviation' => 'batang', 'type_unit' => 'quantity', 'conversion_to_grams' => null], 
    ['name_unit' => 'Butir', 'abbreviation' => 'butir', 'type_unit' => 'quantity', 'conversion_to_grams' => null], 
    ['name_unit' => 'Ruas Jari', 'abbreviation' => 'ruas jari', 'type_unit' => 'quantity', 'conversion_to_grams' => null], 
    ['name_unit' => 'Ekor', 'abbreviation' => 'ekor', 'type_unit' => 'quantity', 'conversion_to_grams' => null], 
    ['name_unit' => 'Secukupnya', 'abbreviation' => 'secukupnya', 'type_unit' => 'quantity', 'conversion_to_grams' => null], 
    ['name_unit' => 'Ikat', 'abbreviation' => 'ikat', 'type_unit' => 'quantity', 'conversion_to_grams' => null], 
    ['name_unit' => 'Sentimeter', 'abbreviation' => 'cm', 'type_unit' => 'quantity', 'conversion_to_grams' => null], 
    ['name_unit' => 'Lembar', 'abbreviation' => 'lembar', 'type_unit' => 'quantity', 'conversion_to_grams' => null],
];
    foreach ($units as $unit) {
                Unit::firstOrCreate(
                    ['name_unit' => $unit['name_unit']], // Database akan mengecek apakah 'Gram' sudah ada?
                    $unit // Jika belum ada, masukkan semua data ini
                );
            }
    }
}