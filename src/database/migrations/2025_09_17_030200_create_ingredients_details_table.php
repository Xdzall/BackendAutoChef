<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipe')->onDelete('cascade');
            $table->foreignId('ingredients_id')->constrained('ingredients')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->foreignId('unit_id')->constrained('unit');
            $table->string('notes')->nullable();
            $table->unique(['recipe_id', 'ingredients_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients_details');
    }
};