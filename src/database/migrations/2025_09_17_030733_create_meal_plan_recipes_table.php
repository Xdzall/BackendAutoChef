<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_plan_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_plan_id')->constrained('meal_plans')->onDelete('cascade');
            $table->foreignId('recipe_id')->constrained('recipe')->onDelete('cascade');
            $table->unsignedInteger('quantity')->default(1)->after('recipe_id');
            $table->unique(['meal_plan_id', 'recipe_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plan_recipes');
    }
};