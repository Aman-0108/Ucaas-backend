<?php

namespace App\Http\Controllers;

use App\Models\UserStatus;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;

class RatchetSocketController extends Controller implements MessageComponentInterface
{
    protected $clients;
    protected $authenticatedConnections;

    /**
     * WebSocket server constructor.
     *
     * Initialize properties and dependencies.
     */
    public function __construct()
    {
        // Initialize a SplObjectStorage to store client connections
        $this->clients = new \SplObjectStorage;

        // Initialize an array to store authenticated connections
        $this->authenticatedConnections = [];
    }

    /**
     * Handle the onOpen event when a new connection is opened.
     *
     * This method is called when a new WebSocket connection is opened.
     * It simulates authentication based on the query parameters in the connection URI.
     * If authentication succeeds, the connection is stored and the user status is updated to 'Online' in the database.
     * If authentication fails, an authentication failure response is sent, and the connection is closed.
     *
     * @param  \Ratchet\ConnectionInterface  $conn The new connection
     * @return void
     */
    public function onOpen(ConnectionInterface $conn)
    {
        // Simulate authentication
        // Extract user ID from the query parameters in the connection URI
        $userId = $this->authenticateUser($conn->httpRequest->getUri()->getQuery());

        // Check if authentication failed
        if (!$userId) {
            // Send authentication failure response
            $conn->send(json_encode(['authenticated' => false]));

            // Close the connection
            $conn->close();
            return;
        } else {
            // Store the authenticated user's connection
            // For demonstration purposes, assume user ID 1 is authenticated
            $this->clients->attach($conn, ['user_id' => 1]);

            // Retrieve the resource ID of the connection
            $resourceId = $conn->resourceId;

            // Update user status to connected in the database
            // Example: Update the status of user with ID 1 to 'Online'
            $this->updateUserStatus(1, true, $resourceId, 'Online');

            // Log a message indicating the new connection
            echo "New connection ({$conn->resourceId}) " . date('Y/m/d h:i:sa') . "\n";

            // Send authentication success response
            $conn->send(json_encode(['authenticated' => true]));
        }
    }

    /**
     * Handle incoming messages from clients.
     *
     * This method is called when a message is received from a client.
     * It decodes the message and performs actions based on the received data.
     * If the message contains a specific action, it executes the corresponding logic.
     * Otherwise, it echoes the message back to the sender.
     *
     * @param  \Ratchet\ConnectionInterface  $from The client connection sending the message
     * @param  string  $msg The received message
     * @return void
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        // $numRecv = count($this->clients) - 1;
        // echo sprintf(
        //     'Connection %d sending message "%s" to %d other connection%s' . "\n",
        //     $from->resourceId,
        //     $msg,
        //     $numRecv,
        //     $numRecv == 1 ? '' : 's'
        // );

        // foreach ($this->clients as $client) {
        //     if ($from !== $client) {
        //         // The sender is not the receiver, send to each client connected
        //         $client->send($msg);
        //     }
        // }

        // Decode the received message into an associative array
        $data = json_decode($msg, true);

        // Check if the received data contains an 'action' key
        if (array_key_exists("action", $data)) {
            // Handle different actions based on the value of the 'action' key
            switch ($data['action']) {
                case 'getOnlineUsers':
                    // Retrieve online users and send the list to the client
                    $onlineUsers = $this->getOnlineUsers();

                    $from->send(json_encode(['action' => 'onlineUsers', 'users' => $onlineUsers]));

                    break;
                    // Handle other actions...
            }
        } else {
            // If no specific action is specified, echo the message back to the sender
            $res = json_encode($data);
            $from->send($res);
        }

        // Print the decoded message data for debugging purposes
        print_r($data);
    }

    /**
     * Handle the onClose event when a connection is closed.
     *
     * This method is called when a WebSocket connection is closed.
     * It detaches the closed connection from the list of clients.
     * It also updates the user status in the database to indicate that the user is offline.
     *
     * @param  \Ratchet\ConnectionInterface  $conn The closed connection
     * @return void
     */
    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        // Retrieve the resource ID of the closed connection
        $resourceId = $conn->resourceId;

