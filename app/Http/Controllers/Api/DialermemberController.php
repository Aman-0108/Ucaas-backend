<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Autodialer;
use App\Models\DialerMember;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DialermemberController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Dialermember'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Dialermember';
    }

    /**
     * Store a newly created DialerMember resource in storage.
     *
     * This method validates the incoming request data, creates a new DialerMember
     * record in the database, logs the action, and returns a JSON response indicating
     * success or failure.
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing the data to be stored.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the result of the store operation.
     */
    public function store(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;
        $account_id = $request->user()->account_id;

        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'autodialers_id' => 'required|exists:autodialers,id',
                'number' => [
                    'required',
                    'string',
                    'min:11',
                    'max:11',
                    Rule::unique('dialer_members')->where(function ($query) use ($request) {
                        return $query->where('autodialers_id', $request->autodialers_id);
                    }),
                ],
                'tries' => 'integer|min:1',
                'status' => 'in:0,1'
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

        // Create a new dialerMember record with validated data
        $data = DialerMember::create($validated);

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
     * Remove the specified DialerMember resource from storage.
     *
     * This method retrieves the DialerMember with the given ID, checks if it exists,
     * and deletes the DialerMember record from the database if the authenticated user
     * has permission to delete. It returns a JSON response indicating success or
     * failure.
     *
     * @param  int  $id The ID of the DialerMember to delete
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function destroy($id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = auth()->user()->id;
        $account_id = auth()->user()->account_id;

        // Find the dialerMember by ID
        $dialerMember = DialerMember::find($id);

        $autodialer = Autodialer::find($dialerMember->autodialers_id);

        // Check if the dialerMember exists
        if (!$dialerMember) {
            // If the dialerMember is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Dialer member not found'
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

        // Defining action and type for access log
        $action = 'delete';
        $type = $this->type;

        // Log the action
        accessLog($action, $type, $dialerMember, $userId);

        // Delete the autodialer record
        $dialerMember->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $dialerMember,
            'message' => 'Successfully deleted'
        ];

        // Return a JSON response with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
