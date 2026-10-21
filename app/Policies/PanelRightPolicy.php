<?php

namespace App\Policies;

use App\Helpers\Helper;
use App\Models\MainMenu;
use App\Models\RolePermission;
use App\Models\SubMenu;
use App\Models\TeamPerson;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class PanelRightPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
    }

    public function hasPermission($user, string $action, string $module_name): bool
    {
        //dd($user,$action,$module_name);
        Log::info('');
        $logDebugArray = [
            'date_time' => date("Y-m-d H:i:s"),
            'user' => $user->id,
            'action' => $action,
            'module' => $module_name
        ];

        if (Auth::guard('admin_software')->check()) {
            return true;
        }

        /*
        $mainMenu = MainMenu::whereHas('sub_menu', function($q) use($module_name) {
            $q->where('name', $module_name);
        })->first();
        */
        $mainMenu = SubMenu::where('name', $module_name)->where('status', "active")->first();
        $logDebugArray['submenu'] = $mainMenu;
        if(!$mainMenu){
            // Log::debug('hasPermission called 44', $logDebugArray);
            return false;
        }

        $rolePermissionCheck = RolePermission::query();
        if ($user instanceof TeamPerson) {
            $rolePermissionCheck = $rolePermissionCheck->where('team_role_id', $user->team_role_id);
        }elseif ($user instanceof Customer) {
            $rolePermissionCheck = $rolePermissionCheck->where('customer_role_id', $user->customer_type_id);
        }
        $rolePermissionCheck = $rolePermissionCheck->where('company_id', $user->company_id);
        $rolePermissionCheck = $rolePermissionCheck->where('main_menu_id', $mainMenu?->main_menu_id)->where('sub_menu_id', $mainMenu?->id);
        $rolePermissionCheck = $rolePermissionCheck->where($action . '_flag', 1);
        // dd("L-43", $logDebugArray, $mainMenu);

        $logDebugArray['role_permission_sql'] = Helper::interpolateQuery($rolePermissionCheck->toSql(), $rolePermissionCheck->getBindings());
        $logDebugArray['role_permission'] = $rolePermissionCheck->first();
        // if($module_name == "Sales Status"){
        //     dd("L-43", $mainMenu?->toArray(), $module_name, Helper::interpolateQuery($rolePermissionCheck?->toSql(), $rolePermissionCheck->getBindings()));
        // }
        Log::debug('hasPermission called 55', $logDebugArray);

        $rolePermissionCheck = $rolePermissionCheck->first();
        return $rolePermissionCheck ? true : false;

        /** Old Code */
        $menuMap = config('menu_map');
        $logDebugArray['menuMap'] = $menuMap;

        if (!isset($menuMap[$module_name])) {
            Log::debug('hasPermission called 37', $logDebugArray);
            return false;
        }

        $menu = $menuMap[$module_name];
        $logDebugArray['menu'] = $menu;

        if (!$menu) {
            Log::debug('hasPermission called 45', $logDebugArray);
            return false;
        }

        Log::debug('hasPermission called', [
            'date_time' => date("Y-m-d H:i:s"),
            'user' => $user->id,
            'action' => $action,
            'module' => $module_name,
            'menuMap' => $menuMap,
            'menu' => $menu,
        ]);

        $rolePermissionCheck = RolePermission::query();
        if ($user instanceof TeamPerson) {
            $rolePermissionCheck = $rolePermissionCheck->where('team_role_id', $user->team_role_id);
        }elseif ($user instanceof Customer) {
            $rolePermissionCheck = $rolePermissionCheck->where('customer_role_id', $user->customer_type_id);
        }
        $rolePermissionCheck = $rolePermissionCheck->where('main_menu_id', $menu['main_menu_id'])
            ->where('sub_menu_id', $menu['sub_menu_id'])
            ->where('company_id', $user->company_id)
            ->where($action . '_flag', 1)
            ->first();

        return $rolePermissionCheck ? true : false;
    }
}
