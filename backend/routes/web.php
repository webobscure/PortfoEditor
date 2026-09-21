<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vue SPA
|--------------------------------------------------------------------------
|
| Production builds place the Vue entry point in public/index.html. Keep the
| frontend and API on one origin so Sanctum's session cookie remains
| first-party. API, Sanctum, storage and health-check paths are deliberately
| excluded from this fallback.
|
*/

Route::get('/{path?}', function () {
    $index = public_path('index.html');

    abort_unless(is_file($index), 404, 'Frontend build not found.');

    return response()->file($index);
})->where('path', '^(?!api(?:/|$)|sanctum(?:/|$)|storage(?:/|$)|up(?:/|$)).*');
