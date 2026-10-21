<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\CommonController;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Inquiry;
use App\Models\InquiryLogs;
use App\Models\MasterArea;
use App\Models\MasterCity;
use App\Models\MasterCountry;
use App\Models\MasterState;
use App\Models\PriceList;
use App\Models\SaleExecutiveTracking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function __construct(Request $request) {}

    /**
     * Customer Type List
     * company_id is nullable when token is exist
     * filter_by_status is all, active, inactive
     */
    public function customer_type_list(Request $request)
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

            $data = new CustomerType();
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
                $temp['parent_id'] = $row?->parent_id . "";
                $temp['status'] = $row?->status . "";
                $temp['company_id'] = $row?->company_id . "";
                $temp['company_name'] = $row?->company->company_name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Cusotmer Type list by company.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    /**
     * Customer Type List
     * company_id is nullable when token is exist
     * filter_by_status is all, active, inactive
     */
    public function customer_list(Request $request)
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

            /* Pagination */
            $pagination = (isset($request?->page)) ? true : false;
            $perPage = (isset($request?->per_page)) ? $request?->per_page : env("API_PER_PAGE");
            $page = (isset($request?->page)) ? $request?->page : 1;

            $module = Customer::query();
            // $module = $module->with(['company']);
            $module = $module->where('company_id', $company_id);

            if ($request?->filter_by_status) {
                if ($request?->filter_by_status != "all") {
                    $module = $module->where('status', $request?->filter_by_status);
                }
            } else {
                $module = $module->where('status', 'active');
            }

            if ($request?->customer_type_id) {
                $module = $module->where('customer_type_id', $request?->customer_type_id);
            }
            if ($request?->customer_parent_id) {
                $module = $module->where('customer_type_id', $request?->customer_parent_id);
            }

            if ($request?->country_id) {
                $module = $module->where('country_id', $request?->country_id);
            }

            if ($request?->state_id) {
                $module = $module->where('state_id', $request?->state_id);
            }

            if ($request?->city_id) {
                $module = $module->where('city_id', $request?->city_id);
            }

            if ($request?->area_id) {
                $module = $module->where('area_id', $request?->area_id);
            }

            $module = $module->orderBy('id', 'DESC');
            // return $module?->get();

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

                    $temp['client_code'] = $record?->client_code . "";

                    $temp['company_id'] = $record?->company_id . "";
                    $temp['company_name'] = $record?->company?->company_name . "";

                    $temp['customer_name'] = $record?->customer_name . "";
                    $temp['contact_person_name'] = $record?->contact_person_name . "";

                    $temp['parent_id'] = $record?->parent_id . "";

                    $temp['customer_type_id'] = $record?->customer_type_id . "";
                    $temp['customer_type_name'] = $record?->customerType?->name . "";

                    $temp['mobile_number'] = $record?->mobile_number . "";
                    $temp['whatsapp_number'] = $record?->whatsapp_number . "";


                    $temp['price_list_id'] = $record?->price_list_id . "";
                    $temp['gst_number'] = $record?->gst_number . "";

                    $temp['country_id'] = $record?->country_id . "";
                    $temp['country_name'] = $record?->country?->name . "";

                    $temp['state_id'] = $record?->state_id . "";
                    $temp['state_name'] = $record?->state?->name . "";

                    $temp['city_id'] = $record?->city_id . "";
                    $temp['city_name'] = $record?->city?->name . "";

                    $temp['area_id'] = $record?->area_id . "";
                    $temp['area_name'] = $record?->area?->area_name . "";

                    $temp['address'] = $record?->address . "";
                    $temp['pincode'] = $record?->pincode . "";

                    $temp['longitude'] = $record?->longitude . "";
                    $temp['latitude'] = $record?->latitude . "";

                    $temp['shipping_address'] = $record?->shipping_address . "";
                    $temp['billing_address'] = $record?->billing_address . "";
                    $temp['address'] = $record?->address . "";

                    $temp['email'] = $record?->email . "";
                    $temp['birth_date'] = $record?->birth_date . "";

                    $temp['status'] = $record?->status . "";

                    $temp['platform'] = $record?->platform . "";


                    $temp['created_by'] = $record?->created_by . "";
                    $temp['created_at'] = $record?->created_at . "";

                    $temp['updated_by'] = $record?->updated_by . "";
                    $temp['updated_at'] = $record?->updated_at . "";

                    return $temp;
                });

                return $this->sendResponse($tempResponseData, "Cusotmer list by company.");
            }
            return $this->sendError("Something want to wrong in data fetching.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    /**
     * Customer Add / Edit
     * company_id is nullable when token is exist
     */
    public function customer_add_edit(Request $request)
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
                'platform' => ['required', 'in:web,android,ios'],
                'edit_id' => [
                    'nullable',
                    Rule::exists((new Customer())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        }),
                ],
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],

                'is_partially' => ['required', 'in:yes,no'],
                'customer_name' => ['required', 'string'],
                'customer_type_id' => [
                    'required',
                    Rule::exists((new CustomerType())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        }),
                ],
                'contact_person_name' => ['required', 'string'],
                'mobile_number' => [
                    'required',
                    'numeric',
                    function ($attribute, $value, $fail) use ($request) {
                        $companyId = $request?->company_id;
                        $company = Company::find($companyId);

                        if (!$company) {
                            return $fail("Please select a company.");
                        }


                        $length = strlen((string) $value);

                        if ($company->mobile_min && $length < $company->mobile_min) {
                            return $fail("The mobile number must be at least {$company->mobile_min} digits.");
                        }

                        if ($company->mobile_max && $length > $company->mobile_max) {
                            return $fail("The mobile number must not exceed {$company->mobile_max} digits.");
                        }
                        // dd("L-207", $length, $request->all(), $request?->edit_id, Rule::unique((new Customer())->getTable(), 'mobile_number')->where(function ($query) use ($request) {
                        //     return $query->where('company_id', $request?->company_id)
                        //         ->whereNull('deleted_at');
                        // })->ignore($request?->edit_id));
                    },
                    Rule::unique((new Customer())->getTable(), 'mobile_number')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id)
                                ->whereNull('deleted_at');
                        })
                        ->ignore($request?->edit_id), // ignores current record while editing
                ],
                'whatsapp_number' => ['nullable', 'digits:10', 'numeric'],
                'inquiry_id' => [
                    'nullable',
                    Rule::exists((new Inquiry())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        })
                ],
                'country_id' => ['required_if:is_partially,no', Rule::exists((new MasterCountry())->getTable(), 'id')],
                'state_id' => ['required_if:is_partially,no', Rule::exists((new MasterState())->getTable(), 'id')],
                'city_id' => ['required_if:is_partially,no', Rule::exists((new MasterCity())->getTable(), 'id')],
                'area_id' => [
                    'required_if:is_partially,no',
                    Rule::exists((new MasterArea())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        }),
                ],
                'pincode' => ['nullable'],
                'address' => ['required_if:is_partially,no'],
                'price_list_id' => [
                    'nullable',
                    Rule::exists((new PriceList())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        })
                ],
                'longitude' => ['nullable'],
                'latitude' => ['nullable'],
                'shipping_address' => ['nullable'],
                'billing_address' => ['nullable'],
                'email' => ['nullable', 'email'],
                'birth_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            ], [
                'custom' => [
                    'mobile_number.unique' => 'This mobile number already exists for the selected company.',
                ],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $loginUserId = $loginUser?->id;

            $input = $request->except(['edit_id']);
            $returnMessage = "Add customer by company.";
            // return $input;

            if ($request?->edit_id) {
                $input['updated_type'] = 'Team';
                $input['updated_by'] = $loginUserId;
                $returnMessage = "Update customer by company.";
                $CustomerAddUpdate = Customer::where('id', $request?->edit_id);
                $CustomerAddUpdate = $CustomerAddUpdate->where('company_id', $company_id);
                $CustomerAddUpdate = $CustomerAddUpdate->first();
                $CustomerAddUpdate->update($input);

                if ($request?->tracking_latitude && $request?->tracking_longitude && $request?->tracking_address && $request?->edit_id) {
                    /*
                    SaleExecutiveTracking::create([
                        'company_id' => $company_id,
                        'type_reference_id' => $request?->edit_id,
                        'type' => SaleExecutiveTracking::CUSTOMER_EDIT,
                        'latitude' => $request?->tracking_latitude,
                        'longitude' => $request?->tracking_longitude,
                        'address' => $request?->tracking_address,
                        'current_datetime' => Carbon::now(),
                        'created_by' => $loginUserId,
                    ]);
                    */
                }
            } else {
                $input['created_by'] = $loginUserId;
                $input['created_type'] = 'Team';

                $generate_client_code = (new CommonController(new Request()))->generate_client_code($request);
                // dd("L-265", $validator, $generate_client_code);

                if ($generate_client_code->getStatusCode() == 200) {
                    $dataResponse = $generate_client_code?->getData();
                    if ($dataResponse?->data) {
                        $input['client_code'] = $dataResponse?->data;
                    }
                }

                $CustomerAddUpdate = Customer::create($input);

                if ($request?->inquiry_id && $CustomerAddUpdate?->id) {
                    $inquiry = Inquiry::where('id', $request?->inquiry_id)->first();
                    if ($inquiry) {
                        $inquiry->customer_id = $CustomerAddUpdate?->id;

                        $inquiryLogInput = [];
                        $inquiryLogInput['customer_id'] = $CustomerAddUpdate?->id;

                        if ($CustomerAddUpdate?->country_id && $inquiry?->country_id == null) {
                            $inquiry->country_id = $CustomerAddUpdate?->country_id;
                            $inquiryLogInput['country_id'] = $CustomerAddUpdate?->country_id;
                        }
                        if ($CustomerAddUpdate?->state_id && $inquiry?->state_id == null) {
                            $inquiry->state_id = $CustomerAddUpdate?->state_id;
                            $inquiryLogInput['state_id'] = $CustomerAddUpdate?->state_id;
                        }
                        if ($CustomerAddUpdate?->city_id && $inquiry?->city_id == null) {
                            $inquiry->city_id = $CustomerAddUpdate?->city_id;
                            $inquiryLogInput['city_id'] = $CustomerAddUpdate?->city_id;
                        }
                        if ($CustomerAddUpdate?->area_id && $inquiry?->area_id == null) {
                            $inquiry->area_id = $CustomerAddUpdate?->area_id;
                            $inquiryLogInput['area_id'] = $CustomerAddUpdate?->area_id;
                        }
                        if ($CustomerAddUpdate?->pincode && $inquiry?->pincode == null) {
                            $inquiry->pincode = $CustomerAddUpdate?->pincode;
                            $inquiryLogInput['pincode'] = $CustomerAddUpdate?->pincode;
                        }
                        if ($CustomerAddUpdate?->address && $inquiry?->address == null) {
                            $inquiry->address = $CustomerAddUpdate?->address;
                            $inquiryLogInput['address'] = $CustomerAddUpdate?->address;
                        }
                        $inquiry->updated_by = $loginUserId;
                        $inquiry->save();

                        // dd("L-437", $inquiry?->toArray(), $inquiry->isDirty());

                        InquiryLogs::create([
                            'company_id' => $company_id,
                            'inquiry_id' => $request?->inquiry_id,
                            'action' => 'Convert to customer',
                            'json_data' => json_encode($inquiryLogInput),
                            'created_by' => $loginUserId,
                            'created_type' => 'team',
                        ]);
                        // return $inquiry;
                    }
                }
                // return $input;

                /*
                if ($request?->tracking_latitude && $request?->tracking_longitude && $request?->tracking_address && $CustomerAddUpdate?->id) {
                    SaleExecutiveTracking::create([
                        'company_id' => $company_id,
                        'type_reference_id' => $CustomerAddUpdate?->id,
                        'type' => SaleExecutiveTracking::CUSTOMER_ADD,
                        'latitude' => $request?->tracking_latitude,
                        'longitude' => $request?->tracking_longitude,
                        'address' => $request?->tracking_address,
                        'current_datetime' => Carbon::now(),
                        'created_by' => $loginUserId,
                    ]);
                }
                    */
            }

            // $CustomerAddUpdate = Customer::updateOrCreate(['id' => $edit_id], $input);

            // dd("L-260", $edit_id, $request->all(), $input, $CustomerAddUpdate);
            DB::commit();
            return $this->sendResponse($CustomerAddUpdate, $returnMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage());
        }
        DB::rollBack();
        return $this->sendError("Something want to wrong in register API");
    }


    /**
     * customer delete
     * company_id is nullable when token is exist
     * delete_id
     */
    public function customer_delete(Request $request)
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
                'delete_id' => ['required', Rule::exists((new Customer())->getTable(), 'id')->where(function ($query) use ($request) {
                    return $query->where('company_id', $request?->company_id);
                })],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }


            $module = Customer::where('company_id', $company_id);
            $module = $module->where('id', $request?->delete_id);
            $module = $module->orderBy('id', 'DESC');
            $module = $module->first();

            if ($module) {
                // if ($module?->status != "pending") {
                //     return $this->sendError("You can not delete this Customer. reason is your Customer current status is " . ucfirst($module->status));
                // }
                if ($module->delete()) {
                    return $this->sendResponse($module, "Your Customer Delete Successfully.");
                }
                return $this->sendError("Your Customer found, but can not deleted.");
            }

            return $this->sendError("Your Customer not found.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }
}
