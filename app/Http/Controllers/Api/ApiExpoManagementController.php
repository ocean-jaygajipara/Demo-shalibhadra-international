<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;

use App\Models\Company;
use App\Models\ExpoCategory;
use App\Models\ExpoManagement;
use App\Models\RolePermissionsApp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApiExpoManagementController extends Controller
{
    public $modules = [];
    public function __construct(Request $request)
    {
        parent::__construct();

        $this->modules = [
            'table_name' => (new ExpoManagement())->getTable(),
            'module_name' => 'Expo',
            'module_exp_visitor' => 'Visitor',
        ];
    }

    /**
     * Expo Management List
     * company_id is nullable when token is exist
     */
    public function expo_management_list(Request $request)
    {
        $modules = $this->modules;
        try {
            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            $company_id = $request?->company_id ?? $loginUser?->company_id;
            $request['company_id'] = $company_id;

            $modules['authLoginUserDetail'] = $loginUser ?? null;
            $modules['company_id'] = $request['company_id'] ?? null;
            $modules['parent_type_id'] = $loginUser?->parent_type_id ?? null;

            foreach (config('constants.app_permissions') as $key => $value) {
                // Helper::checkAppPermission("app", (new RolePermissionsApp())->getTable(), $modules['company_id'], $loginUser?->id, $modules['module_name'], $value.'_flag');
                $modules[$value . '_permission'] = Helper::checkAppPermission("app", (new RolePermissionsApp())->getTable(), $modules['company_id'], $loginUser?->id, $modules['module_name'], $value . '_flag');
            }

            // return $modules;
            if (!$modules['view_permission']) {
                return $this->sendError('You do not have permission to access the '.$modules['module_name'].' module.', [], [], 403);
            }

            // return $modules;
            $validator =  Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('status', 'active');
                        }),
                ],
                // 'company_id' => [
                //     'required',
                //     Rule::exists((new Company())->getTable(), 'id')
                // ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $loginUserId = $loginUser?->id;

            /* Pagination */
            $pagination = (isset($request?->page)) ? true : false;
            $perPage = (isset($request?->per_page)) ? $request?->per_page : env("API_PER_PAGE");
            $page = (isset($request?->page)) ? $request?->page : 1;

            $module = ExpoManagement::query();
            $module = $module->where('company_id', $company_id);

            if (isset($modules['personal_data_permission']) && isset($modules['personal_data_permission'])) {
                if ($modules['all_data_permission']) {
                } else if ($modules['all_data_permission'] == false && $modules['personal_data_permission']) {
                    $module = $module->where('created_by', $loginUserId);
                } else {
                    $module = $module->where('created_by', $loginUserId);
                }
            }

            if ($request?->with_visitor_count) {
                $modules['module_exp_visitor_all_data_permission'] = Helper::checkAppPermission("app", (new RolePermissionsApp())->getTable(), $modules['company_id'], $loginUser?->id, $modules['module_exp_visitor'], 'all_data_flag');
                $modules['module_exp_visitor_personal_data_permission'] = Helper::checkAppPermission("app", (new RolePermissionsApp())->getTable(), $modules['company_id'], $loginUser?->id, $modules['module_exp_visitor'], 'personal_data_flag');

                if($modules['module_exp_visitor_all_data_permission']){
                    $module = $module->withCount('has_visitors as expo_visitors_count');
                }else if ($modules['module_exp_visitor_all_data_permission'] == false && $modules['module_exp_visitor_personal_data_permission']) {
                    $module->withCount(['has_visitors as expo_visitors_count' => function ($query) use ($loginUserId) {
                        $query->where('created_by', $loginUserId);
                    }]);
                } else {
                    return $this->sendError('You do not have permission to access the '.$modules['module_exp_visitor'].' module.', [], [], 403);
                }
                // dd("L-107", Helper::interpolateQuery($module?->toSql(), $module?->getBindings()), $module?->get()->toArray(), $modules);
            }
            // $module = $module->with(['company']);
            // $module = $module->where('created_by', $loginUserId);

            if ($request?->filter_by_year) {
                $module = $module->whereYear('start_date', $request?->filter_by_year);
            }
            if ($request?->start_date && !$request?->end_date) {
                $start_date = Carbon::parse($request->start_date)->toDateString();
                $module = $module->whereDate('start_date', '>=', $start_date);
            } else if ($request?->start_date && $request?->end_date) {
                $start_date = Carbon::parse($request->start_date)->toDateString();
                $end_date = Carbon::parse($request->end_date)->toDateString();

                $module = $module->where(function ($sub) use ($start_date, $end_date) {
                    $sub->whereDate('start_date', '>=', $start_date)
                        ->whereDate('end_date', '<=', $end_date);
                });
            }

            if ($request?->filter_by_expo_category_id) {
                $module = $module->where('expo_category_id', $request?->filter_by_expo_category_id);
            }

            if ($request?->search && !empty($request?->search)) {
                $module = $module->where('name', "%" . $request?->search . "%");
            }

            if ($request?->filter_by_status) {
                if (in_array($request?->filter_by_status, ['active', 'inactive'])) {
                    $module = $module->where('status', $request?->filter_by_status);
                }
            }
            $module = $module->orderBy('id', 'DESC');

            // dd('tax-list 81', Helper::interpolateQuery($module->toSql(), $module->getBindings()), $module->first(), $loginUser->toArray());

            $tempResponseData = [];
            if ($pagination) {
                $module = $module->paginate($perPage);

                $tempResponseData['total'] = "" . $module->total();
                $tempResponseData['perPage'] = $perPage;
                $tempResponseData['currentPage'] = $page;
                $tempResponseData['totalPage'] = "" . $module->lastPage();
                $tempResponseData['items'] = (object)$module->items();
                $tempResponseData['items'] = collect($tempResponseData['items']);
            } else {
                $module = $module->get();
                $tempResponseData['total'] = $module->count() . "";
                $tempResponseData['items'] = $module;
            }

            if (isset($tempResponseData['items'])) {
                $tempResponseData['items'] = $tempResponseData['items']->map(function ($record) {
                    $temp = [];
                    // $temp = $record;

                    $temp['id'] = $record?->id . "" ?? '';

                    $temp['company_id'] = $record?->company_id . "" ?? '';
                    $temp['company_name'] = $record?->company?->company_name . "" ?? '';
                    $temp['expo_category_id'] = $record?->expo_category_id . "" ?? '';
                    $temp['expo_category_name'] = $record?->expo_category?->name . "" ?? '';
                    $temp['name'] = $record?->name . "" ?? '';
                    $temp['attachment'] = $record?->attachment . "" ?? '';
                    $temp['attachment_url'] = $record?->attachment_url . "" ?? '';
                    $temp['start_date'] = $record?->start_date . "" ?? '';
                    $temp['end_date'] = $record?->end_date . "" ?? '';
                    $temp['expo_url'] = $record?->expo_url . "" ?? '';
                    $temp['location'] = $record?->location . "" ?? '';
                    $temp['status'] = $record?->status . "" ?? '';
                    $temp['platform'] = $record?->platform . "" ?? '';
                    $temp['created_at'] = $record?->created_at . "" ?? '';
                    $temp['updated_at'] = $record?->updated_at . "" ?? '';
                    $temp['expo_visitors_count'] = $record?->expo_visitors_count . "" ?? '';

                    return $temp;
                });

                return $this->sendResponse($tempResponseData, "Expo Management list by company.");
            }
            return $this->sendError("Something want to wrong in data fetching.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }


    /**
     * Expo Management Add / Edit
     * company_id is nullable when token is exist
     */
    public function expo_management_add_edit(Request $request)
    {
        $modules = $this->modules;

        try {
            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            if ($loginUser?->company_id) {
                $request['company_id'] = $loginUser?->company_id;
            }

            $company_id = $request?->company_id ?? $loginUser?->company_id;
            $request['company_id'] = $company_id;

            // return $request->all();
            $validator =  Validator::make($request->all(), [
                'edit_id' => [
                    'nullable',
                    Rule::exists((new ExpoManagement())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        }),
                ],
                'platform' => ['required', 'in:web,android,ios'],
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
                'expo_category_id' => ['required', Rule::exists((new ExpoCategory())->getTable(), 'id')],
                'name' => [
                    'required',
                    Rule::unique((new ExpoManagement())->getTable())
                        ->ignore($request->edit_id) // ✅ Ignore current record during update
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        }),
                ],
                'start_date' => [
                    'required',
                    'date',
                    // 'after_or_equal:' . Carbon::now()->subWeek()->startOfDay()->toDateString(),
                ],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
                'expo_url' => ['nullable', 'url'],
                'location' => ['nullable', 'string', 'max:255'],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $loginUserId = $loginUser?->id;
            // return Carbon::now()->subWeek()->startOfDay();
            // return $request->all();

            $input = $request->except(['edit_id']);
            $returnMessage = "Add Expo detail successfully.";

            if ($request->hasFile('attachment')) {
                // return $image_name = Helper::make_slug('leave-application ' . (string)$loginUser?->id. ' '. (string)$loginUser?->name . ' ' . date('Ymd-His'));
                $image_name = Helper::make_slug(date('Ymd-His') . ' ' . (string)$loginUser?->id . ' ' . (string)$loginUser?->name);

                $file = $request->file('attachment');

                $extenstion = $file->getClientOriginalExtension();

                $filename = $image_name . '.' . $extenstion;

                $sub_folder_path = Helper::fileUploadPath($loginUser, ExpoManagement::$folderPath);
                $uploadedPath = public_path($sub_folder_path);
                // return $uploadedPath;
                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $sub_folder_path . $filename;
                    if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                        $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $image_name, 50, true);
                        if ($webP) {
                            $uploadedImage = $sub_folder_path . $webP;
                            $extenstion = "webp";
                        }
                    }
                    // $filename = $uploadedImage;
                    // $image_path = $sub_folder_path . '/' . $filename;
                }
                $input['attachment'] = $uploadedImage;
            }

            if ($request?->edit_id) {
                $input['updated_type'] = 'Team';
                $input['updated_by'] = $loginUserId;
                $returnMessage = "Update expo detail successfully.";
                $modelAddEdit = ExpoManagement::where('id', $request?->edit_id);
                $modelAddEdit = $modelAddEdit->where('company_id', $company_id);
                $modelAddEdit = $modelAddEdit->first();
                $modelAddEdit->update($input);
            } else {
                $input['created_by'] = $loginUserId;
                $input['created_type'] = 'Team';
                // return $input;
                $modelAddEdit = ExpoManagement::create($input);
            }

            return $this->sendResponse($modelAddEdit, $returnMessage);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }


    /**
     * Expo Management delete
     * company_id is nullable when token is exist
     * delete_id
     */
    public function expo_management_delete(Request $request)
    {
        try {

            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id && !$request?->login_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            if ($loginUser?->company_id) {
                $request['company_id'] = $loginUser?->company_id;
            }
            $company_id = $request?->company_id ?? $loginUser?->company_id;

            $validator =  Validator::make($request->all(), [
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
                'delete_id' => ['required', Rule::exists((new ExpoManagement())->getTable(), 'id')],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }


            $module = ExpoManagement::where('company_id', $company_id);
            $module = $module->where('id', $request?->delete_id);
            $module = $module->orderBy('id', 'DESC');
            $module = $module->first();

            if ($module) {
                if ($module->delete()) {
                    return $this->sendResponse($module, "Your Expo detail Delete Successfully.");
                }
                return $this->sendError("Your Expo detail found, but can not deleted.");
            }

            return $this->sendError("Your Expo detail not found.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }
}
