<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vehicle = $this->route('vehicle');

        return [
            'category_id' => ['sometimes', 'exists:categories,id'],
            'brand' => ['sometimes', 'string', 'max:255'],
            'model' => ['sometimes', 'string', 'max:255'],
            'year' => ['sometimes', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'registration_number' => ['sometimes', 'string', 'max:50', 'unique:vehicles,registration_number,' . $vehicle->id],
            'vin_number' => ['nullable', 'string', 'max:17', 'unique:vehicles,vin_number,' . $vehicle->id],
            'description' => ['nullable', 'string', 'max:2000'],
            'fuel_type' => ['sometimes', 'string', 'in:petrol,diesel,electric,hybrid'],
            'transmission' => ['sometimes', 'string', 'in:automatic,manual'],
            'seats' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'color' => ['nullable', 'string', 'max:50'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'rental_price_per_day' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', 'in:available,reserved,ready_for_pickup,rented,return_pending_inspection,inspection_required,maintenance,unavailable,transfer_pending,transfer_in_transit,transferred,retired'],
            'condition' => ['sometimes', 'string', 'in:excellent,good,fair,poor'],
            'mileage_correction' => ['sometimes', 'boolean'],
            'featured' => ['sometimes', 'boolean'],
            'location' => ['nullable', 'string', 'max:255'],
            'images' => ['sometimes', 'array', 'max:10'],
            'images.*.image_url' => ['required', 'string', 'max:500'],
            'images.*.is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
