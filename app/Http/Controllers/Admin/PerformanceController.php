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
     * Technical reading codes indicating meter issues.
     * These codes are used to identify problematic readings
     * across both district and CSA performance metrics.
     *
     * @var array<string>
     */
    protected array $technicalCodes = [
        '6',  // stuck meter
        '7',  // damaged meter
        '20', // leaking meter
        '25', // reversing meter
    ];

    /**
     * Display the performance dashboard with district or CSA view.
     * 
     * The view selection is controlled via a `?view=` query parameter
     * in a GET request, ensuring only the selected dataset is computed
     * per page load for optimal performance.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $view = $request->query('view', 'district');

        if (!in_array($view, ['district', 'csa'], true)) {
            $view = 'district';
        }

        $currentCycle = BillingCycle::where('status', 'active')->first();

        $performanceData = [
            'currentCycle' => $currentCycle,
            'view' => $view,
            'data' => $view === 'district' ? $this->emptyDistrictData() : $this->emptyCsaData(),
            'activity' => ['labels' => [], 'data' => []],
        ];

        if ($currentCycle) {
            $recentUploads = $this->recentUploads($currentCycle);
            $performanceData['activity'] = $this->uploadActivity($currentCycle);

            if ($view === 'district') {
                $assignedZoneIds = CsaAssignment::where(
                    'billing_cycle_id',
                    $currentCycle->id
                )->pluck('zone_id');

                $performanceData['data'] = $this->districtPerformance(
                    $currentCycle,
                    $assignedZoneIds,
                    $recentUploads
                );
            } else {
                $performanceData['data'] = $this->csaPerformance(
                    $currentCycle,
                    $recentUploads
                );
            }
        }

        return view('dashboard.performance', compact('performanceData'));
    }

    /**
     * Calculate district-level performance metrics.
     *
     * @param BillingCycle $currentCycle
     * @param Collection $assignedZoneIds
     * @param Collection $recentUploads
     * @return array
     */
    private function districtPerformance(
        BillingCycle $currentCycle,
        Collection $assignedZoneIds,
        Collection $recentUploads
    ): array {
        // Get district assignment counts (total accounts assigned per district)
        $districtAssignmentCounts = $this->getDistrictAssignmentCounts($currentCycle);

        // Get district read counts (accounts with readings per district)
        $districtReadCounts = $this->getDistrictReadCounts($currentCycle);

        // Build near completion data with both assigned and read counts
        $nearCompletion = $this->buildDistrictNearCompletion(
            $districtAssignmentCounts,
            $districtReadCounts
        );

        // Calculate average completion rate across all districts
        $averageCompletionRate = $this->calculateDistrictAverageCompletionRate(
            $districtAssignmentCounts,
            $districtReadCounts
        );

        return [
            'topByReadings' => $this->getTopDistrictsByReadings($currentCycle),
            'nearCompletion' => $nearCompletion,
            'mostTechnical' => $this->getDistrictsWithMostTechnicalIssues($currentCycle),
            'mostFlagged' => $this->getDistrictsWithMostFlags($currentCycle, $assignedZoneIds),
            'recentUploads' => $recentUploads,
            'fieldIssues' => $this->getDistrictFieldIssues($currentCycle),
            'belowAverage' => $this->getBelowAverageDistricts(
                $districtAssignmentCounts,
                $districtReadCounts
            ),
            'averageCompletionRate' => $averageCompletionRate,
        ];
    }

    /**
     * Calculate CSA-level performance metrics.
     *
     * @param BillingCycle $currentCycle
     * @param Collection $recentUploads
     * @return array
     */
    private function csaPerformance(
        BillingCycle $currentCycle,
        Collection $recentUploads
    ): array {
        // Get CSA assignment counts (total accounts assigned per CSA)
        $csaAssignmentCounts = $this->getCsaAssignmentCounts($currentCycle);

        // Get CSA read counts (accounts with readings per CSA)
        $csaReadCounts = $this->getCsaReadCounts($currentCycle);

        // Build near completion data with both assigned and read counts
        $nearCompletion = $this->buildCsaNearCompletion(
            $csaAssignmentCounts,
            $csaReadCounts
        );

        // Calculate average completion rate across all CSAs
        $averageCompletionRate = $this->calculateCsaAverageCompletionRate(
            $csaAssignmentCounts,
            $csaReadCounts
        );

        return [
            'topByReadings' => $this->getTopCsasByReadings($currentCycle),
            'nearCompletion' => $nearCompletion,
            'mostTechnical' => $this->getCsasWithMostTechnicalIssues($currentCycle),
            'mostFlagged' => $this->getCsasWithMostFlags($currentCycle),
            'recentUploads' => $recentUploads,
            'fieldIssues' => $this->getCsaFieldIssues($currentCycle),
            'belowAverage' => $this->getBelowAverageCsas(
                $csaAssignmentCounts,
                $csaReadCounts
            ),
            'averageCompletionRate' => $averageCompletionRate,
        ];
    }

    /**
     * Get total assigned accounts per district for the current cycle.
     * 
     * Uses the assignment target as the source of truth for total accounts
     * assigned to each district. This ensures consistency and accounts for
     * CSAs who may have incomplete readings.
     *
     * @param BillingCycle $currentCycle
     * @return Collection
     */
    private function getDistrictAssignmentCounts(BillingCycle $currentCycle): Collection
    {
        return CsaAssignment::where('billing_cycle_id', $currentCycle->id)
            ->join('zones', 'zones.id', '=', 'csa_assignments.zone_id')
            ->select(
                'zones.district',
                DB::raw('SUM(csa_assignments.target) as total_assigned')
            )
            ->groupBy('zones.district')
            ->pluck('total_assigned', 'district');
    }

    /**
     * Get total accounts with readings per district for the current cycle.
     * 
     * Counts distinct accounts that have at least one reading in the current
     * billing cycle, grouped by district.
     *
     * @param BillingCycle $currentCycle
     * @return Collection
     */
    private function getDistrictReadCounts(BillingCycle $currentCycle): Collection
    {
        return Reading::join('customer_accounts', 'customer_accounts.id', '=', 'readings.account_id')
            ->join('zones', 'zones.id', '=', 'customer_accounts.zone_id')
            ->where('readings.billing_cycle_id', $currentCycle->id)
            ->select(
                'zones.district',
                DB::raw('COUNT(DISTINCT readings.account_id) as total_read')
            )
            ->groupBy('zones.district')
            ->pluck('total_read', 'district');
    }

    /**
     * Build near completion data for districts.
     * 
     * Calculates completion rate as (read / assigned) * 100 for each district.
     * Sorts by completion rate descending to show highest performers first.
     *
     * @param Collection $assignmentCounts
     * @param Collection $readCounts
     * @return Collection
     */
    private function buildDistrictNearCompletion(
        Collection $assignmentCounts,
        Collection $readCounts
    ): Collection {
        return $assignmentCounts->map(function ($assigned, $district) use ($readCounts) {
            $read = $readCounts[$district] ?? 0;

            return (object) [
                'district' => $district,
                'assigned' => $assigned,
                'read' => $read,
                'completion_rate' => $assigned > 0 
                    ? round(($read / $assigned) * 100, 2) 
                    : 0,
            ];
        })->sortByDesc('completion_rate')->values();
    }

    /**
     * Get top 5 districts by total reading count.
     *
     * @param BillingCycle $currentCycle
     * @return Collection
     */
    private function getTopDistrictsByReadings(BillingCycle $currentCycle): Collection
    {
        return Reading::join('customer_accounts', 'customer_accounts.id', '=', 'readings.account_id')
            ->join('zones', 'zones.id', '=', 'customer_accounts.zone_id')
            ->where('readings.billing_cycle_id', $currentCycle->id)
            ->select('zones.district', DB::raw('COUNT(*) as total_readings'))
            ->groupBy('zones.district')
            ->orderByDesc('total_readings')
            ->take(5)
            ->get();
    }

    /**
     * Get districts with most technical issues.
     *
     * @param BillingCycle $currentCycle
     * @return Collection
     */
    private function getDistrictsWithMostTechnicalIssues(BillingCycle $currentCycle): Collection
    {
        return Reading::join('customer_accounts', 'customer_accounts.id', '=', 'readings.account_id')
            ->join('zones', 'zones.id', '=', 'customer_accounts.zone_id')
            ->where('readings.billing_cycle_id', $currentCycle->id)
            ->whereIn('readings.this_month_code', $this->technicalCodes)
            ->select('zones.district', DB::raw('COUNT(*) as total_technical'))
            ->groupBy('zones.district')
            ->orderByDesc('total_technical')
            ->get();
    }

    /**
     * Get districts with most flagged accounts and readings.
     *
     * @param BillingCycle $currentCycle
     * @param Collection $assignedZoneIds
     * @return Collection
     */
    private function getDistrictsWithMostFlags(
        BillingCycle $currentCycle,
        Collection $assignedZoneIds
    ): Collection {
        $flaggedAccountsByDistrict = CustomerAccount::join('zones', 'zones.id', '=', 'customer_accounts.zone_id')
            ->whereIn('customer_accounts.zone_id', $assignedZoneIds)
            ->whereHas('flags', fn($q) => $q->active())
            ->select('zones.district', DB::raw('COUNT(DISTINCT customer_accounts.id) as flagged_accounts'))
            ->groupBy('zones.district')
            ->pluck('flagged_accounts', 'district');

        $flaggedReadingsByDistrict = Reading::join('customer_accounts', 'customer_accounts.id', '=', 'readings.account_id')
            ->join('zones', 'zones.id', '=', 'customer_accounts.zone_id')
            ->where('readings.billing_cycle_id', $currentCycle->id)
            ->whereHas('flags', fn($q) => $q->active())
            ->select('zones.district', DB::raw('COUNT(DISTINCT readings.id) as flagged_readings'))
            ->groupBy('zones.district')
            ->pluck('flagged_readings', 'district');

        return $flaggedAccountsByDistrict->keys()
            ->merge($flaggedReadingsByDistrict->keys())
            ->unique()
            ->map(fn($district) => (object) [
                'district' => $district,
                'flagged_accounts' => $flaggedAccountsByDistrict[$district] ?? 0,
                'flagged_readings' => $flaggedReadingsByDistrict[$district] ?? 0,
            ])
            ->sortByDesc(fn($row) => $row->flagged_accounts + $row->flagged_readings)
            ->values();
    }

    /**
     * Get field issues reported by district.
     *
     * @param BillingCycle $currentCycle
     * @return Collection
     */
    private function getDistrictFieldIssues(BillingCycle $currentCycle): Collection
    {
        $fieldIssues = CustomerAccountIssue::with('reporter.activeAssignment.zone')
            ->where('created_at', '>=', $currentCycle->start_date)
            ->get();

        return $fieldIssues
            ->groupBy(fn($issue) => $issue->reporter?->activeAssignment?->zone?->district ?? 'Unknown')
            ->map(fn($group, $district) => (object) [
                'district' => $district,
                'total_issues' => $group->count(),
            ])
            ->sortByDesc('total_issues')
            ->values();
    }

    /**
     * Identify underperforming districts based on completion rate.
     * 
     * A district is considered "below average" if its completion rate
     * is below the overall average completion rate. This is more fair
     * than comparing raw reading counts because it accounts for districts
     * with different numbers of assigned accounts.
     *
     * @param Collection $assignmentCounts
     * @param Collection $readCounts
     * @return Collection
     */
    private function getBelowAverageDistricts(
        Collection $assignmentCounts,
        Collection $readCounts
    ): Collection {
        // Calculate completion rate for each district
        $districtCompletionRates = $assignmentCounts->map(function ($assigned, $district) use ($readCounts) {
            $read = $readCounts[$district] ?? 0;
            return (object) [
                'district' => $district,
                'total_readings' => $read,
                'assigned' => $assigned,
                'read' => $read,
                'completion_rate' => $assigned > 0 
                    ? round(($read / $assigned) * 100, 2) 
                    : 0,
            ];
        })->values();

        // Calculate average completion rate across all districts
        $averageCompletionRate = $districtCompletionRates->avg('completion_rate') ?? 0;

        // Filter districts below average, sort by completion rate (ascending - worst first)
        $belowAverage = $districtCompletionRates
            ->filter(fn($row) => $row->completion_rate < $averageCompletionRate)
            ->sortBy('completion_rate')  //  Ensures worst performers first
            ->take(10)
            ->values();  //  Reset keys

        // Now assign ranks based on sorted order
        $rankedResults = $belowAverage->map(function ($row, $index) use ($averageCompletionRate) {
            $row->rank = $index + 1;  //  Rank 1 = worst performer
            $row->name = $row->district;
            $row->gap = round($averageCompletionRate - $row->completion_rate, 2);
            $row->percent_of_average = $averageCompletionRate > 0
                ? round(($row->completion_rate / $averageCompletionRate) * 100)
                : 0;

            return $row;
        });

        return $rankedResults;
    }

    /**
     * Calculate the average completion rate across districts.
     * 
     * This calculates the mean of all district completion rates,
     * providing a fair benchmark for identifying underperformers.
     *
     * @param Collection $assignmentCounts
     * @param Collection $readCounts
     * @return float
     */
    private function calculateDistrictAverageCompletionRate(Collection $assignmentCounts, Collection $readCounts): float
    {
        $completionRates = $assignmentCounts->map(function ($assigned, $district) use ($readCounts) {
            $read = $readCounts[$district] ?? 0;
            return $assigned > 0 ? round(($read / $assigned) * 100, 2) : 0;
        });
        
        return round($completionRates->avg() ?? 0, 2);
    }

    /**
     * Get total assigned accounts per CSA for the current cycle.
     * 
     * Uses the assignment target as the source of truth for total accounts
     * assigned to each CSA. This ensures consistency with the assignment
     * records and provides accurate workload measurements.
     *
     * @param BillingCycle $currentCycle
     * @return Collection
     */
    private function getCsaAssignmentCounts(BillingCycle $currentCycle): Collection
    {
        return CsaAssignment::where('billing_cycle_id', $currentCycle->id)
            ->select(
                'csa_id',
                DB::raw('SUM(target) as total_assigned')
            )
            ->groupBy('csa_id')
            ->pluck('total_assigned', 'csa_id');
    }

    /**
     * Get total accounts with readings per CSA for the current cycle.
     * 
     * Counts distinct accounts that have at least one reading in the current
     * billing cycle, grouped by CSA. This is more accurate than counting
     * reading records as it handles multiple readings per account.
     *
     * @param BillingCycle $currentCycle
     * @return Collection
     */
    private function getCsaReadCounts(BillingCycle $currentCycle): Collection
    {
        return Reading::where('billing_cycle_id', $currentCycle->id)
            ->select(
                'csa_id',
                DB::raw('COUNT(DISTINCT account_id) as total_read')
            )
            ->groupBy('csa_id')
            ->pluck('total_read', 'csa_id');
    }

    /**
     * Build near completion data for CSAs.
     * 
     * Calculates completion rate as (read / assigned) * 100 for each CSA.
     * Attaches CSA names for display purposes.
     *
     * @param Collection $assignmentCounts
     * @param Collection $readCounts
     * @return Collection
     */
    private function buildCsaNearCompletion(
        Collection $assignmentCounts,
        Collection $readCounts
    ): Collection {
        return $assignmentCounts->map(function ($assigned, $csaId) use ($readCounts) {
            $read = $readCounts[$csaId] ?? 0;

            return $this->withCsaName((object) [
                'csa_id' => $csaId,
                'assigned' => $assigned,
                'read' => $read,
                'completion_rate' => $assigned > 0 
                    ? round(($read / $assigned) * 100, 2) 
                    : 0,
            ]);
        })
        ->sortByDesc('completion_rate')
        ->take(10)
        ->values();
    }

    /**
     * Get top 5 CSAs by total reading count.
     *
     * @param BillingCycle $currentCycle
     * @return Collection
     */
    private function getTopCsasByReadings(BillingCycle $currentCycle): Collection
    {
        return Reading::where('billing_cycle_id', $currentCycle->id)
            ->select('csa_id', DB::raw('COUNT(*) as total_readings'))
            ->groupBy('csa_id')
            ->orderByDesc('total_readings')
            ->take(5)
            ->get()
            ->map(fn($row) => $this->withCsaName($row));
    }

    /**
     * Get CSAs with most technical issues.
     *
     * @param BillingCycle $currentCycle
     * @return Collection
     */
    private function getCsasWithMostTechnicalIssues(BillingCycle $currentCycle): Collection
    {
        return Reading::where('billing_cycle_id', $currentCycle->id)
            ->whereIn('this_month_code', $this->technicalCodes)
            ->select('csa_id', DB::raw('COUNT(*) as total_technical'))
            ->groupBy('csa_id')
            ->orderByDesc('total_technical')
            ->get()
            ->map(fn($row) => $this->withCsaName($row));
    }

    /**
     * Get CSAs with most flagged accounts and readings.
     *
     * @param BillingCycle $currentCycle
     * @return Collection
     */
    private function getCsasWithMostFlags(BillingCycle $currentCycle): Collection
    {
        $flaggedAccountsByCsa = CustomerAccount::join('csa_assignments', 'csa_assignments.zone_id', '=', 'customer_accounts.zone_id')
            ->where('csa_assignments.billing_cycle_id', $currentCycle->id)
            ->whereHas('flags', fn($q) => $q->active())
            ->select('csa_assignments.csa_id', DB::raw('COUNT(DISTINCT customer_accounts.id) as flagged_accounts'))
            ->groupBy('csa_assignments.csa_id')
            ->pluck('flagged_accounts', 'csa_id');

        $flaggedReadingsByCsa = Reading::where('billing_cycle_id', $currentCycle->id)
            ->whereHas('flags', fn($q) => $q->active())
            ->select('csa_id', DB::raw('COUNT(DISTINCT readings.id) as flagged_readings'))
            ->groupBy('csa_id')
            ->pluck('flagged_readings', 'csa_id');

        return $flaggedAccountsByCsa->keys()
            ->merge($flaggedReadingsByCsa->keys())
            ->unique()
            ->map(fn($csaId) => $this->withCsaName((object) [
                'csa_id' => $csaId,
                'flagged_accounts' => $flaggedAccountsByCsa[$csaId] ?? 0,
                'flagged_readings' => $flaggedReadingsByCsa[$csaId] ?? 0,
            ]))
            ->sortByDesc(fn($row) => $row->flagged_accounts + $row->flagged_readings)
            ->values();
    }

    /**
     * Get field issues reported by CSA.
     *
     * @param BillingCycle $currentCycle
     * @return Collection
     */
    private function getCsaFieldIssues(BillingCycle $currentCycle): Collection
    {
        return CustomerAccountIssue::where('created_at', '>=', $currentCycle->start_date)
            ->get()
            ->groupBy('reported_by')
            ->map(fn($group, $csaId) => $this->withCsaName((object) [
                'csa_id' => $csaId,
                'total_issues' => $group->count(),
            ]))
            ->sortByDesc('total_issues')
            ->values();
    }

    /**
     * Identify underperforming CSAs based on completion rate.
     * 
     * A CSA is considered "below average" if its completion rate
     * is below the overall average completion rate. This is more fair
     * than comparing raw reading counts because it accounts for CSAs
     * with different numbers of assigned accounts.
     *
     * @param Collection $assignmentCounts
     * @param Collection $readCounts
     * @return Collection
     */
  /**
 * Identify underperforming CSAs based on completion rate.
 *
 * @param Collection $assignmentCounts
 * @param Collection $readCounts
 * @return Collection
 */
