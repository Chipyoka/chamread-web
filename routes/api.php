<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes (Mobile - CSA App)
|--------------------------------------------------------------------------
*/

// Versioned API Group
Route::prefix('v1')->name('api.v1.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Auth Routes
    |--------------------------------------------------------------------------
    */

    // Public
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login');
    });

    // Protected
    Route::middleware(['auth:sanctum'])
        ->prefix('auth')
        ->name('auth.')
        ->group(function () {

            Route::get('/me', [AuthController::class, 'me'])->name('me');

            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

            Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('logoutAll');
        });
});