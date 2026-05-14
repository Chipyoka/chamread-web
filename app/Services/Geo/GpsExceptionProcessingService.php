<?php

namespace App\Services\Geo;

use App\Models\AccountLocation;
use App\Models\ExceptionGpsMismatch;
use App\Models\Reading;
use App\Models\ReadingGpsCheck;
use App\Models\SystemException;
use Illuminate\Support\Facades\DB;

class GpsExceptionProcessingService
{
    public function __construct(
        protected DistanceService $distanceService
    ) {
    }

    /**
     * Process unchecked readings.
     */
    public function process(
        int $chunkSize = 1000
    ): void {

        Reading::query()

            /*
            |--------------------------------------------------------------------------
            | Only unprocessed readings
            |--------------------------------------------------------------------------
            */
            ->whereDoesntHave('gpsCheck')

            /*
            |--------------------------------------------------------------------------
            | Important for memory safety
            |--------------------------------------------------------------------------
            */
            ->orderBy('id')

            ->chunkById($chunkSize, function ($readings) {

                foreach ($readings as $reading) {

                    $this->processReading($reading);
                }
            });
    }

    /**
     * Process single reading.
     */
    protected function processReading(
        Reading $reading
    ): void {

        DB::transaction(function () use ($reading) {

            /*
            |--------------------------------------------------------------------------
            | Fetch account location
            |--------------------------------------------------------------------------
            */

            $location = AccountLocation::where(
                'account_id',
                $reading->account_id
            )->first();

            /*
            |--------------------------------------------------------------------------
            | Missing coordinates
            |--------------------------------------------------------------------------
            */

            if (
                !$location ||
                !$this->distanceService->isValidCoordinate(
                    $location->latitude,
                    $location->longitude
                )
            ) {

                $this->createGpsCheck(
                    reading: $reading,
                    status: 'missing_coordinates'
                );

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Invalid reading GPS
            |--------------------------------------------------------------------------
            */

            if (
                !$this->distanceService->isValidCoordinate(
                    $reading->latitude,
                    $reading->longitude
                )
            ) {

                $this->createGpsCheck(
                    reading: $reading,
                    status: 'invalid_gps'
                );

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate distance
            |--------------------------------------------------------------------------
            */

            $distance = $this->distanceService->calculateMeters(
                $location->latitude,
                $location->longitude,
                $reading->latitude,
                $reading->longitude
            );

            $allowedRadius = config(
                'gps.default_radius_meters',
                150
            );

            /*
            |--------------------------------------------------------------------------
            | Valid reading
            |--------------------------------------------------------------------------
            */

            if (
                !$this->distanceService->isMismatch(
                    $distance,
                    $allowedRadius
                )
            ) {

                $this->createGpsCheck(
                    reading: $reading,
                    status: 'valid',
                    distance: $distance,
                    radius: $allowedRadius
                );

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | GPS mismatch detected
            |--------------------------------------------------------------------------
            */

            $severity = $this->distanceService
                ->determineSeverity(
                    $distance,
                    $allowedRadius
                );

            $exception = SystemException::create([
                'type' => 'gps_mismatch',

                'account_id' => $reading->account_id,

                'reading_id' => $reading->id,

                'billing_cycle_id' => $reading->billing_cycle_id,

                'severity' => $severity,

                'status' => 'open',

                'title' => 'GPS Reading Outside Allowed Radius',

                'description' =>
                    'Reading GPS coordinates exceeded allowed distance threshold.',

                'detected_at' => now(),

                'metadata' => [
                    'distance_meters' => $distance,
                    'allowed_radius_meters' => $allowedRadius,
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | GPS mismatch detail
            |--------------------------------------------------------------------------
            */

            ExceptionGpsMismatch::create([
                'exception_id' => $exception->id,

                'expected_latitude' => $location->latitude,
                'expected_longitude' => $location->longitude,

                'actual_latitude' => $reading->latitude,
                'actual_longitude' => $reading->longitude,

                'distance_meters' => $distance,

                'allowed_radius_meters' => $allowedRadius,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Processing ledger
            |--------------------------------------------------------------------------
            */

            $this->createGpsCheck(
                reading: $reading,
                status: 'mismatch',
                distance: $distance,
                radius: $allowedRadius,
                exceptionId: $exception->id
            );
        });
    }

    /**
     * Create processing ledger record.
     */
    protected function createGpsCheck(
        Reading $reading,
        string $status,
        ?float $distance = null,
        ?int $radius = null,
        ?int $exceptionId = null
    ): void {

        ReadingGpsCheck::create([
            'reading_id' => $reading->id,

            'account_id' => $reading->account_id,

            'billing_cycle_id' => $reading->billing_cycle_id,

            'processed_at' => now(),

            'status' => $status,

            'distance_meters' => $distance,

            'allowed_radius_meters' => $radius,

            'exception_id' => $exceptionId,
        ]);
    }
}