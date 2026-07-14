<?php

namespace App\Jobs;

use App\Models\ImportProcess;
use App\Services\MonthlyTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Handles processing of uploaded monthly ERP templates.
 *
 * Responsibilities:
 *
 * - Manage import lifecycle.
 * - Delegate processing to MonthlyTemplateService.
 * - Update import status on success/failure.
 *
 * The actual import logic remains inside the service layer.
 */
class ProcessMonthlyTemplateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;


    /**
     * Maximum number of attempts.
     */
    public int $tries = 1;


    /**
     * Maximum execution time.
     */
    public int $timeout = 300;


    /**
     * Create a new job instance.
     */
    public function __construct(
        protected ImportProcess $process,
        protected string $filePath
    ) {
    }


    /**
     * Execute the import process.
     */
    public function handle(
        MonthlyTemplateService $service
    ): void {

        try {

            /*
            |--------------------------------------------------------------------------
            | Refresh Process Record
            |--------------------------------------------------------------------------
            |
            | Ensures we are working with the latest database state.
            |
            */

            $this->process->refresh();


            /*
            |--------------------------------------------------------------------------
            | Start Processing
            |--------------------------------------------------------------------------
            */

            $this->process->markProcessing(
                5,
                'Starting import',
                'Preparing template file...'
            );


            /*
            |--------------------------------------------------------------------------
            | Execute Import
            |--------------------------------------------------------------------------
            */

            $service->process(
                $this->process,
                $this->filePath
            );


            /*
            |--------------------------------------------------------------------------
            | Complete Import
            |--------------------------------------------------------------------------
            */

            $this->process->markCompleted(
                'Monthly template imported successfully.'
            );


        } catch (Throwable $exception) {


            /*
            |--------------------------------------------------------------------------
            | Mark Import Failed
            |--------------------------------------------------------------------------
            */

            $this->process->markFailed(
                $exception->getMessage()
            );


            throw $exception;

        }

    }


    /**
     * Handle permanent job failure.
     *
     * This runs after Laravel exhausts retries
     * or the job fails permanently.
     */
    public function failed(
        Throwable $exception
    ): void {

        $this->process->refresh();


        $this->process->markFailed(
            $exception->getMessage()
        );

    }
}