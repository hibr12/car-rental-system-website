<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'chapa' => [
        // 'test' or 'live' — switching this (with matching credentials) is the ONLY change
        // required to move between Chapa TEST and Chapa LIVE environments.
        'mode' => env('CHAPA_MODE', 'test'),

        // Secret key — NEVER expose in VITE_* or frontend code.
        'secret_key' => env('CHAPA_SECRET_KEY'),

        // Chapa REST API base URL (same host for both modes; key determines environment).
        'base_url' => env('CHAPA_BASE_URL', 'https://api.chapa.co'),

        // Server-side callback: Chapa POSTs here after payment completes.
        'callback_url' => env('CHAPA_CALLBACK_URL', rtrim(env('APP_URL', 'http://localhost:8000'), '/') . '/api/payments/callback'),

        // Browser redirect: customer lands here after Chapa checkout.
        'return_url' => env('CHAPA_RETURN_URL', rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/') . '/payments/status'),

        // Webhook endpoint registered in Chapa dashboard.
        'webhook_url' => env('CHAPA_WEBHOOK_URL', rtrim(env('APP_URL', 'http://localhost:8000'), '/') . '/api/payments/chapa/webhook'),

        // Webhook HMAC secret — defaults to secret_key when not separately configured.
        'webhook_secret' => env('CHAPA_WEBHOOK_SECRET', env('CHAPA_SECRET_KEY')),
    ],

    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'key' => env('CLOUDINARY_KEY'),
        'secret' => env('CLOUDINARY_SECRET'),
        'license_folder' => env('CLOUDINARY_LICENSE_FOLDER', 'apex-rentals/licenses'),
        'enabled' => filled(env('CLOUDINARY_CLOUD_NAME'))
            && filled(env('CLOUDINARY_KEY'))
            && filled(env('CLOUDINARY_SECRET')),
    ],

];
