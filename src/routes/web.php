<?php

use Illuminate\Support\Facades\Route;

Route::get('/openapp', function () {
    return view('openapp');
});

// Halaman test untuk mendapatkan Google ID Token (hapus setelah selesai testing)
Route::get('/google-test', function () {
    return response()->file(resource_path('views/google-test.html'));
});


// require __DIR__.'/auth.php';
