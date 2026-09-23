<?php

namespace App\Http\Controllers\Admin;


use App\Exports\CustomerAccountIssuesExport;
use App\Http\Controllers\Controller;
use App\Models\CustomerAccountIssue;
use App\Models\Zone;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CustomerAccountIssueController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | District Scoping Helper
    |--------------------------------------------------------------------------
    |
    | Same convention as the other Admin controllers: a SUPERVISOR is
    | restricted to the zone(s) under auth()->user()->district (case-
    | insensitive match against zones.district). Every other role gets
    | null => unrestricted.
    |
    | Unlike CustomerAccount-based scoping, CustomerAccountIssue carries its
    | own zone_id column directly (it can be filed against an account that
    | doesn't exist in the system yet, so it can't rely on an account
    | relation) — scoping here is therefore a plain whereIn('zone_id', ...),
    | no whereHas() indirection needed.
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
     * Whether a given CustomerAccountIssue falls inside the current user's scope.
     * Always true when unrestricted (null scope).
     */
    private function isIssueInScope(CustomerAccountIssue $issue, ?array $zoneIds): bool
    {
        if ($zoneIds === null) {
            return true;
        }

        return in_array($issue->zone_id, $zoneIds, true);
    }

    /**
     * Display a listing of customer account issues.
     */
    public function index(Request $request)
    {
        $zoneIds = $this->scopedZoneIds();

        $query = CustomerAccountIssue::query()
            ->with([
                'zone',
                'reporter',
                'resolver',
            ])
            // Mandatory district scope — applied before any of the request
            // filters below, so a supervisor tampering with the zone query
            // param can only ever narrow within their own district, never
            // see another one.
            ->when($zoneIds !== null, fn($q) => $q->whereIn('zone_id', $zoneIds));

        // Search
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('account_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('meter_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('issue', 'like', "%{$search}%");
            });
        }

        // Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Zone (combined with the mandatory scope above — requesting a zone
        // outside the district simply yields zero results)
        if ($request->filled('zone')) {
            $query->where('zone_id', $request->zone);
        }

        // Reporter
        if ($request->filled('reported_by')) {
            $query->where('reported_by', $request->reported_by);
        }

        // Date range
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // Sort newest first
        $query->latest();

        $issues = $query
            ->paginate(20)
            ->withQueryString();

        return view('readings.issue.index', [
            'issues' => $issues,
            // Zone filter dropdown — scoped, so a supervisor is never even
            // offered zones outside their district.
            'zones' => Zone::when($zoneIds !== null, fn($q) => $q->whereIn('id', $zoneIds))
                ->orderBy('name')
                ->get(),
            'filters' => $request->only([
                'search',
                'status',
                'zone',
                'reported_by',
                'from',
                'to',
            ]),
        ]);
    }

    /**
     * Export issues to Excel.
     */
    public function export()
    {
        $zoneIds = $this->scopedZoneIds();

        // NOTE: CustomerAccountIssuesExport currently takes no constructor
        // arguments at all — it has no way to be told "only this district's
        // issues" the way this refactor scopes everything else. Rather than
        // hand a restricted supervisor a spreadsheet of every district's
        // issues, this blocks the export until the export class is updated
        // to accept a $zoneIds (or $district) parameter and apply the same
        // whereIn('zone_id', ...) filter used in index() above.
        if ($zoneIds !== null) {
            abort(403, 'Exporting issues is not yet available for your account — please filter and review issues on this page instead.');
        }

        return Excel::download(
            new CustomerAccountIssuesExport(),
            'customer-account-issues.xlsx'
        );
    }

    /**
     * Update issue status.
     */
    public function updateStatus(Request $request, CustomerAccountIssue $customerAccountIssue)
    {
        $zoneIds = $this->scopedZoneIds();

        if (! $this->isIssueInScope($customerAccountIssue, $zoneIds)) {
            abort(403, 'This issue is outside your assigned district.');
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        $customerAccountIssue->update([
            'status' => $validated['status'],
            'resolved_by' => $validated['status'] === 'completed'
                ? auth()->id()
                : null,
            'resolved_at' => $validated['status'] === 'completed'
                ? now()
                : null,
        ]);

        return back()->with('success', 'Issue status updated successfully.');
    }
}