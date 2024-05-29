<?php

namespace App\Listeners;

use App\Events\ChannelHangupComplete;
use App\Http\Controllers\Api\WebSocketController;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ChannelHangupCompleteListener
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
     * @param  object  $event
     * @return void
     */
    public function handle(ChannelHangupComplete $response)
    {
        $formattedData = $response->events;
       
        $customizedResponse = [
            'key' => 'ChannelHangupComplete',
            'result' => $formattedData,
            // 'userId' => 3
        ];

        $socketController = new WebSocketController();

        $socketController->send($customizedResponse);
    }
}
