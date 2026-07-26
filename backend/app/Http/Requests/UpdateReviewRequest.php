<?php

namespace App\Http\Requests;

use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('review'));
    }

    public function rules(): array
    {
        return [
            'rating' => ['sometimes', 'required', 'integer', 'min:' . Review::MIN_RATING, 'max:' . Review::MAX_RATING],
            'comment' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Rating is required.',
            'rating.integer' => 'Rating must be an integer.',
            'rating.min' => 'Rating must be at least ' . Review::MIN_RATING . '.',
            'rating.max' => 'Rating must not exceed ' . Review::MAX_RATING . '.',
            'comment.max' => 'Comment must not exceed 2000 characters.',
        ];
    }
}
