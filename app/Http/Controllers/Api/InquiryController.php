<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Followup;
use App\Models\FollowupLogs;
use App\Models\Inquiry;
use App\Models\InquiryLogs;
use App\Models\MarketingStatus;
use App\Models\RolePermissionsApp;
use App\Models\SaleExecutiveTracking;
use App\Models\SourceOfInquiry;
use App\Models\TeamPerson;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InquiryController extends Controller
{
    public $modules = [];
    public function __construct(Request $request)
    {
        parent::__construct();

        $this->modules = [
            'table_name' => (new Inquiry())->getTable(),
            'module_name' => 'Inquiry',
        ];
    }


    /**
     * Inquiry List
     * company_id is nullable when token is exist
     * inquiry_status_id
     */
    public function inquiry_list(Request $request)
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

            $modules['authLoginUserDetail'] = $loginUser ?? null;
            $modules['company_id'] = $request['company_id'] ?? null;
            $modules['parent_type_id'] = $loginUser?->parent_type_id ?? null;

            $modules['view_permission'] = Helper::checkAppPermission("app", (new RolePermissionsApp())->getTable(), $modules['company_id'], $loginUser?->id, $modules['module_name'], 'view_flag');
            //dd( $modules['view_permission']);
            if (!$modules['view_permission']) {
               return $this->sendError('You do not have permission to access the '.$modules['module_name'].' module.', [], [], 403);
            }

            $modules['all_data_permission'] = Helper::checkAppPermission("app", (new RolePermissionsApp())->getTable(), $modules['company_id'], $loginUser?->id, $modules['module_name'], 'all_data_flag');
            $modules['personal_data_permission'] = Helper::checkAppPermission("app", (new RolePermissionsApp())->getTable(), $modules['company_id'], $loginUser?->id, $modules['module_name'], 'personal_data_flag');


            $validator =  Validator::make($request->all(), [
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
                'from_date' => ['nullable', 'date', 'date_format:Y-m-d'],
                'to_date' => ['nullable', 'date', 'date_format:Y-m-d']
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            /* Pagination */
            $pagination = (isset($request?->page)) ? true : false;
            $perPage = (isset($request?->per_page)) ? $request?->per_page : env("API_PER_PAGE");
            $page = (isset($request?->page)) ? $request?->page : 1;

            $module = Inquiry::where('company_id', $company_id);

            if ($modules['personal_data_permission'] && !$modules['all_data_permission']) {
                $module->where(function ($subQuery) use ($loginUser) {
                    $subQuery->where('inquiry_created_by', $loginUser?->id)
                        ->orWhereRaw("FIND_IN_SET(?, inquiry_assigned_to)", [$loginUser?->id]);
                });
            }else if (!$modules['personal_data_permission'] && !$modules['all_data_permission']) {
                $module->where(function ($subQuery) use ($loginUser) {
                    $subQuery->where('inquiry_created_by', $loginUser?->id)
                        ->orWhereRaw("FIND_IN_SET(?, inquiry_assigned_to)", [$loginUser?->id]);
                });
            }

            // if ($loginUser && $loginUser?->parent_type_id) {
            //     $module = $module->whereRaw("FIND_IN_SET(?, inquiry_assigned_to)", [$loginUser?->id]);
            // }
            if ($request?->filter_by_inquiry_no) {
                $module = $module->where('inquiry_no', $request?->filter_by_inquiry_no);
            }

            if ($request?->inquiry_status_id) {
                $module = $module->where('inquiry_status_id', $request?->inquiry_status_id);
            }

            if ($request?->followup_status_id) {
                $module = $module->where('followup_status_id', $request?->followup_status_id);
            }

            if ($request?->source_of_inquiry_id) {
                $module = $module->where('source_of_inquiry_id', $request?->source_of_inquiry_id);
            }

            if ($request?->from_date && !$request?->to_date) {
                $module = $module->whereDate('inquiry_date', "==", Carbon::parse($request?->from_date));
            } else if ($request?->from_date && $request?->to_date) {
                // $module = $module->whereDate('inquiry_date', ">=", Carbon::parse($request?->from_date))->whereDate('inquiry_date', "<=", Carbon::parse($request?->to_date));
                $module = $module->whereBetween('inquiry_date', [Carbon::parse($request?->from_date), Carbon::parse($request?->to_date)]);
            }
            // dd('Inquiry 84', Helper::interpolateQuery($module->toSql(), $module->getBindings()), $module->first(), $loginUser->toArray());
            $module = $module->orderBy('id', 'DESC');

            if ($request?->toSql) {
                return Helper::interpolateQuery($module?->toSql(), $module->getBindings());
            }
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
                    // $temp['id'] = $record?->id;
                    // return $temp;

                    // $temp = $record;
                    $temp['id'] = $record?->id . "";
                    $temp['company_id'] = $record?->company_id . "";
                    $temp['company_name'] = $record?->company?->company_name . "";

                    $temp['inquiry_no'] = $record?->inquiry_no . "";
                    $temp['inquiry_date'] = $record?->inquiry_date . "";

                    $temp['inquiry_to_customer_id'] = $record?->inquiry_to_customer_id;

                    $temp['customer_name'] = $record?->customer_name . "";
                    $temp['contact_person_name'] = $record?->contact_person_name . "";
                    $temp['mobile_no'] = $record?->mobile_no . "";
                    $temp['whatsapp_no'] = $record?->whatsapp_no . "";
                    $temp['email'] = $record?->email . "";

                    $temp['platform'] = $record?->platform . "";

                    $temp['inquiry_status_id'] = $record?->inquiry_status_id . "";
                    $temp['inquiry_status_name'] = $record?->inquiry_status?->name ??  "";
                    $temp['inquiry_status_color'] = $record?->inquiry_status?->status_color ??  "";
                    $temp['inquiry_status_color_2'] = $record?->inquiry_status?->status_color_2 ??  "";
                    $temp['inquiry_text_color_2'] = $record?->inquiry_status?->text_color ??  "";

                    $temp['followup_status_id'] = $record?->followup_status_id . "";
                    $temp['followup_status_name'] = $record?->followup_status?->name ?? "";
                    $temp['followup_status_color'] = $record?->followup_status?->status_color ?? "";
                    $temp['followup_status_color_2'] = $record?->followup_status?->status_color_2 ?? "";
                    $temp['followup_text_color'] = $record?->followup_status?->text_color ?? "";

                    $temp['source_of_inquiry_id'] = $record?->source_of_inquiry_id . "";
                    $temp['source_of_inquiry_name'] = $record?->source_of_inquiry?->name ?? "";

                    $temp['requirement_details'] = $record?->requirement_details . "";

                    $temp['inquiry_created_by'] = $record?->inquiry_created_by . "";
                    $temp['inquiry_created_by_name'] = $record?->created_by_name. "";
                    // $temp['inquiry_created_by_name'] = $record?->created_by_name?->name . "" ?? '';

                    $temp['inquiry_assigned_to'] = $record?->inquiry_assigned_to . "";
                    $temp['inquiry_assigned_to_name'] = $record?->assigned_to_name;
                    // $temp['inquiry_assigned_to_name'] = $record?->assigned_to_name?->name . "";

                    $temp['website'] = $record?->website . "";

                    $temp['address'] = $record?->address . "";

                    $temp['country_id'] = $record?->country_id . "";
                    $temp['country_name'] = $record?->country?->name . "";

                    $temp['state_id'] = $record?->state_id . "";
                    $temp['state_name'] = $record?->state?->name . "";

                    $temp['city_id'] = $record?->city_id . "";
                    $temp['city_name'] = $record?->city?->name . "";

                    $temp['area_id'] = $record?->area_id . "";
                    $temp['area_name'] = $record?->area?->area_name . "";

                    $temp['attachment'] = $record?->attachment . "";
                    $temp['attachment_url'] = $record?->attachment_url . "";

                    $temp['followup_date'] = $record?->followup_date . "";
                    $temp['followup_details'] = $record?->followup_details . "";

                    // $temp['status'] = $record?->status . "";

                    return $temp;
                });

                return $this->sendResponse($tempResponseData, "Your Inquiry List.");
            }
            return $this->sendError("Something want to wrong in data fetching.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }


    /**
     * Inquiry Add / Edit
     * company_id is Required
     * */
    public function inquiry_add_edit(Request $request, $edit_id = null)
    {
        DB::beginTransaction();
        try {

            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            if ($loginUser?->company_id) {
                $request['company_id'] = $loginUser?->company_id;
            }

            if ($request->has('inquiry_assigned_to') && is_string($request->inquiry_assigned_to)) {
                $request->merge([
                    'inquiry_assigned_to_array' => array_filter(explode(',', $request->inquiry_assigned_to))
                ]);
            }

            $company_id = $request?->company_id ?? $loginUser?->company_id;
            $request['company_id'] = $company_id;
            // return Rule::date()->format("Y-m-d")->after(today());
            // return $request?->edit_id;
            $rules =  [
                'edit_id' => ['nullable', Rule::exists((new Inquiry())->getTable(), 'id')],
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
                'platform' => ['required', 'in:web,android,ios'],
                'inquiry_date' => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:today'],
                'customer_name' => ['required', 'string'],
                'contact_person_name' => ['nullable', 'string'],
                'mobile_no' => [
                    'required',
                    'numeric',
                    function ($attribute, $value, $fail) use ($request) {
                        $companyId = $request?->company_id;
                        $company = Company::find($companyId);

                        if ($company) {
                            $length = strlen((string) $value);

                            if ($company->mobile_min && $length < $company->mobile_min) {
                                $fail("The mobile number must be at least {$company->mobile_min} digits.");
                            }

                            if ($company->mobile_max && $length > $company->mobile_max) {
                                $fail("The mobile number must not exceed {$company->mobile_max} digits.");
                            }
                        } else {
                            $fail("Please select a company.");
                        }
                    },
                    Rule::unique((new Inquiry())->getTable(), 'mobile_no')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id)
                                ->whereNull('deleted_at');
                        })
                        ->ignore($request?->edit_id),
                ],
                // 'inquiry_status_id' => ['required', 'exists:' . (new MarketingStatus())->getTable() . ',id'],
                // 'followup_status_id' => ['required', 'exists:' . (new MarketingStatus())->getTable() . ',id'],
                'inquiry_status_id' => [
                    'required',
                    function ($attribute, $value, $fail) use ($request) {
                        $exists = MarketingStatus::where('id', $value)
                            ->where('type', 'Inquiry')
                            ->where('company_id', $request?->company_id)
                            ->exists();

                        if (!$exists) {
                            $fail("The selected inquiry status is invalid, not of type 'Inquiry', or does not belong to the selected company.");
                        }
                    },
                ],
                'followup_status_id' => [
                    'required',
                    function ($attribute, $value, $fail) use ($request) {
                        $exists = MarketingStatus::where('id', $value)
                            ->where('type', 'Followup')
                            ->where('company_id', $request?->company_id)
                            ->exists();

                        if (!$exists) {
                            $fail("The selected followup status is invalid, not of type 'Followup', or does not belong to the selected company.");
                        }
                    }
                ],
                'whatsapp_no' => ['nullable', 'digits:10', 'numeric'],
                'source_of_inquiry_id' => ['nullable', 'exists:' . (new SourceOfInquiry())->getTable() . ',id'],
                'email' => ['nullable', 'email'],
                'website' => ['nullable', 'url'],
                'followup_date' => ['nullable', 'date', 'date_format:"Y-m-d'],
                'followup_details' => ['nullable'],
                'requirement_details' => ['nullable'],
                'address' => ['nullable'],
                'inquiry_date' => ['required', 'date', 'date_format:"Y-m-d'],
                'attachment' => ['nullable'],
                'country_id' => ['nullable'],
                'state_id' => ['nullable'],
                'city_id' => ['nullable'],
                'area_id' => ['nullable'],
                'inquiry_created_by' => ['exists:' . (new TeamPerson())->getTable() . ',id'],
                'inquiry_assigned_to_array' => ['required', 'array'],
                'inquiry_assigned_to_array.*' => ['exists:' . (new TeamPerson())->getTable() . ',id'],
                'attachment' => ['nullable', 'mimes:jpeg,png,jpg,gif,pdf,docx', 'max:2048'],
            ];

            $validator =  Validator::make($request->all(), $rules);

            $validator->setAttributeNames([
                'inquiry_assigned_to_array' => 'Inquiry Assign To',
                'inquiry_assigned_to_array.*' => 'Inquiry Assign To',
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $loginUserId = $loginUser?->id;
            $input = $request->except(['edit_id', 'tracking_latitude', 'tracking_longitude', 'tracking_address']);
            $input['created_by'] = $loginUserId;

            $generate_inquiry_no = (new CommonController(new Request()))->generate_inquiry_no($request);
            // dd("L-410", $validated, $modules['company_id'], $newRequest, $generate_inquiry_no);

            if ($generate_inquiry_no->getStatusCode() == 200) {
                $dataResponse = $generate_inquiry_no?->getData();
                if ($dataResponse?->data) {
                    $input['inquiry_no'] = $dataResponse?->data;
                }
            }

            if ($request->hasFile('attachment')) {
                // return $image_name = Helper::make_slug('leave-application ' . (string)$loginUser?->id. ' '. (string)$loginUser?->name . ' ' . date('Ymd-His'));
                $image_name = Helper::make_slug(date('Ymd-His') . ' ' . (string)$loginUser?->id . ' ' . (string)$loginUser?->name);

                $file = $request->file('attachment');

                $extenstion = $file->getClientOriginalExtension();

                $filename = $image_name . '.' . $extenstion;

                $sub_folder_path = Helper::fileUploadPath($loginUser, Inquiry::$folderPath);
                $uploadedPath = public_path($sub_folder_path);
                // return $uploadedPath;
                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $sub_folder_path . $filename;
                    if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                        $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $image_name, 60, true);
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

            if(isset($input['inquiry_assigned_to_array'])){
                unset($input['inquiry_assigned_to_array']);
            }
            // return $input;

            if ($request?->edit_id) {
                $inquiry = Inquiry::where('id', $request?->edit_id)->update($input);
                if (!$inquiry) {
                    return $this->sendResponse($inquiry, "Inquiry not found.");
                }

                InquiryLogs::create([
                    'company_id' => $request?->company_id,
                    'inquiry_id' => $request?->edit_id,
                    'action' => 'Update',
                    'json_data' => json_encode([
                        'inquiry' => $inquiry,
                        'products' => $request->has('products') ? $request->products : []
                    ]),
                    'remarks' => '',
                    'created_by' => $loginUserId,
                ]);
                if ($request?->tracking_latitude && $request?->tracking_longitude && $request?->tracking_address) {
                    // SaleExecutiveTracking::create([
                    //     'company_id' => $company_id,
                    //     'type_reference_id' => $request?->edit_id,
                    //     'type' => SaleExecutiveTracking::INQUIRY_EDIT,
                    //     'latitude' => $request?->tracking_latitude,
                    //     'longitude' => $request?->tracking_longitude,
                    //     'address' => $request?->tracking_address,
                    //     'current_datetime' => Carbon::now(),
                    //     'created_by' => $loginUser?->id,
                    // ]);
                }
                DB::commit();
                $inquiry = Inquiry::where('id', $request?->edit_id)->first();

                return $this->sendResponse($inquiry, "Inquiry update successfully");
            } else {
                $inquiry = Inquiry::create($input);

                if ($request?->tracking_latitude && $request?->tracking_longitude && $request?->tracking_address) {
                    // SaleExecutiveTracking::create([
                    //     'company_id' => $company_id,
                    //     'type_reference_id' => $inquiry?->id,
                    //     'type' => SaleExecutiveTracking::INQUIRY_ADD,
                    //     'latitude' => $request?->tracking_latitude,
                    //     'longitude' => $request?->tracking_longitude,
                    //     'address' => $request?->tracking_address,
                    //     'current_datetime' => Carbon::now(),
                    //     'created_by' => $loginUser?->id,
                    // ]);
                }
            }

            $lastInsertedId = $inquiry->id;
            $lastFollowupId = null;

            $statusDB = MarketingStatus::where('id', $request?->followup_status_id)->first();
            //$teamDB = TeamPerson::where('id', $request?->inquiry_assigned_to)->first();
            $teamMembers = TeamPerson::whereIn('id', explode(',', $request?->inquiry_assigned_to))->get();
            $teamNames = $teamMembers->pluck('name')->implode(', ');

            if (!empty($request->followup_details) && !empty($request->followup_date)) {
                // $followup_date = Helper::convert_date($request->followup_date, 'd-m-Y', 'Y-m-d');
                $followup_date = $request->followup_date;

                $followup = Followup::create([
                    'company_id' => $request?->company_id,
                    'inquiry_id' => $lastInsertedId,
                    'date_time' => $followup_date ?? null,
                    'reason_id' => '0',
                    'details' => $request->followup_details,
                    'status_id' => $request?->followup_status_id,
                    'created_by' => $loginUserId,
                ]);
                $lastFollowupId = $followup->id;
                $jsonData = $followup->toJson();

                FollowupLogs::create([
                    'company_id' => $request?->company_id,
                    'inquiry_id' => $lastInsertedId,
                    'followup_id' => $lastFollowupId,
                    'action' => 'Create',
                    'status_id' => $request?->followup_status_id,
                    'status_value' => $statusDB->name,
                    'assigned_to_id' => $request?->inquiry_assigned_to,
                    'assigned_to' => $teamNames,
                    'json_data' => $jsonData,
                    'remarks' => '',
                    'created_by' => $loginUserId,
                ]);
            }

            InquiryLogs::create([
                'company_id' => $request?->company_id,
                'inquiry_id' => $lastInsertedId,
                'followup_id' => $lastFollowupId,
                'action' => 'Create',
                'status_id' => $request?->followup_status_id,
                'status_value' => $statusDB->name,
                'inquiry_assigned_to_id' => $request?->inquiry_assigned_to,
                'inquiry_assigned_to' => $teamNames,
                'json_data' => json_encode([
                    'inquiry' => $inquiry,
                    'products' => $request->has('products') ? $request->products : []
                ]),
                'remarks' => '',
                'created_by' => $loginUserId,
            ]);
            DB::commit();
            return $this->sendResponse($inquiry, "Inquiry added successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage());
        }
        DB::rollBack();
        return $this->sendError("Something want to wrong in register API");
    }


    /**
     * Inquiry delete
     * company_id is nullable when token is exist
     * inquiry_id
     */
    public function inquiry_delete(Request $request)
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
                'inquiry_id' => ['required', Rule::exists((new Inquiry())->getTable(), 'id')],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }


            $module = Inquiry::where('company_id', $company_id);
            $module = $module->where('id', $request?->inquiry_id);
            $module = $module->orderBy('id', 'DESC');
            $module = $module->first();

            if ($module) {
                if ($request?->tracking_latitude && $request?->tracking_longitude && $request?->tracking_address) {
                    SaleExecutiveTracking::create([
                        'company_id' => $company_id,
                        'type_reference_id' => $request?->inquiry_id,
                        'type' => SaleExecutiveTracking::ADD_INQUIRY,
                        'latitude' => $request?->tracking_latitude,
                        'longitude' => $request?->tracking_longitude,
                        'address' => $request?->tracking_address,
                        'current_datetime' => Carbon::now(),
                        'created_by' => $loginUser?->id,
                    ]);
                }
                // if ($module?->status != "pending") {
                //     return $this->sendError("You can not delete this Inquiry. reason is your Inquiry current status is " . ucfirst($module->status));
                // }
                if ($module->delete()) {
                    return $this->sendResponse($module, "Your Inquiry Delete Successfully.");
                }
                return $this->sendError("Your Inquiry found, but can not deleted.");
            }

            return $this->sendError("Your Inquiry not found.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }
}
