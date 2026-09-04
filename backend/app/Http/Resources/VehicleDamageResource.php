<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleDamageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'booking_id' => $this->booking_id,
            'inspection_id' => $this->inspection_id,
            'reported_by' => $this->reported_by,
            'damage_type' => $this->damage_type,
            'description' => $this->description,
            'severity' => $this->severity,
            'location' => $this->location,
            'photos' => $this->photos,
            'estimated_repair_cost' => $this->estimated_repair_cost,
            'repair_status' => $this->repair_status,
            'reported_at' => $this->reported_at?->toISOString(),
            'repaired_at' => $this->repaired_at?->toISOString(),
            'notes' => $this->notes,
            'vehicle' => $this->whenLoaded('vehicle', fn () => [
                'id' => $this->vehicle->id,
                'brand' => $this->vehicle->brand,
                'model' => $this->vehicle->model,
                'registration_number' => $this->vehicle->registration_number,
                'branch' => $this->vehicle->branch,
            ]),
            'reporter' => $this->whenLoaded('reporter', fn () => [
                'id' => $this->reporter->id,
                'name' => $this->reporter->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
