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
            'branch_id' => ['sometimes', 'integer', 'exists:branches,id'],
            'images' => ['sometimes', 'array', 'max:10'],
            'images.*.image_url' => ['required', 'string', 'max:500'],
            'images.*.is_primary' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'The selected category does not exist.',
            'brand.required' => 'Brand is required.',
            'model.required' => 'Model is required.',
            'year.required' => 'Year is required.',
            'year.min' => 'Year must be 1900 or later.',
            'year.max' => 'Year cannot be in the future.',
            'registration_number.required' => 'Registration number is required.',
            'registration_number.unique' => 'A vehicle with this registration number already exists.',
            'vin_number.unique' => 'A vehicle with this VIN number already exists.',
            'vin_number.max' => 'VIN number must not exceed 17 characters.',
            'fuel_type.required' => 'Fuel type is required.',
            'fuel_type.in' => 'Fuel type must be one of: petrol, diesel, electric, hybrid.',
            'transmission.required' => 'Transmission is required.',
            'transmission.in' => 'Transmission must be one of: automatic, manual.',
            'seats.required' => 'Number of seats is required.',
            'seats.min' => 'Vehicle must have at least 1 seat.',
            'seats.max' => 'Vehicle cannot have more than 50 seats.',
            'rental_price_per_day.required' => 'Rental price per day is required.',
            'rental_price_per_day.min' => 'Rental price per day cannot be negative.',
            'images.max' => 'Vehicle cannot have more than 10 images.',
            'images.*.image_url.required' => 'Image URL is required.',
            'images.*.image_url.max' => 'Image URL must not exceed 500 characters.',
        ];
    }
}
