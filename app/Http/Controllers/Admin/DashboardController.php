<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Zone;
use App\Models\SystemNotification;
use App\Models\CustomerAccount;
use App\Models\AuditLog;
use App\Models\Reading;
use App\Models\Dma;
use App\Models\BillingCycle;
use App\Models\ExceptionGpsMismatch;
use App\Models\CsaAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;


use App\Services\AuditLogService;

class DashboardController extends Controller
{
    protected $auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->auditLog = $auditLog;
    }




public function index()
{
    $totalCsas = User::where('role', 'CSA')->count();
    $totalZones = Zone::count();
    $totalDmas = Dma::count();
    $totalBillingCycles = BillingCycle::count();

    // Latest billing cycle
    $currentCycle = BillingCycle::latest()->first();

    /*
    |--------------------------------------------------------------------------
    | Default Safe Values
    |--------------------------------------------------------------------------
    */
    $assignedCsas = 0;
    $totalReadings = 0;
    $completionRate = 0;
    $topCsas = collect();
    $accountsRead = 0;
    $accountsNotRead = 0;
    $accountsAbnormal = 0;
    $zeroConsumption = 0;

    /*
    |--------------------------------------------------------------------------
    | Only execute billing-cycle-dependent logic if cycle exists
    |--------------------------------------------------------------------------
    */
    if ($currentCycle) {

        // Total assigned CSA records
        $assignedCsas = CsaAssignment::where(
            'billing_cycle_id',
            $currentCycle->id
        )->count();

        // Total readings in cycle
        $totalReadings = Reading::where(
            'billing_cycle_id',
            $currentCycle->id
        )->count();

        // Completion rate
        $assignedZoneIds = CsaAssignment::where(
            'billing_cycle_id',
            $currentCycle->id
        )->pluck('zone_id');

        $totalAssignedAccounts = CustomerAccount::whereIn(
            'zone_id',
            $assignedZoneIds
        )->count();

        $completionRate = $totalAssignedAccounts > 0
            ? round(($totalReadings / $totalAssignedAccounts) * 100, 2)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Top CSAs
        |--------------------------------------------------------------------------
        */
       $topCsas = Reading::where('billing_cycle_id', $currentCycle->id)
            ->select('csa_id', DB::raw('COUNT(*) as total_readings'))
            ->groupBy('csa_id')
            ->orderByDesc('total_readings')
            ->take(5)
            ->get()
            ->map(function ($item) {

                $user = User::find($item->csa_id);

                $item->csa_name = $user?->username ?? 'Unknown';

                return $item; // KEEP AS OBJECT
            });

        /*
        |--------------------------------------------------------------------------
        | Reading Status Counts
        |--------------------------------------------------------------------------
        */
        $accountsRead = Reading::where('billing_cycle_id', $currentCycle->id)
            ->where('status', 'READ')
            ->count();

        $accountsNotRead = Reading::where('billing_cycle_id', $currentCycle->id)
            ->where('status', 'NOT_READ')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Abnormal Readings
        |--------------------------------------------------------------------------
        */
        $accountsAbnormal = max(
            0,
            $totalReadings - ($accountsRead + $accountsNotRead)
        );

        /*
        |--------------------------------------------------------------------------
        | Zero Consumption
        |--------------------------------------------------------------------------
        */
        $zeroConsumption = Reading::whereNotNull('previous_reading')
            ->whereNotNull('current_reading')
            ->whereRaw('ABS(current_reading - previous_reading) < 0.001')
            ->where('billing_cycle_id', $currentCycle->id)
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Global Metrics
    |--------------------------------------------------------------------------
    */
    $billingAreaEdits = AuditLog::where(
        'action',
        'BILLING_EDIT'
    )->count();

    $gpsMismatch = ExceptionGpsMismatch::count();

    return view('dashboard', compact(
        'totalCsas',
        'totalZones',
        'totalDmas',
        'totalBillingCycles',
        'currentCycle',
        'assignedCsas',
        'totalReadings',
        'completionRate',
        'topCsas',
        'accountsRead',
        'accountsNotRead',
        'accountsAbnormal',
        'zeroConsumption',
        'billingAreaEdits',
        'gpsMismatch',
        'totalAssignedAccounts',
    ))->with(
        'success',
        'Dashboard loaded successfully!'
    );
}


      public function search(Request $request)
    {
        $query = trim($request->input('search'));

        if (!$query) {
            return view('search', [
                'accounts' => [],
                'readings' => [],
                'people' => [],
                'query' => $query
            ]);
        }

        /**
         * CUSTOMER ACCOUNTS
         * Adjust searchable fields as per schema (account_number, name, meter_no, etc.)
         */
        $accounts = CustomerAccount::query()
            ->where('account_number', 'like', "%{$query}%")
            ->orWhere('name', 'like', "%{$query}%")
            ->orWhere('meter_number', 'like', "%{$query}%")
            ->limit(10)
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'title' => $account->name ?? 'Unnamed Account',
                    'subtitle' => "Account: {$account->account_number}",
                    'url' => route('readings.accounts.show', $account->id),
                ];
            });

        /**
         * PEOPLE (USERS)
         */
        $people = User::query()
            ->where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->orWhere('username', 'like', "%{$query}%")
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'title' => $user->name,
                    'subtitle' => $user->email,
                    'role' => $user->role,
                    'url' => route('readings.csas.show', $user),
                ];
            });

        return view('search', [
            'accounts' => $accounts,
            'people' => $people,
            'query' => $query,
        ]);
    }
}