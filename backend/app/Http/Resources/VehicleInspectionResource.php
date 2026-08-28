<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleInspectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'booking_id' => $this->booking_id,
            'branch_id' => $this->branch_id,
            'inspector_id' => $this->inspector_id,
            'inspection_type' => $this->inspection_type,
            'inspected_at' => $this->inspected_at?->toISOString(),
            'mileage' => $this->mileage,
            'fuel_level' => $this->fuel_level,
            'exterior_condition' => $this->exterior_condition,
            'interior_condition' => $this->interior_condition,
            'tires_condition' => $this->tires_condition,
            'lights_condition' => $this->lights_condition,
            'brakes_condition' => $this->brakes_condition,
            'engine_indicators' => $this->engine_indicators,
            'has_damage' => $this->has_damage,
            'damage_notes' => $this->damage_notes,
            'photos' => $this->photos,
            'notes' => $this->notes,
            'result' => $this->result,
            'status' => $this->status,
            'vehicle' => $this->whenLoaded('vehicle', fn () => [
                'id' => $this->vehicle->id,
                'brand' => $this->vehicle->brand,
                'model' => $this->vehicle->model,
                'registration_number' => $this->vehicle->registration_number,
                'branch' => $this->vehicle->branch,
            ]),
            'booking' => $this->whenLoaded('booking'),
            'inspector' => $this->whenLoaded('inspector', fn () => [
                'id' => $this->inspector->id,
                'name' => $this->inspector->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
