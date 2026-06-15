<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Authentication Controller
 * 
 * Handles login/logout for inspectors
 * Uses Laravel Sanctum for token-based authentication
 */
class AuthController extends Controller
{
    /**
     * Register Inspector
     * 
     * POST /api/register
     * 
     * Request body:
     * {
     *   "name": "John Inspector",
     *   "email": "inspector@example.com",
     *   "password": "password",
     *   "password_confirmation": "password"
     * }
     * 
     * Response (201):
     * {
     *   "token": "1|abc123...",
     *   "user": {
     *     "id": 1,
     *     "name": "John Inspector",
     *     "email": "inspector@example.com",
     *     "role": "inspector"
     *   }
     * }
     * 
     * Response (422):
     * {
     *   "message": "Email already exists"
     * }
     */
    public function register(Request $request)
    {
        // Validate input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'inspector',
        ]);

        // Generate token for offline sync
        $token = $user->createToken('inspection-app', ['*'], now()->addYears(1))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 201);
    }

    /**
     * 
     * Request body:
     * {
     *   "email": "inspector@example.com",
     *   "password": "password"
     * }
     * 
     * Response (200):
     * {
     *   "token": "1|abc123...",
     *   "user": {
     *     "id": 1,
     *     "name": "John Inspector",
     *     "email": "inspector@example.com",
     *     "role": "inspector"
     *   }
     * }
     * 
     * Response (401):
     * {
     *   "message": "Invalid credentials"
     * }
     */
    public function login(Request $request)
    {
        // Validate input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Check credentials
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Generate token for offline sync (valid for 1 year)
        $token = $user->createToken('inspection-app', ['*'], now()->addYears(1))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 200);
    }

    /**
     * Logout Inspector
     * 
     * POST /api/logout
     * 
     * Revokes current token
     * 
     * Response (200):
     * {
     *   "message": "Logged out successfully"
     * }
     */
    public function logout(Request $request)
    {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ], 200);
    }

    /**
     * Get current authenticated user
     * 
     * GET /api/user
     * 
     * Response (200):
     * {
     *   "id": 1,
     *   "name": "John Inspector",
     *   "email": "inspector@example.com",
     *   "role": "inspector"
     * }
     */
    public function user(Request $request)
    {
        return response()->json($request->user(), 200);
    }
}
