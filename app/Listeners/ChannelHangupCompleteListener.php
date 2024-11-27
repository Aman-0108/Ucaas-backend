<?php

namespace App\Listeners;

use App\Events\ChannelHangupComplete;
use App\Http\Controllers\Api\WebSocketController;
use App\Models\AccountBalance;
use Illuminate\Support\Facades\Log;

class ChannelHangupCompleteListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(ChannelHangupComplete $response)
    {
        $formattedData = $response->events;

        // Log::info($formattedData);

        $account_id = $formattedData['account_id'];

        $account_balance = AccountBalance::where('account_id', $account_id)
            ->select('amount')
            ->first();

        $customizedResponse = [
            'key' => 'ChannelHangupComplete',
            'result' => $formattedData,
            'balance' =>  $account_balance && $account_balance->amount !== null ? $account_balance->amount : 0,
            // 'userId' => 3
        ];

        $socketController = new WebSocketController();

        $socketController->send($customizedResponse);
    }
}
