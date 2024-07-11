<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\WalletTransaction;
use App\Models\AccountBalance;

class WalletTransactionController extends Controller
{

    //public function useWalletBalance($accountId, $deductValue)
    public function useWalletBalance($walletData)
    {   
        //echo '<pre>';print_r($walletData); exit;

        $accountId      = $walletData['accountId'];
        $deductValue    =   $walletData['amount'];

        $chkWalletBalance = AccountBalance::where('account_id', $accountId)->first();

        if (!$chkWalletBalance) {
            $response = [
                'status' => false,
                'message' => 'Account Balance Not Found'
            ];
            return response()->json($response, Response::HTTP_NOT_FOUND);
        } else {
            // return ($chkWalletBalance->amount >= $deductValue) ? true : false;

           
                
                if ($chkWalletBalance->amount >= $deductValue) {

                    //echo "balance".$chkWalletBalance->amount." rate".$deductValue;  exit;

                    $inputData = $chkWalletBalance->amount - $deductValue;

                    AccountBalance::where('id', $accountId)->update(['amount' => $inputData]);

                    //`created_by`, `account_id`, `amount`, `transaction_type`, `payment_gateway_session_id`, `payment_gateway_transaction_id`, `payment_gateway`, `invoice_url`, `descriptor`,
                    $walletDataDetail = [
                        'created_by'                        => !empty($walletData['created_by']) ? $walletData['created_by'] : "",
                        'account_id'                        => !empty($walletData['accountId']) ? $walletData['accountId'] : "",
                        'amount'                            => !empty($walletData['amount']) ? $walletData['amount'] : "",
                        'transaction_type'                  => !empty($walletData['transaction_type']) ? $walletData['transaction_type'] : "",
                        'payment_gateway_session_id'        => !empty($walletData['payment_gateway_session_id']) ? $walletData['payment_gateway_session_id'] : "",
                        'payment_gateway_transaction_id'    => !empty($walletData['payment_gateway_transaction_id']) ? $walletData['payment_gateway_transaction_id'] : "",
                        'payment_gateway'                   => !empty($walletData['payment_gateway']) ? $walletData['payment_gateway'] : "",
                        'invoice_url'                       => !empty($walletData['invoice_url']) ? $walletData['invoice_url'] : "",
                        'descriptor'                        => !empty($walletData['descriptor']) ? $walletData['descriptor'] : "",
                    ];
                    WalletTransaction::create($walletDataDetail);

                    $response = [
                        'status' => true,
                        'message' => 'Balane Deducted From Wallet'
                    ];

                    return response()->json($response, Response::HTTP_OK);

                } else {
                    $response = [
                        'status' => false,
                        'message' => 'Low Wallet Balance'
                    ];
                    return response()->json($response, Response::HTTP_NOT_FOUND);
                }
            
        }
    }
}
