<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\Company;
use App\Models\Customer;
use App\Models\MasterTransportBy;
use App\Models\MasterTransportDetail;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SalesOrderItems;
use App\Models\RolePermissionsApp;
use App\Models\SalesStatus;
use App\Models\TeamPerson;
use App\Models\TermsAndCondition;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SalesOrderController extends Controller
{
    public $modules = [];
    public function __construct(Request $request)
    {
        parent::__construct();

        $this->modules = [
            'table_name' => (new SalesOrder())->getTable(),
            'module_name' => 'My Order',
        ];
    }

    /**
     * SalesOrder List
     */
    public function salesorder_list(Request $request)
    {
        $modules = $this->modules;
        try {
            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            $company_id = $request?->company_id ?? $loginUser?->company_id;

            $modules['authLoginUserDetail'] = $loginUser ?? null;
            $modules['company_id'] = $company_id ?? null;
            $modules['parent_type_id'] = $loginUser?->parent_type_id ?? null;

            foreach (config('constants.app_permissions') as $key => $value) {
                $modules[$value . '_permission'] = Helper::checkAppPermission("app", (new RolePermissionsApp())->getTable(), $modules['company_id'], $loginUser?->id, $modules['module_name'], $value . '_flag');
            }

            // return $modules;
            if (!$modules['view_permission']) {
                return $this->sendError('You do not have permission to access the '.$modules['module_name'].' module.', [], [], 403);
            }


            $validator =  Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('status', 'active');
                        }),
                    ],
                'team_person_id' => [
                    'nullable',
                    Rule::exists((new TeamPerson())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            $query->where('status', 'active')->where('company_id', $request?->company_id);
                        }),
                ],
                'filter_by_status' => [
                    'nullable',
                    function ($attribute, $value, $fail) use ($request) {
                        if ($value !== 'all') {
                            $exists = SalesStatus::where('salestype', 'Sales Orders')->where('company_id', $request?->company_id)->where('status', 'active')->where('id', $value)->exists();
                            if (!$exists) {
                                $fail('The selected status is invalid.');
                            }
                        }
                    },
                ],
                'from_date' => ['nullable', 'date', 'date_format:Y-m-d'],
                'to_date' => ['nullable', 'date', 'date_format:Y-m-d']
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $loginUserId = $loginUser?->id;

            /* Pagination */
            $pagination = (isset($request?->page)) ? true : false;
            $perPage = (isset($request?->per_page)) ? $request?->per_page : env("API_PER_PAGE");
            $page = (isset($request?->page)) ? $request?->page : 1;

            $module = SalesOrder::where('company_id', $company_id);


            if (isset($modules['personal_data_permission']) && isset($modules['personal_data_permission'])) {
                if ($modules['all_data_permission']) {
                } else if ($modules['all_data_permission'] == false && $modules['personal_data_permission']) {
                    $module = $module->where('created_by', $loginUserId);
                } else {
                    $module = $module->where('created_by', $loginUserId);
                }
            }

            if ($request?->team_person_id) {
                $module = $module->where('created_by', $request?->team_person_id)->where('created_type', 'team');
            }

            if ($request?->filter_by_status && $request->filter_by_status !== 'all') {
                $module = $module->where('sales_status_id', $request?->filter_by_status);
            }

            if ($request?->filter_by_sales_order_no) {
                $module = $module->where('sales_order_no', $request?->filter_by_sales_order_no);
            }

            if ($request?->filter_by_sales_order_id) {
                $module = $module->where('id', $request?->filter_by_sales_order_id);
            }

            if ($request?->search && !empty($request?->search)) {
                $module = $module->where(function ($q) use ($request) {
                    $q->where('sales_order_no', 'like', '%' . $request->search . '%')
                    ->orWhereHas('customer', function ($q2) use ($request) {
                        $q2->where('customer_name', 'like', '%' . $request->search . '%');
                    });
                });
            }

            if ($request?->from_date && !$request?->to_date) {
                $module = $module->whereDate('sales_order_date', "==", Carbon::parse($request?->from_date));
            } else if ($request?->from_date && $request?->to_date) {
                $module = $module->whereBetween('sales_order_date', [Carbon::parse($request?->from_date), Carbon::parse($request?->to_date)]);
            }
            // dd('Inquiry 84', Helper::interpolateQuery($module->toSql(), $module->getBindings()), $module->first(), $loginUser->toArray());
            $module = $module->orderBy('id', 'DESC');

            if ($request?->toSql) {
                return Helper::interpolateQuery($module?->toSql(), $module->getBindings());
            }
            $tempResponseData = [];
            if ($pagination) {
                $module = $module->paginate($perPage);

                $tempResponseData['total'] = "" . $module->total();
                $tempResponseData['perPage'] = $perPage;
                $tempResponseData['currentPage'] = $page;
                $tempResponseData['totalPage'] = "" . $module->lastPage();
                $tempResponseData['items'] = (object)$module->items();
                $tempResponseData['items'] = collect($tempResponseData['items']);
            } else {
                $module = $module->get();
                $tempResponseData['total'] = $module->count() . "";
                $tempResponseData['items'] = $module;
            }

            if (isset($tempResponseData['items'])) {
                $tempResponseData['items'] = $tempResponseData['items']->map(function ($record) use ($request) {
                    $temp = [];


                    $temp['id'] = $record?->id . "";
                    $temp['company_id'] = $record?->company_id . "";
                    $temp['company_name'] = $record?->company?->company_name . "";

                    $temp['quotation_id'] = $record?->quotation_id . "";
                    $temp['sales_order_no'] = $record?->sales_order_no . "";
                    $temp['sales_order_date'] = $record?->sales_order_date . "";
                    $temp['expected_dispatch_date'] = $record?->expected_dispatch_date . "";

                    if(!empty($record?->quotation_id) && $record?->quotation_id > 0){
                        $temp['quotation_no'] =  Quotation::where('company_id', $record->company_id)
                                                    ->where('id', $record->quotation_id)
                                                    ->pluck('quotation_no')
                                                    ->first();
                    }

                    if ($record?->created_type == 'admin') {
                        $temp['team_person_name'] = $record?->admin_person->name;
                    } else {
                        $temp['team_person_name'] = $record?->team_person->name;
                    }
                    $temp['grand_total'] = $record?->grand_total . "";

                    $temp['customer_id'] = $record?->customer_id . "";
                    $temp['customer_name'] = $record?->customer?->customer_name . "";

                    $temp['contact_person_name'] = $record?->contact_person_name . "";
                    $temp['phone'] = $record?->phone . "";

                    $temp['sales_status_id'] = $record?->sales_status_id . "";
                    $temp['sales_status_name'] = $record?->sales_status?->statusname . "";
                    $temp['sales_status_color'] = $record?->sales_status?->status_color . "";

                    $temp['status'] = $record?->status . "";
                    $temp['created_by'] = $record?->created_by . "";
                    $temp['created_by_name'] = ($record?->created_type == 'admin') ?  $record->admin_person?->name : $record->team_person?->name ;


                    $salesStatusesLast = SalesStatus::where('company_id', $record?->company_id)
                                    ->where('salestype', 'Sales Orders')
                                    ->select("id")
                                    ->orderBy('display_order', 'desc')
                                    ->first();

                    if (!empty($salesStatusesLast->id) && !empty($record?->sales_status_id) && $salesStatusesLast->id > 0 && ((int)$record?->sales_status_id != (int)$salesStatusesLast->id)) {
                        $temp['is_edit'] = true;
                    }else{
                        $temp['is_edit'] = false;
                    }
                     $temp['is_revise'] = ($record?->sales_status?->is_revise == 1) ? true : false;
                     $temp['is_repeat'] = ($record?->sales_status?->is_repeat == 1) ? true : false;

                    if (!empty($request?->filter_by_sales_order_no) || !empty($request?->filter_by_sales_order_id)) {
                        $temp['term_condition_id'] = $record?->term_condition_id . "";
                        $temp['term_condition_title'] = $record?->term_condition?->title . "";


                        $temp['state'] = $record?->state . "";
                        $temp['gst_no'] = $record?->gst_no . "";

                        $temp['price_list_id'] = $record?->price_list_id . "";
                        $temp['price_list_name'] = $record?->price_list?->name . "";

                        $temp['address'] = $record?->address . "";
                        $temp['shipping_address'] = $record?->shipping_address . "";
                        $temp['billing_address'] = $record?->billing_address . "";

                        $temp['transport_by_id'] = $record?->transport_by_id . "";
                        $temp['transport_by_name'] = $record?->transport_by?->name . "";

                        $temp['transport_details_id'] = $record?->transport_details_id . "";
                        $temp['transport_details_name'] = $record?->transport_detail?->name . "";

                        $temp['cash_discount_percentage'] = $record?->cash_discount_percentage . "";
                        $temp['cash_discount_amount'] = $record?->cash_discount_amount . "";
                        $temp['additional_discount_percentage'] = $record?->additional_discount_percentage . "";
                        $temp['additional_discount_amount'] = $record?->additional_discount_amount . "";
                        $temp['packing_forwarding_charge'] = $record?->packing_forwarding_charge . "";
                        $temp['transport_charge'] = $record?->transport_charge . "";
                        $temp['freight_charge'] = $record?->freight_charge . "";
                        $temp['total_taxable'] = $record?->total_taxable . "";
                        $temp['is_gst_applicable'] = $record?->is_gst_applicable . "";
                        $temp['gst_amount'] = $record?->gst_amount . "";
                        $temp['is_tcs_applicable'] = $record?->is_tcs_applicable . "";
                        $temp['tcs_amount'] = $record?->tcs_amount . "";
                        $temp['round_off'] = $record?->round_off . "";
                        $temp['grand_total'] = $record?->grand_total . "";
                        $temp['master_quantity'] = $record?->master_quantity . "";
                        $temp['master_amount'] = $record?->master_amount . "";
                        $temp['master_taxable_amount'] = $record?->master_taxable_amount . "";
                        $temp['master_gst_amount'] = $record?->master_gst_amount . "";
                        $temp['master_sub_total'] = $record?->master_sub_total . "";
                        $temp['term_and_condition'] = $record?->term_and_condition . "";
                        $temp['regards'] = $record?->regards . "";
                        $temp['notes'] = $record?->notes . "";
                        $temp['latitude'] = $record?->latitude . "";
                        $temp['longitude'] = $record?->longitude . "";
                        $temp['sales_order_repeat'] = $record?->sales_order_repeat . "";


                        $temp['sales_order_items'] = $record->sales_order_items->map(function ($item) {
                            return [
                                'id' => $item->id . "",
                                'quotation_item_id' => $item?->quotation_item_id . "",
                                'sales_order_id' => $item?->sales_order_id . "",
                                'product_id' => $item?->product_id . "",
                                'product_variant_price_id' => $item?->product_variant_price_id . "",
                                'price_list_product_id' => $item?->price_list_product_id . "",
                                'display_order' => $item?->display_order . "",
                                'product_name' => $item?->product_name . "",
                                'product_description' => $item->product_description . "",
                                'hsn_code' => $item->hsn_code . "",
                                'outer_quantity' => $item->outer_quantity . "",
                                'inner_quantity' => $item->inner_quantity . "",
                                'loose_quantity' => $item->loose_quantity . "",
                                'kg_quantity' => $item->kg_quantity . "",
                                'unit' => $item->unit . "",
                                'quantity' => $item->quantity . "",
                                'remaining_quantity' => $item->remaining_quantity . "",
                                'price_in_amount' => $item->price_in_amount . "",
                                'discount_flat' => $item->discount_flat . "",
                                'discount_percentage' => $item->discount_percentage . "",
                                'rate_in_amount' => $item->rate_in_amount . "",
                                'amount' => $item->amount . "",
                                'taxable_amount' => $item->taxable_amount . "",
                                'gst_in_percentage' => $item->gst_in_percentage . "",
                                'gst_amount' => $item->gst_amount . "",
                                'sub_total' => $item->sub_total . "",
                            ];
                        })->toArray();
                    }

                    return $temp;
                });

                return $this->sendResponse($tempResponseData, "Your SalesOrder List.");
            }
            return $this->sendError("Something want to wrong in data fetching.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    /**
     * SalesOrder Add / Edit
     * company_id is Required
     * */
    public function salesorder_add_edit(Request $request, $edit_id = null)
    {
        $modules = $this->modules;

        DB::beginTransaction();
        try {

            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            if ($loginUser?->company_id) {
                $request['company_id'] = $loginUser?->company_id;
            }

            $company_id = $request?->company_id ?? $loginUser?->company_id;
            $modules['company_id'] = $request['company_id'] = $company_id;
            $edit_id = $request?->edit_id;


            foreach (config('constants.app_permissions') as $key => $value) {
                $modules[$value . '_permission'] = Helper::checkAppPermission("app", (new RolePermissionsApp())->getTable(), $modules['company_id'], $loginUser?->id, $modules['module_name'], $value . '_flag');
            }

            // return $modules;
            if ($request?->edit_id) {
                if (!$modules['update_permission']) {
                    return $this->sendError('You do not have permission to access the '.$modules['module_name'].' module.', [], [], 403);
                }
            }else{
                if (!$modules['add_permission']) {
                    return $this->sendError('You do not have permission to access the '.$modules['module_name'].' module.', [], [], 403);
                }
            }


            if(!in_array($request->type, ['customer', 'products', 'terms'])) {
                return $this->sendError("Invalid type. Allowed values are 'customer' or 'terms' or 'products'.", [], [], 422);
            }

            if (in_array($request->type, ['products', 'terms']) && empty($edit_id)) {
                return $this->sendError("You need to first create a customer salesorder before adding {$request->type} details.", [], [], 422);
            }

            if ($request->type === 'customer') {
                $rules = [
                   'company_id' => [
                        'required',
                        Rule::exists((new Company())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('status', 'active');
                        }),
                    ],
                    'customer_id' => [
                        'required',
                        Rule::exists((new Customer())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('status', 'active')->where('company_id', $request->company_id);
                        }),
                    ],
                    // 'sales_order_no' => [
                    //     'required',
                    //     'string',
                    //     'max:255',
                    //     Rule::unique((new SalesOrder())->getTable())
                    //         ->where(function ($query) use ($request) {
                    //             return $query->where('company_id', $request->company_id)
                    //                         ->whereNull('deleted_at');
                    //         })
                    //         ->ignore($edit_id), // Use $edit_id for update, null for create
                    // ],
                    'sales_order_date' => [
                        'required',
                        'date',
                        'date_format:Y-m-d',
                        function ($attribute, $value, $fail) {
                            $date = Carbon::createFromFormat('Y-m-d', $value);
                            if ($date->lt(now()->subMonth())) {
                                $fail('The salesorder date must not be older than 1 month from today.');
                            }
                        }
                    ],
                    'transport_by_id' => [
                        'nullable',
                        Rule::exists((new MasterTransportBy())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('status', 'active')->where('company_id', $request->company_id);
                        }),
                    ],
                    'transport_details_id' => [
                        'nullable',
                        Rule::exists((new MasterTransportDetail())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('status', 'active')->where('company_id', $request->company_id);
                        }),
                    ],
                    'gst_no' => ['nullable', 'digits:14'],
                    'contact_person_name' => ['required', 'string'],
                    'phone' => ['required', 'digits:10'],
                    'state' => ['required', 'string'],
                    'address' => ['required', 'string'],
                    'price_list_id' => [
                        'required',
                        Rule::exists((new PriceList())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('status', 'active')->where('company_id', $request->company_id);
                        }),
                    ],
                    'shipping_address' => ['nullable', 'string'],
                    'billing_address' => ['nullable', 'string'],
                    'platform' => ['required', 'in:web,android,ios'],
                ];
            } elseif ($request->type === 'products') {
                $rules = [
                    'company_id' => [
                        'required',
                        Rule::exists((new Company())->getTable(), 'id')->where(fn($q) => $q->where('status', 'active')),
                    ],
                    'product_id' => ['required', 'array'],
                    'product_variant_price_id' => ['required', 'array'],
                    'price_list_product_id' => ['required', 'array'],
                    'product_name' => ['required', 'array'],
                    'product_description' => ['nullable', 'array'],
                    'hsn_code' => ['nullable', 'array'],
                    'outer' => ['nullable', 'array'],
                    'inner' => ['nullable', 'array'],
                    'loose' => ['nullable', 'array'],
                    'kg' => ['nullable', 'array'],
                    'unit' => ['nullable', 'array'],
                    'quantity' => ['required', 'array'],
                    'price' => ['required', 'array'],
                    'discount_flat' => ['nullable', 'array'],
                    'discount_percentage' => ['nullable', 'array'],
                    'rate_in_amount' => ['nullable', 'array'],
                    'amount' => ['nullable', 'array'],
                    'taxable_amount' => ['nullable', 'array'],
                    'gst_in_percentage' => ['nullable', 'array'],
                    'gst_amount' => ['nullable', 'array'],
                    'subtotal' => ['nullable', 'array'],
                    'platform' => ['required', 'in:web,android,ios'],

                    'cash_discount_percentage' => ['nullable'],
                    'cash_discount_amount' => ['nullable'],
                    'additional_discount_percentage' => ['nullable'],
                    'additional_discount_amount' => ['nullable'],
                    'packing_forwarding_charge' => ['nullable'],
                    'transport_charge' => ['nullable'],
                    'freight_charge' => ['nullable'],
                    'total_taxable' => ['nullable'],
                    'is_gst_applicable' => ['nullable'],
                    'gst_amount' => ['nullable'],
                    'is_tcs_applicable' => ['nullable'],
                    'tcs_amount' => ['nullable'],
                    'round_off' => ['nullable'],
                    'grand_total' => ['required'],
                    'master_quantity' => ['required'],
                    'master_amount' => ['required'],
                    'master_taxable_amount' => ['required'],
                    'master_gst_amount' => ['required'],
                    'master_sub_total' => ['required'],
                ];

            } elseif ($request->type === 'terms') {
                $rules = [
                    'company_id' => [
                        'required',
                        Rule::exists((new Company())->getTable(), 'id')
                            ->where(fn($q) => $q->where('status', 'active')),
                    ],
                    'term_condition_id' => [
                        'nullable',
                        Rule::exists((new TermsAndCondition())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('status', 'active')->where('company_id', $request->company_id);
                        }),
                    ],
                    'term_and_condition' => ['nullable', 'string'],
                    'regards' => ['nullable', 'string'],
                    'notes' => ['nullable', 'string'],
                    'platform' => ['required', 'in:web,android,ios'],
                ];
            }


            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 422);
            }
            $loginUserId = $loginUser?->id;

            $input = $request->except(['edit_id']);
            $returnMessage = "SalesOrder added successfully.";


            if (in_array($request->type, ['customer']) && empty($edit_id)) {
                $generate_sales_order_no = (new CommonController(new Request()))->generate_sales_order_no($request);
                if ($generate_sales_order_no->getStatusCode() == 200) {
                    $dataResponse = $generate_sales_order_no?->getData();
                    $input['sales_order_no'] = $dataResponse->data;
                    // if (!empty($dataResponse?->data) && $dataResponse->data !== $request->sales_order_no) {
                    //     return $this->sendError("The salesorder number is invalid or does not match the generated format.", [], [], 422);
                    // }
                }
            }

            if ($request?->edit_id) {
                $salesStatusesLast = SalesStatus::where('company_id', $request->company_id)->where('salestype', 'Sales Orders')->select("id")->orderBy('display_order', 'desc')->first();
                if (!empty($salesStatusesLast->id) && !empty($request->sales_status_id) && $salesStatusesLast->id > 0 && ((int)$request->sales_status_id == (int)$salesStatusesLast->id)) {
                    return $this->sendError(
                        "You cannot update salesorder records after order full dispatched.",
                        ['sales_status_id' => 'order has already been full dispatched from this Salesorder. No further edits are allowed.'],
                        [],
                        422
                    );

                }
            }

            if ($request?->edit_id) {
                $input['updated_type'] = 'Team';
                $input['updated_by'] = $loginUserId;
                $returnMessage = "Update SalesOrder detail successfully.";

                $salesorderAddEdit = SalesOrder::where('id', $request?->edit_id);
                $salesorderAddEdit = $salesorderAddEdit->where('company_id', $company_id);
                $salesorderAddEdit = $salesorderAddEdit->first();
                if (!$salesorderAddEdit) {
                    return $this->sendError("Your SalesOrder Id not Found. Try Again.");
                }
                $salesorderAddEdit->update($input);

                if ($request->type === 'products') {
                    $company = Company::find($company_id);

                        // Per-product loop validation
                        if ($request->has('product_name')) {
                            foreach ($request->product_name as $key => $product) {
                                if (is_null($product) || trim($product) === '') {
                                    return $this->sendError("Product name is required at index $key.", [
                                        "product_name.$key" => "Product name is required."
                                    ], [], 422);
                                }

                                $quantity = $request->quantity[$key] ?? null;
                                if (!isset($quantity) || $quantity === '' || floatval($quantity) <= 0) {
                                    return $this->sendError("Quantity must be greater than 0 at index $key.", [
                                        "quantity.$key" => "Quantity must be greater than 0."
                                    ], [], 422);
                                }

                                $price = $request->price[$key] ?? null;
                                if (!isset($price) || $price === '' || floatval($price) <= 0) {
                                    return $this->sendError("Price must be greater than 0 at index $key.", [
                                        "price.$key" => "Price must be greater than 0."
                                    ], [], 422);
                                }

                                $productId = $request->product_id[$key] ?? null;
                                $variantPriceId = $request->product_variant_price_id[$key] ?? null;
                                $companyId = $validated['company_id'] ?? $request->company_id;

                                if (!empty($variantPriceId)) {
                                    $productExists = Product::where('id', $productId)
                                        ->where('company_id', $companyId)
                                        ->whereHas('product_variant_prices', function ($q) use ($variantPriceId) {
                                            $q->where('id', $variantPriceId);
                                        })->exists();

                                    if (!$productExists) {
                                        return $this->sendError("Invalid product or variant at index $key.", [
                                            "product_variant_price_id.$key" => "Product variant doesn't belong to the selected company or product."
                                        ], [], 422);
                                    }
                                }

                            }
                        }

                        // Total taxable validation
                        if (!isset($request->total_taxable) || $request->total_taxable === '' || floatval($request->total_taxable) <= 0) {
                            return $this->sendError("Total taxable must be greater than 0.", [
                                "total_taxable" => "Total taxable must be greater than 0."
                            ], [], 422);
                        }

                    if ($request->has('product_name')) {
                        SalesOrderItems::where('sales_order_id', $edit_id)->delete();

                        foreach ($request->product_name as $key => $product) {
                            $data = [
                                'company_id'   => $company_id,
                                'sales_order_id' => $edit_id,
                                'product_id' => $request->product_id[$key],
                                'product_variant_price_id' => $request->product_variant_price_id[$key],
                                'price_list_product_id' => $request->price_list_product_id[$key],
                                'display_order' => ($key + 1),
                                'product_name' => $product,
                                'product_description' => $request->product_description[$key] ?? null,
                                'hsn_code' => $request->hsn_code[$key] ?? null,
                                'outer_quantity' => $request->outer[$key] ?? null,
                                'inner_quantity' => $request->inner[$key] ?? null,
                                'loose_quantity' => $request->loose[$key] ?? null,
                                'kg_quantity' => $request->kg[$key] ?? null,
                                'unit' => $request->unit[$key] ?? null,
                                'quantity' => $request->quantity[$key] ?? null,
                                'price_in_amount' => $request->price[$key] ?? null,
                                'discount_flat' => $request->discount_flat[$key] ?? null,
                                'discount_percentage' => $request->discount_percentage[$key] ?? null,
                                'rate_in_amount' => $request->rate_in_amount[$key] ?? null,
                                'amount' => $request->amount[$key] ?? null,
                            ];

                            if ($company->tax_type == 'product_wise') {
                                $data += [
                                    'taxable_amount' => $request->taxable_amount[$key] ?? null,
                                    'gst_in_percentage' => $request->gst_in_percentage[$key] ?? null,
                                    'gst_amount' => $request->gst_amount[$key] ?? null,
                                    'sub_total' => $request->subtotal[$key] ?? null,
                                ];
                            } else {
                                $data += [
                                    'taxable_amount' => '0.00',
                                    'gst_in_percentage' => '0.00',
                                    'gst_amount' => '0.00',
                                    'sub_total' => '0.00',
                                ];
                            }
                            SalesOrderItems::create($data);
                        }
                    }

                    $salesorderAddEdit = SalesOrder::with('sales_order_items')
                                ->where('id', $edit_id)
                                ->where('company_id', $request->company_id)
                                ->first();
                }
            }else {
                $input['created_type'] = 'team';
                $input['created_by'] = $loginUserId;
                $salesStatus = SalesStatus::where('salestype', 'Sales Orders')->where('company_id', $request->company_id)->where('display_order', 1)->first();
                $input['sales_status_id'] = $salesStatus->id ?? null;

                //dd($input);
                $salesorderAddEdit = SalesOrder::create($input);
            }

            DB::commit();
            return $this->sendResponse($salesorderAddEdit, $returnMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage());
        }
        DB::rollBack();
        return $this->sendError("Something want to wrong in register API");
    }

    /**
     * SalesOrder delete
     * company_id is nullable when token is exist
     */
    public function salesorder_delete(Request $request)
    {
        $modules = $this->modules;
        try {

            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            if ($loginUser?->company_id) {
                $request['company_id'] = $loginUser?->company_id;
            }
            $company_id = $request?->company_id ?? $loginUser?->company_id;
            $modules['authLoginUserDetail'] = $loginUser ?? null;
            $modules['company_id'] = $company_id ?? null;
            $modules['parent_type_id'] = $loginUser?->parent_type_id ?? null;

            foreach (config('constants.app_permissions') as $key => $value) {
                $modules[$value . '_permission'] = Helper::checkAppPermission("app", (new RolePermissionsApp())->getTable(), $modules['company_id'], $loginUser?->id, $modules['module_name'], $value . '_flag');
            }

            // return $modules;
            if (!$modules['delete_permission']) {
                return $this->sendError('You do not have permission to access the '.$modules['module_name'].' module.', [], [], 403);
            }

            $validator =  Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'salesorder_id' => [
                    'required',
                    Rule::exists((new SalesOrder())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('status', 'active')->where('company_id', $request->company_id);
                        }),
                ],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $salesOrderDelete = SalesOrder::where('company_id', $company_id)->where('id', $request?->salesorder_id)->first();

            if (!$salesOrderDelete) {
                return $this->sendError("Your SalesOrder not found contect to admin.");
            }
            $salesOrderDelete->deleted_by = $loginUser?->id;
            $salesOrderDelete->deleted_at = Helper::trait_current_date();
            $salesOrderDelete->save();


            return $this->sendResponse($salesOrderDelete, "Your SalesOrder Delete Successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }


    /**
     * SalesOrder approve
     * company_id is nullable when token is exist
     */
    public function salesorder_approve_status(Request $request)
    {
        $modules = $this->modules;
        try {

            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            if ($loginUser?->company_id) {
                $request['company_id'] = $loginUser?->company_id;
            }
            $company_id = $request?->company_id ?? $loginUser?->company_id;

            $modules['authLoginUserDetail'] = $loginUser ?? null;
            $modules['company_id'] = $company_id ?? null;
            $modules['parent_type_id'] = $loginUser?->parent_type_id ?? null;

            foreach (config('constants.app_permissions') as $key => $value) {
                $modules[$value . '_permission'] = Helper::checkAppPermission("app", (new RolePermissionsApp())->getTable(), $modules['company_id'], $loginUser?->id, $modules['module_name'], $value . '_flag');
            }

            // return $modules;
            if (!$modules['approval_permission']) {
                return $this->sendError('You do not have permission to access the '.$modules['module_name'].' module.', [], [], 403);
            }

            $validator =  Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'salesorder_id' => [
                    'required',
                    Rule::exists((new SalesOrder())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('status', 'active')->where('company_id', $request->company_id);
                        }),
                ],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $salesOrder = SalesOrder::withTrashed()->findOrFail($request?->salesorder_id);
            if (!$salesOrder) {
                return $this->sendError("SalesOrder not found contect to admin.");
            }

            $sales_approve_id = Helper::getSalesStatusNameById($company_id, "app", 'Sales Orders');

            $salesStatusesAfterApproval = SalesStatus::where('company_id', $company_id)
                ->where('salestype', 'Sales Orders')
                ->where('id', '>', $sales_approve_id)
                ->pluck('id')
                ->toArray();

            if (in_array($salesOrder?->sales_status_id, $salesStatusesAfterApproval)) {
                return $this->sendError("This Sales Order has already progressed beyond the approval stage and cannot be updated.");
            }

            // Check if already approved
            if (!empty($sales_approve_id) && (int)$sales_approve_id === (int)$salesOrder?->sales_status_id) {
                return $this->sendError("This Sales Order is already approved.");
            }

            // Perform status update
            if (!empty($sales_approve_id) && $sales_approve_id > 0) {
                $salesOrder->sales_status_id = $sales_approve_id;
                $salesOrder->save();
            } else {
                return $this->sendError("Invalid status update attempted.");
            }

            return $this->sendResponse($salesOrder, "salesOrder Approve Successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }


     /**
     * SalesOrder clone(revise or repeat)
     * company_id is nullable when token is exist
     */
    public function salesorder_clone(Request $request)
    {
        try {

            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            if ($loginUser?->company_id) {
                $request['company_id'] = $loginUser?->company_id;
            }
            $company_id = $request?->company_id ?? $loginUser?->company_id;

            $validator =  Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'salesorder_id' => [
                    'required',
                    Rule::exists((new SalesOrder())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('status', 'active')->where('company_id', $request?->company_id);
                        }),
                ],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $originalSalesOrder = SalesOrder::with(['sales_order_items'])->withTrashed()->findOrFail($request?->salesorder_id);
            if (!$originalSalesOrder) {
                return $this->sendError("SalesOrder not found contect to admin.");
            }
            $loginUserId = $loginUser?->id;
            if ($originalSalesOrder) {


                if($originalSalesOrder?->sales_status?->is_revise != 1){
                    return $this->sendError("This SalesOrder does not have revise permission.");
                }

                if($originalSalesOrder?->sales_status?->is_repeat != 1){
                    return $this->sendError("This SalesOrder does not have repeat permission.");
                }

                //get last quotation no
                $quotationCount = SalesOrder::withTrashed()->where('company_id', $originalSalesOrder->company_id)->count();
                $nextNumber = $quotationCount + 1;
                $salesOrderNo = 'SO-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                $status_data = SalesStatus::where('company_id', $company_id)
                                ->where('salestype', 'Sales Orders')
                                ->where('display_order', 1)
                                ->first();


                $newSalesOrder = $originalSalesOrder->replicate();
                $newSalesOrder->parent_sales_order_id = $originalSalesOrder->sales_order_no;
                $newSalesOrder->sales_order_no = $salesOrderNo;
                $newSalesOrder->sales_order_date = date('Y-m-d');
                $newSalesOrder->expected_dispatch_date = date('Y-m-d');
                $newSalesOrder->quotation_id = '';
                $newSalesOrder->sales_order_repeat = $request->type === 'repeat' ? 'repeat' : '';
                $newSalesOrder->sales_status_id = $status_data->id;
                $newSalesOrder->created_by = $loginUserId;
                $newSalesOrder->created_at = now();
                $newSalesOrder->save();

                $clonedItems = [];
                foreach ($originalSalesOrder->sales_order_items as $item) {
                    $newItem = $item->replicate();
                    $newItem->sales_order_id = $newSalesOrder->id;
                    $newItem->quotation_item_id = '';
                    $newItem->created_at = now();
                    $newItem->save();

                    $clonedItems[] = $newItem;
                }
                $responseData = $newSalesOrder->toArray();
                $responseData['sales_order_items'] = $clonedItems;
                unset($responseData['sales_status']);

                return $this->sendResponse($responseData, "SalesOrder ".$request->type . " successfully");
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }
}
