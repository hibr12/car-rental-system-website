<?php

namespace App\Http\Requests;

use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class AdminReviewStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('moderate', $this->route('review'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:' . implode(',', Review::STATUSES)],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Please select a status.',
            'status.string' => 'Status must be text.',
            'status.in' => 'Please select a valid status.',
            'reason.string' => 'Reason must be text.',
            'reason.max' => 'Reason must not exceed 500 characters.',
        ];
    }
}
