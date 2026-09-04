<?php

namespace App\Http\Requests;

use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['sometimes', 'integer', 'exists:bookings,id'],
            'overall_rating' => ['required', 'integer', 'min:' . Review::MIN_RATING, 'max:' . Review::MAX_RATING],
            'vehicle_rating' => ['required', 'integer', 'min:' . Review::MIN_RATING, 'max:' . Review::MAX_RATING],
            'cleanliness_rating' => ['required', 'integer', 'min:' . Review::MIN_RATING, 'max:' . Review::MAX_RATING],
            'staff_rating' => ['required', 'integer', 'min:' . Review::MIN_RATING, 'max:' . Review::MAX_RATING],
            'value_rating' => ['required', 'integer', 'min:' . Review::MIN_RATING, 'max:' . Review::MAX_RATING],
            'comment' => ['nullable', 'string', 'max:' . Review::MAX_COMMENT_LENGTH],
            // Legacy single-rating support
            'rating' => ['sometimes', 'integer', 'min:' . Review::MIN_RATING, 'max:' . Review::MAX_RATING],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('rating') && !$this->has('overall_rating')) {
            $rating = (int) $this->input('rating');
            $this->merge([
                'overall_rating' => $rating,
                'vehicle_rating' => $this->input('vehicle_rating', $rating),
                'cleanliness_rating' => $this->input('cleanliness_rating', $rating),
                'staff_rating' => $this->input('staff_rating', $rating),
                'value_rating' => $this->input('value_rating', $rating),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'overall_rating.required' => 'Please select an overall rating.',
            'overall_rating.min' => 'Overall rating must be at least ' . Review::MIN_RATING . '.',
            'overall_rating.max' => 'Overall rating must not exceed ' . Review::MAX_RATING . '.',
            'comment.max' => 'Comment must not exceed ' . Review::MAX_COMMENT_LENGTH . ' characters.',
        ];
    }
}
