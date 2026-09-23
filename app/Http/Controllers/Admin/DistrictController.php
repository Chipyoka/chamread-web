<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Zone;
use App\Models\Reading;
use App\Models\CustomerAccount;
use App\Models\BillingCycle;
use App\Models\CustomerAccountIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DistrictController extends Controller
{
    /**
     * Technical reading codes indicating meter issues.
     * Mirrors PerformanceController — kept in sync manually.
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
     * Zone IDs belonging to a district.
     * districts.name matches zones.district (string, case-insensitive, no FK),
     * same convention as CsaController::scopedZoneIds().
     *
     * @return array<int>
     */
    private function zoneIdsFor(District $district): array
    {
        return Zone::whereRaw('LOWER(district) = ?', [strtolower($district->name)])
            ->pluck('id')
            ->toArray();
    }

    /**
     * Distinct account IDs belonging to a district (through zones).
     * Cached per-request to avoid repeating the same subquery.
     */
    private function accountIdsFor(District $district): \Illuminate\Support\Collection
    {
        static $cache = [];

        if (!isset($cache[$district->id])) {
            $zoneIds = $this->zoneIdsFor($district);

            $cache[$district->id] = CustomerAccount::whereIn('zone_id', $zoneIds)
                ->pluck('id');
        }

        return $cache[$district->id];
    }

    /**
     * List all districts with light rollup stats for the current cycle.
     */
    public function index(Request $request)
    {
        $currentCycle = BillingCycle::where('status', 'active')->first();

        $query = District::query()
            ->withCount([
                'users as team_count' => fn($q) => $q->where('district_user.status', 'active'),
            ])
            ->orderByDesc(
                CustomerAccount::query()
                    ->selectRaw('COUNT(*)')
                    ->join('zones', 'zones.id', '=', 'customer_accounts.zone_id')
                    ->whereRaw('LOWER(zones.district) = LOWER(districts.name)')
            )
            ->orderBy('name');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('short_code', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $districts = $query->paginate(15)->withQueryString();

        // Rollups for the page only (≤15 rows).
        if ($currentCycle) {
            foreach ($districts as $district) {
                $accountIds = $this->accountIdsFor($district);

                $district->accounts_total = $accountIds->count();

                $district->accounts_read = $accountIds->isEmpty()
                    ? 0
                    : Reading::where('billing_cycle_id', $currentCycle->id)
                        ->whereIn('account_id', $accountIds)
                        ->where('status', 'read')
                        ->distinct('account_id')
                        ->count('account_id');

                $district->completion_rate = $district->accounts_total > 0
                    ? round(($district->accounts_read / $district->accounts_total) * 100, 2)
                    : 0;
            }
        }

        return view('readings.district.index', compact('districts', 'currentCycle'));
    }

    /**
     * Show a single district dashboard (read-only).
     */
    public function show(District $district)
    {
        $currentCycle = BillingCycle::where('status', 'active')->first();

        $accountIds = $this->accountIdsFor($district);

        // --- Team (pivot: role + status) ---
        $team = $district->users()
            ->wherePivot('status', 'active')
            ->orderBy('name')
            ->get();

        // --- Account totals (accounts scoped to district via zones) ---
        $accountsTotal = $accountIds->count();

        $accountsRead = 0;
        $accountsPending = $accountsTotal;

        if ($currentCycle && $accountsTotal > 0) {
            $accountsRead = Reading::where('billing_cycle_id', $currentCycle->id)
                ->whereIn('account_id', $accountIds)
                ->where('status', 'read')
                ->distinct('account_id')
                ->count('account_id');

            $accountsPending = max($accountsTotal - $accountsRead, 0);
        }

        $completionRate = $accountsTotal > 0
            ? round(($accountsRead / $accountsTotal) * 100, 2)
            : 0;

        // --- Technical cases (readings for accounts in district, current cycle) ---
        $technicalCases = 0;
        if ($currentCycle && $accountsTotal > 0) {
            $technicalCases = Reading::where('billing_cycle_id', $currentCycle->id)
                ->whereIn('account_id', $accountIds)
                ->whereIn('this_month_code', $this->technicalCodes)
                ->count();
        }

        // --- Flagged accounts (no cycle scope; flags are account-level) ---
        $flaggedAccounts = $accountIds->isEmpty()
            ? 0
            : CustomerAccount::whereIn('id', $accountIds)
                ->whereHas('flags', fn($q) => $q->active())
                ->count();

        // --- Flagged readings (current cycle only) ---
        $flaggedReadings = 0;
        if ($currentCycle && $accountsTotal > 0) {
            $flaggedReadings = Reading::where('billing_cycle_id', $currentCycle->id)
                ->whereIn('account_id', $accountIds)
                ->whereHas('flags', fn($q) => $q->active())
                ->count();
        }

        // --- Field issues (reporter's active zone falls in this district) ---
        $fieldIssues = 0;
        if ($currentCycle) {
            $fieldIssues = CustomerAccountIssue::where('created_at', '>=', $currentCycle->start_date)
                ->whereHas('reporter.activeAssignment.zone', function ($q) use ($district) {
                    $q->whereRaw('LOWER(district) = ?', [strtolower($district->name)]);
                })
                ->count();
        }

        return view('readings.district.show', compact(
            'district',
            'currentCycle',
            'team',
            'accountsTotal',
            'accountsRead',
            'accountsPending',
            'completionRate',
            'technicalCases',
            'flaggedAccounts',
            'flaggedReadings',
            'fieldIssues',
        ));
    }

    /**
     * Export: accounts not read in the current cycle (CSV).
     * Phone emitted as Excel-safe text.
     */
    public function exportPending(District $district)
    {
        $currentCycle = BillingCycle::where('status', 'active')->first();
        abort_unless($currentCycle, 404, 'No active billing cycle.');

        $zoneIds = $this->zoneIdsFor($district);
        abort_if(empty($zoneIds), 404, 'No zones mapped to this district.');

        $filename = Str::slug($district->name, '_') . '_pending_' . now()->format('Ymd_His') . '.csv';

        $columns = [
            'ACCOUNT_NUMBER',
            'CUSTOMER_NAME',
            'ADDRESS',
            'PHONE',
            'METER_NUMBER',
            'CUSTOMER_CATEGORY',
            'ZONE',
        ];

        return $this->streamCsv($filename, $columns, function ($handle) use ($zoneIds, $currentCycle) {
            CustomerAccount::query()
                ->whereIn('customer_accounts.zone_id', $zoneIds)
                ->leftJoin('readings', function ($join) use ($currentCycle) {
                    $join->on('readings.account_id', '=', 'customer_accounts.id')
                        ->where('readings.billing_cycle_id', '=', $currentCycle->id);
                })
                ->select('customer_accounts.*')
                ->selectRaw("COALESCE(readings.status, 'NOT_READ') as read_status")
                ->where(function ($q) {
                    $q->whereNull('readings.status')
                        ->orWhere('readings.status', 'NOT_READ');
                })
                ->orderBy('customer_accounts.id')
                ->chunkById(500, function ($accounts) use ($handle) {
                    foreach ($accounts as $account) {
                        fputcsv($handle, [
                            $account->account_number,
                            $account->customer_name,
                            $account->address,
                            $this->asText($account->phone),
                            $account->meter_number,
                            $account->customer_category,
                            $account->zone->name ?? 'N/A',
                        ]);
                    }
                }, 'customer_accounts.id', 'id');
        });
    }

    /**
     * Export: field issues reported within the district (CSV).
     *
     * NOTE: CustomerAccountIssue has no `account` relation — an issue may
     * reference an account not present in our records. We emit whatever raw
     * account identifier column the model carries (account_number / account_id
     * / reference). Adjust the column name below if it differs.
     */
    public function exportFieldIssues(District $district)
    {
        $currentCycle = BillingCycle::where('status', 'active')->first();
        abort_unless($currentCycle, 404, 'No active billing cycle.');

        $filename = Str::slug($district->name, '_') . '_field_issues_' . now()->format('Ymd_His') . '.csv';

        $columns = [
            'REPORTED_AT',
            'ACCOUNT_REFERENCE',
            'ISSUE_TYPE',
            'DESCRIPTION',
            'REPORTED_BY',
            'REPORTER_PHONE',
        ];

        return $this->streamCsv($filename, $columns, function ($handle) use ($district, $currentCycle) {
            CustomerAccountIssue::with('reporter')
                ->where('created_at', '>=', $currentCycle->start_date)
                ->whereHas('reporter.activeAssignment.zone', function ($q) use ($district) {
                    $q->whereRaw('LOWER(district) = ?', [strtolower($district->name)]);
                })
                ->orderBy('id')
                ->chunkById(500, function ($issues) use ($handle) {
                    foreach ($issues as $issue) {
                        fputcsv($handle, [
                            optional($issue->created_at)->format('Y-m-d H:i:s'),
                            $this->issueAccountReference($issue),
                            $issue->issue_type ?? 'N/A',
                            $issue->description ?? '',
                            $issue->reporter->name ?? 'Unknown',
                            $this->asText($issue->reporter->phone ?? null),
                        ]);
                    }
                });
        });
    }

    /**
     * Export: flagged accounts in the district (CSV).
     */
    public function exportFlaggedAccounts(District $district)
    {
        $zoneIds = $this->zoneIdsFor($district);
        abort_if(empty($zoneIds), 404, 'No zones mapped to this district.');

        $filename = Str::slug($district->name, '_') . '_flagged_accounts_' . now()->format('Ymd_His') . '.csv';

        $columns = [
            'ACCOUNT_NUMBER',
            'CUSTOMER_NAME',
            'ADDRESS',
            'PHONE',
            'METER_NUMBER',
            'CUSTOMER_CATEGORY',
            'ZONE',
            'ACTIVE_FLAGS',
        ];

        return $this->streamCsv($filename, $columns, function ($handle) use ($zoneIds) {
            CustomerAccount::query()
                ->whereIn('customer_accounts.zone_id', $zoneIds)
                ->whereHas('flags', fn($q) => $q->active())
                ->withCount(['flags as active_flags_count' => fn($q) => $q->active()])
                ->with('zone')
                ->orderBy('customer_accounts.id')
                ->chunkById(500, function ($accounts) use ($handle) {
                    foreach ($accounts as $account) {
                        fputcsv($handle, [
                            $account->account_number,
                            $account->customer_name,
                            $account->address,
                            $this->asText($account->phone),
                            $account->meter_number,
                            $account->customer_category,
                            $account->zone->name ?? 'N/A',
                            $account->active_flags_count,
                        ]);
                    }
                }, 'customer_accounts.id', 'id');
        });
    }

    /**
     * Export: flagged readings in the current cycle (CSV).
     * Scoped to the district via account → zone.
     */
    public function exportFlaggedReadings(District $district)
    {
        $currentCycle = BillingCycle::where('status', 'active')->first();
        abort_unless($currentCycle, 404, 'No active billing cycle.');

        $accountIds = $this->accountIdsFor($district);
        abort_if($accountIds->isEmpty(), 404, 'No accounts mapped to this district.');

        $filename = Str::slug($district->name, '_') . '_flagged_readings_' . now()->format('Ymd_His') . '.csv';

        $columns = [
            'ACCOUNT_NUMBER',
            'CUSTOMER_NAME',
            'ZONE',
            'CURRENT_READING',
            'THIS_MONTH_CODE',
            'STATUS',
            'CSA',
            'SYNCED_AT',
        ];

        return $this->streamCsv($filename, $columns, function ($handle) use ($accountIds, $currentCycle) {
            Reading::with(['account.zone', 'csa'])
                ->where('billing_cycle_id', $currentCycle->id)
                ->whereIn('account_id', $accountIds)
                ->whereHas('flags', fn($q) => $q->active())
                ->orderBy('id')
                ->chunkById(500, function ($readings) use ($handle) {
                    foreach ($readings as $reading) {
                        fputcsv($handle, [
                            $reading->account->account_number ?? 'N/A',
                            $reading->account->customer_name ?? 'N/A',
                            $reading->account->zone->name ?? 'N/A',
                            $reading->current_reading ?? '',
                            $reading->this_month_code ?? '',
                            $reading->status ?? '',
                            $reading->csa->name ?? 'Unknown',
                            optional($reading->synced_at)->format('Y-m-d H:i:s'),
                        ]);
                    }
                });
        });
    }

    /**
     * Stream a CSV response with standard headers + UTF-8 BOM.
     */
    private function streamCsv(string $filename, array $columns, callable $writer)
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($columns, $writer) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            fputcsv($handle, $columns);

            $writer($handle);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Force a value to be treated as text by Excel (preserve leading zeros).
     */
    private function asText(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return '="' . str_replace('"', '""', $value) . '"';
    }

    /**
     * Best-effort account reference for a field issue.
     * The issue may point at an account outside our records, so we fall back
     * through the plausible raw columns before giving up.
     */
    private function issueAccountReference(CustomerAccountIssue $issue): string
    {
        foreach (['account_number', 'account_no', 'account_ref', 'reference', 'account_id'] as $col) {
            if (!empty($issue->{$col})) {
                return (string) $issue->{$col};
            }
        }

        return 'N/A';
    }
}