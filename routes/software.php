<?php

use App\Helpers\Helper;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\software\DepartmentController;
use App\Http\Controllers\software\AccountLeagerController;
use App\Http\Controllers\software\CompanyController;
use App\Http\Controllers\software\ComplainController;
use App\Http\Controllers\software\ComplainTypesController;
use App\Http\Controllers\software\DashboardController;
use App\Http\Controllers\software\ExpenseCategoryController;
use App\Http\Controllers\software\ExpenseController;
use App\Http\Controllers\software\ExpenseSubCategoryController;
use App\Http\Controllers\software\ExpoManagementController;
use App\Http\Controllers\software\ExpoMarketingSliderController;
use App\Http\Controllers\software\JobWorkIssueReportController;
use App\Http\Controllers\software\JobWorkPendingController;
use App\Http\Controllers\software\JobWorkReceiveReportController;
use App\Http\Controllers\software\MasterStateController;
use App\Http\Controllers\software\MasterCityController;
use App\Http\Controllers\software\MasterCountryController;
use App\Http\Controllers\software\MasterAreaController;
use App\Http\Controllers\software\PackingSlipController;
use App\Http\Controllers\software\PackingTypeController;
use App\Http\Controllers\software\PaymentTermController;
use App\Http\Controllers\software\ProductComboController;
use App\Http\Controllers\software\ProspectedCustomerController;
use App\Http\Controllers\software\SetPointToRupeesController;
use App\Http\Controllers\software\SoftwareAuthController;
use App\Http\Controllers\software\DatabaseImportController;
use App\Http\Controllers\software\LeaveTypeController;
use App\Http\Controllers\software\TeamPersonController;
use App\Http\Controllers\software\DesignationController;
use App\Http\Controllers\software\MarketingStatusController;

use App\Http\Controllers\software\ProductTAXController;
use App\Http\Controllers\software\ProductVariantController;
use App\Http\Controllers\software\MasterUnitController;
use App\Http\Controllers\software\MasterWarehouseController;
use App\Http\Controllers\software\MasterTransportByController;
use App\Http\Controllers\software\MasterTransportDetailController;
use App\Http\Controllers\software\LeaveApplicationController;
use App\Http\Controllers\software\PlanMasterController;
use App\Http\Controllers\software\ProductCategoryController;
use App\Http\Controllers\software\ProductGradeController;
use App\Http\Controllers\software\ProductGodownController;
use App\Http\Controllers\software\ProductRackController;
use App\Http\Controllers\software\ProductAlloyController;
use App\Http\Controllers\software\ProductSubcategoryController;
use App\Http\Controllers\software\InquiryController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\software\FollowupController;
use App\Http\Controllers\software\CustomerController;
use App\Http\Controllers\software\TermsAndConditionController;
use App\Http\Controllers\software\SourceOfInquiryController;
use App\Http\Controllers\software\FollowUpReasonController;
use App\Http\Controllers\software\DocumentListController;
use App\Http\Controllers\software\DocumentTypeController;
use App\Http\Controllers\software\ProductController;
use App\Http\Controllers\software\TeamAttendanceController;
use App\Http\Controllers\software\VisitController;
use App\Http\Controllers\software\ProductStockController;
use App\Http\Controllers\software\ProductStockHistoryController;
use App\Http\Controllers\software\CustomerTypeController;
use App\Http\Controllers\software\DispatchController;
use App\Http\Controllers\software\SalesOrderController;
use App\Http\Controllers\software\OrderunitsrTypeController;
use App\Http\Controllers\software\QuotationController;
use App\Http\Controllers\software\SalesStatusController;
use App\Http\Controllers\software\TeamRoleController;
use App\Http\Controllers\software\PriceListController;
use App\Http\Controllers\software\ManageEmailController;
use App\Http\Controllers\software\TrackingDashboardController;
use App\Http\Controllers\software\VendorController;
use App\Http\Controllers\software\PurchaseOrderController;
use App\Http\Controllers\software\JobWorkOrderController;
use App\Http\Controllers\software\JobWorkIssueController;
use App\Http\Controllers\software\JobWorkTypeController;
use App\Http\Controllers\software\InwardController;
use App\Http\Controllers\software\OutstandingReportController;
use App\Http\Controllers\software\PaymentReceiptController;
use App\Http\Controllers\software\CompanySubscriptionPlanController;
use App\Http\Controllers\software\CustomerReportController;
use App\Http\Controllers\software\ExpoCategoryController;
use App\Http\Controllers\software\ExpoVisitorController;
use App\Http\Controllers\software\FacebookController;
use App\Http\Controllers\software\NotificationController;
use App\Http\Controllers\software\ProductSalesReportController;
use App\Http\Controllers\software\PurposeVisitMasterController;
use App\Http\Controllers\software\QRLinkGenerateController;
use App\Http\Controllers\software\QuickQRGeneratorController;
use App\Http\Controllers\software\UserController;
use App\Http\Controllers\software\SalesOrderReportController;
use App\Http\Controllers\software\SetPointToRupees;
use App\Http\Controllers\software\WithdrawRequestController;
use App\Http\Controllers\software\TaskController;
use App\Http\Controllers\software\TaskStatusController;
use App\Http\Controllers\software\VisitorGatepassController;
use App\Http\Controllers\software\VisitorTypeController;
use App\Http\Controllers\software\ProductBOMController;
use App\Http\Controllers\software\ProductCustomerItemController;
use App\Http\Controllers\software\ProductSupplierDetailController;
use App\Http\Controllers\software\ProductImageController;
use App\Http\Controllers\software\ProductRmDetailController;
use App\Http\Controllers\software\IssueForProcessController;
use App\Http\Controllers\software\LastProcessController;
use App\Http\Controllers\software\LocationController;
use App\Http\Controllers\software\ProcessReceivedController;
use App\Http\Controllers\software\JobWorkReceiveController;
use App\Http\Controllers\software\ProductHSNController;
use App\Http\Controllers\software\QCInspectionReportController;
use App\Http\Controllers\software\InvoiceController;
use App\Http\Controllers\software\GRNController;
use App\Http\Controllers\software\WhatsAppController;
use App\Http\Middleware\SoftwareAuthMiddleware;
use Illuminate\Support\Facades\Route;
// Route::prefix('/software')->group(function () {
// Route::get('/', [DashboardController::class, 'dashboard'])->name('software.landing');

/** Guest routes */
Route::GET('/login', [SoftwareAuthController::class, 'showLoginForm'])->name('software.login');
Route::POST('/login-submit', [SoftwareAuthController::class, 'submitLoginForm'])->name('software.submit.login');
Route::match(['get', 'post'], '/logout', [SoftwareAuthController::class, 'logout'])->name('software.logout');
Route::get('/ip-test', [SoftwareAuthController::class, 'softwareIpTest'])->name('software.ip.test');

// Database Import Guest Routes
Route::GET('/db-import', [DatabaseImportController::class, 'showImportForm'])->name('software.db-import');
Route::POST('/db-import/verify-password', [DatabaseImportController::class, 'verifyPassword'])->name('software.db-import.verify-password');
Route::GET('/db-import/backup', [DatabaseImportController::class, 'downloadBackup'])->name('software.db-import.backup');
Route::POST('/db-import/upload', [DatabaseImportController::class, 'uploadSql'])->name('software.db-import.upload');
Route::POST('/db-import/execute-chunk', [DatabaseImportController::class, 'executeChunk'])->name('software.db-import.execute-chunk');
Route::POST('/db-import/execute-single', [DatabaseImportController::class, 'executeSingle'])->name('software.db-import.execute-single');

// IP Verification Routes
// Route::get('/verification-pending', [SoftwareAuthController::class, 'verificationPending'])->name('software.verification.pending');
// Route::get('/verify-ip/{token}', [SoftwareAuthController::class, 'verifyIp'])->name('software.verify.ip');

