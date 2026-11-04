<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ContactMessage;
use App\Models\Maintenance;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getDashboardStats(): array
    {
        $monthStart = now()->startOfMonth();
        $nextMonthStart = $monthStart->copy()->addMonth();

        // Keep the dashboard fresh while avoiding one database round trip per
        // card. Each table is scanned once and all of its counters are derived
        // with conditional aggregates.
        $userStats = User::query()
            ->selectRaw(
                'COUNT(*) AS total_users, '
                . 'SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) AS total_customers',
                [User::ROLE_CUSTOMER],
            )
            ->first();

        $vehicleStats = Vehicle::query()
            ->selectRaw(
                'COUNT(*) AS total_vehicles, '
                . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS available_vehicles, '
                . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS rented_vehicles, '
                . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS vehicles_under_maintenance',
                [
                    Vehicle::STATUS_AVAILABLE,
                    Vehicle::STATUS_RENTED,
                    Vehicle::STATUS_MAINTENANCE,
                ],
            )
            ->first();

        $bookingStats = Booking::query()
            ->selectRaw(
                'COUNT(*) AS total_bookings, '
                . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS pending_bookings, '
                . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS active_rentals, '
                . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS completed_rentals, '
                . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS cancelled_bookings, '
                . 'COALESCE(SUM(total_price), 0) AS total_revenue, '
                . 'COALESCE(SUM(CASE WHEN created_at >= ? AND created_at < ? THEN total_price ELSE 0 END), 0) AS monthly_revenue, '
                . 'COALESCE(SUM(CASE WHEN payment_status = ? THEN total_price ELSE 0 END), 0) AS paid_revenue, '
                . 'COALESCE(SUM(CASE WHEN payment_status = ? THEN total_price ELSE 0 END), 0) AS pending_revenue, '
                . 'COALESCE(SUM(CASE WHEN payment_status = ? THEN total_price ELSE 0 END), 0) AS unpaid_revenue',
                [
                    Booking::STATUS_PENDING,
                    Booking::STATUS_ACTIVE,
                    Booking::STATUS_COMPLETED,
                    Booking::STATUS_CANCELLED,
                    $monthStart,
                    $nextMonthStart,
                    Booking::PAYMENT_STATUS_PAID,
                    Booking::PAYMENT_STATUS_PENDING,
                    Booking::PAYMENT_STATUS_UNPAID,
                ],
            )
            ->first();

        $recentBookings = Booking::with(['user', 'vehicle'])->latest()->take(5)->get();
        $recentUsers = User::latest()->take(5)->get();
        $popularVehicles = Booking::select('vehicle_id', DB::raw('count(*) as bookings_count'))
            ->groupBy('vehicle_id')
            ->with('vehicle')
            ->orderByDesc('bookings_count')
            ->take(5)
            ->get();

        return [
            'summary' => [
                'total_users' => (int) ($userStats->total_users ?? 0),
                'total_customers' => (int) ($userStats->total_customers ?? 0),
                'total_vehicles' => (int) ($vehicleStats->total_vehicles ?? 0),
                'available_vehicles' => (int) ($vehicleStats->available_vehicles ?? 0),
                'rented_vehicles' => (int) ($vehicleStats->rented_vehicles ?? 0),
                'vehicles_under_maintenance' => (int) ($vehicleStats->vehicles_under_maintenance ?? 0),
                'total_bookings' => (int) ($bookingStats->total_bookings ?? 0),
                'pending_bookings' => (int) ($bookingStats->pending_bookings ?? 0),
                'active_rentals' => (int) ($bookingStats->active_rentals ?? 0),
                'completed_rentals' => (int) ($bookingStats->completed_rentals ?? 0),
                'cancelled_bookings' => (int) ($bookingStats->cancelled_bookings ?? 0),
                'total_revenue' => (float) ($bookingStats->total_revenue ?? 0),
                'monthly_revenue' => (float) ($bookingStats->monthly_revenue ?? 0),
                'pending_messages' => ContactMessage::where('status', 'pending')->count(),
                'maintenance_count' => Maintenance::count(),
            ],
            'monthly_revenue' => $this->monthlyRevenue(),
            'booking_statuses' => $this->bookingStatusBreakdown(),
            'maintenance_costs' => $this->maintenanceCostBreakdown(),
            'revenue_summary' => [
                'total' => (float) ($bookingStats->total_revenue ?? 0),
                'paid' => (float) ($bookingStats->paid_revenue ?? 0),
                'pending' => (float) ($bookingStats->pending_revenue ?? 0),
                'unpaid' => (float) ($bookingStats->unpaid_revenue ?? 0),
            ],
            'recent_bookings' => $recentBookings,
            'recent_users' => $recentUsers,
            'popular_vehicles' => $popularVehicles,
        ];
    }

    public function monthlyRevenue(): array
    {
        $driver = DB::connection()->getDriverName();
        $dateExpression = match ($driver) {
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', created_at)",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        return Booking::select(DB::raw($dateExpression . ' as month'), DB::raw('SUM(total_price) as revenue'))
            ->groupBy('month')
            ->orderBy('month')
            ->take(12)
            ->get()
            ->map(fn ($item) => [
                'month' => $item->month,
                'revenue' => (float) $item->revenue,
            ])
            ->toArray();
    }

    public function bookingStatusBreakdown(): array
    {
        return Booking::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(fn ($item) => [
                'status' => $item->status,
                'count' => (int) $item->count,
            ])
            ->toArray();
    }

    public function maintenanceCostBreakdown(): array
    {
        return Maintenance::select('status', DB::raw('SUM(cost) as total_cost'))
            ->groupBy('status')
            ->get()
            ->map(fn ($item) => [
                'status' => $item->status,
                'total_cost' => (float) $item->total_cost,
            ])
            ->toArray();
    }

    public function revenueSummary(): array
    {
        $stats = Booking::query()
            ->selectRaw(
                'COALESCE(SUM(total_price), 0) AS total, '
                . 'COALESCE(SUM(CASE WHEN payment_status = ? THEN total_price ELSE 0 END), 0) AS paid, '
                . 'COALESCE(SUM(CASE WHEN payment_status = ? THEN total_price ELSE 0 END), 0) AS pending, '
                . 'COALESCE(SUM(CASE WHEN payment_status = ? THEN total_price ELSE 0 END), 0) AS unpaid',
                [
                    Booking::PAYMENT_STATUS_PAID,
                    Booking::PAYMENT_STATUS_PENDING,
                    Booking::PAYMENT_STATUS_UNPAID,
                ],
            )
            ->first();

        return [
            'total' => (float) ($stats->total ?? 0),
            'paid' => (float) ($stats->paid ?? 0),
            'pending' => (float) ($stats->pending ?? 0),
            'unpaid' => (float) ($stats->unpaid ?? 0),
        ];
    }
}
