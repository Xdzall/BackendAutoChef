<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user('sanctum');

        return [
            'id' => $this->id,
            'nama_resep' => $this->name_recipe,
            'url_gambar' => $this->image_url,
            'waktu_masak' => $this->cooking_time_minutes,
            // 'dibuat' => $this->created_at->diffForHumans(),
            'kategori' => $this->category_id,
            'negara' => $this->country ? $this->country->name_country : null,

            // Sertakan relasi hanya jika sudah dimuat (di-load)
            'bahan' => IngredientResource::collection($this->whenLoaded('ingredients')),
            'langkah_langkah' => StepResource::collection($this->whenLoaded('steps')),
            'is_favorited' => $user ? $this->favorites()->where('user_id', $user->id)->exists() : false,
        ];
    }
}