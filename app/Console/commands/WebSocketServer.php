<?php

namespace App\Console\Commands;

use App\WebSocket\SocketHandler;
use Illuminate\Console\Command;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

// use React\EventLoop\Loop;
// use React\Socket\SocketServer;
// use React\Socket\SecureServer;

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
        $this->info(`WebSocket server started at ws://localhost:config('services.websocket.port')`);

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new SocketHandler()
                )
            ),
            config('services.websocket.port')
        );

        $server->run();

        $app = new HttpServer(
            new WsServer(
                new SocketHandler()
            )
        );

         // Use the default loop
        //  $loop = Loop::get();
        // Create a WebSocket server
        // $webSock = new SocketServer('0.0.0.0:8080', [], $loop);

        // $webSock = new SecureServer($webSock, $loop, [
        //     'local_cert' => 'CRT_PATH', 
        //     'local_pk'=> 'KEY_PATH', 
        //     'allow_self_signed' => true, 
        //     'verify_peer' => false
        // ]);

        // $webSock = new IoServer($app, $webSock, $loop);

        return Command::SUCCESS;
    }
}
