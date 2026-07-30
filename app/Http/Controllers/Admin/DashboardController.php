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




    /**
     * Overview
     */
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
        $totalAssignedAccounts = 0;
        $readings = [];


        $read = CustomerAccount::whereExists(function ($query) {
                $query->selectRaw(1)
                    ->from('readings')
                    ->whereColumn('readings.account_id', 'customer_accounts.id');
            })->count();

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
                )->pluck('zone_id');

                // Total accounts in assigned zones
                $total = CustomerAccount::whereIn('zone_id', $assignedZoneIds)->count();
                
                // Accounts WITH readings (completed)
                $read = CustomerAccount::whereIn('zone_id', $assignedZoneIds)
                    ->whereExists(function ($query) {
                        $query->selectRaw(1)
                            ->from('readings')
                            ->whereColumn('readings.account_id', 'customer_accounts.id');
                    })->count();
                
                // Accounts WITHOUT readings (pending)
                $pending = $total - $read;
            }


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



              // total flagged
                $totalFlagged = Flaggable::active()->distinct('flaggable_id')->count();
        }

        

        if($currentCycle) {
            $readings = Reading::with([
            'pendingReread',
            ])->where('billing_cycle_id', $currentCycle->id)->paginate(10);
        }


       

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
            'totalFlagged'
        ));
    }

    /**
     * Supervisor View
     */
    public function supervisor()
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
        $totalAssignedAccounts = 0;
        $readings = [];
        $totalReRead = 0;
        $accountPendingList = [];


    

    

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
            )->pluck('zone_id');

            // Total accounts in assigned zones
            $total = CustomerAccount::whereIn('zone_id', $assignedZoneIds)->count();
            
            // Accounts WITH readings (completed)
            $read = CustomerAccount::whereIn('zone_id', $assignedZoneIds)
                ->whereExists(function ($query) {
                    $query->selectRaw(1)
                        ->from('readings')
                        ->whereColumn('readings.account_id', 'customer_accounts.id');
                })->count();
            
            // Accounts WITHOUT readings (pending)
            $pending = $total - $read;

            $accountReadList = CustomerAccount::with('assignedCsa')->whereIn('zone_id', $assignedZoneIds)->whereExists(function ($query) {
                $query->selectRaw(1)
                    ->from('readings')
                    ->whereColumn('readings.account_id', 'customer_accounts.id');
            })->paginate(50);

            $accountPendingList = CustomerAccount::with('assignedCsa')->whereIn('zone_id', $assignedZoneIds)->whereNotExists(function ($query) {
                    $query->selectRaw(1)
                        ->from('readings')
                        ->whereColumn('readings.account_id', 'customer_accounts.id');
                })->paginate(50);
      
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

            $accountsRead = Reading::where('billing_cycle_id', $currentCycle->id)
                ->where('status', 'READ')
                ->count();


            // Re readings
            $totalReRead = ReadingReread::where('billing_cycle_id', $currentCycle->id)
                ->where('status', 'completed')
                ->where('updated_at', '>=', now()->subDay())
                ->count();

            $totalReReadPending = ReadingReread::where('billing_cycle_id', $currentCycle->id)
                ->where('status', 'pending')
                ->count();

            $totalReReadCompleted = ReadingReread::where('billing_cycle_id', $currentCycle->id)
                ->where('status', 'completed')
                ->count();
         
        }

        if($currentCycle) {
            $readings = Reading::with([
            'pendingReread',
            ])->where('billing_cycle_id', $currentCycle->id)->paginate(10);
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
        $currentCycle = BillingCycle::latest()->first();

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
            '01',
            '02',
            '05',
            '08',
            '09',
            '12',
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
                ->whereHas('reading', function ($query) use ($technicalCodes) {

                    $query->whereIn(
                        'this_month_code',
                        $technicalCodes
                    );

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

                        '01' => 'Meter Fault',
                        '02' => 'Leak Detected',
                        '05' => 'Damaged Meter',
                        '08' => 'Blocked Meter',
                        '09' => 'No Access',
                        '12' => 'Other Issue',

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

        /**
         * CUSTOMER ACCOUNTS
         * Adjust searchable fields as per schema (account_number, name, meter_no, etc.)
         */
        $accounts = CustomerAccount::query()
            ->where('account_number', 'like', "%{$query}%")
            ->orWhere('customer_name', 'like', "%{$query}%")
            ->orWhere('meter_number', 'like', "%{$query}%")
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