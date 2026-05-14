<?php

namespace App\Services\Geo;

class DistanceService
{
    /**
     * Calculate distance between two coordinates using Haversine formula.
     *
     * Returns distance in meters.
     */
    public function calculateMeters(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a =
            sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) *
            cos(deg2rad($lat2)) *
            sin($dLon / 2) *
            sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Validate coordinates.
     */
    public function isValidCoordinate(
        ?float $latitude,
        ?float $longitude
    ): bool {
        if (
            is_null($latitude) ||
            is_null($longitude)
        ) {
            return false;
        }

        if (
            $latitude == 0 &&
            $longitude == 0
        ) {
            return false;
        }

        return (
            $latitude >= -90 &&
            $latitude <= 90 &&
            $longitude >= -180 &&
            $longitude <= 180
        );
    }

    /**
     * Determine severity level based on distance.
     */
    public function determineSeverity(
        float $distanceMeters,
        int $allowedRadius
    ): string {
        $excess = $distanceMeters - $allowedRadius;

        return match (true) {
            $excess <= 100 => 'low',
            $excess <= 500 => 'medium',
            $excess <= 1000 => 'high',
            default => 'critical',
        };
    }

    /**
     * Check if distance exceeds allowed radius.
     */
    public function isMismatch(
        float $distanceMeters,
        int $allowedRadius
    ): bool {
        return $distanceMeters > $allowedRadius;
    }
}