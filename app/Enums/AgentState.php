<?php

namespace App\Enums;

enum AgentState: string
{
    case Idle = 'Idle';
    case Waiting = 'Waiting';
    case Receiving = 'Receiving';
    case InAQueueCall = 'In a queue call';

    public static function toArray(): array
    {
        $array = [];
        
        foreach (self::cases() as $case) {
            $array[$case->value] = $case->name;
        }

        return $array;
    }
}