private function getBelowAverageCsas(
    Collection $assignmentCounts,
    Collection $readCounts
): Collection {
    // Calculate completion rate for each CSA
    $csaCompletionRates = $assignmentCounts->map(function ($assigned, $csaId) use ($readCounts) {
        $read = $readCounts[$csaId] ?? 0;
        $user = User::find($csaId);
        
        return (object) [
            'csa_id' => $csaId,
            'csa_name' => $user?->name ?? 'Unknown',
            'total_readings' => $read,
            'assigned' => $assigned,
            'read' => $read,
            'completion_rate' => $assigned > 0 
                ? round(($read / $assigned) * 100, 2) 
                : 0,
        ];
    })->values();

    // Calculate average completion rate across all CSAs
    $averageCompletionRate = $csaCompletionRates->avg('completion_rate') ?? 0;

    // Filter CSAs below average, sort by completion rate (ascending - worst first)
    $belowAverage = $csaCompletionRates
        ->filter(fn($row) => $row->completion_rate < $averageCompletionRate)
        ->sortBy('completion_rate')  //  Ensures worst performers first
        ->take(10)
        ->values();  //  Reset keys

    // Now assign ranks based on sorted order
    $rankedResults = $belowAverage->map(function ($row, $index) use ($averageCompletionRate) {
        $row->rank = $index + 1;  //  Rank 1 = worst performer
        $row->name = $row->csa_name;
        $row->gap = round($averageCompletionRate - $row->completion_rate, 2);
        $row->percent_of_average = $averageCompletionRate > 0
            ? round(($row->completion_rate / $averageCompletionRate) * 100)
            : 0;

        return $row;
    });

    return $rankedResults;
}

    /**
     * Calculate the average completion rate across CSAs.
     * 
     * This calculates the mean of all CSA completion rates,
     * providing a fair benchmark for identifying underperformers.
     *
     * @param Collection $assignmentCounts
     * @param Collection $readCounts
     * @return float
     */
    private function calculateCsaAverageCompletionRate(Collection $assignmentCounts, Collection $readCounts): float
    {
        $completionRates = $assignmentCounts->map(function ($assigned, $csaId) use ($readCounts) {
            $read = $readCounts[$csaId] ?? 0;
            return $assigned > 0 ? round(($read / $assigned) * 100, 2) : 0;
        });
        
        return round($completionRates->avg() ?? 0, 2);
    }

    /**
     * Get recent uploads (last 10 synced readings).
     * 
     * This query is shared between district and CSA views to avoid
     * duplicate queries. The same base data is used with different
     * display formats.
     *
     * @param BillingCycle $currentCycle
     * @return Collection
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
     * Get upload activity for the last 14 days.
     * 
     * Creates a rolling 14-day window of reading uploads to visualize
     * upload trends over time. This is independent of the billing cycle
     * and provides a consistent view across both district and CSA pages.
     *
     * @param BillingCycle $currentCycle
     * @return array
     */
    private function uploadActivity(BillingCycle $currentCycle): array
    {
        $days = collect(range(13, 0))->map(fn($i) => now()->subDays($i)->toDateString());

        $counts = Reading::where('billing_cycle_id', $currentCycle->id)
            ->whereNotNull('synced_at')
            ->whereDate('synced_at', '>=', now()->subDays(13)->startOfDay())
            ->select(DB::raw('DATE(synced_at) as upload_date'), DB::raw('COUNT(*) as total'))
            ->groupBy('upload_date')
            ->pluck('total', 'upload_date');

        return [
            'labels' => $days->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))->values()->toArray(),
            'data' => $days->map(fn($d) => $counts[$d] ?? 0)->values()->toArray(),
        ];
    }

    /**
     * Attach CSA name to any object containing a csa_id.
     * 
     * This helper method centralizes the user lookup logic and provides
     * consistent naming across all CSA-related metrics.
     *
     * @param object $row
     * @return object
     */
    private function withCsaName(object $row): object
    {
        $user = User::find($row->csa_id);
        $row->csa_name = $user?->name ?? 'Unknown';

        return $row;
    }

    /**
     * Get empty district data structure.
     * 
     * Provides a consistent empty state when no active billing cycle exists.
     *
     * @return array
     */
    private function emptyDistrictData(): array
    {
        return [
            'topByReadings' => collect(),
            'nearCompletion' => collect(),
            'mostTechnical' => collect(),
            'mostFlagged' => collect(),
            'recentUploads' => collect(),
            'fieldIssues' => collect(),
            'belowAverage' => collect(),
            'averageCompletionRate' => 0,
        ];
    }

    /**
     * Get empty CSA data structure.
     * 
     * Provides a consistent empty state when no active billing cycle exists.
     *
     * @return array
     */
    private function emptyCsaData(): array
    {
        return [
            'topByReadings' => collect(),
            'nearCompletion' => collect(),
            'mostTechnical' => collect(),
            'mostFlagged' => collect(),
            'recentUploads' => collect(),
            'fieldIssues' => collect(),
            'belowAverage' => collect(),
            'averageCompletionRate' => 0,
        ];
    }
}