<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Extension;
use App\Models\Followme;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FollowmeController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * This function handles the storage of extension-related data, including follow me details.
     *
     * @param  \Illuminate\Http\Request  $request The HTTP request containing the data to be stored.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating the outcome of the operation.
     */
    public function store(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Extract specific fields from the request data
        $inputData = $request->only([
            'account_id',
            'extension_id',
            'callforward',
            'callforwardTo',
            'onbusy',
            'onbusyTo',
            'noanswer',
            'noanswerTo',
            'notregistered',
            'notregisteredTo',
            'dnd',
            'followme',
            'ignorebusy',
            'callTimeOut',
            'voicemailEnabled',
            'voiceEmailTo',
            'record',
            'blockIncomingStatus',
            'blockOutGoingStatus'
        ]);

        // Create a validator instance to validate extension data
        $extensionValidator = Validator::make(
            $inputData,
            [
                'account_id' => 'required|exists:accounts,id',
                'extension_id' => 'required|exists:extensions,id',
                'callforward' => 'required|boolean',
                'callforwardTo' => 'required_if:callforward,true|string|nullable',
                'onbusy' => 'required|boolean',
                'onbusyTo' => 'required_if:onbusy,true|string|nullable',
                'noanswer' => 'required|boolean',
                'noanswerTo' => 'required_if:noanswer,true|string|nullable',
                'notregistered' => 'required|boolean',
                'notregisteredTo' => 'required_if:notregistered,true|string|nullable',
                'dnd' => 'required|boolean',
                'followme' => 'required|boolean',
                'ignorebusy' => 'required|boolean',

                'voicemailEnabled' => 'in:Y,N',
                'voiceEmailTo' => 'string|nullable',
                'record' => 'in:L,I,O,A,D',
                'blockIncomingStatus' => 'boolean',
                'blockOutGoingStatus' => 'boolean',
                'callTimeOut' => 'numeric|nullable',
            ]
        );

        // Check if validation fails
        if ($extensionValidator->fails()) {
            // If validation fails, return a JSON response with error messages
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $extensionValidator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Set the variable $id based on the presence of 'extension_id' field in the request
        $extension_id = $request->extension_id;
        $account_id = $request->account_id;

        // Find the extension by ID
        $extension = Extension::where(['account_id' => $account_id, 'id' => $extension_id])->first();

        // Check if the extension exists
        if (!$extension) {
            // If the extension is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Data not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Check if the request contains a 'data' field
        if ($request->has('data')) {
            // Begin a database transaction
            DB::beginTransaction();

            // Retrieve data from the request object
            $inputs = $request->data;

            $jsonResponse = $this->createOrUpdate($inputs, $account_id, $extension_id);
            $jsonArray = $jsonResponse->getData(true);

            if (!$jsonArray['status']) {
                return $jsonResponse;
            }

            // Commit the database transaction
            DB::commit();
        }

        // Call the compareValues function to generate a formatted description based on the extension and validated data
        $formattedDescription = compareValues($extension, $extensionValidator->validated());

        // // Defining action and type for creating UID
        $action = 'update';
        $type = 'Extension';

        // Update the extension model with the provided data
        unset($inputData['extension_id']);

        $extension->update($inputData);

        // Log the action
        accessLog($action, $type, $formattedDescription, $userId);

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfull.',
            'data' => $inputData
        ];

        // Return a JSON response with HTTP status code 201 (Created)
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Remove the specified followme resource from storage.
     *
     * This method deletes a followme resource identified by its unique ID.
     * If the specified followme does not exist, it returns a JSON response
     * with a 404 Not Found status code and an error message.
     *
     * If the followme is successfully deleted, it returns a JSON response
     * with a 200 OK status code and a success message.
     *
     * @param  int  $id The ID of the followme resource to delete
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the deletion operation
     */
    public function destroy($id)
    {
        // Find the details by ID
        $followme = Followme::find($id);

        // Check if the details exists
        if (!$followme) {
            // If the details is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Details not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the details
        $followme->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Update the specified extension resource in storage.
     *
     * This method handles the updating of an extension resource identified by its unique ID.
     * It validates the incoming request data, updates the extension data, and commits the changes to the database.
     * If validation fails or the extension is not found, appropriate error responses are returned.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the extension resource to update
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the update operation
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the extension by ID
        $extension = Extension::find($id);

        // Check if the extension exists
        if (!$extension) {
            // If the extension is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Extension not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Extract specific fields from the request data
        $extensionData = $request->only([
            'callforward',
            'callforwardTo',
            'onbusy',
            'onbusyTo',
            'noanswer',
            'noanswerTo',
            'notregistered',
            'notregisteredTo',
            'dnd',
            'followme',
            'ignorebusy',
            'voicemailEnabled',
            'voiceEmailTo',
            'record',
            'blockIncomingStatus',
            'blockOutGoingStatus'
        ]);

        // Create a validator instance to validate extension data
        $extensionValidator = Validator::make(
            $extensionData,
            [
                'callforward' => 'boolean',
                'callforwardTo' => 'required_if:callforward,true|string|nullable',
                'onbusy' => 'boolean',
                'onbusyTo' => 'required_if:onbusy,true|string|nullable',
                'noanswer' => 'boolean',
                'noanswerTo' => 'required_if:noanswer,true|string|nullable',
                'notregistered' => 'boolean',
                'notregisteredTo' => 'required_if:notregistered,true|string|nullable',
                'dnd' => 'boolean',
                'followme' => 'boolean',
                'ignorebusy' => 'boolean',

                'voicemailEnabled' => 'in:Y,N',
                'voiceEmailTo' => 'string|nullable',
                'record' => 'in:L,I,O,A,D',
                'blockIncomingStatus' => 'boolean',
                'blockOutGoingStatus' => 'boolean',
                'callTimeOut' => 'numeric|nullable',
            ]
        );

        // Check if validation fails
        if ($extensionValidator->fails()) {
            // If validation fails, return a JSON response with error messages
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $extensionValidator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Begin transaction
        DB::beginTransaction();

        // Check if the request contains a 'data' field
        if ($request->has('data')) {
            // Retrieve data from the request object
            $inputs = $request->data;

            // Iterate over each input element in the $inputs array
            foreach ($inputs as $input) {
                // Create a new validator instance to validate the request data
                $validator = Validator::make(
                    $input,
                    [
                        'id' => 'required|numeric|exists:followmes,id',
                        'destination' => 'string',
                        'delay' => 'numeric',
                        'timeout' => 'numeric',
                        'prompt' => 'string|nullable',
                        'extension_id' => 'numeric|exists:extensions,id',
                    ]
                );

                // Check if the validation process has failed
                if ($validator->fails()) {
                    // If validation fails, return a JSON response with error messages
                    $response = [
                        'status' => false,
                        'message' => 'validation error',
                        'errors' => $validator->errors()
                    ];

                    return response()->json($response, Response::HTTP_FORBIDDEN);
                }

                $followme = Followme::find($input['id']);

                $followme->update($validator->validated());
            }
        }

        // Call the compareValues function to generate a formatted description based on the extension and validated data
        $formattedDescription = compareValues($extension, $extensionValidator->validated());

        // Defining action and type for creating UID
        $action = 'update';
        $type = 'Extension';

        // Update the extension model with the provided data
        $extension->update($extensionData);

        // Log the action
        accessLog($action, $type, $formattedDescription, $userId);

        // Commit the database transaction
        DB::commit();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully stored'
        ];

        // Return a JSON response with HTTP status code 201 (Created)
        return response()->json($response, Response::HTTP_CREATED);
    }

    // create or update
    public function createOrUpdate($inputs, $account_id, $extension_id)
    {
        foreach ($inputs as $input) {

            if (isset($input['id'])) {
                $validator = Validator::make(
                    $input,
                    [
                        'id' => 'required|exists:followmes,id',
                        'destination' => 'string|required|unique:followmes,destination,' . $input['id'] . ',id,extension_id,' . $extension_id . '',
                        'delay' => 'numeric',
                        'timeout' => 'numeric',
                        'prompt' => 'string|nullable',
                        'extension_id' => 'numeric|exists:extensions,id',
                    ]
                );
            } else {
                // Create a new validator instance to validate the request data
                $validator = Validator::make(
                    $input,
                    [
                        'destination' => 'string|required|unique:followmes,destination,NULL,id,extension_id,' . $input['extension_id'] . '',
                        'delay' => 'numeric',
                        'timeout' => 'numeric',
                        'prompt' => 'string|nullable',
                        'extension_id' => 'numeric|exists:extensions,id',
                    ]
                );
            }

            // Check if the validation process has failed
            if ($validator->fails()) {
                // If validation fails, return a JSON response with error messages
                $response = [
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validator->errors()
                ];

                return response()->json($response, Response::HTTP_FORBIDDEN);
            }

            if (isset($input['id'])) {
                $followmeData = Followme::find($input['id']);

                if ($followmeData->extension_id == $extension_id) {
                    // create a new Followme model instance with validated data
                    $followmeData->update($validator->validated());

                    $response = [
                        'status' => true,
                        'message' => 'success',
                    ];

                    return response()->json($response, Response::HTTP_OK);
                } else {

                    $response = [
                        'status' => false,
                        'message' => 'validation error',
                        'errors' => 'Extension id not matched'
                    ];

                    return response()->json($response, Response::HTTP_FORBIDDEN);
                }
            } else {
                $validatedData = $validator->validated();

                $validatedData['account_id'] = $account_id;

                // create a new Followme model instance with validated data
                Followme::create($validatedData);

                $response = [
                    'status' => true,
                    'message' => 'success',
                ];

                return response()->json($response, Response::HTTP_OK);
            }
        }
    }
}
