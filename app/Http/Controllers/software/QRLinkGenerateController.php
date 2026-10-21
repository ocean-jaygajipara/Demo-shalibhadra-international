<?php

namespace App\Http\Controllers\software;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\QRLinkGenerate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use App\Http\Requests\QRLinkGenerateRequest;
use Illuminate\Support\Facades\Gate;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\PdfWriter;

class QRLinkGenerateController extends Controller
{
    public $modules = [];
    public function __construct()
    {
        parent::__construct();
        $this->modules = [
            'title' => 'QR Link Generate',
            'folder_path' => 'software.modules.qr_link_generate',
            'route' => 'qr-link-generate',
            'table_name' => (new QRLinkGenerate())->getTable(),
            'module_name' => 'QR Link Generate',
            'permisstion_prefix' => 'QR Link Generate',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null,
        ];

        View::share('QRTypes', QRLinkGenerate::QR_Types());
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
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
        View::share('modules', $modules);
        try {

            $columns = [
                (object)['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'td_label' => 'Sr No.', 'orderable' => false, 'searchable' => false],
                (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                (object)['data' => "name", 'name' => 'name', 'td_label' => 'Name', 'className' =>  ''],
                (object)['data' => "qr_type", 'name' => 'qr_type', 'td_label' => 'QR Type', 'className' =>  ''],
                (object)['data' => "qr_short_link", 'name' => 'qr_short_link', 'td_label' => 'QR Link', 'className' =>  ''],
                (object)['data' => "scan_count", 'name' => 'scan_count', 'td_label' => 'QR Scans', 'className' =>  ''],
                (object)['data' => "status", 'name' => 'status', 'td_label' => 'Status', 'className' =>  'w-15 text-start'],
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
                // dd($request->all());
                if (!$modules['view_permission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }
                $data = QRLinkGenerate::select('*');
                $data->with(['company'])->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check() && $modules['company_id']) {
                            //$query->where('company_id', $modules['company_id']);

                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where('created_by', $loginUserId);
                            }
                        }
                    })
                    ->orderBy('id', 'desc');
                // if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                //     $data = $data->withTrashed();
                // }
                $data = $data;


                return Datatables::of($data)
                    ->addIndexColumn()
                    ->filter(function ($query) use ($request) {
                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }
                        if ($request->has('search')) {
                            $query->where('name', 'like', "%" . $request->search . "%");
                        }
                    })
                    ->addColumn('company_name', function ($row) {
                        return $row->company->company_name;
                    })
                    ->editColumn('qr_type', function ($row) {
                        $qrType = QRLinkGenerate::QR_Types($row->qr_type_id);
                        return $qrType ? $qrType->name : '-';
                    })
                    ->editColumn('qr_short_link', function ($row) {
                        $originalLink = $row->qr_code_uuid ? url('qr/link/' .$row->qr_code_uuid) : '-';
                        return '<a class="text-decoration-none text-reset" href= "'.$originalLink.'" target="_blank">'.$originalLink.'</a>';
                    })
                    ->editColumn('status', function ($row) use ($modules) {
                        $btn = '';

                        $dropdown = "";
                        $dropdown .= '<ul class="dropdown-menu" style="">';

                        if ($row->status == "active") {
                            $btn .= '<button type="button" class="btn btn-success btn-sm
                            dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item
                             waves-effect btn-label-danger update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="inactive">Inactive</a></li>';
                        } elseif ($row->status == "inactive") {
                            $btn .= '<button type="button" class="btn btn-danger btn-sm
                            dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown"
                            aria-expanded="false">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item
                            waves-effect btn-label-success update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="active">Active</a></li>';
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
                            // $btn .= '<a href="javascript:void(0)" class="edit btn btn-primary btn-sm">View</a>';
                            if ($row->status == "active") {
                                $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" class="btn btn-info btn-icon mx-1 viewQrBtn"><i class="fa-solid fa-eye"></i></a>';

                                $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" class="btn btn-primary btn-icon mx-1 download-qr">
                                <i class="fa-solid fa-download"></i>
                                </a>';
                            }

                            if ($modules['update_permission']) {
                                $btn .= '<a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="btn btn-light btn-icon mx-1"><i class="fa-solid fa-pen-to-square"></i></a>';
                            }
                            if ($modules['delete_permission']) {
                                $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="btn btn-danger btn-icon deletebutton mx-1"><i class="fa-solid fa-trash"></i></i></a>';
                            }
                        }

                        if ($btn == '') {
                            $btn = '-';
                        }
                        return $btn;
                    })
                    ->rawColumns(['status', 'action', 'qr_short_link'])
                    ->make(true);
            }
            // dd($modules['folder_path'] . '.index');
            if (!$modules['view_permission']) {
                abort(403, 'Unauthorized');
            }

            return view($modules['folder_path'] . '.index');
        } catch (\Exception $e) {
            return Redirect::route('software.dashboard')->withErrors($e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
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
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(QRLinkGenerateRequest $request)
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
            // return $request->all();

            $input = $request->all();
            $qr_type = null;
            if ($request?->qr_type_id) {
                $qr_type = QRLinkGenerate::QR_Types($request?->qr_type_id);
            }

            if (!$qr_type) {
                return Redirect::back()->with('error', 'The QR Type field is required.')->withInput();
            }

            if ($qr_type) {
                $qr_type_fields = [];
                if ($qr_type?->slug && $qr_type?->slug == "link") {
                    $qr_type_fields = $request->only(['link']);
                } elseif ($qr_type?->slug && $qr_type?->slug == "text") {
                    $qr_type_fields = $request->only(['text']);
                } elseif ($qr_type?->slug && $qr_type?->slug == "email") {
                    $qr_type_fields = $request->only(['email_to', 'subject', 'body']);
                } elseif ($qr_type?->slug && $qr_type?->slug == "whatsapp") {
                    $qr_type_fields = $request->only(['phone_whatsapp', 'message_whatsapp']);
                } elseif ($qr_type?->slug && $qr_type?->slug == "wifi") {
                    $qr_type_fields = $request->only(['ssid', 'password', 'encryption']);
                } elseif ($qr_type?->slug && $qr_type?->slug == "image") {
                    if ($request->hasFile('image')) {

                        $year = now()->format('Y');
                        $month = now()->format('m');
                        $folder = "uploads/company/{$request?->company_id}/qrcode/{$year}-{$month}/";
                        $sub_folder_path = $folder;
                        $uploadedPath = public_path($folder);

                        // Ensure folder exists
                        if (!file_exists($uploadedPath)) {
                            mkdir($uploadedPath, 0777, true);
                        }
                        $file = $request->file('image');
                        // File name base
                        $imageName = 'image_profile_' . uniqid();

                        $extenstion = $file->getClientOriginalExtension();

                        $filename = $imageName . '.' . $extenstion;

                        if ($file->move($uploadedPath, $filename)) {
                            $uploadedImage = $sub_folder_path . $filename;
                            if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                                $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $imageName, 60, true);
                                if ($webP) {
                                    $uploadedImage = $sub_folder_path . $webP;
                                    $extenstion = "webp";
                                }
                            }
                            // return $uploadedImage;
                            $qr_type_fields = ['image' => $uploadedImage];
                        }
                    }
                } elseif ($qr_type?->slug && $qr_type?->slug == "pdf") {
                    if ($request->hasFile('pdf')) {

                        $year = now()->format('Y');
                        $month = now()->format('m');
                        $folder = "uploads/company/{$request?->company_id}/qrcode/{$year}-{$month}/";
                        $sub_folder_path = $folder;
                        $uploadedPath = public_path($folder);

                        // Ensure folder exists
                        if (!file_exists($uploadedPath)) {
                            mkdir($uploadedPath, 0777, true);
                        }
                        $file = $request->file('pdf');
                        // File name base
                        $imageName = 'pdf_profile_' . uniqid();

                        $extenstion = $file->getClientOriginalExtension();

                        $filename = $imageName . '.' . $extenstion;

                        if ($file->move($uploadedPath, $filename)) {
                            $uploadedImage = $sub_folder_path . $filename;
                            if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                                $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $imageName, 60, true);
                                if ($webP) {
                                    $uploadedImage = $sub_folder_path . $webP;
                                    $extenstion = "webp";
                                }
                            }
                            // return $uploadedImage;
                            $qr_type_fields = ['pdf' => $uploadedImage];
                        }
                    }
                } elseif ($qr_type?->slug == "vcard") {
                    $qr_type_fields = $request->only([
                        'first_name', 'last_name', 'phone_vcard', 'email',
                        'company', 'job_title', 'website', 'address',
                        'dob', 'card_color'
                    ]);

                    if ($request->hasFile('profile_image')) {

                        $year = now()->format('Y');
                        $month = now()->format('m');
                        $folder = "uploads/company/{$request?->company_id}/qrcode/{$year}-{$month}/";
                        $sub_folder_path = $folder;
                        $uploadedPath = public_path($folder);

                        // Ensure folder exists
                        if (!file_exists($uploadedPath)) {
                            mkdir($uploadedPath, 0777, true);
                        }
                        $file = $request->file('profile_image');
                        // File name base
                        $imageName = 'vcard_profile_' . uniqid();

                        $extenstion = $file->getClientOriginalExtension();

                        $filename = $imageName . '.' . $extenstion;

                        if ($file->move($uploadedPath, $filename)) {
                            $uploadedImage = $sub_folder_path . $filename;
                            if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                                $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $imageName, 60, true);
                                if ($webP) {
                                    $uploadedImage = $sub_folder_path . $webP;
                                    $extenstion = "webp";
                                }
                            }
                            // return $uploadedImage;
                            $qr_type_fields['profile_image'] = $uploadedImage;
                        }
                    }

                } elseif ($qr_type?->slug == "phonecall") {
                    $qr_type_fields = $request->only(['phone_call']);

                } elseif ($qr_type?->slug == "map") {
                    $qr_type_fields = $request->only(['latitude', 'longitude', 'map_link']);

                } elseif ($qr_type?->slug == "audio") {
                    if ($request->hasFile('audio')) {

                        $year = now()->format('Y');
                        $month = now()->format('m');
                        $folder = "uploads/company/{$request?->company_id}/qrcode/{$year}-{$month}/";
                        $sub_folder_path = $folder;
                        $uploadedPath = public_path($folder);

                        // Ensure folder exists
                        if (!file_exists($uploadedPath)) {
                            mkdir($uploadedPath, 0777, true);
                        }
                        $file = $request->file('audio');
                        // File name base
                        $imageName = 'audio_profile_' . uniqid();

                        $extenstion = $file->getClientOriginalExtension();

                        $filename = $imageName . '.' . $extenstion;

                        if ($file->move($uploadedPath, $filename)) {
                            $uploadedImage = $sub_folder_path . $filename;
                            if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                                $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $imageName, 60, true);
                                if ($webP) {
                                    $uploadedImage = $sub_folder_path . $webP;
                                    $extenstion = "webp";
                                }
                            }
                            // return $uploadedImage;
                            $qr_type_fields = ['audio' => $uploadedImage];
                        }
                    }

                } elseif ($qr_type?->slug == "facebook") {
                    $qr_type_fields = $request->only(['facebook_url']);

                } elseif ($qr_type?->slug == "instagram") {
                    $qr_type_fields = $request->only(['instagram_url']);

                } elseif ($qr_type?->slug == "youtube") {
                    $qr_type_fields = $request->only(['youtube_url']);

                } elseif ($qr_type?->slug == "telegram") {
                    $qr_type_fields = $request->only(['telegram_url']);

                } elseif ($qr_type?->slug && $qr_type?->slug == "appstore") {
                    $qr_type_fields = $request->only(['app_store_link', 'google_play_link', 'other_device_link']);

                } elseif ($qr_type?->slug == "tiktok") {
                    $qr_type_fields = $request->only(['tiktok_url']);

                } elseif ($qr_type?->slug == "video") {
                    $qr_type_fields = $request->only(['video_url']);

                } elseif ($qr_type?->slug == "googleform") {
                    $qr_type_fields = $request->only(['googleform_url']);

                } elseif ($qr_type?->slug == "twitter") {
                    $qr_type_fields = $request->only(['twitter_url']);

                } elseif ($qr_type?->slug == "snapchat") {
                    $qr_type_fields = $request->only(['snapchat_url']);

                } elseif ($qr_type?->slug == "spotify") {
                    $qr_type_fields = $request->only(['spotify_url']);

                } elseif ($qr_type?->slug == "googledoc") {
                    $qr_type_fields = $request->only(['googledoc_url']);

                } elseif ($qr_type?->slug == "googlereview") {
                    $qr_type_fields = $request->only(['googlereview_url']);

                } elseif ($qr_type?->slug == "googlesheet") {
                    $qr_type_fields = $request->only(['googlesheet_url']);

                } elseif ($qr_type?->slug == "payment") {
                    $qr_type_fields = $request->only(['payment_url']);

                } elseif ($qr_type?->slug == "sms") {
                    $qr_type_fields = $request->only(['phone_sms','phone_message']);

                } elseif ($qr_type?->slug == "logo") {
                    $qr_type_fields = $request->only(['logo_url']);

                } elseif ($qr_type?->slug == "office365") {
                    $qr_type_fields = $request->only(['office365_url']);

                } elseif ($qr_type?->slug == "shaped") {
                    $qr_type_fields = $request->only(['shaped_url']);

                } elseif ($qr_type?->slug == "paypal") {
                    $qr_type_fields = $request->only(['paypal_url']);

                } elseif ($qr_type?->slug == "etsy") {
                    $qr_type_fields = $request->only(['etsy_url']);

                } elseif ($qr_type?->slug == "png") {

                    if ($request->hasFile('png_file')) {

                        $year = now()->format('Y');
                        $month = now()->format('m');
                        $folder = "uploads/company/{$request?->company_id}/qrcode/{$year}-{$month}/";
                        $sub_folder_path = $folder;
                        $uploadedPath = public_path($folder);

                        // Ensure folder exists
                        if (!file_exists($uploadedPath)) {
                            mkdir($uploadedPath, 0777, true);
                        }
                        $file = $request->file('png_file');
                        // File name base
                        $imageName = 'png_file_' . uniqid();

                        $extenstion = $file->getClientOriginalExtension();

                        $filename = $imageName . '.' . $extenstion;

                        if ($file->move($uploadedPath, $filename)) {
                            $uploadedImage = $sub_folder_path . $filename;
                            if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                                $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $imageName, 60, true);
                                if ($webP) {
                                    $uploadedImage = $sub_folder_path . $webP;
                                    $extenstion = "webp";
                                }
                            }
                            // return $uploadedImage;
                            $qr_type_fields = ['png_file' => $uploadedImage];
                        }
                    }

                } elseif ($qr_type?->slug == "linkedin") {
                    $qr_type_fields = $request->only(['linkedin_url']);

                } elseif ($qr_type?->slug == "crypto") {
                    $qr_type_fields = $request->only(['crypto_type','crypto_amount','wallet_address','crypto_message']);

                } elseif ($qr_type?->slug == "calendar") {
                    $qr_type_fields = $request->only(['calendar_url']);

                } elseif ($qr_type?->slug == "social") {
                    $qr_type_fields = $request->only(['social_media_url']);

                } elseif ($qr_type?->slug == "reddit") {
                    $qr_type_fields = $request->only(['reddit_url']);

                } elseif ($qr_type?->slug == "file") {
                    if ($request->hasFile('file')) {

                        $year = now()->format('Y');
                        $month = now()->format('m');
                        $folder = "uploads/company/{$request?->company_id}/qrcode/{$year}-{$month}/";
                        $sub_folder_path = $folder;
                        $uploadedPath = public_path($folder);

                        // Ensure folder exists
                        if (!file_exists($uploadedPath)) {
                            mkdir($uploadedPath, 0777, true);
                        }
                        $file = $request->file('file');
                        // File name base
                        $imageName = 'file_' . uniqid();

                        $extenstion = $file->getClientOriginalExtension();

                        $filename = $imageName . '.' . $extenstion;

                        if ($file->move($uploadedPath, $filename)) {
                            $uploadedImage = $sub_folder_path . $filename;
                            if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                                $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $imageName, 60, true);
                                if ($webP) {
                                    $uploadedImage = $sub_folder_path . $webP;
                                    $extenstion = "webp";
                                }
                            }
                            // return $uploadedImage;
                            $qr_type_fields = ['file' => $uploadedImage];
                        }
                    }

                } elseif ($qr_type?->slug == "excel") {
                    if ($request->hasFile('excel')) {

                        $year = now()->format('Y');
                        $month = now()->format('m');
                        $folder = "uploads/company/{$request?->company_id}/qrcode/{$year}-{$month}/";
                        $sub_folder_path = $folder;
                        $uploadedPath = public_path($folder);

                        // Ensure folder exists
                        if (!file_exists($uploadedPath)) {
                            mkdir($uploadedPath, 0777, true);
                        }
                        $file = $request->file('excel');
                        // File name base
                        $imageName = 'excel_' . uniqid();

                        $extenstion = $file->getClientOriginalExtension();

                        $filename = $imageName . '.' . $extenstion;

                        if ($file->move($uploadedPath, $filename)) {
                            $uploadedImage = $sub_folder_path . $filename;
                            if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                                $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $imageName, 60, true);
                                if ($webP) {
                                    $uploadedImage = $sub_folder_path . $webP;
                                    $extenstion = "webp";
                                }
                            }
                            // return $uploadedImage;
                            $qr_type_fields = ['excel' => $uploadedImage];
                        }
                    }

                } elseif ($qr_type?->slug == "venmo") {
                    $qr_type_fields = $request->only(['venmo_url']);

                } elseif ($qr_type?->slug == "amazon") {
                    $qr_type_fields = $request->only(['amazon_url']);

                } elseif ($qr_type?->slug && $qr_type?->slug == "pptx") {
                    if ($request->hasFile('pptx_file')) {

                        $year = now()->format('Y');
                        $month = now()->format('m');
                        $folder = "uploads/company/{$request?->company_id}/qrcode/{$year}-{$month}/";
                        $sub_folder_path = $folder;
                        $uploadedPath = public_path($folder);

                        // Ensure folder exists
                        if (!file_exists($uploadedPath)) {
                            mkdir($uploadedPath, 0777, true);
                        }
                        $file = $request->file('pptx_file');
                        // File name base
                        $imageName = 'pptx_file_' . uniqid();

                        $extenstion = $file->getClientOriginalExtension();

                        $filename = $imageName . '.' . $extenstion;

                        if ($file->move($uploadedPath, $filename)) {
                            $uploadedImage = $sub_folder_path . $filename;
                            if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                                $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $imageName, 60, true);
                                if ($webP) {
                                    $uploadedImage = $sub_folder_path . $webP;
                                    $extenstion = "webp";
                                }
                            }
                            // return $uploadedImage;
                            $qr_type_fields = ['pptx_file' => $uploadedImage];
                        }
                    }
                } elseif ($qr_type?->slug == "2dbarcode") {
                    $qr_type_fields = $request->only([
                        'barcode_encode_type',
                        'gtin_code',
                        'product_link',
                    ]);
                } elseif ($qr_type?->slug == "upi") {
                    $qr_type_fields = $request->only([
                        'upi_id',
                        'payee_name',
                        'upi_amount',
                        'upi_remark',
                        'upi_currency'
                    ]);

                    // sanitize / defaults if needed
                    $qr_type_fields['upi_amount'] = $qr_type_fields['upi_amount'] ?? null;
                    $qr_type_fields['upi_remark'] = $qr_type_fields['upi_remark'] ?? null;
                    $qr_type_fields['upi_currency'] = $qr_type_fields['upi_currency'] ?? 'INR';
                }  elseif ($qr_type?->slug == "pcr") {
                    $qr_type_fields = $request->only([
                        'expire_time',
                        'pcr_password',
                    ]);

                    if ($request->hasFile('pcr_file')) {

                        $year = now()->format('Y');
                        $month = now()->format('m');
                        $folder = "uploads/company/{$request?->company_id}/qrcode/{$year}-{$month}/";
                        $sub_folder_path = $folder;
                        $uploadedPath = public_path($folder);

                        // Ensure folder exists
                        if (!file_exists($uploadedPath)) {
                            mkdir($uploadedPath, 0777, true);
                        }
                        $file = $request->file('pcr_file');
                        // File name base
                        $imageName = 'pcr_file_' . uniqid();

                        $extenstion = $file->getClientOriginalExtension();

                        $filename = $imageName . '.' . $extenstion;

                        if ($file->move($uploadedPath, $filename)) {
                            $uploadedImage = $sub_folder_path . $filename;
                            if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                                $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $imageName, 60, true);
                                if ($webP) {
                                    $uploadedImage = $sub_folder_path . $webP;
                                    $extenstion = "webp";
                                }
                            }
                            // return $uploadedImage;
                            $qr_type_fields['pcr_file'] = $uploadedImage;
                        }
                    }else{
                         $qr_type_fields['pcr_file'] = $request?->pcr_file_hidden ?? null;
                    }
                } elseif($qr_type?->slug == "customurl") {
                    $qr_type_fields = $request->only([
                        'default_link',
                    ]);

                    $locations = $request->input('locations', []);
                    foreach ($locations as $index => $location) {
                        $qr_type_fields['locations_arr'][$index] = [
                            'country' => $location['country'] ?? null,
                            'state'   => $location['state'] ?? null,
                            'city'    => $location['city'] ?? null,
                            'url'     => $location['url'] ?? null,
                        ];
                    }
                }else {
                    dd("requestFile L-240", $qr_type, $request->all());
                }
                $input['qr_type_fields'] = json_encode($qr_type_fields);
            }

            // Generate unique short code
            do {
                $shortCode = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            } while (QRLinkGenerate::where('qr_code_uuid', $shortCode)->exists());

            $input['qr_code_uuid'] = $shortCode;
            // Folder structure


            if ($qr_type?->slug == 'vcard') {
                $fields = json_decode($input['qr_type_fields'], true);

                $vcard  = "BEGIN:VCARD\r\n";
                $vcard .= "VERSION:3.0\r\n";

                $firstName = $fields['first_name'] ?? '';
                $lastName  = $fields['last_name'] ?? '';
                $fullName  = trim($firstName . ' ' . $lastName);

                $vcard .= "FN:{$fullName}\r\n";
                $vcard .= "N:{$lastName};{$firstName};;;\r\n";

                if (!empty($fields['company'])) {
                    $vcard .= "ORG:{$fields['company']}\r\n";
                }
                if (!empty($fields['job_title'])) {
                    $vcard .= "TITLE:{$fields['job_title']}\r\n";
                }
                if (!empty($fields['dob'])) {
                    $vcard .= "BDAY:{$fields['dob']}\r\n";
                }
                if (!empty($fields['phone_vcard'])) {
                    $vcard .= "TEL;TYPE=CELL:{$fields['phone_vcard']}\r\n";
                }
                if (!empty($fields['email'])) {
                    $vcard .= "EMAIL;TYPE=WORK:{$fields['email']}\r\n";
                }
                if (!empty($fields['website'])) {
                    $vcard .= "URL:{$fields['website']}\r\n";
                }
                if (!empty($fields['address'])) {
                    // $vcard .= "ADR;TYPE=HOME:;;{$fields['address']};;;;\r\n";
                    // Remove HTML tags and extra spaces
                    $cleanAddress = strip_tags($fields['address']);

                    // Decode HTML entities like &nbsp; -> space
                    $cleanAddress = html_entity_decode($cleanAddress, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                    // Replace non-breaking spaces with normal spaces
                    $cleanAddress = str_replace("\xc2\xa0", ' ', $cleanAddress); // UTF-8 non-breaking space
                    $cleanAddress = str_replace('&nbsp;', ' ', $cleanAddress);   // fallback

                    // Replace line breaks/newlines with semicolons
                    $cleanAddress = preg_replace("/\r\n|\r|\n/", ';', $cleanAddress);

                    // Ensure no multiple ;; issues
                    $cleanAddress = preg_replace('/;{2,}/', ';', $cleanAddress);

                    $vcard .= "ADR;TYPE=HOME:;;{$cleanAddress};;;;\r\n";
                }
                if (!empty($fields['profile_image'])) {
                    try {
                        $photoBase64 = chunk_split(base64_encode(file_get_contents($fields['profile_image'])));
                        $vcard .= "PHOTO;ENCODING=b;TYPE=JPEG:{$photoBase64}\r\n";
                    } catch (\Exception $e) {
                        // ignore broken image
                    }
                }

                $vcard .= "END:VCARD";

                $data = $vcard;

            }else{
                $data = route('qr_link', ['uuid' => $shortCode]);
            }

            $year = now()->format('Y');
            $month = now()->format('m');
            $folder = "uploads/company/{$request?->company_id}/qrcode/{$year}-{$month}/";
            $sub_folder_path = $folder;
            $uploadedPath = public_path($folder);

            // Ensure folder exists
            if (!file_exists($uploadedPath)) {
                mkdir($uploadedPath, 0777, true);
            }

            // File name base
            $imageName = 'qr_' . uniqid();

            // ================== PNG ==================
            $pngFile = $uploadedPath . $imageName . '.png';
            Builder::create()
                ->writer(new PngWriter())
                ->data($data)
                ->size(300)
                ->margin(10)
                ->build()
                ->saveToFile($pngFile);

            // ================== SVG ==================
            $svgFile = $uploadedPath . $imageName . '.svg';
            Builder::create()
                ->writer(new SvgWriter())
                ->data($data)
                ->size(300)
                ->margin(10)
                ->build()
                ->saveToFile($svgFile);

            // ================== PDF ==================
            $pdfFile = $uploadedPath . $imageName . '.pdf';
            Builder::create()
                ->writer(new PdfWriter())
                ->data($data)
                ->size(300)
                ->margin(10)
                ->build()
                ->saveToFile($pdfFile);

            $uploadedImage = $sub_folder_path . $imageName . '.png';
            $input['qr_code_image'] = $uploadedImage;
            // return $input;

            QRLinkGenerate::create($input);
            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id) {}

    /**
     * Show the form for editing the specified resource.
     */
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

            $edit = QRLinkGenerate::findOrFail($id);
            $qrFields = [];
            if (!empty($edit->qr_type_fields)) {
                $qrFields = json_decode($edit->qr_type_fields, true);
            }

            // Merge qrFields into $edit object
            foreach ($qrFields as $key => $value) {
                $edit->{$key} = $value;
            }

            View::share('edit', $edit);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(QRLinkGenerateRequest $request, string $id)
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

        $validated = $request->validated();

        try {
            $validated['updated_by'] = $loginUserId;
            // return $validated;
            $updateData = QRLinkGenerate::findOrFail($id);
            if ($updateData) {
                unset($validated['id']);

                $qr_type = null;
                if ($request?->qr_type_id) {
                    $qr_type = QRLinkGenerate::QR_Types($request?->qr_type_id);
                }

                if (!$qr_type) {
                    return Redirect::back()->with('error', 'The QR Type field is required.')->withInput();
                }

                if ($qr_type) {
                    $qr_type_fields = [];
                    if ($qr_type?->slug && $qr_type?->slug == "link") {
                        $qr_type_fields = $request->only(['link']);
                    } elseif ($qr_type?->slug && $qr_type?->slug == "text") {
                        $qr_type_fields = $request->only(['text']);
                    } elseif ($qr_type?->slug && $qr_type?->slug == "email") {
                        $qr_type_fields = $request->only(['email_to', 'subject', 'body']);
                    } elseif ($qr_type?->slug && $qr_type?->slug == "whatsapp") {
                        $qr_type_fields = $request->only(['phone_whatsapp', 'message_whatsapp']);
                    } elseif ($qr_type?->slug && $qr_type?->slug == "wifi") {
                        $qr_type_fields = $request->only(['ssid', 'password', 'encryption']);
                    } elseif ($qr_type?->slug && $qr_type?->slug == "image") {
                        if ($request->hasFile('image')) {

                            $year = now()->format('Y');
                            $month = now()->format('m');
                            $folder = "uploads/company/{$request?->company_id}/qrcode/{$year}-{$month}/";
                            $sub_folder_path = $folder;
                            $uploadedPath = public_path($folder);

                            // Ensure folder exists
                            if (!file_exists($uploadedPath)) {
                                mkdir($uploadedPath, 0777, true);
                            }
                            $file = $request->file('image');
                            // File name base
                            $imageName = 'qr_type_image_' . uniqid();

                            $extenstion = $file->getClientOriginalExtension();

                            $filename = $imageName . '.' . $extenstion;

                            if ($file->move($uploadedPath, $filename)) {
                                $uploadedImage = $sub_folder_path . $filename;
                                if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                                    $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $imageName, 60, true);
                                    if ($webP) {
                                        $uploadedImage = $sub_folder_path . $webP;
                                        $extenstion = "webp";
                                    }
                                }
                                // return $uploadedImage;
                                $qr_type_fields = ['image' => $uploadedImage];
                            }
                        }
                } elseif ($qr_type?->slug && $qr_type?->slug == "pdf") {
                    if ($request->hasFile('pdf')) {

                        $year = now()->format('Y');
                        $month = now()->format('m');
                        $folder = "uploads/company/{$request?->company_id}/qrcode/{$year}-{$month}/";
                        $sub_folder_path = $folder;
                        $uploadedPath = public_path($folder);

                        // Ensure folder exists
                        if (!file_exists($uploadedPath)) {
                            mkdir($uploadedPath, 0777, true);
                        }
                        $file = $request->file('pdf');
                        // File name base
                        $imageName = 'qr_type_pdf_' . uniqid();

                        $extenstion = $file->getClientOriginalExtension();

                        $filename = $imageName . '.' . $extenstion;

                        if ($file->move($uploadedPath, $filename)) {
                            $uploadedImage = $sub_folder_path . $filename;
                            if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                                $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $imageName, 60, true);
                                if ($webP) {
                                    $uploadedImage = $sub_folder_path . $webP;
                                    $extenstion = "webp";
                                }
                            }
                            // return $uploadedImage;
                            $qr_type_fields = ['pdf' => $uploadedImage];
                        }
                    }
                } elseif ($qr_type?->slug == "vcard") {
                    $qr_type_fields = $request->only([
                        'first_name', 'last_name', 'phone_vcard', 'email',
                        'company', 'job_title', 'website', 'address',
                        'dob', 'card_color'
                    ]);

                    if ($request->hasFile('profile_image')) {

                        $year = now()->format('Y');
                        $month = now()->format('m');
                        $folder = "uploads/company/{$request?->company_id}/qrcode/{$year}-{$month}/";
                        $sub_folder_path = $folder;
                        $uploadedPath = public_path($folder);

                        // Ensure folder exists
                        if (!file_exists($uploadedPath)) {
                            mkdir($uploadedPath, 0777, true);
                        }
                        $file = $request->file('profile_image');
                        // File name base
                        $imageName = 'vcard_profile_' . uniqid();

                        $extenstion = $file->getClientOriginalExtension();

                        $filename = $imageName . '.' . $extenstion;

                        if ($file->move($uploadedPath, $filename)) {
                            $uploadedImage = $sub_folder_path . $filename;
                            if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                                $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $imageName, 60, true);
                                if ($webP) {
                                    $uploadedImage = $sub_folder_path . $webP;
                                    $extenstion = "webp";
                                }
                            }
                            // return $uploadedImage;
                            $qr_type_fields['profile_image'] = $uploadedImage;
                        }
                    }else{
                         $qr_type_fields['profile_image'] = $request?->profile_image_hidden ?? null;
                    }

                } elseif ($qr_type?->slug == "phonecall") {
                    $qr_type_fields = $request->only(['phone_call']);

                } elseif ($qr_type?->slug == "map") {
                    $qr_type_fields = $request->only(['latitude', 'longitude', 'map_link']);

                } elseif ($qr_type?->slug == "audio") {
                    if ($request->hasFile('audio')) {

                        $year = now()->format('Y');
                        $month = now()->format('m');
                        $folder = "uploads/company/{$request?->company_id}/qrcode/{$year}-{$month}/";
                        $sub_folder_path = $folder;
                        $uploadedPath = public_path($folder);

                        // Ensure folder exists
                        if (!file_exists($uploadedPath)) {
                            mkdir($uploadedPath, 0777, true);
                        }
                        $file = $request->file('audio');
                        // File name base
                        $imageName = 'audio_profile_' . uniqid();

                        $extenstion = $file->getClientOriginalExtension();

                        $filename = $imageName . '.' . $extenstion;

                        if ($file->move($uploadedPath, $filename)) {
                            $uploadedImage = $sub_folder_path . $filename;
                            if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                                $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $imageName, 60, true);
                                if ($webP) {
                                    $uploadedImage = $sub_folder_path . $webP;
                                    $extenstion = "webp";
                                }
                            }
                            // return $uploadedImage;
                            $qr_type_fields = ['audio' => $uploadedImage];
                        }
                    }

                } elseif ($qr_type?->slug == "facebook") {
                    $qr_type_fields = $request->only(['facebook_url']);

                } elseif ($qr_type?->slug == "instagram") {
                    $qr_type_fields = $request->only(['instagram_url']);

                } elseif ($qr_type?->slug == "youtube") {
                    $qr_type_fields = $request->only(['youtube_url']);

                } elseif ($qr_type?->slug == "telegram") {
                    $qr_type_fields = $request->only(['telegram_url']);

                } elseif ($qr_type?->slug && $qr_type?->slug == "appstore") {
                    $qr_type_fields = $request->only(['app_store_link', 'google_play_link', 'other_device_link']);

                } elseif ($qr_type?->slug == "tiktok") {
                    $qr_type_fields = $request->only(['tiktok_url']);

                } elseif ($qr_type?->slug == "video") {
                    $qr_type_fields = $request->only(['video_url']);

                } elseif ($qr_type?->slug == "googleform") {
                    $qr_type_fields = $request->only(['googleform_url']);

                } elseif ($qr_type?->slug == "twitter") {
                    $qr_type_fields = $request->only(['twitter_url']);

                } elseif ($qr_type?->slug == "snapchat") {
                    $qr_type_fields = $request->only(['snapchat_url']);

                } elseif ($qr_type?->slug == "spotify") {
                    $qr_type_fields = $request->only(['spotify_url']);

                } elseif ($qr_type?->slug == "googledoc") {
                    $qr_type_fields = $request->only(['googledoc_url']);

                } elseif ($qr_type?->slug == "googlereview") {
                    $qr_type_fields = $request->only(['googlereview_url']);

                } elseif ($qr_type?->slug == "googlesheet") {
                    $qr_type_fields = $request->only(['googlesheet_url']);

                } elseif ($qr_type?->slug == "payment") {
                    $qr_type_fields = $request->only(['payment_url']);

                } elseif ($qr_type?->slug == "sms") {
                    $qr_type_fields = $request->only(['phone_sms','phone_message']);

                } elseif ($qr_type?->slug == "logo") {
                    $qr_type_fields = $request->only(['logo_url']);

                } elseif ($qr_type?->slug == "office365") {
                    $qr_type_fields = $request->only(['office365_url']);

                } elseif ($qr_type?->slug == "shaped") {
                    $qr_type_fields = $request->only(['shaped_url']);

                } elseif ($qr_type?->slug == "paypal") {
                    $qr_type_fields = $request->only(['paypal_url']);

                } elseif ($qr_type?->slug == "etsy") {
                    $qr_type_fields = $request->only(['etsy_url']);

                } elseif ($qr_type?->slug == "png") {

                    if ($request->hasFile('png_file')) {

                        $year = now()->format('Y');
                        $month = now()->format('m');
                        $folder = "uploads/company/{$request?->company_id}/qrcode/{$year}-{$month}/";
                        $sub_folder_path = $folder;
                        $uploadedPath = public_path($folder);

                        // Ensure folder exists
                        if (!file_exists($uploadedPath)) {
                            mkdir($uploadedPath, 0777, true);
                        }
                        $file = $request->file('png_file');
                        // File name base
                        $imageName = 'png_file_' . uniqid();

                        $extenstion = $file->getClientOriginalExtension();

                        $filename = $imageName . '.' . $extenstion;

                        if ($file->move($uploadedPath, $filename)) {
                            $uploadedImage = $sub_folder_path . $filename;
                            if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                                $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $imageName, 60, true);
                                if ($webP) {
                                    $uploadedImage = $sub_folder_path . $webP;
                                    $extenstion = "webp";
                                }
                            }
                            // return $uploadedImage;
                            $qr_type_fields = ['png_file' => $uploadedImage];
                        }
                    }

                } elseif ($qr_type?->slug == "linkedin") {
                    $qr_type_fields = $request->only(['linkedin_url']);

                } elseif ($qr_type?->slug == "crypto") {
                    $qr_type_fields = $request->only(['crypto_type','crypto_amount','wallet_address','crypto_message']);

                } elseif ($qr_type?->slug == "calendar") {
                    $qr_type_fields = $request->only(['calendar_url']);

                } elseif ($qr_type?->slug == "social") {
                    $qr_type_fields = $request->only(['social_media_url']);

                } elseif ($qr_type?->slug == "reddit") {
                    $qr_type_fields = $request->only(['reddit_url']);

                } elseif ($qr_type?->slug == "file") {
                    if ($request->hasFile('file')) {

                        $year = now()->format('Y');
                        $month = now()->format('m');
                        $folder = "uploads/company/{$request?->company_id}/qrcode/{$year}-{$month}/";
                        $sub_folder_path = $folder;
                        $uploadedPath = public_path($folder);

                        // Ensure folder exists
                        if (!file_exists($uploadedPath)) {
                            mkdir($uploadedPath, 0777, true);
                        }
                        $file = $request->file('file');
                        // File name base
                        $imageName = 'file_' . uniqid();

                        $extenstion = $file->getClientOriginalExtension();

                        $filename = $imageName . '.' . $extenstion;

                        if ($file->move($uploadedPath, $filename)) {
                            $uploadedImage = $sub_folder_path . $filename;
                            if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                                $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $imageName, 60, true);
                                if ($webP) {
                                    $uploadedImage = $sub_folder_path . $webP;
                                    $extenstion = "webp";
                                }
                            }
                            // return $uploadedImage;
                            $qr_type_fields = ['file' => $uploadedImage];
                        }
                    }

                } elseif ($qr_type?->slug == "excel") {
                    if ($request->hasFile('excel')) {

                        $year = now()->format('Y');
                        $month = now()->format('m');
                        $folder = "uploads/company/{$request?->company_id}/qrcode/{$year}-{$month}/";
                        $sub_folder_path = $folder;
                        $uploadedPath = public_path($folder);

                        // Ensure folder exists
                        if (!file_exists($uploadedPath)) {
                            mkdir($uploadedPath, 0777, true);
                        }
                        $file = $request->file('excel');
                        // File name base
                        $imageName = 'excel_' . uniqid();

                        $extenstion = $file->getClientOriginalExtension();

                        $filename = $imageName . '.' . $extenstion;

                        if ($file->move($uploadedPath, $filename)) {
                            $uploadedImage = $sub_folder_path . $filename;
                            if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                                $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $imageName, 60, true);
                                if ($webP) {
                                    $uploadedImage = $sub_folder_path . $webP;
                                    $extenstion = "webp";
                                }
                            }
                            // return $uploadedImage;
                            $qr_type_fields = ['excel' => $uploadedImage];
                        }
                    }

                } elseif ($qr_type?->slug == "venmo") {
                    $qr_type_fields = $request->only(['venmo_url']);

                } elseif ($qr_type?->slug == "amazon") {
                    $qr_type_fields = $request->only(['amazon_url']);

                } elseif ($qr_type?->slug && $qr_type?->slug == "pptx") {
                    if ($request->hasFile('pptx_file')) {

                        $year = now()->format('Y');
                        $month = now()->format('m');
                        $folder = "uploads/company/{$request?->company_id}/qrcode/{$year}-{$month}/";
                        $sub_folder_path = $folder;
                        $uploadedPath = public_path($folder);

                        // Ensure folder exists
                        if (!file_exists($uploadedPath)) {
                            mkdir($uploadedPath, 0777, true);
                        }
                        $file = $request->file('pptx_file');
                        // File name base
                        $imageName = 'pptx_file_' . uniqid();

                        $extenstion = $file->getClientOriginalExtension();

                        $filename = $imageName . '.' . $extenstion;

                        if ($file->move($uploadedPath, $filename)) {
                            $uploadedImage = $sub_folder_path . $filename;
                            if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                                $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $imageName, 60, true);
                                if ($webP) {
                                    $uploadedImage = $sub_folder_path . $webP;
                                    $extenstion = "webp";
                                }
                            }
                            // return $uploadedImage;
                            $qr_type_fields = ['pptx_file' => $uploadedImage];
                        }
                    }
                } elseif ($qr_type?->slug == "2dbarcode") {
                    $qr_type_fields = $request->only([
                        'barcode_encode_type',
                        'gtin_code',
                        'product_link',
                    ]);
                } elseif ($qr_type?->slug == "upi") {
                    $qr_type_fields = $request->only([
                        'upi_id',
                        'payee_name',
                        'upi_amount',
                        'upi_remark',
                        'upi_currency'
                    ]);

                    // sanitize / defaults if needed
                    $qr_type_fields['upi_amount'] = $qr_type_fields['upi_amount'] ?? null;
                    $qr_type_fields['upi_remark'] = $qr_type_fields['upi_remark'] ?? null;
                    $qr_type_fields['upi_currency'] = $qr_type_fields['upi_currency'] ?? 'INR';
                } elseif ($qr_type?->slug == "pcr") {
                    $qr_type_fields = $request->only([
                        'expire_time',
                        'pcr_password',
                    ]);

                    if ($request->hasFile('pcr_file')) {

                        $year = now()->format('Y');
                        $month = now()->format('m');
                        $folder = "uploads/company/{$request?->company_id}/qrcode/{$year}-{$month}/";
                        $sub_folder_path = $folder;
                        $uploadedPath = public_path($folder);

                        // Ensure folder exists
                        if (!file_exists($uploadedPath)) {
                            mkdir($uploadedPath, 0777, true);
                        }
                        $file = $request->file('pcr_file');
                        // File name base
                        $imageName = 'pcr_file_' . uniqid();

                        $extenstion = $file->getClientOriginalExtension();

                        $filename = $imageName . '.' . $extenstion;

                        if ($file->move($uploadedPath, $filename)) {
                            $uploadedImage = $sub_folder_path . $filename;
                            if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                                $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $imageName, 60, true);
                                if ($webP) {
                                    $uploadedImage = $sub_folder_path . $webP;
                                    $extenstion = "webp";
                                }
                            }
                            // return $uploadedImage;
                            $qr_type_fields['pcr_file'] = $uploadedImage;
                        }
                    }else{
                         $qr_type_fields['pcr_file'] = $request?->pcr_file_hidden ?? null;
                    }
                } else {
                    dd("requestFile L-240", $qr_type, $request->all());
                }

                $validated['qr_type_fields'] = json_encode($qr_type_fields);
                }

                $updateData->update($validated);

                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' update successfully');
            }
            return Redirect::back()->withErrors('something went wrong please try again later')->withInput();
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function get_qr_details(Request $request)
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

        try {
            View::share('modules', $modules);

            $show = QRLinkGenerate::findOrFail($request->id);

            $originalLink = $show->qr_code_uuid ? url($show->qr_code_uuid) : '-';
            // Optional: increment scan count here if needed
            $showData = [
                'name'        => $show->name,
                'short_link'  => $originalLink,
                'created_at'  => $show->created_at->format('Y-m-d'),
                'qr_code_image' => $show->qr_code_image ? asset($show->qr_code_image) : null,
                //'scan_count'  => $show->scan_count ?? 0,
            ];
            return $this->sendResponse($showData, 'Records Fetched Successfully');
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


        try {
            $country = QRLinkGenerate::withTrashed()->findOrFail($request?->id);
            if ($country) {
                $country->status = $request->update_status;
                $country->updated_by = $loginUserId;
                $country->save();
                if ($isAjax) {
                    return $this->sendResponse($country, $modules['title'] . ' status update successfully.');
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

        $dataDelete = QRLinkGenerate::findOrFail($id);
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

    public function download($id, Request $request)
    {
        $type = $request->query('type', 'png');
        $size = (int) $request->query('size', 300); // Default size 300px

        $qr = QRLinkGenerate::findOrFail($id);

        $qrLink = route('qr_link', ['uuid' => $qr->qr_code_uuid]);
        $qrType = QRLinkGenerate::QR_Types($qr->qr_type_id);

        if ($qrType->slug == 'vcard') {
            $fields = json_decode($qr->qr_type_fields, true);

            $vcard  = "BEGIN:VCARD\r\n";
            $vcard .= "VERSION:3.0\r\n";

            $firstName = $fields['first_name'] ?? '';
            $lastName  = $fields['last_name'] ?? '';
            $fullName  = trim($firstName . ' ' . $lastName);

            $vcard .= "FN:{$fullName}\r\n";
            $vcard .= "N:{$lastName};{$firstName};;;\r\n";

            if (!empty($fields['company'])) {
                $vcard .= "ORG:{$fields['company']}\r\n";
            }
            if (!empty($fields['job_title'])) {
                $vcard .= "TITLE:{$fields['job_title']}\r\n";
            }
            if (!empty($fields['dob'])) {
                $vcard .= "BDAY:{$fields['dob']}\r\n";
            }
            if (!empty($fields['phone_vcard'])) {
                $vcard .= "TEL;TYPE=CELL:{$fields['phone_vcard']}\r\n";
            }
            if (!empty($fields['email'])) {
                $vcard .= "EMAIL;TYPE=WORK:{$fields['email']}\r\n";
            }
            if (!empty($fields['website'])) {
                $vcard .= "URL:{$fields['website']}\r\n";
            }
            if (!empty($fields['address'])) {
                // $vcard .= "ADR;TYPE=HOME:;;{$fields['address']};;;;\r\n";
                 // Remove HTML tags and extra spaces
                $cleanAddress = strip_tags($fields['address']);

                // Decode HTML entities like &nbsp; -> space
                $cleanAddress = html_entity_decode($cleanAddress, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                // Replace non-breaking spaces with normal spaces
                $cleanAddress = str_replace("\xc2\xa0", ' ', $cleanAddress); // UTF-8 non-breaking space
                $cleanAddress = str_replace('&nbsp;', ' ', $cleanAddress);   // fallback

                // Replace line breaks/newlines with semicolons
                $cleanAddress = preg_replace("/\r\n|\r|\n/", ';', $cleanAddress);

                // Ensure no multiple ;; issues
                $cleanAddress = preg_replace('/;{2,}/', ';', $cleanAddress);

                $vcard .= "ADR;TYPE=HOME:;;{$cleanAddress};;;;\r\n";
            }
            if (!empty($fields['profile_image'])) {
                try {
                    $photoBase64 = chunk_split(base64_encode(file_get_contents($fields['profile_image'])));
                    $vcard .= "PHOTO;ENCODING=b;TYPE=JPEG:{$photoBase64}\r\n";
                } catch (\Exception $e) {
                    // ignore broken image
                }
            }

            $vcard .= "END:VCARD";

            $data = $vcard;
        } else {
            // Default → just encode the route URL
            $data = route('qr_link', ['uuid' => $qr->qr_code_uuid]);
        }

        $builder = Builder::create()
            ->data($data)
            ->size($size)
            ->margin(0);

        switch ($type) {
            case 'svg':
                $builder->writer(new SvgWriter());
                $mime = 'image/svg+xml';
                $ext = 'svg';
                break;

            case 'pdf':
                $builder->writer(new PdfWriter());
                $mime = 'application/pdf';
                $ext = 'pdf';
                break;

            default: // png
                $builder->writer(new PngWriter());
                $mime = 'image/png';
                $ext = 'png';
        }

        $qrGenerated = $builder->build();
        $fileName = "qrcode-{$id}.{$ext}";

        return response($qrGenerated->getString(), 200, [
            'Content-Type'        => $mime,
            'Content-Disposition' => "attachment; filename={$fileName}",
        ]);

        // $builder = Builder::create()
        //     ->data($qrLink)
        //     ->size($size)
        //     ->margin(0);

        // switch ($type) {
        //     case 'svg':
        //         $builder->writer(new SvgWriter());
        //         $mime = 'image/svg+xml';
        //         $ext = 'svg';
        //         break;

        //     case 'pdf':
        //         $builder->writer(new PdfWriter());
        //         $mime = 'application/pdf';
        //         $ext = 'pdf';
        //         break;

        //     default: // png
        //         $builder->writer(new PngWriter());
        //         $mime = 'image/png';
        //         $ext = 'png';
        // }

        // $qrGenerated = $builder->build();

        // $fileName = "qrcode-{$id}.{$ext}";

        // return response($qrGenerated->getString(), 200, [
        //     'Content-Type'        => $mime,
        //     'Content-Disposition' => "attachment; filename={$fileName}",
        // ]);
    }

}
