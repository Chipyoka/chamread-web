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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use App\Services\AuditLogService;

class CsaController extends Controller
{
    protected $auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->auditLog = $auditLog;
    }

    /*
    |--------------------------------------------------------------------------
    | District Scoping Helpers
    |--------------------------------------------------------------------------
    |
    | Same convention as the other Admin controllers: a SUPERVISOR is
    | restricted to the zone(s) under auth()->user()->district (case-
    | insensitive match against zones.district). Every other role gets
    | null => unrestricted.
    |
    | A CSA (User) has no zone/district of its own — it is only ever tied to
    | a zone through a CsaAssignment. "This CSA is in my district" therefore
    | means "this CSA has (or has ever had) a CsaAssignment in one of my
    | district's zones" — the same $csaIds convention already used in
    | DashboardController. Single-CSA actions run that check through
    | ensureCSA(), which now also 404s (not 403) when the CSA is out of
    | scope, matching the existing role check below it: a supervisor
    | shouldn't be able to tell "wrong district" from "doesn't exist".
    |
    */

    /**
     * Zone IDs the current user is allowed to see.
     * null = unrestricted (non-supervisor / no district set).
     */
    private function scopedZoneIds(): ?array
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'SUPERVISOR' || !$user->district) {
            return null;
        }

        $districtName = strtolower($user->district->name);

        return Zone::whereRaw('LOWER(district) = ?', [$districtName])
            ->pluck('id')
            ->toArray();
    }

    /**
     * CSA (user) IDs who have ever been assigned to a zone in $zoneIds.
     * null in => null out (unrestricted).
     */
    private function scopedCsaIds(?array $zoneIds): ?array
    {
        if ($zoneIds === null) {
            return null;
        }

        return CsaAssignment::whereIn('zone_id', $zoneIds)
            ->distinct()
            ->pluck('csa_id')
            ->toArray();
    }


    
    /**
     * Display a paginated list of CSAs with optional filtering and sorting.
     *
     * Strategy:
     *  - Aggregates reading counts in a single query over `readings` (cached).
     *  - Filters/sorts on lightweight id lists before loading full models.
     *  - Eager loads relations only for the current page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $perPage = 10;

        $zoneIds = $this->scopedZoneIds();
        $csaIds  = $this->scopedCsaIds($zoneIds);

        $currentCycle = BillingCycle::where('status', 'active')->first();
        $cycleId      = $currentCycle?->id;

        // Resolve readings table and foreign key from the relation.
        $readingsRel   = (new User)->readings();
        $readingsTable = $readingsRel->getRelated()->getTable();
        $readingsFk    = $readingsRel->getForeignKeyName();

        // Cached per-CSA all-time and current-cycle "read" counts.
        $counts = Cache::remember('csa-read-counts:' . ($cycleId ?? 'none'), 60, function () use ($readingsTable, $readingsFk, $cycleId) {
            $q = DB::table($readingsTable)
                ->where('status', 'read')
                ->groupBy($readingsFk)
                ->select("{$readingsFk} as csa_id")
                ->selectRaw('COUNT(*) as total');

            $cycleId
                ? $q->selectRaw('SUM(CASE WHEN billing_cycle_id = ? THEN 1 ELSE 0 END) as cycle_total', [$cycleId])
                : $q->selectRaw('0 as cycle_total');

            return $q->get()
                ->mapWithKeys(fn ($r) => [
                    (int) $r->csa_id => ['total' => (int) $r->total, 'cycle' => (int) $r->cycle_total],
                ])
                ->all();
        });

        // Helpers to read counts for a given CSA id.
        $totalFor = fn ($id) => $counts[$id]['total'] ?? 0;
        $cycleFor = fn ($id) => $counts[$id]['cycle'] ?? 0;

        // Build the filtered id query.
        $base = User::query()
            ->where('role', 'CSA')
            ->when($zoneIds !== null, fn ($q) =>
                $q->whereHas('activeAssignment', fn ($aq) => $aq->whereIn('zone_id', $zoneIds))
            )
            ->when($request->filled('zone'), fn ($q) =>
                $q->whereHas('activeAssignment', fn ($aq) => $aq->where('zone_id', $request->zone))
            )
            ->when($request->filled('search'), fn ($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
            );

        // Status filter: restrict to CSAs with/without reads in the current cycle.
        if ($cycleId && $request->filled('status')) {
            $base->whereHas('activeAssignment', fn ($q) => $q->where('billing_cycle_id', $cycleId));

            $idsWithCycleReads = array_keys(array_filter($counts, fn ($c) => $c['cycle'] > 0));

            if ($request->status === 'withReadings') {
                $base->whereIn('id', $idsWithCycleReads);
            } elseif ($request->status === 'withoutReadings') {
                $base->whereNotIn('id', $idsWithCycleReads);
            }
        }

        // Sort ids by all-time read count desc, with id asc as tie-breaker.
        $sortedIds = $base->pluck('id')
            ->sort(fn ($a, $b) => ($totalFor($b) <=> $totalFor($a)) ?: ($a <=> $b))
            ->values();

        // Load full models and relations for the current page only.
        $page    = LengthAwarePaginator::resolveCurrentPage();
        $pageIds = $sortedIds->forPage($page, $perPage)->values();

        $models = User::with('activeAssignment.zone')
            ->whereIn('id', $pageIds)
            ->get()
            ->keyBy('id');

        $items = $pageIds
            ->map(function ($id) use ($models, $totalFor) {
                $csa = $models->get($id);
                if ($csa) {
                    $csa->readings_count = $totalFor($id);
                }
                return $csa;
            })
            ->filter()
            ->values();

        $csas = (new LengthAwarePaginator($items, $sortedIds->count(), $perPage, $page))
            ->withPath($request->url())
            ->withQueryString();

        // Summary cards, cached per cycle and zone scope.
        $statsKey = 'csa-index-stats:' . ($cycleId ?? 'none') . ':' . md5(json_encode($zoneIds));

        [$csasTotal, $withReadings, $withoutReadings] = Cache::remember($statsKey, 60, function () use ($csaIds, $cycleId, $cycleFor) {
            $scoped = fn () => User::where('role', 'CSA')
                ->when($csaIds !== null, fn ($q) => $q->whereIn('id', $csaIds));

            $total = $scoped()->count();

            if (! $cycleId) {
                return [$total, 0, 0];
            }

            $assignedIds = $scoped()
                ->whereHas('activeAssignment', fn ($q) => $q->where('billing_cycle_id', $cycleId))
                ->pluck('id');

            $with = $assignedIds->filter(fn ($id) => $cycleFor($id) > 0)->count();

            return [$total, $with, $assignedIds->count() - $with];
        });

        // Zones dropdown data.
        $zones = Zone::select('id', 'name')
            ->when($zoneIds !== null, fn ($q) => $q->whereIn('id', $zoneIds))
            ->orderBy('name')
            ->get();

        return view('readings.csa.index', compact(
            'csas',
            'zones',
            'csasTotal',
            'withReadings',
            'withoutReadings'
        ));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $zoneIds = $this->scopedZoneIds();

        $zones = Zone::when($zoneIds !== null, fn($q) => $q->whereIn('id', $zoneIds))->get();

        return view('readings.csa.create', compact('zones'));
    }

    /**
     * Store new CSA
     */
    public function store(Request $request)
    {
        // NOTE: creating a CSA here does not assign them to a zone (that
        // happens separately via assign()/storeAssignment(), which already
        // enforces district scope below) — so there is nothing to scope in
        // this method itself.
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
        $zoneIds = $this->scopedZoneIds();
        $csaIds = $this->scopedCsaIds($zoneIds);

        $this->ensureCSA($csa, $csaIds);

        // $assignments = $csa->assignments()
        //     ->with(['zone', 'dma', 'billingCycle'])
        //     ->latest()
        //     ->get();
        $currentCycle = BillingCycle::where('status', 'active')->first();
         $devices = Device::whereNotIn(
                'id',
                User::whereNotNull('device_id')->pluck('device_id')
            )->get();

         $zones = Zone::withCount('customerAccounts')
            ->has('customerAccounts')
            ->when($zoneIds !== null, fn($q) => $q->whereIn('id', $zoneIds))
            ->get();
            
        $cycles = BillingCycle::latest()->get();

        $assignments = CsaAssignment::where('csa_id', $csa->id)
            ->latest()
            ->paginate(15);
        // total assigned accounts for this CSA
        $target =$csa->activeAssignment?->target ?? 0;
        $totalRead = $csa->readings()->where('status', 'read')->where('billing_cycle_id', $currentCycle->id)->count();
        $totalPending = $target - $totalRead;


        return view('readings.csa.show', compact('csa', 'assignments', 'zones', 'cycles', 'devices', 'target', 'totalRead', 'totalPending'));
    }

    /**
     * Show edit form
     */
    public function edit(User $csa)
    {
        $zoneIds = $this->scopedZoneIds();
        $csaIds = $this->scopedCsaIds($zoneIds);

        $this->ensureCSA($csa, $csaIds);

        $zones = Zone::when($zoneIds !== null, fn($q) => $q->whereIn('id', $zoneIds))->get();

        return view('readings.csa.edit', compact('csa', 'zones'));
    }

    /**
     * Update CSA
     */
    public function update(Request $request, User $csa)
    {
        $zoneIds = $this->scopedZoneIds();
        $csaIds = $this->scopedCsaIds($zoneIds);

        $this->ensureCSA($csa, $csaIds);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'username' => "required|string|max:100|unique:users,username,{$csa->id}",
            'email' => "nullable|email|unique:users,email,{$csa->id}",
            'status' => 'required|in:ACTIVE,SUSPENDED,INACTIVE',
            'password' => 'nullable|min:6',
            'zone_id' => [
                'nullable',
                'exists:zones,id',
                // If this ever reassigns the CSA's zone directly, a
                // supervisor must not be able to move them outside their
                // district by editing the request body.
                $zoneIds !== null ? Rule::in($zoneIds) : 'nullable',
            ],
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
     * Show assignment page
     */
    public function assign(User $csa)
    {
        $zoneIds = $this->scopedZoneIds();
        $csaIds = $this->scopedCsaIds($zoneIds);

        $this->ensureCSA($csa, $csaIds);

        $zones = Zone::when($zoneIds !== null, fn($q) => $q->whereIn('id', $zoneIds))->get();

        // NOTE: assumes Dma has a zone_id column — confirm against the Dma
        // model/migration (same assumption made in the other controllers).
        $dmas = Dma::when($zoneIds !== null, fn($q) => $q->whereIn('zone_id', $zoneIds))->get();

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
        $zoneIds = $this->scopedZoneIds();
        $csaIds = $this->scopedCsaIds($zoneIds);

        $this->ensureCSA($csa, $csaIds);

        // Validate input
        $data = $request->validate([
            'zone_id' => [
                'required',
                'exists:zones,id',
                // The zone dropdown in assign() is already scoped, but the
                // request body is not trustworthy on its own — a supervisor
                // must not be able to assign a CSA into another district's
                // zone by editing the form before submitting.
                $zoneIds !== null ? Rule::in($zoneIds) : 'nullable',
            ],
            'billing_cycle_id' => 'required|exists:billing_cycles,id',
            'device_id' => 'nullable|exists:devices,id',
   
            'assignment_type' => 'nullable|in:primary,secondary',
            'covered_csa_id' => 'nullable|exists:users,id',
            'covering_reason' => 'nullable|string|max:255',
            'end_at' => 'nullable|date|after_or_equal:now',
        ]);


        // validate target
        $customerCount = CustomerAccount::where('zone_id', $data['zone_id'])->count();

        $target = $customerCount;

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

        // $existingUser = User::where('device_id', $data['device_id'])->first();

        // if ($existingUser) {
        //    return redirect()->back()
        //     ->with('warning', 'Device already taken.');
        // }

        // Attempt to find existing assignment for the CSA + zone + cycle + type
        $existing = CsaAssignment::where([
            'csa_id' => $csa->id,
            'zone_id' => $data['zone_id'],
            'billing_cycle_id' => $data['billing_cycle_id'],
        ])->first();

        $before = $existing ? $existing->toArray() : null;

        // Deactivate any previous active assignment for this CSA + zone
        CsaAssignment::where('csa_id', $csa->id)
            ->where('zone_id', $data['zone_id'])
            ->where('status', 'active')
            ->where('billing_cycle_id', '!=', $data['billing_cycle_id'])
            ->update([
                'status' => 'inactive',
                'end_at' => now(),
            ]);

        // Upsert the assignment
        $assignment = CsaAssignment::updateOrCreate(
            [
                'csa_id' => $csa->id,
                'zone_id' => $data['zone_id'],
                'billing_cycle_id' => $data['billing_cycle_id'],
            ],
            [
                'target' => $target,
                'status' => 'active',
                'assigned_at' => now(),
                'end_at' => $data['end_at'] ?? null,
                'covered_csa_id' => $data['covered_csa_id'] ?? null,
                'covering_reason' => $data['covering_reason'] ?? null,
            ]
        );

        // User::where('id', $csa->id)->update([
        //     'device_id' => $data['device_id'],
        // ]);

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
        $zoneIds = $this->scopedZoneIds();
        $csaIds = $this->scopedCsaIds($zoneIds);

        $this->ensureCSA($csa, $csaIds);

        // Current billing cycle
        $currentCycle = BillingCycle::where('status', 'active')->first();

        // Readings
        $readings = $csa->readings()
            ->where('billing_cycle_id', $currentCycle->id)
            ->with(['zone', 'dma'])
            ->latest()
            ->paginate(10);

        $totalReadings = $csa->readings()
            ->where('billing_cycle_id', $currentCycle->id)
            ->count();

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
            'totalReadings',
        ));
    }

    public function assignedAccounts(User $csa)
    {
        $zoneIds = $this->scopedZoneIds();
        $csaIds = $this->scopedCsaIds($zoneIds);

        $this->ensureCSA($csa, $csaIds);

        $currentCycle = BillingCycle::where('status', 'active')->first();

        $assignment = CsaAssignment::where('csa_id', $csa->id)
            ->where('status', 'active')
            ->first();

        abort_unless($assignment, 404, 'No active assignment for this CSA.');

        // Defense in depth: ensureCSA() above only confirms the CSA has ever
        // been assigned somewhere in the district. Their *current* active
        // assignment could in theory sit in a different (out-of-scope) zone
        // if it was recently changed — check that specifically before
        // handing back its account list.
        if ($zoneIds !== null && !in_array($assignment->zone_id, $zoneIds, true)) {
            abort(404);
        }

        $target = $assignment->target ?? 0;

        // Step 1: the fixed set of account IDs this CSA is assigned to
        $accountIds = CustomerAccount::where('account_status', 'metered')->where('zone_id', $assignment->zone_id)
            ->orderBy('id')
            ->take($target)
            ->pluck('id');

        // Step 2: base query - one query does status derivation + filter together
        $baseQuery = CustomerAccount::query()
            ->whereIn('customer_accounts.id', $accountIds)
            ->leftJoin('readings', function ($join) use ($currentCycle) {
                $join->on('readings.account_id', '=', 'customer_accounts.id')
                    ->where('readings.billing_cycle_id', '=', $currentCycle->id);
            })
            ->with('zone')
            ->select('customer_accounts.*')
            ->selectRaw("COALESCE(readings.status, 'NOT_READ') as read_status")
            ->orderBy('customer_accounts.id');

        // Apply filter based on status query parameter
        if (request('status') === 'read') {
            // Filter for accounts with actual 'READ' status in the database
            $baseQuery->where('readings.status', 'read');
        } elseif (request('status') === 'not-read') {
            // Filter for accounts with no reading or NOT_READ status
            $baseQuery->where(function ($q) {
                $q->whereNull('readings.status')
                ->orWhere('readings.status', 'NOT_READ');
            });
        }

        // Step 3: Paginate the results
        $accounts = $baseQuery->paginate(10)->withQueryString();

        // Step 4: Calculate totals from ALL accounts (unfiltered)
        $totalAssigned = CustomerAccount::whereIn('id', $accountIds)->count();

        // Get all accounts with their status for counting (without pagination and without filter)
        $allAccountsWithStatus = CustomerAccount::query()
            ->whereIn('customer_accounts.id', $accountIds)
            ->leftJoin('readings', function ($join) use ($currentCycle) {
                $join->on('readings.account_id', '=', 'customer_accounts.id')
                    ->where('readings.billing_cycle_id', '=', $currentCycle->id);
            })
            ->select('customer_accounts.*')
            ->selectRaw("COALESCE(readings.status, 'NOT_READ') as read_status")
            ->get();

        // Count total pending/not_read (derived)
        $totalPending = $allAccountsWithStatus->filter(function ($account) {
            return $account->read_status === 'NOT_READ';
        })->count();

        // Count total read (actual DB status)
        $totalRead = $allAccountsWithStatus->filter(function ($account) {
            return $account->read_status === 'read';
        })->count();

        return view('readings.csa.accounts', compact('accounts', 'csa', 'totalAssigned', 'totalPending', 'totalRead'));
    }


    public function downloadPending(User $csa)
    {
        $zoneIds = $this->scopedZoneIds();
        $csaIds = $this->scopedCsaIds($zoneIds);

        $this->ensureCSA($csa, $csaIds);

        $currentCycle = BillingCycle::where('status', 'active')->first();
        abort_unless($currentCycle, 404, 'No active billing cycle.');

        $assignment = CsaAssignment::where('csa_id', $csa->id)
            ->where('status', 'active')
            ->first();

        abort_unless($assignment, 404, 'No active assignment for this CSA.');

        // Defense in depth — see the same check in assignedAccounts().
        if ($zoneIds !== null && !in_array($assignment->zone_id, $zoneIds, true)) {
            abort(404);
        }

        $target = $assignment->target ?? 0;

        // Same fixed set of assigned account IDs as assignedAccounts()
        $accountIds = CustomerAccount::where('zone_id', $assignment->zone_id)
            ->orderBy('id')
            ->take($target)
            ->pluck('id');

        $filename = Str::slug($csa->name, '_') . '_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $columns = [
            'ACCOUNT_NUMBER',
            'CUSTOMER_NAME',
            'ADDRESS',
            'PHONE',
            'METER_NUMBER',
            'CUSTOMER_CATEGORY',
            'ZONE',
        ];

        $callback = function () use ($accountIds, $currentCycle, $columns) {
            $handle = fopen('php://output', 'w');

            // BOM so Excel opens UTF-8 correctly (names/addresses may have special chars)
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $columns);

            CustomerAccount::query()
                ->whereIn('customer_accounts.id', $accountIds)
                ->leftJoin('readings', function ($join) use ($currentCycle) {
                    $join->on('readings.account_id', '=', 'customer_accounts.id')
                        ->where('readings.billing_cycle_id', '=', $currentCycle->id);
                })
                ->select('customer_accounts.*')
                ->selectRaw("COALESCE(readings.status, 'NOT_READ') as read_status")
                ->where(function ($q) {
                    $q->whereNull('readings.status')
                        ->orWhere('readings.status', 'NOT_READ');
                })
                ->orderBy('customer_accounts.id')
                ->chunkById(500, function ($accounts) use ($handle) {
                    foreach ($accounts as $account) {
                        fputcsv($handle, [
                            $account->account_number,
                            $account->customer_name,
                            $account->address,
                            $account->phone,
                            $account->meter_number,
                            $account->customer_category,
                            $account->zone->name ?? 'N/A',
                        ]);
                    }
                }, 'customer_accounts.id', 'id');

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

       /**
     * Delete CSA
     */
    public function destroy(User $csa)
    {
        $zoneIds = $this->scopedZoneIds();
        $csaIds = $this->scopedCsaIds($zoneIds);

        $this->ensureCSA($csa, $csaIds);

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
     * Ensure user is CSA (and, when the current user is a district-scoped
     * SUPERVISOR, that this CSA belongs to their district). Both failures
     * 404 rather than 403 so a supervisor can't distinguish "wrong role"
     * from "wrong district" from "doesn't exist".
     */
    private function ensureCSA(User $user, ?array $csaIds = null): void
    {
        abort_if($user->role !== 'CSA', 404);

        if ($csaIds !== null && !in_array($user->id, $csaIds, true)) {
            abort(404);
        }
    }
}