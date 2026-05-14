<?php

namespace App\Services\Geo;

use App\Models\Account;
use App\Models\AccountLocation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AccountGeocodingService
{
    /**
     * Geocode an account.
     */
    public function geocode(Account $account): AccountLocation
    {
        $address = trim($account->address ?? '');

        $addressHash = md5($address);

        $location = AccountLocation::firstOrNew([
            'account_id' => $account->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Skip if already geocoded and address unchanged
        |--------------------------------------------------------------------------
        */

        if (
            $location->exists &&
            $location->address_hash === $addressHash &&
            $location->status === 'success'
        ) {
            return $location;
        }

        /*
        |--------------------------------------------------------------------------
        | Snapshot current address
        |--------------------------------------------------------------------------
        */

        $location->address_snapshot = $address;
        $location->address_hash = $addressHash;

        /*
        |--------------------------------------------------------------------------
        | Empty address handling
        |--------------------------------------------------------------------------
        */

        if (empty($address)) {

            $location->status = 'failed';
            $location->retry_count += 1;

            $location->save();

            return $location;
        }

        /*
        |--------------------------------------------------------------------------
        | Execute provider geocoding
        |--------------------------------------------------------------------------
        */

        $result = $this->resolveCoordinates($address);

        if (!$result['success']) {

            $location->status = 'failed';
            $location->retry_count += 1;

            $location->save();

            return $location;
        }

        /*
        |--------------------------------------------------------------------------
        | Persist coordinates
        |--------------------------------------------------------------------------
        */

        $location->latitude = $result['latitude'];
        $location->longitude = $result['longitude'];

        $location->geocode_provider = $result['provider'];
        $location->geocode_confidence = $result['confidence'];

        $location->geocoded_at = now();

        $location->status = 'success';

        $location->save();

        return $location;
    }

    /**
     * Resolve coordinates from provider.
     */
    protected function resolveCoordinates(string $address): array
    {
        /*
        |--------------------------------------------------------------------------
        | Example Using Nominatim
        |--------------------------------------------------------------------------
        |
        | Replace later with preferred provider.
        |
        */

        try {

            $response = Http::withHeaders([
                'User-Agent' => config('app.name'),
            ])->get(
                'https://nominatim.openstreetmap.org/search',
                [
                    'q' => $address . ', Zambia',
                    'format' => 'json',
                    'limit' => 1,
                ]
            );

            if (!$response->successful()) {
                return [
                    'success' => false,
                ];
            }

            $data = $response->json();

            if (empty($data)) {
                return [
                    'success' => false,
                ];
            }

            return [
                'success' => true,
                'latitude' => (float) $data[0]['lat'],
                'longitude' => (float) $data[0]['lon'],
                'provider' => 'nominatim',
                'confidence' => 80,
            ];

        } catch (\Throwable $e) {

            return [
                'success' => false,
            ];
        }
    }
}