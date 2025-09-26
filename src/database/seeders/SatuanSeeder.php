<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SatuanSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('unit')->truncate();

        DB::table('unit')->insert([
            ['name_unit' => 'Gram', 'abbreviation' => 'gr', 'type_unit' => 'weight', 'conversion_to_grams' => 1.0000],
            ['name_unit' => 'Kilogram', 'abbreviation' => 'kg', 'type_unit' => 'weight', 'conversion_to_grams' => 1000.0000],
            ['name_unit' => 'Mililiter', 'abbreviation' => 'ml', 'type_unit' => 'volume', 'conversion_to_grams' => 1.0000],
            ['name_unit' => 'Sendok Makan', 'abbreviation' => 'sdm', 'type_unit' => 'volume', 'conversion_to_grams' => 15.0000],
            ['name_unit' => 'Sendok Teh', 'abbreviation' => 'sdt', 'type_unit' => 'volume', 'conversion_to_grams' => 5.0000],
            ['name_unit' => 'Buah', 'abbreviation' => 'buah', 'type_unit' => 'quantity', 'conversion_to_grams' => null],
            ['name_unit' => 'Siung', 'abbreviation' => 'siung', 'type_unit' => 'quantity', 'conversion_to_grams' => null],
            ['name_unit' => 'Batang', 'abbreviation' => 'batang', 'type_unit' => 'quantity', 'conversion_to_grams' => null],
        ]);
    }
}