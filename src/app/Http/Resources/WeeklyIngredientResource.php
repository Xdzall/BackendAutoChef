<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeeklyIngredientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_bahan' => $this['id_bahan'],
            'nama_bahan' => $this['nama_bahan'],
            'detail_bahan' => [
                'jumlah' => $this['detail_bahan']['jumlah'],
                'satuan' => $this['detail_bahan']['satuan'],
                // 'catatan' => $this['detail_bahan']['catatan'],
            ]
        ];
    }
}
