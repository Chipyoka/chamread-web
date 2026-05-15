<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Zone;
use App\Models\CustomerAccount;
use App\Models\Dma;
use App\Models\BillingCycle;
use App\Models\CsaAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Services\AuditLogService;

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
    public function index()
    {
        $accounts = CustomerAccount::paginate(10);
        return view('admin.account.index', compact('accounts'));
    }


    /**
     * Show form to create new account
     */
    public function create(){
        $zones = Zone::all();
        $dmas = Dma::all();

        return view('admin.account.create', compact('zones','dmas'));
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

        return redirect()->route('admin.accounts.index')->with('success', 'Customer account created successfully.');
    }

    /**
     * Show account details:
     * - We show all account details (all column)
     * - We show consuption trend chart for last 6 readings (reading.previous_reading - reading.current_reading)
     * - We show past 6 readings
     */
    public function show(CustomerAccount $account){
        $readings = $account->readings()->latest()->take(6)->get();

        $assignedCsa = User::where('zone_id', $account->zone_id)
            ->where('role', 'CSA')
            ->first();

        // Prepare data for consumption trend chart
        $chartData = $readings->reverse()->map(function($reading) {
            return [
                'date' => $reading->created_at->format('M Y'),
                'consumption' => $reading->current_reading - $reading->previous_reading
            ];
        });

        return view('admin.account.show', compact('account', 'readings', 'chartData', 'assignedCsa'));
    }

    /**
     * Generate a pdf as an export of the account details page
     */
     public function export(CustomerAccount $account)
    {
        $readings = $account->readings()
            ->latest()
            ->take(6)
            ->get();

        $assignedCsa = User::where('zone_id', $account->zone_id)
            ->where('role', 'CSA')
            ->first();

        $chartData = $readings->reverse()->map(function ($reading) {
            return [
                'date' => $reading->created_at->format('M Y'),
                'consumption' =>
                    $reading->current_reading - $reading->previous_reading
            ];
        });

        $pdf = Pdf::loadView('admin.account.pdf', [
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
 
}