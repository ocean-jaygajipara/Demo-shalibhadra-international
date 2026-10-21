<?php

namespace App\Http\Controllers\software;

use App\Exports\CustomerExport;
use App\Helpers\Helper;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Imports\ProductImport;
use App\Mail\GenericMail;
use App\Models\Customer;
use App\Models\CustomerAssignArea;
use App\Models\CustomerBankDetail;
use App\Models\CustomerContactDetail;
use App\Models\CustomerImportFile;
use App\Models\CustomerSalesItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\software\ManageEmailController;
use App\Imports\CustomerImport;
use App\Models\Company;
use App\Models\MasterArea;
use App\Models\MasterCity;
use App\Models\MasterCountry;
use App\Models\MasterState;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;



class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Customer',
            'folder_path' => 'software.modules.customer',
            'route' => 'customer',
            'table_name' => (new Customer())->getTable(),
            'permisstion_prefix' => 'customer',
            'module_name' => 'Customer',
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
        $modules['followup_add'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.add'), 'Follow Up']);
        // dd( $modules['followup_add'] );
        View::share('modules', $modules);


        try {
            $customerLimitReached = null;
            if ($modules['company_id']) {
                $company = Company::find($modules['company_id']);
                $currentCustomerCount = Customer::withTrashed()->where('company_id', $company->id)->count();
                $customerLimitReached = $company && $currentCustomerCount >= $company->max_customer_count;
            }
            View::share('customerLimitReached', $customerLimitReached);

            $columns = [
                // (object)['data' => "id", 'name' => 'id', 'td_label' => 'Sr No.'],
                (object) ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'td_label' => 'Sr No.', 'orderable' => false, 'searchable' => false],
                (object) ['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' => ''],
                (object) ['data' => "personal_details", 'name' => 'personal_details', 'td_label' => 'Personal Detail', 'className' => ''],
                (object) ['data' => "contact_details", 'name' => 'contact_details', 'td_label' => 'Contact Detail', 'className' => ''],
                (object) ['data' => "client_code", 'name' => 'client_code', 'td_label' => 'Client Code', 'className' => ''],
                (object) ['data' => "country.name", 'name' => 'country.name', 'td_label' => 'Country', 'className' => ''],
                (object) ['data' => "state.name", 'name' => 'state.name', 'td_label' => 'State', 'className' => ''],
                (object) ['data' => "city.name", 'name' => 'city.name', 'td_label' => 'City', 'className' => ''],
                (object) ['data' => "area.area_name", 'name' => 'area.area_name', 'td_label' => 'Area', 'className' => ''],
                (object) ['data' => "status", 'name' => 'status', 'td_label' => 'Status', 'className' => 'w-5 text-start'],
                (object) ['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'w-10 text-start'],

            ];
            if ($modules['company_id']) {
                // array_unshift($columns, (object)['data' => "company_name", 'name' => 'company_name', 'td_label' => 'Company Name', 'className' =>  '']);
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'id';
                });
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
                //   dd(Auth::guard('team_person')->user()->company_id);
                $data = Customer::select('customers.*')
                    ->where('is_prospect', 0)
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check() && $modules['company_id']) {

                            $query->where('company_id', $modules['company_id']);

                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where('customers.created_by', $loginUserId);
                            }
                        }
                    });
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }
                $data = $data->with(['company', 'country', 'state', 'city', 'area', 'customerType']);



                return Datatables::of($data)
                    ->addIndexColumn()
                    ->orderColumn('personal_details', function ($query, $order) {
                        $query->orderBy('customer_name', $order);
                    })
                    ->orderColumn('contact_details', function ($query, $order) {
                        $query->orderBy('mobile_number', $order);
                    })
                    // ->filter(function ($query) use ($request) {
                    //     if ($request->has('search')) {
                    //     $query->where('customer_name', 'like', "%" . $request->search . "%");
                    //         // $query->orWhere('short_name', 'like', "%" . $request->search . "%");
                    //     }
                    // })
                    ->filter(function ($query) use ($request) {

                        if ($request->has('search') && $request->search) {
                            $query->where(function ($q) use ($request) {
                                $q->where('gst_number', 'like', "%" . $request->search . "%")
                                    ->orWhere('mobile_number', 'like', "%" . $request->search . "%")
                                    ->orWhere('whatsapp_number', 'like', "%" . $request->search . "%")
                                    ->orWhere('customer_name', 'like', "%" . $request->search . "%")
                                    ->orWhere('contact_person_name', 'like', "%" . $request->search . "%")
                                    ->orWhere('client_code', 'like', "%" . $request->search . "%");
                            });
                        }
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
                    })
                    ->addColumn('personal_details', function ($row) {
                        $personal_details = '';

                        $customerName = $row?->customer_name ?? '-';
                        $contactPerson = $row?->contact_person_name ?? '-';
                        $personal_details .= '<div><strong><i class="fas fa-user text-primary"></i> ' . $customerName . ' - ' . $contactPerson . '</strong></div>';
                        if ($row?->customerType?->name) {
                            $personal_details .= '<div><strong><i class="fas fa-file-invoice text-success"></i> ' . $row?->customerType?->name . '</strong></div>';
                        } else {
                            $personal_details .= '<div><strong><i class="fas fa-file-invoice text-success"></i> - </strong></div>';
                        }
                        if ($row?->gst_number) {
                            $personal_details .= '<div><strong><i class="fas fa-receipt text-info"></i> ' . $row?->gst_number . '</strong></div>';
                        } else {
                            $personal_details .= '<div><strong><i class="fas fa-receipt text-info"></i> -</strong></div>';
                        }
                        return $personal_details;
                    })
                    ->addColumn('contact_details', function ($row) {
                        $contactDetails = '';
                        // $contactDetails .= '<div>';
                        if ($row?->contact_person_name) {
                            $contactDetails .= '<div><strong><i class="fas fa-user-tag text-info"></i> ' . $row?->contact_person_name ?? "-" . '</strong></div>';
                        }

                        // Mobile
                        $mobile_number = preg_replace('/[^0-9]/', '', $row->mobile_number);
                        $mobile = $row->mobile_number
                            ? '<a href="https://wa.me/' . $mobile_number . '" target="_blank" class="text-primary">
                                    <i class="fa-solid fa-mobile"></i> ' . $row->mobile_number . '
                            </a>'
                            : '<i class="fa-solid fa-mobile text-dark"></i> -';

                        // WhatsApp
                        $whatsapp_number = preg_replace('/[^0-9]/', '', $row->whatsapp_number);
                        $whatsapp = $row->whatsapp_number
                            ? '<a href="https://wa.me/' . $whatsapp_number . '" target="_blank" class="text-success">
                                <i class="fab fa-whatsapp"></i> ' . $row->whatsapp_number . '
                        </a>'
                            : '<i class="fab fa-whatsapp text-dark"></i> -';

                        // Email
                        $email = $row->email
                            ? '<a href="https://mail.google.com/mail/?view=cm&fs=1&to=' . urlencode($row->email) . '" target="_blank" class="text-muted">
                            <i class="fas fa-envelope"></i> ' . $row->email . '
                        </a>'
                            : '<i class="fas fa-envelope text-dark"></i> -';

                        return $mobile . '<br>' . $whatsapp . '<br>' . $email;
                    })

                    ->editColumn('status', function ($row) use ($modules) {
                        $btn = '';
                        // $btn = ucfirst($row->status);

                        $dropdown = "";
                        $dropdown .= '<ul class="dropdown-menu" style="">';

                        if ($row->status == "active") {
                            $btn .= '<button type="button" class="btn btn-success btn-sm  dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item waves-effect btn-label-danger update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="inactive">Inactive</a></li>';
                            // $btn = '<span class="badge bg-success bg-glow">Active</span>';
                        } elseif ($row->status == "inactive") {
                            $btn .= '<button type="button" class="btn btn-danger btn-sm  dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item waves-effect btn-label-success update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="active">Active</a></li>';
                            // $btn = '<span class="badge bg-danger bg-glow">In-Active</span>';
                        } else {
                            return $btn;
                        }
                        $dropdown .= '</ul>';
                        $btn .= $dropdown;
                        return $btn;
                    })
                    ->editColumn('country.name', function ($row) {
                        return $row->country?->name ?? '-';
                    })
                    ->editColumn('state.name', function ($row) {
                        return $row->state?->name ?? '-';
                    })
                    ->editColumn('city.name', function ($row) {
                        return $row->city?->name ?? '-';
                    })
                    ->editColumn('area.area_name', function ($row) {
                        return $row->area?->area_name ?? '-';
                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $btn = '';
                        $btn .= '
                            <div class="">
                                <button class="btn btn-icon waves-effect waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">';

                        if (!$row?->deleted_at) {
                            if ($modules['update_permission']) {
                                $btn .= '<li>
                                            <a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="dropdown-item">
                                                <i class="fa-solid fa-pen-to-square me-2"></i> Edit
                                            </a>
                                        </li>';
                            }
                            if ($modules['delete_permission']) {
                                $btn .= '<li>
                                            <a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="dropdown-item deletebutton">
                                                <i class="fa-solid fa-trash me-2"></i> Delete
                                            </a>
                                        </li>';
                            }
                        } else {
                            $btn .= '<li>
                                        <a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="dropdown-item record-restore">
                                            <i class="ti ti-history me-2"></i> Restore
                                        </a>
                                    </li>';
                        }

                        // Add Followup button
                        $modelData = [];

                        if (!$modules['company_id']) {
                            $modelData['company_name'] = $row->company->company_name;
                        }

                        $modelData['client_code'] = $row?->client_code;
                        // $modelData['created_at'] = $row?->quotation_date;
                        $modelData['customer_name'] = $row?->customer_name;
                        $modelData['mobile_no'] = $row?->mobile_number;
                        $modelData['contact_person_name'] = $row?->contact_person_name;



                        if ($modules['followup_add']) {
                            $btn .= '<li>
                <a href="javascript:void(0)"
                    data-company_id="' . $row->company_id . '"
                    data-company_name="' . ($row->company->company_name ?? '') . '"
                    data-id="' . $row->id . '"
                    class="dropdown-item add-followup mx-1"
                    data-customer="' . htmlspecialchars(json_encode($modelData), ENT_QUOTES, 'UTF-8') . '">
                    <i class="fa-solid fa-plus me-2"></i> Add Followup
                </a>
            </li>';
                        }



                        if ($modules['add_permission']) {
                            $btn .= '<li>
        <a href="' . route('customer.assign.area', [$row->id]) . '"
            class="dropdown-item mx-1"
            target="_blank">
            <i class="fa-solid fa-location-dot me-2"></i> Add Area
        </a>
    </li>';
                        }


                        $btn .= '</ul></div>';

                        return $btn ?: '-';
                    })


                    ->rawColumns(['personal_details', 'contact_details', 'contact_info', 'status', 'action'])
                    ->make(true);
            }
            $currentCustomerCount = $modules['restore_permission']
                ? Customer::withTrashed()
                    ->where('company_id', $modules['company_id'])
                    ->where('created_by', $loginUserId)
                    ->count()
                : Customer::where('company_id', $modules['company_id'])
                    ->where('created_by', $loginUserId)
                    ->count();

            if (!$modules['view_permission']) {
                abort(403, 'Unauthorized');
            }

            return view($modules['folder_path'] . '.index', compact('currentCustomerCount'));
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
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        //    dd($loginUserId);
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
        $modules['is_prospect'] = 0;

        View::share("modules", $modules);


        try {

            $currentCustomerCount = -1;
            if ($modules['company_id']) {
                $company = \App\Models\Company::find($modules['company_id']);

                $currentCustomerCount = $modules['restore_permission']
                    ? Customer::withTrashed()
                        ->where('company_id', $modules['company_id'])
                        ->where('created_by', $loginUserId)
                        ->count()
                    : Customer::where('company_id', $modules['company_id'])
                        ->where('created_by', $loginUserId)
                        ->count();

                if ($company && $currentCustomerCount >= $company->max_customer_count) {
                    return redirect()->route($modules['route'] . '.index')
                        ->withErrors('Customer limit reached as per your plan. Please upgrade to add more.');
                }
            }
            View::share("currentCustomerCount", $currentCustomerCount);

            $convertToCustomer = Session::get('newRequest');
            if ($convertToCustomer) {
                // $convertToCustomer = ()$convertToCustomer->toArray();

                if ($convertToCustomer?->inquiry_id && $convertToCustomer?->customer_name) {
                    View::share('convert_to_customer', $convertToCustomer);
                    Session::forget('newRequest');
                }
            }


            // Generate client code
            $lastClient = Customer::orderBy('id', 'desc')->first();

            if ($lastClient) {
                $lastNumber = (int) substr($lastClient->client_code, 4);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            $clientCode = 'CC-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

            $company = Company::find($modules['company_id']);
            $mobile_min = $company?->mobile_min;
            $mobile_max = $company?->mobile_max;

            // Pass everything to the view
            return view($modules['folder_path'] . '.form', compact('clientCode', 'company', 'mobile_min', 'mobile_max'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function store(CustomerRequest $request)
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
        // dd($validated, $request->all());


        try {
            $company_id = $modules['company_id'] ?? $request->company_id;

            $company = Company::find($company_id);
            // if (!$company) {
            //     return Redirect::back()->withErrors('Company not found.');
            // }

            $modules['company_id'] = $company->id;


            $currentCustomerCount = Customer::where('company_id', $company->id)
                ->where('created_by', $loginUserId)
                ->count();
            if ($currentCustomerCount >= $company->max_customer_count) {
                return Redirect::back()->withErrors('Customer limit reached as per your plan. Please upgrade to add more customers.');
            }

            $newRequest = new Request();
            $newRequest['company_id'] = $modules['company_id'];
            $newRequest['company_id'] = (isset($newRequest['company_id']) && !empty($newRequest['company_id'])) ? $newRequest['company_id'] : $request?->company_id;
            // $newRequest['directReturn'] = true;

            $generate_client_code = (new CommonController(new Request()))->generate_client_code($newRequest);
            // dd("L-410", $validated, $modules['company_id'], $newRequest, $generate_client_code);

            if ($generate_client_code->getStatusCode() == 200) {
                $dataResponse = $generate_client_code?->getData();
                if ($dataResponse?->data) {
                    $validated['client_code'] = $dataResponse?->data;
                }
            }

            $validated['created_by'] = $loginUserId;

            $last4Digits = substr($validated['mobile_number'], -4);
            $validated['sp'] = Str::random(4) . $last4Digits . Str::random(3);
            $validated['password'] = Hash::make($last4Digits);
            $validated['whatsapp_number'] = $validated['mobile_number'];

            $customer = Customer::create($validated);
            $customer->load('company');

            CustomerAssignArea::create([
                'company_id' => $customer->company_id,
                'customer_id' => $customer->id,
                'country_id' => $validated['country_id'] ?? null,
                'state_id' => $validated['state_id'] ?? null,
                'city_id' => $validated['city_id'] ?? null,
                'area_id' => $validated['area_id'] ?? null,
                'created_type' => ($modules['currentGuard'] == 'admin_software') ? 'Admin' : 'Team',
                'created_by' => $loginUserId,
                'updated_by' => null,
                'deleted_by' => null,
                'deleted_at' => null,
            ]);

            // Contact Details
            if (!empty($validated['contacts']) && is_array($validated['contacts'])) {
                foreach ($validated['contacts'] as $contact) {
                    CustomerContactDetail::create([
                        'customer_id' => $customer->id,
                        'department' => $contact['department'] ?? null,
                        'name' => $contact['name'] ?? null,
                        'designation' => $contact['designation'] ?? null,
                        'mobile' => $contact['mobile'] ?? null,
                        'email' => $contact['email'] ?? null,
                        'status' => 'active',
                        'created_by' => $loginUserId,
                    ]);
                }
            }
            // BANK DETAILS
            if (!empty($validated['banks']) && is_array($validated['banks'])) {
                foreach ($validated['banks'] as $bank) {
                    CustomerBankDetail::create([
                        'customer_id' => $customer->id,
                        'bank_name' => $bank['bank_name'] ?? null,
                        'account_number' => $bank['account_number'] ?? null,
                        'branch' => $bank['branch'] ?? null,
                        'ifsc' => $bank['ifsc'] ?? null,
                        'swift_code' => $bank['swift_code'] ?? null,
                        'status' => 'active',
                        'created_by' => $loginUserId,
                    ]);
                }
            }
            // SALES ITEM
            if (!empty($validated['sales']) && is_array($validated['sales'])) {
                foreach ($validated['sales'] as $sale) {
                    CustomerSalesItem::create([
                        'customer_id' => $customer->id,
                        'item_code' => $sale['item_code'] ?? null,
                        'item_name' => $sale['item_name'] ?? null,
                        'rate' => $sale['rate'] ?? null,
                        'type' => $sale['type'] ?? null,
                        'date' => $sale['date'] ?? null,
                        'status' => 'active',
                        'created_by' => $loginUserId,
                    ]);
                }
            }





            $companyId = $customer->company_id ?? $loginUser?->company_id ?? '';


            if ($companyId && !empty($customer->email)) {
                try {
                    $data = [
                        'customer_name' => $customer->name,
                        'mobile_number' => $customer->mobile_number ?? '',
                        'whatsapp_number' => $customer->whatsapp_number ?? '',
                        'client_code' => $customer->client_code ?? '',
                        'company_name' => $customer->company->name ?? 'Your Company',
                    ];


                    Helper::applyDynamicMailConfig($companyId);


                    $template = Helper::getRenderedEmailTemplate('registrion_company', $companyId, $data);


                    Mail::to($customer->email)->send(
                        new GenericMail($template['subject'], $template['body']),

                    );
                } catch (\Exception $mailException) {
                    Log::error("Email sending failed: {$customer->id}: " . $mailException->getMessage());
                }
            }

            return Redirect::route($modules['route'] . '.index')
                ->withSuccess($modules['title'] . ' created successfully.');
        } catch (\Exception $e) {
            return $e->getMessage();
            return Redirect::route($modules['route'] . '.index')
                ->withErrors($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if ($id = "print") {
            return self::print();
        }
        dd($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
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

            // Fetch the customer record for editing
            $edit = Customer::with(['contacts_details', 'bank_details', 'sales_items'])->findOrFail($id);
            View::share('edit', $edit);

            // Contacts
            $contacts = $edit->contacts_details ?? collect();
            View::share('contacts', $contacts);


            // Banks
            $banks = $edit->bank_details ?? collect();
            View::share('banks', $banks);

            // SALES ITEM
            $salesItems = $edit->sales_items ?? collect();
            View::share('salesItems', $salesItems);

            // dd($contacts);



            // Fetch all customer types (for customer type dropdown)
            $customerTypes = DB::table('customer_type')
                ->whereNull('deleted_at')
                ->select('id', 'name')
                ->get();
            View::share('customerTypes', $customerTypes);

            // Fetch only parent types (for conditional parent dropdown)
            $parentTypes = DB::table('customer_type')
                ->whereNull('deleted_at')
                ->whereNull('parent_id') // or ->where('is_parent', true) if you have such a flag
                ->select('id', 'name')
                ->get();
            View::share('parentTypes', $parentTypes);

            $company = Company::find($modules['company_id']);
            $mobile_min = $company?->mobile_min;
            $mobile_max = $company?->mobile_max;

            // Return the form view
            return view($modules['folder_path'] . '.form', compact('mobile_min', 'mobile_max'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(CustomerRequest $request, string $id)
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
        View::share('modules', $modules);

        try {
            // Validation
            $validated = $request->validated();
            //    dd($validated['banks']);
            $validated['updated_by'] = $loginUserId;

            $updateData = Customer::with('contacts_details', 'bank_details', 'sales_items')->findOrFail($id);

            if (empty($updateData->password)) {
                $last4Digits = substr($validated['mobile_number'], -4);
                $validated['sp'] = Str::random(4) . $last4Digits . Str::random(3);
                $validated['password'] = Hash::make($last4Digits);
            }
            $validated['whatsapp_number'] = $validated['mobile_number'];
            $updateData->update($validated);
            // Contact Details
            $submittedContacts = $validated['contacts'] ?? [];
            $existingContactIds = $updateData->contacts_details->pluck('id')->toArray();
            $submittedContactIds = [];

            foreach ($submittedContacts as $contactData) {
                if (!empty($contactData['id']) && in_array($contactData['id'], $existingContactIds)) {
                    // Update existing contact
                    $contact = CustomerContactDetail::find($contactData['id']);
                    $contact->update([
                        'department' => $contactData['department'] ?? null,
                        'name' => $contactData['name'] ?? null,
                        'designation' => $contactData['designation'] ?? null,
                        'mobile' => $contactData['mobile'] ?? null,
                        'email' => $contactData['email'] ?? null,
                    ]);
                    $submittedContactIds[] = $contact->id;
                } else {

                    // Add new contact
                    $newContact = CustomerContactDetail::create([
                        'customer_id' => $updateData->id,
                        'department' => $contactData['department'] ?? null,
                        'name' => $contactData['name'] ?? null,
                        'designation' => $contactData['designation'] ?? null,
                        'mobile' => $contactData['mobile'] ?? null,
                        'email' => $contactData['email'] ?? null,
                        'status' => 'active',
                        'created_by' => $loginUserId,
                    ]);
                    $submittedContactIds[] = $newContact->id;
                }
            }
            // Delete contacts removed from the form
            $contactsToDelete = array_diff($existingContactIds, $submittedContactIds);
            if (!empty($contactsToDelete)) {
                CustomerContactDetail::whereIn('id', $contactsToDelete)->delete();
            }

            // Bank Details
            $submittedBanks = $validated['banks'] ?? [];
            $existingBankIds = $updateData->bank_details->pluck('id')->toArray();
            $submittedBankIds = [];

            foreach ($submittedBanks as $bankData) {
                if (!empty($bankData['id']) && in_array($bankData['id'], $existingBankIds)) {
                    // Update existing bank
                    $bank = CustomerBankDetail::find($bankData['id']);
                    $bank->update([
                        'bank_name' => $bankData['bank_name'] ?? null,
                        'account_number' => $bankData['account_number'] ?? null,
                        'branch' => $bankData['branch'] ?? null,
                        'ifsc' => $bankData['ifsc'] ?? null,
                        'swift_code' => $bankData['swift_code'] ?? null,
                    ]);
                    $submittedBankIds[] = $bank->id;
                    // dd($request->all(), $validated, $bank->id, $bank->toArray());
                } else {
                    $newBank = CustomerBankDetail::create([
                        'customer_id' => $updateData->id,
                        'bank_name' => $bankData['bank_name'] ?? null,
                        'account_number' => $bankData['account_number'] ?? null,
                        'branch' => $bankData['branch'] ?? null,
                        'ifsc' => $bankData['ifsc'] ?? null,
                        'swift_code' => $bankData['swift_code'] ?? null,
                        'status' => 'active',
                        'created_by' => auth()->id(),
                    ]);
                    $submittedBankIds[] = $newBank->id;
                }
            }

            $banksToDelete = array_diff($existingBankIds, $submittedBankIds);
            if (!empty($banksToDelete)) {
                CustomerBankDetail::whereIn('id', $banksToDelete)->delete();
            }

            // SALES ITEM
            $submittedSales = $validated['sales'] ?? [];
            $existingSalesIds = $updateData->sales_items->pluck('id')->toArray();
            $submittedSalesIds = [];

            foreach ($submittedSales as $saleData) {
                if (!empty($saleData['id']) && in_array($saleData['id'], $existingSalesIds)) {
                    // Update existing sale
                    $sale = CustomerSalesItem::find($saleData['id']);
                    $sale->update([
                        'item_code' => $saleData['item_code'] ?? null,
                        'item_name' => $saleData['item_name'] ?? null,
                        'rate' => $saleData['rate'] ?? null,
                        'type' => $saleData['type'] ?? null,
                        'date' => $saleData['date'] ?? null,
                    ]);
                    $submittedSalesIds[] = $sale->id;
                } else {
                    // Add new sale
                    $newSale = CustomerSalesItem::create([
                        'customer_id' => $updateData->id,
                        'item_code' => $saleData['item_code'] ?? null,
                        'item_name' => $saleData['item_name'] ?? null,
                        'rate' => $saleData['rate'] ?? null,
                        'type' => $saleData['type'] ?? null,
                        'date' => $saleData['date'] ?? null,
                        'status' => 'active',
                        'created_by' => auth()->id(),
                    ]);
                    $submittedSalesIds[] = $newSale->id;
                }
            }

            // Delete removed rows
            $salesToDelete = array_diff($existingSalesIds, $submittedSalesIds);
            if (!empty($salesToDelete)) {
                CustomerSalesItem::whereIn('id', $salesToDelete)->delete();
            }

            // dd($request->all(),$validated);




            return Redirect::route($modules['route'] . '.index')
                ->withSuccess($modules['title'] . ' updated successfully.');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')
                ->withErrors($e->getMessage());
        }
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
        $isAjax = $request->ajax();

        try {
            $dataDelete = Customer::findOrFail($id);

            // Soft delete logic
            $dataDelete->deleted_by = Auth::guard('admin_software')?->user()?->id;
            $dataDelete->save();

            if ($dataDelete->delete()) {
                if ($isAjax) {
                    return response()->json([
                        'success' => true,
                        'message' => $modules['title'] . ' deleted successfully.',
                    ]);
                }

                return Redirect::route($modules['route'] . '.index')
                    ->withSuccess($modules['title'] . ' deleted successfully.');
            }

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong, please try again later.',
                ]);
            }

            return Redirect::route($modules['route'] . '.index')
                ->withErrors('Something went wrong, please try again later.');
        } catch (\Exception $e) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]);
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

            $state = Customer::withTrashed()->findOrFail($id);
            $state->restore();

            return Redirect::back()->withSuccess($modules['title'] . ' restored successfully!');
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

        // if ($validator->fails() && $isAjax) {
        //     return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
        // }


        try {
            $state = Customer::withTrashed()->findOrFail($request?->id);
            if ($state) {
                $state->status = $request->update_status;
                $state->updated_by = $loginUserId;
                $state->save();
                if ($isAjax) {
                    return $this->sendResponse($state, $modules['title'] . ' status update successfully.');
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
    public function checkMobileNumber(Request $request)
    {
        $request->validate([
            'mobile_number' => 'required|string'
        ]);

        $exists = DB::table('inquiries')
            ->where('mobile_number', $request->mobile_number)
            ->exists();

        return response()->json(['exists' => $exists]);
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
        return Excel::download(new CustomerExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'Customer-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }

    public function print(Request $request)
    {
        // dd($request->all());
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
            // Permissions
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

            $query = Customer::select('*')
                ->where(function ($q) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check()) {
                        $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                        $q->where('company_id', $teamPersonCompanyId);

                        if ($modules['personalDataPermission'] && !$modules['allDataPermission']) {
                            $q->where('created_by', $loginUserId);
                        }
                    }
                })
                ->with(['company', 'country', 'state', 'city', 'area'])

                ->orderBy('id', 'DESC');



            // Filters
            if ($request->has('company') && !empty($request->company)) {
                $query->where('company_id', $request->company);
            }

            if ($request->has('country') && $request->country) {
                $query->where('country_id', $request->country);
            }
            if ($request->has('state') && $request->state) {
                $query->where('state_id', $request->state);
            }
            if ($request->has('city') && $request->city) {
                $query->where('city_id', $request->city);
            }

            if ($request->has('status') && $request->status !== null && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($subQuery) use ($request) {
                    $subQuery->where('gst_number', 'like', "%" . $request->search . "%")
                        ->orWhere('mobile_number', 'like', "%" . $request->search . "%")
                        ->orWhere('whatsapp_number', 'like', "%" . $request->search . "%")
                        ->orWhere('customer_name', 'like', "%" . $request->search . "%")
                        ->orWhere('contact_person_name', 'like', "%" . $request->search . "%")
                        ->orWhere('client_code', 'like', "%" . $request->search . "%");
                });
            }
            ;
            $Customer = $query->orderBy('id', 'DESC')->get();
            // dd($Customer);
            // dd([
            //     'sql' => $query->toSql(),
            //     'bindings' => $query->getBindings()
            // ]);

            return view($modules['folder_path'] . '.print', compact('Customer', 'modules', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function import()
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        View::share('modules', $modules);

        return view($modules['folder_path'] . '.import');
    }

    public function import_store(Request $request)
    {
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $authLoginUserDetail = $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $company_Id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        View::share('modules', $modules);
        View::share('company_id', $modules['company_id']);

        $validator = Validator::make($request->all(), [
            'company_id' => 'required',
            'import_file' => 'required|mimes:xls,xlsx|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 422,
                'errors' => $validator->errors()
            ]);
        }

        try {
            if ($request->hasFile('import_file')) {
                $company = Company::find($request->company_id);
                $company_name = $company->company_name;
                $company_slug = Str::slug($company_name);

                $file = $request->file('import_file');
                $extension = $file->getClientOriginalExtension();
                $filename = now()->format('dmYHis') . rand() . '.' . $extension;

                $year = now()->format('Y');
                $month = now()->format('m');
                $folder = "uploads/" . $request->company_id . "-" . $company_slug . "/customer/{$year}-{$month}/import-customer-file";
                $path = $file->storeAs($folder, $filename, 'public');

                $filename_path = "{$folder}/{$filename}";
            }

            $customerImportFileId = CustomerImportFile::insertGetId([
                'company_id' => $request->company_id,
                'filename' => $filename_path,
                'total_customers' => 0,
                'total_success_customers' => 0,
                'total_failed_customers' => 0,
                'errors' => '',
                'created_by' => $loginUserId,
            ]);

            $company_id = $request->company_id;

            Excel::import(
                new CustomerImport($company_id, $loginUserId, $modules['currentGuard'], $customerImportFileId, $authLoginUserDetail),
                $request->file('import_file')
            );

            $customerImportFile = CustomerImportFile::find($customerImportFileId);

            // flash summary
            session()->flash('customer_summary', [
                'total_customers' => $customerImportFile->total_customers,
                'total_success_customers' => $customerImportFile->total_success_customers,
                'total_failed_customers' => $customerImportFile->total_failed_customers,
            ]);


            if (!empty($customerImportFile->errors)) {
                if ($customerImportFile->total_success_customers > 0) {

                    return Redirect::back()
                        ->with('customerImportFileMessages', json_decode($customerImportFile->errors))
                        ->with('success', 'Customers imported successfully with some errors!');
                } else {

                    return Redirect::back()
                        ->with('customerImportFileMessages', json_decode($customerImportFile->errors));
                }
            }


            return back()->with('success', 'Customers imported successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function assignArea(Request $request, $id)
    {
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $authLoginUserDetail = $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $company_Id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        $modules['breadcrumb'] = [
            ['title' => 'Customer', 'url' => route('customer.index')],
            ['title' => 'Assign Area', 'url' => ''],
        ];

        View::share('modules', $modules);
        // dd($modules['currentGuard']);
        try {
            $customer = Customer::with(['company', 'country', 'state', 'city'])->findOrFail($id);

            if (!$modules['company_id']) {
                $modules['company_id'] = $customer->company_id ?? null;
            }

            View::share('company_id', $modules['company_id']);

            if ($request->isMethod('post')) {
                $request->validate([
                    'company_id' => ['required', 'integer', 'exists:' . (new Company())->getTable() . ',id'],
                    'country_id' => ['required', 'integer', 'exists:' . (new MasterCountry())->getTable() . ',id'],
                    'state_id' => ['required', 'integer', 'exists:' . (new MasterState())->getTable() . ',id'],
                    'city_id' => ['required', 'integer', 'exists:' . (new MasterCity())->getTable() . ',id'],
                    'area_id' => ['required', 'integer', 'exists:' . (new MasterArea())->getTable() . ',id'],
                ]);
                //   dd($request->all());
                $existingAssignment = CustomerAssignArea::where([
                    'company_id' => $request->company_id,
                    'customer_id' => $id,
                    'country_id' => $request->country_id,
                    'state_id' => $request->state_id,
                    'city_id' => $request->city_id,
                    'area_id' => $request->area_id,
                ])->first();

                if ($existingAssignment) {
                    return redirect()->back()->with('error', 'This area is already assigned to the customer.');
                }

                CustomerAssignArea::create([
                    'company_id' => $request->company_id,
                    'customer_id' => $id,
                    'country_id' => $request->country_id,
                    'state_id' => $request->state_id,
                    'city_id' => $request->city_id,
                    'area_id' => $request->area_id,
                    'created_type' => ($modules['currentGuard'] == 'admin_software') ? 'Admin' : 'Team',
                    'created_by' => $loginUserId,
                ]);



                return redirect()->back()->with('success', 'Area assigned successfully.');
            }

            $edit = (object) [
                'company_id' => $customer->company_id,
                'country_id' => $customer->country_id,
                'state_id' => $customer->state_id,
                'city_id' => $customer->city_id,
                'area_id' => null,
            ];
            $assignedAreas = CustomerAssignArea::with(['country', 'state', 'city', 'area'])
                ->where('company_id', $modules['company_id'])
                ->where('customer_id', $customer->id)
                ->get();

            View::share('assignedAreas', $assignedAreas);


            return view($modules['folder_path'] . '.area', compact('customer', 'edit'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    public function getAssignedAreasData(Request $request, $id)
    {
        $query = CustomerAssignArea::with(['country', 'state', 'city', 'area'])
            ->where('customer_id', $id);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('country', fn($row) => $row->country->name ?? '-')
            ->addColumn('state', fn($row) => $row->state->name ?? '-')
            ->addColumn('city', fn($row) => $row->city->name ?? '-')
            ->addColumn('area', fn($row) => $row->area->area_name ?? '-')
            ->addColumn('action', function ($row) use ($request) {
                $deleteRoute = route('customer-assign-area.destroy', $row->id);
                $btn = '<a href="javascript:void(0)"
                            data-id="' . $row->id . '"
                            data-did="' . $deleteRoute . '"
                            class="btn btn-danger btn-icon deletebutton mx-1"
                            title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </a>';
                return $btn;
            })
            ->rawColumns(['country', 'state', 'city', 'area', 'action'])
            ->make(true);
    }
    public function destroyAssignArea(Request $request, string $id)
    {
        try {
            $dataDelete = CustomerAssignArea::findOrFail($id);
            $dataDelete->delete();

            return response()->json([
                'success' => true,
                'message' => 'Assigned area deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
