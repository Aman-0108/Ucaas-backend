<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Extension;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

use App\Services\SSHService;

use App\Traits\CreateXml;
use App\Traits\Esl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ExtensionController extends Controller
{
    use CreateXml;
    use Esl;

    protected $sshService;
    protected $type;

    /**
     * Constructor for initializing the SSHService instance.
     *
     * This constructor initializes the SSHService instance used by the class.
     * It accepts an instance of SSHService as a dependency injection.
     *
     * @param SSHService $sshService An instance of SSHService used for SSH operations.
     */
    public function __construct(SSHService $sshService)
    {
        // Initialize the SSHService instance
        $this->sshService = $sshService;
        $this->type = 'Extension';
    }

    /**
     * Display a listing of the extensions.
     *
     * This method fetches all extensions from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Fetch all extensions from the database
        $extensions = Extension::with(['followmes', 'domain']);

        // Start building the query to fetch extensions
        // $extensions = Extension::query();

        // Check if the request contains an 'account' parameter
        if ($request->has('account')) {
            // If 'account' parameter is provided, filter extensions by account ID
            $extensions->where('account_id', $request->account);
        }

        // COMING FROM GLOBAL CONFIG
        $ROW_PER_PAGE = config('globals.PAGINATION.ROW_PER_PAGE');

        // Execute the query to fetch domains
        $extensions = $extensions->orderBy('extension', 'asc')->paginate($ROW_PER_PAGE);

        // Prepare the response data
        $response = [
            'status' => true, // Indicates the success status of the request
            'data' => $extensions, // Contains the fetched extensions
            'message' => 'Successfully fetched all extensions'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Display the specified extension.
     *
     * This method fetches the extension with the given ID from the database and returns it as a JSON response.
     *
     * @param  int $id The ID of the extension to be fetched.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the fetched extension.
     */
    public function show($id)
    {
        // Find the extension with the given ID
        $extension = Extension::with(['followmes'])->find($id);

        // Check if the extension exists
        if (!$extension) {
            // If the extension is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Extension not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => ($extension) ? $extension : '', // Ensure data is not null
            'message' => 'Successfully fetched'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created extension in storage.
     *
     * This method validates the incoming request data, creates a new extension record in the database,
     * and returns a JSON response indicating success or failure.
     *
     * @param  \Illuminate\Http\Request $request The incoming HTTP request.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the result of the store operation.
     */
    public function store(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                // Validation rules for each field
                'account_id' => 'required|exists:accounts,id',
                'domain' => 'required|string|exists:domains,id',
                'extension' => 'required|unique:extensions,extension,NULL,id,account_id,' . $request->account_id,
                'password' => 'required|string',
                'voicemail_password' => 'required|string',

                'user' => 'string|nullable',
                'range' => 'numeric|nullable',
                'account_code' => 'string|nullable',
                'effectiveCallerIdName' => 'string|nullable',
                'effectiveCallerIdNumber' => 'numeric|nullable',
                'outbundCallerIdName' => 'string|nullable',
                'outbundCallerIdNumber' => 'numeric|nullable',

                'emergencyCallerIdName' => 'string|nullable',
                'emergencyCallerIdNumber' => 'numeric|nullable',
                'directoryFullname' => 'string|nullable',
                'directoryVisible' => 'string|nullable',
                'directoryExtensionVisible' => 'string|nullable',
                'maxRegistration' => 'string|nullable',
                'limitMax' => 'string|nullable',
                'limitDestinations' => 'string|nullable',

                'voicemailEnabled' => 'in:Y,N',
                'voiceEmailTo' => 'string|nullable',
                'voiceMailFile' => 'string|nullable',
                'voiceMailkeepFile' => 'string|nullable',
                'missedCall' => 'string|nullable',
                'tollAllowValue' => 'string|nullable',
                'callTimeOut' => 'numeric|nullable',
                'callgroup' => 'string|nullable',
                'callScreen' => 'in:Enable,Disable|nullable',

                'record' => 'in:L,I,O,A,D',
                'description' => 'string|nullable',
                'callforward' => 'boolean',
                'callforwardTo' => 'string|nullable',
                'onbusy' => 'boolean',
                'onbusyTo' => 'string|nullable',
                'noanswer' => 'boolean',
                'noanswerTo' => 'string|nullable',
                'notregistered' => 'boolean',
                'notregisteredTo' => 'string|nullable',
                'dnd' => 'boolean',
                'followme' => 'boolean',
                'ignorebusy' => 'boolean',
                'blockIncomingStatus' => 'boolean',
                'blockOutGoingStatus' => 'boolean',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a JSON response with error messages
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        $validated['created_by'] = $userId;

        // Defining action and type for creating UID
        $action = 'create';
        $type = $this->type;

        // Begin a database transaction
        DB::beginTransaction();

        // Check if 'range' is provided in the request
        if ($request->has('range')) {
            $newArr = [];
            $extension = $request->extension;
            $range = $request->range;

            // If 'range' is provided and greater than 0, create multiple extensions
            if ($range > 0) {
                for ($i = 0; $i < $range; $i++) {
                    $validated['extension'] = $extension + $i;

                    // Generate UID and attach it to the validated data
                    createUid($action, $type, $validated, $userId);

                    $data = Extension::create($validated);
                    $newArr[] = $validated;
                }
            }
        } else {
            // Generate UID and attach it to the validated data
            createUid($action, $type, $validated, $userId);

            // If 'range' is not provided, create a single extension
            $data = Extension::create($validated);
        }

        // Commit the database transaction
        DB::commit();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => ($request->has('range')) ? $newArr : $data, // Contains either the array of newly created extensions or a single extension
            'message' => 'Successfully stored'
        ];

        // Return a JSON response with HTTP status code 201 (Created)
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Assign an extension to a user.
     *
     * This method finds the extension and user by their IDs, assigns the extension to the user,
     * and returns a JSON response indicating success or failure.
     *
     * @param  \Illuminate\Http\Request $request The incoming HTTP request containing extension and user IDs.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the result of the assignment operation.
     */
    public function assign(Request $request)
    {
        // Extract extension and user IDs from the request
        $id = ($request->has('id')) ? $request->id : null;
        $user_id = ($request->has('userId')) ? $request->userId : null;
        $forceUpdate = ($request->has('forceUpdate')) ? $request->forceUpdate : false;

        // Check if both extension and user IDs are provided
        if (isset($id) && isset($user_id)) {
            // Find the extension by ID
            $extension = Extension::find($id);

            // Check if the extension exists
            if (!$extension) {
                // If the extension is not found, return a 404 Not Found response
                $response = [
                    'status' => false,
                    'error' => 'Extension not found'
                ];

                return response()->json($response, Response::HTTP_NOT_FOUND);
            }
            // Find the user by ID
            $user = User::find($user_id);

            // Check if the user exists
            if (!$user) {
                // If the user is not found, return a 404 Not Found response
                $response = [
                    'status' => false,
                    'error' => 'User not found'
                ];

                return response()->json($response, Response::HTTP_NOT_FOUND);
            }

            if ($forceUpdate == true) {
                // Begin Transaction
                DB::beginTransaction();

                // Assign the extension to the user
                $user->extension_id = $id;
                $user->save();

                $extension->user = $user_id;
                $extension->save();

                // Commit transaction
                DB::commit();
            } else {
                $checkExist = User::where('extension_id', $id)->first();

                if (!empty($checkExist)) {
                    // Prepare the response data
                    $response = [
                        'status' => false,
                        'message' => 'Already assigned to a different user'
                    ];

                    // Return a JSON response with HTTP status code 409 (OK)
                    return response()->json($response, Response::HTTP_CONFLICT);
                }
            }

            // Prepare the response data
            $response = [
                'status' => true,
                'data' => ($extension) ? $extension : '', // Ensure data is not null
                'message' => 'Successfully assigned'
            ];

            // Return a JSON response with HTTP status code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Invalid input'], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Update the specified extension in storage.
     *
     * This method updates the extension with the given ID using the provided request data,
     * and returns a JSON response indicating success or failure.
     *
     * @param  \Illuminate\Http\Request $request The incoming HTTP request containing the update data.
     * @param  int $id The ID of the extension to be updated.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the result of the update operation.
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the extension by ID
        $extension = Extension::find($id);

        // Check if the extension exists
        if (!$extension) {
            // If the extension is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Extension not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'domain' => 'string',
                // 'extension' => 'string|unique:extensions,extension,' . $id,
                'extension' => [
                    'string',
                    'unique:extensions,extension,' . $id . ',id,account_id,' . $request->account_id,
                    // Rule::unique('extensions')->where('account_id', $request->account_id)->ignore($id),
                ],
                'password' => 'string',
                'voicemail_password' => 'string',

                'user' => 'numeric|exists:users,id',
                'account_code' => 'string|nullable',
                'effectiveCallerIdName' => 'string|nullable',
                'effectiveCallerIdNumber' => 'numeric|nullable',
                'outbundCallerIdName' => 'string|nullable',
                'outbundCallerIdNumber' => 'numeric|nullable',

                'emergencyCallerIdName' => 'string|nullable',
                'emergencyCallerIdNumber' => 'numeric|nullable',
                'directoryFullname' => 'string|nullable',
                'directoryVisible' => 'string|nullable',
                'directoryExtensionVisible' => 'string|nullable',
                'maxRegistration' => 'string|nullable',
                'limitMax' => 'string|nullable',
                'limitDestinations' => 'string|nullable',

                'voicemailEnabled' => 'in:Y,N',
                'voiceEmailTo' => 'string|nullable',
                'voiceMailFile' => 'string|nullable',
                'voiceMailkeepFile' => 'string|nullable',
                'missedCall' => 'string|nullable',
                'tollAllowValue' => 'string|nullable',
                'callTimeOut' => 'numeric|nullable',
                'callgroup' => 'string|nullable',
                'callScreen' => 'in:Enable,Disable|nullable',

                'record' => 'in:L,I,O,A,D',
                'description' => 'string|nullable',
                'callforward' => 'boolean',
                'callforwardTo' => 'string|nullable',
                'onbusy' => 'boolean',
                'onbusyTo' => 'string|nullable',
                'noanswer' => 'boolean',
                'noanswerTo' => 'string|nullable',
                'notregistered' => 'boolean',
                'notregisteredTo' => 'string|nullable',
                'dnd' => 'boolean',
                'followme' => 'boolean',
                'ignorebusy' => 'boolean',

                'blockIncomingStatus' => 'boolean',
                'blockOutGoingStatus' => 'boolean',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a JSON response with error messages
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // If assign user
        if ($request->has('user')) {

            $check = $this->isUserAssigned($request->user);

            if ($check) {
                if ($request->has('forceUpdate')) {
                    if ($check != $id) {

                        $exten = Extension::find($check);
                        $exten->user = NULL;
                        $exten->save();

                        $userInstance = User::find($request->user);
                        $userInstance->extension_id = NULL;
                        $userInstance->save();

                        User::where(['extension_id' => $id])->update(['extension_id' => NULL]);

                        User::where(['id' => $request->user])->update(['extension_id' => $id]);
                    }
                } else {
                    if ($check != $id) {
                        // Prepare the response data
                        $response = [
                            'status' => false,
                            'message' => 'Already assigned to a different user'
                        ];

                        // Return a JSON response with HTTP status code 409 (OK)
                        return response()->json($response, Response::HTTP_CONFLICT);
                    }
                }
            } else {

                $extension_id = User::where(['extension_id' => $id])->first();

                if ($extension_id) {
                    $userInstance = User::find($extension_id->id);
                    $userInstance->extension_id = NULL;
                    $userInstance->save();
                }

                $requestedUserId = $request->user;
                $userdata = User::find($requestedUserId);
                $userdata->extension_id = $id;
                $userdata->save();
            }
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // $validated['created_by'] = $userId;

        // Call the compareValues function to generate a formatted description based on the extension and validated data
        $formattedDescription = compareValues($extension, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Generate UID and attach it to the validated data
        createUid($action, $type, $formattedDescription, $userId);

        // Update the extension with the validated data
        $extension->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $extension,
            'message' => 'Successfully updated Extension',
        ];

        // Return a JSON response with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Search for extensions by extension name.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->get('query');

        // Perform search query using Eloquent ORM
        $extensions = Extension::where('extension', 'like', "%$query%");

        if ($request->get('account')) {
            $extensions->where('account_id', $request->get('account'));
        }

        $extensions = $extensions->get();

        // Prepare success response with search results
        $response = [
            'status' => true,
            'data' => $extensions,
            'message' => 'Successfully fetched',
        ];

        // Return a JSON response with domain data and success message
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Check if any user is assigned to the given extension ID.
     *
     * @param  int  $id The ID of the extension to check.
     * @return bool Returns true if any user is assigned to the extension, otherwise false.
     */
    public function isExtensionAssigned($id)
    {
        // Check if any user is assigned to the given extension ID
        $checkExist = User::where('extension_id', $id)->first();

        // Return true if any user is assigned, otherwise false
        return (!empty($checkExist)) ? true : false;
    }

    /**
     * Check if any extension is assigned to the given user ID.
     *
     * @param  int  $id The ID of the user to check.
     * @return bool Returns id if any extension is assigned to the user, otherwise false.
     */
    public function isUserAssigned($id)
    {
        // Check if any user is assigned to the given extension ID
        $extension = Extension::where('user', $id)->first();

        // Return true if any user is assigned, otherwise false
        return (!empty($extension)) ? $extension->id : false;
    }

    /**
     * Update the extension ID of the user forcefully to null.
     *
     * @param  int  $id The ID of the extension to forcefully update.
     * @return void
     */
    public function updateExtensionForcefully($id)
    {
        // Check if any user is assigned to the given extension ID
        $checkExist = User::where('extension_id', $id)->first();

        // If any user is assigned, update their extension ID to null
        if (!empty($checkExist)) {
            // Assuming $checkExist is an instance of User model
            User::where('id', $checkExist->id)->update(['extension_id' => null]);
        }

        // Check if any user is assigned to the given extension ID
        $checkExtensionExist = Extension::where('id', $id)->first();

        // If any user is assigned, update their extension ID to null
        if (!empty($checkExtensionExist)) {
            // Assuming $checkExist is an instance of User model
            Extension::where('id', $checkExtensionExist->id)->update(['user' => null]);
        }
    }

    // Test pbx
    public function executeSSHCommand()
    {
        $output = $this->sshService->executeCommand('ls -la');
        return response($output);
    }

    // List Of all directories
    public function directories()
    {
        $remoteDirectory = '/usr/local/freeswitch/conf/directory';

        // Change directory to the remote directory
        if (!$this->sshService->changeDirectory($remoteDirectory)) {
            die('Failed to change directory');
        }

        $directoryContents = $this->sshService->getAllFilesWithDirectory();

        // Output the list of files and directories
        return response()->json($directoryContents, 201);
    }

    // Delete Particular directory
    public function removeDirectory()
    {
        $dirName = 'check';
        $jsonResponse = $this->directories();

        // Get the JSON content from the response
        $jsonContent = $jsonResponse->getContent();

        // Decode the JSON content into a PHP object
        $phpObject = json_decode($jsonContent);

        // Check if decoding was successful
        if ($phpObject === null && json_last_error() !== JSON_ERROR_NONE) {
            die("Error decoding JSON content: " . json_last_error_msg());
        }

        if (in_array($dirName, $phpObject)) {
            $result = $this->sshService->removeDirectory($dirName);
            $data = [
                'status' => $result,
                'message' => 'Successfully created',
                'directory' => $dirName,
            ];
        } else {
            $data = [
                'status' => false,
                'error' => 'No Directory Exist',
            ];
        }

        // Output the list of files and directories
        return response()->json($data, 200);
    }

    // Add Directory
    public function addDirectory()
    {
        $dirName = 'check';
        $jsonResponse = $this->directories();

        // Get the JSON content from the response
        $jsonContent = $jsonResponse->getContent();

        // Decode the JSON content into a PHP object
        $phpObject = json_decode($jsonContent);

        // Check if decoding was successful
        if ($phpObject === null && json_last_error() !== JSON_ERROR_NONE) {
            die("Error decoding JSON content: " . json_last_error_msg());
        }

        if (!in_array($dirName, $phpObject)) {
            $result = $this->sshService->addDirectory($dirName);

            $data = [
                'status' => $result,
                'message' => 'Successfully created',
                'directory' => $dirName,
            ];
        } else {
            $data = [
                'status' => false,
                'error' => 'Directory Exist',
            ];
        }

        return response()->json($data, 201);
    }

    // xml
    public function xml()
    {
        $remoteDirectory = '/usr/local/freeswitch/conf/directory/check/';

        // $filename = 'dialpad';
        // // $this->createxml($filename);

        // Specify the file path
        $filePath = 'xml/dialpadww.xml'; // e.g., 'public/xml/example.xml'

        // Get the full file path
        // $fullPath = Storage::path($filePath);

        // Output the full file path
        // $result = $this->sshService->uploadFile($fullPath, $remoteDirectory);
        // echo $result;


        $socket = $this->esl();

        if ($socket->is_connected()) {
            $result = $socket->request('api sofia status');
            echo $result;
        } else {
            echo 'Not Connected';
        }

        // return response()->json($result, 201);
    }
}
