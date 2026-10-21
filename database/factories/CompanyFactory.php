<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PlanMaster;
use App\Models\MasterCountry;
use App\Models\MasterState;
use App\Models\MasterCity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        static $counter = 1;

        $country = MasterCountry::inRandomOrder()->first();
        $state = MasterState::where('country_id', $country->id)->inRandomOrder()->first();
        $city = MasterCity::where('state_id', $state->id)->inRandomOrder()->first();
        $plan = PlanMaster::inRandomOrder()->first();

        $randomColor = fn() => '#' . $this->faker->unique()->hexColor;
        $gstNo = strtoupper('22' . $this->faker->bothify('?????##?##') . 'ZV');

        if ($counter === 1) {
            $companyName = 'RM Engineering';
            $personName = 'Kiran bhai';
            $appKey = Str::upper(Str::random(rand(5, 10)));
        } elseif ($counter === 2) {
            $companyName = 'Jeel Flow Pvt Ltd';
            $personName = 'Bhavesh bhai';
            $appKey = Str::upper(Str::random(rand(5, 10)));
        } elseif ($counter === 3) {
            $companyName = 'Ocean Infotech';
            $personName = 'Sandip Gajera';
            $appKey = 'Ocean@2025';
        } else {
            $companyName = 'Manufacturing ' . $this->faker->company;
            $personName = $this->faker->name;
            $appKey = Str::upper(Str::random(rand(5, 10)));
        }

        $counter++;

        return [
            'gst_no' => $gstNo,
            'company_name' => $companyName,
            'person_name' => $personName,
            'whatsapp_number' => $this->faker->numerify('91########'),
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'),
            'sp' => 'SP001',
            'otp' => rand(1000, 9999),
            'pan_card' => strtoupper(Str::random(10)),
            'bank_details' => $this->faker->iban,
            'address' => $this->faker->address,
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'plan_id' => $plan->id,
            'app_right' => implode(',', ['dashboard', 'reports']),
            'panel_right' => implode(',', ['users', 'settings']),
            'plan_from' => now(),
            'plan_to' => now()->addYear(),
            'app_key' => $appKey,
            'panel_url' => 'https://panel.example.com',
            'database_name' => 'db_' . Str::random(6),
            'database_user' => 'dbuser',
            'database_password' => 'dbpass',
            'max_team_user_count' => 10,
            'max_customer_count' => 100,
            'max_inquiry_count' => 500,
            'order_view_format' => 'standard',
            'quotation_view_format' => 'standard',
            'dispatch_view_format' => 'standard',
            'order_prefix' => 'ORD',
            'quotation_prefix' => 'QTN',
            'dispatch_prefix' => 'DSP',
            'order_title' => 'Order Form',
            'quotation_title' => 'Quotation',
            'dispatch_title' => 'Dispatch Slip',
            'order_view_color' => $randomColor(),
            'quotation_view_color' => $randomColor(),
            'dispatch_view_color' => $randomColor(),
            'stock_manage_by' => 'qty',
            'company_logo' => null,
            'company_favicon' => null,
            'white_labeling_logo' => null,
            'app_logo' => null,
            'order_header_logo' => null,
            'order_footer_logo' => null,
            'is_copyright_view' => 1,
            'default_password' => bcrypt('defaultpass'),
            'reset_password' => null,
            'phonecode' => '+91',
            'color_bg_primary_1' => $randomColor(),
            'color_bg_primary_2' => $randomColor(),
            'color_bg_primary_3' => $randomColor(),
            'color_text_primary_1' => $randomColor(),
            'color_text_primary_2' => $randomColor(),
            'color_text_primary_3' => $randomColor(),
            'panel_sidebar_background_color' => $randomColor(),
            'panel_sidebar_text_color' => $randomColor(),
            'panel_text_color' => $randomColor(),
            'status_bar_color' => $randomColor(),
            'title_name_color' => $randomColor(),
            'all_icon_color' => $randomColor(),
            'edittext_title_color' => $randomColor(),
            'screen_background_light_color' => $randomColor(),
            'screen_background_dark_color' => $randomColor(),
            'all_screen_header_color' => $randomColor(),
            'all_screen_back_arrow_background_color' => $randomColor(),
            'all_screen_back_arrow_color' => $randomColor(),
            'data_list_border_color' => $randomColor(),
            'login_text_color_1' => $randomColor(),
            'login_text_color_2' => $randomColor(),
            'background_shape_1' => $randomColor(),
            'background_shape_2' => $randomColor(),
            'background_shape_3' => $randomColor(),
            'extra_color_1' => $randomColor(),
            'extra_color_2' => $randomColor(),
            'extra_color_3' => $randomColor(),
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
        ];
    }
}
