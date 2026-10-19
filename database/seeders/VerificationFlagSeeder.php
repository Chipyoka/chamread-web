<?php

namespace Database\Seeders;

use App\Models\Flag;
use App\Models\FlagRule;
use Illuminate\Database\Seeder;

/**
 * Seeds the reading verification flags + rules.
 * Safe to re-run: it only creates what is missing and never overwrites
 * thresholds or on/off switches that were edited in the admin UI.
 *
 * Run: php artisan db:seed --class=VerificationFlagSeeder
 */
class VerificationFlagSeeder extends Seeder
{
    /** [min, max] consumption per account category. Outside the band = flagged. */
    private const BANDS = [
        'DOMESTIC'    => [3, 100],
        'COMMERCIAL'  => [12, 106],
        'GOVERNMENT'  => [12, 113],
        'INSTITUTION' => [12, 113],
        'OTHER'       => [2, 500],
    ];

    private const CODE_WORKING      = '01';
    private const CODE_DISCONNECTED = '03';

    public function run(): void
    {
        // The description is the static reason shown to the supervisor.
        $negative = $this->flag('Negative Consumption', 'NEGATIVE_CONSUMPTION', 'Consumption cannot be negative', '#DC2626');
        $low      = $this->flag('Low Consumption', 'LOW_CONSUMPTION', 'Consumption is below the expected range for this account category', '#D97706');
        $high     = $this->flag('High Consumption', 'HIGH_CONSUMPTION', 'Possible high consumption', '#DC2626');
        $change   = $this->flag('Meter Status Change', 'METER_STATUS_CHANGE', 'Meter status changed from disconnected to working', '#2563EB');

        // Rule 1: negative consumption (single condition)
        $this->rule($negative, 'consumption', '<', '0');

        // Rule 2: consumption bands. Rules sharing a group_key are ANDed.
        foreach (self::BANDS as $category => [$min, $max]) {
            // >= 0 keeps a negative reading from also showing "below range"
            $this->rule($low, 'account.customer_category', '=', $category, "low_{$category}", 1);
            $this->rule($low, 'consumption', '>=', '0', "low_{$category}", 2);
            $this->rule($low, 'consumption', '<', (string) $min, "low_{$category}", 3);

            $this->rule($high, 'account.customer_category', '=', $category, "high_{$category}", 1);
            $this->rule($high, 'consumption', '>', (string) $max, "high_{$category}", 2);
        }

        // Rule 3: previous cycle disconnected -> this cycle working
        $this->rule($change, 'previous_cycle_code', '=', self::CODE_DISCONNECTED, 'disconnected_to_working', 1);
        $this->rule($change, 'this_month_code', '=', self::CODE_WORKING, 'disconnected_to_working', 2);
    }

    private function flag(string $name, string $code, string $reason, string $color): Flag
    {
        return Flag::firstOrCreate(['code' => $code], [
            'name'                  => $name,
            'description'           => $reason,
            'applies_to'            => 'reading',
            'color'                 => $color,
            'is_system'             => true,
            'active'                => true,
            'requires_verification' => true,
        ]);
    }

    private function rule(Flag $flag, string $field, string $operator, string $value, ?string $group = null, int $order = 0): void
    {
        FlagRule::firstOrCreate(
            ['flag_id' => $flag->id, 'field' => $field, 'operator' => $operator, 'group_key' => $group],
            ['value' => $value, 'order' => $order, 'active' => true, 'evaluation_type' => 'on_save'],
        );
    }
}