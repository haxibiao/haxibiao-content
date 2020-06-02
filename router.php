<?php

use Illuminate\Support\Facades\Route;

// APIs routes.
Route::group(
    [
        'middleware' => ['api'],
    ],
    __DIR__.'/routes/api.php'
);

// Web routes.
Route::group(
    [
        'middleware' => ['web'],
    ],
    __DIR__.'/routes/web.php'
);
