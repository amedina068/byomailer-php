<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Instance URL
    |--------------------------------------------------------------------------
    |
    | The URL of your BYOMailer instance, for example
    | "https://mail.example.com". Each user runs an own instance, so this
    | value is required. The SDK adds "/api/v1".
    |
    */

    'url' => env('BYOMAILER_URL'),

    /*
    |--------------------------------------------------------------------------
    | API token
    |--------------------------------------------------------------------------
    |
    | A personal API token. Make it in Settings, API Tokens. A read-only
    | token gets HTTP 403 on each request that changes data.
    |
    */

    'token' => env('BYOMAILER_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Timeout and retries
    |--------------------------------------------------------------------------
    |
    | The request timeout in seconds, and the number of retries after a 429,
    | a 5xx or a connection error.
    |
    */

    'timeout' => (float) env('BYOMAILER_TIMEOUT', 30),

    'retries' => (int) env('BYOMAILER_RETRIES', 2),

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    |
    | The secret of your webhook endpoint, with its "whsec_" prefix, and the
    | maximum age of a delivery in seconds. The secret is a secret. Use it
    | only on the server.
    |
    */

    'webhook' => [
        'secret' => env('BYOMAILER_WEBHOOK_SECRET'),
        'tolerance' => (int) env('BYOMAILER_WEBHOOK_TOLERANCE', 300),
    ],

];
