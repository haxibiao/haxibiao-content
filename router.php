<?php

use Illuminate\Support\Facades\Route;

// APIs routes.
Route::group(
    [
        'middleware' => ['api'],
        // 'namespace'  => 'Haxibiao\Content\Http\Api',
    ],
    __DIR__ . '/routes/api.php'
);

// Web routes.
Route::group(
    [
        'middleware' => ['web'],
        'namespace'  => 'Haxibiao\Content\Controllers',
    ],
    __DIR__ . '/routes/web.php'
);
