<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conference;
use App\Models\Domain;
use App\Models\DummyExtension;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ConferenceController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Conference'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Conference';
    }

    /**
     * Fetch all conferences.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $account_id = request()->user()->account_id;

        $query = Conference::query();

        if ($account_id) {
            $query->where('account_id', $account_id);
        }

        // COMING FROM GLOBAL CONFIG
        $ROW_PER_PAGE = config('globals.PAGINATION.ROW_PER_PAGE');

        // Execute the query to fetch cdrs
        $clead = $query->orderBy('id', 'desc')->paginate($ROW_PER_PAGE);

        // Prepare a success response with the list of fax
        $response = [
            'status' => true,
            'data' => $clead,
            'message' => 'Successfully fetched all coferences'
        ];

        // Return a JSON response with the list of fax
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created conference resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new conference record in the database, and returning a 
     * JSON response indicating success or failure.
     *
     * It performs the following operations:
     * - Retrieves the authenticated user's ID and account ID.
     * - Merges the account ID into the request data.
     * - Validates the request data against a set of defined rules.
     * - If validation fails, returns a 403 Forbidden response with error messages.
     * - Begins a database transaction.
     * - Logs the action for audit purposes.
     * - Creates a new conference record with validated data.
     * - Commits the database transaction.
     * - Returns a JSON response with the created conference data and a 201 Created status.
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing conference data.
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the outcome of the operation.
     */
    public function store(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;
        $account_id = $request->user()->account_id;

        $request->merge(['account_id' => $account_id]);

        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'instance_id' => 'nullable|string|max:255',
                'conf_type' => 'required|in:public,private,webiner',
                // 'conf_ext' => 'nullable|string|max:10',
                'conf_name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('conferences')->where(function ($query) use ($request) {
                        return $query->where('account_id', $request->input('account_id'));
                    }),
                ],
                'moh_sound' => 'required|integer',
                'description' => 'nullable|string',
                'participate_pin' => 'nullable|string|max:11',
                'conf_max_members' => 'required|integer',
                'pin_retries' => 'required|integer|min:1|max:10', // Add limits if necessary
                'nopin' => 'required|in:0,1',
                'moderator_pin' => 'nullable|string|max:11',
                'wait_moderator' => 'nullable|in:0,1',
                'name_announce' => 'nullable|in:0,1',
                'end_conf' => 'nullable|in:0,1',
                'record_conf' => 'nullable|in:0,1',
                'status' => 'nullable|in:0,1',
                'conf_start_time' => 'nullable|date',
                'conf_end_time' => 'nullable|date|after_or_equal:conf_start_time',
                'notification_settings' => 'nullable|string',
                'conf_url' => 'nullable|string'
            ],
            // Custom validation messages
            [
                'conf_type.required' => 'The conference type is required.',
                'conf_type.in' => 'The conference type must be one of the following: public, private, webiner.'
            ]
        );

        // If validation fails
        if ($validator->fails()) {
            // If validation fails, return a JSON response with error messages
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve validated input
        $validated = $validator->validated();

        // Begin a database transaction
        DB::beginTransaction();

        // Defining action and type for creating UID
        $action = 'create';
        $type = $this->type;

        // conf_ext
        $startingPoint = config('globals.CONFERENCE_EXTENSION_START_FROM');

        $maxExtension = Conference::where('account_id', $request->account_id)->max('conf_ext');

        // Ensure the maxExtension is at least the starting point
        $maxExtension = $maxExtension !== null ? max($maxExtension, $startingPoint) : $startingPoint;

        // Fetch all existing extensions
        $existingExtensions = Conference::where('account_id', $request->account_id)
            ->pluck('conf_ext')
            ->toArray();

        // Generate a list of potential extensions
        $potentialExtensions = range($startingPoint, $maxExtension + 1); // Include +1 to cover the edge case

        // Find the missing extensions
        $availableExtensions = array_diff($potentialExtensions, $existingExtensions);

        // Get the smallest available extension number
        $newExtension = !empty($availableExtensions) ? min($availableExtensions) : $maxExtension + 1;

        $validated['conf_ext'] = $newExtension;

        // Log the action
        accessLog($action, $type, $validated, $userId);

        // Create a new conference record with validated data
        $data = Conference::create($validated);

        // create conference url
        $conferenceUrl = 'https://ucaas.webvio.in/conference?type=' . $data->conf_type . '/' . $data->id . '/' . generateRandomString();

        // update the conference url
        Conference::where('id', $data->id)->update(['conf_url' => $conferenceUrl]);

        $data['conf_url'] = $conferenceUrl;

        $max_members = $validated['conf_max_members'];

        $startingExtension = config('globals.DUMMY_EXTENSION_START_FROM') ?? 9000;

        $domainId = Domain::where('account_id', $validated['account_id'])->first()->id;

        if ($max_members > 0) {
            $inputData = [];

            for ($i = 0; $i < $max_members; $i++) {
                $randomPassword = rand(1000, 9999);
                $inputData[] = [
                    'account_id' => $validated['account_id'],
                    'domain' => $domainId,
                    'conference_id' => $data->id,
                    'extension' => $validated['account_id'] . 'dummy_' . $startingExtension + $i,
                    'password' => $randomPassword,
                    'voicemail_password' => $randomPassword,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }

            DummyExtension::insert($inputData);
        }

        // Commit the database transaction
        DB::commit();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully stored'
        ];

        // Return a JSON response with HTTP status code 201 (Created)
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Checks the status of a conference by delegating to FreeSwitchController.
     * 
     * This method creates a new FreeSwitchController instance and calls its 
     * checkConference method to get the current status of conferences in the system.
     *
     * @param mixed $request The incoming request object
     * @return \Illuminate\Http\JsonResponse Returns JSON response with conference status data
     */
    protected function checkConference($request)
    {
        $freeSWitch = new FreeSwitchController();

        return $freeSWitch->checkConference($request);
    }

    /**
     * Starts a conference call with the given name and room ID.
     * 
     * This method validates the conference exists, checks for available dummy extensions,
     * marks an extension as joined, and initiates the conference call through FreeSwitch.
     * 
     * @param \Illuminate\Http\Request $request Request containing name and conference ID
     * @return \Illuminate\Http\JsonResponse Returns success/failure status with appropriate message
     */
    public function registerExtensions(Request $request)
    {
        // Validate the request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'string|max:255',
                'room_id' => 'exists:conferences,id',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, prepare error response with validation errors
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            // Return a JSON response with validation errors and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        $name = $request->name;
        $roomId = $request->room_id;

        $conference = Conference::find($roomId);

        if (!$conference) {
            $response = [
                'status' => false,
                'message' => 'Conference not found',
            ];
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $dummyExt = DummyExtension::where(['conference_id' => $conference->id, 'joined' => 0])
            ->orderBy('created_at', 'asc')  // Order by 'created_at' ascending
            ->get();

        if ($dummyExt->isEmpty()) {
            $response = [
                'status' => false,
                'message' => 'Room is full',
            ];
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        $firstRow = $dummyExt->first();

        $domain = Domain::where('account_id', $firstRow->account_id)->first();

        if (!$domain) {
            $response = [
                'status' => false,
                'message' => 'Domain not found',
            ];
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $data = [
            'extension_id' => $firstRow->id,
            'name' => $name,
            'domainName' => $domain->domain_name,
            'room_id' => $roomId,
            'participate_pin' => $conference->participate_pin,
            'moderator_pin' => $conference->moderator_pin,
            'extension' => $firstRow->extension,
            'password' => $firstRow->password
        ];

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully initiated'
        ];

        // Return a JSON response with HTTP status code 201 (Created)
        return response()->json($response, Response::HTTP_CREATED);

        //  bgapi originate {origination_caller_id_name='vivek negi'}user/1002@webs.9.webvio.in &conference(1@)"

    }

    /**
     * Start a conference call with the given participant details
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @throws \Illuminate\Validation\ValidationException
     * 
     * Request body parameters:
     * @bodyParam id integer required The ID of the dummy extension. Example: 1
     * @bodyParam name string required The name of the conference participant. Max length: 100. Example: "John Doe"
     * 
     */
    public function startConference(Request $request)
    {
        // Validate the request data
        $validator = Validator::make(
            $request->all(),
            [
                'id' => 'required|exists:dummy_extensions,id',
                'name' => 'required|string|max:100'
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, prepare error response with validation errors
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            // Return a JSON response with validation errors and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        $dext = DummyExtension::find($request->id);

        // Update the 'joined' status to 1
        $dext->joined = 1;
        $dext->save();

        $domain = Domain::where('account_id', $dext->account_id)->first();

        if (!$domain) {
            $response = [
                'status' => false,
                'message' => 'Domain not found',
            ];
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $name = $request->name;
        $roomId = $dext->conference_id;
        $domainName = $domain->domain_name;
        $extension = $dext->extension;
        $user = 'user/' . $extension . '@' . $domainName;

        $freeSWitch = new FreeSwitchController();

        return $freeSWitch->createConference($name, $roomId, $user);
    }

    /**
     * Retrieve conference details by ID.
     * 
     * This method finds a conference by ID and fetches its details from FreeSwitch.
     * If the conference is not found, it returns a 404 Not Found response.
     * Otherwise, it delegates to FreeSwitchController to get the conference details.
     *
     * @param int $id The ID of the conference to retrieve details for
     * @return \Illuminate\Http\JsonResponse Returns conference details or error response
     */
    public function conferenceDetailsById($id)
    {
        $validator = Validator::make(['id' => $id], [
            'id' => 'required|integer|exists:conferences,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $conference = Conference::find($id);

        if (!$conference) {
            $response = [
                'status' => false,
                'error' => 'Invalid room id.'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $fsController = new FreeSwitchController();
        return $fsController->conferenceDetails($conference->id);
    }

    /**
     * Execute an action on a conference member.
     * 
     * This method validates the incoming request and performs the specified action 
     * (mute or kick) on a conference member. It requires the action type, conference 
     * room ID, and member ID. The request is validated before being delegated to 
     * FreeSwitchController to execute the action.
     *
     * @param \Illuminate\Http\Request $request The request containing action, room_id and member
     * @return \Illuminate\Http\JsonResponse Returns success/failure status with response data
     */
    public function conferenceAction(Request $request)
    {
        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'action' => 'required|in:mute,kick',
                'room_id' => 'required|exists:conferences,id',
                'member' => 'required|string',
            ]
        );

        // If validation fails
        if ($validator->fails()) {
            // If validation fails, return a JSON response with error messages
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        $action = $request->action;
        $roomId = $request->room_id;
        $member = $request->member;

        $fsController = new FreeSwitchController();
        return $fsController->conferenceAction($action, $roomId, $member);
    }
}
