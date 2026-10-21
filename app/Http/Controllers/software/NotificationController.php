<?php

namespace App\Http\Controllers\software;

use App\Http\Controllers\Controller;
use App\Helpers\Helper;
use App\Models\AdminSoftware;
use App\Models\MainMenu;
use App\Models\Notification;
use App\Models\SubMenu;
use Illuminate\Http\Request;
use App\Models\TeamPerson;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use App\Services\FirebaseService;

class NotificationController extends Controller
{

    public $modules = [];

    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Notification',
            'folder_path' => 'software.modules.notification',
            'route' => 'notification',
            'table_name' => '',
            'permisstion_prefix' => 'notification',
            'module_name' => 'Notification',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null
        ];
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

        try {

            $columns = [
                (object)['data' => "id", 'name' => 'id', 'td_label' => 'Id'],
                (object)['data' => "user_name", 'name' => 'user_name', 'td_label' => 'User Name'],
                (object)['data' => "user_type", 'name' => 'user_type', 'td_label' => 'User Type'],
                (object)['data' => "title", 'name' => 'title', 'td_label' => 'Title', 'className' =>  'text-wrap'],
                (object)['data' => "body", 'name' => 'body', 'td_label' => 'Body', 'className' =>  'text-wrap'],
                (object)['data' => "module_name", 'name' => 'module_name', 'td_label' => 'Module Name', 'className' =>  'text-wrap'],
                (object)['data' => "created_by", 'name' => 'created_by', 'td_label' => 'Created By', 'className' =>  'text-wrap'],
                (object)['data' => "created_at", 'name' => 'created_at', 'td_label' => 'Created Date', 'className' =>  ''],
            ];
            if ($modules['company_id']) {
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'company_id';
                });

                $columns = array_values($columns);
            }
            // dd(!$modules['company_id'], $columns);
            View::share("columns", $columns);

            View::share('modules', $modules);

            if ($request->ajax()) {
                 if (!$modules['view_permission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }

                // dd($modules, Auth::guard('team_person')->check(), $modules['personal_data_permission'], $modules['all_data_permission'], $loginUserId);
                $data = Notification::select('*')
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check()) {
                            $query->where('user_id', $loginUserId);
                            $query->where('user_type', "Team");

                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where('created_by', $loginUserId);
                            }
                        }
                    })
                    ->orderBy('id', 'DESC');

                   $returnData = Datatables::of($data)
                    ->addIndexColumn()
                    ->filter(function ($query) use ($request) {
                        if ($request->has('search')) {
                            $query->where('name', 'like', "%" . $request->search . "%");
                        }
                    })
                    ->editColumn('user_name', function ($row) {
                        return $row->user_name ?? '-';
                    })
                    ->editColumn('created_by', function ($row) {
                        return $row->created_by_name ?? '-';
                    })
                    ->editColumn('body', function ($row) {
                        return $row->body ?? '-';
                    })
                    ->editColumn('created_at', function ($row) {
                        return $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d-m-Y H:i') : '';
                    })
                    ->rawColumns(['body','title'])
                    ->make(true);


                return $returnData;
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
    public function destroy(string $id)
    {
        //
    }

    public function updateDeviceToken(Request $request)
    {
        try {
            $modules = $this->modules;
            $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
            $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
            $modules['company_id'] = $this->authenticateLoginUserDetails?->company_id ?? null;
            $loginUserId = $modules['authLoginUserDetail']?->id ?? null;
            $modules['parent_type_id'] = $this->authenticateLoginUserDetails?->parent_type_id ?? null;

            if (!empty($loginUserId)) {
                if ($modules['currentGuard'] == 'admin_software') {
                    $adminSoftware = AdminSoftware::findOrFail($loginUserId);
                    $adminSoftware->device_token = $request->token;
                    $adminSoftware->save();
                }else{
                    $teamPerson = TeamPerson::findOrFail($loginUserId);
                    $teamPerson->device_token = $request->token;
                    $teamPerson->save();
                }


                return $this->sendResponse([], 'Token successfully stored.');
            }
            return response()->json(['error' => 'User ID not found.'], 400);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', $e->getMessage(), [], 500);
        }
    }

    public function getNotification(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        $type = '';
        if ($modules['currentGuard'] == 'admin_software') {
            $type = 'Admin';
        } else {
            $type = 'Team';
        }

        try {
            $notificationData = Notification::where('notify_read', 0);

            //if ($modules['currentGuard'] == 'team_person') {
                $notificationData = $notificationData->where('user_id', $loginUserId)->where('user_type', $type)->where('notify_read', 0)->orderBy('id', 'desc')->limit(5);
            //}

            $notificationData = $notificationData->get()->map(function ($notification) {
                    $subMenu = SubMenu::where('name', $notification->module_name)->first();
                    $mainMenuIcon = 'tf-icons ti ti-smart-home';
                    $url = '';

                    if ($subMenu) {
                        $mainMenu = MainMenu::where('id', $subMenu->main_menu_id)->first();
                        $mainMenuIcon = $mainMenu ? $mainMenu->menu_icon : 'tf-icons ti ti-smart-home';
                    }

                    $excludedModules = ['facebook', 'Indiamart', 'tradeindia'];
                    if (!in_array($notification->module_name, $excludedModules, true)) {
                        if ($subMenu) {
                            $mainMenu = MainMenu::where('id', $subMenu->main_menu_id)->first();
                            $mainMenuIcon = $mainMenu?->menu_icon ?? 'tf-icons ti ti-smart-home';

                            if(!empty($subMenu->route_name)) {
                                $routeBase = explode('.', $subMenu->route_name)[0] ?? null;

                                if ($routeBase && $notification->module_id) {
                                    $action = strtolower($notification->module_action);

                                    if (($action === 'add' || $action === 'edit') && ($notification->module_name != 'Follow Up')) {
                                       $url = url("software/{$routeBase}/{$notification->module_id}/edit");
                                    } else {
                                        $url = url("software/{$routeBase}");
                                    }
                                }
                            }
                        }
                    }

                    return [
                        'id' => $notification->id,
                        'user_id' => $notification->user_id,
                        'user_type' => $notification->user_type,
                        'title' => $notification->title,
                        'body' => $notification->body,
                        'created_at' => \Carbon\Carbon::parse($notification->created_at)->diffForHumans(),
                        'menu_icon' => $mainMenuIcon,
                        'module_name' => $notification->module_name,
                        'module_id' => $notification->module_id,
                        'module_action' => $notification->module_action,
                        'module_url' => $url,
                    ];
                });

            return $this->sendResponse($notificationData, 'Notification Fetched Successfully.');
        }catch (\Exception $e) {
            return $this->sendError('Something went wrong', $e->getMessage(), [], 500);
        }
    }

    public function clearNotification(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        $type = '';
        if ($modules['currentGuard'] == 'admin_software') {
            $type = 'Admin';
        } else {
            $type = 'Team';
        }

        try {

            Notification::where('user_id', $loginUserId)->where('user_type', $type)->where('notify_read', 0)->update(['notify_read' => 1]);
            return $this->sendResponse([],'Notifications marked as read.');

        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', $e->getMessage(), [], 500);
        }
    }

    // In NotificationController.php
    public function acknowledge(Request $request)
    {
        $validated = $request->validate([
            'notification_id' => 'required|integer',
            'action' => 'required|in:completed,decline'
        ]);

        try {
            $notification = Notification::findOrFail($validated['notification_id']);

            $notification->acknowledgment_status = $validated['action'] === 'completed' ? 'completed' : 'declined';
            $notification->acknowledged_at = now();
            $notification->save();

            return response()->json([
                'success' => true,
                'message' => 'Notification acknowledged successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to acknowledge notification'
            ], 500);
        }
    }

}
