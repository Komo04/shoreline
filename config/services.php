<?php

$appUrl = (string) env('APP_URL', 'http://localhost');
$isLocalMidtransHost = str_contains($appUrl, '127.0.0.1')
    || str_contains($appUrl, 'localhost');

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
    'komerce' => [
        'search_url' => env('KOMERCE_SEARCH_URL'),
        'api_key' => env('KOMERCE_API_KEY'),
        'base_url' => env('KOMERCE_BASE_URL', 'https://rajaongkir.komerce.id/api/v1'),
    ],
    'shop' => [
        'origin_id' => env('SHOP_ORIGIN_ID'),
    ],
    'shipping' => [
        'fallback_enabled' => filter_var(env('SHIPPING_FALLBACK_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'fallback_flat' => (int) env('SHIPPING_FALLBACK_FLAT', 20000),
        'fallback_etd'  => env('SHIPPING_FALLBACK_ETD', '2-5 hari'),
    ],
    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),

        'is_production'   => $isLocalMidtransHost
            ? false
            : filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN),
        'is_sanitized'    => filter_var(env('MIDTRANS_IS_SANITIZED', true), FILTER_VALIDATE_BOOLEAN),
        'is_3ds'          => filter_var(env('MIDTRANS_IS_3DS', true), FILTER_VALIDATE_BOOLEAN),
        'webhook_enabled' => filter_var(env('MIDTRANS_WEBHOOK_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    ],





];
