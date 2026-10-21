<?php

namespace Database\Seeders;

use App\Exports\ExpenseExport;
use App\Http\Controllers\software\CompanyController;
use App\Models\Company;
use App\Models\CompanyDetails;
use App\Models\CompanySubscriptionPlan;
use App\Models\ComplanType;
use App\Models\Designation;
use App\Models\DocumentType;
use App\Models\ExpenseCategory;
use App\Models\ExpenseSubCategory;
use App\Models\FollowUpReason;
use App\Models\LeaveType;
use App\Models\MainMenu;
use App\Models\MarketingStatus;
use App\Models\MasterCity;
use App\Models\MasterCountry;
use App\Models\MasterState;
use App\Models\MasterTransportBy;
use App\Models\MasterTransportDetail;
use App\Models\MasterUnit;
use App\Models\MasterWarehouse;
use App\Models\Orderunit;
use App\Models\PlanMaster;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use App\Models\ProductTAX;
use App\Models\ProductVariant;
use App\Models\RolePermission;
use App\Models\SalesOrder;
use App\Models\SalesStatus;
use App\Models\SourceOfInquiry;
use App\Models\SubMenu;
use App\Models\TeamPerson;
use App\Models\TeamRole;
use App\Models\TermsAndCondition;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (MasterCountry::count()) {
            MasterCountry::truncate();
        }
        $country = MasterCountry::create(['name' => 'India', 'short_name' => 'IND', 'code' => '91']);

        $this->command->info("MasterCountry Done.");

        if (MasterState::count()) {
            MasterState::truncate();
        }
        $state = MasterState::create(
            [
                'country_id' => $country?->id, // This will be set dynamically from the command
                'name' => "Gujarat", // Generates a unique state name
                'status' => 'active', // Random status
                'created_by' => 1, // Static creator ID
                'updated_by' => 1, // Static updater ID
                'deleted_by' => null, // Static deleted_by field
            ]
        );
        $this->command->info("MasterState Done.");

        if (MasterCity::count()) {
            MasterCity::truncate();
        }
        $city = MasterCity::create([
            'country_id' => $country?->id, // Will be set dynamically in the seeder/command
            'state_id' => $state->id,   // Will be set dynamically in the seeder/command
            'name' => "Rajkot",
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
        ]);
        $this->command->info("MasterCity Done.");

        if (Company::count()) {
            Company::truncate();
        }
        $planMaster = PlanMaster::firstWhere('status', 'active');

        $companyData = [
            'gst_no' => null,
            'company_name' => "Ocean Infotech",
            'person_name' => "Sandipbhai",
            'whatsapp_number' => "8000853781",
            'email' => 'info@oceaninfotech.co.in',
            'password' => Hash::make("Ocean@2025"),
            'sp' => "Ocean@2025",
            'otp' => "2025",
            'pan_card' => null,
            'address' => "Rajkot",
            'country_id' => $country?->id ?? 1,
            'state_id' => $state?->id ?? 1,
            'city_id' => $city?->id ?? 1,
            'plan_id' => $planMaster->id,
            'app_right' => implode(",", $planMaster->app_right),
            'panel_right' => implode(",", $planMaster->panel_right),
            'customer_panel_right' => null,
            'plan_from' => \Carbon\Carbon::now(),
            'plan_to' => \Carbon\Carbon::now(),
            'app_key' => "Ocean@2025",
            'panel_url' => env("APP_ENV"),
            'database_name' => null,
            'database_user' => null,
            'database_password' => null,
            'max_team_user_count' => $planMaster->id,
            'max_customer_count' => $planMaster->id,
            'max_inquiry_count' => $planMaster->id,
            'mobile_min' => 10,
            'mobile_max' => 10,
            'order_view_format' => 'general_view',
            'quotation_view_format' => 'general_view',
            'dispatch_view_format' => 'v1',
            'purchaseOrder_view_format' => 'general_view',
            'order_prefix' => 'SO',
            'quotation_prefix' => 'QUO',
            'dispatch_prefix' => "DIS",
            'packing_slip_prefix' => "PS",
            'vendor_prefix' => "VENDOR",
            'purchase_order_prefix' => "PO",
            'order_title' => "Sales Order",
            'quotation_title' => 'Quatation',
            'dispatch_title' => "Dispatch",
            'packing_slip_title' => 'Packing Slip',
            'vendor_title' => null,
            'purchase_order_title' => "Purchase Order",
            'purchase_order_color' => "#c7c6c6",
            'order_view_color' => "#c7c6c6",
            'quotation_view_color' => "#c7c6c6",
            'dispatch_view_color' => "#c7c6c6",
            'vendor_view_color' => "#c7c6c6",
            'packing_slip_view_color' => "#c7c6c6",
            'stock_manage_by' => 'lifo',
            'quantity' => 'qty',
            'tax_type' => 'product_wise',
            'stock_check' => 'yes',
            'is_copyright_view' => 'yes',
            'default_password' => "Default@2025",
            'reset_password' => "Reset@2025",
            'phonecode' => $country->code,
            'status' => 'active'
        ];

        if (Company::count()) {
            Company::truncate();
        }
        $company = Company::create($companyData);
        $this->command->info("Company Done.");
        $company_id = $company->id;

        $team_role = TeamRole::create([
            'company_id' => $company->id,
            'parent_id' => '0',
            'name' => $company->company_name,
            'created_type' => 'admin_software',
            'created_by' => $company->created_by,
            // 'updated_by' => $company->created_by,
        ]);
        $this->command->info("TeamRole Done.");

        if (CompanyDetails::count()) {
            CompanyDetails::truncate();
        }
        CompanyDetails::create([
            'company_id' => $company?->id ?? '1',
            'how_assign_inquiry' => 'source_of_inquiry_wise'
        ]);
        $this->command->info("CompanyDetails Done.");

        if (CompanySubscriptionPlan::count()) {
            CompanySubscriptionPlan::truncate();
        }
        CompanySubscriptionPlan::create([
            'company_id' => $company?->id ?? '1',
            'plan_id' => $planMaster?->id ?? '1',
            'plan_from' => \Carbon\Carbon::now(),
            'plan_to' => \Carbon\Carbon::now()->addDay($planMaster?->plan_valid_day),
            'extra_detail' => json_encode($planMaster),
            'plan_expiry_date' => \Carbon\Carbon::now()->addDay($planMaster?->plan_valid_day),
            'subscription_status' => 'active',
        ]);
        $this->command->info("CompanySubscriptionPlan Done.");

        if (RolePermission::count()) {
            RolePermission::truncate();
        }
        $mainMenus = MainMenu::where('status', 'active')->get();

        foreach ($mainMenus as $mainMenu) {
            $subMenus = SubMenu::where('main_menu_id', $mainMenu->id)
                ->where('status', 'active')
                ->get();

            foreach ($subMenus as $subMenu) {
                if (in_array((string) $subMenu->id, $planMaster->panel_right)) {  // Match by submenu ID
                    RolePermission::create([
                        'company_id' => $company?->id,
                        'team_role_id' => $team_role->id,
                        'main_menu_id' => $mainMenu->id,
                        'sub_menu_id' => $subMenu->id,
                        'view_flag' => 1,
                        'add_flag' => 1,
                        'update_flag' => 1,
                        'delete_flag' => 1,
                        'restore_flag' => 1,
                        'print_flag' => 1,
                        'excel_flag' => 1,
                        'approval_flag' => 1,
                        'all_data_flag' => 1,
                        'status' => 'active',
                        'created_by' => $company->created_by,
                    ]);
                }
            }
        }

        if (TeamPerson::count()) {
            TeamPerson::truncate();
        }
        // Insert team person
        $teamPerson = TeamPerson::create([
            'company_id' => $company_id,
            'name' => $company->person_name,
            'parent_type_id' => 0,
            'mobile_no' => $company->whatsapp_number,
            'employee_code' => (new CompanyController())->generateEmployeeCode($company_id),
            'username' => $company->whatsapp_number,
            'password' => Hash::make($company->sp),
            'sp' => Str::random(4) . $company->sp . Str::random(3),
            'min_working_start_time' => '05:30:00',
            'max_working_start_time' => '05:30:00',
            'working_end_time' => '05:30:00',
            'country_id' => $company->country_id,
            'state_id' => $company->state_id,
            'city_id' => $company->city_id,
            'email' => $company->email,
            'team_role_id' => $team_role->id,
            'created_by' => $company->created_by,
            // 'updated_by' => $company->created_by,
        ]);
        $this->command->info("TeamPerson Done.");

        if (SourceOfInquiry::count()) {
            SourceOfInquiry::truncate();
        }
        // Source Of Inquiry
        $sourceNames = ['Indiamart', 'Facebook', 'Call', 'By Visit'];
        foreach ($sourceNames as $name) {
            SourceOfInquiry::create([
                'company_id' => $company_id,
                'name' => $name,
                'status' => 'active',
                'created_by' => $company->created_by,
            ]);
        }
        $this->command->info("SourceOfInquiry Done.");

        if (Designation::count()) {
            Designation::truncate();
        }
        //Designation
        Designation::create([
            'company_id' => $company_id,
            'name' => 'Admin',
            'status' => 'active',
            'created_by' => $company->created_by,
        ]);
        $this->command->info("Designation Done.");

        if (DocumentType::count()) {
            DocumentType::truncate();
        }
        // Document Type
        DocumentType::create([
            'company_id' => $company_id,
            'name' => 'General Documents',
            'status' => 'active',
            'created_by' => $company->created_by,
        ]);
        $this->command->info("DocumentType Done.");

        if (MasterTransportBy::count()) {
            MasterTransportBy::truncate();
        }
        // Transport By
        $transportBy = MasterTransportBy::create([
            'company_id' => $company_id,
            'name' => 'By Road',
            'status' => 'active',
            'created_by' => $company->created_by,
        ]);
        $this->command->info("MasterTransportBy Done.");

        if (MasterTransportDetail::count()) {
            MasterTransportDetail::truncate();
        }
        // TransportDetail
        MasterTransportDetail::create([
            'company_id' => $company_id,
            'transport_by_id' => $transportBy->id,
            'name' => 'My Self',
            'status' => 'active',
            'created_by' => $company->created_by,
        ]);
        $this->command->info("MasterTransportDetail Done.");

        if (MasterWarehouse::count()) {
            MasterWarehouse::truncate();
        }
        // Warehouse
        MasterWarehouse::create([
            'company_id' => $company_id,
            'name' => 'General Warehouse',
            'status' => 'active',
            'created_by' => $company->created_by,
        ]);
        $this->command->info("MasterWarehouse Done.");

        if (MasterUnit::count()) {
            MasterUnit::truncate();
        }
        // Unit
        MasterUnit::create([
            'company_id' => $company_id,
            'name' => 'Pcs',
            'status' => 'active',
            'platform' => 'web',
            'created_by' => $company->created_by,
        ]);
        $this->command->info("MasterUnit Done.");

        if (Orderunit::count()) {
            Orderunit::truncate();
        }
        // Order Unit
        Orderunit::create([
            'company_id' => $company_id,
            'name' => 'Qty',
            'status' => 'active',
            'platform' => 'web',
            'created_by' => $company->created_by,
        ]);
        $this->command->info("Orderunit Done.");

        if (ProductTAX::count()) {
            ProductTAX::truncate();
        }
        // Product TAX
        ProductTAX::create([
            'company_id' => $company_id,
            'tax_name' => 'GST 18%',
            'tax_value' => 18,
            'status' => 'active',
            'created_by' => $company->created_by,
        ]);
        $this->command->info("ProductTAX Done.");

        if (ProductVariant::count()) {
            ProductVariant::truncate();
        }
        // Product Variant
        ProductVariant::create([
            'company_id' => $company_id,
            'name' => 'Without Variant',
            'status' => 'active',
            'platform' => 'web',
            'created_by' => $company->created_by,
        ]);
        $this->command->info("ProductVariant Done.");

        if (ProductCategory::count()) {
            ProductCategory::truncate();
        }
        //  Product Category
        $productCategory = ProductCategory::create([
            'company_id' => $company_id,
            'name' => 'General Product Category',
            'status' => 'active',
            'platform' => 'web',
            'created_by' => $company->created_by,
        ]);
        $this->command->info("productCategory Done.");

        if (ProductSubcategory::count()) {
            ProductSubcategory::truncate();
        }
        //  Product Sub Category
        ProductSubcategory::create([
            'company_id' => $company_id,
            'product_category_id' => $productCategory->id,
            'name' => 'General Product Sub Category',
            'status' => 'active',
            'platform' => 'web',
            'created_by' => $company->created_by,
        ]);
        $this->command->info("ProductSubcategory Done.");

        if (LeaveType::count()) {
            LeaveType::truncate();
        }
        // Leave Type
        LeaveType::create([
            'company_id' => $company_id,
            'name' => 'General leave',
            'status' => 'active',
            'created_by' => $company->created_by,
        ]);
        $this->command->info("LeaveType Done.");

        if (ExpenseCategory::count()) {
            ExpenseCategory::truncate();
        }
        // Expense Category
        $expenseCategory = ExpenseCategory::create([
            'company_id' => $company_id,
            'name' => 'General Expense Category',
            'status' => 'active',
            'created_by' => $company->created_by,
        ]);
        $this->command->info("ExpenseCategory Done.");

        if (ExpenseSubCategory::count()) {
            ExpenseSubCategory::truncate();
        }
        // Expense SubCategory
        ExpenseSubCategory::create([
            'company_id' => $company_id,
            'expense_category_id' => $expenseCategory->id,
            'team_person_ids' => (string) $teamPerson->id,
            'name' => 'General Expense Sub Category',
            'expense_type' => 'General',
            'is_image_required' => 0,
            'min_amount' => 1,
            'max_amount' => 10000,
            'per_km_rate' => null,
            'fix_amount' => null,
            'from_time' => null,
            'to_time' => null,
            'status' => 'active',
            'created_by' => $company->created_by,
        ]);
        $this->command->info("ExpenseSubCategory Done.");

        if (FollowUpReason::count()) {
            FollowUpReason::truncate();
        }
        // FollowUpReason
        FollowUpReason::create([
            'company_id' => $company_id,
            'name' => 'General Follow Up',
            'status' => 'active',
            'created_by' => $company->created_by,
        ]);
        $this->command->info("FollowUpReason Done.");

        if (MarketingStatus::count()) {
            MarketingStatus::truncate();
        }
        // Marketing Status - Inquiry
        MarketingStatus::create([
            'company_id' => $company_id,
            'type' => 'Inquiry',
            'name' => 'New Inquiry',
            'display_order' => 1,
            'status_color' => '#c7c6c6',
            'status_color_2' => '#c7c6c6',
            'text_color' => '#000000',
            'is_default' => 1,
            'status' => 'active',
            'created_by' => $company->created_by,
        ]);
        $this->command->info("MarketingStatus Done.");

        // Marketing Status - Follow Up
        MarketingStatus::create([
            'company_id' => $company_id,
            'type' => 'Followup',
            'name' => 'Generated',
            'display_order' => 1,
            'status_color' => '#c7c6c6',
            'status_color_2' => '#c7c6c6',
            'text_color' => '#000000    ',
            'is_default' => 1,
            'status' => 'active',
            'created_by' => $company->created_by,
        ]);
        $this->command->info("MarketingStatus Done.");

        if (ComplanType::count()) {
            ComplanType::truncate();
        }
        // Complain Type
        ComplanType::create([
            'company_id' => $company_id,
            'name' => 'General Complain',
            'status' => 'active',
            'created_by' => $company->created_by,
        ]);
        $this->command->info("ComplanType Done.");

        if (TermsAndCondition::count()) {
            TermsAndCondition::truncate();
        }
        // Terms and Condition
        TermsAndCondition::create([
            'company_id' => $company_id,
            'title' => 'General Terms',
            'terms_and_condition_detail' => 'General Terms',
            'status' => 'active',
            'created_by' => $company->created_by,
        ]);
        $this->command->info("TermsAndCondition Done.");

        // Sales Statuses
        $salesStatuses = [
            'Quotation',
            'Sales Orders',
            'Invoice',
            'Purchase Order',
        ];

        if (SalesStatus::count()) {
            SalesStatus::truncate();
        }
        foreach ($salesStatuses as $salesType) {
            SalesStatus::create([
                'company_id' => $company_id,
                'salestype' => $salesType,
                'statusname' => 'Pending',
                'statusslug' => 'pen',
                'status_color' => '#000000',
                'display_order' => 1,
                'status' => 'active',
                'is_redirect' => 0,
                'is_revise' => 0,
                'is_repeat' => 0,
                'parent_status_id' => null,
                'created_by' => $company->created_by,
            ]);
        }
        $this->command->info("SalesStatus Done.");

        echo "Company created";
        // dd($planMaster->toArray(), implode(",", $planMaster->panel_right), $companyData);
    }
}
