<?php

namespace App\Console\Commands;

use App\Helpers\Helper;
use App\Models\AdminSoftware;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\Inquiry;
use App\Models\MarketingStatus;
use App\Models\SourceOfInquiry;
use App\Models\CompanySocial;
use App\Models\InquiryProduct;
use App\Models\MasterState;
use App\Models\MasterCity;
use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\TeamPerson;


class IndiaMartFetchLeads extends Command
{

    protected $signature = 'app:india-mart-fetch-leads';
    protected $description = 'IndiaMart Fetch Leads CRM API every 5 minutes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('IndiaMartFetchLeads command run ' . now());
        try {

            $companies = Company::select('id')->orderBy('id', 'ASC')->get();

            foreach ($companies as $company) {

                $CompanySocialData  = CompanySocial::where('company_id', $company->id)
                    ->where('platform', 'IndiaMart')
                    ->first();

                $india_mart_api_key = $CompanySocialData?->api_key;
                $minutes_time = $CompanySocialData?->minutes_time;


                if (!empty($company->id) && !empty($india_mart_api_key)) {
                    // $endTimeObj = Carbon::now('Asia/Kolkata');
                    // $startTimeObj = (clone $endTimeObj)->subMinutes(3);

                    // $startTime = $startTimeObj->format('d-m-Y H:i:s');
                    // $endTime = $endTimeObj->format('d-m-Y H:i:s');

                    //$time_slot = ($minutes_time > 5) ? $minutes_time : 6;
                    $time_slot = ($minutes_time > 5) ? 360 : 360;

                    $now = Carbon::now('Asia/Kolkata');
                    $startTimeObj = $now->copy()->subMinutes($time_slot);
                    $endTimeObj = $now;

                    $startTime = $startTimeObj->format('d-m-Y H:i:s');
                    $endTime = $endTimeObj->format('d-m-Y H:i:s');
                    //dd($india_mart_api_key,$startTime,$endTime);
                    $url = 'https://mapi.indiamart.com/wservce/crm/crmListing/v2/';
                    $response = Http::timeout(20)->get($url, [
                        'glusr_crm_key' => $india_mart_api_key,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        // 'start_time' => '17-07-2025',
                        // 'end_time' => '17-07-2025',
                    ]);


                    $inquiry_status = MarketingStatus::where('company_id', $company->id)->where('type', 'Inquiry')->where('status', 'active')->orderBy('is_default', 'desc')->orderBy('name', 'asc')->first();
                    $followup_status = MarketingStatus::where('company_id', $company->id)->where('type', 'Followup')->where('status', 'active')->orderBy('is_default', 'desc')->orderBy('name', 'asc')->first();
                    $source_of_inquiry = SourceOfInquiry::where('company_id', $company->id)->where('name', 'Indiamart')->where('status', 'active')->orderBy('name', 'asc')->first();
                    $teams = TeamPerson::where('company_id', $company->id)->where('parent_type_id', 0)->where('status', 'active')->orderBy('id', 'asc')->first();
                    $adminSoftware = AdminSoftware::where('status', 'active')->orderBy('id', 'asc')->first();

                    if (!$source_of_inquiry) {
                        $source_of_inquiry = SourceOfInquiry::create([
                            'company_id' => $company->id,
                            'name'       => 'Indiamart',
                            'status'     => 'active',
                            'created_by' => $teams?->id ?? '',
                        ]);
                    }

                    //dd($teams->id);
                    //dd($response);
                    if ($response->successful()) {
                        $data = $response->json();

                        if ($data['CODE'] == 200 && $data['STATUS'] == 'SUCCESS') {
                            $leadCount = 0;
                            if (is_array($data['RESPONSE']) && count($data['RESPONSE']) > 0) {
                                foreach ($data['RESPONSE'] as $inquiry) {
                                    $inquiryCount = Inquiry::withTrashed()->where('company_id', $company->id)->count();
                                    $nextNumber = $inquiryCount + 1;
                                    $inquiryNo = 'INQ-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                                    $state = MasterState::where('country_id', 101)->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($inquiry['SENDER_STATE']) . '%'])->where('status', 'active')->orderBy('id', 'asc')->first();

                                    if (!$state) {
                                        $state = MasterState::create([
                                            'country_id' => 101,
                                            'name'       => $inquiry['SENDER_STATE'],
                                            'status'     => 'active',
                                            'created_by' => $teams?->id ?? '',
                                        ]);
                                    }

                                    $city = MasterCity::where('country_id', 101)->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($inquiry['SENDER_CITY']) . '%'])->where('status', 'active')->orderBy('id', 'asc')->first();

                                    if (!$city) {
                                        $city = MasterCity::create([
                                            'country_id' => 101,
                                            'state_id' => $state?->id ?? '',
                                            'name'       => $inquiry['SENDER_CITY'],
                                            'status'     => 'active',
                                            'created_by' => $teams?->id ?? '',
                                        ]);
                                    }

                                    $product = Product::where('company_id', $company->id)->whereRaw('LOWER(product_name) LIKE ?', ['%' . strtolower($inquiry['QUERY_PRODUCT_NAME']) . '%'])->where('status', 'active')->orderBy('id', 'asc')->first();

                                    if (!$product) {
                                        $product = Product::create([
                                            'company_id' => $company->id,
                                            'product_category_id' => 0,
                                            'product_sub_category_id' => 0,
                                            'product_name' => $inquiry['QUERY_PRODUCT_NAME'],
                                            'status'     => 'active',
                                            'platform'     => 'Indiamart',
                                            'created_by' => $teams?->id ?? '',
                                        ]);

                                        // ProductVariantPrice::create([
                                        //     'product_id' => $product->id,
                                        //     'variant_id' => 0,
                                        //     'including_gst' => 0,
                                        //     'price' => 0,
                                        //     'inner_pack_qty' => 0,
                                        //     'outer_pack_qty' => 0,
                                        //     'min_stock_qty' => 0,
                                        //     'max_stock_qty' => 0,
                                        // ]);
                                    }

                                    $inquiryData = Inquiry::where('platform_unique_id', $inquiry['UNIQUE_QUERY_ID'])->first();
                                    $isNewInquiry = false;

                                    if (!$inquiryData) {
                                        $isNewInquiry = true;
                                        $inquiryData = Inquiry::create([
                                            'inquiry_date' => \Carbon\Carbon::parse($inquiry['QUERY_TIME'])->format('Y-m-d'),
                                            'inquiry_no' => $inquiryNo,
                                            'company_id' => $company->id,
                                            'customer_name' => !empty($inquiry['SENDER_COMPANY']) ? $inquiry['SENDER_COMPANY'] : $inquiry['SENDER_NAME'],
                                            'contact_person_name' => $inquiry['SENDER_NAME'] ?? null,
                                            'platform_unique_id' => $inquiry['UNIQUE_QUERY_ID'] ?? null,
                                            'mobile_no' => preg_replace('/^\+91[-\s]?/', '', $inquiry['SENDER_MOBILE']) ?? null,
                                            'whatsapp_no' => preg_replace('/^\+91[-\s]?/', '', $inquiry['SENDER_MOBILE_ALT']) ?? null,
                                            'email' => $inquiry['SENDER_EMAIL'] ?? null,
                                            'inquiry_status_id' => $inquiry_status?->id ?? null,
                                            'followup_status_id' => $followup_status?->id ?? null,
                                            'source_of_inquiry_id' => $source_of_inquiry?->id ?? null,
                                            'requirement_details' => $inquiry['QUERY_MESSAGE'] ?? null,
                                            'country_id' => 101,
                                            'state_id' => $state?->id ?? '',
                                            'city_id' => $city?->id ?? '',
                                            'pincode' => $inquiry['SENDER_PINCODE'] ?? '',
                                            'address' => $inquiry['SENDER_ADDRESS'] ?? '',
                                            'platform' => 'Indiamart',
                                            'inquiry_created_by' => $teams?->id ?? '',
                                            //'inquiry_assigned_to' => $teams?->id ?? '',
                                            'platform_json' => json_encode($data['RESPONSE']),
                                            'created_type' => 'Team',
                                            'created_by' => $teams?->id ?? '',
                                        ]);
                                    }


                                    if (!empty($product->id) && !empty($inquiryData->id)) {
                                        InquiryProduct::create([
                                            'company_id' => $company->id,
                                            'inquiry_id' => $inquiryData->id,
                                            'product_id' => $product->id,
                                            'quantity' => 1,
                                            'requirements' => $inquiry['QUERY_MESSAGE'] ?? null,
                                        ]);
                                    }

                                    if ($isNewInquiry && !empty($inquiryData->id)) {
                                        Helper::sendInquiryAutoReply($inquiryData);
                                    }

                                    $leadCount++;
                                }
                            }

                            if ($leadCount > 0) {
                                Helper::sendPushNotification(array("company_id" => $company->id, "user_id" => $teams?->id, "user_type" => 'Team', 'title'=> "IndiaMart lead(s) received.", 'body'=> "Total {$leadCount} IndiaMart lead(s) received.", 'module_name'=> 'Indiamart', 'status' => 'success'));
                            }

                            Log::warning('Fetched: ' . json_encode($data['RESPONSE']));
                            $this->info("Fetched " . json_encode($data['RESPONSE']) . " leads.");
                        } else {
                            Log::warning('IndiaMart No leads or error: ' . json_encode($data));
                            //Helper::sendPushNotification(array("user_id" => $adminSoftware?->id, "user_type" => 'Admin',"title"=> " IndiaMart No leads or error ", "body"=> " No leads or error ".$data['MESSAGE'],'module_name'=> 'Indiamart', 'status' => 'success'));
                        }
                    } else {
                        Log::error('IndiaMart API request failed with status code: ' . $response->status());
                        Helper::sendPushNotification(array("user_id" => $adminSoftware?->id, "user_type" => 'Admin','module_name'=> 'Indiamart', "title"=> 'IndiaMart API request failed' , 'status' => 'error', "body"=> 'IndiaMart API request failed with status code: ' . $response->status()));
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('IndiaMart Fetch Leads command failed: ' . $e->getMessage());
            Helper::sendPushNotification(array("user_id" => $adminSoftware?->id, "user_type" => 'Admin','module_name'=> 'Indiamart', "title"=> "IndiaMart fetch error: " . $e->getMessage(), 'status' => 'error', "body"=> "IndiaMart fetch error: " . $e->getMessage()));
        }
    }
}
