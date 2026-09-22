<?php
/**
 * Overlay environment variables onto .env file.
 * Uses getenv() which reads from the process environment (set by Render).
 * Replaces fragile sed with reliable PHP string manipulation.
 */

$envFile = __DIR__ . '/.env';
$env = file_get_contents($envFile);

$vars = [
    'APP_ENV',
    'APP_DEBUG',
    'APP_URL',
    'APP_KEY',
    'DB_CONNECTION',
    'DB_HOST',
    'DB_PORT',
    'DB_DATABASE',
    'DB_USERNAME',
    'DB_PASSWORD',
    'DB_SSLMODE',
    'SESSION_DRIVER',
    'CACHE_STORE',
    'QUEUE_CONNECTION',
    'FRONTEND_URL',
    'SANCTUM_STATEFUL_DOMAINS',
    'CHAPA_MODE',
    'CHAPA_SECRET_KEY',
    'CHAPA_BASE_URL',
    'CHAPA_CALLBACK_URL',
    'CHAPA_RETURN_URL',
    'CHAPA_WEBHOOK_URL',
    'CHAPA_WEBHOOK_SECRET',
    'CLOUDINARY_CLOUD_NAME',
    'CLOUDINARY_KEY',
    'CLOUDINARY_SECRET',
    'MAIL_MAILER',
    'MAIL_HOST',
    'MAIL_PORT',
    'MAIL_USERNAME',
    'MAIL_PASSWORD',
    'MAIL_ENCRYPTION',
    'MAIL_FROM_ADDRESS',
    'MAIL_FROM_NAME',
];

foreach ($vars as $var) {
    $value = getenv($var);
    if ($value === false || $value === '') {
        continue; // Don't override with empty — keep .env.example default
    }

    // Quote values containing spaces to make .env parser happy
    $escapedValue = $value;
    if (preg_match('/\s/', $value) && !str_starts_with($value, '"')) {
        $escapedValue = '"' . $value . '"';
    }

    $pattern = '/^' . preg_quote($var, '/') . '=.*$/m';

    if (preg_match($pattern, $env)) {
        $env = preg_replace($pattern, "$var=$escapedValue", $env);
    } else {
        $env .= "\n$var=$escapedValue";
    }
}

file_put_contents($envFile, $env);

// Verify critical vars
$check = ['APP_KEY', 'DB_HOST', 'CHAPA_SECRET_KEY'];
foreach ($check as $var) {
    $val = getenv($var);
    $status = ($val !== false && $val !== '') ? 'SET (' . strlen($val) . ' chars)' : 'MISSING';
    echo "  $var: $status\n";
}

echo "Environment overlay complete.\n";
