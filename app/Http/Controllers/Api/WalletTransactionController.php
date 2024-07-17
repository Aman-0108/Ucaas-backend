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
    public function index(Request $request)
    {
        $query = WalletTransaction::query();

        // Check if the request contains an 'account_id' parameter
        if ($request->has('account_id')) {
            // If 'account' parameter is provided, filter domains by account ID
            $query->where('account_id', $request->account_id);
        }

        if ($request->has('transaction_type')) {
            // If 'account' parameter is provided, filter domains by account ID
            $query->where('transaction_type', $request->transaction_type);
        }

        // COMING FROM GLOBAL CONFIG
        $ROW_PER_PAGE = config('globals.PAGINATION.ROW_PER_PAGE');

        // Execute the query to fetch domains
        $payments = $query->orderBy('id', 'desc')->paginate($ROW_PER_PAGE);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $payments,
            'message' => 'Successfully fetched.'
        ];

        // Return a JSON response containing the list of domains
        return response()->json($response, Response::HTTP_OK);
    }

    //public function useWalletBalance($accountId, $deductValue)
    public function useWalletBalance($walletData)
    {
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
