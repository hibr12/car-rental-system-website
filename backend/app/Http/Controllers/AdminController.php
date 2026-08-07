<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\UserResource;
use App\Models\Booking;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

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

    public function updateUser(UpdateUserRequest $request, User $user): JsonResponse
    {
        if (!Gate::allows('update', $user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $data = $request->validated();

        if (isset($data['password']) && $data['password'] !== null && $data['password'] !== '') {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => new UserResource($user->fresh()),
        ]);
    }
}
