<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChannelHangupComplete;
use App\Services\SSHService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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

    public function callDetailsByUserId(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        $success = 'SUCCESS';
        $missed = 'NOANSWER';

        // All
        $query = ChannelHangupComplete::query();
        $all = $query->get();

        $allSuccess = $this->filterByValue($all, $success);

        $allMissed = $this->filterByValue($all, $missed);

        // inbound
        $query = ChannelHangupComplete::query();
        $inbound = $query->where('Call-Direction', 'inbound')->get();

        $inboundSuccess = $this->filterByValue($inbound, $success);

        $inboundMissed = $this->filterByValue($inbound, $missed);

        // outbound
        $query = ChannelHangupComplete::query();
        $outbound = $query->where('Call-Direction', 'outbound')->get();

        $outboundSuccess = $this->filterByValue($outbound, $success);

        $outboundMissed = $this->filterByValue($outbound, $missed);

        $allDetails = [
            'calls' => $all,
            'count' => $all->count(),
            'success' => $allSuccess->count(),
            'missed' => $allMissed->count(),
            'active' => NULL
        ];

        $inboundDetails = [
            'calls' => $inbound,
            'count' => $inbound->count(),
            'success' => $inboundSuccess->count(),
            'missed' => $inboundMissed->count(),
            'active' => NULL
        ];

        $outboundDetails = [
            'calls' => $outbound,
            'count' => $outbound->count(),
            'success' => $outboundSuccess->count(),
            'missed' => $outboundMissed->count(),
            'active' => NULL
        ];

        $data = [
            'all' => $allDetails,
            'inboundData' => $inboundDetails,
            'outboundData' => $outboundDetails
        ];

        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully fetched.'
        ];

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

    // download call record
    public function getFileByPath(Request $request)
    {
        $string = substr($request->path, strpos($request->path, '.') + 1);

        // Get the file extension using pathinfo()
        $file_info = pathinfo($string);
        $extension = $file_info['extension'];

        $localFileName = 'file_' . date('YmdHis') . '.' . $extension;

        $directoryPath = 'app/public/recordings';

        // Create the directory recursively
        Storage::makeDirectory('/recordings', 0777, true);

        $storagePath = storage_path($directoryPath);

        // Create the local file path
        $localFilePath = $storagePath . '/' . $localFileName;

        $result = $this->sshService->downloadFile($localFilePath, $request->path);

        if ($result) {
            $response = [
                'status' => true,
                'message' => 'Successfully downloaded.'
            ];

            return response()->json($response, Response::HTTP_CREATED);
        } else {
            $response = [
                'status' => false,
                'message' => 'Something went wrong. Please try again later.'
            ];

            return response()->json($response, Response::HTTP_PRECONDITION_FAILED);
        }
    }
}
