<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Orderunit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApiOrderUnitController extends Controller
{

    public function __construct(Request $request) {}

    /**
     * Order Unit List
     * company_id is nullable when token is exist
     */
    public function order_unit_list(Request $request)
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

            $module = Orderunit::query();
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
            $module = $module->orderBy('id', 'DESC');

            // dd('tax-list 158', Helper::interpolateQuery($module->toSql(), $module->getBindings()), $module->first(), $loginUser->toArray());

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
                    $temp['name'] = $record?->name . "";
                    $temp['status'] = $record?->status . "";
                    $temp['created_at'] = $record?->created_at . "";
                    $temp['updated_at'] = $record?->updated_at . "";

                    return $temp;
                });

                return $this->sendResponse($tempResponseData, "Order Unit list by company.");
            }
            return $this->sendError("Something want to wrong in data fetching.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }


    /**
     * Order Unit Add / Edit
     * company_id is nullable when token is exist
     */
    public function order_unit_add_edit(Request $request)
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
                    Rule::exists((new Orderunit())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        }),
                ],
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
                'platform' => ['required', 'in:web,android,ios'],
                'name' => [
                    'required',
                    Rule::unique((new Orderunit())->getTable())
                        ->ignore($request->edit_id) // ✅ Ignore current record during update
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        }),
                ],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $loginUserId = $loginUser?->id;
            // return $request->all();

            $input = $request->except(['edit_id']);
            $returnMessage = "Add Product Order Unit successfully.";

            if ($request?->edit_id) {
                $input['updated_type'] = 'Team';
                $input['updated_by'] = $loginUserId;
                $returnMessage = "Update Product successfully.";
                $modelAddEdit = Orderunit::where('id', $request?->edit_id);
                $modelAddEdit = $modelAddEdit->where('company_id', $company_id);
                $modelAddEdit = $modelAddEdit->first();
                $modelAddEdit->update($input);
            } else {
                $input['created_by'] = $loginUserId;
                $input['created_type'] = 'Team';
                // return $input;
                $modelAddEdit = Orderunit::create($input);
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
     * Order Unit delete
     * company_id is nullable when token is exist
     * delete_id
     */
    public function order_unit_delete(Request $request)
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
                'delete_id' => ['required', Rule::exists((new Orderunit())->getTable(), 'id')],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }


            $module = Orderunit::where('company_id', $company_id);
            $module = $module->where('id', $request?->delete_id);
            $module = $module->orderBy('id', 'DESC');
            $module = $module->first();

            if ($module) {
                if ($module->delete()) {
                    return $this->sendResponse($module, "Your Order Unit Delete Successfully.");
                }
                return $this->sendError("Your Order Unit found, but can not deleted.");
            }

            return $this->sendError("Your Order Unit not found.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }
}
