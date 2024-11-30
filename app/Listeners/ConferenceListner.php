<?php

namespace App\Listeners;

use App\Events\Conference;
use App\Http\Controllers\Api\WebSocketController;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ConferenceListner
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\Conference  $event
     * @return void
     */
    public function handle(Conference $response)
    {
        $result = $response->events;

        // Prepare a customized response format
        $customizedResponse = [
            'key' => 'Conference',
            'result' => $result,
        ];

        // Send the result of the conference  to the WebSocket clients
        $socketController = new WebSocketController();
        $socketController->send($customizedResponse);
    }
}
