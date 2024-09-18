<?php

namespace App\Console\Commands;

use App\WebSocket\SocketHandler;
use Illuminate\Console\Command;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

use React\EventLoop\Loop;
use React\Socket\SocketServer;
use React\Socket\SecureServer;

class WebSocketServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

   /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Use the default loop
        $loop = Loop::get();

        // Create a regular TCP server
        $socket = new SecureServer(  
            new SocketServer($loop, config('services.websocket.port')),  
            config('services.websocket.ssl')
        );  

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new SocketHandler()
                )
            ),
            $socket
        );

        $this->info("WebSocket server started at wss://localhost:" . config('services.websocket.port'));

        $server->run();

        return Command::SUCCESS;
    }
}
