<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        Log::info('Password update request initiated', [
            'user_id' => $user->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Validate Role
        |--------------------------------------------------------------------------
        */

        if ($user->role !== 'CSA') {

            Log::warning('Unauthorized password update attempt', [
                'user_id' => $user->id,
                'role' => $user->role,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized role',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Validate Current Password
        |--------------------------------------------------------------------------
        */

        if (!Hash::check($validated['current_password'], $user->password)) {

            Log::warning('Invalid current password supplied', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent Reuse
        |--------------------------------------------------------------------------
        */

        if (Hash::check($validated['new_password'], $user->password)) {

            return response()->json([
                'success' => false,
                'message' => 'New password must differ from current password',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Save Password
        |--------------------------------------------------------------------------
        */

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        Log::info('Password updated successfully', [
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully',
        ]);
    }
}