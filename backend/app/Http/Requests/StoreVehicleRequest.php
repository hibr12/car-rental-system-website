<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'registration_number' => ['required', 'string', 'max:50', 'unique:vehicles'],
            'vin_number' => ['nullable', 'string', 'max:17', 'unique:vehicles'],
            'description' => ['nullable', 'string', 'max:2000'],
            'fuel_type' => ['required', 'string', 'in:petrol,diesel,electric,hybrid'],
            'transmission' => ['required', 'string', 'in:automatic,manual'],
            'seats' => ['required', 'integer', 'min:1', 'max:50'],
            'color' => ['nullable', 'string', 'max:50'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'rental_price_per_day' => ['required', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', 'in:available,rented,reserved,maintenance,unavailable'],
            'featured' => ['sometimes', 'boolean'],
            'location' => ['nullable', 'string', 'max:255'],
            'images' => ['sometimes', 'array', 'max:10'],
            'images.*.image_url' => ['required', 'string', 'max:500'],
            'images.*.is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
