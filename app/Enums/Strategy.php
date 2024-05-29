<?php

namespace App\Enums;

enum Strategy: string
{
    case RINGALL = 'ring-all';
    case LONGESTIDLEAGENT = 'longest-idle-agent';
    case ROUNDROBIN = 'round-robin';
    case TOPDOWN = 'top-down';
    case AGENTWITHLEASTTALKTIME = 'agent-with-least-talk-time';
    case AGENTWITHFEWESTCALLS = 'agent-with-fewest-calls';
    case SEQUENTIALLYBYAGENTORDER = 'sequentially-by-agent-order';
    case RANDOM = 'random';
    case RINGPROGRESSIVELY = 'ring-progressively';

    public static function toArray(): array
    {
        $array = [];
        
        foreach (self::cases() as $case) {
            $array[$case->value] = $case->name;
        }

        return $array;
    }
}
