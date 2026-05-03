<?php

namespace App\Observers;

use App\Models\Recipe;
use Illuminate\Support\Facades\Artisan;

class RecipeObserver
{
    /**
     * Panggil Artisan Command di background saat resep dibuat, diupdate, atau dihapus.
     */
    private function updateVectors()
    {
        // Menggunakan queue agar API tidak lambat (tidak menunggu kalkulasi selesai)
        Artisan::queue('recipe:generate-vectors');
    }

    public function created(Recipe $recipe): void
    {
        $this->updateVectors();
    }

    public function updated(Recipe $recipe): void
    {
        $this->updateVectors();
    }

    public function deleted(Recipe $recipe): void
    {
        $this->updateVectors();
    }
}