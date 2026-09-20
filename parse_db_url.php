<?php

$url = getenv('DB_URL');
if (!$url) {
    fwrite(STDERR, "DB_URL not set\n");
    exit(1);
}

$parts = parse_url($url);
if (!$parts || empty($parts['host'])) {
    fwrite(STDERR, "Failed to parse DB_URL: $url\n");
    exit(1);
}

$envFile = '.env';
$env = file_get_contents($envFile);

$replacements = [
    'DB_HOST' => $parts['host'] ?? '',
    'DB_PORT' => $parts['port'] ?? '5432',
    'DB_DATABASE' => ltrim($parts['path'] ?? '', '/'),
    'DB_USERNAME' => $parts['user'] ?? '',
    'DB_PASSWORD' => $parts['pass'] ?? '',
];

foreach ($replacements as $key => $value) {
    $env = preg_replace("/^" . preg_quote($key, '/') . "=.*/m", "$key=$value", $env);
}

file_put_contents($envFile, $env);

echo "Database config parsed successfully:\n";
echo "  DB_HOST: {$replacements['DB_HOST']}\n";
echo "  DB_PORT: {$replacements['DB_PORT']}\n";
echo "  DB_DATABASE: {$replacements['DB_DATABASE']}\n";
echo "  DB_USERNAME: {$replacements['DB_USERNAME']}\n";
