<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\DidRateChart;
use App\Models\CardDetail;
use App\Models\BillingAddress;
use App\Models\DefaultPermission;
use App\Models\DidDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use App\Models\DidVendor;
use App\Models\Domain;
use App\Models\Extension;
use App\Models\Package;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Validation\Rule;

class TfnController extends Controller
{

    protected $stripeController;

    public function __construct(StripeController $stripeController)
    {
        $this->stripeController = $stripeController;
    }

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

        $getVendorDetail =  $this->getActiveDidVendor();

        $vendorDataObject = $getVendorDetail->getData();

        if (empty($getVendorDetail->getData())) {
            $response = [
                'status' => false,
                'message' => 'No Availabe Active Vendor',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_BAD_REQUEST);
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
        $createdBy = $request->user()->id;

        $validator = Validator::make(
            $request->all(),
            [
                'vendorId' => [
                    'required',
                    Rule::exists('did_vendors', 'id')->where(function ($query) use ($request) {
                        $query->where('id', $request->vendorId);
                    })
                ],
                'dids' => 'required',
                'didQty' => 'required|integer',
                'rate' => 'required',
                'accountId' => 'required',
                'companyId' => 'required|exists:accounts,id',
                'type' => 'required|in:wallet,card,configure',
                'didType' => 'required|in:random,block',
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

        $rateCard = DidRateChart::where(['vendor_id' => $request->vendorId, 'rate_type' => $request->didType])->first();

        // Check Rate is defined or not
        if (!$rateCard) {
            $response = [
                'status' => false,
                'error' => 'Rate card not found.'
            ];

            return response()->json($response, Response::HTTP_BAD_REQUEST);
        }

        $qty = count($request->dids);
        $rate = $qty * $rateCard->rate;

        $metadata = [
            'cause' => 'Did Buy',
            'account_id' => $request->account_id
            // Add more metadata fields as needed
        ];

        $paymentMode = 'card';
        $description = 'New DID Buy.';
        $transaction_type = 'debit';
        $payment_gateway = '';

        // DB::beginTransaction();

        $vendor = DidVendor::find($request->vendorId);

        if (empty($vendor->vendor_name)) {
            $response = [
                'status' => false,
                'error' => 'Active Vendor is not Properly Configure',
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // use wallet
        if ($request->type == 'wallet') {

            $walletResponse = $this->useWallet($createdBy, $request->companyId, $rate);

            if (!$walletResponse->status) {
                $response = [
                    'status' => false,
                    'errors' => $walletResponse->message
                ];

                return response()->json($response, Response::HTTP_FORBIDDEN);
            }

            if ($vendor->vendor_name == 'Commio') {
                $response = $this->purchaseViaCommio($createdBy, $request->companyId, $request->vendorId, $qty, $rate, $request->accountId, $request->dids);

                return $response;
            } else {
                $response = [
                    'status' => false,
                    'errors' => 'Check vendor configuration.'
                ];

                return response()->json($response, Response::HTTP_FORBIDDEN);
            }
        }

        // use card
        if ($request->type == 'card') {

            $payment_gateway = checkPaymentGateway();

            if (!$payment_gateway) {
                return response()->json([
                    'status' => false,
                    'error' => 'Payment Gateway configuration error'
                ], 400);
            }

            if ($vendor->vendor_name == 'Commio') {

                $paymentController = new PaymentController();

                $request->merge([
                    'amount' => $rate,
                    'account_id' => $request->companyId,
                ]);

                $paymentResponse = $paymentController->pay($request, $metadata);

                // Extract content from response
                $paymentResponse = $paymentResponse->getContent();
                $responseData = json_decode($paymentResponse, true);

                if ($responseData['status']) {
                    $response = $this->purchaseViaCommio($createdBy, $request->companyId, $request->vendorId, $qty, $rate, $request->accountId, $request->dids);

                    $transactionId = $responseData['transactionId'];

                    if ($request->has('card_id')) {
                        // card details
                        $card = CardDetail::where(['id' => $request->card_id, 'account_id' => $request->account_id, 'cvc' => $request->cvc])->first();

                        if (!$card) {
                            $type = config('enums.RESPONSE.ERROR'); // Response type (error)
                            $status = false; // Operation status (failed)
                            $msg = 'CVV is invalid.'; // Detailed error messages

                            // Return CVV validation error response
                            return responseHelper($type, $status, $msg, Response::HTTP_BAD_REQUEST);
                        }
                    } else {
                        $card = [
                            'name' => $request->name,
                            'card_number' => $request->card_number,
                            'exp_month' => $request->exp_month,
                            'exp_year' => $request->exp_year,
                            'cvc' => $request->cvc,
                        ];

                        $card = json_decode(json_encode($card));
                    }

                    if ($request->has('address_id')) {
                        $billingAddress = BillingAddress::find($request->address_id);
                    } else {
                        $billingAddresInputs = [
                            'fullname' => $request->fullname,
                            'contact_no' => $request->contact_no,
                            'email' => $request->email,
                            'address' => $request->address,
                            'zip' => $request->zip,
                            'city' => $request->city,
                            'state' => $request->state,
                            'country' => $request->country
                        ];

                        $billingAddress = json_decode(json_encode($billingAddresInputs));
                    }

                    // Add payment record
                    $paymentController->addPayment($transaction_type, $payment_gateway, $billingAddress, $request->account_id, $card, $paymentMode, $rate, $transactionId, $description);

                    return $response;
                } else {
                    if (isset($responseData['error'])) {
                        return response()->json([
                            'status' => false,
                            'error' => $responseData['error']
                        ], 400);
                    }

                    return commonServerError();
                }
            } else {
                $response = [
                    'status' => false,
                    'errors' => 'Check vendor configuration.'
                ];

                return response()->json($response, Response::HTTP_FORBIDDEN);
            }
        }

        // for cofiguration
        if ($request->type == 'configure') {

            if ($vendor->vendor_name == 'Commio') {

                $srcResponse = $this->purchaseViaCommio($createdBy, $request->companyId, $request->vendorId, $qty, $rate, $request->accountId, $request->dids);

                $response = $srcResponse->getData();

                if (!$response->status) {
                    return $srcResponse;
                }

                // Find account
                $account = Account::find($request->companyId);

                // Remove spaces from domain name
                $domainName = preg_replace('/\s+/', '', strtolower($account->admin_name));

                // To shorten the domain name if it's longer than 4 characters
                $formattedDomain = strlen($domainName) > 4 ? substr($domainName, 0, 4) : $domainName;

                // Domain inputs
                $domainInputs = [
                    'domain_name' =>  $formattedDomain . '.' . $account->id . '.webvio.in',
                    'created_by' => $createdBy
                ];

                // create domain or update  
                $result = Domain::updateOrCreate(
                    ['account_id' => (int) $request->companyId],
                    $domainInputs
                );

                // update company status 
                Account::where('id', $request->companyId)->update(['company_status' => 6]);

                // update domain id
                User::where('email', $account->email)->update(['domain_id' => $result->id]);

                // First, set all rows for the account_id to false
                DidDetail::where('account_id', $request->companyId)->update(['default_outbound' => false]);

                // Then, set the first row found to true
                DidDetail::where('account_id', $request->companyId)
                    ->first()
                    ->update(['default_outbound' => true]);

                $domain = Domain::where('account_id', $request->companyId)->first();

                if (!$domain) {
                    return response()->json([
                        'status' => false,
                        'error' => 'Domain not found.'
                    ], 404);
                }

                $activeSubscription = Subscription::where(['account_id' => $request->companyId, 'status' => 'active'])->first();

                if ($activeSubscription) {
                    $package = Package::find($activeSubscription->package_id);

                    // Check if there are any users to create 
                    if (!$package || $package->number_of_user < 1) {
                        return response()->json([
                            'status' => false,
                            'error' => 'Check Package Details.'
                        ]);
                    }

                    $number_of_user = $package->number_of_user;

                    $intitialExtension = config('globals.EXTENSION_START_FROM');

                    for ($i = 0; $i < $number_of_user; $i++) {

                        $data = Extension::create([
                            'account_id' => $request->companyId,
                            "domain" => $domain->id,
                            "extension" => $intitialExtension,
                            "password" => $intitialExtension,
                            "voicemail_password" => $intitialExtension,
                        ]);

                        // Check if this is the first extension
                        if ($i == 0) {
                            // Insert the first extension into the user table                    
                            $userdata = User::find($request->user()->id);
                            $userdata->extension_id = $data->id;
                            $userdata->save();

                            // Update the extension in the extension table
                            Extension::where('id', $data->id)->update(['user' => $request->user()->id]);
                        }

                        $intitialExtension++;
                    }
                }

                return $response;
            } else {
                $response = [
                    'status' => false,
                    'errors' => 'Check vendor configuration.'
                ];

                return response()->json($response, Response::HTTP_FORBIDDEN);
            }
        }

        // DB::commit();
    }

    protected function useWallet($createdBy, $companyId, $rate)
    {
        //checking the wallet balance
        $AccountWallet = new WalletTransactionController();

        //date preparing for wallet controller
        $walletData['created_by']                       = $createdBy;
        $walletData['accountId']                        = $companyId;
        $walletData['amount']                           = $rate;
        $walletData['transaction_type']                 = 'debit';
        $walletData['descriptor']                       = 'DID Purchase';
        $walletData['payment_gateway_session_id']       = '';
        $walletData['payment_gateway_transaction_id']   = '';
        $walletData['payment_gateway']                  = '';
        $walletData['invoice_url']                      = '';

        $AccountWalletData = $AccountWallet->useWalletBalance($walletData);
        $result = $AccountWalletData->getData();

        return $result;
    }

    protected function checkVendor($vendorId)
    {
        $DidVendorController = new DidVendorController();

        $vendordata = $DidVendorController->show($vendorId);

        $result = $vendordata->getData();

        return $result;
    }

    protected function purchaseViaCommio($createdBy, $companyId, $vendorId, $qty, $rate, $accountId, $dids)
    {
        $CommioController = new CommioController();
        $purchaseDataResponse = $CommioController->purchaseDidInCommio($createdBy, $companyId, $vendorId, $qty, $rate, $accountId, $dids);

        return $purchaseDataResponse;
    }

}
