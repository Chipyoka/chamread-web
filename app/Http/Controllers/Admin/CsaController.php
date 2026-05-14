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

use App\Services\AuditLogService;

class CsaController extends Controller
{
    protected $auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->auditLog = $auditLog;
    }


    /**
     * List all CSAs
     */
    public function index()
    {
        $csas = User::where('role', 'CSA')
            ->latest()
            ->paginate(15);

        return view('admin.csa.index', compact('csas'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $zones = Zone::all();

        return view('admin.csa.create', compact('zones'));
    }

    /**
     * Store new CSA
     */
    public function store(Request $request)
        {
            $data = $request->validate([
                'name' => 'required|string|max:100',
                'username' => 'required|string|max:100|unique:users,username',
                'email' => 'nullable|email|unique:users,email',
                'password' => 'required|string|min:6',
            ]);

            $data['password'] = Hash::make($data['password']);
            $data['role'] = 'CSA';

            // Persist and capture the model
            $user = User::create($data);

            // Remove sensitive data before logging
            $logData = collect($user->toArray())
                ->except(['password', 'remember_token'])
                ->toArray();

            // Audit log
            $this->auditLog->log('CREATE', 'User created', [
                'user_id' => $user->id,
                'payload' => $logData,
            ]);

            return redirect()
                ->route('admin.csas.index')
                ->with('success', 'CSA created successfully.');
        }

    /**
     * Show single CSA
     */
    public function show(User $csa)
    {
        $this->ensureCSA($csa);

        // $assignments = $csa->assignments()
        //     ->with(['zone', 'dma', 'billingCycle'])
        //     ->latest()
        //     ->get();
        $assignments = CsaAssignment::where('csa_id', $csa->id)
            ->latest()
            ->paginate(15);

        return view('admin.csa.show', compact('csa', 'assignments'));
    }

    /**
     * Show edit form
     */
    public function edit(User $csa)
    {
        $this->ensureCSA($csa);

        $zones = Zone::all();

        return view('admin.csa.edit', compact('csa', 'zones'));
    }

    /**
     * Update CSA
     */
   public function update(Request $request, User $csa)
    {
        $this->ensureCSA($csa);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'username' => "required|string|max:100|unique:users,username,{$csa->id}",
            'email' => "nullable|email|unique:users,email,{$csa->id}",
            'status' => 'required|in:ACTIVE,SUSPENDED,INACTIVE',
            'password' => 'nullable|min:6'
        ]);

        // Capture original state (before update)
        $original = collect($csa->toArray())
            ->except(['password', 'remember_token'])
            ->toArray();

        // Handle password mutation
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // Perform update
        $csa->update($data);

        // Refresh model to get latest state
        $csa->refresh();

        // Capture updated state (after update)
        $updated = collect($csa->toArray())
            ->except(['password', 'remember_token'])
            ->toArray();

        // Compute only changed fields (delta)
        $changes = array_diff_assoc($updated, $original);

        // Audit log
        $this->auditLog->log('UPDATE', 'User updated', [
            'user_id' => $csa->id,
            'changes' => $changes,
            'before' => $original,
            'after' => $updated,
        ]);

        return redirect()
            ->route('admin.csas.index')
            ->with('success', 'CSA updated successfully.');
    }
    /**
     * Delete CSA
     */
    public function destroy(User $csa)
    {
        $this->ensureCSA($csa);

        // Optional: prevent delete if has readings
        if ($csa->readings()->exists()) {
        return redirect()->back()
            ->with('error', 'Cannot delete CSA with Readings');
         }

        // Capture state before deletion
        $snapshot = collect($csa->toArray())
            ->except(['password', 'remember_token'])
            ->toArray();

        // Perform delete
        $csa->delete();

        // Audit log
        $this->auditLog->log('DELETE', 'User deleted', [
            'user_id' => $csa->id,
            'payload' => $snapshot,
        ]);

        return redirect()
            ->route('admin.csas.index')
            ->with('success', 'CSA deleted successfully.');
    }

    /**
     * Show assignment page
     */
    public function assign(User $csa)
    {
        $this->ensureCSA($csa);

        $zones = Zone::all();
        $dmas = Dma::all();
        $cycles = BillingCycle::latest()->get();

        $assignments = $csa->assignments()
            ->with(['zone', 'dma', 'billingCycle'])
            ->paginate(10);

        return view('admin.csa.assign', compact(
            'csa',
            'zones',
            'dmas',
            'cycles',
            'assignments'
        ));
    }

    /**
     * Store assignment
     */
    public function storeAssignment(Request $request, User $csa)
    {
        $this->ensureCSA($csa);

        // Validate input
        $data = $request->validate([
            'zone_id' => 'required|exists:zones,id',
            'dma_id' => 'nullable|exists:dmas,id',
            'billing_cycle_id' => 'required|exists:billing_cycles,id',
            'target' => 'required|integer',
            'assignment_type' => 'nullable|in:primary,secondary',
            'covered_csa_id' => 'nullable|exists:users,id',
            'covering_reason' => 'nullable|string|max:255',
            'end_at' => 'nullable|date|after_or_equal:now',
        ]);

        // Attempt to find existing assignment for the CSA + zone + cycle + type
        $existing = CsaAssignment::where([
            'csa_id' => $csa->id,
            'zone_id' => $data['zone_id'],
            'dma_id' => $data['dma_id'],
            'billing_cycle_id' => $data['billing_cycle_id'],
        ])->first();

        $before = $existing ? $existing->toArray() : null;

        // Upsert the assignment
        $assignment = CsaAssignment::updateOrCreate(
            [
                'csa_id' => $csa->id,
                'zone_id' => $data['zone_id'],
                'dma_id' => $data['dma_id'],
                'billing_cycle_id' => $data['billing_cycle_id'],
            ],
            [
                'target' => $data['target'],
                'status' => 'active',
                'assigned_at' => now(),
                'end_at' => $data['end_at'] ?? null,
                'covered_csa_id' => $data['covered_csa_id'] ?? null,
                'covering_reason' => $data['covering_reason'] ?? null,
            ]
        );

        $assignment->refresh();
        $after = $assignment->toArray();

        // Compute changes for audit
        $changes = $before ? array_diff_assoc($after, $before) : $after;

        // Audit log
        $this->auditLog->log('ASSIGN', 'CSA assignment saved', [
            'csa_id' => $csa->id,
            'assignment_id' => $assignment->id,
            'operation' => $before ? 'updated' : 'created',
            'changes' => $changes,
            'before' => $before,
            'after' => $after,
        ]);

        return redirect()
            ->route('admin.csas.assign', $csa->id)
            ->with('success', 'Assignment saved successfully.');
    }

    /**
     * Ensure user is CSA
     */
    private function ensureCSA(User $user): void
    {
        abort_if($user->role !== 'CSA', 404);
    }
}