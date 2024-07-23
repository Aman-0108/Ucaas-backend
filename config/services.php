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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'ssh' => [
        'host' => env('SSH_HOST'),
        'username' => env('SSH_USERNAME'),
        'password' => env('SSH_PASSWORD'),
    ],

    'esl' => [
        'host' => env('ESL_HOST'),
        'port' => env('ESL_PORT'),
        'password' => env('ESL_PASSWORD'),
    ],

    'freeswitch' => [
        'host' => env('ESL_HOST'),
        'port' => env('ESL_PORT'),
        'password' => env('ESL_PASSWORD'),
    ],

    'websocket' => [
        'port' => env('WEBSOCKET_PORT', 8091),
        'ip' => env('WEBSOCKET_IP', '127.0.0.1')
    ],

    'stripe' => [
        'api_key' => env('STRIPE_KEY', ''),
        'api_secret' => env('STRIPE_SECRET', '')
    ]

];
