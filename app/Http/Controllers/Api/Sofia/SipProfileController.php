<?php

namespace App\Http\Controllers\Api\Sofia;

use App\Http\Controllers\Controller;
use App\Models\SipProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SipProfileController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Sip Profile'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Sip_Profile';
    }

    public function index(Request $request)
    {
        // Start building the query to fetch sip profiles
        $profiles = SipProfile::query();

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

    public function show($id)
    {
        // Find the profile with the given ID
        $profile = SipProfile::find($id);

        // Check if the profile exists
        if (!$profile) {
            // If profile is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Profile not found'
            ];
            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare success response with profile details
        $response = [
            'status' => true,
            'data' => ($profile) ? $profile : '', // Include profile details if found
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response containing the domain details
        return response()->json($response, Response::HTTP_OK);
    }

    public function destroy(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the profile with the given ID
        $profile = SipProfile::find($id);

        // Check if the profile exists
        if (!$profile) {
            // If profile is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Profile not found'
            ];

            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Generate UID for the deletion action
        $type = $this->type;
        createUid('destroy', $type, $profile, $userId);

        // Delete the profile from the database
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
