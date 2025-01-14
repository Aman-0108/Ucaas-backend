<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\AccountDetail;
use App\Models\DefaultPermission;
use App\Models\Document;
use App\Models\Domain;
use App\Models\Payment;
use App\Models\Role;
use App\Models\RolePermission;
use App\Notifications\NewAccountRegistered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Account'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Account';
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

        // $accountQuery->select('id','email','package_id','timezone_id');

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
        $accounts = $accountQuery->with([
            'users',
            'extensions',
            'dids',
            'details:id,account_id,document_id,path,status,description',
            'details.document:id,name',
            'balance',
            'bundleMinutes',
            'subscription',
            'payments' => function ($query) {
                $query->select('account_id', 'amount_subtotal', 'transaction_id', 'currency', 'payment_status', 'transaction_date', 'invoice_url', 'subscription_type')
                    ->orderBy('transaction_date', 'desc')
                    ->take(5);
            },
            'payments.paymentDetails',
            'billingAddress:account_id,id,fullname,contact_no,email,address,zip,city,state,country,default',
            'cardDetails' => function ($query) {
                $query->select('account_id', 'id', 'name', 'card_number', 'exp_month', 'exp_year', 'cvc', 'default')
                    ->where('save_card', 1);
            },
            'package' => function ($query) {
                $query->select('id', 'name', 'number_of_user', 'description', 'subscription_type', 'regular_price', 'offer_price');
            },
            'package.features:package_id,name',
            'timezone:id,name,value'
        ])->get();

        if (!empty($accounts)) {
            $accounts->each(function ($account) {
                if ($account->details) {
                    $account->details->each(function ($detail) {
                        $detail->path = Storage::url($detail->path);
                    });
                }
            });
        }

        $type = config('enums.RESPONSE.SUCCESS');
        $status = true;
        $msg = 'Successfully fetched all accounts.';

        return responseHelper($type, $status, $msg, Response::HTTP_OK, $accounts);
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
        $account = Account::with([
            'users',
            'extensions',
            'dids',
            'details:id,account_id,document_id,path,status,description',
            'details.document:id,name',
            'balance',
            'bundleMinutes',
            'subscription',
            'payments' => function ($query) {
                $query->select('account_id', 'amount_subtotal', 'transaction_id', 'currency', 'payment_status', 'transaction_date', 'invoice_url', 'subscription_type')
                    ->orderBy('transaction_date', 'desc')
                    ->take(5);
            },
            'payments.paymentDetails',
            'billingAddress:account_id,id,fullname,contact_no,email,address,zip,city,state,country,default',
            'cardDetails' => function ($query) {
                $query->select('account_id', 'id', 'name', 'card_number', 'exp_month', 'exp_year', 'cvc', 'default')
                    ->where('save_card', 1);
            },
            'package' => function ($query) {
                $query->select('id', 'name', 'number_of_user', 'description', 'subscription_type', 'regular_price', 'offer_price');
            },
            'package.features:package_id,name',
            'timezone:id,name,value'
        ])->find($id);

        // Find the account by ID
        if (!$account) {
            // If the account is not found, return a 404 Not Found response
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'Account not found';

            return responseHelper($type, $status, $msg, Response::HTTP_NOT_FOUND);
        }

        // Mapped full url
        if (!empty($account->details)) {
            $account->details->each(function ($item) {
                $item->path = Storage::url($item->path);
            });
        }

        // Prepare a success response with the account data
        $type = config('enums.RESPONSE.SUCCESS');
        $status = true;
        $msg = 'Successfully fetched';

        $data = ($account) ? $account : '';

        // Return a JSON response with the account data with status(200)
        return responseHelper($type, $status, $msg, Response::HTTP_OK, $data);
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
                'state' => 'required|string',
                'zip' => 'required|string',
                'country' => 'required|string',
                'package_id' => 'required|numeric|exists:packages,id',
                'status' => 'in:active,inactive'
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a 403 Forbidden response with validation errors
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = $validator->errors();

            return responseHelper($type, $status, $msg, Response::HTTP_FORBIDDEN);
        }

        // Additional layer of security to check 
        if (!is_valid_email($request->email)) {

            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'Mail exchange is not available';

            // Return a JSON response with the success message and stored account data
            return responseHelper($type, $status, $msg, Response::HTTP_NOT_FOUND);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Begin transaction
        DB::beginTransaction();

        // default company status set as 'new'
        $validated['company_status'] = 1;

        // Create a new account with the validated input
        $data = Account::create($validated);

        // Encrypt the Account ID
        // $encryptedId = Crypt::encrypt($data->id);

        // Generate dynamic URL with account_id
        // $dynamicUrl = env('FRONTEND_URL', url()) . '/document-upload?id=' . $encryptedId;
        $dynamicUrl = '';

        // commit
        DB::commit();

        $eventData = [
            'company_name' => $data->company_name,
            'email' =>  $data->email,
            'dynamicUrl' => $dynamicUrl
        ];

        // Notify 
        $data->notify(new NewAccountRegistered($eventData));

        $type = config('enums.RESPONSE.SUCCESS');
        $status = true;
        $msg = 'Successfully stored';

        // Return a JSON response with the success message and stored account data
        return responseHelper($type, $status, $msg, Response::HTTP_CREATED, $data);
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
        $userId = $request->user()->id;

        // Find the account by ID
        $account = Account::find($id);

        // Check if the account exists
        if (!$account) {
            // If the account is not found, return a 404 Not Found response          
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'Account not found';

            return responseHelper($type, $status, $msg, Response::HTTP_NOT_FOUND);
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
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = $validator->errors();

            return responseHelper($type, $status, $msg, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        $formattedDescription = compareValues($account, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        DB::beginTransaction();

        // Log the action
        accessLog($action, $type, $formattedDescription, $userId);

        // Update the account with the validated input
        $account->update($validated);

        DB::commit();

        $type = config('enums.RESPONSE.SUCCESS');
        $status = true;
        $msg = 'Successfully updated Account';

        // Return a JSON response with the success message and updated account data with status(200)
        return responseHelper($type, $status, $msg, Response::HTTP_OK, $account);
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
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'Account not found';

            return responseHelper($type, $status, $msg, Response::HTTP_NOT_FOUND);
        }

        // Delete the account
        $account->delete();

        $type = config('enums.RESPONSE.SUCCESS');
        $status = true;
        $msg = 'Successfully deleted account';

        // Return a JSON response with HTTP status code 200 (OK)
        return responseHelper($type, $status, $msg, Response::HTTP_OK);
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
                'account_id' => 'required|exists:accounts,id',
                'document_id' => 'required|exists:documents,id',
                'status' => 'required|in:1,2',
                'row_id' => 'required|integer|exists:account_details,id',
                'description' => 'string|nullable'
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a 403 Forbidden response with validation errors          
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = $validator->errors();

            return responseHelper($type, $status, $msg, Response::HTTP_FORBIDDEN);
        }

        $account = Account::find($request->account_id);

        // Find the account by ID
        if (!$account) {
            // If the account is not found, return a 404 Not Found response
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'Account not found';

            return responseHelper($type, $status, $msg, Response::HTTP_NOT_FOUND);
        }

        if (intval($account->company_status) === 1 || intval($account->company_status) === 2) {
            // If the account is not found, return a 404 Not Found response
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'Either document is not uploaded or payment not verified';

            return responseHelper($type, $status, $msg, Response::HTTP_EXPECTATION_FAILED);
        }

        if (intval($account->company_status) === 4) {
            // If the account status is already applied, return a response indicating it's already verified
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'Document already verified.';

            return responseHelper($type, $status, $msg, Response::HTTP_IM_USED);
        }

        // check document status
        $documents = Document::where('status', 'active')->get();

        // If documents not set 
        if ($documents->isEmpty()) {
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'First upload which documents to be uploaded.';
            return responseHelper($type, $status, $msg, Response::HTTP_NOT_FOUND);
        }

        $documentId = $request->document_id;
        $rowId = $request->row_id;

        // Uploaded documents
        $companyDocuments = AccountDetail::where([
            'id' => $rowId,
            'account_id' => $account->id,
            'document_id' => $documentId
        ])->first();

        if (!$companyDocuments) {
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'No documents found.';
            return responseHelper($type, $status, $msg, Response::HTTP_NOT_FOUND);
        }

        if ($companyDocuments->status == 1) {
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'This document is already verified.';
            return responseHelper($type, $status, $msg, Response::HTTP_IM_USED);
        } elseif ($companyDocuments->status == 2) {
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'This document is rejected.';
            return responseHelper($type, $status, $msg, Response::HTTP_EXPECTATION_FAILED);
        } else {
            $accountDetail = AccountDetail::find($request->row_id);
            $accountDetail->status = $request->status;
            $accountDetail->status_by = $userId;
            $accountDetail->description = ($request->description) ? ($request->description) : null;
            $accountDetail->save();
        }

        $ss = [];
        $documents->each(function ($df) use (&$ss, $request) {
            $allStatus = AccountDetail::where(['status' => 1, 'document_id' => $df['id'], 'account_id' => $request->account_id])->get();

            if (!$allStatus->isEmpty()) {
                $ss[] = 'true';
            }
        });

        if (count($documents) == count($ss)) {
            $account->company_status = 4;
            $account->document_approved_by = $userId;
            $account->save();
        }

        $type = config('enums.RESPONSE.SUCCESS');
        $status = true;
        $msg = 'success.';

        return responseHelper($type, $status, $msg, Response::HTTP_ACCEPTED, $account);
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
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = $validator->errors();

            return responseHelper($type, $status, $msg, Response::HTTP_FORBIDDEN);
        }

        $account = Account::find($request->account_id);

        // Find the account by ID
        if (!$account) {
            // If the account is not found, return a 404 Not Found response
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'Account not found';

            return responseHelper($type, $status, $msg, Response::HTTP_NOT_FOUND);
        }

        if (intval($account->company_status) === 2) {
            // If the account status is already approved, return a response indicating it's already approved
            $type = config('enums.RESPONSE.ERROR');
            $status = false;
            $msg = 'payment already verified.';

            return responseHelper($type, $status, $msg, Response::HTTP_IM_USED);
        }

        $account->company_status = 2;
        $account->payment_approved_by = $userId;
        $account->save();

        $type = config('enums.RESPONSE.SUCCESS');
        $status = true;
        $msg = 'Payment successfully verified.';

        return responseHelper($type, $status, $msg, Response::HTTP_ACCEPTED);
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

        $account->balance = $account->balance + $amount;
        $account->save();

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

    /**
     * Retrieves the balance of the account associated with the authenticated user.
     *
     * @param Request $request The request containing the user object.
     * @return \Illuminate\Http\JsonResponse The JSON response with the account balance.
     */
    public function getAccountBalance(Request $request)
    {
        // Get the account ID from the authenticated user
        $account_id = $request->user()->account_id;

        // Retrieve the account balance using the account ID
        $accountBalance = AccountBalance::where('account_id', $account_id)->first();

        $amount = isset($accountBalance) ? $accountBalance->amount : 0;

        // Prepare the response data
        $response = [
            'status' => true,
            'balance' => $amount,
            'message' => 'Balance fetched successfully.'
        ];

        // Return a JSON response with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Sets default roles with their associated permissions for the given account.
     * @param int $user_id The ID of the user to set default roles for.
     * @return void
     */
    public function setDefaultRolesWithPermissions($user_id)
    {
        // Define the roles to be set as default roles
        $roles = ['Admin', 'Manager', 'Agent'];

        // Iterate through the roles and set default permissions for each
        foreach ($roles as $role) {
            // Create a new role with the given name and created_by set to the user ID
            $roleInputs = [
                'name' => $role,
                'created_by' => $user_id
            ];

            // Create a new role with the input data
            $role = Role::create($roleInputs);

            // Get the IDs of the default permissions for the given set (New Company)
            $permissionIds = DefaultPermission::where('setfor', 'New Company')->pluck('permission_id');

            // Iterate through the permission IDs and set the default permissions for each
            foreach ($permissionIds as $permission_id) {
                // Create an input array with the role ID, permission ID and timestamps
                $inputData = [
                    'role_id' => $role->id,
                    'permission_id' => $permission_id,
                ];

                // Create a new role-permission record with the input data
                RolePermission::create($inputData);
            }
        }
    }

}
