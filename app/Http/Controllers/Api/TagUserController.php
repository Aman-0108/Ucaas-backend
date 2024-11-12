<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\TagUser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TagUserController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'tag_user'.
     */
    public function __construct()
    {
        // Perform initialization
        $this->type = 'tag_user';
    }

    /**
     * Store a newly created tag_user record in storage.
     *
     * This method validates the incoming request data and creates a new
     * tag_user record in the database. If the validation fails, it returns
     * a 400 Bad Request response with validation errors. If the tag_user is
     * successfully stored, it returns a success message along with the
     * stored tag_user data.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the tag_user data
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function store(Request $request)
    {
        // Defining action and type
        $action = 'create';
        $type = $this->type;

        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'tag_id' => 'required|exists:tags,id',
                'user_id' => 'required|exists:users,id',
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

        //  check if user exist in other tags
        $tagUser = TagUser::where(['tag_id' => $validated['tag_id'], 'user_id' => $validated['user_id']])->first();

        if ($tagUser) {
            // remove from tag
            $tagUser->delete();
        }

        // Create a new tag with validated data
        $data = TagUser::create($validated);

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
     * Remove the specified TagUser resource from storage.
     *
     * This method retrieves the TagUser with the given user_id, checks if it exists,
     * and deletes the TagUser record from the database. It returns a JSON response
     * indicating success or failure.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        // Defining action and type
        $action = 'delete';
        $type = $this->type;

        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => 'required|exists:users,id',
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

        // check if user belongs to same account
        if ($request->user()->account_id != $request->account_id) {
            $response = [
                'status' => false,
                'message' => 'You are not authorized to delete this tag',
            ];

            return response()->json($response, Response::HTTP_BAD_REQUEST);
        }

        // Retrieve validated input
        $validated = $validator->validated();

        // Begin a database transaction
        DB::beginTransaction();

        // Log the action
        accessLog($action, $type, $validated, $userId);

        // delete tag
        TagUser::where('user_id', $validated['user_id'])->delete();

        // Commit the database transaction
        DB::commit();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted',
        ];

        // Return a JSON response with HTTP status code 201 (Created)
        return response()->json($response, Response::HTTP_CREATED);
    }
}
