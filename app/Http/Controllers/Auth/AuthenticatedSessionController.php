<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;


use App\Models\Reading;
use App\Models\CustomerAccount;
use App\Models\BillingCycle;
use App\Models\CsaAssignment;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View

    {
        // Current billing cycle
        $currentCycle = BillingCycle::where('status', 'active')->first();

        $pending = 0;
        $read = 0;

        if ($currentCycle) {
            $assignedZoneIds = CsaAssignment::where(
                'billing_cycle_id',
                $currentCycle->id
            )->pluck('zone_id');

            // Total accounts in assigned zones
            $total = CustomerAccount::whereIn('zone_id', $assignedZoneIds)
                ->count();

            // Accounts WITH readings for the CURRENT billing cycle
            $read = CustomerAccount::whereIn('zone_id', $assignedZoneIds)
                ->whereExists(function ($query) use ($currentCycle) {
                    $query->selectRaw(1)
                        ->from('readings')
                        ->whereColumn(
                            'readings.account_id',
                            'customer_accounts.id'
                        )
                        ->where(
                            'readings.billing_cycle_id',
                            $currentCycle->id
                        );
                })
                ->count();

            // Accounts WITHOUT readings for the CURRENT billing cycle
            $pending = $total - $read;
        }

        return view('auth.login', compact(
            'currentCycle',
            'read',
            'pending'
        )
    );
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // update last login 
        $request->user()->update([
            'last_login_at' => now()
        ]);

        return redirect()->intended(route('dashboard.dashboard.index', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
