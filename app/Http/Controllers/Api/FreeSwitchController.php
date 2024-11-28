<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountBalance;
use App\Models\DidDetail;
use App\Models\Domain;
use App\Models\Extension;
use App\Traits\Esl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;


class FreeSwitchController extends Controller
{
    // Traits for ESL
    use Esl;

    protected $socket;
    protected $connected;

    /**
     * Constructor function for the class.
     *
     * Initializes the socket connection to the FreeSWITCH Event Socket.
     */
    public function __construct()
    {
        // Initialize the socket connection to the FreeSWITCH Event Socket
        $this->socket = $this->esl();
        $this->connected = $this->socket->is_connected();
    }

    /**
     * Handles the scenario where the FreeSWITCH server is not connected.
     *
     * @return \Illuminate\Http\JsonResponse JSON response indicating the server disconnection status.
     */
    public function disconnected(): JsonResponse
    {
        // Prepare the response data
        $response = [
            'status' => false, // Indicates the success status of the request
            'error' => 'Freeswitch server is not connected'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Reloads the XML configuration.
     *
     * @return \Illuminate\Http\JsonResponse JSON response indicating the status of the XML reload operation.
     */
    public function reloadXml(): JsonResponse
    {
        if ($this->connected) {
            // Check call state
            $response = $this->socket->request('api reloadxml');

            // Check if the string contains "+OK [Success]"
            if (strpos($response, "+OK [Success]") !== false) {
                // If it does, remove this substring
                $response = 'success';
            }

            // Prepare the response data
            $response = [
                'status' => true, // Indicates the success status of the request
                'data' => $response, // Contains the fetched extensions
                'message' => 'Successfully reloaded'
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected,
            return $this->disconnected();
        }
    }

    /**
     * Reloads the Access Control List (ACL).
     *
     * @return \Illuminate\Http\JsonResponse JSON response indicating the status of the ACL reload operation.
     */
    public function reloadacl(): JsonResponse
    {
        if ($this->connected) {
            // Check call state
            $response = $this->socket->request('api reloadacl');

            // Check if the string contains "+OK acl reloaded"
            if (strpos($response, "+OK acl reloaded") !== false) {
                // If it does, remove this substring
                $response = 'success';
            }

            // Prepare the response data
            $response = [
                'status' => true, // Indicates the success status of the request
                'data' => $response, // Contains the fetched extensions
                'message' => 'Successfully reloaded'
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected,
            return $this->disconnected();
        }
    }

    /**
     * Retrieves the registrations information.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing the registrations information.
     */
    public function showRegistrations(): JsonResponse
    {
        if ($this->connected) {
            // Check call state
            $response = $this->socket->request('api show registrations');

            // Check if the string contains "0 total"
            if (strpos($response, '0 total') !== false) {
                // No registrations found, return an empty response
                $response = [
                    'status' => true, // Indicates the success status of the request
                    'data' => [], // Contains the fetched extensions
                    'message' => 'Successfully fetched',
                ];

                // Return the response as JSON with HTTP status code 200 (OK)
                return response()->json($response, Response::HTTP_OK);
            }

            // Split the output by lines
            $lines = explode("\n", $response);

            // Combine the first two lines into a key-value pair
            $columns = explode(",", trim($lines[0], '"'));

            $parsedData = [];

            // Loop through the data lines
            for ($i = 1; $i < count($lines) - 2; $i++) {
                // Split the line by commas
                $values = explode(",", $lines[$i]);

                if (count($columns) == count($values)) {
                    // Combine column names with values to create key-value pairs
                    $entry = array_combine($columns, $values);

                    // Add the entry to the parsed data
                    $parsedData[] = $entry;
                }
            }

            // Prepare the response data
            $response = [
                'status' => true, // Indicates the success status of the request
                'data' => $parsedData, // Contains the fetched extensions
                'message' => 'Successfully fetched',
                'originalSrc' => $response
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected,
            return $this->disconnected();
        }
    }

    /**
     * Retrieves the status of the Sofia SIP Stack.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing the Sofia SIP Stack status information.
     */
    public function sofiaStatus(): JsonResponse
    {
        if ($this->connected) {
            // Check call state
            $response = $this->socket->request('api sofia status');

            // Split the output by lines
            $lines = explode("\n", $response);

            // Initialize an array to store the parsed data
            $parsedData = [];

            // Start parsing after the header line
            for ($i = 2; $i < count($lines) - 1; $i++) {
                // Split each line by tabs
                $fields = preg_split('/\s+/', trim($lines[$i]));

                // Ensure we have at least 4 fields before accessing them
                if (count($fields) >= 4) {
                    // Extract relevant information
                    $name = $fields[0];
                    $type = $fields[1];
                    $data = $fields[2];
                    $state = $fields[3];

                    // Store the information in an associative array
                    $parsedData[] = [
                        'Name' => $name,
                        'Type' => $type,
                        'Data' => $data,
                        'State' => $state
                    ];
                }
            }

            // Prepare the response data
            $response = [
                'status' => true, // Indicates the success status of the request
                'data' => $parsedData, // Contains the fetched extensions
                'message' => 'Successfully fetched',
                'originalSrc' => $response
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected, 
            return $this->disconnected();
        }
    }

    /**
     * Retrieves the status of the service.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing the status information.
     */
    public function status()
    {
        if ($this->connected) {
            // Check call state
            $response = $this->socket->request('api status');

            // Split the string into lines
            $lines = explode("\n", $response);

            // Initialize an empty array to store key-value pairs of status
            $data = array();

            // Iterate through each line of the response
            foreach ($lines as $line) {
                // Split each line into key-value pairs
                $parts = explode(' ', $line, 2);
                $key = $parts[0];
                $value = isset($parts[1]) ? $parts[1] : null;
                $data[$key] = $value;
            }

            // Prepare the response data
            $response = [
                'status' => true, // Indicates the success status of the request
                'data' => $data, // Contains the fetched extensions
                'message' => 'Successfully fetched'
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected,
            return $this->disconnected();
        }
    }

    /**
     * Initiates the shutdown process for the service.
     *
     * @param Socket $socket An instance of the socket class representing the connection.
     * @return \Illuminate\Http\JsonResponse JSON response indicating the status of the shutdown process.
     */
    public function shutDown(): JsonResponse
    {
        if ($this->connected) {
            // Check call state
            $response = $this->socket->request('api shutdown');

            // Prepare the response data
            $response = [
                'status' => true, // Indicates the success status of the request
                'data' => $response, // Contains the fetched extensions
                'message' => 'Successfully shutting down'
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected,
            return $this->disconnected();
        }
    }

    /**
     * Checks the status of ongoing calls.
     *
     * @return \Illuminate\Http\JsonResponse JSON response with status, data, and message
     */
    public function checkCallStatus(): JsonResponse
    {
        // Check if the socket is connected
        if ($this->socket->is_connected()) {
            // Send an API request to fetch call events
            $response = $this->socket->request('event json ALL');

            // Prepare the response data
            $responseData = [
                'status' => true, // Indicates the success status of the request
                'data' => $response, // Contains the response from the server
                'message' => 'Successfully fetched call status'
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($responseData, Response::HTTP_OK);
        } else {
            // If the socket is not connected, return a disconnected response
            return $this->disconnected();
        }
    }

    /**
     * Initiates a call between two extensions.
     *
     * @param  Request  $request  HTTP request containing src, destination, and account_id
     * @return \Illuminate\Http\JsonResponse JSON response with status and message
     */
    public function call(Request $request)
    {
        // Check if a user is authenticated
        $user = $request->user();

        // Check if the socket is connected
        if ($this->socket->is_connected()) {

            // Perform validation on the request data
            $validator = Validator::make(
                $request->all(),
                [
                    'src' => 'required|string',
                    'destination' => 'required|string',
                    'account_id' => 'required|exists:accounts,id'
                ]
            );

            // Check if validation fails
            if ($validator->fails()) {
                // If validation fails, return a 403 Forbidden response with validation errors
                $response = [
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validator->errors()
                ];

                return response()->json($response, Response::HTTP_FORBIDDEN);
            }

            // Get user's ID
            $srcUserId = $user->id;

            // Extract data from request
            $account_id = $request->account_id;
            $src = $request->src;
            $destination = $request->destination;

            // check extension is assigned with any user or not
            $srcCheck = $this->checkExtensionWithAssignedUser($account_id, $src);
            $destinationCheck = $this->checkExtensionWithAssignedUser($account_id, $destination);

            // if any extension is not assigned to any user then return error
            if (empty($srcCheck) || empty($destinationCheck)) {
                $response = [
                    'status' => false, // Indicates the success status of the request
                    'message' => (empty($srcCheck) ? $src . ',' : '') . (empty($destinationCheck) ? $destination : '') . ' not available.'
                ];

                // Return the response as JSON with HTTP status code 422 
                return response()->json($response, Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Check if the user is allowed to make the call
            if ($srcUserId !== $srcCheck->user) {
                $response = [
                    'status' => false, // Indicates the success status of the request
                    'message' => 'You dont have permission to make call.'
                ];

                // Return the response as JSON with HTTP status code 422 
                return response()->json($response, Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Check if source and destination extensions are online
            $srcCheckExtensionActive = $this->CheckExtensionActive($account_id, $src);
            $destinationCheckExtensionActive = $this->CheckExtensionActive($account_id, $destination);

            if (!$srcCheckExtensionActive || !$destinationCheckExtensionActive) {
                $response = [
                    'status' => false, // Indicates the success status of the request
                    'message' => ((!$srcCheckExtensionActive) ? $src . ',' : '') . ((!$destinationCheckExtensionActive) ? $destination : '') . ' offline.'
                ];

                // Return the response as JSON with HTTP status code 422 
                return response()->json($response, Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Construct the command to initiate the call
            $destination = 'user/' . $destination;
            $cmd_add = "api originate {origination_caller_id_number=$src}$destination $src default XML\n\n";

            // Send the command to the server via socket
            $response = $this->socket->request($cmd_add);

            // Prepare success response
            $response = [
                'status' => true, // Indicates the success status of the request
                'message' => 'success.'
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);

            // $checkDestinationAvailable = Extension::where(['sofia_status' => true])->pluck('extension');

            // $onlineExtensions = Extension::where(['user' => $userId,'sofia_status' => true])->pluck('extension');

            // $onlineExtensions = Extension::where('sofia_status', true)->pluck('extension');

            // if ($onlineExtensions->isEmpty()) {
            //     // Handle the case where the response is empty
            //     // Prepare the response data
            //     $response = [
            //         'status' => true, // Indicates the success status of the request
            //         'message' => 'No extensions available to make call.'
            //     ];

            //     // Return the response as JSON with HTTP status code 200 (OK)
            //     return response()->json($response, Response::HTTP_OK);
            // } else {
            //     if ($onlineExtensions->contains($src) && $onlineExtensions->contains($destination)) {
            //         $destination = 'user/'. $destination;
            //         $cmd_add = "api originate {origination_caller_id_number=$src}$destination $src default XML\n\n";

            //         $response = $this->socket->request($cmd_add);
            //     } else {
            //         $response = [
            //             'status' => true, // Indicates the success status of the request
            //             'message' => 'Extension not available.'
            //         ];

            //         // Return the response as JSON with HTTP status code 200 (OK)
            //         return response()->json($response, Response::HTTP_OK);
            //     }
            // }

            // $destination = 'user/1003';
            // $src = '1002';
            // // $application = "bridge"; // Application to execute after call is answered
            // // $applicationData = "sofia/internal/5555@192.168.1.150"; // Destination to bridge the call to
            // // $timeout = "30"; // Timeout in seconds
            // // $callerId = "123456"; // Caller ID to use
            // // $cmd_add = "api originate $destination $application $applicationData $timeout $callerId\n\n";

        } else {
            return $this->disconnected();
        }
    }

    /**
     * Check if a specific extension is assigned to any user for a given account.
     *
     * This function queries the database to find an extension with the specified
     * account ID and extension number. It retrieves the extension along with its
     * associated user, if any.
     *
     * @param int $account_id The ID of the account to check.
     * @param string $extension The extension number to check.
     * @return mixed|null Returns an Extension model instance if found, or null if not found.
     * The Extension model instance may contain the associated user details.
     */
    public function checkExtensionWithAssignedUser($account_id, $extension)
    {
        // The result will include the associated user information, if any.
        $result = Extension::where(['account_id' => $account_id, 'extension' => $extension])->first();

        // Return the result, which may be an Extension model instance with associated user details,
        // or null if no extension is found for the given account and extension number.
        return $result;
    }

    /**
     * Check if a specific extension is active for a given account.
     *
     * This function queries the database to find an extension with the specified
     * account ID and extension number. It also checks if the extension's 'sofia_status'
     * field is set to true, indicating that the extension is active.
     *
     * @param int $account_id The ID of the account to check.
     * @param string $extension The extension number to check.
     * @return bool Returns true if the extension is active for the account, false otherwise.
     */
    public function CheckExtensionActive($account_id, $extension)
    {
        // Query the database to find the extension with the given account ID, extension number,
        // and 'sofia_status' set to true.
        $result = Extension::where(['account_id' => $account_id, 'extension' => $extension, 'sofia_status' => true])->first();

        // If a result is found, the extension is considered active, so return true.
        // Otherwise, return false.
        return (!empty($result)) ? true : false;
    }

    /**
     * Reloads the mod_callcenter module via API and returns status.
     *
     * @return \Illuminate\Http\JsonResponse JSON response with status, data, and message
     */
    public function reload_mod_callcenter(): JsonResponse
    {
        // Check if the socket is connected
        if ($this->connected) {

            // Prepare the command to reload mod_callcenter
            $cmd = 'api reload mod_callcenter' . PHP_EOL;

            // Send API request to reload mod_callcenter
            $response = $this->socket->request($cmd);

            $status = false;

            // Check if the response contains "+OK Reloading XML"
            if (strpos($response, "+OK Reloading XML") !== false) {
                // If it does, set status to true indicating success
                $status = true;
            }

            // Prepare the response data
            $responseData = [
                'status' => $status, // Indicates the success status of the request
                'data' => $response, // Contains the response from the server
                'message' => 'Successfully reloaded call center'
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($responseData, Response::HTTP_OK);
        } else {
            // If the socket is not connected, return a disconnected response
            return $this->disconnected();
        }
    }

    public function callcenter_config_queue_reload($queueName): JsonResponse
    {
        if ($this->connected) {

            $cmd = "api callcenter_config queue reload {$queueName}" . PHP_EOL;

            $response = $this->socket->request($cmd);

            $status = false;

            // Check if the response contains "+OK" indicating success
            if (strpos($response, "+OK") !== false) {
                // If it does, set status to true
                $status = true;
            }

            // Prepare the response data
            $responseData = [
                'status' => $status, // Indicates the success status of the request
                'data' => $response, // Contains the response from the server
                'message' => 'Successfully reload queue'
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($responseData, Response::HTTP_OK);
        } else {
            // If the socket is not connected, return a disconnected response
            return $this->disconnected();
        }
    }

    /**
     * Adds an agent to the call center via the API.
     *
     * @param string $agent_name The name of the agent to add.
     * @return \Illuminate\Http\JsonResponse JSON response with status, data, and message
     */
    public function callcenter_config_agent_add($agent_name, $agent_type = null): JsonResponse
    {
        if ($this->connected) {
            // Send API request to add an agent
            $cmd = "api callcenter_config agent add {$agent_name} $agent_type" . PHP_EOL;

            $response = $this->socket->request($cmd);

            $status = false;

            // Check if the response contains "+OK" indicating success
            if (strpos($response, "+OK") !== false) {
                // If it does, set status to true
                $status = true;
            }

            // Prepare the response data
            $responseData = [
                'status' => $status, // Indicates the success status of the request
                'data' => $response, // Contains the response from the server
                'message' => 'Successfully added agent'
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($responseData, Response::HTTP_OK);
        } else {
            // If the socket is not connected, return a disconnected response
            return $this->disconnected();
        }
    }

    /**
     * Sets the contact information for an agent in a call center via the API.
     *
     * @param string $agentName The name of the agent to set the contact for.
     * @param string $contact   The contact information to set for the agent, e.g.
     *                          "sip:username@domain.tld" or "user/1000".
     *
     * @return \Illuminate\Http\JsonResponse JSON response with status, data, and message
     */
    public function callcenter_config_agent_set_contact($agentName, $contact)
    {
        if ($this->connected) {
            // Construct the command to set the contact information for the agent
            $cmd = "api callcenter_config agent set contact {$agentName} {$contact}" . PHP_EOL;

            // Send the command to the FreeSwitch server and get the response
            $response = $this->socket->request($cmd);

            // Initialize the status to false
            $status = false;

            // Check if the response contains "+OK" indicating success
            if (strpos($response, "+OK") !== false) {
                // If it does, set status to true
                $status = true;
            }

            // Prepare the response data
            $response = [
                'status' => $status, // Indicates the success status of the request
                'data' => $response, // Contains the response from the server
                'message' => 'Successfully set contact for agent.'
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected, return a disconnected response
            return $this->disconnected();
        }
    }

    /**
     * Adds an agent to a tier in a call center via the API.
     *
     * @param string $queueName The name of the call center queue.
     * @param string $agentName The name of the agent to add to the tier.
     * @param int    $level     The tier level to set (1-10).
     * @param int    $position  The position to set the agent at in the tier.
     *
     * @return \Illuminate\Http\JsonResponse JSON response with status, data, and message
     */
    public function callcenter_config_tier_add($queueName, $agentName, $level, $position)
    {
        if ($this->connected) {
            // Construct the command to add the agent to a tier in the call center
            $cmd = "api callcenter_config tier add {$queueName} {$agentName} {$level} {$position}" . PHP_EOL;

            // Log the command for debugging purposes
            Log::info($cmd);

            // Send the command to the FreeSwitch server and get the response
            $response = $this->socket->request($cmd);

            // Log the response for debugging purposes
            Log::info($response);

            // Initialize the status to false
            $status = false;

            // Check if the response contains "+OK" indicating success
            if (strpos($response, "+OK") !== false) {
                // If it does, set status to true
                $status = true;
            }

            // Prepare the response data
            $response = [
                'status' => $status, // Indicates the success status of the request
                'data' => $response, // Contains the response from the server
                'message' => 'Successfully set tier position.'
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected, return a disconnected response
            return $this->disconnected();
        }
    }

    /**
     * Sets the tier level of an agent in a call center via the API.
     * 
     * @param string $queueName The name of the call center queue.
     * @param string $agentName The name of the agent to set the tier level for.
     * @param int $level The tier level to set (1-10).
     * @return \Illuminate\Http\JsonResponse JSON response with status, data, and message
     */
    public function callcenter_config_tier_set_level($queueName, $agentName, $level): JsonResponse
    {
        if ($this->connected) {

            // Construct the command to set the tier of the agent            
            $cmd = "api callcenter_config tier set level {$queueName} {$agentName} {$level}" . PHP_EOL;

            log::info($cmd);

            // Send the command to the FreeSwitch server and get the response
            $response = $this->socket->request($cmd);

            Log::info($response);

            // Initialize the status to false
            $status = false;

            // Check if the response contains "+OK" indicating success
            if (strpos($response, "+OK") !== false) {
                // If it does, set status to true
                $status = true;
            }

            // Prepare the response data
            $response = [
                'status' => $status, // Indicates the success status of the request
                'data' => $response, // Contains the response from the server
                'message' => 'Successfully set tier level.'
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected, return a disconnected response
            return $this->disconnected();
        }
    }

    /**
     * Sets the tier position of an agent in a call center via the API.
     *
     * @param string $queueName The name of the call center queue.
     * @param string $agentName The name of the agent to set the tier position for.
     * @param int $position The tier position to set (1-10).
     * @return \Illuminate\Http\JsonResponse JSON response with status, data, and message
     */
    public function callcenter_config_tier_set_position($queueName, $agentName, $position): JsonResponse
    {
        if ($this->connected) {

            // Construct the command to set the tier of the agent
            $cmd = "api callcenter_config tier set position {$queueName} {$agentName} {$position}" . PHP_EOL;

            // Send the command to the FreeSwitch server and get the response
            $response = $this->socket->request($cmd);

            // Initialize the status to false
            $status = false;

            // Check if the response contains "+OK" indicating success
            if (strpos($response, "+OK") !== false) {
                // If it does, set status to true
                $status = true;
            }

            // Prepare the response data
            $response = [
                'status' => $status, // Indicates the success status of the request
                'data' => $response, // Contains the response from the server
                'message' => 'Successfully set tier position.'
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected, return a disconnected response
            return $this->disconnected();
        }
    }

    /**
     * Sets the status of an agent in a call center via the API.
     * 
     * @param string $agent_name The name of the agent to set the status for.
     * @param string $status The status to set (e.g. "Available" or "Unavailable").
     * @return \Illuminate\Http\JsonResponse JSON response with status, data, and message
     */
    public function callcenter_config_agent_set_status($agent_name, $status): JsonResponse
    {
        if ($this->connected) {

            // Construct the command to set the status of the agent
            $cmd = "api callcenter_config agent set status $agent_name $status" . PHP_EOL;

            // Log the command for debugging purposes
            Log::info($cmd);

            // Send the command to the FreeSwitch server and get the response
            $response = $this->socket->request($cmd);

            // Initialize the status to false
            $status = false;

            // Check if the response contains "+OK" indicating success
            if (strpos($response, "+OK") !== false) {
                // If it does, set status to true
                $status = true;
            }

            // Prepare the response data
            $response = [
                'status' => $status, // Indicates the success status of the request
                'data' => $response, // Contains the response from the server
                'message' => 'Successfully set agent status.'
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected, return a disconnected response
            return $this->disconnected();
        }
    }

    /**
     * Deletes an agent from a call center via the API.
     *
     * @param string $agent_name The name of the agent to delete.
     * @return \Illuminate\Http\JsonResponse JSON response with status, data, and message
     */
    public function callcenter_config_agent_del($agent_name): JsonResponse
    {
        if ($this->connected) {

            // Construct the command to delete the agent
            $cmd = "api callcenter_config agent del $agent_name" . PHP_EOL;

            // Send the API request to delete an agent
            $response = $this->socket->request($cmd);

            // Initialize the status to false
            $status = false;

            // Check if the response contains "+OK" indicating success
            if (strpos($response, "+OK") !== false) {
                // If it does, set status to true
                $status = true;
            }

            // Prepare the response data
            $response = [
                'status' => $status, // Indicates the success status of the request
                'data' => $response, // Contains the response from the server
                'message' => 'Successfully agent delete'
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected, return a disconnected response
            return $this->disconnected();
        }
    }

    /**
     * Deletes an agent from a tier in a call center via the API.
     *
     * @param string $queueName The name of the call center queue.
     * @param string $agentName The name of the agent to delete.
     * @return \Illuminate\Http\JsonResponse JSON response with status, data, and message
     */
    public function callcenter_config_tier_del($queueName, $agentName): JsonResponse
    {
        if ($this->connected) {

            // Construct the command to delete an agent from a tier in the call center
            $cmd = "api callcenter_config tier del {$queueName} {$agentName}" . PHP_EOL;

            // Send the API request to delete an agent
            $response = $this->socket->request($cmd);

            // Initialize the status to false
            $status = false;

            // Check if the response contains "+OK" indicating success
            if (strpos($response, "+OK") !== false) {
                // If it does, set status to true
                $status = true;
            }

            // Prepare the response data
            $response = [
                'status' => $status, // Indicates the success status of the request
                'data' => $response, // Contains the response from the server
                'message' => 'Successfully agent delete'
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected, return a disconnected response
            return $this->disconnected();
        }
    }

    /**
     * Sets the state of an agent in a call center via the API.
     * 
     * @param string $queueName The name of the call center queue.
     * @param string $agentName The name of the agent to set the state for.
     * @param string $state The state to set (e.g. "Available" or "Unavailable").
     * @return \Illuminate\Http\JsonResponse JSON response with status, data, and message
     */
    public function callcenter_config_tier_set_state($queueName, $agentName, $state): JsonResponse
    {
        if ($this->connected) {

            // Construct the command to set the state of the agent
            $cmd = "api callcenter_config tier set state {$queueName} {$agentName} {$state}" . PHP_EOL;

            // Send the API request to set the state of the agent
            $response = $this->socket->request($cmd);

            // Initialize the status to false
            $status = false;

            // Check if the response contains "+OK" indicating success
            if (strpos($response, "+OK") !== false) {
                // If it does, set status to true
                $status = true;
            }

            // Prepare the response data
            $response = [
                'status' => $status, // Indicates the success status of the request
                'data' => $response, // Contains the response from the server
                'message' => "Successfully set agent's state"
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected, return a disconnected response
            return $this->disconnected();
        }
    }

    /**
     * Loads a queue into the call center via the API.
     *
     * @param string $queueName The name of the queue to load.
     * @return \Illuminate\Http\JsonResponse JSON response with status, data, and message
     */
    public function callcenter_queue_load($queueName): JsonResponse
    {
        if ($this->connected) {
            // Construct the command to load the queue
            $cmd = "api callcenter_config queue load {$queueName}" . PHP_EOL;

            // Log the command for debugging purposes
            Log::info($cmd);

            // Send the API request to load the queue
            $response = $this->socket->request($cmd);

            // Initialize the status to false
            $status = false;

            // Check if the response contains "+OK" indicating success
            if (strpos($response, "+OK") !== false) {
                // If it does, set status to true
                $status = true;
            }

            // Prepare the response data
            $response = [
                'status' => $status, // Indicates the success status of the request
                'data' => $response, // Contains the response from the server
                'message' => 'Successfully queue loaded'
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected, return a disconnected response
            return $this->disconnected();
        }
    }

    /**
     * Unloads a queue from the call center via the API.
     *
     * @param string $queueName The name of the queue to unload.
     * @return \Illuminate\Http\JsonResponse JSON response with status, data, and message
     */
    public function callcenter_queue_unload($queueName): JsonResponse
    {
        if ($this->connected) {

            // Construct the command to unload the queue
            $cmd = "api callcenter_config queue unload {$queueName}" . PHP_EOL;

            // Send the API request to unload the queue
            $response = $this->socket->request($cmd);

            // Initialize the status to false
            $status = false;

            // Check if the response contains "+OK" indicating success
            if (strpos($response, "+OK") !== false) {
                // If it does, set status to true
                $status = true;
            }

            // Prepare the response data
            $response = [
                'status' => $status, // Indicates the success status of the request
                'data' => $response, // Contains the response from the server
                'message' => 'Successfully queue unloaded'
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected, return a disconnected response
            return $this->disconnected();
        }
    }

    /**
     * Check active extensions on the server and update database accordingly.
     *
     * @return \Illuminate\Http\JsonResponse JSON response with status, data, and message
     */
    public function checkActiveExtensionOnServer(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $userType = $request->user()->usertype;

        // Check if the socket is connected
        if ($this->connected) {
            // Make a request to the server API to show registrations
            $response = $this->socket->request('api show registrations');

            // Check if the response indicates no registrations found
            if (strpos($response, '0 total') !== false) { // No registrations found, return an empty response
                $response = [
                    'status' => true, // Indicates the success status of the request
                    'data' => [], // Contains the fetched extensions (empty in this case)
                    'message' => 'Successfully fetched',
                ];

                // Return the response as JSON with HTTP status code 200 (OK)
                return response()->json($response, Response::HTTP_OK);
            }

            // Split the output by lines
            $lines = explode("\n", $response);

            // Extract column names from the first line
            $columns = explode(",", trim($lines[0], '"'));

            $parsedData = [];

            // Loop through the data lines (excluding the last two lines)
            for ($i = 1; $i < count($lines) - 2; $i++) {
                // Split the line by commas
                $values = explode(",", $lines[$i]);

                if (count($columns) == count($values)) {
                    // Combine column names with values to create key-value pairs
                    $entry = array_combine($columns, $values);

                    // Add the entry to the parsed data
                    $parsedData[] = $entry;
                }
            }

            // Fetch active extensions from the database
            $activeExts = Extension::select('extension', 'domain', 'account_id')->where('sofia_status', true)->get()->toArray();

            $formattedArray = [];

            // Format parsed data for comparison
            foreach ($parsedData as $ext) {
                $extension = $ext['reg_user'];
                $realm = $ext['realm'];

                // Fetch domain ID from the database
                $domain = Domain::where('domain_name', $realm)->first();

                if ($domain) {
                    $formattedArray[] = ['extension' => $extension, 'domain' => json_encode($domain->id), 'account_id' => $domain->account_id];
                }
            }

            // Find differences between active extensions and formatted array
            $differences = array_filter($activeExts, function ($item) use ($formattedArray) {
                $accId = $item['account_id'];
                $domain = $item['domain'];
                $extension = $item['extension'];

                // Check if there exists an item in $formattedArray that matches the current $item
                $matches = collect($formattedArray)->filter(function ($formattedItem) use ($accId, $domain, $extension) {
                    return $formattedItem['account_id'] == $accId
                        && $formattedItem['domain'] == $domain
                        && $formattedItem['extension'] == $extension;
                })->isEmpty();

                return $matches;
            });

            // Update database records for extensions that are not active
            foreach ($differences as $difference) {
                $extension = $difference['extension'];
                $account_id = $difference['account_id'];
                $domain = $difference['domain'];

                Extension::where([
                    'extension' => $extension,
                    'account_id' => $account_id,
                    'domain' => $domain
                ])->update(['sofia_status' => false]);
            }

            // Prepare final response
            $response = [
                'status' => true, // Indicates the success status of the request
                // 'data' => $activeExts, // Contains the fetched extensions
                // 'new' => $formattedArray, // Contains the formatted new data
                // 'result' => $differences, // Contains the differences found
                'message' => 'Successfully updated',
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected, return a disconnected response
            return $this->disconnected();
        }
    }

    /**
     * Fetches active calls from the FreeSwitch server and sends them to the UI via WebSocket.
     *
     * @return void
     */
    public function getActiveCall()
    {
        // Check if the socket is connected
        if (!$this->socket->is_connected()) {
            return $this->disconnected();
        }

        // Send an API request to fetch call events
        $response = $this->socket->request('api show calls');

        // Initialize response variable
        $customizedResponse = [
            'key' => 'activeCalls',
            'result' => [],
        ];

        // Check if there are no active calls
        if (strpos($response, '0 total') !== false) {
            // No active calls, respond with empty result
            $customizedResponse['result'] = [];
        } else {
            // Process response to get active calls
            $activeCalls = activeCallDataFormat($response);

            // Filter for inbound calls with the status "ACTIVE"
            $filteredCalls = array_filter($activeCalls, function ($call) {
                return $call['callstate'] === 'ACTIVE' || $call['callee_direction'] === 'ACTIVE' || $call['callstate'] === 'RINGING';
            });

            // // Re-index array numerically
            $customizedResponse['result'] = array_values($filteredCalls);
        }

        // Initialize WebSocket controller and send the response
        $socketController = new WebSocketController();
        $socketController->send($customizedResponse);
    }

    /**
     * Kills a call by UUID.
     *
     * @param string $uuid The UUID of the call to kill.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the request.
     */
    protected function callKill($uuid)
    {
        if ($this->connected) {
            // Build the API command to kill the call
            $cmd = "api uuid_kill {$uuid}";
            // Check call state
            $response = $this->socket->request($cmd);

            if (trim($response) == "-ERR No such channel!") {
                // If the call does not exist, return an error response
                $response = [
                    'status' => false, // Indicates the success status of the request
                    'message' => 'Wrong channel. Please try again.',
                ];

                // Return the response as JSON with HTTP status code 400 (Bad Request)
                return response()->json($response, Response::HTTP_BAD_REQUEST);
            }

            // Prepare the response data
            $response = [
                'status' => true, // Indicates the success status of the request
                'message' => 'Successfully terminated call.',
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected,
            return $this->disconnected();
        }
    }

    /**
     * Barge on a call using the provided UUID
     *
     * This method makes a request to the FreeSwitch server to originate a call
     * to the authenticated user's extension with the three_way application
     * and the provided UUID as an argument. The three_way application will
     * barge on the call with the provided UUID.
     *
     * @param string $uuid The unique identifier of the call to barge on
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the request
     */
    protected function barge($uuid)
    {
        if ($this->connected) {
            // Get the authenticated user's extension
            $extension = Extension::find(Auth::user()->extension_id);

            if (!$extension) {
                $response = [
                    'status' => false,
                    'message' => 'Extension not found.',
                ];
                // Return the response as JSON with HTTP status code 400 (Bad Request)
                return response()->json($response, Response::HTTP_BAD_REQUEST);
            }

            $domain = Domain::where('account_id', $extension->account_id)->first()->domain_name;
            
            $domain =  $extension->extension.'@'.$domain;

            $effectiveCallerIdName = $extension->effectiveCallerIdName;

            // Construct the command to barge on the call
            $cmd = "api originate {origination_caller_id_number={$extension->extension},application_state='barge',origination_caller_id_name='$effectiveCallerIdName'}user/{$domain} &three_way({$uuid})";

            Log::info($cmd);

            // Check call state
            $response = $this->socket->request($cmd);

            // Prepare the response data
            $response = [
                'status' => true, // Indicates the success status of the request
                'message' => 'Successfully barge call.',
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected,
            return $this->disconnected();
        }
    }

    /**
     * Eavesdrop on a call using the provided UUID
     *
     * @param string $uuid The unique identifier of the call
     * @return \Illuminate\Http\JsonResponse
     */
    protected function eavesdrop($uuid, $other_leg_destination_number = null)
    {
        return $this->handleCallAction($uuid, 'eavesdrop', $other_leg_destination_number);
    }

    /**
     * Intercept a call using the provided UUID
     *
     * @param string $uuid The unique identifier of the call
     * @return \Illuminate\Http\JsonResponse
     */
    protected function intercept($uuid, $other_leg_destination_number = null)
    {
        return $this->handleCallAction($uuid, 'intercept', $other_leg_destination_number);
    }

    /**
     * Handle call actions like eavesdropping and intercepting
     *
     * @param string $uuid The unique identifier of the call
     * @param string $action The action to perform ('eavesdrop' or 'intercept')
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleCallAction($uuid, $action, $other_leg_destination_number = null)
    {
        // Get the authenticated user's extension
        $extension = Extension::find(Auth::user()->extension_id);

        if (!$extension) {
            $response = [
                'status' => false,
                'message' => 'Extension not found.',
            ];
            // Return the response as JSON with HTTP status code 400 (Bad Request)
            return response()->json($response, Response::HTTP_BAD_REQUEST);
        }

        // Get the account ID of the authenticated user
        $accountId = Auth::user()->account_id;

        // Check if the account ID is empty
        if (empty($accountId)) {
            $response = [
                'status' => false,
                'message' => 'Account not found.',
            ];
            // Return the response as JSON with HTTP status code 400 (Bad Request)
            return response()->json($response, Response::HTTP_BAD_REQUEST);
        }

        // Get the domain name associated with the account ID
        $domain = Domain::where('account_id', $accountId)->first()->domain_name;

        // Check if the domain is empty
        if (empty($domain)) {
            $response = [
                'status' => false,
                'message' => 'Domain not found.',
            ];
            // Return the response as JSON with HTTP status code 400 (Bad Request)
            return response()->json($response, Response::HTTP_BAD_REQUEST);
        }

        $effectiveCallerIdName = $extension->effectiveCallerIdName;

        // If the socket is connected, perform the call action
        if ($this->connected) {

            // Prepare the API command based on the action
            // $uuid = ($action == 'intercept') ? "'-bleg'. $uuid" : $uuid;

            // Build the API command to originate the call
            $cmd = "api originate {origination_caller_id_number={$extension->extension},application_state='$action',origination_caller_id_name='$effectiveCallerIdName',original_destination_number='test',other_leg_destination_number='$other_leg_destination_number'}user/{$extension->extension}@{$domain} &$action($uuid)";

            Log::info($cmd);

            // Check call state
            $response = $this->socket->request($cmd);

            // Check if the response contains "-ERR NO_USER_RESPONSE"
            if (strpos($response, "-ERR NO_USER_RESPONSE") !== false) {
                $response = [
                    'status' => false, // Indicates the success status of the request
                    'data' => $response,
                    'message' => 'User rejected the call.',
                ];

                // Return the response as JSON with HTTP status code 400 (Bad Request)
                return response()->json($response, Response::HTTP_BAD_REQUEST);
            }

            // Check if the response contains "+OK"
            if (strpos($response, "+OK") !== false) {
                // Prepare the response data
                $response = [
                    'status' => true, // Indicates the success status of the request
                    'data' => $response,
                    'message' => 'Successfully ' . $action . ' call.',
                ];
                // Return the response as JSON with HTTP status code 200 (OK)
                return response()->json($response, Response::HTTP_OK);
            }
        } else {
            // If the socket is not connected, return an error response
            return $this->disconnected();
        }
    }

    /**
     * Unpark a call from the provided slot to the provided user.
     * 
     * This method makes a request to the FreeSwitch server to originate a call
     * to the provided user's extension with the unpark_slot as an argument. The
     * unpark_slot is the slot where the call is currently parked.
     *
     * @param Request $request The request containing the unpark_slot and user
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the request
     */
    public function callUnPark(Request $request)
    {
        if ($this->connected) {

            // Validate the request data
            $validate = Validator::make($request->all(), [
                'unpark_slot' => 'required|string',
                'user' => 'required|string',
            ]);

            if ($validate->fails()) {
                // If validation fails, return an error response
                $response = [
                    'status' => false,
                    'message' => $validate->errors()->first(),
                ];
                // Return the response as JSON with HTTP status code 400 (Bad Request)
                return response()->json($response, Response::HTTP_BAD_REQUEST);
            }

            // Extract data from the request
            $unpark_slot = $request->unpark_slot;
            $user = $request->user;

            // Get the domain name from the database based on the authenticated user's account_id
            $account_id = $request->user()->account_id;
            $domain = Domain::where('account_id', $account_id)->first()->domain_name;

            if (empty($domain)) {
                // If the domain is not found, return an error response
                $response = [
                    'status' => false,
                    'message' => 'Domain not found.',
                ];
                // Return the response as JSON with HTTP status code 400 (Bad Request)
                return response()->json($response, Response::HTTP_BAD_REQUEST);
            }

            // Construct the API command to unpark the call
            $cmd = "api originate {origination_caller_id_number=$user,unpark_slot=$unpark_slot}user/$user@$domain   *6000 XML webvio";

            // Check call state
            $response = $this->socket->request($cmd);

            if (trim($response) == "-ERR No such channel!") {
                // If the call does not exist, return an error response
                $response = [
                    'status' => false, // Indicates the success status of the request
                    'message' => 'Wrong channel. Please try again.',
                ];

                // Return the response as JSON with HTTP status code 400 (Bad Request)
                return response()->json($response, Response::HTTP_BAD_REQUEST);
            }

            // Prepare the response data
            $response = [
                'status' => true, // Indicates the success status of the request
                'message' => 'Successfully unpark call.',
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected, return an error response
            return $this->disconnected();
        }
    }

    /**
     * Park a call to the specified user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function callPark(Request $request)
    {
        if ($this->connected) {

            // Extract the domain name from the database based on the authenticated user's account_id
            $account_id = $request->user()->account_id;
            $domain = Domain::where('account_id', $account_id)->first()->domain_name;

            if (empty($domain)) {
                // If the domain is not found, return an error response
                $response = [
                    'status' => false,
                    'message' => 'Domain not found.',
                ];
                // Return the response as JSON with HTTP status code 400 (Bad Request)
                return response()->json($response, Response::HTTP_BAD_REQUEST);
            }

            // Validate the request data
            $validate = Validator::make($request->all(), [
                'user' => 'required|string'
            ]);

            if ($validate->fails()) {
                // If validation fails, return an error response
                $response = [
                    'status' => false,
                    'message' => $validate->errors()->first(),
                ];
                // Return the response as JSON with HTTP status code 400 (Bad Request)
                return response()->json($response, Response::HTTP_BAD_REQUEST);
            }

            // Extract data from the request
            $user = $request->user;

            // Construct the API command to park the call
            $cmd = "api originate {origination_caller_id_number=$user}user/$user@$domain   *6001 XML webvio";

            Log::info($cmd);

            // Check call state
            $response = $this->socket->request($cmd);

            // Check if the response contains "+OK" indicating success
            if (strpos($response, "-ERR") !== false) {
                // If the call does not exist, return an error response
                $response = [
                    'status' => false, // Indicates the success status of the request
                    'message' => 'Something went wrong. Please try again.',
                    'originate_msg' => $response,
                    // 'sdb' => $data,
                    'domain' => $domain
                ];
                // Return the response as JSON with HTTP status code 400 (Bad Request)
                return response()->json($response, Response::HTTP_BAD_REQUEST);
            }

            // Prepare the response data
            $response = [
                'status' => true, // Indicates the success status of the request
                'message' => 'Successfully parked call.',
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            // If the socket is not connected, return an error response
            return $this->disconnected();
        }
    }

    /**
     * Retrieves the available parking slots for calls.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function availableSlots(Request $request)
    {
        if ($this->connected) {

            $account_id = $request->user()->account_id;
            $domain = Domain::where('account_id', $account_id)->first()->domain_name;

            if (empty($domain)) {
                // If the domain is not found, return an error response
                $response = [
                    'status' => false,
                    'message' => 'Domain not found.',
                ];
                // Return the response as JSON with HTTP status code 400 (Bad Request)
                return response()->json($response, Response::HTTP_BAD_REQUEST);
            }

            // Construct the API command to retrieve valet info
            $cmd = "api valet_info my_lot@$domain";

            // Send the API request and store the response
            $response = $this->socket->request($cmd);

            // Check if the response contains "+OK" indicating success
            if (strpos($response, "-ERR") !== false) {
                // If the call does not exist, return an error response
                $response = [
                    'status' => false, // Indicates the success status of the request
                    'message' => 'Something went wrong. Please try again.',
                    'originate_msg' => $response
                ];
                // Return the response as JSON with HTTP status code 400 (Bad Request)
                return response()->json($response, Response::HTTP_BAD_REQUEST);
            }

            // Load the XML string
            $xml = simplexml_load_string($response);

            // Define the total range of parking slots
            $totalSlots = range(6001, 6010);

            // Initialize an empty array to hold the result
            $unavailableSlots = [];

            // Loop through each lot
            foreach ($xml->lot as $lot) {
                // Loop through each extension within the lot
                foreach ($lot->extension as $extension) {

                    // Retrieve the data from the database
                    $data = DB::connection('second_db')->table('basic_calls')
                        ->where('uuid', '=', $extension['uuid'])
                        ->first();

                    $unavailableSlots[] = [
                        'uuid' => (string) $extension['uuid'],
                        'park_slot' => (string) $extension,
                        'parked_by' =>  $data->parked_by,
                        'user' => (!empty($data)) ? $data->cid_num : null
                    ];
                }
            }

            // Convert unavailable slots to a simple array for easy comparison
            $unavailableSlotsArray = array_map(function ($slot) {
                return $slot['park_slot'];
            }, $unavailableSlots);

            // Find the available slots by excluding unavailable ones
            $availableSlots = array_filter($totalSlots, function ($slot) use ($unavailableSlotsArray) {
                return !in_array((string)$slot, $unavailableSlotsArray);
            });

            // Prepare the result array with available slots
            $result = [];
            foreach ($availableSlots as $slot) {
                $result[] = [
                    'park_slot' => (string)$slot
                ];
            }

            // Prepare the response data
            $response = [
                'status' => true, // Indicates the success status of the request
                'message' => 'Successfully fetched available slots for call.',
                'usedSlots' => $unavailableSlots,
                'available' => $result
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            return $this->disconnected();
        }
    }

    /**
     * Send a fax using the provided file and configuration.
     *
     * This method makes a request to the FreeSwitch server to originate a call
     * to the provided destination with the fax configuration and sends the fax.
     *
     * @param  \Illuminate\Http\Request  $request  The request object containing the fax configuration and file
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function sendFax(Request $request)
    {
        if ($this->connected) {

            $origination_caller_id_number = $request->origination_caller_id_number;
            $origination_caller_id_name = $request->origination_caller_id_name;
            $fax_ident = $request->fax_ident;
            $fax_header = $request->fax_header;
            $destination_caller_id_number = $request->destination_caller_id_number;
            $fax_file = $request->fax_file;

            $call_plan_id = $request->call_plan_id;
            $destination = $request->destination;
            $selling_billing_block = $request->selling_billing_block;
            $sell_rate = $request->sell_rate;
            $gateway_id = $request->gateway_id;
            $billing_type = $request->billing_type;

            $cmd = "api originate {absolute_codec_string=PCMU,PCMA,origination_caller_id_number=$origination_caller_id_number,origination_caller_id_name=$origination_caller_id_name,fax_ident=$fax_ident,fax_header='$fax_header',application='outbound',selling_billing_block='$selling_billing_block',sell_rate=$sell_rate,gateway_id=$gateway_id,billing_type=$billing_type',destination='$destination',call_plan_id='$call_plan_id'}sofia/gateway/$gateway_id/$destination_caller_id_number   &txfax($fax_file)";

            // originate {ignore_early_media=true,absolute_codec_string=PCMU,GSM,origination_caller_id_number=18882610473,origination_caller_id_name=18882610473,fax_ident=8882610473,fax_header=8882610473,fax_verbose=true}sofia/gateway/1/18553301239   &txfax(/home/solman/sample-2.tiff)

            // Check call state
            $response = $this->socket->request($cmd);

            // Check if the response contains "+OK"
            if (strpos($response, "+OK") !== false) {
                // Prepare the response data
                $response = [
                    'status' => true, // Indicates the success status of the request
                    'data' => $response,
                    'message' => 'Successfully send fax',
                ];
                // Return the response as JSON with HTTP status code 200 (OK)
                return response()->json($response, Response::HTTP_OK);
            }

            // Check if the response contains "+OK" indicating success
            if (strpos($response, "-ERR") !== false) {
                // If the call does not exist, return an error response
                $response = [
                    'status' => false, // Indicates the success status of the request
                    'message' => 'Something went wrong. Please try again later.',
                    'originate_msg' => $response
                ];
                // Return the response as JSON with HTTP status code 400 (Bad Request)
                return response()->json($response, Response::HTTP_BAD_REQUEST);
            }
        } else {
            // If the socket is not connected, return an error response
            return $this->disconnected();
        }
    }

    /**
     * Initiates a call to a destination number using the default outbound gateway for the user's account.
     *
     * @param  \Illuminate\Http\Request  $request  HTTP request containing the destination number
     * @return \Illuminate\Http\JsonResponse  JSON response with status and message
     */
    public function clickToCall(Request $request)
    {
        $gatewayId = activeGatewayId();

        if (!$gatewayId) {
            return response()->json(['status' => false, 'message' => 'Gateway not found.'], 400);
        }

        $validator = Validator::make($request->all(), [
            'destination' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $account_id = $request->user()->account_id;

        // Get the account balance
        $account = AccountBalance::where('account_id', $account_id)->first();

        // Check if account balance is not found
        if (!$account) {
            return response()->json(['status' => false, 'message' => 'Account balance not found.'], 400);
        }

        // Check if balance is less than 2
        if ($account->amount < 1) {
            return response()->json(['status' => false, 'message' => 'Insufficient balance. Please top up.'], 500);
        }

        // Check if the socket is connected
        if ($this->connected) {
            $destination = $request->destination;

            $did_details = DidDetail::where(['account_id' => $account_id, 'default_outbound' => true])->first();

            if (!$did_details) {
                return response()->json(['status' => false, 'message' => 'Did details not found.'], 400);
            }

            $ext = Extension::where('account_id', $account_id)->first();
            $extension = $ext->extension;

            $domainResult = Domain::where('account_id', $account_id)->first();
            $domain = $domainResult->domain_name;

            $origination_caller_id_name = $did_details->did;
            $origination_caller_id_number = $did_details->did;

            $cmd = "api originate {ignore_early_media=true,absolute_codec_string=PCMU,GSM,origination_caller_id_number=$origination_caller_id_number,origination_caller_id_name=$origination_caller_id_name,application_state=click2call}sofia/gateway/$gatewayId/$destination &bridge(user/$extension@$domain)";

            Log::info($cmd);

            // Check call state
            $response = $this->socket->request($cmd);

            Log::info($response);

            // Check if the response contains "+OK"
            if (strpos($response, "+OK") !== false) {
                // Prepare the response data
                $response = [
                    'status' => true, // Indicates the success status of the request
                    'data' => $response,
                    'message' => 'Successfully send fax',
                ];
                // Return the response as JSON with HTTP status code 200 (OK)
                return response()->json($response, Response::HTTP_OK);
            }

            // Check if the response contains "+OK" indicating success
            if (strpos($response, "-ERR") !== false) {
                // If the call does not exist, return an error response
                $response = [
                    'status' => false, // Indicates the success status of the request
                    'message' => 'Something went wrong. Please try again later.',
                    'originate_msg' => $response
                ];
                // Return the response as JSON with HTTP status code 400 (Bad Request)
                return response()->json($response, Response::HTTP_BAD_REQUEST);
            }
        } else {
            return $this->disconnected();
        }
    }

    public function checkConference($request)
    {
        if ($this->connected) {
            
            $cmd = "api conference list";
            
            $response = $this->socket->request($cmd);

            Log::info($response);

            if (strpos($response, "+OK") !== false) {
                $response = [
                    'status' => true,
                    'data' => $response,
                ];
                return response()->json($response, Response::HTTP_OK);
            }

        } else {
            return $this->disconnected();
        }   
    }
}
