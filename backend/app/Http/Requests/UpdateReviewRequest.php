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
}
