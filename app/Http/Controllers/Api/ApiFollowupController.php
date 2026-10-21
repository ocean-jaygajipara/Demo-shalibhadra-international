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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApiFollowupController extends Controller
{
    public function __construct(Request $request) {}

    public function get_followup_status(Request $request)
    {
        $loginUser = Auth::user();
        if (!$loginUser && !$request?->company_id) {
            return $this->sendError("Unauthorization", [], [], 401);
        }
        if ($loginUser?->company_id) {
            $request['company_id'] = $loginUser?->company_id;
        }

        $company_id = $request?->company_id ?? $loginUser?->company_id;
        $request['company_id'] = $company_id;
        try {
            $validator =  Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_id = $request?->company_id;
            $followup_status = MarketingStatus::where('company_id', $company_id)->where('type', 'Followup')->where('status', 'active');
            $data = $followup_status->orderBy('is_default', 'desc')->orderBy('name', 'asc');
            $data = $followup_status->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['followup_status'] = $row?->name . "";
                $temp['status_color'] = $row?->status_color . "";
                $temp['status_color_2'] = $row?->status_color_2 . "";
                $temp['text_color'] = $row?->text_color . "";
                $temp['is_default'] = ($row?->is_default) ? 'yes' : "no";
                return $temp;
            });
            return $this->sendResponse($data, "Followup Status list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_followup_reason(Request $request)
    {
        $loginUser = Auth::user();
        if (!$loginUser && !$request?->company_id) {
            return $this->sendError("Unauthorization", [], [], 401);
        }
        if ($loginUser?->company_id) {
            $request['company_id'] = $loginUser?->company_id;
        }

        $company_id = $request?->company_id ?? $loginUser?->company_id;
        $request['company_id'] = $company_id;
        try {
            $validator =  Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $company_id = $request?->company_id;

            $follow_up_reason = FollowUpReason::where('company_id', $company_id)->where('status', 'active');
            $data = $follow_up_reason->orderBy('name', 'asc')->get();
            $data = $data->map(function ($row) {
                $temp['id'] = $row?->id . "";
                $temp['followup_reason'] = $row?->name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Follow Up Reason list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }


    /**
     * Followup List
     * company_id is nullable when token is exist
     */
    public function followup_list(Request $request)
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
                'followup_type' => [
                    'required',
                    'in:inquiries,customers'
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

            $module = Followup::query();
            // $module = $module->with(['company']);
            $module = $module->where('company_id', $company_id);
            $module = $module->where('created_by', $loginUserId);

            if ($request?->followup_type) {
                if ($request?->followup_type == "inquiries") {
                    $module = $module->whereNotNull('inquiry_id');
                } else if ($request?->followup_type == "customers") {
                    $module = $module->whereNotNull('customer_id');
                } else {
                }
            }

            /** Filter by type or filder date same as panel */
            if($request?->filter_by_followups){
                if($request?->filter_by_followups == "all_followup"){

                }elseif($request?->filter_by_followups == "all_pending_followup"){
                    $module = $module->whereNull('response_details')->whereNull('response_date_time');
                }elseif($request?->filter_by_followups == "today_followup"){
                    $module = $module->whereDate('date_time', '>=', Helper::convert_date("", "", "Y-m-d"));
                }elseif($request?->filter_by_followups == "today_pending_followup"){
                    $module = $module->whereNull('response_details')->whereNull('response_date_time')->whereDate('date_time', '>=', Helper::convert_date("", "", "Y-m-d"));
                }
            }else{
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
            }
            $module = $module->orderBy('id', 'DESC');

            // dd('followup-list 175', Helper::interpolateQuery($module->toSql(), $module->getBindings()), $module->first(), $loginUser->toArray());

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
                    // return $record?->marketingStatus;
                    // $temp = $record;

                    $temp['id'] = $record?->id . "";

                    $temp['inquiry_id'] = $record?->inquiry_id . "";
                    $temp['quotation_id'] = $record?->quotation_id . "";
                    $temp['customer_id'] = $record?->customer_id . "";

                    $temp['followup_type'] = $record?->followup_type;

                    // $temp['inquiry'] = $record?->inquiry;


                    $temp['inquiry_no'] = $record?->inquiry?->inquiry_no . "" ?? "";
                    $temp['client_code'] = $record?->customer?->client_code . "" ?? "";

                    $temp['customer_name'] = "";
                    $temp['contact_person_name'] = "";
                    $temp['mobile_no'] = "";
                    $temp['whatsapp_no'] = "";
                    $temp['requirement_details'] = "";

                    $temp['followup_status_id'] =  $record?->status_id ?? '';
                    $temp['followup_status_name'] = $record?->marketingStatus?->name."" ?? '';
                    $temp['followup_status_color'] = $record?->marketingStatus?->status_color."" ?? '';
                    $temp['followup_status_color_2'] = $record?->marketingStatus?->status_color_2."" ?? '';
                    $temp['followup_text_color'] = $record?->marketingStatus?->text_color."" ?? '';

                    $temp['inquiry_status_id'] = "";
                    $temp['inquiry_status_name'] = "";
                    $temp['inquiry_status_color'] = "";

                    if ($temp['followup_type'] == "inquiries") {
                        $temp['customer_name'] = $record?->inquiry?->customer_name . "";
                        $temp['contact_person_name'] = $record?->inquiry?->contact_person_name . "";
                        $temp['mobile_no'] = $record?->inquiry?->mobile_no . "";
                        $temp['whatsapp_no'] = $record?->inquiry?->whatsapp_no . "";
                        $temp['requirement_details'] = $record?->inquiry?->requirement_details . "";

                        $temp['inquiry_status_id'] = $record?->inquiry?->inquiry_status_id . "";
                        $temp['inquiry_status_name'] = $record?->inquiry?->inquiry_status?->name . "";
                        $temp['inquiry_status_color'] = $record?->inquiry?->inquiry_status?->status_color . "";
                    } else if ($temp['followup_type'] == "customers") {
                        $temp['customer_name'] = $record?->customer?->customer_name . "";
                        $temp['contact_person_name'] = $record?->customer?->contact_person_name . "";
                        $temp['mobile_no'] = $record?->customer?->mobile_number . "";
                        $temp['whatsapp_no'] = $record?->customer?->whatsapp_number . "";
                        $temp['requirement_details'] = $record?->customer?->requirement_details . "";
                        // $temp['customer'] = $customer;
                    }
                    // $temp['inquiry_status_name'] = $record?->inquiry?->inquiry_status . "" ?? "";

                    $temp['date_time'] = $record?->date_time . "";

                    $temp['company_id'] = $record?->company_id . "";
                    $temp['company_name'] = $record?->company?->company_name . "";

                    $temp['response_details'] = $record?->response_details . "";
                    $temp['response_date_time'] = $record?->response_date_time . "";

                    $temp['platform'] = $record?->platform . "";


                    $temp['created_by'] = $record?->created_by . "";
                    $temp['created_by_name'] = $record?->created_by_name?->name . "";
                    $temp['created_at'] = $record?->created_at . "";

                    $temp['updated_by'] = $record?->updated_by . "";
                    $temp['updated_at'] = $record?->updated_at . "";

                    return $temp;
                });

                return $this->sendResponse($tempResponseData, "Follow Up list by company.");
            }
            return $this->sendError("Something want to wrong in data fetching.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    /**
     * Followup Add / Edit
     * company_id is nullable when token is exist
     */
    public function followup_add_edit(Request $request)
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
            $request['company_id'] = $company_id;

            // return $request->all();
            $validator =  Validator::make($request->all(), [
                'edit_id' => [
                    'nullable',
                    Rule::exists((new Followup())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        }),
                ],
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
                'platform' => ['required', 'in:web,android,ios'],
                'inquiry_id' => [
                    'nullable', // allow null
                    'string',
                    Rule::exists((new Inquiry())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        }),
                ],
                'customer_id' => [
                    'nullable', // allow null
                    'string',
                    Rule::exists((new Customer())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        }),
                ],
                'date_time' => ['nullable', 'date', 'date_format:Y-m-d H:i:s'],
                'reason_id' => [
                    'required',
                    Rule::exists((new FollowUpReason())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        }),
                ],
                'through' => ['required', 'in:Call,SMS,Email'],
                'details' => ['required'],
                'status_id' => [
                    'required',
                    Rule::exists((new MarketingStatus())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        }),
                ],
                'inquiry_status_id' => [
                    'nullable',
                    Rule::exists((new MarketingStatus())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        }),
                ],
                'status' => 'required|in:active,inactive',
            ]);

            // Custom XOR logic: Either inquiry_id or customer_id must be present
            $validator->after(function ($validator) use ($request) {
                $inquiryId = $request->inquiry_id;
                $customerId = $request->customer_id;
                $inquiryStatusId = $request->inquiry_status_id;

                if ((empty($inquiryId) && empty($customerId)) || (is_null($inquiryId) && is_null($customerId))) {
                    $validator->errors()->add('inquiry_id', 'Either inquiry_id or customer_id is required.');
                    $validator->errors()->add('customer_id', 'Either inquiry_id or customer_id is required.');
                }

                if (!empty($inquiryId) && empty($inquiryStatusId)) {
                    $validator->errors()->add('inquiry_status_id', 'The inquiry_status_id is required when inquiry_id is present.');
                }
            });

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $loginUserId = $loginUser?->id;

            $input = $request->except(['edit_id']);
            $returnMessage = "Add Follow successfully.";

            if ($request?->edit_id) {
                $input['updated_type'] = 'Team';
                $input['updated_by'] = $loginUserId;
                $returnMessage = "Update Follow successfully.";
                $modelAddEdit = Followup::where('id', $request?->edit_id);
                $modelAddEdit = $modelAddEdit->where('company_id', $company_id);
                $modelAddEdit = $modelAddEdit->first();
                $modelAddEdit->update($input);
            } else {
                $input['created_by'] = $loginUserId;
                $input['created_type'] = 'Team';
                // return $input;
                $modelAddEdit = Followup::create($input);
            }

            // $modelAddEdit = Customer::updateOrCreate(['id' => $edit_id], $input);

            // dd("L-260", $edit_id, $request->all(), $input, $modelAddEdit);

            return $this->sendResponse($modelAddEdit, $returnMessage);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    /**
     * Followup Response Add
     * company_id is nullable when token is exist
     */
    public function followup_response_add(Request $request)
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
            $request['company_id'] = $company_id;

            // return $request->all();
            $validator =  Validator::make($request->all(), [
                'edit_id' => [
                    'required',
                    Rule::exists((new Followup())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        }),
                ],
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
                'platform' => ['required', 'in:web,android,ios'],

                'response_date_time' => ['required', 'date', 'date_format:Y-m-d H:i:s'],
                'response_details' => ['required'],
                'followup_action' => ['required', 'in:Next Follow-up,End Follow-up'],
                'status_id' => [
                    'nullable',
                    Rule::exists((new MarketingStatus())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        }),
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $loginUserId = $loginUser?->id;

            $input = $request->except(['edit_id']);
            $returnMessage = "Add Follow response successfully.";

            if ($request?->edit_id) {
                $input['updated_type'] = 'Team';
                $input['updated_by'] = $loginUserId;
                $returnMessage = "Update Follow successfully.";
                $modelAddEdit = Followup::where('id', $request?->edit_id);
                $modelAddEdit = $modelAddEdit->where('company_id', $company_id);
                // $modelAddEdit = $modelAddEdit->where('assign_to_id', $loginUserId);
                $modelAddEdit = $modelAddEdit->whereNull('response_details');
                $modelAddEdit = $modelAddEdit->first();
                if(!$modelAddEdit){
                    return $this->sendError("Follow up response already added.");
                }
                $modelAddEdit->update($input);
            } else {
                $modelAddEdit = null;
            }

            // dd("L-481", $request->all(), $input, $modelAddEdit);

            return $this->sendResponse($modelAddEdit, $returnMessage);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }
}
