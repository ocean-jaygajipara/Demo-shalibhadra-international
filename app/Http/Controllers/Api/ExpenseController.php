<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseSubCategory;
use App\Models\TeamPerson;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    public function __construct(Request $request) {}

    /**
     * Expense Category List
     * company_id is Required
     */
    public function expense_category_list(Request $request)
    {
        try {

            $validator =  Validator::make($request->all(), [
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $data = new ExpenseCategory();
            $data = $data->with(['company']);
            $data = $data->where('company_id', $request?->company_id);

            if ($request?->filter_by_status) {
                if ($request?->filter_by_status != "all") {
                    $data = $data->where('status', $request?->filter_by_status);
                }
            } else {
                $data = $data->where('status', 'active');
            }

            $data = $data->orderBy('name', 'ASC');
            $data = $data->get();

            $data = $data->map(function ($row) {
                // $temp = $row;
                $temp['id'] = $row?->id . "";
                $temp['name'] = $row?->name . "";
                $temp['company_id'] = $row?->company_id . "";
                $temp['company_name'] = $row?->company->company_name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Expense Category List.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }


    /**
     * Expense Sub Category List
     * company_id is Required
     * expense_category_id is Required
     * team_person_id when fetch the sub category belongs to team person
     * */
    public function expense_sub_category_list(Request $request)
    {
        // return $request->all();
        try {
            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            $company_id = $request?->company_id ?? $loginUser?->company_id;
            $request['company_id'] = $company_id;

            if ($loginUser && !$request?->team_person_id) {
                $request['team_person_id'] = $loginUser?->id;
            }

            $validator =  Validator::make($request->all(), [
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
                'expense_category_id' => ['required', Rule::exists((new ExpenseCategory())->getTable(), 'id')]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $data = new ExpenseSubCategory();
            $data = $data->with(['company']);
            $data = $data->where('company_id', $request?->company_id);
            $data = $data->where('expense_category_id', $request?->expense_category_id);


            /* Belong to Team Person */

            if ($request?->team_person_id) {
                // $data->whereRaw('team_person_ids', $request?->team_person_ids);
                $data = $data->whereRaw("find_in_set('" . $request?->team_person_id . "',team_person_ids)");
                // dd($data->toSql());
            }

            if ($request?->filter_by_status) {
                if ($request?->filter_by_status != "all") {
                    $data = $data->where('status', $request?->filter_by_status);
                }
            } else {
                $data = $data->where('status', 'active');
            }

            $data = $data->orderBy('name', 'ASC');
            $data = $data->get();

            $data = $data->map(function ($row) use ($request) {
                // $temp = $row;
                $temp['id'] = $row?->id . "";
                $temp['name'] = $row?->name . "";
                $temp['company_id'] = $row?->company_id . "";
                $temp['company_name'] = $row?->company->company_name . "";
                if ($request?->team_person_id) {
                    $temp['team_person_ids'] = $row?->team_person_ids . "";
                    $temp['expense_category_id'] = $row?->expense_category_id . "";
                    $temp['expense_type'] = $row?->expense_type . "";
                    $temp['is_image_required'] = $row?->is_image_required . "";
                    $temp['min_amount'] = $row?->min_amount . "";
                    $temp['max_amount'] = $row?->max_amount . "";
                    $temp['per_km_rate'] = $row?->per_km_rate . "";
                    $temp['fix_amount'] = $row?->fix_amount . "";
                    $temp['from_time'] = $row?->from_time . "";
                    $temp['to_time'] = $row?->to_time . "";
                    $temp['status'] = $row?->status . "";
                }
                return $temp;
            });
            return $this->sendResponse($data, "Expense Category List.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    /**
     * Expense add / edit
     * company_id is Required
     * expense_category_id is Required
     * team_person_id when fetch the sub category belongs to team person
     * */
    public function expense_add_edit(Request $request)
    {
        try {

            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            $company_id = $request?->company_id ?? $loginUser?->company_id;
            $request['company_id'] = $company_id;

            $loginUserId = $loginUser?->id;


            $rules =  [
                'edit_id' => [
                    'nullable',
                    Rule::exists((new Expense())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        })->where(function ($query) use ($loginUserId) {
                            return $query->where('created_by', $loginUserId);
                        }),
                ],
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
                'expense_category_id' => ['required', Rule::exists((new ExpenseCategory())->getTable(), 'id')],
                'expense_subcategory_id' => [
                    'required',
                    Rule::exists((new ExpenseSubCategory())->getTable(), 'id')->where(function ($query) use ($request) {
                        return $query->where('expense_category_id', $request->expense_category_id);
                    })
                ],
                'date' => ['required', 'date', 'date_format:Y-m-d'],
                'req_amount' => ['required', 'numeric', 'min:0'],
                'remark' => ['nullable'],
            ];
            // dd($rules);

            $subcategory = ExpenseSubCategory::find($request?->expense_subcategory_id);
            if ($subcategory && $subcategory->is_image_required == 1) {
                $rules['attachment'] = [
                    'required',
                    'file',
                    'mimes:jpg,jpeg,png,pdf,doc,docx',
                    'max:2048', // Max 2MB
                ];
            } else {
                $rules['attachment'] = [
                    'nullable',
                    'file',
                    'mimes:jpg,jpeg,png,pdf,doc,docx',
                    'max:2048', // Max 2MB
                ];
            }

            $validator =  Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }


            $input = $request->all();
            $input['status'] = 'pending';
            $input['team_person_id'] = $loginUser?->id;
            $input['created_by'] = $loginUser?->id;
            if ($request->hasFile('attachment')) {
                // return $image_name = Helper::make_slug('leave-application ' . (string)$loginUser?->id. ' '. (string)$loginUser?->name . ' ' . date('Ymd-His'));
                $image_name = Helper::make_slug(date('Ymd-His') . ' ' . (string)$loginUser?->id . ' ' . (string)$loginUser?->name);

                $file = $request->file('attachment');

                $extenstion = $file->getClientOriginalExtension();

                $filename = $image_name . '.' . $extenstion;

                $sub_folder_path = Helper::fileUploadPath($loginUser, Expense::$folderPath);
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
            // return $input;
            $data = [];
            if($request?->edit_id){
                $data = Expense::where('id', $request?->edit_id)->first();
                if(!$data){
                    return $this->sendError("Expense not found");
                }
                else if($data?->status && $data?->status != 'pending'){
                    return $this->sendError("Your expense is ".$data?->status.", so you can't update");
                }

                $data?->update($input);
                $data = Expense::where('id', $request?->edit_id)->first();

                return $this->sendResponse($data, "Expense update.");
            }else{
                $data = Expense::create($input);
            }

            return $this->sendResponse($data, "Expense add.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }


    /**
     * Expense List
     * company_id is nullable when token is exist
     ******************* Filter
     * expense_category_id
     * expense_subcategory_id
     */
    public function expense_list(Request $request)
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
                'fromdate' => ['nullable', 'date', 'date_format:Y-m-d'],
                'todate' => ['nullable', 'date', 'date_format:Y-m-d']
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            /* Pagination */
            $pagination = (isset($request?->page)) ? true : false;
            $perPage = (isset($request?->per_page)) ? $request?->per_page : env("API_PER_PAGE");
            $page = (isset($request?->page)) ? $request?->page : 1;

            $moduleDocuments = Expense::where('company_id', $company_id)->where('team_person_id', $loginUser?->id);

            if ($request?->expense_category_id) {
                $moduleDocuments = $moduleDocuments->where('expense_category_id', $request?->expense_category_id);
            }
            if ($request?->expense_subcategory_id) {
                $moduleDocuments = $moduleDocuments->where('expense_subcategory_id', $request?->expense_subcategory_id);
            }

            if ($request?->from_date && !$request?->to_date) {
                // dd('Expense 280', $request?->from_date, $request?->to_date);
                // $moduleDocuments = $moduleDocuments->whereDate('from_date', "<=", Carbon::parse($request?->from_date))->whereDate('to_date', ">=", Carbon::parse($request?->from_date));

                $moduleDocuments = $moduleDocuments->where(function ($query) use ($request) {
                    $fromdateTime = Carbon::parse($request->from_date)->toDateString();
                    $query->whereDate('date', '==', $fromdateTime);
                });
            } else if ($request?->from_date && $request?->to_date) {

                $filter_start = Carbon::parse($request->from_date)->toDateString();
                $filter_end = Carbon::parse($request->to_date)->toDateString();
                $moduleDocuments = $moduleDocuments->whereDate('date', '>=', $filter_start)->whereDate('date', '<=', $filter_end);

                // dd('Expense 287', $request?->from_date, $request?->to_date, Helper::interpolateQuery($moduleDocuments->toSql(), $moduleDocuments->getBindings()));
            }
            // dd('Expense 307', Helper::interpolateQuery($moduleDocuments->toSql(), $moduleDocuments->getBindings()), $moduleDocuments->first(), $loginUser->toArray());
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
                $tempResponseData['total'] = $moduleDocuments->count();
                $tempResponseData['items'] = $moduleDocuments;
            }

            if (isset($tempResponseData['items'])) {
                $tempResponseData['items'] = $tempResponseData['items']->map(function ($record) {
                    $temp = [];
                    // $temp = $record;
                    $temp['id'] = $record?->id . "";
                    $temp['company_id'] = $record?->company_id . "";
                    $temp['company_name'] = $record?->company?->company_name . "";

                    $temp['team_person_id'] = $record?->team_person_id . "";
                    $temp['team_person_name'] = $record?->team_person->name . "";

                    $temp['expense_category_id'] = $record?->expense_category_id . "";
                    $temp['expense_category_name'] = $record?->expense_category?->name . "";

                    $temp['expense_subcategory_id'] = $record?->expense_subcategory_id . "";
                    $temp['expense_subcategory_name'] = $record?->expense_sub_category?->name . "";

                    $temp['date'] = $record?->date . "";
                    $temp['req_amount'] = $record?->req_amount . "";
                    $temp['pass_amount'] = $record?->pass_amount . "";
                    $temp['remark'] = $record?->remark . "";
                    $temp['reason'] = $record?->reason . "";
                    $temp['attachment'] = $record?->attachment . "";
                    $temp['attachment_url'] = $record?->attachment_url . "";
                    $temp['status'] = $record?->status . "";
                    $temp['created_by'] = $record?->created_by . "";
                    return $temp;
                });

                return $this->sendResponse($tempResponseData, "Your Expense List.");
            }
            return $this->sendError("Something want to wrong in data fetching.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    /**
     * Expense delete
     * company_id is nullable when token is exist
     * expense_id
     */
    public function expense_delete(Request $request)
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
                'expense_id' => ['required', Rule::exists((new Expense())->getTable(), 'id')],
                'login_id' => ['required', Rule::exists((new TeamPerson())->getTable(), 'id')],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }


            $moduleDocuments = Expense::where('company_id', $company_id)->where('team_person_id', $loginUser?->id);
            $moduleDocuments = $moduleDocuments->where('id', $request?->expense_id);
            $moduleDocuments = $moduleDocuments->orderBy('id', 'DESC');
            $moduleDocuments = $moduleDocuments->first();

            if ($moduleDocuments) {
                if ($moduleDocuments?->status != "pending") {
                    return $this->sendError("You can not delete this Expense. reason is your Expense current status is " . ucfirst($moduleDocuments->status));
                }
                if ($moduleDocuments->delete()) {
                    return $this->sendResponse($moduleDocuments, "Your Expense Delete Successfully.");
                }
                return $this->sendError("Your Expense found, but can not deleted.");
            }

            return $this->sendError("Your Expense not found.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }


    /**
     * Expense chart
     * company_id is Required
     */
    public function expense_chart(Request $request)
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
                'fromdate' => ['nullable', 'date', 'date_format:Y-m-d'],
                'todate' => ['nullable', 'date', 'date_format:Y-m-d']
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }


            $moduleDocuments = Expense::where('company_id', $company_id)->where('team_person_id', $loginUser?->id);

            if ($request?->expense_category_id) {
                $moduleDocuments = $moduleDocuments->where('expense_category_id', $request?->expense_category_id);
            }
            if ($request?->expense_subcategory_id) {
                $moduleDocuments = $moduleDocuments->where('expense_subcategory_id', $request?->expense_subcategory_id);
            }

            /*
            if ($request?->from_date && !$request?->to_date) {
                // dd('Expense 280', $request?->from_date, $request?->to_date);
                // $moduleDocuments = $moduleDocuments->whereDate('from_date', "<=", Carbon::parse($request?->from_date))->whereDate('to_date', ">=", Carbon::parse($request?->from_date));

                $moduleDocuments = $moduleDocuments->where(function ($query) use ($request) {
                    $fromdateTime = Carbon::parse($request->from_date)->toDateString();
                    $query->whereDate('date', '==', $fromdateTime);
                });
            } else if ($request?->from_date && $request?->to_date) {

                $filter_start = Carbon::parse($request->from_date)->toDateString();
                $filter_end = Carbon::parse($request->to_date)->toDateString();
                $moduleDocuments = $moduleDocuments->whereDate('date', '>=', $filter_start)->whereDate('date', '<=', $filter_end);

                // dd('Expense 287', $request?->from_date, $request?->to_date, Helper::interpolateQuery($moduleDocuments->toSql(), $moduleDocuments->getBindings()));
            }
                */
            // dd('Expense 307', Helper::interpolateQuery($moduleDocuments->toSql(), $moduleDocuments->getBindings()), $moduleDocuments->first(), $loginUser->toArray());
            $moduleDocuments = $moduleDocuments->orderBy('id', 'DESC');

            $tempResponseData = [];
            $moduleDocuments = $moduleDocuments->get();


            // $tempResponseData['total'] = $moduleDocuments->count()."";
            // $tempResponseData['items'] = $moduleDocuments;

            $now = Carbon::now();
            $fromDate = ($request->from_date) ? Carbon::parse($request->from_date) : $now;
            $toDate = ($request->to_date) ? Carbon::parse($request->to_date) : $now;

            $currentMonthStart = $fromDate->copy()->startOfMonth();
            $currentMonthEnd = $toDate->copy()->endOfMonth();
            $previousMonthStart = $fromDate->copy()->subMonth()->startOfMonth();
            $previousMonthEnd = $toDate->copy()->subMonth()->endOfMonth();


            $currentMonthExpence = $moduleDocuments->whereBetween('date', [$currentMonthStart, $currentMonthEnd]);
            $previousMonthExpence = $moduleDocuments->whereBetween('date', [$previousMonthStart, $previousMonthEnd]);

            // dd($now, $currentMonthStart, $currentMonthEnd, $previousMonthStart, $previousMonthEnd);

            $tempResponseData['current_month_total_expence'] = $currentMonthExpence->sum('req_amount') . "";
            $tempResponseData['previous_month_total_expence'] = $previousMonthExpence->sum('req_amount') . "";

            $tempResponseData['current_month_total_expence_passed'] = $currentMonthExpence->where('status', 'pass')->sum('pass_amount') . "";
            $tempResponseData['previous_month_total_expence_passed'] = $previousMonthExpence->where('status', 'pass')->sum('pass_amount') . "";

            $tempResponseData['current_month_total_expence_pending'] = $currentMonthExpence->where('status', 'pending')->sum('req_amount') . "";
            $tempResponseData['previous_month_total_expence_pending'] = $previousMonthExpence->where('status', 'pending')->sum('req_amount') . "";

            $tempResponseData['current_month_passed_percent'] = self::percent($tempResponseData['current_month_total_expence_passed'], $tempResponseData['current_month_total_expence']) . "";
            $tempResponseData['current_month_pending_percent'] = self::percent($tempResponseData['current_month_total_expence_pending'], $tempResponseData['current_month_total_expence']) . "";

            $tempResponseData['previous_month_passed_percent'] = self::percent($tempResponseData['previous_month_total_expence_passed'], $tempResponseData['previous_month_total_expence']) . "";
            $tempResponseData['previous_month_pending_percent'] = self::percent($tempResponseData['previous_month_total_expence_pending'], $tempResponseData['previous_month_total_expence']) . "";



            return $this->sendResponse($tempResponseData, "Your Expense Chart.");
            return $this->sendError("Something want to wrong in data fetching.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    function percent($part, $total)
    {
        return $total > 0 ? round(($part / $total) * 100, 2) : 0;
    }
}
