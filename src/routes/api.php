<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified'])->group(function() {
    // SEMUA RUTE DI SINI HANYA BISA DIAKSES JIKA PENGGUNA SUDAH LOGIN & TERVERIFIKASI
    Route::get('/my-recipes', [RecipeController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function() {
    // HANYA ADMIN YANG BISA MENGAKSES RUTE INI
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index']);
});

require __DIR__.'/auth.php';