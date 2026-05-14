<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Zone;
use App\Models\SystemNotification;
use App\Models\AccountsSnapshot;
use App\Models\AuditLog;
use App\Models\Reading;
use App\Models\Dma;
use App\Models\BillingCycle;
use App\Models\ExceptionGpsMismatch;
use App\Models\CsaAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\Services\AuditLogService;

class DashboardController extends Controller
{
    protected $auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->auditLog = $auditLog;
    }


    // Index method to show dashboard
    public function index(){
        $totalCsas = User::where('role', 'CSA')->count();
        $totalZones = Zone::count();
        $totalDmas = Dma::count();
        $totalBillingCycles = BillingCycle::count();

        // Get current billing cycle (latest active)
        $currentCycle = BillingCycle::latest()->first();

        // Get completion rate for current cycle
        // We get csa_assignments where billing_cycle_id = current cycle then divide by readings count where billing_cycle_id = current cycle
        $assignedCsas = CsaAssignment::where('billing_cycle_id', $currentCycle->id)->count();
        $totalReadings = Reading::where('billing_cycle_id', $currentCycle->id)->count();

        $completionRate = $totalReadings > 0 ? round(($assignedCsas / $totalReadings) * 100, 2) : 0;


        // We get the top five CSAs with highest number of readings by csa_id column in readings table
        $topCsas = Reading::where('billing_cycle_id', $currentCycle->id)
            ->select('csa_id', \DB::raw('count(*) as total_readings'))
            ->groupBy('csa_id')
            ->take(5)
            ->get()
            ->map(function($item) {
                $item->csa_name = User::find($item->csa_id)->username ?? 'Unknown';
                return $item;
            });


        // We get readings with status READ and NOT_READ as separate variables for the current cycle
        $accountsRead = Reading::where('status', 'READ')->count();
        $accountsNotRead = Reading::where('status', 'NOT_READ')->count();

        // Get accounts with abnormal readings
        $accountsAbnormal = ($accountsRead + $accountsNotRead) < $totalReadings ? $totalReadings - ($accountsRead + $accountsNotRead) : 0;

        // Get Zero consumption readings where previous_reading = current_reading
        $zeroConsumption = Reading::whereColumn('previous_reading', 'current_reading')->where('billing_cycle_id', $currentCycle->id)->count();

        //Billing area edits - we get from audit logs where action = "BILLING_EDIT", these are overall not scoped to billing cycle for now
        $billingAreaEdits = AuditLog::where('action', 'BILLING_EDIT')->count();

        // Get GPS mismatches from the table
        $gpsMismatch = ExceptionGpsMismatch::count();




        return view('dashboard')->with('success', 'Dashboard loaded successfully!')->with(
             compact(
            'totalCsas',
            'accountsRead',
            'accountsNotRead',
            'accountsAbnormal',
            'zeroConsumption',
            'billingAreaEdits',
            'assignedCsas',
            'totalBillingCycles',
            'currentCycle',
            'completionRate',
            'topCsas',
            'gpsMismatch'
            )
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
        $accounts = AccountsSnapshot::query()
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
                    'url' => route('admin.csas.show', $account->id),
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
                    'url' => route('admin.csas.show', $user),
                ];
            });

        return view('search', [
            'accounts' => $accounts,
            'people' => $people,
            'query' => $query,
        ]);
    }
}