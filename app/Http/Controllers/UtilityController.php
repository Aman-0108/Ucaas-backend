<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UtilityController extends Controller
{
    /**
     * Check if the mail exchange server is available for a given email address.
     *
     * This method validates the incoming request data and checks if the mail exchange server is available for the given email address.
     * If the validation fails, it returns a 403 Forbidden response with validation errors.
     * If the mail exchange server is available, it returns a 201 Created response with the email address and a success message.
     * If the mail exchange server is not available, it returns a 404 Not Found response with a message indicating that the mail exchange server is not available.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the email address to check
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
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

    /**
     * Validates an email address by checking its syntax and verifying the domain's mail server.
     *
     * This function first performs a basic syntax check on the provided email address to ensure it complies with standard email formatting.
     * It then extracts the domain portion of the email address and checks for the existence of MX (Mail Exchange) records using DNS.
     * If MX records are found, it indicates that the domain is capable of receiving emails.
     *
     * @param string $email The email address to validate.
     * @return bool Returns true if the email address is valid and the domain has MX records, false otherwise.
     */
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

    /**
     * Returns the IP address associated with a given hostname.
     *
     * This API endpoint takes a hostname as input and returns the IP address associated with it.
     * If the input is invalid, it returns a 403 Forbidden response with a JSON object describing the validation errors.
     * If the hostname does not have an associated IP address, it returns a 404 Not Found response.
     * If the hostname has an associated IP address, it returns a 201 Created response with a JSON object containing the IP address.
     *
     * @param  \Illuminate\Http\Request  $request The incoming request object containing the hostname to look up.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the status of the operation.
     */
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
}
