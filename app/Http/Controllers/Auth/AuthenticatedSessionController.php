<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;


use App\Models\Reading;
use App\Models\BillingCycle;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        $read = Reading::where('status', 'read')->count();

        $pending = Reading::where('status', '!=', 'read')->count();

        // Latest billing cycle
        $currentCycle = BillingCycle::latest()->first();


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

        return redirect()->intended(route('dashboard.index', absolute: false));
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
