<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

/*
|--------------------------------------------------------------------------
| Non‑SPA routes (must be before the catch‑all)
|--------------------------------------------------------------------------
*/

// Homepage → loads your map
Route::get('/', function () {
    return view('map');
});

// /map → also loads your map
Route::get('/map', function () {
    return view('map');
});

// /auth → login/register page
Route::get('/auth', function () {
    return view('auth');
});

// /profile → user profile page
Route::get('/profile', function () {
    return view('profile');
});

/*
|--------------------------------------------------------------------------
| SPA catch‑all — must be last
|--------------------------------------------------------------------------
*/

Route::get('/{any}', HomeController::class)->where('any', '.*');
