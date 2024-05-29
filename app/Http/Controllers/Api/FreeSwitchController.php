<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Extension;
use App\Traits\Esl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use function PHPUnit\Framework\isEmpty;

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
                // Remove line breaks from the string
                // $string = str_replace(array("\r", "\n"), '', $response);

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
    public function status(): JsonResponse
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

    public function checkCallStatus(): JsonResponse
    {
        if ($this->socket->is_connected()) {
            // Check call state
            $response = $this->socket->request('event json ALL');

            // Prepare the response data
            $response = [
                'status' => true, // Indicates the success status of the request
                'data' => $response, // Contains the fetched extensions
                'message' => 'Successfully fetched'
            ];

            // Return the response as JSON with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            return $this->disconnected();
        }
    }

    // To make call 
    public function call(Request $request)
    {
        // Check if a user is authenticated
        $user = $request->user();

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

            $srcUserId = $user->id;

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

            // check user is same or not while making call
            if ($srcUserId !== $srcCheck->user) {
                $response = [
                    'status' => false, // Indicates the success status of the request
                    'message' => 'You dont have permission to make call.'
                ];

                // Return the response as JSON with HTTP status code 422 
                return response()->json($response, Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // check extensions are online or not
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

            $destination = 'user/' . $destination;
            $cmd_add = "api originate {origination_caller_id_number=$src}$destination $src default XML\n\n";

            $response = $this->socket->request($cmd_add);

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
}
