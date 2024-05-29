<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallRecorded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $events;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($events)
    {
        $this->events = $events;
    }
}
