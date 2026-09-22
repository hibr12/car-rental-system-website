<?php

namespace App\Services;

use App\Exceptions\PaymentVerificationRetryableException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChapaService
{
    private string $mode;
    private string $secretKey;
    private string $baseUrl;
    private string $webhookSecret;

    public function __construct()
    {
        $this->mode = strtolower(trim((string) config('services.chapa.mode', 'test')));
        $this->secretKey = (string) config('services.chapa.secret_key');
        $this->baseUrl = rtrim((string) config('services.chapa.base_url', 'https://api.chapa.co'), '/');
        $this->webhookSecret = (string) config('services.chapa.webhook_secret', $this->secretKey);
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @return array{checkout_url: string, tx_ref: string}
     */
    public function initializePayment(array $payload): array
    {
        if (!$this->secretKey) {
            throw new \RuntimeException('Chapa secret key is not configured on the server.');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->baseUrl . '/v1/transaction/initialize', [
                'tx_ref' => $payload['tx_ref'],
                'amount' => number_format((float) $payload['amount'], 2, '.', ''),
                'currency' => $payload['currency'] ?? 'ETB',
                'email' => $payload['email'],
                'first_name' => $payload['first_name'] ?? '',
                'last_name' => $payload['last_name'] ?? '',
                'callback_url' => $payload['callback_url'],
                'return_url' => $payload['return_url'],
                'customization' => [
                    'title' => substr($payload['title'] ?? 'Car Rental', 0, 16),
                    'description' => $payload['description'] ?? 'Payment for car rental booking',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[Chapa] Initialize request failed', [
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Unable to connect to payment gateway. Please try again later.');
        }

        if ($response->failed()) {
            $errorData = $response->json();
            Log::error('[Chapa] Initialize failed', [
                'status' => $response->status(),
                'response' => $errorData,
            ]);

            $message = is_string($errorData['message'] ?? null)
                ? $errorData['message']
                : 'Payment initialization failed. Please try again later.';

            throw new \RuntimeException($message);
        }

        $data = $response->json();

        if (!isset($data['data']['checkout_url'])) {
            Log::error('[Chapa] Initialize response missing checkout_url', ['response' => $data]);
            throw new \RuntimeException('Payment initialization failed. Invalid response from payment gateway.');
        }

        Log::info('[Chapa] Initialize transaction', [
            'mode' => $this->mode,
            'tx_ref' => $payload['tx_ref'],
            'amount' => $payload['amount'],
            'currency' => $payload['currency'] ?? 'ETB',
        ]);

        return [
            'checkout_url' => $data['data']['checkout_url'],
            'tx_ref' => $payload['tx_ref'],
        ];
    }

    /**
     * Verify a payment transaction with Chapa.
     *
     * @return array{status: string, amount: float, currency: string, tx_ref: string, reference: ?string, raw: array}
     *
     * @throws PaymentVerificationRetryableException when verification should be retried later
     * @throws \InvalidArgumentException for permanent verification failures (amount mismatch handled upstream)
     */
    public function verifyTransaction(string $txRef): array
    {
        if (!$this->secretKey) {
            throw new \RuntimeException('Chapa secret key is not configured on the server.');
        }

        Log::info('[Chapa] Verify transaction', ['mode' => $this->mode, 'tx_ref' => $txRef]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->timeout(30)->get($this->baseUrl . '/v1/transaction/verify/' . urlencode($txRef));
        } catch (\Exception $e) {
            Log::warning('[Chapa] Verify network error', [
                'tx_ref' => $txRef,
                'error' => $e->getMessage(),
            ]);
            throw new PaymentVerificationRetryableException(
                'Unable to reach Chapa for verification. Payment remains processing.'
            );
        }

        if ($response->status() === 404) {
            Log::info('[Chapa] Transaction not found yet', ['tx_ref' => $txRef]);
            throw new PaymentVerificationRetryableException(
                'Transaction not yet available from Chapa. Please retry shortly.'
            );
        }

        if ($response->status() >= 500) {
            Log::warning('[Chapa] Verify server error', [
                'tx_ref' => $txRef,
                'http_status' => $response->status(),
            ]);
            throw new PaymentVerificationRetryableException(
                'Chapa verification temporarily unavailable. Please retry shortly.'
            );
        }

        if ($response->failed()) {
            $errorData = $response->json();
            Log::error('[Chapa] Verify failed', [
                'tx_ref' => $txRef,
                'http_status' => $response->status(),
                'response' => $errorData,
            ]);

            if ($response->status() === 401 || $response->status() === 403) {
                throw new \RuntimeException('Chapa API authentication failed. Check server credentials.');
            }

            throw new PaymentVerificationRetryableException(
                'Chapa verification could not be completed yet. Please retry shortly.'
            );
        }

        $data = $response->json();

        if (!isset($data['data']) || !is_array($data['data'])) {
            Log::error('[Chapa] Verification response missing data', [
                'tx_ref' => $txRef,
                'response' => $data,
            ]);
            throw new PaymentVerificationRetryableException(
                'Invalid verification response from Chapa. Please retry shortly.'
            );
        }

        $payload = $data['data'];
        $status = strtolower((string) ($payload['status'] ?? 'failed'));

        if (in_array($status, ['success', 'successful'], true)) {
            $status = 'success';
        }

        Log::info('[Chapa] Verification result', [
            'mode' => $this->mode,
            'tx_ref' => $txRef,
            'status' => $status,
            'amount' => $payload['amount'] ?? null,
            'currency' => $payload['currency'] ?? null,
            // gateway_reference logged for reconciliation; no secrets logged.
            'reference' => $payload['reference'] ?? null,
        ]);

        return [
            'status' => $status,
            'amount' => (float) ($payload['amount'] ?? 0),
            'currency' => strtoupper((string) ($payload['currency'] ?? 'ETB')),
            'tx_ref' => (string) ($payload['tx_ref'] ?? $txRef),
            'reference' => $payload['reference'] ?? null,
            'raw' => $payload,
        ];
    }

    public function validateWebhookSignature(string $payload, ?string $signature): bool
    {
        if (!$signature || !$this->webhookSecret) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expected, $signature);
    }

    public function generateTransactionRef(?int $bookingId = null, ?string $bookingReference = null): string
    {
        if ($bookingReference) {
            return 'APEX-' . $bookingReference . '-' . str_pad((string) random_int(1, 99), 2, '0', STR_PAD_LEFT);
        }

        $bookingPart = $bookingId ? 'BK-' . $bookingId . '-' : '';

        return 'APEX-' . $bookingPart . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
    }
}
