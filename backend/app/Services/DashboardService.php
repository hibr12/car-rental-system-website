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
        $recentBookings = Booking::with(['user', 'vehicle'])->latest()->take(5)->get();
        $recentUsers = User::latest()->take(5)->get();
        $popularVehicles = Booking::select('vehicle_id', DB::raw('count(*) as bookings_count'))
            ->groupBy('vehicle_id')
            ->with('vehicle')
            ->orderByDesc('bookings_count')
            ->take(5)
            ->get();

        $summary = [
            'total_users' => User::count(),
            'total_customers' => User::where('role', 'customer')->count(),
            'total_vehicles' => Vehicle::count(),
            'available_vehicles' => Vehicle::where('status', 'available')->count(),
            'rented_vehicles' => Vehicle::where('status', 'rented')->count(),
            'vehicles_under_maintenance' => Vehicle::where('status', 'maintenance')->count(),
            'total_bookings' => Booking::count(),
            'pending_bookings' => Booking::where('status', 'pending')->count(),
            'active_rentals' => Booking::where('status', 'active')->count(),
            'completed_rentals' => Booking::where('status', 'completed')->count(),
            'cancelled_bookings' => Booking::where('status', 'cancelled')->count(),
            'total_revenue' => (float) Booking::sum('total_price'),
            'monthly_revenue' => (float) Booking::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total_price'),
            'pending_messages' => ContactMessage::where('status', 'pending')->count(),
            'maintenance_count' => Maintenance::count(),
        ];

        return [
            'summary' => $summary,
            'monthly_revenue' => $this->monthlyRevenue(),
            'booking_statuses' => $this->bookingStatusBreakdown(),
            'maintenance_costs' => $this->maintenanceCostBreakdown(),
            'revenue_summary' => $this->revenueSummary(),
            'recent_bookings' => $recentBookings,
            'recent_users' => $recentUsers,
            'popular_vehicles' => $popularVehicles,
            'report_sections' => [
                'overview',
                'revenue',
                'bookings',
                'maintenance',
                'activity',
            ],
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
        return [
            'total' => (float) Booking::sum('total_price'),
            'paid' => (float) Booking::where('payment_status', 'paid')->sum('total_price'),
            'pending' => (float) Booking::where('payment_status', 'pending')->sum('total_price'),
            'unpaid' => (float) Booking::where('payment_status', 'unpaid')->sum('total_price'),
        ];
    }
}
