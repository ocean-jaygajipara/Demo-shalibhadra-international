<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Followup;
use App\Models\FollowUpReason;
use App\Models\Inquiry;
use App\Models\MarketingStatus;
use App\Models\SaleExecutiveTracking;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApiVisitController extends Controller
{

    public function __construct(Request $request) {}

    /**
     * Visit List
     * company_id is nullable when token is exist
     */
    public function visit_list(Request $request)
    {
        try {

            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            $company_id = $request?->company_id ?? $loginUser?->company_id;
            $request['company_id'] = $company_id;


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

            $module = Visit::query();
            // $module = $module->with(['company']);
            $module = $module->where('company_id', $company_id);
            // $module = $module->where('created_by', $loginUserId);

            if ($request?->from_date && !$request?->to_date) {
                $fromdateTime = Carbon::parse($request->from_date)->toDateString();
                $module = $module->whereDate('date_time', '>=', $fromdateTime);
            } else if ($request?->from_date && $request?->to_date) {
                $filter_start = Carbon::parse($request->date_time)->toDateString();
                $filter_end = Carbon::parse($request->to_date)->toDateString();

                $module = $module->where(function ($sub) use ($filter_start, $filter_end) {
                    $sub->whereDate('date_time', '>=', $filter_start)
                        ->whereDate('date_time', '<=', $filter_end);
                });
            }

            if ($request?->model) {
                $module = $module->where('model', $request?->model);
            }
            $module = $module->orderBy('id', 'DESC');


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

                    $temp['id'] = $record?->id . "";

                    $temp['company_id'] = $record?->company_id . "";
                    $temp['company_name'] = $record?->company?->company_name . "";

                    $temp['model'] = $record?->model . "";
                    $temp['model_id'] = $record?->model_id . "";
                    $temp['inquiry_name'] = "";
                    $temp['customer_name'] = "";
                    $temp['model_data'] = "";
                    if (Helper::checkTableExist($record?->model)) {
                        $model_data = DB::table($record?->model)->where('id', $record?->model_id)->first();
                        if ($model_data) {
                            if ($record?->model == "inquiries") {
                                $temp['inquiry_name'] = $model_data?->customer_name . " ~ " . $model_data?->contact_person_name;
                            } elseif ($record?->model == "customers") {
                                $temp['customer_name'] = $model_data?->customer_name . " ~ " . $model_data?->contact_person_name;
                            }
                        }
                        // $temp['model_data'] = $model_data;
                    }

                    $temp['meet_person_name'] = $record?->meet_person_name . "";
                    $temp['visit_start_date'] = $record?->visit_start_date . "";
                    $temp['visit_start_remark'] = $record?->visit_start_remark . "";
                    $temp['visit_start_latitude'] = $record?->visit_start_latitude . "";
                    $temp['visit_start_longitude'] = $record?->visit_start_longitude . "";
                    $temp['visit_start_address'] = $record?->visit_start_address . "";
                    $temp['start_image'] = $record?->start_image . "";
                    $temp['start_image_url'] = $record?->start_image_url . "";

                    $extra_detail = json_decode($record?->extra_detail);
                    $temp['extra_detail'] = $extra_detail;

                    $temp['visit_stop_date'] = $record?->visit_stop_date . "";
                    $temp['visit_stop_remark'] = $record?->visit_stop_remark . "";
                    $temp['visit_stop_latitude'] = $record?->visit_stop_latitude . "";
                    $temp['visit_stop_longitude'] = $record?->visit_stop_longitude . "";
                    $temp['visit_stop_address'] = $record?->visit_stop_address . "";
                    $temp['stop_image'] = $record?->stop_image . "";
                    $temp['stop_image_url'] = $record?->stop_image_url . "";

                    $temp['total_time_duration'] = $record?->total_time_duration . "";

                    $temp['platform'] = $record?->platform . "";
                    $temp['created_at'] = $record?->created_at . "";
                    $temp['updated_at'] = $record?->updated_at . "";

                    return $temp;
                });

                return $this->sendResponse($tempResponseData, "Visit list by company.");
            }
            return $this->sendError("Something want to wrong in data fetching.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }


    /**
     * Visit Start
     * company_id is nullable when token is exist
     */
    public function visit_start(Request $request)
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

            $company_id = $request?->company_id ?? $loginUser?->company_id;
            $request['company_id'] = $company_id;

            if (Helper::checkTableExist($request?->model)) {
                $input['model'] =  $request?->model;
            } else if ($request?->model == "inquiries") {
                $input['model'] =  (new Inquiry())->getTable();
            } else if ($request?->model == "customers") {
                $input['model'] =  (new Customer())->getTable();
            }

            // return $request->all();
            $validator =  Validator::make($request->all(), [
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
                'platform' => ['required', 'in:web,android,ios'],
                'model' => ['required', 'string',  'in:inquiries,customers'],
                'model_id' => [
                    'required',
                    function ($attribute, $value, $fail) use ($request) {
                        // Determine the table name based on the model field
                        $table = null;
                        if ($request->model === 'inquiries') {
                            $table = (new Inquiry())->getTable(); // e.g., "inquiries"
                        } elseif ($request->model === 'customers') {
                            $table = (new Customer())->getTable(); // e.g., "customers"
                        }
                        if (!Helper::checkTableExist($request?->model)) {
                            return $fail('The model provided is invalid.');
                        }
                        if (!DB::table($table)->where('id', $value)->exists()) {
                            $fail("The selected model id {$value} is invalid for the given {$request->model}.");
                        }
                    }
                ],
                'meet_person_name' => ['required', 'string'],
                'visit_start_date' => ['required', 'date', 'date_format:Y-m-d H:i:s'],
                'visit_start_remark' => ['required'],
                'visit_start_latitude' => ['required'],
                'visit_start_longitude' => ['required'],
                'visit_start_address' => ['required'],
                'start_image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
                'add_as_followup' => ['required', 'in:yes,no'],
                'update_customer_address' => ['required', 'in:yes,no'],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $loginUserId = $loginUser?->id;
            // return $request->all();

            $input = $request->except(['edit_id']);
            if ($request->hasFile('start_image')) {
                $image_name = Helper::make_slug(date('Ymd-His') . ' visit-start ' . (string)$loginUserId);

                $file = $request->file('start_image');

                $extenstion = $file->getClientOriginalExtension();

                $filename = $image_name . '.' . $extenstion;

                $sub_folder_path = Helper::fileUploadPath($loginUser, Visit::$folderPath);
                $uploadedPath = public_path($sub_folder_path);
                // return $uploadedPath;
                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $sub_folder_path . $filename;
                    if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                        $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $image_name, 80, true);
                        if ($webP) {
                            $uploadedImage = $sub_folder_path . $webP;
                            $extenstion = "webp";
                        }
                    }
                    // $filename = $uploadedImage;
                    // $image_path = $sub_folder_path . '/' . $filename;
                    // $image_path = $uploadedImage;
                    $input['start_image'] = $uploadedImage;
                }
            }

            $returnMessage = "Add Visit successfully.";

            $input['team_person_id'] = $loginUserId;
            $input['created_by'] = $loginUserId;
            // $input['created_type'] = 'Team';

            if ($request?->add_as_followup && $request?->add_as_followup == "yes") {
                $inputFollowup = [];
                $inputFollowup['company_id'] = $company_id;
                if ($request?->model == "inquiries") {
                    $inputFollowup['inquiry_id'] = $request?->model_id;
                } elseif ($request?->model == "customers") {
                    $inputFollowup['customer_id'] = $request?->model_id;
                } else {
                }
                $inputFollowup['through'] = "Visit";
                $inputFollowup['date_time'] = $request?->visit_start_date;
                $inputFollowup['details'] = $request?->visit_start_remark;
                $inputFollowup['platform'] = $request?->platform;
                $inputFollowup['assign_to_id'] = $loginUserId;
                $inputFollowup['created_by'] = $loginUserId;
                $inputFollowup['created_type'] = 'Team';
                $marketingStatusFollowup = MarketingStatus::where('type', "Followup")->where('company_id', $company_id)->orderBy('is_default', 'desc')->first();
                if ($marketingStatusFollowup) {
                    $inputFollowup['status_id'] = $marketingStatusFollowup?->id;
                }

                $followUpReason = FollowUpReason::where('company_id', $company_id)->where('name', 'By Visit')->first();
                if ($followUpReason) {
                    $inputFollowup['reason_id'] = $followUpReason?->id;
                } else {
                    $followUpReason = FollowUpReason::create([
                        'company_id' => $company_id,
                        'name' => 'By Visit',
                    ]);
                    $inputFollowup['reason_id'] = $followUpReason?->id;
                }
                // $marketingStatusInquiry = MarketingStatus::where('type', "Inquiry")->where('company_id', $company_id)->orderBy('is_default', 'desc')->get();
                // if($marketingStatusInquiry){
                //     $inputFollowup['status_id'] = $marketingStatusInquiry?->id;
                // }
                // return $inputFollowup;
                $followUp = Followup::create($inputFollowup);
                $extra_detail = [
                    'add_as_followup' => $request?->add_as_followup,
                    'followup_id' => $followUp?->id,
                ];
                $input['extra_detail'] = json_encode($extra_detail);
            }

            // return $input;
            $modelCreate = Visit::create($input);
            if ($modelCreate?->id && $request?->visit_start_latitude && $request?->visit_start_longitude && $request?->visit_start_address) {
                SaleExecutiveTracking::create([
                    'company_id' => $company_id,
                    'type_reference_id' => $modelCreate?->id,
                    'type' => SaleExecutiveTracking::VISIT_START,
                    'latitude' => $request?->visit_start_latitude,
                    'longitude' => $request?->visit_start_longitude,
                    'address' => $request?->visit_start_address,
                    'current_datetime' => Carbon::now(),
                    'created_by' => $loginUserId,
                ]);

                if ($request?->update_customer_address && $request?->model == "customers") {
                    $customer = Customer::where('id', $request?->model_id)->first();
                    if($customer){
                        $customer->longitude = $request?->visit_start_longitude;
                        $customer->latitude = $request?->visit_start_latitude;
                        $customer->address = $request?->visit_start_address;
                        $customer->save();
                    }
                }
            }



            DB::commit();
            return $this->sendResponse($modelCreate, $returnMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage());
        }
        DB::rollBack();
        return $this->sendError("Something want to wrong in register API");
    }

    /**
     * Visit End
     * company_id is nullable when token is exist
     */
    public function visit_end(Request $request)
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

            $company_id = $request?->company_id ?? $loginUser?->company_id;
            $request['company_id'] = $company_id;

            // return $request->all();
            $validator =  Validator::make($request->all(), [
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
                'visit_id' => [
                    'required',
                    Rule::exists((new Visit())->getTable(), 'id')->where('company_id', $company_id)->whereNull('visit_stop_date')->whereNull('visit_stop_remark')
                ],
                'platform' => ['required', 'in:web,android,ios'],
                'visit_stop_date' => ['required', 'date', 'date_format:Y-m-d H:i:s'],
                'visit_stop_remark' => ['required'],
                'visit_stop_latitude' => ['required'],
                'visit_stop_longitude' => ['required'],
                'visit_stop_address' => ['required'],
                'stop_image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $loginUserId = $loginUser?->id;
            // return $request->all();

            $input = $request->except(['edit_id']);
            if ($request->hasFile('stop_image')) {
                $image_name = Helper::make_slug(date('Ymd-His') . ' visit-stop ' . (string)$loginUserId);

                $file = $request->file('stop_image');

                $extenstion = $file->getClientOriginalExtension();

                $filename = $image_name . '.' . $extenstion;

                $sub_folder_path = Helper::fileUploadPath($loginUser, Visit::$folderPath);
                $uploadedPath = public_path($sub_folder_path);
                // return $uploadedPath;
                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $sub_folder_path . $filename;
                    if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                        $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $image_name, 80, true);
                        if ($webP) {
                            $uploadedImage = $sub_folder_path . $webP;
                            $extenstion = "webp";
                        }
                    }
                    $input['stop_image'] = $uploadedImage;
                }
            }

            $returnMessage = "Stop Visit successfully.";

            $visitData = Visit::where('id', $request?->visit_id)->where('company_id', $company_id)->whereNull('visit_stop_date')->whereNull('visit_stop_remark')->first();

            if(!$visitData){
                return $this?->sendError("Your Previous Visit not found.");
            }
            $input['updated_by'] = $loginUserId;
            $extra_detail = json_decode($visitData?->extra_detail);
            if($extra_detail && $extra_detail?->followup_id){
                $followUp = Followup::where('id', $extra_detail?->followup_id)->first();
                if($followUp){
                    $followUp->update([
                        'response_details' => $request?->visit_stop_remark,
                        'response_date_time' => $request?->visit_stop_date,
                        'updated_by' => $input['updated_by'],
                    ]);
                    $extra_detail->followup_response = 'update';
                }
            }
            // return $visitData;
            // $input['created_type'] = 'Team';

            // return $input;
            $modelCreate = $visitData->update($input);

            if ($visitData?->id && $request?->visit_stop_latitude && $request?->visit_stop_longitude && $request?->visit_stop_address) {
                SaleExecutiveTracking::create([
                    'company_id' => $company_id,
                    'type_reference_id' => $visitData?->id,
                    'type' => SaleExecutiveTracking::VISIT_END,
                    'latitude' => $request?->visit_stop_latitude,
                    'longitude' => $request?->visit_stop_longitude,
                    'address' => $request?->visit_stop_address,
                    'current_datetime' => Carbon::now(),
                    'created_by' => $loginUserId,
                ]);
            }

            DB::commit();
            return $this->sendResponse($visitData, $returnMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage());
        }
        DB::rollBack();
        return $this->sendError("Something want to wrong in register API");
    }


    /**
     * Visit delete
     * company_id is nullable when token is exist
     * delete_id
     */
    public function visit_delete(Request $request)
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
                'delete_id' => ['required', Rule::exists((new Visit())->getTable(), 'id')],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }


            $module = Visit::where('company_id', $company_id);
            $module = $module->where('id', $request?->delete_id);
            $module = $module->orderBy('id', 'DESC');
            $module = $module->first();

            if ($module) {
                if ($module->delete()) {
                    return $this->sendResponse($module, "Your Visit Delete Successfully.");
                }
                return $this->sendError("Your Visit found, but can not deleted.");
            }

            return $this->sendError("Your Visit not found.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }
}
