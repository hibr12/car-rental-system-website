<?php

namespace App\Http\Resources;

use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $actor = $request->user();
        $expected = (float) ($this->expected_amount ?? $this->amount);
        $paid = $this->paid_amount !== null ? (float) $this->paid_amount : (
            $this->isSettled() ? $expected : null
        );

        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'booking_reference' => $this->when(
                $this->relationLoaded('booking'),
                fn () => $this->booking?->booking_reference
            ),
            'booking' => $this->when($this->relationLoaded('booking') && $this->booking, function () {
                $b = $this->booking;
                return [
                    'id' => $b->id,
                    'booking_reference' => $b->booking_reference,
                    'status' => method_exists($b, 'normalizeStatus') ? $b->normalizeStatus() : $b->status,
                    'payment_status' => $b->payment_status,
                    'total_price' => (float) $b->total_price,
                    'user' => $b->relationLoaded('user') && $b->user ? [
                        'id' => $b->user->id,
                        'name' => $b->user->name,
                        'email' => $b->user->email,
                    ] : null,
                    'vehicle' => $b->relationLoaded('vehicle') && $b->vehicle ? [
                        'id' => $b->vehicle->id,
                        'brand' => $b->vehicle->brand,
                        'model' => $b->vehicle->model,
                    ] : null,
                ];
            }),
            'user_id' => $this->user_id,
            'customer' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch'),
            'attempt_number' => $this->attempt_number,
            'amount' => $expected,
            'expected_amount' => $expected,
            'paid_amount' => $paid,
            'amount_received' => $this->amount_received !== null ? (float) $this->amount_received : null,
            'currency' => $this->currency ?? 'ETB',
            'payment_method' => $this->payment_method,
            'method' => $this->payment_method,
            'transaction_reference' => $this->transaction_reference,
            'tx_ref' => $this->transaction_reference,
            'gateway_reference' => $this->gateway_reference,
            'gateway_transaction_id' => $this->gateway_reference,
            'gateway' => $this->gateway,
            'gateway_status' => $this->gateway_status,
            'status' => $this->status,
            'verification_status' => $this->verification_status,
            'is_verified' => $this->isVerified(),
            'is_mismatch' => $this->isMismatch(),
            'paid_at' => $this->paid_at?->toISOString(),
            'verified_at' => $this->verified_at?->toISOString(),
            'verification_source' => $this->verification_source,
            'failure_reason' => $this->failure_reason,
            'mismatch_reason' => $this->mismatch_reason,
            'receipt_number' => $this->receipt_number,
            'confirmed_by' => $this->confirmed_by,
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'confirmer' => $this->whenLoaded('confirmer', fn () => [
                'id' => $this->confirmer->id,
                'name' => $this->confirmer->name,
                'role' => $this->confirmer->role,
            ]),
            'verified_by' => $this->verified_by,
            'refund_amount' => $this->refund_amount !== null ? (float) $this->refund_amount : null,
            'refunded_at' => $this->refunded_at?->toISOString(),
            'is_archived' => (bool) $this->is_archived,
            'archived_at' => $this->archived_at?->toISOString(),
            'archive_reason' => $this->archive_reason,
            'allowed_actions' => app(PaymentService::class)->allowedActions($this->resource, $actor),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
