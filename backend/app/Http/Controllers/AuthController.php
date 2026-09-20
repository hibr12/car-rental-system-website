<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'phone' => $request->phone,
            'role' => 'customer',
        ]);

        // Create a session for the user if session is available (web SPA cookie auth)
        if ($request->hasSession()) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        // Issue Sanctum token for mobile and API clients
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $user = User::with('branch')->where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user'  => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Revoke current access token if request was made using a Bearer token
        if ($request->user()?->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('branch');

        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully',
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => new UserResource($user->fresh()),
            ],
        ]);
    }

    // Password Reset
    public function sendResetLink(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send password reset email', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset email. Please try again later.',
            ], 500);
        }

        return $status === Password::RESET_LINK_SENT
            ? response()->json([
                'success' => true,
                'message' => 'Password reset link sent to your email.',
            ])
            : response()->json([
                'success' => false,
                'message' => 'Unable to send reset link. Email not found.',
            ], 422);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->password = Hash::make($password);
                    $user->remember_token = Str::random(60);
                    $user->save();
                }
            );
        } catch (\Throwable $e) {
            Log::error('Failed to reset password', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password. Please try again later.',
            ], 500);
        }

        return $status === Password::PASSWORD_RESET
            ? response()->json([
                'success' => true,
                'message' => 'Password reset successfully.',
            ])
            : response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset token.',
            ], 422);
    }

    /**
     * Permanently delete user account and associated personal data.
     * Complies with Apple App Store Guideline 5.1.1 and Google Play account deletion policy.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Administrative and staff accounts must be managed through company controls
        if (!$user->isCustomer()) {
            return response()->json([
                'success' => false,
                'message' => 'Staff and administrative accounts cannot be deleted directly. Please contact an administrator.',
            ], 403);
        }

        // Ensure user has no ongoing or pending rentals/bookings
        $terminalStatuses = [
            Booking::STATUS_COMPLETED,
            Booking::STATUS_CANCELLED,
            Booking::STATUS_REJECTED,
            Booking::STATUS_EXPIRED,
        ];

        $hasActiveBookings = $user->bookings()
            ->whereNotIn('status', $terminalStatuses)
            ->exists();

        if ($hasActiveBookings) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your account while you have active bookings or pending rentals. Please complete or cancel them first.',
            ], 422);
        }

        // Revoke all personal access tokens
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        // Invalidate session if present (web SPA)
        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // Delete user (associated licenses, reviews, etc. cascade delete)
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Your account and personal data have been permanently deleted.',
        ]);
    }
}
