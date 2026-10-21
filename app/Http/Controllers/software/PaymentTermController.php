<?php

namespace App\Http\Controllers\software;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentTermRequest;
use App\Models\PaymentTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class PaymentTermController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Payment Terms',
            'folder_path' => 'software.modules.customer.payment-terms',
            'route' => 'payment-terms',
            'table_name' => (new PaymentTerm())->getTable(),
            'permisstion_prefix' => 'payment-terms',
            'module_name' => 'Payment Terms',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null
        ];
    }

    /**
     * List view
     */
    public function index(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']->id ?? null;

        // Set permissions
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = empty($modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!$modules['view_permission']) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);

        try {
            // Datatable columns
            $columns = [
                (object)['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'td_label' => 'Sr No.', 'orderable' => false, 'searchable' => false],
                (object)['data' => 'company.company_name', 'name' => 'company_id', 'td_label' => 'Company Name'],
                (object)['data' => 'payment_terms', 'name' => 'payment_terms', 'td_label' => 'Payment Terms'],
                (object)['data' => 'credit_days', 'name' => 'credit_days', 'td_label' => 'Credit Days'],
                (object)['data' => 'status', 'name' => 'status', 'td_label' => 'Status', 'className' => 'w-5 text-start'],
                (object)['data' => 'action', 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'w-10 text-start'],
            ];

            // Remove company column for specific users
            if ($modules['company_id']) {
                $columns = array_values(array_filter($columns, fn($col) => $col->name !== 'company_id'));
            }

            View::share('columns', $columns);

            if ($request->ajax()) {
                $data = PaymentTerm::with(['company'])
                    ->when(Auth::guard('team_person')->check() && $modules['company_id'], function ($query) use ($modules, $loginUserId) {
                        $query->where('company_id', $modules['company_id']);
                        if ($modules['personal_data_permission'] && !$modules['all_data_permission']) {
                            $query->where('created_by', $loginUserId);
                        }
                    })
                    ->orderBy('id', 'desc');

                if (!empty($modules['restore_permission'])) {
                    $data = $data->withTrashed();
                }

                return Datatables::of($data)
                    ->addIndexColumn()
                    ->filter(function ($query) use ($request) {
                        if ($request->filled('filter_company')) {
                            $query->where('company_id', $request->filter_company);
                        }
                        if ($request->filled('search')) {
                            $query->where('payment_terms', 'like', '%' . $request->search . '%');
                        }
                        if ($request->has('status') && $request->status !== null && $request->status !== 'all') {
                            $query->where('status', $request->status);
                        }
                    })
                    ->editColumn('status', function ($row) use ($modules) {
                        $btn = '';
                        $dropdown = '<ul class="dropdown-menu">';

                        if ($row->status === 'active') {
                            $btn .= '<button type="button" class="btn btn-success btn-sm dropdown-toggle" data-bs-toggle="dropdown">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item btn-label-danger update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row->id . '" data-update_status="inactive">Inactive</a></li>';
                        } elseif ($row->status === 'inactive') {
                            $btn .= '<button type="button" class="btn btn-danger btn-sm dropdown-toggle" data-bs-toggle="dropdown">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item btn-label-success update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row->id . '" data-update_status="active">Active</a></li>';
                        }

                        $dropdown .= '</ul>';
                        return $btn . $dropdown;
                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $btn = '';
                        if (!$row->deleted_at) {
                            if ($modules['update_permission']) {
                                $btn .= '<a href="' . route($modules["route"] . ".edit", [$row->id]) . '" class="btn btn-light btn-icon mx-1"><i class="fa-solid fa-pen-to-square"></i></a>';
                            }
                            if ($modules['delete_permission']) {
                                $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row->id]) . '" class="btn btn-danger btn-icon deletebutton mx-1"><i class="fa-solid fa-trash"></i></a>';
                            }
                        } else {
                            $btn .= '<a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row->id]) . '" class="btn btn-light mx-1 record-restore" data-bs-toggle="tooltip" title="Restore Data"><i class="ti ti-history"></i> Restore</a>';
                        }
                        return $btn ?: '-';
                    })
                    ->rawColumns(['status', 'action'])
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


    public function create()
    {
        $modules = $this->modules;
        View::share('modules', $modules);
        return view($modules['folder_path'] . '.form');
    }

    public function store(PaymentTermRequest $request)
    {
        $validated = $request->validated();
        $validated['created_by'] = Auth::id();

        PaymentTerm::create($validated);

        return Redirect::route($this->modules['route'] . '.index')->withSuccess("Payment Term created successfully.");
    }

    public function edit($id)
    {
        $modules = $this->modules;
        $edit = PaymentTerm::findOrFail($id);

        View::share(compact('modules', 'edit'));
        return view($modules['folder_path'] . '.form');
    }
    public function update(PaymentTermRequest $request, $id)
    {
        $validated = $request->validated();
        $validated['updated_by'] = Auth::id();

        $updateData = PaymentTerm::findOrFail($id);
        $updateData->update($validated);

        return Redirect::route($this->modules['route'] . '.index')->withSuccess("Payment Term updated successfully.");
    }

    public function destroy(Request $request, $id)
    {
        $dataDelete = PaymentTerm::findOrFail($id);
        $dataDelete->update(['deleted_by' => Auth::id()]);
        $dataDelete->delete();

        return $request->ajax()
            ? $this->sendResponse([], "Payment Term deleted successfully.")
            : Redirect::back()->withSuccess("Payment Term deleted successfully.");
    }
}
