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
        $tomorrow = $today->copy()->addDay();
        $monthStart = $today->copy()->startOfMonth();
        $nextMonthStart = $monthStart->copy()->addMonth();
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

        $bookingStats = Booking::query()
            ->where('branch_id', $branchId)
            ->selectRaw(
                'SUM(CASE WHEN pickup_date >= ? AND pickup_date < ? AND status IN (?, ?) THEN 1 ELSE 0 END) AS todays_pickups, '
                . 'SUM(CASE WHEN return_date >= ? AND return_date < ? AND status IN (?, ?) THEN 1 ELSE 0 END) AS todays_returns, '
                . 'SUM(CASE WHEN status IN (?, ?, ?) AND branch_approval_status = ? THEN 1 ELSE 0 END) AS pending_approvals, '
                . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS confirmed_bookings, '
                . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS ready_for_pickup, '
                . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS active_rentals, '
                . 'SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) AS todays_bookings',
                [
                    $today,
                    $tomorrow,
                    ...$pickupStatuses,
                    $today,
                    $tomorrow,
                    ...$returnStatuses,
                    ...$pendingApprovalStatuses,
                    Booking::APPROVAL_PENDING,
                    Booking::STATUS_CONFIRMED,
                    Booking::STATUS_READY_FOR_PICKUP,
                    Booking::STATUS_ACTIVE,
                    $today,
                    $tomorrow,
                ],
            )
            ->first();

        $paymentStats = Payment::query()
            ->where('branch_id', $branchId)
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS pending_cash_payments, '
                . 'COALESCE(SUM(CASE WHEN status = ? AND paid_at >= ? AND paid_at < ? THEN amount ELSE 0 END), 0) AS monthly_revenue, '
                . 'COALESCE(SUM(CASE WHEN status = ? AND paid_at >= ? AND paid_at < ? THEN amount ELSE 0 END), 0) AS todays_revenue',
                [
                    Payment::STATUS_CASH_PENDING,
                    Payment::STATUS_PAID,
                    $monthStart,
                    $nextMonthStart,
                    Payment::STATUS_PAID,
                    $today,
                    $tomorrow,
                ],
            )
            ->first();

        $vehicleStats = Vehicle::query()
            ->where('branch_id', $branchId)
            ->selectRaw(
                'COUNT(*) AS total_vehicles, '
                . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS available_vehicles, '
                . 'SUM(CASE WHEN status IN (?, ?, ?, ?) THEN 1 ELSE 0 END) AS vehicles_requiring_attention, '
                . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS rented_vehicles, '
                . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS maintenance_vehicles',
                [
                    Vehicle::STATUS_AVAILABLE,
                    ...$attentionStatuses,
                    Vehicle::STATUS_RENTED,
                    Vehicle::STATUS_MAINTENANCE,
                ],
            )
            ->first();

        return [
            'branch' => $branch->load('manager'),
            'todays_pickups' => (int) ($bookingStats->todays_pickups ?? 0),
            'todays_returns' => (int) ($bookingStats->todays_returns ?? 0),
            'pending_approvals' => (int) ($bookingStats->pending_approvals ?? 0),
            'confirmed_bookings' => (int) ($bookingStats->confirmed_bookings ?? 0),
            'ready_for_pickup' => (int) ($bookingStats->ready_for_pickup ?? 0),
            'active_rentals' => (int) ($bookingStats->active_rentals ?? 0),
            'pending_cash_payments' => (int) ($paymentStats->pending_cash_payments ?? 0),
            'available_vehicles' => (int) ($vehicleStats->available_vehicles ?? 0),
            'vehicles_requiring_attention' => (int) ($vehicleStats->vehicles_requiring_attention ?? 0),
            'maintenance_requests' => MaintenanceRequest::where('branch_id', $branchId)
                ->whereIn('status', [
                    MaintenanceRequest::STATUS_REQUESTED,
                    MaintenanceRequest::STATUS_APPROVED,
                    MaintenanceRequest::STATUS_IN_PROGRESS,
                ])
                ->count(),
            'new_reviews' => $recentReviews,
            'total_vehicles' => (int) ($vehicleStats->total_vehicles ?? 0),
            'rented_vehicles' => (int) ($vehicleStats->rented_vehicles ?? 0),
            'maintenance_vehicles' => (int) ($vehicleStats->maintenance_vehicles ?? 0),
            'todays_bookings' => (int) ($bookingStats->todays_bookings ?? 0),
            'monthly_revenue' => (float) ($paymentStats->monthly_revenue ?? 0),
            'todays_revenue' => (float) ($paymentStats->todays_revenue ?? 0),
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
