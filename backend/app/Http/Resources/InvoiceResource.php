<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'booking_id' => $this->booking_id,
            'user_id' => $this->user_id,
            'payment_id' => $this->payment_id,
            'subtotal' => (float) $this->subtotal,
            'additional_charges' => (float) $this->additional_charges,
            'discount' => (float) $this->discount,
            'tax_amount' => (float) $this->tax_amount,
            'total_amount' => (float) $this->total_amount,
            'status' => $this->status,
            'issued_at' => $this->issued_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'notes' => $this->notes,
            'user' => new UserResource($this->whenLoaded('user')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
