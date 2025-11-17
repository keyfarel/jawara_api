<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
        'message' => 'API is running',
        'docs' => 'Visit /docs for the OpenAPI documentation'
]));

Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
