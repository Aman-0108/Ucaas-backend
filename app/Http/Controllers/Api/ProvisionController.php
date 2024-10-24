<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Extension;
use App\Models\Provisioning;
use App\Traits\GeneratesXmlConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class ProvisionController extends Controller
{
    use GeneratesXmlConfig;

    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Provision'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Provision';
    }

    /**
     * Returns a list of provisioning from the database.
     *
     * This endpoint retrieves all provisioning records from the database. If the
     * account ID is provided, it filters the results by the given account ID.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the list of provisioning records.
     */
    public function index()
    {
        $account_id = auth()->user()->account_id;

        // Retrieve all provisioning from the database
        $query = Provisioning::query();

        if ($account_id) {
            $query->where('account_id', $account_id);
        }

        // Execute the query to fetch provisioning
        $provisioning = $query->orderBy('id', 'desc')->first();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $provisioning,
            'message' => 'Successfully fetched all provisioning'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $action = 'create';

        // Retrieve the authenticated user's ID
        $userId = $request->user()->id;

        // Retrieve the authenticated account's ID
        $account_id = $request->user()->account_id;

        $request->merge(['account_id' => $account_id]);

        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'exists:accounts,id',
                'serial_number' => 'required|regex:/^[a-zA-Z0-9]+$/', // Alphanumeric validation
                'address' => [
                    'required',
                    Rule::exists('extensions', 'id')->where(function ($query) use ($request) {
                        $query->where('account_id', $request->account_id);
                    }),
                ],
                'transport' => 'required|string|in:TCPpreferred,UDPOnly,TLS,TCPOnly',
                'port' => 'required|digits_between:3,4',
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

        $ext = Extension::find($request->address);

        $validated = [
            'account_id' => $request->account_id,
            'serial_number' => $request->serial_number,
            'server_address' => '192.168.2.225',
            'address' => $ext->extension,
            'user_id' => $ext->extension,
            'password' => $ext->password,
            'transport' => $request->transport,
            'port' => $request->port
        ];

        $match = [
            'account_id' => $account_id
        ];

        DB::beginTransaction();

        // Store the mail setting in the database
        $data = Provisioning::updateOrCreate($match, $validated);

        // Log the action
        accessLog($action, $this->type, $validated, $userId);

        $configResult = $this->createCfg($request->serial_number);

        $phoneConfigResult = $this->createPhoneConfig($validated['serial_number'], $validated['server_address'], $validated['user_id'], $validated['password'], $validated['transport']);

        if (!$configResult || !$phoneConfigResult) {
            // DB::rollBack();
            $response = [
                'status' => false,
                'data' => $data,
                'message' => 'Failed to store'
            ];
            return response()->json($response, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        DB::commit();

        // Prepare a success response with the stored mail setting data
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully stored'
        ];

        // Return a JSON response with the success message and stored mail setting data
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Update a provisioning record by id.
     *
     * This API endpoint accepts a PUT/PATCH request with the id parameter.
     * It returns a JSON response with HTTP status code 200 (OK)
     * containing the provisioning record object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the provision by ID
        $provisioning = Provisioning::find($id);

        // Check if the provisioning exists
        if (!$provisioning) {
            // If the provisioning is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Provisioning not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $request->merge(['account_id' => $provisioning->account_id]);

        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'exists:accounts,id',
                'serial_number' => 'string|regex:/^[a-zA-Z0-9]+$/', // Alphanumeric validation
                'address' => [
                    Rule::exists('extensions', 'id')->where(function ($query) use ($request) {
                        $query->where('account_id', $request->account_id);
                    }),
                ],
                'transport' => 'string|in:TCPpreferred,UDPOnly,TLS,TCPOnly',
                'port' => 'digits_between:3,4',
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

        $ext = Extension::find($request->address);

        // Retrieve the validated input
        $validated = [
            'account_id' => $request->account_id,
            'serial_number' => $request->serial_number,
            'server_address' => '192.168.2.225',
            'address' => $ext->extension,
            'user_id' => $ext->extension,
            'password' => $ext->password,
            'transport' => $request->transport,
            'port' => $request->port
        ];

        // Call the compareValues function to generate a formatted description based on the gateway and validated data
        $formattedDescription = compareValues($provisioning, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Begin a database transaction
        DB::beginTransaction();

        // Log the action
        accessLog($action, $type, $formattedDescription, $userId);

        // delete all config files
        $this->deleteConfig($provisioning->serial_number);

        // Update the gateway with the validated data
        $provisioning->update($validated);

        $configResult = $this->createCfg($request->serial_number);

        $phoneConfigResult = $this->createPhoneConfig($validated['serial_number'], $validated['server_address'], $validated['user_id'], $validated['password'], $validated['transport']);

        if (!$configResult || !$phoneConfigResult) {
            // DB::rollBack();
            $response = [
                'status' => false,
                'data' => $validated,
                'message' => 'Failed to store'
            ];
            return response()->json($response, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Commit the database transaction
        DB::commit();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $provisioning,
            'message' => 'Successfully updated provisioning',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Returns a provisioning record by id.
     *
     * This API endpoint accepts a GET request with the id parameter.
     * It returns a JSON response with HTTP status code 200 (OK)
     * containing the provisioning record object.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Fetch the provisioning by id
        $provisioning = Provisioning::find($id);

        if (!$provisioning) {
            // If the provisioning is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Provisioning not found'
            ];
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => ($provisioning) ? $provisioning : '',
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response containing the provisioning record
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Delete a provisioning record by id.
     *
     * This API endpoint accepts a DELETE request with the id parameter.
     * It returns a JSON response with HTTP status code 200 (OK)
     * containing a success message.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $userId = auth()->user()->id;
        $account_id = auth()->user()->account_id;

        $action = 'delete';
        $type = $this->type;

        $provisioning = Provisioning::find($id);

        if ($provisioning->account_id != $account_id) {
            $response = [
                'status' => false,
                'error' => 'Provisioning not found'
            ];
            // If the provisioning is not found, return a 404 Not Found response
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        if (!$provisioning) {
            $response = [
                'status' => false,
                'error' => 'Provisioning not found'
            ];
            // If the provisioning is not found, return a 404 Not Found response
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        DB::beginTransaction();

        // delete all config files
        $this->deleteConfig($provisioning->serial_number);

        // Delete the provisioning
        $provisioning->delete();

        // Generate UID and attach it to the validated data
        accessLog($action, $type, $provisioning, $userId);

        DB::commit();

        // Prepare a success message
        $response = [
            'status' => true,
            'message' => 'Successfully deleted provisioning'
        ];

        // Return a JSON response with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    public function createCfg($serialNumber)
    {
        $parentDir = 'provisions';

        // Create the directory
        if (!Storage::exists($parentDir)) {
            Storage::makeDirectory($parentDir);
        }

        // Define the XML content
        $xmlContent = '<?xml version="1.0" standalone="yes"?>
            <APPLICATION APP_FILE_PATH_EdgeB20="sip.ld" CONFIG_FILES_EdgeB20="' . $serialNumber . '-registration.cfg, phone1.cfg, sip.cfg, custom.cfg" MISC_FILES="" LOG_FILE_DIRECTORY="logs" OVERRIDES_DIRECTORY="overrides" CONTACTS_DIRECTORY="contacts" LICENSE_DIRECTORY="">
            </APPLICATION>';

        // Write the XML content to a .cfg file

        $directoryName = $serialNumber;
        $fileName = $parentDir . '/' . $directoryName . '/' . $serialNumber . '.cfg';

        // Create the directory
        if (!Storage::exists($serialNumber)) {
            Storage::makeDirectory($serialNumber);
        }

        Storage::disk('local')->put($fileName, $xmlContent);

        return true;
    }

    public function createPhoneConfig($serialNumber, $serverAddress, $userId, $userPassword, $transport)
    {
        $data = [
            'serial' => $serialNumber,
            'serverAddress' => $serverAddress,
            'userId' => $userId,
            'userPassword' => $userPassword
        ];

        // Define the XML content with parameters
        $xmlContent = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
            <PHONE_CONFIG>
            <config
                reg.1.server.1.address="' . $serverAddress . '"
                reg.1.address="' . $userId . '"
                reg.1.auth.userId="' . $userId . '"
                reg.1.auth.password="' . $userPassword . '"
                reg.1.server.1.transport="' . $transport . '"
            >
            </config>
            <OBiParameterList
                VoiceService.1.VoiceProfile.6.SIP.ProxyServer="i3.voip.polycom.com"
                VoiceService.1.VoiceProfile.6.SIP.ProxyServerPort="5066"
                VoiceService.1.VoiceProfile.6.SIP.ProxyServerTransport="TLS"
                VoiceService.1.VoiceProfile.1.Line.6.X_LineName="i3"
                VoiceService.1.VoiceProfile.1.Line.6.X_ServProvProfile="F"
            >
            </OBiParameterList>
            </PHONE_CONFIG>';

        // Write the XML content to a .cfg file

        $parentDir = 'provisions';

        // Create the directory
        if (!Storage::exists($parentDir)) {
            Storage::makeDirectory($parentDir);
        }

        $directoryName = $serialNumber;
        $fileName = $parentDir . '/' . $directoryName . '/' . $serialNumber . '-registration.cfg';

        // Create the directory
        if (!Storage::exists($serialNumber)) {
            Storage::makeDirectory($serialNumber);
        }

        Storage::disk('local')->put($fileName, $xmlContent);

        return true;
    }

    protected function deleteConfig($serialNumber)
    {
        $directoryName = $serialNumber;

        if (Storage::exists($directoryName)) {
            Storage::deleteDirectory($directoryName);
            return true;
        } else {
            return false;
        }
    }

    public function deviceResponse($file)
    {
        $userAgent = request()->header('User-Agent');

        Log::info($userAgent);

        $ipAddress = request()->ip();

        $basename = pathinfo($file, PATHINFO_FILENAME);

        // Remove all '0's from the string
        $onlyZeros = str_replace('0', '', $basename);

        if (empty($onlyZeros)) {
            return response()->json(['success' => false], 402);
        }

        // Extract the serial number
        preg_match('/\((.*?)\)/', $userAgent, $serialMatch);
        $serialNumber = $serialMatch[1] ?? null; // Will be '482567391BB0'

        // Extract the model name
        preg_match('/^(.*?)-/', $userAgent, $modelMatch);
        $modelName = $modelMatch[1] ?? null; // Will be 'Poly/PolyEdgeB20'

        // Extract the version number
        preg_match('/-(\d+\.\d+\.\d+\.\d+)/', $userAgent, $versionMatch);
        $versionNumber = $versionMatch[1] ?? null; // Will be '1.1.0.6355'

        Log::info($userAgent);


        $brand = getBrandName($modelName);
        $modelNumber = deviceModelFormat($modelName);

        // Check if the file exists
        $generatedPath = 'provision-templates/' . $brand . '/' . $modelNumber . '/{$mac}-registration.cfg';

        $generatedPath2 = 'provision-templates/' . $brand . '/' . $modelNumber . '/{$mac}.cfg';

        $check = Provisioning::where('serial_number', $serialNumber)->first();

        if ($check) {

            $account_id = $check->account_id;

            $validated = [
                'account_id' => $account_id,
                'brand_model' => $modelName,
                'serial_number' => $serialNumber,
                'firmware_version' => $versionNumber,
            ];

            $match = [
                'account_id' => $account_id
            ];

            // Check if the device is already provisioned
            $device = Device::where(['account_id' => $account_id, 'serial_number' => $serialNumber])->first();

            if ($device) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Device already provisioned',
                ]);
            }

            DB::beginTransaction();

            // Store the mail setting in the database
            Device::updateOrCreate($match, $validated);

            DB::commit();

            if (Storage::disk('local')->exists($generatedPath2)) {
                Log::info('File exists');
                // Read the file contents
                $fileContents2 = Storage::disk('local')->get($generatedPath2);

                $fileContents2 = str_replace(
                    ['{$mac}'],
                    [$serialNumber],
                    $fileContents2
                );

                header("Content-Type: text/xml; charset=utf-8");

                // Return the modified content as XML
                echo $fileContents2;

                Log::info('File contents2: ' . $fileContents2);
            }


            if (Storage::disk('local')->exists($generatedPath)) {
                Log::info('File exists');
                // Read the file contents
                $fileContents = Storage::disk('local')->get($generatedPath);

                // Replace the placeholders with actual values
                $serverAddress = $check->server_address; // Replace with your actual value
                $userId = $check->user_id; // Replace with your actual value
                $userPassword = $check->password; // Replace with your actual value

                $fileContents = str_replace(
                    ['{$server_address_1}', '{$user_id_1}', '{$user_password_1}'],
                    [$serverAddress, $userId, $userPassword],
                    $fileContents
                );

                // Set the appropriate headers for XML response
                // header('Content-Type: application/xml; charset=utf-8');
                header("Content-Type: text/xml; charset=utf-8");

                // Return the modified content as XML
                echo $fileContents;

                Log::info('File contents: ' . $fileContents);
            } 



            // Generate XML
            // $xmlContent = $this->generateXmlConfig($data);

            // Return the XML response
            // return response($xmlContent, 200)
            //     ->header('Content-Type', 'application/xml');
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Device is not configured properly. Please contact support.',
            ]);
        }

        // Handle the request and prepare the response
        $response = [
            'status' => 'success',
            'message' => 'Provisioning successful',
        ];

        return response()->json($response, Response::HTTP_OK);
    }
}
