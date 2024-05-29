<?php

namespace App\Http\Controllers\Api;

use App\Enums\Strategy;
use App\Enums\TokenAbility;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Notifications\PasswordReset;
use Carbon\Carbon;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use Notifiable;

    /**
     * Registers a new user.
     *
     * This method attempts to create a new user with the provided registration data.
     * If validation fails, it returns a validation error response. If user creation
     * fails, it returns a response indicating an error occurred. If user creation
     * succeeds, it returns a response indicating successful user registration.
     *
     * @param Request $request The HTTP request object containing user registration data.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the registration attempt.
     */
    public function register(Request $request)
    {
        try {
            // Validate user input
            $validateUser = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'email' => 'required|email|unique:users,email',
                    'password' => 'required',
                    'username' => 'required|unique:users,username,'
                ]
            );

            // Check if validation fails
            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], Response::HTTP_FORBIDDEN);
            }

            // Create a new user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password)
            ]);

            // Return a JSON response indicating successful user registration
            return response()->json([
                'status' => true,
                'message' => 'User Created Successfully',
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            // Return a JSON response indicating an error occurred during registration
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Logs in a user.
     *
     * This method attempts to authenticate a user with the provided email and password.
     * If validation fails, it returns a validation error response. If authentication
     * fails, it returns a response indicating that the email and password do not match
     * any records. If authentication succeeds, it generates a token for the user and
     * returns a response indicating successful login along with the token.
     *
     * @param Request $request The HTTP request object containing user login credentials.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the login attempt.
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

            // Attempt to authenticate user
            if (!Auth::attempt($request->only(['email', 'password']))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email & Password does not match with our record.',
                ], 401);
            }

            // Retrieve the authenticated user
            $user = User::where('email', $request->email)->first();

            if ($user->status == 'D') {
                return response()->json([
                    'status' => false,
                    'message' => 'user is disabled.',
                ], 451);
            }

            // Generate token for the user
            $token = $user->createToken($user->email)->plainTextToken;

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
     * Retrieves user information.
     *
     * This method retrieves information about the authenticated user making the request.
     * It checks if a user is authenticated and returns user data if available.
     *
     * @param Request $request The HTTP request object.
     * @return \Illuminate\Http\JsonResponse A JSON response containing user information.
     */
    public function user(Request $request)
    {
        // Check if a user is authenticated
        $user = $request->user();

        $userData = User::with(['extension', 'group'])->where('id', $user->id)->first();

        $data = [
            'status' => ($user) ? true : false, // Check if a user is authenticated
            'data' => ($user) ? $userData : [], // If user is authenticated, return user data, otherwise return an empty array
            'message' => 'Successfully fetched user'
        ];

        // Return a JSON response containing user information
        return response()->json($data, Response::HTTP_OK);
    }

    /**
     * Logs out the user by revoking all tokens associated with the request user.
     *
     * This method revokes all tokens associated with the authenticated user making
     * the request. After revoking the tokens, it returns a JSON response indicating
     * successful logout.
     *
     * @param Request $request The HTTP request object containing the authenticated user.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating successful logout.
     */
    public function logout(Request $request)
    {
        // Check if a user is authenticated
        $user = $request->user();

        if ($user->id) {
            User::where('id', $user->id)->update(['socket_session_id' => NULL, 'socket_status' => 'offline']);
        }

        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ], Response::HTTP_OK);
    }

    /**
     * Change the password for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        // Validate the request input
        $validateUser = Validator::make(
            $request->all(),
            [
                'old_password' => 'required',
                'new_password' => 'required|min:6',
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

        // Retrieve the authenticated user
        $user = Auth::user();

        // Check if the old password matches the user's current password
        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['status' => false, 'message' => 'Invalid old password'], Response::HTTP_NOT_FOUND);
        }

        // Update the user's password
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Return a success response
        return response()->json(['status' => true, 'message' => 'Password changed successfully'], Response::HTTP_OK);
    }

    /**
     * Sends a password reset link email to the user.
     *
     * This function generates a random OTP (One-Time Password) and associates it with the user's email.
     * It then sends a notification containing the OTP along with a token for resetting the password.
     *
     * @param \Illuminate\Http\Request $request The HTTP request object containing user's email
     * @return \Illuminate\Http\JsonResponse JSON response indicating the status of the operation
     */
    public function sendResetLinkEmail(Request $request)
    {
        // Validate the request parameters
        $request->validate(['email' => 'required|email']);

        // Generate a random OTP
        $otp = rand(100000, 999999);

        // Calculate the expiration time for the OTP
        $expires_at = Carbon::now()->addMinutes(10);

        // Find the user by email
        $user = User::where('email', $request->email)->first();

        // $user->otp = $otp;

        // Check if the user exists
        if (!$user) {
            $response = [
                'status' => false,
                'message' => 'User not found',
            ];

            return response()->json($response, 404);
        }

        // Generate a random token
        $token = Str::random(60);

        // Check if a record exists for the user's email in the password_resets table
        $exist = DB::table('password_resets')->where('email', $user->email)->first();

        // Update or insert OTP and token into the password_resets table
        if ($exist) {
            DB::table('password_resets')
                ->where('email', $user->email)
                ->update([
                    'otp' => $otp,
                    'token' => $token,
                    'created_at' => $expires_at
                ]);
        } else {
            DB::table('password_resets')->insert(['email' => $user->email, 'otp' => $otp, 'token' => $token, 'created_at' => $expires_at]);
        }

        // Prepare data for the notification
        $data = [
            'token' => $token,
            'otp' => $otp,
            'email' => $user->email
        ];

        // Send notification to the user
        $user->notify(new PasswordReset($data));

        // Prepare and return success response
        $response = [
            'status' => true,
            'message' => 'Reset link sent to your email',
        ];

        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Verify the One-Time Password (OTP) for password reset.
     *
     * This function verifies the OTP provided by the user for password reset.
     * If the OTP is valid and has not expired, it generates a new token for the user and deletes any existing tokens.
     *
     * @param \Illuminate\Http\Request $request The HTTP request object containing user's email and OTP
     * @return \Illuminate\Http\JsonResponse JSON response indicating the status of the operation
     */
    public function verifyOTP(Request $request)
    {
        // Validate the request parameters
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string',
            // 'token'
        ]);

        // Validation failed, return error response
        if ($validator->fails()) {
            $response = [
                'status' => false,
                'error' => $validator->errors()
            ];
            return response()->json($response, 400);
        }

        // Find the user by email
        $user = User::where('email', $request->email)->first();

        // Check if the user exists
        if (!$user) {
            $response = [
                'status' => false,
                'error' => 'User not found'
            ];

            return response()->json($response, 404);
        }

        // Check if there is an entry in the password_resets table for the user's email
        $check = DB::table('password_resets')->where('email', $request->email)->first();

        if ($check) {
            // Verify OTP and check if it's expired
            if ($check->otp !== $request->otp || Carbon::now()->gt($check->created_at)) {

                $response = [
                    'status' => false,
                    'message' => 'Invalid or expired OTP'
                ];

                return response()->json($response, 400);
            }

            // OTP verified, delete tokens associated with the user
            DB::table('password_resets')
                ->where('email', $user->email)
                ->delete();

            // Generate token for the user
            $token = $user->createToken($user->email)->plainTextToken;

            // Prepare success response
            $response = [
                'status' => true,
                'message' => 'OTP verified successfully',
                'data' => $token
            ];

            return response()->json($response, 200);
        } else {
            // No OTP entry found, return error response
            $response = [
                'status' => false,
                'message' => 'something went wrong.',
            ];
            return response()->json($response, 422);
        }
    }

    public function reset(Request $request)
    {
        $credentials = request()->validate([
            'password' => 'required|string|confirmed'
        ]);

        // Retrieve the ID of the authenticated user making the request
        $user = Auth::user();

        // Update the user's password
        $user->password = Hash::make($request->password);
        $user->save();

        // Prepare success response
        $response = [
            'status' => true,
            'message' => 'Password has been successfully reset',
        ];

        $request->user()->tokens()->delete();

        return response()->json($response, 200);
    }
}
