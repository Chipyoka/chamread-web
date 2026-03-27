<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // Not authenticated OR role not allowed
        if (!$user || !in_array($user->role, $roles) || $user->status !== 'ACTIVE') {

            // Handle API vs Web differently
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthorized.'
                ], 403);
            }

            // Web session handling
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login' => 'Unauthorized access.',
            ]);
        }

        return $next($request);
    }
}