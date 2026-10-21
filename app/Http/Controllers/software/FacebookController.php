<?php

namespace App\Http\Controllers\software;

use App\Http\Controllers\Controller;
use App\Models\MasterSetting;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Http;
use Facebook\Facebook;

class FacebookController extends Controller
{
    public function redirectToFacebook(Request $request)
    {
        $companyId = session('selected_company_id');

        if (!$companyId || (int)$companyId <= 0) {
            return back()->withInput()->withErrors(["Please Select Company."]);
        }

        $masterformSetting = MasterSetting::where('company_id', $companyId)
            ->where('platform', 'facebook')
            ->first();

        if (!$masterformSetting || !$masterformSetting->app_id || !$masterformSetting->app_secret || !$masterformSetting->redirect_url) {
            return back()->withErrors(['Missing Facebook app credentials.']);
        }

        config([
            'services.facebook.client_id' => $masterformSetting->app_id,
            'services.facebook.client_secret' => $masterformSetting->app_secret,
            'services.facebook.redirect' => $masterformSetting->redirect_url,
        ]);

        return Socialite::driver('facebook')
            ->scopes([
                'public_profile',
                'email',
                'pages_show_list',
                'pages_read_engagement',
                'pages_manage_ads',
                'ads_management',
                'leads_retrieval',
                'business_management',
            ])
            ->with(['auth_type' => 'rerequest'])
            ->redirect();
    }

    public function handleFacebookCallback(Request $request)
    {
        $companyId = session('selected_company_id');
        $masterformSetting = MasterSetting::where('company_id', $companyId)
            ->where('platform', 'facebook')
            ->first();

        config([
            'services.facebook.client_id' => $masterformSetting->app_id,
            'services.facebook.client_secret' => $masterformSetting->app_secret,
            'services.facebook.redirect' => $masterformSetting->redirect_url,
        ]);

        // Get short-lived user token
        $facebookUser = Socialite::driver('facebook')->stateless()->user();

        // Exchange for long-lived token
        $longLived = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $masterformSetting->app_id,
            'client_secret' => $masterformSetting->app_secret,
            'fb_exchange_token' => $facebookUser->token,
        ])->json();

        if (isset($longLived['access_token'])) {
            $facebookUser->token = $longLived['access_token'];
        }

        // Optional: check permissions granted
        $permissions = Http::withToken($facebookUser->token)
            ->get('https://graph.facebook.com/v18.0/me/permissions')
            ->json();
        // dd($permissions);

        $allPages = [];

        // 1. Pages the user directly manages
        $userPages = Http::withToken($facebookUser->token)
            ->get("https://graph.facebook.com/v18.0/me/accounts?fields=id,name,access_token")
            ->json();

        if (!empty($userPages['data'])) {
            foreach ($userPages['data'] as $page) {
                $allPages[$page['id']] = [
                    'id' => $page['id'],
                    'name' => $page['name'],
                    'access_token' => $page['access_token'],
                    'source' => 'user_account',
                    'business_id' => null,
                    'business_name' => null,
                ];
            }
        }

        // 2. Business-managed pages
        $businesses = Http::withToken($facebookUser->token)
            ->get('https://graph.facebook.com/v18.0/me/businesses?fields=id,name')
            ->json();

        if (!empty($businesses['data'])) {
            foreach ($businesses['data'] as $business) {
                $businessId = $business['id'];

                $businessPages = Http::withToken($facebookUser->token)
                    ->get("https://graph.facebook.com/v18.0/{$businessId}/client_pages?fields=id,name,access_token")
                    ->json();

                foreach ($businessPages['data'] ?? [] as $page) {
                    $allPages[$page['id']] = [
                        'id' => $page['id'],
                        'name' => $page['name'],
                        'access_token' => $page['access_token'] ?? null,
                        'source' => 'business_account',
                        'business_id' => $business['id'],
                        'business_name' => $business['name'],
                    ];
                }
            }
        }

        // Final page list
        $allPages = array_values($allPages);

        // 🔹 Store in session or optionally database
        session([
            'facebook_pages' => $allPages,
            'facebook_company_id' => $companyId,
            'fb_user_token' => $facebookUser->token, // long-lived token
        ]);

        return redirect()->route('company.thirdparty-integration.platform', [
            'platform' => 'facebook',
            'id' => $companyId,
        ]);
    }
}
