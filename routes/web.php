<?php

use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Session authentication endpoints
|--------------------------------------------------------------------------
|
| These routes use the web middleware stack so Sanctum first-party SPA
| cookie/session authentication can establish and destroy sessions.
|
*/

Route::prefix('api')->group(function () {
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:register');

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth');
});

Route::view('/{any?}', 'app')->where('any', '.*');
