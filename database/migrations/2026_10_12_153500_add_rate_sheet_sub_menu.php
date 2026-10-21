<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\SubMenu;
use App\Models\Company;
use App\Models\PlanMaster;
use App\Models\TeamRole;
use App\Models\RolePermission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $subMenu = SubMenu::where('route_name', 'products.rate-sheet.index')->first();
        if (!$subMenu) {
            $subMenu = SubMenu::create([
                'main_menu_id' => 8, // Product
                'name' => 'Rate Sheet',
                'route_name' => 'products.rate-sheet.index',
                'url' => 'software/products/rate-sheet',
                'order_by' => 5, // Directly next to Product
                'platform' => 'panel',
                'status' => 'active',
                'created_by' => 1,
            ]);
        } else {
            $subMenu->update([
                'main_menu_id' => 8,
                'name' => 'Rate Sheet',
                'url' => 'software/products/rate-sheet',
                'status' => 'active',
            ]);
        }

        $subMenuId = (string) $subMenu->id;

        // 1. Add to Companies panel_right
        $companies = Company::all();
        foreach ($companies as $comp) {
            $rights = is_array($comp->panel_right) ? $comp->panel_right : (json_decode($comp->panel_right, true) ?: []);
            if (!in_array($subMenuId, $rights)) {
                $rights[] = $subMenuId;
                $comp->panel_right = array_values(array_unique($rights));
                $comp->save();
            }
        }

        // 2. Add to PlanMaster panel_right
        $plans = PlanMaster::all();
        foreach ($plans as $plan) {
            $rights = is_array($plan->panel_right) ? $plan->panel_right : (json_decode($plan->panel_right, true) ?: []);
            if (!in_array($subMenuId, $rights)) {
                $rights[] = $subMenuId;
                $plan->panel_right = array_values(array_unique($rights));
                $plan->save();
            }
        }

        // 3. Grant view & action permissions to team roles
        $roles = TeamRole::all();
        foreach ($roles as $role) {
            RolePermission::firstOrCreate([
                'company_id' => $role->company_id,
                'team_role_id' => $role->id,
                'main_menu_id' => 8,
                'sub_menu_id' => $subMenuId,
            ], [
                'view_flag' => 1,
                'add_flag' => 1,
                'update_flag' => 1,
                'delete_flag' => 1,
                'print_flag' => 1,
                'excel_flag' => 1,
                'approval_flag' => 1,
                'all_data_flag' => 1,
                'personal_data_flag' => 0,
                'status' => 'active',
                'created_by' => 1,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $subMenu = SubMenu::where('route_name', 'products.rate-sheet.index')->first();
        if ($subMenu) {
            $subMenuId = (string) $subMenu->id;
            RolePermission::where('sub_menu_id', $subMenuId)->delete();

            $companies = Company::all();
            foreach ($companies as $comp) {
                $rights = is_array($comp->panel_right) ? $comp->panel_right : (json_decode($comp->panel_right, true) ?: []);
                $rights = array_diff($rights, [$subMenuId]);
                $comp->panel_right = array_values($rights);
                $comp->save();
            }

            $plans = PlanMaster::all();
            foreach ($plans as $plan) {
                $rights = is_array($plan->panel_right) ? $plan->panel_right : (json_decode($plan->panel_right, true) ?: []);
                $rights = array_diff($rights, [$subMenuId]);
                $plan->panel_right = array_values($rights);
                $plan->save();
            }

            $subMenu->forceDelete();
        }
    }
};
