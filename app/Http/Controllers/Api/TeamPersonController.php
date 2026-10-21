<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ExpenseCategory;
use App\Models\ExpenseSubCategory;
use App\Models\SaleExecutiveTracking;
use App\Models\TeamAttendance;
use App\Models\TeamPerson;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class TeamPersonController extends Controller
{
    public function __construct(Request $request) {}

    /**
     * Team Person List
     * company_id is Required
     * parent_type_id is all, 0
     * filter_by_status is all, active, inactive
     */
    public function team_person_list(Request $request)
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
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 422);
            }

            $data = new TeamPerson();
            $data = $data->with(['company']);
            $data = $data->where('company_id', $company_id);

            if ($request?->filter_by_status) {
                if ($request?->filter_by_status != "all") {
                    $data = $data->where('status', $request?->filter_by_status);
                }
            } else {
                $data = $data->where('status', 'active');
            }

            /** parent_type_id */
            if (isset($request?->parent_type_id)) {
                if ($request?->parent_type_id != "all") {
                    $data = $data->where('parent_type_id', $request?->parent_type_id);
                }
            } else {
                $data = $data->where('parent_type_id', "!=", 0);
            }

            $data = $data->orderBy('name', 'ASC');
            $data = $data->get();

            $data = $data->map(function ($row) {
                // $temp = $row;
                $temp['id'] = $row?->id . "";
                $temp['name'] = $row?->name . "";
                $temp['email'] = $row?->email . "";
                $temp['parent_type_id'] = $row?->parent_type_id . "";
                $temp['email'] = $row?->email . "";
                $temp['mobile_no'] = $row?->mobile_no . "";
                $temp['employee_code'] = $row?->employee_code . "";
                $temp['username'] = $row?->username . "";
                $temp['company_id'] = $row?->company_id . "";
                $temp['company_name'] = $row?->company->company_name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Team Person List.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], [], 402);
        }
        return $this->sendError("Something want to wrong in register API", [], [], 402);
    }

    /**
     * Team Person List
     * company_id is Required
     * parent_type_id is all, 0
     * expense_category_id is Required
     * expense_sub_category_id is Required
     */
    public function team_person_list_by_sub_category(Request $request)
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
                ],
                'expense_category_id' => [
                    'required',
                    Rule::exists((new ExpenseCategory())->getTable(), 'id')
                ],
                'expense_sub_category_id' => [
                    'required',
                    Rule::exists((new ExpenseSubCategory())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 422);
            }

            $expenseSubCategory = ExpenseSubCategory::query();
            $expenseSubCategory = $expenseSubCategory->where('company_id', $company_id);
            $expenseSubCategory = $expenseSubCategory->where('expense_category_id', $request?->expense_category_id);
            $expenseSubCategory = $expenseSubCategory->where('id', $request?->expense_sub_category_id);
            $expenseSubCategory = $expenseSubCategory->orderBy('id', 'DESC');
            // return $expenseSubCategory->toSql();
            $expenseSubCategory = $expenseSubCategory->first();

            $data = [];
            if ($expenseSubCategory && $expenseSubCategory->team_person_ids) {
                $data = new TeamPerson();
                $data = $data->with(['company']);
                $data = $data->where('company_id', $company_id);
                $data = $data->whereIn('id', explode(",", $expenseSubCategory->team_person_ids));

                if ($request?->filter_by_status) {
                    if ($request?->filter_by_status != "all") {
                        $data = $data->where('status', $request?->filter_by_status);
                    }
                } else {
                    $data = $data->where('status', 'active');
                }

                /** parent_type_id */
                /*
                    if (isset($request?->parent_type_id)) {
                    if ($request?->parent_type_id != "all") {
                    $data = $data->where('parent_type_id', $request?->parent_type_id);
                    }
                    } else {
                    $data = $data->where('parent_type_id', "!=", 0);
                    }
                */

                $data = $data->orderBy('name', 'ASC');
                // return $data->toSql();
                $data = $data->get();

                $data = $data->map(function ($row) {
                    // $temp = $row;
                    $temp['id'] = $row?->id . "";
                    $temp['name'] = $row?->name . "";
                    $temp['email'] = $row?->email . "";
                    $temp['parent_type_id'] = $row?->parent_type_id . "";
                    $temp['email'] = $row?->email . "";
                    $temp['mobile_no'] = $row?->mobile_no . "";
                    $temp['employee_code'] = $row?->employee_code . "";
                    $temp['username'] = $row?->username . "";
                    $temp['company_id'] = $row?->company_id . "";
                    $temp['company_name'] = $row?->company->company_name . "";
                    return $temp;
                });
            }
            return $this->sendResponse($data, "Team Person List.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], [], 402);
        }
        return $this->sendError("Something want to wrong in this API", [], [], 402);
    }


    /**
     * Punch In Out
     */
    public function punch_in_out(Request $request)
    {
        try {
            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }

            $teamPerson = TeamPerson::where('id', $loginUser?->id)->first();

            $company_id = $request?->company_id ?? $loginUser?->company_id;
            $today = Carbon::today()->toDateString();

            $validator =  Validator::make($request->all(), [
                'company_id' => [
                    'nullable',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'team_person_id' => [
                    'nullable',
                    Rule::exists((new TeamPerson())->getTable(), 'id')
                ],

                'punch_type' => ['required', 'in:in,out'],
                // 'punch_in_time' => ['required_if:punch_type,in|date|after_or_equal:' . \Carbon\Carbon::today()->toDateString()],
                'punch_in_time' => [
                    'required_if:punch_type,in',
                    'date',
                    'after_or_equal:' . $today,
                    function ($attribute, $value, $fail) use ($teamPerson, $today) {
                        $timeOnly = Carbon::parse($value)->format('H:i');
                        $dateOnly = Carbon::parse($value)->toDateString();


                        if ($dateOnly !== $today) {
                            $fail("Punch time must be for today's date.");
                        }

                        $minWorkingStartTime = $teamPerson?->min_working_start_time; // e.g., '08:00'
                        $maxWorkingSStartTime = $teamPerson?->max_working_start_time; // e.g., '10:00'

                        /*
                        if ($minWorkingStartTime && $timeOnly < $minWorkingStartTime) {
                            $fail("Punch time must not be earlier than $minWorkingStartTime.");
                        }

                        if ($maxWorkingSStartTime && $timeOnly > $maxWorkingSStartTime) {
                            $fail("Punch time must not be later than $maxWorkingSStartTime.");
                        }
                        */

                        // Use Carbon to compare as times
                        $punch = Carbon::createFromFormat('H:i', $timeOnly);
                        $min   = Carbon::createFromFormat('H:i:s', $minWorkingStartTime);
                        $max   = Carbon::createFromFormat('H:i:s', $maxWorkingSStartTime);

                        // dd("L-250", $timeOnly, $dateOnly, $today, $minWorkingStartTime, $maxWorkingSStartTime, $punch, $minWorkingStartTime, $min, $maxWorkingSStartTime, $max);

                        if ($minWorkingStartTime && $maxWorkingSStartTime && ($punch->lt($min) || $punch->gt($max))) {
                            return $fail("Punch time must be between $minWorkingStartTime and $maxWorkingSStartTime.");
                        }
                    },
                ],
                'punch_in_address' => ['required_if:punch_type,in'],
                'punch_in_latitude' => ['required_if:punch_type,in'],
                'punch_in_longitude' => ['required_if:punch_type,in'],

                // 'punch_out_time' => ['required_if:punch_type,out|date|after_or_equal:' . \Carbon\Carbon::today()->toDateString()],
                'punch_out_time' => [
                    'required_if:punch_type,out',
                    'date',
                    // 'after_or_equal:' . $today
                    function ($attribute, $value, $fail) use ($teamPerson, $today) {
                        $timeOnly = Carbon::parse($value)->format('H:i');
                        $dateOnly = Carbon::parse($value)->toDateString();


                        if ($dateOnly !== $today) {
                            $fail("Punch time must be for today's date. " . $today);
                        }

                        $workingEndTimeStr = $teamPerson?->working_end_time; // e.g., "18:00:00"
                        if (!$workingEndTimeStr) {
                            return $fail("Working end time not configured.");
                        }

                        $punchTimeCarbon = Carbon::createFromFormat('H:i', $timeOnly);
                        $workingEndTime = Carbon::createFromFormat('H:i:s', $workingEndTimeStr);

                        if ($punchTimeCarbon->gt($workingEndTime)) {
                            // ".Carbon::createFromFormat('H:i a', $workingEndTimeStr)."
                            return $fail("Punch out time must be before or equal to $workingEndTime.");
                        }
                    }
                ],
                'punch_out_address' => ['required_if:punch_type,out'],
                'punch_out_latitude' => ['required_if:punch_type,out'],
                'punch_out_longitude' => ['required_if:punch_type,out'],

                'image' => ['required:punch_type,out'],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 422);
            }
            // dd("L-267", $validator->fails(), $validator->messages()->first());

            $image_path = null;

            if ($request->hasFile('image')) {
                $image_name = Helper::make_slug('tpa ' . (string)$loginUser?->id) . '-' . $request?->punch_type . '-' . date('Ymd-His');

                $file = $request->file('image');

                $extenstion = $file->getClientOriginalExtension();

                $filename = $image_name . '.' . $extenstion;

                $sub_folder_path = Helper::fileUploadPath($loginUser, TeamAttendance::$folderPath);
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
                    $image_path = $uploadedImage;
                }
            }

            if ($request?->punch_type == 'in') {
                $attendance = TeamAttendance::where('company_id', $company_id);
                $attendance = $attendance->where('team_person_id', $loginUser?->id);
                $attendance = $attendance->whereDate('punch_in_time', \Carbon\Carbon::parse($request?->punch_in_time)->format("Y-m-d"));
                $attendance = $attendance->whereNull('punch_out_time');
                $attendance = $attendance->orderBy('id', 'DESC');
                // return Helper::interpolateQuery($attendance->toSql(), $attendance->getBindings());
                $attendance = $attendance->first();
                if ($attendance) {
                    unlink(public_path($image_path));
                    return $this->sendError("Punch in record found on " . \Carbon\Carbon::parse($request?->punch_out_time)->format("d-m-Y") . " date. First Punch out.", [], [], 422);
                }

                $teamPerson = TeamPerson::where('company_id', $request->company_id)->where('id', $loginUser?->id)->first();
                $minStartTime = $teamPerson?->min_working_start_time;
                $maxStartTime = $teamPerson?->max_working_start_time;
                $now = Carbon::now()->format('H:i:s');
                if ($now > $maxStartTime && $request?->punch_type == 'in') {
                    return $this->sendError("You can't punch in after your shift start time: {$maxStartTime}.", [], [], 422);
                }
                $input = [];
                $input['company_id'] = $company_id;
                $input['team_person_id'] = $loginUser?->id;
                $input['punch_in_time'] =  $request?->punch_in_time;
                $input['punch_in_address'] =  $request?->punch_in_address;
                $input['punch_in_latitude'] =  $request?->punch_in_latitude;
                $input['punch_in_longitude'] =  $request?->punch_in_longitude;
                $input['punch_in_image_path'] = $image_path;
                $input['created_by'] =  $loginUser?->id;
                $input['punch_in_flag'] = 'android';

                $attendance = TeamAttendance::create($input);

                SaleExecutiveTracking::create([
                    'company_id' => $company_id,
                    'type' => SaleExecutiveTracking::ATTENDANCE_IN,
                    'latitude' => $request?->punch_in_latitude,
                    'longitude' => $request?->punch_in_longitude,
                    'address' => $request?->punch_in_address,
                    'current_datetime' => $request?->punch_in_time,
                    'created_by' => $loginUser?->id,
                ]);
            } else if ($request?->punch_type == 'out') {
                $attendance = TeamAttendance::where('company_id', $company_id);
                $attendance = $attendance->where('team_person_id', $loginUser?->id);
                $attendance = $attendance->whereDate('punch_in_time', \Carbon\Carbon::parse($request?->punch_out_time)->format("Y-m-d"));
                $attendance = $attendance->whereNull('punch_out_time');
                $attendance = $attendance->orderBy('id', 'DESC');
                $attendance = $attendance->first();
                if (!$attendance) {
                    unlink(public_path($image_path));
                    return $this->sendError("Punch in record not found on " . \Carbon\Carbon::parse($request?->punch_out_time)->format("d-m-Y") . " date. First Punch In.", [], [], 422);
                }

                if ((int)Helper::convert_date($attendance?->punch_in_time, "Y-m-d H:i:s", "YmdHis") >= (int)Helper::convert_date($request?->punch_out_time, "Y-m-d H:i:s", "YmdHis")) {
                    return $this->sendError("Punch out time after punch in time. Your punch in time is " . $attendance?->punch_in_time, [], [], 422);
                }
                $update = [];
                $update['punch_out_time'] = $request?->punch_out_time;
                $update['punch_out_address'] = $request?->punch_out_address;
                $update['punch_out_latitude'] = $request?->punch_out_latitude;
                $update['punch_out_longitude'] = $request?->punch_out_longitude;
                $update['punch_out_image_path'] = $image_path;
                $update['working_time'] =  Helper::getTimeDifference($attendance->punch_in_time, $request?->punch_out_time);
                $update['updated_by'] =  $loginUser?->id;
                $update['punch_out_flag'] = 'android';
                // dd("Punch Out 310", $update, $attendance->punch_in_time, $request?->punch_out_time, $attendance, (int)Helper::convert_date($attendance?->punch_in_time, "Y-m-d H:i:s", "YmdHis"), (int)Helper::convert_date($request?->punch_out_time, "Y-m-d H:i:s", "YmdHis"));
                // return $update;
                $attendance->update($update);

                SaleExecutiveTracking::create([
                    'company_id' => $company_id,
                    'type' => SaleExecutiveTracking::ATTENDANCE_OUT,
                    'latitude' => $request?->punch_out_latitude,
                    'longitude' => $request?->punch_out_longitude,
                    'address' => $request?->punch_out_address,
                    'current_datetime' => $request?->punch_out_time,
                    'created_by' => $loginUser?->id,
                ]);
            } else {
                return $this->sendError("Punch type missing.", [], [], 422);
            }


            $data = [];

            return $this->sendResponse($data, "Successfully save your attendance.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], [], 422);
        }
        return $this->sendError("Something want to wrong in this API", [], [], 422);
    }

    /**
     * Punch history
     * Filter by from Date
     * Filter by to Date
     */
    public function punch_history(Request $request)
    {
        try {
            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            $company_id = $request?->company_id ?? $loginUser?->company_id;

            $validator =  Validator::make($request->all(), [
                'from_date' => ['nullable', 'date', 'date_format:Y-m-d'],
                'to_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 422);
            }

            $data = [];

            if ($request?->debug && $request?->debug == 365) {
                dd("L-365", $request?->all());
            }

            $grouped = TeamAttendance::select(
                DB::raw('DATE(punch_in_time) as punch_date'),
                DB::raw('SEC_TO_TIME(SUM(TIME_TO_SEC(working_time))) as total_working_time')
            )->where('company_id', $company_id)
                ->where('team_person_id', $loginUser?->id)
                ->whereNotNull('punch_in_time')
                ->whereNotNull('punch_out_time');

            if ($request?->from_date && $request?->to_date) {
                $grouped = $grouped->whereDate('punch_in_time', ">=", $request?->from_date);
                $grouped = $grouped->whereDate('punch_in_time', "<=", $request?->to_date);
            } else if ($request?->from_date) {
                $grouped = $grouped->whereDate('punch_in_time', ">=", $request?->from_date);
            } else if ($request?->to_date) {
                $grouped = $grouped->whereDate('punch_in_time', "<=", $request?->to_date);
            }

            $grouped = $grouped->groupBy(DB::raw('DATE(punch_in_time)'))
                ->orderBy('punch_date', 'DESC');
            // return Helper::interpolateQuery($grouped->toSql(), $grouped->getBindings());
            $grouped = $grouped->get();

            if ($request?->debug && $request?->debug == 389) {
                dd("L-389", $grouped, $request?->all());
            }

            if (count($grouped) > 0) {

                foreach ($grouped as $record) {
                    $returnData = [];
                    // $returnData = $record;
                    $returnData['punch_date'] = $record['punch_date'];
                    $returnData['total_working_time'] = explode('.', $record['total_working_time'])[0];

                    $teamAttendance = TeamAttendance::query();
                    $teamAttendance = $teamAttendance->select(['company_id', 'team_person_id', 'punch_in_time', 'punch_in_image_path', 'punch_in_latitude', 'punch_in_longitude', 'punch_in_address', 'punch_out_time', 'punch_out_image_path', 'punch_out_latitude', 'punch_out_longitude', 'punch_out_address', 'working_time',]);
                    $teamAttendance = $teamAttendance->where('company_id',  $company_id);
                    $teamAttendance = $teamAttendance->where('team_person_id',  $loginUser?->id);
                    $teamAttendance = $teamAttendance->whereNotNull('punch_in_time');
                    $teamAttendance = $teamAttendance->whereNotNull('punch_out_time');
                    $teamAttendance = $teamAttendance->whereDate('punch_in_time', $record?->punch_date);
                    $teamAttendance = $teamAttendance->orderBy('id', 'DESC');
                    $teamAttendance = $teamAttendance->get();

                    /** Add the Punch History */
                    $returnData['punch_history'] = $teamAttendance;
                    // unset($record['punch_in_image_url']);
                    // unset($record['punch_out_image_url']);

                    if ($request?->debug && $request?->debug == 410) {
                        dd("L-410", $grouped, $teamAttendance, $record, $returnData, $request?->all());
                    }

                    $data[] = $returnData;
                }
            }


            if ($request?->debug && $request?->debug == 415) {
                dd("L-415", $grouped->toArray(), $data->toArray(), $request?->all());
            }


            return $this->sendResponse($data, "Attendance History.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], [], 422);
        }
        return $this->sendError("Something want to wrong in this API", [], [], 422);
    }

    /**
     * Active punch in records
     * Filter by today Date
     */
    public function active_punch_in_records(Request $request)
    {
        try {
            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            $company_id = $request?->company_id ?? $loginUser?->company_id;

            $validator =  Validator::make($request->all(), [
                'punch_in_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 422);
            }

            $data = [];
            $teamAttendance = TeamAttendance::query();
            $teamAttendance = $teamAttendance->select(['company_id', 'team_person_id', 'punch_in_time', 'punch_in_image_path', 'punch_in_latitude', 'punch_in_longitude', 'punch_in_address', 'punch_out_time', 'punch_out_image_path', 'punch_out_latitude', 'punch_out_longitude', 'punch_out_address', 'working_time',]);
            $teamAttendance = $teamAttendance->where('company_id',  $company_id);
            $teamAttendance = $teamAttendance->where('team_person_id',  $loginUser?->id);
            $teamAttendance = $teamAttendance->whereNotNull('punch_in_time');
            $teamAttendance = $teamAttendance->whereNull('punch_out_time');
            $teamAttendance = $teamAttendance->whereDate('punch_in_time', $request?->punch_in_date);
            $teamAttendance = $teamAttendance->orderBy('id', 'DESC');
            $teamAttendance = $teamAttendance->first();

            if (!$teamAttendance) {
                $teamAttendance = "";
                return $this->sendResponse($teamAttendance, "No Active Record Found.");
            }
            return $this->sendResponse($teamAttendance, "Get Active Punch Record.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], [], 422);
        }
        return $this->sendError("Something want to wrong in this API", [], [], 422);
    }
}
