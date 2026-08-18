<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Zone;
use App\Models\Dma;
use App\Models\BillingCycle;
use App\Models\CsaAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

use App\Services\AuditLogService;

class CyclesController extends Controller
{
    protected $auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->auditLog = $auditLog;
    }


     /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $billingCycles = BillingCycle::latest()->paginate(10);
        return view('management.cycles.index', compact('billingCycles'));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'deadline' => 'nullable|date|after_or_equal:start_date',
        ]);

        
        // check if name already exist
        $existing = BillingCycle::where('name', $request->name)->first();

        if($existing){
            return redirect()->back()
            ->with('error', 'Billing name conflict.');
        };

        // check if previous is still active
        $activeCycleExists = BillingCycle::where('status', 'active')->exists();

        if ($activeCycleExists) {
            return redirect()->back()
            ->with('warning', 'Close current active cycle.');
        }

        $billingCycle = BillingCycle::create($validated);

        return redirect()->back()
            ->with('success', 'Billing cycle created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(BillingCycle $billingCycle)
    {
        return view('management.cycles.index', compact('billingCycle'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BillingCycle $billingCycle)
    {
        return view('management.cycles.index', compact('billingCycle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BillingCycle $billingCycle)
    {
        $validated = $request->validate([
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => ['required', Rule::in(['pending', 'active', 'locked', 'closed'])],
        ]);

        if(!in_array(Auth::user()->role, ['ADMIN', 'COMMERCIAL', 'IT'])){
            return redirect()->back()
            ->with('error', 'Insufficient permissions.');
        };

        $billingCycle->update($validated);

        return redirect()
            ->back()
            ->with('success', 'Billing cycle updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BillingCycle $billingCycle)
    {
        $billingCycle->delete();

        return redirect()
            ->route('management.cycles.index')
            ->with('success', 'Billing cycle deleted successfully.');
    }

    /**
     * Toggle download permission.
     */
    public function toggleDownload(BillingCycle $billingCycle)
    {
         if(!in_array(Auth::user()->role, ['ADMIN', 'COMMERCIAL', 'IT'])){
            return redirect()->back()
            ->with('error', 'Insufficient permissions.');
        };

        $billingCycle->update([
            'can_download' => !$billingCycle->can_download
        ]);

        return back()->with('success', 'Download permission updated.');
    }

    /**
     * Toggle upload permission.
     */
    public function toggleUpload(BillingCycle $billingCycle)
    {
         if(!in_array(Auth::user()->role, ['ADMIN', 'COMMERCIAL', 'IT'])){
            return redirect()->back()
            ->with('error', 'Insufficient permissions.');
        };

        $billingCycle->update([
            'can_upload' => !$billingCycle->can_upload
        ]);

        return back()->with('success', 'Upload permission updated.');
    }

    /**
     * Extend the deadline.
     */
    public function extendDeadline(Request $request, BillingCycle $billingCycle)
    {
        $request->validate([
            'new_deadline' => 'required|date|after:start_date'
        ]);

        $billingCycle->update([
            'deadline' => $request->new_deadline
        ]);

        return back()->with('success', 'Deadline extended successfully.');
    }

    /**
     * Update the status.
     */
    public function updateStatus(Request $request, BillingCycle $billingCycle)
    {
        $request->validate([
            'status' => ['required', Rule::in(['pending', 'active', 'locked', 'closed'])]
        ]);

        $billingCycle->update([
            'status' => $request->status
        ]);

        return back()->with('success', 'Status updated successfully.');
    }

    /**
     * Quick toggle actions from index.
     */
    public function quickToggle(Request $request, BillingCycle $billingCycle)
    {
        $request->validate([
            'field' => ['required', Rule::in(['can_download', 'can_upload'])],
            'value' => 'required|boolean'
        ]);

        $billingCycle->update([
            $request->field => $request->value
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Updated successfully.'
        ]);
    }

 
}