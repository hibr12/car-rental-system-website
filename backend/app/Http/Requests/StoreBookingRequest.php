<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Booking::class);
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'pickup_location' => ['required', 'string', 'max:255'],
            'return_location' => ['required', 'string', 'max:255'],
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['required', 'date', 'after:pickup_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_id.required' => 'Vehicle is required.',
            'vehicle_id.exists' => 'The selected vehicle does not exist.',
            'pickup_location.required' => 'Pickup location is required.',
            'return_location.required' => 'Return location is required.',
            'pickup_date.required' => 'Pickup date is required.',
            'pickup_date.after_or_equal' => 'Pickup date must be today or later.',
            'return_date.required' => 'Return date is required.',
            'return_date.after' => 'Return date must be after pickup date.',
            'notes.max' => 'Notes must not exceed 1000 characters.',
        ];
    }
}