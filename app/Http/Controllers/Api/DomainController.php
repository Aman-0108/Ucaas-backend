<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DomainController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Domain'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Domain';
    }

    /**
     * Retrieves a list of domains.
     *
     * This method retrieves a list of domains based on optional query parameters.
     * If a specific account ID is provided in the request, it filters domains by that account.
     * It then returns a JSON response containing the list of domains.
     *
     * @param Request $request The HTTP request object.
     * @return \Illuminate\Http\JsonResponse A JSON response containing the list of domains.
     */
    public function index(Request $request)
    {
        // Start building the query to fetch domains
        $domains = Domain::query();

        // Check if the request contains an 'account' parameter
        if ($request->has('account')) {
            // If 'account' parameter is provided, filter domains by account ID
            $domains->where('account_id', $request->account);
        }

        // COMING FROM GLOBAL CONFIG
        $ROW_PER_PAGE = config('globals.PAGINATION.ROW_PER_PAGE');

        // Execute the query to fetch domains
        $domains = $domains->orderBy('id', 'asc')->paginate($ROW_PER_PAGE);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $domains,
            'message' => 'Successfully fetched all domains'
        ];

        // Return a JSON response containing the list of domains
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Retrieves details of a specific domain.
     *
     * This method retrieves details of a domain with the given ID.
     * If the domain is found, it returns a JSON response containing
     * the domain details. If the domain is not found, it returns
     * a JSON response with an error message and a 404 status code.
     *
     * @param int $id The ID of the domain to retrieve.
     * @return \Illuminate\Http\JsonResponse A JSON response containing the domain details or an error message.
     */
    public function show($id)
    {
        // Find the domain with the given ID
        $domain = Domain::find($id);

        // Check if the domain exists
        if (!$domain) {
            // If domain is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Domain not found'
            ];
            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare success response with domain details
        $response = [
            'status' => true,
            'data' => ($domain) ? $domain : '', // Include domain details if found
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response containing the domain details
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Stores a new domain.
     *
     * This method attempts to store a new domain based on the provided data.
     * It validates the request data and checks for validation errors. If validation
     * fails, it returns a JSON response with validation errors. If validation succeeds,
     * it creates a new domain record in the database and returns a JSON response
     * indicating successful storage.
     *
     * @param Request $request The HTTP request object containing domain data.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the storage attempt.
     */
    public function store(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Validate the request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'domain_name' => 'required|unique:domains,domain_name',
                'created_by' => 'required|exists:users,id',
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

        //path or file created { store in DB }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Begin a database transaction
        DB::beginTransaction();

        // Defining action and type for creating UID
        $action = 'create';
        $type = $this->type;

        // Generate UID and attach it to the validated data
        $validated['uid_no'] = createUid($action, $type, $validated, $userId);

        // Create a new domain record in the database
        $data = Domain::create($validated);

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
     * Updates an existing domain.
     *
     * This method attempts to update an existing domain based on the provided data.
     * It first checks if the domain exists and if the authenticated user has permission
     * to edit it. If the domain doesn't exist or the user doesn't have permission,
     * it returns an appropriate error response. If validation fails, it returns
     * a JSON response with validation errors. If validation succeeds and the domain
     * is successfully updated, it returns a JSON response indicating success.
     *
     * @param Request $request The HTTP request object containing domain data.
     * @param int $id The ID of the domain to update.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the update attempt.
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the domain with the given ID
        $domain = Domain::find($id);

        // Check if the domain exists
        if (!$domain) {
            // If domain is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Domain not found'
            ];

            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Check if the authenticated user has permission to edit the domain
        if ($domain->created_by !== $userId) {
            // If user doesn't have permission, prepare error response
            $response = [
                'status' => false,
                'error' => 'You dont have access to edit.'
            ];

            // Return a JSON response with error message and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Validate the request data
        $validator = Validator::make(
            $request->all(),
            [
                'domain_name' => 'unique:domains,domain_name,' . $id,
                'created_by' => 'exists:users,id',
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

        // Call the compareValues function to generate a formatted description based on the domain and validated data
        $formattedDescription = compareValues($domain, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Generate UID and attach it to the validated data
        $validated['uid_no'] = createUid($action, $type, $formattedDescription, $userId);

        // Update the domain with the validated data
        $domain->update($validated);

        // Prepare success response
        $response = [
            'status' => true,
            'data' => $domain,
            'message' => 'Successfully updated Domain',
        ];

        // Return a JSON response indicating successful update
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Deletes a domain.
     *
     * This method attempts to delete a domain with the provided ID.
     * It first checks if the domain exists. If the domain doesn't exist,
     * it returns an appropriate error response. If the domain exists, it
     * generates a UID for the deletion action, deletes the domain from
     * the database, and returns a JSON response indicating successful deletion.
     *
     * @param Request $request The HTTP request object.
     * @param int $id The ID of the domain to delete.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the deletion attempt.
     */
    public function destroy(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the domain with the given ID
        $domain = Domain::find($id);

        // Check if the domain exists
        if (!$domain) {
            // If domain is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Domain not found'
            ];

            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Generate UID for the deletion action
        createUid('destroy', 'Domain', $domain, $userId);

        // Delete the domain from the database
        $domain->delete();

        // Prepare success response
        $response = [
            'status' => true,
            'message' => 'Successfully deleted domain'
        ];

        // Return a JSON response indicating successful deletion and 200 status code
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Search for domains by domain name.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->get('query');

        // Perform search query using Eloquent ORM
        $domains = Domain::where('domain_name', 'like', "%$query%");
        
        if($request->get('account')) {
            $domains->where('account_id', $request->get('account'));
        }

        $domains= $domains->get();

        // Prepare success response with search results
        $response = [
            'status' => true,
            'data' => $domains,
            'message' => 'Successfully fetched',
        ];

        // Return a JSON response with domain data and success message
        return response()->json($response, Response::HTTP_OK);
    }
}
