<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\CompanySocialController;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\CompanySocial;
use App\Models\InquiryProduct;
use App\Models\MarketingStatus;
use App\Models\MasterCity;
use App\Models\MasterState;
use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\SourceOfInquiry;
use App\Models\TeamPerson;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WebhookInquiryController extends Controller
{
    public function __construct(Request $request) { }

    public function inquiry_add(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'token' => [
                    'required',
                    'string',
                    Rule::exists((new CompanySocial())->getTable(), 'api_code')
                        ->where('platform', 'website'),
                ],
                'customer_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'mobile' => ['required', 'string', 'max:20'],
                'message' => ['required', 'string', 'max:1000'],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $CompanySocialData = CompanySocial::with('company')->where('platform', 'Website')->where('api_code', $request->token)->first();

            $company_id = $CompanySocialData?->company_id;
            $api_code = $CompanySocialData?->api_code;

            if (!empty($company_id) && !empty($api_code) && ($CompanySocialData->company->status == 'active')) {

                $product_add_permission = Helper::checkPermissionModule($company_id, 'Product');

                $inquiry_status = MarketingStatus::where('company_id', $company_id)->where('type', 'Inquiry')->where('status', 'active')->orderBy('is_default', 'desc')->orderBy('name', 'asc')->first();
                $followup_status = MarketingStatus::where('company_id', $company_id)->where('type', 'Followup')->where('status', 'active')->orderBy('is_default', 'desc')->orderBy('name', 'asc')->first();
                $source_of_inquiry = SourceOfInquiry::where('company_id', $company_id)->where('name', 'Website')->where('status', 'active')->orderBy('name', 'asc')->first();
                $teams = TeamPerson::where('company_id', $company_id)->where('parent_type_id', 0)->where('status', 'active')->orderBy('id', 'asc')->first();

                if (!$source_of_inquiry) {
                    $source_of_inquiry = SourceOfInquiry::create([
                        'company_id' => $company_id,
                        'name'       => 'Website',
                        'status'     => 'active',
                        'created_by' => $teams?->id ?? '',
                    ]);
                }

                $inquiryCount = Inquiry::withTrashed()->where('company_id', $company_id)->count();
                $nextNumber = $inquiryCount + 1;
                $inquiryNo = 'INQ-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);


                if(isset($request->state) && !empty($request->state)){
                    $state = MasterState::where('country_id', 1)->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($request->state) . '%'])->where('status', 'active')->orderBy('id', 'asc')->first();
                    if (!$state) {
                        $state = MasterState::create([
                            'country_id' => 1,
                            'name'       => $request->state,
                            'status'     => 'active',
                            'created_by' => $teams?->id ?? '',
                        ]);
                    }
                }


                if(isset($request->city) && !empty($request->city)){
                    $city = MasterCity::where('country_id', 1)->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($request->city) . '%'])->where('status', 'active')->orderBy('id', 'asc')->first();

                    if (!$city) {
                        $city = MasterCity::create([
                            'country_id' => 1,
                            'state_id' => $state?->id ?? '',
                            'name'       => $request->city,
                            'status'     => 'active',
                            'created_by' => $teams?->id ?? '',
                        ]);
                    }
                }

                if ($product_add_permission->add_flag) {
                    if(isset($request->product) && !empty($request->product)){
                        $product = Product::where('company_id', $company_id)->whereRaw('LOWER(product_name) LIKE ?', ['%' . strtolower($request->product) . '%'])->where('status', 'active')->orderBy('id', 'asc')->first();

                        if (!$product) {
                            $product = Product::create([
                                'company_id' => $company_id,
                                'product_category_id' => 0,
                                'product_sub_category_id' => 0,
                                'product_name' => $request->product,
                                'status'     => 'active',
                                'platform'     => 'Website',
                                'created_by' => $teams?->id ?? '',
                            ]);

                            ProductVariantPrice::create([
                                'product_id' => $product->id,
                                'variant_id' => 0,
                                'including_gst' => 0,
                                'price' => 0,
                                'inner_pack_qty' => 0,
                                'outer_pack_qty' => 0,
                                'min_stock_qty' => 0,
                                'max_stock_qty' => 0,
                            ]);
                        }
                    }
                }


                $inquiryData = Inquiry::create([
                    'inquiry_date' => \Carbon\Carbon::now()->format('Y-m-d'),
                    'inquiry_no' => $inquiryNo,
                    'company_id' => $company_id,
                    'customer_name' => $request->customer_name ?? null,
                    'contact_person_name' => $request->contact_person_name ?? null,
                    'mobile_no' => preg_replace('/^\+91[-\s]?/', '', $request->mobile) ?? null,
                    'whatsapp_no' => preg_replace('/^\+91[-\s]?/', '', $request->whatsapp_no) ?? null,
                    'inquiry_status_id' => $inquiry_status?->id ?? null,
                    'followup_status_id' => $followup_status?->id ?? null,
                    'source_of_inquiry_id' => $source_of_inquiry?->id ?? null,
                    'requirement_details' => trim(($request->product ? "Product: " . $request->product . "\n" : '') . ($request->message ?? '')),
                    'country_id' => 1,
                    'state_id' => $state?->id ?? '',
                    'city_id' => $city?->id ?? '',
                    'pincode' => $request->pincode ?? '',
                    'address' => $request->address ?? '',
                    'platform' => 'Website',
                    'inquiry_created_by' => $teams?->id ?? '',
                    'platform_json' => json_encode($request->all()),
                    'created_type' => 'Team',
                    'created_by' => $teams?->id ?? '',
                ]);

                if ($product_add_permission->add_flag) {
                    if (!empty($product->id) && !empty($inquiryData->id)) {
                        InquiryProduct::create([
                            'company_id' => $company_id,
                            'inquiry_id' => $inquiryData->id,
                            'product_id' => $product->id,
                            'quantity' => 1,
                            'requirements' => $request->message ?? null,
                        ]);
                    }
                }

                if (!empty($inquiryData->id)) {
                    Helper::sendInquiryAutoReply($inquiryData);
                }

                Log::warning('Webhook Inquiry Fetched: ' . json_encode($inquiryData));

                return $this->sendResponse($inquiryData, "Webhook Inquiry Fetched Successfully.");
            }else {
                Log::info('Webhook Inquiry Fetched API responded but company not active', ['query' => $request->all()]);
            }
        } catch (\Exception $e) {
             return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in Webhook inquiry add API");
    }
}
