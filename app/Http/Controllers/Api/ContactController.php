<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    /**
     * Retrieve all contacts.
     *
     * This method retrieves all contacts from the database.
     * It returns a JSON response containing the list of contacts.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response containing the list of contacts
     */
    public function index(Request $request)
    {
        // Retrieve the authenticated user's ID
        $userId = $request->user()->id;

        $query = Contact::query();

        // Filter contacts by user ID if provided
        $contacts = $userId ? $query->where('user_id', $userId)->get() : $query->get();

        // Prepare a success response with the list of contacts
        $response = [
            'status' => true,
            'data' => $contacts,
            'message' => 'Successfully fetched all contacts'
        ];

        // Return a JSON response with the list of contacts
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Retrieve an Contact by ID.
     *
     * This method finds and retrieves an Contact based on the provided ID.
     * If the Contact is not found, it returns a 404 Not Found response.
     * If the Contact is found, it returns a JSON response containing the Contact data.
     *
     * @param  int  $id The ID of the Contact to retrieve
     * @return \Illuminate\Http\JsonResponse The JSON response containing the Contact data or an error message
     */
    public function show($id)
    {
        // Find the Contact by ID
        $contact = Contact::where('id', $id)->first();

        // Find the Contact by ID
        if (!$contact) {
            // If the Contact is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Contact not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare a success response with the Contact data
        $response = [
            'status' => true,
            'data' => ($contact) ? $contact : '',
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response with the Contact data with status(200)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a new Contact.
     *
     * This method validates the incoming request data and stores a new Contact in the database.
     * If the validation fails, it returns a 403 Forbidden response with validation errors.
     * If the Contact is successfully stored, it returns a success message along with the stored Contact data.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the Contact data
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function store(Request $request)
    {
        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => 'required|exists:users,id',
                'title' => 'required',
                'name' => [
                    'required',
                    'string',
                    Rule::unique('contacts')->where(function ($query) use ($request) {
                        return $query->where('user_id', $request->input('user_id'));
                    }),
                ],
                'did' => [
                    'required',
                    'string',
                    Rule::unique('contacts')->where(function ($query) use ($request) {
                        return $query->where('user_id', $request->input('user_id'));
                    }),
                ],
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a 403 Forbidden response with validation errors
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Create a new Contact with the validated input
        $data = Contact::create($validated);

        // Prepare a success response with the stored Contact data
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully stored'
        ];

        // Return a JSON response with the success message and stored Contact data
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Update an Contact by ID.
     *
     * This method finds and updates an Contact based on the provided ID and request data.
     * If the Contact is not found, it returns a 404 Not Found response.
     * If the validation fails, it returns a 403 Forbidden response with validation errors.
     * If the update is successful, it returns a success message along with the updated Contact data.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the update data
     * @param  int  $id The ID of the Contact to update
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function update(Request $request, $id)
    {
        // Find the Contact by ID
        $contact = Contact::find($id);

        // Check if the Contact exists
        if (!$contact) {
            // If the Contact is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Contact not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request
        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => 'required|exists:users,id',
                'title' => 'required',
                'name' => [
                    'required',
                    'string',
                    Rule::unique('contacts')
                        ->where(function ($query) use ($request) {
                            return $query->where('user_id', $request->input('user_id'));
                        })
                        ->ignore($id), // Ignore the current record's ID
                ],
                'did' => [
                    'required',
                    'string',
                    Rule::unique('contacts')
                        ->where(function ($query) use ($request) {
                            return $query->where('user_id', $request->input('user_id'));
                        })
                        ->ignore($id), // Ignore the current record's ID
                ],
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a 403 Forbidden response with validation errors
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Update the Contact with the validated input
        $contact->update($validated);

        // Prepare a success response with updated Contact data
        $response = [
            'status' => true,
            'data' => $contact,
            'message' => 'Successfully updated Contact',
        ];

        // Return a JSON response with the success message and updated Contact data with status(200)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Delete an Contact by ID.
     *
     * This method finds and deletes an Contact based on the provided ID.
     * If the Contact is not found, it returns a 404 Not Found response.
     * If the Contact is successfully deleted, it returns a success message.
     *
     * @param  int  $id The ID of the Contact to delete
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function destroy($id)
    {
        // Find the Contact by ID
        $contact = Contact::find($id);

        // Check if the Contact exists
        if (!$contact) {
            // If the Contact is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Contact not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the Contact
        $contact->delete();

        // Prepare a success message
        $response = [
            'status' => true,
            'message' => 'Successfully deleted Contact'
        ];

        // Return a JSON response with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
