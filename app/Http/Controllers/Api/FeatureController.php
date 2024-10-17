<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FeatureController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Feature'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Feature';
    }

    /**
     * Retrieves a list of features.
     *
     * This method retrieves a list of features based on optional query parameters.
     * If a specific account ID is provided in the request, it filters features by that account.
     * It then returns a JSON response containing the list of features.
     *
     * @param Request $request The HTTP request object.
     * @return \Illuminate\Http\JsonResponse A JSON response containing the list of features.
     */
    public function index(Request $request)
    {
        // Start building the query to fetch features
        $features = Feature::query();

        // Check if the request contains an 'account' parameter
        if ($request->has('account')) {
            // If 'account' parameter is provided, filter features by account ID
            $features->where('account_id', $request->account);
        }

        // COMING FROM GLOBAL CONFIG
        $ROW_PER_PAGE = config('globals.PAGINATION.ROW_PER_PAGE');

        // Execute the query to fetch features
        $features = $features->orderBy('id', 'asc')->paginate($ROW_PER_PAGE);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $features,
            'message' => 'Successfully fetched all features'
        ];

        // Return a JSON response containing the list of features
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Retrieves details of a specific Feature.
     *
     * This method retrieves details of a Feature with the given ID.
     * If the Feature is found, it returns a JSON response containing
     * the Feature details. If the Feature is not found, it returns
     * a JSON response with an error message and a 404 status code.
     *
     * @param int $id The ID of the Feature to retrieve.
     * @return \Illuminate\Http\JsonResponse A JSON response containing the Feature details or an error message.
     */
    public function show($id)
    {
        // Find the Feature with the given ID
        $feature = Feature::find($id);

        // Check if the Feature exists
        if (!$feature) {
            // If Feature is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Feature not found'
            ];
            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare success response with Feature details
        $response = [
            'status' => true,
            'data' => ($feature) ? $feature : '', // Include Feature details if found
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response containing the Feature details
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Stores a new Feature.
     *
     * This method attempts to store a new Feature based on the provided data.
     * It validates the request data and checks for validation errors. If validation
     * fails, it returns a JSON response with validation errors. If validation succeeds,
     * it creates a new Feature record in the database and returns a JSON response
     * indicating successful storage.
     *
     * @param Request $request The HTTP request object containing Feature data.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the storage attempt.
     */
    public function store(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        if ($request->has('name') && $request->has('package_id')) {
            // Check if a soft-deleted record with the same attributes exists
            $existingRecord = Feature::withTrashed()
                ->where(['name' => $request->name, 'package_id' => $request->package_id]) // Replace 'attribute' with your actual attribute name
                ->first();

            if ($existingRecord) {
                // Restore the soft-deleted record (optional)
                $existingRecord->restore();

                // Optionally, you may choose to update the attributes of the restored record
                $existingRecord->update($request->only('name'));

                $response = [
                    'status' => true,
                    'data' => $existingRecord,
                    'message' => 'Successfully restored'
                ];

                // Return a JSON response indicating successful storage and 201 status code
                return response()->json($response, Response::HTTP_CREATED);
            }
        }

        // Validate the request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|unique:features,name,NULL,id,package_id,' . $request->package_id,
                'package_id' => 'required|exists:packages,id',
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

        // Retrieve the validated input
        $validated = $validator->validated();

        // Begin a database transaction
        DB::beginTransaction();

        // Defining action and type for creating UID
        $action = 'create';
        $type = $this->type;

        // Create a new Feature record in the database
        $data = Feature::create($validated);

        // Log the action
        accessLog($action, $type, $validated, $userId);

        // Commit the database transaction
        DB::commit();

        // Prepare success response
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully stored'
        ];

        // Return a JSON response indicating successful storage and 201 status code
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Updates an existing Feature.
     *
     * This method attempts to update an existing Feature based on the provided data.
     * It first checks if the Feature exists and if the authenticated user has permission
     * to edit it. If the Feature doesn't exist or the user doesn't have permission,
     * it returns an appropriate error response. If validation fails, it returns
     * a JSON response with validation errors. If validation succeeds and the Feature
     * is successfully updated, it returns a JSON response indicating success.
     *
     * @param Request $request The HTTP request object containing Feature data.
     * @param int $id The ID of the Feature to update.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the update attempt.
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the Feature with the given ID
        $feature = Feature::find($id);

        // Check if the Feature exists
        if (!$feature) {
            // If Feature is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Feature not found'
            ];

            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Check if the authenticated user has permission to edit the Feature
        if ($feature->package_id !== intval($request->package_id)) {
            // If user doesn't have permission, prepare error response
            $response = [
                'status' => false,
                'error' => 'Wrong package data.',
            ];

            // Return a JSON response with error message and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }


        // Check if the authenticated user has permission to edit the Feature
        // if ($feature->created_by !== $userId) {
        //     // If user doesn't have permission, prepare error response
        //     $response = [
        //         'status' => false,
        //         'error' => 'You dont have access to edit.'
        //     ];

        //     // Return a JSON response with error message and 403 status code
        //     return response()->json($response, Response::HTTP_FORBIDDEN);
        // }

        // Validate the request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'string|unique:features,name,' . $id . ',id,package_id,' . $request->package_id . '',
                'package_id' => 'exists:packages,id',
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

        // Call the compareValues function to generate a formatted description based on the Feature and validated data
        $formattedDescription = compareValues($feature, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Update the Feature with the validated data
        $feature->update($validated);

        // Generate UID and attach it to the validated data
        accessLog($action, $type, $formattedDescription, $userId);

        // Prepare success response
        $response = [
            'status' => true,
            'data' => $feature,
            'message' => 'Successfully updated Feature',
        ];

        // Return a JSON response indicating successful update
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Deletes a Feature.
     *
     * This method attempts to delete a Feature with the provided ID.
     * It first checks if the Feature exists. If the Feature doesn't exist,
     * it returns an appropriate error response. If the Feature exists, it
     * generates a UID for the deletion action, deletes the Feature from
     * the database, and returns a JSON response indicating successful deletion.
     *
     * @param Request $request The HTTP request object.
     * @param int $id The ID of the Feature to delete.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the deletion attempt.
     */
    public function destroy(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the Feature with the given ID
        $feature = Feature::find($id);

        // Check if the Feature exists
        if (!$feature) {
            // If Feature is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Feature not found'
            ];

            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Generate UID for the deletion action
        createUid('destroy', 'Feature', $feature, $userId);

        // Delete the Feature from the database
        $feature->delete();

        // Prepare success response
        $response = [
            'status' => true,
            'message' => 'Successfully deleted Feature'
        ];

        // Return a JSON response indicating successful deletion and 200 status code
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Search for features by Feature name.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->get('query');

        // Perform search query using Eloquent ORM
        $features = Feature::where('name', 'like', "%$query%");

        $features = $features->get();

        // Prepare success response with search results
        $response = [
            'status' => true,
            'data' => $features,
            'message' => 'Successfully fetched',
        ];

        // Return a JSON response with Feature data and success message
        return response()->json($response, Response::HTTP_OK);
    }
}
