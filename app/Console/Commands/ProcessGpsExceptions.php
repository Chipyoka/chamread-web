<?php

namespace App\Console\Commands;

use App\Services\Geo\GpsExceptionProcessingService;
use Illuminate\Console\Command;

class ProcessGpsExceptions extends Command
{
    /**
     * The console command name.
     */
    protected $signature = 'gps:process-exceptions
                            {--chunk=1000 : Chunk size for processing}';

    /**
     * The console command description.
     */
    protected $description =
        'Process GPS mismatch exceptions for readings';

    public function handle(
        GpsExceptionProcessingService $service
    ): int {

        $this->info('Starting GPS exception processing...');

        $start = microtime(true);

        $chunkSize = (int) $this->option('chunk');

        $service->process($chunkSize);

        $duration = round(
            microtime(true) - $start,
            2
        );

        $this->info(
            "GPS exception processing completed in {$duration} seconds."
        );

        return self::SUCCESS;
    }
}