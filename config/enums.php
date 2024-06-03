<?php

return $enums = [
    'languages' => [
        'English',
        'Hindi'
    ],
    'user' => [
        'types' => [
            'SupreAdmin',
            'Company',
            'General'
        ],
        'defaultusertype' => 'General',
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
    ]
    // Add more static values as needed
];
