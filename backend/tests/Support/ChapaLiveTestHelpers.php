<?php

namespace Tests\Support;

use App\Exceptions\PaymentVerificationRetryableException;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Http;

trait ChapaLiveTestHelpers
{
    protected function skipUnlessChapaLiveTests(): void
    {
        if (!filter_var(env('CHAPA_LIVE_TESTS', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Set CHAPA_LIVE_TESTS=true to run live Chapa integration tests.');
        }

        $key = (string) config('services.chapa.secret_key');

        if ($key === '') {
            $this->markTestSkipped('CHAPA_SECRET_KEY is not configured.');
        }

        if (!str_starts_with($key, 'CHASECK_TEST-')) {
            $this->markTestSkipped('Live Chapa tests require a test secret key (CHASECK_TEST-...).');
        }
    }

    protected function uniqueTxRef(string $prefix = 'APEX-LIVE'): string
    {
        return $prefix . '-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }

    /**
     * Complete a Chapa test payment via Telebirr direct charge (test mobile auto-succeeds).
     */
    protected function chargeTelebirrTestPayment(string $txRef, float $amount): array
    {
        $key = (string) config('services.chapa.secret_key');
        $boundary = 'wL36Yn8afVp8Ag7AmP8qZ0SA4n1v9T';
        $fields = [
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'ETB',
            'tx_ref' => $txRef,
            'mobile' => '0900123456',
        ];

        $parts = [];
        foreach ($fields as $name => $value) {
            $parts[] = '--' . $boundary;
            $parts[] = 'Content-Disposition: form-data; name="' . $name . '"';
            $parts[] = '';
            $parts[] = $value;
        }
        $parts[] = '--' . $boundary . '--';
        $parts[] = '';
        $body = implode("\r\n", $parts);

        $url = rtrim((string) config('services.chapa.base_url', 'https://api.chapa.co'), '/') . '/v1/charges?type=telebirr';
        $headers = [
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
        ];

        $response = Http::withHeaders($headers)
            ->timeout(60)
            ->withBody($body, 'multipart/form-data; boundary=' . $boundary)
            ->post($url);

        if (!$response->successful() && str_contains((string) $response->body(), 'try again')) {
            sleep(65);
            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->withBody($body, 'multipart/form-data; boundary=' . $boundary)
                ->post($url);
        }

        $this->assertTrue(
            $response->successful(),
            'Chapa direct charge failed: ' . ($response->body() ?: 'empty response')
        );

        return $response->json('data') ?? [];
    }

    protected function makeOnlinePayment(
        Booking $booking,
        User $customer,
        string $txRef,
        float $expectedAmount,
        array $overrides = []
    ): Payment {
        return Payment::create(array_merge([
            'booking_id' => $booking->id,
            'attempt_number' => 1,
            'user_id' => $customer->id,
            'branch_id' => $booking->branch_id,
            'amount' => $expectedAmount,
            'expected_amount' => $expectedAmount,
            'currency' => 'ETB',
            'payment_method' => Payment::METHOD_ONLINE_PAYMENT,
            'gateway' => Payment::GATEWAY_CHAPA,
            'transaction_reference' => $txRef,
            'status' => Payment::STATUS_PROCESSING,
            'verification_status' => Payment::VERIFICATION_UNVERIFIED,
        ], $overrides));
    }

    protected function assertRetryableVerification(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected PaymentVerificationRetryableException was not thrown.');
        } catch (PaymentVerificationRetryableException) {
            $this->assertTrue(true);
        }
    }
}
