<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['sometimes', 'exists:vehicles,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'maintenance_type' => ['sometimes', 'string', 'max:100'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'string', 'in:scheduled,in_progress,completed,cancelled'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_id.exists' => 'The selected vehicle does not exist.',
            'title.string' => 'Title must be text.',
            'title.max' => 'Title must not exceed 255 characters.',
            'description.string' => 'Description must be text.',
            'description.max' => 'Description must not exceed 2000 characters.',
            'maintenance_type.string' => 'Maintenance type must be text.',
            'maintenance_type.max' => 'Maintenance type must not exceed 100 characters.',
            'cost.numeric' => 'Cost must be a number.',
            'cost.min' => 'Cost cannot be negative.',
            'start_date.date' => 'Start date must be a valid date.',
            'end_date.date' => 'End date must be a valid date.',
            'end_date.after_or_equal' => 'End date must be on or after start date.',
            'status.in' => 'Status must be one of: scheduled, in_progress, completed, cancelled.',
            'notes.string' => 'Notes must be text.',
            'notes.max' => 'Notes must not exceed 2000 characters.',
        ];
    }
}
