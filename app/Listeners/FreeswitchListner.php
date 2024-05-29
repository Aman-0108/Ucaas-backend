<?php

namespace App\Listeners;

use App\Events\CallRecorded;
use App\Events\ChannelHangupComplete;
use App\Events\FreeswitchEvent;
use App\Events\FreeSwitchShutDown;
use App\Events\FsCallEvent;
use App\Events\ExtensionRegistration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
// use WebSocket\Client;

class FreeswitchListner
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
     * Handle incoming FreeSWITCH events.
     * 
     * This method is responsible for processing FreeSWITCH events received by the application.
     * It takes a FreeswitchEvent object as a parameter, which likely contains one or more events
     * received from the FreeSWITCH server. The method extracts these events, formats them as needed,
     * and then calls the handleEvent method to further process and handle each individual event.
     * 
     * @param FreeswitchEvent $response The FreeswitchEvent object containing the received events.
     * @return void
     */
    public function handle(FreeswitchEvent $response)
    {
        // Extract events from the FreeswitchEvent object
        $formattedData = $response->events;

        // Call the handleEvent method to process the extracted events
        $this->handleEvent($formattedData);
    }

    /**
     * Handle incoming FreeSWITCH events.
     *
     * This method is responsible for processing individual FreeSWITCH events received by the application.
     * It takes an array containing the event data as a parameter. The method first checks if the event data
     * is null or if the 'Event-Name' key is missing. If either condition is met, it handles the respective case
     * (such as logging an error or displaying a message) and returns early.
     * 
     * If the event data is valid and contains the 'Event-Name' key, the method switches based on the value
     * of 'Event-Name' to determine the type of event. It then calls a specific handler method corresponding
     * to the event type to further process the event data.
     * 
     * Additional cases can be added to the switch statement to handle more event types as needed.
     *
     * @param array|null $eventData An array containing the event data received from FreeSWITCH.
     * @return void
     */
    protected function handleEvent($eventData)
    {
        // Check if the event data is null
        if ($eventData === null) {
            // Handle the case where event data is null (optional)
            echo "Received null event data";
            return;
        }

        // Check if the 'Event-Name' key exists in the event data
        if (!isset($eventData['Event-Name'])) {
            // Handle the case where the 'Event-Name' key is missing (optional)
            echo "Missing 'Event-Name' key in event data";
            return;
        }

        switch ($eventData['Event-Name']) {
            case 'CHANNEL_CREATE':
                $this->handleChannelCreateEvent($eventData);
                break;
            case 'CHANNEL_ANSWER':
                $this->handleAnswer($eventData);
                break;
            case 'CHANNEL_STATE':
                $this->handleChannelState($eventData);
                break;
            case 'RECORD_STOP':
                $this->handleCallRecord($eventData);
                break;
            case 'CHANNEL_HANGUP_COMPLETE':
                $this->channelHangupComplete($eventData);
                break;
            case 'DIAL':
                $this->handleDialEvent($eventData);
                break;
            case 'HEARTBEAT':
                $this->handleHeartbeatEvent($eventData);
                break;
            case 'CUSTOM':
                $this->customEvent($eventData);
                break;
            case 'SHUTDOWN':
                $this->shutDown($eventData);
                break;
            case 'MODULE_UNLOAD':
                $this->moduleUnload($eventData);
                break;
                // Add more cases for additional event types as needed
            default:
                $this->allEvent($eventData);
                // Handle unknown event types or skip them
                break;
        }
    }

    /**
     * Handle Heartbeat event.
     *
     * This method is responsible for processing the Heartbeat event received from FreeSWITCH.
     * It takes an array containing the event data as a parameter. The method initiates a WebSocket
     * connection to a specified WebSocket server (e.g., for real-time updates or logging purposes).
     * It then sends the event data, encoded as JSON, over the WebSocket connection.
     * 
     * Optionally, you can check if the WebSocket connection is established before sending the data.
     * If the connection fails to establish, an error message is displayed.
     * 
     * @param array $eventData An array containing the event data received from FreeSWITCH.
     * @return void
     */
    protected function handleHeartbeatEvent($eventData)
    {
        // Create a WebSocket client instance
        // $client = new Client("ws://127.0.0.1:8091");

        // Send the event data to the WebSocket server (encoded as JSON)
        // $client->send(json_encode($eventData));
    }

    /**
     * Handle CUSTOM event.
     *
     * This method is responsible for processing a CUSTOM event received from FreeSWITCH.
     * It takes an array containing the event data as a parameter. The method performs any necessary
     * processing of the event data, such as logging, parsing JSON, saving it to a database, or other
     * application-specific tasks.
     * 
     * In this example, the method prints the event data to the console using print_r and logs
     * the event data to the application's log file using Laravel's Log::info method.
     * You should replace these example statements with your actual processing logic tailored
     * to your application's needs.
     * 
     * @param array $eventData An array containing the event data received from FreeSWITCH.
     * @return void
     */
    protected function customEvent($eventData)
    {
        $subclass = $eventData['Event-Subclass'];

        // Register/unregister of extension from freeswitch
        if ($subclass == 'sofia::register' || $subclass == 'sofia::unregister') {
            Event::dispatch(new ExtensionRegistration($eventData));
        }
    }

    /**
     * Handle CHANNEL_CREATE event.
     *
     * This method is responsible for processing the CHANNEL_CREATE event received from FreeSWITCH.
     * It takes an array containing the event data as a parameter. The method performs any necessary
     * processing of the event data, such as parsing JSON, saving it to a database, logging, or other
     * application-specific tasks.
     * 
     * In this example, the method simply outputs a message to the console containing the event data
     * encoded as JSON. You should replace this with the actual processing logic relevant to your application.
     * 
     * @param array $eventData An array containing the event data received from FreeSWITCH.
     * @return void
     */
    protected function handleChannelCreateEvent($eventData)
    {
        // Process the CHANNEL_CREATE event (parse JSON, save to database, etc.)
        echo '\n';
        echo "CHANNEL_CREATE event received: ";
    }

    /**
     * Handles the answer event.
     *
     * This method logs the event data as JSON for informational purposes.
     *
     * @param array $eventData The data related to the answer event.
     * @return void
     */
    protected function handleAnswer($eventData)
    {
        echo '\n';
        echo "CHANNEL_ANSWER";
        // Log the event data as JSON for informational purposes
        // Log::info(json_encode($eventData, true));
    }

    /**
     * Handle DIAL event.
     *
     * This method is responsible for processing the DIAL event received from FreeSWITCH.
     * It takes an array containing the event data as a parameter. The method performs any necessary
     * processing of the event data, such as parsing JSON, saving it to a database, logging, or other
     * application-specific tasks.
     * 
     * In this example, the method simply outputs a message to the console containing the event data
     * encoded as JSON. You should replace this with the actual processing logic relevant to your application.
     * 
     * @param array $eventData An array containing the event data received from FreeSWITCH.
     * @return void
     */
    protected function handleDialEvent($eventData)
    {
        // Process the DIAL event (parse JSON, save to database, etc.)
        echo '\n';
        echo "DIAL event received: ";
    }

    /**
     * Handles the shutdown event.
     *
     * This method dispatches an event to notify the application about
     * the shutdown of the FreeSwitch system, passing along the event data.
     *
     * @param array $eventData The data related to the shutdown event.
     * @return void
     */
    protected function shutDown($eventData)
    {
        // Dispatch an event to notify the application about the shutdown
        Event::dispatch(new FreeSwitchShutDown($eventData));
    }

    /**
     * Handles the module unload event.
     *
     * This method is a placeholder for handling the event when a module is unloaded.
     * Currently, it is commented out and does not perform any action.
     *
     * @param mixed $eventData The data related to the module unload event.
     * @return void
     */
    protected function moduleUnload($eventData)
    {
        // Log::info($eventData);
    }

    /**
     * Handles the channel state event.
     *
     * This method dispatches an event with the provided event data,
     * allowing other parts of the application to respond to changes
     * in the channel state.
     *
     * @param array $eventData The data related to the channel state event.
     * @return void
     */
    protected function handleChannelState($eventData)
    {
        // Dispatch an event with the provided event data
        Event::dispatch(new FsCallEvent($eventData));
    }

    /**
     * Handles all types of events.
     *
     * This method is a placeholder for handling all types of events.
     * Currently, it is commented out and does not perform any action.
     *
     * @param array $eventData The data related to the event.
     * @return void
     */
    protected function allEvent($eventData)
    {
    }

    /**
     * Handles the event when a channel hangup completes.
     *
     * This method formats the data related to the channel hangup completion,
     * dispatches an event with the formatted data, and notifies the application
     * about the completion of the hangup process.
     *
     * @param array $data The data related to the hangup completion event.
     * @return void
     */
    protected function channelHangupComplete($data)
    {
        // Format the data related to the channel hangup completion
        $response = channelHangupCompleteDataFormat($data);

        // Dispatch an event with the formatted data to notify the application
        Event::dispatch(new ChannelHangupComplete($response));
    }

    /**
     * Handle the recording of a call and dispatch an event.
     *
     * @param mixed $eventData The data related to the recorded call
     * @return void
     */
    protected function handleCallRecord($eventData)
    {
        echo '\n';
        echo ' RECORD_STOP';

        // Dispatch an event with the incoming call recorded data to notify the application
        Event::dispatch(new CallRecorded($eventData));
    }
}
