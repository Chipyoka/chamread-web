<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReadingHistoryResource extends JsonResource
{
    /**
     * Transform the reading into the shape the mobile client's
     * ReadingHistoryApiRecord expects.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,

            'account_number'     => $this->account?->account_number ?? '',
            'customer_name'      => $this->account?->customer_name ?? '',

            'billing_cycle_name' => $this->billingCycle?->name ?? '',
            'zone_name'          => $this->account?->zone?->name ?? '',

            'current_reading'    => (float) $this->current_reading,
            'consumption'        => (float) $this->consumption,

            'meter_reading_code' => $this->code?->code && $this->code?->name
                ? $this->code->code . ' ' . $this->code->name
                : '',

            'comment'            => $this->comment,

            'reading_time'       => $this->reading_time instanceof \Carbon\Carbon
                ? $this->reading_time->toISOString()
                : $this->reading_time,

            'photo_path'         => $this->photo_path,
        ];
    }
}