<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunAllCommands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all necessary commands';

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
        $exitCode1 = $this->call('websocket:init');
        if ($exitCode1 !== 0) {
            $this->error('websocket:init failed.');
            return $exitCode1; // Exit if the first command fails
        }

        $exitCode2 = $this->call('freeswitch:start');
        if ($exitCode2 !== 0) {
            $this->error('freeswitch:start failed.');
            return $exitCode2; // Exit if the first command fails
        }

        $exitCode3 = $this->call('activecall:start');
        if ($exitCode3 !== 0) {
            $this->error('activecall:start failed.');
            return $exitCode3; // Exit if the second command fails
        }

        $this->info('All commands have been executed successfully.');
    }
}
