<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/software/login';

    /**
     * Show reset password form.
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('software.auth.reset-password')->with([
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Handle a reset request for the given broker.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        // Try admin_software first
        $status = Password::broker('admin_software')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->sendResetResponse($request, $status);
        }

        // Try team_persons
        $status = Password::broker('team_persons')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->sendResetResponse($request, $status);
        }

        // Try customer
        $status = Password::broker('customer')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );

        return $status === Password::PASSWORD_RESET
            ? $this->sendResetResponse($request, $status)
            : $this->sendResetFailedResponse($request, $status);
    }

    /**
     * Actually update the user password.
     */
    protected function resetPassword($user, $password)
    {
        $user->password = bcrypt($password);
        $user->sp = $password; // your plain-text column
        $user->save();
    }
    /**
     * The response after successfully resetting the password.
     */
    protected function sendResetResponse(Request $request, $response)
    {
        return redirect()->route('software.login')
            ->with('success', 'Your password has been reset successfully    . Please login with your new password.');
    }

    /**
     * The response after a failed password reset.
     */
    protected function sendResetFailedResponse(Request $request, $response)
    {
        return back()->withInput($request->only('email'))
            ->with('error', 'Failed to reset password. Please check your email and token.');
    }
}
