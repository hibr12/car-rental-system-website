<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => $this->year,
            'registration_number' => $this->registration_number,
            'vin_number' => $this->when($request->user()?->isAdmin() || $request->user()?->isFleetManager(), $this->vin_number),
            'description' => $this->description,
            'fuel_type' => $this->fuel_type,
            'transmission' => $this->transmission,
            'seats' => $this->seats,
            'color' => $this->color,
            'mileage' => $this->mileage,
            'purchase_price' => $this->when($request->user()?->isAdmin(), $this->purchase_price),
            'rental_price_per_day' => $this->rental_price_per_day,
            'status' => $this->status,
            'featured' => $this->featured,
            'location' => $this->location,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => VehicleImageResource::collection($this->whenLoaded('images')),
            'primary_image' => new VehicleImageResource($this->whenLoaded('primaryImage')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
