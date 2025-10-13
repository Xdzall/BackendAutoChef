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

    public $timestamps = false;

    public function recipe(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'ingredients_details')
                    ->withPivot('amount', 'unit_id', 'notes');
    }
}