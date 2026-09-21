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
            'overall_rating' => ['sometimes', 'required', 'integer', 'min:' . Review::MIN_RATING, 'max:' . Review::MAX_RATING],
            'vehicle_rating' => ['sometimes', 'required', 'integer', 'min:' . Review::MIN_RATING, 'max:' . Review::MAX_RATING],
            'cleanliness_rating' => ['sometimes', 'required', 'integer', 'min:' . Review::MIN_RATING, 'max:' . Review::MAX_RATING],
            'staff_rating' => ['sometimes', 'required', 'integer', 'min:' . Review::MIN_RATING, 'max:' . Review::MAX_RATING],
            'value_rating' => ['sometimes', 'required', 'integer', 'min:' . Review::MIN_RATING, 'max:' . Review::MAX_RATING],
            'comment' => ['sometimes', 'nullable', 'string', 'max:' . Review::MAX_COMMENT_LENGTH],
            'rating' => ['sometimes', 'integer', 'min:' . Review::MIN_RATING, 'max:' . Review::MAX_RATING],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('rating') && !$this->has('overall_rating')) {
            $this->merge(['overall_rating' => (int) $this->input('rating')]);
        }
    }

    public function messages(): array
    {
        return [
            'overall_rating.required' => 'Please select an overall rating.',
            'overall_rating.integer' => 'Overall rating must be a whole number.',
            'overall_rating.min' => 'Overall rating must be at least ' . Review::MIN_RATING . '.',
            'overall_rating.max' => 'Overall rating must not exceed ' . Review::MAX_RATING . '.',
            'vehicle_rating.required' => 'Please select a vehicle rating.',
            'vehicle_rating.integer' => 'Vehicle rating must be a whole number.',
            'vehicle_rating.min' => 'Vehicle rating must be at least ' . Review::MIN_RATING . '.',
            'vehicle_rating.max' => 'Vehicle rating must not exceed ' . Review::MAX_RATING . '.',
            'cleanliness_rating.required' => 'Please select a cleanliness rating.',
            'cleanliness_rating.integer' => 'Cleanliness rating must be a whole number.',
            'cleanliness_rating.min' => 'Cleanliness rating must be at least ' . Review::MIN_RATING . '.',
            'cleanliness_rating.max' => 'Cleanliness rating must not exceed ' . Review::MAX_RATING . '.',
            'staff_rating.required' => 'Please select a staff rating.',
            'staff_rating.integer' => 'Staff rating must be a whole number.',
            'staff_rating.min' => 'Staff rating must be at least ' . Review::MIN_RATING . '.',
            'staff_rating.max' => 'Staff rating must not exceed ' . Review::MAX_RATING . '.',
            'value_rating.required' => 'Please select a value rating.',
            'value_rating.integer' => 'Value rating must be a whole number.',
            'value_rating.min' => 'Value rating must be at least ' . Review::MIN_RATING . '.',
            'value_rating.max' => 'Value rating must not exceed ' . Review::MAX_RATING . '.',
            'comment.string' => 'Comment must be text.',
            'comment.max' => 'Comment must not exceed ' . Review::MAX_COMMENT_LENGTH . ' characters.',
            'rating.integer' => 'Rating must be a whole number.',
            'rating.min' => 'Rating must be at least ' . Review::MIN_RATING . '.',
            'rating.max' => 'Rating must not exceed ' . Review::MAX_RATING . '.',
        ];
    }
}
