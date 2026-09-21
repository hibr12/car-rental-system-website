<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $this->user()->id],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'profile_photo' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Name must be text.',
            'name.max' => 'Name must not exceed 255 characters.',
            'email.string' => 'Email must be text.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Email must not exceed 255 characters.',
            'email.unique' => 'An account with this email already exists. Please use a different one.',
            'phone.string' => 'Phone number must be text.',
            'phone.max' => 'Phone number is too long.',
            'profile_photo.string' => 'Profile photo URL must be text.',
            'profile_photo.max' => 'Profile photo URL is too long.',
        ];
    }
}
