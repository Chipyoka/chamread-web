<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Reading;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ReadingVerificationController extends Controller
{
    // Adjust to your real supervisor role names.
    private const ALLOWED_ROLES = ['ADMIN', 'SUPERVISOR'];

    /**
     * Supervisor confirms a flagged reading: flagged -> manual.
     * The flags stay attached as history of why it was held.
     */
    public function verify(Reading $reading) : RedirectResponse
    {
        if (!in_array(Auth::user()->role, self::ALLOWED_ROLES, true)) {
            return back()->with('error', 'Insufficient permissions.', 403);
        }

        // Conditional update: only a still-flagged reading can be verified,
        // so a double click or two supervisors can't both succeed.
        $updated = Reading::whereKey($reading->getKey())
            ->where('verification_status', 'flagged')
            ->update([
                'verification_status' => 'manual',
                'verified_by'         => Auth::id(),
                'verified_at'         => now(),
            ]);

        if (!$updated) {
            return back()->with('error', 'Reading is not awaiting verification.', 409);
        }

        return back()->with('success', 'Reading verified.');
    }
}