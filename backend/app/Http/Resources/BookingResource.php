<?php

namespace App\Http\Resources;

use App\Services\BookingWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $workflow = app(BookingWorkflowService::class);
        $actor = $request->user();

        $this->resource->loadMissing(['payments', 'review', 'branch', 'user', 'vehicle']);

        $paidPayment = $this->payments
            ->where('status', 'paid')
            ->sortByDesc('id')
            ->first();

        $paymentVerification = $paidPayment?->verification_status
            ?? ($this->payment_status === 'paid' ? 'unverified' : null);

        $normalizedStatus = $this->normalizeStatus();

        $nextAction = match (true) {
            $this->payment_status === 'cash_pending' => 'CASH_VERIFICATION_REQUIRED',
            in_array($this->payment_status, ['failed', 'invalid'], true) => 'PAYMENT_FAILED',
            $normalizedStatus === 'pending_admin_approval'
                || $normalizedStatus === 'pending_branch_approval' => 'WAITING_MANUAL_APPROVAL',
            $normalizedStatus === 'payment_processing' => 'PAYMENT_PROCESSING',
            $normalizedStatus === 'payment_required' => 'PAYMENT_REQUIRED',
            $normalizedStatus === 'confirmed' => 'READY_FOR_PICKUP',
            $normalizedStatus === 'ready_for_pickup' => 'READY_FOR_PICKUP',
            $normalizedStatus === 'active' => 'ACTIVE',
            $normalizedStatus === 'completed' => 'COMPLETED',
            default => null,
        };

        return [
            'id' => $this->id,
            'booking_reference' => $this->booking_reference,
            'reference' => $this->booking_reference,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'customer' => new UserResource($this->whenLoaded('user')),
            'vehicle_id' => $this->vehicle_id,
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'pickup_location' => $this->pickup_location,
            'return_location' => $this->return_location,
            'pickup_date' => $this->pickup_date?->toISOString(),
            'return_date' => $this->return_date?->toISOString(),
            'pickup_at' => $this->pickup_date?->toISOString(),
            'return_at' => $this->return_date?->toISOString(),
            'number_of_days' => $this->number_of_days,
            'price_per_day' => (float) $this->price_per_day,
            'subtotal' => (float) $this->subtotal,
            'additional_charges' => (float) $this->additional_charges,
            'discount' => (float) $this->discount,
            'total_price' => (float) $this->total_price,
            'status' => $normalizedStatus,
            'booking_status' => $normalizedStatus,
            'payment_status' => $this->payment_status,
            'payment_verification' => $paymentVerification,
            'payment' => $paidPayment ? new PaymentResource($paidPayment) : (
                $this->payments->sortByDesc('id')->first()
                    ? new PaymentResource($this->payments->sortByDesc('id')->first())
                    : null
            ),
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch'),
            'branch_approval' => $this->branch_approval_status ?? 'pending',
            'branch_approval_status' => $this->branch_approval_status ?? 'pending',
            'admin_approval' => $this->admin_approval_status ?? 'not_required',
            'admin_approval_status' => $this->admin_approval_status ?? 'not_required',
            'admin_approval_required' => (bool) $this->admin_approval_required,
            'branch_approved_at' => $this->branch_approved_at?->toISOString(),
            'admin_approved_at' => $this->admin_approved_at?->toISOString(),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'rejection_reason' => $this->rejection_reason,
            'rejected_by_role' => $this->rejected_by_role,
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancellation_reason' => $this->cancellation_reason,
            'cancellation_source' => $this->cancellation_source,
            'identity_verification_status' => $this->identity_verification_status ?? 'unverified',
            'license_verification_status' => $this->license_verification_status ?? 'unverified',
            'picked_up_at' => $this->picked_up_at?->toISOString(),
            'returned_at' => $this->returned_at?->toISOString(),
            'pickup_mileage' => $this->pickup_mileage,
            'return_mileage' => $this->return_mileage,
            'pickup_fuel_level' => $this->pickup_fuel_level,
            'return_fuel_level' => $this->return_fuel_level,
            'has_review' => (bool) $this->review,
            'review' => $this->whenLoaded('review'),
            'notes' => $this->notes,
            'is_archived' => (bool) $this->is_archived,
            'archived_at' => $this->archived_at?->toISOString(),
            'archive_reason' => $this->archive_reason,
            'allowed_actions' => $workflow->allowedActions($this->resource, $actor),
            'timeline' => $workflow->buildTimeline($this->resource),
            'next_action' => $nextAction,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
