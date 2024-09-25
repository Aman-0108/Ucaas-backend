<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MailSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class MailsettingsController extends Controller
{
    /**
     * Retrieve all mail settings.
     *
     * This method retrieves all mail settings from the database.
     * It returns a JSON response containing the list of mail settings.
     *
     * @param \Illuminate\Http\Request $request The request object containing the user ID
     * @return \Illuminate\Http\JsonResponse The JSON response containing the list of mail settings
     */
    public function index(Request $request)
    {
        // Retrieve the authenticated account's ID
        $account_id = $request->user()->account_id;

        // Retrieve all mail settings from the database
        $query = MailSetting::query();

        $data = $query->where('user_id', $account_id)->get();

        // Prepare a success response with the list of mail settings
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully fetched all mail setting'
        ];

        // Return a JSON response with the list of mail settings with status(200)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Retrieve a mail setting by ID.
     *
     * This method finds and retrieves a mail setting based on the provided ID.
     * If the mail setting is not found, it returns a 404 Not Found response.
     * If the mail setting is found, it returns a JSON response containing the mail setting data.
     *
     * @param int $id The ID of the mail setting to retrieve
     * @return \Illuminate\Http\JsonResponse The JSON response containing the mail setting data or an error message
     */
    public function show($id)
    {
        // Find the settings by ID
        $mailSetting = MailSetting::where('id', $id)->first();

        // Check if the mail setting exists
        if (!$mailSetting) {
            // If the mail setting is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Mail setting not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare a success response with the settings data
        $response = [
            'status' => true,
            'data' => ($mailSetting) ? $mailSetting : '',
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response with the mail setting data with status(200)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a new mail setting.
     *
     * This method validates the incoming request data and stores a new mail setting in the database.
     * If the validation fails, it returns a 403 Forbidden response with validation errors.
     * If the mail setting is successfully stored, it returns a JSON response containing the stored mail setting data.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the mail setting data
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function store(Request $request)
    {
        $account_id = $request->user()->account_id;

        $request->merge(['account_id' => $account_id]);

        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'exists:accounts,id',
                'mail_driver' => 'string|nullable',
                'mail_host' => 'required|string',
                'mail_port' => 'required|nullable',
                'mail_username' => 'string|nullable',
                'mail_password' => 'string|nullable',
                'mail_encryption' => 'string|nullable',
                'mail_from_address' => 'required',
                'mail_from_name' => 'required'
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

        $match = [
            'account_id' => $account_id
        ];

        // Store the mail setting in the database
        $data = MailSetting::updateOrCreate($match, $validated);

        // Prepare a success response with the stored mail setting data
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully stored'
        ];

        // Return a JSON response with the success message and stored mail setting data
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Update an existing mail setting by ID.
     *
     * This method finds and updates a mail setting based on the provided ID and request data.
     * If the mail setting is not found, it returns a 404 Not Found response.
     * If the validation fails, it returns a 403 Forbidden response with validation errors.
     * If the update is successful, it returns a success message along with the updated mail setting data.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the update data
     * @param  int  $id The ID of the mail setting to update
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function update(Request $request, $id)
    {
        // Find the mailSetting by ID
        $mailSetting = MailSetting::find($id);

        // Check if the mailSetting exists
        if (!$mailSetting) {
            // If the Mail Setting is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Mail Setting not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request
        $validator = Validator::make(
            $request->all(),
            [
                'mail_driver' => 'string|nullable',
                'mail_host' => 'string|nullable',
                'mail_port' => 'integer|nullable',
                'mail_username' => 'string|nullable',
                'mail_password' => 'string|nullable',
                'mail_encryption' => 'string|nullable',
                'mail_from_address' => 'string|nullable',
                'mail_from_name' => 'string|nullable'
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

        // Update the mailSetting with the validated input
        $mailSetting->update($validated);

        // Prepare a success response with updated mailSetting data
        $response = [
            'status' => true,
            'data' => $mailSetting,
            'message' => 'Successfully updated',
        ];

        // Return a JSON response with the success message and updated mailSetting data with status(200)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Delete a mail setting by ID.
     *
     * This method finds and deletes a mail setting based on the provided ID.
     * If the mail setting is not found, it returns a 404 Not Found response.
     * If the mail setting is successfully deleted, it returns a success message.
     *
     * @param int $id The ID of the mail setting to delete
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function destroy($id)
    {
        // Find the mail setting by ID
        $mailSetting = MailSetting::find($id);

        // Check if the mail setting exists
        if (!$mailSetting) {
            $response = [
                'status' => false,
                'error' => 'Mail setting not found'
            ];
            // If the mail setting is not found, return a 404 Not Found response
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the mail setting
        $mailSetting->delete();

        // Prepare a success message
        $response = [
            'status' => true,
            'message' => 'Successfully deleted mail setting'
        ];

        // Return a JSON response with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
