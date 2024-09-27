<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'IM'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'IM';
    }

    /**
     * Returns a list of all messages in the database.
     *
     * This API endpoint accepts a GET request with no parameters.
     * It returns a JSON response with HTTP status code 200 (OK)
     * containing a list of message objects.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $receiverId = $request->receiver_id;

        if (!$receiverId) {
            return response()->json([
                'status' => false,
                'message' => 'Receiver ID is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Build a query to fetch message
        $results = DB::table('messages')
            ->select('messages.*', 'message_statuses.user_id as receiver_user_id', 'message_statuses.receiver_id', 'message_statuses.status')
            ->join('message_statuses', 'messages.uuid', '=', 'message_statuses.message_uuid')
            ->where('messages.user_id', $userId)
            ->where('message_statuses.user_id', $receiverId);

        $results2 = DB::table('messages')
            ->select('messages.*', 'message_statuses.user_id as receiver_user_id', 'message_statuses.receiver_id', 'message_statuses.status')
            ->join('message_statuses', 'messages.uuid', '=', 'message_statuses.message_uuid')
            ->where('messages.user_id', $receiverId)
            ->where('message_statuses.user_id', $userId);

        // Use union to merge both results and order by id in descending order
        $mergedResults = $results->union($results2)->orderBy('id', 'desc')->paginate(40);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $mergedResults,
            'message' => 'Successfully fetched all messages'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Returns a specific message by id.
     *
     * This API endpoint accepts a GET request with the id parameter.
     * It returns a JSON response with HTTP status code 200 (OK)
     * containing the message object.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Fetch the message by id
        $message = Message::find($id);

        // Check if the message exists
        if (!$message) {
            // If the message is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Message not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => ($message) ? $message : '', // Ensure data is not null
            'message' => 'Successfully fetched'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Deletes a message by id.
     *
     * This API endpoint accepts a DELETE request with the id parameter.
     * It returns a JSON response with HTTP status code 200 (OK)
     * containing the success message.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Fetch the message by id
        $message = Message::find($id);

        // Check if the message exists
        if (!$message) {
            // If the message is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Message not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the message record
        $message->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted message'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
    
    /**
     * Returns a list of contacts for the authenticated user.
     *
     * This API endpoint accepts a GET request with no parameters.
     * It returns a JSON response with HTTP status code 200 (OK)
     * containing a list of user objects.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listOfContacts(Request $request)
    {
        $userId = $request->user()->id;

        // Fetch all the contacts for the user
        // by joining the messages table with
        // the message statuses table and the users table
        // and selecting distinct user records
        // with the 'user_id' and 'email' fields
        // and order by 'user_id' in descending order
        $results = User::join('message_statuses', 'users.id', '=', 'message_statuses.user_id')
            ->join('messages', 'message_statuses.message_uuid', '=', 'messages.uuid')
            ->where('messages.user_id', $userId)
            ->where('message_statuses.user_id', '!=', $userId)
            ->select('users.*')
            ->distinct()
            ->orderBy('users.id', 'desc')
            ->get();

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($results, Response::HTTP_OK);
    }

}
