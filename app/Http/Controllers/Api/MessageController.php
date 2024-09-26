<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
        // Build a query to fetch messages
        $message = Message::query();

        // Execute the query to fetch messages
        $groups = $message->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $groups,
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
}
