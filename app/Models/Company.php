<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\PlanMaster;
use Illuminate\Support\Str;

class Company extends Model
{
    use SoftDeletes;

    protected $table = 'companies';

    static $folderPath = "companies/";

    protected $appends = ['company_logo_url', 'company_favicon_url', 'white_labeling_logo_url', 'app_logo_url', 'order_header_logo_url', 'order_footer_logo_url','invoice_letter_head_url', 'authorised_signature_url', 'brochure_url', 'rate_sheet_url'];

    public static $quantity_type = [
        "qty" => "Qty",
        "bag" => "Bag",
        "box" => "Box",
        "kg" => "KG",
        "bag, qty" => "Bag, Qty",
        "box, qty" => "Box, Qty",
        "kg, qty" => "KG, Qty",
        "bag, box" => "Bag, Box",
        "bag, box, qty" => "Bag, Box and Qty",
    ];

    public static $tax_type = [
        "order_wise" => "Order Wise",
        "product_wise" => "Product Wise",
    ];

    public static $quotation_view_format = [
        "general_view" => "General View",
        "default_view" => "Default View",
    ];
    public static $purchaseOrder_view_format = [
        "general_view" => "General View",
        "default_view" => "Default View",
    ];
    public static $how_assign_inquiry = [
        "source_of_inquiry_wise"          => "Source Of Inquiry Wise",
        "area_allocation_wise"           => "Area Allocation Wise",
        "team_person_wise"               => "Particular Team Person Wise",
        "product_category_subcategory_wise" => "Product Category and Sub Category Wise",
        "auto_sequence_wise"             => "Auto Sequence Wise",
        "industry_wise"                  => "Industry Wise if Option Available",
        "manual_assign_wise"            => "Manually Assign Wise",
    ];

    public static $stock_check = [
        "yes" => "Yes",
        "no" => "No",
    ];
    public static $order_view_format = [
        "general_view" => "General View",
        "default_view" => "Default View",
    ];

    public static $invoice_view_format = [
        "general_view" => "General View",
        "default_view" => "Default View",
    ];

    public static $account_effect_by = [
        "quotation" => "Quotation",
        "order" => "Order",
        "invoice" => "Invoice",
    ];

