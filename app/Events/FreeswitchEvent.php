<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FreeswitchEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $events;

    /**
     * Constructor method.
     *
     * This method initializes a new instance of the class.
     * It takes an array of events as a parameter and assigns it to the 'events' property of the object.
     * 
     * @param array $events An array containing events to be processed by the class.
     */
    public function __construct($events)
    {
        // Assign the array of events to the 'events' property
        $this->events = $events;
    }
}
