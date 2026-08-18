<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UsersController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $users = User::query()
            ->where('role', '!=', 'CSA')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->role, function ($query, $role) {
                $query->where('role', $role);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->with('device')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Get filter options
        $roles = User::where('role', '!=', 'CSA')
        ->select('role')
        ->distinct()
        ->pluck('role');
        $statuses = User::select('status')->distinct()->pluck('status');
        $devices = Device::where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return view('system.users.index', compact('users', 'roles', 'statuses', 'devices'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'role' => ['required', 'string', Rule::in(['CSA','SUPERVISOR','ADMIN', 'COMMERCIAL', 'MD', 'FINANCE', 'HR', 'TECHNICAL','IT', 'OTHER'])],
            'status' => ['required', 'string', Rule::in(['ACTIVE','SUSPENDED','INACTIVE'])],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'device_id' => ['nullable', 'exists:devices,id'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'role' => $request->role,
            'status' => $request->status,
            'password' => Hash::make($request->password),
            'device_id' => $request->device_id,
            'photo_url' => $request->photo_url ?? null,
        ]);

        return redirect()
            ->back()
            ->with('success', "User {$user->name} created successfully.");
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        // check user role
        if(!in_array(Auth::user()->role, ['ADMIN'])){
            return redirect()->back()
            ->with('error', 'Insufficient permissions.');
        };

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in(['CSA','SUPERVISOR','ADMIN', 'COMMERCIAL', 'MD', 'FINANCE', 'HR', 'TECHNICAL','IT', 'OTHER'])],
            'status' => ['required', 'string', Rule::in(['ACTIVE','SUSPENDED','INACTIVE'])],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'device_id' => ['nullable', 'exists:devices,id'],
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'role' => $request->role,
            'status' => $request->status,
            'device_id' => $request->device_id,
            'photo_url' => $request->photo_url ?? $user->photo_url,
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return redirect()
            ->back()
            ->with('success', "User {$user->name} updated successfully.");
    }

    /**
     * Toggle user status (ACTIVE <-> SUSPENDED).
     */
    public function toggleStatus(User $user)
    {
        // Prevent toggling if already INACTIVE
        if ($user->status === 'INACTIVE') {
            return redirect()
                ->back()
                ->with('error', "Cannot toggle status for inactive users. Please edit the user directly.");
        }

        $newStatus = $user->status === 'ACTIVE' ? 'SUSPENDED' : 'ACTIVE';
        $user->update(['status' => $newStatus]);

        $action = $newStatus === 'ACTIVE' ? 'activated' : 'suspended';
        
        return redirect()
            ->back()
            ->with('success', "User {$user->name} has been {$action}.");
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()
                ->back()
                ->with('error', 'You cannot delete your own account.');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()
            ->back()
            ->with('success', "User {$userName} has been deleted.");
    }
}