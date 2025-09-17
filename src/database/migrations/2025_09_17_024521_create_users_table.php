<?php
// database/migrations/2025_09_17_100000_create_users_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password'); // Menggunakan 'password' sesuai konvensi Laravel
            $table->timestamps(); // Ini akan membuat created_at dan updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};