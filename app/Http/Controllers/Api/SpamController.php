<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Spam;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SpamController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Spam'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Spam';
    }

    /**
     * Retrieve and return all spam records associated with the authenticated user's account.
     *
     * This method queries the Spam model to fetch all spam entries. It filters the results 
     * based on the account ID of the requesting user. The retrieved data is then structured 
     * into a JSON response, including a status indicator and a message.
     *
     * @param Request $request The HTTP request instance containing user and account information.
     * 
     * @return \Illuminate\Http\JsonResponse A JSON response with the list of spam entries, 
     * a success status, and a message.
     */
    public function index(Request $request)
    {
        // Retrieve all spams from the database
        $spams = Spam::query();

        $account_id = $request->user()->account_id;

        if ($account_id) {
            $spams->where('account_id', $account_id);
        }

        // Execute the query to fetch domains
        $spams = $spams->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $spams,
            'message' => 'Successfully fetched all spams'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a new spam record in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new spam record in the database, and returning a
     * JSON response indicating success or failure.
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

        // Check if the request has a file and validate it
        if ($request->hasFile('csv_file')) {
            // Validate the request data
            $validator = Validator::make(
                $request->all(),
                [
                    'csv_file' => 'required|mimes:csv,txt|max:2048',
                ]
            );
        } else {
            // Validate incoming request data
            $validator = Validator::make(
                $request->all(),
                [
                    'account_id' => 'required|exists:accounts,id',
                    'type' => 'required|string',
                    'number' => [
                        'required',
                        Rule::unique('spam')->where(function ($query) use ($account_id) {
                            return $query->where('account_id', $account_id);
                        }),
                    ],
                ]
            );
        }

        if ($request->hasFile('csv_file')) {
            $csvResponse = $this->uploadFromCsv($request);
            return $csvResponse;
        }

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

        // Defining action and type for creating UID
        $action = 'create';
        $type = $this->type;

        // Log the action
        accessLog($action, $type, $validated, $userId);

        // Create a new spam record with validated data
        $data = Spam::create($validated);

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
     * Remove the specified spam record from storage.
     *
     * This method retrieves the spam record with the given ID, checks if it exists,
     * and deletes the spam record from the database. It verifies that the
     * authenticated user has permission to delete the record, and returns a JSON
     * response indicating success or failure.
     *
     * @param int $id The ID of the spam record to delete
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function destroy($id)
    {
        // Find the spam with the given ID
        $spam = Spam::find($id);

        $userId = auth()->user()->id;
        $account_id = auth()->user()->account_id;

        // Check if the spam exists
        if (!$spam) {
            // If the group is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Spam not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Check if the authenticated user has permission to update the group
        if ($account_id !== $spam->account_id) {
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
        accessLog($action, $type, $spam, $userId);

        // Delete the group record
        $spam->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted spam number. '
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Uploads spam data from a CSV file and stores it in the database.
     *
     * This method handles the upload of a CSV file containing spam data, reads its contents,
     * and processes each row to create or update spam records in the database. It skips the header
     * row and any rows that do not contain essential data. The phone numbers are validated before
     * being stored. A JSON response is returned indicating the success or failure of the operation.
     *
     * @param \Illuminate\Http\Request $request The HTTP request instance containing the CSV file and account ID.
     * @return \Illuminate\Http\JsonResponse A JSON response with the status of the operation, the data processed,
     * and a success message.
     */
    public function uploadFromCsv(Request $request)
    {
        $file = $request->file('csv_file');
        $account_id = $request->account_id;

        // Open the file
        if (($handle = fopen($file->getRealPath(), 'r')) !== FALSE) {
            // Skip the first line (header)
            fgetcsv($handle);

            // Loop through the CSV rows
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {

                // Skip empty rows or rows where essential data is missing
                if (empty($data[1]) && empty($data[2])) {
                    continue;
                }
                // Map CSV data to the correct fields
                $csvData = [
                    'account_id' => $account_id,
                    'phone_number' => validate_local_mobile_number($data[1]) ? $data[1] : null,
                    'type' => $data[2] ?? null
                ];

                // Save data into the database
                Spam::updateOrCreate([
                    'account_id' => $account_id,
                    'phone_number' => $csvData['phone_number'],
                ], $csvData);
            }

            fclose($handle);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully stored'
        ];

        // Return a JSON response with HTTP status code 201 (Created)
        return response()->json($response, Response::HTTP_CREATED);
    }
}
