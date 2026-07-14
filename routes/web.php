<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AccountsController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\ReadingsController;
use App\Http\Controllers\Admin\CsaController;
use App\Http\Controllers\Admin\ERPController;
use App\Http\Controllers\Admin\CyclesController;
use App\Http\Controllers\Admin\UtilityController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\MeterReadingCodeController;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
     return redirect()->route(auth()->check() ? 'dashboard.index' : 'login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth','role:ADMIN,COMMERCIAL,SUPERVISOR,IT'])->name('dashboard.index');
Route::get('/search', [DashboardController::class, 'search'])->middleware(['auth','role:ADMIN,COMMERCIAL,SUPERVISOR,IT'])->name('dashboard.search.results');


Route::middleware(['auth','role:ADMIN,COMMERCIAL,SUPERVISOR,IT'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/csas/map', [ProfileController::class,'edit'])->name('csas.map');
    Route::get('/readings', [ReadingsController::class,'index'])->name('readings.index');
    Route::get('/accounts', [AccountsController::class,'index'])->name('accounts.index');
    Route::get('/analytics', [AnalyticsController::class,'index'])->name('analytics.index');
    Route::get('/admin/settings', [AdminController::class,'index'])->name('readings.settings');
    Route::get('/audit', [AuditController::class,'index'])->name('audit.index');
});



    /**
     * READINGS ROUTES
     */
    Route::prefix('readings')
        ->name('readings.')
        ->middleware(['auth', 'role:ADMIN,COMMERCIAL,SUPERVISOR,IT'])
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

            Route::get('/{csa}/readings', [CsaController::class, 'csaReadings'])->name('readings');
            Route::get('/{csa}/accounts', [CsaController::class, 'assignedAccounts'])->name('accounts');
        });


        /**
         * =====================================================
         * CUSTOMER ACCOUNTS MANAGEMENT
         * =====================================================
         */
        Route::prefix('accounts')->name('accounts.')->group(function () {

            /**
             * -------------------------------
             * CORE CRUD
             * -------------------------------
             */
            Route::get('/', [AccountsController::class, 'index'])->name('index');
            Route::get('/create', [AccountsController::class, 'create'])->name('create');
            Route::post('/', [AccountsController::class, 'store'])->name('store');

            Route::get('/{account}', [AccountsController::class, 'show'])->name('show');
            Route::get('/{account}/edit', [AccountsController::class, 'edit'])->name('edit');
            Route::put('/{account}', [AccountsController::class, 'update'])->name('update');
            Route::delete('/{account}', [AccountsController::class, 'destroy'])->name('destroy');
            
            Route::get('/{account}/export', [AccountsController::class, 'export'])->name('export');
           
        });

        /**
         * =====================================================
         * READINGS MANAGEMENT
         * =====================================================
         */
        Route::prefix('meter-readings')->name('meter-readings.')->group(function () {

            /**
             * -------------------------------
             * CORE CRUD
             * -------------------------------
             */
            Route::get('/', [ReadingsController::class, 'index'])->name('index');
            Route::get('/{reading}', [ReadingsController::class, 'show'])->name('show');
            
            Route::get('/{reading}/export', [ReadingsController::class, 'export'])->name('export');
           
        });

    });



    /**
     * MANAGEMENT ROUTES
     */
    Route::prefix('management')
        ->name('management.')
        ->middleware(['auth', 'role:ADMIN,COMMERCIAL,SUPERVISOR,IT'])
        ->group(function () {

        /**
         * =====================================================
         * ERP MANAGEMENT
         * =====================================================
         */
        Route::prefix('erp')->name('erp.')->group(function () {
            Route::get('/', [ERPController::class, 'index'])->name('index');
        });

        /**
         * =====================================================
         * BILLING CYCLES MANAGEMENT
         * =====================================================
         */
        Route::prefix('cycles')->name('cycles.')->group(function () {
            Route::get('/', [CyclesController::class, 'index'])->name('index');
            Route::get('/create', [CyclesController::class, 'create'])->name('create');
            Route::post('/', [CyclesController::class, 'store'])->name('store');
            Route::get('/{billingCycle}', [CyclesController::class, 'show'])->name('show');
            Route::get('/{billingCycle}/edit', [CyclesController::class, 'edit'])->name('edit');
            Route::put('/{billingCycle}', [CyclesController::class, 'update'])->name('update');
            Route::delete('/{billingCycle}', [CyclesController::class, 'destroy'])->name('destroy');
            
            // Additional actions
            Route::patch('/{billingCycle}/toggle-download', [CyclesController::class, 'toggleDownload'])->name('toggle-download');
            Route::patch('/{billingCycle}/toggle-upload', [CyclesController::class, 'toggleUpload'])->name('toggle-upload');
            Route::patch('/{billingCycle}/extend-deadline', [CyclesController::class, 'extendDeadline'])->name('extend-deadline');
            Route::patch('/{billingCycle}/update-status', [CyclesController::class, 'updateStatus'])->name('update-status');
            Route::patch('/{billingCycle}/quick-toggle', [CyclesController::class, 'quickToggle'])->name('quick-toggle');
        });

        /**
         * =====================================================
         *  MRC DEFINITION MANAGEMENT
         * =====================================================
         */
        Route::prefix('mrc-definition')->name('mrc-definition.')->group(function () {
            Route::get('/', [UtilityController::class, 'index'])->name('index');
        });

        /**
         * =====================================================
         *  ANALYTICS MANAGEMENT
         * =====================================================
         */
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        });


 

    });


    /**
     * IT/SYSTEMS ROUTES
     */
    Route::prefix('systems')
        ->name('systems.')
        ->middleware(['auth', 'role:ADMIN,IT'])
        ->group(function () {

        /**
         * =====================================================
         * USERS MANAGEMENT
         * =====================================================
         */
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [ERPController::class, 'index'])->name('index');
        });

      

        /**
         * =====================================================
         *  MRC DEFINITION MANAGEMENT
         * =====================================================
         */
        Route::prefix('mrc')->name('mrc.')->group(function () {
            Route::get('/', [MeterReadingCodeController::class, 'index'])->name('index');
            Route::post('/', [MeterReadingCodeController::class, 'store']);
            Route::put('/{meterReadingCode}', [MeterReadingCodeController::class, 'update']);
            Route::delete('/{meterReadingCode}', [MeterReadingCodeController::class, 'destroy']);
            Route::post('/bulk-update', [MeterReadingCodeController::class, 'bulkUpdate']);
        });

        /**
         * =====================================================
         *  ANALYTICS MANAGEMENT
         * =====================================================
         */
        Route::prefix('utility')->name('utility.')->group(function () {
            Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        });


 

    });

require __DIR__.'/auth.php';
