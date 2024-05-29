<?php

namespace App\Listeners;

use App\Events\FreeSwitchShutDown;
use App\Http\Controllers\Api\WebSocketController;
use App\Models\Extension;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class FreeSwitchShutDownListener
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
     * @param  \App\Events\FreeSwitchShutDown  $event
     * @return void
     */
    public function handle(FreeSwitchShutDown $response)
    {
        // Extract relevant information from the event object
        $formattedData = $response->events;

        $result = $this->setSofiaStatusFalse();

        // Send the result of the extension update to the WebSocket clients
        $socketController = new WebSocketController();
        $socketController->send($result);
    }

    /**
     * Sets the 'sofia_status' column to false for all extensions.
     * Retrieves and returns the extensions with 'sofia_status' set to true.
     *
     * This method updates the 'sofia_status' column to false for all extensions
     * using the Extension model. Then, it retrieves extensions with 'sofia_status'
     * set to true and constructs a customized response containing relevant data.
     *
     * @return array The customized response containing extensions with 'sofia_status' set to true.
     */
    public function setSofiaStatusFalse()
    {
        // Update 'sofia_status' to false for all extensions
        Extension::where(['sofia_status' => true])->update(['sofia_status' => false]);

        // Retrieve extensions with 'sofia_status' set to true
        $result = Extension::where('sofia_status', true)->get(['extension', 'sofia_status', 'account_id']);

        // Prepare a customized response format
        $customizedResponse = [
            'key' => 'UserRegister',
            'result' => $result,
        ];

        // Return the customized response
        return $customizedResponse;
    }
}
