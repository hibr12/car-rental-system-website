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
        $user = $this->user();
        $isAdminOrFleet = $user && in_array($user->role, [
            \App\Models\User::ROLE_COMPANY_ADMIN,
            \App\Models\User::ROLE_FLEET_MANAGER,
            \App\Models\User::ROLE_SUPER_ADMIN,
        ]);

        $branchIdRule = $isAdminOrFleet
            ? ['sometimes', 'required', 'exists:branches,id']
            : ['sometimes', 'nullable', 'exists:branches,id'];

        return [
            'category_id' => ['sometimes', 'exists:categories,id'],
            'branch_id' => $branchIdRule,
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

    public function messages(): array
    {
        return [
            'category_id.exists' => 'The selected category does not exist.',
            'branch_id.required' => 'Branch is required for this vehicle.',
            'branch_id.exists' => 'The selected branch does not exist.',
            'brand.string' => 'Brand must be text.',
            'brand.max' => 'Brand must not exceed 255 characters.',
            'model.string' => 'Model must be text.',
            'model.max' => 'Model must not exceed 255 characters.',
            'year.integer' => 'Year must be a whole number.',
            'year.min' => 'Year must be 1900 or later.',
            'year.max' => 'Year cannot be in the future.',
            'registration_number.string' => 'Registration number must be text.',
            'registration_number.max' => 'Registration number must not exceed 50 characters.',
            'registration_number.unique' => 'A vehicle with this registration number already exists.',
            'vin_number.string' => 'VIN number must be text.',
            'vin_number.max' => 'VIN number must not exceed 17 characters.',
            'vin_number.unique' => 'A vehicle with this VIN number already exists.',
            'description.string' => 'Description must be text.',
            'description.max' => 'Description must not exceed 2000 characters.',
            'fuel_type.string' => 'Fuel type must be text.',
            'fuel_type.in' => 'Fuel type must be one of: petrol, diesel, electric, hybrid.',
            'transmission.string' => 'Transmission must be text.',
            'transmission.in' => 'Transmission must be one of: automatic, manual.',
            'seats.integer' => 'Number of seats must be a whole number.',
            'seats.min' => 'Vehicle must have at least 1 seat.',
            'seats.max' => 'Vehicle cannot have more than 50 seats.',
            'color.string' => 'Color must be text.',
            'color.max' => 'Color must not exceed 50 characters.',
            'mileage.integer' => 'Mileage must be a whole number.',
            'mileage.min' => 'Mileage cannot be negative.',
            'purchase_price.numeric' => 'Purchase price must be a number.',
            'purchase_price.min' => 'Purchase price cannot be negative.',
            'rental_price_per_day.numeric' => 'Rental price per day must be a number.',
            'rental_price_per_day.min' => 'Rental price per day cannot be negative.',
            'status.string' => 'Status must be text.',
            'status.in' => 'Please select a valid status.',
            'condition.string' => 'Condition must be text.',
            'condition.in' => 'Condition must be one of: excellent, good, fair, poor.',
            'mileage_correction.boolean' => 'Mileage correction must be true or false.',
            'featured.boolean' => 'Featured must be true or false.',
            'location.string' => 'Location must be text.',
            'location.max' => 'Location must not exceed 255 characters.',
            'images.max' => 'Vehicle cannot have more than 10 images.',
            'images.*.image_url.required' => 'Image URL is required.',
            'images.*.image_url.max' => 'Image URL must not exceed 500 characters.',
        ];
    }
}
