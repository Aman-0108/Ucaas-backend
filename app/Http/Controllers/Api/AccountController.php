<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\Domain;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\AccountCredentialCreated;
use App\Notifications\NewAccountRegistered;
use App\Notifications\NewUserNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    protected $stripeController;

    public function __construct(StripeController $stripeController)
    {
        $this->stripeController = $stripeController;
    }

    /**
     * Retrieve all accounts.
     *
     * This method retrieves all accounts from the database.
     * It returns a JSON response containing the list of accounts.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response containing the list of accounts
     */
    public function index(Request $request)
    {
        $accountQuery = Account::query();

        // Check if the request contains an 'company_status' parameter
        if ($request->has('company_status')) {
            // If 'company_status' parameter is provided, filter domains by company_status
            if ($request->company_status == 'document') {
                $filter = [2, 3];
                $accountQuery->whereIn('company_status', $filter);
            } else {
                $accountQuery->where('company_status', $request->company_status);
            }
        }

        // Retrieve filtered accounts with their details and timezone
        $accounts = $accountQuery->with(['details', 'timezone'])->get();

        foreach ($accounts as $account) {
            if (!empty($account->details)) {
                $details = $account->details;
                $details->registration_path = Storage::url($details->registration_path);
                $details->tin_path = Storage::url($details->tin_path);
                $details->moa_path = Storage::url($details->moa_path);
            }
        }

        // Prepare a success response with the list of accounts
        $response = [
            'status' => true,
            'data' => $accounts,
            'message' => 'Successfully fetched all accounts'
        ];

        // Return a JSON response with the list of accounts with status(200)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Retrieve an account by ID.
     *
     * This method finds and retrieves an account based on the provided ID.
     * If the account is not found, it returns a 404 Not Found response.
     * If the account is found, it returns a JSON response containing the account data.
     *
     * @param  int  $id The ID of the account to retrieve
     * @return \Illuminate\Http\JsonResponse The JSON response containing the account data or an error message
     */
    public function show($id)
    {
        // Find the account by ID
        $account = Account::with(['details', 'balance', 'payments.billingAddress', 'payments.cardDetails', 'payments.subscription', 'package.features', 'timezone'])->find($id);

        // Find the account by ID
        if (!$account) {
            // If the account is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Account not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Mapped full url
        if (!empty($account->details)) {
            $details = $account->details;
            $details->registration_path = Storage::url($details->registration_path);
            $details->tin_path = Storage::url($details->tin_path);
            $details->moa_path = Storage::url($details->moa_path);
        }

        // Prepare a success response with the account data
        $response = [
            'status' => true,
            'data' => ($account) ? $account : '',
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response with the account data with status(200)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a new account.
     *
     * This method validates the incoming request data and stores a new account in the database.
     * If the validation fails, it returns a 403 Forbidden response with validation errors.
     * If the account is successfully stored, it returns a success message along with the stored account data.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the account data
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function store(Request $request)
    {
        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'company_name' => 'required|string|unique:accounts,company_name',
                'admin_name' => 'required|string',
                'timezone_id' => 'required|exists:timezones,id',
                'email' => 'required|email|unique:accounts,email',
                'contact_no' => 'required|string',
                'alternate_contact_no' => 'string|nullable',
                'unit' => 'required|string',
                'street' => 'required|string',
                'city' => 'required|string',
                'zip' => 'required|string',
                'country' => 'required|string',
                'package_id' => 'required|numeric|exists:packages,id',
                'status' => 'in:active,inactive'
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a 403 Forbidden response with validation errors
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Begin transaction
        DB::beginTransaction();

        // default company status set as 'new'
        $validated['company_status'] = 'new';

        // generate temporary password
        $validated['temp_password'] = generateTemporaryPassword();

        // set password in encrypted format
        $validated['passkey'] = Hash::make($validated['temp_password']);

        // Create a new account with the validated input
        $data = Account::create($validated);

        // Encrypt the Account ID
        $encryptedId = Crypt::encrypt($data->id);

        // Generate dynamic URL with account_id
        $dynamicUrl = env('FRONTEND_URL', url()) . '/document-upload?id=' . $encryptedId;

        // commit
        DB::commit();

        $eventData = [
            'company_name' => $data->company_name,
            'email' =>  $data->email,
            'dynamicUrl' => $dynamicUrl
        ];

        // Notify 
        $data->notify(new NewAccountRegistered($eventData));

        // Prepare a success response with the stored account data
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully stored'
        ];

        // Return a JSON response with the success message and stored account data
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Update an account by ID.
     *
     * This method finds and updates an account based on the provided ID and request data.
     * If the account is not found, it returns a 404 Not Found response.
     * If the validation fails, it returns a 403 Forbidden response with validation errors.
     * If the update is successful, it returns a success message along with the updated account data.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the update data
     * @param  int  $id The ID of the account to update
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function update(Request $request, $id)
    {
        // Find the account by ID
        $account = Account::find($id);

        // Check if the account exists
        if (!$account) {
            // If the account is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Account not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'company_name' => 'string|unique:accounts,company_name,' . $id,
                'admin_name' => 'string',
                'timezone_id' => 'exists:timezones,id',
                'email' => 'email|unique:accounts,email',
                'contact_no' => 'string',
                'alternate_contact_no' => 'string|nullable',
                'unit' => 'string',
                'street' => 'string',
                'city' => 'string',
                'zip' => 'string',
                'country' => 'string',
                'package_id' => 'numeric|exists:packages,id'
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a 403 Forbidden response with validation errors
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Update the account with the validated input
        $account->update($validated);

        // Prepare a success response with updated account data
        $response = [
            'status' => true,
            'data' => $account,
            'message' => 'Successfully updated Account',
        ];

        // Return a JSON response with the success message and updated account data with status(200)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Delete an account by ID.
     *
     * This method finds and deletes an account based on the provided ID.
     * If the account is not found, it returns a 404 Not Found response.
     * If the account is successfully deleted, it returns a success message.
     *
     * @param  int  $id The ID of the account to delete
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function destroy($id)
    {
        // Find the account by ID
        $account = Account::find($id);

        // Check if the account exists
        if (!$account) {
            // If the account is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Account not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the account
        $account->delete();

        // Prepare a success message
        $response = [
            'status' => true,
            'message' => 'Successfully deleted account'
        ];

        // Return a JSON response with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Authenticate a user by email and password, and generate an access token upon successful login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            // Validate user input
            $validateUser = Validator::make(
                $request->all(),
                [
                    'email' => 'required|email',
                    'password' => 'required'
                ]
            );

            // Check if validation fails
            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            // Retrieve the account by email
            $account = Account::where('email', $request->email)->first();

            // Check if account exists and password matches
            if (!$account || !Hash::check($request->password, $account->passkey)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email & Password does not match with our record.',
                ], 401);
            }

            // Generate token for the account holder
            $token = $account->createToken($account->email, ['role:account'])->plainTextToken;

            // Return a JSON response indicating successful login along with the token
            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfully',
                'token' => $token
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            // Return a JSON response indicating an error occurred during login
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Retrieves account information.
     *
     * This method retrieves information about the authenticated account making the request.
     * It checks if an account is authenticated and returns account data if available.
     *
     * @param Request $request The HTTP request object.
     * @return \Illuminate\Http\JsonResponse A JSON response containing account information.
     */
    public function account(Request $request)
    {
        // Check if a user is authenticated
        $account = $request->user();

        return $this->show($account->id);
    }

    /**
     * Invalidate the user's access tokens to log them out.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Delete all access tokens associated with the authenticated user
        $request->user()->tokens()->delete();

        // Return a JSON response with success message
        return response()->json([
            'message' => 'Successfully logged out'
        ], Response::HTTP_OK);
    }

    /**
     * Change the status of an account and handle related operations.
     *
     * @param Request $request The incoming request object
     * @return \Illuminate\Http\JsonResponse The JSON response
     */
    public function postDocumentVerify(Request $request)
    {
        // Get the ID of the authenticated user
        $userId = $request->user()->id;

        // Perform validation on the request data     
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id'
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a 403 Forbidden response with validation errors
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        $account = Account::find($request->account_id);

        // Find the account by ID
        if (!$account) {
            // If the account is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Account not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        if (intval($account->company_status) === 1 || intval($account->company_status) === 2) {
            // If the account is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Either document is not uploaded or payment not verified'
            ];

            return response()->json($response, Response::HTTP_EXPECTATION_FAILED);
        }

        if (intval($account->company_status) === 4) {
            // If the account status is already applied, return a response indicating it's already verified
            $response = [
                'status' => false,
                'message' => 'Document already verified.',
            ];

            return response()->json($response, Response::HTTP_IM_USED);
        }

        $account->company_status = 4;
        $account->document_approved_by = $userId;
        $account->save();

        $response = [
            'status' => true,
            'message' => 'success.',
            'data' => $account,
        ];

        return response()->json($response, Response::HTTP_ACCEPTED);
    }

    // After Payment change company status
    public function postPaymentVerify(Request $request)
    {
        // Get the ID of the authenticated user
        $userId = $request->user()->id;

        // Perform validation on the request data     
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id'
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a 403 Forbidden response with validation errors
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        $account = Account::find($request->account_id);

        // Find the account by ID
        if (!$account) {
            // If the account is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Account not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        if (intval($account->company_status) === 2) {
            // If the account status is already approved, return a response indicating it's already approved
            $response = [
                'status' => false,
                'message' => 'payment already verified.',
            ];

            return response()->json($response, Response::HTTP_IM_USED);
        }

        $account->company_status = 2;
        $account->payment_approved_by = $userId;
        $account->save();

        $response = [
            'status' => true,
            'message' => 'Payment successfully verified.',
        ];

        return response()->json($response, Response::HTTP_ACCEPTED);
    }

    // Initiate Recharge
    // public function initiateRecharge(Request $request)
    // {
    //     // Perform validation on the request data
    //     $validator = Validator::make(
    //         $request->all(),
    //         [
    //             'account_id' => 'required|exists:accounts,id',
    //             'amount' => 'required|numeric|between:0,9999999.99',
    //         ]
    //     );

    //     // Check if validation fails
    //     if ($validator->fails()) {
    //         // If validation fails, return a 403 Forbidden response with validation errors
    //         $response = [
    //             'status' => false,
    //             'message' => 'validation error',
    //             'errors' => $validator->errors()
    //         ];

    //         return response()->json($response, Response::HTTP_FORBIDDEN);
    //     }

    //     $type = 'topup';
    //     $accountId = $request->account_id;
    //     $amount = $request->amount;

    //     $client_secret = $this->stripeController->createPaymentIntentForClient($type, $accountId, $amount);

    //     // Prepare the response data
    //     $response = [
    //         'status' => true,
    //         'data' => $client_secret,
    //         'message' => 'Recharge initiated'
    //     ];

    //     // Return a JSON response with HTTP status code 201 (Created)
    //     return response()->json($response, Response::HTTP_CREATED);
    // }

    /**
     * Adjust the payment for an account and record the transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentAdjust(Request $request)
    {
        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'amount_total' => 'required|numeric|between:0,9999999.99',
                'amount_subtotal' => 'required|numeric|between:0,9999999.99',
                'stripe_session_id' => 'string|nullable',
                'payment_gateway' => 'required|string',
                'transaction_type' => 'required|string',
                'subscription_type' => 'string|nullable',
                'payment_method_options' => 'required|string',
                'currency' => 'required|string',
                'payment_status' => 'required|string',
                'transaction_date' => 'required',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a 403 Forbidden response with validation errors
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Begin transaction
        DB::beginTransaction();

        // Create a new balance record with the validated data
        $data = Payment::create($validated);

        // Commit the database transaction
        DB::commit();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Payment added successfully.'
        ];

        // Return a JSON response with HTTP status code 201 (Created)
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Recharge the account balance using Stripe payment gateway.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recharge(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'amount' => 'required|numeric|between:0,9999999.99',
                'type' => 'required|string|max:20',
                'card_number' => 'required|numeric|digits_between:14,16',
                'exp_month' => 'required|digits_between:1,2|numeric|between:1,12',
                'exp_year' => 'required|numeric|digits:4',
                'cvc' => 'required|numeric|digits_between:3,4',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a 403 Forbidden response with validation errors
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Extract input data
        $amount = $request->amount;
        $input = $request->only(['card_number', 'exp_month', 'exp_year', 'cvc', 'type']);

        // Create payment method using Stripe
        $paymentId = $this->stripeController->createPaymentMethod($input);

        // Create payment intent for the recharge transaction
        $transactionId = $this->stripeController->createPaymentIntent($request->acconut_id, $amount, $paymentId);

        // If transaction is successful
        if ($transactionId) {
            // Record transaction details in the database
            $accountInput = [
                'account_id' => $request->account_id,
                'amount_total' => $amount,
                'amount_subtotal' => $amount,
                'stripe_session_id' => $transactionId,
                'payment_gateway' => 'Stripe',
                'payment_method_options' => 'card',
                'currency' => 'usd',
                'payment_status' => 'complete',
                'transaction_date' => date("Y-m-d H:i:s"),
            ];

            Payment::create($accountInput);

            // Update account balance
            return $this->addOrUpdateBalance($request->account_id, $amount, $paymentId, $transactionId);
        }
    }

    /**
     * Add or update the account balance based on the provided amount.
     *
     * @param  int  $account_id
     * @param  float  $amount
     * @param  string  $paymentId
     * @param  string  $transactionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function addOrUpdateBalance($account_id, $amount, $paymentId, $transactionId)
    {
        // Find the account by ID
        $account = Account::find($account_id);

        // Check if the account exists
        if (!$account) {
            // If the account is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Account not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Find the associated domain
        $domain = Domain::where('account_id', $account->id)->first();

        // Prepare input data for account balance
        $inputData = [
            'domain_id' => ($domain) ? $domain->id : null,
            'amount' => $amount,
            'account_id' => $account_id
        ];

        // Find account balance by account ID
        $accountBalance = AccountBalance::find($account->id);

        // If account balance exists, update it
        if ($accountBalance) {
            $inputData['amount'] = $accountBalance->amount + $amount;
            AccountBalance::where('id', $accountBalance->id)->update(['amount' => $inputData['amount']]);
        } else {
            // If account balance doesn't exist, create a new one
            AccountBalance::create($inputData);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $account,
            'paymentId' => $paymentId,
            'transactionId' => $transactionId,
            'message' => 'Balance added successfully.'
        ];

        // Return a JSON response with HTTP status code 201 (Created)
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Creates a new account using input information.
     * @param $input object The input object containing information for creating the account.
     * @return Account The newly created account object.
     */
    public function createAccount($input)
    {
        // Create a new account using input information
        $account = Account::create($input);

        // Return the newly created account
        return $account;
    }
}
