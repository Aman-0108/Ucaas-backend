<?php

namespace App\Traits;

use App\Services\EventSocket;

trait Esl
{
    /**
     * Establishes a connection to the FreeSWITCH ESL server.
     *
     * This method retrieves the ESL (Event Socket Layer) configuration from the Laravel
     * configuration and uses it to establish a connection to the FreeSWITCH ESL server.
     * It creates an instance of the EventSocket class, initializes it with the provided
     * host, port, and password, and returns the ESL connection object.
     *
     * @return EventSocket The ESL connection object.
     */
    public function esl()
    {
        // Retrieve ESL configuration parameters from Laravel configuration
        $host = config('services.esl.host');
        $port = config('services.esl.port');
        $password = config('services.esl.password');

        // Create a new EventSocket instance
        $ns = new EventSocket();

        // Create and return the ESL connection object
        $esl = $ns->create($host, $port, $password);

        return $esl;
    }
}
