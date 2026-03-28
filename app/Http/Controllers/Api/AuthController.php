<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Mobile Login (CSA / Any User)
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string'], // e.g. "Samsung A10", "Tecno Spark"
        ]);

        // Attempt authentication
        if (!Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ])) {
            throw ValidationException::withMessages([
                'username' => ['Invalid credentials provided.'],
            ]);
        }

        $user = $request->user();

        $request->user()->update([
            'last_login_at' => now()
        ]);

        // Optional: Restrict mobile login to CSA only
        // if ($user->role !== 'CSA') {
        //     return response()->json([
        //         'message' => 'Access denied. Not a CSA account.'
        //     ], 403);
        // }

        // Revoke existing tokens for this device (optional strict mode)
        $user->tokens()
            ->where('name', $credentials['device_name'])
            ->delete();

        // Create token
        $token = $user->createToken($credentials['device_name'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $this->userPayload($user),
                'token' => $token,
            ]
        ]);
    }

    /**
     * Get Authenticated User (Session Restore)
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->userPayload($request->user())
            ]
        ]);
    }

    /**
     * Logout current device
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Logout all devices (optional admin/security use)
     */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices'
        ]);
    }

    /**
     * Standardized user payload
     */
    protected function userPayload($user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'photo_url' => $user->photo_url,
            'created_at' => $user->created_at,
            'last_login_at' => $user->last_login_at,
        ];
    }
}