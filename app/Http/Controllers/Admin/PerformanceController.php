<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Zone;
use App\Models\CustomerAccount;
use App\Models\Reading;
use App\Models\BillingCycle;
use App\Models\CsaAssignment;
use App\Models\CustomerAccountIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class PerformanceController extends Controller
{
    /**
     * Technical reading codes - kept identical to DashboardController::technical()
     */
    protected array $technicalCodes = [
        '6',  // stuck meter
        '7',  // damaged meter
        '20', // leaking meter
        '25', // reversing meter
    ];

    /**
     * Performance page - the view (district|csa) is chosen via a
     * ?view= query param on a dropdown filter that submits a normal
     * GET request, so only the selected dataset is computed per load.
     */
    public function index(Request $request)
    {
        $view = $request->query('view', 'district');

        if (! in_array($view, ['district', 'csa'], true)) {
            $view = 'district';
        }

        $currentCycle = BillingCycle::latest()->first();

        /*
        |--------------------------------------------------------------------------
        | Default Safe Values
        |--------------------------------------------------------------------------
        */
        $performanceData = [
            'currentCycle' => $currentCycle,
            'view' => $view,
            'data' => $view === 'district' ? $this->emptyDistrictData() : $this->emptyCsaData(),
        ];

        if ($currentCycle) {
            $recentUploads = $this->recentUploads($currentCycle);

            if ($view === 'district') {
                $assignedZoneIds = CsaAssignment::where(
                    'billing_cycle_id',
                    $currentCycle->id
                )->pluck('zone_id');

                $performanceData['data'] = $this->districtPerformance($currentCycle, $assignedZoneIds, $recentUploads);
            } else {
                $performanceData['data'] = $this->csaPerformance($currentCycle, $recentUploads);
            }
        }

        return view('dashboard.performance', compact('performanceData'));
    }

    /*
    |--------------------------------------------------------------------------
    | District Metrics
    |--------------------------------------------------------------------------
    */
private function districtPerformance(BillingCycle $currentCycle, Collection $assignedZoneIds, Collection $recentUploads): array
{
    /*
    |--------------------------------------------------------------------------
    | Top 5 Districts By Readings
    |--------------------------------------------------------------------------
    */
    $topByReadings = Reading::join('customer_accounts', 'customer_accounts.id', '=', 'readings.account_id')
        ->join('zones', 'zones.id', '=', 'customer_accounts.zone_id')
        ->where('readings.billing_cycle_id', $currentCycle->id)
        ->select('zones.district', DB::raw('COUNT(*) as total_readings'))
        ->groupBy('zones.district')
        ->orderByDesc('total_readings')
        ->take(5)
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Near Completion (Assigned vs Read)
    |--------------------------------------------------------------------------
    */
    $assignedByDistrict = CustomerAccount::join('zones', 'zones.id', '=', 'customer_accounts.zone_id')
        ->whereIn('customer_accounts.zone_id', $assignedZoneIds)
        ->select('zones.district', DB::raw('COUNT(*) as total_assigned'))
        ->groupBy('zones.district')
        ->pluck('total_assigned', 'zones.district');

    $readByDistrict = CustomerAccount::join('zones', 'zones.id', '=', 'customer_accounts.zone_id')
        ->whereIn('customer_accounts.zone_id', $assignedZoneIds)
        ->whereExists(function ($query) {
            $query->selectRaw(1)
                ->from('readings')
                ->whereColumn('readings.account_id', 'customer_accounts.id');
        })
        ->select('zones.district', DB::raw('COUNT(*) as total_read'))
        ->groupBy('zones.district')
        ->pluck('total_read', 'zones.district');

    $nearCompletion = $assignedByDistrict->map(function ($assigned, $district) use ($readByDistrict) {
        $read = $readByDistrict[$district] ?? 0;

        return (object) [
            'district' => $district,
            'assigned' => $assigned,
            'read' => $read,
            'completion_rate' => $assigned > 0 ? round(($read / $assigned) * 100, 2) : 0,
        ];
    })->sortByDesc('completion_rate')->values();

    /*
    |--------------------------------------------------------------------------
    | Most Technical Issues
    |--------------------------------------------------------------------------
    */
    $mostTechnical = Reading::join('customer_accounts', 'customer_accounts.id', '=', 'readings.account_id')
        ->join('zones', 'zones.id', '=', 'customer_accounts.zone_id')
        ->where('readings.billing_cycle_id', $currentCycle->id)
        ->whereIn('readings.this_month_code', $this->technicalCodes)
        ->select('zones.district', DB::raw('COUNT(*) as total_technical'))
        ->groupBy('zones.district')
        ->orderByDesc('total_technical')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Most Flagged (Accounts + Readings)
    |--------------------------------------------------------------------------
    */
    $flaggedAccountsByDistrict = CustomerAccount::join('zones', 'zones.id', '=', 'customer_accounts.zone_id')
        ->whereIn('customer_accounts.zone_id', $assignedZoneIds)
        ->whereHas('flags', fn ($q) => $q->active())
        ->select('zones.district', DB::raw('COUNT(DISTINCT customer_accounts.id) as flagged_accounts'))
        ->groupBy('zones.district')
        ->pluck('flagged_accounts', 'zones.district');

    $flaggedReadingsByDistrict = Reading::join('customer_accounts', 'customer_accounts.id', '=', 'readings.account_id')
        ->join('zones', 'zones.id', '=', 'customer_accounts.zone_id')
        ->where('readings.billing_cycle_id', $currentCycle->id)
        ->whereHas('flags', fn ($q) => $q->active())
        ->select('zones.district', DB::raw('COUNT(DISTINCT readings.id) as flagged_readings'))
        ->groupBy('zones.district')
        ->pluck('flagged_readings', 'zones.district');

    $mostFlagged = $flaggedAccountsByDistrict->keys()
        ->merge($flaggedReadingsByDistrict->keys())
        ->unique()
        ->map(fn ($district) => (object) [
            'district' => $district,
            'flagged_accounts' => $flaggedAccountsByDistrict[$district] ?? 0,
            'flagged_readings' => $flaggedReadingsByDistrict[$district] ?? 0,
        ])
        ->sortByDesc(fn ($row) => $row->flagged_accounts + $row->flagged_readings)
        ->values();

    /*
    |--------------------------------------------------------------------------
    | Field Issues (via reporter -> activeAssignment -> zone -> district)
    |--------------------------------------------------------------------------
    */
    $fieldIssues = CustomerAccountIssue::with('reporter.activeAssignment.zone')
        ->where('created_at', '>=', $currentCycle->start_date)
        ->get();

    $fieldIssuesByDistrict = $fieldIssues
        ->groupBy(fn ($issue) => $issue->reporter?->activeAssignment?->zone?->district ?? 'Unknown')
        ->map(fn ($group, $district) => (object) [
            'district' => $district,
            'total_issues' => $group->count(),
        ])
        ->sortByDesc('total_issues')
        ->values();

    return [
        'topByReadings' => $topByReadings,
        'nearCompletion' => $nearCompletion,
        'mostTechnical' => $mostTechnical,
        'mostFlagged' => $mostFlagged,
        'recentUploads' => $recentUploads,
        'fieldIssues' => $fieldIssuesByDistrict,
    ];
}

    /*
    |--------------------------------------------------------------------------
    | CSA Metrics
    |--------------------------------------------------------------------------
    */
    private function csaPerformance(BillingCycle $currentCycle, Collection $recentUploads): array
    {
        /*
        |--------------------------------------------------------------------------
        | Top 5 CSAs By Readings
        |--------------------------------------------------------------------------
        */
        $topByReadings = Reading::where('billing_cycle_id', $currentCycle->id)
            ->select('csa_id', DB::raw('COUNT(*) as total_readings'))
            ->groupBy('csa_id')
            ->orderByDesc('total_readings')
            ->take(5)
            ->get()
            ->map(fn ($row) => $this->withCsaName($row));

        /*
        |--------------------------------------------------------------------------
        | Near Completion (Assigned vs Read) By Percent
        |--------------------------------------------------------------------------
        */
        $assignedByCsa = CustomerAccount::join('csa_assignments', 'csa_assignments.zone_id', '=', 'customer_accounts.zone_id')
            ->where('csa_assignments.billing_cycle_id', $currentCycle->id)
            ->select('csa_assignments.csa_id', DB::raw('COUNT(*) as total_assigned'))
            ->groupBy('csa_assignments.csa_id')
            ->pluck('total_assigned', 'csa_assignments.csa_id');

        $readByCsa = CustomerAccount::join('csa_assignments', 'csa_assignments.zone_id', '=', 'customer_accounts.zone_id')
            ->where('csa_assignments.billing_cycle_id', $currentCycle->id)
            ->whereExists(function ($query) {
                $query->selectRaw(1)
                    ->from('readings')
                    ->whereColumn('readings.account_id', 'customer_accounts.id');
            })
            ->select('csa_assignments.csa_id', DB::raw('COUNT(*) as total_read'))
            ->groupBy('csa_assignments.csa_id')
            ->pluck('total_read', 'csa_assignments.csa_id');

        $nearCompletion = $assignedByCsa->map(function ($assigned, $csaId) use ($readByCsa) {
            $read = $readByCsa[$csaId] ?? 0;

            return $this->withCsaName((object) [
                'csa_id' => $csaId,
                'assigned' => $assigned,
                'read' => $read,
                'completion_rate' => $assigned > 0 ? round(($read / $assigned) * 100, 2) : 0,
            ]);
        })
        ->sortByDesc('completion_rate')
        ->take(10)  // Limit to top 10 by completion rate
        ->values();

        /*
        |--------------------------------------------------------------------------
        | Most Technical Issues By Count
        |--------------------------------------------------------------------------
        */
        $mostTechnical = Reading::where('billing_cycle_id', $currentCycle->id)
            ->whereIn('this_month_code', $this->technicalCodes)
            ->select('csa_id', DB::raw('COUNT(*) as total_technical'))
            ->groupBy('csa_id')
            ->orderByDesc('total_technical')
            ->get()
            ->map(fn ($row) => $this->withCsaName($row));

        /*
        |--------------------------------------------------------------------------
        | Most Flagged (Accounts + Readings)
        |--------------------------------------------------------------------------
        */
        $flaggedAccountsByCsa = CustomerAccount::join('csa_assignments', 'csa_assignments.zone_id', '=', 'customer_accounts.zone_id')
            ->where('csa_assignments.billing_cycle_id', $currentCycle->id)
            ->whereHas('flags', fn ($q) => $q->active())
            ->select('csa_assignments.csa_id', DB::raw('COUNT(DISTINCT customer_accounts.id) as flagged_accounts'))
            ->groupBy('csa_assignments.csa_id')
            ->pluck('flagged_accounts', 'csa_assignments.csa_id');

        $flaggedReadingsByCsa = Reading::where('billing_cycle_id', $currentCycle->id)
            ->whereHas('flags', fn ($q) => $q->active())
            ->select('csa_id', DB::raw('COUNT(DISTINCT readings.id) as flagged_readings'))
            ->groupBy('csa_id')
            ->pluck('flagged_readings', 'csa_id');

        $mostFlagged = $flaggedAccountsByCsa->keys()
            ->merge($flaggedReadingsByCsa->keys())
            ->unique()
            ->map(fn ($csaId) => $this->withCsaName((object) [
                'csa_id' => $csaId,
                'flagged_accounts' => $flaggedAccountsByCsa[$csaId] ?? 0,
                'flagged_readings' => $flaggedReadingsByCsa[$csaId] ?? 0,
            ]))
            ->sortByDesc(fn ($row) => $row->flagged_accounts + $row->flagged_readings)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Field Issues Reported By Count
        |--------------------------------------------------------------------------
        */
        $fieldIssues = CustomerAccountIssue::where('created_at', '>=', $currentCycle->start_date)
            ->get()
            ->groupBy('reported_by')
            ->map(fn ($group, $csaId) => $this->withCsaName((object) [
                'csa_id' => $csaId,
                'total_issues' => $group->count(),
            ]))
            ->sortByDesc('total_issues')
            ->values();

        return [
            'topByReadings' => $topByReadings,
            'nearCompletion' => $nearCompletion,
            'mostTechnical' => $mostTechnical,
            'mostFlagged' => $mostFlagged,
            'recentUploads' => $recentUploads,
            'fieldIssues' => $fieldIssues,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Recent Uploads (shared base set - district/CSA are just different
    | columns pulled from the same rows, so we only query this once)
    |--------------------------------------------------------------------------
    */
    private function recentUploads(BillingCycle $currentCycle): Collection
    {
        return Reading::with(['zone', 'account'])
            ->where('billing_cycle_id', $currentCycle->id)
            ->whereNotNull('synced_at')
            ->orderByDesc('synced_at')
            ->take(10)
            ->get()
            ->map(function ($reading) {
                $csa = User::find($reading->csa_id);

                return [
                    'account_number' => $reading->account?->account_number,
                    'district' => $reading->account?->zone?->district ?? 'Unknown',
                    'zone_id' => $reading->zone_id,
                    'csa_id' => $reading->csa_id,
                    'csa_name' => $csa?->name ?? 'Unknown',
                    'synced_at' => $reading->synced_at,
                ];
            });
    }

    /**
     * Attach a csa_name to any row/object that carries a csa_id,
     * mirroring the manual User::find() lookup pattern already used
     * in DashboardController::index() (topCsas).
     */
    private function withCsaName(object $row): object
    {
        $user = User::find($row->csa_id);
        $row->csa_name = $user?->name ?? 'Unknown';

        return $row;
    }

    private function emptyDistrictData(): array
    {
        return [
            'topByReadings' => collect(),
            'nearCompletion' => collect(),
            'mostTechnical' => collect(),
            'mostFlagged' => collect(),
            'recentUploads' => collect(),
            'fieldIssues' => collect(),
        ];
    }

    private function emptyCsaData(): array
    {
        return [
            'topByReadings' => collect(),
            'nearCompletion' => collect(),
            'mostTechnical' => collect(),
            'mostFlagged' => collect(),
            'recentUploads' => collect(),
            'fieldIssues' => collect(),
        ];
    }
}