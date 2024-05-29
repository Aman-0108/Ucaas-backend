<?php

namespace App\Listeners;

use App\Events\ExtensionRegistration;
use App\Http\Controllers\Api\WebSocketController;
use App\Models\Extension;
use Illuminate\Support\Facades\Log;

class ExtensionRegistrationListner
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
     * @param  \App\Events\UserRegistration  $event
     *
     * This method is responsible for processing user registration events.
     * It takes a UserRegistration event object as a parameter ($response).
     * It extracts relevant information from the event object, such as the incoming extension and action (event subclass).
     * It retrieves extension details from the database based on the incoming extension.
     * If the extension exists, it updates the extension status based on the action.
     * It logs the formatted data for debugging purposes.
     * Finally, it sends the result of the extension update to the WebSocket clients.
     *
     * @return void
     */
    public function handle(ExtensionRegistration $response)
    {
        // Extract relevant information from the event object
        $formattedData = $response->events;

        $incomingExtension = $formattedData['from-user'];

        $action = $formattedData['Event-Subclass'];

        // Retrieve extension details from the database based on the incoming extension
        $extension = Extension::where(['extension' => $incomingExtension])->first();

        // If the extension exists, handle extension update based on the action
        if ($extension) {
            $result = $this->handleExtensionUpdate($action, $incomingExtension);

            // Send the result of the extension update to the WebSocket clients
            $socketController = new WebSocketController();
            $socketController->send($result);
        }
    }

    /**
     * Handle the update of Extension model based on the event subclass.
     *
     * @param string $action The event subclass to determine the action.
     * @param string $incomingExtension The incoming extension.
     * @return array The customized response containing the updated extension details.
     */
    protected function handleExtensionUpdate($action, $incomingExtension)
    {
        $value = ($action == 'sofia::register') ? true : false;

        // 'sofia::unregister'

        Extension::where(['extension' => $incomingExtension])->update(['sofia_status' => $value]);

        $result = Extension::where('sofia_status', true)->get(['extension', 'sofia_status', 'account_id']);

        $customizedResponse = [
            'key' => 'UserRegister',
            'result' => $result,
        ];

        return $customizedResponse;
    }
}
