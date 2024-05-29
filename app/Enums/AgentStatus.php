<?php

namespace App\Enums;

enum AgentStatus: string
{
    case LoggedOut = 'Logged Out';
    case Available = 'Available';
    case AOD = 'Available (On Demand)';
    case OnBreak = 'On Break';

    public static function toArray(): array
    {
        $array = [];
        
        foreach (self::cases() as $case) {
            $array[$case->value] = $case->name;
        }

        return $array;
    }
}