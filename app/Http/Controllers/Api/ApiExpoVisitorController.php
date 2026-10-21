<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;

use App\Models\Company;
use App\Models\ExpoCategory;
use App\Models\ExpoManagement;
use App\Models\ExpoVisitor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApiExpoVisitorController extends Controller
{
    public function __construct(Request $request) {}

    /**
     * Expo Visitor List
     * company_id is nullable when token is exist
     */
    public function expo_visitor_list(Request $request)
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

            $module = ExpoVisitor::query();
            // $module = $module->with(['company']);
            $module = $module->where('company_id', $company_id);
            // $module = $module->where('created_by', $loginUserId);

            if ($request?->start_date && !$request?->end_date) {
                $start_date = Carbon::parse($request->start_date)->toDateString();
                $module = $module->whereDate('start_date', '>=', $start_date);
            } else if ($request?->start_date && $request?->end_date) {
                $start_date = Carbon::parse($request->start_date)->toDateString();
                $end_date = Carbon::parse($request->end_date)->toDateString();

                $module = $module->where(function ($sub) use ($start_date, $end_date) {
                    $sub->whereDate('start_date', '>=', $start_date)
                        ->whereDate('end_date', '<=', $end_date);
                });
            }

            if ($request?->filter_by_expo_id) {
                $module = $module->where('expo_id', $request?->filter_by_expo_id);
            }

            if ($request?->search && !empty($request?->search)) {
                $module = $module->where('name', "%" . $request?->search . "%");
            }

            if ($request?->filter_by_status) {
                if (in_array($request?->filter_by_status, ['active', 'inactive'])) {
                    $module = $module->where('status', $request?->filter_by_status);
                }
            }
            $module = $module->orderBy('id', 'DESC');

            // dd('tax-list 81', Helper::interpolateQuery($module->toSql(), $module->getBindings()), $module->first(), $loginUser->toArray());

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

                    $temp['id'] = $record?->id . "" ?? '';

                    $temp['company_id'] = $record?->company_id . "" ?? '';
                    $temp['company_id_name'] = $record?->company?->company_name . "" ?? '';

                    $temp['expo_id'] = $record?->expo_id . "" ?? '';
                    $temp['expo_name'] = $record?->expo?->name . "" ?? '';

                    $temp['company_name'] = $record?->company_name . "" ?? '';
                    $temp['person_name'] = $record?->person_name . "" ?? '';
                    $temp['mobile_no'] = $record?->mobile_no . "" ?? '';
                    $temp['email'] = $record?->email . "" ?? '';
                    $temp['attachment_url'] = $record?->attachment_url . "" ?? '';
                    $temp['website'] = $record?->website . "" ?? '';
                    $temp['requirement'] = $record?->requirement . "" ?? '';
                    $temp['inquiry_id'] = $record?->inquiry_id . "" ?? '';
                    $temp['platform'] = $record?->platform . "" ?? '';
                    $temp['status'] = $record?->status . "" ?? '';
                    $temp['platform'] = $record?->platform . "" ?? '';
                    $temp['created_at'] = $record?->created_at . "" ?? '';
                    $temp['updated_at'] = $record?->updated_at . "" ?? '';

                    return $temp;
                });

                return $this->sendResponse($tempResponseData, "Expo Visitor list by company.");
            }
            return $this->sendError("Something want to wrong in data fetching.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }


    /**
     * Expo Visitor Add / Edit
     * company_id is nullable when token is exist
     */
    public function expo_visitor_add_edit(Request $request)
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
                    Rule::exists((new ExpoVisitor())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        }),
                ],
                'platform' => ['required', 'in:web,android,ios'],
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
                'expo_id' => ['nullable', 'required_without:expo_name', Rule::exists((new ExpoManagement())->getTable(), 'id')->where('status', 'active')],
                'expo_name' => [
                    'nullable',
                    'required_without:expo_id',
                    'string',
                    'max:255'
                ],
                'company_name' => ['required', 'string', 'max:255'],
                'person_name' => [
                    'required',
                    'string',
                    'max:255',
                    // Will validate uniqueness in closure below
                ],
                'mobile_no' => [
                    'required',
                    Rule::unique((new ExpoVisitor())->getTable())
                        ->ignore($request->edit_id) // ✅ Ignore current record during update
                        ->where(function ($query) use ($request) {
                            return $query->where('person_name', $request?->person_name)
                                ->where('company_id', $request?->company_id)
                                ->where('expo_id', $request?->expo_id);
                        }),
                ],
                'email' => ['nullable', 'email', 'max:255'],
                'requirement' => ['nullable'],
                'website' => ['nullable', 'url'],
                'status' => ['required', 'in:active,inactive'],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $loginUserId = $loginUser?->id;
            // return Carbon::now()->subWeek()->startOfDay();
            // return $request->all();

            $input = $request->except(['edit_id']);
            $returnMessage = "Add Expo vistor successfully.";

            $expoId = null;
            if (empty($request?->expo_id) && $request?->expo_name) {

                $expo = ExpoManagement::firstWhere(['name' => $request?->expo_name]);
                if (!$expo) {
                    $expoCAtegory = ExpoCategory::updateOrCreate(['name' => "Other"]);
                    $expoAdd = [
                        'company_id' => $company_id,
                        'expo_category_id' => $expoCAtegory?->id,
                        'expo_category_id' => $company_id,
                        'name' => $request?->expo_name,
                        'start_date' => Helper::convert_date("", "", "Y-m-d"),
                        'end_date' => Carbon::now()->addDays(2)->format("Y-m-d"),
                        'platform' => $input['platform'],
                    ];
                    $expo = ExpoManagement::create($expoAdd);
                }
                $input['expo_id'] = $expo?->id;
            }

            if ($request?->edit_id) {
                $input['updated_type'] = 'Team';
                $input['updated_by'] = $loginUserId;
                $returnMessage = "Update expo vistor successfully.";
                $modelAddEdit = ExpoVisitor::where('id', $request?->edit_id);
                $modelAddEdit = $modelAddEdit->where('company_id', $company_id);
                $modelAddEdit = $modelAddEdit->first();
                $modelAddEdit->update($input);
            } else {
                $input['created_by'] = $loginUserId;
                $input['created_type'] = 'Team';
                $input['status'] = 'active';
                // return $input;
                $modelAddEdit = ExpoVisitor::updateOrCreate([
                    'expo_id' => $input['expo_id'],
                    'mobile_no' => $input['mobile_no'],
                    'mobile_no' => $input['mobile_no'],
                    'company_name' => $input['company_name'],
                    'person_name' => $input['person_name'],
                ], $input);
            }

            return $this->sendResponse($modelAddEdit, $returnMessage);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }


    /**
     * Expo Visitor delete
     * company_id is nullable when token is exist
     * delete_id
     */
    public function expo_visitor_delete(Request $request)
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
                'delete_id' => ['required', Rule::exists((new ExpoVisitor())->getTable(), 'id')],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }


            $module = ExpoVisitor::where('company_id', $company_id);
            $module = $module->where('id', $request?->delete_id);
            $module = $module->orderBy('id', 'DESC');
            $module = $module->first();

            if ($module) {
                if ($module->delete()) {
                    return $this->sendResponse($module, "Your Expo vistor Delete Successfully.");
                }
                return $this->sendError("Your Expo vistor found, but can not deleted.");
            }

            return $this->sendError("Your Expo vistor not found.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }
}
