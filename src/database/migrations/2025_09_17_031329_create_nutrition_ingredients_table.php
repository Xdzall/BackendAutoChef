<?php
// database/migrations/2025_09_17_200900_create_nutrition_ingredients_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutrition_ingredients', function (Blueprint $table) {
            $table->foreignId('ingredient_id')->primary()->constrained('ingredients')->onDelete('cascade');
            $table->decimal('calories', 10, 2)->nullable();
            $table->decimal('protein_grams', 10, 2)->nullable();
            $table->decimal('carbohydrates_grams', 10, 2)->nullable();
            $table->decimal('fats_grams', 10, 2)->nullable();
            $table->decimal('fiber_grams', 10, 2)->nullable();
            $table->timestamp('last_updated')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutrition_ingredients');
    }
};