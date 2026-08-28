<?php

namespace App\Http\Requests;

use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class AdminReviewResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('respond', $this->route('review'));
    }

    public function rules(): array
    {
        return [
            'admin_response' => ['required', 'string', 'max:' . Review::MAX_COMMENT_LENGTH],
        ];
    }
}
