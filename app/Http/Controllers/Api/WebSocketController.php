<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use WebSocket\Client;

class WebSocketController extends Controller
{
    public function send($data)
    {
        // WebSocket server URL
        $serverUrl = 'ws://192.168.1.88:'. config('services.websocket.port').'?type=admin';
        $client = new Client($serverUrl);

        // $client->text("Hello WebSocket.org!");
        $client->send(json_encode($data));
    }    
}
