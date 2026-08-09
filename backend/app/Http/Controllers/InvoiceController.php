<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!Gate::allows('viewAny', Invoice::class)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $query = Invoice::with(['booking', 'user', 'payment']);

        if ($request->user()->isCustomer()) {
            $query->where('user_id', $request->user()->id);
        } elseif ($request->user()->isBranchManager()) {
            $query->whereHas('booking', fn($q) => $q->where('branch_id', $request->user()->branch_id));
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        if (!Gate::allows('view', $invoice)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $invoice->load(['booking.vehicle', 'user', 'payment']),
        ]);
    }

    public function generate(Request $request, int $bookingId): JsonResponse
    {
        $booking = Booking::with(['vehicle', 'user', 'payments'])->findOrFail($bookingId);

        if ($booking->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Invoice can only be generated for paid bookings.',
            ], 422);
        }

        if ($booking->invoice) {
            return response()->json([
                'success' => true,
                'message' => 'Invoice already exists for this booking.',
                'data' => $booking->invoice,
            ]);
        }

        $payment = $booking->payments()->where('status', 'paid')->first();

        $invoice = Invoice::create([
            'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'payment_id' => $payment?->id,
            'subtotal' => $booking->subtotal,
            'additional_charges' => $booking->additional_charges,
            'discount' => $booking->discount,
            'tax_amount' => 0,
            'total_amount' => $booking->total_price,
            'status' => 'paid',
            'issued_at' => now(),
            'paid_at' => $payment?->paid_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invoice generated successfully.',
            'data' => $invoice->load(['booking.vehicle', 'user', 'payment']),
        ], 201);
    }
}
