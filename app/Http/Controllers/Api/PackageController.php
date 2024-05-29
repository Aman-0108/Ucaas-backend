<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    /**
     * Retrieve all packages.
     *
     * This method retrieves all packages from the database.
     * It returns a JSON response containing the list of packages.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response containing the list of packages
     */
    public function index()
    {
        // Retrieve all packages from the database
        $packages = Package::with(['features']);
        $packages = $packages->get();

        // Prepare a success response with the list of packages
        $response = [
            'status' => true,
            'data' => $packages,
            'message' => 'Successfully fetched all packages'
        ];

        // Return a JSON response with the list of packages with status(200)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Retrieve an package by ID.
     *
     * This method finds and retrieves an package based on the provided ID.
     * If the package is not found, it returns a 404 Not Found response.
     * If the package is found, it returns a JSON response containing the package data.
     *
     * @param  int  $id The ID of the package to retrieve
     * @return \Illuminate\Http\JsonResponse The JSON response containing the package data or an error message
     */
    public function show($id)
    {
        // Find the package by ID
        $package = Package::with(['features'])->where('id', $id)->first();

        // Find the package by ID
        if (!$package) {
            // If the package is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'package not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare a success response with the package data
        $response = [
            'status' => true,
            'data' => ($package) ? $package : '',
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response with the package data with status(200)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a new package.
     *
     * This method validates the incoming request data and stores a new package in the database.
     * If the validation fails, it returns a 403 Forbidden response with validation errors.
     * If the package is successfully stored, it returns a success message along with the stored package data.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the package data
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function store(Request $request)
    {
        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|unique:packages,name,NULL,id,number_of_user,' . $request->input('number_of_user'),
                'number_of_user' => 'required|string',
                'description' => 'required|string',
                'subscription_type' => 'required|in:annually,monthly',
                'regular_price' => 'required|numeric|between:0,9999999.99',
                'offer_price' => 'required|numeric|between:0,9999999.99',
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

        // Create a new package with the validated input
        $data = Package::create($validated);

        // Prepare a success response with the stored package data
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully stored'
        ];

        // Return a JSON response with the success message and stored package data
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Update an package by ID.
     *
     * This method finds and updates an package based on the provided ID and request data.
     * If the package is not found, it returns a 404 Not Found response.
     * If the validation fails, it returns a 403 Forbidden response with validation errors.
     * If the update is successful, it returns a success message along with the updated package data.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the update data
     * @param  int  $id The ID of the package to update
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function update(Request $request, $id)
    {
        // Find the package by ID
        $package = Package::find($id);

        // Check if the package exists
        if (!$package) {
            // If the package is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'package not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => [
                    'string',
                    Rule::unique('packages')->where(function ($query) use ($request) {
                        return $query->where('number_of_user', $request->input('number_of_user'));
                    })->ignore($id),
                ],
                'number_of_user' => [
                    'string'
                ],
                'description' => 'string',
                'subscription_type' => 'in:annually,monthly',
                'regular_price' => 'numeric|between:0,9999999.99',
                'offer_price' => 'numeric|between:0,9999999.99',
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

        // Update the package with the validated input
        $package->update($validated);

        // Prepare a success response with updated package data
        $response = [
            'status' => true,
            'data' => $package,
            'message' => 'Successfully updated package',
        ];

        // Return a JSON response with the success message and updated package data with status(200)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Delete an package by ID.
     *
     * This method finds and deletes an package based on the provided ID.
     * If the package is not found, it returns a 404 Not Found response.
     * If the package is successfully deleted, it returns a success message.
     *
     * @param  int  $id The ID of the package to delete
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function destroy($id)
    {
        // Find the package by ID
        $package = Package::find($id);

        // Check if the package exists
        if (!$package) {
            // If the package is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'package not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the package
        $package->delete();

        // Prepare a success message
        $response = [
            'status' => true,
            'message' => 'Successfully deleted package'
        ];

        // Return a JSON response with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
