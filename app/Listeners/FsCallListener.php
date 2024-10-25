<?php

namespace App\Listeners;

use App\Events\FsCallEvent;
use App\Http\Controllers\Api\WebSocketController;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class FsCallListener
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
    public function handle(FsCallEvent $response)
    {
        // Extract relevant information from the event object
        $formattedData = $response->events;

        // status
        $answerState = $formattedData['Answer-State'];

        if ($answerState == 'ringing') {
            $this->ringing($formattedData);
        } else if ($answerState == 'answered') {
            $this->answered($formattedData);
        } else if ($answerState == 'hangup') {
            $this->hangup($formattedData);
        } else {
            echo '\n';
            echo 'others';
        }
    }

    /**
     * Initiates the ringing process and sends relevant data to the UI.
     *
     * This method echoes 'ringing' to indicate the start of the process and then sends
     * the provided data to the UI using the sendToUi() method.
     *
     * @param mixed $data The data to be sent to the UI.
     * @return void
     */
    public function ringing($data)
    {
        echo '\n';
        echo 'ringing';
        $this->sendToUi($data);
    }

    /**
     * Handles the answered event and sends relevant data to the UI.
     *
     * This method echoes 'answered' to indicate the event and then sends
     * the provided data to the UI using the sendToUi() method.
     *
     * @param mixed $data The data to be sent to the UI.
     * @return void
     */
    public function answered($data)
    {
        echo '\n';
        echo 'answered';
        $this->sendToUi($data);
    }

    /**
     * Handles the hangup event and sends relevant data to the UI.
     *
     * This method echoes 'hangup' to indicate the event and then sends
     * the provided data to the UI using the sendToUi() method.
     *
     * @param mixed $data The data to be sent to the UI.
     * @return void
     */
    public function hangup($data)
    {
        echo '\n';
        echo 'hangup';
        $this->sendToUi($data);
    }

    /**
     * Prepares and sends relevant call data to the UI via WebSocket.
     *
     * This method extracts specific call-related data from the provided array,
     * prepares a customized response format, and sends it to the UI using a WebSocket connection.
     *
     * @param array $data The array containing call-related data.
     * @return void
     */
    public function sendToUi($data)
    {
        $response = [];

        // Extract specific call-related data if available

        if (array_key_exists("Answer-State", $data)) {
            $response['Answer-State'] = $data['Answer-State'];
        }

        if (array_key_exists("Caller-Destination-Number", $data)) {
            $response['Caller-Destination-Number'] = $data['Caller-Destination-Number'];
        }

        if (array_key_exists("Call-Direction", $data)) {
            $response['Call-Direction'] = $data['Call-Direction'];
        }

        if (array_key_exists("Caller-Callee-ID-Number", $data)) {
            $response['Caller-Callee-ID-Number'] = $data['Caller-Callee-ID-Number'];
        }

        if (array_key_exists("Hangup-Cause", $data)) {
            $response['Hangup-Cause'] = $data['Hangup-Cause'];
        }

        if (array_key_exists("Hangup-Cause", $data)) {
            $response['Hangup-Cause'] = $data['Hangup-Cause'];
        }

        if (array_key_exists("Hangup-Cause", $data)) {
            $response['Hangup-Cause'] = $data['Hangup-Cause'];
        }

        if (array_key_exists("Caller-Caller-ID-Number", $data)) {
            $response['origin'] = $data['Caller-Caller-ID-Number'];
        }

        if (array_key_exists("Other-Leg-Destination-Number", $data)) {
            $response['destination'] = $data['Other-Leg-Destination-Number'];
        } else {
            if (array_key_exists("Caller-Destination-Number", $data)) {
                $response['destination'] = $data['Caller-Destination-Number'];
            }
        }

        // Event-Date-Local
        if (array_key_exists("Event-Date-Local", $data)) {
            $response['time'] = $data['Event-Date-Local'];
        }

        if (array_key_exists("Call-Direction", $data)) {
            $response['Call-Direction'] = $data['Call-Direction'];
        }

        // Prepare a customized response format
        $customizedResponse = [
            'key' => 'CallState',
            'result' => $response,
        ];

        // Initialize WebSocket controller
        $socketController = new WebSocketController();

        // Send the customized response to the UI via WebSocket
        $socketController->send($customizedResponse);
    }
}
