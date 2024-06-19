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

    public function useWalletBalance($accountId, $deductValue)
    {

        $chkWalletBalance = AccountBalance::where('account_id', $accountId)->first();

        if (!$chkWalletBalance) {
            $response = [
                'status' => false,
                'message' => 'Account Balance Not Found'
            ];
            return response()->json($response, Response::HTTP_NOT_FOUND);
        } else {
            // return ($chkWalletBalance->amount >= $deductValue) ? true : false;

            if ($chkWalletBalance->amount) {
                if ($chkWalletBalance->amount >= $deductValue) {
                    $inputData = $chkWalletBalance->amount - $deductValue;

                    AccountBalance::where('id', $accountId)->update(['amount' => $inputData]);

                    $response = [
                        'status' => true,
                        'message' => 'Balane Deducted From Wallet'
                    ];

                    return response()->json($response, Response::HTTP_OK);

                } else {
                    $response = [
                        'status' => true,
                        'message' => 'Low Wallet Balance'
                    ];
                    return response()->json($response, Response::HTTP_NOT_FOUND);
                }
            } else {

                $response = [
                    'status' => true,
                    'message' => 'Something went Wrong ! Contact to Support'
                ];

                return response()->json($response, Response::HTTP_NOT_FOUND);
            }
        }
    }
}
