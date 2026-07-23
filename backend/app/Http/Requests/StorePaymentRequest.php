<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'payment_method' => ['required', 'string', 'in:cash,bank_transfer,card,online_payment'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_id.required' => 'Booking ID is required.',
            'booking_id.exists' => 'The selected booking does not exist.',
            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Payment method must be one of: cash, bank_transfer, card, online_payment.',
        ];
    }
}
