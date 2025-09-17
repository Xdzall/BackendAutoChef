<?php
// database/migrations/2025_09_17_200100_create_langkah_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipe')->onDelete('cascade');
            $table->integer('step_number');
            $table->text('instruction');
            $table->unique(['recipe_id', 'step_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('steps');
    }
};