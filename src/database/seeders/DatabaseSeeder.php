<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Jalankan seeder Role & Permission PALING PERTAMA
        $this->call([
            RolesAndPermissionsSeeder::class,
            SatuanSeeder::class,
            ContohResepSeeder::class,
        ]);

        // 2. Buat akun User Admin
        $admin = User::firstOrCreate(
            ['email' => 'autochefteam@gmail.com'], 
            [
                'name' => 'Admin AutoChef',
                'password' => Hash::make('autochef123.'),
                'email_verified_at' => now(),
            ]
        );

        // 3. Assign Role (Bisa langsung pakai string nama role-nya!)
        // Karena role 'admin' sudah pasti dibuat oleh RolesAndPermissionsSeeder di atas
        $admin->assignRole('admin');
    }
}