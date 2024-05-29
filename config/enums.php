<?php

return $global = [
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
        'status' => [
            'Logged Out',
            'Available',
            'Available (On Demand)',
            'On Break',

        ],
        'state' => [
            'Idle',
            'Waiting',
            'Receiving',
            'In a queue call',
        ],
    ],
    'Agentbackup' => [
        'Strategy' => [
            'RINGALL' => 'ring-all',
            'LONGESTIDLEAGENT' => 'longest-idle-agent',
            'ROUNDROBIN' => 'round-robin',
            'TOPDOWN' => 'top-down',
            'AGENTWITHLEASTTALKTIME' => 'agent-with-least-talk-time',
            'AGENTWITHFEWESTCALLS' => 'agent-with-fewest-calls',
            'SEQUENTIALLYBYAGENTORDER' => 'sequentially-by-agent-order',
            'RANDOM' => 'random',
            'RINGPROGRESSIVELY' => 'ring-progressively',
        ],
        'Status' => [
            'LoggedOut' => 'Logged Out',
            'Available' => 'Available',
            'AOD' => 'Available (On Demand)',
            'OnBreak' => 'On Break',

        ],
        'state' => [
            'Idle' => 'Idle',
            'Waiting' => 'Waiting',
            'Receiving' => 'Receiving',
            'InAQueueCall' => 'In a queue call',
        ],
    ],

    // Add more static values as needed
];
