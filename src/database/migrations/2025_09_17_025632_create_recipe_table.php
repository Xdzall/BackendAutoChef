<?php
// database/migrations/2025_09_17_200000_create_resep_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe', function (Blueprint $table) {
            $table->id();
            $table->string('name_recipe');
            $table->string('image_url')->nullable();
            $table->integer('cooking_time_minutes')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('category')->onDelete('set null');
            $table->foreignId('country_id')->nullable()->constrained('country')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe');
    }
};