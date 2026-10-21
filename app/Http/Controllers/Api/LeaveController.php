<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LeaveController extends Controller
{
    public function __construct(Request $request) {}

    /**
     * Leave Type List
     * company_id is nullable when token is exist
     * filter_by_status is all, active, inactive
     */
    public function leave_type_list(Request $request)
    {
        try {

            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            $company_id = $request?->company_id ?? $loginUser?->company_id;

            $validator =  Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $data = new LeaveType();
            $data = $data->with(['company']);
            $data = $data->where('company_id', $company_id);

            if ($request?->filter_by_status) {
                if ($request?->filter_by_status != "all") {
                    $data = $data->where('status', $request?->filter_by_status);
                }
            } else {
                $data = $data->where('status', 'active');
            }

            $data = $data->orderBy('name', 'ASC');
            $data = $data->get();
            // return $data;
            $data = $data->map(function ($row) {
                // $temp = $row;
                $temp['id'] = $row?->id . "";
                $temp['name'] = $row?->name . "";
                $temp['company_id'] = $row?->company_id . "";
                $temp['company_name'] = $row?->company->company_name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Leave Type List.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    /**
     * Leave add / Edit
     * company_id is nullable when token is exist
     */
    public function leave_add_edit(Request $request)
    {
        try {

            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            if ($loginUser?->company_id) {
                $request['company_id'] = $loginUser?->company_id;
            }
            $request['team_person_ids'] = $loginUser?->id ?? '';
            $company_id = $request?->company_id ?? $loginUser?->company_id;

            $validator =  Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'edit_id' => [
                    'nullable',
                    Rule::exists((new LeaveApplication())->getTable(), 'id')
                        ->where(function ($query) use ($company_id) {
                            $query->where('company_id', $company_id);
                        }),
                ],
                'leave_type_id' => [
                    'required',
                    Rule::exists((new LeaveType())->getTable(), 'id'),
                ],
                'halfday_fullday' => [
                    'required',
                    'in:' . implode(",", array_keys(LeaveApplication::$leaveForDay)),
                ],
                // 'singleday_multipleday' => [
                //     'required_if:halfday_fullday,fullday',
                //     'in:' . implode(",", array_keys(LeaveApplication::$leaveByDays)),
                // ],
                'firsthalf_secondhalf' => [
                    'required_if:halfday_fullday,halfday',
                    'nullable',
                    'in:' . implode(",", array_keys(LeaveApplication::$leaveForHalfdays)),
                ],
                'fromdate_time' => [
                    'required',
                    'date',
                    'date_format:Y-m-d H:i:s',
                    'after_or_equal:' . now()->format('Y-m-d H:i:s')
                ],
                'todate_time' => [
                    // 'required_unless:halfday_fullday,halfday',
                    'required_if:singleday_multipleday,multipleday',
                    // 'nullable',
                    'date',
                    'date_format:Y-m-d H:i:s',
                    'after_or_equal:' . $request?->fromdate_time
                ],
                'leave_reason' => [
                    'required',
                    'string',
                    'max:1000',
                ],
                'attachment' => [
                    'nullable',
                    'file',
                    'mimes:jpg,jpeg,png,pdf,doc,docx',
                    'max:2048', // Max 2MB
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $fromdateTime = (int)Helper::convert_date($request?->fromdate_time, "Y-m-d H:m:s", "YmdHms");

            $minStartTime = $loginUser->min_working_start_time ?? '09:00:00';

            $fromDate = \Carbon\Carbon::parse($request?->fromdate_time)->toDateString();
            $fromDate = Carbon::now()->toDateString();

            // Add 4 hours to min_working_start_time
            $minStartWithBuffer = \Carbon\Carbon::parse($fromDate . ' ' . $minStartTime)->addHours(4);
            $minStartTime = \Carbon\Carbon::parse($fromDate . ' ' . $minStartTime);

            $minStartTime = (int)Helper::convert_date($minStartTime, "Y-m-d H:m:s", "YmdHms");
            $minStartWithBufferInt = (int)Helper::convert_date($minStartWithBuffer, "Y-m-d H:m:s", "YmdHms");

            /** First Half Leavel Added Before the Punch In Time */
            /*
            if ($request->firsthalf_secondhalf === 'firsthalf' && $minStartTime > $fromdateTime) {
                dd("L-158", $fromdateTime, $minStartTime, $minStartWithBuffer, $minStartWithBuffer);
            } else if ($request->firsthalf_secondhalf === 'secondhalf') {
                if ($minStartTime > $fromdateTime) {
                    return $this->sendError("Your Secod Half Leave after the ".$loginUser->min_working_start_time);
                }else if($minStartWithBufferInt > $fromdateTime){
                    return $this->sendError("You can not add the leave of current second half");
                    dd("L-165", $request->firsthalf_secondhalf, $fromdateTime, $minStartTime, $minStartWithBufferInt.' < '.$fromdateTime, $loginUser->min_working_start_time);
                }
            }
            dd("L-168", $request->firsthalf_secondhalf, $fromdateTime, $minStartTime, $minStartWithBuffer, $minStartTime . ' < ' . $fromdateTime, $minStartTime < $fromdateTime, $fromdateTime > $minStartWithBufferInt);
            */

            // Custom after validation to prevent duplicate leave on same date/time
            $checkLeaveApplicationExists = LeaveApplication::where('company_id', $company_id)->where('team_person_ids', $loginUser?->id);

            if ($request?->edit_id) {
                $checkLeaveApplicationExists = $checkLeaveApplicationExists->where('id', '!=', $request->edit_id);
            }
            /** */
            $errorMessage = "";
            if ($request?->halfday_fullday == "halfday") {
                $checkLeaveApplicationExists = $checkLeaveApplicationExists->where('halfday_fullday', $request?->halfday_fullday);
                $checkLeaveApplicationExists = $checkLeaveApplicationExists->where('firsthalf_secondhalf', $request?->firsthalf_secondhalf);
                // $checkLeaveApplicationExists = $checkLeaveApplicationExists->whereDate('fromdate_time', $request?->fromdate_time);
                $checkLeaveApplicationExists = $checkLeaveApplicationExists->whereDate('fromdate_time', Carbon::parse($request->fromdate_time)->toDateString());

                /*
                $minStartTime = $loginUser->min_working_start_time ?? '09:00:00';

                // Add 4 hours to min_working_start_time
                $minStartWithBuffer = \Carbon\Carbon::parse($fromDate . ' ' . $minStartTime)->addHours(4);

                if ($request->firsthalf_secondhalf === 'firsthalf') {
                    $checkLeaveApplicationExists = $checkLeaveApplicationExists->whereTime('fromdate_time', '<=', $minStartTime);
                } else if ($request->firsthalf_secondhalf === 'secondhalf') {
                    $checkLeaveApplicationExists = $checkLeaveApplicationExists->whereTime('fromdate_time', '>=', $minStartTime)->whereTime('fromdate_time', '<=', $minStartWithBuffer);
                }
                */
            } else if ($request?->halfday_fullday == "fullday") {
                // $checkLeaveApplicationExists = $checkLeaveApplicationExists->where('halfday_fullday', $request?->halfday_fullday);
                // $checkLeaveApplicationExists = $checkLeaveApplicationExists->whereDate('fromdate_time', ">=", Carbon::parse($request->fromdate_time)->toDateString());
                if ($request?->singleday_multipleday == "multipleday") {
                    /*
                    $checkLeaveApplicationExists = $checkLeaveApplicationExists->where(function ($query) use ($request) {
                        $fromdateTime = Carbon::parse($request->fromdate_time)->toDateString();
                        $query->whereDate('fromdate_time', '<=', $fromdateTime)->whereDate('todate_time', '>=', $fromdateTime);
                    });
                    $checkLeaveApplicationExists = $checkLeaveApplicationExists->orWhere(function ($query) use ($request) {
                        $todateTime = Carbon::parse($request->todate_time)->toDateString();
                        $query->whereDate('fromdate_time', '<=', $todateTime)->whereDate('todate_time', '>=', $todateTime);
                    });
                    */
                    $checkLeaveApplicationExists = $checkLeaveApplicationExists->where(function ($query) use ($request) {
                        $fromdateTime = Carbon::parse($request->fromdate_time)->toDateString();
                        $todateTime = Carbon::parse($request->todate_time)->toDateString();

                        $query->whereBetween('fromdate_time', [$fromdateTime, $todateTime])
                            ->orWhereBetween('todate_time', [$fromdateTime, $todateTime])
                            ->orWhere(function ($q) use ($fromdateTime, $todateTime) {
                                $q->where('fromdate_time', '<=', $fromdateTime)
                                    ->where('todate_time', '>=', $todateTime);
                            });
                    });
                    $errorMessage = "Leave Exist on this date " . Carbon::parse($request->fromdate_time)->format("d-m-Y") . ' to ' . Carbon::parse($request->todate_time)->format("d-m-Y") . '. Select other date.';
                } else {
                    $checkLeaveApplicationExists = $checkLeaveApplicationExists->whereDate('fromdate_time', '<=', Carbon::parse($request->fromdate_time)->toDateString())->whereDate('todate_time', '>=', Carbon::parse($request->fromdate_time)->toDateString());
                    $errorMessage = "Leave Exist on this date " . Carbon::parse($request->fromdate_time)->format("d-m-Y") . '. Select other date.';
                }
            } else {
                $checkLeaveApplicationExists = $checkLeaveApplicationExists->where('id', "0");
            }
            // dd('checkLeaveApplicationExists 228', Helper::interpolateQuery($checkLeaveApplicationExists->toSql(), $checkLeaveApplicationExists->getBindings()), $checkLeaveApplicationExists->first(), $loginUser->toArray());
            $checkLeaveApplicationExists = $checkLeaveApplicationExists->first();

            if ($checkLeaveApplicationExists) {
                if ($request?->halfday_fullday == "halfday") {
                    if ($request->firsthalf_secondhalf === 'firsthalf') {
                        return $this->sendError("All ready add leave on this date(" . Helper::convert_date($checkLeaveApplicationExists?->fromdate_time, "Y-m-d H:m:s", "d-m-Y") . ") and First Half.");
                    } else if ($request->firsthalf_secondhalf === 'secondhalf') {
                        return $this->sendError("All ready add leave on this date(" . Helper::convert_date($checkLeaveApplicationExists?->fromdate_time, "Y-m-d H:m:s", "d-m-Y") . ") and Second Half.");
                    } else {
                        return 208;
                    }
                } else if ($request?->halfday_fullday == "fullday") {
                    return $this->sendError($errorMessage);
                }
            }

            // $checkLeaveApplicationExists = $checkLeaveApplicationExists->get();

            // dd('checkLeaveApplicationExists 211', $checkLeaveApplicationExists);
            // dd('checkLeaveApplicationExists 176', Helper::interpolateQuery($checkLeaveApplicationExists->toSql(), $checkLeaveApplicationExists->getBindings()), $checkLeaveApplicationExists->first(), $loginUser->toArray());
            // return $request->all();
            //dd($request->all());
            $loginUserId = $loginUser?->id;

            $input = $request->except(['edit_id']);
            $returnMessage = "Add Leave by company.";

            $leaveApplicationInputs = $request->all();
            if ($request->hasFile('attachment')) {
                // return $image_name = Helper::make_slug('leave-application ' . (string)$loginUser?->id. ' '. (string)$loginUser?->name . ' ' . date('Ymd-His'));
                $image_name = Helper::make_slug(date('Ymd-His') . ' ' . (string)$loginUser?->id . ' ' . (string)$loginUser?->name);

                $file = $request->file('attachment');

                $extenstion = $file->getClientOriginalExtension();

                $filename = $image_name . '.' . $extenstion;

                $sub_folder_path = Helper::fileUploadPath($loginUser, LeaveApplication::$folderPath);
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
                $leaveApplicationInputs['attachment'] = $uploadedImage;
            }
            // return $leaveApplicationInputs;
            if ($request?->edit_id) {
                $leaveApplicationInputs = [];

                $leaveApplicationInputs['leave_type_id'] = ($request?->leave_type_id) ? $request?->leave_type_id : null;
                $leaveApplicationInputs['halfday_fullday'] = ($request?->halfday_fullday) ? $request?->halfday_fullday : null;
                $leaveApplicationInputs['firsthalf_secondhalf'] = ($request?->firsthalf_secondhalf) ? $request?->firsthalf_secondhalf : null;
                $leaveApplicationInputs['singleday_multipleday'] = ($request?->singleday_multipleday) ? $request?->singleday_multipleday : null;
                $leaveApplicationInputs['leave_reason'] = ($request?->leave_reason) ? $request?->leave_reason : null;
                $leaveApplicationInputs['fromdate_time'] = ($request?->fromdate_time) ? $request?->fromdate_time : null;
                $leaveApplicationInputs['todate_time'] = ($request?->todate_time) ? $request?->todate_time : null;

                $leaveApplicationInputs['updated_type'] = 'Team';
                $leaveApplicationInputs['updated_by'] = $loginUserId;
                // return $leaveApplicationInputs;

                $returnMessage = "Update Leave by company.";
                $LeaveAddUpdate = LeaveApplication::where('id', $request?->edit_id);
                $LeaveAddUpdate = $LeaveAddUpdate->where('company_id', $company_id);
                $LeaveAddUpdate = $LeaveAddUpdate->first();
                if (!$LeaveAddUpdate) {
                    return $this->sendError("Your Leave Application Id not Found. Try Again.");
                }
                $LeaveAddUpdate->update($leaveApplicationInputs);
                $returnResponse = LeaveApplication::where('id', $request?->edit_id)->where('company_id', $company_id)->first();
            } else {
                $leaveApplicationInputs['team_person_ids'] = $loginUser?->id;
                $leaveApplicationInputs['status'] = 'pending';
                $leaveApplicationInputs['created_by'] = $loginUserId;
                $leaveApplicationInputs['created_type'] = 'Team';
                $returnResponse = LeaveApplication::create($leaveApplicationInputs);
            }


            return $this->sendResponse($returnResponse, $returnMessage);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    /**
     * Leave delete
     * company_id is nullable when token is exist
     */
    public function leave_delete(Request $request)
    {
        try {

            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            if ($loginUser?->company_id) {
                $request['company_id'] = $loginUser?->company_id;
            }
            $company_id = $request?->company_id ?? $loginUser?->company_id;

            $validator =  Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'leavel_application_id' => [
                    'required',
                    Rule::exists((new LeaveApplication())->getTable(), 'id'),
                ],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $leavelApplication = LeaveApplication::where('company_id', $company_id)->where('id', $request?->leavel_application_id)->first();

            if (!$leavelApplication) {
                return $this->sendError("Your Application not found contect to admin.");
            }
            $leavelApplication->deleted_by = $loginUser?->id;
            $leavelApplication->deleted_at = Helper::trait_current_date();
            // return $leavelApplication;
            $leavelApplication->save();


            return $this->sendResponse($leavelApplication, "Your Leave Application Delete Successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    /**
     * Leave List
     * company_id is nullable when token is exist
     */
    public function leave_list(Request $request)
    {
        try {

            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            if ($loginUser?->company_id) {
                $request['company_id'] = $loginUser?->company_id;
            }
            $company_id = $request?->company_id ?? $loginUser?->company_id;

            $validator =  Validator::make($request->all(), [
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
                'fromdate_time' => ['nullable', 'date', 'date_format:Y-m-d'],
                'todate_time' => ['nullable', 'date', 'date_format:Y-m-d']
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            /* Pagination */
            $pagination = (isset($request?->page)) ? true : false;
            $perPage = (isset($request?->per_page)) ? $request?->per_page : env("API_PER_PAGE");
            $page = (isset($request?->page)) ? $request?->page : 1;

            $moduleDocuments = LeaveApplication::where('company_id', $company_id)->where('team_person_ids', $loginUser?->id);
            if ($request?->leave_type_id) {
                $moduleDocuments = $moduleDocuments->where('leave_type_id', $request?->leave_type_id);
            }
            if ($request?->fromdate_time && !$request?->todate_time) {
                $fromdateTime = Carbon::parse($request->fromdate_time)->toDateString();
                // $moduleDocuments = $moduleDocuments->whereDate('fromdate_time', "<=", Carbon::parse($request?->fromdate_time))->whereDate('todate_time', ">=", Carbon::parse($request?->fromdate_time));
                // $moduleDocuments = $moduleDocuments->where(function ($query) use ($request) {
                //     $fromdateTime = Carbon::parse($request->fromdate_time)->toDateString();
                //     $query->whereDate('fromdate_time', '<=', $fromdateTime)->whereDate('todate_time', '>=', $fromdateTime);
                // });
                $moduleDocuments = $moduleDocuments->whereDate('fromdate_time', '>=', $fromdateTime)->orWhereNull('todate_time');
            } else if ($request?->fromdate_time && $request?->todate_time) {
                $filter_start = Carbon::parse($request->fromdate_time)->toDateString();
                $filter_end = Carbon::parse($request->todate_time)->toDateString();

                // $moduleDocuments = $moduleDocuments->whereDate('fromdate_time', '>=', $filter_start);
                // $moduleDocuments = $moduleDocuments->whereDate('todate_time', '<=', $filter_end);

                $moduleDocuments = $moduleDocuments->where(function ($sub) use ($filter_start, $filter_end) {
                    $sub->whereDate('fromdate_time', '>=', $filter_start)
                        ->whereDate('fromdate_time', '<=', $filter_end);
                });
                $moduleDocuments = $moduleDocuments->where(function ($sub) use ($filter_start, $filter_end) {
                    $sub->whereDate('todate_time', '>=', $filter_start)
                        ->whereDate('todate_time', '<=', $filter_end)
                        ->orWhereNull('todate_time');
                });
                /*
                $moduleDocuments = $moduleDocuments->where(function ($query) use ($request) {
                    $filter_start = Carbon::parse($request->fromdate_time)->toDateString();
                    $filter_end = Carbon::parse($request->todate_time)->toDateString();
                    $query->where(function ($q) use ($filter_start) {
                        $q->whereDate('fromdate_time', '<=', $filter_start)->where(function ($sub) use ($filter_start) {
                            $sub->whereDate('todate_time', '>=', $filter_start)
                            ->orWhereNull('todate_time');
                        });
                    })
                    ->orWhere(function ($q) use ($filter_end) {
                        $q->whereDate('fromdate_time', '<=', $filter_end)
                        ->whereDate('todate_time', '>=', $filter_end);
                    });
                });
                */
            }
            // dd('leavelApplication 387', Helper::interpolateQuery($moduleDocuments->toSql(), $moduleDocuments->getBindings()), $moduleDocuments->first(), $loginUser->toArray());

            if ($request?->status) {
                $moduleDocuments = $moduleDocuments->where('status', $request?->status);
            }

            $moduleDocuments = $moduleDocuments->orderBy('id', 'DESC');

            $tempResponseData = [];
            if ($pagination) {
                $moduleDocuments = $moduleDocuments->paginate($perPage);

                $tempResponseData['total'] = "" . $moduleDocuments->total();
                $tempResponseData['perPage'] = $perPage;
                $tempResponseData['currentPage'] = $page;
                $tempResponseData['totalPage'] = "" . $moduleDocuments->lastPage();
                $tempResponseData['items'] = (object)$moduleDocuments->items();
                $tempResponseData['items'] = collect($tempResponseData['items']);
            } else {
                $moduleDocuments = $moduleDocuments->get();
                $tempResponseData['total'] = $moduleDocuments->count() . "";
                $tempResponseData['items'] = $moduleDocuments;
            }

            // $leavelApplication = $moduleDocuments->get();

            if (isset($tempResponseData['items'])) {
                $tempResponseData['items'] = $tempResponseData['items']->map(function ($record) {
                    $temp = [];
                    // $temp = $record;
                    $temp['id'] = $record?->id . "";
                    $temp['company_id'] = $record?->company_id . "";
                    $temp['team_person_ids'] = $record?->team_person_ids . "";
                    $temp['leave_type_id'] = $record?->leave_type_id . "";
                    $temp['leave_type_name'] = $record?->leave_type?->name . "";
                    $temp['fromdate_time'] = $record?->fromdate_time . "";
                    $temp['todate_time'] = $record?->todate_time . "";
                    $temp['halfday_fullday'] = $record?->halfday_fullday . "";
                    $temp['singleday_multipleday'] = $record?->singleday_multipleday . "";
                    $temp['firsthalf_secondhalf'] = $record?->firsthalf_secondhalf . "";
                    $temp['leave_reason'] = $record?->leave_reason . "";
                    // $temp['attachment'] = ($record?->attachment && !empty($record?->attachment_url)) ?  : . "";
                    $temp['attachment_url'] = ($record?->attachment && !empty($record?->attachment_url)) ? $record?->attachment_url  :  "";
                    $temp['status'] = $record?->status . "";
                    $temp['reject'] = $record?->reject . "";
                    return $temp;
                });

                return $this->sendResponse($tempResponseData, "Your Leave Application List.");
            }
            return $this->sendError("Something want to wrong in data fetching.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }
}
