<?php

use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\NewPasswordController;
use App\Http\Controllers\Api\Auth\PasswordResetLinkController;
use App\Http\Controllers\Api\Auth\RegisteredUserController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SpaController;
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

    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:password-reset');

    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:password-reset');
});

Route::get('/sitemap.xml', SitemapController::class);

Route::get('/offline.html', function () {
    return response(
        file_get_contents(public_path('offline.html')),
        200,
        [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ],
    );
});

Route::get('/sw.js', function () {
    return response(
        file_get_contents(public_path('sw.js')),
        200,
        [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-cache',
            'Service-Worker-Allowed' => '/',
        ],
    );
});

Route::get('/{any?}', SpaController::class)
    ->where('any', '.*');
