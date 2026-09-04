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
}
