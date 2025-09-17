<?php
// database/migrations/2025_09_17_200800_create_user_logs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('recipe_id')->constrained('recipe')->onDelete('cascade');
            $table->timestamp('viewed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_logs');
    }
};