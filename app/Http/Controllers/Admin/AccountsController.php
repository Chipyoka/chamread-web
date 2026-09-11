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


    /**
     * Load initial page
     */
 
    public function index(Request $request)
    {
        $query = CustomerAccount::with('zone');

        // Zone filter
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
        $accountsTotal = CustomerAccount::count();
        
        // Get zones for the filter dropdown
        $zones = Zone::orderBy('name')->get();

        // billing cycles for the filter dropdown
        $billingCycles = BillingCycle::orderByDesc('start_date')->get();

        return view('readings.account.index', compact('accounts', 'zones', 'accountsTotal', 'billingCycles'));
    }


    /**
     * Show form to create new account
     */
    public function create(){
        $zones = Zone::all();
        $dmas = Dma::all();

        return view('readings.account.create', compact('zones','dmas'));
    }

    /**
     * Store new account
     */
    public function store(Request $request){
        $validated = $request->validate([
            'account_number' => 'required|unique:customer_accounts,account_number',
            'meter_number' => 'nullable|unique:customer_accounts,meter_number',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'zone_id' => 'required|exists:zones,id',
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
    public function show(CustomerAccount $account){
        $readings = $account->readings()->latest()->take(6)->get();

        $assignment = CsaAssignment::where('zone_id', $account->zone_id)->first();

        $csaId = $assignment->csa_id;

        $assignedCsa = User::where('id', $csaId)
            ->where('role', 'CSA')
            ->first();

        // Prepare data for consumption trend chart
        $chartData = $readings->reverse()->map(function($reading) {
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
        $zoneId = $request->filled('zone') ? $request->zone : null;
        $search = $request->filled('search') ? $request->search : null;
        $category = $request->filled('category') ? $request->category : null;

        $export = new CustomerAccountsExport($zoneId, $search, $category);
        
        return Excel::download($export, 'customer_accounts_' . date('Y-m-d_His') . '.xlsx');
    }

    /**
     * Download filtered customer accounts as CSV.
     */
    public function downloadAccounts(Request $request)
    {
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
            ->with('zone');

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