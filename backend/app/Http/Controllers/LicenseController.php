<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LicenseController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'license_number' => $user->license_number,
                'license_image' => $user->license_image,
                'license_status' => $user->license_status,
                'license_verified_at' => $user->license_verified_at,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'license_number' => 'required|string|max:50',
            'license_image' => 'required|file|image|max:5120',
        ]);

        $user = $request->user();

        if ($user->license_status === 'verified') {
            return response()->json([
                'success' => false,
                'message' => 'Your license is already verified.',
            ], 422);
        }

        $path = $request->file('license_image')->store('licenses', 'public');

        $user->update([
            'license_number' => $request->license_number,
            'license_image' => $path,
            'license_status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Driver\'s license submitted successfully. Pending verification.',
            'data' => [
                'license_number' => $user->license_number,
                'license_status' => $user->license_status,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'license_number' => 'sometimes|required|string|max:50',
            'license_image' => 'sometimes|required|file|image|max:5120',
        ]);

        $user = $request->user();
        $data = $request->only(['license_number']);

        if ($request->hasFile('license_image')) {
            if ($user->license_image && Storage::disk('public')->exists($user->license_image)) {
                Storage::disk('public')->delete($user->license_image);
            }
            $data['license_image'] = $request->file('license_image')->store('licenses', 'public');
        }

        $data['license_status'] = 'pending';

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Driver\'s license updated successfully. Pending verification.',
            'data' => [
                'license_number' => $user->license_number,
                'license_status' => $user->license_status,
            ],
        ]);
    }

    public function verify(Request $request, int $userId): JsonResponse
    {
        if (!in_array($request->user()->role, ['admin', 'staff', 'branch_manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to verify licenses.',
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:verified,rejected',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($userId);

        if ($user->license_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This license is not pending verification.',
            ], 422);
        }

        $updateData = [
            'license_status' => $request->status,
        ];

        if ($request->status === 'verified') {
            $updateData['license_verified_at'] = now();
        }

        $user->update($updateData);

        $message = $request->status === 'verified'
            ? 'Your driver\'s license has been verified successfully.'
            : 'Your driver\'s license could not be verified. Please upload a valid document.';

        $user->notify(new \App\Notifications\LicenseVerified($user, $request->status, $request->notes));

        return response()->json([
            'success' => true,
            'message' => "License {$request->status} successfully.",
            'data' => (new UserResource($user->fresh()))->resolve(),
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        if (!in_array($request->user()->role, ['admin', 'staff', 'branch_manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $query = User::where('license_status', 'pending');

        if ($request->user()->isBranchManager() || $request->user()->isStaff()) {
            $query->where('branch_id', $request->user()->branch_id);
        }

        $users = $query->get();

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users),
        ]);
    }
}
