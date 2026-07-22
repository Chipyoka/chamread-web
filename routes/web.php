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
use App\Http\Controllers\Admin\MonthlyTemplateController;
use App\Http\Controllers\Admin\DeviceController;
use App\Http\Controllers\Admin\ZoneController;
use App\Http\Controllers\Admin\FlagController;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
     return redirect()->route(auth()->check() ? 'dashboard.dashboard.index' : 'login');
});


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
     * DASHBOARD ROUTES
     */
    Route::prefix('dashboard')
        ->name('dashboard.')
        ->middleware(['auth', 'role:ADMIN,COMMERCIAL,SUPERVISOR,IT'])
        ->group(function () {

        Route::get('/overview', [DashboardController::class, 'index'])->name('dashboard.index');
        Route::get('/supervisor', [DashboardController::class, 'supervisor'])->name('supervisor.index');
        Route::get('/technical', [DashboardController::class, 'technical'])->name('technical.index');


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

            Route::post('/{reading}/re-read', [ReadingsController::class, 'requestReread'])->name('re-read');
            Route::get('/{reading}/re-read/complete', [ReadingsController::class, 'completeReread'])->name('re-read.complete');

            Route::get('/{reading}/technical/resolve', [ReadingsController::class, 'resolveReading'])->name('resolve');

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
            Route::get('/', [MonthlyTemplateController::class, 'index'])->name('index');
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
         *  ZONES MANAGEMENT
         * =====================================================
         */
        Route::prefix('zones')->name('zones.')->group(function () {
            Route::get('/', [ZoneController::class, 'index'])->name('index');
            Route::post('/', [ZoneController::class, 'store'])->name('store');
            Route::put('/{zone}', [ZoneController::class, 'update'])->name('update');
            Route::delete('/{zone}', [ZoneController::class, 'destroy'])->name('delete');
            Route::post('/bulk-update', [ZoneController::class, 'bulkUpdate'])->name('bulk-update');
            Route::post('/import', [ZoneController::class, 'import'])->name('import');
            Route::get('/download-template', [ZoneController::class, 'downloadTemplate'])->name('download-template');
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

    Route::prefix('management')
        ->name('management.')
        ->middleware(['auth', 'role:ADMIN,IT'])
        ->group(function () {

            Route::prefix('monthly-template')
            ->name('monthly-template.')
            ->group(function () {

                Route::get('/', [MonthlyTemplateController::class, 'index'])
                    ->name('index');

                Route::post('/upload', [MonthlyTemplateController::class, 'upload'])
                    ->name('upload');

                Route::get('/status/{process}', [MonthlyTemplateController::class, 'status'])
                    ->name('status');

                Route::get('/download/{billingCycle}', [MonthlyTemplateController::class, 'download'])
                    ->name('download');

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
  
          Route::prefix('users')
            ->name('users.')
            ->controller(UsersController::class)
            ->group(function () {
                
                // Main listing page
                Route::get('/', 'index')->name('index');
                
                // Store new user
                Route::post('/', 'store')->name('store');
                
                // Update user
                Route::put('/{user}', 'update')->name('update');
                
                // Toggle user status (ACTIVE/SUSPENDED)
                Route::patch('/{user}/toggle-status', 'toggleStatus')->name('toggle-status');
                
                // Delete user
                Route::delete('/{user}', 'destroy')->name('destroy');
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
         *  UTILITY MANAGEMENT
         * =====================================================
         */
        // Route::prefix('utility')->name('utility.')->group(function () {
        //     Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        // });


        /**
         * =====================================================
         *  DEVICES MANAGEMENT
         * =====================================================
         */
        Route::prefix('devices')->name('devices.')->group(function () {
            Route::get('/', [DeviceController::class, 'index'])->name('index');
            Route::post('/', [DeviceController::class, 'store']);
            Route::put('/{device}', [DeviceController::class, 'update']);
            Route::delete('/{device}', [DeviceController::class, 'destroy']);
            Route::post('/bulk-update', [DeviceController::class, 'bulkUpdate']);
        });

        /**
         * =====================================================
         *  FLAGS MANAGEMENT
         * =====================================================
         */
        Route::prefix('flags')->name('flags.')->group(function () {
           // Flag routes
            Route::get('/', [FlagController::class, 'index'])->name('index');
            Route::post('/', [FlagController::class, 'store'])->name('store');
            Route::put('/{flag}', [FlagController::class, 'update'])->name('update');
            Route::delete('/{flag}', [FlagController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-update', [FlagController::class, 'bulkUpdate'])->name('bulk-update');

            Route::post('/run-evaluation', [FlagController::class, 'runEvaluation'])->name('evaluate');

            // Flag Rule routes
            Route::post('/rules', [FlagController::class, 'storeRule'])->name('rule.store');
            Route::put('/rules/{flagRule}', [FlagController::class, 'updateRule'])->name('rule.update');
            Route::delete('/rules/{flagRule}', [FlagController::class, 'destroyRule'])->name('rule.destroy');

        });


 

    });




require __DIR__.'/auth.php';
