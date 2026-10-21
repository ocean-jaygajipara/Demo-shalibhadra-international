<?php

namespace App\Http\Controllers;

use App\Models\PackingSlip;
use App\Models\PackingType;
use App\Models\PaymentTerm;
use App\Models\PurposeVisitMaster;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\CompanyDetails;
use App\Models\VisitorType;
use App\Models\Department;
use App\Models\CompanySocial;
use App\Models\Inward;
use App\Models\Complain;
use Illuminate\Support\Facades\Http;
use App\Models\Dispatch;
use App\Models\MasterTransportDetail;
use App\Models\ExpenseCategory;
use App\Models\MasterArea;
use App\Models\MasterCity;
use App\Models\MasterCountry;
use App\Models\MasterState;
use App\Models\MasterTransportBy;
use App\Models\TeamPerson;
use App\Models\Designation;
use App\Models\DocumentType;
use App\Models\MarketingStatus;
use App\Models\SourceOfInquiry;
use App\Models\ProductCombo;
use App\Models\FollowUpReason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\TeamRole;
use App\Models\Inquiry;
use App\Models\ProductVariant;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use App\Models\MasterUnit;
use App\Models\Orderunit;
use App\Models\ComplanType;
use App\Models\CustomerType;
use App\Models\Customer;
use App\Models\ProductTAX;
use App\Models\MasterWarehouse;
use App\Models\ProductVariantPrice;
use App\Models\Product;
use App\Models\ProductCustomerItem;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\TermsAndCondition;
use App\Models\Vendor;
use App\Models\PurchaseOrder;
use App\Models\JobWorkOrder;
use App\Models\SalesStatus;
use App\Models\PlanMaster;
use App\Models\CompanySubscriptionPlan;
use App\Models\PaymentReceipt;
use App\Models\ExpoCategory;
use App\Models\ExpoMarketingSlider;
use App\Models\SaleExecutiveTracking;
use App\Models\TeamPersonMapCordinates;
use App\Models\ProductGrade;
use App\Models\ProductGodown;
use App\Models\ProductRack;
use App\Models\ProductAlloy;
use App\Models\ProductStockHistory;
use App\Models\JobWorkType;
use App\Models\JobWorkIssue;
use App\Models\Location;
use App\Models\LastProcess;
use App\Models\IssueForProcess;
use App\Models\ProcessReceived;
use App\Models\JobWorkReceive;
use App\Models\ProductBOM;
use App\Models\ProductHSN;
use App\Models\QcInspection;
use App\Models\MaterialDetailMap;
use App\Models\Invoice;
use App\Models\GRN;
use App\Models\QuotationItems;
use App\Models\SalesOrderItems;
use App\Models\PurchaseOrderItems;
use App\Models\JobWorkIssueItems;
use Carbon\Carbon;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class CommonController extends Controller
{
    public function __construct(Request $request) {}

    public function get_product_attachments(Request $request)
    {
        try {
            $productId = $request->product_id;
            $product = \App\Models\Product::find($productId);
            if (!$product) {
                return $this->sendError('Product not found');
            }

            $attachments = $product->attachments_list;
            $company = $product->company ?? null;
            $company_slug = \Illuminate\Support\Str::slug($company->company_name ?? 'default-company');

            $data = [];
            foreach ($attachments as $attach) {
                $relativePath = "uploads/{$product->company_id}-{$company_slug}/product-attachments/{$attach['file_path']}";
                $data[] = [
                    'original_name' => $attach['original_name'] ?? basename($attach['file_path']),
                    'file_url' => asset($relativePath),
                    'uploaded_at' => $attach['uploaded_at'] ?? 'N/A'
                ];
            }

            return $this->sendResponse($data, 'Product attachments retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function get_product_info(Request $request)
    {
        try {
            $product_variant_price_id = $request->product_id;
            $customer_id = $request->customer_id ?? null;
            $vendor_id = $request->vendor_id ?? null;
            $company_id = $request->company_id;

            $isCombo = $request->boolean('is_combo', false);

            if ($isCombo) {
                $combo = ProductCombo::with([
                    'combo_items.product',
                    'displayUnit'
                ])
                    ->where('id', $product_variant_price_id)
                    ->where('company_id', $company_id)
                    ->where('status', 'active')
                    ->first();

                if (!$combo) {
                    return $this->sendError('Combo product not found');
                }


                $hsnName = null;
                if ($combo->hsn_code) {
                    $hsnName = DB::table('product_hsn')
                        ->where('id', $combo->hsn_code)
                        ->value('name');
                }

                $gst = $combo->product_tax_id ?? 0;
                if ($customer_id) {
                    $discount = Customer::with('customerType')->find($customer_id);
                    if ($discount && strtolower($discount->customerType->name) === 'merchant') {
                        $gst = 0.11;
                    }
                }

                $comboItems = [];
                $availableStocks = [];

                foreach ($combo->combo_items as $item) {

                    $productName = $item
                        ? ($item->product->part_no ?? '') . ' - ' . ($item->product->product_name ?? '')
                        : '';

                    $stock = 0;
                    if ($request->boolean('available_stock')) {
                        $stock = ProductStockHistory::where('company_id', $company_id)
                            ->where('product_category_id', $combo->product_category_id)
                            ->where('product_variant_price_id', $item->product_variant_price_id)
                            ->whereNull('deleted_at')
                            ->orderBy('id', 'desc')
                            ->value('total_quantity');
                    }

                    $comboItems[] = [
                        'combo_item_id' => $item->id,
                        'product_variant_price_id' => $item->product_variant_price_id,
                        'product_name' => $productName,
                        'order_type' => $item->order_type,
                        'quantity' => $item->quantity,
                        'available_stock' => (float) ($stock ?? 0),
                    ];

                    $availableStocks[] = (float) ($stock ?? 0);
                }

                $data = [
                    'id' => $combo->id,
                    'product_id' => $combo->id,
                    'product_name' => $combo->product_combo_name,
                    'part_no' => $combo->part_no,
                    'is_combo' => true,
                    'price' => $combo->price,
                    'hsn_code' => $hsnName,
                    'gst' => $gst,
                    'gross_wt' => $combo->gross_wt,
                    'net_wt' => $combo->net_wt,
                    'grosse_per_qty' => $combo->grosse_per_qty,
                    'display_unit_name' => $combo->displayUnit?->name ?? null,
                    'product_category_id' => $combo->product_category_id,
                    'combo_items' => $comboItems,
                    'available_stock' => $availableStocks,
                ];
            } else {
                // Get product info
                $data = DB::table('products as p')
                    ->leftJoin('master_unit as u', 'u.id', '=', 'p.unit_id')
                    ->leftJoin('product_hsn as ph', 'ph.id', '=', 'p.hsn_code')
                    ->where('p.status', 'Active')
                    ->where('p.id', $product_variant_price_id)
                    ->select(
                        'p.id as product_id',
                        'p.part_no as part_no',
                        'p.net_wt as net_wt',
                        'u.name as display_unit_name',
                        'p.*',
                        'ph.name as hsn_code_name',
                        'p.product_tax_id as gst',
                    )
                    ->first();
                //dd($request->all(),$data);
                // Get discount info
                $discount = Customer::where('id', $customer_id)->first();

                if ($discount && $discount->customerType && strtolower($discount->customerType->name) == 'merchant') {
                    $data->gst = 0.11;
                }

                $company = Company::with(['company_details'])->where('id', $company_id)->first();


                $total_stock_quantity = null;
                $product_bom = null;
                if ($data) {
                    $total_stock_quantity = ProductStockHistory::where('company_id', $company_id)
                        ->where('product_category_id', $data->product_category_id)
                        ->where('product_variant_price_id', $product_variant_price_id)
                        ->whereNull('deleted_at')
                        ->orderBy('id', 'desc')
                        ->value('total_quantity');

                    $product_bom = ProductBOM::where('company_id', $company_id)
                        ->where('product_id', $data->product_id)
                        ->whereNull('deleted_at')
                        ->orderBy('id', 'desc')
                        ->first();
                }

                // Optionally add discount to product data
                if ($data) {
                    $data->price = $data->price ?? 0;
                    $data->quantity = $company->quantity ?? null;
                    $data->tax_type = $company->tax_type ?? null;
                    $data->hsn_code = $data->hsn_code_name ?? null;
                    $data->is_quantity_decimal = $company?->company_details?->is_quantity_decimal ?? null;
                    $data->total_stock_quantity = $total_stock_quantity ?? 0;

                    if (!is_null($total_stock_quantity) && !is_null($data->net_wt)) {
                        $data->product_stock = round($total_stock_quantity * $data->net_wt, 2);
                    } else {
                        $data->product_stock = 0;
                    }

                    $data->product_bom = $product_bom?->material_name ?? '-';

                    if (!is_null($data->net_wt) && $data->net_wt > 0) {
                        $data->alter_quantity = round(1 / $data->net_wt, 2);
                    } else {
                        $data->alter_quantity = 0;
                    }

                    // Use customer-specific product item details when available
                    $module_name = $request->module_name ?? null;
                    if ($customer_id && $product_variant_price_id) {
                        $customer = Customer::find($customer_id);
                        if ($customer && $customer->customer_name) {
                            $customerProductItem = ProductCustomerItem::where('company_id', $company_id)
                                ->where('product_id', $product_variant_price_id)
                                ->where('customer_name', $customer->customer_name)
                                ->whereNull('deleted_at')
                                ->first();

                            if ($customerProductItem) {
                                $codeVal = !empty($customerProductItem->customer_item_code)
                                    ? $customerProductItem->customer_item_code
                                    : ($customerProductItem->customer_code ?? '');
                                $data->customer_item_code = $codeVal;
                                $data->customer_code = $customerProductItem->customer_code ?? '';
                                $data->customer_item_description = $customerProductItem->customer_item_description;
                                if (!empty($customerProductItem->customer_item_description)) {
                                    $data->description = $customerProductItem->customer_item_description;
                                }
                            }
                        }
                    }

                    $module_name = $request->module_name ?? null;
                    $orderTypeValue = $request->orderTypeValue ?? null;
                    $modulesAllowed = ['sales-order', 'quotation', 'purchase-order', 'job-work-issue'];
                    if (in_array($module_name, $modulesAllowed) && ($customer_id || $vendor_id) && $product_variant_price_id) {

                        if (in_array($module_name, ['sales-order', 'quotation'])) {
                            $searchId = $customer_id;
                        } else {
                            $searchId = $vendor_id ?? null;
                        }


                        if ($searchId) {
                            $lastCustomerPrice = $this->getLastCustomerPriceByModule(
                                $searchId,
                                $product_variant_price_id,
                                $module_name,
                                $orderTypeValue,
                            );

                            if ($lastCustomerPrice !== null) {
                                $data->price = $lastCustomerPrice;
                            }
                        }
                    }

                    // Rate sheet details lookup
                    $rateSheetInfo = Helper::getRateSheetProductDetails($data->part_no ?? null, $data->product_name ?? null);
                    $data->rate_sheet_details = $rateSheetInfo;
                    $data->job_work_gross_rate = $rateSheetInfo['job_work_gross_rate'] ?? 0;
                    $data->gross_labour_charges = $rateSheetInfo['gross_labour_charges'] ?? 0;
                    $data->job_work_pcs_rate = $rateSheetInfo['job_work_pcs_rate'] ?? 0;

                    $module_name = $request->module_name ?? null;
                    $orderTypeValue = strtolower($request->orderTypeValue ?? $request->order_type ?? '');
                    if (in_array($module_name, ['job-work-order', 'job_work_order'])) {
                        if ($orderTypeValue === 'kg') {
                            if (!empty($rateSheetInfo['gross_labour_charges'])) {
                                $data->price = $rateSheetInfo['gross_labour_charges'];
                            }
                        } elseif ($orderTypeValue === 'gross' || $orderTypeValue === 'grosse') {
                            if (!empty($rateSheetInfo['job_work_gross_rate'])) {
                                $data->price = $rateSheetInfo['job_work_gross_rate'];
                            }
                        } elseif ($orderTypeValue === 'pcs' || $orderTypeValue === 'pc') {
                            if (!empty($rateSheetInfo['job_work_pcs_rate'])) {
                                $data->price = $rateSheetInfo['job_work_pcs_rate'];
                            } elseif (!empty($rateSheetInfo['job_work_gross_rate'])) {
                                $data->price = round($rateSheetInfo['job_work_gross_rate'] / 144, 2);
                            }
                        }
                    }
                }
            }

            return $this->sendResponse($data, "Product info list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }


    // Add this new helper function to the controller
    public function getLastCustomerPriceByModule($customer_id, $product_id, $module_name, $orderTypeValue)
    {
        try {
            $lastPrice = null;
            $orderTypeValue = strtolower($orderTypeValue);

            if ($module_name == 'sales-order') {
                $lastPrice = SalesOrderItems::join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
                    ->where('sales_orders.customer_id', $customer_id)
                    ->where('sales_order_items.product_id', $product_id)
                    ->where('sales_order_items.unit', $orderTypeValue)
                    ->whereNull('sales_order_items.deleted_at')
                    ->orderBy('sales_order_items.id', 'desc')
                    ->value('sales_order_items.price_in_amount');
            } elseif ($module_name == 'quotation') {
                $lastPrice = QuotationItems::join('quotations', 'quotation_items.quotation_id', '=', 'quotations.id')
                    ->where('quotations.customer_id', $customer_id)
                    ->where('quotation_items.product_id', $product_id)
                    ->where('quotation_items.unit', $orderTypeValue)
                    ->whereNull('quotation_items.deleted_at')
                    ->orderBy('quotation_items.id', 'desc')
                    ->value('quotation_items.price_in_amount');
            } elseif ($module_name == 'purchase-order') {
                $lastPrice = PurchaseOrderItems::join(
                    'purchase_orders',
                    'purchase_order_items.purchase_order_id',
                    '=',
                    'purchase_orders.id'
                )
                    ->where('purchase_orders.vendor_id', $customer_id)
                    ->where('purchase_order_items.product_id', $product_id)
                    ->where('purchase_order_items.unit', $orderTypeValue)
                    ->whereNull('purchase_order_items.deleted_at')
                    ->orderBy('purchase_order_items.id', 'desc')
                    ->value('purchase_order_items.price_in_amount');
            } elseif ($module_name == 'job-work-issue') {
                $lastPrice = JobWorkIssueItems::join(
                    'job_work_issue',
                    'job_work_issue_items.job_work_issue_id',
                    '=',
                    'job_work_issue.id'
                )
                    ->where('job_work_issue.vendor_id', $customer_id)
                    ->where('job_work_issue_items.product_id', $product_id)
                    ->whereNull('job_work_issue_items.deleted_at')
                    ->orderBy('job_work_issue_items.id', 'desc')
                    ->value('job_work_issue_items.rate_in_amount');
            }

            return $lastPrice ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function get_job_issue_product_info(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
                'job_work_issue_no' => [
                    'required',
                    Rule::exists((new JobWorkIssue())->getTable(), 'job_work_issue_no'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $companyId = $request->company_id;
            $issueNo = $request->job_work_issue_no;

            $salesStatusesLast = SalesStatus::where('company_id', $request->company_id)
                ->where('salestype', 'Job Work Issue')
                ->select("id")
                ->orderBy('display_order', 'desc')
                ->first();

            $jobWorkIssue = JobWorkIssue::where('company_id', $companyId)
                ->where('job_work_issue_no', $issueNo)
                ->when($salesStatusesLast, function ($query) use ($salesStatusesLast) {
                    $query->where('sales_status_id', '!=', $salesStatusesLast->id);
                })
                ->whereNull('deleted_at')
                ->with([
                    'vendor.state',
                    'job_work_issue_items' => function ($query) {
                        $query->with([
                            'product',
                            'job_work_receive_items' => function ($subQuery) {
                                $subQuery->where('pending_weight', '>', 0);
                            }
                        ]);

                        $query->where(function ($q) {
                            $q->whereHas('job_work_receive_items', function ($subQuery) {
                                $subQuery->where('pending_weight', '>', 0);
                            })
                                ->orWhereDoesntHave('job_work_receive_items');
                        });
                    }
                ])
                ->first();

            if (!$jobWorkIssue) {
                return $this->sendError("Job Work Issue not found.");
            }

            $master_gross_weight = $jobWorkIssue->master_total_pending;

            $jobWorkIssue->job_work_issue_items->transform(function ($item) use ($companyId, &$master_gross_weight) {
                $product = $item->product;
                $item->gross_weight = $item->remaining_weight;
                $item->master_gross_weight = $master_gross_weight;

                // Stock & BOM
                $productBOM = ProductBOM::where('company_id', $companyId)
                    ->where('product_id', $item->product_id)
                    ->whereNull('deleted_at')
                    ->latest('id')
                    ->first();

                $stockQty = ProductStockHistory::where('company_id', $companyId)
                    ->where('product_variant_price_id', $item->product_id)
                    ->whereNull('deleted_at')
                    ->latest('id')
                    ->value('total_quantity');

                // Attach computed fields
                $item->product_bom = $productBOM?->material_name ?? '-';
                $item->total_stock_quantity = $stockQty ?? 0;
                return $item;
            });
            $jobWorkIssue->master_gross_weight = $master_gross_weight;


            $jobWorkIssueId = $jobWorkIssue->id;
            $jobWorkReceive = JobWorkReceive::where('job_work_issue_id', $jobWorkIssueId)
                ->whereNull('deleted_at')
                ->latest('id')
                ->first();
            $pending = $jobWorkReceive->master_total_current_pending ?? null;
            $jobWorkIssue->master_total_current_pending = $pending ?? 0;

            return $this->sendResponse($jobWorkIssue, "Job Work Issue with Items fetched successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function get_pending_qc_inspection(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
                'vendor_id' => [
                    'nullable',
                    Rule::exists((new Vendor())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $companyId = $request->company_id;
            $vendor_id = $request->vendor_id;

            $pendingQCInspection = JobWorkReceive::with([
                'vendor:id,vendor_name',
                'process_received:id,name as process_name',
                'job_work_receive_items',
            ])
                ->select(
                    'id',
                    'company_id',
                    'vendor_id',
                    'warehouse_id',
                    'product_category_id',
                    'process_receive_id',
                    'job_work_issue_id',
                    'job_work_receive_date',
                    'job_work_receive_no',
                    'mobile_no',
                    'phone',
                    'state',
                    'address',
                )
                ->where('company_id', $companyId)
                ->where('vendor_id', $vendor_id)
                ->whereHas('job_work_issue', function ($q) {
                    $q->whereNotNull('sales_status_id');
                })
                ->orderBy('id', 'desc');
            // return Helper::interpolateQuery($pendingQCInspection->toSql(), $pendingQCInspection->getBindings());
            $pendingQCInspection = $pendingQCInspection->get();

            // Flatten data: one row per job_work_receive_item
            $QCInspectionData = [];
            foreach ($pendingQCInspection as $receive) {
                foreach ($receive->job_work_receive_items as $item) {

                    $pendingWeight = $item->received_weight - $item->qc_check_weight;
                    if ($pendingWeight <= 0) {
                        continue;
                    }
                    $item->received_weight = $pendingWeight;

                    $item->available_qty = 0;
                    if (!empty($item->received_weight)) {
                        $item->available_qty = Helper::getProductStockTotalQuantity($receive->company_id, $receive->warehouse_id, $receive->product_category_id, $item->product_id);
                    }

                    $categoryName = optional($item?->product?->product_category)->name;
                    $warehouseName = optional($receive?->job_work_issue?->warehouse)->name ?? '';

                    $categoryNameLower = strtolower(trim($categoryName));
                    $warehouseNameLower = strtolower(trim($warehouseName));

                    $isSizeRequired = false;

                    if ($categoryNameLower === 'road' || $categoryNameLower === 'bar') {
                        $isSizeRequired = false;
                    } elseif ($categoryNameLower === 'finish good' || $categoryNameLower === 'finish goods') {
                        $isSizeRequired = true;
                    } elseif (strpos($warehouseNameLower, 'finish good') !== false || strpos($warehouseNameLower, 'fg') !== false) {
                        $isSizeRequired = true;
                    }

                    $QCInspectionData[] = [
                        'id' => $receive->id,
                        'process_receive_id' => $receive->process_receive_id,
                        'mobile_no' => $receive->mobile_no,
                        'phone' => $receive->phone,
                        'state' => $receive->state,
                        'address' => $receive->address,
                        'vendor_id' => $receive->vendor_id,
                        'vendor_name' => optional($receive->vendor)->vendor_name,
                        'process_name' => optional($receive->process_received)->process_name,
                        'job_work_receive_date' => $receive->job_work_receive_date,
                        'job_work_receive_no' => $receive->job_work_receive_no,
                        'warehouse_id' => $receive->warehouse_id,
                        'warehouse_name' => $receive->warehouse?->name,
                        'job_work_receive_item' => $item,
                        'is_size_required' => $isSizeRequired,
                        'type' => 'job_work'
                    ];
                }
            }

            // Add Inward Items
            $pendingInwardItems = \App\Models\InwardItems::with(['inward.vendor', 'inward.warehouse', 'product.product_category'])
                ->whereHas('inward', function ($q) use ($companyId, $vendor_id, $request) {
                    $q->where('company_id', $companyId);
                    if ($request->inward_id) {
                        $q->where('id', $request->inward_id);
                    } else {
                        $q->where('vendor_id', $vendor_id);
                    }
                })
                ->where('is_qc_done', 0)
                ->get();

            foreach ($pendingInwardItems as $item) {
                $categoryName = optional($item?->product?->product_category)->name;
                $warehouseName = optional($item?->inward?->warehouse)->name ?? '';

                $categoryNameLower = strtolower(trim($categoryName));
                $warehouseNameLower = strtolower(trim($warehouseName));

                $isSizeRequired = false;
                if ($categoryNameLower === 'road' || $categoryNameLower === 'bar') {
                    $isSizeRequired = false;
                } elseif ($categoryNameLower === 'finish good' || $categoryNameLower === 'finish goods') {
                    $isSizeRequired = true;
                } elseif (strpos($warehouseNameLower, 'finish good') !== false || strpos($warehouseNameLower, 'fg') !== false) {
                    $isSizeRequired = true;
                }

                $QCInspectionData[] = [
                    'inward_id' => $item->inward_id,
                    'inward_item_id' => $item->id,
                    'mobile_no' => $item->inward->phone,
                    'phone' => $item->inward->phone,
                    'state' => $item->inward->state,
                    'address' => $item->inward->address,
                    'vendor_id' => $item->inward->vendor_id,
                    'vendor_name' => optional($item->inward->vendor)->vendor_name,
                    'process_name' => 'Inward QC',
                    'job_work_receive_date' => $item->inward->inward_date,
                    'job_work_receive_no' => $item->inward->inward_no,
                    'warehouse_id' => $item->inward->warehouse_id,
                    'warehouse_name' => $item->inward->warehouse?->name,
                    'job_work_receive_item' => [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_code' => $item->product->part_no ?? '',
                        'product_name' => $item->product_name,
                        'received_weight' => $item->inward_quantity,
                        'quantity' => $item->inward_quantity,
                        'wt_pc' => $item->product_weight,
                        'available_qty' => Helper::getProductStockTotalQuantity($companyId, $item->inward->warehouse_id, $item->product->product_category_id, $item->product_id),
                    ],
                    'is_size_required' => $isSizeRequired,
                    'type' => 'inward'
                ];
            }

            return $this->sendResponse($QCInspectionData, "QC Pending Inspection fetched successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function get_country(Request $request)
    {
        try {
            // $data = MasterCountry::orderBy('name', 'ASC')->get();
            $data = MasterCountry::query();

            if ($request?->filter_by_status && $request?->filter_by_status != "all") {
                $data = $data->where('status', $request?->filter_by_status);
            }
            $data = $data->orderBy('name', 'ASC')->get();

            $data = $data->map(function ($row) {
                // $temp = $row;
                $temp['id'] = $row?->id . "";
                $temp['name'] = $row?->name . "";
                $temp['code'] = $row?->code . "";
                $temp['short_name'] = $row?->short_name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Country list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_state(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'country_id' => [
                    'required',
                    Rule::exists((new MasterCountry())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            if (!$request?->country_id) {
                return $this->sendError("Country id is required.");
            }
            $data = MasterState::where('country_id', $request?->country_id)->get();
            $data = $data->map(function ($row) {
                // $temp = $row;
                $temp['id'] = $row?->id . "";
                $temp['name'] = $row?->name . "";
                $temp['country_id'] = $row?->country_id . "";
                $temp['country_name'] = $row?->country?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "State list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_product_stock_total(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            'warehouse_id' => [
                'required',
                Rule::exists((new MasterWarehouse())->getTable(), 'id')
            ],
            'product_category_id' => [
                'required',
                Rule::exists((new ProductCategory())->getTable(), 'id')
            ],
            'product_variant_price_id' => [
                'required',
                Rule::exists((new Product())->getTable(), 'id')
            ]
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
        }

        try {
            $company_id = $request?->company_id;
            $warehouse_id = $request?->warehouse_id;
            $product_category_id = $request?->product_category_id;
            $product_variant_price_id = $request?->product_variant_price_id;

            $product_total_kg = Helper::getProductStockTotalKG($company_id, $warehouse_id, $product_category_id, $product_variant_price_id);
            $product_total_qty = Helper::getProductStockTotalQuantity($company_id, $warehouse_id, $product_category_id, $product_variant_price_id);

            $data = [
                'product_total_kg' => $product_total_kg,
                'product_total_qty' => $product_total_qty,
            ];
            return $this->sendResponse($data, "City list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_finished_goods_warehouse(Request $request)
    {
        try {
            $company_id = $request?->company_id;
            if (!$company_id) {
                return $this->sendError("Company ID is required");
            }

            $fgWarehouse = MasterWarehouse::where('company_id', $company_id)
                ->where(function ($q) {
                    $q->where('status', 'active')
                        ->orWhere('status', 'Active');
                })
                ->where(function ($query) {
                    $query->where('name', 'like', '%Finished Goods%')
                        ->orWhere('name', 'like', '%FG%')
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%finish%'])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%fg%']);
                })
                ->first();

            if (!$fgWarehouse) {
                $fgWarehouse = MasterWarehouse::where('company_id', $company_id)
                    ->where(function ($q) {
                        $q->where('status', 'active')
                            ->orWhere('status', 'Active');
                    })
                    ->first();
            }

            return $this->sendResponse($fgWarehouse, "Finished Goods Warehouse");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function get_product_stock(Request $request)
    {
        try {
            $company_id = $request?->company_id;
            $warehouse_id = $request?->warehouse_id;
            $product_category_id = $request?->product_category_id;
            $product_variant_price_id = $request?->product_variant_price_id;
            $is_combo = $request?->is_combo;
            $unit = $request?->unit;

            $is_combo_bool = filter_var($is_combo, FILTER_VALIDATE_BOOLEAN) || $is_combo == 1 || $is_combo === '1' || $is_combo === 'true';

            $stockQty = Helper::getProductStockTotalQuantity($company_id, $warehouse_id, $product_category_id, $product_variant_price_id, $is_combo_bool);
            $stockKg = Helper::getProductStockTotalKG($company_id, $warehouse_id, $product_category_id, $product_variant_price_id, $is_combo_bool);

            $currentStock = 0;
            $unitLower = strtolower((string) $unit);

            if ($unitLower === 'kg') {
                $currentStock = number_format($stockKg, 3, '.', '');
            } elseif ($unitLower === 'gross') {
                $currentStock = number_format($stockQty, 3, '.', '');
            } else {
                $currentStock = round($stockQty);
            }

            return $this->sendResponse($currentStock, "Current Stock");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function get_city(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_id' => [
                'required',
                Rule::exists((new MasterCountry())->getTable(), 'id')
            ],
            'state_id' => [
                'required',
                Rule::exists((new MasterState())->getTable(), 'id')
            ]
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
        }

        try {
            if (!$request?->country_id) {
                return $this->sendError("Country id is required.");
            }
            if (!$request?->state_id) {
                return $this->sendError("State id is required.");
            }
            $data = MasterCity::where('country_id', $request?->country_id)->where('state_id', $request?->state_id)->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                // $temp = $row;
                $temp['id'] = $row?->id . "";
                $temp['name'] = $row?->name . "";
                $temp['state_id'] = $row?->state_id . "";
                $temp['state_name'] = $row?->state?->name . "";
                $temp['country_id'] = $row?->country_id . "";
                $temp['country_name'] = $row?->country?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "City list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_area(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            'country_id' => [
                'required',
                Rule::exists((new MasterCountry())->getTable(), 'id')
            ],
            'state_id' => [
                'required',
                Rule::exists((new MasterState())->getTable(), 'id')
            ],
            'city_id' => [
                'required',
                Rule::exists((new MasterCity())->getTable(), 'id')
            ]
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
        }

        try {

            $data = MasterArea::query();
            $data = $data->where('company_id', $request?->company_id);
            $data = $data->where('country_id', $request?->country_id);
            $data = $data->where('state_id', $request?->state_id);
            $data = $data->where('city_id', $request?->city_id);
            $data = $data->orderBy('area_name', 'asc')->get();

            $data = $data->map(function ($row) {
                // $temp = $row;
                $temp['id'] = $row?->id . "";
                $temp['area_name'] = $row?->area_name . "";

                $temp['company_id'] = $row?->company_id . "";
                $temp['company_name'] = $row?->company?->company_name . "";

                $temp['city_id'] = $row?->city_id . "";
                $temp['city_name'] = $row?->city?->name . "";

                $temp['state_id'] = $row?->state_id . "";
                $temp['state_name'] = $row?->state?->name . "";

                $temp['country_id'] = $row?->country_id . "";
                $temp['country_name'] = $row?->country?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Area list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    /** API Get Company */
    public function get_companies(Request $request)
    {

        try {

            $data = new Company();
            $data = $data->with(['plan', 'country', 'state', 'city']);
            if ($request?->filter_by_status) {
                if ($request?->filter_by_status != "all") {
                    $data = $data->where('status', $request?->filter_by_status);
                }
            } else {
                $data = $data->where('status', 'active');
            }
            $data = $data->orderBy('company_name', 'asc');
            $data = $data->get();

            $data = $data->map(function ($row) {
                // $temp = $row;
                $temp['id'] = $row?->id . "";
                $temp['company_name'] = $row?->company_name . "";
                $temp['gst_no'] = $row?->gst_no . "";
                return $temp;
            });
            return $this->sendResponse($data, "Company list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    /** API Get Company */
    public function get_company_social(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')->where('status', 'active')
            ]
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
        }

        try {

            $data = CompanySocial::where('company_id', $request?->company_id)->where('platform', $request?->platform);
            $data = $data->whereNotNull('platform');
            $data = $data->orderBy('id', 'asc');
            $data = $data->get();
            // return $data;
            return $this->sendResponse($data, "Company social list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_expense_category(Request $request)
    {

        try {

            $data = new ExpenseCategory();
            $data = $data->with(['company']);
            if ($request?->filter_by_status) {
                if ($request?->filter_by_status != "all") {
                    $data = $data->where('status', $request?->filter_by_status);
                }
            } else {
                $data = $data->where('status', 'active');
            }
            $data = $data->orderBy('name', 'asc');
            $data = $data->get();

            $data = $data->map(function ($row) {
                // $temp = $row;
                $temp['id'] = $row?->id . "";
                $temp['name'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Expense Category list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    /** API Get transport by */
    public function get_transportby(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            if (!$request?->company_id) {
                return $this->sendError("Company Name is required.");
            }
            $data = MasterTransportBy::where('company_id', $request?->company_id)->get();
            $data = $data->map(function ($row) {
                // $temp = $row;
                $temp['id'] = $row?->id . "";
                $temp['name'] = $row?->name . "";
                $temp['company_id'] = $row?->company_id . "";
                $temp['company_name'] = $row?->company?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Transport Detail list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in Transport Detail API");
    }

    /** API Generate Employee Code */
    public function generate_employee_code(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;

            $employeeCount = TeamPerson::withTrashed()->where('company_id', $company_id)->count();

            $nextNumber = $employeeCount + 1;
            $employeeCode = 'EMP-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            if ($request->directReturn) {
                return $employeeCode;
            }

            return $this->sendResponse($employeeCode, "Employee code generated.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendError("Something went wrong in generate employee code API");
    }



    /** API Get Designation */
    public function get_designation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request?->company_id;
            $designation = Designation::where('company_id', $company_id)->where('status', 'active');
            $data = $designation->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['designation_name'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Designation list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_team_role(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request?->company_id;
            $team_role = TeamRole::where('company_id', $company_id)->where('status', 'active');
            $data = $team_role->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['team_role_name'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Team Role list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function generate_inquiry_no(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;
            $lastInquiry = Inquiry::withTrashed()
                ->where('company_id', $company_id)
                ->orderByDesc('id')
                ->first();

            if ($lastInquiry) {
                $lastNo = (int) preg_replace('/[^0-9]/', '', $lastInquiry->inquiry_no);

                if ($lastInquiry->trashed()) {
                    $nextNumber = $lastNo;
                } else {
                    $nextNumber = $lastNo + 1;
                }
            } else {
                $nextNumber = 1;
            }


            $inquiryNo = 'INQ-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $exists = Inquiry::where('company_id', $company_id)
                ->where('inquiry_no', $inquiryNo)
                ->exists();

            if ($exists) {
                $nextNumber++;
                $inquiryNo = 'INQ-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }

            if ($request->directReturn) {
                return $inquiryNo;
            }
            return $this->sendResponse($inquiryNo, "Inquiry number generated successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function generate_client_code(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;
            $lastCustomer = Customer::withTrashed()
                ->where('company_id', $company_id)
                ->orderByDesc('id')
                ->first();

            if ($lastCustomer) {
                $lastNo = (int) preg_replace('/[^0-9]/', '', $lastCustomer->client_code);

                if ($lastCustomer->trashed()) {
                    $nextNumber = $lastNo;
                } else {
                    $nextNumber = $lastNo + 1;
                }
            } else {
                $nextNumber = 1;
            }

            $clientCode = 'SIPL-CUS-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $exists = Customer::where('company_id', $company_id)
                ->where('client_code', $clientCode)
                ->exists();

            if ($exists) {
                $nextNumber++;
                $clientCode = 'SIPL-CUS-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }

            if ($request->directReturn) {
                return $clientCode;
            }

            return $this->sendResponse($clientCode, "Client code generated successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendError("Something went wrong while generating client code.");
    }


    public function get_inquiry_status(Request $request)
    {
        try {
            $loginUser = Auth::guard('team_person-api')->user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            if ($loginUser?->company_id) {
                $request['company_id'] = $loginUser?->company_id;
            }
            // dd("L-513", $loginUser, Auth::guard('team_person-api')->user());
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request?->company_id;
            $inquiry_status = MarketingStatus::query();

            $inquiry_status = $inquiry_status->where('company_id', $company_id)->where('type', 'Inquiry')->where('status', 'active');
            $data = $inquiry_status->orderBy('is_default', 'desc')->orderBy('name', 'asc')->get();

            $data = $data->map(function ($row) use ($request, $loginUser) {
                // $temp = $row;
                $temp['id'] = $row?->id . "";
                $temp['inquiry_status'] = $row?->name . "";
                $temp['status_color'] = $row?->status_color . "";
                $temp['status_color_2'] = $row?->status_color_2 . "";
                $temp['text_color'] = $row?->text_color . "";
                // $temp['is_default'] = $row?->is_default;
                $temp['is_default'] = ($row?->is_default) ? 'yes' : "no";
                if ($request?->get_inquiry_count) {
                    $temp['inquiries_count'] = Inquiry::query()->where('inquiry_assigned_to', $loginUser?->id)->where('inquiry_status_id', $row?->id)->count();
                }
                return $temp;
            });
            return $this->sendResponse($data, "Inquiry Status list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }


    public function get_teams(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $teams = TeamPerson::where('company_id', $company_id)->where('status', 'active');
            $data = $teams->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['team_name'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Teams list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_status(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'salestype' => [
                    'required'
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request->company_id;
            $salestype = $request->salestype;

            $status = SalesStatus::where('company_id', $company_id);
            if (isset($salestype) && !empty($salestype)) {
                $status = $status->where('salestype', $salestype);
            }
            $status = $status->orderBy('id', 'desc')->get();

            return $this->sendResponse($status, 'Status list');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), [], 401);
        }
        return $this->sendError("Something want to wrong in sales status API");
    }

    public function get_document_type(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $data = DocumentType::query();
            $data = $data->where('company_id', $request?->company_id);

            if ($request?->filter_by_status) {
                if ($request?->filter_by_status != "all") {
                    $data = $data->where('status', $request?->filter_by_status);
                }
            } else {
                $data = $data->where('status', 'active');
            }

            $data = $data->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['document_type_name'] = $row?->name . "";
                $temp['company_id'] = $row?->company_id . "";
                $temp['company_name'] = $row?->company?->company_name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Company wise document list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }


    public function get_source_of_inquiry(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $source_of_inquiry = SourceOfInquiry::where('company_id', $company_id)->where('status', 'active');
            $data = $source_of_inquiry->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['source_of_inquiry'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Source of Inquiry list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_product_variant(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 400);
            }
            $company_id = $request?->company_id;
            $customer_id = $request?->customer_id;
            $customer_name = null;
            if ($customer_id) {
                $customer = Customer::find($customer_id);
                $customer_name = $customer?->customer_name;
            }

            $product_variants = ProductVariant::with('product')->where('company_id', $company_id)->where('status', 'active');
            $data = $product_variants->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) use ($customer_name) {
                $productCode = $row?->product?->part_no ?? '';

                if ($customer_name && $row->product_id) {
                    $customerProductItem = ProductCustomerItem::where('company_id', $row->company_id)
                        ->where('product_id', $row->product_id)
                        ->where('customer_name', $customer_name)
                        ->whereNull('deleted_at')
                        ->first();

                    $codeVal = !empty($customerProductItem->customer_item_code)
                        ? $customerProductItem->customer_item_code
                        : ($customerProductItem->customer_code ?? '');
                    if ($customerProductItem && !empty($codeVal)) {
                        $productCode = $codeVal;
                    }
                }

                $temp['id'] = $row?->id . "";
                $temp['product_variant'] = $row?->name . "";
                $temp['product_code'] = $productCode . "";
                return $temp;
            });
            return $this->sendResponse($data, "Product Variant list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_expo_category(Request $request)
    {
        try {
            /*
            $validator =  Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 400);
            }
                */
            // $company_id = $request?->company_id;
            $expo_category = ExpoCategory::query();
            $expo_category = $expo_category->where('status', 'active');
            // $expo_category = $expo_category->where('company_id', $company_id);
            $data = $expo_category->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['name'] = $row?->name . "";
                $temp['status'] = $row?->status . "";
                return $temp;
            });
            return $this->sendResponse($data, "Expo Category List.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_expo_marketing_slider(Request $request)
    {
        try {
            /*
            $validator =  Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 400);
            }
                */
            // $company_id = $request?->company_id;
            $expo_category = ExpoMarketingSlider::query();
            $expo_category = $expo_category->where('status', 'active');
            // $expo_category = $expo_category->where('company_id', $company_id);
            $data = $expo_category->orderBy('id', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['company_id'] = $row?->company_id . "";
                $temp['name'] = $row?->name . "";
                $temp['attachment_url'] = $row?->attachment_url . "";
                $temp['redirect_url'] = $row?->redirect_url . "";
                $temp['status'] = $row?->status . "";
                return $temp;
            });
            return $this->sendResponse($data, "Expo marketing sliders.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_ProductCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $ProductCategory = ProductCategory::where('company_id', $company_id)->where('status', 'active');
            $data = $ProductCategory->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['ProductCategory'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Product Category list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_product_unit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $product_unit = MasterUnit::where('company_id', $company_id)->where('status', 'active');
            $data = $product_unit->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['product_unit'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Product Unit list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_product_tax(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $product_tax = ProductTAX::where('company_id', $company_id)->where('status', 'active');
            $data = $product_tax->orderBy('tax_name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['product_tax'] = $row?->tax_name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Product Tax list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_order_unit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $product_unit = Orderunit::where('company_id', $company_id)->where('status', 'active');
            $data = $product_unit->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['order_unit'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Order Unit list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_complain_type(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $complain_type = ComplanType::where('company_id', $company_id)->where('status', 'active');
            $data = $complain_type->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['complain_type'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Complain Type list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_customer_type(Request $request)
    {
        try {
            $data = CustomerType::orderBy('id', 'ASC')->get();

            $data = $data->map(function ($row) {
                // $temp = $row;
                $temp['id'] = $row?->id . "";
                $temp['name'] = $row?->name . "";
                $temp['parent_id'] = $row?->parent_id . "";
                return $temp;
            });
            return $this->sendResponse($data, "Customer Type list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_warehouse(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $warehouse = MasterWarehouse::where('company_id', $company_id)->where('status', 'active');
            $data = $warehouse->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['warehouse'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Warehouse list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_product_variant_price(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'category_id' => [
                    'required',
                    Rule::exists((new ProductCategory())->getTable(), 'id')
                ],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $product = Product::where('company_id', $company_id)
                ->where('status', 'active');
            if (isset($request->category_id)) {
                $product->where(function ($query) use ($request) {
                    $query->where('product_category_id', $request->category_id)
                        ->orWhere('product_category_id', 0);
                });
            }
            $data = $product->orderBy('products.id', 'asc')->get();
            $result = [];

            foreach ($data as $row) {
                $parts = [
                    $row->product_name ?? '',
                ];
                $product = collect($parts)
                    ->filter()
                    ->implode(' - ');

                $result[] = [
                    'id' => (string) $row->id,
                    'product' => $product,
                    'gross_wt' => $row->gross_wt,
                    'net_wt' => $row->net_wt,
                ];
            }

            return $this->sendResponse($result, "Product list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_product_and_variant(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'category_id' => [
                    'required',
                    Rule::exists((new ProductCategory())->getTable(), 'id')
                ],
            ]);

            // If available_stock is requested, warehouse_id is required
            $includeStock = $request->has('available_stock') &&
                ($request->available_stock === true ||
                    $request->available_stock === 'true' ||
                    $request->available_stock === '1' ||
                    $request->available_stock === 1);

            if ($includeStock) {
                $validator->sometimes('warehouse_id', [
                    'required',
                    Rule::exists((new MasterWarehouse())->getTable(), 'id')
                ], function ($input) {
                    return true;
                });
            }

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $includeCombo = $request->has('product_combo') &&
                ($request->product_combo === true ||
                    $request->product_combo === 'true' ||
                    $request->product_combo === '1' ||
                    $request->product_combo === 1);

            $company_id = $request?->company_id;
            $category_id = $request?->category_id;
            $customer_id = $request?->customer_id;
            $warehouse_id = $includeStock ? $request->warehouse_id : null;

            $customer_name = null;
            if ($customer_id) {
                $customer = Customer::find($customer_id);
                $customer_name = $customer?->customer_name;
            }

            $product = Product::with([
                'product_variant_prices:id,product_id,price,variant_id,product_code',
                'product_variant_prices.variant:id,name'
            ])
                ->where('company_id', $company_id)
                ->where('product_category_id', $category_id)
                ->where('status', 'active');
            $data = $product->orderBy('products.id', 'asc')->get();
            $result = [];
            foreach ($data as $row) {
                // if (!empty($row->product_variant_prices) && count($row->product_variant_prices)) {
                //     foreach ($row->product_variant_prices as $pvp) {
                //         $parts = [
                //             $row->product_name ?? '',
                //             $pvp->variant->name ?? '',
                //             $pvp->product_code ?? '',
                //         ];

                //         $product = collect($parts)
                //             ->filter()
                //             ->implode(' - ');

                //         $item = [
                //             'id' => (string) $pvp?->id,
                //             'product' => $product,
                //             'is_combo' => false,
                //         ];

                //         // Add available stock if requested
                //         if ($includeStock && $warehouse_id) {
                //             $availableStock = Helper::getProductStockTotalQuantity(
                //                 $company_id,
                //                 $warehouse_id,
                //                 $category_id,
                //                 $pvp?->id
                //             );
                //             $item['available_stock'] = (float)($availableStock ?? 0);
                //         }

                //         $result[] = $item;
                //     }
                // } else {
                $custCode = '';
                $custItemCode = '';
                $custDesc = '';

                if ($customer_name) {
                    $customerProductItem = ProductCustomerItem::where('company_id', $company_id)
                        ->where('product_id', $row->id)
                        ->where('customer_name', $customer_name)
                        ->whereNull('deleted_at')
                        ->first();

                    if ($customerProductItem) {
                        $custCode = $customerProductItem->customer_code ?? '';
                        $custItemCode = !empty($customerProductItem->customer_item_code)
                            ? $customerProductItem->customer_item_code
                            : ($customerProductItem->customer_code ?? '');
                        $custDesc = $customerProductItem->customer_item_description ?? '';
                    }
                }

                $item = [
                    'id' => (string) $row->id,
                    'product' => $row->product_name ?? '',
                    'product_name' => $row->product_name ?? '',
                    'part_no' => $row->part_no ?? '',
                    'customer_code' => $custCode,
                    'customer_item_code' => $custItemCode,
                    'customer_item_description' => $custDesc,
                    'is_combo' => false,
                ];

                // Add available stock if requested (for products without variants)
                if ($includeStock && $warehouse_id) {
                    // For products without variants, try using product_id as product_variant_price_id
                    // This might work if stock is stored with product_id in the variant_price_id field
                    $availableStock = Helper::getProductStockTotalQuantity(
                        $company_id,
                        $warehouse_id,
                        $category_id,
                        $row->id
                    );
                    $item['available_stock'] = (float) ($availableStock ?? 0);
                }

                $result[] = $item;
                //}
            }


            if ($includeCombo) {
                $combos = ProductCombo::with([
                    'combo_items',
                    'combo_items.product'
                ])
                    ->where('company_id', $company_id)
                    ->where('product_category_id', $category_id)
                    ->where('status', 'active')
                    ->get();

                foreach ($combos as $combo) {

                    // Initialize arrays
                    $comboItemIds = [];
                    $comboProductIds = [];
                    $orderTypes = [];
                    $quantities = [];
                    $productNames = [];
                    $availableStocks = [];

                    foreach ($combo->combo_items as $comboItem) {


                        if (isset($comboItem->product)) {
                            $productNames[] =
                                ($comboItem->product->part_no ?? '') .
                                ' - ' .
                                ($comboItem->product->product_name ?? '');
                        }

                        $comboItemIds[] = $comboItem->id;
                        $comboProductIds[] = $comboItem->product_variant_price_id;
                        $orderTypes[] = $comboItem->order_type;
                        $quantities[] = $comboItem->quantity;


                        if ($includeStock && $warehouse_id) {
                            $stock = Helper::getProductStockTotalQuantity(
                                $company_id,
                                $warehouse_id,
                                $combo->product_category_id,
                                $comboItem->product->product_variant_price_id
                            );
                            $availableStocks[] = (float) ($stock ?? 0);
                        } else {
                            $availableStocks[] = 0;
                        }
                    }

                    if (empty($comboItemIds)) {
                        continue;
                    }

                    $item = [
                        'id' => $combo->id,
                        'product' => $combo->product_combo_name,
                        'part_no' => $combo->part_no,
                        'is_combo' => true,
                        'combo_id' => $combo->id,
                        'combo_item_id' => $comboItemIds,
                        'combo_product_id' => $comboProductIds,
                        'combo_product_category_id' => $combo->product_category_id,
                        'order_type' => $orderTypes,
                        'quantity' => $quantities,
                        'combo_products' => $productNames,
                        'price' => $combo->price,
                    ];


                    if ($includeStock && $warehouse_id) {
                        $item['available_stock'] = $availableStocks;
                    }

                    $result[] = $item;
                }
            }

            return $this->sendResponse($result, "Product list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_product_and_variant_without_category(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $product = Product::with([
                'product_variant_prices:id,product_id,price,variant_id,product_code',
                'product_variant_prices.variant:id,name'
            ])
                ->where('company_id', $company_id)
                ->where('status', 'active');
            $data = $product->orderBy('products.id', 'asc')->get();
            $result = [];
            foreach ($data as $row) {
                // if (!empty($row->product_variant_prices) && count($row->product_variant_prices)) {
                //     foreach ($row->product_variant_prices as $pvp) {
                //         $parts = [
                //             $row->product_name ?? '',
                //             $pvp->variant->name ?? '',
                //             $pvp->product_code ?? '',
                //         ];

                //         $product = collect($parts)
                //             ->filter()
                //             ->implode(' - ');

                //         $result[] = [
                //             'id' => (string) $pvp->id,
                //             'product' => $product,
                //         ];
                //     }
                // } else {
                $result[] = [
                    'id' => (string) $row->id,
                    'product' => $row->product_name ?? '',
                    'part_no' => $row->part_no ?? ''
                ];
                // }
            }

            return $this->sendResponse($result, "Product list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function generate_quotation_no(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;
            $company = Company::where('id', $company_id)->select('quotation_prefix')->first();
            $quotation_prefix = $company->quotation_prefix ?? 'OA';

            $year = date('Y');
            $month = date('m');

            $shortYear = function ($y) {
                return substr($y, -2);
            };

            if ($month >= 4) {
                $fy = $shortYear($year) . '-' . $shortYear($year + 1);
            } else {
                $fy = $shortYear($year - 1) . '-' . $shortYear($year);
            }

            $prefix = $fy . '/' . $quotation_prefix . '/';
            $allQuotations = Quotation::withTrashed()
                ->where('company_id', $company_id)
                ->where('quotation_no', 'like', $prefix . '%')
                ->get()
                ->map(function ($q) {
                    $parts = explode('/', $q->quotation_no);
                    return [
                        'num' => (int) end($parts),
                        'trashed' => $q->trashed()
                    ];
                });

            if ($allQuotations->isEmpty()) {
                $nextNumber = 1;
            } else {
                $maxNum = $allQuotations->max('num');
                $isMaxTrashed = $allQuotations->where('num', $maxNum)->where('trashed', false)->isEmpty();

                if ($isMaxTrashed) {
                    $nextNumber = $maxNum;
                } else {
                    $nextNumber = $maxNum + 1;
                }
            }

            $quotationNo = $fy . '/' . $quotation_prefix . '/' . $nextNumber;
            return $this->sendResponse($quotationNo, "Quotation number generated successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function generate_receipt_no(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;
            $lastReceipt = PaymentReceipt::withTrashed()
                ->where('company_id', $company_id)
                ->orderByDesc('id')
                ->first();

            if ($lastReceipt && !empty($lastReceipt->receipt_no)) {
                preg_match('/\d+$/', $lastReceipt->receipt_no, $matches);
                $lastNo = isset($matches[0]) ? (int) $matches[0] : 0;

                $nextNumber = $lastReceipt->trashed()
                    ? $lastNo
                    : $lastNo + 1;
            } else {
                $nextNumber = 1;
            }

            $receiptNo = 'REC-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $exists = PaymentReceipt::where('company_id', $company_id)
                ->where('receipt_no', $receiptNo)
                ->exists();

            if ($exists) {
                $nextNumber++;
                $receiptNo = 'REC-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }

            return $this->sendResponse($receiptNo, "Receipt number generated successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendError("Something went wrong while generating receipt number.");
    }

    public function get_quotation_dropdown(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;

            $quotations = Quotation::select('id', 'quotation_no')
                ->where('company_id', $company_id)
                ->get();

            return $this->sendResponse($quotations, 'Quotations fetched successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function get_quantity_tax_type(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request?->company_id;
            $company = Company::where('id', $company_id)->get();
            return $this->sendResponse($company, "Company info.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function generate_vendor_no(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;

            $company = Company::where('id', $company_id)->select('vendor_prefix')->first();
            $vendor_prefix = $company->vendor_prefix ?? 'VND';


            $lastVendor = Vendor::withTrashed()
                ->where('company_id', $company_id)
                ->orderByDesc('id')
                ->first();

            if ($lastVendor && !empty($lastVendor->vendor_code)) {
                preg_match('/\d+$/', $lastVendor->vendor_code, $matches);
                $lastNo = isset($matches[0]) ? (int) $matches[0] : 0;

                $nextNumber = $lastVendor->trashed()
                    ? $lastNo
                    : $lastNo + 1;
            } else {
                $nextNumber = 1;
            }

            $vendorNo = $vendor_prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $exists = Vendor::where('company_id', $company_id)
                ->where('vendor_code', $vendorNo)
                ->exists();

            if ($exists) {
                $nextNumber++;
                $vendorNo = $vendor_prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }

            return $this->sendResponse($vendorNo, "Vendor number generated successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something went wrong while generating vendor number.");
    }


    public function get_vendor_info(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'vendor_id' => [
                    'required',
                    Rule::exists((new Vendor())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request?->company_id;
            $vendor_id = $request?->vendor_id;
            $vendor = Vendor::with(['state', 'country'])->where('company_id', $company_id)->where('id', $vendor_id)->first();
            return $this->sendResponse($vendor, "Vendor info.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_vendors(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request?->company_id;
            $vendors = Vendor::where('company_id', $company_id)->get();
            return $this->sendResponse($vendors, "Vendor list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function generate_purchase_order_no(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;
            $company = Company::where('id', $company_id)->select('purchase_order_prefix')->first();
            $purchase_order_prefix = $company->purchase_order_prefix ?? 'PO';


            $year = date('Y');
            $month = date('m');

            $shortYear = function ($y) {
                return substr($y, -2);
            };

            if ($month >= 4) {
                $fy = $shortYear($year) . '-' . $shortYear($year + 1);
            } else {
                $fy = $shortYear($year - 1) . '-' . $shortYear($year);
            }


            $lastPurchaseOrder = PurchaseOrder::withTrashed()
                ->where('company_id', $company_id)
                ->selectRaw("purchase_order_no, CAST(SUBSTRING_INDEX(purchase_order_no, '/', -1) AS UNSIGNED) AS num")
                ->orderByDesc('num')
                ->first();

            if ($lastPurchaseOrder && !empty($lastPurchaseOrder->purchase_order_no)) {
                preg_match('/\d+$/', $lastPurchaseOrder->purchase_order_no, $matches);
                $lastNo = isset($matches[0]) ? (int) $matches[0] : 0;

                $nextNumber = $lastPurchaseOrder->trashed()
                    ? $lastNo
                    : $lastNo + 1;
            } else {
                $nextNumber = 1;
            }

            $purchaseOrderNo = $fy . '/' . $purchase_order_prefix . '/' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            if (
                PurchaseOrder::where('company_id', $company_id)
                ->where('purchase_order_no', $purchaseOrderNo)
                ->exists()
            ) {
                $nextNumber++;
                $purchaseOrderNo = $fy . '/' . $purchase_order_prefix . '/' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
            return $this->sendResponse($purchaseOrderNo, "Purchase order number generated successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something went wrong while generating purchase order number.");
    }


    public function generate_job_work_order_no(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new CompanyDetails())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;
            $company = CompanyDetails::where('id', $company_id)->select('job_work_order_prefix')->first();
            $job_work_order_prefix = $company->job_work_order_prefix ?? 'JWO';


            $year = date('Y');
            $month = date('m');

            $shortYear = function ($y) {
                return substr($y, -2);
            };

            if ($month >= 4) {
                $fy = $shortYear($year) . '-' . $shortYear($year + 1);
            } else {
                $fy = $shortYear($year - 1) . '-' . $shortYear($year);
            }

            $lastJobWorkOrder = JobWorkOrder::withTrashed()
                ->where('company_id', $company_id)
                ->orderByDesc('id')
                ->first();

            if ($lastJobWorkOrder && !empty($lastJobWorkOrder->job_work_order_no)) {
                preg_match('/\d+$/', $lastJobWorkOrder->job_work_order_no, $matches);
                $lastNo = isset($matches[0]) ? (int) $matches[0] : 0;

                $nextNumber = $lastJobWorkOrder->trashed()
                    ? $lastNo
                    : $lastNo + 1;
            } else {
                $nextNumber = 1;
            }

            $jobWorkOrderNo = $fy . '/' . $job_work_order_prefix . '/' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            if (
                JobWorkOrder::where('company_id', $company_id)
                ->where('job_work_order_no', $jobWorkOrderNo)
                ->exists()
            ) {
                $nextNumber++;
                $jobWorkOrderNo = $fy . '/' . $job_work_order_prefix . '/' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
            return $this->sendResponse($jobWorkOrderNo, "Job Work Order number generated successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something went wrong while generating Job Work Order number.");
    }


    private function generateAvailableJobWorkNumber(string $modelClass, string $numberColumn, string $companyId, string $fy, string $prefix): string
    {
        $lastRecord = $modelClass::where('company_id', $companyId)
            ->orderByDesc('id')
            ->first();

        if ($lastRecord && !empty($lastRecord->{$numberColumn})) {
            preg_match('/\d+$/', $lastRecord->{$numberColumn}, $matches);
            $lastNo = isset($matches[0]) ? (int) $matches[0] : 0;
            $nextNumber = $lastNo + 1;
        } else {
            $nextNumber = 1;
        }

        do {
            $jobWorkNo = $fy . '/' . $prefix . '/' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $numberExists = $modelClass::where('company_id', $companyId)
                ->where($numberColumn, $jobWorkNo)
                ->exists();

            if ($numberExists) {
                $nextNumber++;
            }
        } while ($numberExists);

        return $jobWorkNo;
    }

    public function generate_job_work_issue_no(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new CompanyDetails())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;
            $company = CompanyDetails::where('id', $company_id)->select('job_work_issue_prefix')->first();
            $job_work_issue_prefix = $company->job_work_issue_prefix ?? 'JWI';

            $year = date('Y');
            $month = date('m');

            $shortYear = function ($y) {
                return substr($y, -2);
            };

            if ($month >= 4) {
                $fy = $shortYear($year) . '-' . $shortYear($year + 1);
            } else {
                $fy = $shortYear($year - 1) . '-' . $shortYear($year);
            }

            $jobWorkIssueNo = $this->generateAvailableJobWorkNumber(
                JobWorkIssue::class,
                'job_work_issue_no',
                $company_id,
                $fy,
                $job_work_issue_prefix
            );
            return $this->sendResponse($jobWorkIssueNo, "Job Work Issue number generated successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function generate_job_work_receive_no(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new CompanyDetails())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;
            $company = CompanyDetails::where('id', $company_id)->select('job_work_receive_prefix')->first();
            $job_work_receive_prefix = $company->job_work_receive_prefix ?? 'JWR';

            $year = date('Y');
            $month = date('m');

            $shortYear = function ($y) {
                return substr($y, -2);
            };

            if ($month >= 4) {
                $fy = $shortYear($year) . '-' . $shortYear($year + 1);
            } else {
                $fy = $shortYear($year - 1) . '-' . $shortYear($year);
            }

            $jobWorkReceiveNo = $this->generateAvailableJobWorkNumber(
                JobWorkReceive::class,
                'job_work_receive_no',
                $company_id,
                $fy,
                $job_work_receive_prefix
            );
            return $this->sendResponse($jobWorkReceiveNo, "Job Work Receive number generated successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something went wrong while generating Job Work Receive number.");
    }


    public function generate_qc_inspection_no(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new CompanyDetails())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;
            $company = CompanyDetails::where('id', $company_id)->select('qc_inspection_prefix')->first();
            $qc_inspection_prefix = $company->qc_inspection_prefix ?? 'QC';

            $year = date('Y');
            $month = date('m');

            $shortYear = function ($y) {
                return substr($y, -2);
            };

            if ($month >= 4) {
                $fy = $shortYear($year) . '-' . $shortYear($year + 1);
            } else {
                $fy = $shortYear($year - 1) . '-' . $shortYear($year);
            }

            $lastQcInspection = QcInspection::withTrashed()
                ->where('company_id', $company_id)
                ->orderByDesc('id')
                ->first();

            if ($lastQcInspection && !empty($lastQcInspection->qc_inspection_no)) {

                preg_match('/\d+$/', $lastQcInspection->qc_inspection_no, $matches);
                $lastNo = isset($matches[0]) ? (int) $matches[0] : 0;

                $nextNumber = $lastQcInspection->trashed()
                    ? $lastNo
                    : $lastNo + 1;
            } else {
                $nextNumber = 1;
            }

            $qcInspectionNo = $fy . '/' . $qc_inspection_prefix . '/' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            if (
                QcInspection::where('company_id', $company_id)
                ->where('qc_inspection_no', $qcInspectionNo)
                ->exists()
            ) {
                $nextNumber++;
                $qcInspectionNo = $fy . '/' . $qc_inspection_prefix . '/' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
            return $this->sendResponse($qcInspectionNo, "QC Inspection number generated successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something went wrong while generating QC Inspection number.");
    }

    public function get_job_work_issue_no(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request->company_id;

            $salesStatusesLast = SalesStatus::where('company_id', $request->company_id)
                ->where('salestype', 'Job Work Issue')
                ->select("id")
                ->orderBy('display_order', 'desc')
                ->first();

            $jobWorkIssues = JobWorkIssue::where('company_id', $company_id)
                ->where('status', 'active')
                ->when($salesStatusesLast, function ($query) use ($salesStatusesLast) {
                    $query->where('sales_status_id', '!=', $salesStatusesLast->id)->whereNotNull('sales_status_id')->where('sales_status_id', '!=', "");
                })
                ->with('warehouse')
                ->orderBy('id', 'desc')
                ->get();

            $data = $jobWorkIssues->map(function ($row) {
                return [
                    'id' => (string) $row->id,
                    'job_work_issue_no' => (string) $row->job_work_issue_no,
                    'sales_status_id' => $row->sales_status_id,
                    'product_category_id' => $row->product_category_id,
                    'warehouse_id' => $row->warehouse_id,
                    'warehouse_name' => $row->warehouse ? $row->warehouse->name : null,
                    'job_work_issue_date' => $row->job_work_issue_date
                        ? date('d-m-Y', strtotime($row->job_work_issue_date))
                        : null,
                    'vendor_id' => $row->vendor_id,
                ];
            });

            return $this->sendResponse($data, "Job Work Issue list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function generate_sales_order_no(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;
            $company = Company::where('id', $company_id)->select('order_prefix')->first();
            $order_prefix = $company->order_prefix ?? 'SO';


            $year = date('Y');
            $month = date('m');

            $shortYear = function ($y) {
                return substr($y, -2);
            };

            if ($month >= 4) {
                $fy = $shortYear($year) . '-' . $shortYear($year + 1);
            } else {
                $fy = $shortYear($year - 1) . '-' . $shortYear($year);
            }

            $lastSalesOrder = SalesOrder::withTrashed()
                ->where('company_id', $company_id)
                ->orderByDesc('id')
                ->first();

            if ($lastSalesOrder && !empty($lastSalesOrder->sales_order_no)) {
                preg_match('/\d+$/', $lastSalesOrder->sales_order_no, $matches);
                $lastNo = isset($matches[0]) ? (int) $matches[0] : 0;

                $nextNumber = $lastSalesOrder->trashed()
                    ? $lastNo
                    : $lastNo + 1;
            } else {
                $nextNumber = 1;
            }

            $salesOrderNo = $fy . '/' . $order_prefix . '/' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            if (
                SalesOrder::where('company_id', $company_id)
                ->where('sales_order_no', $salesOrderNo)
                ->exists()
            ) {
                $nextNumber++;
                $salesOrderNo = $fy . '/' . $order_prefix . '/' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
            return $this->sendResponse($salesOrderNo, "Sales order number generated successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendError("Something went wrong while generating sales order number.");
    }



    public function get_transport_details(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'transport_by_id' => [
                    'required',
                    Rule::exists((new MasterTransportBy())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request?->company_id;
            $transport_by_id = $request?->transport_by_id;
            $transport_details = MasterTransportDetail::where('company_id', $company_id)->where('transport_by_id', $transport_by_id)->where('status', 'active');
            $data = $transport_details->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['transport_details'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Transport Details list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_payment_receipt_type(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $companyId = $request?->company_id;
            $company = Company::select('id', 'company_name', 'email', 'account_effect_by')->findOrFail($companyId);

            $allTypes = PaymentReceipt::$receipt_type;
            $accountEffect = $company->account_effect_by;

            $filteredTypes = [];
            foreach ($allTypes as $key => $val) {
                if (isset($key) && $key == 'general') {
                    $filteredTypes['general'] = $val;
                }
                if ($key && $key != 'general' && $key === $accountEffect) {
                    $filteredTypes[$key] = $val;
                }
            }
            return $this->sendResponse($filteredTypes, 'Receipt Type Fetched Successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation failed.', $e->errors(), [], 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', $e->getMessage(), [], 500);
        }
    }

    public function get_terms(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request?->company_id;
            $terms = TermsAndCondition::where('company_id', $company_id)->where('status', 'active');
            $data = $terms->orderBy('title', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['terms_name'] = $row?->title . "";
                return $temp;
            });
            return $this->sendResponse($data, "Terms list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_terms_info(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'terms_id' => [
                    'required',
                    Rule::exists((new TermsAndCondition())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request?->company_id;
            $terms_id = $request?->terms_id;
            $terms = TermsAndCondition::where('company_id', $company_id)->where('id', $terms_id)->where('status', 'active');
            $data = $terms->orderBy('title', 'asc')->first();
            return $this->sendResponse($data, "Terms list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_customer(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'customer_type_id' => [
                    'nullable',
                    Rule::exists((new CustomerType())->getTable(), 'id')
                ],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $customer = Customer::where('company_id', $company_id)
                ->where('status', 'active');
            if (!empty($request->customer_type_id)) {
                $customer = $customer->where('customer_type_id', $request?->customer_type_id);
            }

            $data = $customer->orderBy('id', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['customer_name'] = $row?->customer_name . "";
                $temp['contact_person_name'] = $row?->contact_person_name . "";
                $temp['client_code'] = $row?->client_code . "";
                $temp['gst_number'] = $row?->gst_number . "";
                $temp['shipping_address'] = $row?->shipping_address . "";
                $temp['billing_address'] = $row?->billing_address . "";
                $temp['address'] = $row?->address . "";

                return $temp;
            });

            return $this->sendResponse($data, "Customer list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_customer_info(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'customer_id' => [
                    'required',
                    Rule::exists((new Customer())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request?->company_id;
            $customer_id = $request?->customer_id;

            $company = Company::with(['company_details'])->where('id', $company_id)->first();
            $data = Customer::with(['state', 'country', 'city'])->where('company_id', $company_id)->where('id', $customer_id)->where('status', 'active')->first();

            if ($company->state_id != $data->state_id) {
                $data->is_igst = 1;
            } else {
                $data->is_igst = 0;
            }

            return $this->sendResponse($data, "Customer info.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function generate_dispatch_no(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;
            $company = Company::where('id', $company_id)->select('dispatch_prefix')->first();
            $prefix = $company->dispatch_prefix ?? 'DIS';

            $last = Dispatch::withTrashed()->where('company_id', $company_id)->orderByDesc('id')->first();

            if ($last && !empty($last->dispatch_no)) {
                preg_match('/\d+$/', $last->dispatch_no, $matches);
                $lastNo = isset($matches[0]) ? (int) $matches[0] : 0;

                $nextNumber = $last->trashed()
                    ? $lastNo
                    : $lastNo + 1;
            } else {
                $nextNumber = 1;
            }

            $dispatchNo = $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            $exists = Dispatch::where('company_id', $company_id)->where('dispatch_no', $dispatchNo)->exists();
            if ($exists) {
                $nextNumber++;
                $dispatchNo = $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }

            return $this->sendResponse($dispatchNo, "Dispatch number generated successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function generate_packing_slip_no(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;
            $company = Company::where('id', $company_id)->select('packing_slip_prefix')->first();
            $prefix = $company->packing_slip_prefix ?? 'PS';

            $year = date('Y');
            $month = date('m');

            $shortYear = function ($y) {
                return substr($y, -2);
            };

            if ($month >= 4) {
                $fy = $shortYear($year) . '-' . $shortYear($year + 1);
            } else {
                $fy = $shortYear($year - 1) . '-' . $shortYear($year);
            }

            $last = PackingSlip::withTrashed()
                ->where('company_id', $company_id)
                ->orderByDesc('id')
                ->first();

            if ($last && !empty($last->packing_slip_no)) {
                preg_match('/\d+$/', $last->packing_slip_no, $matches);
                $lastNo = isset($matches[0]) ? (int) $matches[0] : 0;

                $nextNumber = $last->trashed()
                    ? $lastNo
                    : $lastNo + 1;
            } else {
                $nextNumber = 1;
            }


            $packingSlipNo = $fy . '/' . $prefix . '/' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            if (
                PackingSlip::where('company_id', $company_id)
                ->where('packing_slip_no', $packingSlipNo)
                ->exists()
            ) {
                $nextNumber++;
                $packingSlipNo = $fy . '/' . $prefix . '/' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
            return $this->sendResponse($packingSlipNo, "Packing slip number generated successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function generate_inward_no(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;
            $year = date('Y');
            $month = date('m');

            $shortYear = function ($y) {
                return substr($y, -2);
            };

            if ($month >= 4) {
                $fy = $shortYear($year) . '-' . $shortYear($year + 1);
            } else {
                $fy = $shortYear($year - 1) . '-' . $shortYear($year);
            }

            $last = Inward::withTrashed()
                ->where('company_id', $company_id)
                ->orderByDesc('id')
                ->first();

            if ($last && !empty($last->inward_no)) {
                preg_match('/\d+$/', $last->inward_no, $matches);
                $lastNo = isset($matches[0]) ? (int) $matches[0] : 0;

                $nextNumber = $last->trashed()
                    ? $lastNo
                    : $lastNo + 1;
            } else {
                $nextNumber = 1;
            }

            $inwardNo = $fy . '/INW/' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            if (
                Inward::where('company_id', $company_id)
                ->where('inward_no', $inwardNo)
                ->exists()
            ) {
                $nextNumber++;
                $inwardNo = $fy . '/INW/' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
            return $this->sendResponse($inwardNo, "Inward number generated successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }


    public function generate_invoice_no(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;
            $year = date('Y');
            $month = date('m');

            $shortYear = function ($y) {
                return substr($y, -2);
            };

            if ($month >= 4) {
                $fy = $shortYear($year) . '-' . $shortYear($year + 1);
            } else {
                $fy = $shortYear($year - 1) . '-' . $shortYear($year);
            }

            $lastInvoice = Invoice::withTrashed()
                ->where('company_id', $company_id)
                ->whereNotNull('invoice_no')
                ->orderByDesc('id')
                ->first();

            if ($lastInvoice && !empty($lastInvoice->invoice_no)) {
                preg_match('/\d+$/', $lastInvoice->invoice_no, $matches);
                $lastNo = isset($matches[0]) ? (int) $matches[0] : 0;

                $nextNumber = $lastInvoice->trashed()
                    ? $lastNo
                    : $lastNo + 1;
            } else {
                $nextNumber = 1;
            }

            $invoiceNo = $fy . '/INV/' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            if (Invoice::where('company_id', $company_id)->where('invoice_no', $invoiceNo)->exists()) {
                $nextNumber++;
                $invoiceNo = $fy . '/INV/' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }

            if ($request->directReturn) {
                return $invoiceNo;
            }

            return $this->sendResponse($invoiceNo, "Invoice number generated successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something went wrong while generating invoice number.");
    }



    public function generate_grn_no(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;
            $year = date('Y');
            $month = date('m');

            $shortYear = function ($y) {
                return substr($y, -2);
            };

            if ($month >= 4) {
                $fy = $shortYear($year) . '-' . $shortYear($year + 1);
            } else {
                $fy = $shortYear($year - 1) . '-' . $shortYear($year);
            }

            $last = GRN::withTrashed()
                ->where('company_id', $company_id)
                ->orderByDesc('id')
                ->first();

            if ($last && !empty($last->grn_no)) {
                preg_match('/\d+$/', $last->grn_no, $matches);
                $lastNo = isset($matches[0]) ? (int) $matches[0] : 0;

                $nextNumber = $last->trashed()
                    ? $lastNo
                    : $lastNo + 1;
            } else {
                $nextNumber = 1;
            }

            $grnNo = $fy . '/GRN/' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            if (
                GRN::where('company_id', $company_id)
                ->where('grn_no', $grnNo)
                ->exists()
            ) {
                $nextNumber++;
                $grnNo = $fy . '/GRN/' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
            return $this->sendResponse($grnNo, "GRN number generated successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }


    public function generate_complain_no(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;

            $last = Complain::withTrashed()->where('company_id', $company_id)->orderByDesc('id')->first();

            if ($last && !empty($last->complain_no)) {
                preg_match('/\d+$/', $last->complain_no, $matches);
                $lastNo = isset($matches[0]) ? (int) $matches[0] : 0;

                $nextNumber = $last->trashed()
                    ? $lastNo
                    : $lastNo + 1;
            } else {
                $nextNumber = 1;
            }

            $complainNo = 'CMP-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            $exists = Complain::where('company_id', $company_id)->where('complain_no', $complainNo)->exists();
            if ($exists) {
                $nextNumber++;
                $complainNo = 'CMP-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }

            return $this->sendResponse($complainNo, "Complain number generated successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }


    public function inquiryExists(Request $request)
    {
        try {
            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }

            $company_id = $request?->company_id ?? $loginUser?->company_id;

            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'mobile_no' => [
                    'required',
                    'digits:10',
                    'numeric'
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $inquiry = Inquiry::where('company_id', $company_id)->where('mobile_no', $request->mobile_no)->first();
            if ($inquiry) {
                $extraData = [];
                $extraData['inquiry'] = $inquiry;
                $extraData['modelTitle'] = "Customer With Same Mobile No";
                // $extraData['inquiryViewLink'] = route('inquiry.show', ['inquiry' => $inquiry?->id]);

                View::share('inquiry', $inquiry);
                $extraData['exitingInquiryHtml'] = view('software.modules.inquiry.show')->render();
                return $this->sendError("This inquiry duplicate.", [], $extraData);
            }
            return $this->sendResponse($inquiry, "Inquiry not exists.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }


    public function get_price_lists(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new \App\Models\Company())->getTable(), 'id')
                ]
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;

            $priceLists = \App\Models\PriceList::where('company_id', $company_id)
                ->where('status', 'active') // if you have a 'status' column
                ->orderBy('name', 'asc')
                ->get();

            $data = $priceLists->map(function ($row) {
                // return $row;
                return [
                    'id' => (string) $row?->id,
                    'name' => (string) $row?->name,
                    'company_id' => (string) $row?->company_id,
                    'company_name' => (string) $row?->company?->company_name,
                ];
            });

            return $this->sendResponse($data, "Price list fetched successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendError("Something went wrong in the price list API.");
    }

    public function get_packingType(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $packingType = PackingType::where('company_id', $company_id)
                ->where('status', 'active');

            $data = $packingType->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['name'] = $row?->name . "";
                $temp['weight'] = $row?->weight . "";
                return $temp;
            });

            return $this->sendResponse($data, "Packing Type list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_subscription_plan(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'plan_id' => [
                    'nullable',
                    Rule::exists((new PlanMaster())->getTable(), 'id')
                ],
                'subscription_id' => [
                    'required',
                    Rule::exists((new CompanySubscriptionPlan())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $plan_id = $request?->plan_id;
            $subscription_id = $request?->subscription_id;

            $query = CompanySubscriptionPlan::with(['plan', 'company'])->select('*')
                ->where('company_id', $company_id)->where('id', $subscription_id)->where('plan_id', $plan_id)->where('subscription_status', 'active');
            $data = $query->orderBy('id', 'desc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['company_name'] = $row?->company?->company_name . "";
                $temp['plan_name'] = $row?->plan?->name . "";
                return $temp;
            });

            return $this->sendResponse($data, "Subscription Plan list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_plans(Request $request)
    {
        try {
            $plan_master = PlanMaster::where('status', 'active');
            $plan_master = $plan_master->orderBy('id', 'desc')->get();

            return $this->sendResponse($plan_master, 'Plan list');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), [], 401);
        }
        return $this->sendError("Something want to wrong in Plan list API");
    }


    /** Add Current Location */
    public function add_current_location(Request $request)
    {
        try {
            $loginUser = Auth::user();
            // If not authenticated, we must validate all fields
            $isAuthenticated = $loginUser !== null;

            // Base rules
            $rules = [
                'latitude' => ['required', 'numeric'],
                'longitude' => ['required', 'numeric'],
            ];

            if (!$isAuthenticated) {
                $rules['company_id'] = ['required', 'integer'];
                $rules['user_type'] = ['required', 'string'];
                $rules['user_id'] = ['required', 'integer'];
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            // Extract data
            // $insertData = [
            //     'company_id' => $isAuthenticated ? $loginUser->company_id : $request->input('company_id'),
            //     'user_id'    => $isAuthenticated ? $loginUser->id : $request->input('user_id'),
            //     'latitude'   => $request->latitude,
            //     'longitude'  => $request->longitude,
            // ];

            $insertData = $request->all();
            $insertData['company_id'] = ($isAuthenticated && $loginUser?->company_id) ? $loginUser?->company_id : null;

            $insertData['user_id'] = ($isAuthenticated && $loginUser?->id) ? $loginUser?->id : null;
            $insertData['user_id'] = (!isset($insertData['user_id']) && $request?->user_id) ? $request?->user_id : $insertData['user_id'];

            $insertData['user_type'] = ($isAuthenticated && $loginUser?->user_type) ? $loginUser?->user_type : 'team_person';

            $responseMessage = 'Your current location added';
            if ($isAuthenticated && $insertData['company_id']) {
                $checkAnyActionStarted = SaleExecutiveTracking::query();
                $checkAnyActionStarted = $checkAnyActionStarted->where('company_id', $insertData['company_id']);
                $checkAnyActionStarted = $checkAnyActionStarted->where('created_by', $insertData['user_id']);
                $checkAnyActionStarted = $checkAnyActionStarted->whereIn('type', [SaleExecutiveTracking::VISIT_START, SaleExecutiveTracking::VISIT_END]);
                $checkAnyActionStarted = $checkAnyActionStarted->orderBy('id', 'DESC')->limit(2)->get();

                if ($checkAnyActionStarted->first()?->type == SaleExecutiveTracking::VISIT_START) {
                    $insertedData = SaleExecutiveTracking::create([
                        'company_id' => $insertData['company_id'],
                        'type_reference_id' => $checkAnyActionStarted->first()?->type_reference_id,
                        'type' => SaleExecutiveTracking::VISIT_IN_PROCESS,
                        'latitude' => $request?->latitude ?? NULL,
                        'longitude' => $request?->longitude ?? NULL,
                        'address' => $request?->address ?? NULL,
                        'current_datetime' => Carbon::now(),
                        'created_by' => $insertData['user_id'],
                    ]);
                }
                $responseMessage = "Your route added in sales tracking";
                // dd("L-1754", $insertData, $checkAnyActionStarted->toArray());
            } else {
                // return $insertData;
                $insertedData = TeamPersonMapCordinates::create($insertData);
            }

            return $this->sendResponse($insertedData, $responseMessage);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), [], 401);
        }
        return $this->sendError("Something want to wrong in Plan list API");
    }
    public function getLocationByPincode(Request $request)
    {
        $pincode = $request->pincode;

        // Validate pincode
        if (!$pincode || strlen($pincode) != 6) {
            return response()->json(['status' => false, 'message' => 'Invalid Pincode']);
        }

        // Call India Post API
        $response = Http::withHeaders([
            'User-Agent' => 'Laravel-HttpClient'
        ])->get("https://api.postalpincode.in/pincode/{$pincode}");
        $data = $response->json();

        // If response contains valid PostOffice data
        if (!empty($data[0]['PostOffice'])) {
            $postOffices = $data[0]['PostOffice'];
            $firstPO = $postOffices[0];

            // Extract Country and State
            $countryName = $firstPO['Country'] ?? 'India';
            $stateName = $firstPO['State'] ?? null;

            // Match with database
            $country = MasterCountry::where('name', $countryName)->first();
            $state = MasterState::where('name', $stateName)->first();

            // Collect city names from only 'Name'
            $cityNames = [];
            foreach ($postOffices as $po) {
                if (!empty($po['Name'])) {
                    $cityNames[] = $po['Name'];
                }
            }

            // Remove duplicates
            $cityNames = collect($cityNames)->unique()->values();

            // Get all matched cities
            $cities = MasterCity::whereIn('name', $cityNames)->get();

            // Try selecting a city that exactly matches the first PostOffice name
            $selectedCity = null;
            if (!empty($firstPO['Name'])) {
                $selectedCity = MasterCity::where('name', $firstPO['Name'])->first();
            }

            // Return response
            return response()->json([
                'status' => true,
                'data' => [
                    'country_id' => $country?->id,
                    'state_id' => $state?->id,
                    'cities' => $cities->map(fn($city) => [
                        'id' => $city->id,
                        'name' => $city->name
                    ]),
                    'selected_city_id' => $selectedCity?->id
                ]
            ]);
        }

        // No valid post office data found
        return response()->json(['status' => false, 'message' => 'Pincode not found']);
    }
    // Purpose Of Visit Master
    public function get_purpose_visit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;

            $purposeVisits = PurposeVisitMaster::where('company_id', $company_id)
                ->where('status', 'active')
                ->orderBy('name', 'asc')
                ->get();

            $data = $purposeVisits->map(function ($item) {
                return [
                    'id' => (string) $item->id,
                    'purpose_visit_name' => (string) $item->name,
                ];
            });

            return $this->sendResponse($data, "Purpose Visit list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendError("Something went wrong in Purpose Visit API.");
    }
    // Department
    public function get_department(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;

            $departments = Department::where('company_id', $company_id)
                ->where('status', 'active')
                ->orderBy('name', 'asc')
                ->get();

            $data = $departments->map(function ($item) {
                return [
                    'id' => (string) $item->id,
                    'department_name' => (string) $item->name,
                ];
            });

            return $this->sendResponse($data, "Department list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendError("Something went wrong in Department API.");
    }
    // Visitor Type
    public function get_visitor_type(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;

            $visitorTypes = VisitorType::where('company_id', $company_id)
                ->where('status', 'active')
                ->orderBy('name', 'asc')
                ->get();

            $data = $visitorTypes->map(function ($item) {
                return [
                    'id' => (string) $item->id,
                    'visitor_type_name' => (string) $item->name,
                ];
            });

            return $this->sendResponse($data, "Visitor Type list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendError("Something went wrong in Visitor Type API.");
    }

    public function get_product_grade(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $product_grade = ProductGrade::where('company_id', $company_id)->where('status', 'active');
            $data = $product_grade->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['product_grade'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Product Grade list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }



    public function get_product_hsn(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $product_hsn = ProductHSN::where('company_id', $company_id)->where('status', 'active');
            $data = $product_hsn->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['product_hsn'] = $row?->name . "";
                $temp['product_tax'] = $row?->product_tax . "";
                return $temp;
            });
            return $this->sendResponse($data, "Product HSN list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_product_godown(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $product_godown = ProductGodown::where('company_id', $company_id)->where('status', 'active');
            $data = $product_godown->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['product_godown'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Product Godown list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_product_location(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $product_location = Location::where('company_id', $company_id)->where('status', 'active');
            $data = $product_location->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['product_location'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Location list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_last_process(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $last_process = LastProcess::where('company_id', $company_id)->where('status', 'active');
            $data = $last_process->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['last_process'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Last Process list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_issue_for_process(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $issue_process = IssueForProcess::where('company_id', $company_id)->where('status', 'active');
            $data = $issue_process->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['issue_process'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Issue For Process list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_process_received(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $process_received = ProcessReceived::where('company_id', $company_id)->where('status', 'active');
            $data = $process_received->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['process_received'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Process Received list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_product_rack(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $product_rack = ProductRack::where('company_id', $company_id)->where('status', 'active');
            $data = $product_rack->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['product_rack'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Product Rack list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_product_alloy(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $product_alloy = ProductAlloy::where('company_id', $company_id)->where('status', 'active');
            $data = $product_alloy->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['product_alloy'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Product Alloy list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_product_material_map(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'product_id' => [
                    'required',
                    Rule::exists((new Product())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;
            $product_id = $request?->product_id;
            $data = MaterialDetailMap::select(
                'material_detail_map.*',
                'material_detail.dimension',
                'material_detail.size',
                'material_detail.ltl',
                'material_detail.utl',
                'material_detail.instrument'
            )
                ->leftJoin('material_detail', 'material_detail_map.material_id', '=', 'material_detail.id')
                ->where('material_detail_map.company_id', $company_id)
                ->where('material_detail_map.product_id', $product_id)
                ->where('material_detail.status', 'active')
                ->get();

            return $this->sendResponse($data, "Product BOM list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }


    public function paymentTermsList(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request->company_id;

            $paymentTerms = PaymentTerm::where('company_id', $company_id)
                ->where('status', 'active')
                ->orderBy('id', 'asc')
                ->get(['id', 'payment_terms', 'credit_days']);

            return $this->sendResponse(
                $paymentTerms,
                "Payment terms list fetched successfully."
            );
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendError("Something went wrong while fetching payment terms.");
    }

    public function get_job_work_type(Request $request)
    {
        try {
            $company_id = $request->company_id;
            $data = JobWorkType::where('company_id', $company_id)->orderBy('id', 'ASC')->get();

            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['name'] = $row?->name . "";
                $temp['bl_checkbox'] = $row?->bl_checkbox . "";
                $temp['bl_percentage'] = $row?->bl_percentage . "";
                return $temp;
            });
            return $this->sendResponse($data, "Job Work Type list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }
}
