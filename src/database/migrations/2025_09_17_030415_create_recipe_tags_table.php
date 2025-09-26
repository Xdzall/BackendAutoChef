<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_tags', function (Blueprint $table) {
            $table->foreignId('recipe_id')->constrained('recipe')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('tags')->onDelete('cascade');
            $table->primary(['recipe_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_tags');
    }
};