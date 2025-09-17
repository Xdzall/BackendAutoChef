<?php
// database/migrations/2025_09_17_100400_create_satuan_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit', function (Blueprint $table) {
            $table->id();
            $table->string('name_unit', 50)->unique();
            $table->string('abbreviation', 10)->unique();
            $table->enum('type_unit', ['weight', 'volume', 'quantity']);
            $table->decimal('conversion_to_grams', 10, 4)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit');
    }
};