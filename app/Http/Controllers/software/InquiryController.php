<?php

namespace App\Http\Controllers\software;

use App\Exports\InquiryExport;
use App\Helpers\Helper;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\Controller;
use App\Http\Requests\InquiryRequest;
use App\Imports\InquiryImport;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\ExpoVisitor;
use App\Models\Inquiry;
use App\Models\MasterCountry;
use App\Models\AdminSoftware;
use App\Models\InquiryProduct;
use App\Models\MarketingStatus;
use App\Models\InquiryLogs;
use App\Models\Followup;
use App\Models\FollowupLogs;
use Illuminate\Support\Facades\Hash;
use App\Models\InquiryImportFile;
use App\Models\TeamPerson;
use App\Models\ProductVariantPrice;
use App\Models\Quotation;
use App\Models\SourceOfInquiry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;


class InquiryController extends Controller
{
    public $modules = [];

    public function __construct()
    {

        parent::__construct();

        $this->modules = [
            'title' => 'Inquiry',
            'folder_path' => 'software.modules.inquiry',
            'route' => 'inquiry',
            'table_name' => (new Inquiry())->getTable(),
            'permisstion_prefix' => 'inquiry',
            'module_name' => 'Inquiry',
            'followup_module_name' => 'Follow Up',
            'customer_module_name' => 'Customer',
            'authLoginUserDetail' => null,
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null,
        ];
    }

