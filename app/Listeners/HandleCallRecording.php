<?php

namespace App\Listeners;

use App\Events\CallRecorded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Http\Controllers\Api\WebSocketController;
use Illuminate\Support\Facades\Log;

class HandleCallRecording
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
     * @param  \App\Events\CallRecorded  $event
     * @return void
     */
    public function handle(CallRecorded $response)
    {
        // Extract relevant information from the event object
        $formattedData = $response->events;

        $data = [];

        // Extract specific call record related data if available

        if (array_key_exists("fs_call_uuid", $formattedData)) {
            $data['fs_call_uuid'] = $formattedData['fs_call_uuid'];
        }

        if (array_key_exists("Record-File-Path", $formattedData)) {
            $data['Record-File-Path'] = $formattedData['Record-File-Path'];
        }

        // Prepare a customized response format
        $customizedResponse = [
            'key' => 'CallRecord',
            'result' => $data,
        ];

        // Initialize WebSocket controller
        $socketController = new WebSocketController();

        // Send the customized response to the UI via WebSocket
        $socketController->send($customizedResponse);
    }
}
