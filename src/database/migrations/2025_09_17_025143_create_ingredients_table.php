<?php
// database/migrations/2025_09_17_100300_create_bahan_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name_ingredients', 150)->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};