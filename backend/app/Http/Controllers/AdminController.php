<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookingResource;
use App\Http\Resources\UserResource;
use App\Models\Booking;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    public function dashboard(): JsonResponse
    {
        if (!Gate::allows('viewAny', User::class)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dashboard statistics retrieved successfully',
            'data' => $this->dashboardService->getDashboardStats(),
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        if (!Gate::allows('viewAny', User::class)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $users = User::query()->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully',
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function showUser(User $user): JsonResponse
    {
        if (!Gate::allows('view', $user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully',
            'data' => new UserResource($user),
        ]);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        if (!Gate::allows('update', $user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'role'      => ['sometimes', 'string', 'in:customer,admin,branch_manager,fleet_manager,staff'],
            'branch_id' => ['sometimes', 'nullable', 'exists:branches,id'],
        ]);

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => new UserResource($user->fresh()),
        ]);
    }
}
