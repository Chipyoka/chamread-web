<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Zone;
use App\Models\Reading;
use App\Models\Dma;
use App\Models\BillingCycle;
use App\Models\CsaAssignment;
use App\Models\ReadingReread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Models\ReadingResolve;
use App\Exports\MeterReadingsExport;
use Maatwebsite\Excel\Facades\Excel;

use App\Services\AuditLogService;

class ReadingsController extends Controller
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
    | Same convention as DashboardController: a SUPERVISOR is restricted to
    | the zone(s) under auth()->user()->district (case-insensitive match
    | against zones.district). Every other role gets null => unrestricted.
    |
    | This controller deals with individual Reading records users can act on
    | directly via route-model binding (show/export/reread/resolve), so on
    | top of filtering lists we also gate those single-record actions with
    | isReadingInScope() to stop a supervisor acting on a reading outside
    | their district just by guessing/typing its ID/URL.
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
     * Whether a given Reading falls inside the current user's scope.
     * Always true when unrestricted (null scope).
     */
    private function isReadingInScope(Reading $reading, ?array $zoneIds): bool
    {
        if ($zoneIds === null) {
            return true;
        }

        // Reading has no zone_id of its own; it is resolved via its account.
        return $reading->account && in_array($reading->account->zone_id, $zoneIds, true);
    }


    /**
     * Load initial page
     */
    public function index(Request $request)
    {
        $zoneIds = $this->scopedZoneIds();

        $currentCycle = BillingCycle::where('status', 'active')->first();

        if (!$currentCycle) {
            return view('readings.reading.index', [
                'readings' => collect(),
                'zones' => Zone::when($zoneIds !== null, fn($q) => $q->whereIn('id', $zoneIds))
                    ->orderBy('name')
                    ->get(),
                'districts' => collect(),
            ]);
        }

        $query = Reading::with(['account', 'account.zone', 'csa', 'billingCycle'])
            ->where('billing_cycle_id', $currentCycle->id);

        // Mandatory district scope — applied regardless of any filters below,
        // so a supervisor can never see readings outside their district
        // (including by tampering with the zone/district query params).
        if ($zoneIds !== null) {
            $query->whereHas('account', function ($accountQuery) use ($zoneIds) {
                $accountQuery->whereIn('zone_id', $zoneIds);
            });
        }

        // Apply duration filter
        if ($request->duration === 'today') {
            $query->whereDate('reading_time', today());
        } elseif ($request->duration === 'this_week') {
            $query->whereBetween('reading_time', [now()->startOfWeek(), now()->endOfWeek()]);
        }

        // Apply search filter by account number
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('account', function ($accountQuery) use ($request) {
                    $accountQuery->where('account_number', 'like', '%' . $request->search . '%');
                })->orWhereHas('csa', function ($csaQuery) use ($request) {
                    $csaQuery->where('name', 'like', '%' . $request->search . '%');
                });
            });
        }

        // Apply zone filter (combined with the mandatory scope above, so a
        // supervisor requesting a zone outside their district simply gets
        // zero results rather than leaking another district's readings).
        if ($request->filled('zone')) {
            $query->whereHas('account', function ($q) use ($request) {
                $q->where('zone_id', $request->zone);
            });
        }

        // Apply district filter (from zone relation) — likewise combined
        // with the mandatory scope above.
        if ($request->filled('district')) {
            $query->whereHas('account.zone', function ($q) use ($request) {
                $q->where('district', $request->district);
            });
        }

        $readings = $query->orderBy('reading_time', 'desc')->paginate(15)->withQueryString();

        // Get zones for filter dropdown — scoped, so a supervisor is never
        // even offered zones outside their district.
        $zones = Zone::when($zoneIds !== null, fn($q) => $q->whereIn('id', $zoneIds))
            ->orderBy('name')
            ->get();

        // Get districts from zones for filter dropdown — likewise scoped
        // (a supervisor will only ever see their own district here).
        $districts = Zone::whereNotNull('district')
            ->when($zoneIds !== null, fn($q) => $q->whereIn('id', $zoneIds))
            ->distinct()
            ->pluck('district')
            ->sort()
            ->values();

        return view('readings.reading.index', compact(
            'readings',
            'zones',
            'districts',
            'currentCycle'
        ));
    }


    /**
     * show reading with associated account
     */
    public function show(Reading $reading)
    {
        $zoneIds = $this->scopedZoneIds();

        if (! $this->isReadingInScope($reading, $zoneIds)) {
            abort(403, 'This reading is outside your assigned district.');
        }

        return view('readings.reading.show', compact('reading'));
    }

    /**
     * Export Reading as pdf
     */
    public function export(Reading $reading)
    {
        $zoneIds = $this->scopedZoneIds();

        if (! $this->isReadingInScope($reading, $zoneIds)) {
            abort(403, 'This reading is outside your assigned district.');
        }

        $consumption = (($reading->current_reading ?? 0) - ($reading->previous_reading ?? 0));

        $pdf = Pdf::loadView('readings.reading.pdf', [
            'reading' => $reading,
            'consumption' => $consumption,
            'date' => now()->format('Y-m-d H:i:s'),
            'user' => auth()->user(),
        ]);

        $fileName =
            'READING-' .
            $reading->account?->account_number .
            '-' .
            now()->format('Y-m-d_H-i-s') .
            '.pdf';

        // Audit log
        $this->auditLog->log('EXPORT', 'Reading report exported', [
            'reading_id' => $reading->id,
            'account_number' => $reading->account?->account_number,
            'file_name' => $fileName,
            'performed_by' => auth()->id(),
        ]);

        return $pdf->download($fileName);
    }

    /**
     * Request re-reading
     */

    public function requestReread(Request $request, Reading $reading)
    {
        $zoneIds = $this->scopedZoneIds();

        if (! $this->isReadingInScope($reading, $zoneIds)) {
            abort(403, 'This reading is outside your assigned district.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        // Prevent duplicate pending requests
        if ($reading->rereads()->where('status', 'pending')->exists()) {
            return back()->with(
                'error',
                'A request already exists.'
            );
        }

        ReadingReread::create([
            'reading_id'       => $reading->id,
            'supervisor_id'    => auth()->id(),
            'billing_cycle_id' => $reading->billing_cycle_id,
            'old_value'        => $reading->current_reading,
            'reason'           => $validated['reason'],
            'status'           => 'pending',
        ]);

        return back()->with(
            'success',
            'Re-read instruction created.'
        );
    }

    /**
     * Mark re-reading complete
     */

    public function completeReread(Reading $reading)
    {
        $zoneIds = $this->scopedZoneIds();

        if (! $this->isReadingInScope($reading, $zoneIds)) {
            abort(403, 'This reading is outside your assigned district.');
        }

        $reread = $reading->rereads()
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (! $reread) {
            return back()->with(
                'error',
                'No pending re-read request was found for this reading.'
            );
        }

        $reread->update([
            'status' => 'completed',
        ]);

        return back()->with(
            'success',
            'Re-read marked as completed.'
        );
    }




    public function resolveReading(Request $request, Reading $reading)
    {
        $user = auth()->user();
        $zoneIds = $this->scopedZoneIds();

        if (! $this->isReadingInScope($reading, $zoneIds)) {
            abort(403, 'This reading is outside your assigned district.');
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate resolution
        |--------------------------------------------------------------------------
        */

        if ($reading->resolves()->exists()) {

            return back()->with(
                'error',
                'This reading has already been resolved.'
            );

        }
        /*
        |--------------------------------------------------------------------------
        | Validate billing cycle
        |--------------------------------------------------------------------------
        */

        if (! $reading->billing_cycle_id) {

            return back()->with(
                'error',
                'This reading has no billing cycle assigned.'
            );

        }
        /*
        |--------------------------------------------------------------------------
        | Validate technical issue
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
        if (! in_array($reading->this_month_code, $technicalCodes)) {

            return back()->with(
                'error',
                'This reading does not require technical resolution.'
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Create resolve record
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($reading, $user) {

            ReadingResolve::create([

                'reading_id' => $reading->id,

                'resolved_by' => $user->id,

                'billing_cycle_id' => $reading->billing_cycle_id,

            ]);

        });

        return back()->with(
            'success',
            'Reading resolved successfully.'
        );
    }


    public function exportExcel(Request $request)
    {
        $zoneIds = $this->scopedZoneIds();

        $currentCycle = BillingCycle::where('status', 'active')->first();

        if (!$currentCycle) {
            return back()->with('error', 'No active billing cycle found.');
        }

        $duration = $request->filled('duration') ? $request->duration : null;
        $search = $request->filled('search') ? $request->search : null;
        $zoneId = $request->filled('zone') ? $request->zone : null;
        $district = $request->filled('district') ? $request->district : null;

        // NOTE: MeterReadingsExport currently takes a single $zoneId / $district
        // value, not an array of scoped zone IDs — this refactor works within
        // that existing signature rather than changing the export class.
        if ($zoneIds !== null) {
            // Supervisor: the district they are allowed to export is fixed —
            // ignore whatever district value came in on the request and force
            // their own, so they can't export another district by editing the
            // query string.
            $district = strtolower(auth()->user()->district->name);

            // If a specific zone was requested, only honor it when that zone
            // actually belongs to the supervisor's district. Otherwise drop
            // the zone filter and fall back to "every zone in my district"
            // (handled by $district above), rather than silently returning
            // another district's zone or erroring out.
            if ($zoneId !== null && !in_array((int) $zoneId, $zoneIds, true)) {
                $zoneId = null;
            }
        }

        $export = new MeterReadingsExport($currentCycle->id, $duration, $search, $zoneId, $district);

        return Excel::download($export, 'meter_readings_' . date('Y-m-d_His') . '.xlsx');
    }
}