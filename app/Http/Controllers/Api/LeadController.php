<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LeadController extends Controller
{    
    /**
     * Retrieves a list of leads.
     *
     * It then returns a JSON response containing the list of leads.
     *    
     * @return \Illuminate\Http\JsonResponse A JSON response containing the list of lead.
     */
    public function index()
    {
        // Start building the query to fetch leads
        $leads = Lead::query();

        // COMING FROM GLOBAL CONFIG
        $ROW_PER_PAGE = config('globals.PAGINATION.ROW_PER_PAGE');

        // Execute the query to fetch leads
        $leads = $leads->orderBy('id', 'desc')->paginate($ROW_PER_PAGE);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $leads,
            'message' => 'Successfully fetched.'
        ];

        // Return a JSON response containing the list of leads
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a new Lead.
     *
     * This method validates the incoming request data and stores a new lead in the database.
     * If the validation fails, it returns a 403 Forbidden response with validation errors.
     * If the lead is successfully stored, it returns a success message along with the stored lead data.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the lead data
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function store(Request $request)
    {
        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'company_name' => 'required|string|unique:accounts,company_name',
                'admin_name' => 'required|string',
                'timezone_id' => 'required|exists:timezones,id',
                'email' => 'required|email|unique:accounts,email',
                'contact_no' => 'required|string',
                'alternate_contact_no' => 'string|nullable',
                'building' => 'string|nullable',
                'unit' => 'required|string',
                'street' => 'required|string',
                'city' => 'required|string',
                'state' => 'required|string',
                'zip' => 'required|string',
                'country' => 'required|string',
                'package_id' => 'required|numeric|exists:packages,id',
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

        // Additional layer of security to check 
        if (!is_valid_email($request->email)) {
            // Prepare a success response with the stored account data
            $response = [
                'status' => false,
                'message' => 'Mail exchange is not available'
            ];

            // Return a JSON response with the success message and stored account data
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Begin transaction
        DB::beginTransaction();

        // Create a new lead with the validated input
        $lead = Lead::create($validated);

        // commit
        DB::commit();

        // Prepare a success response with the stored lead data
        $response = [
            'status' => true,
            'data' => $lead,
            'message' => 'Successfully stored'
        ];

        // Return a JSON response with the success message and stored account data
        return response()->json($response, Response::HTTP_CREATED);
    }
}
