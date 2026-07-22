<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Zone;
use App\Models\Reading;
use App\Models\Device;
use App\Models\Dma;
use App\Models\CustomerAccount;
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
        $csas = User::where('role', 'CSA')->with('activeAssignment.zone')
            ->latest()
            ->paginate(15);

        $zones = Zone::all();

        return view('readings.csa.index', compact('csas', 'zones'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $zones = Zone::all();

        return view('readings.csa.create', compact('zones'));
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
            ->back()
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

         $devices = Device::whereNotIn(
                'id',
                User::whereNotNull('device_id')->pluck('device_id')
            )->get();

         $zones = Zone::withCount('customerAccounts')
            ->has('customerAccounts')
            ->get();
            
        $cycles = BillingCycle::latest()->get();

        $assignments = CsaAssignment::where('csa_id', $csa->id)
            ->latest()
            ->paginate(15);

        return view('readings.csa.show', compact('csa', 'assignments', 'zones', 'cycles', 'devices'));
    }

    /**
     * Show edit form
     */
    public function edit(User $csa)
    {
        $this->ensureCSA($csa);

        $zones = Zone::all();

        return view('readings.csa.edit', compact('csa', 'zones'));
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
            'password' => 'nullable|min:6',
            'zone_id' => 'nullable|exists:zones,id',
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
            ->route('readings.csas.index')
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
            ->route('readings.csas.index')
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

        return view('readings.csa.assign', compact(
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
            'billing_cycle_id' => 'required|exists:billing_cycles,id',
            'device_id' => 'nullable|exists:devices,id',
            'target' => 'nullable|integer',
            'assignment_type' => 'nullable|in:primary,secondary',
            'covered_csa_id' => 'nullable|exists:users,id',
            'covering_reason' => 'nullable|string|max:255',
            'end_at' => 'nullable|date|after_or_equal:now',
        ]);


        // validate target
        $customerCount = CustomerAccount::where('zone_id', $data['zone_id'])->count();

        $target = !empty($data['target'])
            ? (int) $data['target']
            : $customerCount;

        if ($target > $customerCount) {
              return redirect()->back()
            ->with('Error', 'Target is too high.');
        }

        // validate assignment 
        $existingAssignment = CsaAssignment::where('billing_cycle_id', $data['billing_cycle_id'])
            ->where('zone_id', $data['zone_id'])
            ->first();

        if ($existingAssignment) {
            return redirect()->back()
            ->with('warning', 'Zone already taken.');
        }

        $existingUser = User::where('device_id', $data['device_id'])->first();

        if ($existingUser) {
           return redirect()->back()
            ->with('warning', 'Device already taken.');
        }

        // Attempt to find existing assignment for the CSA + zone + cycle + type
        $existing = CsaAssignment::where([
            'csa_id' => $csa->id,
            'zone_id' => $data['zone_id'],
            'billing_cycle_id' => $data['billing_cycle_id'],
        ])->first();

        $before = $existing ? $existing->toArray() : null;

        // Upsert the assignment
        $assignment = CsaAssignment::updateOrCreate(
            [
                'csa_id' => $csa->id,
                'zone_id' => $data['zone_id'],
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

        User::where('id', $csa->id)->update([
            'device_id' => $data['device_id'],
        ]);

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
            'device' => $data['device_id'],
        ]);

        return redirect()
            ->route('readings.csas.show', $csa->id)
            ->with('success', 'Assignment saved successfully.');
     }

    /**
     * Get readings and coordinates for a CSA
     */
    public function csaReadings(User $csa)
    {
        $this->ensureCSA($csa);

        // Current billing cycle
        $currentCycle = BillingCycle::latest()->first();

        // Readings
        $readings = $csa->readings()
            ->where('billing_cycle_id', $currentCycle->id)
            ->with(['zone', 'dma'])
            ->latest()
            ->paginate(10);

        /*
        |--------------------------------------------------------------------------
        | Extract GPS points for map
        |--------------------------------------------------------------------------
        */

        $points = $csa->readings()
            ->where('billing_cycle_id', $currentCycle->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get([
                'id',
                'latitude',
                'longitude',
                'created_at',
                'account_id',
            ])
            ->map(function ($reading) {

                return [
                    'id' => $reading->id,

                    'lat' => (float) $reading->latitude,

                    'lng' => (float) $reading->longitude,

                    'time' => $reading->created_at?->format('Y-m-d H:i:s'),

                    'account' => $reading->account?->account_number ?? 'N/A',
                ];
            })
            ->unique(fn ($point) => $point['lat'] . ',' . $point['lng'])
            ->values()
            ->toArray();

            $pointsT = [
                    [
                        'id' => 1,
                        'lat' => -15.4067,
                        'lng' => 28.2871,
                        'time' => now()->subMinutes(40)->format('Y-m-d H:i:s'),
                        'account' => 'ACC-1001',
                    ],

                    [
                        'id' => 2,
                        'lat' => -15.4165,
                        'lng' => 28.3012,
                        'time' => now()->subMinutes(30)->format('Y-m-d H:i:s'),
                        'account' => 'ACC-1002',
                    ],

                    [
                        'id' => 3,
                        'lat' => -15.4250,
                        'lng' => 28.3158,
                        'time' => now()->subMinutes(20)->format('Y-m-d H:i:s'),
                        'account' => 'ACC-1003',
                    ],

                    [
                        'id' => 4,
                        'lat' => -15.4382,
                        'lng' => 28.3304,
                        'time' => now()->subMinutes(10)->format('Y-m-d H:i:s'),
                        'account' => 'ACC-1004',
                    ],
                ];

        return view('readings.csa.readings', compact(
            'readings',
            'csa',
            'points',
            'pointsT',
        ));
    }


    /**
     * Get assigned accounts: all customer accounts with the same zone_id as the CSA
     */
    public function assignedAccounts(User $csa)
    {
        $this->ensureCSA($csa);

        $currentCycle = BillingCycle::latest()->first();
        $assignment = CsaAssignment::where('csa_id', $csa->id)->where('status', 'active')->first();
        $target = $assignment->target ?? 0;

        // Step 1: Fetch accounts
        $accounts = CustomerAccount::where('zone_id', $assignment->zone_id)
            ->orderBy('id') // important for deterministic results
            ->take($target);

        $accounts = CustomerAccount::fromSub($accounts, 'accounts')
            ->with('zone')
            ->paginate(10);

        // Step 2: Fetch all readings for these accounts in ONE query
        $readings = Reading::where('billing_cycle_id', $currentCycle->id)
            ->whereIn('account_id', $accounts->pluck('id'))
            ->get()
            ->groupBy('account_id');

        // Step 3: Attach computed status to each account
        $accounts->getCollection()->transform(function ($account) use ($readings) {

            $reading = $readings->get($account->id)?->first();

            $account->read_status = $reading
                ? $reading->status
                : 'NOT_READ';

            return $account;
        });

        return view('readings.csa.accounts', compact('accounts', 'csa'));
    }
    /**
     * Ensure user is CSA
     */
    private function ensureCSA(User $user): void
    {
        abort_if($user->role !== 'CSA', 404);
    }
}