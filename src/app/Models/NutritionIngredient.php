<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NutritionIngredient extends Model
{
    use HasFactory;

    protected $table = 'nutrition_ingredients';

    protected $primaryKey = 'ingredient_id';
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'ingredient_id',
        'calories',
        'protein_grams',
        'carbohydrates_grams',
        'fats_grams',
        'fiber_grams',
        'weight_per_piece',
        'last_updated',
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredients::class, 'ingredient_id', 'id');
    }

}
