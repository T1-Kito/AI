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

    'upstream_api' => [
        'base_url' => env('UPSTREAM_API_BASE_URL'),
        'key' => env('UPSTREAM_API_KEY'),
    ],

    'bank_account' => [
        'bank_name' => env('BANK_ACCOUNT_NAME', 'TPBank'),
        'bank_code' => env('BANK_ACCOUNT_CODE', 'TPB'),
        'account_number' => env('BANK_ACCOUNT_NUMBER', '10003998194'),
        'account_name' => env('BANK_ACCOUNT_HOLDER', 'NGUYEN ANH KHOA'),
    ],

    'bank_webhook' => [
        'token' => env('BANK_WEBHOOK_TOKEN', 'local-bank-webhook-token'),
        'min_deposit' => env('MIN_DEPOSIT', 20000),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
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

];
