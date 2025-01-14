<?php

namespace App\Console\Commands;

use App\Services\FreeSwitchService;
use Illuminate\Console\Command;

class Freeswitch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freeswitch:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Freeswitch Server';

    protected $freeSwitch;

    public function __construct(FreeSwitchService $freeSwitch)
    {
        parent::__construct();
        $this->freeSwitch = $freeSwitch;
    }

    /**
     * This function is responsible for handling incoming events from a FreeSWITCH server.
     * It attempts to establish a connection with the FreeSWITCH server and subscribes to
     * receive notifications for all events. Upon successful authentication, it enters
     * into a loop where it continuously listens for events from the FreeSWITCH server.
     * 
     * @return void
     */
    public function handle()
    {
        // Retry loop
        while (true) {
            // Attempt to connect to FreeSWITCH ESL
            $connected = $this->connectToESL();

            // If connection is successful
            if ($connected) {
                // Inform that connection is successful
                $this->info('Connected to FreeSWITCH ESL.');

                // Subscribe to receive notifications for all events
                $this->freeSwitch->subscribe('ALL');

                // Start listening for events
                $this->freeSwitch->startListening();
            } else {
                // If connection fails, inform the user and retry after a delay
                $this->info('Failed to connect to FreeSWITCH ESL. Retrying in 5 seconds...');
                sleep(5); // Retry after 5 seconds
            }
        }
    }

    /**
     * Attempt to connect to FreeSWITCH ESL (Event Socket Library).
     *
     * @return bool Returns true if the connection is successful, otherwise false.
     */
    protected function connectToESL()
    {
        try {
            // Attempt to connect to FreeSWITCH ESL
            $response = $this->freeSwitch->connect();

            return ($response) ? $response : false;
            // Return true if connection is successful

        } catch (\Exception $e) {
            // Log any connection errors (optional)
            $this->error('Error connecting to FreeSWITCH ESL: ' . $e->getMessage());

            // Return false if connection fails
            return false;
        }
    }
}
