<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentBreak;
use App\Models\CallCenterAgent;
use App\Models\CallCenterQueue;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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
        $account_id = $request->user()->account_id;

        // Retrieve all call centre queues from the database
        $call_centre_queues = CallCenterQueue::with('agents');

        if ($account_id) {
            $call_centre_queues = $call_centre_queues->where('account_id', $account_id);
        }

        // COMING FROM GLOBAL CONFIG
        $ROW_PER_PAGE = config('globals.PAGINATION.ROW_PER_PAGE');

        // Execute the query to fetch call centre queues
        // $call_centre_queues = $call_centre_queues->orderBy('id', 'desc')->paginate($ROW_PER_PAGE);
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
     * Display a listing of the resource for the dashboard.
     *
     * This method fetches all call centre queues from the database and returns them as a JSON response
     * for the dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched call centre queues.
     */
    public function dashboard(Request $request)
    {
        $account_id = $request->user()->account_id;

        // Retrieve all call centre queues from the database
        $call_centre_queues = CallCenterQueue::with('agents');

        if ($account_id) {
            $call_centre_queues = $call_centre_queues->where('account_id', $account_id);
        }

        if ($request->has('search')) {
            // 
            $searchTerm = $request->search;

            $call_centre_queues->where(function ($q) use ($searchTerm) {
                $q->where('queue_name', 'like', "%$searchTerm%")
                    ->orWhere('extension', 'like', "%$searchTerm%")
                    ->orWhere('strategy', 'like', "%$searchTerm%");
            });
        }

        if ($request->has('row_per_page')) {
            $ROW_PER_PAGE = $request->row_per_page;

            if (!is_numeric($ROW_PER_PAGE) || $ROW_PER_PAGE < 1) {
                // Fallback to a default value if invalid
                $ROW_PER_PAGE = config('globals.PAGINATION.ROW_PER_PAGE');
            }
        } else {
            $ROW_PER_PAGE = config('globals.PAGINATION.ROW_PER_PAGE');
        }

        // Execute the query to fetch call centre queues
        $call_centre_queues = $call_centre_queues->orderBy('id', 'desc')->paginate($ROW_PER_PAGE);
        // Execute the query to fetch call centre queues

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
        $userId = $request->user()->id;

        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'queue_name' => [
                    'required',
                    'string',
                    Rule::unique('call_center_queues')->where(function ($query) use ($request) {
                        return $query->where('account_id', $request->input('account_id'));
                    }),
                ],
                'greeting' => 'required|string',
                'strategy' => 'in:' . implode(',', config('enums.agent.strategy')),
                'moh_sound' => 'required|string',
                'time_base_score' => 'in:queue,system',
                'record_template' => 'string|nullable',
                'tier_rules_apply' => 'boolean',
                'tier_rule_wait_second' => 'integer|nullable',
                'tier_rule_wait_multiply_level' => 'boolean',
                'tier_rule_no_agent_no_wait' => 'boolean',
                'abondoned_resume_allowed' => 'boolean',
                'max_wait_time' => 'integer|nullable',
                'max_wait_time_with_no_agent' => 'integer|nullable',
                'max_wait_time_with_no_agent_time_reached' => 'integer|nullable',
                'ring_progressively_delay' => 'integer|nullable',
                'queue_timeout_action' => 'string|nullable',
                'discard_abandoned_after' => 'numeric|nullable',
                'queue_cid_prefix' => 'string|nullable',
                'queue_description' => 'string|nullable',
                'queue_announce_sound' => 'string|nullable',
                'queue_announce_frequency' => 'integer|nullable',
                'created_by' => 'required|exists:users,id',
                'recording_enabled' => 'boolean|nullable',
                'status' => 'boolean',
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
        // DB::beginTransaction();

        $startingPoint = config('globals.CALLCENTRE_START_FROM');

        $maxExtension = CallCenterQueue::where('account_id', $request->account_id)->max('extension');

        // Ensure the maxExtension is at least the starting point
        $maxExtension = $maxExtension !== null ? max($maxExtension, $startingPoint) : $startingPoint;

        // Fetch all existing extensions
        $existingExtensions = CallCenterQueue::where('account_id', $request->account_id)
            ->pluck('extension')
            ->toArray();

        // Generate a list of potential extensions
        $potentialExtensions = range($startingPoint, $maxExtension + 1); // Include +1 to cover the edge case

        // Find the missing extensions
        $availableExtensions = array_diff($potentialExtensions, $existingExtensions);

        // Get the smallest available extension number
        $newExtension = !empty($availableExtensions) ? min($availableExtensions) : $maxExtension + 1;

        // Defining action and type for creating UID
        $action = 'create';
        $type = $this->type;

        $validated['extension'] = $newExtension;

        // Create a new record with validated data
        $data = CallCenterQueue::create($validated);

        accessLog($action, $type, $validated, $userId);

        $freeSWitch = new FreeSwitchController();

        $account_id = $data->account_id;
        $domain = Domain::where(['account_id' => $account_id])->first();

        $generatedQueueName = $data->extension . '@' . $domain->domain_name;

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
                        'call_timeout' => 'integer|nullable',
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

                $rvalidated['max_no_answer'] = isset($rvalidated['max_no_answer']) ? intval($rvalidated['max_no_answer']) : null;
                $rvalidated['wrap_up_time'] = isset($rvalidated['wrap_up_time']) ? intval($rvalidated['wrap_up_time']) : null;
                $rvalidated['reject_delay_time'] = isset($rvalidated['reject_delay_time']) ? intval($rvalidated['reject_delay_time']) : null;
                $rvalidated['call_timeout'] = isset($rvalidated['call_timeout']) ? intval($rvalidated['call_timeout']) : null;
                $rvalidated['busy_delay_time'] = isset($rvalidated['busy_delay_time']) ? intval($rvalidated['busy_delay_time']) : null;
                $rvalidated['no_answer_delay_time'] = isset($rvalidated['no_answer_delay_time']) ? intval($rvalidated['no_answer_delay_time']) : null;

                CallCenterAgent::create($rvalidated);

                $action = 'store';
                $type = 'call_centre_agent';

                accessLog($action, $type, $rvalidated, $userId);

                // $fsResponse = $freeSWitch->callcenter_config_agent_add($newAgent->agent_name, $newAgent->type);
                // $fsResponse = $fsResponse->getData();

                // $fsLevelResponse = $freeSWitch->callcenter_config_tier_set_level($generatedQueueName, $newAgent->agent_name, $newAgent->tier_level);
                // $fsLevelResponse = $fsLevelResponse->getData();

                // $fsPositionResponse = $freeSWitch->callcenter_config_tier_set_position($generatedQueueName, $newAgent->agent_name, $newAgent->tier_position);
                // $fsPositionResponse = $fsPositionResponse->getData();

                // || !$fsLevelResponse->status || !$fsPositionResponse->status
                // if (!$fsResponse->status) {
                //     $type = config('enums.RESPONSE.ERROR');
                //     $status = false;
                //     $msg = 'Something went wrong in freeswitch while adding an agent. Please try again later.';

                //     return responseHelper($type, $status, $msg, Response::HTTP_EXPECTATION_FAILED);
                // }
            }
        }

        $reloadmodResponse = $freeSWitch->reload_mod_callcenter();
        $reloadmodResponse = $reloadmodResponse->getData();

        if (!$reloadmodResponse->status) {
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'Something went wrong in freeswitch while reloading mod call canter. Please try again later.';

            return responseHelper($type, $status, $msg, Response::HTTP_EXPECTATION_FAILED);
        }

        // Commit the database transaction
        // DB::commit();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully stored'
        ];

        // Return a JSON response with HTTP status code 201 (Created)
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Display the specified call centre queue by ID.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Find the call_centre_queue by ID
        $call_centre_queue = CallCenterQueue::with('agents')->find($id);

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
        $userId = $request->user()->id;

        // Find the call_centre_queue by ID
        $call_centre_queue = CallCenterQueue::find($id);

        $account_id = $call_centre_queue->account_id;
        $domain = Domain::where(['account_id' => $account_id])->first();

        $generatedQueueName = $call_centre_queue->extension . '@' . $domain->domain_name;

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
                'queue_name' => [
                    'string',
                    Rule::unique('call_center_queues')
                        ->where(function ($query) use ($request) {
                            return $query->where('account_id', $request->input('account_id'));
                        })
                        ->ignore($id), // Assuming 'id' is the route parameter
                ],
                'account_id' => 'required|exists:accounts,id',
                'greeting' => 'string|nullable',
                'strategy' => 'in:' . implode(',', config('enums.agent.strategy')),
                'moh_sound' => 'string|nullable',
                'time_base_score' => 'in:queue,system',
                'record_template' => 'string|nullable',
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
                'recording_enabled' => 'boolean|nullable',
                'queue_description' => 'string|nullable',
                'queue_announce_sound' => 'string|nullable',
                'queue_announce_frequency' => 'integer|nullable',
                'status' => 'boolean|nullable',
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
        // DB::beginTransaction();

        // Update the gateway with the validated data
        $call_centre_queue->update($validated);

        $freeSWitch = new FreeSwitchController();

        $callQueueReload = $freeSWitch->callcenter_config_queue_reload($generatedQueueName);
        $callQueueReload = $callQueueReload->getData();

        if (!$callQueueReload->status) {
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'Something went wrong in freeswitch while reloading queue. Please try again later.';

            return responseHelper($type, $status, $msg, Response::HTTP_EXPECTATION_FAILED);
        }
        // Log the action
        accessLog($action, $type, $formattedDescription, $userId);

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
                            'call_timeout' => 'integer|nullable',
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
                            'call_timeout' => 'integer|nullable',
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

                $rvalidated['max_no_answer'] = isset($rvalidated['max_no_answer']) ? intval($rvalidated['max_no_answer']) : null;
                $rvalidated['wrap_up_time'] = isset($rvalidated['wrap_up_time']) ? intval($rvalidated['wrap_up_time']) : null;
                $rvalidated['reject_delay_time'] = isset($rvalidated['reject_delay_time']) ? intval($rvalidated['reject_delay_time']) : null;
                $rvalidated['call_timeout'] = isset($rvalidated['call_timeout']) ? intval($rvalidated['call_timeout']) : null;
                $rvalidated['busy_delay_time'] = isset($rvalidated['busy_delay_time']) ? intval($rvalidated['busy_delay_time']) : null;
                $rvalidated['no_answer_delay_time'] = isset($rvalidated['no_answer_delay_time']) ? intval($rvalidated['no_answer_delay_time']) : null;

                if (isset($input['id'])) {
                    $callCenterAgent = CallCenterAgent::find($input['id']);

                    if ($callCenterAgent->call_center_queue_id == $id) {

                        $callCenterAgent->update($rvalidated);

                        $freeSWitch = new FreeSwitchController();

                        $aname = $rvalidated['agent_name'];
                        $tier_level = $rvalidated['tier_level'];
                        $tier_position = $rvalidated['tier_position'];

                        $fsLevelResponse = $freeSWitch->callcenter_config_tier_set_level($generatedQueueName, $aname, $tier_level);
                        $fsLevelResponse = $fsLevelResponse->getData();

                        $fsPositionResponse = $freeSWitch->callcenter_config_tier_set_position($generatedQueueName, $aname, $tier_position);
                        $fsPositionResponse = $fsPositionResponse->getData();

                        if (!$fsLevelResponse->status || !$fsPositionResponse->status) {
                            $type = config('enums.RESPONSE.ERROR');
                            $status = false;
                            $msg = 'Something went wrong in freeswitch tier set or level. Please try again later.';

                            return responseHelper($type, $status, $msg, Response::HTTP_EXPECTATION_FAILED);
                        }
                    } else {
                        $response = [
                            'status' => false,
                            'message' => 'validation error',
                            'errors' => 'Agent id not matched'
                        ];

                        return response()->json($response, Response::HTTP_FORBIDDEN);
                    }
                } else {
                    $rvalidated['status'] = 'Logged Out';
                    $newAgent = CallCenterAgent::create($rvalidated);

                    // $freeSWitch = new FreeSwitchController();

                    $count = CallCenterAgent::where(['agent_name' => $newAgent->agent_name])->count();

                    if ($count > 1) {
                        $fsTierResponse = $freeSWitch->callcenter_config_tier_add($generatedQueueName, $newAgent->agent_name, $newAgent->tier_level, $newAgent->tier_position);
                        $fsTierResponse = $fsTierResponse->getData();

                        if (!$fsTierResponse->status) {
                            $type = config('enums.RESPONSE.ERROR');
                            $status = false;
                            $msg = 'Something went wrong in freeswitch while setting tier for agent. Please try again later.';

                            return responseHelper($type, $status, $msg, Response::HTTP_EXPECTATION_FAILED);
                        }
                    } else {
                        $fsResponse = $freeSWitch->callcenter_config_agent_add($newAgent->agent_name, $newAgent->type);
                        $fsResponse = $fsResponse->getData();

                        if (!$fsResponse->status) {
                            $type = config('enums.RESPONSE.ERROR');
                            $status = false;
                            $msg = 'Something went wrong in freeswitch while creating agent. Please try again later.';

                            return responseHelper($type, $status, $msg, Response::HTTP_EXPECTATION_FAILED);
                        }

                        $setContactRespone = $freeSWitch->callcenter_config_agent_set_contact($newAgent->agent_name, $newAgent->contact);
                        $setContactRespone = $setContactRespone->getData();

                        if (!$setContactRespone->status) {
                            $type = config('enums.RESPONSE.ERROR');
                            $status = false;
                            $msg = 'Something went wrong in freeswitch while setting contact for agent. Please try again later.';

                            return responseHelper($type, $status, $msg, Response::HTTP_EXPECTATION_FAILED);
                        }

                        $fsTierResponse = $freeSWitch->callcenter_config_tier_add($generatedQueueName, $newAgent->agent_name, $newAgent->tier_level, $newAgent->tier_position);
                        $fsTierResponse = $fsTierResponse->getData();

                        if (!$fsTierResponse->status) {
                            $type = config('enums.RESPONSE.ERROR');
                            $status = false;
                            $msg = 'Something went wrong in freeswitch while setting tier for agent. Please try again later.';

                            return responseHelper($type, $status, $msg, Response::HTTP_EXPECTATION_FAILED);
                        }
                    }
                }
            }
        }

        $reloadmodResponse = $freeSWitch->reload_mod_callcenter();
        $reloadmodResponse = $reloadmodResponse->getData();

        if (!$reloadmodResponse->status) {
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'Something went wrong in freeswitch while reloading mod call canter. Please try again later.';

            return responseHelper($type, $status, $msg, Response::HTTP_EXPECTATION_FAILED);
        }

        // Commit the database transaction
        // DB::commit();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $call_centre_queue,
            'message' => 'Successfully updated.',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Delete the specified call centre agent from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
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

        // Find the call centre queue associated with the agent
        // and the domain associated with the call centre queue
        $call_center_queue_id = $callCentreAgent->call_center_queue_id;
        $call_centre_queue = CallCenterQueue::where(['id' => $call_center_queue_id])->first();
        $account_id = $call_centre_queue->account_id;
        $domain = Domain::where(['account_id' => $account_id])->first();

        // Generate the queue name by concatenating the queue extension and domain name
        $generatedQueueName = $call_centre_queue->extension . '@' . $domain->domain_name;

        // Start a database transaction
        DB::beginTransaction();

        // Get an instance of FreeSwitchController
        $freeSWitch = new FreeSwitchController();

        // Call the callcenter_config_tier_del API to delete the agent from the queue
        $fsResponse = $freeSWitch->callcenter_config_tier_del($generatedQueueName, $callCentreAgent->agent_name);

        // Get the response data from the API call
        $fsResponse = $fsResponse->getData();

        // Check if the API call was successful
        if (!$fsResponse->status) {
            // If the API call failed, roll back the database transaction
            DB::rollBack();

            // Prepare an error response
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'Something went wrong in freeswitch while deleting agent. Please try again later.';

            // Return the error response as JSON with HTTP status code 417 Expectation Failed
            return responseHelper($type, $status, $msg, Response::HTTP_EXPECTATION_FAILED);
        }

        // Delete the call centre agent
        $callCentreAgent->delete();

        // Commit the database transaction
        DB::commit();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted.'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified call centre queue from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // Find the call centre queue by ID
        $call_centre_queue = CallCenterQueue::with('agents')->find($id);

        if (!$call_centre_queue) {
            // If the call centre queue is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Call centre queue not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        DB::beginTransaction();

        // Create an instance of FreeSWitchController
        $freeSWitch = new FreeSwitchController();

        // Get the account ID and domain name from the call centre queue
        $account_id = $call_centre_queue->account_id;
        $domain = Domain::where(['account_id' => $account_id])->first();

        // Generate the queue name by concatenating the queue extension and domain name
        $generatedQueueName = $call_centre_queue->extension . '@' . $domain->domain_name;

        // Get the agents associated with the call centre queue
        $agents = $call_centre_queue->agents;

        // Iterate through each agent and remove it from the queue
        foreach ($agents as $agent) {

            // Get the count of agents with the same agent name
            $count = CallCenterAgent::where(['agent_name' => $agent->agent_name])->count();

            // Remove the agent from the queue
            $fsResponse = $freeSWitch->callcenter_config_tier_del($generatedQueueName, $agent->agent_name);
            $fsResponse = $fsResponse->getData();

            if (!$fsResponse->status) {
                // If there is an error, set the status and message
                $type = config('enums.RESPONSE.ERROR');
                $status = false;
                $msg = 'Something went wrong in freeswitch while removing agent. Please try again later.';
            }

            // If there is only one agent left with the same name, delete it
            if ($count == 1) {
                $fsAgentDeleteResponse = $freeSWitch->callcenter_config_agent_del($agent->agent_name);
                $fsAgentDeleteResponse = $fsAgentDeleteResponse->getData();

                if (!$fsAgentDeleteResponse->status) {
                    // If there is an error, set the status and message
                    $type = config('enums.RESPONSE.ERROR');
                    $status = false;
                    $msg = 'Something went wrong in freeswitch while deleting agent. Please try again later.';
                }
            }
        }

        // Unload the queue
        $queueUnloadResponse = $freeSWitch->callcenter_queue_unload($generatedQueueName);
        $queueUnloadResponse = $queueUnloadResponse->getData();

        if (!$queueUnloadResponse->status) {
            // If there is an error, set the status and message
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'Something went wrong in freeswitch while unloading queue. Please try again later.';

            return responseHelper($type, $status, $msg, Response::HTTP_EXPECTATION_FAILED);
        }

        // Delete the call centre queue
        $call_centre_queue->delete();

        DB::commit();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted call centre queue.'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function callCentreAgentUpdate(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $account_id = $request->user()->account_id;

        // Find the call centre agent by ID
        $data = CallCenterAgent::find($id);

        if (!$data) {
            // If the call centre agent is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Agent not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $call_centre_queue = CallCenterQueue::find($data->call_center_queue_id);
        $account_id = $call_centre_queue->account_id;
        $domain = Domain::where(['account_id' => $account_id])->first();
        $generatedQueueName = $call_centre_queue->extension . '@' . $domain->domain_name;

        // Begin database transaction
        DB::beginTransaction();

        // Create an instance of FreeSWitchController
        $freeSWitch = new FreeSwitchController();

        // Retrieve the call centre queue ID from the call centre agent
        $call_center_queue_id = $data->call_center_queue_id;

        // Find the call centre queue by ID
        $callCenter = CallCenterQueue::where('id', $call_center_queue_id)->first();

        // Check if the authenticated user making the request is authorized to update the call centre agent
        if ($callCenter->account_id != $account_id) {
            // If the user is not authorized, return a 401 Unauthorized response
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($request->status == 'On Break') {
            AgentBreak::create([
                'call_center_agent_id' => $id,
                'start_time' => now(),
                'end_time' => null,
            ]);

            $StateResponse = $this->agentStateChange($generatedQueueName, $data->agent_name, 'standby');

            if ($StateResponse !== true) {
                return $StateResponse;
            }
        }

        if ($request->status == 'Available') {
            if ($data->status == 'On Break') {
                // Find the ongoing break record for the agent
                $ongoingBreak = AgentBreak::where('call_center_agent_id', $id)
                    ->whereNull('end_time')
                    ->first();

                if ($ongoingBreak) {
                    // Update the end_time of the ongoing break
                    $ongoingBreak->end_time = now();
                    $ongoingBreak->save();

                    // Calculate the duration of the break
                    $breakDuration = $ongoingBreak->end_time->diffInSeconds($ongoingBreak->start_time);

                    // Update the total_break_time for this specific break record
                    $ongoingBreak->total_break_time = $breakDuration;
                    $ongoingBreak->save();
                }
            }

            // Add the agent to the queue
            $fsTierResponse = $freeSWitch->callcenter_config_tier_add($generatedQueueName, $data->agent_name, $data->tier_level, $data->tier_position);
            $fsTierResponse = $fsTierResponse->getData();

            if (!$fsTierResponse->status) {
                // If there is an error, set the status and message
                $type = config('enums.RESPONSE.ERROR');
                $status = false;
                $msg = 'Something went wrong in freeswitch while adding tier. Please try again later.';
            }

            $StateResponse = $this->agentStateChange($generatedQueueName, $data->agent_name, 'ready');

            if ($StateResponse !== true) {
                return $StateResponse;
            }
        }

        if ($request->status == 'Logged Out') {
            // Remove the agent from the queue
            $fsTierResponse = $freeSWitch->callcenter_config_tier_del($generatedQueueName, $data->agent_name);
            $fsTierResponse = $fsTierResponse->getData();

            if (!$fsTierResponse->status) {
                // If there is an error, set the status and message
                $type = config('enums.RESPONSE.ERROR');
                $status = false;
                $msg = 'Something went wrong in freeswitch while deleting tier. Please try again later.';

                return responseHelper($type, $status, $msg, Response::HTTP_EXPECTATION_FAILED);
            }
        }

        if ($request->status !== 'On Break' && $request->status !== 'Logged Out') {
            $fsStatus = addQuotesIfHasSpace($request->status);
            // callcenter_config_agent_set_status
            $agentResponse = $freeSWitch->callcenter_config_agent_set_status($data->agent_name, $fsStatus);
            $agentResponse = $agentResponse->getData();

            if (!$agentResponse->status) {
                // If there is an error, set the status and message
                $type = config('enums.RESPONSE.ERROR');
                $status = false;
                $msg = 'Something went wrong in freeswitch while setting status. Please try again later.';

                return responseHelper($type, $status, $msg, Response::HTTP_EXPECTATION_FAILED);
            }
        }

        // Update the status of the call centre agent
        $data->status = $request->status;

        // Save the changes to the call centre agent
        $data->save();

        // Commit the changes to the database
        DB::commit();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully updated call centre agent'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Changes the state of an agent in a call centre queue to either 'standby' or 'ready'.
     *
     * @param string $queue The name of the call centre queue.
     * @param string $agent The name of the agent to change the state of.
     * @param string $state The state to change the agent to. Can be either 'standby' or 'ready'.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating success or failure.
     */
    protected function agentStateChange($queue, $agent, $state)
    {
        // Create an instance of FreeSWitchController
        $freeSWitch = new FreeSwitchController();

        // Use the callcenter_config_tier_set_state method to change the state of the agent
        $fsBreakStatus = $freeSWitch->callcenter_config_tier_set_state($queue, $agent, $state);
        $fsBreakStatus = $fsBreakStatus->getData();

        if (!$fsBreakStatus->status) {
            // If there is an error, set the status and message
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'Something went wrong in freeswitch while setting agent ' . $state . '. Please try again later.';

            // Return a JSON response with an error message
            return responseHelper($type, $status, $msg, Response::HTTP_EXPECTATION_FAILED);
        }

        // Return a JSON response with a success message
        return true;
    }
}
