<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;

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
}
