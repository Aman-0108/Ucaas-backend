<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\FreeSwitchController;
use Illuminate\Console\Command;

class ActiveCalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activecall:start';

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
     * @return int
     */
    public function handle()
    {
        // Continuously listen for events from the FreeSWITCH server
        while (true) {
            // Inform that connection is successful   
            $this->info('Listening for active calls...');

            // Get active calls
            $this->getActiveCalls();

            sleep(3);
        }
    }

    /**
     * Get the active calls from FreeSwitch using the FreeSwitchController.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response containing the active calls.
     */
    protected function getActiveCalls()
    {
        $this->info('Fetching active calls...');
        // Create an instance of the FreeSwitchController
        $fsController = new FreeSwitchController();

        // Call the getActiveCalls method of the FreeSwitchController to get the active calls
        $fsController->getActiveCall();
    }
}
