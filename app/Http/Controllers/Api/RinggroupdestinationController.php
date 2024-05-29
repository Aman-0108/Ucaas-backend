<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Ring_group_destination;
use Illuminate\Support\Facades\DB;

class RinggroupdestinationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        echo 'Hello';
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $ringdestination = Ring_group_destination::find($id);
        if (!$ringdestination) {
            $response = [
                 'status' => false,
                 'error' => 'Details not found'
             ];
 
             return response()->json($response, Response::HTTP_NOT_FOUND);
         }
        $ringdestination->delete();
        $response = [
             'status' => true,
             'message' => 'Successfully Deleted Destination'
        ];
        return response()->json($response, Response::HTTP_OK);
    }
}
