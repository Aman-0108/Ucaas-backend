<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DidVendor;
use App\Models\DidOrderStatus;
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

    public function searchDidInCommio($companyId,$vendorId,$vendorName,$vendorUserName,$vendorToken,$searchType,$quantity,$npa,$rateType)
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

    public function purchaseDidInCommio($companyId,$vendorId,$didQty,$rate,$accountId,$dids)
    {
        // $completeOrder = $this->completeOrder(1,16130488,1,14642);
        // print_r($completeOrder); exit;


        $DidController = new DidVendorController();
        $vendorDataResponse = $DidController->show($vendorId);
        $datas = $vendorDataResponse->getData();
        $username = $datas->data->username;
        $password = $datas->data->token;
        
        $tnsarray = [];
        if (!empty($dids)) {
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
                    "sms"=> false,
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

            $jsondata = json_encode($issuedata);

            //print_r($issuedata); exit;

            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.thinq.com/account/'.$accountId.'/origination/order/create',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $jsondata,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic '. base64_encode("$username:$password"),
                'Content-Type: application/json'
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            //print_r($response);   exit;
            //echo $response['status'];
            $responseData = json_decode($response, true); 
            //echo $responseData['message'] ;  exit;
            if (isset($responseData['status'])) {
                if($responseData['status'] == 'created')
                {

                    //add param like order created but not Completed order as per commio
                    $ordeDetail = [
                        'account_id' => $companyId,
                        'vendor_id' => $vendorId,
                        'order_id' => $responseData['id'],
                        'status' => $responseData['status'],
                    ];
                    $ordeDetail = DidOrderStatus::create($ordeDetail);

                    if($ordeDetail){
                        //params = companyid , orderid, vendorId , commio account id
                        $completeOrder = $this->completeOrder($companyId,$responseData['id'],$vendorId,$accountId);
                        echo $completeOrder['status'];
                        if (isset($completeOrder['status']) && isset($completeOrder['type'])) 
                        {
                            //origination_order
                            if($completeOrder['status'] == 'completed')
                            {
                                if($completeOrder['type'] == 'origination_order')
                                {
                                    foreach($completeOrder['tns'] as $row)
                                    {
                                        $purchasedDid = $row['did'];
                                        //insert into database
                                    }
                                }
                            }
                        }
                        else
                        {
                            $res = [
                                'status' => false,
                                'message' => 'Order Completion Failed!Please Contact Support.',
                            ];
                            return response()->json($res, Response::HTTP_OK);
                        }

                    }
                }
                else
                {
                    $res = [
                        'status' => false,
                        'message' => $responseData['message'],
                    ];
            
                    return response()->json($res, Response::HTTP_OK);
                }
            } else {
                
                $res = [
                    'status' => false,
                    'message' => $responseData['message'],
                ];
        
                return response()->json($res, Response::HTTP_OK);
            }


        }
    }

    //params = companyid , orderid, vendorId , commio account id
    public function completeOrder($accountId,$orderId,$vendorId,$commioAccountId)
    {

        $DidController = new DidVendorController();
        $vendorDataResponse = $DidController->show($vendorId);
        $datas = $vendorDataResponse->getData();
        $username = $datas->data->username;
        $password = $datas->data->token;

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.thinq.com/account/'.$commioAccountId.'/origination/order/complete/'.$orderId,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Basic '. base64_encode("$username:$password"),
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        //echo $response;
        $responseData = json_decode($response, true); 
        return $responseData;


    }
}
