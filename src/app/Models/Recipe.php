<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Recipe extends Model
{
    use HasFactory;

    /**
     * Nama tabel database yang terhubung dengan model ini.
     */
    protected $table = 'recipe';

    /**
     * Properti $fillable menentukan kolom mana saja yang boleh diisi
     * secara massal menggunakan metode create() atau update().
     * Ini penting untuk keamanan.
     */
    protected $fillable = [
        'name_recipe',
        'image_url',
        'cooking_time_minutes',
        'category_id',
        'country_id',
    ];

    /**
     * Mendefinisikan relasi "satu Resep dimiliki oleh satu category".
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Mendefinisikan relasi "satu Resep dimiliki oleh satu Country".
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Mendefinisikan relasi "satu Resep memiliki banyak steps ".
     */
    public function steps(): HasMany
    {
        return $this->hasMany(Step::class)->orderBy('step_number');
    }

    /**
     * Mendefinisikan relasi "satu Resep memiliki banyak Ingredients"
     * melalui tabel pivot 'ingredients_details'.
     */
    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredients::class, 'ingredients_details')
                    ->withPivot('amount', 'unit_id', 'notes');
    }
}