<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TagController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Tag'.
     */
    public function __construct()
    {
        // Perform initialization
        $this->type = 'Tag';
    }

    /**
     * Fetch all tags for the authenticated user's account.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $accountId = $request->user()->account_id;

        $tags = Tag::where('account_id', $accountId)->get();

        $response = [
            'status' => true,
            'data' => $tags,
            'message' => 'Successfully fetched all tags',
        ];

        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Retrieve a tag by ID.
     *
     * This method finds and retrieves a tag based on the provided ID.
     * If the tag is not found, it returns a 404 Not Found response.
     * If the tag is found, it returns a JSON response containing the tag data.
     *
     * @param  int  $id The ID of the tag to retrieve
     * @return \Illuminate\Http\JsonResponse The JSON response containing the tag data or an error message
     */
    public function show($id)
    {
        $tag = Tag::find($id);

        // Check if the tag exists
        if (!$tag) {
            // If the tag is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Tag not found',
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Retrieve the account_id of the authenticated user
        $account_id = auth()->user()->account_id;

        // Check if the user has permission to access the tag
        if ($account_id !== $tag->account_id) {
            // If user doesn't have permission, prepare error response
            $response = [
                'status' => false,
                'error' => 'You do not have permission to access this resource.',
            ];

            // Return a JSON response with error message and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        $response = [
            'status' => true,
            'data' => $tag,
            'message' => 'Successfully fetched tag',
        ];

        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created tag resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new tag record in the database, and returning a
     * JSON response indicating success or failure.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Defining action and type
        $action = 'create';

        // Defining type
        $type = $this->type;

        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;
        $account_id = $request->user()->account_id;
        $request->merge(['account_id' => $account_id]);

        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|unique:tags,name,NULL,id,account_id,' . $request->account_id,
            ]
        );

        // If validation fails
        if ($validator->fails()) {
            // If validation fails, return a JSON response with error messages
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors(),
            ];

            return response()->json($response, Response::HTTP_BAD_REQUEST);
        }

        // Retrieve validated input
        $validated = $validator->validated();

        // Begin a database transaction
        DB::beginTransaction();

        // Log the action
        accessLog($action, $type, $validated, $userId);

        // Create a new tag with validated data
        $data = Tag::create($validated);

        // Commit the database transaction
        DB::commit();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully stored',
        ];

        // Return a JSON response with HTTP status code 201 (Created)
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Update the specified tag resource in storage.
     *
     * This method retrieves the tag with the given ID, checks if it exists,
     * validates the incoming request data, and updates the tag record in the
     * database if validation passes. It ensures that the authenticated user
     * has permission to access and update the tag. It returns a JSON response
     * indicating success or failure along with any relevant data or error messages.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request
     * @param int $id The ID of the tag to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the tag by ID
        $tag = Tag::find($id);

        // Check if the tag exists
        if (!$tag) {
            // If the tag is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Tag not found',
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $account_id = $request->user()->account_id;

        $request->merge(['account_id' => $tag->account_id]);

        // Retrieve the account_id of the authenticated user
        $account_id = auth()->user()->account_id;

        // Check if the user has permission to access the tag
        if ($account_id !== $tag->account_id) {
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
                'name' => 'required|unique:tags,name,' . $id . ',id,account_id,' . $request->account_id,
            ]
        );

        // If validation fails
        if ($validator->fails()) {
            // If validation fails, return a JSON response with error messages
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors(),
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve validated input
        $validated = $validator->validated();

        // Begin a database transaction
        DB::beginTransaction();

        // Log the action
        accessLog('update', $this->type, $validated, $userId);

        // Update the tag with validated data
        $tag->update($validated);

        // Commit the database transaction
        DB::commit();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $tag,
            'message' => 'Successfully updated',
        ];

        // Return a JSON response with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Delete a tag by ID.
     *
     * This API endpoint accepts a DELETE request with the id parameter.
     * It returns a JSON response with HTTP status code 200 (OK)
     * containing a success message.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $tag = Tag::find($id);

        // Check if the tag exists
        if (!$tag) {
            // If the tag is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Tag not found',
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Retrieve the account_id of the authenticated user
        $account_id = auth()->user()->account_id;

        // Check if the user has permission to access the tag
        if ($account_id !== $tag->account_id) {
            // If user doesn't have permission, prepare error response
            $response = [
                'status' => false,
                'error' => 'You do not have permission to access this resource.',
            ];
        }

        // Delete the tag
        $tag->delete();

        $response = [
            'status' => true,
            'message' => 'Successfully deleted tag',
        ];

        return response()->json($response, Response::HTTP_OK);
    }
}
