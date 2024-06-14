<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CardDetail;

class CardController extends Controller
{
    /**
     * Saves card details associated with an account.
     *
     * This function updates or creates a new record in the CardDetail model based on the provided request data.
     * 
     * @param $request Illuminate\Http\Request The request object containing card details.
     * @param $accountId int The ID of the account associated with the card details.
     * @return void
     */
    public function saveCard($accountId, $inputData)
    {
        // Update or create card details based on account_id and card_number
        $result = CardDetail::updateOrCreate(
            ['account_id' => $accountId, 'card_number' => $inputData['card_number']], // Conditions to check if the record exists
            $inputData            
        );

        return $result;
    }
}
