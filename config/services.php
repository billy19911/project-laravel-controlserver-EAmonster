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
        'token' => env('POSTMARK_TOKEN'),
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

    'ea' => [
        'api_key' => env('EA_API_KEY'),
        'auto_register_account' => env('EA_AUTO_REGISTER_ACCOUNT', true),
        'default_user_id' => (int) env('EA_DEFAULT_USER_ID', 0),
        'online_fresh_window_sec' => (int) env('EA_ONLINE_FRESH_WINDOW_SEC', 90),
        'bulk_toggle_enabled' => env('EA_BULK_TOGGLE_ENABLED', true),
        'bulk_toggle_account_whitelist' => env('EA_BULK_TOGGLE_ACCOUNT_WHITELIST', ''),
        'require_signature' => env('EA_REQUIRE_SIGNATURE', false),
        'signature_secret' => env('EA_SIGNATURE_SECRET'),
        'signature_ttl_seconds' => env('EA_SIGNATURE_TTL', 300),
        'rate_limit_per_minute' => env('EA_RATE_LIMIT_PER_MINUTE', 120),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    'finnhub' => [
        'api_key' => env('FINNHUB_API_KEY'),
        'base_url' => env('FINNHUB_BASE_URL', 'https://finnhub.io/api/v1'),
    ],

    'news' => [
        'provider' => env('NEWS_CALENDAR_PROVIDER', 'forexfactory'),
        'cache_ttl' => (int) env('NEWS_CALENDAR_CACHE_TTL', 1800),
        'calendar_url' => env('ECONOMIC_CALENDAR_URL', 'https://nfs.faireconomy.media/ff_calendar_thisweek.json'),
    ],

    'billing' => [
        'bank_name' => env('BILLING_BANK_NAME', 'BCA'),
        'bank_account_name' => env('BILLING_BANK_ACCOUNT_NAME', 'Nama Pemilik Rekening'),
        'bank_account_number' => env('BILLING_BANK_ACCOUNT_NUMBER', '-'),
        'bank_note' => env('BILLING_BANK_NOTE', 'Transfer ke rekening pribadi di atas lalu isi referensi pembayaran.'),
        'contact_name' => env('BILLING_CONTACT_NAME', 'Admin Billing'),
        'contact_phone' => env('BILLING_CONTACT_PHONE', ''),
        'monthly_price' => (float) env('BILLING_MONTHLY_PRICE', 0),
        'auto_gateway_enabled' => env('BILLING_AUTO_GATEWAY_ENABLED', false),
        'auto_qris_enabled' => env('BILLING_AUTO_QRIS_ENABLED', false),
        'auto_va_enabled' => env('BILLING_AUTO_VA_ENABLED', false),
    ],

];
