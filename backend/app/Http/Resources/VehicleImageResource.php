<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'image_url' => $this->image_url,
            'is_primary' => $this->is_primary,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
