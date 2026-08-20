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


    /**
     * Load initial page
     */
   public function index(Request $request)
    {
        $currentCycle = BillingCycle::where('status', 'active')->first();
        
        if (!$currentCycle) {
            return view('readings.reading.index', [
                'readings' => collect(),
                'zones' => Zone::orderBy('name')->get(),
                'districts' => collect(),
            ]);
        }

        $query = Reading::with(['account', 'account.zone', 'csa', 'billingCycle'])
            ->where('billing_cycle_id', $currentCycle->id);

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

        // Apply zone filter
        if ($request->filled('zone')) {
            $query->whereHas('account', function ($q) use ($request) {
                $q->where('zone_id', $request->zone);
            });
        }

        // Apply district filter (from zone relation)
        if ($request->filled('district')) {
            $query->whereHas('account.zone', function ($q) use ($request) {
                $q->where('district', $request->district);
            });
        }

        $readings = $query->orderBy('reading_time', 'desc')->paginate(15)->withQueryString();

        // Get zones for filter dropdown
        $zones = Zone::orderBy('name')->get();

        // Get districts from zones for filter dropdown
        $districts = Zone::whereNotNull('district')
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
    public function show(Reading $reading){
    
        return view('readings.reading.show', compact('reading',));
    }

    /**
     * Export Reading as pdf
     */
    public function export(Reading $reading)
    {
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
        $currentCycle = BillingCycle::where('status', 'active')->first();
        
        if (!$currentCycle) {
            return back()->with('error', 'No active billing cycle found.');
        }

        $duration = $request->filled('duration') ? $request->duration : null;
        $search = $request->filled('search') ? $request->search : null;
        $zoneId = $request->filled('zone') ? $request->zone : null;
        $district = $request->filled('district') ? $request->district : null;

        $export = new MeterReadingsExport($currentCycle->id, $duration, $search, $zoneId, $district);
        
        return Excel::download($export, 'meter_readings_' . date('Y-m-d_His') . '.xlsx');
    }
}