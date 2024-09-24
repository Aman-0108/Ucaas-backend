<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver;
use Illuminate\Http\JsonResponse;

class S3Controller extends Controller
{
    /**
     * Generate a presigned URL for an S3 object.
     *
     * @param  string  $fileName
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPresignedUrl(Request $request)
    {
        // Get the S3 client
        $s3Client = Storage::disk('s3')->getDriver()->getAdapter()->getClient();

        $fileName = $request->src;

        // Create a command to get the object
        $cmd = $s3Client->getCommand('GetObject', [
            'Bucket' => env('AWS_BUCKET'),
            'Key' => $fileName,
            'ResponseContentType' => 'audio/wav',
        ]);

        // Create a presigned request
        $request = $s3Client->createPresignedRequest($cmd, '+60 minutes'); // URL valid for 60 minutes

        // Get the presigned URL
        $presignedUrl = (string) $request->getUri();

        return response()->json([
            'status' => true,
            'url' => $presignedUrl,
            'message' => 'Successfully generated.',
        ]);
    }

    /**
     * Converts a PNG image to a TIFF image and saves it to storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function imageToTiff(Request $request): JsonResponse
    {
        $source = $request->src;

        // Create new manager instance with desired driver
        $manager = new ImageManager(new Driver());

        try {
            // Parse the S3 URL to get the file path
            $filePath = parse_url($source, PHP_URL_PATH);

            // Check if the file exists on S3
            if (!Storage::disk('s3')->exists($filePath)) {
                return response()->json([
                    'status' => false,
                    'error' => "File does not exist: " . $source
                ], 404);
            }

            // Read the file data from S3
            $fileData = Storage::disk('s3')->get($filePath);
            $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            // Initialize image variable
            $image = null;

            // Handle based on file extension
            if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                // Create an image instance from the data
                $image = $manager->read($fileData);
            } else {
                return response()->json([
                    'status' => false,
                    'error' => "Unsupported file type: " . $fileExtension
                ], 400);
            }

            // Encode TIFF format
            if ($image) {
                try {
                    $encoded = $image->toTiff();

                    // Get the encoded image data
                    $tiffData = (string) $encoded;

                    // Save to S3
                    $timestamp = date('YmdHis');
                    Storage::disk('s3')->put("efax/{$timestamp}.tiff", $tiffData);

                    // Return success response
                    return response()->json([
                        'status' => true,
                        'message' => 'File successfully converted and saved.'
                    ], 201);
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => false,
                        'error' => "Error encoding image: " . $e->getMessage()
                    ], 400);
                }
            }
        } catch (\Intervention\Image\Exceptions\DecoderException $e) {
            return response()->json([
                'status' => false,
                'error' => "Error decoding image: " . $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => "Error reading file: " . $e->getMessage()
            ], 400);
        }

        // Fallback response in case no return happened
        return response()->json([
            'status' => false,
            'error' => 'An unexpected error occurred.'
        ], 500);
    }
}
