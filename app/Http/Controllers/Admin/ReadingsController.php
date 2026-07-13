<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Zone;
use App\Models\Reading;
use App\Models\Dma;
use App\Models\BillingCycle;
use App\Models\CsaAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Barryvdh\DomPDF\Facade\Pdf;

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
    public function index()
    {
        $currentCycle = BillingCycle::where('status', 'active')->first();
        $readings = [];

        if($currentCycle) {
            $readings = Reading::where('billing_cycle_id', $currentCycle->id)->paginate(10);
        }



        return view('readings.meter-reading.index', compact(
            'readings',
        ));
    }

    /**
     * show reading with associated account
     */
    public function show(Reading $reading){
    
        return view('readings.meter-reading.show', compact('reading',));
    }

    /**
     * Export Reading as pdf
     */
    public function export(Reading $reading)
    {
        $consumption = (($reading->current_reading ?? 0) - ($reading->previous_reading ?? 0));

        $pdf = Pdf::loadView('readings.meter-reading.pdf', [
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
 
}