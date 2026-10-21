<?php

namespace App\Http\Controllers\software;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Models\Vendor;
use App\Exports\VendorExport;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

use App\Http\Requests\VendorRequest;
use Illuminate\Support\Facades\Gate;


class VendorController extends Controller
{
    public $modules = [];
    public function __construct()
    {
        parent::__construct();
        $this->modules = [
            'title' => 'Vendor',
            'folder_path' => 'software.modules.purchase_management.vendor',
            'route' => 'vendors',
            'table_name' => (new Vendor())->getTable(),
            'permisstion_prefix' => 'vendors',
            'module_name' => 'Vendor',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null
        ];
    }

    public function index(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['view_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);

            $columns = [
                (object)['data' => "company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                (object)['data' => "vendor_details", 'name' => 'vendor_details', 'td_label' => 'Vendor Detail', 'className' =>  ''],
                (object)['data' => "contact_details", 'name' => 'contact_details', 'td_label' => 'Contact Detail', 'className' =>  ''],
                (object)['data' => "vendor_code", 'name' => 'vendor_code', 'td_label' => 'Vendor Code', 'className' =>  ''],
                (object)['data' => "country_name", 'name' => 'country_name', 'td_label' => 'Country', 'className' =>  ''],
                (object)['data' => "state_name", 'name' => 'state_name', 'td_label' => 'State', 'className' =>  ''],
                (object)['data' => "city_name", 'name' => 'city_name', 'td_label' => 'City', 'className' =>  ''],
                (object)['data' => "area_name", 'name' => 'area_name', 'td_label' => 'Area', 'className' =>  ''],
                (object)['data' => "status", 'name' => 'status', 'td_label' => 'Status', 'className' =>  ''],
                // (object)['data' => "team_person_name", 'name' => 'team_person_name', 'td_label' => 'Team Person Name', 'className' =>  ''],
                (object)['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' =>  'w-10 text-start'],
            ];
            if ($modules['company_id']) {
                // array_unshift($columns, (object)['data' => "company_name", 'name' => 'company_name', 'td_label' => 'Company Name', 'className' =>  '']);
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'company_id';
                });

                $columns = array_values($columns);
            }
            // dd(!$modules['company_id'], $columns);
            View::share("columns", $columns);


            if ($request->ajax()) {
                if (!$modules['view_permission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }
                $data = Vendor::select('*');
                $data = $data->where(function ($query) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check()) {
                        $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                        $query->where('company_id', $teamPersonCompanyId);

                        if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                            $query->where('created_by', $loginUserId);
                        }
                    }
                });
                $data = $data->whereHas('company', function ($query) {
                    $query->where('status', 'active');
                });
                $data = $data->with(['company', 'country', 'state', 'city', 'area', 'team_person', 'admin_person']);
                $data = $data->orderBy('id', 'desc');
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }

