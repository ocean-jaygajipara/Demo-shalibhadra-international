<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\AdminSoftware;
use App\Models\TeamPerson;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('software.auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'app_key' => 'required|string',
            'email'   => 'required|email',
        ]);

        // ----------- CHECK ADMIN SOFTWARE FIRST -----------
        if ($request->app_key === 'office@2016') {
            $admin = AdminSoftware::where('email', $request->email)->first();
            if ($admin) {
                // Remove old tokens
                DB::table('password_reset_tokens')->where('email', $request->email)->delete();

                // Generate token
                $token = Password::broker('admin_software')->createToken($admin);

                $resetUrl = url('software/reset-password/' . $token . '?email=' . urlencode($request->email));

                // Use static company_id, e.g., 1
                $staticCompanyId = 1;

                Helper::mail_new(
                    $staticCompanyId,        
                    'Inquiry',
                    'forgot_password',
                    $request->email,
                    $admin->id,
                    'forgot_password',
                    ['link' => '<a href="' . $resetUrl . '">Reset Password</a>']
                );

                return back()->with('success', __('We have emailed your password reset link!'));
            }
        }


        // ----------- ELSE CHECK TEAM PERSONS -----------
        $company = DB::table('companies')->where('app_key', $request->app_key)->first();

        if (!$company) {
            return back()->with('error', 'Invalid App Key provided.');
        }

        $team_person = TeamPerson::where('email', $request->email)
            ->where('company_id', $company->id)
            ->first();

        if (!$team_person) {
            return back()->with('error', 'No account found with this email for the given App Key.');
        }

        // Remove old tokens
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Generate token
        $token = Password::broker('team_persons')->createToken($team_person);

        $resetUrl = url('software/reset-password/' . $token . '?email=' . urlencode($request->email));

        Helper::mail_new(
            $team_person->company_id,
            'Inquiry',
            'forgot_password',
            $request->email,
            $team_person->id,
            'forgot_password',
            ['link' => '<a href="' . $resetUrl . '">Reset Password</a>']
        );

        return back()->with('success', __('We have emailed your password reset link!'));
    }


    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        // ----------- TRY ADMIN SOFTWARE -----------
        $status = Password::broker('admin_software')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = bcrypt($password);
                $user->sp = $password; // plain text column
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('software.login')
                ->with('success', __('Your password has been reset successfully.'));
        }

        // ----------- ELSE TRY TEAM PERSONS -----------
        $status = Password::broker('team_persons')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = bcrypt($password);
                $user->sp = $password;
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('software.login')
            ->with('success', __('Your password has been reset successfully.'))
            : back()->with('error', __('Invalid token or email.'));
    }

    use SendsPasswordResetEmails;
}
