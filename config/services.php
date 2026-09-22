<?php

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],
    'attendance' => [
        'base_url' => env('ATTENDANCE_BASE_URL', 'https://api.shehryar.me/api/public/attendance'),
        'api_key' => env('ATTENDANCE_API_KEY'),
    ],
];
