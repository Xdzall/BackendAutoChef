<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealPlanRecipe extends Model
{
    use HasFactory;

    protected $fillable = ['meal_plan_id', 'recipe_id', 'quantity'];

    public function mealPlan()
    {
        return $this->belongsTo(MealPlan::class);
    }

    public function recipe()
    {
    return $this->belongsTo(Recipe::class, 'recipe_id');
    }

}
