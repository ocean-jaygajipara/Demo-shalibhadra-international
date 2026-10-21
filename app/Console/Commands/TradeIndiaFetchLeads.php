<?php

namespace App\Console\Commands;

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
use App\Helpers\Helper;
use App\Models\AdminSoftware;

class TradeIndiaFetchLeads extends Command
{
    protected $signature = 'app:trade-india-fetch-leads';
    protected $description = 'TradeIndia Fetch Leads CRM API every 6 minutes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
         try {
                $companies = Company::select('id')->orderBy('id', 'ASC')->get();
                foreach ($companies as $company) {
                    $CompanySocialData  = CompanySocial::where('company_id', $company->id)
                            ->where('platform', 'TradeIndia')
                            ->first();
                    $trade_india_api_key = $CompanySocialData?->api_key;
                    $trade_india_user_id = $CompanySocialData?->user_id;
                    $trade_india_profile_id = $CompanySocialData?->profile_id;
                    $minutes_time = $CompanySocialData?->minutes_time;

                    if(!empty($company->id) && !empty($trade_india_api_key) && !empty($trade_india_user_id) && !empty($trade_india_profile_id)){
                        $time_slot = ($minutes_time > 5) ? $minutes_time : 6;

                        $now = Carbon::now('Asia/Kolkata');
                        $startTimeObj = $now->copy()->subMinutes($time_slot);
                        $endTimeObj = $now;

                        $startTime = $startTimeObj->format('Y-m-d H:i:s');
                        $endTime = $endTimeObj->format('Y-m-d H:i:s');

                       // https://www.tradeindia.com/utils/my_inquiry.html?userid=2165219&profile_id=20050656&key=71e10e9bd0262ca92136674047381641&from_date=2025-07-04&to_date=2025-07-04&limit=10&page_no=1


                        $url = "https://www.tradeindia.com/utils/my_inquiry.html";
                        $query = [
                            'userid' => $trade_india_user_id,
                            'profile_id' => $trade_india_profile_id,
                            'key' => $trade_india_api_key,
                            'from_date' => $startTime,
                            'to_date' => $endTime,
                            // 'from_date' => '2025-07-04',
                            // 'to_date' => '2025-07-05',
                            'limit' => 20,
                            'page_no' => 1,
                        ];

                        $response = Http::withHeaders([
                            'User-Agent' => 'LaravelClient/1.0',
                        ])->timeout(30)->get($url, $query);

                        Log::debug("TradeIndia Response: " . $response->body());

                        $inquiry_status = MarketingStatus::where('company_id', $company->id)->where('type', 'Inquiry')->where('status', 'active')->orderBy('is_default', 'desc')->orderBy('name', 'asc')->first();
                        $followup_status = MarketingStatus::where('company_id', $company->id)->where('type', 'Followup')->where('status', 'active')->orderBy('is_default', 'desc')->orderBy('name', 'asc')->first();
                        $source_of_inquiry = SourceOfInquiry::where('company_id', $company->id)->where('name', 'Tradeindia')->where('status', 'active')->orderBy('name', 'asc')->first();
                        $teams = TeamPerson::where('company_id', $company->id)->where('parent_type_id', 0)->where('status', 'active')->orderBy('id', 'asc')->first();
                        $adminSoftware = AdminSoftware::where('status', 'active')->orderBy('id', 'asc')->first();

                        if (!$source_of_inquiry) {
                            $source_of_inquiry = SourceOfInquiry::create([
                                'company_id' => $company->id,
                                'name'       => 'Tradeindia',
                                'status'     => 'active',
                                'created_by' => $teams?->id ?? '',
                            ]);
                        }


                        $data = $response->json();
                        if (is_array($data) && count($data) > 0) {

                            if (!empty($data)) {
                                $leadCount = 0;
                                foreach ($data as $inquiry) {

                                    $inquiryCount = Inquiry::withTrashed()->where('company_id', $company->id)->count();
                                    $nextNumber = $inquiryCount + 1;
                                    $inquiryNo = 'INQ-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                                    if(isset($inquiry['sender_state'])){
                                        $state = MasterState::where('country_id', 101)->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($inquiry['sender_state']) . '%'])->where('status', 'active')->orderBy('id', 'asc')->first();

                                        if (!$state) {
                                            $state = MasterState::create([
                                                'country_id' => 101,
                                                'name'       => $inquiry['sender_state'],
                                                'status'     => 'active',
                                                'created_by' => $teams?->id ?? '',
                                            ]);
                                        }
                                    }

                                    if(isset($inquiry['sender_city'])){
                                        $city = MasterCity::where('country_id', 101)->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($inquiry['sender_city']) . '%'])->where('status', 'active')->orderBy('id', 'asc')->first();

                                        if (!$city) {
                                            $city = MasterCity::create([
                                                'country_id' => 101,
                                                'state_id' => $state?->id ?? '',
                                                'name'       => $inquiry['sender_city'],
                                                'status'     => 'active',
                                                'created_by' => $teams?->id ?? '',
                                            ]);
                                        }
                                    }

                                    if(isset($inquiry['product_name'])){
                                        $product = Product::where('company_id', $company->id)->whereRaw('LOWER(product_name) LIKE ?', ['%' . strtolower($inquiry['product_name']) . '%'])->where('status', 'active')->orderBy('id', 'asc')->first();

                                        // if (!$product) {
                                        //     $product = Product::create([
                                        //         'company_id' => $company->id,
                                        //         'product_category_id' => 0,
                                        //         'product_sub_category_id' => 0,
                                        //         'product_name' => $inquiry['product_name'],
                                        //         'status'     => 'active',
                                        //         'platform'     => 'Tradeindia',
                                        //         'created_by' => $teams?->id ?? '',
                                        //     ]);

                                        //     ProductVariantPrice::create([
                                        //         'product_id' => $product->id,
                                        //         'variant_id' => 0,
                                        //         'including_gst' => 0,
                                        //         'price' => 0,
                                        //         'inner_pack_qty' => 0,
                                        //         'outer_pack_qty' => 0,
                                        //         'min_stock_qty' => 0,
                                        //         'max_stock_qty' => 0,
                                        //     ]);
                                        // }
                                    }

                                    $inquiryData = Inquiry::where('platform_unique_id', $inquiry['rfi_id'])->first();
                                    $isNewInquiry = false;

                                    if (!$inquiryData) {
                                        $isNewInquiry = true;
                                        $inquiryData = Inquiry::create([
                                            'inquiry_date' => \Carbon\Carbon::parse($inquiry['generated_date'])->format('Y-m-d'),
                                            'inquiry_no' => $inquiryNo,
                                            'company_id' => $company->id,
                                            'customer_name' => !empty($inquiry['sender_co']) ? $inquiry['sender_co'] : $inquiry['sender_co'],
                                            'contact_person_name' => $inquiry['sender_name'] ?? null,
                                            'platform_unique_id' => $inquiry['rfi_id'] ?? null,
                                            'mobile_no' => preg_replace('/^\+91[-\s]?/', '', $inquiry['sender_mobile']) ?? null,
                                            'whatsapp_no' => preg_replace('/^\+91[-\s]?/', '', $inquiry['sender_other_mobiles']) ?? null,
                                            'inquiry_status_id' => $inquiry_status?->id ?? null,
                                            'followup_status_id' => $followup_status?->id ?? null,
                                            'source_of_inquiry_id' => $source_of_inquiry?->id ?? null,
                                            'requirement_details' => $inquiry['message'] ?? null,
                                            'country_id' => 101,
                                            'state_id' => $state?->id ?? '',
                                            'city_id' => $city?->id ?? '',
                                            'platform' => 'Tradeindia',
                                            'inquiry_created_by' => $teams?->id ?? '',
                                            'platform_json' => json_encode($inquiry),
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
                                            'requirements' => $inquiry['message'] ?? null,
                                        ]);
                                    }

                                    if ($isNewInquiry && !empty($inquiryData->id)) {
                                        Helper::sendInquiryAutoReply($inquiryData);
                                    }

                                    $leadCount++;
                                }
                                Log::warning('TradeIndia lead Fetched: ' . json_encode($data));
                                $this->info("TradeIndia lead Fetched " . json_encode($data) . " leads.");

