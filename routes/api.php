<?php

use App\Http\Controllers\Api\ApiExpoVisitorController;
use App\Http\Controllers\Api\ApiFollowupController;
use App\Http\Controllers\Api\ApiMasterUnitController;
use App\Http\Controllers\Api\ApiOrderUnitController;
use App\Http\Controllers\Api\ApiProductCategoryController;
use App\Http\Controllers\Api\ApiProductSubCategoryController;
use App\Http\Controllers\Api\ApiProductVariantController;
use App\Http\Controllers\Api\ApiTaxController;
use App\Http\Controllers\Api\ApiVisitController;
use App\Http\Controllers\Api\AuthenticateController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RolePermissionsController;
use App\Http\Controllers\Api\TeamPersonController;
use App\Http\Controllers\Api\WebhookInquiryController;
use App\Http\Controllers\Api\ApiExpoManagementController;
use App\Http\Controllers\Api\QuotationController;
use App\Http\Controllers\Api\SalesOrderController;
use App\Http\Controllers\CommonController;
use App\Models\PaymentTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::any('test', function () {
    return "API Test";
});

Route::controller(CommonController::class)->group(function () {
    Route::any('get-country', 'get_country')->name('get-country');
    Route::any('get-state', 'get_state')->name('get-state');
    Route::any('get-city', 'get_city')->name('get-city');
    Route::any('get-area', 'get_area')->name('get-area');
    Route::any('get-companies', 'get_companies')->name('get-companies');
    Route::any('get-company-social', 'get_company_social')->name('get-company-social');

    Route::any('get-product-variant', 'get_product_variant')->name('get-product-variant');

    Route::any('get-product-attachments', 'get_product_attachments')->name('get-product-attachments');
    Route::any('get-product-and-variant', 'get_product_and_variant')->name('get-product-and-variant');
    Route::any('get-product-and-variant-without-category', 'get_product_and_variant_without_category')->name('get-product-and-variant-without-category');

    Route::any('get-order-unit', 'get_order_unit')->name('get-order-unit');
    Route::any('get-complain-type', 'get_complain_type')->name('get-complain-type');
    Route::any('get-customer-type', 'get_customer_type')->name('get-customer-type');
    Route::any('get-customer', 'get_customer')->name('get-customer');

    Route::any('get-product-unit', 'get_product_unit')->name('get-product-unit');
    Route::any('get-product-tax', 'get_product_tax')->name('get-product-tax');
    Route::any('get-product-grade', 'get_product_grade')->name('get-product-grade');
    Route::any('get-product-hsn', 'get_product_hsn')->name('get-product-hsn');
    Route::any('get-product-godown', 'get_product_godown')->name('get-product-godown');
    Route::any('get-product-location', 'get_product_location')->name('get-product-location');
    Route::any('get-last-process', 'get_last_process')->name('get-last-process');
    Route::any('get-issue-for-process', 'get_issue_for_process')->name('get-issue-for-process');
    Route::any('get-process-received', 'get_process_received')->name('get-process-received');
    Route::any('get-product-rack', 'get_product_rack')->name('get-product-rack');
    Route::any('get-product-alloy', 'get_product_alloy')->name('get-product-alloy');
    Route::any('get-product-material-map', 'get_product_material_map')->name('get-product-material-map');

    Route::any('get-product-variant-price', 'get_product_variant_price')->name('get-product-variant-price');
    Route::any('get-warehouse', 'get_warehouse')->name('get-warehouse');

    Route::any('get-price-lists', 'get_price_lists')->name('get-price-lists');
    Route::get('get-location-by-pincode', [CommonController::class, 'getLocationByPincode'])->name('get-location-by-pincode');

    Route::any('get-transportby', 'get_transportby')->name('get-transportby');
    Route::any('get-designation', 'get_designation')->name('get-designation');
    Route::any('get-team-role', 'get_team_role')->name('get-team-role');
    Route::any('generate-employee-code', 'generate_employee_code')->name('generate-employee-code');
    Route::any('check-pincode', 'check_pincode')->name('check-pincode');

    Route::any('generate-vendor-no', 'generate_vendor_no')->name('generate-vendor-no');
    Route::any('generate-inward-no', 'generate_inward_no')->name('generate-inward-no');
    Route::any('generate-invoice-no', 'generate_invoice_no')->name('generate-invoice-no');
    Route::any('generate-purchase-order-no', 'generate_purchase_order_no')->name('generate-purchase-order-no');
    Route::any('generate-job-work-order-no', 'generate_job_work_order_no')->name('generate-job-work-order-no');
    Route::any('generate-job-work-issue-no', 'generate_job_work_issue_no')->name('generate-job-work-issue-no');
    Route::any('generate-job-work-receive-no', 'generate_job_work_receive_no')->name('generate-job-work-receive-no');
    Route::any('generate-qc-inspection-no', 'generate_qc_inspection_no')->name('generate-qc-inspection-no');
    Route::any('generate-grn-no', 'generate_grn_no')->name('generate-grn-no');
    Route::any('get-job-work-issue-no', 'get_job_work_issue_no')->name('get-job-work-issue-no');
    Route::any('get-vendors', 'get_vendors')->name('get-vendors');
    Route::any('get-vendor-info', 'get_vendor_info')->name('get-vendor-info');
    Route::any('get-job-work-type', 'get_job_work_type')->name('get-job-work-type');

    Route::any('generate-quotation-no', 'generate_quotation_no')->name('generate-quotation-no');
    Route::any('generate-receipt-no', 'generate_receipt_no')->name('generate-receipt-no');
    Route::any('get-pending-qc-inspection', 'get_pending_qc_inspection')->name('get-pending-qc-inspection');
    Route::any('get-product-stock-total', 'get_product_stock_total')->name('get-product-stock-total');


    Route::any('get-quantity-tax-type', 'get_quantity_tax_type')->name('get-quantity-tax-type');
    Route::any('generate-sales-order-no', 'generate_sales_order_no')->name('generate-sales-order-no');
    Route::any('get-transport-details', 'get_transport_details')->name('get-transport-details');
    Route::any('get-terms', 'get_terms')->name('get-terms');
    Route::any('get-terms-info', 'get_terms_info')->name('get-terms-info');
    Route::any('get-customer', 'get_customer')->name('get-customer');
    Route::any('get-customer-info', 'get_customer_info')->name('get-customer-info');
    Route::any('get-product-info', 'get_product_info')->name('get-product-info');
    Route::any('get-job-issue-product-info', 'get_job_issue_product_info')->name('get-job-issue-product-info');

    Route::any('get-packing-type', 'get_packingType')->name('get-packing-type');

    Route::any('generate-complain-no', 'generate_complain_no')->name('generate-complain-no');
    Route::any('generate-dispatch-no', 'generate_dispatch_no')->name('generate-dispatch-no');
    Route::any('generate-packing-slip-no', 'generate_packing_slip_no')->name('generate-packing-slip-no');
    Route::any('generate-inquiry-no', 'generate_inquiry_no')->name('generate-inquiry-no');
    Route::any('generate-client-code', 'generate_client_code')->name('generate-client-code');

    Route::any('get-inquiry-status', 'get_inquiry_status')->name('get-inquiry-status');

    Route::any('get-teams', 'get_teams')->name('get-teams');
    Route::any('get-status', 'get_status')->name('get-status');

    /** Master Module */
    Route::any('get-document-type', 'get_document_type')->name('get-document-type');
    Route::any('get-source-of-inquiry', 'get_source_of_inquiry')->name('get-source-of-inquiry');

    /** Check the inquiry */
    Route::any('inquiry-exists', 'inquiryExists')->name('inquiry-exists');

    Route::any('get-subscription-plan', 'get_subscription_plan')->name('get-subscription-plan');
    Route::any('get-plans', 'get_plans')->name('get-plans');

    /** Expo Visitor Book */
    Route::any('get-expo-category', 'get_expo_category')->name('api.get-expo-category');
    Route::POST('expo-management-list', [ApiExpoManagementController::class, 'expo_management_list'])->name('expo-management.list');

    Route::any('get-payment-receipt-type', 'get_payment_receipt_type')->name('get-payment-receipt-type');

    /* Gate Pass */
    Route::any('get-purpose-visit', [CommonController::class, 'get_purpose_visit'])->name('get-purpose-visit');
    Route::any('get-department', [CommonController::class, 'get_department'])->name('get-department');
    Route::any('get-visitor-type', [CommonController::class, 'get_visitor_type'])->name('get-visitor-type');

    /** Attendances */
    Route::POST('/punch-in-out', [TeamPersonController::class, 'punch_in_out']);
    Route::POST('/punch-history', [TeamPersonController::class, 'punch_history']);
    Route::POST('/active-punch-in-records', [TeamPersonController::class, 'active_punch_in_records']);

    /**Payement Terms */
    Route::post('payment-terms/list', [CommonController::class, 'paymentTermsList'])->name('payment.terms.list');
});


