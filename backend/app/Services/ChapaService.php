<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChapaService
{
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.chapa.secret_key');
        $this->baseUrl = config('services.chapa.base_url', 'https://api.chapa.co');
    }

    /**
     * Initialize a payment with Chapa.
     *
     * @return array{checkout_url: string, tx_ref: string}
     */
    public function initializePayment(array $payload): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->baseUrl . '/v1/transaction/initialize', [
                'tx_ref' => $payload['tx_ref'],
                'amount' => $payload['amount'],
                'currency' => $payload['currency'] ?? 'ETB',
                'email' => $payload['email'],
                'first_name' => $payload['first_name'] ?? '',
                'last_name' => $payload['last_name'] ?? '',
                'callback_url' => $payload['callback_url'],
                'return_url' => $payload['return_url'],
                'customization' => [
                    'title' => $payload['title'] ?? 'Car Rental',
                    'description' => $payload['description'] ?? 'Payment for car rental booking',
                ],
            ]);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            Log::error('Chapa API connection failed', [
                'error' => $e->getMessage(),
                'url' => $this->baseUrl . '/v1/transaction/initialize',
            ]);
            throw new \RuntimeException('Unable to connect to payment gateway. Please check your network and try again.');
        } catch (\Exception $e) {
            Log::error('Chapa API request failed', [
                'error' => $e->getMessage(),
                'url' => $this->baseUrl . '/v1/transaction/initialize',
            ]);
            throw new \RuntimeException('Payment gateway request failed. Please try again later.');
        }

        Log::debug('Chapa API response', [
            'status' => $response->status(),
            'body' => $response->body(),
            'successful' => $response->successful(),
            'failed' => $response->failed(),
        ]);

        if ($response->failed()) {
            $errorData = $response->json();
            $errorMessages = $errorData['message'] ?? [];

            Log::error('Chapa payment initialization failed', [
                'status' => $response->status(),
                'response' => $errorData,
            ]);

            $detail = '';
            if (is_array($errorMessages)) {
                foreach ($errorMessages as $field => $errors) {
                    $detail .= $field . ': ' . implode(', ', (array) $errors) . '; ';
                }
            } elseif (is_string($errorMessages)) {
                $detail = $errorMessages;
            }

            $detail = trim($detail);
            throw new \RuntimeException(
                'Payment initialization failed' . ($detail ? ": {$detail}" : '. Please try again later.')
            );
        }

        $data = $response->json();

        if (!isset($data['data']['checkout_url'])) {
            Log::error('Chapa response missing checkout_url', ['response' => $data]);

            throw new \RuntimeException('Payment initialization failed. Invalid response from payment gateway.');
        }

        return [
            'checkout_url' => $data['data']['checkout_url'],
            'tx_ref' => $payload['tx_ref'],
        ];
    }

    /**
     * Verify a payment transaction with Chapa.
     *
     * @return array{status: string, amount: float, tx_ref: string}
     */
    public function verifyTransaction(string $txRef): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
        ])->timeout(30)->get($this->baseUrl . '/v1/transaction/verify/' . $txRef);

        if ($response->failed()) {
            Log::error('Chapa transaction verification failed', [
                'tx_ref' => $txRef,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new \RuntimeException('Transaction verification failed. Please try again later.');
        }

        $data = $response->json();

        if (!isset($data['data'])) {
            Log::error('Chapa verification response missing data', ['tx_ref' => $txRef, 'response' => $data]);

            throw new \RuntimeException('Invalid verification response.');
        }

        return [
            'status' => $data['data']['status'] ?? 'failed',
            'amount' => (float) ($data['data']['amount'] ?? 0),
            'tx_ref' => $data['data']['tx_ref'] ?? $txRef,
            'reference' => $data['data']['reference'] ?? null,
        ];
    }

    /**
     * Generate a unique transaction reference.
     */
    public function generateTransactionRef(): string
    {
        return 'TXN-' . now()->format('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(8));
    }
}
