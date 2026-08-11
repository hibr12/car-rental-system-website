<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RentalController extends Controller
{
    /**
     * List active/completed rentals scoped to branch or customer.
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Booking::with(['vehicle.category', 'vehicle.primaryImage', 'user', 'branch'])
            ->whereIn('status', ['confirmed', 'active', 'completed']);

        if ($user->isCustomer()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isBranchManager() || $user->isStaff()) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $rentals = $query->orderByDesc('updated_at')->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $rentals->items(),
            'meta'    => [
                'current_page' => $rentals->currentPage(),
                'last_page'    => $rentals->lastPage(),
                'total'        => $rentals->total(),
            ],
        ]);
    }

    /**
     * Perform vehicle check-out (hand vehicle to customer).
     */
    public function checkOut(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();

        $this->enforceBranchAccess($user, $booking);

        if ($booking->status !== Booking::STATUS_CONFIRMED) {
            return response()->json(['success' => false, 'message' => 'Only confirmed bookings can be checked out.'], 422);
        }

        if ($booking->payment_status !== Booking::PAYMENT_STATUS_PAID) {
            return response()->json(['success' => false, 'message' => 'Payment must be completed before check-out.'], 422);
        }

        $data = $request->validate([
            'start_mileage'      => ['required', 'integer', 'min:0'],
            'start_fuel_level'   => ['required', 'string', 'in:empty,quarter,half,three_quarter,full'],
            'exterior_condition' => ['required', 'string', 'in:excellent,good,fair,poor'],
            'interior_condition' => ['required', 'string', 'in:excellent,good,fair,poor'],
            'existing_damage'    => ['nullable', 'string', 'max:2000'],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($booking, $data, $user) {
            $checkoutMeta = array_merge($data, [
                'checked_out_by' => $user->id,
                'checkout_time'  => now()->toIso8601String(),
            ]);

            $booking->update([
                'status' => Booking::STATUS_ACTIVE,
                'notes'  => $booking->notes
                    ? $booking->notes . "\n[CHECKOUT] " . json_encode($checkoutMeta)
                    : '[CHECKOUT] ' . json_encode($checkoutMeta),
            ]);

            $booking->vehicle->update([
                'status'  => 'rented',
                'mileage' => $data['start_mileage'],
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Vehicle checked out successfully. Rental is now active.',
            'data'    => $booking->fresh()->load(['vehicle', 'user']),
        ]);
    }

    /**
     * Perform vehicle check-in (vehicle returned by customer).
     */
    public function checkIn(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();

        $this->enforceBranchAccess($user, $booking);

        if ($booking->status !== Booking::STATUS_ACTIVE) {
            return response()->json(['success' => false, 'message' => 'Only active rentals can be checked in.'], 422);
        }

        $data = $request->validate([
            'end_mileage'        => ['required', 'integer', 'min:0'],
            'end_fuel_level'     => ['required', 'string', 'in:empty,quarter,half,three_quarter,full'],
            'exterior_condition' => ['required', 'string', 'in:excellent,good,fair,poor'],
            'interior_condition' => ['required', 'string', 'in:excellent,good,fair,poor'],
            'new_damage'         => ['nullable', 'string', 'max:2000'],
            'additional_charges' => ['nullable', 'numeric', 'min:0'],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($booking, $data, $user) {
            $checkinMeta = array_merge($data, [
                'checked_in_by' => $user->id,
                'checkin_time'  => now()->toIso8601String(),
            ]);

            $additionalCharges = (float) ($data['additional_charges'] ?? 0);
            $newTotal = (float) $booking->total_price + $additionalCharges;

            $booking->update([
                'status'             => Booking::STATUS_COMPLETED,
                'payment_status'     => Booking::PAYMENT_STATUS_PAID,
                'additional_charges' => $booking->additional_charges + $additionalCharges,
                'total_price'        => $newTotal,
                'notes'              => $booking->notes
                    ? $booking->notes . "\n[CHECKIN] " . json_encode($checkinMeta)
                    : '[CHECKIN] ' . json_encode($checkinMeta),
            ]);

            $booking->vehicle->update([
                'status'  => 'available',
                'mileage' => $data['end_mileage'],
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Vehicle checked in successfully. Rental completed.',
            'data'    => $booking->fresh()->load(['vehicle', 'user']),
        ]);
    }

    private function enforceBranchAccess($user, Booking $booking): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($user->branch_id !== $booking->branch_id) {
            abort(403, 'You do not have access to this booking.');
        }
    }
}
