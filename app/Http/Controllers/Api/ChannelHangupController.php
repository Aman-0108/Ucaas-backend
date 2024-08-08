<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChannelHangupComplete;
use App\Services\SSHService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ChannelHangupController extends Controller
{
    protected $type;
    protected $sshService;

    /**
     * Constructor function initializes the 'type' property to 'CDR'.
     * @param SSHService $sshService An instance of SSHService used for SSH operations.
     */
    public function __construct(SSHService $sshService)
    {
        // Perform initialization 
        // Initialize the SSHService instance
        $this->sshService = $sshService;
        $this->type = 'CDR';
    }

    /**
     * Retrieves a list of records.
     *
     * This method retrieves a list of cdrs based on optional query parameters.
     * It then returns a JSON response containing the list of cdrs.
     *
     * @param Request $request The HTTP request object.
     * @return \Illuminate\Http\JsonResponse A JSON response containing the list of cdr.
     */
    public function index(Request $request)
    {
        // Start building the query to fetch cdrs
        $cdrs = ChannelHangupComplete::query();

        // Check if the request contains an 'account' parameter
        if ($request->has('account')) {
            // If 'account' parameter is provided, filter cdrs by account ID
            $cdrs->where('account_id', $request->account);
        }

        // COMING FROM GLOBAL CONFIG
        $ROW_PER_PAGE = config('globals.PAGINATION.ROW_PER_PAGE');

        // Execute the query to fetch cdrs
        $cdrs = $cdrs->orderBy('id', 'desc')->paginate($ROW_PER_PAGE);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $cdrs,
            'message' => 'Successfully fetched.'
        ];

        // Return a JSON response containing the list of cdrs
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Retrieve call details for a user.
     *
     * This function retrieves call details for the authenticated user, categorized into all calls,
     * inbound calls, and outbound calls. It counts the total number of calls, successful calls, and
     * missed calls for each category.
     *
     * @param \Illuminate\Http\Request $request The HTTP request object containing user authentication details.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response with call details for the user.
     */
    public function callDetailsByUserId(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        $success = 'SUCCESS';
        $missed = 'NOANSWER';

        // Retrieve all calls
        $query = ChannelHangupComplete::query();

        if($request->has('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Retrieve all calls
        $all = $query->get();

        // Filter all calls by success and missed status
        $allSuccess = $this->filterByValue($all, $success);
        $allMissed = $this->filterByValue($all, $missed);

        // Retrieve inbound calls
        $query = ChannelHangupComplete::query();
        $inbound = $query->where('Call-Direction', 'inbound')->get();

        // Filter inbound calls by success and missed status
        $inboundSuccess = $this->filterByValue($inbound, $success);
        $inboundMissed = $this->filterByValue($inbound, $missed);

        // Retrieve outbound calls
        $query = ChannelHangupComplete::query();
        $outbound = $query->where('Call-Direction', 'outbound')->get();

        // Filter outbound calls by success and missed status
        $outboundSuccess = $this->filterByValue($outbound, $success);
        $outboundMissed = $this->filterByValue($outbound, $missed);

        // Prepare call details for all calls
        $allDetails = [
            'calls' => $all,
            'count' => $all->count(),
            'success' => $allSuccess->count(),
            'missed' => $allMissed->count(),
            'active' => null
        ];

        // Prepare call details for inbound calls
        $inboundDetails = [
            'calls' => $inbound,
            'count' => $inbound->count(),
            'success' => $inboundSuccess->count(),
            'missed' => $inboundMissed->count(),
            'active' => null
        ];

        // Prepare call details for outbound calls
        $outboundDetails = [
            'calls' => $outbound,
            'count' => $outbound->count(),
            'success' => $outboundSuccess->count(),
            'missed' => $outboundMissed->count(),
            'active' => null
        ];

        // Combine all call details into a single data array
        $data = [
            'all' => $allDetails,
            'inboundData' => $inboundDetails,
            'outboundData' => $outboundDetails
        ];

        // Prepare JSON response
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully fetched.'
        ];

        // Return JSON response
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Filters a collection of data based on a specific attribute value.
     *
     * @param Illuminate\Support\Collection $data The collection of data to filter.
     * @param mixed $value The value to filter the data by.
     * @return Illuminate\Support\Collection The filtered collection of data.
     */
    public function filterByValue($data, $value)
    {
        // Use the filter method on the data collection
        $result = $data->filter(function ($item) use ($value) {
            return $item->variable_DIALSTATUS == $value;
        });

        // Return the filtered result
        return $result;
    }

    /**
     * Retrieve a file from a remote server by its path.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFileByPath(Request $request)
    {
        // Extract the file extension from the request path
        $string = substr($request->path, strpos($request->path, '.') + 1);

        // Get the file extension using pathinfo()
        $file_info = pathinfo($string);
        $extension = $file_info['extension'];

        // Generate a unique file name based on current date and time
        $localFileName = 'file_' . date('YmdHis') . '.' . $extension;

        // Define the directory where the file will be stored
        $directoryPath = 'app/public/recordings';

        // Create the directory recursively if it doesn't exist
        Storage::makeDirectory('/recordings', 0777, true);

        // Get the absolute path to the storage directory
        $storagePath = storage_path($directoryPath);

        // Create the local file path
        $localFilePath = $storagePath . '/' . $localFileName;

        // Download the file from a remote server using SSH service
        $result = $this->sshService->downloadFile($localFilePath, $request->path);

        // Check if the file was downloaded successfully
        if ($result) {
            $response = [
                'status' => true,
                'message' => 'Successfully downloaded.'
            ];

            // Return a success response with HTTP status code 201
            return response()->json($response, Response::HTTP_CREATED);
        } else {
            $response = [
                'status' => false,
                'message' => 'Something went wrong. Please try again later.'
            ];

            // Return an error response with HTTP status code 412 (Precondition Failed)
            return response()->json($response, Response::HTTP_PRECONDITION_FAILED);
        }
    }
}
