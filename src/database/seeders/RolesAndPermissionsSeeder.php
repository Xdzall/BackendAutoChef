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
        Permission::create(['name' => 'view recipes']);
        Permission::create(['name' => 'create recipes']);
        Permission::create(['name' => 'edit recipes']);
        Permission::create(['name' => 'delete recipes']);

        // --- Buat Role 'user' dan berikan izin ---
        $userRole = Role::create(['name' => 'user']);
        $userRole->givePermissionTo('view recipes'); // User hanya bisa melihat

        // --- Buat Role 'admin' dan berikan semua izin ---
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());
    }
}