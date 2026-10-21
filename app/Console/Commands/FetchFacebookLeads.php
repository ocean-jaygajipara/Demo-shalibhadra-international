<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Company;
use App\Models\CompanySocial;
use App\Models\Inquiry;
use App\Models\MarketingStatus;
use App\Models\SourceOfInquiry;
use App\Models\TeamPerson;
use App\Models\MasterSetting;
use App\Models\MasterCountry;
use App\Models\MasterState;
use App\Models\MasterCity;
use Carbon\Carbon;
use App\Helpers\Helper;
use App\Models\AdminSoftware;

class FetchFacebookLeads extends Command
{
    protected $signature = 'app:fetch-facebook-leads';
    protected $description = 'Fetch Facebook Leads from connected pages and campaigns CRM API every 5 minutes';

    public function __construct()
    {
        parent::__construct();
        Log::info('FacebookFetchLeads construct run ' . now());
    }

    public function handle()
    {
        Log::info('FacebookFetchLeads command run ' . now());

        try {
            $companies = Company::select('id')->orderBy('id', 'ASC')->get();

            foreach ($companies as $company) {
                $facebookEntries = CompanySocial::where('company_id', $company->id)
                    ->where('platform', 'facebook')->get();

                $inquiry_status = MarketingStatus::where('company_id', $company->id)
                    ->where('type', 'Inquiry')->where('status', 'active')
                    ->orderBy('is_default', 'desc')->first();

                $followup_status = MarketingStatus::where('company_id', $company->id)
                    ->where('type', 'Followup')->where('status', 'active')
                    ->orderBy('is_default', 'desc')->first();

                $source_of_inquiry = SourceOfInquiry::where('company_id', $company->id)
                    ->where('name', 'Facebook')->where('status', 'active')->first();

                $teams = TeamPerson::where('company_id', $company->id)
                    ->where('parent_type_id', 0)->where('status', 'active')->first();
                $adminSoftware = AdminSoftware::where('status', 'active')->orderBy('id', 'asc')->first();

                if (!$source_of_inquiry) {
                    $source_of_inquiry = SourceOfInquiry::create([
                        'company_id' => $company->id,
                        'name' => 'Facebook',
                        'status' => 'active',
                        'created_by' => $teams?->id ?? '',
                    ]);
                }



                foreach ($facebookEntries as $entry) {
                    $accessToken = $entry->api_key;
                    $pageId = $entry->page_id;
                    $userToken = $entry->user_id;

                    // Check if user token is valid
                    $check = Http::withToken($userToken)->get('https://graph.facebook.com/v18.0/me/permissions')->json();

                    if (isset($check['error'])) {
                        $setting = MasterSetting::where('company_id', $company->id)
                            ->where('platform', 'facebook')->first();

                        if ($setting) {
                            $response = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
                                'grant_type' => 'fb_exchange_token',
                                'client_id' => $setting->app_id,
                                'client_secret' => $setting->app_secret,
                                'fb_exchange_token' => $userToken,
                            ])->json();

                            if (isset($response['access_token'])) {
                                $userToken = $response['access_token'];
                                $entry->user_id = $userToken;
                                $entry->save();
                                Log::info("Refreshed user token for company_id {$company->id}");
                            } else {
                                Log::error("Unable to refresh user token for company_id {$company->id}", $response);
                                continue;
                            }
                        }
                    }

                    // Try getting forms using page token
                    $forms = Http::withToken($accessToken)
                        ->get("https://graph.facebook.com/v18.0/{$pageId}/leadgen_forms")
                        ->json();



                    if (isset($forms['error']) && $forms['error']['code'] == 190) {
                        // Refresh page token using user token
                        Log::warning("Page token expired for Page ID: {$pageId}. Refreshing page token...");

                        $pages = Http::withToken($userToken)
                            ->get("https://graph.facebook.com/v18.0/me/accounts")
                            ->json();

                        $newPageToken = collect($pages['data'] ?? [])
                            ->firstWhere('id', $pageId)['access_token'] ?? null;

                        if ($newPageToken) {
                            $accessToken = $newPageToken;
                            $entry->api_key = $accessToken;
                            $entry->save();
                            Log::info("Page token refreshed for Page ID: {$pageId}");

                            $forms = Http::withToken($accessToken)
                                ->get("https://graph.facebook.com/v18.0/{$pageId}/leadgen_forms")
                                ->json();
                        } else {
                            Log::error("Unable to refresh page access token for Page ID: {$pageId}");
                            continue;
                        }
                    }

                    Log::info("Forms fetched for Page ID: {$pageId}", $forms);

                    $leadCount = 0;
                    foreach ($forms['data'] ?? [] as $form) {
                        $leads = Http::get("https://graph.facebook.com/v18.0/{$form['id']}/leads?access_token={$accessToken}")->json();
                        Log::info("fetched lead: {$pageId}", $leads);


                        foreach ($leads['data'] ?? [] as $lead) {
                            $leadId = $lead['id'] ?? null;
                            if (!$leadId) continue;

                            $createdTime = isset($lead['created_time']) ? Carbon::parse($lead['created_time']) : null;
                            if (!$leadId || !$createdTime || $createdTime->lt(Carbon::now()->subMinutes(10))) {
                                continue;
                            }

                            $fieldData = collect($lead['field_data'] ?? []);
                            Log::info("Facebook Lead field_data for Lead ID: {$leadId}", [
                                'form_id' => $form['id'],
                                'lead_id' => $leadId,
                                'field_data' => $fieldData,
                            ]);

                            $city_name      = optional($fieldData->firstWhere('name', 'city'))['values'][0] ?? null;
                            $state_name         = optional($fieldData->firstWhere('name', 'state'))['values'][0] ?? null;
                            $country_name       = optional($fieldData->firstWhere('name', 'country'))['values'][0] ?? null;

                            $country = MasterCountry::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($country_name) . '%'])->where('status', 'active')->orderBy('id', 'asc')->first();
                            if (!$country) {
                                $country = MasterState::create([
                                    'name'       => $country_name,
                                    'status'     => 'active',
                                    'created_by' => $teams?->id ?? '',
                                ]);
                            }

                            $state = MasterState::where('country_id', $country?->id ?? 1)->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($state_name) . '%'])->where('status', 'active')->orderBy('id', 'asc')->first();
                            if (!$state) {
                                $state = MasterState::create([
                                    'country_id' => $country?->id ?? 1,
                                    'name'       => $state_name,
                                    'status'     => 'active',
                                    'created_by' => $teams?->id ?? '',
                                ]);
                            }

