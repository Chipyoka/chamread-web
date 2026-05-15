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
 
}