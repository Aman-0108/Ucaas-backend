<?php

use App\Models\Uid;
use Illuminate\Support\Str;
use Illuminate\Http\Response;

/**
 * Generates a unique identifier (UUID) and stores it along with relevant data in the database.
 *
 * @param string $action The action performed (e.g., 'create', 'update', 'delete').
 * @param string $type The type of entity or object being acted upon (e.g., 'user', 'post').
 * @param mixed|null $requestData The request data associated with the action (optional).
 * @param int|null $userId The ID of the user performing the action (optional).
 * @return string The generated UUID.
 */
if (!function_exists('createUid')) {
    function createUid($action, $type, $requestData = null, $userId = null)
    {
        // Generate a UUID
        $uuid = Str::uuid()->toString();

        // If action is not 'update', convert request data to JSON
        if ($action !== 'update') {
            $requestData = json_encode($requestData);
        }

        // Format the action
        $formattedAction = strtolower($type) . '_' . $action . '';

        // Prepare data for storing in the database
        $uidData = [
            "uid_no" => $uuid,
            "date" => date("Y-m-d"),
            "time" => date("H:m:i"),
            "server_timezone" => date("Y-m-d H:i:s"),
            'user_id' => $userId,
            'action' => $formattedAction,
            "description" => $requestData,
        ];

        // Store data in the database
        Uid::create($uidData);

        return $uuid; // Return the generated UUID
    }
}

/**
 * Compares the old and new values to generate a formatted description of changes.
 *
 * @param  mixed $oldValue The old value to compare.
 * @param  mixed $newValue The new value to compare.
 * @return string Returns a formatted description of the changes.
 */
if (!function_exists('compareValues')) {
    function compareValues($oldValue, $newValue)
    {
        // Convert the old value to an associative array for comparison
        $oldValues = json_decode(json_encode($oldValue), true);

        // Calculate the differences between the validated data and the old values
        $diff = array_diff_assoc($newValue, $oldValues);

        // Initialize the formatted description of changes
        $formattedDescription = '';

        // Check if there are any differences
        if (!empty($diff)) {
            // If differences exist, iterate through each difference
            foreach ($diff as $key => $item) {
                // Append each difference to the formatted description
                $formattedDescription .= $key . ' updated From: ' . $oldValue[$key] . ' To: ' . $item . '' . "\n";
            }
        }

        // Return the formatted description of changes
        return $formattedDescription;
    }
}

/**
 * Checks if FreeSWITCH server is disconnected.
 * If disconnected, prepares and returns a JSON response indicating the failure.
 *
 * @return \Illuminate\Http\JsonResponse
 */
