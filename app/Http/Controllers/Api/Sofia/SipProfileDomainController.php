<?php

namespace App\Http\Controllers\Api\Sofia;

use App\Http\Controllers\Controller;
use App\Models\SipProfileDomain;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\DB;

class SipProfileDomainController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Sip Profile Domain'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Sip_Profile_Domain';
    }

    public function index(Request $request)
    {
        // Start building the query to fetch sip profiles
        $profiles = SipProfileDomain::query();

        // Check if the request contains an 'account' parameter
        if ($request->has('account')) {
            // If 'account' parameter is provided, filter sip profile by account ID
            $profiles->where('account_id', $request->account);
        }

        // Execute the query to fetch sip profiles
        $profiles = $profiles->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $profiles,
            'message' => 'Successfully fetched.'
        ];

        // Return a JSON response containing the list of sip profiles
        return response()->json($response, Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Validate the request data
        $validator = Validator::make(
            $request->all(),
            [
                'sip_profile_id' => 'required|numeric|exists:sip_profiles,id',
                'name' => 'required|unique:sip_profile_domains,name',
                'alias' => 'required|string',
                'parse' => 'required|string',
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

        // Retrieve the validated input
        $validated = $validator->validated();

        // Begin a database transaction
        DB::beginTransaction();

        // Defining action and type for creating UID
        $action = 'create';
        $type = $this->type;

        // Generate UID and attach it to the validated data
        $validated['uid_no'] = createUid($action, $type, $validated, $userId);

        // Create a new Sip profile domain record in the database
        $data = SipProfileDomain::create($validated);

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

    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the domain with the given ID
        $domain = SipProfileDomain::find($id);

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
                'sip_profile_id' => 'numeric|exists:sip_profiles,id',
                'name' => 'unique:sip_profile_domains,name,' . $id,
                'alias' => 'string',
                'parse' => 'string',
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

    public function show($id)
    {
        // Find the profile domain with the given ID
        $profile = SipProfileDomain::find($id);

        // Check if the profile exists
        if (!$profile) {
            // If profile is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Profile domain not found'
            ];
            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare success response with profile details
        $response = [
            'status' => true,
            'data' => ($profile) ? $profile : '', // Include profile domain details if found
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response containing the domain details
        return response()->json($response, Response::HTTP_OK);
    }

    public function destroy(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the profile domain with the given ID
        $profile = SipProfileDomain::find($id);

        // Check if the profile exists
        if (!$profile) {
            // If profile is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Profile domain not found'
            ];

            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Generate UID for the deletion action
        $type = $this->type;
        createUid('destroy', $type, $profile, $userId);

        // Delete the profile domain from the database
        $profile->delete();

        // Prepare success response
        $response = [
            'status' => true,
            'message' => 'Successfully deleted.'
        ];

        // Return a JSON response indicating successful deletion and 200 status code
        return response()->json($response, Response::HTTP_OK);
    }
}
