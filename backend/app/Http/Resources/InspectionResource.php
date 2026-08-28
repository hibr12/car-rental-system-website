<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InspectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'vehicle_id' => $this->vehicle_id,
            'inspected_by' => $this->inspected_by,
            'inspection_type' => $this->inspection_type,
            'mileage_at_inspection' => $this->mileage_at_inspection ? (float) $this->mileage_at_inspection : null,
            'fuel_level_full' => $this->fuel_level_full,
            'has_damage' => $this->has_damage,
            'damage_description' => $this->damage_description,
            'notes' => $this->notes,
            'condition_rating' => $this->condition_rating,
            'inspected_at' => $this->inspected_at->toISOString(),
            'inspector' => new UserResource($this->whenLoaded('inspector')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
