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
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'code' => $this->branch->code,
                'city' => $this->branch->city,
                'status' => $this->branch->status,
            ]),
            'active_transfer' => $this->when(
                $request->user() && !$request->user()->isCustomer(),
                fn () => $this->whenLoaded('activeTransfer', function () {
                    if (!$this->activeTransfer) {
                        return null;
                    }

                    return [
                        'id' => $this->activeTransfer->id,
                        'status' => $this->activeTransfer->status,
                        'from_branch_id' => $this->activeTransfer->from_branch_id,
                        'to_branch_id' => $this->activeTransfer->to_branch_id,
                        'transfer_date' => $this->activeTransfer->transfer_date?->toDateString(),
                        'from_branch' => $this->activeTransfer->relationLoaded('fromBranch') && $this->activeTransfer->fromBranch
                            ? ['id' => $this->activeTransfer->fromBranch->id, 'name' => $this->activeTransfer->fromBranch->name]
                            : null,
                        'to_branch' => $this->activeTransfer->relationLoaded('toBranch') && $this->activeTransfer->toBranch
                            ? ['id' => $this->activeTransfer->toBranch->id, 'name' => $this->activeTransfer->toBranch->name]
                            : null,
                    ];
                })
            ),
            'completed_transfers_count' => $this->when(
                $request->user() && !$request->user()->isCustomer(),
                fn () => $this->whenCounted('completedTransfers')
            ),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => VehicleImageResource::collection($this->whenLoaded('images')),
            'primary_image' => new VehicleImageResource($this->whenLoaded('primaryImage')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
