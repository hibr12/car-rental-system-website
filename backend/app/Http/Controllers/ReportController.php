<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Maintenance;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Company-wide revenue report (admin only).
     */
    public function companyRevenue(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->subMonths(6)->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->endOfMonth()->toDateString());
        $fromStart = Carbon::parse($from)->startOfDay();
        $toExclusive = Carbon::parse($to)->addDay()->startOfDay();

        // One grouped query keeps zero-revenue branches while avoiding the
        // previous payment SUM query for every branch.
        $byBranch = Branch::query()
            ->leftJoin('payments', function ($join) use ($fromStart, $toExclusive) {
                $join->on('payments.branch_id', '=', 'branches.id')
                    ->where('payments.status', Payment::STATUS_PAID)
                    ->where('payments.paid_at', '>=', $fromStart)
                    ->where('payments.paid_at', '<', $toExclusive);
            })
            ->select(['branches.id', 'branches.name', 'branches.code'])
            ->selectRaw('COALESCE(SUM(payments.amount), 0) AS revenue')
            ->groupBy('branches.id', 'branches.name', 'branches.code')
            ->orderBy('branches.name')
            ->get()
            ->map(fn ($branch) => [
                'branch' => $branch->name,
                'code' => $branch->code,
                'revenue' => (float) $branch->revenue,
            ]);

        $total = $byBranch->sum('revenue');

        $driver = DB::connection()->getDriverName();
        $dateExpr = match ($driver) {
            'pgsql'  => "to_char(paid_at, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', paid_at)",
            default  => "DATE_FORMAT(paid_at, '%Y-%m')",
        };

        $monthly = Payment::select(DB::raw($dateExpr . ' as month'), DB::raw('SUM(amount) as revenue'))
            ->where('status', Payment::STATUS_PAID)
            ->where('paid_at', '>=', $fromStart)
            ->where('paid_at', '<', $toExclusive)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn($r) => ['month' => $r->month, 'revenue' => (float) $r->revenue]);

        return response()->json([
            'success' => true,
            'data'    => [
                'total_revenue'    => $total,
                'revenue_by_branch' => $byBranch,
                'monthly_trend'    => $monthly,
                'period'           => ['from' => $from, 'to' => $to],
            ],
        ]);
    }

    /**
     * Branch-level report — auto-scoped by user role.
     */
    public function branchReport(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $branchId = $request->input('branch_id');
        } else {
            $branchId = $user->branch_id;
        }

        if (!$branchId) {
            return response()->json(['success' => false, 'message' => 'Branch not specified.'], 422);
        }

        $from = $request->input('from', now()->subMonths(1)->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->endOfMonth()->toDateString());
        $fromStart = Carbon::parse($from)->startOfDay();
        $toExclusive = Carbon::parse($to)->addDay()->startOfDay();

        $branch = Branch::findOrFail($branchId);

        $driver = DB::connection()->getDriverName();
        $dateExpr = match ($driver) {
            'pgsql'  => "to_char(paid_at, 'YYYY-MM-DD')",
            'sqlite' => "strftime('%Y-%m-%d', paid_at)",
            default  => "DATE_FORMAT(paid_at, '%Y-%m-%d')",
        };

        $dailyRevenue = Payment::select(DB::raw($dateExpr . ' as day'), DB::raw('SUM(amount) as revenue'))
            ->where('branch_id', $branchId)
            ->where('status', Payment::STATUS_PAID)
            ->where('paid_at', '>=', $fromStart)
            ->where('paid_at', '<', $toExclusive)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn($r) => ['day' => $r->day, 'revenue' => (float) $r->revenue]);

        $bookingStats = Booking::where('branch_id', $branchId)
            ->where('created_at', '>=', $fromStart)
            ->where('created_at', '<', $toExclusive)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $vehicleUtilization = Vehicle::where('branch_id', $branchId)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $maintenanceCost = (float) Maintenance::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->where('service_date', '>=', $fromStart->toDateString())
            ->where('service_date', '<=', Carbon::parse($to)->toDateString())
            ->sum('cost');

        $topVehicles = Booking::select('vehicle_id', DB::raw('count(*) as bookings'))
            ->where('branch_id', $branchId)
            ->where('created_at', '>=', $fromStart)
            ->where('created_at', '<', $toExclusive)
            ->groupBy('vehicle_id')
            ->with('vehicle:id,brand,model,registration_number')
            ->orderByDesc('bookings')
            ->take(5)
            ->get()
            ->map(fn($b) => [
                'vehicle'  => $b->vehicle?->brand . ' ' . $b->vehicle?->model,
                'plate'    => $b->vehicle?->registration_number,
                'bookings' => $b->bookings,
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'branch'              => $branch->name,
                'period'              => ['from' => $from, 'to' => $to],
                'total_revenue'       => (float) $dailyRevenue->sum('revenue'),
                'daily_revenue'       => $dailyRevenue,
                'booking_stats'       => $bookingStats,
                'vehicle_utilization' => $vehicleUtilization,
                'maintenance_cost'    => $maintenanceCost,
                'top_vehicles'        => $topVehicles,
            ],
        ]);
    }

    /**
     * Fleet utilization report.
     */
    public function fleetUtilization(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Vehicle::query();

        if ($user->isBranchManager() && !$user->isAdmin()) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        $vehicles = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn($r) => [$r->status => $r->count]);

        $total = array_sum($vehicles->toArray());

        return response()->json([
            'success' => true,
            'data'    => [
                'total'         => $total,
                'by_status'     => $vehicles,
                'available_pct' => $total > 0 ? round(($vehicles->get('available', 0) / $total) * 100, 1) : 0,
                'rented_pct'    => $total > 0 ? round(($vehicles->get('rented', 0) / $total) * 100, 1) : 0,
                'maintenance_pct'=> $total > 0 ? round(($vehicles->get('maintenance', 0) / $total) * 100, 1) : 0,
            ],
        ]);
    }
}