    protected $fillable = [
        'gst_no',
        'lut_number',
        'company_name',
        'person_name',
        'whatsapp_number',
        'email',
        'password',
        'sp',
        'otp',
        'pan_card',
        'bank_details',
        'address',
        'country_id',
        'state_id',
        'city_id',
        'pincode',
        'plan_id',
        'app_right',
        'panel_right',
        'customer_panel_right',
        'plan_from',
        'plan_to',
        'app_key',
        'panel_url',
        'database_name',
        'database_user',
        'database_password',
        'max_team_user_count',
        'max_customer_count',
        'max_inquiry_count',
        'mobile_min',
        'mobile_max',
        'order_view_format',
        'quotation_view_format',
        'dispatch_view_format',
        'purchaseOrder_view_format',
        'invoice_view_format',
        'order_prefix',
        'quotation_prefix',
        'dispatch_prefix',
        'packing_slip_prefix',
        'vendor_prefix',
        'purchase_order_prefix',
        'order_title',
        'quotation_title',
        'dispatch_title',
        'packing_slip_title',
        'vendor_title',
        'purchase_order_title',
        'purchase_order_color',
        'order_view_color',
        'quotation_view_color',
        'dispatch_view_color',
        'vendor_view_color',
        'packing_slip_view_color',
        'stock_manage_by',
        'quantity',
        'tax_type',
        'stock_check',
        'account_effect_by',
        'company_logo',
        'company_favicon',
        'white_labeling_logo',
        'app_logo',
        'order_header_logo',
        'order_footer_logo',
        'invoice_letter_head',
        'authorised_signature',
        'brochure',
        'rate_sheet',
        'is_copyright_view',
        'default_password',
        'reset_password',
        'phonecode',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function customers()
    {
        return $this->hasMany(Customer::class, 'company_id', 'id');
    }

    public function company_details()
    {
        return $this->hasOne(CompanyDetails::class, 'company_id', 'id');
    }
    // Get value as array (accessor)
    public function getAppRightAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    // Store value as comma-separated string (mutator)
    public function setAppRightAttribute($value)
    {
        $this->attributes['app_right'] = is_array($value) ? implode(',', $value) : $value;
    }

    // Get value as array (accessor)
    public function getPanelRightAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    // Store value as comma-separated string (mutator)
    public function setPanelRightAttribute($value)
    {
        $this->attributes['panel_right'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function setCustomerPanelRightAttribute($value)
    {
        $this->attributes['customer_panel_right'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function getCustomerPanelRightAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    // Automatically generate the app_key
    public function setAppKeyAttribute($value)
    {
        if (!$value) {
            $this->attributes['app_key'] = Str::random(rand(5, 10)); // Generate a random app key (alphanumeric, length between 5 to 10)
        } else {
            $this->attributes['app_key'] = $value;
        }
    }

    // Automatically generate random color codes for color fields
    public function setOrderViewColorAttribute($value)
    {
        $this->attributes['order_view_color'] = $value ?: $this->generateRandomColor();
    }

    public function setQuotationViewColorAttribute($value)
    {
        $this->attributes['quotation_view_color'] = $value ?: $this->generateRandomColor();
    }

    public function setDispatchViewColorAttribute($value)
    {
        $this->attributes['dispatch_view_color'] = $value ?: $this->generateRandomColor();
    }

    // Helper function to generate a random color in hexadecimal format
    protected function generateRandomColor()
    {
        return '#' . substr(sha1(rand()), 0, 6); // Generates a random hex color code
    }

    public function getCurrentLatestPlanAttribute()
    {
        # current_latest_plan
        return CompanySubscriptionPlan::where('company_id', $this->id)->where('plan_id', $this->plan_id)->orderBy('id', 'desc')->first();
    }

    public function plan()
    {
        return $this->belongsTo(PlanMaster::class, 'plan_id', 'id');
    }

    public function country()
    {
        return $this->belongsTo(MasterCountry::class, 'country_id', 'id');
    }

    public function state()
    {
        return $this->belongsTo(MasterState::class, 'state_id', 'id');
    }

    public function city()
    {
        return $this->belongsTo(MasterCity::class, 'city_id', 'id');
    }

    public function getCompanyLogoUrlAttribute()
    {
        # company_logo_url
        if ($this->company_logo) {
            if (file_exists(public_path($this->company_logo))) {
                return asset($this->company_logo);
            }
        }
        return null;
    }

    public function getCompanyFaviconUrlAttribute()
    {
        # company_favicon_url
        if ($this->company_favicon) {
            if (file_exists(public_path($this->company_favicon))) {
                return asset($this->company_favicon);
            }
        }
        return null;
    }

    public function getWhiteLabelingLogoUrlAttribute()
    {
        # white_labeling_logo_url
        if ($this->white_labeling_logo) {
            if (file_exists(public_path($this->white_labeling_logo))) {
                return asset($this->white_labeling_logo);
            }
        }
        return null;
    }

    public function getAppLogoUrlAttribute()
    {
        # app_logo_url
        if ($this->app_logo) {
            if (file_exists(public_path($this->app_logo))) {
                return asset($this->app_logo);
            }
        }
        return null;
    }

    public function getOrderHeaderLogoUrlAttribute()
    {
        # order_header_logo_url
        if ($this->order_header_logo) {
            if (file_exists(public_path($this->order_header_logo))) {
                return asset($this->order_header_logo);
            }
        }
        return null;
    }

    public function getOrderFooterLogoUrlAttribute()
    {
        # order_footer_logo_url
        if ($this->order_footer_logo) {
            if (file_exists(public_path($this->order_footer_logo))) {
                return asset($this->order_footer_logo);
            }
        }
        return null;
    }

    public function getInvoiceLetterHeadUrlAttribute()
    {
        # invoice_letter_head_url
        if ($this->invoice_letter_head) {
            if (file_exists(public_path($this->invoice_letter_head))) {
                return asset($this->invoice_letter_head);
            }
        }
        return null;
    }

    public function getAuthorisedSignatureUrlAttribute()
    {
        # authorised_signature_url
        if ($this->authorised_signature) {
            if (file_exists(public_path($this->authorised_signature))) {
                return asset($this->authorised_signature);
            }
        }
        return asset('defualts/authorised_signature.webp');
        return null;
    }

    public function getBrochureUrlAttribute()
    {
        # brochure_url
        if ($this->brochure) {
            if (file_exists(public_path($this->brochure))) {
                return asset($this->brochure);
            }
        }
        return null;
    }

    public function getRateSheetUrlAttribute()
    {
        # rate_sheet_url
        if ($this->rate_sheet && file_exists(public_path($this->rate_sheet))) {
            return asset($this->rate_sheet);
        }
        $rateSheetPath = \App\Helpers\Helper::getRateSheetPath();
        if (file_exists($rateSheetPath)) {
            return route('products.rate-sheet.download');
        }
        return null;
    }

    public function MailSetting()
    {
        return $this->belongsTo(MailSetting::class, 'id', 'company_id');
    }
}
