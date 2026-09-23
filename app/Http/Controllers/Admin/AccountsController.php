<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Zone;
use App\Models\CustomerAccount;
use App\Models\Dma;
use App\Models\Reading;
use App\Models\BillingCycle;
use App\Models\CsaAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\CustomerAccountsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;
use App\Services\AuditLogService;
use Illuminate\Support\Str;

class AccountsController extends Controller
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
    | Same convention as DashboardController / ReadingsController: a
    | SUPERVISOR is restricted to the zone(s) under auth()->user()->district
    | (case-insensitive match against zones.district). Every other role gets
    | null => unrestricted.
    |
    | CustomerAccount has zone_id directly, so scoping here is a plain
    | whereIn('zone_id', $zoneIds) almost everywhere. Single-record actions
    | (show/export) are additionally gated with isAccountInScope() so a
    | supervisor can't reach another district's account just by typing its
    | ID/URL.
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
     * Whether a given CustomerAccount falls inside the current user's scope.
     * Always true when unrestricted (null scope).
     */
    private function isAccountInScope(CustomerAccount $account, ?array $zoneIds): bool
    {
        if ($zoneIds === null) {
            return true;
        }

        return in_array($account->zone_id, $zoneIds, true);
    }


    /**
     * Load initial page
     */

    public function index(Request $request)
    {
        $zoneIds = $this->scopedZoneIds();

        $query = CustomerAccount::with('zone')
            ->when($zoneIds !== null, fn($q) => $q->whereIn('zone_id', $zoneIds));

        // Zone filter (combined with the mandatory scope above, so a
        // supervisor requesting a zone outside their district simply gets
        // zero results rather than leaking another district's accounts).
        if ($request->filled('zone')) {
            $query->where('zone_id', $request->zone);
        }

        // Search by account number
        if ($request->filled('search')) {
            $query->where('account_number', 'like', '%' . $request->search . '%');
        }
        // Search by account number
        if ($request->filled('category')) {
            $query->where('customer_category', 'like', '%' . $request->category . '%');
        }

        $accounts = $query->paginate(10)->withQueryString();

        $accountsTotal = CustomerAccount::when($zoneIds !== null, fn($q) => $q->whereIn('zone_id', $zoneIds))
            ->count();

        // Get zones for the filter dropdown — scoped, so a supervisor is
        // never even offered zones outside their district.
        $zones = Zone::when($zoneIds !== null, fn($q) => $q->whereIn('id', $zoneIds))
            ->orderBy('name')
            ->get();

        // billing cycles for the filter dropdown — global, not district-specific.
        $billingCycles = BillingCycle::orderByDesc('start_date')->get();

        return view('readings.account.index', compact('accounts', 'zones', 'accountsTotal', 'billingCycles'));
    }


    /**
     * Show form to create new account
     */
    public function create()
    {
        $zoneIds = $this->scopedZoneIds();

        $zones = Zone::when($zoneIds !== null, fn($q) => $q->whereIn('id', $zoneIds))->get();

        // NOTE: assumes Dma has a zone_id column — confirm against the Dma
        // model/migration (same assumption made in DashboardController).
        $dmas = Dma::when($zoneIds !== null, fn($q) => $q->whereIn('zone_id', $zoneIds))->get();

        return view('readings.account.create', compact('zones', 'dmas'));
    }

    /**
     * Store new account
     */
    public function store(Request $request)
    {
        $zoneIds = $this->scopedZoneIds();

        $validated = $request->validate([
            'account_number' => 'required|unique:customer_accounts,account_number',
            'meter_number' => 'nullable|unique:customer_accounts,meter_number',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'zone_id' => [
                'required',
                'exists:zones,id',
                // A supervisor can only create accounts in a zone that
                // belongs to their own district — the dropdown already
                // restricts this in create(), but the request body is not
                // trustworthy on its own, so it is enforced again here.
                $zoneIds !== null ? Rule::in($zoneIds) : 'nullable',
            ],
            'dma_id' => 'required|exists:dmas,id',
            'billing_area' => 'nullable|string|max:255'
        ]);

        $account = CustomerAccount::create($validated);

        // Log the creation of a new account
        $this->auditLog->log('CREATE', 'Customer account created', [
            'account_id' => $account->id,
            'account_number' => $account->account_number,
            'performed_by' => auth()->user()->id
        ]);

        return redirect()->route('readings.accounts.index')->with('success', 'Customer account created successfully.');
    }

    /**
     * Show account details:
     * - We show all account details (all column)
     * - We show consuption trend chart for last 6 readings (reading.previous_reading - reading.current_reading)
     * - We show past 6 readings
     */
    public function show(CustomerAccount $account)
    {
        $zoneIds = $this->scopedZoneIds();

        if (! $this->isAccountInScope($account, $zoneIds)) {
            abort(403, 'This account is outside your assigned district.');
        }

        $readings = $account->readings()->latest()->take(6)->get();

        $assignment = CsaAssignment::where('zone_id', $account->zone_id)->first();

        $csaId = $assignment->csa_id;

        $assignedCsa = User::where('id', $csaId)
            ->where('role', 'CSA')
            ->first();

        // Prepare data for consumption trend chart
        $chartData = $readings->reverse()->map(function ($reading) {
            return [
                'date' => $reading->created_at->format('M Y'),
                'consumption' => $reading->current_reading - $reading->previous_reading
            ];
        });

        return view('readings.account.show', compact('account', 'readings', 'chartData', 'assignedCsa'));
    }

    /**
     * Generate a pdf as an export of the account details page
     */
    public function export(CustomerAccount $account)
    {
        $zoneIds = $this->scopedZoneIds();

        if (! $this->isAccountInScope($account, $zoneIds)) {
            abort(403, 'This account is outside your assigned district.');
        }

        $readings = $account->readings()->latest()->take(6)->get();

        $assignment = CsaAssignment::where('zone_id', $account->zone_id)->first();

        $csaId = $assignment->csa_id;

        $assignedCsa = User::where('id', $csaId)
            ->where('role', 'CSA')
            ->first();

        $chartData = $readings->reverse()->map(function ($reading) {
            return [
                'date' => $reading->created_at->format('M Y'),
                'consumption' =>
                    $reading->current_reading - $reading->previous_reading
            ];
        });

        $pdf = Pdf::loadView('readings.account.pdf', [
            'account' => $account,
            'readings' => $readings,
            'chartData' => $chartData,
            'assignedCsa' => $assignedCsa,
            'date' => now()->format('Y-m-d H-i-s'),
            'user' => auth()->user(),
        ]);

        $fileName = 'ACCOUNT-' . $account->account_number . '-REPORT-' . now()->format('Y-m-d_H-i-s') . '.pdf';

        // Log the report generation
        $this->auditLog->log('EXPORT', 'Customer account report created', [
            'account_id' => $account->id,
            'account_number' => $account->account_number,
            'file_name' => $fileName,
            'performed_by' => auth()->user()->id
        ]);

        return $pdf->download($fileName);
    }

    /**
     * Export customer accounts to Excel.
     */
    public function exportExcel(Request $request)
    {
        $zoneIds = $this->scopedZoneIds();

        $zoneId = $request->filled('zone') ? $request->zone : null;
        $search = $request->filled('search') ? $request->search : null;
        $category = $request->filled('category') ? $request->category : null;

        // NOTE: CustomerAccountsExport currently accepts a single $zoneId,
        // not an array — unlike MeterReadingsExport it has no $district
        // parameter either. That means there is no way, as written, to hand
        // it "every zone in my district" in one call. Until the export class
        // is updated to accept an array of zone IDs (or a $district value,
        // scoped the same way as elsewhere in this app), a supervisor is
        // required to pick one specific in-scope zone rather than exporting
        // "all" — exporting with no zone filter would otherwise leak every
        // other district's accounts, since the export builds its own query.
        if ($zoneIds !== null) {
            if ($zoneId === null) {
                return back()->with(
                    'error',
                    'Please select a zone in your district to export — exporting all zones is not available for your account.'
                );
            }

            if (! in_array((int) $zoneId, $zoneIds, true)) {
                abort(403, 'That zone is outside your assigned district.');
            }
        }

        $export = new CustomerAccountsExport($zoneId, $search, $category);

        return Excel::download($export, 'customer_accounts_' . date('Y-m-d_His') . '.xlsx');
    }

    /**
     * Download filtered customer accounts as CSV.
     */
    public function downloadAccounts(Request $request)
    {
        $zoneIds = $this->scopedZoneIds();

        $validated = $request->validate([
            'billing_cycle_id' => ['required', 'exists:billing_cycles,id'],
            'filter' => [
                'required',
                Rule::in([
                    'not_assigned',
                    'not_read',
                    'phone_edited',
                    'billing_area_edited',
                    'meter_number_edited',
                ]),
            ],
        ]);

        $billingCycle = BillingCycle::findOrFail($validated['billing_cycle_id']);
        $filter = $validated['filter'];

        $query = CustomerAccount::query()
            ->select('customer_accounts.*')
            ->with('zone')
            // Mandatory district scope — built directly into this query (no
            // dependency on a downstream export class), so a supervisor can
            // never download another district's accounts here.
            ->when($zoneIds !== null, fn($q) => $q->whereIn('customer_accounts.zone_id', $zoneIds));

        switch ($filter) {

            case 'not_assigned':

                $assignedZoneIds = CsaAssignment::where(
                    'billing_cycle_id',
                    $billingCycle->id
                )->pluck('zone_id');

                $query->whereNotIn('customer_accounts.zone_id', $assignedZoneIds);

                break;

            case 'not_read':

                $query->whereNotExists(function ($q) use ($billingCycle) {
                    $q->selectRaw(1)
                        ->from('readings')
                        ->whereColumn('readings.account_id', 'customer_accounts.id')
                        ->where('readings.billing_cycle_id', $billingCycle->id);
                });

                break;

            case 'phone_edited':

                $query->whereNotNull('new_phone')
                    ->where('new_phone', '<>', '');

                break;

            case 'billing_area_edited':

                $query->whereNotNull('new_address')
                    ->where('new_address', '<>', '');

                break;

            case 'meter_number_edited':

                $query->whereNotNull('new_meter_number')
                    ->where('new_meter_number', '<>', '');

                break;
        }

        if (!(clone $query)->exists()) {
            return back()->with('warning', 'No accounts found.');
        }

        $filename = "{$filter}_" . "{$billingCycle->name}_" . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($query, $filter) {

            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fwrite($handle, "\xEF\xBB\xBF");

            switch ($filter) {

                case 'phone_edited':
                    fputcsv($handle, [
                        'ACCOUNT_NUMBER',
                        'CUSTOMER_NAME',
                        'CURRENT_PHONE',
                        'NEW_PHONE',
                        'ZONE',
                    ]);
                    break;

                case 'billing_area_edited':
                    fputcsv($handle, [
                        'ACCOUNT_NUMBER',
                        'CUSTOMER_NAME',
                        'CURRENT_BILLING_AREA',
                        'NEW_BILLING_AREA',
                        'ZONE',
                    ]);
                    break;

                case 'meter_number_edited':
                    fputcsv($handle, [
                        'ACCOUNT_NUMBER',
                        'CUSTOMER_NAME',
                        'CURRENT_METER_NUMBER',
                        'NEW_METER_NUMBER',
                        'ZONE',
                    ]);
                    break;

                default:
                    fputcsv($handle, [
                        'ACCOUNT_NUMBER',
                        'CUSTOMER_NAME',
                        'ADDRESS',
                        'PHONE',
                        'METER_NUMBER',
                        'CUSTOMER_CATEGORY',
                        'ZONE',
                    ]);
                    break;
            }

            $query
                ->orderBy('customer_accounts.id')
                ->chunkById(
                    500,
                    function ($accounts) use ($handle, $filter) {

                        foreach ($accounts as $account) {

                            switch ($filter) {

                                case 'phone_edited':

                                    fputcsv($handle, [
                                        $this->excelText($account->account_number),
                                        $account->customer_name,
                                        $this->excelText($account->phone),
                                        $this->excelText($account->new_phone),
                                        $account->zone->name ?? 'N/A',
                                    ]);

                                    break;

                                case 'billing_area_edited':

                                    fputcsv($handle, [
                                        $this->excelText($account->account_number),
                                        $account->customer_name,
                                        $account->address,
                                        $account->new_address,
                                        $account->zone->name ?? 'N/A',
                                    ]);

                                    break;

                                case 'meter_number_edited':

                                    fputcsv($handle, [
                                        $this->excelText($account->account_number),
                                        $account->customer_name,
                                        $this->excelText($account->meter_number),
                                        $this->excelText($account->new_meter_number),
                                        $account->zone->name ?? 'N/A',
                                    ]);

                                    break;

                                default:

                                    fputcsv($handle, [
                                        $this->excelText($account->account_number),
                                        $account->customer_name,
                                        $account->address,
                                        $this->excelText($account->phone),
                                        $this->excelText($account->meter_number),
                                        $account->customer_category,
                                        $account->zone->name ?? 'N/A',
                                    ]);

                                    break;
                            }
                        }
                    },
                    'customer_accounts.id',
                    'id'
                );

            fclose($handle);

        }, 200, $headers);
    }

    /**
     * Helper function to ensure Excel treats the value as text.
     */
    private function excelText($value)
    {
        return is_null($value) ? '' : '="' . $value . '"';
    }


}