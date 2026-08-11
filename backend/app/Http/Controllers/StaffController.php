<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = User::with('branch')->notCustomer();

        // Branch managers see only their branch staff
        if ($user->isBranchManager()) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->has('branch_id') && $user->isAdmin()) {
            $query->where('branch_id', $request->branch_id);
        }

        $staff = $query->orderBy('name')->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $staff->items(),
            'meta'    => [
                'current_page' => $staff->currentPage(),
                'last_page'    => $staff->lastPage(),
                'total'        => $staff->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $actor = $request->user();

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8'],
            'phone'     => ['nullable', 'string', 'max:30'],
            'role'      => ['required', 'in:branch_manager,fleet_manager,staff'],
            'branch_id' => ['required', 'exists:branches,id'],
        ]);

        // Branch managers can only add staff to their own branch
        if ($actor->isBranchManager() && (int) $data['branch_id'] !== $actor->branch_id) {
            return response()->json(['success' => false, 'message' => 'You can only add staff to your own branch.'], 403);
        }

        $staff = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'phone'     => $data['phone'] ?? null,
            'role'      => $data['role'],
            'branch_id' => $data['branch_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Staff member created successfully.',
            'data'    => $staff->load('branch'),
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();

        // Branch managers can only update their own branch's staff
        if ($actor->isBranchManager() && $user->branch_id !== $actor->branch_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $data = $request->validate([
            'name'      => ['sometimes', 'string', 'max:255'],
            'email'     => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'phone'     => ['nullable', 'string', 'max:30'],
            'role'      => ['sometimes', 'in:branch_manager,fleet_manager,staff'],
            'branch_id' => ['sometimes', 'exists:branches,id'],
        ]);

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Staff updated.',
            'data'    => $user->fresh()->load('branch'),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();

        if ($actor->isBranchManager() && $user->branch_id !== $actor->branch_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($user->id === $actor->id) {
            return response()->json(['success' => false, 'message' => 'You cannot remove yourself.'], 422);
        }

        $user->delete();

        return response()->json(['success' => true, 'message' => 'Staff member removed.']);
    }
}
