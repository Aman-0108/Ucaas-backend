<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallCenterAgent;
use App\Models\CallCenterQueue;
use App\Models\Dialplan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CallCentreController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'call_centre_queue'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'call_centre_queue';
    }

    /**
     * Display a listing of the resource.
     *
     * This method fetches all call centre queues from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched call centre queues.
     */
    public function index(Request $request)
    {
        // Retrieve all call centre queues from the database
        $call_centre_queues = CallCenterQueue::with('agents');

        // Execute the query to fetch call centre queues
        $call_centre_queues = $call_centre_queues->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $call_centre_queues,
            'message' => 'Successfully fetched all call centre queues'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created call centre queues resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new call centre queue record in the database, and returning a
     * JSON response indicating success or failure.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        // $userId = $request->user()->id;

        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'queue_name' => 'required|unique:call_center_queues,queue_name',
                'greeting' => 'string|nullable',
                'extension' => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) use ($request) {
                        // Check if extension exists in call_center_queues table with account_id 
                        if (DB::table('call_center_queues')
                            ->where('extension', $value)
                            ->where('account_id', $request->account_id)
                            ->exists()
                        ) {
                            $fail('The extension already exists in the call_center_queues table for this account.');
                        }

                        // Check if extension exists in extensions table with account_id
                        if (DB::table('extensions')
                            ->where('extension', $value)
                            ->where('account_id', $request->account_id)
                            ->exists()
                        ) {
                            $fail('The extension already exists in the extensions table.');
                        }

                        // Check if extension exists in ringgroups table with account_id
                        if (DB::table('ringgroups')
                            ->where('extension', $value)
                            ->where('account_id', $request->account_id)
                            ->exists()
                        ) {
                            $fail('The extension already exists in the ringgroups.');
                        }
                    }
                ],
                'strategy' => 'in:' . implode(',', config('enums.agent.strategy')),
                'moh_sound' => 'string|nullable',
                'time_base_score' => 'in:queue,system',
                'record_template' => 'boolean',
                'tier_rules_apply' => 'boolean',
                'tier_rule_wait_second' => 'integer|nullable',
                'tier_rule_wait_multiply_level' => 'boolean',
                'tier_rule_no_agent_no_wait' => 'boolean',
                'abondoned_resume_allowed' => 'boolean',
                'max_wait_time' => 'integer',
                'max_wait_time_with_no_agent' => 'integer',
                'max_wait_time_with_no_agent_time_reached' => 'integer',
                'ring_progressively_delay' => 'integer',
                'queue_timeout_action' => 'string|nullable',
                'discard_abandoned_after' => 'numeric|nullable',
                'queue_cid_prefix' => 'string|nullable',
                'created_by' => 'required|exists:users,id',
                'xml' => 'string|nullable'
            ]
        );

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

        $xml = '';
        if ($request->has('xml')) {
            $xml = $request->xml;
            unset($validated['xml']);
        }

        // Create a new record with validated data
        $data = CallCenterQueue::create($validated);

        $freeSWitch = new FreeSwitchController();
        // Reload mod call centre
        $reloadModCallCenterresponse = $freeSWitch->reload_mod_callcenter();
        $reloadModCallCenterresponse = $reloadModCallCenterresponse->getData();

        if (!$reloadModCallCenterresponse->status) {
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'Something went wrong in freeswitch. Please try again later.';

            return responseHelper($type, $status, $msg, Response::HTTP_EXPECTATION_FAILED);
        }

        $call_center_queue_id = $data->id;

        //data for child table group call_centre_agent
        if ($request->has('agents')) {
            // Retrieve data from the request object
            $inputs = $request->agents;

            foreach ($inputs as $input) {
                $input['call_center_queue_id'] = $call_center_queue_id;
                $agentValidator = Validator::make(
                    $input,
                    [
                        'call_center_queue_id' => 'required|exists:call_center_queues,id',
                        'agent_name' => 'required|string|unique:call_center_agents,agent_name,' . $input['agent_name'] . ',id,call_center_queue_id,' . $call_center_queue_id,
                        'password' => 'required',
                        'type' => 'in:' . implode(',', config('enums.agent.type')),
                        'contact' => 'string|nullable',
                        'max_no_answer' => 'integer|nullable',
                        'wrap_up_time' => 'integer|nullable',
                        'reject_delay_time' => 'integer|nullable',
                        'busy_delay_time' => 'integer|nullable',
                        'no_answer_delay_time' => 'integer|nullable',
                        'reserve_agents' => 'boolean',
                        'truncate_agents_on_load' => 'boolean',
                        'truncate_tiers_on_load' => 'boolean',
                        'tier_level' => 'numeric|between:0,9',
                        'tier_position' => 'numeric|between:0,9',
                        'status' => 'in:' . implode(',', config('enums.agent.status')),
                        'state' => 'in:' . implode(',', config('enums.agent.state')),
                    ]
                );

                // Check if the validation process has failed
                if ($agentValidator->fails()) {
                    // If validation fails, return a JSON response with error messages
                    $response = [
                        'status' => false,
                        'message' => 'validation error',
                        'errors' => $agentValidator->errors()
                    ];

                    return response()->json($response, Response::HTTP_FORBIDDEN);
                }

                // Retrieve the validated input
                $rvalidated = $agentValidator->validated();

                CallCenterAgent::create($rvalidated);
            }
        }

        // Store inside dialplan
        // $dialplanData = [
        //     "account_id" => $request->account_id,
        //     "type" => 'Inbound',
        //     "country_code" => '91',
        //     "destination" => 'callcenter',
        //     "context" => 'default',
        //     "usage" => 'voice',
        //     "order" => 230,
        //     "dialplan_enabled" => 1,
        //     "description" => 'call center queue',
        //     "dialplan_xml" => $xml,
        //     "call_center_queues_id" => $call_center_queue_id
        // ];

        // $dailPlanController = new DialplanController();
        // $dailPlanController->insertFromRawData($dialplanData);

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

    public function show($id)
    {
        // Find the call_centre_queue by ID
        $call_centre_queue = CallCenterQueue::find($id);

        // Check if the call_centre_queue exists
        if (!$call_centre_queue) {
            // If call_centre_queue is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'call centre queue not found'
            ];
            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $call_centre_queue,
            'message' => 'Successfully fetched call centre queue'

        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    public function update(Request $request, $id)
    {
        // Find the call_centre_queue by ID
        $call_centre_queue = CallCenterQueue::find($id);

        // Check if the call_centre_queue exists
        if (!$call_centre_queue) {
            // If the call_centre_queue is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Call centre queue not found.'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'queue_name' => 'unique:call_center_queues,queue_name,' . $id,
                'account_id' => 'required|exists:accounts,id',
                'greeting' => 'string|nullable',
                // 'extension' => 'string|unique:call_center_queues,extension,' . $id . ',id,account_id,' . $call_centre_queue->account_id,
                'extension' => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) use ($request) { 
                        // Check if extension exists in extensions table with account_id
                        if (DB::table('extensions')
                            ->where('extension', $value)
                            ->where('account_id', $request->account_id)
                            ->exists()
                        ) {
                            $fail('The extension already exists in the extensions table.');
                        }

                        // Check if extension exists in ringgroups table with account_id
                        if (DB::table('ringgroups')
                            ->where('extension', $value)
                            ->where('account_id', $request->account_id)
                            ->exists()
                        ) {
                            $fail('The extension already exists in the ringgroups.');
                        }
                    },
                    // Ensure the extension is unique in call_center_queues table within the specific account
                    Rule::unique('call_center_queues', 'extension')
                        ->ignore($id)
                        ->where(function ($query) use ($request) {
                            return $query->where('account_id', $request->account_id);
                        }),
                ],
                'strategy' => 'in:' . implode(',', config('enums.agent.strategy')),
                'moh_sound' => 'string|nullable',
                'time_base_score' => 'in:queue,system',
                'record_template' => 'boolean',
                'tier_rules_apply' => 'boolean',
                'tier_rule_wait_second' => 'integer|nullable',
                'tier_rule_wait_multiply_level' => 'boolean',
                'tier_rule_no_agent_no_wait' => 'boolean',
                'abondoned_resume_allowed' => 'boolean',
                'max_wait_time' => 'integer',
                'max_wait_time_with_no_agent' => 'integer',
                'max_wait_time_with_no_agent_time_reached' => 'integer',
                'ring_progressively_delay' => 'integer',
                'queue_timeout_action' => 'string|nullable',
                'discard_abandoned_after' => 'numeric|nullable',
                'queue_cid_prefix' => 'string|nullable',
                'xml' => 'string|nullable'
            ]
        );

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

        // Retrieve the validated input
        $validated = $validator->validated();

        // Call the compareValues function to generate a formatted description based on the call centre queue and validated data
        $formattedDescription = compareValues($call_centre_queue, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Begin a database transaction
        DB::beginTransaction();

        // 
        if ($request->has('xml')) {

            $dp = Dialplan::where('call_center_queues_id', $id)->first();
            $dp->dialplan_xml = $request->xml;
            $dp->save();

            unset($validated['xml']);
        }

        // Update the gateway with the validated data
        $call_centre_queue->update($validated);

        $freeSWitch = new FreeSwitchController();
        // Reload mod call centre
        $reloadModCallCenterresponse = $freeSWitch->reload_mod_callcenter();
        $reloadModCallCenterresponse = $reloadModCallCenterresponse->getData();

        if (!$reloadModCallCenterresponse->status) {
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'Something went wrong in freeswitch. Please try again later.';

            return responseHelper($type, $status, $msg, Response::HTTP_EXPECTATION_FAILED);
        }

        //data for child table group call_centre_agent
        if ($request->has('agents')) {
            // Retrieve data from the request object
            $inputs = $request->agents;

            foreach ($inputs as $input) {

                $input['call_center_queue_id'] = $id;

                if (isset($input['id'])) {
                    $agentValidator = Validator::make(
                        $input,
                        [
                            'id' => 'required|exists:call_center_agents,id',
                            'call_center_queue_id' => 'required|exists:call_center_queues,id',
                            'agent_name' => 'required|string|unique:call_center_agents,agent_name,' . $input['id'] . ',id,call_center_queue_id,' . $id,
                            'password' => 'string',
                            'type' => 'in:' . implode(',', config('enums.agent.type')),
                            'contact' => 'string|nullable',
                            'max_no_answer' => 'integer|nullable',
                            'wrap_up_time' => 'integer|nullable',
                            'reject_delay_time' => 'integer|nullable',
                            'busy_delay_time' => 'integer|nullable',
                            'no_answer_delay_time' => 'integer|nullable',
                            'reserve_agents' => 'boolean',
                            'truncate_agents_on_load' => 'boolean',
                            'truncate_tiers_on_load' => 'boolean',
                            'tier_level' => 'numeric|between:0,9',
                            'tier_position' => 'numeric|between:0,9',
                            'status' => 'in:' . implode(',', config('enums.agent.status')),
                            'state' => 'in:' . implode(',', config('enums.agent.state')),
                        ]
                    );
                } else {
                    $agentValidator = Validator::make(
                        $input,
                        [
                            'call_center_queue_id' => 'required|exists:call_center_queues,id',
                            'agent_name' => 'required|string|unique:call_center_agents,agent_name,' . $input['agent_name'] . ',id,call_center_queue_id,' . $id,
                            'password' => 'required',
                            'type' => 'in:' . implode(',', config('enums.agent.type')),
                            'contact' => 'string|nullable',
                            'max_no_answer' => 'integer|nullable',
                            'wrap_up_time' => 'integer|nullable',
                            'reject_delay_time' => 'integer|nullable',
                            'busy_delay_time' => 'integer|nullable',
                            'no_answer_delay_time' => 'integer|nullable',
                            'reserve_agents' => 'boolean',
                            'truncate_agents_on_load' => 'boolean',
                            'truncate_tiers_on_load' => 'boolean',
                            'tier_level' => 'numeric|between:0,9',
                            'tier_position' => 'numeric|between:0,9',
                            'status' => 'in:' . implode(',', config('enums.agent.status')),
                            'state' => 'in:' . implode(',', config('enums.agent.state')),
                        ]
                    );
                }

                // Check if the validation process has failed
                if ($agentValidator->fails()) {
                    // If validation fails, return a JSON response with error messages
                    $response = [
                        'status' => false,
                        'message' => 'validation error',
                        'errors' => $agentValidator->errors()
                    ];

                    return response()->json($response, Response::HTTP_FORBIDDEN);
                }

                // Retrieve the validated input
                $rvalidated = $agentValidator->validated();

                if (isset($input['id'])) {
                    $callCenterAgent = CallCenterAgent::find($input['id']);

                    if ($callCenterAgent->call_center_queue_id == $id) {

                        $callCenterAgent->update($rvalidated);
                    } else {
                        $response = [
                            'status' => false,
                            'message' => 'validation error',
                            'errors' => 'Agent id not matched'
                        ];

                        return response()->json($response, Response::HTTP_FORBIDDEN);
                    }
                } else {
                    CallCenterAgent::create($rvalidated);
                }
            }
        }

        DB::commit();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $call_centre_queue,
            'message' => 'Successfully updated.',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    public function callCentreAgentDelete($id)
    {
        // Find the call centre agent by ID
        $callCentreAgent = CallCenterAgent::find($id);

        // Check if the call centre agent exists
        if (!$callCentreAgent) {
            // If the call centre agent is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'call centre agent not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the call centre agent
        $callCentreAgent->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted.'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
