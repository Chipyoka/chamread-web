<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingCycle;
use App\Models\MeterReadingCode;
use App\Models\Unmetered;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UnmeteredController extends Controller
{
    /**
     * Zone IDs the current user may see. null = unrestricted.
     *
     * Same convention as Admin\ReadingsController and DashboardController:
     * a SUPERVISOR is limited to the zones of their district.
     */
    private function scopedZoneIds(): ?array
    {
        $user = auth()->user();

        if (! $user || $user->role !== 'SUPERVISOR' || ! $user->district) {
            return null;
        }

        return Zone::whereRaw('LOWER(district) = ?', [strtolower($user->district->name)])
            ->pluck('id')
            ->toArray();
    }

    /**
     * The unmetered query for a cycle with the district scope and every
     * request filter applied.
     *
     * index() and exportCsv() both build on this, so the export always
     * contains exactly what the list is showing for the same query string.
     *
     * Query params: duration (today|this_week), search (account number or
     * CSA name), zone, district, status (active|inactive), code (a
     * meter_reading_codes id).
     */
    private function filteredQuery(Request $request, BillingCycle $cycle, ?array $zoneIds): Builder
    {
        $query = Unmetered::with(['account.zone', 'csa', 'billingCycle', 'code'])
            ->where('billing_cycle_id', $cycle->id);

        // Mandatory district scope, applied before any filter so a supervisor
        // cannot reach another district by editing the query string.
        if ($zoneIds !== null) {
            $query->whereHas('account', fn ($q) => $q->whereIn('zone_id', $zoneIds));
        }

        if ($request->duration === 'today') {
            $query->whereDate('captured_at', today());
        } elseif ($request->duration === 'this_week') {
            $query->whereBetween('captured_at', [now()->startOfWeek(), now()->endOfWeek()]);
        }

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';

            $query->where(function ($q) use ($term) {
                $q->whereHas('account', fn ($a) => $a->where('account_number', 'like', $term))
                    ->orWhereHas('csa', fn ($c) => $c->where('name', 'like', $term));
            });
        }

        if ($request->filled('zone')) {
            $query->whereHas('account', fn ($q) => $q->where('zone_id', $request->zone));
        }

        if ($request->filled('district')) {
            $query->whereHas('account.zone', fn ($q) => $q->where('district', $request->district));
        }

        if (in_array($request->status, Unmetered::STATUSES, true)) {
            $query->where('connection_status', $request->status);
        }

        if ($request->filled('code')) {
            $query->where('meter_reading_code', $request->code);
        }

        return $query->orderByDesc('captured_at');
    }

    /**
     * Unmetered records for the active billing cycle, with filters.
     */
    public function index(Request $request)
    {
        $zoneIds = $this->scopedZoneIds();

        $zones = Zone::when($zoneIds !== null, fn ($q) => $q->whereIn('id', $zoneIds))
            ->orderBy('name')
            ->get();

        $districts = $zones->pluck('district')->filter()->unique()->sort()->values();

        // Options for the code filter dropdown.
        $codes = MeterReadingCode::whereIn('code', Unmetered::ALLOWED_CODES)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $currentCycle = BillingCycle::where('status', 'active')->first();

        $unmetered = $currentCycle
            ? $this->filteredQuery($request, $currentCycle, $zoneIds)->paginate(15)->withQueryString()
            : collect();

        return view('readings.unmetered.index', compact('unmetered', 'zones', 'districts', 'codes', 'currentCycle'));
    }

    /**
     * Show one unmetered record with its account, CSA and photo.
     */
    public function show(Unmetered $unmetered)
    {
        $zoneIds = $this->scopedZoneIds();

        $unmetered->load(['account.zone', 'csa', 'billingCycle', 'code']);

        // Route-model binding would otherwise let a supervisor open a record
        // outside their district by typing its id.
        if ($zoneIds !== null && ! ($unmetered->account && in_array($unmetered->account->zone_id, $zoneIds, true))) {
            abort(403, 'This record is outside your assigned district.');
        }

        return view('readings.unmetered.show', compact('unmetered'));
    }

    /**
     * Stream the currently filtered list as a CSV download.
     *
     * Takes the same query string as index(), so linking to this route with
     * the current query exports what is on screen (all pages, not just the
     * visible one). Rows are streamed in chunks to keep memory flat.
     */
    public function exportCsv(Request $request)
    {
        $zoneIds = $this->scopedZoneIds();

        $currentCycle = BillingCycle::where('status', 'active')->first();

        if (! $currentCycle) {
            return back()->with('error', 'No active billing cycle found.');
        }

        $query = $this->filteredQuery($request, $currentCycle, $zoneIds);

        // e.g. unmetered_today_october-2026_20261110_162100.csv
        $filter = in_array($request->duration, ['today', 'this_week'], true) ? $request->duration : 'all';

        $filename = sprintf(
            'unmetered_%s_%s_%s.csv',
            $filter,
            Str::slug($currentCycle->name),
            now()->format('Ymd_His')
        );

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($query) {

            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Account Number',
                'Customer Name',
                'Category',
                'Zone',
                'District',
                'Code',
                'Code Description',
                'Comment',
                'CSA',
                'Latitude',
                'Longitude',
                'Captured At',
                'Synced At',
                'Billing Cycle',
            ]);

            // lazy() pages through the results and keeps the eager loads.
            foreach ($query->lazy(500) as $row) {
                fputcsv($handle, array_map([$this, 'csvSafe'], [
                    $row->account?->account_number,
                    $row->account?->customer_name,
                    $row->account?->customer_category,
                    $row->account?->zone?->name,
                    $row->account?->zone?->district,
                    $row->code?->code,
                    $row->code?->name,
                    $row->comment,
                    $row->csa?->name,
                    $row->latitude,
                    $row->longitude,
                    $row->captured_at?->format('Y-m-d H:i:s'),
                    $row->synced_at?->format('Y-m-d H:i:s'),
                    $row->billingCycle?->name,
                ]));
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Neutralise spreadsheet formulas in exported text.
     *
     * Comments are typed by readers in the field; a value starting with
     * = + - or @ would be executed as a formula when the file opens in
     * Excel. Prefixing an apostrophe makes it plain text. Numbers are left
     * alone so negative coordinates stay numeric.
     */
    private function csvSafe($value)
    {
        if (is_string($value) && ! is_numeric($value) && preg_match('/^[=+\-@\t\r]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }
}