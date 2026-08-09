<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Http\Resources\BranchResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BranchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!Gate::allows('viewAny', Branch::class)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $query = Branch::with('manager');

        if ($request->user()->isBranchManager()) {
            $query->where('id', $request->user()->branch_id);
        }

        $branches = $query->get();

        return response()->json([
            'success' => true,
            'data' => BranchResource::collection($branches),
        ]);
    }

    public function show(Branch $branch): JsonResponse
    {
        if (!Gate::allows('view', $branch)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $branch->load('manager', 'vehicles', 'users');

        return response()->json([
            'success' => true,
            'data' => new BranchResource($branch),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!Gate::allows('create', Branch::class)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:branches',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $branch = Branch::create($request->only([
            'name', 'address', 'city', 'phone', 'email', 'manager_id', 'status',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Branch created successfully.',
            'data' => new BranchResource($branch),
        ], 201);
    }

    public function update(Request $request, Branch $branch): JsonResponse
    {
        if (!Gate::allows('update', $branch)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:branches,name,' . $branch->id,
            'address' => 'sometimes|required|string|max:255',
            'city' => 'sometimes|required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $branch->update($request->only([
            'name', 'address', 'city', 'phone', 'email', 'manager_id', 'status',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Branch updated successfully.',
            'data' => new BranchResource($branch->fresh()->load('manager')),
        ]);
    }

    public function destroy(Branch $branch): JsonResponse
    {
        if (!Gate::allows('delete', $branch)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $branch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Branch deleted successfully.',
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $branch = $request->user()->branch;

        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'No branch assigned.',
            ], 404);
        }

        $todayBookings = $branch->bookings()->whereDate('created_at', today())->count();
        $activeRentals = $branch->bookings()->where('status', 'active')->count();
        $pendingApprovals = $branch->bookings()->where('status', 'pending')->count();
        $availableVehicles = $branch->vehicles()->where('status', 'available')->count();
        $maintenanceVehicles = $branch->vehicles()->where('status', 'maintenance')->count();
        $todayRevenue = $branch->bookings()->where('status', 'completed')->whereDate('created_at', today())->sum('total_price');
        $monthlyRevenue = $branch->bookings()->where('status', 'completed')->whereMonth('created_at', now()->month)->sum('total_price');

        $recentBookings = $branch->bookings()->with('vehicle', 'user')->latest()->take(5)->get();
        $staff = $branch->users()->whereIn('role', ['staff', 'branch_manager'])->get();

        return response()->json([
            'success' => true,
            'data' => [
                'today_bookings' => $todayBookings,
                'active_rentals' => $activeRentals,
                'pending_approvals' => $pendingApprovals,
                'available_vehicles' => $availableVehicles,
                'maintenance_vehicles' => $maintenanceVehicles,
                'today_revenue' => $todayRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'recent_bookings' => $recentBookings,
                'staff' => $staff->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'role' => $s->role]),
            ],
        ]);
    }
}
