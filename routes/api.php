<?php
use App\Http\Controllers\Api\AssignmentsController;
use App\Http\Controllers\Api\ReadingsController;
use App\Http\Controllers\Api\AccountsController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\AuthController;

Route::prefix('v1')->name('api.v1.')->group(function () {

    /*
    |----------------------------------------------------------------------
    | Auth Routes
    |----------------------------------------------------------------------
    */

    // Public
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login');
    });

    // Protected
    Route::middleware(['auth:sanctum'])->group(function () {

        Route::prefix('auth')->name('auth.')->group(function () {
            Route::get('/me', [AuthController::class, 'me'])->name('me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('logoutAll');

            Route::post('/password/update', [ProfileController::class, 'updatePassword'])->name('updatePassword');
        });

        /*
        |------------------------------------------------------------------
        | Assignments Routes (CSA)
        |------------------------------------------------------------------
        | Currently only exposes the "current" stats endpoint.
        | Can be expanded later for CRUD, history, or assignment actions.
        */
        Route::prefix('assignments')->name('assignments.')->group(function () {
            Route::get('/current', [AssignmentsController::class, 'current'])->name('current');
        });


        /*
        |------------------------------------------------------------------
        | Readings Routes (CSA)
        |------------------------------------------------------------------
        | Currently saves one reading at a time. Can be expanded later for batch uploads or updates.
        */
        Route::prefix('readings')->name('readings.')->group(function () {
            Route::post('/save', [ReadingsController::class, 'store'])->name('store');
            Route::post('/batch', [ReadingsController::class, 'batchStore'])->name('batchStore');
            Route::get('/reasons', [ReadingsController::class, 'reasons'])->name('reasons');
        });

        /*
        |------------------------------------------------------------------
        | Customer accounts Routes (CSA)
        |------------------------------------------------------------------
        | Currently search an account, and update details. For now we only do phone number updates. Can be expanded later for more account details, updates, or actions.
        */
        Route::prefix('accounts')->name('accounts.')->group(function () {
            Route::post('/search', [AccountsController::class, 'search'])->name('search');
            Route::patch('/{account}/update', [AccountsController::class, 'updateAccount'])->name('update');
            Route::get('/download', [AccountsController::class, 'downloadZoneAccounts'])->name('downloadZoneAccounts');
        });

    });
});