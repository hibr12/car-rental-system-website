<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Maintenance;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Models\VehicleInspection;
use App\Models\VehicleTransfer;
use Illuminate\Support\Facades\DB;

class FleetService
{
    public function dashboardStats(User $user, ?int $branchId = null): array
    {
        $vehicleQuery = Vehicle::query();

        if ($user->isBranchManager() && !$user->isAdmin()) {
            $vehicleQuery->where('branch_id', $user->branch_id);
        } elseif ($branchId) {
            $vehicleQuery->where('branch_id', $branchId);
        }

        $statusCounts = (clone $vehicleQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $total = (int) $statusCounts->sum();

        $transferQuery = VehicleTransfer::query()
            ->whereIn('status', [
                VehicleTransfer::STATUS_PENDING,
                VehicleTransfer::STATUS_APPROVED,
                VehicleTransfer::STATUS_IN_TRANSIT,
            ]);

        if ($user->isBranchManager() && !$user->isAdmin()) {
            $transferQuery->where(function ($q) use ($user) {
                $q->where('from_branch_id', $user->branch_id)
                    ->orWhere('to_branch_id', $user->branch_id);
            });
        } elseif ($branchId) {
            $transferQuery->where(function ($q) use ($branchId) {
                $q->where('from_branch_id', $branchId)
                    ->orWhere('to_branch_id', $branchId);
            });
        }

        $activeMaintenance = Maintenance::query()
            ->whereIn('status', ['scheduled', 'in_progress']);

        if ($user->isBranchManager() && !$user->isAdmin()) {
            $activeMaintenance->where('branch_id', $user->branch_id);
        } elseif ($branchId) {
            $activeMaintenance->where('branch_id', $branchId);
        }

        $activeRentals = Booking::query()
            ->where('status', Booking::STATUS_ACTIVE);

        if ($user->isBranchManager() && !$user->isAdmin()) {
            $activeRentals->where('branch_id', $user->branch_id);
        } elseif ($branchId) {
            $activeRentals->where('branch_id', $branchId);
        }

        $rentedCount = (int) ($statusCounts->get(Vehicle::STATUS_RENTED, 0));
        $utilization = $total > 0 ? round(($rentedCount / $total) * 100, 1) : 0;

        $inspectionRequired = (int) ($statusCounts->get(Vehicle::STATUS_INSPECTION_REQUIRED, 0))
            + (int) ($statusCounts->get(Vehicle::STATUS_RETURN_PENDING_INSPECTION, 0));

        $pendingInspections = VehicleInspection::query()
            ->whereIn('status', [VehicleInspection::STATUS_PENDING, VehicleInspection::STATUS_IN_PROGRESS]);

        if ($user->isBranchManager() && !$user->isAdmin()) {
            $pendingInspections->where('branch_id', $user->branch_id);
        } elseif ($branchId) {
            $pendingInspections->where('branch_id', $branchId);
        }

        $expiringDocuments = VehicleDocument::query()
            ->where('status', VehicleDocument::STATUS_EXPIRING_SOON);

        if ($user->isBranchManager() && !$user->isAdmin()) {
            $expiringDocuments->whereHas('vehicle', fn ($q) => $q->where('branch_id', $user->branch_id));
        } elseif ($branchId) {
            $expiringDocuments->whereHas('vehicle', fn ($q) => $q->where('branch_id', $branchId));
        }

        return [
            'total_vehicles' => $total,
            'available' => (int) ($statusCounts->get(Vehicle::STATUS_AVAILABLE, 0)),
            'reserved' => (int) ($statusCounts->get(Vehicle::STATUS_RESERVED, 0)),
            'ready_for_pickup' => (int) ($statusCounts->get(Vehicle::STATUS_READY_FOR_PICKUP, 0)),
            'active_rental' => (int) $activeRentals->count(),
            'rented' => $rentedCount,
            'maintenance' => (int) ($statusCounts->get(Vehicle::STATUS_MAINTENANCE, 0)),
            'out_of_service' => (int) ($statusCounts->get(Vehicle::STATUS_UNAVAILABLE, 0)),
            'inspection_required' => $inspectionRequired,
            'transfer_pending' => (int) $transferQuery->count(),
            'maintenance_active' => (int) $activeMaintenance->count(),
            'documents_expiring' => (int) $expiringDocuments->count(),
            'pending_inspections' => (int) $pendingInspections->count(),
            'utilization_pct' => $utilization,
            'by_status' => $statusCounts->toArray(),
        ];
    }
}
