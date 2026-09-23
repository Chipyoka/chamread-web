<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Zone;
use App\Models\SystemNotification;
use App\Models\CustomerAccount;
use App\Models\AuditLog;
use App\Models\Reading;
use App\Models\Flaggable;
use App\Models\ReadingReread;
use App\Models\CustomerAccountIssue;
use App\Models\ReadingResolve;
use App\Models\Dma;
use App\Models\BillingCycle;
use App\Models\ExceptionGpsMismatch;
use App\Models\CsaAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Services\FlagService;


use App\Services\AuditLogService;

class DashboardController extends Controller
{
    protected $auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->auditLog = $auditLog;
    }

    /*
    |--------------------------------------------------------------------------
    | District Scoping Helpers
    |--------------------------------------------------------------------------
    |
    | A SUPERVISOR is restricted to a single district (auth()->user()->district).
    | A district can contain multiple zones (zones.district = district->name,
    | compared case-insensitively). Every other resource is scoped down to that
    | set of zone IDs:
    |   - CustomerAccount has a direct zone_id column.
    |   - CsaAssignment has a direct zone_id column.
    |   - Reading, ReadingResolve, ReadingReread do not have a district/zone
    |     column of their own, so they are scoped via their relationship back
    |     to CustomerAccount (reading->account->zone_id).
    |   - CustomerAccountIssue has no account relation at all (an issue can be
    |     filed against an account we have no record of yet). It is instead
    |     scoped via reported_by -> the CSA (user) who submitted it, using
    |     $csaIds (CSAs assigned within the district) rather than $zoneIds.
    |   - Dma is assumed to carry a zone_id column (DMA = District Metering
    |     Area, sitting under a zone). CONFIRM this column name against the
    |     actual Dma model/migration before relying on this in production.
    |   - Flaggable is polymorphic (flaggable_type/flaggable_id) and is scoped
    |     by resolving which CustomerAccount/Reading ids fall inside the
    |     district and matching against those. This assumes flaggable_type
    |     stores the default Eloquent morph class strings
    |     (App\Models\CustomerAccount / App\Models\Reading) — confirm if you
    |     use morph map aliases.
    |
    | Non-SUPERVISOR roles (e.g. ADMIN) get no scoping at all — every helper
    | below returns null in that case, and every query below is written to
    | skip its whereIn/whereHas clause when the scope is null.
    |
    */

    /**
     * Zone IDs the current user is allowed to see.
     * null = unrestricted (non-supervisor / no district set).
     */
    private function scopedZoneIds(): ?array
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'SUPERVISOR' || !$user->district) {
            return null;
        }

        $districtName = strtolower($user->district->name);

        return Zone::whereRaw('LOWER(district) = ?', [$districtName])
            ->pluck('id')
            ->toArray();
    }

    /**
     * CSA (user) IDs who have ever been assigned to a zone in $zoneIds.
     * null in => null out (unrestricted).
     */
    private function scopedCsaIds(?array $zoneIds): ?array
    {
        if ($zoneIds === null) {
            return null;
        }

        return CsaAssignment::whereIn('zone_id', $zoneIds)
            ->distinct()
            ->pluck('csa_id')
            ->toArray();
    }

    /**
     * Apply account-level district scoping to any query builder that has
     * (or can reach, via whereHas) a customer_accounts.zone_id column.
     */
    private function applyAccountZoneScope($query, ?array $zoneIds, string $accountColumn = 'zone_id')
    {
        if ($zoneIds === null) {
            return $query;
        }

        return $query->whereIn($accountColumn, $zoneIds);
    }

    /*
    |--------------------------------------------------------------------------
    | Overview
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $zoneIds = $this->scopedZoneIds();
        $csaIds = $this->scopedCsaIds($zoneIds);

        $totalCsas = User::where('role', 'CSA')
            ->when($csaIds !== null, fn($q) => $q->whereIn('id', $csaIds))
            ->count();

        $totalZones = Zone::when($zoneIds !== null, fn($q) => $q->whereIn('id', $zoneIds))
            ->count();

        // NOTE: assumes Dma has a zone_id column — confirm against the Dma model/migration.
        $totalDmas = Dma::when($zoneIds !== null, fn($q) => $q->whereIn('zone_id', $zoneIds))
            ->count();

        // Billing cycles are global (not district-specific), so no scoping here.
        $totalBillingCycles = BillingCycle::count();

        // Latest billing cycle
        $currentCycle = BillingCycle::where('status', 'active')->first();

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
        $totalAssignedAccounts = 0;
        $readings = [];
        $reportedIssues = 0;
        $totalFlagged = 0;
        $flaggedReadings = collect();


        $read = 0;

        /*
        |--------------------------------------------------------------------------
        | Only execute billing-cycle-dependent logic if cycle exists
        |--------------------------------------------------------------------------
        */

        $pending = 0;
        $totalTechnicalCases = 0;

        $technicalCodes = [
            '6', // stuck metet
            '7', // damaged meter
            '20', // leaking meter
            '25', // reversing meter
        ];


        if ($currentCycle) {
            $assignedZoneIds = CsaAssignment::where(
                'billing_cycle_id',
                $currentCycle->id
            )
                ->when($zoneIds !== null, fn($q) => $q->whereIn('zone_id', $zoneIds))
                ->pluck('zone_id');

            // Total accounts in assigned zones
            $total = CustomerAccount::whereIn('zone_id', $assignedZoneIds)
                ->count();

            // Accounts WITH readings for the CURRENT billing cycle
            $read = CustomerAccount::whereIn('zone_id', $assignedZoneIds)
                ->whereExists(function ($query) use ($currentCycle) {
                    $query->selectRaw(1)
                        ->from('readings')
                        ->whereColumn(
                            'readings.account_id',
                            'customer_accounts.id'
                        )
                        ->where(
                            'readings.billing_cycle_id',
                            $currentCycle->id
                        );
                })
                ->count();

            // Accounts WITHOUT readings for the CURRENT billing cycle
            $pending = $total - $read;
        }


        if ($currentCycle) {

            $totalTechnicalCases = Reading::where(
                'billing_cycle_id',
                $currentCycle->id
            )
                ->whereIn('this_month_code', $technicalCodes)
                ->when($zoneIds !== null, function ($q) use ($zoneIds) {
                    $q->whereHas('account', fn($acc) => $acc->whereIn('zone_id', $zoneIds));
                })
                ->count();


            // Total assigned CSA records
            $assignedCsas = CsaAssignment::where(
                'billing_cycle_id',
                $currentCycle->id
            )
                ->when($zoneIds !== null, fn($q) => $q->whereIn('zone_id', $zoneIds))
                ->count();

            // Total readings in cycle
            $totalReadings = Reading::where(
                'billing_cycle_id',
                $currentCycle->id
            )
                ->when($zoneIds !== null, function ($q) use ($zoneIds) {
                    $q->whereHas('account', fn($acc) => $acc->whereIn('zone_id', $zoneIds));
                })
                ->count();

            // Completion rate
            $assignedZoneIds = CsaAssignment::where(
                'billing_cycle_id',
                $currentCycle->id
            )
                ->when($zoneIds !== null, fn($q) => $q->whereIn('zone_id', $zoneIds))
                ->pluck('zone_id');

            $totalAssignedAccounts = CustomerAccount::whereIn(
                'zone_id',
                $assignedZoneIds
            )->count();

            $completionRate = $totalAssignedAccounts > 0
                ? round(($totalReadings / $totalAssignedAccounts) * 100, 2)
                : 0;


            // CustomerAccountIssue has no account relation (issues can be filed
            // against accounts we have no record of yet), so it is scoped via
            // reported_by -> the CSA who submitted it, using $csaIds instead of
            // $zoneIds/whereHas('account', ...).
            $reportedIssues = CustomerAccountIssue::whereDate(
                'created_at',
                '>=',
                $currentCycle->start_date
            )
                ->when($csaIds !== null, fn($q) => $q->whereIn('reported_by', $csaIds))
                ->count();
            /*
            |--------------------------------------------------------------------------
            | Top CSAs
            |--------------------------------------------------------------------------
            */
            $topCsas = Reading::where('billing_cycle_id', $currentCycle->id)
                ->when($zoneIds !== null, function ($q) use ($zoneIds) {
                    $q->whereHas('account', fn($acc) => $acc->whereIn('zone_id', $zoneIds));
                })
                ->select('csa_id', DB::raw('COUNT(*) as total_readings'))
                ->groupBy('csa_id')
                ->orderByDesc('total_readings')
                ->take(5)
                ->get()
                ->map(function ($item) {

                    $user = User::find($item->csa_id);

                    $item->csa_name = $user?->name ?? 'Unknown';

                    return $item; // KEEP AS OBJECT
                });

            /*
            |--------------------------------------------------------------------------
            | Reading Status Counts
            |--------------------------------------------------------------------------
            */
            $accountsRead = Reading::where('billing_cycle_id', $currentCycle->id)
                ->where('status', 'READ')
                ->when($zoneIds !== null, function ($q) use ($zoneIds) {
                    $q->whereHas('account', fn($acc) => $acc->whereIn('zone_id', $zoneIds));
                })
                ->count();

            $accountsNotRead = Reading::where('billing_cycle_id', $currentCycle->id)
                ->where('status', 'NOT_READ')
                ->when($zoneIds !== null, function ($q) use ($zoneIds) {
                    $q->whereHas('account', fn($acc) => $acc->whereIn('zone_id', $zoneIds));
                })
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
                ->when($zoneIds !== null, function ($q) use ($zoneIds) {
                    $q->whereHas('account', fn($acc) => $acc->whereIn('zone_id', $zoneIds));
                })
                ->count();



            // total flagged (scoped to accounts/readings that fall inside the district)
            $totalFlagged = Flaggable::active()
                ->when($zoneIds !== null, function ($q) use ($zoneIds) {
                    $q->where(function ($outer) use ($zoneIds) {
                        $outer->where(function ($accountFlags) use ($zoneIds) {
                            $accountFlags->where('flaggable_type', CustomerAccount::class)
                                ->whereIn('flaggable_id', function ($sub) use ($zoneIds) {
                                    $sub->select('id')
                                        ->from('customer_accounts')
                                        ->whereIn('zone_id', $zoneIds);
                                });
                        })->orWhere(function ($readingFlags) use ($zoneIds) {
                            $readingFlags->where('flaggable_type', Reading::class)
                                ->whereIn('flaggable_id', function ($sub) use ($zoneIds) {
                                    $sub->select('readings.id')
                                        ->from('readings')
                                        ->join('customer_accounts', 'customer_accounts.id', '=', 'readings.account_id')
                                        ->whereIn('customer_accounts.zone_id', $zoneIds);
                                });
                        });
                    });
                })
                ->distinct('flaggable_id')
                ->count();
        }



        if ($currentCycle) {
            $readings = Reading::with([
                'pendingReread',
            ])
                ->where('billing_cycle_id', $currentCycle->id)
                ->when($zoneIds !== null, function ($q) use ($zoneIds) {
                    $q->whereHas('account', fn($acc) => $acc->whereIn('zone_id', $zoneIds));
                })
                ->paginate(10);


            // Readings that have at least one flag
            $flaggedReadings = Reading::whereHas('flags')
                ->when($zoneIds !== null, function ($q) use ($zoneIds) {
                    $q->whereHas('account', fn($acc) => $acc->whereIn('zone_id', $zoneIds));
                })
                ->with(['flags'])  // eager load flags
                ->latest()
                ->limit(50)
                ->get();
        }
        // Accounts that have at least one flag
        $flaggedAccounts = CustomerAccount::whereHas('flags')
            ->when($zoneIds !== null, fn($q) => $q->whereIn('zone_id', $zoneIds))
            ->with('flags')  // eager load flags
            ->latest()
            ->limit(50)
            ->get();



        $totalAccountsLoaded = CustomerAccount::when($zoneIds !== null, fn($q) => $q->whereIn('zone_id', $zoneIds))
            ->count();



        return view('dashboard.index', compact(
            'totalCsas',
            'totalZones',
            'totalDmas',
            'totalBillingCycles',
            'currentCycle',
            'assignedCsas',
            'totalReadings',
            'completionRate',
            'topCsas',
            'read',
            'accountsRead',
            'accountsNotRead',
            'accountsAbnormal',
            'totalAssignedAccounts',
            'pending',
            'readings',
            'totalFlagged',
            'flaggedAccounts',
            'flaggedReadings',
            'reportedIssues',
            'totalAccountsLoaded',
            'totalTechnicalCases',
        ));
    }

    /**
     * Supervisor View
     */
    public function supervisor()
    {
        $zoneIds = $this->scopedZoneIds();
        $csaIds = $this->scopedCsaIds($zoneIds);

        $totalCsas = User::where('role', 'CSA')
            ->when($csaIds !== null, fn($q) => $q->whereIn('id', $csaIds))
            ->count();

        $totalZones = Zone::when($zoneIds !== null, fn($q) => $q->whereIn('id', $zoneIds))
            ->count();

        // NOTE: assumes Dma has a zone_id column — confirm against the Dma model/migration.
        $totalDmas = Dma::when($zoneIds !== null, fn($q) => $q->whereIn('zone_id', $zoneIds))
            ->count();

        $totalBillingCycles = BillingCycle::count();

        // Latest billing cycle
        $currentCycle = BillingCycle::where('status', 'active')->first();

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
        $totalAssignedAccounts = 0;
        $readings = [];
        $totalReRead = 0;
        $accountPendingList = [];
        $read = 0;
        $totalReReadCompleted = 0;
        $totalReReadPending = 0;
        $accountReadList = [];


        /*
        |--------------------------------------------------------------------------
        | Only execute billing-cycle-dependent logic if cycle exists
        |--------------------------------------------------------------------------
        */

        $pending = 0;

        if ($currentCycle) {
            $assignedZoneIds = CsaAssignment::where(
                'billing_cycle_id',
                $currentCycle->id
            )
                ->when($zoneIds !== null, fn($q) => $q->whereIn('zone_id', $zoneIds))
                ->pluck('zone_id');

            // Total accounts in assigned zones
            $total = CustomerAccount::whereIn('zone_id', $assignedZoneIds)
                ->count();

            // Accounts WITH readings for the CURRENT billing cycle
            $read = CustomerAccount::whereIn('zone_id', $assignedZoneIds)
                ->whereExists(function ($query) use ($currentCycle) {
                    $query->selectRaw(1)
                        ->from('readings')
                        ->whereColumn(
                            'readings.account_id',
                            'customer_accounts.id'
                        )
                        ->where(
                            'readings.billing_cycle_id',
                            $currentCycle->id
                        );
                })
                ->count();

            // Accounts WITHOUT readings for the CURRENT billing cycle
            $pending = $total - $read;

            $accountReadList = CustomerAccount::with('assignedCsa')
                ->whereIn('zone_id', $assignedZoneIds)
                ->whereExists(function ($query) {
                    $query->selectRaw(1)
                        ->from('readings')
                        ->whereColumn('readings.account_id', 'customer_accounts.id');
                })
                ->paginate(50);

            $accountPendingList = CustomerAccount::with('assignedCsa')
                ->whereIn('zone_id', $assignedZoneIds)
                ->whereNotExists(function ($query) {
                    $query->selectRaw(1)
                        ->from('readings')
                        ->whereColumn('readings.account_id', 'customer_accounts.id');
                })
                ->paginate(50);

            // Total readings in cycle
            $totalReadings = Reading::where(
                'billing_cycle_id',
                $currentCycle->id
            )
                ->when($zoneIds !== null, function ($q) use ($zoneIds) {
                    $q->whereHas('account', fn($acc) => $acc->whereIn('zone_id', $zoneIds));
                })
                ->count();

            // Completion rate
            $assignedZoneIds = CsaAssignment::where(
                'billing_cycle_id',
                $currentCycle->id
            )
                ->when($zoneIds !== null, fn($q) => $q->whereIn('zone_id', $zoneIds))
                ->pluck('zone_id');

            $totalAssignedAccounts = CustomerAccount::whereIn(
                'zone_id',
                $assignedZoneIds
            )->count();

            $completionRate = $totalAssignedAccounts > 0
                ? round(($totalReadings / $totalAssignedAccounts) * 100, 2)
                : 0;

            $accountsRead = Reading::where('billing_cycle_id', $currentCycle->id)
                ->where('status', 'READ')
                ->when($zoneIds !== null, function ($q) use ($zoneIds) {
                    $q->whereHas('account', fn($acc) => $acc->whereIn('zone_id', $zoneIds));
                })
                ->count();


            // Re readings
            $totalReRead = ReadingReread::where('billing_cycle_id', $currentCycle->id)
                ->where('status', 'completed')
                ->where('updated_at', '>=', now()->subDay())
                ->when($zoneIds !== null, function ($q) use ($zoneIds) {
                    $q->whereHas('reading.account', fn($acc) => $acc->whereIn('zone_id', $zoneIds));
                })
                ->count();

            $totalReReadPending = ReadingReread::where('billing_cycle_id', $currentCycle->id)
                ->where('status', 'pending')
                ->when($zoneIds !== null, function ($q) use ($zoneIds) {
                    $q->whereHas('reading.account', fn($acc) => $acc->whereIn('zone_id', $zoneIds));
                })
                ->count();

            $totalReReadCompleted = ReadingReread::where('billing_cycle_id', $currentCycle->id)
                ->where('status', 'completed')
                ->when($zoneIds !== null, function ($q) use ($zoneIds) {
                    $q->whereHas('reading.account', fn($acc) => $acc->whereIn('zone_id', $zoneIds));
                })
                ->count();

        }

        if ($currentCycle) {
            $readings = Reading::with([
                'pendingReread',
            ])
                ->where('billing_cycle_id', $currentCycle->id)
                ->when($zoneIds !== null, function ($q) use ($zoneIds) {
                    $q->whereHas('account', fn($acc) => $acc->whereIn('zone_id', $zoneIds));
                })
                ->paginate(10);
        }



        return view('dashboard.supervisor', [
            'overviewData' => [
                'totalCsas' => $totalCsas,
                'totalZones' => $totalZones,
                'totalDmas' => $totalDmas,
                'totalBillingCycles' => $totalBillingCycles,
                'currentCycle' => $currentCycle,
                'assignedCsas' => $assignedCsas,
                'totalReadings' => $totalReadings,
                'completionRate' => $completionRate,
                'topCsas' => $topCsas,
                'read' => $read,
                'accountsRead' => $accountsRead,
                'accountsNotRead' => $accountsNotRead,
                'totalAssignedAccounts' => $totalAssignedAccounts,
                'pending' => $pending,
                'readings' => $readings,
                'totalReRead' => $totalReRead,
                'totalReReadCompleted' => $totalReReadCompleted,
                'totalReReadPending' => $totalReReadPending,
                'accountPendingList' => $accountPendingList,
                'accountReadList' => $accountReadList,

            ]
        ]);
    }

    /**
     * Technical View
     */
    public function technical()
    {
        $zoneIds = $this->scopedZoneIds();

        $currentCycle = BillingCycle::where('status', 'active')->first();

        /*
        |--------------------------------------------------------------------------
        | Default Values
        |--------------------------------------------------------------------------
        */

        $totalTechnicalCases = 0;
        $pendingResolves = 0;
        $resolvedCases = 0;
        $resolvedToday = 0;
        $resolutionRate = 0;

        $technicalDistribution = collect();
        $technicalReadings = collect();


        /*
        |--------------------------------------------------------------------------
        | Technical Reading Codes
        |--------------------------------------------------------------------------
        */

        $technicalCodes = [
            '6', // stuck metet
            '7', // damaged meter
            '20', // leaking meter
            '25', // reversing meter
        ];


        if ($currentCycle) {


            /*
            |--------------------------------------------------------------------------
            | Technical Reading Queue
            |--------------------------------------------------------------------------
            */

            $technicalReadings = Reading::with([
                'account',
                'zone',
                'latestResolve',
            ])
                ->where('billing_cycle_id', $currentCycle->id)
                ->whereIn('this_month_code', $technicalCodes)
                ->whereDoesntHave('resolves')
                ->when($zoneIds !== null, function ($q) use ($zoneIds) {
                    $q->whereHas('account', fn($acc) => $acc->whereIn('zone_id', $zoneIds));
                })
                ->latest()
                ->paginate(10);



            /*
            |--------------------------------------------------------------------------
            | Total Technical Cases
            |--------------------------------------------------------------------------
            */

            $totalTechnicalCases = Reading::where(
                'billing_cycle_id',
                $currentCycle->id
            )
                ->whereIn('this_month_code', $technicalCodes)
                ->when($zoneIds !== null, function ($q) use ($zoneIds) {
                    $q->whereHas('account', fn($acc) => $acc->whereIn('zone_id', $zoneIds));
                })
                ->count();



            /*
            |--------------------------------------------------------------------------
            | Resolution Metrics
            |--------------------------------------------------------------------------
            */

            $resolvedCases = ReadingResolve::where(
                'billing_cycle_id',
                $currentCycle->id
            )
                ->whereHas('reading', function ($query) use ($technicalCodes, $zoneIds) {

                    $query->whereIn(
                        'this_month_code',
                        $technicalCodes
                    );

                    if ($zoneIds !== null) {
                        $query->whereHas('account', fn($acc) => $acc->whereIn('zone_id', $zoneIds));
                    }

                })
                ->count();



            $pendingResolves = max(
                0,
                $totalTechnicalCases - $resolvedCases
            );



            $resolvedToday = ReadingResolve::where(
                'billing_cycle_id',
                $currentCycle->id
            )
                ->where('created_at', '>=', now()->subDay())
                ->when($zoneIds !== null, function ($q) use ($zoneIds) {
                    $q->whereHas('reading.account', fn($acc) => $acc->whereIn('zone_id', $zoneIds));
                })
                ->count();



            $resolutionRate = $totalTechnicalCases > 0
                ? round(
                    ($resolvedCases / $totalTechnicalCases) * 100,
                    2
                )
                : 0;



            /*
            |--------------------------------------------------------------------------
            | Technical Issue Distribution
            |--------------------------------------------------------------------------
            */

            $technicalDistribution = Reading::where(
                'billing_cycle_id',
                $currentCycle->id
            )
                ->whereIn(
                    'this_month_code',
                    $technicalCodes
                )
                ->when($zoneIds !== null, function ($q) use ($zoneIds) {
                    $q->whereHas('account', fn($acc) => $acc->whereIn('zone_id', $zoneIds));
                })
                ->select(
                    'this_month_code',
                    DB::raw('COUNT(*) as total_cases')
                )
                ->groupBy('this_month_code')
                ->orderByDesc('total_cases')
                ->get()
                ->map(function ($item) {


                    /*
                    |--------------------------------------------------------------
                    | Temporary Labels
                    | Replace with code table later
                    |--------------------------------------------------------------
                    */

                    $labels = [

                        '6' => 'Stuck Meter',
                        '7' => 'Damaged Meter',
                        '20' => 'Leaking Meter',
                        '25' => 'Reversing Meter',

                    ];


                    $item->issue_name =
                        $labels[$item->this_month_code]
                        ?? 'Unknown';


                    return $item;

                });

        }



        return view('dashboard.technical', [

            'technicalData' => [

                'currentCycle' => $currentCycle,


                /*
                |--------------------------------------------------------------------------
                | Cards
                |--------------------------------------------------------------------------
                */

                'totalTechnicalCases' => $totalTechnicalCases,

                'pendingResolves' => $pendingResolves,

                'resolvedCases' => $resolvedCases,

                'resolvedToday' => $resolvedToday,

                'resolutionRate' => $resolutionRate,


                /*
                |--------------------------------------------------------------------------
                | Charts
                |--------------------------------------------------------------------------
                */

                'piePending' => $pendingResolves,

                'pieResolved' => $resolvedCases,

                'barChartData' => $technicalDistribution,


                /*
                |--------------------------------------------------------------------------
                | Table
                |--------------------------------------------------------------------------
                */

                'readings' => $technicalReadings,

            ],

        ]);
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

        $zoneIds = $this->scopedZoneIds();
        $csaIds = $this->scopedCsaIds($zoneIds);

        /**
         * CUSTOMER ACCOUNTS
         * Adjust searchable fields as per schema (account_number, name, meter_no, etc.)
         */
        $accounts = CustomerAccount::query()
            ->where('account_number', 'like', "%{$query}%")
            ->orWhere('customer_name', 'like', "%{$query}%")
            ->orWhere('meter_number', 'like', "%{$query}%")
            ->when($zoneIds !== null, fn($q) => $q->whereIn('zone_id', $zoneIds))
            ->limit(10)
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'title' => $account->customer_name ?? 'Unnamed Account',
                    'subtitle' => "Account: {$account->account_number}",
                    'url' => route('readings.accounts.show', $account->id),
                ];
            });

        /**
         * PEOPLE (USERS)
         */
        $people = User::query()
            ->where('role', 'CSA')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('username', 'like', "%{$query}%");
            })
            ->when($csaIds !== null, fn($q) => $q->whereIn('id', $csaIds))
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