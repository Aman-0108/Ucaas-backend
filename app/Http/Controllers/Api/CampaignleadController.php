<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaignlead;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class CampaignleadController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Campaignlead'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Campaignlead';
    }

    /**
     * Retrieves a list of campaign leads.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the list of campaign leads.
     */
    public function index()
    {
        // Retrieve the authenticated user's ID
        $account_id = request()->user()->account_id;

        $query = Campaignlead::query();

        if ($account_id) {
            $query->where('account_id', $account_id);
        }

        // COMING FROM GLOBAL CONFIG
        $ROW_PER_PAGE = config('globals.PAGINATION.ROW_PER_PAGE');

        // Execute the query to fetch cdrs
        $clead = $query->orderBy('id', 'desc')->paginate($ROW_PER_PAGE);

        // Prepare a success response with the list of fax
        $response = [
            'status' => true,
            'data' => $clead,
            'message' => 'Successfully fetched all lead'
        ];

        // Return a JSON response with the list of fax
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created Campaignlead resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new Campaignlead record in the database, and returning a
     * JSON response indicating success or failure.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // $account_id = $request->user()->account_id;
        $account_id = 1;

        // Validate the request data
        $validator = Validator::make(
            $request->all(),
            [
                'csv_file' => 'required|mimes:csv,txt|max:2048',
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

        $file = $request->file('csv_file');

        // Open the file
        if (($handle = fopen($file->getRealPath(), 'r')) !== FALSE) {
            // Skip the first line (header)
            fgetcsv($handle);

            // Loop through the CSV rows
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {

                // Skip empty rows or rows where essential data is missing
                if (empty($data[1]) && empty($data[3]) && empty($data[5])) {
                    continue;
                }
                // Map CSV data to the correct fields
                $leadData = [
                    'account_id' => $account_id,
                    'phone_code' => validate_country_code($data[0]) ? $data[0] : null,
                    'phone_number' => validate_local_mobile_number($data[1]) ? $data[1] : null,
                    'title' => $data[2] ?? null,
                    'first_name' => $data[3] ?? null,
                    'middle_initial' => $data[4] ?? null,
                    'last_name' => $data[5] ?? null,
                    'address1' => $data[6] ?? null,
                    'address2' => $data[7] ?? null,
                    'address3' => $data[8] ?? null,
                    'city' => $data[9] ?? null,
                    'state' => $data[10] ?? null,
                    'province' => $data[11] ?? null,
                    'postal_code' => $data[12] ?? null,
                    'country_code' => $data[13] ?? null,
                    'gender' => in_array(strtoupper($data[14]), ['M', 'F', 'MALE', 'FEMALE']) ? strtoupper($data[14]) : null,
                    'date_of_birth' => parseDateOfBirth($data[15]),
                    'alt_phone' => $data[16] ?? null,
                    'email' => filter_var($data[17] ?? null, FILTER_VALIDATE_EMAIL) ?: null,
                    'security_phrase' => $data[18] ?? null,
                    'comments' => $data[19] ?? null,
                    'rank' => empty($data[20]) ? null : (int) $data[20],
                ];

                // Save data into the database
                Campaignlead::updateOrCreate([
                    'account_id' => $account_id,
                    'phone_number' => $leadData['phone_number'],
                ], $leadData);
            }

            fclose($handle);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully uploaded documents'
        ];

        // Return a JSON response containing the list of documents
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Update the specified campaign lead in storage.
     *
     * This method validates the incoming request data and updates the campaign lead in the database.
     * If the validation fails, it returns a JSON response with validation errors and 403 status code.
     * If the campaign lead is successfully updated, it returns a JSON response with the updated campaign lead and 200 status code.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the campaign lead data
     * @param  int  $id The ID of the campaign lead to update
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function update(Request $request, $id)
    {
        $account_id = auth()->user()->account_id;

        $request->merge(['account_id' => $account_id]);

        // Find the CallRatesPlan with the given ID
        $campaignLead = Campaignlead::find($id);

        // Validate the request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'phone_code' => 'numeric|nullable',
                'phone_number' =>  ['nullable', function ($attribute, $value, $fail) {
                    if ($value && !validate_local_mobile_number($value)) {
                        // Custom error message if validation fails
                        $fail('The phone number format is invalid.');
                    }
                }],
                'title' => 'string|nullable',
                'first_name' => 'string',
                'middle_initial' => 'string',
                'last_name' => 'string',
                'address1' => 'string|nullable',
                'address2' => 'string|nullable',
                'address3' => 'string|nullable',
                'city' => 'string|nullable',
                'state' => 'string|nullable',
                'province' => 'string|nullable',
                'postal_code' => 'string|nullable',
                'country_code' => ['nullable', function ($attribute, $value, $fail) {
                    if ($value && !validate_country_code($value)) {
                        // Custom error message if validation fails
                        $fail('The country code format is invalid.');
                    }
                }],
                'gender' => 'in:M,F,MALE,FEMALE',
                'date_of_birth' => 'date|nullable',
                'alt_phone' => 'string|nullable',
                'email' => 'email|nullable',
                'security_phrase' => 'string|nullable',
                'comments' => 'string|nullable',
                'rank' => 'integer|nullable',
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

        // Retrieve the validated input...
        $validated = $validator->validated();

        // Update the CallRatesPlan with the validated data
        $campaignLead->update($validated);

        $response = [
            'status' => true,
            'data' => $campaignLead,
            'message' => 'Successfully updated lead'
        ];

        // Return a JSON response with the updated CallRatesPlan and 200 status code
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Delete an Campaignlead by ID.
     *
     * This method finds and deletes an Campaignlead based on the provided ID.
     * If the Campaignlead is not found, it returns a 404 Not Found response.
     * If the Campaignlead is successfully deleted, it returns a success message.
     *
     * @param  int  $id The ID of the Campaignlead to delete
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function destroy($id)
    {
        // try {            
        //     DB::beginTransaction();
        //     Campaignlead::where('id', $id)->delete();
        //     DB::commit();
        //     return responseHelper(config('enums.RESPONSE.SUCCESS'), true, 'Successfully deleted', Response::HTTP_OK);
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     Log::error($e->getMessage());
        //     return responseHelper(config('enums.RESPONSE.ERROR'), false, $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        // }
        $account_id = auth()->user()->account_id;

        $clead = Campaignlead::find($id);

        if (!$clead) {
            $response = [
                'status' => false,
                'error' => 'clead not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Check if the authenticated user has permission to edit the audio
        if ($clead->account_id !== $account_id) {
            // If user doesn't have permission, prepare error response
            $response = [
                'status' => false,
                'error' => 'You dont have access to delete.',
            ];

            // Return a JSON response with error message and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Delete the lead
        $clead->delete();

        // Prepare a success message
        $response = [
            'status' => true,
            'message' => 'Successfully deleted lead'
        ];

        // Return a JSON response with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
