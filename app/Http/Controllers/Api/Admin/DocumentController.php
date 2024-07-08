<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Document'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Document';
    }

    /**
     * Display a listing of the resource.
     *
     * This method fetches all documents from the database,
     * orders them by their IDs in ascending order,
     * and returns a JSON response containing the fetched documents.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Start building the query to fetch documents
        $documents = Document::query();

        // Execute the query to fetch documents and order them by ID in ascending order
        $documents = $documents->orderBy('id', 'asc')->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $documents,
            'message' => 'Successfully fetched all documents'
        ];

        // Return a JSON response containing the list of documents
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Display the specified document.
     *
     * This method fetches a specific document by its ID,
     * checks if the document exists, and returns a JSON response
     * with either the document details or an error message if not found.
     *
     * @param  int  $id  The ID of the document to fetch
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // Find the Document with the given ID
        $document = Document::find($id);

        // Check if the document exists
        if (!$document) {
            // If document is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Document not found'
            ];
            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare success response with Document details
        $response = [
            'status' => true,
            'data' => $document, // Include document details if found
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response containing the document details
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created document.
     *
     * This method validates the incoming request data, creates a new document record
     * in the database if validation passes, and returns a JSON response indicating
     * success or failure along with appropriate status codes.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming request object
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Validate the request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|unique:documents,name',
                'status' => 'required|in:active,inactive',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, prepare error response with validation errors
            $response = [
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ];

            // Return a JSON response with validation errors and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Begin a database transaction
        DB::beginTransaction();

        try {
            // Create a new document record in the database
            $document = Document::create($validated);

            // Commit the database transaction
            DB::commit();

            // Prepare success response
            $response = [
                'status' => true,
                'data' => $document,
                'message' => 'Successfully stored'
            ];

            // Return a JSON response indicating successful storage and 201 status code
            return response()->json($response, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            // If an exception occurs, rollback the transaction
            DB::rollback();

            // Prepare error response for unexpected errors
            $response = [
                'status' => false,
                'message' => 'Failed to store the document'
            ];

            // Return a JSON response indicating failure and 500 status code
            return response()->json($response, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified document in storage.
     *
     * This method updates an existing document in the database with the given ID,
     * validates the incoming request data, and returns a JSON response indicating
     * success or failure along with appropriate status codes.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming request object
     * @param  int  $id  The ID of the document to update
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the document with the given ID
        $document = Document::find($id);

        // Check if the document exists
        if (!$document) {
            // If document is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Document not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'string|unique:documents,name,' . $id,
                'status' => 'in:active,inactive',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a JSON response with error messages
            $response = [
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Begin a database transaction
        DB::beginTransaction();

        try {
            // Update the document record with validated data
            $document->update($validated);

            // Commit the database transaction
            DB::commit();

            // Prepare the response data
            $response = [
                'status' => true,
                'data' => $document,
                'message' => 'Successfully updated document',
            ];

            // Return a JSON response indicating successful update with response code 200 (OK)
            return response()->json($response, Response::HTTP_OK);
        } catch (\Exception $e) {
            // If an exception occurs, rollback the transaction
            DB::rollback();

            // Prepare error response for unexpected errors
            $response = [
                'status' => false,
                'message' => 'Failed to update the document'
            ];

            // Return a JSON response indicating failure and 500 status code (Internal Server Error)
            return response()->json($response, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified document from storage.
     *
     * This method deletes a document from the database based on the provided ID,
     * and returns a JSON response indicating success or failure with appropriate
     * status codes.
     *
     * @param  int  $id  The ID of the document to delete
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // Find the document by ID
        $document = Document::find($id);

        // Check if the document exists
        if (!$document) {
            // If the document is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Document not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the document
        $document->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted.'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
