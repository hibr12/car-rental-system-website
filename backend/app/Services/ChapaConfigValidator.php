<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Validates Chapa gateway configuration at application boot.
 *
 * Rules:
 * - In 'live' mode ALL required keys must be present and non-empty.
 *   A missing key throws immediately — the system never silently falls back to
 *   test credentials in a live environment.
 * - In 'test' mode a missing key logs a warning but does not throw, so local
 *   development can still start without credentials (tests are mocked).
 * - A 'live' mode key that looks like a test key (CHASECK_TEST-) throws.
 * - A 'test' mode key that does NOT look like a test key logs a warning.
 */
class ChapaConfigValidator
{
    /** Keys required in every environment. */
    private const REQUIRED_ALWAYS = [
        'mode',
        'secret_key',
        'base_url',
        'callback_url',
        'return_url',
    ];

    /** Additional key required when webhooks are enabled. */
    private const WEBHOOK_KEY = 'webhook_url';

    public function validate(): void
    {
        $cfg = config('services.chapa', []);
        $mode = strtolower(trim((string) ($cfg['mode'] ?? 'test')));

        if (!in_array($mode, ['test', 'live'], true)) {
            throw new \RuntimeException(
                "CHAPA_MODE must be 'test' or 'live'. Current value: '{$mode}'."
            );
        }

        foreach (self::REQUIRED_ALWAYS as $key) {
            $value = trim((string) ($cfg[$key] ?? ''));

            if ($value === '') {
                $envKey = $this->configKeyToEnvKey($key);

                if ($mode === 'live') {
                    // Hard failure — never use test creds in production silently.
                    throw new \RuntimeException(
                        "Chapa configuration error: {$envKey} is required in live mode but is not set. "
                        . "Set the value in your production environment / secrets manager."
                    );
                }

                Log::warning("[Chapa] {$envKey} is not configured. Set it before accepting real payments.", [
                    'mode' => $mode,
                    'key' => $key,
                ]);
            }
        }

        // Cross-check: live mode must not use a test secret key.
        $secretKey = trim((string) ($cfg['secret_key'] ?? ''));
        if ($mode === 'live' && str_starts_with($secretKey, 'CHASECK_TEST-')) {
            throw new \RuntimeException(
                'Chapa configuration error: CHAPA_MODE is live but CHAPA_SECRET_KEY looks like a test key '
                . '(starts with CHASECK_TEST-). Set a real live secret key for production.'
            );
        }

        // Advisory: test mode should use a test key.
        if ($mode === 'test' && $secretKey !== '' && !str_starts_with($secretKey, 'CHASECK_TEST-')) {
            Log::warning('[Chapa] CHAPA_MODE is test but the secret key does not start with CHASECK_TEST-. '
                . 'Verify you are not using a live key in a test environment.', [
                'mode' => $mode,
            ]);
        }

        Log::info('[Chapa] Configuration validated.', [
            'mode' => $mode,
            'base_url' => $cfg['base_url'] ?? null,
            'callback_url' => $cfg['callback_url'] ?? null,
            'return_url' => $cfg['return_url'] ?? null,
            'webhook_url' => $cfg['webhook_url'] ?? null,
            // Secret key intentionally omitted from logs.
        ]);
    }

    private function configKeyToEnvKey(string $configKey): string
    {
        return 'CHAPA_' . strtoupper($configKey);
    }
}
