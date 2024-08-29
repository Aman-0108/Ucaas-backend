<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Variable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use App\Rules\ValidString;

class VariableController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Variable'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Variable';
    }

    /**
     * Retrieves a paginated list of variables.
     *
     * @param Request $request The HTTP request object.
     * @return \Illuminate\Http\JsonResponse The JSON response containing the list of variables.
     */
    public function index(Request $request)
    {
        // Start building the query to fetch variables
        $variables = Variable::query();

        if($request->has('search')) {
            $search = $request->input('search');
            $variables = $variables->where('command', 'like', '%'.$search.'%')
                                    ->orWhere('var_value', 'like', '%'.$search.'%')
                                    ->orWhere('hostname', 'like', '%'.$search.'%')
                                    ->orWhere('var_name', 'like', '%'.$search.'%')
                                    ->orWhere('category', 'like', '%'.$search.'%')
                                    ->orWhere('enabled', 'like', '%'.$search.'%');
        }

        // Get the number of rows per page from the global configuration
        $ROW_PER_PAGE = config('globals.PAGINATION.ROW_PER_PAGE');

        // Execute the query to fetch variables
        $variables = $variables->orderBy('id', 'desc')->paginate($ROW_PER_PAGE);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $variables,
            'message' => 'Successfully fetched all variables'
        ];

        // Return a JSON response containing the list of variables
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = ($request->user()) ? $request->user()->id : null;

        // Validate the request data
        $validator = Validator::make(
            $request->all(),
            [
                'command' => 'string|max:255|required',
                'category' => 'string|required',
                'var_name' => 'string|required',
                'var_value' => 'string|required',
                'hostname' => 'string|required',
                'enabled' => 'string|required',
                'order' => 'integer|required',
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

        // Set the created_by field of the validated data to the user ID
        $validated['created_by'] = $userId;

        // Create a new variable record in the database
        $data = Variable::create($validated);

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

    /**
     * Update a variable record in the database.
     *
     * @param \Illuminate\Http\Request $request The HTTP request object.
     * @param int $id The ID of the variable to update.
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the success or failure of the update.
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the variable with the given ID
        $variable = Variable::find($id);

        // Check if the variable exists
        if (!$variable) {
            // Prepare error response
            $response = [
                'status' => false,
                'error' => 'variable not found'
            ];

            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the request data
        $validator = Validator::make(
            $request->all(),
            [
                'command' => ['string','max:255','nullable', new ValidString], // The command associated with the variable
                'category' => ['string', 'nullable', new ValidString], // The category of the variable
                'var_name' => ['string', 'nullable', new ValidString], // The name of the variable
                'var_value' => ['string', 'nullable', new ValidString],// The value of the variable
                'hostname' => ['string', 'nullable', new ValidString], // The hostname associated with the variable
                'enabled' => ['string', 'nullable', new ValidString], // Indicates whether the variable is enabled
                'order' => 'integer|nullable', // The order of the variable
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // Prepare error response with validation errors
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

        // Update the variable with the validated data
        $variable->update($validated);

        // Prepare success response
        $response = [
            'status' => true,
            'data' => $variable,
            'message' => 'Successfully updated variable',
        ];

        // Return a JSON response indicating successful update
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id The ID of the variable to display
     * @return \Illuminate\Http\JsonResponse The JSON response containing the variable details
     */
    public function show($id)
    {
        // Find the variable with the given ID
        $variable = Variable::find($id);

        // Check if the variable exists
        if (!$variable) {
            // If variable is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'variable not found'
            ];
            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare success response with variable details
        $response = [
            'status' => true, // Set status to true to indicate success
            'data' => ($variable) ? $variable : '', // Include variable details if found
            'message' => 'Successfully fetched' // Include a success message
        ];

        // Return a JSON response containing the variable details
        return response()->json($response, Response::HTTP_OK);
    }
    
    /**
     * Delete a specific variable.
     *
     * @param Request $request The HTTP request object containing the authenticated user's ID.
     * @param int $id The ID of the variable to delete.
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the success or failure of the deletion.
     */
    public function destroy(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the variable with the given ID
        $variable = Variable::find($id);

        // Check if the variable exists
        if (!$variable) {
            // If variable is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'variable not found'
            ];

            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the variable from the database
        $variable->delete();

        // Prepare success response
        $response = [
            'status' => true,
            'message' => 'Successfully deleted variable'
        ];

        // Return a JSON response indicating successful deletion and 200 status code
        return response()->json($response, Response::HTTP_OK);
    }
}

