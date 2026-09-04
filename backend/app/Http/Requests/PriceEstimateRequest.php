<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PriceEstimateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['required', 'date', 'after:pickup_date'],
            'additional_charges' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_id.required' => 'Vehicle ID is required.',
            'vehicle_id.exists' => 'The selected vehicle does not exist.',
            'pickup_date.required' => 'Pickup date is required.',
            'pickup_date.after_or_equal' => 'Pickup date must be today or later.',
            'return_date.required' => 'Return date is required.',
            'return_date.after' => 'Return date must be after pickup date.',
            'additional_charges.numeric' => 'Additional charges must be a number.',
            'additional_charges.min' => 'Additional charges cannot be negative.',
            'discount.numeric' => 'Discount must be a number.',
            'discount.min' => 'Discount cannot be negative.',
        ];
    }
}
