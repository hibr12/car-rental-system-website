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
            'notes' => $this->notes,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}