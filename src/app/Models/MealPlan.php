<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealPlan extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'plan_name', 'description'];

    public function recipes()
    {
        return $this->hasMany(MealPlanRecipe::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
