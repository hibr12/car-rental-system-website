<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Payment::class);
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
            'booking_id.required' => 'Booking is required.',
            'booking_id.integer' => 'Booking must be a valid ID.',
            'booking_id.exists' => 'The selected booking does not exist.',
            'payment_method.required' => 'Please select a payment method.',
            'payment_method.string' => 'Payment method must be text.',
            'payment_method.in' => 'Please select a valid payment method.',
            'transaction_reference.string' => 'Transaction reference must be text.',
            'transaction_reference.max' => 'Transaction reference must not exceed 255 characters.',
        ];
    }
}