/** Only use in application */
Route::post('verify-appkey', [AuthenticateController::class, 'verify_appkey'])->name('verify-appkey');
Route::post('login', [AuthenticateController::class, 'login'])->name('login');
// Route::match(['get', 'post'],'login', [AuthenticateController::class, 'login'])->name('login');
// Route::match(['get', 'post'],'login', [AuthenticateController::class, 'login'])->name('api.login');

Route::controller(ExpenseController::class)->group(function () {
    Route::POST('/expense/category/list', 'expense_category_list');
    Route::POST('/expense/sub-category/list', 'expense_sub_category_list');
});
// Route::group(['prefix' => '/expense'], function () {
//     Route::POST('/category/list', [ExpenseController::class, 'expense_category_list']);
//     Route::POST('/sub-category/list', [ExpenseController::class, 'expense_sub_category_list']);
// });

Route::controller(ProductController::class)->group(function () {
    Route::POST('/product/category/list', 'category_list');
    Route::POST('/product/sub-category/list', 'sub_category_list');
});

Route::controller(TeamPersonController::class)->group(function () {
    Route::POST('/team-person/list', 'team_person_list')->name('api-team-person.list');
    Route::POST('/team-person/list/by-sub-category', 'team_person_list_by_sub_category');
});

Route::POST('/leave/type/list', [LeaveController::class, 'leave_type_list'])->name('api.leave_type.list');
Route::POST('/customer/type/list', [CustomerController::class, 'customer_type_list'])->name('api.customer_type.list');
Route::POST('/customer-list', [CustomerController::class, 'customer_list']);

