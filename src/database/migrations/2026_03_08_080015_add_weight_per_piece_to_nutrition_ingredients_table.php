<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nutrition_ingredients', function (Blueprint $table) {
            $table->decimal('weight_per_piece', 10, 2)->nullable();
        });
    }
};
