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
        // $tagUser = TagUser::where(['tag_id' => $validated['tag_id'], 'user_id' => $validated['user_id']])->first();

        // if ($tagUser) {
        //     // remove from tag
        //     $tagUser->delete();
        // }

        $tagUser = TagUser::where(['tag_id' => $validated['tag_id'], 'user_id' => $validated['user_id']])->first();

        if ($tagUser) {

            $response = [
                'status' => false,
                'message' => 'Already tagged',
            ];

            return response()->json($response, Response::HTTP_EXPECTATION_FAILED);
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
     * Remove the specified Tag User resource from storage.
     *
     * This method retrieves the Tag User with the given ID, checks if it exists,
     * and deletes the Tag User record from the database. It returns a JSON response
     * indicating success or failure.
     *
     * @param  int  $id The ID of the Tag User to delete
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function destroy($id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = auth()->user()->id;

        // Retrieve the account ID of the authenticated user
        $account_id = auth()->user()->account_id;

        // Find the tagUser by ID
        $tagUser = TagUser::find($id);

        // Check if the groupUser exists
        if (!$tagUser) {
            // If the groupUser is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Tag User not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $tag = Tag::find($tagUser->tag_id);

        // Check if the user has permission to access the group_user
        if ($account_id !== $tag->account_id) {
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
        accessLog($action, $type, $tagUser, $userId);

        // Delete the groupUser
        $tagUser->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted user'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
