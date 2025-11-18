<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Authentication Service API',
        'version' => '1.0.0',
    ]);
});

