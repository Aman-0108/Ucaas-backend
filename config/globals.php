<?php

return $global = [
    'app_name' => 'UCAAS',
    'api_version' => 'V-1',
    'current_version' => 'V-1',
    'active_currency' => env('CASHIER_CURRENCY', 'USD'),
    'PAGINATION' => [
        'ROW_PER_PAGE' => 20,
    ],
    'EXTENSION_START_FROM' => 1000,
    'RINGGROUP_START_FROM' => 5000,
    'CALLCENTRE_START_FROM' => 8000,
    'support_mail' => env('SUPPORT_MAIL', 'support@webvio.com'),
    'website_url' => env('WEBSITE_URL', 'http://192.168.1.88:3000/login')

    // Add more static values as needed
];
