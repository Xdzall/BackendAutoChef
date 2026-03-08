<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Unit;

class RecipeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user('sanctum');

        // 1. Data dasar yang selalu tampil (di List maupun Detail)
        $data = [
            'id' => $this->id,
            'nama_resep' => $this->name_recipe,
            'url_gambar' => $this->image_url,
            'waktu_masak' => $this->cooking_time_minutes,
            'kategori' => $this->category_id,
            'negara_id' => $this->country_id,
            'negara' => $this->country ? $this->country->name_country : null,
            'is_favorited' => $user ? $this->favorites()->where('user_id', $user->id)->exists() : false,
        ];

        // 2. Hanya hitung nutrisi & tampilkan bahan JIKA relasi 'ingredients' dipanggil
        // (Biasanya hanya terjadi di Controller method show() alias Detail Resep)
        if ($this->relationLoaded('ingredients')) {
            $totalCalories = 0;
            $totalProtein = 0;
            $totalCarbs = 0;
            $totalFats = 0;
            $totalFiber = 0;

            $units = Unit::all()->keyBy('id');

            foreach ($this->ingredients as $ingredient) {
                $amount = $ingredient->pivot->amount;
                $unitId = $ingredient->pivot->unit_id;
                $unit = $units->get($unitId);
                
                if ($unit && $unit->type_unit === 'quantity') {
                    $konversiGram = $ingredient->nutrition ? ($ingredient->nutrition->weight_per_piece ?? 0) : 0;
                } else {
                    $konversiGram = $unit->conversion_to_grams ?? 0;
                }
                
                $totalGram = $amount * $konversiGram;

                if ($ingredient->nutrition) {
                    $multiplier = $totalGram / 100;
                    $totalCalories += $multiplier * $ingredient->nutrition->calories;
                    $totalProtein += $multiplier * $ingredient->nutrition->protein_grams;
                    $totalCarbs += $multiplier * $ingredient->nutrition->carbohydrates_grams;
                    $totalFats += $multiplier * $ingredient->nutrition->fats_grams;
                    $totalFiber += $multiplier * $ingredient->nutrition->fiber_grams;
                }
            }

            // Masukkan data nutrisi ke dalam response
            $data['total_nutrisi'] = [
                'kalori_kcal' => round($totalCalories, 2),
                'protein_gram' => round($totalProtein, 2),
                'karbohidrat_gram' => round($totalCarbs, 2),
                'lemak_gram' => round($totalFats, 2),
                'serat_gram' => round($totalFiber, 2),
            ];

            // Masukkan list bahan
            $data['bahan'] = IngredientResource::collection($this->ingredients);
        }

        // 3. Hanya tampilkan langkah-langkah JIKA relasi 'steps' dipanggil
        if ($this->relationLoaded('steps')) {
            $data['langkah_langkah'] = StepResource::collection($this->steps);
        }

        return $data;
    }
}