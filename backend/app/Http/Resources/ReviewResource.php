<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isAdmin = $user && ($user->isAdmin() || $user->isBranchManager());
        $isOwner = $user && $user->id === $this->user_id;

        return [
            'id' => $this->id,
            'user' => $this->when($isAdmin || $isOwner, fn () => new UserResource($this->whenLoaded('user'))),
            'customer' => $this->when($isAdmin, fn () => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ])),
            'customer_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'booking' => $this->whenLoaded('booking', fn () => [
                'id' => $this->booking->id,
                'booking_reference' => $this->booking->booking_reference,
                'status' => $this->booking->status,
                'pickup_date' => $this->booking->pickup_date?->toISOString(),
                'return_date' => $this->booking->return_date?->toISOString(),
                'returned_at' => $this->booking->returned_at?->toISOString(),
                'picked_up_at' => $this->booking->picked_up_at?->toISOString(),
            ]),
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'code' => $this->branch->code,
            ]),
            'vehicle_id' => $this->vehicle_id,
            'booking_id' => $this->booking_id,
            'branch_id' => $this->branch_id,
            'overall_rating' => $this->overall_rating,
            'rating' => $this->overall_rating,
            'vehicle_rating' => $this->vehicle_rating,
            'cleanliness_rating' => $this->cleanliness_rating,
            'staff_rating' => $this->staff_rating,
            'value_rating' => $this->value_rating,
            'comment' => $this->comment,
            'status' => $this->status,
            'verified_rental' => $this->isVerifiedRental(),
            'is_editable' => $isOwner ? $this->isEditableByCustomer() : false,
            'admin_response' => $this->admin_response,
            'admin_response_at' => $this->admin_response_at?->toISOString(),
            'admin_responder' => $this->whenLoaded('adminResponder', fn () => $this->adminResponder ? [
                'id' => $this->adminResponder->id,
                'name' => $this->adminResponder->name,
            ] : null),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
