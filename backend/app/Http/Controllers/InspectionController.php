<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Inspection;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InspectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!Gate::allows('viewAny', Inspection::class)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $query = Inspection::with(['booking', 'vehicle', 'inspector']);

        if ($request->user()->isBranchManager() || $request->user()->isStaff()) {
            $query->whereHas('vehicle', fn($q) => $q->where('branch_id', $request->user()->branch_id));
        }

        if ($request->has('booking_id')) {
            $query->where('booking_id', $request->booking_id);
        }

        if ($request->has('inspection_type')) {
            $query->where('inspection_type', $request->inspection_type);
        }

        $inspections = $query->latest('inspected_at')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $inspections,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!Gate::allows('create', Inspection::class)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'inspection_type' => 'required|in:pickup,return',
            'mileage_at_inspection' => 'nullable|numeric|min:0',
            'fuel_level_full' => 'boolean',
            'has_damage' => 'boolean',
            'damage_description' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'condition_rating' => 'nullable|in:excellent,good,fair,poor',
        ]);

        $booking = Booking::findOrFail($request->booking_id);

        $existingInspection = Inspection::where('booking_id', $booking->id)
            ->where('inspection_type', $request->inspection_type)
            ->exists();

        if ($existingInspection) {
            return response()->json([
                'success' => false,
                'message' => ucfirst($request->inspection_type) . ' inspection already exists for this booking.',
            ], 422);
        }

        $inspection = Inspection::create([
            'booking_id' => $request->booking_id,
            'vehicle_id' => $request->vehicle_id,
            'inspected_by' => $request->user()->id,
            'inspection_type' => $request->inspection_type,
            'mileage_at_inspection' => $request->mileage_at_inspection,
            'fuel_level_full' => $request->boolean('fuel_level_full', true),
            'has_damage' => $request->boolean('has_damage', false),
            'damage_description' => $request->damage_description,
            'notes' => $request->notes,
            'condition_rating' => $request->condition_rating,
            'inspected_at' => now(),
        ]);

        if ($request->inspection_type === 'pickup') {
            $booking->update(['status' => 'active']);
            $booking->vehicle->update(['status' => 'rented']);
        }

        if ($request->inspection_type === 'return') {
            $booking->update(['status' => 'completed']);
            $booking->vehicle->update(['status' => 'available']);
        }

        return response()->json([
            'success' => true,
            'message' => ucfirst($request->inspection_type) . ' inspection completed successfully.',
            'data' => $inspection->load(['booking', 'vehicle', 'inspector']),
        ], 201);
    }

    public function show(Inspection $inspection): JsonResponse
    {
        if (!Gate::allows('view', $inspection)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $inspection->load(['booking', 'vehicle', 'inspector']),
        ]);
    }
}
