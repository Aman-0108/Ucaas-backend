<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\DidVendor;
use App\Models\DidRateChart;
use App\Models\DidDetail;

use function PHPUnit\Framework\isEmpty;

class TfnController extends Controller
{

    

    public function getActiveDidVendor()
    {
        $vendors = DidVendor::where('status', 'active')->get();
        if ($vendors->isEmpty()) {
            return response()->json($vendors, Response::HTTP_NOT_FOUND);
        } else {
            return response()->json($vendors, Response::HTTP_OK);
        }
    }

    public function searchTfn(Request $request)
    {

       // $createdBy = $request->user()->id;
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


        $getVendorDetail =  $this->getActiveDidVendor();
        $vendorDataObject = $getVendorDetail->getData();

        if (empty($getVendorDetail->getData())) {

            $response = [
                'status' => false,
                'message' => 'No Availabe Active Vendor',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        } else {
            $vendorId           =  !empty($vendorDataObject[0]->id) ? $vendorDataObject[0]->id : "";
            $vendorName         =  !empty($vendorDataObject[0]->vendor_name) ? $vendorDataObject[0]->vendor_name : "";
            $vendorUserName     =  !empty($vendorDataObject[0]->username) ? $vendorDataObject[0]->username : "";
            $vendorToken        =  !empty($vendorDataObject[0]->token) ? $vendorDataObject[0]->token : "";

            if (empty($vendorName)) {
                $response = [
                    'status' => false,
                    'message' => 'DID Vendor is Not Selected',
                    'errors' => $validator->errors()
                ];
                return response()->json($response, Response::HTTP_FORBIDDEN);
            } else {

                if ($vendorName == 'Commio') {

                    if (empty($vendorUserName) || empty($vendorToken)) {

                        $response = [
                            'status' => false,
                            'message' => 'API Credentials are not Properly Configured',
                            'errors' => $validator->errors()
                        ];
                        return response()->json($response, Response::HTTP_FORBIDDEN);
                    } else {
                        //pass crteated By parameter
                        $rateType = 'random';
                        $CommioController = new CommioController();
                        $vendorDataResponse = $CommioController->searchDidInCommio($request->companyId, $vendorId, $vendorName, $vendorUserName, $vendorToken, $request->searchType, $request->quantity, $request->npa, $rateType);
                        $functionDataObject = $vendorDataResponse->getData();

                        return response()->json($functionDataObject, Response::HTTP_OK);
                    }
                } else {
                    $response = [
                        'status' => false,
                        'message' => 'Active Vendor is not Properly Configure',
                        'errors' => $validator->errors()
                    ];
                    return response()->json($response, Response::HTTP_NOT_FOUND);
                }
            }
        }
    }

    public function purchaseTfn(Request $request)
    {
        // $createdBy = $request->user()->id;
        $createdBy = 2;
        $validator = Validator::make(
            $request->all(),
            [
                'vendorId' => 'required',
                //'dids' => 'required',
                'didQty' => 'required|integer',
                'rate' => 'required',
                'accountId' => 'required',
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

        //before sending to check wallet balance check the rate of did and match the incoming value form frontend



        //checking the wallet balance
        $AccountWallet = new WalletTransactionController();
        //pass created By parameter
        $AccountWalletData = $AccountWallet->useWalletBalance($request->companyId, $request->rate);
        $AccountWalletDataObject = $AccountWalletData->getData();

        if ($AccountWalletDataObject->status == false) {
            $response = [
                'status' => false,
                'errors' => $AccountWalletDataObject->message
            ];
            return response()->json($response, Response::HTTP_FORBIDDEN);
        } else {

            $DidVendorController = new DidVendorController();
            $vendorDataResponse = $DidVendorController->show($request->vendorId);


            if (empty($vendorDataResponse)) {
                $response = [
                    'status' => false,
                    'message' => 'Vendor Id Not Found.',
                    'errors' => $validator->errors()
                ];
                return response()->json($response, Response::HTTP_NOT_FOUND);
            } else {
                $functionDataObject = $vendorDataResponse->getData();
                //echo $functionDataObject->data->vendor_name; exit;

                if ($functionDataObject->data->vendor_name == 'Commio') {
                    //pass created By parameter
                    $CommioController = new CommioController();
                    $purchaseDataResponse = $CommioController->purchaseDidInCommio($createdBy,$request->companyId, $request->vendorId, $request->didQty, $request->rate, $request->accountId, $request->dids);
                    $responseFunctionDataObject = $purchaseDataResponse->getData();
                    return response()->json($responseFunctionDataObject, Response::HTTP_OK);

                } else {
                    $response = [
                        'status' => false,
                        'message' => 'Active Vendor is not Properly Configure',
                        'errors' => $validator->errors()
                    ];
                    return response()->json($response, Response::HTTP_NOT_FOUND);
                }
            }
        }
    }
}
