<?php

return $enums = [
    'languages' => [
        'English',
        'Hindi'
    ],
    'user' => [
        'types' => [
            '',
            'SuperAdmin',
            'Company'
        ],
        'defaultusertype' => '',
        'status' => [
            'E',
            'D'
        ],
        'defaultstatus' => 'E',
        'statuscomment' => 'E for Enable & D for Disable'
    ],
    'socket' => [
        'status' => [
            'online',
            'offline'
        ],
        'defaultstatus' => 'offline'
    ],
    'agent' => [
        'strategy' => [
            'ring-all',
            'longest-idle-agent',
            'round-robin',
            'top-down',
            'agent-with-least-talk-time',
            'agent-with-fewest-calls',
            'sequentially-by-agent-order',
            'random',
            'ring-progressively',
        ],
        'type' => [
            'callback',
            'uuid-standby'
        ],
        'status' => [
            'Logged Out',
            'Available',
            'Available (On Demand)',
            'On Break'
        ],
        'state' => [
            'Idle',
            'Waiting',
            'Receiving',
            'In a queue call',
        ],
    ],
    'company' => [
        'company_status' => [
            1,
            2,
            3,
            4,
            5,
            6
        ],
        'comment_company_status' => '1 for Payment Completed, 2 for payment verified, 3 for Document Uploaded, 4 for Document Verified, 5 for DID configuration & 6 for full configuration',
        'status' => [
            'active',
            'inactive'
        ],
        'default_status' => 'active',
    ],
    'card' => [
        'save_card' => [
            1,
            2
        ],
        'default_save_card' => 2,
        'comment_save_card' => '1 for save card & 2 for do not save card'
    ],
    'RESPONSE' => [
        "SUCCESS" => 'success',
        "ERROR" => 'error'
    ]
    // Add more static values as needed
];
