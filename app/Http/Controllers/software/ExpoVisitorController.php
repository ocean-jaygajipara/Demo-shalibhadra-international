<?php

namespace App\Http\Controllers\software;


use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\ExpoVisitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExpoVisitorExport;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class ExpoVisitorController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();
        $this->modules = [
            'title' => 'Expo Visitor',
            'folder_path' => 'software.modules.visitor-book.expo-visitor',
            'route' => 'expo-visitor',
            'table_name' => (new ExpoVisitor())->getTable(),
            'permisstion_prefix' => 'expo-visitor',
            'module_name' => 'Expo Visitor',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null

        ];
    }

    /*
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']->id ?? null;

        foreach (config('constants.permissions') as $key => $value) {
            $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
        }

        if (!$modules['view_permission']) {
            return $request->ajax()
                ? $this->sendError('Unauthorized', [], [], 403)
                : abort(403, 'Unauthorized');
        }
        $modules['inquiry_view_permission'] =  Gate::check('hasPermission', ['view', 'inquiry']);
        $modules['inquiry_add_permission'] =  Gate::check('hasPermission', ['add', 'inquiry']);
        // dd($modules);
        View::share('modules', $modules);

        try {
            $columns = [

                (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company By', 'className' =>  ''],
                (object)['data' => 'expo.name', 'name' => 'expo_id', 'td_label' => 'Expo Name', 'className' => 'text-wrap'],
                (object)['data' => 'company_name', 'name' => 'company_name', 'td_label' => 'Company Name', 'className' => 'text-wrap'],
                (object)['data' => 'visitor_details', 'name' => 'visitor_details', 'td_label' => 'Visitor Details', 'className' => 'text-wrap', 'orderable' => false, 'searchable' => false],
                (object)['data' => 'created_at', 'name' => 'created_at', 'td_label' => 'Date', 'orderable' => true, 'searchable' => true],
                (object)['data' => 'website', 'name' => 'website', 'td_label' => 'Website', 'className' => 'text-wrap', 'orderable' => true, 'searchable' => true],
                (object)['data' => 'requirement', 'name' => 'requirement', 'td_label' => 'Requirement', 'className' => 'text-wrap'],
                (object)['data' => 'inquiry_detail', 'name' => 'inquiry_id', 'td_label' => 'Inquiry Detail', 'className' => 'text-wrap', 'orderable' => false, 'searchable' => false],
                (object)['data' => 'created_by', 'name' => 'created_by', 'td_label' => 'Created By', 'className' => 'text-wrap', 'orderable' => false, 'searchable' => false],
                (object)['data' => 'action', 'name' => 'action', 'td_label' => 'Action', 'className' => 'text-start w-10', 'orderable' => false, 'searchable' => false],
                // (object)['data' => 'inquiry_id', 'name' => 'inquiry_id', 'td_label' => 'Converted to Inquiry', 'className' => 'text-center'],
            ];
            if ($modules['company_id']) {
                // array_unshift($columns, (object)['data' => "company_name", 'name' => 'company_name', 'td_label' => 'Company Name', 'className' =>  '']);
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'company_id';
                });

                $columns = array_values($columns);
            }

            if (!$modules['inquiry_view_permission'] && !$modules['inquiry_add_permission']) {
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'inquiry_id';
                });

                $columns = array_values($columns);
            }
            View::share('columns', $columns);
            if ($request->ajax()) {
                $data = ExpoVisitor::with(['expo', 'company', 'expo_category', 'expo_inquiry'])
                    ->when($request->filled('filter_company'), fn($q) => $q->where('company_id', $request->filter_company))
                    ->when($request->filled('filter_expo'), fn($q) => $q->where('expo_id', $request->filter_expo))
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (isset($modules['company_id']) && $modules['company_id']) {
                            $query->where('company_id', $modules['company_id']);

                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where(function ($q) use ($loginUserId) {
                                    $q->where('created_by', $loginUserId);
                                });
                            } else {
                                $query->where('created_by', $loginUserId);
                            }
                        }
                    })
                    ->orderBy('id', 'DESC');
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }
                $data = $data->with(['company', 'expo', 'expo_category']);

                return DataTables::of($data)
                    ->addIndexColumn()
                    ->filter(function ($query) use ($request) {
                        if ($request->has('search') && $request->search) {
                            $search = $request->search;
                            $query->where(function ($q) use ($search) {
                                $q->where('person_name', 'like', "%" . $search . "%")
                                    ->orWhere('mobile_no', 'like', "%" . $search . "%")
                                    ->orWhere('company_name', 'like', "%" . $search . "%")
                                    ->orWhere('email', 'like', "%" . $search . "%")
                                    ->orWhere('requirement', 'like', "%" . $search . "%");
                            });
                        }

                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }

                        if ($request->has('filter_expo') && $request->filter_expo) {
                            $query->where('expo_id', $request->filter_expo);
                        }
                    })


                    ->editColumn('created_by', function ($row) use ($modules) {
                        return $row?->team_person?->name ?? '-';
                    })
                    ->editColumn('created_at', function ($row) {
                        return $row->created_at ? $row->created_at->format('d-m-Y') : '-';
                    })

                    ->addColumn('visitor_details', function ($row) {
                        $name = $row->person_name ?: '-';
                        $mobile = $row->mobile_no ?: '-';
                        $email = $row->email ?: '-';

                        $details = '<div class="visitor-details">';
                        $details .= '<i class="fa-solid fa-user text-info me-1"></i>  ' . $name . '<br>';
                        $details .= '<i class="fa-solid fa-phone me-1"></i>  ' . $mobile . '<br>';
                        $details .= '<i class="fa-solid fa-envelope text-danger me-1"></i>  ' . $email;
                        $details .= '</div>';
                        

                        return $details;
                    })


                    ->addColumn('inquiry_detail', function ($row) use ($modules) {
                        $action = "";
                        if ($modules['inquiry_add_permission'] && !$row->inquiry_id) {
                            $action .= '
                            <a href="' . route('convert-to-inquiry', [$row['id']]) . '" class="btn btn-info convert-to-inquiry mx-1" target="_blank">
                                <i class="fa-solid fa-right-to-bracket mt-1 me-1"></i> Convert to Inquiry
                            </a>';
                        } else if ($modules['inquiry_view_permission'] && $row?->expo_inquiry && $row?->expo_inquiry?->id) {
                            $action .= '
                                <a href="' . route('inquiry.show', [Helper::encryptString($row?->expo_inquiry?->id)]) . '" class="dropdown-item convert-to-inquiry mx-1" target="_blank">
                                Converted Inquiry No <b>' . $row?->expo_inquiry?->inquiry_no . '</b>
                                </a>';
                        }
                        return $action;
                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $action = '';
                        $action .= '
                        <div class="dropdown">
                            <button class="btn btn-icon waves-effect waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ti ti-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">';

                        if (!$row?->deleted_at) {
                            // if ($modules['inquiry_add_permission'] && !$row->inquiry_id) {
                            //     $action .= '<li>
                            //     <a href="' . route('convert-to-inquiry', [$row['id']]) . '" class="dropdown-item convert-to-inquiry mx-1" target="_blank">
                            //         <i class="fa-solid fa-right-to-bracket mt-1 me-1"></i> Convert to Inquiry
                            //     </a>
                            //     </li>';
                            // } else if ($modules['inquiry_view_permission'] && $row?->expo_inquiry && $row?->expo_inquiry?->id) {
                            //     $action .= '<li>
                            //         <a href="' . route('inquiry.index') . '" class="dropdown-item convert-to-inquiry mx-1" target="_blank">
                            //         Converted Inquiry Id is <b>' . $row?->expo_inquiry?->inquiry_no . '</b>
                            //         </a>
                            //     </li>';
                            // }
                            if ($modules['delete_permission']) {
                                $action .= '<li>
                                            <a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="dropdown-item deletebutton mx-1"><i class="fa-solid fa-trash mt-1 me-1"></i> Delete</a>
                                        </li>';
                            }
                        } else {
                            $action .= '<li>
                                        <a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="record-restore dropdown-item" data-id="' . $row->id . '" data-bs-original-title="Restore Data">
                                            Restore
                                        </a>
                                    </li>';
                        }

                        $action .= '</ul></div>';
                        return $action;
                    })

                    ->rawColumns(['inquiry_id', 'inquiry_detail', 'visitor_details', 'action'])
                    ->make(true);
            }

            return view($modules['folder_path'] . '.index');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return Redirect::route('software.dashboard')->withErrors($e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
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

        $dataDelete = ExpoVisitor::findOrFail($id);
        $isAjax = ($request->ajax()) ? true : false;

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

            $module = ExpoVisitor::withTrashed()->findOrFail($id);
            $module->restore();

            return Redirect::back()->withSuccess($modules['title'] . ' restored successfully!');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function exportExcel(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $this->authenticateLoginUserDetails?->company_id ?? null;
        $modules['parent_type_id'] = $this->authenticateLoginUserDetails?->parent_type_id ?? null;

        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $hasPermission = (!isset($modules['company_id']) || !$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);

                $modules[$value . '_permission'] = $hasPermission;

                if ($value === 'personal_data') {
                    $modules['personal_data_permission'] = $hasPermission;
                }
                if ($value === 'all_data') {
                    $modules['all_data_permission'] = $hasPermission;
                }
            }
        }

        if (empty($modules['excel_permission'])) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        return Excel::download(new ExpoVisitorExport($request->all(), $this->authenticateLoginUserDetails, $modules, $loginUserId), 'ExpoVisitor-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }
    public function print(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']->id ?? null;

        foreach (config('constants.permissions') as $key => $value) {
            $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
        }

        if (!$modules['print_permission']) {
            return $request->ajax()
                ? $this->sendError('Unauthorized', [], [], 403)
                : abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);
            $moduleName = $modules['module_name'];
            $modules['viewPermission'] = Gate::check('hasPermission', ['view', $moduleName]);
            $modules['addPermission'] = Gate::check('hasPermission', ['add', $moduleName]);
            $modules['editPermission'] = Gate::check('hasPermission', ['update', $moduleName]);
            $modules['deletePermission'] = Gate::check('hasPermission', ['delete', $moduleName]);
            $modules['personalDataPermission'] = Gate::check('hasPermission', ['personal_data', $moduleName]);
            $modules['allDataPermission'] = Gate::check('hasPermission', ['all_data', $moduleName]);

            // Unauthorized check
            if ($request->ajax()) {
                if (!$modules['viewPermission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }
            } elseif (!$modules['viewPermission']) {
                abort(403, 'Unauthorized');
            }
            $query = ExpoVisitor::select('*')
                ->where(function ($q) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check()) {
                        $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                        $q->where('company_id', $teamPersonCompanyId);

                        if ($modules['personalDataPermission'] && !$modules['allDataPermission']) {
                            $q->where('created_by', $loginUserId);
                        }
                    }
                })
                ->with(['company'])
                ->orderBy('id', 'DESC');
            // Match search logic from DataTable
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('person_name', 'like', '%' . $search . '%')
                        ->orWhere('mobile_no', 'like', '%' . $search . '%')
                        ->orWhere('company_name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('requirement', 'like', '%' . $search . '%');
                });
            }

            if ($request->has('filter_company') && $request->filter_company) {
                $query->where('company_id', $request->filter_company);
            }



            if ($request->has('filter_expo') && $request->filter_expo) {
                $query->where('expo_id', $request->filter_expo);
            }


            $visitors = $query->get();

            return view($modules['folder_path'] . '.print', compact('visitors'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function convert_to_inquiry(Request $request, $id)
    {

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        View::share('modules', $modules);

        $modules['inquiry_view'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Inquiry']);
        $modules['inquiry_add'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.add'), 'Inquiry']);
        $modules['inquiry_personal_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.personal_data'), 'Inquiry']);

        if (!$modules['inquiry_view'] && !$modules['inquiry_add']) {
            return $request->ajax()
                ? $this->sendError('Unauthorized', [], [], 403)
                : abort(403, 'Unauthorized');
        }

        try {

            $convertToInquiry = ExpoVisitor::firstWhere(['id' => $id]);
            // 'inquiry_id' => null

            if (!$convertToInquiry) {
                return Redirect::back()->withErrors(['Visitor not found.']);
            } else if ($convertToInquiry && $convertToInquiry?->inquiry_id) {
                return Redirect::back()->withErrors(['All ready converted this visit belogn to ' . $convertToInquiry?->inquiry_id]);
            }

            if ($convertToInquiry) {

                $newRequest = new Request();
                $newRequest['conert_to_inquiry'] = 'true';
                $newRequest['visitor_id'] = $id;
                $newRequest['company_id'] = $convertToInquiry?->company_id;
                // $newRequest['convertToInquiry'] = $convertToInquiry;
                $newRequest['customer_name'] = $convertToInquiry?->company_name;
                $newRequest['contact_person_name'] = $convertToInquiry?->person_name;
                $newRequest['mobile_no'] = $convertToInquiry?->mobile_no;
                $newRequest['email'] = $convertToInquiry?->email;
                $newRequest['requirement_details'] = $convertToInquiry?->requirement;
                $newRequest['inquiry_created_by'] = $convertToInquiry?->created_by;
                $newRequest['inquiry_assigned_to'] = $convertToInquiry?->inquiry_assigned_to;
                $newRequest['website'] = $convertToInquiry?->website;
                $newRequest['status'] = 'active';

                // return $newRequest;

                Session::put('newRequest', $newRequest);
                return Redirect::route('inquiry.create', ['newRequest' => $newRequest]);

                return (new CustomerController())->create($newRequest);
            }
            return $convertToInquiry;
        } catch (\Exception $e) {
            return $e->getMessage();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
}
