<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Timezone;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class TimezoneController extends Controller
{
    /**
     * Display a listing of the Timezones.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $timezones = Timezone::query();

        if ($request->has('account')) {
            $timezones->where('account_id', $request->account);
        }

        $timezones = $timezones->get();

        $response = [
            'status' => true,
            'data' => $timezones,
            'message' => 'Successfully fetched all timezones'
        ];

        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Display the specified Timezone.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $timezone = Timezone::find($id);

        if (!$timezone) {
            $response = [
                'status' => false,
                'error' => 'Timezone not found'
            ];
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $response = [
            'status' => true,
            'data' => ($timezone) ? $timezone : '',
            'message' => 'Successfully fetched'
        ];

        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created Timezone in database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'uid_no' => 'required|exists:uids,uid_no',
                'name' => 'required|unique:timezones,name',
                'value' => 'required',
                'created_by' => 'required|exists:users,id',
            ]
        );

        if ($validator->fails()) {
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input...
        $validated = $validator->validated();

        $data = Timezone::create($validated);

        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully stored'
        ];

        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Update the specified Timezone.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $timezone = Timezone::find($id);

        if (!$timezone) {
            $response = [
                'status' => false,
                'error' => 'Timezone not found'
            ];
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'uid_no' => 'exists:uids,uid_no',
                'name' => 'unique:timezones,name,' .$id,
                // 'value' => 'required',
                'created_by' => 'exists:users,id',
            ]
        );

        if ($validator->fails()) {
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input...
        $validated = $validator->validated();

        $timezone->update($validated);

        $response = [
            'status' => true,
            'data' => $timezone,
            'message' => 'Successfully updated Timezone',
        ];

        return response()->json($response);
    }

    /**
     * Destroy the specified Timezone.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $timezone = Timezone::find($id);

        if (!$timezone) {
            $response = [
                'status' => false,
                'error' => 'Timezone not found'
            ];
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $timezone->delete();

        $response = [
            'status' => true,
            'message' => 'Successfully deleted timezone'
        ];

        return response()->json($response, Response::HTTP_OK);
    }
}
