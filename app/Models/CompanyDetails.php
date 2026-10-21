<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class CompanyDetails extends Model
{
    use SoftDeletes;

    public static $duplication_check_type = [
        "variant" => "Variant Wise",
        "product" => "Product Wise",
    ];
    protected $table = 'company_details';
    protected $fillable = [
        'company_id',
        'how_assign_inquiry',
        'duplication_check_type',
        'color_bg_primary_1',
        'color_bg_primary_2',
        'color_bg_primary_3',
        'color_text_primary_1',
        'color_text_primary_2',
        'color_text_primary_3',
        'panel_sidebar_background_color',
        'panel_sidebar_text_color',
        'panel_text_color',
        'status_bar_color',
        'title_name_color',
        'all_icon_color',
        'edittext_title_color',
        'screen_background_light_color',
        'screen_background_dark_color',
        'all_screen_header_color',
        'all_screen_back_arrow_background_color',
        'all_screen_back_arrow_color',
        'data_list_border_color',
        'login_text_color_1',
        'login_text_color_2',
        'background_shape_1',
        'background_shape_2',
        'background_shape_3',
        'extra_color_1',
        'extra_color_2',
        'extra_color_3',
        'per_minutes_request_current_location',
        'is_quantity_decimal',
        'inward_title',
        'inward_prefix',
        'inward_color',
        'job_work_order_title',
        'job_work_order_prefix',
        'job_work_order_color',
        'job_work_issue_title',
        'job_work_issue_prefix',
        'job_work_issue_color',
        'job_work_receive_title',
        'job_work_receive_prefix',
        'job_work_receive_color',
        'qc_inspection_title',
        'qc_inspection_prefix',
        'qc_inspection_color',
        'invoice_title',
        'invoice_prefix',
        'invoice_color',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    protected function generateRandomColor()
    {
        return '#' . substr(sha1(rand()), 0, 6); // Generates a random hex color code
    }

    public function setColorBgPrimary1Attribute($value)
    {
        $this->attributes['color_bg_primary_1'] = $value ?: $this->generateRandomColor();
    }

    public function setColorBgPrimary2Attribute($value)
    {
        $this->attributes['color_bg_primary_2'] = $value ?: $this->generateRandomColor();
    }

    public function setColorBgPrimary3Attribute($value)
    {
        $this->attributes['color_bg_primary_3'] = $value ?: $this->generateRandomColor();
    }
}
