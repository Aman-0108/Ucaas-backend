<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Autodialer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AutodialerController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Autodialler'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Autodialler';
    }

    /**
     * Display a listing of autodialers filtered by the authenticated user's account.
     *
     * Retrieves all autodialers associated with the current user's account ID.
     * Returns a JSON response with the autodialers data and a success message.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Retrieve all dialers from the database
        $autodialers = Autodialer::with('members');

        $account_id = $request->user()->account_id;

        if ($account_id) {
            $autodialers->where('account_id', $account_id);
        }

        // Execute the query to fetch domains
        $autodialers = $autodialers->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $autodialers,
            'message' => 'Successfully fetched all autodialers'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Display the specified autodialer resource.
     *
     * This method fetches the autodialer with the given ID from the database and returns it as a JSON response.
     * If the autodialer is not found, it returns a 404 Not Found response.
     * If the autodialer is found, it checks if the user has permission to access it.
     * If the user doesn't have permission, it returns a 403 Forbidden response.
     * If the user has permission, it returns a JSON response containing the fetched autodialer.
     *
     * @param  int  $id The ID of the autodialer to be fetched.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the fetched autodialer or an error message.
     */
    public function show($id)
    {
        // Find the autodialer by ID
        $autodialer = Autodialer::find($id);

        // Check if the autodialer exists
        if (!$autodialer) {
            // If the autodialer is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Autodialer not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Retrieve the account_id of the authenticated user
        $account_id = auth()->user()->account_id;

        // Check if the user has permission to access the group
        if ($account_id !== $autodialer->account_id) {
            // If user doesn't have permission, prepare error response
            $response = [
                'status' => false,
                'error' => 'You do not have permission to access this resource.',
            ];

            // Return a JSON response with error message and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $autodialer, // Ensure data is not null
            'message' => 'Successfully fetched'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created autodialler in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
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
                'name' => [
                    'required',
                    Rule::unique('autodialers')->where(function ($query) use ($account_id) {
                        return $query->where('account_id', $account_id);
                    }),
                ],
                'tries' => 'required|integer|min:1',
                'status' => 'required|in:0,1',
                'schedule_time' => 'nullable|date_format:Y-m-d H:i:s',
                'did_configure_id' => 'nullable|exists:did_configures,id',
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

        // Defining action and type for access log
        $action = 'create';
        $type = $this->type;

        // Log the action
        accessLog($action, $type, $validated, $userId);

        // Create a new autodialer record with validated data
        $data = Autodialer::create($validated);

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
     * Update an autodialer by ID.
     *
     * This method finds and updates an autodialer based on the provided ID and request data.
     * If the autodialer is not found, it returns a 404 Not Found response.
     * If the validation fails, it returns a 403 Forbidden response with validation errors.
     * If the update is successful, it returns a success message along with the updated autodialer data.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the update data
     * @param  int  $id The ID of the autodialer to update
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;
        $account_id = $request->user()->account_id;

        $request->merge(['account_id' => $account_id]);

        // Find the autodialer with the given ID
        $autodialer = Autodialer::find($id);

        // Check if the autodialer exists
        if (!$autodialer) {
            // If the autodialer is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Autodialer not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Check if the authenticated user has permission to update the group
        if ($account_id !== $autodialer->account_id) {
            // If user doesn't have permission, prepare error response
            $response = [
                'status' => false,
                'error' => 'You do not have permission to access this resource.',
            ];

            // Return a JSON response with error message and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'name' => [
                    Rule::unique('autodialers')->where(function ($query) use ($account_id) {
                        return $query->where('account_id', $account_id);
                    }),
                ],
                'tries' => 'integer|min:1',
                'status' => 'in:0,1',
                'schedule_time' => 'nullable|date_format:Y-m-d H:i:s',
                'did_configure_id' => 'nullable|exists:did_configures,id',
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

        // Call the compareValues function to generate a formatted description based on the gateway and validated data
        $formattedDescription = compareValues($autodialer, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Log the action
        accessLog($action, $type, $formattedDescription, $userId);

        // Update the group record with validated data
        $autodialer->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $autodialer,
            'message' => 'Successfully updated autodialer',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified autodialer resource from storage.
     *
     * This method retrieves the autodialer with the given ID, checks if it exists,
     * and deletes the autodialer record from the database if the authenticated user
     * has permission to delete. It returns a JSON response indicating success or
     * failure.
     *
     * @param  int  $id The ID of the autodialer to delete
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function destroy($id)
    {
        // Find the autodialer with the given ID
        $autodialer = Autodialer::find($id);

        $userId = auth()->user()->id;
        $account_id = auth()->user()->account_id;

        // Check if the autodialer exists
        if (!$autodialer) {
            // If the autodialer is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Autodialer not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Check if the authenticated user has permission to update the group
        if ($account_id !== $autodialer->account_id) {
            // If user doesn't have permission, prepare error response
            $response = [
                'status' => false,
                'error' => 'You do not have permission to access this resource.',
            ];

            // Return a JSON response with error message and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        $action = 'delete';
        $type = $this->type;

        // Log the action
        accessLog($action, $type, $autodialer, $userId);

        // Delete the group record
        $autodialer->forceDelete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted autodialer'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
