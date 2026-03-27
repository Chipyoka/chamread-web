<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
     return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth','role:ADMIN,SUPERVISOR'])->name('dashboard');
    
Route::middleware(['auth','role:ADMIN,SUPERVISOR'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/csas', [ProfileController::class,'edit'])->name('csas.index');
    Route::get('/csas/map', [ProfileController::class,'edit'])->name('csas.map');
    Route::get('/exceptions', [ProfileController::class,'edit'])->name('exceptions.index');
    Route::get('/accounts', [ProfileController::class,'edit'])->name('accounts.index');
    Route::get('/analytics', [ProfileController::class,'edit'])->name('analytics.index');
    Route::get('/admin/settings', [ProfileController::class,'edit'])->name('admin.settings');
    Route::get('/audit', [ProfileController::class,'edit'])->name('audit.index');
});

require __DIR__.'/auth.php';
