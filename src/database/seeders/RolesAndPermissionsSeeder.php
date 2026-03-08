<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- Buat Permissions ---
        Permission::firstOrCreate(['name' => 'view recipes']);
        Permission::firstOrCreate(['name' => 'create recipes']);
        Permission::firstOrCreate(['name' => 'edit recipes']);
        Permission::firstOrCreate(['name' => 'delete recipes']);

        // --- Buat Role 'user' dan berikan izin ---
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->givePermissionTo('view recipes'); // User hanya bisa melihat

        // --- Buat Role 'admin' dan berikan semua izin ---
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());
    }
}