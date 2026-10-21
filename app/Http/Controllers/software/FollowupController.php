<?php

namespace App\Http\Controllers\software;

use App\Exports\FollowupExport;
use App\Helpers\Helper;

use App\Http\Controllers\Controller;
use App\Http\Requests\FollowupRequest;
use App\Http\Requests\QuotationRequest;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerLogs;
use App\Models\FollowUpReason;
use App\Models\Inquiry;
use App\Models\InquiryProduct;
use App\Models\MarketingStatus;
use App\Models\InquiryLogs;
use App\Models\Followup;
use App\Models\FollowupLogs;
use App\Models\Quotation;
use App\Models\QuotationLogs;
use App\Models\TeamPerson;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;


class FollowupController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Followup',
            'folder_path' => 'software.modules.followup',
            'route' => 'followup',
            'table_name' => (new Followup())->getTable(),
            'permisstion_prefix' => 'followup',
            'module_name' => 'Follow Up',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null,
        ];

        View::share('followFilterList', Followup::$followFilterList);
    }

    public function index(Request $request)
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
        $modules['followup_add'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.add'), 'Follow Up']);
        if (!$modules['view_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        View::share('modules', $modules);

        try {
            $columns = [
                // (object)[ 'data' => "id", 'name' => 'id', 'td_label' => 'Id' ],
                (object) ['data' => "response", 'name' => 'response', 'td_label' => 'Response', 'orderable' => false, 'searchable' => false, 'className' => 'w-10 text-wrap'],
                (object) ['data' => "inquiry_no", 'name' => 'inquiry_no', 'td_label' => 'Inquiry No/Quotation No', 'orderable' => false, 'searchable' => false, 'className' => 'text-wrap'],
                (object) ['data' => "followup_status", 'name' => 'followup_status', 'td_label' => 'Followup Status', 'orderable' => false, 'searchable' => false, 'className' => 'text-wrap'],
                (object) ['data' => "created_by_name", 'name' => 'created_by_name', 'td_label' => 'Team person name', 'orderable' => false, 'searchable' => false, 'className' => 'text-wrap'],
                (object) ['data' => "customer_details", 'name' => 'customer_details', 'td_label' => 'Customer details', 'orderable' => false, 'searchable' => false, 'className' => 'text-wrap'],
                (object) ['data' => "description", 'name' => 'description', 'td_label' => 'Description', 'orderable' => false, 'searchable' => false, 'className' => 'text-wrap'],
                (object) ['data' => "show_date_time", 'name' => 'show_date_time', 'td_label' => 'Date & Time', 'orderable' => false, 'searchable' => false, 'className' => ''],
                (object) ['data' => "company_name", 'name' => 'company_name', 'td_label' => 'Company Name', 'className' => ''],
                (object) ['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-start'],

            ];
            if ($modules['company_id']) {
                // array_unshift($columns, (object)['data' => "company_name", 'name' => 'company_name', 'td_label' => 'Company Name', 'className' =>  '']);
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'company_name';
                });

                if ($modules['parent_type_id']) {
                    $columns = array_filter($columns, function ($col) {
                        return $col->name !== 'created_by_name';
                    });
                }
                $columns = array_values($columns);
            }
            // dd(!$modules['company_id'], $columns);
            View::share("columns", $columns);

            if ($request->ajax()) {
                if (!$modules['view_permission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }

                $data = Followup::select('*');
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }
                $data = $data->select('followups.*', 'marketing_statuses.status_color as followup_status_color', 'marketing_statuses.status_color_2 as followup_status_color_2', 'marketing_statuses.text_color as followup_text_color', 'companies.company_name', 'inquiries.mobile_no', 'inquiries.contact_person_name', 'inquiries.customer_name', 'team_persons.name as created_by_name', 'inquiries.inquiry_no', 'quotations.quotation_no', 'marketing_statuses.name as followup_status')
                    ->leftJoin('inquiries', 'followups.inquiry_id', '=', 'inquiries.id')
                    ->leftJoin('marketing_statuses', 'followups.status_id', '=', 'marketing_statuses.id')
                    ->leftJoin('quotations', 'followups.quotation_id', '=', 'quotations.id')
                    ->leftJoin('team_persons', 'followups.created_by', '=', 'team_persons.id')
                    ->leftJoin('companies', 'inquiries.company_id', '=', 'companies.id')
                    ->where(function ($query) use ($modules, $loginUserId) {


                        if ($modules['company_id']) {
                            $query->where('followups.company_id', $modules['company_id']);
                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where((new Followup())->getTable() . '.created_by', $loginUserId);
                            }
                        }
                    });
                if ($modules['parent_type_id'] && $loginUserId) {
                    $data = $data->where((new Followup())->getTable() . '.created_by', $loginUserId);
                }
                $data = $data->orderBy((new Followup())->getTable() . '.id', 'desc');

                return DataTables::of($data)
                    ->addIndexColumn()
                    ->filter(function ($query) use ($request) {
                        if ($request->has('company_id') && $request->company_id != '') {
                            $query->where('company_id', $request->company_id);
                        }
                        if ($request->has('search') && $request->search) {
                            $query->where('inquiries.inquiry_no', 'like', "%" . $request->search . "%");
                            $query->orWhere('inquiries.customer_name', $request->search);
                            $query->orWhere('inquiries.mobile_no', 'like', "%" . $request->search . "%");
                            $query->orWhere('inquiries.contact_person_name', $request->search);
                        }


                        if ($request->has('filter_followup_status') && $request->filter_followup_status) {
                            $query->where((new Followup())->getTable() . '.status_id', $request->filter_followup_status);
                        }

                        if ($request->has('filter_created_by') && $request->filter_created_by) {
                            $query->where('team_persons.id', $request->filter_created_by);
                        }

                        if ($request->has('filter_followup_date') && $request->filter_followup_date) {

                            $filter_followup_date = $request->filter_followup_date;
                            $filter_followup_date = explode(" to ", $filter_followup_date);
                            $fromDate = null;
                            $toDate = null;

                            if (count($filter_followup_date) == 2) {
                                $fromDate = Helper::convert_date($filter_followup_date[0], "d/m/Y", "Y-m-d");
                                $toDate = Helper::convert_date($filter_followup_date[1], "d/m/Y", "Y-m-d");
                            }
                            if ($fromDate && $toDate) {
                                $query->whereDate((new Followup())->getTable() . '.date_time', ">=", $fromDate);
                                $query->whereDate((new Followup())->getTable() . '.date_time', "<=", $toDate);
                            } elseif ($fromDate) {
                                $query->whereDate((new Followup())->getTable() . '.date_time', $fromDate);
                            }
                            // dd("L-194", $filter_followup_date, $fromDate, $toDate, $query->toSql());
                        }

                        if ($request->has('filter_by_followup_type') && $request->filter_by_followup_type) {
                            if ($request->filter_by_followup_type == "today_all_follow_up") {
                                // $data = $query->whereDate((new Followup())->getTable() . '.date_time', Helper::trait_current_date("Y-m-d"));
                                $data = $query->where(function ($q) {
                                    $q->whereDate((new Followup())->getTable() . '.date_time', Helper::trait_current_date("Y-m-d"));
                                    $q->orWhereDate((new Followup())->getTable() . '.created_at', Helper::trait_current_date("Y-m-d"));
                                });
                            } elseif ($request->filter_by_followup_type == "today_pending_all_follow_up") {
                                $data = $query->whereDate((new Followup())->getTable() . '.date_time', Helper::trait_current_date("Y-m-d"));
                                $data = $query->whereNull((new Followup())->getTable() . '.response_date_time');
                            } elseif ($request->filter_by_followup_type == "pending_all_follow_up") {
                                $data = $query->whereNull((new Followup())->getTable() . '.response_date_time');
                            }
                            // dd("L-128", Helper::interpolateQuery($data->toSql(), $data->getBindings()));
                        }
                    })
                    ->editColumn('date_time', fn($row) => $row->date_time ? Carbon::parse($row->datdate_timee)->format('d-m-Y H:i A') : '-')
                    ->addColumn('company_name', function ($row) {
                        return $row->company->company_name ?? '-';
                    })
                    ->editColumn('created_at', function ($row) {
                        return date('d-m-Y H:i A', strtotime($row->created_at));
                    })
                    ->editColumn('response', function ($row) use ($modules) {
                        if ($modules['add_permission']) {
                            if (empty($row->response_details) && !$row?->deleted_at) {
                                $buttonClass = 'showresponsemodal'; // Default: inquiry
                                $extraData = 'data-inquiry="' . htmlspecialchars(json_encode($row?->inquiry), ENT_QUOTES, 'UTF-8') . '"';

                                if (!empty($row->quotation_id)) {
                                    $quotation = Quotation::findOrFail($row->quotation_id);
                                    $buttonClass = 'showResponseQuotationModal';
                                    $extraData = 'data-quotation="' . htmlspecialchars(json_encode($quotation), ENT_QUOTES, 'UTF-8') . '"';
                                } elseif (!empty($row->customer_id)) {
                                    $customer = Customer::findOrFail($row->customer_id);
                                    $buttonClass = 'showResponseCustomerModal';
                                    $extraData = 'data-customer="' . htmlspecialchars(json_encode($customer), ENT_QUOTES, 'UTF-8') . '"';
                                }

                                return '<button type="button"
                data-company_name="' . $row->company->company_name . '"
                data-inquiry_id="' . ($row->inquiry_id ?? '') . '"
                data-quotation_id="' . ($row->quotation_id ?? '') . '"
                data-customer_id="' . ($row->customer_id ?? '') . '"
                data-id="' . $row->id . '"
                data-company_id="' . $row->company_id . '"
                data-status_id="' . $row->status_id . '"
                class="' . $buttonClass . ' btn btn-primary btn-sm"
                ' . $extraData . '>Response</button>';
                            }
                        }

                        return $row->response_details ?? '-';
                    })



                    ->addColumn('followup_status', function ($row) use ($modules) {
                        $followup_status_color = $row?->followup_status_color;
                        $followup_status_color_2 = $row?->followup_status_color_2 ?? $row?->followup_status_color;
                        $followup_text_color = $row?->followup_text_color ?? '#000';

                        if (empty($row->response_details)) {
                            return '<span class="badge bg-glow w-100" style="border: 0px; background: linear-gradient(45deg, ' . $followup_status_color . ', ' . $followup_status_color_2 . '); color: ' . $followup_text_color . ';">' . $row->followup_status ?? '-' . '</span>';
                        } else {
                            $followupStatus = MarketingStatus::where('company_id', $row->company_id)->where('type', 'Followup')->where('status', 'active')->get();
                            $btn = '';
                            $dropdown = '';


                            // Main button with dynamic background color
                            // style="background-color: ' . htmlspecialchars($row->followup_status_color) . '; color: #000;"
                            $btn .= '<button type="button"
                            class="btn btn-sm w-100 dropdown-toggle waves-effect waves-light"
                            style="border: 0px; background: linear-gradient(45deg, ' . $followup_status_color . ', ' . $followup_status_color_2 . '); color: ' . $followup_text_color . ';"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">'
                                . ucfirst(htmlspecialchars($row->followup_status)) .
                                '</button>';

                            // Dropdown menu (plain or separate styling)
                            $dropdown .= '<ul class="dropdown-menu">';
                            foreach ($followupStatus as $fstatus) {
                                $dropdown .= '<li>
                                    <a href="javascript:void(0)"
                                    class="dropdown-item waves-effect update-status"
                                    data-url="' . route($modules['route'] . '.status-update') . '"
                                    data-id="' . htmlspecialchars($row["id"]) . '"
                                    data-update_status="' . htmlspecialchars($fstatus->id) . '">'
                                    . htmlspecialchars($fstatus->name) .
                                    '</a>
                                </li>';
                            }
                            $dropdown .= '</ul>';
                            return '<div class="dropdown">' . $btn . $dropdown . '</div>';
                        }
                    })
                    ->addColumn('inquiry_no', function ($row) {
                        $display = $row->inquiry_no ?: ($row->quotation_no ?? '-');

                        if ($row->inquiry_id) {
                            $followupCount = Followup::where('inquiry_id', $row->inquiry_id)->count();
                            $companyName = htmlspecialchars($row->company->company_name ?? '', ENT_QUOTES, 'UTF-8');
                            return $display . '<br><span class="badge bg-glow bg-info list-followup cursor-pointer" style="cursor:pointer;" data-inquiry_id="' . $row->inquiry_id . '" data-company_id="' . $row->company_id . '" data-company_name="' . $companyName . '">' . $followupCount . ' Followup' . ($followupCount > 1 ? 's' : '') . '</span>';
                        } elseif ($row->quotation_id) {
                            $followupCount = Followup::where('quotation_id', $row->quotation_id)->count();
                            $companyName = htmlspecialchars($row->company->company_name ?? '', ENT_QUOTES, 'UTF-8');
                            return $display . '<br><span class="badge bg-glow bg-info list-followup-quotation cursor-pointer" style="cursor:pointer;" data-quotation_id="' . $row->quotation_id . '" data-company_id="' . $row->company_id . '" data-company_name="' . $companyName . '">' . $followupCount . ' Followup' . ($followupCount > 1 ? 's' : '') . '</span>';
                        } elseif ($row->customer_id) {
                            $followupCount = Followup::where('customer_id', $row->customer_id)->count();
                            $companyName = htmlspecialchars($row->company->company_name ?? '', ENT_QUOTES, 'UTF-8');
                            return $display . '<br><span class="badge bg-glow bg-info list-followup-customer cursor-pointer" style="cursor:pointer;" data-customer_id="' . $row->customer_id . '" data-company_id="' . $row->company_id . '" data-company_name="' . $companyName . '">' . $followupCount . ' Followup' . ($followupCount > 1 ? 's' : '') . '</span>';
                        }

                        return $display;
                    })

                    ->addColumn('inquiry_created_by_name', function ($row) {
                        return $row->inquiry_created_by_name ?? '-';
                    })
                    ->addColumn('customer_details', function ($row) {
                        $customer_details = '';
                        $customer_name = '-';
                        $contact_person = '';
                        $mobile_no = '-';

                        if ($row->inquiry_id) {
                            $customer_name = $row->customer_name ?? '-';
                            $contact_person = $row->contact_person_name ?? '';
                            $mobile_no = $row->mobile_no ?? '-';

                            $customer_details .= '<a href="javascript:void(0);" class="list-followup"
            data-inquiry_id="' . $row->inquiry_id . '"
            data-id="' . $row->id . '"
            data-company_id="' . $row->company_id . '"
            data-customer_name="' . $customer_name . '"
            data-company_name="' . $row?->company?->company_name . '">' . $customer_name . '</a>';
                        } elseif ($row->quotation_id) {
                            $quotation = Quotation::with('customer')->find($row->quotation_id);

                            $customer_name = $quotation?->customer?->customer_name ?? '-';
                            $contact_person = $quotation?->customer?->contact_person_name ?? '';
                            $mobile_no = $quotation?->customer?->mobile_number ?? '-';

                            $customer_details .= '<a href="javascript:void(0);" class="list-followup-quotation"
            data-quotation_id="' . $row->quotation_id . '"
            data-id="' . $row->id . '"
            data-company_id="' . $row->company_id . '"
            data-company_name="' . $row?->company?->company_name . '">' . $customer_name . '</a>';
                        } elseif ($row->customer_id) {
                            $customer = Customer::find($row->customer_id);

                            $customer_name = $customer?->customer_name ?? '-';
                            $contact_person = $customer?->contact_person_name ?? '';
                            $mobile_no = $customer?->mobile_number ?? '-';

                            $customer_details .= '<a href="javascript:void(0);" class="list-followup-customer"
            data-customer_id="' . $row->customer_id . '"
            data-id="' . $row->id . '"
            data-company_id="' . $row->company_id . '"
            data-company_name="' . $row?->company?->company_name . '">' . $customer_name . '</a>';
                        }

                        if (!empty($contact_person)) {
                            $customer_details .= ', <br> ' . $contact_person;
                        }

                        $customer_details .= '<br><a href="tel: ' . $mobile_no . '"><i class="fa fa-phone"></i> ' . $mobile_no . '</a>';

                        return $customer_details;
                    })


                    /*
                    $name = $row->customer_name ? '<i class="ti tabler-user"></i> ' . $row->customer_name : '-';
                    if(!empty($row->contact_person_name)){
                    $name .= ' -> ' . $row->contact_person_name;
                    }

                    $name = '<a href="javascript:void(0);" class="list-followup" data-inquiry_id="' . $row->inquiry_id . '" data-id="' . $row->id . '" data-company_id="' . $row->company_id . '" data-customer_name="' . $row->customer_name . '">' . $name . '</a>';
                    $mobile_no = '<br><a href="tel: '. $row->mobile_no.'"><i class="fa fa-phone"></i> ' . $row->mobile_no.'</a>';
                    return $name . $mobile_no;
                    */

                    ->addColumn('show_date_time', function ($row) {
                        $date_time = '';

                        $fd_time = $row?->date_time ? strtotime($row->date_time) : null;
                        $now = time();

                        $overduetime = $fd_time && $fd_time < $now;
                        $response = !empty($row->response_details);

                        if ($fd_time) {
                            $fd_label = 'FD :- ' . date('d-m-Y H:i A', $fd_time);

                            // Highlight in red ONLY if it's past and response is empty
                            if ($overduetime && !$response) {
                                $fd_label = '<span style="color:red;">' . $fd_label . '</span>';
                            }

                            $date_time .= $fd_label;
                        }

                        if ($row?->created_at) {
                            $date_time .= '<br>';
                            $date_time .= 'CD :- ' . date('d-m-Y H:i A', strtotime($row->created_at));
                        }

                        return $date_time;
                    })

                    ->addColumn('description', function ($row) {
                        $details = '';
                        if (!empty($row->through)) {
                            $details .= 'By: ' . $row->through;
                        }
                        if (!empty($row->details)) {
                            $details .= '<br><strong>' . $row->details . '</strong>';
                        }
                        return $details;
                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $dropdown_action_text = config('constants.DROPDOWN_ACTION_TEXT');
                        $edit_url = route('inquiry.edit', $row->id);
                        $btn = '';

                        if ($modules['delete_permission']) {
                            if (!$row?->deleted_at) {
                                $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="btn btn-danger btn-icon deletebutton mx-1"><i class="fa-solid fa-trash"></i></i></a>';
                            } else {
                                $btn .= '<a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="btn btn-light mx-1 record-restore" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Restore Data"><i class="ti ti-history"></i> Restore</a>';
                            }
                        }

                        return $btn;
                    })
                    ->addIndexColumn()
                    ->rawColumns(['inquiry_no', 'show_date_time', 'customer_details', 'followup_status', 'response', 'action', 'description'])
                    ->make(true);
            }

            if (!$modules['view_permission']) {
                abort(403, 'Unauthorized');
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
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function store(FollowupRequest $request)
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

        View::share('modules', $modules);

        $validated = $request->validated();



        DB::beginTransaction();
        try {
            if (!$modules['add_permission']) {
                return $this->sendError('Unauthorized', [], [], 403);
            }

            $duplicate = Followup::where('company_id', $validated['company_id'])
                ->where('inquiry_id', $validated['inquiry_id'])
                ->whereDate('date_time', $validated['date_time'])
                ->where('details', $validated['details'])
                ->exists();

            if ($duplicate) {
                return $this->sendError('A followup with the same details and date/time already exists for this inquiry and company.', [], [], 403);
            }

            $validated['created_by'] = $loginUserId;

            if ($request?->date_time) {
                $validated['date_time'] = Helper::convert_date($request?->date_time, "d-m-Y h:i a", "Y-m-d H:i");
            }


            // return $validated;

            $followup = Followup::create($validated);
            $followupId = $followup->id;
            $statusDB = MarketingStatus::where('id', $validated['status_id'])->first();

            $log = FollowupLogs::create([
                'company_id' => $validated['company_id'],
                'inquiry_id' => $validated['inquiry_id'],
                'followup_id' => $followupId,
                'action' => 'Create',
                'status_id' => $validated['status_id'],
                'status_value' => $statusDB->name,
                'json_data' => $followup->toJson(),
                'remarks' => 'Manually Added',
                'created_by' => $loginUserId,
            ]);



            InquiryLogs::create([
                'company_id' => $validated['company_id'],
                'inquiry_id' => $validated['inquiry_id'],
                'followup_id' => $followupId,
                'action' => 'Add Followup',
                'json_data' => $followup->toJson(),
                'created_by' => $loginUserId,
            ]);

            $updateInquiry = [];
            $updateInquiry['followup_status_id'] = $validated['status_id'];
            if ($request?->inquiry_status) {
                $updateInquiry['inquiry_status_id'] = $request?->inquiry_status;
            }
            Inquiry::where('id', $validated['inquiry_id'])->update($updateInquiry);

            InquiryLogs::create([
                'company_id' => $validated['company_id'],
                'inquiry_id' => $validated['inquiry_id'],
                'followup_id' => $followupId,
                'action' => 'Followup Status Changed',
                'status_id' => $validated['status_id'],
                'status_value' => $statusDB->name,
                'created_by' => $loginUserId,
            ]);

            DB::commit();
            return $this->sendResponse([], 'Followup created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->sendError('Validation Error', $e->errors(), [], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Error', $e->getMessage(), [], 500);
        }
    }


    public function show(string $id)
    {
        if ($id = "print") {
            return self::print();
        }
        dd($id);
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

        $dataDelete = Followup::findOrFail($id);
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
            $followup = Followup::withTrashed()->findOrFail($id);
            $followup->restore();
            return Redirect::back()->withSuccess($modules['title'] . ' restored successfully!');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function store_response(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {
            $validated = $request->validate([
                'response' => ['required'],
                'followup_action' => ['required', 'in:Next Follow-up,End Follow-up'],
                'followup_id' => ['required', 'exists:followups,id'],
            ]);

            $followup = Followup::find($validated['followup_id']);
            $followup->response_details = $validated['response'];
            $followup->followup_action = $validated['followup_action'];
            $followup->response_date_time = date('Y-m-d H:i:s');
            $followup->updated_by = $request->login_id;

            if ($validated['followup_action'] == 'End Follow-up') {
                $followup->status_id = $request->status_id;
                $statusDB = MarketingStatus::where('id', $request->status_id)->first();

                FollowupLogs::create([
                    'company_id' => $followup->company_id,
                    'inquiry_id' => $followup->inquiry_id,
                    'followup_id' => $validated['followup_id'],
                    'action' => 'Status Changed',
                    'status_id' => $request->status_id,
                    'status_value' => $statusDB->name,
                    'created_by' => $loginUserId,
                ]);
            } else {
                $statusDB = MarketingStatus::where('id', $request->status_id)->first();
                FollowupLogs::create([
                    'company_id' => $followup->company_id,
                    'inquiry_id' => $followup->inquiry_id,
                    'followup_id' => $validated['followup_id'],
                    'action' => 'Add Response',
                    'status_id' => $request->status_id,
                    'status_value' => $statusDB->name,
                    'json_data' => $followup,
                    'created_by' => $loginUserId,
                ]);
            }
            $followup->save();

            if ($request->has('status_id')) {
                $followup->status_id = $request->status_id;
                $statusDB = MarketingStatus::where('id', $request->status_id)->first();

                $inquiry = Inquiry::findOrFail($followup->inquiry_id);
                $inquiry->followup_status_id = $followup->status_id;
                $inquiry->save();

                InquiryLogs::create([
                    'company_id' => $inquiry->company_id,
                    'inquiry_id' => $inquiry->id,
                    'followup_id' => $validated['followup_id'],
                    'action' => 'Followup Status Changed',
                    'status_id' => $request->status_id,
                    'status_value' => $statusDB->name,
                    'created_by' => $loginUserId,
                ]);
            }
            return $this->sendResponse([], 'Response added successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation failed.', $e->errors(), [], 422);
        } catch (\Exception $e) {
            return $this->sendError('Something want to wrong', $e->getMessage(), [], 500);
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
                $modules[$value . '_permission'] = (!$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (empty($modules['excel_permission'])) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        return Excel::download(
            new FollowupExport($request->all(), $this->authenticateLoginUserDetails),
            'FollowUp-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx'
        );
    }

    public function print(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_id = $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        // Permission Check
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (!$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!$modules['print_permission']) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {
            $FollowupQuery = Followup::with(['company', 'inquiry', 'teamPerson', 'marketingStatus'])
                ->orderBy('id', 'DESC');

            // Company-wise access
            if (Auth::guard('team_person')->check() && $company_id) {
                $FollowupQuery->where('company_id', $company_id);

                if (!empty($modules['personal_data_permission']) && empty($modules['all_data_permission'])) {
                    $FollowupQuery->where('created_by', $loginUserId);
                }
            }

            // Filter: Company
            if ($request->filled('company')) {
                $FollowupQuery->where('company_id', $request->company);
            } else if ($company_id) {
                $FollowupQuery->where('company_id', $company_id);
            }

            // Filter: Search
            if ($request->filled('search')) {
                $search = $request->search;
                $FollowupQuery->whereHas('inquiry', function ($q) use ($search) {
                    $q->where('inquiry_no', 'like', "%$search%")
                        ->orWhere('customer_name', 'like', "%$search%")
                        ->orWhere('mobile_no', 'like', "%$search%")
                        ->orWhere('contact_person_name', 'like', "%$search%");
                });
            }

            // Filter: Created By
            if ($request->filled('filter_created_by')) {
                $FollowupQuery->where('created_by', $request->filter_created_by);
            } else if ($company_id && $modules['parent_type_id']) {
                $FollowupQuery->where('created_by', $loginUserId);
            }

            // Filter: Followup Status
            if ($request->filled('filter_followup_status')) {
                $FollowupQuery->where('status_id', $request->filter_followup_status);
            }

            // Filter: Followup Date Range
            if ($request->filled('filter_followup_date')) {
                $dates = explode(" - ", $request->filter_followup_date);
                if (count($dates) == 2) {
                    $fromDate = Helper::convert_date($dates[0], "d/m/Y", "Y-m-d");
                    $toDate = Helper::convert_date($dates[1], "d/m/Y", "Y-m-d");

                    $FollowupQuery->whereDate('date_time', '>=', $fromDate)
                        ->whereDate('date_time', '<=', $toDate);
                }
            }

            // Filter: Followup Type
            if ($request->has('filter_by_followup_type') && $request->filter_by_followup_type) {
                $followupTable = (new Followup())->getTable();
                $currentDate = Helper::trait_current_date("Y-m-d");

                if ($request->filter_by_followup_type == "today_all_follow_up") {
                    $FollowupQuery->where(function ($q) use ($followupTable, $currentDate) {
                        $q->whereDate($followupTable . '.date_time', $currentDate)
                            ->orWhereDate($followupTable . '.created_at', $currentDate);
                    });
                } elseif ($request->filter_by_followup_type == "today_pending_all_follow_up") {
                    $FollowupQuery->whereDate($followupTable . '.date_time', $currentDate)
                        ->whereNull($followupTable . '.response_date_time');
                } elseif ($request->filter_by_followup_type == "pending_all_follow_up") {
                    $FollowupQuery->whereNull($followupTable . '.response_date_time');
                }
            }

            // Get final data
            $Followup = $FollowupQuery->get();

            return view($modules['folder_path'] . '.print', compact('Followup', 'company_id', 'modules'));
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
            'update_status' => ['required', 'exists:marketing_statuses,id']
        ]);

        if ($validator->fails() && $isAjax) {
            return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
        }

        try {
            $followup = Followup::withTrashed()->findOrFail($request?->id);
            $statusDB = MarketingStatus::findOrFail($request?->update_status);

            if ($followup) {
                $followup->status_id = $request->update_status;
                $followup->save();

                $loginUserId = null;
                if (Auth::guard('admin_software')->check()) {
                    $loginUserId = Auth::guard('admin_software')?->user()?->id;
                } else if (Auth::guard('team_person')->check()) {
                    $loginUserId = Auth::guard('team_person')?->user()?->id;
                }

                FollowupLogs::create([
                    'company_id' => $followup->company_id,
                    'inquiry_id' => $followup->inquiry_id,
                    'followup_id' => $followup->id,
                    'action' => 'Followup Status Changed',
                    'status_id' => $request->update_status,
                    'status_value' => $statusDB->name,
                    'created_by' => $loginUserId,
                ]);

                InquiryLogs::create([
                    'company_id' => $followup->company_id,
                    'inquiry_id' => $followup->inquiry_id,
                    'followup_id' => $followup->id,
                    'action' => 'Followup Status Changed',
                    'status_id' => $request->update_status,
                    'status_value' => $statusDB->name,
                    'created_by' => $loginUserId,
                ]);

                if ($isAjax) {
                    return $this->sendResponse($followup, $modules['title'] . ' status update successfully.');
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

    public function get_followup(Request $request)
    {
        try {
            $inquiry_id = $request->inquiry_id;
            $company_id = $request->company_id;
            $data = Followup::select('followups.*', 'follow_up_reason.name as reason', 'companies.company_name as company_name', 'inquiries.mobile_no', 'inquiries.contact_person_name', 'inquiries.customer_name', 'team_persons.name as created_by_name', 'inquiries.inquiry_no', 'marketing_statuses.name as followup_status', 'marketing_statuses.status_color as followup_status_color', 'marketing_statuses.status_color_2 as followup_status_color_2', 'marketing_statuses.text_color as followup_text_color', 'follow_up_reason.name as followup_reason')
                ->leftJoin('inquiries', 'followups.inquiry_id', '=', 'inquiries.id')
                ->leftJoin('marketing_statuses', 'followups.status_id', '=', 'marketing_statuses.id')
                ->leftJoin('team_persons', 'followups.created_by', '=', 'team_persons.id')
                ->leftJoin('follow_up_reason', 'followups.reason_id', '=', 'follow_up_reason.id')
                ->leftJoin('companies', 'inquiries.company_id', '=', 'companies.id');
            $data = $data->where('followups.inquiry_id', $inquiry_id)->where('followups.company_id', $company_id)->orderBy('followups.date_time', 'desc')->get();

            $extraData = Inquiry::query()->with(['inquiry_status'])->where('id', $inquiry_id)->first();

            return $this->sendResponse($data, 'Followup data retrieved successfully.', ['inquiry' => $extraData]);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), [], 401);
        }
    }

    public function get_followup_by_inquiry(Request $request)
    {
        try {
            $inquiry_id = $request->inquiry_id;
            $company_id = $request->company_id;
            $data = Followup::select('followups.*', 'follow_up_reason.name as reason', 'companies.company_name as company_name', 'inquiries.mobile_no', 'inquiries.contact_person_name', 'inquiries.customer_name', 'team_persons.name as created_by_name', 'inquiries.inquiry_no', 'marketing_statuses.name as followup_status', 'follow_up_reason.name as followup_reason')
                ->leftJoin('inquiries', 'followups.inquiry_id', '=', 'inquiries.id')
                ->leftJoin('marketing_statuses', 'followups.status_id', '=', 'marketing_statuses.id')
                ->leftJoin('team_persons', 'followups.created_by', '=', 'team_persons.id')
                ->leftJoin('follow_up_reason', 'followups.reason_id', '=', 'follow_up_reason.id')
                ->leftJoin('companies', 'inquiries.company_id', '=', 'companies.id');
            $data = $data->where('followups.inquiry_id', $inquiry_id)->where('followups.company_id', $company_id)->orderBy('followups.id', 'desc')->get();
            return $this->sendResponse($data, 'Followup data retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), [], 401);
        }
    }
    public function get_followup_quotation(Request $request)
    {
        try {
            $quotation_id = $request->quotation_id;
            $company_id = $request->company_id;
            $data = Followup::select('followups.*', 'follow_up_reason.name as reason', 'companies.company_name as company_name', 'quotations.phone', 'quotations.contact_person_name', 'team_persons.name as created_by_name', 'quotations.quotation_no', 'marketing_statuses.name as followup_status', 'marketing_statuses.status_color as followup_status_color', 'follow_up_reason.name as followup_reason')
                ->leftJoin('quotations', 'followups.quotation_id', '=', 'quotations.id')
                ->leftJoin('marketing_statuses', 'followups.status_id', '=', 'marketing_statuses.id')
                ->leftJoin('team_persons', 'followups.created_by', '=', 'team_persons.id')
                ->leftJoin('follow_up_reason', 'followups.reason_id', '=', 'follow_up_reason.id')
                ->leftJoin('companies', 'quotations.company_id', '=', 'companies.id');
            $data = $data->where('followups.quotation_id', $quotation_id)->where('followups.company_id', $company_id)->orderBy('followups.date_time', 'desc')->get();

            $extraData = Quotation::query()->where('id', $quotation_id)->first();


            // dd($data);
            return $this->sendResponse($data, 'Followup data retrieved successfully.', ['quotation' => $extraData]);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), [], 401);
        }
    }
    public function quotation_store(Request $request)
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

        View::share('modules', $modules);


        if ($request?->_token) {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    'integer',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
                'quotation_id' => [
                    'required',
                    'integer',
                    Rule::exists((new Quotation())->getTable(), 'id'),
                ],
                'reason_id' => [
                    'required',
                    Rule::exists((new FollowUpReason())->getTable(), 'id'),
                ],
                'status_id' => [
                    'required',
                    Rule::exists((new MarketingStatus())->getTable(), 'id'),
                ],
                'through' => [
                    'required',
                    'in:Call,SMS,Email',
                ],
                'details' => [
                    'required',
                ],
                'date_time' => [
                    'required',
                    // 'date_format:d-m-Y h:i A',
                    function ($attribute, $value, $fail) {
                        try {
                            $date = \Carbon\Carbon::createFromFormat('d-m-Y h:i A', $value);
                            $now = \Carbon\Carbon::now()->subMinutes(30);

                            if ($date->lte($now)) {
                                $fail("The $attribute must be a future date and time (after now).");
                            }
                        } catch (\Exception $e) {
                            $fail("The $attribute is not a valid date and time.");
                        }
                    },

                ],
            ]);

            $validated = $validator->validated();
            // dd($validated);
        }






        DB::beginTransaction();
        try {
            if (!$modules['add_permission']) {
                return $this->sendError('Unauthorized', [], [], 403);
            }

            $duplicate = Followup::where('company_id', $validated['company_id'])
                ->where('quotation_id', $validated['quotation_id'])
                ->whereDate('date_time', $validated['date_time'])
                ->where('details', $validated['details'])
                ->exists();

            if ($duplicate) {
                return $this->sendError('A followup with the same details and date/time already exists for this inquiry and company.', [], [], 403);
            }

            $validated['created_by'] = $loginUserId;

            if ($request?->date_time) {
                $validated['date_time'] = Helper::convert_date($request?->date_time, "d-m-Y h:i a", "Y-m-d H:i");
            }


            // return $validated;
            $followup = Followup::create($validated);
            // dd($followup);

            $followupId = $followup->id;
            $statusDB = MarketingStatus::where('id', $validated['status_id'])->first();

            QuotationLogs::create([
                'company_id' => $validated['company_id'],
                'quotation_id' => $validated['quotation_id'],
                'followup_id' => $followupId,
                'action' => 'Create',
                'status_id' => $validated['status_id'],
                'status_value' => $statusDB->name,
                'json_data' => $followup->toJson(),
                'remarks' => 'Manually Added',
                'created_by' => $loginUserId,
            ]);

            QuotationLogs::create([
                'company_id' => $validated['company_id'],
                'quotation_id' => $validated['quotation_id'],
                'followup_id' => $followupId,
                'action' => 'Add Followup',
                'json_data' => $followup->toJson(),
                'created_by' => $loginUserId,
            ]);

            // $updateQuotation = [];
            // $updateQuotation['followup_status_id'] = $validated['status_id'];

            // Quotation::where('id', $validated['quotation_id'])->update($updateQuotation);

            QuotationLogs::create([
                'company_id' => $validated['company_id'],
                'quotation_id' => $validated['quotation_id'],
                'followup_id' => $followupId,
                'action' => 'Followup Status Changed',
                'status_id' => $validated['status_id'],
                'status_value' => $statusDB->name,
                'created_by' => $loginUserId,
            ]);

            DB::commit();
            return $this->sendResponse([], 'Followup created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->sendError('Validation Error', $e->errors(), [], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Error', $e->getMessage(), [], 500);
        }
    }
    public function store_response_quotation(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {
            $validated = $request->validate([
                'response' => ['required'],
                'followup_action' => ['required', 'in:Next Follow-up,End Follow-up'],
                'followup_id' => ['required', 'exists:followups,id'],
            ]);

            $followup = Followup::find($validated['followup_id']);
            $followup->response_details = $validated['response'];
            $followup->followup_action = $validated['followup_action'];
            $followup->response_date_time = date('Y-m-d H:i:s');
            $followup->updated_by = $request->login_id;

            if ($validated['followup_action'] == 'End Follow-up') {
                $followup->status_id = $request->status_id;
                $statusDB = MarketingStatus::where('id', $request->status_id)->first();

                FollowupLogs::create([
                    'company_id' => $followup->company_id,
                    'quotation_id' => $followup->quotation_id,
                    'followup_id' => $validated['followup_id'],
                    'action' => 'Status Changed',
                    'status_id' => $request->status_id,
                    'status_value' => $statusDB->name,
                    'created_by' => $loginUserId,
                ]);
            } else {
                FollowupLogs::create([
                    'company_id' => $followup->company_id,
                    'quotation_id' => $followup->quotation_id,
                    'followup_id' => $validated['followup_id'],
                    'action' => 'Add Response',
                    'json_data' => $followup,
                    'created_by' => $loginUserId,
                ]);
            }
            $followup->save();

            if ($request->has('status_id')) {
                $followup->status_id = $request->status_id;
                $statusDB = MarketingStatus::where('id', $request->status_id)->first();

                $quotation = Quotation::findOrFail($followup->quotation_id);
                // $quotation->followup_status_id = $followup->status_id;
                $quotation->save();

                QuotationLogs::create([
                    'company_id' => $quotation->company_id,
                    'quotation_id' => $quotation->id,
                    'followup_id' => $validated['followup_id'],
                    'action' => 'Followup Status Changed',
                    'status_id' => $request->status_id,
                    'status_value' => $statusDB->name,
                    'created_by' => $loginUserId,
                ]);
            }
            return $this->sendResponse([], 'Response added successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation failed.', $e->errors(), [], 422);
        } catch (\Exception $e) {
            return $this->sendError('Something want to wrong', $e->getMessage(), [], 500);
        }
    }
    public function get_followup_customer(Request $request)
    {
        try {
            $customer_id = $request->customer_id;
            $company_id = $request->company_id;
            $data = Followup::select('followups.*', 'follow_up_reason.name as reason', 'companies.company_name as company_name', 'customers.mobile_number', 'customers.contact_person_name', 'team_persons.name as created_by_name', 'marketing_statuses.name as followup_status', 'marketing_statuses.status_color as followup_status_color', 'follow_up_reason.name as followup_reason')
                ->leftJoin('customers', 'followups.customer_id', '=', 'customers.id')
                ->leftJoin('marketing_statuses', 'followups.status_id', '=', 'marketing_statuses.id')
                ->leftJoin('team_persons', 'followups.created_by', '=', 'team_persons.id')
                ->leftJoin('follow_up_reason', 'followups.reason_id', '=', 'follow_up_reason.id')
                ->leftJoin('companies', 'customers.company_id', '=', 'companies.id');
            $data = $data->where('followups.customer_id', $customer_id)->where('followups.company_id', $company_id)->orderBy('followups.date_time', 'desc')->get();
            // dd($data);
            $extraData = Customer::query()->where('id', $customer_id)->first();


            // dd($data);
            return $this->sendResponse($data, 'Followup data retrieved successfully.', ['customers' => $extraData]);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), [], 401);
        }
    }
    public function customer_store(Request $request)
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

        View::share('modules', $modules);


        if ($request?->_token) {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    'integer',
                    Rule::exists((new Company())->getTable(), 'id'),
                ],
                'customer_id' => [
                    'required',
                    'integer',
                    Rule::exists((new Customer())->getTable(), 'id'),
                ],
                'reason_id' => [
                    'required',
                    Rule::exists((new FollowUpReason())->getTable(), 'id'),
                ],
                'status_id' => [
                    'required',
                    Rule::exists((new MarketingStatus())->getTable(), 'id'),
                ],
                'through' => [
                    'required',
                    'in:Call,SMS,Email',
                ],
                'details' => [
                    'required',
                ],
                'date_time' => [
                    'required',
                    // 'date_format:d-m-Y h:i A',
                    function ($attribute, $value, $fail) {
                        try {
                            $date = \Carbon\Carbon::createFromFormat('d-m-Y h:i A', $value);
                            $now = \Carbon\Carbon::now()->subMinutes(30);

                            if ($date->lte($now)) {
                                $fail("The $attribute must be a future date and time (after now).");
                            }
                        } catch (\Exception $e) {
                            $fail("The $attribute is not a valid date and time.");
                        }
                    },

                ],
            ]);

            $validated = $validator->validated();
            // dd($validated);
        }






        DB::beginTransaction();
        try {
            if (!$modules['add_permission']) {
                return $this->sendError('Unauthorized', [], [], 403);
            }

            $duplicate = Followup::where('company_id', $validated['company_id'])
                ->where('customer_id', $validated['customer_id'])
                ->whereDate('date_time', $validated['date_time'])
                ->where('details', $validated['details'])
                ->exists();

            if ($duplicate) {
                return $this->sendError('A followup with the same details and date/time already exists for this inquiry and company.', [], [], 403);
            }

            $validated['created_by'] = $loginUserId;

            if ($request?->date_time) {
                $validated['date_time'] = Helper::convert_date($request?->date_time, "d-m-Y h:i a", "Y-m-d H:i");
            }


            // return $validated;
            $followup = Followup::create($validated);
            // dd($followup);

            $followupId = $followup->id;
            $statusDB = MarketingStatus::where('id', $validated['status_id'])->first();

            CustomerLogs::create([
                'company_id' => $validated['company_id'],
                'customer_id' => $validated['customer_id'],
                'followup_id' => $followupId,
                'action' => 'Create',
                'status_id' => $validated['status_id'],
                'status_value' => $statusDB->name,
                'json_data' => $followup->toJson(),
                'remarks' => 'Manually Added',
                'created_by' => $loginUserId,
            ]);


            CustomerLogs::create([
                'company_id' => $validated['company_id'],
                'customer_id' => $validated['customer_id'],
                'followup_id' => $followupId,
                'action' => 'Add Followup',
                'json_data' => $followup->toJson(),
                'created_by' => $loginUserId,
            ]);

            // $updateQuotation = [];
            // $updateQuotation['followup_status_id'] = $validated['status_id'];

            // Quotation::where('id', $validated['quotation_id'])->update($updateQuotation);

            CustomerLogs::create([
                'company_id' => $validated['company_id'],
                'customer_id' => $validated['customer_id'],
                'followup_id' => $followupId,
                'action' => 'Followup Status Changed',
                'status_id' => $validated['status_id'],
                'status_value' => $statusDB->name,
                'created_by' => $loginUserId,
            ]);

            DB::commit();
            return $this->sendResponse([], 'Followup created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->sendError('Validation Error', $e->errors(), [], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Error', $e->getMessage(), [], 500);
        }
    }
    public function store_response_customer(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {
            $validated = $request->validate([
                'response' => ['required'],
                'followup_action' => ['required', 'in:Next Follow-up,End Follow-up'],
                'followup_id' => ['required', 'exists:followups,id'],
            ]);

            $followup = Followup::find($validated['followup_id']);
            $followup->response_details = $validated['response'];
            $followup->followup_action = $validated['followup_action'];
            $followup->response_date_time = date('Y-m-d H:i:s');
            $followup->updated_by = $request->login_id;

            if ($validated['followup_action'] == 'End Follow-up') {
                $followup->status_id = $request->status_id;
                $statusDB = MarketingStatus::where('id', $request->status_id)->first();

                FollowupLogs::create([
                    'company_id' => $followup->company_id,
                    'customer_id' => $followup->customer_id,
                    'followup_id' => $validated['followup_id'],
                    'action' => 'Status Changed',
                    'status_id' => $request->status_id,
                    'status_value' => $statusDB->name,
                    'created_by' => $loginUserId,
                ]);
            } else {
                FollowupLogs::create([
                    'company_id' => $followup->company_id,
                    'customer_id' => $followup->customer_id,
                    'followup_id' => $validated['followup_id'],
                    'action' => 'Add Response',
                    'json_data' => $followup,
                    'created_by' => $loginUserId,
                ]);
            }
            $followup->save();

            if ($request->has('status_id')) {
                $followup->status_id = $request->status_id;
                $statusDB = MarketingStatus::where('id', $request->status_id)->first();

                $customer = Customer::findOrFail($followup->customer_id);
                // $quotation->followup_status_id = $followup->status_id;
                $customer->save();

                CustomerLogs::create([
                    'company_id' => $customer->company_id,
                    'customer_id' => $customer->id,
                    'followup_id' => $validated['followup_id'],
                    'action' => 'Followup Status Changed',
                    'status_id' => $request->status_id,
                    'status_value' => $statusDB->name,
                    'created_by' => $loginUserId,
                ]);
            }
            return $this->sendResponse([], 'Response added successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation failed.', $e->errors(), [], 422);
        } catch (\Exception $e) {
            return $this->sendError('Something want to wrong', $e->getMessage(), [], 500);
        }
    }
}
