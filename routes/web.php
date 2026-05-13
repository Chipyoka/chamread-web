<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
     return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth','role:ADMIN,SUPERVISOR'])->name('dashboard');


Route::middleware(['auth','role:ADMIN,SUPERVISOR'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/csas/map', [ProfileController::class,'edit'])->name('csas.map');
    Route::get('/exceptions', [ProfileController::class,'edit'])->name('exceptions.index');
    Route::get('/accounts', [ProfileController::class,'edit'])->name('accounts.index');
    Route::get('/analytics', [ProfileController::class,'edit'])->name('analytics.index');
    Route::get('/admin/settings', [ProfileController::class,'edit'])->name('admin.settings');
    Route::get('/audit', [ProfileController::class,'edit'])->name('audit.index');
});


use App\Http\Controllers\Admin\CsaController;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:ADMIN'])
    ->group(function () {

        /**
         * =====================================================
         * CSA MANAGEMENT
         * =====================================================
         */
        Route::prefix('csas')->name('csas.')->group(function () {

            /**
             * -------------------------------
             * CORE CRUD
             * -------------------------------
             */
            Route::get('/', [CsaController::class, 'index'])->name('index');
            Route::get('/create', [CsaController::class, 'create'])->name('create');
            Route::post('/', [CsaController::class, 'store'])->name('store');

            Route::get('/{csa}', [CsaController::class, 'show'])->name('show');
            Route::get('/{csa}/edit', [CsaController::class, 'edit'])->name('edit');
            Route::put('/{csa}', [CsaController::class, 'update'])->name('update');
            Route::delete('/{csa}', [CsaController::class, 'destroy'])->name('destroy');

            /**
             * -------------------------------
             * ASSIGNMENTS
             * -------------------------------
             */
            Route::get('/{csa}/assign', [CsaController::class, 'assign'])->name('assign');
            Route::post('/{csa}/assign', [CsaController::class, 'storeAssignment'])->name('assign.store');
        });

    });

require __DIR__.'/auth.php';