if (!function_exists('freeSwitchDisconnected')) {
    function freeSwitchDisconnected()
    {
        // Prepare the response data
        $response = [
            'status' => false, // Indicates the success status of the request
            'data' => [], // Contains the fetched extensions
            'message' => 'Freeswitch server is not connected'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}

/**
 * Formats the data related to a channel hangup completion event.
 *
 * This function extracts specific data fields from the provided array
 * related to a channel hangup completion event and formats them into a response array.
 *
 * @param array $data The data related to the channel hangup completion event.
 * @return array The formatted response containing relevant data fields.
 */
if (!function_exists('channelHangupCompleteDataFormat')) {
    function channelHangupCompleteDataFormat($data)
    {
        $response = [];

        // variable_flow_billsec,variable_dialed_domain,variable_dialed_user

        if (array_key_exists("Call-Direction", $data)) {
            $response['Call-Direction'] = $data['Call-Direction'];
        }

        if (array_key_exists("Hangup-Cause", $data)) {
            $response['Hangup-Cause'] = $data['Hangup-Cause'];
        }

        if (array_key_exists("variable_DIALSTATUS", $data)) {
            $response['variable_DIALSTATUS'] = $data['variable_DIALSTATUS'];
        }

        if (array_key_exists("Caller-Orig-Caller-ID-Name", $data)) {
            $response['Caller-Orig-Caller-ID-Name'] = $data['Caller-Orig-Caller-ID-Name'];
        }

        if (array_key_exists("Caller-Orig-Caller-ID-Number", $data)) {
            $response['Caller-Orig-Caller-ID-Number'] = $data['Caller-Orig-Caller-ID-Number'];
        }

        if (array_key_exists("Channel-Call-State", $data)) {
            $response['Channel-Call-State'] = $data['Channel-Call-State'];
        }

        if (array_key_exists("Caller-Caller-ID-Number", $data)) {
            $response['Caller-Caller-ID-Number'] = $data['Caller-Caller-ID-Number'];
        }

        if (array_key_exists("Caller-Callee-ID-Number", $data)) {
            $response['Caller-Callee-ID-Number'] = $data['Caller-Callee-ID-Number'];
        }

        if (array_key_exists("variable_callgroup", $data)) {
            $response['variable_callgroup'] = $data['variable_callgroup'];
        }

        // 
        if (array_key_exists("variable_sip_from_user", $data)) {
            $response['variable_sip_from_user'] = $data['variable_sip_from_user'];
        }

        if (array_key_exists("variable_sip_to_user", $data)) {
            $response['variable_sip_to_user'] = $data['variable_sip_to_user'];
        }

        if (array_key_exists("variable_sip_from_host", $data)) {
            $response['variable_sip_from_host'] = $data['variable_sip_from_host'];
        }

        if (array_key_exists("variable_sip_to_host", $data)) {
            $response['variable_sip_to_host'] = $data['variable_sip_to_host'];
        }

        // Log::info(json_encode($eventData, true));

        if (array_key_exists("variable_sip_call_id", $data)) {
            $response['variable_sip_call_id'] = $data['variable_sip_call_id'];
        }

        if (array_key_exists("variable_start_stamp", $data)) {
            $response['variable_start_stamp'] = $data['variable_start_stamp'];
        }

        if (array_key_exists("variable_answer_stamp", $data)) {
            $response['variable_answer_stamp'] = $data['variable_answer_stamp'];
        }

        if (array_key_exists("variable_end_stamp", $data)) {
            $response['variable_end_stamp'] = $data['variable_end_stamp'];
        }

        if (array_key_exists("variable_duration", $data)) {
            $response['variable_duration'] = $data['variable_duration'];
        }

        // 
        if (array_key_exists("variable_billmsec", $data)) {
            $response['variable_billmsec'] = $data['variable_billmsec'];
        }

        if (array_key_exists("variable_billsec", $data)) {
            $response['variable_billsec'] = $data['variable_billsec'];
        }

        if (array_key_exists("variable_answermsec", $data)) {
            $response['variable_answermsec'] = $data['variable_answermsec'];
        }

        if (array_key_exists("variable_answersec", $data)) {
            $response['variable_answersec'] = $data['variable_answersec'];
        }

        if (array_key_exists("variable_waitmsec", $data)) {
            $response['variable_waitmsec'] = $data['variable_waitmsec'];
        }

        if (array_key_exists("variable_waitsec", $data)) {
            $response['variable_waitsec'] = $data['variable_waitsec'];
        }

        if (array_key_exists("variable_progressmsec", $data)) {
            $response['variable_progressmsec'] = $data['variable_progressmsec'];
        }

        if (array_key_exists("variable_progresssec", $data)) {
            $response['variable_progresssec'] = $data['variable_progresssec'];
        }

        // 
        if (array_key_exists("variable_record_stereo", $data)) {
            $response['variable_record_stereo'] = $data['variable_record_stereo'];
        }

        if (array_key_exists("variable_originate_early_media", $data)) {
            $response['variable_originate_early_media'] = $data['variable_originate_early_media'];
        }

        if (array_key_exists("variable_dialed_extension", $data)) {
            $response['variable_dialed_extension'] = $data['variable_dialed_extension'];
        }

        if (array_key_exists("variable_mduration", $data)) {
            $response['variable_mduration'] = $data['variable_mduration'];
        }

        if (array_key_exists("variable_rtp_audio_in_quality_percentage", $data)) {
            $response['variable_rtp_audio_in_quality_percentage'] = $data['variable_rtp_audio_in_quality_percentage'];
        }

        if (array_key_exists("variable_progress_mediasec", $data)) {
            $response['variable_progress_mediasec'] = $data['variable_progress_mediasec'];
        }

        if (array_key_exists("Caller-Network-Addr", $data)) {
            $response['Caller-Network-Addr'] = $data['Caller-Network-Addr'];
        }

        if (array_key_exists("Other-Leg-Network-Addr", $data)) {
            $response['Other-Leg-Network-Addr'] = $data['Other-Leg-Network-Addr'];
        }

        return $response;
    }
}

/**
 * Generate a temporary password.
 *
 * This function generates a random temporary password of a specified length.
 *
 * @param int $length The length of the temporary password (default: 10)
 * @return string The generated temporary password
 */
if (!function_exists('generateTemporaryPassword')) {
    function generateTemporaryPassword($length = 10)
    {
        // Define a pool of characters and numbers
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        // Generate a temporary password
        $temporaryPassword = '';
        $max = strlen($pool) - 1;
        for ($i = 0; $i < $length; $i++) {
            $temporaryPassword .= $pool[random_int(0, $max)];
        }

        return $temporaryPassword;
    }
}

/**
 * Validates an email address.
 *
 * @param string $email The email address to validate.
 * @return bool True if the email address is valid, false otherwise.
 */
if (!function_exists('is_valid_email')) {
    function is_valid_email($email)
    {
        // First, perform a basic syntax check
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Next, extract the domain from the email address
        list($user, $domain) = explode('@', $email);

        // Use the 'checkdnsrr' function to check if the domain has MX records
        // This helps to verify if the domain has a valid mail server
        // If MX records exist, it indicates that the domain is capable of receiving emails
        return (checkdnsrr($domain, 'MX')) ? true : false;
    }
}

/**
 * Helper function to generate consistent JSON responses.
 *
 * @param string $type       Type of response ('error' or 'success').
 * @param string $status     Status message for the response.
 * @param string $message    Message describing the response.
 * @param int    $statusCode HTTP status code for the response.
 * @param mixed  $data       Additional data to include in the response (optional).
 * @return \Illuminate\Http\JsonResponse JSON response.
 */
if (!function_exists('responseHelper')) {
    function responseHelper($type, $status, $message, $statusCode, $data = null)
    {
        // Initialize the response array with status and message
        $response = [
            'status' => $status,
            'message' => $message,
        ];

        // Add data to the response if provided and if the response type is 'success'
        if ($type === config('enums.RESPONSE.SUCCESS') && isset($data)) {
            $response['data'] = $data;
        }

        if ($type === config('enums.RESPONSE.ERROR')) {
            // If the type of response is 'error', construct the error response structure
            $response['error'] = $message;
            unset($response['message']);
        }

        // Return the JSON response with the constructed response array and HTTP status code
        return response()->json($response, $statusCode);
    }
}

/**
 * Masks all but the last 4 digits of a credit card number with asterisks.
 *
 * @param string $creditCardNumber The credit card number to be masked.
 * @return string The masked credit card number with asterisks, keeping the last 4 digits visible.
 */
if (!function_exists('maskCreditCard')) {
    function maskCreditCard($creditCardNumber)
    {
        // Calculate the length of the credit card number
        $length = strlen($creditCardNumber);

        // Mask all characters except the last 4 with '*'
        $masked = str_repeat('*', $length - 4) . substr($creditCardNumber, -4);

        // Return the masked credit card number
        return $masked;
    }
}

if (!function_exists('commonServerError')) {
    function commonServerError()
    {
        $type = config('enums.RESPONSE.ERROR'); // Response type (error)
        $status = false; // Operation status (failed)
        $msg = 'Something went wrong.'; // Detailed error messages

        return responseHelper($type, $status, $msg, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
