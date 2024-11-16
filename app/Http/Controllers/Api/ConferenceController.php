<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conference;
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
                'conf_ext' => 'nullable|string|max:10',
                'conf_name' => 'required|string|max:255',
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

        // Log the action
        accessLog($action, $type, $validated, $userId);

        // Create a new conference record with validated data
        $data = Conference::create($validated);

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
}
