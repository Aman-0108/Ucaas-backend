<?php

namespace App\WebSocket;

use App\Models\Extension;
use App\Models\User;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

use Laravel\Sanctum\PersonalAccessToken;

class SocketHandler implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        // $this->clients = new \SplObjectStorage;
        $this->clients = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // New connection opened
        echo "New connection! ({$conn->resourceId})\n";

        $this->clients[$conn->resourceId] = $conn;
        // Store authenticated user's connection
        // $this->clients->attach($conn);
        $query = $conn->httpRequest->getUri()->getQuery();

        // Parse query parameters to get user credentials or token
        parse_str($query, $queryParams);

        // Check if user is authenticated based on query parameters
        $type = $queryParams['type'] ?? null;

        if ($type != 'admin') {
            // Simulate authentication
            $userId = $this->authenticateUser($query);

            if (!$userId) {
                // Send authentication failure response
                $conn->send(json_encode(['authenticated' => false]));
                $conn->close(); // Close connection
                return;
            } else {
                // $this->clients[$conn->resourceId] = $conn;

                $resourceId = $conn->resourceId;

                // Update user status to connected in the database
                User::where('id', $userId)->update(['socket_session_id' => $resourceId, 'socket_status' => 'online']);

                echo "New connection ({$resourceId}) " . date('Y/m/d h:i:sa') . "\n";

                $conn->send(json_encode(['authenticated' => true]));

                $this->getOnlineExtensions();

                $this->getOnlineUsers();
            }
        }
    }

    /**
     * Handle incoming messages from WebSocket clients.
     *
     * This method is called when a message is received from a WebSocket client.
     * It takes the sending connection ($from) and the received message ($msg) as parameters.
     *
     * The method checks the type of the received message (string or array) and performs different actions accordingly.
     * If the message is a string, it calls the onDataReceived method to handle the message.
     * If the message is an array, it checks the 'action' key in the array to determine the type of action to perform.
     * It then switches based on the value of the 'action' key and handles different actions accordingly.
     * If the message is neither a string nor an array, it outputs a message indicating the unknown type of the message.
     *
     * @param \Ratchet\ConnectionInterface $from The sending WebSocket connection.
     * @param mixed $msg The received message from the WebSocket client.
     * @return void
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        // Message received
        if (isset($msg)) {
            if (is_string($msg)) {
                // Message is a string
                echo "Received string message: $msg\n";
                $this->onDataReceived($msg, $from);
            } elseif (is_array($msg)) {
                // Message is an array
                echo "Received array message:\n";
                $data = json_decode($msg, true);

                if (array_key_exists("action", $data)) {
                    switch ($data['action']) {
                        case 'getOnlineUsers':
                            break;
                            // Handle other actions...
                    }
                }
            } else {
                // Message is neither a string nor an array
                echo "Received message of unknown type\n";
            }
        }
    }

    /**
     * Handle WebSocket connection closure.
     *
     * This method is called when a WebSocket connection is closed.
     * It takes the closed connection as a parameter ($conn).
     *
     * The method outputs a message indicating that the connection has been closed,
     * along with the ID of the closed connection.
     *
     * Additionally, it removes the closed connection from the list of clients,
     * as the server can no longer send messages to it.
     *
     * @param \Ratchet\ConnectionInterface $conn The closed WebSocket connection.
     * @return void
     */
    public function onClose(ConnectionInterface $conn)
    {
        // Connection closed
        echo "Connection {$conn->resourceId} has disconnected\n";

        // The connection is closed, remove it, as we can no longer send it messages
        // $this->clients->detach($conn);
        unset($this->clients[$conn->resourceId]);

        $querystring = $conn->httpRequest->getUri()->getQuery();

        parse_str($querystring, $queryarray);

        $userId = $this->authenticateUser($conn->httpRequest->getUri()->getQuery());

        if ($userId) {
            User::where('id', $userId)->update(['socket_session_id' => NULL, 'socket_status' => 'offline']);
        }

        $this->getOnlineUsers();

        // $userId = $queryarray['user_id'];

        // if (isset($queryarray['user_id'])) {
        //     User::where('id', $userId)->update(['socket_session_id' => NULL, 'socket_status' => 'offline']);
        // }
    }

    /**
     * Handle WebSocket connection errors.
     *
     * This method is called when an error occurs on a WebSocket connection.
     * It takes the connection where the error occurred ($conn) and the exception object ($e) as parameters.
     *
     * The method outputs a message indicating that an error has occurred, along with the error message
     * retrieved from the exception object.
     *
     * Additionally, it closes the connection to prevent further communication on the affected connection.
     *
     * @param \Ratchet\ConnectionInterface $conn The WebSocket connection where the error occurred.
     * @param \Exception $e The exception object representing the error.
     * @return void
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        // Error occurred
        echo "An error has occurred: {$e->getMessage()}\n";

        // Close the connection to prevent further communication on the affected connection
        $conn->close();
    }

    /**
     * Handle data received from WebSocket clients.
     *
     * This method is responsible for broadcasting data received from one WebSocket client
     * to all other connected WebSocket clients. It takes the received message ($msg) and
     * optionally the sender WebSocket client ($from) as parameters.
     *
     * The method iterates through each connected WebSocket client and sends the received
     * message to all clients except the sender. This allows for real-time communication
     * between multiple WebSocket clients.
     *
     * @param mixed $msg The data received from the WebSocket client.
     * @param \Ratchet\ConnectionInterface|null $from (Optional) The WebSocket client that sent the data.
     * @return void
     */
    public function onDataReceived($msg, $from = null)
    {
        // Broadcast data to connected WebSocket clients
        foreach ($this->clients as $client) {
            // Exclude the sender from receiving the message
            if ($from !== $client) {
                // Send the received message to each connected client
                $client->send(json_encode($msg));
            }
        }
    }

    /**
     * Send a message to a specific client.
     *
     * This function sends a message to a client identified by a resource ID.
     *
     * @param string $resourceId The resource ID of the client to send the message to.
     * @param mixed $message The message to be sent to the client.
     *
     * @return void
     */
    public function sendMessageToClient($resourceId, $message)
    {
        // Check if the client with the provided resource ID exists
        if (isset($this->clients[$resourceId])) {
            $client = $this->clients[$resourceId];

            // Send your message to the client
            $message = json_encode($message);
            $client->send($message);
        } else {
            // If the client with the provided resource ID is not found, display an error message
            echo "Client with resource ID $resourceId not found.";
        }
    }

    /**
     * Authenticate user based on query parameters.
     *
     * This method is responsible for authenticating a user based on the query parameters passed in the WebSocket connection URL.
     * It takes the query string from the WebSocket connection URL as input ($query).
     *
     * The method parses the query parameters to extract user credentials or token. It then validates the user credentials
     * or token against your authentication system. If authentication succeeds, it returns the user ID. Otherwise, it returns null.
     *
     * @param string $query The query string from the WebSocket connection URL containing user credentials or token.
     * @return int|null The user ID if authentication succeeds, otherwise null.
     */
    protected function authenticateUser($query)
    {
        // Parse query parameters to get user credentials or token
        parse_str($query, $queryParams);

        // Check if user is authenticated based on query parameters
        $token = $queryParams['token'] ?? null;

        // Validate user credentials or token
        // Check if $userId and $token are valid in your authentication system
        if ($token) {
            return ($this->isValidToken($token)) ? $this->isValidToken($token) : false;
        }

        // Return null if authentication fails
        return null;
    }

    /**
     * Validate the provided personal access token.
     *
     * This function checks whether the provided personal access token is valid.
     *
     * @param string $token The personal access token to validate.
     *
     * @return mixed Returns the ID of the token owner if the token is valid, otherwise returns false.
     */
    protected function isValidToken($token)
    {
        // Find token details by token value
        $tokenDetails = PersonalAccessToken::findToken($token);

        // If token details are found, return the ID of the token owner; otherwise, return false
        return ($tokenDetails) ? $tokenDetails->tokenable->id : false;
    }

    /**
     * Retrieve online extensions.
     *
     * This function retrieves online extensions by querying the Extension model
     * for entries where 'sofia_status' is true. It then constructs a customized
     * response and sends it to the onDataReceived method.
     *
     * @return void
     */
    protected function getOnlineExtensions()
    {
        // Query the Extension model for online extensions
        $result = Extension::where('sofia_status', true)->get(['extension', 'sofia_status', 'account_id']);

        // If online extensions are found
        if ($result) {
            // Construct a customized response
            $customizedResponse = [
                'key' => 'UserRegister',
                'result' => $result,
            ];

            // Send the customized response to onDataReceived method
            $this->onDataReceived(json_encode($customizedResponse));
        }
    }

    /**
     * Retrieve online users.
     *
     * This function retrieves online users by querying the User model
     * for entries where 'socket_status' is true. It then constructs a customized
     * response and sends it to the onDataReceived method.
     *
     * @return void
     */
    protected function getOnlineUsers()
    {
        // Query the User model for online users
        $result = User::where('socket_status', true)->get(['id', 'name', 'email']);

        // If online users are found
        if ($result) {
            // Construct a customized response
            $customizedResponse = [
                'key' => 'onlineUser',
                'result' => $result,
            ];

            // Send the customized response to onDataReceived method
            $this->onDataReceived(json_encode($customizedResponse));
        }
    }
}
