<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\RolePermission;
use App\Models\RolePermissionsApp;
use Illuminate\Http\Request;
use App\Models\TeamPerson;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RolePermissionsController extends Controller
{
    public function __construct(Request $request) {}

    public function module_list(Request $request)
    {
        try {
            $loginUser = Auth::user();

            if (!$loginUser || !$loginUser?->company_id) {
                return $this->sendError("Unauthorized", [], [], 401);
            }

            $company_id = $loginUser->company_id;

            $team_person = TeamPerson::with(['company', 'team_role'])->find($loginUser->id);

            if (!$team_person) {
                return $this->sendError("Team Person not found.");
            }

            if ($team_person?->team_role_id == 0) {
                return $this->sendError("You cannot assign permission. Please contact the company.");
            }

            $company = Company::where('status', 'active')->find($company_id);

            if (!$company || !$company->app_right) {
                return $this->sendError("Company panel rights not configured.");
            }

            $panel_sub_modules = Helper::getSubMenu(['platform' => 'app', 'id' => ['type' => 'in', 'values' => $company?->app_right]], "active");
            $allowedSubMenus = collect($panel_sub_modules)->pluck('id')->toArray();
            $assignedPermission = RolePermissionsApp::with([
                    'main_menu:id,name',
                    'sub_menu:id,name',
                    'company:id,company_name',
                    'team_person:id,name'
                ])
                ->where('company_id', $team_person?->company_id)
                ->where('team_role_id', $team_person?->team_role?->id)
                ->where('team_id', $team_person?->id);

            if (!empty($allowedSubMenus)) {
                $assignedPermission = $assignedPermission->whereIn('sub_menu_id', $allowedSubMenus);
            }

            $assignedPermission = $assignedPermission->get()
                ->map(function ($item) {
                    return [
                        'main_menu_id'         => $item->main_menu_id,
                        'main_menu_name'       => $item->main_menu?->name ?? '-',
                        'sub_menu_id'          => $item->id,
                        'sub_menu_name'        => $item->sub_menu?->name ?? '-',
                        'company_name'         => $item->company?->company_name ?? '-',
                        'team_person_name'     => $item->team_person?->name ?? '-',
                        'view_flag'            => (string) ($item?->view_flag ?? 0),
                        'add_flag'             => (string) ($item?->add_flag ?? 0),
                        'update_flag'          => (string) ($item?->update_flag ?? 0),
                        'delete_flag'          => (string) ($item?->delete_flag ?? 0),
                        'approval_flag'        => (string) ($item?->approval_flag ?? 0),
                        'all_data_flag'        => (string) ($item?->all_data_flag ?? 0),
                        'personal_data_flag'   => (string) ($item?->personal_data_flag ?? 0),
                    ];
                })
                ->values();

            return $this->sendResponse($assignedPermission, "Modules and permissions fetched successfully.");

        } catch (\Exception $e) {
            return $this->sendError("Exception: " . $e->getMessage());
        }

        return $this->sendError("Unexpected error occurred while fetching modules.");
    }


}
