<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Recipe;

class Ingredients extends Model
{
    use HasFactory;

    protected $table = 'ingredients';

    protected $fillable = [
        'name_ingredients',
    ];

    /**
     * Karena tabel 'ingredients' di skema Anda tidak memiliki kolom
     * created_at dan updated_at, kita perlu menonaktifkannya
     * agar tidak terjadi error saat menyimpan data.
     */
    public $timestamps = false;

    /**
     * Mendefinisikan relasi "satu ingredients bisa dimiliki oleh banyak Resep".
     * Ini adalah relasi kebalikan dari yang ada di model Resep.
     */
    public function recipe(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'ingredients_details')
                    ->withPivot('amount', 'unit_id', 'notes');
    }
}