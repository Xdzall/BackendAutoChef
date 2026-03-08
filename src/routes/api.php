<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\MealPlanController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\IngredientController;
use App\Http\Controllers\Api\UnitController;

// =================================================================
// 1. PUBLIC ROUTES (Bisa diakses tanpa login)
// =================================================================
Route::post('/login', [AuthController::class, 'login'])->name('login');
require __DIR__.'/auth.php';

// =================================================================
// 2. AUTHENTICATED ROUTES (Akses Mobile & Website dengan Token)
// =================================================================
Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    // --- PROFIL USER ---
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto']);

    // --- FITUR UTAMA (Lihat Resep & Kategori) ---
    // PENTING: Route spesifik ('recommendations') harus di ATAS route dinamis ('{recipe}')
    Route::get('/recipes', [RecipeController::class, 'index'])->middleware('permission:view recipes');
    Route::get('/recipes/recommendations', [RecipeController::class, 'recommendations'])->middleware('permission:view recipes');
    Route::get('/recipes/{recipe}', [RecipeController::class, 'show']); 
    
    Route::get('/categories', [RecipeController::class, 'getCategories']);
    Route::get('/categories/{categoryId}/recipes', [RecipeController::class, 'getByCategory']);
    Route::get('/countries', [RecipeController::class, 'getCountries']);

    // --- DATA MASTER (Read-only untuk umum) ---
    Route::get('/ingredients', [IngredientController::class, 'getIngredients']);
    Route::get('/units', [UnitController::class, 'getUnits']);

    // --- MEAL PLANS ---
    Route::get('/meal-plans', [MealPlanController::class, 'index']);
    Route::post('/meal-plans', [MealPlanController::class, 'store']);
    Route::delete('/meal-plans/{day}/{recipeId}', [MealPlanController::class, 'destroy']);
    Route::get('/meal-plans/weekly/ingredients', [MealPlanController::class, 'weeklyIngredients']);
    Route::get('/meal-plans/{day}/ingredients', [MealPlanController::class, 'ingredients']);

    // --- FAVORITES ---
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/recipes/{recipe}/favorites', [FavoriteController::class, 'toggleFavorite']);


    // =================================================================
    // 3. ADMIN ROUTES (Hanya untuk akses dari Website Kelola Resep)
    // =================================================================
    Route::middleware(['role:admin'])->group(function () {
        
        // Manajemen Resep (Create, Update, Delete)
        Route::get('/recipes-list', [RecipeController::class, 'getRecipeList']); 
        Route::post('/recipes', [RecipeController::class, 'store']);
        Route::put('/recipes/{recipe}', [RecipeController::class, 'update']);
        Route::patch('/recipes/{recipe}', [RecipeController::class, 'update']);
        Route::delete('/recipes/{recipe}', [RecipeController::class, 'destroy']);

        // Manajemen Master Data (Hanya admin yang boleh nambah bahan & unit)
        Route::post('/ingredients', [IngredientController::class, 'store']);
        Route::post('/ingredients/{id}/nutrition', [IngredientController::class, 'updateNutrition']);
        Route::post('/units', [UnitController::class, 'store']);
        
    });
});