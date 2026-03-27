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

class CsaController extends Controller
{
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
            'zone_id' => 'nullable|exists:zones,id',
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['role'] = 'CSA';

        User::create($data);

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
            'zone_id' => 'nullable|exists:zones,id',
            'status' => 'required|in:ACTIVE,SUSPENDED,INACTIVE',
            'password' => 'nullable|min:6'
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $csa->update($data);

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
        // abort_if($csa->readings()->exists(), 403);

        $csa->delete();

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

        $data = $request->validate([
            'zone_id' => 'required|exists:zones,id',
            'dma_id' => 'nullable|exists:dmas,id',
            'billing_cycle_id' => 'required|exists:billing_cycles,id',
        ]);

        CsaAssignment::updateOrCreate(
            [
                'csa_id' => $csa->id,
                'zone_id' => $data['zone_id'],
                'dma_id' => $data['dma_id'],
                'billing_cycle_id' => $data['billing_cycle_id'],
            ],
            [
                'status' => 'active',
                'assigned_at' => now(),
            ]
        );

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