    public function index(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        $modules['convertToCustomerPermission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Inquiry']);
        $modules['convertToQuatationPermission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Quotation']);

        $modules['addFollowupPermission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Inquiry']);

        $modules['product_view'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Product']);
        $modules['followup_add'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.add'), 'Follow Up']);
        // dd( $modules['convertToCustomerPermission']);
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
        // dd($modules);
        View::share('modules', $modules);

        try {

            $inquiryLimitReached = null;
            if ($modules['company_id']) {
                $company = Company::find($modules['company_id']);
                $currentInquiryCount = Inquiry::where('company_id', $company->id)->withTrashed()->count();
                $inquiryLimitReached = $company && $currentInquiryCount >= $company->max_inquiry_count;
                // dd($currentInquiryCount, $company->max_inquiry_count, $inquiryLimitReached);
            }
            View::share('inquiryLimitReached', $inquiryLimitReached);

            $columns = [
                // (object)[ 'data' => "id", 'name' => 'id', 'td_label' => 'Id' ],
                (object) ['data' => 'assign_checkbox', 'name' => 'assign_checkbox', 'orderable' => false, 'searchable' => false, 'className' => 'text-center w-5', 'td_label' => 'Select', 'orderable' => false],

                (object) ['data' => "inquiry_no", 'name' => 'inquiry_no', 'td_label' => 'Inquiry No', 'className' => 'text-wrap', 'orderable' => true],
                (object) ['data' => "inquiry_date", 'name' => 'inquiry_date', 'td_label' => 'Inquiry Date', 'className' => '', 'orderable' => true],
                (object) ['data' => "inquiry_status", 'name' => 'inquiry_status', 'td_label' => 'Inquiry Status', 'className' => 'text-wrap', 'orderable' => true],
                // (object)['data' => "customer_name", 'name' => 'customer_name', 'td_label' => 'Customer Name', 'className' =>  'text-wrap'],
                (object) ['data' => "contact_details", 'name' => 'contact_details', 'td_label' => 'Contact Details', 'orderable' => false, 'searchable' => false, 'className' => 'text-wrap', 'orderable' => true],

                (object) ['data' => "followup_status", 'name' => 'followup_status', 'td_label' => 'Followup Status', 'className' => 'text-wrap', 'orderable' => true],
                // (object)['data' => "mobile_no", 'name' => 'mobile_no', 'td_label' => 'Mobile No.', 'className' =>  ''],
                (object) ['data' => "source_of_inquiry", 'name' => 'source_of_inquiry', 'td_label' => 'Source of Inquiry', 'className' => 'text-wrap', 'orderable' => true],
                (object) ['data' => "requirement_details", 'name' => 'requirement_details', 'td_label' => 'Requirement Details', 'className' => 'text-wrap', 'orderable' => true],
                // (object)['data' => "location", 'name' => 'location', 'td_label' => 'Location', 'className' =>  'text-wrap'],
                (object) ['data' => "location", 'name' => 'location', 'td_label' => 'Location', 'orderable' => false, 'searchable' => false, 'className' => 'text-wrap', 'orderable' => true],

                (object) ['data' => "created_by_name", 'name' => 'created_by_name', 'td_label' => 'Created by', 'className' => 'text-wrap', 'orderable' => true],
                (object) ['data' => "assigned_to_name", 'name' => 'assigned_to_name', 'td_label' => 'Assigned', 'className' => 'text-wrap', 'orderable' => true],
                (object) ['data' => "company", 'name' => 'company', 'td_label' => 'Company name', 'className' => 'text-wrap', 'orderable' => true],
                // (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                (object) ['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'w-10 text-center', 'orderable' => false],

            ];
            if ($modules['company_id']) {
                // array_unshift($columns, (object)['data' => "company_name", 'name' => 'company_name', 'td_label' => 'Company Name', 'className' =>  '']);
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'company';
                });

                $columns = array_values($columns);
            }
            // dd($modules, !$modules['company_id'], $columns);
            View::share("columns", $columns);

            if ($request->ajax()) {
                if (!$modules['view_permission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }
                // $loginUserId = Auth::guard('team_person')->id();


                $data = Inquiry::select('*')
                    ->with(['company', 'inquiry_status', 'followup_status', 'source_of_inquiry', 'created_by_name', 'assigned_to_name', 'country', 'state', 'city'])
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check() && $modules['company_id']) {
                            $query->where('company_id', $modules['company_id']);
                            // $query->orWhere('inquiry_created_by', $loginUserId);
                            // $query->whereRaw('FIND_IN_SET(?, inquiry_assigned_to)', [$loginUserId]);
                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where(function ($q) use ($loginUserId) {
                                    $q->orWhere('inquiry_created_by', $loginUserId)
                                        ->orWhereRaw('FIND_IN_SET(?, inquiry_assigned_to)', [$loginUserId]);
                                    // $query->whereRaw("FIND_IN_SET(?, inquiry_assigned_to)", [$request->filter_assigned_to]);
                                });
                            }
                        }
                    })


                    ->where(function ($query) use ($request) {
                        if ($request->get('show_lost')) {
                            $query->whereHas('inquiry_status', function ($q) {
                                $q->where('display_order', '<', 0);
                            });
                        } else {
                            $query->whereHas('inquiry_status', function ($q) {
                                $q->where('display_order', '>', 0);
                            });
                        }
                    });

                //->orderBy((new Inquiry())->getTable() . '.id', 'desc');
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }
                // return $data->get()
                // dd("L-176", Helper::interpolateQuery($data->toSql(), $data->getBindings()), $modules);

                return DataTables::of($data)
                    ->orderColumn('inquiry_status', function ($query, $order) {
                        $query->orderBy(
                            MarketingStatus::select('name')
                                ->whereColumn((new MarketingStatus())->getTable() . '.id', (new Inquiry())->getTable() . '.inquiry_status_id'),
                            $order
                        );
                    })

                    ->orderColumn('followup_status', function ($query, $order) {
                        $query->orderBy(
                            MarketingStatus::select('name')
                                ->whereColumn((new MarketingStatus())->getTable() . '.id', (new Inquiry())->getTable() . '.followup_status_id'),
                            $order
                        );
                    })

                    ->orderColumn('source_of_inquiry', function ($query, $order) {
                        $query->orderBy(
                            SourceOfInquiry::select('name')
                                ->whereColumn((new SourceOfInquiry())->getTable() . '.id', (new Inquiry())->getTable() . '.source_of_inquiry_id'),
                            $order
                        );
                    })
                    ->orderColumn('company', function ($query, $order) {
                        $query->orderBy(
                            Company::select('company_name')
                                ->whereColumn((new Company())->getTable() . '.id', (new Inquiry())->getTable() . '.company_id'),
                            $order
                        );
                    })
                    ->orderColumn('created_by_name', function ($query, $order) {
                        $teamTable = (new TeamPerson())->getTable();
                        $adminTable = (new AdminSoftware())->getTable();
                        $inquiryTable = (new Inquiry())->getTable();

                        $query->orderByRaw("
                            CASE
                                WHEN {$inquiryTable}.created_type = 'Team' THEN
                                    (SELECT name FROM {$teamTable}
                                    WHERE {$teamTable}.id = {$inquiryTable}.inquiry_created_by)
                                ELSE
                                    (SELECT name FROM {$adminTable}
                                    WHERE {$adminTable}.id = {$inquiryTable}.inquiry_created_by)
                            END {$order}
                        ");
                    })
                    ->orderColumn('assigned_to_name', function ($query, $order) {
                        $query->orderBy(
                            TeamPerson::select('name')
                                ->whereColumn((new TeamPerson())->getTable() . '.id', (new Inquiry())->getTable() . '.inquiry_assigned_to'),
                            $order
                        );
                    })
                    ->orderColumn('location', function ($query, $order) {

                        $countryTable = (new MasterCountry())->getTable();
                        $inquiryTable = (new Inquiry())->getTable();

                        $query->orderBy(
                            MasterCountry::select('name')
                                ->whereColumn("{$countryTable}.id", "{$inquiryTable}.country_id"),
                            $order
                        );
                    })
                    ->orderColumn('contact_details', function ($query, $order) {
                        $inquiryTable = (new Inquiry())->getTable();
                        $query->orderBy("{$inquiryTable}.customer_name", $order);
                    })
                    ->orderColumn('inquiry_no', function ($query, $order) {
                        $query->orderBy('id', $order);
                    })
                    ->addIndexColumn()
                    ->filter(function ($query) use ($request) {
                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }
                        if ($request->has('search') && $request->search) {
                            $searchTerm = $request->search;

                            $query->where(function ($q) use ($searchTerm) {
                                $q->where('customer_name', 'like', "%{$searchTerm}%")
                                    ->orWhere('mobile_no', 'like', "%{$searchTerm}%")
                                    ->orWhere('contact_person_name', 'like', "%{$searchTerm}%")
                                    ->orWhere('inquiry_no', 'like', "%{$searchTerm}%");

                                // Add company_id as a direct match (or make it like search if needed)
                                if (is_numeric($searchTerm)) {
                                    $q->orWhere('company_id', $searchTerm);
                                }
                            });
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
                        if ($request->has('filter_inquiry_status') && $request->filter_inquiry_status) {
                            $query->where('inquiry_status_id', $request->filter_inquiry_status);
                        }
                        if ($request->has('filter_followup_status') && $request->filter_followup_status) {
                            $query->where('followup_status_id', $request->filter_followup_status);
                        }
                        if ($request->has('filter_source_of_inquiry') && $request->filter_source_of_inquiry) {
                            $query->where('source_of_inquiry_id', 'like', "%" . $request->filter_source_of_inquiry . "%");
                        }
                        if ($request->has('filter_created_by') && $request->filter_created_by) {
                            $query->where('inquiry_created_by', $request->filter_created_by);
                        }
                        if ($request->has('filter_assigned_to') && $request->filter_assigned_to) {
                            $query->whereRaw("FIND_IN_SET(?, inquiry_assigned_to)", [$request->filter_assigned_to]);
                        }
                        if ($request->has('filter_inquiry_date') && $request->filter_inquiry_date) {
                            $filter_inquiry_date = $request->filter_inquiry_date;
                            $filter_inquiry_date = explode(" to ", $filter_inquiry_date);
                            $fromDate = null;
                            $toDate = null;

                            if (count($filter_inquiry_date) == 2) {
                                $fromDate = Helper::convert_date($filter_inquiry_date[0], "d/m/Y", "Y-m-d");
                                $toDate = Helper::convert_date($filter_inquiry_date[1], "d/m/Y", "Y-m-d");
                            }
                            if ($fromDate && $toDate) {
                                $query->whereDate((new Inquiry())->getTable() . '.inquiry_date', ">=", $fromDate);
                                $query->whereDate((new Inquiry())->getTable() . '.inquiry_date', "<=", $toDate);
                            } elseif ($fromDate) {
                                $query->whereDate((new Inquiry())->getTable() . '.inquiry_date', $fromDate);
                            }
                        }

                        if ($request->has('filter_followup_date') && $request->filter_followup_date) {
                            $query->where('followup_date', 'like', "%" . $request->filter_followup_date . "%");
                        }
                    })
                    ->editColumn('requirement_details', function ($row) {
                        return $row->requirement_details ?? '-';
                    })
                    ->editColumn('show_dates', function ($row) {
                        $show_dates = '';
                        if ($row->inquiry_date) {
                            $show_dates .= Carbon::parse($row->inquiry_date)->format('d-m-Y');
                        }
                        return $show_dates;
                    })
                    ->editColumn('inquiry_date', function ($row) {
                        return Helper::convert_date($row->inquiry_date, 'Y-m-d', 'd-m-Y');
                    })
                    ->addColumn('location', function ($row) {
                        $country = $row->country->name ?? '';
                        $state = $row->state->name ?? '';
                        $city = $row->city->name ?? '';

                        $locationParts = [];

                        if ($country) {
                            $locationParts[] = '<b>Country:</b> ' . $country;
                        }
                        if ($state) {
                            $locationParts[] = '<b>State:</b> ' . $state;
                        }
                        if ($city) {
                            $locationParts[] = '<b>City:</b> ' . $city;
                        }

                        $location = implode('<br>', $locationParts);
                        $address = $row->address ?? '';

                        return $address . '<br><small>' . $location . '</small>';
                    })


                    ->addColumn('assigned_to_name', function ($row) {
                        $names = $row->assigned_to_name;
                        return !empty($names) ? implode(', ', $names) : '-';
                    })



                    ->addColumn('created_by_name', function ($row) {
                        return $row->created_by_name;
                    })
                    ->addColumn('source_of_inquiry', function ($row) {
                        return $row->source_of_inquiry && $row->source_of_inquiry->name
                            ? $row->source_of_inquiry->name
                            : 'N/A';
                    })


                    ->addColumn('company', function ($row) {
                        return $row->company->company_name;
                    })
                    ->addColumn('inquiry_status', function ($row) use ($modules) {
                        // return $row?->inquiry_status;
                        $inquiryStatusList = MarketingStatus::where('company_id', $row->company_id)
                            ->where('type', 'Inquiry')
                            ->where('status', 'active')
                            ->get();

                        // Handle case when inquiry_status is NULL
                        if (!$row->inquiry_status) {
                            return '<span class="badge bg-secondary">No Status</span>';
                        }
                        $status_color = $row?->inquiry_status?->status_color;
                        $status_color_2 = $row?->inquiry_status?->status_color_2 ?? $row?->inquiry_status?->status_color;
                        $text_color = $row?->inquiry_status?->text_color ?? '#000';
                        // style="background-color: ' . htmlspecialchars($row->inquiry_status->status_color) . '; color: #000;"
                        $btn = '<button type="button"
                        class="btn btn-sm w-100 dropdown-toggle waves-effect waves-light"
                        style="border: 0px; background: linear-gradient(45deg, ' . $status_color . ', ' . $status_color_2 . '); color: ' . $text_color . ';"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">'
                            . ucfirst(htmlspecialchars($row->inquiry_status->name)) .
                            '</button>';

                        $dropdown = '<ul class="dropdown-menu">';
                        foreach ($inquiryStatusList as $istatus) {
                            $dropdown .= '<li>
                            <a href="javascript:void(0)"
                            class="dropdown-item waves-effect update-status"
                            data-url="' . route($modules['route'] . '.status-update') . '"
                            data-id="' . htmlspecialchars($row["id"]) . '"
                            data-update_status="' . htmlspecialchars($istatus->id) . '">'
                                . htmlspecialchars($istatus->name) . '
                            </a>
                        </li>';
                        }
                        $dropdown .= '</ul>';

                        return '<div class="dropdown">' . $btn . $dropdown . '</div>';
                    })

                    ->addColumn('followup_status', function ($row) use ($modules) {
                        if (!$row->followup_status || $row->followup_status->name === null || $row->followup_status->name === '-') {
                            return 'N/A';
                        }

                        $modelData = [];

                        if (!$modules['company_id']) {
                            $modelData['company_name'] = $row->company->company_name;
                        }

                        $modelData['inquiry_no'] = $row?->inquiry_no;
                        $modelData['inquiry_date'] = $row?->inquiry_date;
                        $modelData['customer_name'] = $row->customer_name;
                        $modelData['mobile_no'] = $row->mobile_no;

                        $jsonModelData = htmlspecialchars(json_encode($modelData), ENT_QUOTES, 'UTF-8');

                        return '<a href="javascript:void(0)" class="followup-list"
                        data-company_name="' . $row->company->company_name . '"
                        data-company_id="' . $row->company_id . '"
                        data-inquiry_id="' . $row->id . '"
                        data-modelData="' . $jsonModelData . '">' . $row->followup_status->name . '</a>';
                    })



                    // ->editColumn('mobile_no', function ($row) {
                    //     $mobile_display = $row->mobile_no ?: '-';
                    //     $mobile_number = preg_replace('/[^0-9]/', '', $row->mobile_no);
                    //     $tel_link = !empty($mobile_number) ? "tel:{$mobile_number}" : '#';

                    //     // Show only dialer link (no WhatsApp icon)
                    //     $mobile = $row->mobile_no
                    //         ? '<i class="fa-solid fa-mobile"></i>
                    //     <a href="' . $tel_link . '" style="margin-right:6px" title="Call">' . $mobile_display . '</a>'
                    //         : '-';

                    //     $whatsapp = '';
                    //     if (!empty($row->whatsapp_no)) {
                    //         $wa_number = preg_replace('/[^0-9]/', '', $row->whatsapp_no);
                    //         $message = urlencode("");
                    //         $wa_link = "https://wa.me/{$wa_number}?text={$message}";
                    //         $whatsapp = '<br><i class="fab fa-whatsapp"></i> <a href="' . $wa_link . '" target="_blank">' . $row->whatsapp_no . '</a>';
                    //     }

                    //     $email = '';
                    //     if (!empty($row->email)) {
                    //         $email_address = $row->email;
                    //         $gmail_link = 'https://mail.google.com/mail/?view=cm&fs=1&to=' . urlencode($email_address);
                    //         $email = '<br><i class="fa-solid fa-envelope"></i> <a href="' . $gmail_link . '" target="_blank">' . $email_address . '</a>';
                    //     }

                    //     return $mobile . $whatsapp . $email;
                    // })

                    ->addColumn('contact_details', function ($row) {
                        $html = '<div style="line-height: 1.4;">';

                        // Customer Name
                        $customer_name = $row->customer_name ?? '-';
                        $html .= '<div><i class="fa-solid fa-building text-primary me-1"></i> <strong>' . e($customer_name) . '</strong></div>';

                        // Contact Person
                        $contact_person = $row->contact_person_name ?? '-';
                        $html .= '<div><i class="fa-solid fa-user text-info me-1"></i> ' . e($contact_person) . '</div>';

                        // Mobile Number
                        $mobile = $row->mobile_no ?? '-';
                        $mobile_clean = preg_replace('/\D/', '', $mobile);
                        $html .= '<div><i class="fa-solid fa-phone me-1"></i> <a href="tel:' . $mobile_clean . '">' . e($mobile) . '</a></div>';

                        // WhatsApp
                        if (!empty($row->whatsapp_no)) {
                            $wa = preg_replace('/\D/', '', $row->whatsapp_no);
                            $html .= '<div><i class="fa-brands fa-whatsapp text-success me-1"></i> <a href="https://wa.me/' . $wa . '" target="_blank">' . e($row->whatsapp_no) . '</a></div>';
                        }
                        //Mail
                        if (!empty($row->email)) {
                            $html .= '<div>
                            <i class="fa-solid fa-envelope text-danger me-1"></i>
                            <a href="https://mail.google.com/mail/?view=cm&fs=1&to=' . urlencode($row->email) . '" target="_blank">
                                ' . e($row->email) . '
                            </a>
                        </div>';
                        }

                        $html .= '</div>';
                        return $html;
                    })


                    // ->addColumn('assign_checkbox', function ($row) {
                    //     return '<input type="checkbox" class="assign-checkbox form-check-input" data-company_id="' . $row->company_id . '" name="assign_to[]" value="' . $row->id . '">';
                    // })

                    ->addColumn('assign_checkbox', function ($row) {
                        return '<input type="checkbox" class="assign-checkbox form-check-input"
                        data-company-id="' . $row->company_id . '"
                        name="assign_to[]"
                        value="' . $row->id . '">';
                    })

                    ->addColumn('action', function ($row) use ($modules) {
                        $action = '';
                        $action .= '<div class="dropdown" style="display:inline-block">
                    <button class="btn btn-icon waves-effect waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu">';

                        if (!$row?->deleted_at) {
                            if ($modules['update_permission']) {
                                $action .= '<li>
                            <a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="dropdown-item mx-1">
                                <i class="fa-solid fa-pen-to-square mt-1 me-1"></i> Edit
                            </a>
                        </li>';
                            }

                            $customerExists = \App\Models\Customer::where('company_id', $row->company_id)
                                ->where('mobile_number', $row->mobile_no)
                                ->exists();

                            if ($modules['convertToCustomerPermission'] && !$customerExists) {
                                $action .= '<li>
                            <a href="' . route($modules["route"] . ".convert-to-customer", [$row["id"]]) . '" class="dropdown-item convert-to-customer mx-1" target="_blank">
                                <i class="fa-solid fa-user mt-1 me-1"></i> Convert to Customer
                            </a>
                        </li>';
                            }

                            if ($modules['followup_add']) {
                                $action .= '<li>
                            <a href="javascript:void(0)" data-company_id="' . $row->company_id . '" data-company_name="' . $row->company->company_name . '" data-id="' . $row->id . '" class="dropdown-item add-followup mx-1" data-inquiry="' . htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') . '">
                                <i class="fa-solid fa-plus mt-1 me-1"></i> Add Followup
                            </a>
                        </li>';
                            }

                            $sentCount = (int) ($row->whatsapp_general_sent_count ?? 0);
                            $countBadge = $sentCount > 0 ? ' <span class="badge bg-success rounded-pill ms-1" style="font-size: 10px;">' . $sentCount . '</span>' : '';
                            $action .= '<li>
                            <a href="javascript:void(0)" data-id="' . $row->id . '" class="dropdown-item send-general-whatsapp mx-1">
                                <i class="fa-brands fa-whatsapp text-success mt-1 me-1"></i> Send WhatsApp' . $countBadge . '
                            </a>
                        </li>';

                            if ($modules['convertToQuatationPermission']) {
                                $action .= '<li>
                            <a href="' . route($modules["route"] . ".inquiry-to-quatation", [$row["id"]]) . '" class="dropdown-item inquiry-to-quatation mx-1" target="_blank">
                                 <i class="fa-solid fa-file-pen mt-1 me-1"></i> Add Quotation
                            </a>
                        </li>';
                            }
                            // if ($modules['followup_add']) {
                            //     $action .= '<li>
                            //     <a href="' . route('quotation.create') . '"
                            //     class="dropdown-item mx-1">
                            //     <i class="fa-solid fa-file-pen mt-1 me-1"></i> Add Quotation
                            //     </a>
                            // </li>';
                            // }
    

                            if ($modules['add_permission']) {
                                $action .= '<li>
                            <a href="javascript:void(0);" class="dropdown-item mx-0 open-single-assign-modal" data-company_id="' . $row->company_id . '" data-selectedinquiryassignedto="' . $row->inquiry_assigned_to . '" data-inquiry_id="' . $row->id . '">
                                <i class="fa-solid ti ti-user mt-1 me-1"></i> Assign to
                            </a>
                        </li>';
                            }

                            if ($modules['delete_permission']) {
                                $action .= '<li>
                            <a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="dropdown-item deletebutton mx-1">
                                <i class="fa-solid fa-trash mt-1 me-1"></i> Delete
                            </a>
                        </li>';
                            }
                        } else {
                            $action .= '<li>
                        <a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="record-restore dropdown-item" data-id="' . $row->id . '">
                            Restore
                        </a>
                    </li>';
                        }

                        $action .= '</ul></div>';
                        return $action;
                    })
                    ->editColumn('inquiry_no', function ($row) {
                        $count = 0;
                        if (!empty($row->mobile_no)) {
                            $count = \App\Models\Inquiry::where('company_id', $row->company_id)
                                ->where('mobile_no', $row->mobile_no)
                                ->count();
                        }

                        $html = '<div>' . htmlspecialchars($row->inquiry_no) . '</div>';
                        if ($count > 1) {
                            $html .= '<div style="margin-top: 4px;">';
                            $html .= '<span class="badge bg-danger duplicate-count-badge" style="cursor: pointer;" data-mobile="' . htmlspecialchars($row->mobile_no) . '" data-company-id="' . htmlspecialchars($row->company_id) . '">' . $count . ' Inquiries</span>';
                            $html .= '</div>';
                        }
                        return $html;
                    })
                    ->setRowClass(function ($row) {
                        if (!empty($row->mobile_no)) {
                            $count = \App\Models\Inquiry::where('company_id', $row->company_id)
                                ->where('mobile_no', $row->mobile_no)
                                ->count();
                            if ($count > 1) {
                                return 'duplicate-row-highlight';
                            }
                        }
                        return '';
                    })
                    ->rawColumns(['inquiry_no', 'inquiry_date', 'action', 'followup_status', 'location', 'contact_details', 'inquiry_status', 'requirement_details', 'assign_checkbox'])
                    ->make(true);
            }

            View::share('modules', $modules);
            if (!$modules['view_permission']) {
                abort(403, 'Unauthorized');
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
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;

        $modules['product_view'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Product']);
        // dd($modules['currentGuard']);
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
        $created_type = null;
        if ($modules['currentGuard'] === 'team_person') {
            $created_type = 'Team';
        } elseif ($modules['currentGuard'] === 'admin_software') {
            $created_type = 'Admin';
        }

        View::share('created_type', $created_type);
        View::share("modules", $modules);

        try {

            $currentInquiryCount = -1;
            if ($modules['company_id']) {
                $company = \App\Models\Company::find($modules['company_id']);

                $currentInquiryCount = $modules['restore_permission']
                    ? Inquiry::withTrashed()
                        ->where('company_id', $company->id)
                        ->where('created_by', $loginUserId)
                        ->count()
                    : Inquiry::where('company_id', $company->id)
                        ->where('created_by', $loginUserId)
                        ->count();

                if ($company && $currentInquiryCount >= $company->max_inquiry_count) {
                    return redirect()->route($modules['route'] . '.index')
                        ->withErrors('Inquiry limit reached as per your plan. Please upgrade to add more.');
                }
            }
            View::share("currentInquiryCount", $currentInquiryCount);

            $convertToInquiry = Session::get('newRequest');
            if ($convertToInquiry) {

                $sourceOfInquiry = SourceOfInquiry::firstWhere([
                    'company_id' => $convertToInquiry['company_id'],
                    'name' => "Visitor Book"
                ]);
                if (!$sourceOfInquiry) {
                    $sourceOfInquiry = SourceOfInquiry::create([
                        'company_id' => $convertToInquiry['company_id'],
                        'name' => "Visitor Book",
                        'created_by' => $loginUserId
                    ]);
                }
                $convertToInquiry['source_of_inquiry_id'] = $sourceOfInquiry?->id;

                if ($convertToInquiry && $convertToInquiry?->customer_name) {
                    View::share('convertToInquiry', $convertToInquiry);
                    // Session::forget('newRequest');
                }
            }
            // return $convertToInquiry;

            $company = Company::find($modules['company_id']);

            if ($modules['currentGuard'] === 'admin_software') {
                $mobile_min = 10;
                $mobile_max = 10;
            } else {
                $mobile_min = $company?->mobile_min;
                $mobile_max = $company?->mobile_max;
            }

            View::share('modules', $modules);

            $defaultInquiryStatus = MarketingStatus::where('type', 'Inquiry')
                ->where('is_default', 1)
                ->where('company_id', $modules['company_id'])
                ->first();
            View::share("defaultInquiryStatus", $defaultInquiryStatus);

            $defaultFollowupStatus = MarketingStatus::where('type', 'Followup')
                ->where('is_default', 1)
                ->where('company_id', $modules['company_id'])
                ->first();
            View::share("defaultFollowupStatus", $defaultFollowupStatus);

            return view($modules['folder_path'] . '.form', compact('company', 'mobile_min', 'mobile_max', 'currentInquiryCount'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function store(InquiryRequest $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
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

        $created_type = null;
        if ($modules['currentGuard'] === 'team_person') {
            $created_type = 'Team';
        } elseif ($modules['currentGuard'] === 'admin_software') {
            $created_type = 'Admin';
        }
        $validated['created_type'] = $created_type;
        // dd($request->all(), $validated);
        DB::beginTransaction();
        try {
            $company_id = $modules['company_id'] ?? $request->company_id;

            $company = \App\Models\Company::find($company_id);
            // if (!$company) {
            //     return Redirect::back()->withErrors('Company not found.');
            // }

            $modules['company_id'] = $company->id;

            $currentInquiryCount = Inquiry::where('company_id', $company->id)
                ->where('created_by', $loginUserId)
                ->count();


            if ($currentInquiryCount >= $company->max_inquiry_count) {
                return Redirect::back()->withErrors('Inquiry limit reached as per your plan. Please upgrade to add more customers.');
            }

            $newRequest = new Request();
            $newRequest['company_id'] = $modules['company_id'];
            $newRequest['company_id'] = (isset($newRequest['company_id']) && !empty($newRequest['company_id'])) ? $newRequest['company_id'] : $request?->company_id;
            // $newRequest['directReturn'] = true;

            $generate_inquiry_no = (new CommonController(new Request()))->generate_inquiry_no($newRequest);
            // dd("L-410", $validated, $modules['company_id'], $newRequest, $generate_inquiry_no);

            if ($generate_inquiry_no->getStatusCode() == 200) {
                $dataResponse = $generate_inquiry_no?->getData();
                if ($dataResponse?->data) {
                    $validated['inquiry_no'] = $dataResponse?->data;
                }
            }

            $validated['created_by'] = $loginUserId;
            $validated['inquiry_date'] = Helper::convert_date($request->inquiry_date, 'd-m-Y', 'Y-m-d');

            if ($request->followup_date) {
                $followup_date = Helper::convert_date($request?->followup_date, 'd-m-Y h:i a', 'Y-m-d H:i');
                $validated['followup_date'] = $followup_date;
            }

            if ($request->hasFile('attachment')) {
                $company = Company::find($validated['company_id']);
                $company_name = $company->company_name ?? 'default-company';
                $company_slug = Str::slug($company_name);

                $file = $request->file('attachment');
                $extension = $file->getClientOriginalExtension();
                $filename = now()->format('dmYHis') . rand() . '.' . $extension;

                $year = now()->format('Y');
                $month = now()->format('m');

                $folder = "uploads/{$validated['company_id']}-{$company_slug}/inquiry/{$year}-{$month}/";
                $uploadedPath = public_path($folder);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0777, true);
                }

                $file->move($uploadedPath, $filename);

                // Save relative path to database
                $validated['attachment'] = "{$year}/{$month}/{$filename}";
            }

            if (isset($validated['inquiry_assigned_to']) && is_array($validated['inquiry_assigned_to'])) {
                $validated['inquiry_assigned_to'] = implode(',', $validated['inquiry_assigned_to']);
            } else {
                $validated['inquiry_assigned_to'] = null;
            }
            // dd("L-438", $validated, $request->all());

            $inquiry = Inquiry::create($validated);
            $lastInsertedId = $inquiry->id;
            $lastFollowupId = null;

            $convertToInquiry = Session::get('newRequest');
            if ($convertToInquiry && $lastInsertedId) {
                $expoVitorRecord = ExpoVisitor::where('company_id', $request?->company_id)->where('id', $convertToInquiry?->visitor_id)->whereNull('inquiry_id')->first();

                if ($convertToInquiry && $expoVitorRecord?->id) {
                    $expoVitorRecord->inquiry_id = $lastInsertedId;
                    $expoVitorRecord->save();
                    Session::forget('newRequest');
                }
            }
            // dd("L-784", $request->all(), $validated, $lastInsertedId);
            if ($request->has('products')) {
                foreach ($request->products as $product) {
                    InquiryProduct::create([
                        'company_id' => $validated['company_id'],
                        'inquiry_id' => $lastInsertedId,
                        'product_id' => $product['product_id'],
                        'quantity' => $product['quantity'],
                        'requirements' => $product['requirements'],
                    ]);
                }
            }

            $statusDB = MarketingStatus::where('id', $validated['followup_status_id'])->first();
            //$teamDB = TeamPerson::where('id', $validated['inquiry_assigned_to'])->first();

            $teamMembers = TeamPerson::whereIn('id', explode(',', $validated['inquiry_assigned_to']))->get();
            $teamNames = $teamMembers->pluck('name')->implode(', ');

            if (!empty($request->followup_details) && !empty($request->followup_date)) {
                $followup_date = Helper::convert_date($request?->followup_date, 'd-m-Y h:i a', 'Y-m-d H:i');

                $followup = Followup::create([
                    'company_id' => $validated['company_id'],
                    'inquiry_id' => $lastInsertedId,
                    'date_time' => $followup_date ?? null,
                    'reason_id' => '0',

                    'details' => $request->followup_details,
                    'status_id' => $validated['followup_status_id'],

                    'created_by' => $loginUserId,
                ]);
                $lastFollowupId = $followup->id;
                $jsonData = $followup->toJson();

                FollowupLogs::create([
                    'company_id' => $validated['company_id'],
                    'inquiry_id' => $lastInsertedId,
                    'followup_id' => $lastFollowupId,
                    'action' => 'Create',
                    'status_id' => $validated['followup_status_id'],
                    'status_value' => $statusDB->name,
                    'assigned_to_id' => $validated['inquiry_assigned_to'],
                    'assigned_to' => $teamNames,
                    'json_data' => $jsonData,
                    'remarks' => '',
                    'created_by' => $loginUserId,
                ]);
            }



            InquiryLogs::create([
                'company_id' => $validated['company_id'],
                'inquiry_id' => $lastInsertedId,
                'followup_id' => $lastFollowupId,
                'action' => 'Create',
                'status_id' => $validated['followup_status_id'],
                'status_value' => $statusDB->name,
                'inquiry_assigned_to_id' => $validated['inquiry_assigned_to'],
                'inquiry_assigned_to' => $teamNames,
                'json_data' => json_encode([
                    'inquiry' => $validated,
                    'products' => $request->has('products') ? $request->products : []
                ]),
                'remarks' => '',
                'created_by' => $loginUserId,
            ]);
            DB::commit();
            // dd("L-431", $request->all(), $modules);
            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function show(string $id)
    {
        $modules = $this->modules;
        try {

            $inquiryId = Helper::decryptString($id);

            $inquiry = Inquiry::where('id', $inquiryId)->orWhere('id', $id)->first();
            if ($inquiry) {
                View::share('modules', $modules);

                View::share('show', $inquiry);
                // return $inquiry;
                // return $modules;
                return view($modules['folder_path'] . '.inquiry_detail');
            }
            // if ($id = "print") {
            //     return self::print();
            // }
            dd($inquiryId, $id, $inquiry);
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
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
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
        $modules['product_view'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Product']);
        try {

            View::share('modules', $modules);
            $edit = Inquiry::findOrFail($id);

            View::share('edit', $edit);
            $inquiryProducts = InquiryProduct::where('inquiry_id', $id)->with(['product_variant_prices', 'product', 'product_variant_prices.variant'])->get();

            View::share('inquiryProducts', $inquiryProducts);
            $company = Company::find($modules['company_id']);
            if ($modules['currentGuard'] === 'admin_software') {
                $mobile_min = 10;
                $mobile_max = 10;
            } else {
                $mobile_min = $company?->mobile_min;
                $mobile_max = $company?->mobile_max;
            }

            return view($modules['folder_path'] . '.form', compact('company', 'mobile_min', 'mobile_max'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function update(InquiryRequest $request, string $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        $request['id'] = $id;
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

        $validated = $request->validated();
        if (!empty($validated['inquiry_date'])) {
            $validated['inquiry_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $validated['inquiry_date'])->format('Y-m-d');
        }
        try {

            $validated['updated_by'] = $loginUserId;
            $updateData = Inquiry::findOrFail($id);
            if ($updateData) {
                unset($validated['id']);

                if ($request->hasFile('attachment')) {
                    $company = Company::find($validated['company_id']);
                    $company_name = $company->company_name ?? 'default-company';
                    $company_slug = Str::slug($company_name);
                    $file = $request->file('attachment');
                    $extension = $file->getClientOriginalExtension();
                    $filename = now()->format('dmYHis') . rand() . '.' . $extension;


                    $year = now()->format('Y');
                    $month = now()->format('m');
                    $folder = "uploads/{$validated['company_id']}-{$company_slug}/inquiry/{$year}-{$month}/";
                    $uploadedPath = public_path($folder);
                    $file->move($uploadedPath, $filename);
                    $validated['attachment'] = "{$year}/{$month}/{$filename}";
                }
                if (isset($validated['inquiry_assigned_to']) && is_array($validated['inquiry_assigned_to'])) {
                    $validated['inquiry_assigned_to'] = implode(',', $validated['inquiry_assigned_to']);
                } else {
                    $validated['inquiry_assigned_to'] = null;
                }
                $updateData->update($validated);

                $teamNames = '-';
                if (!empty($validated['inquiry_assigned_to'])) {
                    $teamMembers = TeamPerson::whereIn('id', explode(',', $validated['inquiry_assigned_to']))->get();
                    $teamNames = $teamMembers->pluck('name')->implode(', ');
                }

                if ($request->has('products')) {
                    InquiryProduct::where('inquiry_id', $id)->delete();

                    foreach ($request->products as $product) {
                        InquiryProduct::create([
                            'company_id' => $validated['company_id'],
                            'inquiry_id' => $id,
                            'product_id' => $product['product_id'],
                            'quantity' => $product['quantity'],
                            'requirements' => $product['requirements'],
                        ]);
                    }
                }



                InquiryLogs::create([
                    'company_id' => $validated['company_id'],
                    'inquiry_id' => $id,
                    'action' => 'Update',
                    'inquiry_assigned_to_id' => $validated['inquiry_assigned_to'],
                    'inquiry_assigned_to' => $teamNames,
                    'json_data' => json_encode([
                        'inquiry' => $validated,
                        'products' => $request->has('products') ? $request->products : []
                    ]),
                    'remarks' => '',
                    'created_by' => $loginUserId,
                ]);
                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' update successfully');
            }
            return Redirect::back()->withErrors('something went wrong please try again later')->withInput();
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

        try {
            $dataDelete = Inquiry::findOrFail($id);

            if ($dataDelete) {
                $validated['deleted_by'] = Auth::guard('admin_software')?->user()?->id;
                $dataDelete->update($validated);

                $loginUserId = null;
                if (Auth::guard('admin_software')->check()) {
                    $loginUserId = Auth::guard('admin_software')?->user()?->id;
                } else if (Auth::guard('team_person')->check()) {
                    $loginUserId = Auth::guard('team_person')?->user()?->id;
                }

                InquiryLogs::create([
                    'company_id' => $dataDelete->company_id,
                    'inquiry_id' => $dataDelete->id,
                    'action' => 'Delete',
                    'created_by' => $loginUserId,
                ]);

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
            'update_status' => ['required', 'exists:marketing_statuses,id']
        ]);

        if ($validator->fails() && $isAjax) {
            return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
        }

        try {
            $inquiry = Inquiry::withTrashed()->findOrFail($request?->id);
            $statusDB = MarketingStatus::findOrFail($request?->update_status);

            if ($inquiry) {
                $inquiry->inquiry_status_id = $request->update_status;
                $inquiry->updated_by = $loginUserId;
                $inquiry->save();

                InquiryLogs::create([
                    'company_id' => $inquiry->company_id,
                    'inquiry_id' => $inquiry->id,
                    'action' => 'Inquiry Status Changed',
                    'status_id' => $request->update_status,
                    'status_value' => $statusDB->name,
                    'created_by' => $loginUserId,
                ]);

                if ($isAjax) {
                    return $this->sendResponse($inquiry, $modules['title'] . ' status update successfully.');
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
            $inquiry = Inquiry::withTrashed()->findOrFail($id);
            $inquiry->restore();

            InquiryLogs::create([
                'company_id' => $inquiry->company_id,
                'inquiry_id' => $inquiry->id,
                'action' => 'Restored',
                'created_by' => $loginUserId,
            ]);

            return Redirect::back()->withSuccess($modules['title'] . ' restored successfully!');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


    public function assign_to_team(Request $request)
    {
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {
            $inquiryIds = explode(',', $request->inquiry_ids);
            $team_id = $request->team_id;

            if (!empty($inquiryIds) && $team_id) {
                foreach ($inquiryIds as $inquiryId) {
                    $inquiry = Inquiry::findOrFail($inquiryId);
                    $inquiry->inquiry_assigned_to = $team_id;
                    $inquiry->save();

                    $teamDB = TeamPerson::find($team_id);

                    InquiryLogs::create([
                        'company_id' => $inquiry->company_id,
                        'inquiry_id' => $inquiry->id,
                        'action' => 'Assigned to team',
                        'inquiry_assigned_to_id' => $team_id,
                        'inquiry_assigned_to' => $teamDB->name ?? '-',
                        'created_by' => $loginUserId,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Inquiries assigned to team successfully!',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid team or inquiry selection.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }
    public function assignToTeam(Request $request)
    {
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        if ($request->has('inquiry_assigned_to') && is_array($request->inquiry_assigned_to)) {
            $filtered = array_filter($request->inquiry_assigned_to, function ($value) {
                return $value !== null && $value !== '';
            });
            $request->merge([
                'inquiry_assigned_to' => empty($filtered) ? null : array_values($filtered)
            ]);
        }

        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'inquiry_assigned_to' => 'nullable|array',
            'inquiry_assigned_to.*' => 'exists:team_persons,id',
            'inquiry_ids' => 'required|string', // comma-separated ids
        ]);

        $inquiryIds = explode(',', $request->inquiry_ids);

        DB::beginTransaction();
        try {
            foreach ($inquiryIds as $inquiryId) {
                $inquiry = Inquiry::find($inquiryId);
                if ($inquiry) {
                    $inquiry->company_id = $request->company_id;
                    if ($request->has('inquiry_assigned_to') && !empty($request->inquiry_assigned_to)) {
                        $inquiry->inquiry_assigned_to = implode(',', $request->inquiry_assigned_to);
                    } else {
                        $inquiry->inquiry_assigned_to = null;
                    }
                    $inquiry->save();

                    if ($request->has('inquiry_assigned_to') && !empty($request->inquiry_assigned_to)) {
                        foreach ($request->inquiry_assigned_to as $assignedUserId) {
                            $teamDB = TeamPerson::find($assignedUserId);
                            InquiryLogs::create([
                                'company_id' => $inquiry->company_id,
                                'inquiry_id' => $inquiry->id,
                                'action' => 'Assigned to team',
                                'inquiry_assigned_to_id' => $assignedUserId,
                                'inquiry_assigned_to' => $teamDB->name ?? '-',
                                'created_by' => $loginUserId,
                            ]);
                        }
                    } else {
                        InquiryLogs::create([
                            'company_id' => $inquiry->company_id,
                            'inquiry_id' => $inquiry->id,
                            'action' => 'Unassigned team members',
                            'inquiry_assigned_to_id' => null,
                            'inquiry_assigned_to' => '-',
                            'created_by' => $loginUserId,
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Server error.'], 500);
        }
    }


    public function bulkDelete(Request $request)
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

        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'inquiry_ids' => 'required|array',
            'inquiry_ids.*' => 'exists:inquiries,id'
        ]);

        $inquiryIds = $request->inquiry_ids;
        DB::beginTransaction();
        try {
            Inquiry::whereIn('id', $inquiryIds)->delete();
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Server error.'], 500);
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
            'import_file' => 'required|mimes:xls,xlsx|max:20480', // Max 20MB
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
                $folder = "uploads/" . $request->company_id . "-" . $company_slug . "/inquiry/{$year}-{$month}/import-inquiry-file";
                $path = $file->storeAs($folder, $filename, 'public');

                $filename_path = "{$folder}/{$filename}";
            }

            $inquiryImportFileId = InquiryImportFile::insertGetId([
                'company_id' => $request->company_id,
                'filename' => $filename_path,
                'total_inquiries' => 0,
                'total_success_inquiries' => 0,
                'total_failed_inquiries' => 0,
                'errors' => '',
                'created_by' => $loginUserId,
            ]);

            $company_id = $request->company_id;

            Excel::import(
                new InquiryImport($company_id, $loginUserId, $modules['currentGuard'], $inquiryImportFileId, $authLoginUserDetail),
                $request->file('import_file')
            );

            $inquiryImportFile = InquiryImportFile::find($inquiryImportFileId);


            session()->flash('inquiry_summary', [
                'total_inquiries' => $inquiryImportFile->total_inquiries,
                'total_success_inquiries' => $inquiryImportFile->total_success_inquiries,
                'total_failed_inquiries' => $inquiryImportFile->total_failed_inquiries,
            ]);


            if (!empty($inquiryImportFile->errors)) {
                if ($inquiryImportFile->total_success_inquiries > 0) {

                    return Redirect::back()
                        ->with('inquiryImportFileMessages', json_decode($inquiryImportFile->errors))
                        ->with('success', 'Inquiries imported successfully with some errors!');
                } else {

                    return Redirect::back()
                        ->with('inquiryImportFileMessages', json_decode($inquiryImportFile->errors));
                }
            }


            return back()->with('success', 'Inquiries imported successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }


    public function exportExcel(Request $request)
    {
        // dd($request->all());
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
        // dd("L-1309", $request?->all(), $modules);
        return Excel::download(new InquiryExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'Inquiry-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
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
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!$modules['print_permission']) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {


            $query = Inquiry::with(['company'])
                ->orderBy('id', 'DESC')
                ->where(function ($query) use ($modules, $loginUserId, $request) {
                    if (Auth::guard('team_person')->check() && $modules['company_id']) {
                        $query->where('company_id', $modules['company_id']);

                        if (!empty($modules['personal_data_permission']) && empty($modules['all_data_permission'])) {
                            $query->where('inquiry_created_by', $loginUserId);
                        }
                    }

                    // Filters
                    if ($request->filled('filter_company')) {
                        $query->where('company_id', $request->filter_company);
                    }

                    if ($request->filled('search')) {
                        $searchTerm = $request->search;
                        $query->where(function ($q) use ($searchTerm) {
                            $q->where('customer_name', 'like', '%' . $searchTerm . '%')
                                ->orWhere('mobile_no', 'like', '%' . $searchTerm . '%')
                                ->orWhere('contact_person_name', 'like', '%' . $searchTerm . '%');
                        });
                    }

                    if ($request->filled('filter_inquiry_status')) {
                        $query->where('inquiry_status_id', $request->filter_inquiry_status);
                    }

                    if ($request->filled('filter_followup_status')) {
                        $query->where('followup_status_id', $request->filter_followup_status);
                    }

                    if ($request->filled('filter_source_of_inquiry')) {
                        $query->where('source_of_inquiry_id', $request->filter_source_of_inquiry);
                    }

                    if ($request->filled('filter_created_by')) {
                        $query->where('inquiry_created_by', $request->filter_created_by);
                    }

                    if ($request->has('filter_assigned_to') && $request->filter_assigned_to) {
                        $query->whereRaw("FIND_IN_SET(?, inquiry_assigned_to)", [$request->filter_assigned_to]);
                    }

                    if ($request->has('filter_inquiry_date') && $request->filter_inquiry_date) {

                        $filter_inquiry_date = $request->filter_inquiry_date;
                        $filter_inquiry_date = explode(" to ", $filter_inquiry_date);
                        $fromDate = null;
                        $toDate = null;

                        if (count($filter_inquiry_date) == 2) {
                            $fromDate = Helper::convert_date($filter_inquiry_date[0], "d/m/Y", "Y-m-d");
                            $toDate = Helper::convert_date($filter_inquiry_date[1], "d/m/Y", "Y-m-d");
                        }
                        if ($fromDate && $toDate) {
                            $query->whereDate((new Inquiry())->getTable() . '.inquiry_date', ">=", $fromDate);
                            $query->whereDate((new Inquiry())->getTable() . '.inquiry_date', "<=", $toDate);
                        } elseif ($fromDate) {
                            $query->whereDate((new Inquiry())->getTable() . '.inquiry_date', $fromDate);
                        }
                    }

                    if ($request->filled('filter_followup_date')) {
                        $query->whereDate('followup_date', $request->filter_followup_date);
                    }
                });

            $Inquiry = $query->orderBy('id', 'DESC')->get();

            return view($modules['folder_path'] . '.print', compact('Inquiry', 'modules', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


    public function convert_to_customer(Request $request, $id)
    {

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        View::share('modules', $modules);

        try {
            $convertToCustomer = Inquiry::where('id', $id)->first();
            if ($convertToCustomer) {
                $newRequest = new Request();
                $newRequest['inquiry_id'] = $id;
                $newRequest['company_id'] = $convertToCustomer?->company_id;
                $newRequest['customer_name'] = $convertToCustomer?->customer_name;
                $newRequest['contact_person_name'] = $convertToCustomer?->contact_person_name;
                $newRequest['mobile_number'] = $convertToCustomer?->mobile_no;
                $newRequest['whatsapp_number'] = $convertToCustomer?->whatsapp_no;
                $newRequest['email'] = $convertToCustomer?->email;
                $newRequest['address'] = $convertToCustomer?->address;
                $newRequest['country_id'] = $convertToCustomer?->country_id;
                $newRequest['state_id'] = $convertToCustomer?->state_id;
                $newRequest['city_id'] = $convertToCustomer?->city_id;
                $newRequest['pincode'] = $convertToCustomer?->pincode;
                $newRequest['area_id'] = $convertToCustomer?->area_id;
                $newRequest['status'] = 'active';
                $newRequest['shipping_address'] = $convertToCustomer?->shipping_address ?? $convertToCustomer?->address ?? '';
                $newRequest['billing_address'] = $convertToCustomer?->billing_address ?? $convertToCustomer?->address ?? '';
                // dd($convertToCustomer);

                Session::put('newRequest', $newRequest);
                return Redirect::route('customer.create', ['newRequest' => $newRequest]);

                return (new CustomerController())->create($newRequest);
            }
            return $convertToCustomer;
        } catch (\Exception $e) {
            return $e->getMessage();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function inquiry_to_quatation(Request $request, $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $this->authenticateLoginUserDetails?->company_id ?? null;
        $modules['parent_type_id'] = $this->authenticateLoginUserDetails?->parent_type_id ?? null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        View::share('modules', $modules);
        try {
            $convertToQuotation = Inquiry::where('id', $id)->first();

            if (!$convertToQuotation) {
                return redirect()->back()->withErrors('Inquiry not found.');
            }

            // 🔹 Generate Quotation No (company wise)
            $lastQuotation = Quotation::where('company_id', $convertToQuotation->company_id)
                ->whereNotNull('quotation_no')
                ->orderByDesc('id')
                ->first();

            if ($lastQuotation && preg_match('/QUO-(\d+)/', $lastQuotation->quotation_no, $matches)) {
                $nextNumber = (int) $matches[1] + 1;
            } else {
                $nextNumber = 1;
            }

            $quotationNo = 'QUO-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            // 🔹 First check if customer already exists (by mobile/email)
            $customer = Customer::where('mobile_number', $convertToQuotation->mobile_no)->first();

            if (!$customer) {
                // 🔹 Generate client_code
                $lastCustomer = Customer::where('company_id', $convertToQuotation->company_id)
                    ->whereNotNull('client_code')
                    ->orderByDesc('id')
                    ->first();

                if ($lastCustomer && preg_match('/CC-(\d+)/', $lastCustomer->client_code, $matches)) {
                    $nextNumber = (int) $matches[1] + 1;
                } else {
                    $nextNumber = 1;
                }

                $clientCode = 'CC-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                // 🔹 Generate password + sp
                $last4Digits = substr($convertToQuotation->mobile_no, -4);
                $password = Hash::make($last4Digits);
                $spCode = Str::random(4) . $last4Digits . Str::random(3);

                $superStockiestType = CustomerType::where('company_id', $convertToQuotation->company_id)
                    ->where('name', 'Super Stockiest')
                    ->first();

                $customerTypeId = $superStockiestType ? $superStockiestType->id : null;


                // 🔹 Create new customer
                $customer = Customer::create([
                    'company_id' => $convertToQuotation->company_id,
                    'price_list_id' => null,
                    'gst_number' => null,
                    'customer_name' => $convertToQuotation->customer_name,
                    'password' => $password,
                    'sp' => $spCode,
                    'customer_type_id' => $customerTypeId,
                    'parent_id' => null,
                    'contact_person_name' => $convertToQuotation->contact_person_name,
                    'mobile_number' => $convertToQuotation->mobile_no,
                    'whatsapp_number' => $convertToQuotation->whatsapp_no,
                    'client_code' => $clientCode,
                    'country_id' => $convertToQuotation->country_id,
                    'state_id' => $convertToQuotation->state_id,
                    'city_id' => $convertToQuotation->city_id,
                    'area_id' => $convertToQuotation->area_id,
                    'pincode' => $convertToQuotation->pincode,
                    'address' => $convertToQuotation->address,
                    'longitude' => null,
                    'latitude' => null,
                    'shipping_address' => $convertToQuotation->address,
                    'billing_address' => $convertToQuotation->address,
                    'email' => $convertToQuotation->email,
                    'birth_date' => null,
                    'platform' => 'inquiry',
                    'created_type' => 'system',
                    'status' => 'active',
                    'created_by' => $loginUserId,
                    'updated_by' => null,
                ]);
            }

            // 🔹 Pass customer_id to quotation form
            $newRequest = new Request();
            $newRequest['inquiry_id'] = $id;
            $newRequest['company_id'] = $convertToQuotation->company_id;
            $newRequest['customer_id'] = $customer->id;
            $newRequest['quotation_no'] = $quotationNo;

            Session::put('newQuotationRequest', $newRequest);

            return Redirect::route('quotation.create', ['newQuotationRequest' => $newRequest]);
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')
                ->withErrors($e->getMessage());
        }
    }

    public function getDuplicateInquiries(Request $request)
    {
        $mobile = $request->get('mobile_no');
        $company_id = $request->get('company_id');

        if (empty($mobile) || empty($company_id)) {
            return response()->json(['success' => false, 'data' => []]);
        }

        $inquiries = Inquiry::where('company_id', $company_id)
            ->where('mobile_no', $mobile)
            ->with(['inquiry_status', 'followup_status', 'source_of_inquiry'])
            ->orderBy('id', 'desc')
            ->get();

        $data = $inquiries->map(function ($inq) {
            return [
                'inquiry_no' => $inq->inquiry_no,
                'inquiry_date' => Helper::convert_date($inq->inquiry_date, 'Y-m-d', 'd-m-Y'),
                'inquiry_status' => $inq->inquiry_status?->name ?? 'N/A',
                'followup_status' => $inq->followup_status?->name ?? 'N/A',
                'source' => $inq->source_of_inquiry?->name ?? 'N/A',
                'assigned_to' => implode(', ', $inq->assigned_to_name ?? []),
                'edit_url' => route('inquiry.edit', [$inq->id])
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Get preview and history for General WhatsApp Message
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWhatsAppGeneralPreview($id)
    {
        try {
            $inquiry = Inquiry::with(['company'])->find($id);
            if (!$inquiry) {
                return response()->json(['success' => false, 'message' => 'Inquiry not found.'], 404);
            }

            $customerName = $inquiry->contact_person_name ?: ($inquiry->customer_name ?: 'Valued Customer');
            $phone = $inquiry->whatsapp_no ?: $inquiry->mobile_no;

            // Product / Requirement
            $productName = '';
            if (method_exists($inquiry, 'inquiry_products')) {
                $firstInqProduct = $inquiry->inquiry_products()->with('product')->first();
                $productName = $firstInqProduct?->product?->product_name ?? '';
            }
            if (empty($productName) && !empty($inquiry->requirement_details)) {
                $lines = explode("\n", strip_tags($inquiry->requirement_details));
                $productName = trim($lines[0] ?? '');
            }
            if (empty($productName)) {
                $productName = 'Industrial Products';
            }
            if (strlen($productName) > 60) {
                $productName = substr($productName, 0, 57) . '...';
            }

            $templateName = env('LILIYA_GENERAL_TEMPLATE', 'shalibhadra_inquiry_general_msg');

            $previewText = "Dear {$customerName},\n\n" .
                "Greetings from Shalibhadra International Private Limited!\n\n" .
                "Following our initial acknowledgment regarding your inquiry for {$productName}, our sales team is here to assist you further.\n\n" .
                "To provide you with our best competitive quotation and technical details, kindly share your specific requirements with us.\n\n" .
                "You can reply directly to this message or connect with our sales team for further assistance.\n\n" .
                "Best Regards,\n" .
                "Sales Team\n" .
                "Shalibhadra International Pvt. Ltd.\n" .
                "If you don't want to receive messages reply- STOP";

            // Fetch history of sends from inquiry_logs
            $logs = InquiryLogs::where('inquiry_id', $inquiry->id)
                ->where('action', 'General WhatsApp Sent')
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($log) {
                    $sender = 'System';
                    if ($log->created_by && is_numeric($log->created_by)) {
                        $team = \App\Models\TeamPerson::find($log->created_by);
                        if ($team) {
                            $sender = $team->name;
                        } else {
                            $admin = \App\Models\AdminSoftware::find($log->created_by);
                            if ($admin) {
                                $sender = $admin->name;
                            }
                        }
                    }
                    return [
                        'date' => $log->created_at ? $log->created_at->format('d-m-Y h:i A') : '',
                        'sender' => $sender,
                        'remarks' => $log->remarks,
                    ];
                });

            return response()->json([
                'success' => true,
                'inquiry' => [
                    'id' => $inquiry->id,
                    'inquiry_no' => $inquiry->inquiry_no,
                    'customer_name' => $customerName,
                    'phone' => $phone,
                    'product_name' => $productName,
                    'sent_count' => (int) ($inquiry->whatsapp_general_sent_count ?? 0),
                    'last_sent_at' => $inquiry->last_whatsapp_general_sent_at ? Carbon::parse($inquiry->last_whatsapp_general_sent_at)->format('d-m-Y h:i A') : null,
                ],
                'template_name' => $templateName,
                'preview_text' => $previewText,
                'history' => $logs
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Send General WhatsApp message for an Inquiry
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendWhatsAppGeneral(Request $request, $id)
    {
        try {
            $inquiry = Inquiry::find($id);
            if (!$inquiry) {
                return response()->json(['success' => false, 'message' => 'Inquiry not found.'], 404);
            }

            $userId = Auth::guard('team_person')->id() ?: (Auth::guard('admin')->id() ?: Auth::id());
            $userType = Auth::guard('team_person')->check() ? 'Team' : 'Admin';

            $result = Helper::sendInquiryGeneralWhatsApp($inquiry, $userId, $userType);

            if (!empty($result['success'])) {
                // Refresh inquiry
                $inquiry->refresh();
                return response()->json([
                    'success' => true,
                    'message' => 'WhatsApp message sent successfully!',
                    'sent_count' => (int) ($inquiry->whatsapp_general_sent_count ?? 0)
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to send WhatsApp message.'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