// Forgot Password
Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('software.forgot.password');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('software.forgot.password.submit');
Route::get('reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
// Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
// Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.update');
Route::post('/reset-password-direct', [ForgotPasswordController::class, 'resetPasswordDirect'])->name('software.reset.password.direct');


/** Protected routes */
Route::group(['middleware' => [SoftwareAuthMiddleware::class]], function () {

    // Dashboard Route
    Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart-data');

    Route::get('/dashboard/customer-locations', [DashboardController::class, 'getLocations'])->name('dashboard.getLocations');
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('software.dashboard');
    Route::get('/dashboard/customer', [DashboardController::class, 'dashboard_customer'])->name('software.dashboard_customer');
    Route::get('/dashboard/order-stock', [DashboardController::class, 'order_stock'])->name('dashboard.order_stock');

    Route::get('/tracking-dashboard', [TrackingDashboardController::class, 'tracking_dashboard'])->name('tracking-dashboard.index');
    Route::post('/tracking-dashboard/get-team-person-details', [TrackingDashboardController::class, 'get_team_person_details'])->name('tracking-dashboard.get-team-person-details');

    Route::match(['get', 'post'], '/live-tracking-dashboard', [TrackingDashboardController::class, 'live_tracking_dashboard'])->name('live-tracking-dashboard');
    // Route::POST('/live-tracking-data', [TrackingDashboardController::class, 'live_tracking_data'])->name('live-tracking-data');

    Route::get('/reports', [DashboardController::class, 'reports'])->name('reports.index');

    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
    Route::post('/dashboard/update-profile', [DashboardController::class, 'updateProfile'])->name('dashboard.updateProfile');
    Route::post('/dashboard/update-profile-image', [DashboardController::class, 'updateProfileImage'])->name('dashboard.updateProfileImage');

    Route::get('/dashboard/profile', [DashboardController::class, 'showProfile'])->name('dashboard.showProfile');


    // Vendor
    Route::resource('vendors', VendorController::class);
    Route::match(['get', 'post'], 'vendors/restore/{id}', [VendorController::class, 'restore'])->name('vendors.restore');
    Route::match(['get', 'post'], 'vendors-update-status', [VendorController::class, 'status_update'])->name('vendors.status-update');
    Route::get('vendors/export/excel', [VendorController::class, 'exportExcel'])->name('vendors.export.excel');
    Route::get('vendors/export/print', [VendorController::class, 'print'])->name('vendors.print');


    // Purchase Order
    Route::resource('purchase-order', PurchaseOrderController::class);
    Route::match(['get', 'post'], 'purchase-order/purchaseOrderShow/{id}', [PurchaseOrderController::class, 'purchaseOrderShow'])->name('purchase-order.purchaseOrderShow');

    Route::match(['get', 'post'], 'purchase-order/purchaseOrderPrint/{id}', [PurchaseOrderController::class, 'purchaseOrderPrint'])->name('purchase-order.purchaseOrderPrint');
    Route::match(['get', 'post'], 'purchase-order/sendPurchaseOrderPdf/{id}', [PurchaseOrderController::class, 'sendPurchaseOrderPdf'])->name('purchase-order.sendPurchaseOrderPdf');

    Route::match(['get', 'post'], 'purchase-order/restore/{id}', [PurchaseOrderController::class, 'restore'])->name('purchase-order.restore');
    Route::match(['get', 'post'], 'purchase-order-update-status', [PurchaseOrderController::class, 'status_update'])->name('purchase-order.status-update');
    Route::match(['get', 'post'], 'purchase-order-change-status', [PurchaseOrderController::class, 'change_purchase_order_status'])->name('purchase-order.change-purchase-order-status');
    Route::get('purchase-order/export/excel', [PurchaseOrderController::class, 'exportExcel'])->name('purchase-order.export.excel');
    Route::delete('purchase-order/{id}/delete', [PurchaseOrderController::class, 'destroy'])->name('purchase-order.destroy');
    Route::get('purchase-order/export/print', [PurchaseOrderController::class, 'print'])->name('purchase-order.print');

    // Process Wise Production
    Route::post('process-wise-production/change-status', [App\Http\Controllers\software\ProcessWiseProductionController::class, 'changeStatus'])->name('process-wise-production.change-status');
    Route::resource('process-wise-production', App\Http\Controllers\software\ProcessWiseProductionController::class);



    // GRN
    Route::resource('grn', GRNController::class);
    Route::match(['get', 'post'], 'grn/restore/{id}', [GRNController::class, 'restore'])->name('grn.restore');
    Route::match(['get', 'post'], 'grn-update-status', [GRNController::class, 'status_update'])->name('grn.status-update');
    Route::match(['get', 'post'], 'grn-change-status', [GRNController::class, 'change_grn_status'])->name('grn.change-grn-status');
    Route::get('grn/export/excel', [GRNController::class, 'exportExcel'])->name('grn.export.excel');
    Route::get('grn/export/print', [GRNController::class, 'print'])->name('grn.print');

    //Job Work Order
    Route::resource('job-work-order', JobWorkOrderController::class);
    Route::get('job-work-order/export/excel', [JobWorkOrderController::class, 'exportExcel'])->name('job-work-order.export.excel');
    Route::get('job-work-order/export/print', [JobWorkOrderController::class, 'print'])->name('job-work-order.print');
    Route::match(['get', 'post'], 'job-work-order/restore/{id}', [JobWorkOrderController::class, 'restore'])->name('job-work-order.restore');
    Route::match(['get', 'post'], 'job-work-order/jobOrderShow/{id}', [JobWorkOrderController::class, 'jobOrderShow'])->name('job-work-order.jobOrderShow');
    Route::match(['get', 'post'], 'job-work-order/jobOrderPrint/{id}', [JobWorkOrderController::class, 'jobOrderPrint'])->name('job-work-order.jobOrderPrint');
    Route::match(['get', 'post'], 'job-work-order/jobOrderPdf/{id}', [JobWorkOrderController::class, 'jobOrderPdf'])->name('job-work-order.jobOrderPdf');
    Route::match(['get', 'post'], 'job-work-order/SendjobOrderPdf/{id}', [JobWorkOrderController::class, 'SendjobOrderPdf'])->name('job-work-order.SendjobOrderPdf');

    //Job Work Issue
    Route::resource('job-work-issue', JobWorkIssueController::class);
    Route::match(['get', 'post'], 'job-work-issue/ewayBillPrint/{id}', [JobWorkIssueController::class, 'ewayBillPrint'])->name('job-work-issue.ewayBillPrint');
    Route::post('job-work-issue/update-vehicle-details', [JobWorkIssueController::class, 'updateVehicle'])->name('job-work-issue.update-vehicle-details');
    Route::get('job-work-issue/vehicle-history-details/{id}', [JobWorkIssueController::class, 'vehicleHistory'])->name('job-work-issue.vehicle-history-details');
    Route::get('job-work-issue-gst-report/export/excel', [App\Http\Controllers\software\JobWorkIssueGstReportController::class, 'exportExcel'])->name('job-work-issue-gst-report.export.excel');
    Route::resource('job-work-issue-gst-report', App\Http\Controllers\software\JobWorkIssueGstReportController::class);
    Route::get('job-work-receive-gst-report/export/excel', [App\Http\Controllers\software\JobWorkReceiveGstReportController::class, 'exportExcel'])->name('job-work-receive-gst-report.export.excel');
    Route::resource('job-work-receive-gst-report', App\Http\Controllers\software\JobWorkReceiveGstReportController::class);

    Route::get('job-work-receive-other-gst-report/export/excel', [App\Http\Controllers\software\JobWorkReceiveOtherGstReportController::class, 'exportExcel'])->name('job-work-receive-other-gst-report.export.excel');
    Route::resource('job-work-receive-other-gst-report', App\Http\Controllers\software\JobWorkReceiveOtherGstReportController::class)->parameters(['job-work-receive-other-gst-report' => 'report']);

    Route::get('job-work-supplied-gst-report/export/excel', [App\Http\Controllers\software\JobWorkSuppliedGstReportController::class, 'exportExcel'])->name('job-work-supplied-gst-report.export.excel');
    Route::resource('job-work-supplied-gst-report', App\Http\Controllers\software\JobWorkSuppliedGstReportController::class)->parameters(['job-work-supplied-gst-report' => 'report']);




    Route::get('job-work-issue/export/excel', [JobWorkIssueController::class, 'exportExcel'])->name('job-work-issue.export.excel');
    Route::get('job-work-issue/export/print', [JobWorkIssueController::class, 'print'])->name('job-work-issue.print');
    Route::match(['get', 'post'], 'job-work-issue/restore/{id}', [JobWorkIssueController::class, 'restore'])->name('job-work-issue.restore');
    Route::match(['get', 'post'], 'job-work-issue/jobWorkIssueShow/{id}', [JobWorkIssueController::class, 'jobWorkIssueShow'])->name('job-work-issue.jobWorkIssueShow');
    Route::match(['get', 'post'], 'job-work-issue/jobWorkIssuePrint/{id}', [JobWorkIssueController::class, 'jobWorkIssuePrint'])->name('job-work-issue.jobWorkIssuePrint');
    Route::match(['get', 'post'], 'job-work-issue/SendjobWorkIssuePdf/{id}', [JobWorkIssueController::class, 'SendjobWorkIssuePdf'])->name('job-work-issue.SendjobWorkIssuePdf');
    Route::post('job-work-issue/bulk-download', [JobWorkIssueController::class, 'bulkDownload'])->name('job-work-issue.bulk-download');
    //Job work Type
    Route::resource('job-work-type', JobWorkTypeController::class);
    Route::match(['get', 'post'], 'job-work-type/restore/{id}', [JobWorkTypeController::class, 'restore'])->name('job-work-type.restore');
    Route::match(['get', 'post'], 'job-work-type-update-status', [JobWorkTypeController::class, 'status_update'])->name('job-work-type.status-update');
    Route::get('job-work-type/export/excel', [JobWorkTypeController::class, 'exportExcel'])->name('job-work-type.export.excel');
    Route::get('job-work-type/export/print', [JobWorkTypeController::class, 'print'])->name('job-work-type.print');

    //Issue For Process
    Route::resource('issue-for-process', IssueForProcessController::class);
    Route::match(['get', 'post'], 'issue-for-process/restore/{id}', [IssueForProcessController::class, 'restore'])->name('issue-for-process.restore');
    Route::match(['get', 'post'], 'issue-for-process-update-status', [IssueForProcessController::class, 'status_update'])->name('issue-for-process.status-update');
    Route::get('issue-for-process/export/excel', [IssueForProcessController::class, 'exportExcel'])->name('issue-for-process.export.excel');
    Route::get('issue-for-process/export/print', [IssueForProcessController::class, 'print'])->name('issue-for-process.print');

    //Last Process
    Route::resource('last-process', LastProcessController::class);
    Route::match(['get', 'post'], 'last-process/restore/{id}', [LastProcessController::class, 'restore'])->name('last-process.restore');
    Route::match(['get', 'post'], 'last-process-update-status', [LastProcessController::class, 'status_update'])->name('last-process.status-update');
    Route::get('last-process/export/excel', [LastProcessController::class, 'exportExcel'])->name('last-process.export.excel');
    Route::get('last-process/export/print', [LastProcessController::class, 'print'])->name('last-process.print');

    //Location
    Route::resource('location', LocationController::class);
    Route::match(['get', 'post'], 'location/restore/{id}', [LocationController::class, 'restore'])->name('location.restore');
    Route::match(['get', 'post'], 'location-update-status', [LocationController::class, 'status_update'])->name('location.status-update');
    Route::get('location/export/excel', [LocationController::class, 'exportExcel'])->name('location.export.excel');
    Route::get('location/export/print', [LocationController::class, 'print'])->name('location.print');

    //Process Received
    Route::resource('process-received', ProcessReceivedController::class);
    Route::match(['get', 'post'], 'process-received/restore/{id}', [ProcessReceivedController::class, 'restore'])->name('process-received.restore');
    Route::match(['get', 'post'], 'process-received-update-status', [ProcessReceivedController::class, 'status_update'])->name('process-received.status-update');
    Route::get('process-received/export/excel', [ProcessReceivedController::class, 'exportExcel'])->name('process-received.export.excel');
    Route::get('process-received/export/print', [ProcessReceivedController::class, 'print'])->name('process-received.print');

    //Job Work Receive
    Route::resource('job-work-receive', JobWorkReceiveController::class);
    Route::match(['get', 'post'], 'job-work-receive/restore/{id}', [JobWorkReceiveController::class, 'restore'])->name('job-work-receive.restore');
    Route::get('job-work-receive/export/excel', [JobWorkReceiveController::class, 'exportExcel'])->name('job-work-receive.export.excel');
    Route::get('job-work-receive/export/print', [JobWorkReceiveController::class, 'print'])->name('job-work-receive.print');
    Route::match(['get', 'post'], 'job-work-receive/jobWorkReceiveShow/{id}', [JobWorkReceiveController::class, 'jobWorkReceiveShow'])->name('job-work-receive.jobWorkReceiveShow');
    Route::match(['get', 'post'], 'job-work-receive/jobWorkReceivePrint/{id}', [JobWorkReceiveController::class, 'jobWorkReceivePrint'])->name('job-work-receive.jobWorkReceivePrint');
    Route::match(['get', 'post'], 'job-work-receive/SendjobWorkReceivePdf/{id}', [JobWorkReceiveController::class, 'SendjobWorkReceivePdf'])->name('job-work-receive.SendjobWorkReceivePdf');
    Route::post('job-work-receive/bulk-download', [JobWorkReceiveController::class, 'bulkDownload'])->name('job-work-receive.bulk-download');

    //QC Inspection Report
    Route::resource('qc-inspection-report', QCInspectionReportController::class);
    Route::match(['get', 'post'], 'qc-inspection-report/restore/{id}', [QCInspectionReportController::class, 'restore'])->name('qc-inspection-report.restore');
    Route::match(['get', 'post'], 'qc-inspection-report-update-status', [QCInspectionReportController::class, 'status_update'])->name('qc-inspection-report.status-update');
    Route::match(['get', 'post'], 'qc-inspection-report/QCInspectionShow/{id}', [QCInspectionReportController::class, 'QCInspectionShow'])->name('qc-inspection-report.QCInspectionShow');
    Route::get('qc-inspection-report/export/excel', [QCInspectionReportController::class, 'exportExcel'])->name('qc-inspection-report.export.excel');
    Route::get('qc-inspection-report/export/print', [QCInspectionReportController::class, 'print'])->name('qc-inspection-report.print');
    Route::match(['get', 'post'], 'qc-inspection-report/QcInspectionPrint/{id}', [QCInspectionReportController::class, 'QcInspectionPrint'])->name('qc-inspection-report.QcInspectionPrint');
    Route::match(['get', 'post'], 'qc-inspection-report/QcInspectionPdf/{id}', [QCInspectionReportController::class, 'QcInspectionPdf'])->name('qc-inspection-report.QcInspectionPdf');

    // Inward
    Route::resource('inwards', InwardController::class);
    Route::match(['get', 'post'], 'inwards/inwardShow/{id}', [InwardController::class, 'inwardShow'])->name('inwards.inwardShow');
    Route::match(['get', 'post'], 'inwards/inwardPrint/{id}', [InwardController::class, 'inwardPrint'])->name('inwards.inwardPrint');
    Route::match(['get', 'post'], 'inwards/sendInwardPdf/{id}', [InwardController::class, 'sendInwardPdf'])->name('inwards.sendInwardPdf');
    Route::match(['get', 'post'], 'inwards/insert/{id}', [InwardController::class, 'insert'])->name('inwards.insert');
    Route::match(['get', 'post'], 'inwards/restore/{id}', [InwardController::class, 'restore'])->name('inwards.restore');
    Route::match(['get', 'post'], 'inwards-update-status', [InwardController::class, 'status_update'])->name('inwards.status-update');
    Route::match(['get', 'post'], 'inwards/get-purchase-order-items', [InwardController::class, 'getPurchaseOrderItems'])->name('inwards.get-purchase-order-items');
    Route::get('inwards/export/excel', [InwardController::class, 'exportExcel'])->name('inwards.export.excel');
    Route::get('inwards/export/print', [InwardController::class, 'print'])->name('inwards.print');


    // Price List
    Route::resource('price-list', PriceListController::class);
    Route::match(['get', 'post'], 'price-list/restore/{id}', [PriceListController::class, 'restore'])->name('price-list.restore');
    Route::match(['get', 'post'], 'price-list-update-status', [PriceListController::class, 'status_update'])->name('price-list.status-update');
    Route::match(['get', 'post'], 'price-list/save-pricelist-products', [PriceListController::class, 'save_pricelist_products'])->name('price-list.save-pricelist-products');
    Route::match(['get', 'post'], 'price-list/manage-products/{id}', [PriceListController::class, 'manage_products'])->name('price-list.manage-products');
    Route::match(['get', 'post'], 'price-list/get-products', [PriceListController::class, 'get_products'])->name('price-list.get-products');
    Route::get('price-list/export/excel', [PriceListController::class, 'exportExcel'])->name('price-list.export.excel');
    Route::get('price-list/export/print', [PriceListController::class, 'print'])->name('price-list.print');



    // Region
    Route::resource('master-country', MasterCountryController::class);
    Route::match(['get', 'post'], 'master-country/restore/{id}', [MasterCountryController::class, 'restore'])->name('master-country.restore');
    Route::match(['get', 'post'], 'master-country-update-status', [MasterCountryController::class, 'status_update'])->name('master-country.status-update');
    Route::get('master-country/export/excel', [MasterCountryController::class, 'exportExcel'])->name('master-country.export.excel');
    Route::get('master-country/export/print', [MasterCountryController::class, 'print'])->name('master-country.print');


    // State
    Route::resource('master-state', MasterStateController::class);
    Route::match(['get', 'post'], 'master-state/restore/{id}', [MasterStateController::class, 'restore'])->name('master-state.restore');
    Route::match(['get', 'post'], 'master-state-update-status', [MasterStateController::class, 'status_update'])->name('master-state.status-update');
    Route::get('master-state/export/excel', [MasterStateController::class, 'exportExcel'])->name('master-state.export.excel');
    Route::get('master-state/export/print', [MasterStateController::class, 'print'])->name('master-state.print');




    //City
    Route::resource('master-city', MasterCityController::class);
    Route::match(['get', 'post'], 'master-city/restore/{id}', [MasterCityController::class, 'restore'])->name('master-city.restore');
    Route::match(['get', 'post'], 'master-city-update-status', [MasterCityController::class, 'status_update'])->name('master-city.status-update');
    Route::get('master-city/export/excel', [MasterCityController::class, 'exportExcel'])->name('master-city.export.excel');
    Route::get('master-city/export/print', [MasterCityController::class, 'print'])->name('master-city.print');



    //Area
    Route::resource('master-area', MasterAreaController::class);
    Route::match(['get', 'post'], 'master-area/restore/{id}', [MasterAreaController::class, 'restore'])->name('master-area.restore');
    Route::match(['get', 'post'], 'master-area-update-status', [MasterAreaController::class, 'status_update'])->name('master-area.status-update');
    Route::get('master-area/export/excel', [MasterAreaController::class, 'exportExcel'])->name('master-area.export.excel');
    Route::get('master-area/export/print', [MasterAreaController::class, 'print'])->name('master-area.print');



    // Expense Category
    Route::resource('expense-category', ExpenseCategoryController::class);
    Route::match(['get', 'post'], 'expense-category/restore/{id}', [ExpenseCategoryController::class, 'restore'])->name('expense-category.restore');
    Route::match(['get', 'post'], 'expense-category-update-status', [ExpenseCategoryController::class, 'status_update'])->name('expense-category.status-update');
    Route::get('expense-category/export/excel', [ExpenseCategoryController::class, 'exportExcel'])->name('expense-category.export.excel');
    Route::get('expense-category/export/print', [ExpenseCategoryController::class, 'print'])->name('expense-category.print');


    // Expense Sub Category
    Route::resource('expense-subcategory', ExpenseSubCategoryController::class);
    Route::match(['get', 'post'], 'expense-subcategory/restore/{id}', [ExpenseSubCategoryController::class, 'restore'])->name('expense-subcategory.restore');
    Route::match(['get', 'post'], 'expense-subcategory-update-status', [ExpenseSubCategoryController::class, 'status_update'])->name('expense-subcategory.status-update');
    Route::get('expense-subcategory/export/excel', [ExpenseSubCategoryController::class, 'exportExcel'])->name('expense-subcategory.export.excel');
    Route::get('expense-subcategory/export/print', [ExpenseSubCategoryController::class, 'print'])->name('expense-subcategory.print');

    // Expense
    Route::resource('expense', ExpenseController::class);
    Route::match(['get', 'post'], 'expense/restore/{id}', [ExpenseController::class, 'restore'])->name('expense.restore');
    Route::match(['get', 'post'], 'expense-update-status', [ExpenseController::class, 'status_update'])->name('expense.status-update');
    Route::get('expense/export/excel', [ExpenseController::class, 'exportExcel'])->name('expense.export.excel');
    Route::get('expense/export/print', [ExpenseController::class, 'print'])->name('expense.print');



    Route::resource('leavetype', LeaveTypeController::class);
    Route::match(['get', 'post'], 'leavetype/restore/{id}', [LeaveTypeController::class, 'restore'])->name('leavetype.restore');
    Route::match(['get', 'post'], 'leavetype-update-status', [LeaveTypeController::class, 'status_update'])->name('leavetype.status-update');
    Route::get('leavetype/export/excel', [LeaveTypeController::class, 'exportExcel'])->name('leavetype.export.excel');
    Route::get('leavetype/export/print', [LeaveTypeController::class, 'print'])->name('leavetype.print');


    Route::resource('leave-application', LeaveApplicationController::class);
    Route::post('/submit-leave-request', [LeaveApplicationController::class, 'submitLeaveRequest'])->name("submit-leavel-application");
    Route::match(['get', 'post'], 'leave-application/restore/{id}', [LeaveApplicationController::class, 'restore'])->name('leave-application.restore');
    Route::match(['get', 'post'], 'leave-application-update-status', [LeaveApplicationController::class, 'status_update'])->name('leave-application.status-update');
    Route::get('leave-application/export/excel', [LeaveApplicationController::class, 'exportExcel'])->name('leave-application.export.excel');
    Route::get('leave-application/export/print', [LeaveApplicationController::class, 'print'])->name('leave-application.print');




    Route::get('team-person/import', [TeamPersonController::class, 'import'])->name('import-team-person.index');
    Route::post('team-person/import', [TeamPersonController::class, 'import_store'])->name('import-team-person.store');
    Route::resource('team-person', TeamPersonController::class);
    Route::match(['get', 'post'], 'team-person-update-status', [TeamPersonController::class, 'status_update'])->name('team-person.status-update');
    Route::get('team-person/export/excel', [TeamPersonController::class, 'exportExcel'])->name('team-person.export.excel');
    Route::get('team-person/export/print', [TeamPersonController::class, 'print'])->name('team-person.print');
    Route::get('team-person/assign-area/{id}', [TeamPersonController::class, 'assignArea'])->name('team-person.assign.area');
    Route::post('team-person/assign-area/{id}', [TeamPersonController::class, 'assignArea'])->name('team-person.assign.area.store');
    Route::get('team-person/assigned-areas-data/{id}', [TeamPersonController::class, 'assignedAreasData'])->name('team-person.assigned.areas.data');
    Route::delete('team-person-assign-area/{id}', [TeamPersonController::class, 'destroyAssignedArea'])->name('team-person-assign-area.destroy');
    Route::match(['get'], 'team-person/assign-permission/{id}', [TeamPersonController::class, 'assign_permission'])->name('team-person.assign-permission');
    Route::match(['post'], 'team-person/store-permission', [TeamPersonController::class, 'store_permission'])->name('team-person.store-permission');

    //Expo Category

    Route::resource('expo-category', ExpoCategoryController::class);
    Route::match(['get', 'post'], 'expo-category/restore/{id}', [ExpoCategoryController::class, 'restore'])->name('expo-category.restore');
    Route::match(['get', 'post'], 'expo-category-update-status', [ExpoCategoryController::class, 'status_update'])->name('expo-category.status-update');
    Route::get('expo-category/export/excel', [ExpoCategoryController::class, 'exportExcel'])->name('expo-category.export.excel');
    Route::get('expo-category/export/print', [ExpoCategoryController::class, 'print'])->name('expo-category.print');

    //Expo Management
    Route::resource('expo-management', ExpoManagementController::class);
    Route::match(['get', 'post'], 'expo-management/restore/{id}', [ExpoManagementController::class, 'restore'])->name('expo-management.restore');
    Route::match(['get', 'post'], 'expo-management-update-status', [ExpoManagementController::class, 'status_update'])->name('expo-management.status-update');
    Route::get('expo-management/export/excel', [ExpoManagementController::class, 'exportExcel'])->name('expo-management.export.excel');
    Route::get('expo-management/export/print', [ExpoManagementController::class, 'print'])->name('expo-management.print');

    //Expo Visitor

    Route::resource('expo-visitor', ExpoVisitorController::class);
    Route::match(['get', 'post'], 'expo-visitor/restore/{id}', [ExpoVisitorController::class, 'restore'])->name('expo-visitor.restore');
    Route::match(['get', 'post'], 'expo-visitor-update-status', [ExpoVisitorController::class, 'status_update'])->name('expo-visitor.status-update');
    Route::match(['get', 'post'], 'convert-to-inquiry/{id}', [ExpoVisitorController::class, 'convert_to_inquiry'])->name('convert-to-inquiry');
    Route::get('expo-visitor/export/excel', [ExpoVisitorController::class, 'exportExcel'])->name('expo-visitor.export.excel');
    Route::get('expo-visitor/export/print', [ExpoVisitorController::class, 'print'])->name('expo-visitor.print');

    // team-attendance
    Route::resource('team-attendance', TeamAttendanceController::class);
    Route::match(['get', 'post'], 'visit/restore/{id}', [TeamAttendanceController::class, 'restore'])->name('visit.restore');
    Route::match(['get', 'post'], 'visit-update-status', [TeamAttendanceController::class, 'status_update'])->name('visit.status-update');

    Route::get('team-attendance/export/excel', [TeamAttendanceController::class, 'exportExcel'])->name('team-attendance.export.excel');
    Route::get('team-attendance/export/print', [TeamAttendanceController::class, 'print'])->name('team-attendance.print');




    Route::resource('designation', DesignationController::class);
    Route::match(['get', 'post'], 'designation/restore/{id}', [DesignationController::class, 'restore'])->name('designation.restore');
    Route::match(['get', 'post'], 'designation-update-status', [DesignationController::class, 'status_update'])->name('designation.status-update');
    Route::get('designation/export/excel', [DesignationController::class, 'exportExcel'])->name('designation.export.excel');
    Route::get('designation/export/print', [DesignationController::class, 'print'])->name('designation.print');


    Route::resource('marketing-status', MarketingStatusController::class);
    Route::match(['get', 'post'], 'marketing-status/restore/{id}', [MarketingStatusController::class, 'restore'])->name('marketing-status.restore');
    Route::match(['get', 'post'], 'marketing-status-update-status', [MarketingStatusController::class, 'status_update'])->name('marketing-status.status-update');
    Route::get('marketing-status/export/excel', [MarketingStatusController::class, 'exportExcel'])->name('marketing-status.export.excel');
    Route::get('marketing-status/export/print', [MarketingStatusController::class, 'print'])->name('marketing-status.print');
    Route::post('marketing-status/update-displayorder', [MarketingStatusController::class, 'updateDisplayOrder'])->name('marketing-status.update-displayorder');


    Route::get('inquiry/get-duplicate-inquiries', [InquiryController::class, 'getDuplicateInquiries'])->name('inquiry.get-duplicate-inquiries');
    Route::get('inquiry/import', [InquiryController::class, 'import'])->name('import-inquiry.index');
    Route::post('inquiry/import', [InquiryController::class, 'import_store'])->name('import-inquiry.store');
    Route::resource('inquiry', InquiryController::class);
    Route::match(['get', 'post'], 'inquiry/restore/{id}', [InquiryController::class, 'restore'])->name('inquiry.restore');
    Route::match(['get', 'post'], 'inquiry-update-status', [InquiryController::class, 'status_update'])->name('inquiry.status-update');
    Route::match(['get', 'post'], 'inquiry-convert-to-customer/{id}', [InquiryController::class, 'convert_to_customer'])->name('inquiry.convert-to-customer');
    Route::match(['get', 'post'], 'inquiry-to-quatation/{id}', [InquiryController::class, 'inquiry_to_quatation'])->name('inquiry.inquiry-to-quatation');
    Route::get('inquiry/{id}/whatsapp-general-preview', [InquiryController::class, 'getWhatsAppGeneralPreview'])->name('inquiry.whatsapp-general-preview');
    Route::post('inquiry/{id}/send-whatsapp-general', [InquiryController::class, 'sendWhatsAppGeneral'])->name('inquiry.send-whatsapp-general');

    Route::post('inquiry/assign-inquiry', [InquiryController::class, 'assignToTeam'])->name('inquiry.assign-inquiry');

    Route::post('inquiry-assign-to-team', [InquiryController::class, 'assign_to_team'])->name('inquiry.assign-to-team');
    Route::get('inquiry/export/excel', [InquiryController::class, 'exportExcel'])->name('inquiry.export.excel');
    Route::get('inquiry/export/print', [InquiryController::class, 'print'])->name('inquiry.print');
    Route::match(['get', 'post'], 'inquiry/bulk-delete', [InquiryController::class, 'bulkDelete'])->name('inquiry.bulk-delete');

    Route::resource('followup', FollowupController::class);
    Route::match(['get', 'post'], 'followup/restore/{id}', [FollowupController::class, 'restore'])->name('followup.restore');
    Route::match(['get', 'post'], 'followup/store_response', [FollowupController::class, 'store_response'])->name('followup.store_response');
    Route::match(['get', 'post'], 'followup/store_response_quotation', [FollowupController::class, 'store_response_quotation'])->name('followup.store_response_quotation');
    Route::match(['get', 'post'], 'followup/store_response_customer', [FollowupController::class, 'store_response_customer'])->name('followup.store_response_customer');
    Route::match(['get', 'post'], 'followup/update-status', [FollowupController::class, 'status_update'])->name('followup.status-update');
    Route::get('followup/export/excel', [FollowupController::class, 'exportExcel'])->name('followup.export.excel');
    Route::get('followup/export/print', [FollowupController::class, 'print'])->name('followup.print');
    Route::match(['get', 'post'], 'get-followup', [FollowupController::class, 'get_followup'])->name('get-followup');
    Route::match(['get', 'post'], 'get-followup-quotation', [FollowupController::class, 'get_followup_quotation'])->name('get-followup-quotation');
    Route::match(['get', 'post'], 'get-followup-customer', [FollowupController::class, 'get_followup_customer'])->name('get-followup-customer');
    Route::match(['get', 'post'], 'get-followup-by-inquiry', [FollowupController::class, 'get_followup_by_inquiry'])->name('get-followup-by-inquiry');
    Route::match(['get', 'post'], 'quotation-store', [FollowupController::class, 'quotation_store'])->name('quotation-store');
    Route::match(['get', 'post'], 'customer-store', [FollowupController::class, 'customer_store'])->name('customer-store');

    // marketing-source-of-inquiry
    Route::resource('marketing-source-of-inquiry', SourceOfInquiryController::class);
    Route::match(['get', 'post'], 'marketing-source-of-inquiry-update-status', [SourceOfInquiryController::class, 'status_update'])->name('marketing-source-of-inquiry.status-update');
    Route::match(['get', 'post'], 'marketing-source-of-inquiry/restore/{id}', [SourceOfInquiryController::class, 'restore'])->name('marketing-source-of-inquiry.restore');
    Route::get('marketing-source-of-inquiry/export/excel', [SourceOfInquiryController::class, 'exportExcel'])->name('marketing-source-of-inquiry.export.excel');
    Route::get('marketing-source-of-inquiry/export/print', [SourceOfInquiryController::class, 'print'])->name('marketing-source-of-inquiry.print');

    // marketing-follow-up-reason
    Route::resource('marketing-follow-up-reason', FollowUpReasonController::class);
    Route::match(['get', 'post'], 'marketing-follow-up-reason-update-status', [FollowUpReasonController::class, 'status_update'])->name('marketing-follow-up-reason.status-update');
    Route::match(['get', 'post'], 'marketing-follow-up-reason/restore/{id}', [FollowUpReasonController::class, 'restore'])->name('marketing-follow-up-reason.restore');
    Route::get('marketing-follow-up-reason/export/excel', [FollowUpReasonController::class, 'exportExcel'])->name('marketing-follow-up-reason.export.excel');
    Route::get('marketing-follow-up-reason/export/print', [FollowUpReasonController::class, 'print'])->name('marketing-follow-up-reason.print');

    //Product Category

    Route::resource('product-category', ProductCategoryController::class);
    Route::match(['get', 'post'], 'product-category/restore/{id}', [ProductCategoryController::class, 'restore'])->name('product-category.restore');
    Route::match(['get', 'post'], 'product-category-update-status', [ProductCategoryController::class, 'status_update'])->name('product-category.status-update');
    Route::get('product-category/export/excel', [ProductCategoryController::class, 'exportExcel'])->name('product-category.export.excel');
    Route::get('product-category/export/print', [ProductCategoryController::class, 'print'])->name('product-category.print');


    //product Subcategory
    Route::resource('product-subcategory', ProductSubcategoryController::class);
    Route::match(['get', 'post'], 'product-subcategory/restore/{id}', [ProductSubcategoryController::class, 'restore'])->name('product-subcategory.restore');
    Route::match(['get', 'post'], 'product-subcategory-update-status', [ProductSubcategoryController::class, 'status_update'])->name('product-subcategory.status-update');
    Route::get('product-subcategory/export/excel', [ProductSubcategoryController::class, 'exportExcel'])->name('product-subcategory.export.excel');
    Route::get('product-subcategory/export/print', [ProductSubcategoryController::class, 'print'])->name('product-subcategory.print');

    Route::resource('product-tax', ProductTAXController::class);
    Route::match(['get', 'post'], 'product-tax-update-status', [ProductTAXController::class, 'status_update'])->name('product-tax.status-update');
    Route::match(['get', 'post'], 'product-tax/restore/{id}', [ProductTAXController::class, 'restore'])->name('product-tax.restore');
    Route::get('product-tax/export/excel', [ProductTAXController::class, 'exportExcel'])->name('product-tax.export.excel');
    Route::get('product-tax/export/print', [ProductTAXController::class, 'print'])->name('product-tax.print');


    Route::resource('product-variant', ProductVariantController::class);
    Route::match(['get', 'post'], 'product-variant-update-status', [ProductVariantController::class, 'status_update'])->name('product-variant.status-update');
    Route::match(['get', 'post'], 'product-variant/restore/{id}', [ProductVariantController::class, 'restore'])->name('product-variant.restore');
    Route::get('product-variant/export/excel', [ProductVariantController::class, 'exportExcel'])->name('product-variant.export.excel');
    Route::get('product-variant/export/print', [ProductVariantController::class, 'print'])->name('product-variant.print');

    // Product Rate Sheet
    Route::get('products/rate-sheet', [ProductController::class, 'rate_sheet_index'])->name('products.rate-sheet.index');
    Route::post('products/rate-sheet/upload', [ProductController::class, 'rate_sheet_upload'])->name('products.rate-sheet.upload');
    Route::post('products/rate-sheet/sync', [ProductController::class, 'rate_sheet_sync'])->name('products.rate-sheet.sync');
    Route::post('products/rate-sheet/update-cell', [ProductController::class, 'rate_sheet_update_cell'])->name('products.rate-sheet.update-cell');
    Route::post('products/rate-sheet/update-scrap-rate', [ProductController::class, 'rate_sheet_update_global_scrap_rate'])->name('products.rate-sheet.update-scrap-rate');
    Route::get('products/rate-sheet/download', [ProductController::class, 'rate_sheet_download'])->name('products.rate-sheet.download');

    // Product
    Route::get('products/import', [ProductController::class, 'import'])->name('import-product.index');
    Route::post('products/import', [ProductController::class, 'import_store'])->name('import-product.store');
    Route::resource('products', ProductController::class);
    Route::match(['get', 'post'], 'products/restore/{id}', [ProductController::class, 'restore'])->name('products.restore');
    Route::match(['get', 'post'], 'products-update-status', [ProductController::class, 'status_update'])->name('products.status-update');
    Route::get('products/export/excel', [ProductController::class, 'exportExcel'])->name('products.export.excel');
    Route::get('products/export/print', [ProductController::class, 'print'])->name('products.print');
    Route::match(['get', 'post'], 'product/remove-product-variant', [ProductController::class, 'remove_product_variant'])->name('product.remove_product_variant');

    // Product BOM
    Route::resource('product-bom', ProductBOMController::class);
    Route::match(['get', 'post'], 'product-bom/restore/{id}', [ProductBOMController::class, 'restore'])->name('product-bom.restore');

    // Product CustomerItem
    Route::resource('product-customer-item', ProductCustomerItemController::class);
    Route::match(['get', 'post'], 'product-customer-item/restore/{id}', [ProductCustomerItemController::class, 'restore'])->name('product-customer-item.restore');

    // Product Supplier Detail
    Route::resource('product-supplier-detail', ProductSupplierDetailController::class);
    Route::match(['get', 'post'], 'product-supplier-detail/restore/{id}', [ProductSupplierDetailController::class, 'restore'])->name('product-supplier-detail.restore');

    // Product Image Detail
    Route::resource('product-image', ProductImageController::class);
    Route::match(['get', 'post'], 'product-image/restore/{id}', [ProductImageController::class, 'restore'])->name('product-image.restore');

    // Product RM Detail
    Route::resource('product-rm-detail', ProductRmDetailController::class);
    Route::match(['get', 'post'], 'product-rm-detail/restore/{id}', [ProductRmDetailController::class, 'restore'])->name('product-rm-detail.restore');

    // Product Combo
    Route::resource('product-combo', ProductComboController::class);
    Route::match(['get', 'post'], 'product-combo/restore/{id}', [ProductComboController::class, 'restore'])->name('product-combo.restore');
    Route::match(['get', 'post'], 'product-combo-update-status', [ProductComboController::class, 'status_update'])->name('product-combo.status-update');
    Route::get('product-combo/export/excel', [ProductComboController::class, 'exportExcel'])->name('product-combo.export.excel');
    Route::get('product-combo/export/print', [ProductComboController::class, 'print'])->name('product-combo.print');



    //Product Stock
    Route::get('product-stock/import', [ProductStockController::class, 'import'])->name('import-product-stock.index');
    Route::post('product-stock/import/store', [ProductStockController::class, 'import_store'])->name('import-product-stock.store');
    Route::get('product-stock/export/excel', [ProductStockController::class, 'exportExcel'])->name('product-stock.export.excel');
    Route::get('product-stock/export/print', [ProductStockController::class, 'print'])->name('product-stock.print');
    Route::resource('product-stock', ProductStockController::class);
    Route::match(['get', 'post'], 'product-stock/restore/{id}', [ProductStockController::class, 'restore'])->name('product-stock.restore');
    Route::match(['get', 'post'], 'product-stock-update-status', [ProductStockController::class, 'status_update'])->name('product-stock.status-update');
    Route::get('/product-stock/sample/download', [ProductStockController::class, 'sampleDownload'])->name('product-stock.sample-download');

    // Product Stock History
    Route::resource('product-stock-history', ProductStockHistoryController::class);
    Route::match(['get', 'post'], 'product-stock-history/restore/{id}', [ProductStockHistoryController::class, 'restore'])->name('product-stock-history.restore');
    Route::match(['get', 'post'], 'product-stock/add/{product_variant_price_id}/{warehouse_id}', [ProductStockHistoryController::class, 'add'])->name('product-stock.add');
    Route::match(['get', 'post'], 'product-stock-history-update-status', [ProductStockHistoryController::class, 'status_update'])->name('product-stock-history.status-update');
    Route::get('product-stock-history/export/excel', [ProductStockHistoryController::class, 'exportExcel'])->name('product-stock-history.export.excel');
    Route::get('product-stock-history/export/print', [ProductStockHistoryController::class, 'print'])->name('product-stock-history.print');

    Route::resource('master-unit', MasterUnitController::class);
    Route::match(['get', 'post'], 'master-unit-update-status', [MasterUnitController::class, 'status_update'])->name('master-unit.status-update');
    Route::match(['get', 'post'], 'master-unit/restore/{id}', [MasterUnitController::class, 'restore'])->name('master-unit.restore');
    Route::get('master-unit/export/excel', [MasterUnitController::class, 'exportExcel'])->name('master-unit.export.excel');
    Route::get('master-unit/export/print', [MasterUnitController::class, 'print'])->name('master-unit.print');


    //Product Grade
    Route::resource('product-grade', ProductGradeController::class);
    Route::match(['get', 'post'], 'product-grade/restore/{id}', [ProductGradeController::class, 'restore'])->name('product-grade.restore');
    Route::match(['get', 'post'], 'product-grade-update-status', [ProductGradeController::class, 'status_update'])->name('product-grade.status-update');
    Route::get('product-grade/export/excel', [ProductGradeController::class, 'exportExcel'])->name('product-grade.export.excel');
    Route::get('product-grade/export/print', [ProductGradeController::class, 'print'])->name('product-grade.print');


    //Product Go Down
    Route::resource('product-godown', ProductGodownController::class);
    Route::match(['get', 'post'], 'product-godown/restore/{id}', [ProductGodownController::class, 'restore'])->name('product-godown.restore');
    Route::match(['get', 'post'], 'product-godown-update-status', [ProductGodownController::class, 'status_update'])->name('product-godown.status-update');
    Route::get('product-godown/export/excel', [ProductGodownController::class, 'exportExcel'])->name('product-godown.export.excel');
    Route::get('product-godown/export/print', [ProductGodownController::class, 'print'])->name('product-godown.print');


    //Product HSN
    Route::resource('product-hsn', ProductHSNController::class);
    Route::match(['get', 'post'], 'product-hsn/restore/{id}', [ProductHSNController::class, 'restore'])->name('product-hsn.restore');
    Route::match(['get', 'post'], 'product-hsn-update-status', [ProductHSNController::class, 'status_update'])->name('product-hsn.status-update');
    Route::get('product-hsn/export/excel', [ProductHSNController::class, 'exportExcel'])->name('product-hsn.export.excel');
    Route::get('product-hsn/export/print', [ProductHSNController::class, 'print'])->name('product-hsn.print');


    //Product Rack
    Route::resource('product-rack', ProductRackController::class);
    Route::match(['get', 'post'], 'product-rack/restore/{id}', [ProductRackController::class, 'restore'])->name('product-rack.restore');
    Route::match(['get', 'post'], 'product-rack-update-status', [ProductRackController::class, 'status_update'])->name('product-rack.status-update');
    Route::get('product-rack/export/excel', [ProductRackController::class, 'exportExcel'])->name('product-rack.export.excel');
    Route::get('product-rack/export/print', [ProductRackController::class, 'print'])->name('product-rack.print');


    //Product Alloy
    Route::resource('product-alloy', ProductAlloyController::class);
    Route::match(['get', 'post'], 'product-alloy/restore/{id}', [ProductAlloyController::class, 'restore'])->name('product-alloy.restore');
    Route::match(['get', 'post'], 'product-alloy-update-status', [ProductAlloyController::class, 'status_update'])->name('product-alloy.status-update');
    Route::get('product-alloy/export/excel', [ProductAlloyController::class, 'exportExcel'])->name('product-alloy.export.excel');
    Route::get('product-alloy/export/print', [ProductAlloyController::class, 'print'])->name('product-alloy.print');



    Route::resource('master-warehouse', MasterWarehouseController::class);
    Route::match(['get', 'post'], 'master-warehouse-update-status', [MasterWarehouseController::class, 'status_update'])->name('master-warehouse.status-update');
    Route::match(['get', 'post'], 'master-warehouse/restore/{id}', [MasterWarehouseController::class, 'restore'])->name('master-warehouse.restore');
    Route::get('master-warehouse/export/excel', [MasterWarehouseController::class, 'exportExcel'])->name('master-warehouse.export.excel');
    Route::get('master-warehouse/export/print', [MasterWarehouseController::class, 'print'])->name('master-warehouse.print');
    Route::get('get-finished-goods-warehouse', [CommonController::class, 'get_finished_goods_warehouse'])->name('get-finished-goods-warehouse');
    Route::get('get-product-stock', [CommonController::class, 'get_product_stock'])->name('get-product-stock');



    Route::resource('master-transport-by', MasterTransportByController::class);
    Route::match(['get', 'post'], 'master-transport-by-update-status', [MasterTransportByController::class, 'status_update'])->name('master-transport-by.status-update');
    Route::match(['get', 'post'], 'master-transport-by/restore/{id}', [MasterTransportByController::class, 'restore'])->name('master-transport-by.restore');
    Route::get('master-transport-by/export/excel', [MasterTransportByController::class, 'exportExcel'])->name('master-transport-by.export.excel');
    Route::get('master-transport-by/export/print', [MasterTransportByController::class, 'print'])->name('master-transport-by.print');


    Route::resource('master-transport-detail', MasterTransportDetailController::class);
    Route::match(['get', 'post'], 'master-transport-detail-update-status', [MasterTransportDetailController::class, 'status_update'])->name('master-transport-detail.status-update');
    Route::match(['get', 'post'], 'master-transport-detail/restore/{id}', [MasterTransportDetailController::class, 'restore'])->name('master-transport-detail.restore');
    Route::get('master-transport-detail/export/excel', [MasterTransportDetailController::class, 'exportExcel'])->name('master-transport-detail.export.excel');
    Route::get('master-transport-detail/export/print', [MasterTransportDetailController::class, 'print'])->name('master-transport-detail.print');

    Route::resource('packing_type', PackingTypeController::class);
    Route::match(['get', 'post'], 'packing_type-update-status', [PackingTypeController::class, 'status_update'])->name('packing_type.status-update');
    Route::match(['get', 'post'], 'packing_type/restore/{id}', [PackingTypeController::class, 'restore'])->name('packing_type.restore');
    Route::get('packing_type/export/excel', [PackingTypeController::class, 'exportExcel'])->name('packing_type.export.excel');
    Route::get('packing_type/export/print', [PackingTypeController::class, 'print'])->name('packing_type.print');

    // terms-and-condition
    Route::resource('terms-and-condition', TermsAndConditionController::class);
    Route::match(['get', 'post'], 'terms-and-condition-update-status', [TermsAndConditionController::class, 'status_update'])->name('terms-and-condition.status-update');
    Route::match(['get', 'post'], 'terms-and-condition/restore/{id}', [TermsAndConditionController::class, 'restore'])->name('terms-and-condition.restore');
    Route::get('terms-and-condition/export/excel', [TermsAndConditionController::class, 'exportExcel'])->name('terms-and-condition.export.excel');
    Route::get('terms-and-condition/export/print', [TermsAndConditionController::class, 'print'])->name('terms-and-condition.print');



    //customer
    Route::get('customer/import', [CustomerController::class, 'import'])->name('import-customer.index');
    Route::post('customer/import', [CustomerController::class, 'import_store'])->name('import-customer.store');
    Route::resource('customer', CustomerController::class);
    Route::match(['get', 'post'], 'customer/restore/{id}', [CustomerController::class, 'restore'])->name('customer.restore');
    Route::match(['get', 'post'], 'customer-update-status', [CustomerController::class, 'status_update'])->name('customer.status-update');
    Route::get('customer/export/excel', [CustomerController::class, 'exportExcel'])->name('customer.export.excel');
    Route::get('customer/export/print', [CustomerController::class, 'print'])->name('customer.print');
    Route::post('/check-mobile', [CustomerController::class, 'checkMobileNumber'])->name('customer.mobile');
    Route::get('customer/assign-area/{id}', [CustomerController::class, 'assignArea'])->name('customer.assign.area');
    Route::post('customer/assign-area/{id}', [CustomerController::class, 'assignArea'])->name('customer.assign.area.store');
    Route::get('customer/assigned-areas-data/{id}', [CustomerController::class, 'assignedAreasData'])->name('customer.assigned.areas.data');
    Route::get('customer/assigned-areas-data/{id}', [CustomerController::class, 'getAssignedAreasData'])->name('customer.assigned.areas.data');
    Route::delete('/customer-assign-area/{id}', [CustomerController::class, 'destroyAssignArea'])->name('customer-assign-area.destroy');


    Route::get('prospected-customer/convert/{id}', [ProspectedCustomerController::class, 'convert_to_customer'])
        ->name('prospected-customer.convert_to_customer');

    // Keep other routes as they are
    Route::resource('prospected-customer', ProspectedCustomerController::class);
    Route::match(['get', 'post'], 'prospected-customer/restore/{id}', [ProspectedCustomerController::class, 'restore'])
        ->name('prospected-customer.restore');
    Route::match(['get', 'post'], 'prospected-customer-update-status', [ProspectedCustomerController::class, 'status_update'])
        ->name('prospected-customer.status-update');
    Route::get('prospected-customer/export/excel', [ProspectedCustomerController::class, 'exportExcel'])
        ->name('prospected-customer.export.excel');
    Route::get('prospected-customer/export/print', [ProspectedCustomerController::class, 'print'])
        ->name('prospected-customer.print');


    //customer Type
    Route::resource('customer-type', CustomerTypeController::class);
    Route::match(['get', 'post'], 'customer-type/restore/{id}', [CustomerTypeController::class, 'restore'])->name('customer-type.restore');
    Route::match(['get', 'post'], 'customer-type-update-status', [CustomerTypeController::class, 'status_update'])->name('customer-type.status-update');
    Route::match(['get'], 'customer-type/assign-permission/{id}', [CustomerTypeController::class, 'assign_permission'])->name('customer-type.assign-permission');
    Route::match(['post'], 'customer-type/store-permission', [CustomerTypeController::class, 'store_permission'])->name('customer-type.store-permission');
    Route::get('customer-type/export/excel', [CustomerTypeController::class, 'exportExcel'])->name('customer-type.export.excel');
    Route::get('customer-type/export/print', [CustomerTypeController::class, 'print'])->name('customer-type.print');


    // Complain Type
    Route::resource('complaintype', ComplainTypesController::class);
    Route::match(['get', 'post'], 'complaintype/restore/{id}', [ComplainTypesController::class, 'restore'])->name('complaintype.restore');
    Route::match(['get', 'post'], 'complaintype-update-status', [ComplainTypesController::class, 'status_update'])->name('complaintype.status-update');
    Route::get('complaintype/export/excel', [ComplainTypesController::class, 'exportExcel'])->name('complaintype.export.excel');
    Route::get('complaintype/export/print', [ComplainTypesController::class, 'print'])->name('complaintype.print');



    //Complain
    Route::resource('complain', ComplainController::class);
    Route::match(['get', 'post'], 'complain/restore/{id}', [ComplainController::class, 'restore'])->name('complain.restore');
    Route::match(['get', 'post'], 'complain-update-status', [ComplainController::class, 'status_update'])->name('complain.status-update');
    Route::get('complain/export/excel', [ComplainController::class, 'exportExcel'])->name('complain.export.excel');
    Route::get('complain/export/print', [ComplainController::class, 'print'])->name('complain.print');



    //Payment Terms
    Route::resource('payment-terms', PaymentTermController::class);
    Route::match(['get', 'post'], 'payment-terms/restore/{id}', [PaymentTermController::class, 'restore'])->name('payment-terms.restore');
    Route::match(['get', 'post'], 'payment-terms-update-status', [PaymentTermController::class, 'status_update'])->name('payment-terms.status-update');
    Route::get('payment-terms/export/excel', [PaymentTermController::class, 'exportExcel'])->name('payment-terms.export.excel');
    Route::get('payment-terms/export/print', [PaymentTermController::class, 'print'])->name('payment-terms.print');





    // Doucument Type
    Route::resource('document-type', DocumentTypeController::class);
    Route::match(['get', 'post'], 'document-type/restore/{id}', [DocumentTypeController::class, 'restore'])->name('document-type.restore');
    Route::match(['get', 'post'], 'document-type-update-status', [DocumentTypeController::class, 'status_update'])->name('document-type.status-update');
    Route::get('document-type/export/excel', [DocumentTypeController::class, 'exportExcel'])->name('document-type.export.excel');
    Route::get('document-type/export/print', [DocumentTypeController::class, 'print'])->name('document-type.print');


    // DocumentList
    Route::resource('document-list', DocumentListController::class);
    Route::match(['get', 'post'], 'document-list/restore/{id}', [DocumentListController::class, 'restore'])->name('document-list.restore');
    Route::match(['get', 'post'], 'document-list-update-status', [DocumentListController::class, 'status_update'])->name('document-list.status-update');
    Route::get('document-list/export/excel', [DocumentListController::class, 'exportExcel'])->name('document-list.export.excel');
    Route::get('document-list/export/print', [DocumentListController::class, 'print'])->name('document-list.print');


    // Visit
    Route::resource('visit', VisitController::class);
    Route::match(['get', 'post'], 'visit/restore/{id}', [VisitController::class, 'restore'])->name('visit.restore');
    Route::match(['get', 'post'], 'visit-update-status', [VisitController::class, 'status_update'])->name('visit.status-update');
    Route::get('visit/export/excel', [VisitController::class, 'exportExcel'])->name('visit.export.excel');
    Route::get('visit/export/print', [VisitController::class, 'print'])->name('visit.print');



    //Sales Status

    Route::resource('sales-status', SalesStatusController::class);
    Route::match(['get', 'post'], 'sales-status/restore/{id}', [SalesStatusController::class, 'restore'])->name('sales-status.restore');
    Route::match(['get', 'post'], 'sales-status-update-status', [SalesStatusController::class, 'status_update'])->name('sales-status.status-update');
    Route::get('sales-status/export/print', [SalesStatusController::class, 'print'])->name('sales-status.print');

    Route::post('sales-status/update-displayorder', [SalesStatusController::class, 'updateDisplayOrder'])->name('sales-status.update-displayorder');
    Route::get('sales-status/export/excel', [SalesStatusController::class, 'exportExcel'])->name('sales-status.export.excel');

    //Account Leager
    Route::get('account-ledger/print', [AccountLeagerController::class, 'print'])->name('account-ledger.print');

    Route::resource('account-ledger', AccountLeagerController::class);

    Route::match(['get', 'post'], 'account-ledger/restore/{id}', [AccountLeagerController::class, 'restore'])->name('account-ledger.restore');
    Route::match(['get', 'post'], 'account-ledger-update-status', [AccountLeagerController::class, 'status_update'])->name('account-ledger.status-update');
    Route::post('account-ledger/get-report', [AccountLeagerController::class, 'getLedgerReport'])->name('account-ledger.getReport');
    Route::get('account-ledger/export/excel', [AccountLeagerController::class, 'exportExcel'])->name('account-ledger.export.excel');
    Route::post('account-ledger/get-status', [AccountLeagerController::class, 'get_status'])->name('account-ledger.get-status');

    // Route::match(['get', 'post'], 'account-ledger/print', [AccountLeagerController::class, 'print'])->name('account-ledger.print');



    //Account Leager

    Route::resource('outstanding-report', OutstandingReportController::class);
    Route::match(['get', 'post'], 'outstanding-report/restore/{id}', [OutstandingReportController::class, 'restore'])->name('outstanding-report.restore');
    Route::match(['get', 'post'], 'outstanding-report-update-status', [OutstandingReportController::class, 'status_update'])->name('outstanding-report.status-update');
    Route::get('outstanding-report/export/excel', [OutstandingReportController::class, 'exportExcel'])->name('outstanding-report.export.excel');
    Route::post('outstanding-report/get-status', [OutstandingReportController::class, 'get_status'])->name('outstanding-report.get-status');
    Route::get('outstanding-report/export/print', [OutstandingReportController::class, 'print'])->name('outstanding-report.print');
    Route::post('outstanding-report/fetch', [OutstandingReportController::class, 'fetch'])->name('outstanding-report.fetch');

    //payment Receipt

    Route::resource('payment-receipt', PaymentReceiptController::class);
    Route::match(['get', 'post'], 'payment-receipt/restore/{id}', [PaymentReceiptController::class, 'restore'])->name('payment-receipt.restore');
    Route::match(['get', 'post'], 'payment-receipt-update-status', [PaymentReceiptController::class, 'status_update'])->name('payment-receipt.status-update');
    Route::get('payment-receipt/export/excel', [PaymentReceiptController::class, 'exportExcel'])->name('payment-receipt.export.excel');
    Route::post('payment-receipt/get-status', [PaymentReceiptController::class, 'get_status'])->name('payment-receipt.get-status');
    Route::get('payment-receipt/generate-receipt-no', [PaymentReceiptController::class, 'generateReceiptNo'])->name('payment-receipt.generate-receipt-no');
    Route::get('payment-receipt/export/print', [PaymentReceiptController::class, 'print'])->name('payment-receipt.print');
    Route::post('get-quotation-dropdown', [PaymentReceiptController::class, 'get_quotation_dropdown'])->name('payment-receipt.get-quotation-dropdown');
    Route::post('get-sales-order-dropdown', [PaymentReceiptController::class, 'get_sales_order_dropdown'])->name('payment-receipt.get-sales-order-dropdown');
    Route::get('get-quotation-amount', [PaymentReceiptController::class, 'getQuotationAmount'])->name('payment-receipt.get-quotation-amount');
    Route::get('get-sales-order-amount', [PaymentReceiptController::class, 'getSalesOrderAmount'])->name('payment-receipt.get-sales-order-amount');
    Route::match(['get', 'post'], 'get_payment_receipt_type', [PaymentReceiptController::class, 'get_payment_receipt_type'])->name('payment-receipt.get_payment_receipt_type');





    //  Packing Slip
    Route::resource('packing_slip', PackingSlipController::class);
    Route::match(['get', 'post'], 'packing_slip/insert/{id}', [PackingSlipController::class, 'insert'])->name('packing_slip.insert');
    Route::match(['get', 'post'], 'packing_slip/restore/{id}', [PackingSlipController::class, 'restore'])->name('packing_slip.restore');
    Route::match(['get', 'post'], 'packing_slip-update-status', [PackingSlipController::class, 'status_update'])->name('packing_slip.status-update');
    Route::match(['get', 'post'], 'packing_slip/get-sales-order-items', [PackingSlipController::class, 'getSalesOrderItems'])->name('packing_slip.get-sales-order-items');
    Route::match(['get', 'post'], 'packing_slip/get-customer-order-items', [PackingSlipController::class, 'getCustomerOrderItems'])->name('packing_slip.get-customer-order-items');
    Route::match(['get', 'post'], 'packing_slip/PackingSlipShow/{id}', [PackingSlipController::class, 'PackingSlipShow'])->name('packing_slip.PackingSlipShow');
    Route::match(['get', 'post'], 'packing_slip/packingSlipPrint/{id}', [PackingSlipController::class, 'packingSlipPrint'])->name('packing_slip.packingSlipPrint');
    Route::match(['get', 'post'], 'packing_slip/sendpackingSlipPdf/{id}', [PackingSlipController::class, 'sendpackingSlipPdf'])->name('packing_slip.sendpackingSlipPdf');
    Route::match(['get', 'post'], 'packing_slip/lable_view/{id}', [PackingSlipController::class, 'lable_view'])->name('packing_slip.lable_view');
    Route::match(['get', 'post'], 'packing_slip/lable_print/{id}', [PackingSlipController::class, 'lable_print'])->name('packing_slip.lable_print');
    Route::match(['get', 'post'], 'packing_slip/cartoon_view/{id}', [PackingSlipController::class, 'cartoon_view'])->name('packing_slip.cartoon_view');
    Route::match(['get', 'post'], 'packing_slip/cartoon_print/{id}', [PackingSlipController::class, 'cartoon_print'])->name('packing_slip.cartoon_print');

    Route::get('packing_slip/export/excel', [PackingSlipController::class, 'exportExcel'])->name('packing_slip.export.excel');
    Route::get('packing_slip/export/print', [PackingSlipController::class, 'print'])->name('packing_slip.print');
    Route::match(['get', 'post'], 'packing_slip/pending-packing-notifications', [PackingSlipController::class, 'pendingPackingNotifications'])->name('packing_slip.pending-packing-notifications');
    Route::match(['get', 'post'], 'packing_slip/mark-as-read-notifications', [PackingSlipController::class, 'markAsReadNotifications'])->name('packing_slip.mark-as-read-notifications');


    // `dispatch` Orders
    Route::resource('dispatch_orders', DispatchController::class);
    Route::match(['get', 'post'], 'dispatch_orders/insert/{id}', [DispatchController::class, 'insert'])->name('dispatch_orders.insert');
    Route::match(['get', 'post'], 'dispatch_orders/restore/{id}', [DispatchController::class, 'restore'])->name('dispatch_orders.restore');
    Route::match(['get', 'post'], 'dispatch_orders-update-status', [DispatchController::class, 'status_update'])->name('dispatch_orders.status-update');
    Route::match(['get', 'post'], 'dispatch_orders/get-sales-order-items', [DispatchController::class, 'getSalesOrderItems'])->name('dispatch_orders.get-sales-order-items');
    Route::get('dispatch_orders/export/excel', [DispatchController::class, 'exportExcel'])->name('dispatch_orders.export.excel');
    Route::get('dispatch_orders/export/print', [DispatchController::class, 'print'])->name('dispatch_orders.print');
    Route::match(['get', 'post'], 'dispatch_orders/dispatchShow/{id}', [DispatchController::class, 'dispatchShow'])->name('dispatch_orders.dispatchShow');
    Route::match(['get', 'post'], 'dispatch_orders/dispatchPrint/{id}', [DispatchController::class, 'dispatchPrint'])->name('dispatch_orders.dispatchPrint');


    // Quotation
    Route::resource('quotation', QuotationController::class);
    // Route::match(['get', 'post'], 'quotation/inquiry-to-quatation/{id}', [QuotationController::class, 'inquiry_to_quatation'])->name('quotation.inquiry-to-quatation');

    Route::match(['get', 'post'], 'quotation/restore/{id}', [QuotationController::class, 'restore'])->name('quotation.restore');
    Route::match(['get', 'post'], 'quotation-update-status', [QuotationController::class, 'status_update'])->name('quotation.status-update');
    Route::match(['get', 'post'], 'quotation/change-quotation-status', [QuotationController::class, 'change_quotation_status'])->name('quotation.change-quotation-status');
    Route::get('quotation/export/excel', [QuotationController::class, 'exportExcel'])->name('quotation.export.excel');
    Route::match(['get', 'post'], 'quotation/clone', [QuotationController::class, 'clone'])->name('quotation.clone');
    Route::get('quotation/export/print', [QuotationController::class, 'print'])->name('quotation.print');
    Route::match(['get', 'post'], 'quotation/quotationShow/{id}', [QuotationController::class, 'quotationShow'])->name('quotation.quotationShow');

    Route::match(['get', 'post'], 'quotation/quotationPrint/{id}', [QuotationController::class, 'quotationPrint'])->name('quotation.quotationPrint');
    Route::match(['get', 'post'], 'quotation/sendQuotationPdf/{id}', [QuotationController::class, 'sendQuotationPdf'])->name('quotation.sendQuotationPdf');
    // Order
    Route::resource('sales-order', SalesOrderController::class);
    Route::match(['get', 'post'], 'sales-order/restore/{id}', [SalesOrderController::class, 'restore'])->name('sales-order.restore');
    Route::match(['get', 'post'], 'sales-order-update-status', [SalesOrderController::class, 'status_update'])->name('sales-order.status-update');
    Route::match(['get', 'post'], 'sales-order-change-status', [SalesOrderController::class, 'change_sales_order_status'])->name('sales-order.change-sales-order-status');
    Route::match(['get', 'post'], 'quotation/convert-to-sales-order/{id}', [SalesOrderController::class, 'convert_to_sales_order'])->name('quotation.convert-to-sales-order');
    Route::get('sales-order/export/excel', [SalesOrderController::class, 'exportExcel'])->name('sales-order.export.excel');
    Route::get('sales-order/export/print', [SalesOrderController::class, 'print'])->name('sales-order.print');
    Route::match(['get', 'post'], 'sales-order/clone', [SalesOrderController::class, 'clone'])->name('sales-order.clone');
    Route::match(['get', 'post'], 'sales-order/salesOrderShow/{id}', [SalesOrderController::class, 'salesOrderShow'])->name('sales-order.salesOrderShow');
    Route::match(['get', 'post'], 'sales-order/salesOrderPrint/{id}', [SalesOrderController::class, 'salesOrderPrint'])->name('sales-order.salesOrderPrint');
    Route::match(['get', 'post'], 'sales-order/sendSalesOrderPdf/{id}', [SalesOrderController::class, 'sendSalesOrderPdf'])->name('sales-order.sendSalesOrderPdf');
    Route::match(['get', 'post'], 'sales-order/pending-sales-order-notifications', [SalesOrderController::class, 'pendingSalesOrderNotifications'])->name('sales-order.pending-sales-order-notifications');
    Route::match(['get', 'post'], 'sales-order/mark-as-read-sales-order-notifications', [SalesOrderController::class, 'markAsReadOrderNotifications'])->name('sales-order.mark-as-read-sales-order-notifications');

    // Invoice
    Route::resource('invoice', InvoiceController::class);
    Route::match(['get', 'post'], 'invoice/restore/{id}', [InvoiceController::class, 'restore'])->name('invoice.restore');
    Route::match(['get', 'post'], 'invoice-update-status', [InvoiceController::class, 'status_update'])->name('invoice.status-update');
    Route::match(['get', 'post'], 'invoice/change-invoice-status', [InvoiceController::class, 'change_invoice_status'])->name('invoice.change-invoice-status');
    Route::match(['get', 'post'], 'packing_slip/convert-to-invoice/{id}', [InvoiceController::class, 'convert_to_invoice'])->name('packing_slip.convert-to-invoice');
    Route::get('invoice/export/excel', [InvoiceController::class, 'exportExcel'])->name('invoice.export.excel');
    Route::get('invoice/export/print', [InvoiceController::class, 'print'])->name('invoice.print');
    Route::match(['get', 'post'], 'invoice/invoiceShow/{id}', [InvoiceController::class, 'invoiceShow'])->name('invoice.invoiceShow');
    Route::match(['get', 'post'], 'invoice/eInvoicePrint/{id}', [InvoiceController::class, 'eInvoicePrint'])->name('invoice.eInvoicePrint');
    Route::match(['get', 'post'], 'invoice/ewayBillPrint/{id}', [InvoiceController::class, 'ewayBillPrint'])->name('invoice.ewayBillPrint');
    Route::match(['get', 'post'], 'invoice/invoicePrint/{id}', [InvoiceController::class, 'invoicePrint'])->name('invoice.invoicePrint');
    Route::match(['get', 'post'], 'invoice/sendInvoicePdf/{id}', [InvoiceController::class, 'sendInvoicePdf'])->name('invoice.sendInvoicePdf');
    Route::post('invoice/bulk-download', [InvoiceController::class, 'bulkDownload'])->name('invoice.bulk-download');
    Route::match(['get', 'post'], 'invoice/{id}/cancel-einvoice-form', [InvoiceController::class, 'showCancelForm'])->name('invoice.cancel_einvoice_form');
    Route::match(['get', 'post'], 'invoice/{id}/cancel-einvoice', [InvoiceController::class, 'cancelEInvoice'])->name('invoice.cancel_einvoice');
    Route::match(['get', 'post'], 'invoice/update-vehicle', [InvoiceController::class, 'updateVehicle'])->name('invoice.updateVehicle');
    Route::match(['get', 'post'], 'invoice/vehicle-history/{id}', [InvoiceController::class, 'getVehicleHistory'])->name('invoice.vehicleHistory');


    // Order unit
    Route::resource('orderunit', OrderunitsrTypeController::class);
    Route::match(['get', 'post'], 'orderunit/restore/{id}', [OrderunitsrTypeController::class, 'restore'])->name('orderunit.restore');
    Route::match(['get', 'post'], 'orderunit-update-status', [OrderunitsrTypeController::class, 'status_update'])->name('orderunit.status-update');
    Route::get('orderunit/export/excel', [OrderunitsrTypeController::class, 'exportExcel'])->name('orderunit.export.excel');
    Route::get('orderunit/export/print', [OrderunitsrTypeController::class, 'print'])->name('orderunit.print');

    //Purpose of Visit Master
    Route::resource('purpose-visit-master', PurposeVisitMasterController::class);
    Route::match(['get', 'post'], 'purpose-visit-master-update-status', [PurposeVisitMasterController::class, 'status_update'])->name('purpose-visit-master.status-update');
    Route::match(['get', 'post'], 'purpose-visit-master/restore/{id}', [PurposeVisitMasterController::class, 'restore'])->name('purpose-visit-master.restore');
    Route::get('purpose-visit-master/export/excel', [PurposeVisitMasterController::class, 'exportExcel'])->name('purpose-visit-master.export.excel');
    Route::get('purpose-visit-master/export/print', [PurposeVisitMasterController::class, 'print'])->name('purpose-visit-master.print');



    //Department
    Route::resource('departments', DepartmentController::class);
    Route::match(['get', 'post'], 'departments-update-status', [DepartmentController::class, 'status_update'])->name('departments.status-update');
    Route::match(['get', 'post'], 'departments/restore/{id}', [DepartmentController::class, 'restore'])->name('departments.restore');
    Route::get('departments/export/excel', [DepartmentController::class, 'exportExcel'])->name('departments.export.excel');
    Route::get('departments/export/print', [DepartmentController::class, 'print'])->name('departments.print');
    // Visitor Type
    Route::resource('visitor-types', VisitorTypeController::class);
    Route::match(['get', 'post'], 'visitor-types/update-status', [VisitorTypeController::class, 'status_update'])->name('visitor-types.status-update');
    Route::match(['get', 'post'], 'visitor-types/restore/{id}', [VisitorTypeController::class, 'restore'])->name('visitor-types.restore');
    Route::get('visitor-types/export/excel', [VisitorTypeController::class, 'exportExcel'])->name('visitor-types.export.excel');
    Route::get('visitor-types/export/print', [VisitorTypeController::class, 'print'])->name('visitor-types.print');



    //Visitor Gatepass
    Route::resource('visitor-gatepass', VisitorGatepassController::class);
    Route::match(['get', 'post'], 'visitor-gatepass-update-status', [VisitorGatepassController::class, 'status_update'])->name('visitor-gatepass.status-update');
    Route::match(['get', 'post'], 'visitor-gatepass/restore/{id}', [VisitorGatepassController::class, 'restore'])->name('visitor-gatepass.restore');
    Route::get('visitor-gatepass/export/excel', [VisitorGatepassController::class, 'exportExcel'])->name('visitor-gatepass.export.excel');
    Route::get('visitor-gatepass/export/print', [VisitorGatepassController::class, 'print'])->name('visitor-gatepass.print');
    Route::post('/visitor-gatepass/mark-time', [VisitorGatepassController::class, 'markTime'])->name('visitor-gatepass.markTime');

    //Expo Maketing Slider
    Route::resource('expo-marketing-slider', ExpoMarketingSliderController::class);
    Route::match(['get', 'post'], 'expo-marketing-slider-update-status', [ExpoMarketingSliderController::class, 'status_update'])->name('expo-marketing-slider.status-update');
    Route::match(['get', 'post'], 'expo-marketing-slider/restore/{id}', [ExpoMarketingSliderController::class, 'restore'])->name('expo-marketing-slider.restore');
    Route::get('expo-marketing-slider/export/excel', [ExpoMarketingSliderController::class, 'exportExcel'])->name('expo-marketing-slider.export.excel');
    Route::get('expo-marketing-slider/export/print', [ExpoMarketingSliderController::class, 'print'])->name('expo-marketing-slider.print');
    Route::post('/expo-marketing-slider/mark-time', [ExpoMarketingSliderController::class, 'markTime'])->name('expo-marketing-slider.markTime');
    Route::post('expo-marketing-slider/update-display-order', [ExpoMarketingSliderController::class, 'updateDisplayOrder'])->name('expo-marketing-slider.update-display-order');


    // team-role
    Route::resource('team-role', TeamRoleController::class);
    Route::match(['get', 'post'], 'team-role-update-status', [TeamRoleController::class, 'status_update'])->name('team-role.status-update');
    Route::match(['get', 'post'], 'team-role/restore/{id}', [TeamRoleController::class, 'restore'])->name('team-role.restore');
    Route::match(['get'], 'team-role/assign-permission/{id}', [TeamRoleController::class, 'assign_permission'])->name('team-role.assign-permission');
    Route::match(['post'], 'team-role/store-permission', [TeamRoleController::class, 'store_permission'])->name('team-role.store-permission');


    // manage email
    Route::resource('manage-email', ManageEmailController::class);
    Route::match(['get', 'post'], 'manage-email/restore/{id}', [ManageEmailController::class, 'restore'])->name('manage-email.restore');
    Route::match(['get', 'post'], 'manage-email-update-status', [ManageEmailController::class, 'status_update'])->name('manage-email.status-update');


    //Plan Master
    Route::resource('plan-master', PlanMasterController::class);
    Route::match(['get', 'post'], 'plan-master-update-status', [PlanMasterController::class, 'status_update'])->name('plan-master.status-update');
    Route::match(['get', 'post'], 'plan-master/restore/{id}', [PlanMasterController::class, 'restore'])->name('plan-master.restore');


    // Company

    Route::resource('company', CompanyController::class);
    Route::post('company/check-company-exists', [CompanyController::class, 'check_company_exists'])->name('company.check-company-exists');
    Route::match(['get', 'post'], 'company-update-status', [CompanyController::class, 'status_update'])->name('company.status-update');

    Route::match(['get', 'post'], 'company/thirdpartyintegration/{id}', [CompanyController::class, 'thirdPartyIntegration'])->name('company.thirdpartyintegration');
    Route::match(['get', 'post'], 'company/thirdparty-integration/{platform?}/{id?}', [CompanyController::class, 'thirdPartyIntegrationPlatform'])->name('company.thirdparty-integration.platform');
    Route::post('company/remove-rate-sheet/{id}', [CompanyController::class, 'remove_rate_sheet'])->name('company.remove-rate-sheet');

    Route::match(['get', 'post'], 'company/mail-setting/{id}', [CompanyController::class, 'mail_setting'])->name('company.mail_setting');
    Route::match(['get', 'post'], 'company/license-setting/{id}', [CompanyController::class, 'license_setting'])->name('company.license_setting');
    Route::match(['get', 'post'], 'company/upgrade_plan/{id}', [CompanyController::class, 'upgrade_plan'])->name('company.upgrade_plan');
    Route::match(['get', 'post'], 'company/detail/{id}', [CompanyController::class, 'detail'])->name('company.detail');
    Route::match(['get', 'post'], 'set_company_session', [CompanyController::class, 'set_company_session'])->name('company.set_company_session');
    Route::match(['get', 'post'], 'get_company_social', [CompanyController::class, 'get_company_social'])->name('company.get_company_social');
    Route::match(['get', 'post'], 'verify_website_api_code', [CompanyController::class, 'verify_website_api_code'])->name('company.verify_website_api_code');
    Route::match(['get', 'post'], 'master_config/{platform?}/{id?}', [CompanyController::class, 'set_master_config'])->name('company.master_config');
    Route::match(['get', 'post'], 'get_master_social', [CompanyController::class, 'get_master_social'])->name('company.get_master_social');

    // Route::get('company/integration/third-party/{id?}', [CompanyController::class, 'thirdPartyIntegration'])->name('company.third-party-integration');

    // Sidebar Menu
    Route::match(['GET', 'POST'], 'sidebar-menu', [DashboardController::class, 'sidebar_menu'])->name('sidebar.menu');
    Route::delete('sidebar-menu-delete', [DashboardController::class, 'sidebar_menu_delete'])->name('sidebar.menu.delete');
    Route::match(['get', 'post'], 'permissions', [DashboardController::class, 'permissions'])->name('permissions.punchinout');

    Route::resource('company-subscription-plan', CompanySubscriptionPlanController::class);
    // Route::get('company-subscription-plan/{id}', [CompanySubscriptionPlanController::class, 'show'])->name('company-subscription-plan.show');
    Route::match(['get', 'post'], 'update_subscription_plan', [CompanySubscriptionPlanController::class, 'update_subscription_plan'])->name('company-subscription-plan.update_subscription_plan');
    Route::match(['get', 'post'], 'get_subscription_addons', [CompanySubscriptionPlanController::class, 'get_subscription_addons'])->name('company-subscription-plan.get_subscription_addons');

    Route::resource('user', UserController::class);
    Route::match(['get', 'post'], 'user-update-status', [UserController::class, 'status_update'])->name('user.status-update');
    Route::get('user/export/excel', [UserController::class, 'exportExcel'])->name('user.export.excel');
    Route::get('user/export/print', [UserController::class, 'print'])->name('user.print');

    Route::resource('sales-order-report', SalesOrderReportController::class);
    Route::get('sales-order-report/export/print', [SalesOrderReportController::class, 'print'])->name('sales-order-report.print');
    Route::get('sales-order-report/export/excel', [SalesOrderReportController::class, 'exportExcel'])->name('sales-order-report.export.excel');

    Route::resource('customer-report', CustomerReportController::class);
    Route::match(['get', 'post'], 'get_sales_order_report', [CustomerReportController::class, 'get_sales_order_report'])->name('customer-report.get_sales_order_report');
    Route::match(['get', 'post'], 'get_quotation_report', [CustomerReportController::class, 'get_quotation_report'])->name('customer-report.get_quotation_report');
    Route::match(['get', 'post'], 'get_dispatch_report', [CustomerReportController::class, 'get_dispatch_report'])->name('customer-report.get_dispatch_report');
    Route::get('customer-report/export/excel', [CustomerReportController::class, 'exportExcel'])->name('customer-report.export.excel');

    Route::resource('product-sales-report', ProductSalesReportController::class);
    Route::get('product-sales-report/export/excel', [ProductSalesReportController::class, 'exportExcel'])->name('product-sales-report.export.excel');
    Route::match(['get', 'post'], 'get_customer_sales_report', [ProductSalesReportController::class, 'get_customer_sales_report'])->name('product-sales-report.get_customer_sales_report');

    Route::resource('jobwork-issue-report', JobWorkIssueReportController::class);
    Route::get('jobwork-issue-report/export/print', [JobWorkIssueReportController::class, 'print'])->name('jobwork-issue-report.print');
    Route::get('jobwork-issue-report/export/excel', [JobWorkIssueReportController::class, 'exportExcel'])->name('jobwork-issue-report.export.excel');

    Route::resource('jobwork-receive-report', JobWorkReceiveReportController::class);
    Route::get('jobwork-receive-report/export/print', [JobWorkReceiveReportController::class, 'print'])->name('jobwork-receive-report.print');
    Route::get('jobwork-receive-report/export/excel', [JobWorkReceiveReportController::class, 'exportExcel'])->name('jobwork-receive-report.export.excel');

    Route::resource('jobwork-pending-report', JobWorkPendingController::class);
    Route::get('jobwork-pending-report/export/print', [JobWorkPendingController::class, 'print'])->name('jobwork-pending-report.print');
    Route::get('jobwork-pending-report/export/excel', [JobWorkPendingController::class, 'exportExcel'])->name('jobwork-pending-report.export.excel');


    Route::get('auth/facebook', [FacebookController::class, 'redirectToFacebook'])->name('facebook.login');
    Route::get('auth/facebook/callback', [FacebookController::class, 'handleFacebookCallback']);
    Route::get('facebook/leads', [FacebookController::class, 'getLeads']);


    Route::resource('set-point-to-rupees', SetPointToRupeesController::class);
    Route::match(['get', 'post'], 'set-point-to-rupees/restore/{id}', [SetPointToRupeesController::class, 'restore'])->name('set-point-to-rupees.restore');
    Route::match(['get', 'post'], 'set-point-to-rupees-update-status', [SetPointToRupeesController::class, 'status_update'])->name('set-point-to-rupees.status-update');


    Route::resource('quick-qr-generator', QuickQRGeneratorController::class);
    Route::match(['get', 'post'], 'quick-qr-generator-update-status', [QuickQRGeneratorController::class, 'status_update'])->name('quick-qr-generator.status-update');
    Route::match(['get', 'post'], 'quick-qr-generator-item-status', [QuickQRGeneratorController::class, 'redemptionItemStatuUpdate'])->name('quick-qr-generator-item-status');
    Route::match(['get', 'post'], 'quick-qr-generator/restore/{id}', [QuickQRGeneratorController::class, 'restore'])->name('quick-qr-generator.restore');
    Route::delete('/quick-qr-generator-view/{id}', [QuickQRGeneratorController::class, 'redemption_items_destroy'])->name('quick-qr-generator-view.redemption_items_destroy');

    Route::post('quick-qr-generator/store', [QuickQRGeneratorController::class, 'storeQuickQR'])->name('quick-qr-generator-scanner.store-scanner');

    Route::resource('withdraw-request', WithdrawRequestController::class);
    Route::match(['get', 'post'], 'withdraw-request-update-status', [WithdrawRequestController::class, 'status_update'])->name('withdraw-request.status-update');
    Route::match(['get', 'post'], 'withdraw-request/restore/{id}', [WithdrawRequestController::class, 'restore'])->name('withdraw-request.restore');
    Route::get('withdraw-request/export/print', [WithdrawRequestController::class, 'print'])->name('withdraw-request.print');
    Route::get('withdraw-request/export/excel', [WithdrawRequestController::class, 'exportExcel'])->name('withdraw-request.export.excel');

    Route::resource('qr-link-generate', QRLinkGenerateController::class);
    Route::match(['get', 'post'], 'qr-link-generate-update-status', [QRLinkGenerateController::class, 'status_update'])->name('qr-link-generate.status-update');
    Route::match(['get', 'post'], 'get_qr_details', [QRLinkGenerateController::class, 'get_qr_details'])->name('qr-link-generate.get_qr_details');
    Route::get('qr-link-generate/download/{id}', [QRLinkGenerateController::class, 'download'])->name('qr-link-generate.download');

    // Task
    Route::resource('task', TaskController::class);
    Route::match(['get', 'post'], 'task/restore/{id}', [TaskController::class, 'restore'])->name('task.restore');
    Route::match(['get', 'post'], 'task-update-status', [TaskController::class, 'status_update'])->name('task.status-update');
    Route::match(['get', 'post'], 'task/change-task-status', [TaskController::class, 'change_task_status'])->name('task.change-task-status');
    Route::match(['get', 'post'], 'task/get-task-status-log', [TaskController::class, 'get_task_status_log'])->name('task.get-task-status-log');
    Route::get('task/export/excel', [TaskController::class, 'exportExcel'])->name('task.export.excel');
    Route::get('task/export/print', [TaskController::class, 'print'])->name('task.print');

    // Task status
    Route::resource('task-status', TaskStatusController::class);
    Route::match(['get', 'post'], 'task-status/restore/{id}', [TaskStatusController::class, 'restore'])->name('task-status.restore');
    Route::match(['get', 'post'], 'task-status-update-status', [TaskStatusController::class, 'status_update'])->name('task-status.status-update');
    Route::get('task-status/export/excel', [TaskStatusController::class, 'exportExcel'])->name('task-status.export.excel');
    Route::get('task-status/export/print', [TaskStatusController::class, 'print'])->name('task-status.print');
    Route::post('task-status/update-displayorder', [TaskStatusController::class, 'updateDisplayOrder'])->name('task-status.update-displayorder');


    Route::resource('notification', NotificationController::class);
    Route::match(['GET', 'POST'], 'testNotification', [DashboardController::class, 'testNotification'])->name('testNotification');
    Route::post('/store-token', [NotificationController::class, 'updateDeviceToken'])->name('store.token');
    Route::post('/send-notification', [NotificationController::class, 'sendNotification'])->name('send.web-notification');
    Route::match(['GET', 'POST'], 'get_notification', [NotificationController::class, 'getNotification'])->name('notification.get_notification');
    Route::match(['GET', 'POST'], 'clear', [NotificationController::class, 'clearNotification'])->name('notification.clear');
    Route::post('/acknowledge', [NotificationController::class, 'acknowledge'])->name('notification.acknowledge');

    // WhatsApp API
    Route::get('send-whatsapp/{id}/{type}', [WhatsAppController::class, 'send'])->name('whatsapp.send');
    Route::post('test-whatsapp', [WhatsAppController::class, 'testWhatsAppConnection'])->name('company.test-whatsapp');
});

Route::get('/scan-form/{id}', [QuickQRGeneratorController::class, 'scanForm'])->name('scan.form');
Route::get('/software', function () {
    return redirect('software/dashboard', 301);
});
