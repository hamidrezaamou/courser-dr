<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reminder channels
    |--------------------------------------------------------------------------
    |
    | telegram: send to clinic Telegram chat via Bot API
    | sms drivers: log | smsir | http
    |
    */
    'enabled' => env('REMINDERS_ENABLED', true),

    'days_ahead' => (int) env('REMINDERS_DAYS_AHEAD', 1),

    'send_time' => env('REMINDERS_SEND_TIME', '18:00'),

    'telegram' => [
        'enabled' => (bool) env('TELEGRAM_REMINDERS', false),
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    'sms' => [
        'enabled' => (bool) env('SMS_REMINDERS', false),
        'driver' => env('SMS_DRIVER', 'log'), // log|smsir|http
        'endpoint' => env('SMS_HTTP_ENDPOINT'),
        'api_key' => env('SMS_API_KEY'),
        'sender' => env('SMS_SENDER'),
        'smsir' => [
            'api_key' => env('SMSIR_API_KEY'),
            'line_number' => env('SMSIR_LINE_NUMBER'),
        ],
    ],
];
