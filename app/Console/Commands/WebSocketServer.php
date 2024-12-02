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
        $app = new HttpServer(
            new WsServer(
                new SocketHandler()
            )
        );

        // Use the default loop
        $loop = Loop::get();
        // Create a WebSocket server
        $webSock = new SocketServer(config('services.websocket.ip') . ':' . config('services.websocket.port'), [], $loop);

        $webSock = new SecureServer($webSock, $loop, [
            'local_cert' => config('services.websocket.ssl.local_cert'),
            'local_pk' => config('services.websocket.ssl.local_pk'),
            'allow_self_signed' => config('services.websocket.ssl.allow_self_signed'),
            'verify_peer' => config('services.websocket.ssl.verify_peer')
        ]);

        $webSock = new IoServer($app, $webSock, $loop);

        $this->info("WebSocket server started at wss://" . config('services.websocket.ip') . ":" . config('services.websocket.port'));

        $webSock->run();

        return Command::SUCCESS;
    }
}
