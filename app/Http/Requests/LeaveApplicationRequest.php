<?php

namespace App\Http\Requests;

use App\Helpers\Helper;
use App\Models\Company;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\TeamPerson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class LeaveApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(Request $request): array
    {
        $id = $request->route('leave_application') ?? 0; // Adjust 'leave' based on your route parameter name
        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id'),
            ],
            'team_person_ids' => [
                'required',
                Rule::exists((new TeamPerson())->getTable(), 'id'),
            ],
            'leave_type_id' => [
                'required',
                Rule::exists((new LeaveType())->getTable(), 'id')
            ],
            'halfday_fullday' => [
                'required',
                'in:' . implode(",", array_keys(LeaveApplication::$leaveForDay))
            ],
            // 'singleday_multipleday' => [
            //     'nullable',
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
                'date_format:d-m-Y H:i',
                'after_or_equal:' . now()->format('Y-m-d H:i:s')
            ],
            'todate_time' => [
                'nullable',
                // 'required_if:singleday_multipleday,multipleday',
                'date',
                'date_format:d-m-Y H:i',
                'after_or_equal:fromdate_time'
            ],
            'leave_reason' => [
                'required',
                'string',
                'max:1000',
                // Conditionally require the rejection_reason if status is 'reject'
                // Rule::requiredIf(function () use ($request) {
                //     return $request->input('status') === 'reject';
                // })
            ],
            'status' => [
                'required',
                'in:pending,pass,reject'
            ],
            'attachment' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf,doc,docx',
                // 'max:10MB',
            ],
        ];
        // dd("L-88", $request->all(), $rules);
        return $rules;
    }


    public function withValidator($validator)
    {

        $validator->after(function ($validator) {

            $edit_id = $this->route('leave_application') ?? 0; // Adjust 'leave' based on your route parameter name
            $teamPersonId = $this->input('team_person_ids');

            $fromDateTime = $this->input('fromdate_time');
            $toDateTime = $this->input('todate_time');

            // $fromDateTime = \Carbon\Carbon::parse($fromDateTime)->format('Y-m-d');

            $firstOrSecond = $this->input('firsthalf_secondhalf');
            $halfFullDay = $this->input('halfday_fullday');
            $currentLeaveId = $this->route('leave_application') ?? 0;

            if ($teamPersonId && $fromDateTime) {
                // Check overlapping leave applications
                $existingLeave = LeaveApplication::query()
                ->where('status', '!=', 'reject')
                ->where('team_person_ids', $teamPersonId);
                if ($edit_id) {
                    $existingLeave = $existingLeave->where('id', '!=', $edit_id);
                }
                if ($halfFullDay === 'halfday') {
                    $existingLeave = $existingLeave->where('halfday_fullday', $halfFullDay);
                    $existingLeave = $existingLeave->where('firsthalf_secondhalf', $firstOrSecond);
                    $existingLeave = $existingLeave->whereDate('fromdate_time', \Carbon\Carbon::parse($fromDateTime)->toDateString());
                } else if ($halfFullDay === 'fullday') {
                    if ((int)\Carbon\Carbon::parse($fromDateTime)->format("YmdHis") != (int)\Carbon\Carbon::parse($toDateTime)->format("YmdHis")) {

                        // $existingLeave = $existingLeave->whereDate('fromdate_time', '<=', \Carbon\Carbon::parse($fromDateTime)->toDateString())->whereDate('todate_time', '>=', \Carbon\Carbon::parse($fromDateTime)->toDateString());
                        // $existingLeave = $existingLeave->orWhereDate('fromdate_time', '<=', \Carbon\Carbon::parse($toDateTime)->toDateString())->whereDate('todate_time', '>=', \Carbon\Carbon::parse($toDateTime)->toDateString());
                        $existingLeave = $existingLeave->where(function ($query) use ($fromDateTime, $toDateTime) {
                            $query = $query->whereDate('fromdate_time', '<=', \Carbon\Carbon::parse($fromDateTime)->toDateString())->whereDate('todate_time', '>=', \Carbon\Carbon::parse($fromDateTime)->toDateString());
                            $query = $query->orWhereDate('fromdate_time', '<=', \Carbon\Carbon::parse($toDateTime)->toDateString())->whereDate('todate_time', '>=', \Carbon\Carbon::parse($toDateTime)->toDateString());
                        });
                        // dd("l-129", request()->all(), $teamPersonId, $fromDateTime, $toDateTime, $halfFullDay, Helper::interpolateQuery($existingLeave->toSql(), $existingLeave->getBindings()), $existingLeave->first());
                        if ($existingLeave->first()) {
                            $validator->errors()->add('fromdate_time', 'Leave Exist on this date ' . \Carbon\Carbon::parse($fromDateTime)->format("d-m-Y") . ' to ' . \Carbon\Carbon::parse($toDateTime)->format("d-m-Y") . '. Select other date.');
                            return;
                        }
                    } else {
                        $existingLeave = $existingLeave->whereDate('fromdate_time', '<=', \Carbon\Carbon::parse($fromDateTime)->toDateString())->whereDate('todate_time', '>=', \Carbon\Carbon::parse($toDateTime)->toDateString());
                        if ($existingLeave->first()) {
                            $validator->errors()->add('fromdate_time', 'Leave Exist on this date ' . \Carbon\Carbon::parse($fromDateTime)->format("d-m-Y") . '. Select other date.');
                            return;
                        }
                    }
                }
                /*
                // dd("l-143", request()->all(), $teamPersonId, $fromDateTime, $toDateTime, $halfFullDay, Helper::interpolateQuery($existingLeave->toSql(), $existingLeave->getBindings()), $existingLeave->first());
                $existingLeave = $existingLeave->first();
                if ($existingLeave->first()) {
                    // dd("l-146", request()->all(), $teamPersonId, $fromDateTime, $toDateTime, $halfFullDay);
                    return;
                }
                // dd("l-149", request()->all(), $teamPersonId, $fromDateTime, $toDateTime, $halfFullDay, $existingLeave);
                return;
                */
            }
        });
    }
}
