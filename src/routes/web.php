<?php

use Illuminate\Support\Facades\Route;

Route::get('/openapp', function () {
    return view('openapp');
});


// require __DIR__.'/auth.php';
