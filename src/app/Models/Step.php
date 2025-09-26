<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Step extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     * (Dibutuhkan jika nama tabel tidak mengikuti konvensi 'steps').
     */
    protected $table = 'steps';

    public $timestamps = false;

    /**
     * Kolom yang boleh diisi secara massal.
     */
    protected $fillable = [
        'recipe_id', 
        'step_number', 
        'instruction', 
    ];

    /**
     * Mendefinisikan relasi "satu Langkah dimiliki oleh satu Resep".
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class, 'recipe_id');
    }
}