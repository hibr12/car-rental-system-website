<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_reference' => $this->booking_reference,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'vehicle_id' => $this->vehicle_id,
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'pickup_location' => $this->pickup_location,
            'return_location' => $this->return_location,
            'pickup_date' => $this->pickup_date->toISOString(),
            'return_date' => $this->return_date->toISOString(),
            'number_of_days' => $this->number_of_days,
            'price_per_day' => (float) $this->price_per_day,
            'subtotal' => (float) $this->subtotal,
            'additional_charges' => (float) $this->additional_charges,
            'discount' => (float) $this->discount,
            'total_price' => (float) $this->total_price,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch'),
            'branch_approval_status' => $this->branch_approval_status ?? 'pending',
            'admin_approval_status' => $this->admin_approval_status ?? 'pending',
            'branch_approved_at' => $this->branch_approved_at?->toISOString(),
            'admin_approved_at' => $this->admin_approved_at?->toISOString(),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'rejection_reason' => $this->rejection_reason,
            'rejected_by_role' => $this->rejected_by_role,
            'notes' => $this->notes,
            'is_archived' => (bool) $this->is_archived,
            'archived_at' => $this->archived_at?->toISOString(),
            'archive_reason' => $this->archive_reason,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}