                                if ($leadCount > 0) {
                                    Helper::sendPushNotification(array("company_id" => $company->id, "user_id" => $teams?->id, "user_type" => 'Team', 'title'=> "TradeIndia lead(s) received.", 'body'=> "Total {$leadCount} TradeIndia lead(s) received.", 'module_name'=> 'tradeindia', 'status' => 'success'));
                                }
                            } else {
                                Log::info('TradeIndia API responded but no leads found.', ['query' => $query]);
                                Helper::sendPushNotification(array("user_id" => $adminSoftware?->id, "user_type" => 'Admin',"title"=> " TradeIndia API responded but no leads found.", "body"=> " TradeIndia API responded but no leads found. ",'module_name'=> 'tradeindia', 'status' => 'success'));
                            }
                        } else {
                            Log::warning('TradeIndia API error', [
                                'response' => $data,
                                'http_code' => $response->status(),
                                'query' => $query,
                            ]);
                            Helper::sendPushNotification(array("user_id" => $adminSoftware?->id, "user_type" => 'Admin',"title"=> " TradeIndia API error.", "body"=> " TradeIndia API error. ",'module_name'=> 'tradeindia', 'status' => 'error'));
                        }

                        return Command::SUCCESS;
                    }
                }
        } catch (\Exception $e) {
            Log::error('TradeIndia Fetch Leads command failed: ' . $e->getMessage());
            Helper::sendPushNotification(array("user_id" => $adminSoftware?->id, "user_type" => 'Admin','module_name'=> 'tradeindia', "title"=> 'TradeIndia Fetch Leads command failed.' , 'status' => 'error', "body"=> 'TradeIndia Fetch Leads command failed. ' . $response->status()));
        }
    }
}
