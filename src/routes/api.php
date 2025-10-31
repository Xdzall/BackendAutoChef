<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RecipeController;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Rute yang bisa diakses oleh SEMUA PENGGUNA TERAUTENTIKASI
    // (karena admin juga memiliki izin 'view recipes')
    Route::get('/recipes', [RecipeController::class, 'index'])->middleware('permission:view recipes');
    Route::get('/recipes/recommendations', [RecipeController::class, 'recommendations'])->middleware('permission:view recipes');
    Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])->middleware('permission:view recipes');
    
    // Rute yang HANYA BISA DIAKSES OLEH ADMIN
    Route::post('/recipes', [RecipeController::class, 'store'])->middleware('role:admin');
    Route::put('/recipes/{recipe}', [RecipeController::class, 'update'])->middleware('role:admin');
    Route::patch('/recipes/{recipe}', [RecipeController::class, 'update'])->middleware('role:admin');
    Route::delete('/recipes/{recipe}', [RecipeController::class, 'destroy'])->middleware('role:admin');
});

require __DIR__.'/auth.php';