/** Follow up*/
Route::any('get-followup-status', [ApiFollowupController::class, 'get_followup_status'])->name('get-followup-status');
Route::any('get-followup-reason', [ApiFollowupController::class, 'get_followup_reason'])->name('get-followup-reason');

Route::match(['get', 'post'], 'add-current-location', [CommonController::class, 'add_current_location'])->name('api.add_current_location');
Route::match(['get', 'post'], 'get-expo-category', [CommonController::class, 'get_expo_category'])->name('api.get_expo_category');
Route::match(['get', 'post'], 'get-expo-marketing-slider', [CommonController::class, 'get_expo_marketing_slider'])->name('api.get_expo_marketing_slider');


Route::match(['GET', 'POST'], 'get-product-with-variant', [ProductController::class, 'get_product_with_variant'])->name('api.get_product_with_variant');

Route::group(['middleware' => ['auth:team_person-api']], function () {
    // Route::get('/get-profile', function (Request $request) {
    //     return response()->json([
    //         'user' => $request->user(), // or Auth::guard('team_person-api')->user()
    //     ]);
    // });
    Route::POST('/get-profile', [AuthenticateController::class, 'get_profile']);
    Route::POST('/update-profile', [AuthenticateController::class, 'update_profile']);
    Route::match(['get', 'post'], 'add-current-location', [CommonController::class, 'add_current_location']);


    /** Attendances */
    Route::POST('/punch-in-out', [TeamPersonController::class, 'punch_in_out']);
    Route::POST('/punch-history', [TeamPersonController::class, 'punch_history']);
    Route::POST('/active-punch-in-records', [TeamPersonController::class, 'active_punch_in_records']);

    /** Attendances */
    Route::POST('/leave/add_edit', [LeaveController::class, 'leave_add_edit'])->name('api.leave.add_edit');
    Route::POST('/leave/list', [LeaveController::class, 'leave_list'])->name('api.leave.list');
    Route::POST('/leave/delete', [LeaveController::class, 'leave_delete'])->name('api.leave.delete');

    Route::POST('/expense/add_edit', [ExpenseController::class, 'expense_add_edit'])->name('api.expense.add');
    Route::POST('/expense/list', [ExpenseController::class, 'expense_list'])->name('api.expense.list');
    Route::POST('/expense/delete', [ExpenseController::class, 'expense_delete'])->name('api.expense.delete');
    Route::POST('/expense/chart', [ExpenseController::class, 'expense_chart'])->name('api.expense.chart');

    /** Inquiry Module */
    Route::POST('/inquiry/add_edit', [InquiryController::class, 'inquiry_add_edit'])->name('api.inquiry.add_edit');
    Route::POST('/inquiry/list', [InquiryController::class, 'inquiry_list'])->name('api.inquiry.list');
    Route::POST('/inquiry/delete', [InquiryController::class, 'inquiry_delete'])->name('api.inquiry.delete');

    /** Customer  */
    Route::POST('/customer/add_edit', [CustomerController::class, 'customer_add_edit'])->name('api.customer.add_edit');
    Route::POST('/customer/list', [CustomerController::class, 'customer_list'])->name('api.customer.list');
    Route::POST('/customer/delete', [CustomerController::class, 'customer_delete'])->name('api.customer.delete');


    /** Followup Module */
    Route::POST('/follow-up/add_edit', [ApiFollowupController::class, 'followup_add_edit'])->name('api.followup.add_edit');
    Route::POST('/follow-up/list', [ApiFollowupController::class, 'followup_list'])->name('api.followup.list');
    Route::POST('/follow-up/response-add', [ApiFollowupController::class, 'followup_response_add'])->name('api.followup.response_add');


    /** Get Product Tax */
    Route::POST('/tax/add_edit', [ApiTaxController::class, 'tax_add_edit'])->name('api.tax.add_edit');
    Route::POST('/tax/list', [ApiTaxController::class, 'tax_list'])->name('api.tax.list');
    Route::POST('/tax/delete', [ApiTaxController::class, 'tax_delete'])->name('api.tax.delete');

    /** Get Product Unit */
    Route::POST('/master-unit/add_edit', [ApiMasterUnitController::class, 'master_unit_add_edit'])->name('api.master-unit.add_edit');
    Route::POST('/master-unit/list', [ApiMasterUnitController::class, 'master_unit_list'])->name('api.master-unit.list');
    Route::POST('/master-unit/delete', [ApiMasterUnitController::class, 'master_unit_delete'])->name('api.master-unit.delete');

    /** Order Unit */
    Route::POST('/order-unit/add_edit', [ApiOrderUnitController::class, 'order_unit_add_edit'])->name('api.order-unit.add_edit');
    Route::POST('/order-unit/list', [ApiOrderUnitController::class, 'order_unit_list'])->name('api.order-unit.list');
    Route::POST('/order-unit/delete', [ApiOrderUnitController::class, 'order_unit_delete'])->name('api.order-unit.delete');

    /** product-category Unit */
    Route::POST('/product-category/list', [ApiProductCategoryController::class, 'product_category_list'])->name('api.product-category.list');
    Route::POST('/product-category/add_edit', [ApiProductCategoryController::class, 'product_category_add_edit'])->name('api.product-category.add_edit');
    Route::POST('/product-category/delete', [ApiProductCategoryController::class, 'product_category_delete'])->name('api.product-category.delete');

    /** product-sub-category Unit */
    Route::POST('/product-sub-category/list', [ApiProductSubCategoryController::class, 'product_sub_category_list'])->name('api.product-sub-category.list');
    Route::POST('/product-sub-category/add_edit', [ApiProductSubCategoryController::class, 'product_sub_category_add_edit'])->name('api.product-sub-category.add_edit');
    Route::POST('/product-sub-category/delete', [ApiProductSubCategoryController::class, 'product_sub_category_delete'])->name('api.product-sub-category.delete');

    /** product-variant Unit */
    Route::POST('/product-variant/list', [ApiProductVariantController::class, 'product_variant_list'])->name('api.product-variant.list');
    Route::POST('/product-variant/add_edit', [ApiProductVariantController::class, 'product_variant_add_edit'])->name('api.product-variant.add_edit');
    Route::POST('/product-variant/delete', [ApiProductVariantController::class, 'product_variant_delete'])->name('api.product-variant.delete');

    /** Product Module */
    Route::POST('/product/list', [ProductController::class, 'product_list'])->name('api.product.list');
    Route::POST('/product/add_edit', [ProductController::class, 'product_add_edit'])->name('api.product.add_edit');

    /** visit Unit */
    Route::POST('/visit/list', [ApiVisitController::class, 'visit_list'])->name('api.visit.list');
    Route::POST('/visit/start', [ApiVisitController::class, 'visit_start'])->name('api.visit.start');
    Route::POST('/visit/end', [ApiVisitController::class, 'visit_end'])->name('api.visit.end');
    Route::POST('/visit/delete', [ApiVisitController::class, 'visit_delete'])->name('api.visit.delete');


    /** expo-management Unit */
    Route::POST('/expo-management/list', [ApiExpoManagementController::class, 'expo_management_list'])->name('api.expo-management.list');
    Route::POST('/expo-management/add_edit', [ApiExpoManagementController::class, 'expo_management_add_edit'])->name('api.expo-management.add_edit');
    Route::POST('/expo-management/delete', [ApiExpoManagementController::class, 'expo_management_delete'])->name('api.expo-management.delete');

    /** Expo visitor Unit */
    Route::POST('/expo-visitor/list', [ApiExpoVisitorController::class, 'expo_visitor_list'])->name('api.expo-visitor.list');
    Route::POST('/expo-visitor/add_edit', [ApiExpoVisitorController::class, 'expo_visitor_add_edit'])->name('api.expo-visitor.add_edit');
    Route::POST('/expo-visitor/delete', [ApiExpoVisitorController::class, 'expo_visitor_delete'])->name('api.expo-visitor.delete');

    /** role-permission */
    Route::POST('/role-permission/list', [RolePermissionsController::class, 'module_list'])->name('api.role-permission.list');

    /** Quotation Module */
    Route::POST('/quotation/list', [QuotationController::class, 'quotation_list'])->name('api.quotation.list');
    Route::POST('/quotation/add_edit', [QuotationController::class, 'quotation_add_edit'])->name('api.quotation.add_edit');
    Route::POST('/quotation/delete', [QuotationController::class, 'quotation_delete'])->name('api.quotation.delete');
    Route::POST('/quotation/approve_status', [QuotationController::class, 'quotation_approve_status'])->name('api.quotation.approve_status');
    Route::POST('/quotation/quotation_order_generated', [QuotationController::class, 'quotation_order_generated'])->name('api.quotation.quotation_order_generated');
    Route::POST('/quotation/clone', [QuotationController::class, 'quotation_clone'])->name('api.quotation.clone');

    /** SalesOrder Module */
    Route::POST('/sales-order/list', [SalesOrderController::class, 'salesorder_list'])->name('api.sales-order.list');
    Route::POST('/sales-order/add_edit', [SalesOrderController::class, 'salesorder_add_edit'])->name('api.sales-order.add_edit');
    Route::POST('/sales-order/delete', [SalesOrderController::class, 'salesorder_delete'])->name('api.sales-order.delete');
    Route::POST('/sales-order/approve_status', [SalesOrderController::class, 'salesorder_approve_status'])->name('api.sales-order.approve_status');
    Route::POST('/sales-order/clone', [SalesOrderController::class, 'salesorder_clone'])->name('api.sales-order.clone');
});



/** webhook-inquiry add */
Route::POST('/webhook/inquiry_add', [WebhookInquiryController::class, 'inquiry_add'])->name('api.webhook-inquiry.inquiry_add');
