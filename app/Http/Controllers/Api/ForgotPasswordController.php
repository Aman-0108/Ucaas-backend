<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /**
     * Create User
     * @param Request $request
     * @return User 
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if(!$user) {
            return 'error';
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
                    ? response()->json(['message' => 'Reset link sent to your email'], 200)
                    : response()->json(['message' => 'Unable to send reset link'], 400);

    }

    public function reset()
    {
        $credentials = request()->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|confirmed'
        ]);

        $reset_password_status = Password::reset($credentials, function ($user, $password) {
            $user->password = $password;
            $user->save();
        });

        if ($reset_password_status == Password::INVALID_TOKEN) {
            return response()->json(["msg" => "Invalid token provided"], 400);
        }

        return response()->json(["msg" => "Password has been successfully changed"]);
    }
}
