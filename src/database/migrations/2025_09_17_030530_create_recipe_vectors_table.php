<?php
// database/migrations/2025_09_17_200400_create_resep_vectors_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_vectors', function (Blueprint $table) {
            $table->foreignId('recipe_id')->primary()->constrained('recipe')->onDelete('cascade');
            $table->jsonb('vector');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_vectors');
    }
};