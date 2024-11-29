<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DidDetail;
use App\Models\DidOrderStatus;
use App\Models\Account;
use App\Models\DidRouting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CommioController extends Controller
{
    public function searchDid_commio(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'searchType' => 'required',
                'quantity' => 'required|integer',
                'npa' => 'required|integer'
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

        $searchType = $request->searchType; //tollfree  //domestic
        $quantity   = $request->quantity;
        $npa   = $request->npa; //855 

        $authToken = 'b0297d1b8199f7516500a3544bfde67bf13748a4';
        $username = 'Natty';
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.thinq.com/inbound/get-numbers?searchType=' . $searchType . '&searchBy=&quantity=' . $quantity . '&contiguous=false&npa=' . $npa . '&related=true',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                //'Authorization: Basic '.$authToken,
                'Authorization: Basic ' . base64_encode("$username:$authToken"),
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $arr = json_decode($response);

        if (isset($arr->dids) && empty($arr->dids)) {
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

    public function searchDidInCommio($companyId, $vendorId, $vendorName, $vendorUserName, $vendorToken, $searchType, $quantity, $npa, $rateType)
    {
        $authToken = $vendorToken;
        $username = $vendorUserName;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.thinq.com/inbound/get-numbers?searchType=' . $searchType . '&searchBy=&quantity=' . $quantity . '&contiguous=false&npa=' . $npa . '&related=true',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic ' . base64_encode("$username:$authToken"),
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $arr = json_decode($response);

        if (isset($arr->dids) && empty($arr->dids)) {
            $res = [
                'status' => false,
                'message' => 'Data Not Available',
                'data' => [],

            ];

            return response()->json($res, Response::HTTP_OK);
        }

        $DidrateController = new DidRateController();
        $vendorDataResponse = $DidrateController->show($vendorId, $rateType);
        $functionDataObject = $vendorDataResponse->getData();
        //$functionDataObject->data->rate; 

        foreach ($arr->dids as $numbersData) {
            $resp[] = [
                "vendorId" => $vendorId,
                "carrierName" => $numbersData->carrierName,
                "vendorAccountId" => 14642,
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

    public function purchaseDidInCommio($createdBy, $companyId, $vendorId, $didQty, $rate, $accountId, $dids)
    {
        $DidController = new DidVendorController();
        $vendorDataResponse = $DidController->show($vendorId);
        $datas = $vendorDataResponse->getData();
        $username = $datas->data->username;
        $password = $datas->data->token;

        $tnsarray = [];
        if (!empty($dids)) {
            $inputs = $dids;

            foreach ($inputs as $input) {
                $tnsarray[] = array(
                    "caller_id" => null,
                    "caller_id" => null,
                    "account_location_id" => null,
                    "sms_routing_profile_id" => null,
                    "route_id" => null,
                    "features" => array(
                        "cnam" => false,
                        "sms" => false,
                        "e911" => false
                    ),
                    "did" => $input['dids']
                );
            }

            $issuedata = array(
                "order" => [
                    "tns" => $tnsarray,
                    "blocks" => []
                ]
            );

            $jsondata = json_encode($issuedata);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.thinq.com/account/' . $accountId . '/origination/order/create',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $jsondata,
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Basic ' . base64_encode("$username:$password"),
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            $responseData = json_decode($response, true);

            if (isset($responseData['status']) && $responseData['status'] == 'created') {

                //add param like order created but not Completed order as per commio
                $ordeDetail = [
                    'account_id' => $companyId,
                    'vendor_id' => $vendorId,
                    'order_id' => $responseData['id'],
                    'status' => $responseData['status'],
                ];

                $ordeDetail = DidOrderStatus::create($ordeDetail);

                if (!$ordeDetail) {
                    //effect in DB to notify to techteam
                    $res = [
                        'status' => false,
                        'message' => 'Order Completion Failed!Please Contact Support.',
                    ];

                    return response()->json($res, Response::HTTP_OK);
                }

                $completeOrder = $this->completeOrder($companyId, $responseData['id'], $vendorId, $accountId);

                Log::info($completeOrder);

                if (isset($completeOrder['status']) && isset($completeOrder['type'])) {
                    //origination_order
                    if ($completeOrder['status'] == 'completed' && $completeOrder['type'] == 'origination_order') {

                        foreach ($completeOrder['tns'] as $row) {

                            //insert into did detail tbl
                            $ordeDetail = [
                                'account_id' => $companyId,
                                'did_vendor_id' => $vendorId,
                                'orderid' => $responseData['id'],
                                'domain' => $vendorId,
                                'did' => $row['did'],
                                'cnam' => $row['features']['cnam'],
                                'sms' => $row['features']['sms'],
                                'e911' => $row['features']['e911'],
                                'price' => $rate,
                                'created_by' => $createdBy,
                            ];

                            $ordeDetail = DidDetail::create($ordeDetail);

                            $didConfigureResponse = $this->didRoutingConfigure($vendorId, $accountId, $row['did']);

                            if (isset($didConfigureResponse['status'])) {
                                $arr = [
                                    'did' => $row['did'],
                                    'route_id' => 246

                                ];

                                DidRouting::create($arr);
                            }
                        }

                        //make the order Status Completed in did order statuses tbl
                        DidOrderStatus::where('order_id', $responseData['id'])->update(['status' => 'Completed']);

                        $res = [
                            'status' => true,
                            'message' => 'Order Completed',
                        ];

                        return response()->json($res, Response::HTTP_OK);
                    } else {
                        //notify to tech team , order not completed but order created.
                        DidOrderStatus::where('order_id', $responseData['id'])->update(['status' => 'Failed']);

                        $res = [
                            'status' => false,
                            'message' => 'Order Created But Completion Failed!Please Contact Support. With Order Id ' . $responseData['id']
                        ];

                        return response()->json($res, Response::HTTP_OK);
                    }
                } else {
                    //notify to tech team , order not completed but order created.
                    $res = [
                        'status' => false,
                        'message' => 'Order Created But Completion Failed!Please Contact Support. With Order Id ' . $responseData['id']
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
    public function completeOrder($accountId, $orderId, $vendorId, $commioAccountId)
    {
        $DidController = new DidVendorController();
        $vendorDataResponse = $DidController->show($vendorId);
        $datas = $vendorDataResponse->getData();
        $username = $datas->data->username;
        $password = $datas->data->token;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.thinq.com/account/' . $commioAccountId . '/origination/order/complete/' . $orderId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic ' . base64_encode("$username:$password"),
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $responseData = json_decode($response, true);

        return $responseData;
    }

    /**
     * Configure DID routing settings via the Commio API.
     * 
     * This method configures routing for a specific DID number through Commio's API. It sends a PUT request
     * to update the routing configuration with a predefined route ID. The method requires vendor credentials
     * which are retrieved using the vendor ID.
     *
     * @param int $vendorId The ID of the Commio vendor
     * @param string $commioAccountId The Commio account ID
     * @param string $did The DID number to configure routing for
     * @return array The decoded response from the Commio API
     */
    public function didRoutingConfigure($vendorId, $commioAccountId, $did)
    {
        // The data you want to send in the request, in JSON format
        $data = [
            "routing" => [
                [
                    "did" => $did,
                    "route_id" => 16486
                ]
            ]
        ];

        $DidController = new DidVendorController();
        $vendorDataResponse = $DidController->show($vendorId);
        $datas = $vendorDataResponse->getData();
        $username = $datas->data->username;
        $password = $datas->data->token;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.thinq.com/account/' . $commioAccountId . '/origination/did/routing/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic ' . base64_encode("$username:$password"),
                'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => json_encode($data),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $responseData = json_decode($response, true);

        return $responseData;
    }

    /**
     * Send an SMS message via the Commio API.
     *
     * This method sends an SMS message through Commio's API by first validating that an active
     * Commio vendor exists, then making a POST request to their messaging endpoint. It handles
     * the authentication and formatting of the request payload.
     *
     * @param string $fromDid The phone number/DID that will send the message
     * @param string $ToDid The recipient's phone number/DID
     * @param string $message The text content of the message to send
     * @return \Illuminate\Http\JsonResponse|array The API response containing status and message details
     */
    public function sendMessage($fromDid, $ToDid, $message)
    {
        $vendor = DidVendor::where('status', 'active')->first();

        if (!$vendor || $vendor->vendor_name !== 'Commio') {
            $response = [
                'status' => false,
                'error' => 'Vendor not found.'
            ];

            return response()->json($response, Response::HTTP_BAD_REQUEST);
        }

        // Define the JSON payload to send in the POST request
        $data = [
            'from_did' => $fromDid,  // The sending DID (replace with actual DID)
            'to_did' => $ToDid,    // The recipient DID (replace with actual DID)
            'message' => $message  // The message content
        ];

        $username = $vendor->username;
        $password = $vendor->token;

        $account_id = 14642;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.thinq.com/account/' . $account_id . '/product/origination/sms/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic ' . base64_encode("$username:$password"),
                'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => json_encode($data),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $responseData = json_decode($response, true);

        Log::info($responseData);

        return $responseData;
    }
}
