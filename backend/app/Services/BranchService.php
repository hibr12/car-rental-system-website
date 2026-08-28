<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Maintenance;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\MaintenanceRequestSubmitted;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

class BranchService
{
    public function resolveBranch(User $user, ?int $branchId = null): Branch
    {
        if ($user->isAdmin() && $branchId) {
            return Branch::findOrFail($branchId);
        }

        if (!$user->branch_id) {
            throw new \InvalidArgumentException('You are not assigned to a branch.');
        }

        return Branch::findOrFail($user->branch_id);
    }

    public function dashboardStats(Branch $branch): array
    {
        $today = Carbon::today();
        $branchId = $branch->id;

        $pickupStatuses = [
            Booking::STATUS_CONFIRMED,
            Booking::STATUS_READY_FOR_PICKUP,
        ];

        $returnStatuses = [
            Booking::STATUS_ACTIVE,
            Booking::STATUS_RETURN_PENDING,
        ];

        $pendingApprovalStatuses = [
            Booking::STATUS_PENDING_BRANCH_APPROVAL,
            Booking::STATUS_BRANCH_REVIEW,
            Booking::STATUS_PENDING,
        ];

        $attentionStatuses = [
            Vehicle::STATUS_MAINTENANCE,
            Vehicle::STATUS_INSPECTION_REQUIRED,
            Vehicle::STATUS_RETURN_PENDING_INSPECTION,
            Vehicle::STATUS_UNAVAILABLE,
        ];

        $recentReviews = Review::query()
            ->where('branch_id', $branchId)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return [
            'branch' => $branch->load('manager'),
            'todays_pickups' => Booking::where('branch_id', $branchId)
                ->whereDate('pickup_date', $today)
                ->whereIn('status', $pickupStatuses)
                ->count(),
            'todays_returns' => Booking::where('branch_id', $branchId)
                ->whereDate('return_date', $today)
                ->whereIn('status', $returnStatuses)
                ->count(),
            'pending_approvals' => Booking::where('branch_id', $branchId)
                ->whereIn('status', $pendingApprovalStatuses)
                ->where('branch_approval_status', Booking::APPROVAL_PENDING)
                ->count(),
            'confirmed_bookings' => Booking::where('branch_id', $branchId)
                ->where('status', Booking::STATUS_CONFIRMED)
                ->count(),
            'ready_for_pickup' => Booking::where('branch_id', $branchId)
                ->where('status', Booking::STATUS_READY_FOR_PICKUP)
                ->count(),
            'active_rentals' => Booking::where('branch_id', $branchId)
                ->where('status', Booking::STATUS_ACTIVE)
                ->count(),
            'pending_cash_payments' => Payment::where('branch_id', $branchId)
                ->where('status', Payment::STATUS_CASH_PENDING)
                ->count(),
            'available_vehicles' => Vehicle::where('branch_id', $branchId)
                ->where('status', Vehicle::STATUS_AVAILABLE)
                ->count(),
            'vehicles_requiring_attention' => Vehicle::where('branch_id', $branchId)
                ->whereIn('status', $attentionStatuses)
                ->count(),
            'maintenance_requests' => MaintenanceRequest::where('branch_id', $branchId)
                ->whereIn('status', [
                    MaintenanceRequest::STATUS_REQUESTED,
                    MaintenanceRequest::STATUS_APPROVED,
                    MaintenanceRequest::STATUS_IN_PROGRESS,
                ])
                ->count(),
            'new_reviews' => $recentReviews,
            'total_vehicles' => Vehicle::where('branch_id', $branchId)->count(),
            'rented_vehicles' => Vehicle::where('branch_id', $branchId)
                ->where('status', Vehicle::STATUS_RENTED)
                ->count(),
            'maintenance_vehicles' => Vehicle::where('branch_id', $branchId)
                ->where('status', Vehicle::STATUS_MAINTENANCE)
                ->count(),
            'todays_bookings' => Booking::where('branch_id', $branchId)
                ->whereDate('created_at', $today)
                ->count(),
            'monthly_revenue' => (float) Payment::where('branch_id', $branchId)
                ->where('status', Payment::STATUS_PAID)
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount'),
            'todays_revenue' => (float) Payment::where('branch_id', $branchId)
                ->where('status', Payment::STATUS_PAID)
                ->whereDate('paid_at', $today)
                ->sum('amount'),
            'recent_bookings' => Booking::with(['user', 'vehicle'])
                ->where('branch_id', $branchId)
                ->orderByDesc('created_at')
                ->take(8)
                ->get(),
        ];
    }

    public function branchCustomers(Branch $branch, int $perPage = 15)
    {
        return User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->whereHas('bookings', fn ($q) => $q->where('branch_id', $branch->id))
            ->withCount(['bookings as branch_bookings_count' => fn ($q) => $q->where('branch_id', $branch->id)])
            ->with(['bookings' => fn ($q) => $q->where('branch_id', $branch->id)->latest()->limit(1)])
            ->orderByDesc('branch_bookings_count')
            ->paginate($perPage);
    }
}
