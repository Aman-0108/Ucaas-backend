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
        'ip' => env('WEBSOCKET_IP', '127.0.0.1'),
        'ssl' => [
            'local_cert' => env('WEBSOCKET_SSL_LOCAL_CERT', base_path('C:\ssl\server.crt')),
            'local_pk' => env('WEBSOCKET_SSL_LOCAL_PK', base_path('C:\ssl\server.key')),
            'allow_self_signed' => true,  // Allow self-signed certs for development
            'verify_peer' => false,       // Disable peer verification for development
        ],
    ],

    'stripe' => [
        'api_key' => env('STRIPE_KEY', ''),
        'api_secret' => env('STRIPE_SECRET', '')
    ]

];

// openssl x509 -in server.crt -text -noout
// openssl x509 -enddate -noout -in server.crt
// openssl x509 -startdate -noout -in server.crt

// openssl rsa -in server.key -check
// openssl rsa -in server.key -text -noout
// netstat -an | find "8093"
