<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\CompanyDetails;

class CompanyDetailsAddSeeder extends Seeder
{
    public function run(): void
    {

        $fieldsToTransfer = [
            'how_assign_inquiry',
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
        ];

        $companies = Company::all();

        foreach ($companies as $company) {
            $detailData = [];

            foreach ($fieldsToTransfer as $field) {
                $detailData[$field] = $company->$field ?? null;
            }

            CompanyDetails::updateOrCreate(
                ['company_id' => $company->id],
                $detailData
            );
        }

        echo "CompanyDetailsAddSeeder executed: Fields copied to company_details.\n";
    }
}
