<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Unit;

class IngredientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 'this' merujuk pada satu instance dari model Ingredient
        return [
            'id_bahan' => $this->id,
            'nama_bahan' => $this->name_ingredients,
            
            // 'pivot' adalah objek spesial yang berisi data dari tabel pivot
            // ('ingredients_details') saat relasi many-to-many dimuat.
            // Kita menggunakan whenPivotLoaded untuk memastikan tidak ada error jika pivot tidak di-load.
            'detail_penggunaan' => $this->whenPivotLoaded('ingredients_details', function () {
                return [
                    'jumlah' => $this->pivot->amount,
                    'satuan' => Unit::find($this->pivot->unit_id)?->abbreviation ?? 'N/A',
                    'catatan' => $this->pivot->notes,
                ];
            }),
        ];
    }
}