<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountDetail;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AccountDetailsController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Account Details'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Account Details';
    }

    /**
     * Display a listing of the resource.
     *
     * This method fetches all accountDetails from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched accountDetails.
     */
    public function index(Request $request)
    {
        // Retrieve all accountDetails from the database
        $accountDetails = AccountDetail::query();

        // Execute the query to fetch 
        $data = $accountDetails->get();
        $data = $data->toArray();

        // Map keys to custom values
        $mappedData = array_map(function ($item) {
            return [
                'identifier' => $item['id'],
                'account_number' => $item['account_id'],
                'registration_image_url' => Storage::url($item['registration_path']),
                'tin_image_url' => Storage::url($item['tin_path']),
                'moa_image_url' => Storage::url($item['moa_path']),
                'created_at' => $item['created_at'],
                'updated_at' => $item['updated_at']
            ];
        }, $data);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $mappedData,
            'message' => 'Successfully fetched all account Details',
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * This method fetches the Account Details with the given ID from the database and returns it as a JSON response.
     *
     * @param  int $id The ID of the Account Details to be fetched.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the fetched Account Details.
     */
    public function show($id)
    {
        // Find the Account Details by ID
        $accountDetail = AccountDetail::find($id);

        // Check if the Account Details exists
        if (!$accountDetail) {
            // If the Account Details is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Account Details not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => ($accountDetail) ? $accountDetail : '', // Ensure data is not null
            'message' => 'Successfully fetched'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created Account Details resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new Account Details record in the database, and returning a
     * JSON response indicating success or failure.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // $userId = $request->user()->id;

        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'documents' => 'required|array',
                'documents.*.document_id' => 'required|integer|exists:documents,id',
                'documents.*.path' => 'required|mimes:jpeg,png,jpg,pdf|max:2048',
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

        $accountId = $request->account_id;

        // Get Account details
        $account = Account::find($accountId);

        if ($account->company_status == 1) {
            // Prepare the response data
            $response = [
                'status' => false,
                'message' => 'Your payment is under verification.'
            ];

            // Return a JSON response with HTTP status code 424
            return response()->json($response, Response::HTTP_FAILED_DEPENDENCY);
        }

        foreach ($request->documents as $document) {

            $uploadedData = AccountDetail::where(['account_id' => $accountId, 'document_id' => $document['document_id']])->get();

            // Assuming $uploadedData is a collection of AccountDetail
            $approvedData = $uploadedData->filter(function ($item) {
                return $item->status == 1;
            });

            if (!empty($approvedData)) {
                $response = [
                    'status' => false,
                    'message' => 'already approved.'
                ];

                // Return a JSON response with HTTP status code 302 (found)
                return response()->json($response, Response::HTTP_FOUND);
            }

            $newData = $uploadedData->filter(function ($item) {
                return $item->status == 3;
            });

            if (!empty($newData)) {
                $response = [
                    'status' => false,
                    'message' => 'already uploaded.'
                ];

                // Return a JSON response with HTTP status code 302 (found)
                return response()->json($response, Response::HTTP_FOUND);
            }
            
            // Insert data
            $this->insertData($accountId, $document);

            // Check if all documents are uploaded
            $this->checkAllDocumentsUploadedOrNot($accountId);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully stored'
        ];

        // Return a JSON response with HTTP status code 201 (Created)
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Update the specified Account Details resource in storage.
     *
     * This method retrieves the Account Details with the given ID, checks if it exists,
     * validates the incoming request data, and updates the Account Details record in the
     * database if validation passes. It returns a JSON response indicating success
     * or failure along with any relevant data or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the Account Details to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request)
    {
        // Find the Account Details with the given ID
        $accountDetail = AccountDetail::find($request->id);

        // Check if the Account Details exists
        if (!$accountDetail) {
            // If the Account Details is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Account Details not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'id' => 'required',
                'registration_path' => 'mimes:jpeg,png,jpg,pdf|max:2048',
                'tin_path' => 'mimes:jpeg,png,jpg,pdf|max:2048',
                'moa_path' => 'mimes:jpeg,png,jpg,pdf|max:2048',
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
        $formattedDescription = compareValues($accountDetail, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Generate UID and attach it to the validated data
        // createUid($action, $type, $formattedDescription, $userId);

        // Retrieve the old file paths

        if ($request->registration_path) {
            if (Storage::exists($accountDetail->registration_path)) {
                Storage::delete($accountDetail->registration_path);
            }
        }

        if ($request->tin_path) {
            if (Storage::exists($accountDetail->tin_path)) {
                Storage::delete($accountDetail->tin_path);
            }
        }

        if ($request->moa_path) {
            if (Storage::exists($accountDetail->moa_path)) {
                Storage::delete($accountDetail->moa_path);
            }
        }

        $accountId = $request->input('account_id');
        $filePaths = [];
        $files = $request->only(['registration_path', 'tin_path', 'moa_path']);

        foreach ($files as $key => $file) {
            if ($request->hasFile($key)) {
                $uploadedFile = $request->file($key);
                $filePath = $uploadedFile->store('company');
                $filePaths[$key] = $filePath;
            }
        }

        // Update the Account Details record with validated data

        // Save file paths in the database
        if (isset($filePaths['registration_path'])) {
            $accountDetail->registration_path = $filePaths['registration_path'];
        }

        if (isset($filePaths['tin_path'])) {
            $accountDetail->tin_path = $filePaths['tin_path'];
        }

        if (isset($filePaths['moa_path'])) {
            $accountDetail->moa_path = $filePaths['moa_path'];
        }

        $accountDetail->save();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $accountDetail,
            'message' => 'Successfully updated Account Details',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified Account Details resource from storage.
     *
     * This method retrieves the Account Details with the given ID, checks if it exists,
     * and deletes the Account Details record from the database. It returns a JSON response
     * indicating success or failure.
     *
     * @param  int  $id The ID of the Account Details to delete
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function destroy($id)
    {
        // Find the Account Details with the given ID
        $accountDetail = AccountDetail::find($id);

        // Check if the Account Details exists
        if (!$accountDetail) {
            // If the Account Details is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Account Details not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $path = $accountDetail->path;

        $exists = Storage::exists($path);

        if ($exists) {
            // Delete associated file from storage
            Storage::delete($path);
        }

        // Delete the Account Details record
        $accountDetail->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted Account Details'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    public function insertData($accountId, $document)
    {
        // Get the file from the request
        $file = $document['path'];

        // Generate a unique filename
        $filename = uniqid() . '_' . $file->getClientOriginalName();

        $filePath = $file->storeAs('company', $filename);

        AccountDetail::create([
            'account_id' => $accountId,
            'document_id' => $document['document_id'],
            'path' => $filePath,
            'status' => 3
        ]);
    }

    // Check if all required documents are uploaded
    protected function checkAllDocumentsUploadedOrNot($accountId)
    {
        // Get all required documents
        $allRequiredDocuments = Document::where('status', 'active')->pluck('id');
       
        // Get the uploaded documents for the account
        $uploadedDocuments = AccountDetail::where(['account_id' => $accountId])->distinct('document_id')->pluck('document_id');

        // Check if all required documents are uploaded
        $result = rsort($allRequiredDocuments) === rsort($uploadedDocuments);

        // Update the account status if all required documents are uploaded
        if($result) {
            $account = Account::find($accountId);
            $account->company_status = 3;
            $account->save();
        }
    }
}
