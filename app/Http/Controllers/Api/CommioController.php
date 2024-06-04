<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CommioController extends Controller
{
    
    public function searchDid_commio(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'searchType' => 'required',
                'quantity' => 'required|integer',
                'npa' => 'required|integer',
                
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


            //$searchType = !empty($request->searchType) ? $request->searchType : "";
            $searchType = $request->searchType; //tollfree  //domestic
            $quantity   = $request->quantity;
            $npa   = $request->npa; //855 
           

            $authToken = 'b0297d1b8199f7516500a3544bfde67bf13748a4';
            $username = 'Natty';
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.thinq.com/inbound/get-numbers?searchType='.$searchType.'&searchBy=&quantity='.$quantity.'&contiguous=false&npa='.$npa.'&related=true',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                //'Authorization: Basic '.$authToken,
                'Authorization: Basic '. base64_encode("$username:$authToken"),
                'Content-Type: application/json'
            ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            //echo $response; 
           
            $arr = json_decode($response);
           
            if (isset($arr->dids) && empty($arr->dids)) 
            {
                
                $res = [
                    'status' => false,
                    'message' => 'Data Not Available',
                    'data' => [],
                    
                ];
        
                return response()->json($res, Response::HTTP_OK);
            }
            

            foreach ($arr->dids as $numbersData) {
                $resp[] = [
                    "carrierName" => $numbersData->carrierName,
                    "didSummary" => $numbersData->didSummary,
                    "id" => $numbersData->id,
                    "npanxx" => $numbersData->npanxx,
                    "ratecenter" => $numbersData->ratecenter,
                    "thinqTier" => $numbersData->thinqTier,
                    "tollfreePrefix" => $numbersData->tollfreePrefix,
                    "match" => $numbersData->match,
                    "currency" => "USD",
                    "price" => 05.50,
                ];
            }

            $res = [
                'status' => true,
                'message' => 'Please Select Available TFN',
                'data' => ($resp) ? $resp : '',
                
            ];
    
            return response()->json($res, Response::HTTP_OK);
    
            

    }

    public function searchDidInCommio($vendorId,$vendorName,$vendorUserName,$vendorToken,$searchType,$quantity,$npa,$rateType)
    {
       
            $authToken = $vendorToken;
            $username = $vendorUserName;
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.thinq.com/inbound/get-numbers?searchType='.$searchType.'&searchBy=&quantity='.$quantity.'&contiguous=false&npa='.$npa.'&related=true',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                //'Authorization: Basic '.$authToken,
                'Authorization: Basic '. base64_encode("$username:$authToken"),
                'Content-Type: application/json'
            ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            //echo $response; 
           
            $arr = json_decode($response);
           
            if (isset($arr->dids) && empty($arr->dids)) 
            {
                
                $res = [
                    'status' => false,
                    'message' => 'Data Not Available',
                    'data' => [],
                    
                ];
        
                return response()->json($res, Response::HTTP_OK);
            }

            $DidrateController = new DidRateController();
            $vendorDataResponse = $DidrateController->show($vendorId,$rateType);
            $functionDataObject = $vendorDataResponse->getData();
            //$functionDataObject->data->rate; 

            foreach ($arr->dids as $numbersData) {
                $resp[] = [
                    "vendorId" => $vendorId,
                    "carrierName" => $numbersData->carrierName,
                    "didSummary" => $numbersData->didSummary,
                    "id" => $numbersData->id,
                    "npanxx" => $numbersData->npanxx,
                    "ratecenter" => $numbersData->ratecenter,
                    "thinqTier" => $numbersData->thinqTier,
                    "tollfreePrefix" => $numbersData->tollfreePrefix,
                    "match" => $numbersData->match,
                    "currency" => "USD",
                    "price" => !empty($functionDataObject->data->rate) ? $functionDataObject->data->rate : "",
                ];
            }

            $res = [
                'status' => true,
                'message' => 'Please Select Available TFN',
                'data' => ($resp) ? $resp : '',
                
            ];
    
            return response()->json($res, Response::HTTP_OK);
    
            

    }

    public function purchaseDidInCommio($vendorId,$didQty,$rate,$accountId,$dids)
    {

        // Prepare the response data
       

        //echo '<pre>'; print_r($issuedata); exit;
       
        $tnsarray = [];
        if (!empty($dids)) {
            //echo count($request->dids); exit;
            $inputs = $dids;
            foreach ($inputs as $input) 
            {
                $tnsarray[] = array(
                    "caller_id"=>null,
                    "caller_id"=> null,
                    "account_location_id"=> null,
                    "sms_routing_profile_id"=> null,
                    "route_id"=> null,
                    "features" => array(
                    "cnam"=> false,
                    "sms"=> true,
                    "e911"=> false
                    ),
                    "did"=> $input['dids']
                );
            }

            $issuedata = array(
                "order" => [
                    "tns" => $tnsarray,
                    "blocks" => []
                ]
            );

            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.thinq.com/account/{{account_id}}/origination/order/create',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $issuedata,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic <auth string>',
                'Content-Type: application/json'
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            echo $response;
            
        }
    }
}
