<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use WebSocket\Client;

class WebSocketController extends Controller
{
    public function send($data)
    {
        // WebSocket server URL
        $serverUrl = 'ws://'.config('services.websocket.ip').':'. config('services.websocket.port').'?type=admin';
        $client = new Client($serverUrl);

        $client->send(json_encode($data));
    }    
}