                $returnData = Datatables::of($data)
                    ->filter(function ($query) use ($request) {
                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }
                        if ($request->has('filter_country') && $request->filter_country) {
                            $query->where('country_id', $request->filter_country);
                        }
                        if ($request->has('filter_state') && $request->filter_state) {
                            $query->where('state_id', $request->filter_state);
                        }
                        if ($request->has('filter_city') && $request->filter_city) {
                            $query->where('city_id', $request->filter_city);
                        }
                        if ($request->has('status') && $request->status !== null && $request->status !== 'all') {
                            $query->where('status', $request->status);
                        }
                        if ($request->has('search')) {
                            $search = $request->search;

                            $query->where(function ($q) use ($search) {
                                $q->where('vendor_name', 'like', "%{$search}%")
                                    //->orWhere('gst_number', 'like', "%{$search}%")
                                    ->orWhere('vendor_code', 'like', "%{$search}%");
                            });
                        }
                    })
                    ->addColumn('team_person_name', function ($row) {
                        if ($row->created_type == 'admin') {
                            return $row?->admin_person?->name;
                        } else {
                            return $row?->team_person?->name;
                        }
                    })
                    ->addColumn('contact_details', function ($row) {
                        $mobile = $row->mobile_number ? '<a href="tel:' . $row->mobile_number . '"><i class="fas fa-phone text-success"></i> ' . $row->mobile_number . '</a>' : '<i class="fas fa-phone text-muted"></i> -';
                        $whatsapp = $row->whatsapp_number ? '<a href="https://wa.me/' . $row->whatsapp_number . '" target="_blank"><i class="fab fa-whatsapp text-success"></i> ' . $row->whatsapp_number . '</a>' : '<i class="fab fa-whatsapp text-muted"></i> -';
                        $email = $row->email_id ? '<a href="mailto:' . $row->email_id . '"><i class="fas fa-envelope text-danger"></i> ' . $row->email_id . '</a>' : '<i class="fas fa-envelope text-muted"></i> -';

                        return '
        <div>
            ' . $mobile . '<br>
            ' . $whatsapp . '<br>
            ' . $email . '
        </div>';
                    })

                    ->addColumn('vendor_details', function ($row) {
                        return '
                            <div>
                                <strong><i class="fas fa-user text-primary"></i> ' . ($row?->vendor_name ?? '-') .    '</strong><br>
                                <strong><i class="fas fa-file-invoice text-success"></i></strong> ' . ($row?->gst_number ?? '-') . '
                            </div>';
                    })
                    ->addColumn('company_name', function ($row) {
                        return $row?->company?->company_name;
                    })
                    ->addColumn('country_name', function ($row) {
                        return $row?->country?->name;
                    })
                    ->addColumn('state_name', function ($row) {
                        return $row?->state?->name;
                    })
                    ->addColumn('city_name', function ($row) {
                        return $row?->city?->name;
                    })
                    ->addColumn('area_name', function ($row) {
                        return $row?->area?->area_name ?? '-';
                    })
                    ->editColumn('status', function ($row) use ($modules) {
                        $btn = '';
                        $dropdown = "";
                        $dropdown .= '<ul class="dropdown-menu" style="">';

                        if ($row->status == "active") {
                            $btn .= '<button type="button" class="btn btn-success btn-sm  dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item waves-effect btn-label-danger update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="inactive">Inactive</a></li>';
                        } elseif ($row->status == "inactive") {
                            $btn .= '<button type="button" class="btn btn-danger btn-sm  dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item waves-effect btn-label-success update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="active">Active</a></li>';
                        } else {
                            return $btn;
                        }
                        $dropdown .= '</ul>';
                        $btn .= $dropdown;
                        return $btn;
                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $btn = '';
                        if (!$row?->deleted_at) {
                            if ($modules['update_permission'] == true) {
                                $btn .= '<a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="btn btn-light btn-icon mx-1"><i class="fa-solid fa-pen-to-square"></i></a>';
                            }
                            if ($modules['delete_permission'] == true) {
                                $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="btn btn-danger btn-icon deletebutton mx-1"><i class="fa-solid fa-trash"></i></i></a>';
                            }
                        } else {
                            if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                                $btn .= '<a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="btn btn-light mx-1 record-restore" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Restore Data"><i class="ti ti-history"></i> Restore</a>';
                            }
                        }
                        if ($btn == '') {
                            $btn = '-';
                        }
                        return $btn;
                    })
                    ->addColumn('action1', function ($row) use ($modules) {
                        $action = '';
                        $action .= '
                            <div class="">
                                <button class="btn btn-icon waves-effect waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></button>
                                <ul class="dropdown-menu">';

                        if (!$row?->deleted_at) {
                            if ($modules['update_permission'] == true && empty($row->sales_order_id)) {
                                $action .= '<li>
                                            <a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="dropdown-item mx-1"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                        </li>';
                            }

                            if ($modules['delete_permission'] == true) {
                                $action .= '<li>
                                            <a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="dropdown-item deletebutton mx-1"><i class="fa-solid fa-trash"></i> Delete</a>
                                        </li>';
                            }
                        } else {
                            if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                                $action .= '<li>
                                        <a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="record-restore dropdown-item" data-id="' . $row->id . '" data-bs-original-title="Restore Data">
                                            Restore
                                        </a>
                                    </li>';
                            }
                        }
                        $action .= '</ul>
                                </div>
                            ';
                        return $action;
                    })
                    ->rawColumns(['status', 'action', 'contact_details', 'vendor_details'])
                    ->make(true);
                if (!$modules['view_permission']) {
                    abort(403, 'Unauthorized');
                }
                return $returnData;
            }

            return view($modules['folder_path'] . '.index');
        } catch (\Exception $e) {
            return Redirect::route('software.dashboard')->withErrors($e->getMessage());
        }
    }

    public function create()
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['add_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return $e->getMessage();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function store(VendorRequest $request)
    {
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['add_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);
        $validated = $request->validated();

        try {
            if ($modules['currentGuard'] == 'admin_software') {
                $validated['created_type'] = 'admin';
            } else {
                $validated['created_type'] = 'team';
            }
            $validated['created_by'] = $loginUserId;
            $vendor = Vendor::create($validated);
            $lastInsertedId = $vendor->id;

            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function destroy(Request $request, string $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['delete_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        $isAjax = ($request->ajax()) ? true : false;
        $dataDelete = Vendor::findOrFail($id);

        try {
            if ($dataDelete) {
                $validated['deleted_by'] = $loginUserId;
                $dataDelete->update($validated);

                if ($dataDelete->delete()) {
                    if ($isAjax) {
                        return $this->sendResponse([], $modules['title'] . ' delete successfully');
                    }
                    return true;
                }
            }
            if ($isAjax) {
                return $this->sendResponse([], "something went wrong please try again later");
            }
            return false;
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function status_update(Request $request)
    {
        $isAjax = ($request->ajax()) ? true : false;

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['update_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        $validator = Validator::make($request->all(), [
            'id' => ['required', Rule::exists($modules['table_name'], 'id')],
            'update_status' => ['required', 'in:active,inactive']
        ]);

        if ($validator->fails() && $isAjax) {
            return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
        }

        try {
            $vendor = Vendor::withTrashed()->findOrFail($request?->id);
            if ($vendor) {
                $vendor->status = $request->update_status;
                $vendor->save();
                if ($isAjax) {
                    return $this->sendResponse($vendor, $modules['title'] . ' status update successfully.');
                }
                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' status update successfully.');
            }
            if ($isAjax) {
                return $this->sendError('something went wrong please try again later');
            }
            return Redirect::back()->withErrors('something went wrong please try again later')->withInput();
        } catch (\Exception $e) {
            if ($isAjax) {
                return $this->sendError($e->getMessage(), $e->getMessage(), [], 401);
            }
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function restore($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['restore_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {
            $vendor = Vendor::withTrashed()->findOrFail($id);
            $vendor->restore();
            return Redirect::back()->withSuccess($modules['title'] . ' restored successfully!');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function edit(string $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['update_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);
            $edit = Vendor::findOrFail($id);
            View::share('edit', $edit);
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function update(VendorRequest $request, string $id)
    {
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['update_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        $request['id'] = $id;

        $validated = $request->validated();
        try {
            if ($modules['currentGuard'] == 'admin_software') {
                $validated['updated_type'] = 'admin';
            } else {
                $validated['updated_type'] = 'team';
            }
            $validated['updated_by'] = $loginUserId;
            $updateData = Vendor::findOrFail($id);

            if ($updateData) {
                unset($validated['id']);
                $updateData->update($validated);
                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' update successfully');
            }
            return Redirect::back()->withErrors('something went wrong please try again later')->withInput();
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


    public function exportExcel(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['excel_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        return Excel::download(new VendorExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'Vendor-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }

    public function print(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $company_id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['print_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);

            $data = Vendor::select('*');
            $data = $data->where(function ($query) use ($modules, $loginUserId) {
                if (Auth::guard('team_person')->check()) {
                    $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                    $query->where('company_id', $teamPersonCompanyId);

                    if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                        $query->where('created_by', $loginUserId);
                    }
                }
            });
            $data = $data->with(['company', 'country', 'state', 'city', 'area', 'team_person', 'admin_person']);

            if ($request->has('company') && $request->company) {
                $data->where('company_id', $request->company);
            }

            if ($request->has('filter_country') && $request->filter_country) {
                $data->where('country_id', $request->filter_country);
            }
            if ($request->has('filter_state') && $request->filter_state) {
                $data->where('state_id', $request->filter_state);
            }
            if ($request->has('filter_city') && $request->filter_city) {
                $data->where('city_id', $request->filter_city);
            }
            if ($request->has('status') && $request->status !== null && $request->status !== 'all') {
                $data->where('status', $request->status);
            }
            if ($request->has('search')) {
                $search = $request->search;

                $data->where(function ($q) use ($search) {
                    $q->where('vendor_name', 'like', "%{$search}%")
                        ->orWhere('vendor_code', 'like', "%{$search}%");
                });
            }
            $vendors = $data->orderBy('id', 'desc')->get();

            return view($modules['folder_path'] . '.print', compact('vendors', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
}
