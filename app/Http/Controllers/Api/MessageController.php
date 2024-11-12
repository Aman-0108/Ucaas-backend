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
     * Returns a list of all contacts for the current user.
     *
     * This API endpoint accepts a GET request with no parameters.
     * It returns a JSON response with HTTP status code 200 (OK)
     * containing a list of contact objects.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listOfContacts(Request $request)
    {
        $userId = $request->user()->id;

        // Build a query to fetch all contacts
        $results = User::join('message_statuses', 'users.id', '=', 'message_statuses.user_id')
            ->join('messages', 'message_statuses.message_uuid', '=', 'messages.uuid')
            ->join('extensions as e', 'e.id', '=', 'users.extension_id')
            ->where('messages.user_id', $userId)
            ->where('message_statuses.user_id', '!=', $userId)
            ->select(
                'users.name',
                'users.email',
                'users.id',
                'e.id as extension_id',
                'e.extension',
                DB::raw('(
                    SELECT JSON_OBJECT(
                        "message_text", message_text, 
                        "created_at", created_at, 
                        "id", id
                    ) 
                    FROM messages 
                    WHERE messages.user_id = users.id 
                    ORDER BY messages.created_at DESC 
                    LIMIT 1
                ) AS last_message_data'),
                DB::raw('(
                    SELECT JSON_OBJECT(
                        "message_text", message_text,
                        "created_at", created_at,
                        "id", id
                    ) 
                    FROM messages
                    WHERE messages.user_id = users.id
                    AND messages.is_pinned = 1
                    ORDER BY messages.created_at DESC
                    LIMIT 1
                ) AS pin_message')
            )
            ->distinct()
            ->orderBy('users.id', 'desc')
            ->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $results,
            'message' => 'Successfully fetched all contacts'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Pins a message for the current user.
     *
     * This API endpoint accepts a GET request with a required parameter 'message_id'.
     * It returns a JSON response with HTTP status code 200 (OK) containing the pinned message.
     *
     * @param int $message_id The ID of the message to be pinned.
     * @return \Illuminate\Http\JsonResponse The JSON response containing the pinned message.
     */
    public function isPinned($message_id, $unpin = null)
    {
        $user_id = auth()->user()->id;

        // If the pin parameter is not provided, default it to true
        $unpin = $unpin ?? true;

        // Check if the message exists for the user
        $message = Message::where('id', $message_id)->where('user_id', $user_id)->first();

        if (!$message) {
            // If the message is not found, return a 404 Not Found response
            return response()->json([
                'status' => false,
                'error' => 'Message not found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Unpin any previously pinned message for the user
        Message::where('user_id', $user_id)->update(['is_pinned' => 0]);

        if ($unpin) {
            return response()->json([
                'status' => true,
                'data' => $message,
                'message' => 'Successfully unpinned',
            ], Response::HTTP_OK);
        }

        // Pin the selected message
        $message->update(['is_pinned' => 1]);

        return response()->json([
            'status' => true,
            'data' => $message,
            'message' => 'Successfully pinned',
        ], Response::HTTP_OK);
    }
}
