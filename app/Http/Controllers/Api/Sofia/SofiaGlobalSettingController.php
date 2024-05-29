<?php

namespace App\Http\Controllers\Api\Sofia;

use App\Http\Controllers\Controller;
use App\Models\SofiaGlobalSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SofiaGlobalSettingController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Sofia Global Settings'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Global_Settings';
    }

    public function index(Request $request)
    {
        // Start building the query to fetch settings
        $sofiaGlobalSetting = SofiaGlobalSetting::query();

        // Check if the request contains an 'account' parameter
        if ($request->has('account')) {
            // If 'account' parameter is provided, filter settings by account ID
            $sofiaGlobalSetting->where('account_id', $request->account);
        }

        // Execute the query to fetch settings
        $sofiaGlobalSetting = $sofiaGlobalSetting->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $sofiaGlobalSetting,
            'message' => 'Successfully fetched.'
        ];

        // Return a JSON response containing the list of sip profiles
        return response()->json($response, Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Validate the request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|unique:sofia_global_settings,name',
                'value' => 'required|string',
                'description' => 'required|string',
                'enabled' => 'boolean',
                'created_by' => 'required|exists:users,id',
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

        // Retrieve the validated input
        $validated = $validator->validated();

        // Begin a database transaction
        DB::beginTransaction();

        // Defining action and type for creating UID
        $action = 'create';
        $type = $this->type;

        // Generate UID and attach it to the validated data
        $validated['uid_no'] = createUid($action, $type, $validated, $userId);

        // Create a new Sip profile domain record in the database
        $data = SofiaGlobalSetting::create($validated);

        // Commit the database transaction
        DB::commit();

        // Prepare success response
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully stored'
        ];

        // Return a JSON response indicating successful storage and 201 status code
        return response()->json($response, Response::HTTP_CREATED);
    }

    public function show($id)
    {
        // Find the settings with the given ID
        $sofiaGlobalSetting = SofiaGlobalSetting::find($id);

        // Check if the settings exists
        if (!$sofiaGlobalSetting) {
            // If profile is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Settings not found'
            ];
            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare success response with profile details
        $response = [
            'status' => true,
            'data' => ($sofiaGlobalSetting) ? $sofiaGlobalSetting : '', // Include settings details if found
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response containing the domain details
        return response()->json($response, Response::HTTP_OK);
    }

    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the settings with the given ID
        $globalSettings = SofiaGlobalSetting::find($id);

        // Check if the settings exists
        if (!$globalSettings) {
            // If settings is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Settings not found'
            ];

            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // checking for permission to update
        if (!$globalSettings->isEditable) {
            // Prepare success response
            $response = [
                'status' => true,
                'message' => 'You dont have permission to edit this.'
            ];

            // Return a JSON response indicating successful deletion and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Check if the authenticated user has permission to edit the settings
        if ($globalSettings->created_by !== $userId) {
            // If user doesn't have permission, prepare error response
            $response = [
                'status' => false,
                'error' => 'You dont have access to edit.'
            ];

            // Return a JSON response with error message and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Validate the request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'unique:sofia_global_settings,name,' . $id,
                'value' => 'string',
                'description' => 'string',
                'enabled' => 'boolean',
                'created_by' => 'exists:users,id',
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

        // Retrieve the validated input...
        $validated = $validator->validated();

        // Call the compareValues function to generate a formatted description based on the domain and validated data
        $formattedDescription = compareValues($globalSettings, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Generate UID and attach it to the validated data
        $validated['uid_no'] = createUid($action, $type, $formattedDescription, $userId);

        // Update the settings with the validated data
        $globalSettings->update($validated);

        // Prepare success response
        $response = [
            'status' => true,
            'data' => $globalSettings,
            'message' => 'Successfully updated.',
        ];

        // Return a JSON response indicating successful update
        return response()->json($response, Response::HTTP_OK);
    }

    public function destroy(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the settings with the given ID
        $sofiaGlobalSetting = SofiaGlobalSetting::find($id);

        // Check if the profile exists
        if (!$sofiaGlobalSetting) {
            // If settings is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Profile not found'
            ];

            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // checking for permission to delete
        if (!$sofiaGlobalSetting->isEditable) {
            // Prepare success response
            $response = [
                'status' => true,
                'message' => 'You dont have permission to delete this.'
            ];

            // Return a JSON response indicating successful deletion and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Generate UID for the deletion action
        $type = $this->type;
        createUid('destroy', $type, $sofiaGlobalSetting, $userId);

        // Delete the setting from the database
        $sofiaGlobalSetting->delete();

        // Prepare success response
        $response = [
            'status' => true,
            'message' => 'Successfully deleted.'
        ];

        // Return a JSON response indicating successful deletion and 200 status code
        return response()->json($response, Response::HTTP_OK);
    }
}
