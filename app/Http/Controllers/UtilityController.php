<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UtilityController extends Controller
{
    public function checkMailExchangeserver(Request $request)
    {
        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required|email',
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

        if ($this->is_valid_email($request->email)) {
            // Prepare a success response with the stored account data
            $response = [
                'status' => true,
                'data' => $request->email,
                'message' => 'Mail exchange is on'
            ];

            // Return a JSON response with the success message and stored account data
            return response()->json($response, Response::HTTP_CREATED);
        } else {
            // Prepare a success response with the stored account data
            $response = [
                'status' => false,
                'message' => 'Mail exchange is not available'
            ];

            // Return a JSON response with the success message and stored account data
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }
    }

    public function is_valid_email($email)
    {
        // First, perform a basic syntax check
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Next, extract the domain from the email address
        list($user, $domain) = explode('@', $email);

        return (checkdnsrr($domain, 'MX')) ? true : false;
    }

    // Get IP address from host
    public function getIpFromHost(Request $request)
    {
        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'host' => 'required|string',
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

        $ip = gethostbyname($request->host);

        // Prepare a success response with the stored account data
        $response = [
            'status' => true,
            'data' => $ip,
            'message' => 'Successfully stored'
        ];

        // Return a JSON response with the success message and stored account data
        return response()->json($response, Response::HTTP_CREATED);
    }

    public function getAccountcredentials($account_id = 7, $destination_number = '+1234567890')
    {
        // check_dialout_billing
        $result = DB::select(DB::raw("CALL check_dialout_billing($account_id, '$destination_number')"));
        Log::info($result);
        return $result;

    }
}
