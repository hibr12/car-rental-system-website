<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'booking' => new BookingResource($this->whenLoaded('booking')),
            'user_id' => $this->user_id,
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch'),
            'amount' => (float) $this->amount,
            'currency' => $this->currency ?? 'ETB',
            'payment_method' => $this->payment_method,
            'transaction_reference' => $this->transaction_reference,
            'gateway_reference' => $this->gateway_reference,
            'gateway' => $this->gateway,
            'gateway_status' => $this->gateway_status,
            'status' => $this->status,
            'verification_status' => $this->verification_status,
            'is_verified' => $this->isVerified(),
            'paid_at' => $this->paid_at?->toISOString(),
            'verified_at' => $this->verified_at?->toISOString(),
            'verification_source' => $this->verification_source,
            'failure_reason' => $this->failure_reason,
            'receipt_number' => $this->receipt_number,
            'confirmed_by' => $this->confirmed_by,
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'verified_by' => $this->verified_by,
            'is_archived' => (bool) $this->is_archived,
            'archived_at' => $this->archived_at?->toISOString(),
            'archive_reason' => $this->archive_reason,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}