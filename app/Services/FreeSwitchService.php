<?php

namespace App\Services;

use App\Events\FreeswitchEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

class FreeSwitchService
{
    private $host;
    private $port;
    private $password;
    private $client;

    /**
     * Constructor for initializing the connection parameters.
     *
     * This constructor initializes the connection parameters such as host, port, and password
     * required to establish a connection with the server.
     *
     * @param string $host The host address of the server.
     * @param int $port The port number used for the connection.
     * @param string $password The password required for authentication.
     */
    public function __construct($host, $port, $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->client = null;
    }

    /**
     * Establishes a connection to the FreeSWITCH ESL server.
     *
     * This method attempts to establish a connection to the FreeSWITCH Event Socket Layer (ESL) server
     * using the provided host, port, and password. It authenticates with the server and returns
     * true if the connection is successful and authentication is successful, otherwise returns false.
     *
     * @return bool True if the connection and authentication are successful, otherwise false.
     */
    public function connect()
    {
        $this->client = fsockopen($this->host, $this->port, $errno, $errstr);
        if (!$this->client) {
            Log::error("Failed to connect to FreeSWITCH ESL: $errno - $errstr");
            return false;
        }

        Log::info("Connected to FreeSWITCH ESL");
        fwrite($this->client, "auth {$this->password}\n\n");

        // Read the response line by line
        $response = '';
        while (!feof($this->client)) {
            $response .= fgets($this->client);

            // Check if the response contains 'OK' indicating successful authentication
            if (strpos($response, 'OK') !== false) {
                Log::info("Auth Success");
                return true; // Authentication successful
            }

            // Check if the response contains 'ERR' indicating authentication failure
            if (strpos($response, 'ERR') !== false) {
                Log::info("Auth Failure");
                fclose($this->client); // Close the connection
                return false; // Authentication failed
            }
        }

        // Authentication successful
        return true;
    }

    /**
     * Subscribes to a specific event.
     *
     * This method subscribes to a specific event on the FreeSWITCH ESL server by sending
     * the appropriate command to the server. The event type is specified as a parameter.
     *
     * @param string $event The name of the event to subscribe to.
     */
    public function subscribe($event)
    {
        $this->sendCommand("event json $event");
    }

    /**
     * Sends a command to the FreeSWITCH ESL server.
     *
     * This method sends a command to the FreeSWITCH Event Socket Layer (ESL) server
     * using the established client connection. The command string is provided as
     * a parameter.
     *
     * @param string $command The command to be sent to the server.
     */
    public function sendCommand($command)
    {
        fwrite($this->client, "$command\n\n");
        Log::info("Command send to FreeSWITCH ESL");
    }

    /**
     * Starts listening for events from the FreeSWITCH ESL server.
     *
     * This method continuously listens for incoming data from the FreeSWITCH
     * Event Socket Layer (ESL) server. It processes the received data, extracts
     * JSON-formatted events, and logs them. Optionally, you can dispatch Laravel
     * events or perform other actions based on the received events.
     */
    public function startListening()
    {
        // Continuously listen for incoming data until the connection is closed
        while (!feof($this->client)) {
            // Read a line of data from the client
            $data = fgets($this->client);

            // Split the data into separate events using double newline as delimiter
            $events = explode("\n\n", $data);

            // Process each event individually
            foreach ($events as $event) {
                // Trim whitespace from the event
                $event = trim($event);

                // Check if the event is not empty
                if (!empty($event)) {
                    // Extract JSON data from the event string
                    $eventData = $this->extractJsonFromString($event);

                    // Check if JSON data was successfully extracted
                    if ($eventData) {
                        Event::dispatch(new FreeswitchEvent($eventData));
                    }
                }
            }
        }
        // fclose($this->client);
        // Log::info("Connection closed");
    }

    /**
     * Disconnect freeswitche.
     *
     * @return void
     */
    public function disconnect()
    {
        fclose($this->client);
        Log::info("Disconnected from FreeSWITCH ESL");
    }

    /**
     * Extracts JSON data from a string.
     *
     * This method extracts JSON-formatted data from the provided string.
     * It searches for the first occurrence of '{' (opening brace) and the last
     * occurrence of '}' (closing brace) within the string, and extracts the
     * substring between them. The extracted substring is then validated and
     * parsed as JSON, and the resulting associative array is returned.
     *
     * @param string $str The string from which JSON data is to be extracted.
     * @return array|null The associative array containing the extracted JSON data,
     *                     or null if no valid JSON data could be extracted.
     */
    private function extractJsonFromString($str)
    {
        // Find the index of the first '{' (opening brace) in the string
        $startIndex = strpos($str, "{");

        // Find the index of the last '}' (closing brace) in the string
        $endIndex = strrpos($str, "}");

        // Check if either the opening or closing brace was not found,
        // or if the opening brace comes after the closing brace
        if ($startIndex === false || $endIndex === false || $startIndex >= $endIndex) {
            return null;
        }

        // Extract the substring between the opening and closing braces,
        // including both braces
        $jsonString = substr($str, $startIndex, $endIndex - $startIndex + 1);

        // Replace single quotes with double quotes to ensure valid JSON syntax
        $validJsonString = str_replace("'", '"', $jsonString);

        // Parse the valid JSON string into an associative array
        $jsonObject = json_decode($validJsonString, true);

        // Return the resulting associative array
        return $jsonObject;
    }
}
