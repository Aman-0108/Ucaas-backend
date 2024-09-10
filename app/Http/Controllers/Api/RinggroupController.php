<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Ringgroup;
use App\Models\Ring_group_destination;
use Illuminate\support\facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RinggroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Dialplan'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'RingGroup';
    }


    public function index(Request $request)
    {
        $ringgroups = Ringgroup::with(['ring_group_destination']); //relations tbl small character 1st letter

        if ($request->has('account')) {
            $ringgroups->where('account_id', $request->account);
        }

        $ringgroups = $ringgroups->get();

        $response = [
            'status' => true,
            'data' => $ringgroups,
            'message' => 'Successfully fetched all Ring Groups'
        ];

        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        $validator = Validator::make(
            $request->all(),
            [
                // Validation rules for each field
                'account_id' => 'required:exists:accounts,id',
                'name' => [
                    'required',
                    'string',
                    Rule::unique('ringgroups')->where(function ($query) use ($request) {
                        return $query->where('account_id', $request->input('account_id'));
                    }),
                ],
                'strategy' => 'in:enterprise,sequence,simultaneously,random,rollover,',
                'timeout_destination' => 'string|nullable',
                'call_timeout' => 'required|string',
                'ring_group_caller_id_name' => 'string|nullable',
                'ring_group_caller_id_number' => 'string|nullable',
                'ring_group_cid_name_prefix' => 'string|nullable',
                'ring_group_cid_number_prefix' => 'string|nullable',
                'ring_group_timeout_app' => 'string|nullable',
                'ring_group_timeout_data' => 'string|nullable',
                'distinctive_ring' => 'string|nullable',
                'ring_back' => 'string|nullable',
                'followme' => 'boolean|nullable',
                'missed_call' => 'string|nullable',
                'missed_destination' => 'string|nullable',
                'ring_group_forward' => 'string|nullable',
                'ring_group_forward_destination' => 'string|nullable',
                'toll_allow' => 'string|nullable',
                'context' => 'string|nullable',
                'greeting' => 'string|nullable',
                'status' => 'in:active,inactive',
                'description' => 'string|nullable',
                'recording_enabled' => 'boolean|nullable',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a JSON response with error messages
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        $domain = Domain::where('account_id', $request->account_id)->first();

        if (empty($domain)) {
            $response = [
                'status' => false,
                'message' => 'No domains found on this account'
            ];

            // Return a JSON response with HTTP status code 204 (No Content)
            return response()->json($response, Response::HTTP_NO_CONTENT);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Defining action and type for creating UID
        $action = 'create';
        $type = $this->type;

        createUid($action, $type, $validated, $userId);

        $startingPoint = config('globals.RINGGROUP_START_FROM');

        $maxExtension = Ringgroup::where('account_id', $request->account_id)->max('extension');

        // Ensure the maxExtension is at least the starting point
        $maxExtension = $maxExtension !== null ? max($maxExtension, $startingPoint) : $startingPoint;

        // Fetch all existing extensions
        $existingExtensions = Ringgroup::where('account_id', $request->account_id)
            ->pluck('extension')
            ->toArray();

        // Generate a list of potential extensions
        $potentialExtensions = range($startingPoint, $maxExtension + 1); // Include +1 to cover the edge case

        // Find the missing extensions
        $availableExtensions = array_diff($potentialExtensions, $existingExtensions);

        // Get the smallest available extension number
        $newExtension = !empty($availableExtensions) ? min($availableExtensions) : $maxExtension + 1;

        $validated['extension'] = $newExtension;
        $validated['domain_name'] = $domain->domain_name;
        $validated['created_by'] = $userId;

        // Begin a database transaction
        DB::beginTransaction();

        $data = Ringgroup::create($validated);

        $ringGroupid =  $data->id;
        $account_id =  $data->account_id;

        //data for child table group destinations
        if ($request->has('destination')) {

            // Retrieve data from the request object
            $inputs = $request->destination;

            foreach ($inputs as $input) {

                $input['account_id'] = $account_id;
                $input['ring_group_id'] = $ringGroupid;

                $rDestinationValidator = Validator::make(
                    $input,
                    [
                        'account_id' => 'required',
                        'ring_group_id' => 'required|numeric',
                        'destination' => 'string|exists:extensions,extension|unique:ring_group_destinations,destination,' . $input['destination'] . ',id,ring_group_id,' . $input['ring_group_id'],
                        'destination_timeout' => 'string|nullable',
                        'delay_order' => 'numeric',
                        'prompt' => 'string|nullable',
                        'created_by' => 'required',
                        'status' => 'in:active,inactive',
                    ]
                );

                // Check if the validation process has failed
                if ($rDestinationValidator->fails()) {
                    // If validation fails, return a JSON response with error messages
                    $response = [
                        'status' => false,
                        'message' => 'validation error',
                        'errors' => $rDestinationValidator->errors()
                    ];

                    return response()->json($response, Response::HTTP_FORBIDDEN);
                }

                // Retrieve the validated input
                $rvalidated = $rDestinationValidator->validated();

                Ring_group_destination::create($rvalidated);
            }
        }

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

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $ringgroups = Ringgroup::with(['ring_group_destination']); //relations tbl small character 1st letter
        $ringgroups->where('ringgroups.id', $id);
        $ringgroups = $ringgroups->get();

        if (!$ringgroups) {
            $response = [
                'status' => false,
                'error' => 'Ring group not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $response = [
            'status' => true,
            'data' => ($ringgroups) ? $ringgroups : '', // Ensure data is not null
            'message' => 'Successfully fetched'
        ];

        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the ringgroup by ID
        $ringgroup = Ringgroup::find($id);

        // Check if the ringgroup exists
        if (!$ringgroup) {
            // If the ringgroup is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Ring group not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make(
            $request->all(),
            [
                // Validation rules for each field
                'account_id' => 'exists:accounts,id',
                'name' => [
                    'string',
                    Rule::unique('ringgroups')
                        ->where(function ($query) use ($request) {
                            return $query->where('account_id', $request->input('account_id'));
                        })
                        ->ignore($id), // Assuming 'id' is the route parameter
                ],
                'strategy' => 'in:enterprise,sequence,simultaneously,random,rollover,',
                'timeout_destination' => 'string|nullable',
                'call_timeout' => 'string',
                'ring_group_caller_id_name' => 'string|nullable',
                'ring_group_caller_id_number' => 'string|nullable',
                'ring_group_cid_name_prefix' => 'string|nullable',
                'ring_group_cid_number_prefix' => 'string|nullable',
                'ring_group_timeout_app' => 'string',
                'ring_group_timeout_data' => 'string',
                'distinctive_ring' => 'string|nullable',
                'ring_back' => 'string|nullable',
                'followme' => 'boolean|nullable',
                'missed_call' => 'string|nullable',
                'missed_destination' => 'string|nullable',
                'ring_group_forward' => 'string|nullable',
                'ring_group_forward_destination' => 'string|nullable',
                'toll_allow' => 'string|nullable',
                'context' => 'string|nullable',
                'greeting' => 'string|nullable',
                'status' => 'in:active,inactive',
                'description' => 'string|nullable',
                'recording_enabled' => 'boolean|nullable',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a JSON response with error messages
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Call the compareValues function to generate a formatted description based on the gateway and validated data
        $formattedDescription = compareValues($ringgroup, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Begin a database transaction
        DB::beginTransaction();

        // Generate UID and attach it to the validated data
        createUid($action, $type, $formattedDescription, $userId);

        // Update the gateway with the validated data
        $ringgroup->update($validated);

        //data for child table group destinations
        if ($request->has('destination')) {
            // Ring_group_destination
            // Retrieve data from the request object
            $inputs = $request->destination;

            // Iterate over each input element in the $inputs array
            foreach ($inputs as $input) {

                $input['ring_group_id'] = $id;

                if (isset($input['id'])) {
                    // Create a new validator instance to validate the request data
                    $rDestinationValidator = Validator::make(
                        $input,
                        [
                            'id' => 'numeric|exists:ring_group_destinations,id',
                            'destination' => 'required|string|exists:extensions,extension|unique:ring_group_destinations,destination,' . $input['id'] . ',id,ring_group_id,' . $id . '',
                            'destination_timeout' => 'string|nullable',
                            'delay_order' => 'numeric',
                            'prompt' => 'string|nullable',
                            'status' => 'in:active,inactive',
                        ]
                    );
                } else {
                    $rDestinationValidator = Validator::make(
                        $input,
                        [
                            'ring_group_id' => 'required',
                            'destination' => 'required|string|exists:extensions,extension|
                            unique:ring_group_destinations,destination,null,null,ring_group_id,' . $id . '',
                            'destination_timeout' => 'string|nullable',
                            'delay_order' => 'numeric',
                            'prompt' => 'string|nullable',
                            'status' => 'in:active,inactive',
                        ]
                    );
                }

                // Check if the validation process has failed
                if ($rDestinationValidator->fails()) {
                    // If validation fails, return a JSON response with error messages
                    $response = [
                        'status' => false,
                        'message' => 'validation error',
                        'errors' => $rDestinationValidator->errors()
                    ];

                    return response()->json($response, Response::HTTP_FORBIDDEN);
                }

                if (isset($input['id'])) {
                    // create a new Followme model instance with validated data
                    $ringGroupDestinations = Ring_group_destination::find($input['id']);

                    if ($ringGroupDestinations->ring_group_id == $id) {
                        $ringGroupDestinations->update($rDestinationValidator->validated());
                    } else {
                        $response = [
                            'status' => false,
                            'message' => 'validation error',
                            'errors' => 'Ringgroup id not matched'
                        ];

                        return response()->json($response, Response::HTTP_FORBIDDEN);
                    }
                } else {
                    $input['account_id'] = $ringgroup->account_id;
                    Ring_group_destination::create($input);
                }
            }
        }

        // Commit the database transaction
        DB::commit();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $ringgroup,
            'message' => 'Successfully updated ringgroup',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $ringgroup = Ringgroup::find($id);

        if (!$ringgroup) {
            $response = [
                'status' => false,
                'error' => 'Ring group not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $ringgroup->delete();

        $response = [
            'status' => true,
            'message' => 'Successfully deleted ringgroup'
        ];

        return response()->json($response, Response::HTTP_OK);
    }
}
