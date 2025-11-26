<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\MealPlanController;
use App\Http\Controllers\Api\FavoriteController;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/recipes', [RecipeController::class, 'index'])->middleware('permission:view recipes');
    Route::get('/recipes/recommendations', [RecipeController::class, 'recommendations'])->middleware('permission:view recipes');
    Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])->middleware('permission:view recipes');
    
    // Rute yang HANYA BISA DIAKSES OLEH ADMIN
    Route::post('/recipes', [RecipeController::class, 'store'])->middleware('role:admin');
    Route::put('/recipes/{recipe}', [RecipeController::class, 'update'])->middleware('role:admin');
    Route::patch('/recipes/{recipe}', [RecipeController::class, 'update'])->middleware('role:admin');
    Route::delete('/recipes/{recipe}', [RecipeController::class, 'destroy'])->middleware('role:admin');
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/recipes/{recipe}/favorites', [FavoriteController::class, 'toggleFavorite']);
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/meal-plans', [MealPlanController::class, 'index']);
    Route::post('/meal-plans', [MealPlanController::class, 'store']);
    Route::delete('/meal-plans/{day}/{recipeId}', [MealPlanController::class, 'destroy']);
    Route::get('/meal-plans/weekly/ingredients', [MealPlanController::class, 'weeklyIngredients']);
    Route::get('/meal-plans/{day}/ingredients', [MealPlanController::class, 'ingredients']);
});

require __DIR__.'/auth.php';