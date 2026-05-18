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



        return view('admin.reading.index', compact(
            'readings',
        ));
    }

 
}