                            $city = MasterCity::where('country_id', $country?->id ?? 1)->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($city_name) . '%'])->where('status', 'active')->orderBy('id', 'asc')->first();
                            if (!$city) {
                                $city = MasterCity::create([
                                    'country_id' => $country?->id ?? 1,
                                    'state_id' => $state?->id ?? '',
                                    'name'       => $city_name,
                                    'status'     => 'active',
                                    'created_by' => $teams?->id ?? '',
                                ]);
                            }

                            $email         = optional($fieldData->firstWhere('name', 'email'))['values'][0] ?? null;
                            $fullName      = optional($fieldData->firstWhere('name', 'full_name'))['values'][0] ?? null;
                            $firstName     = optional($fieldData->firstWhere('name', 'first_name'))['values'][0] ?? null;
                            $lastName      = optional($fieldData->firstWhere('name', 'last_name'))['values'][0] ?? null;
                            $phone         = optional($fieldData->firstWhere('name', 'phone_number'))['values'][0] ?? null;
                            $street        = optional($fieldData->firstWhere('name', 'street_address'))['values'][0] ?? null;
                            $zip           = optional($fieldData->firstWhere('name', 'zip_code'))['values'][0] ?? null;
                            $dateOfBirth   = optional($fieldData->firstWhere('name', 'date_of_birth'))['values'][0] ?? null;
                            $gender        = optional($fieldData->firstWhere('name', 'gender'))['values'][0] ?? null;

                            $maritalStatus     = optional($fieldData->firstWhere('name', 'marital_status'))['values'][0] ?? null;
                            $relationshipStatus = optional($fieldData->firstWhere('name', 'relationship_status'))['values'][0] ?? null;

                            $jobTitle      = optional($fieldData->firstWhere('name', 'job_title'))['values'][0] ?? null;
                            $companyName   = optional($fieldData->firstWhere('name', 'company_name'))['values'][0] ?? null;
                            $workPhone     = optional($fieldData->firstWhere('name', 'work_phone_number'))['values'][0] ?? null;

                            $website       = optional($fieldData->firstWhere('name', 'website'))['values'][0] ?? null;

                            $cpf           = optional($fieldData->firstWhere('name', 'cpf'))['values'][0] ?? null;
                            $dniArgentina  = optional($fieldData->firstWhere('name', 'dni'))['values'][0] ?? null;
                            $dniPeru       = optional($fieldData->firstWhere('name', 'dni'))['values'][0] ?? null;
                            $rut           = optional($fieldData->firstWhere('name', 'rut'))['values'][0] ?? null;
                            $cc            = optional($fieldData->firstWhere('name', 'cc'))['values'][0] ?? null;
                            $ci            = optional($fieldData->firstWhere('name', 'ci'))['values'][0] ?? null;
                            $rfc           = optional($fieldData->firstWhere('name', 'rfc'))['values'][0] ?? null;
                            if (empty($companyName)) {
                                $companyName = optional($fieldData->firstWhere('name', 'your_company_/_firm_name'))['values'][0] ?? null;
                            }

                            $yourMessage = optional($fieldData->firstWhere('name', 'your_message'))['values'][0] ?? null;


                            Log::info("companyName:", ['value' => $companyName]);


                            $inquiryCount = Inquiry::withTrashed()->where('company_id', $company->id)->count();
                            $nextNumber = $inquiryCount + 1;
                            $inquiryNo = 'INQ-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);


                            $inquiryData = Inquiry::firstOrCreate([
                                'platform_unique_id' => $leadId,
                            ], [
                                'inquiry_date' => \Carbon\Carbon::now()->format('Y-m-d'),
                                'inquiry_no' => $inquiryNo,
                                'company_id' => $company->id,
                                'customer_name' => $companyName,
                                'contact_person_name' => $fullName,
                                'website' => $website,
                                'address' => $street,
                                'email' => $email,
                                'mobile_no' => $phone,
                                'whatsapp_no' => $workPhone,
                                'requirement_details' => $yourMessage,
                                'inquiry_status_id' => $inquiry_status?->id,
                                'followup_status_id' => $followup_status?->id,
                                'source_of_inquiry_id' => $source_of_inquiry?->id,
                                'country_id' => $country?->id ?? 1,
                                'state_id' => $state?->id ?? '',
                                'city_id' => $city?->id ?? '',
                                'pincode' => $zip,
                                'platform' => 'Facebook',
                                'inquiry_created_by' => $teams?->id ?? '',
                                'platform_json' => json_encode($fieldData),
                                'created_type' => 'Team',
                                'created_by' => $teams?->id ?? '',
                            ]);

                            if ($inquiryData->wasRecentlyCreated) {
                                Helper::sendInquiryAutoReply($inquiryData);
                            }

                            $leadCount++;
                        }

                        if ($leadCount > 0) {
                            Helper::sendPushNotification(array("company_id" => $company->id, "user_id" => $teams?->id, "user_type" => 'Team', 'title'=> "Facebook lead(s) received.", 'body'=> "Total {$leadCount} Facebook lead(s) received.", 'module_name'=> 'facebook', 'status' => 'success'));
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Facebook Fetch Leads command failed: ' . $e->getMessage());
            Helper::sendPushNotification(array("user_id" => $adminSoftware?->id, "user_type" => 'Admin','module_name'=> 'facebook', "title"=> 'Facebook Fetch Leads command failed.' , 'status' => 'error', "body"=> 'Facebook Fetch Leads command failed. ' . $response->status()));
        }
    }
}
