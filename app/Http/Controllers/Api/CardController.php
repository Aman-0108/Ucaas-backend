<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\CardAdd;
use App\Models\Account;
use App\Models\CardDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class CardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Initialize a query builder for the CardDetail model
        $cardQuery = CardDetail::query();

        // Check if the request contains an 'account_id' parameter
        if ($request->has('account_id')) {
            // If 'account_id' parameter is present, filter results by 'account_id'
            $cardQuery->where('account_id', $request->account_id);
        }

        // Retrieve data based on the applied filters or no filters
        $data = $cardQuery->get();

        // Prepare response parameters
        $type = config('enums.RESPONSE.SUCCESS'); // Assuming SUCCESS is defined in config/enums.php
        $status = true;
        $msg = 'Successfully fetched all accounts.';

        // Return a JSON response using a helper function (responseHelper assumed to be defined elsewhere)
        return responseHelper($type, $status, $msg, Response::HTTP_OK, $data);
    }

    /**
     * Store a new credit card entry for a specific account.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        // Get the current year
        $currentYear = date('Y');

        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string',                    // Name of the cardholder
                'card_number' => 'required|string|digits_between:15,16|unique:card_details,card_number,NULL,id,account_id,' . $request->account_id, // Card number (15-16 digits)
                'exp_month' => 'required|integer|min:1|max:12',          // Expiry month (1-12)
                'exp_year' => 'required|digits:4|gte:' . $currentYear,   // Expiry year (current year or later)
                'cvc' => 'required|digits:3',                   // Card verification code (3 digits)
                'save_card' => 'boolean',                       // Whether to save the card for future use
                'account_id' => 'required|exists:accounts,id',  // Account ID to associate the card with
            ],
            [
                'card_number.unique' => 'The card number has already been taken for this account.',
            ]
        );

        // If validation fails
        if ($validator->fails()) {
            // Return a JSON response with validation errors
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = $validator->errors();

            return responseHelper($type, $status, $msg, Response::HTTP_FORBIDDEN);
        }

        // If validation passes, prepare data for saving
        $cardInput = [
            'name' => $request->name,
            'card_number' => $request->card_number,
            'exp_month' => $request->exp_month,
            'exp_year' => $request->exp_year,
            'cvc' => $request->cvc,
        ];

        // Optionally, check if the user wants to save the card for future transactions
        if ($request->save_card == 1) {
            $cardInput['save_card'] = 1;
        }

        DB::beginTransaction();

        // Call a method to save the card information
        $data = $this->saveCard($request->account_id, $cardInput);

        $account = Account::find($request->account_id);

        $maskedCard = maskCreditCard($data['card_number']);

        $mailData = [
            'account_name' => $account->company_name,
            'card' => $maskedCard,
            'expiry' => $data['exp_month'] . '/' . $data['exp_year'],
            'cardType' => ''
        ];
        
        DB::commit();

        // Send mail when a new card added
        Mail::to($account->email)->send(new CardAdd($mailData));

        // If the card information is saved successfully, prepare success response
        $type = config('enums.RESPONSE.SUCCESS');
        $status = true;
        $msg = 'Successfully stored';

        // Return a JSON response with the success message and possibly the saved card data
        return responseHelper($type, $status, $msg, Response::HTTP_CREATED, $data);
    }

    /**
     * Delete a card by ID.
     *
     * This method finds and deletes a card based on the provided ID.
     * If the card is not found, it returns a 404 Not Found response.
     * If the card is successfully deleted, it returns a success message.
     *
     * @param  int  $id The ID of the card to delete
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function destroy($id)
    {
        $accountId = Auth::user()->account_id;

        // Find the card by ID
        $card = CardDetail::find($id);

        // Check if the card exists
        if (!$card) {
            // If the card is not found, return a 404 Not Found response
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'Card not found';

            return responseHelper($type, $status, $msg, Response::HTTP_NOT_FOUND);
        }

        if ($accountId != $card->account_id) {
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = "You don't have permission";

            return responseHelper($type, $status, $msg, Response::HTTP_FORBIDDEN);
        }

        // Delete the card
        $card->delete();

        $type = config('enums.RESPONSE.SUCCESS');
        $status = true;
        $msg = 'Successfully deleted card';

        // Return a JSON response with HTTP status code 200 (OK)
        return responseHelper($type, $status, $msg, Response::HTTP_OK);
    }

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

    public function setDefault(Request $request)
    {
        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'id' => 'required|exists:card_details,id',
                'account_id' => 'required|exists:accounts,id',
                'default' => 'required|boolean',
            ]
        );

        // If validation fails
        if ($validator->fails()) {
            // Return a JSON response with validation errors
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = $validator->errors();

            return responseHelper($type, $status, $msg, Response::HTTP_FORBIDDEN);
        }

        $data = CardDetail::find($request->id);
        
        if($request->default) {
            CardDetail::where('account_id', $request->account_id)->update(['default' => false]);
        }

        $data->default = $request->default;
        $data->save();

        $type = config('enums.RESPONSE.SUCCESS');
        $status = true;
        $msg = 'Successfully updated';

        // Return a JSON response with HTTP status code 200 (OK)
        return responseHelper($type, $status, $msg, Response::HTTP_OK);
    }
}
