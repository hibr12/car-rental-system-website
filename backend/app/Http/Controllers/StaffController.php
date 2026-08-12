<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScope
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = User::with('branch')->notCustomer();

        $requestedBranchId = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;
        $effectiveBranchId = $this->branchScope->resolveBranchFilter($user, $requestedBranchId);

        if ($effectiveBranchId !== null) {
            $query->where('branch_id', $effectiveBranchId);
        } elseif ($requestedBranchId && $user->isAdmin()) {
            $query->where('branch_id', $requestedBranchId);
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

        $allowedRoles = $actor->isBranchManager()
            ? 'staff'
            : 'branch_manager,fleet_manager,staff';

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8'],
            'phone'     => ['nullable', 'string', 'max:30'],
            'role'      => ['required', 'in:' . $allowedRoles],
            'branch_id' => ['required', 'exists:branches,id'],
        ]);

        $data = $this->branchScope->forceOwnBranchId($actor, $data);

        if ($actor->isBranchManager() && (int) $data['branch_id'] !== (int) $actor->branch_id) {
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

        if ($actor->isBranchManager() && (int) $user->branch_id !== (int) $actor->branch_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $allowedRoles = $actor->isBranchManager()
            ? 'staff'
            : 'branch_manager,fleet_manager,staff';

        $data = $request->validate([
            'name'      => ['sometimes', 'string', 'max:255'],
            'email'     => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'phone'     => ['nullable', 'string', 'max:30'],
            'role'      => ['sometimes', 'in:' . $allowedRoles],
            'branch_id' => ['sometimes', 'exists:branches,id'],
        ]);

        $data = $this->branchScope->stripBranchId($actor, $data);

        if ($actor->isBranchManager() && isset($data['branch_id']) && (int) $data['branch_id'] !== (int) $actor->branch_id) {
            return response()->json(['success' => false, 'message' => 'You cannot move staff to another branch.'], 403);
        }

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

        if ($actor->isBranchManager() && (int) $user->branch_id !== (int) $actor->branch_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($user->id === $actor->id) {
            return response()->json(['success' => false, 'message' => 'You cannot remove yourself.'], 422);
        }

        $user->delete();

        return response()->json(['success' => true, 'message' => 'Staff member removed.']);
    }
}
