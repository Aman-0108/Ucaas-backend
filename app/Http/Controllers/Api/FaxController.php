<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountBalance;
use App\Models\DidDetail;
use App\Models\FaxFile;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FaxController extends Controller
{
    /**
     * Retrieve all fax.
     *
     * This method retrieves all fax from the database.
     * It returns a JSON response containing the list of fax.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response containing the list of fax
     */
    public function index(Request $request)
    {
        // Retrieve the authenticated user's ID
        $account_id = $request->user()->account_id;

        $query = FaxFile::query();

        // Filter fax by user ID if provided
        $fax = $account_id ? $query->where('account_id', $account_id)->get() : $query->get();

        // Prepare a success response with the list of fax
        $response = [
            'status' => true,
            'data' => $fax,
            'message' => 'Successfully fetched all fax'
        ];

        // Return a JSON response with the list of fax
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Retrieve an Fax by ID.
     *
     * This method finds and retrieves an Fax based on the provided ID.
     * If the Fax is not found, it returns a 404 Not Found response.
     * If the Fax is found, it returns a JSON response containing the Fax data.
     *
     * @param  int  $id The ID of the Fax to retrieve
     * @return \Illuminate\Http\JsonResponse The JSON response containing the Fax data or an error message
     */
    public function show($id)
    {
        // Find the Fax by ID
        $fax = FaxFile::where('id', $id)->first();

        // Find the Fax by ID
        if (!$fax) {
            // If the Fax is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Fax not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare a success response with the Fax data
        $response = [
            'status' => true,
            'data' => ($fax) ? $fax : '',
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response with the Fax data with status(200)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a new Fax.
     *
     * This method validates the incoming request data and stores a new Fax in the database.
     * If the validation fails, it returns a 403 Forbidden response with validation errors.
     * If the Fax is successfully stored, it returns a success message along with the stored Fax data.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the Fax data
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function store(Request $request)
    {
        $account_id = $request->user()->account_id;

        $request->merge(['account_id' => $account_id]);

        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'file_path' => 'required|file|mimes:doc,docx,pdf|max:2048',
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

        // Retrieve the validated input
        $validated = $validator->validated();

        $path = 'fax';

        $file = $request->file('file_path');
        $fileName = time() . '_' . $file->getClientOriginalName();
        // Get the file size
        $fileSize = $file->getSize(); // Size in bytes

        // Upload file to S3
        $filePath = $file->storeAs($path, $fileName, 's3'); // Specify 's3' disk

        // Retrieve the S3 URL of the uploaded file
        $s3Url = Storage::disk('s3')->url($filePath);

        $validated = [
            'account_id' => $request->account_id,
            'file_name' => $fileName,
            'file_path' => $s3Url,
            'file_size' => $fileSize
        ];

        DB::beginTransaction();

        // Create a new Fax with the validated input
        $data = FaxFile::create($validated);

        DB::commit();

        // Prepare a success response with the stored Fax data
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully stored'
        ];

        // Return a JSON response with the success message and stored Fax data
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Update an Fax by ID.
     *
     * This method finds and updates an Fax based on the provided ID and request data.
     * If the Fax is not found, it returns a 404 Not Found response.
     * If the validation fails, it returns a 403 Forbidden response with validation errors.
     * If the update is successful, it returns a success message along with the updated Fax data.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the update data
     * @param  int  $id The ID of the Fax to update
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function update(Request $request, $id)
    {
        $account_id = $request->user()->account_id;

        $request->merge(['account_id' => $account_id]);

        // Find the Fax by ID
        $fax = FaxFile::find($id);

        // Check if the Fax exists
        if (!$fax) {
            // If the Fax is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Fax not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'exists:accounts,id',
                'file_path' => 'file|mimes:doc,docx,pdf|max:2048'
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

        // Check if the authenticated user has permission to edit the fax file
        if ($fax->account_id !== $account_id) {
            // If user doesn't have permission, prepare error response
            $response = [
                'status' => false,
                'error' => 'You dont have access to edit.'
            ];

            // Return a JSON response with error message and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        $path = 'fax';

        if ($request->hasFile('file_path')) {

            $filePath = parse_url($fax->file_path, PHP_URL_PATH); // Get the path part of the URL
            // Check if the file exists on S3
            if (Storage::disk('s3')->exists($filePath)) {
                // Delete the file from S3
                Storage::disk('s3')->delete($filePath);
            }

            $file = $request->file('file_path');
            $fileName = time() . '_' . $file->getClientOriginalName();
            // Get the file size
            $fileSize = $file->getSize(); // Size in bytes

            // Upload file to S3
            $filePath = $file->storeAs($path, $fileName, 's3'); // Specify 's3' disk

            // Retrieve the S3 URL of the uploaded file
            $s3Url = Storage::disk('s3')->url($filePath);

            $validated = [
                'account_id' => $request->account_id,
                'file_name' => $fileName,
                'file_path' => $s3Url,
                'file_size' => $fileSize
            ];
        }

        // Update the Fax with the validated input
        $fax->update($validated);

        // Prepare a success response with updated Fax data
        $response = [
            'status' => true,
            'data' => $fax,
            'message' => 'Successfully updated Fax',
        ];

        // Return a JSON response with the success message and updated Fax data with status(200)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Delete an Fax by ID.
     *
     * This method finds and deletes an Fax based on the provided ID.
     * If the Fax is not found, it returns a 404 Not Found response.
     * If the Fax is successfully deleted, it returns a success message.
     *
     * @param  int  $id The ID of the Fax to delete
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function destroy($id)
    {
        $account_id = auth()->user()->account_id;

        // Find the Fax by ID
        $fax = FaxFile::find($id);

        // Check if the Fax exists
        if (!$fax) {
            // If the Fax is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Fax not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Check if the authenticated user has permission to edit the audio
        if ($fax->account_id !== $account_id) {
            // If user doesn't have permission, prepare error response
            $response = [
                'status' => false,
                'error' => 'You dont have access to delete.',
            ];

            // Return a JSON response with error message and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        $filePath = parse_url($fax->file_path, PHP_URL_PATH); // Get the path part of the URL
        // Check if the file exists on S3
        if (Storage::disk('s3')->exists($filePath)) {
            // Delete the file from S3
            Storage::disk('s3')->delete($filePath);
        }

        // Delete the Fax
        $fax->delete();

        // Prepare a success message
        $response = [
            'status' => true,
            'message' => 'Successfully deleted Fax'
        ];

        // Return a JSON response with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Send a fax using the provided file and configuration.
     *
     * This method validates the incoming request data and performs the following steps:
     * 1. Retrieves the FaxFile record based on the provided ID.
     * 2. Gets the file content from the file path.
     * 3. Saves the file locally.
     * 4. Uses LibreOffice to convert the DOC file to TIFF.
     * 5. Saves the TIFF file to the file system.
     * 6. Copies the TIFF file to the FreeSwitch directory.
     * 7. Creates a FreeSwitch request with the fax configuration and sends the fax.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the fax configuration and file ID
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function sendFax(Request $request)
    {
        $account_id = $request->user()->account_id;

        // Get the account balance
        $balance = AccountBalance::where('account_id', $account_id)->first();

        // Check if balance is less than 2
        if ($balance->balance < 1) {
            return response()->json(['status' => false, 'message' => 'Insufficient balance. Please top up.'], 500);
        }

        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'fax_files_id' => 'required|exists:fax_files,id',
                'destination_caller_id_number' => 'required|integer',
                "fax_ident" => "required|integer",
                "fax_header" => 'required|string',
            ]
        );

        //    Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a 403 Forbidden response with validation errors
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Get the additional parameters
        $additionalParams = $this->getAccountcredentials($account_id, $request->destination_caller_id_number);

        // Check if additional parameters are empty
        if (!$additionalParams) {
            return response()->json(['status' => false, 'message' => 'There is something wrong. Please try again later.'], 500);
        }

        $call_plan_id = $additionalParams['call_plan_id'];
        $destination = $additionalParams['destination'];
        $selling_billing_block = $additionalParams['selling_billing_block'];
        $sell_rate = $additionalParams['sell_rate'];
        $gateway_id = $additionalParams['gateway_id'];
        $billing_type = $additionalParams['billing_type'];

        if (!$call_plan_id || !$destination || !$selling_billing_block || !$sell_rate || !$gateway_id || !$billing_type) {
            return response()->json(['status' => false, 'message' => 'There is something wrong. Please try again later.'], 500);
        }

        $fax_files_id = $request->fax_files_id;

        // Retrieve the FaxFile record
        $faxFile = FaxFile::find($fax_files_id);
        $filePath = $faxFile->file_path;

        $didData = DidDetail::where(['default_outbound' => true, 'account_id' => $account_id])->first();

        if (!$didData) {
            return response()->json(['status' => false, 'message' => 'No default DID set for this account.'], 500);
        }

        // Get the file content
        $fileContent = file_get_contents($filePath);

        if ($fileContent === false) {
            throw new Exception('Failed to read the file content.');
        }

        // Define a local file path
        $localPath = 'downloads/document_' . $fax_files_id . '_' . time() . '.pdf';

        // Save the file locally
        Storage::disk('local')->put($localPath, $fileContent);

        // Use LibreOffice to convert the DOC file to TIFF
        $pdfFilePath = storage_path('app/public') . '/' . $localPath;

        if (!file_exists($pdfFilePath)) {
            return response()->json(['status' => false, 'message' => 'PDF file does not exist.'], 500);
        }

        $outputDirectory = storage_path('app/public/efax/');
        if (!file_exists($outputDirectory)) {
            mkdir($outputDirectory, 0755, true); // Create directory with appropriate permissions
        }

        $tiffName = 'document_' . $fax_files_id . '_' . time() . '.tiff';

        // Use LibreOffice to convert the DOC file to TIFF
        $tiffFilePath = storage_path('app/public/efax/' . $tiffName);

        try {

            // Ghostscript command for conversion
            // $command = "\"C:\\Program Files (x86)\\gs\\gs10.04.0\\bin\\gswin32c.exe\" -dNOPAUSE -dBATCH -sDEVICE=tiff32nc -sOutputFile=\"{$tiffFilePath}\" \"{$pdfFilePath}\"";

            // Ghostscript command for conversion
            $command = "\"C:\\Program Files (x86)\\gs\\gs10.04.0\\bin\\gswin32c.exe\" -q -r204x196 -g1728x2156 -dNOPAUSE -dBATCH -dSAFER -sDEVICE=tiffg3 -sOutputFile=\"{$tiffFilePath}\" \"{$pdfFilePath}\"";

            // Execute the command
            exec($command . ' 2>&1', $output, $returnVar);

            Log::info('Ghostscript output: ' . implode("\n", $output));

            // Check if the command was successful
            if ($returnVar !== 0) {
                throw new Exception('Conversion failed: ' . implode("\n", $output));
            }

            // Optionally, clean up the local PDF file after conversion
            Storage::disk('local')->delete($localPath);

            $this->copyFileToFs($tiffName);

            $requestFormData = [
                "origination_caller_id_number" => $didData->did,
                "origination_caller_id_name" => $didData->did,
                "fax_ident" => $request->fax_ident,
                "fax_header" => $request->fax_header,
                "destination_caller_id_number" => $request->destination_caller_id_number,
                "fax_file" => "/home/fax_files/$tiffName",
                "call_plan_id" => $call_plan_id,
                "destination" => $destination,
                "selling_billing_block" => $selling_billing_block,
                "sell_rate" => $sell_rate,
                "gateway_id" => $gateway_id,
                "billing_type" => $billing_type,
            ];

            Log::info('Fax request data: ' . json_encode($requestFormData));

            $fsController = new FreeSwitchController();
            $response = $fsController->sendFax(new Request($requestFormData));

            return $response;
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Conversion failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Copies a file to the FreeSWITCH server.
     *
     * @param string $tiffFilePath The path to the TIFF file to copy.
     *
     * @return void
     */
    public function copyFileToFs($tiffFilePath)
    {
        // Instantiate the ConfigService directly
        $sshService = app()->make('App\Services\SSHService');

        $sshService->addDirectory('/home/fax_files/', '0777');

        // Remove the file extension from the PDF file path
        $baseFilePath = $tiffFilePath;

        // Define the remote directory
        $remoteDirectory = '/home/fax_files/' . $tiffFilePath;

        $tiffFilePath = storage_path('app/public/efax/' . $tiffFilePath);

        // Upload the .cfg file to the remote server
        $sshService->uploadFile($tiffFilePath, $remoteDirectory);

        // Delete the .cfg file from the local filesystem
        Storage::disk('local')->delete('/efax/' . $baseFilePath);
    }

    public function getAccountcredentials($account_id, $destination_number)
    {
        try {
            $result = DB::select("CALL check_dialout_billing(?, ?)", [$account_id, $destination_number]);
            Log::info($result);
            // Get the first row or null if no results
            $accountCredentials = !empty($result) ? $result[0] : null;
            return $accountCredentials;
        } catch (\Exception $e) {
            Log::error('Error fetching account credentials: ' . $e->getMessage());
            return response()->json(['error' => 'Could not retrieve account credentials.'], 500);
        }
    }
}