        // Update the user status in the database to indicate that the user is offline
        // Example: Update the status of user with ID 1 to 'Offline'
        $this->updateUserStatus(1, true, $resourceId, 'Offline');

        // Log a message indicating that the connection has been closed
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    /**
     * Handle errors that occur on a connection.
     *
     * This method is called when an error occurs on a connection.
     * It logs the error message and closes the connection.
     *
     * @param  \Ratchet\ConnectionInterface  $conn The connection where the error occurred
     * @param  \Exception  $e The exception object representing the error
     * @return void
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        // Log the error message
        echo "An error has occurred: {$e->getMessage()}\n";

        // Close the connection where the error occurred
        $conn->close();
    }

    /**
     * Authenticate the user based on the provided query parameters.
     *
     * This method parses the query parameters to extract user credentials or token.
     * It then validates the user credentials or token to determine if the user is authenticated.
     * You can implement your own logic to validate user credentials or token here.
     * In the provided example, it checks if the user ID and token are valid using the isValidToken() method.
     *
     * @param  string  $query The query string containing user credentials or token
     * @return int|null Returns the ID of the authenticated user if authentication succeeds, otherwise returns null
     */
    protected function authenticateUser($query)
    {
        // Parse query parameters to get user credentials or token
        parse_str($query, $queryParams);

        // Check if user is authenticated based on query parameters
        $userId = $queryParams['user_id'] ?? null;
        $token = $queryParams['token'] ?? null;

        // Validate user credentials or token
        if ($userId && $token && $this->isValidToken($userId, $token)) {
            return $userId;
        }

        // Return null if authentication fails
        return null;
    }

    /**
     * Check if the provided token is valid for the given user ID.
     *
     * This method validates the provided token against the specified user in your authentication system.
     * You can use Laravel's authentication system or implement your own logic here.
     * It attempts to find the Personal Access Token (PAT) associated with the token,
     * and then validates it to determine if it's still valid.
     *
     * @param  int|string  $userId The ID of the user
     * @param  string  $token The token to validate
     * @return bool Returns true if the token is valid for the user, otherwise returns false
     */
    protected function isValidToken($userId, $token)
    {
        // if (Auth::guard('sanctum')->onceUsingId($token)) {
        //     return true;
        // }

        // Attempt to find the Personal Access Token (PAT) associated with the token
        $pat = PersonalAccessToken::findToken($token);

        // Validate the token to determine if it's still valid
        return ($pat && $pat->validate()) ? true : false;
    }

    /**
     * Update the status of a user in the database.
     *
     * This method updates the status of a user in the database based on the provided parameters.
     * It uses the UserStatus model to update the user's status information.
     * If a record for the user ID already exists, it updates the existing record;
     * otherwise, it creates a new record for the user.
     *
     * @param  int|string  $userId The ID of the user
     * @param  bool  $connected Indicates whether the user is connected or not
     * @param  string|null  $resourceId The ID of the resource associated with the user's connection (optional)
     * @param  string|null  $status The status message or description (optional)
     * @return void
     */
    protected function updateUserStatus($userId, $connected, $resourceId, $status)
    {
        // Use the UserStatus model to update or create a record for the user
        UserStatus::updateOrCreate(
            ['user_id' => $userId],
            [
                'connected' => $connected,
                'connection_id' => $resourceId,
                'user_status' => $status
            ],
        );
    }

    /**
     * Get a list of online users.
     *
     * This method retrieves a list of users who are currently marked as connected.
     * You can adjust the logic to include additional conditions, such as checking
     * the last activity time to determine if a user is still online.
     *
     * @return \Illuminate\Database\Eloquent\Collection A collection of online users
     */
    protected function getOnlineUsers()
    {
        // This could involve querying the database or maintaining a list of connected users in memory
        return UserStatus::where('connected', true)
            //  ->where('created_at', '>=', now()->subMinutes(5))
            //  ->where('updated_at', '>=', now()->subMinutes(5))
            ->get();
    }
}
