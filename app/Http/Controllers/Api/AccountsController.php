<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CsaAssignment;
use App\Models\CustomerAccount;
use App\Models\BillingCycle;
use App\Models\Reading;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class AccountsController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Search Customer Account
    |--------------------------------------------------------------------------
    */

    public function search(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:3',
        ]);

        $user = auth()->user();

        Log::info('Account search initiated', [
            'csa_id' => $user->id,
            'search' => $request->search,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Get latest CSA assignment
        |--------------------------------------------------------------------------
        */

        $assignment = CsaAssignment::where('csa_id', $user->id)
            ->where('assignment_type', 'primary')
            ->where('status', 'active')
            ->first();

        if (!$assignment) {

            Log::warning('CSA has no assignment', [
                'csa_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'CSA has no assigned zone',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Search Accounts
        |--------------------------------------------------------------------------
        */

        $accounts = CustomerAccount::query()

            ->where(function ($query) use ($request) {

                $query->where('account_number', 'like', '%' . $request->search . '%')
                    ->orWhere('customer_name', 'like', '%' . $request->search . '%')
                    ->orWhere('phone_number', 'like', '%' . $request->search . '%');
            })

            ->limit(5)
            ->get()

            ->map(function ($account) use ($assignment) {

                $withinZone = $account->zone_id === $assignment->zone_id;

                return [
                    'id' => $account->id,
                    'account_number' => $account->account_number,
                    'customer_name' => $account->customer_name,
                    'phone_number' => $account->phone_number,
                    'zone_id' => $account->zone_id,

                    'assigned_zone_id' => $assignment->zone_id,

                    'within_assigned_zone' => $withinZone,

                    'zone_status' => $withinZone
                        ? 'WITHIN_ASSIGNED_ZONE'
                        : 'OUTSIDE_ASSIGNED_ZONE',
                ];
            });

        Log::info('Account search completed', [
            'results' => $accounts->count(),
            'csa_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Customer Account Information
    |--------------------------------------------------------------------------
    */

    public function updateAccount(Request $request, CustomerAccount $account)
    {
        $request->validate([
            'phone_number' => 'required|string|max:30',
        ]);

        $user = auth()->user();

        Log::info('Phone update request initiated', [
            'csa_id' => $user->id,
            'account_number' => $account->account_number,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Get latest CSA assignment
        |--------------------------------------------------------------------------
        */

        $assignment = CsaAssignment::where('csa_id', $user->id)
            ->where('assignment_type', 'primary')
            ->where('status', 'active')
            ->first();

        if (!$assignment) {

            Log::warning('CSA has no assignment', [
                'csa_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'CSA has no assigned zone',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Zone Authorization Check
        |--------------------------------------------------------------------------
        */

        if ($account->zone_id !== $assignment->zone_id) {

            Log::warning('Unauthorized account edit attempt', [
                'csa_id' => $user->id,
                'account_zone_id' => $account->zone_id,
                'assigned_zone_id' => $assignment->zone_id,
                'account_number' => $account->account_number,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Account is outside assigned zone',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Update Phone Number
        |--------------------------------------------------------------------------
        */

        $oldPhone = $account->phone;

        $account->update([
            'phone' => $request->phone_number,
        ]);

        Log::info('Customer phone updated successfully', [
            'csa_id' => $user->id,
            'account_number' => $account->account_number,
            'old_phone' => $oldPhone,
            'new_phone' => $request->phone_number,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Phone number updated successfully',
            'data' => [
                'account_number' => $account->account_number,
                'phone_number' => $account->phone,
            ]
        ]);
    }

    /**
     * Download accounts in the assigned zone.
     */
    public function downloadZoneAccounts(Request $request)
    {
        $user = auth()->user();

        Log::info('Zone account sync initiated', [
            'csa_id' => $user->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Resolve Active Assignment
        |--------------------------------------------------------------------------
        */

        $assignment = CsaAssignment::where('csa_id', $user->id)
            ->where('assignment_type', 'primary')
            ->where('status', 'active')
            ->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'No active CSA assignment found',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve Current Billing Cycle
        |--------------------------------------------------------------------------
        */

        $currentCycle = BillingCycle::where('status', 'active')->latest()->first();

        if (!$currentCycle) {
            return response()->json([
                'success' => false,
                'message' => 'No billing cycle found',
            ], 500);
        }

        if ($currentCycle->can_download === false) {
            return response()->json([
                'success' => false,
                'message' => 'Download is locked',
            ], 500);
        }

        /*
        |--------------------------------------------------------------------------
        | Extract Target (sync cap)
        |--------------------------------------------------------------------------
        */

        $targetLimit = (int) $assignment->target;

        Log::info('Sync target resolved', [
            'csa_id' => $user->id,
            'zone_id' => $assignment->zone_id,
            'target' => $targetLimit,
            'billing_cycle_id' => $currentCycle->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Fetch Accounts : Exclude those already read for the current billing cycle.
        |--------------------------------------------------------------------------
        */

        $accountsQuery = CustomerAccount::query()
            ->where('zone_id', $assignment->zone_id)
            ->whereDoesntHave('readings', function ($query) use ($currentCycle) {
                $query->where('billing_cycle_id', $currentCycle->id);
            })
            ->orderBy('account_number', 'asc');

        $totalAvailable = (clone $accountsQuery)->count();

        $accounts = $accountsQuery
            ->limit($targetLimit)
            ->get([
                'id',
                'account_number',
                'customer_name',
                'phone',
                'zone_id',
                'address',
                'meter_number'
            ]);

        /*
        |--------------------------------------------------------------------------
        | Resolve Previous Readings (optimized batch lookup)
        |--------------------------------------------------------------------------
        */

        $accountIds = $accounts->pluck('id');

        $previousReadings = Reading::query()
            ->select(
                'account_id',
                'current_reading',
                'billing_cycle_id'
            )
            ->whereIn('account_id', $accountIds)
            ->where('billing_cycle_id', '<', $currentCycle->id)
            ->whereNotNull('current_reading')
            ->orderByDesc('billing_cycle_id')
            ->get()
            ->groupBy('account_id')
            ->map(function ($group) {
                return optional($group->first())->current_reading;
            });

        /*
        |--------------------------------------------------------------------------
        | Attach Previous Readings
        |--------------------------------------------------------------------------
        */

        $accounts = $accounts->map(function ($account) use ($previousReadings) {

            $account->previous_reading =
                $previousReadings[$account->id] ?? 0;

            return $account;
        });

        /*
        |--------------------------------------------------------------------------
        | Response Packaging
        |--------------------------------------------------------------------------
        */

        $payload = [
            'billing_cycle_id' => $currentCycle->id,

            'zone_id' => $assignment->zone_id,

            'target_limit' => $targetLimit,

            'total_available_in_zone' => $totalAvailable,

            'downloaded_count' => $accounts->count(),

            'accounts' => $accounts,
        ];

        Log::info('Zone account sync completed', [
            'csa_id' => $user->id,
            'downloaded' => $accounts->count(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }
}