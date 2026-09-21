<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:4000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your name.',
            'name.string' => 'Name must be text.',
            'name.max' => 'Name must not exceed 255 characters.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Email must not exceed 255 characters.',
            'phone.string' => 'Phone number must be text.',
            'phone.max' => 'Phone number is too long.',
            'subject.required' => 'Please enter a subject.',
            'subject.string' => 'Subject must be text.',
            'subject.max' => 'Subject must not exceed 255 characters.',
            'message.required' => 'Please enter your message.',
            'message.string' => 'Message must be text.',
            'message.max' => 'Message must not exceed 4000 characters.',
        ];
    }
}
