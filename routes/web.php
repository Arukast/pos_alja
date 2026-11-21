<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'name' => 'Alja POS API',
    'version' => 'v1',
    'status' => 'ok',
]));
