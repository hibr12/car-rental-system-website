<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'booking' => $this->whenLoaded('booking', fn () => [
                'id' => $this->booking->id,
                'booking_reference' => $this->booking->booking_reference,
                'status' => $this->booking->status,
            ]),
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'code' => $this->branch->code,
            ]),
            'vehicle_id' => $this->vehicle_id,
            'booking_id' => $this->booking_id,
            'branch_id' => $this->branch_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'status' => $this->status,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
