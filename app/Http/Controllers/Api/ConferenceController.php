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
                'conf_ext' => 'nullable|string|max:10',
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

        // $exist = $this->checkConference($request);

        // if ($exist) {
        //     $response = [
        //         'status' => false,
        //         'message' => 'Conference name already exist',
        //     ];
        //     return response()->json($response, Response::HTTP_FORBIDDEN);
        // }

        // Begin a database transaction
        DB::beginTransaction();

        // Defining action and type for creating UID
        $action = 'create';
        $type = $this->type;

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

        $ROW_PER_PAGE = config('globals.DUMMY_EXTENSION_START_FROM') ?? 9000;

        $domainId = Domain::where('account_id', $validated['account_id'])->first()->id;

        if ($max_members > 0) {
            $inputData = [];

            for ($i = 0; $i < $max_members; $i++) {
                $randomPassword = rand(1000, 9999);
                $inputData[] = [
                    'account_id' => $validated['account_id'],
                    'domain' => $domainId,
                    'conference_id' => $data->id,
                    'extension' => 'dummy_' . $ROW_PER_PAGE + $i,
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

    protected function checkConference($request)
    {
        $freeSWitch = new FreeSwitchController();

        return $freeSWitch->checkConference($request);
    }
}
