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
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
    ],

    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.1'),
    ],

    'zimbra' => [
        'enabled' => filter_var(env('ZIMBRA_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'base_url' => rtrim((string) env('ZIMBRA_BASE_URL', 'https://mailpkt.pupukkaltim.com'), '/'),
        'username' => env('ZIMBRA_USERNAME'),
        'password' => env('ZIMBRA_PASSWORD'),
        'from_name' => env('ZIMBRA_FROM_NAME', 'receiving jasa'),
        'timeout' => (int) env('ZIMBRA_TIMEOUT', 20),
        'verify_ssl' => filter_var(env('ZIMBRA_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
        'test_override_to' => env('ZIMBRA_TEST_OVERRIDE_TO'),
    ],
];
