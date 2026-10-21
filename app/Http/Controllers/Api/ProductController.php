<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\MasterUnit;
use App\Models\Orderunit;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use App\Models\ProductTAX;
use App\Models\ProductVariantPrice;
use App\Models\RolePermissionsApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public $modules = [];

    public function __construct(Request $request)
    {
        $this->modules = [
            'table_name' => (new Product())->getTable(),
            'module_name' => 'Product',
        ];
    }

    /**
     * product categroy List
     * company_id is Required
     * filter_by_status is all, active, inactive
     */
    public function category_list(Request $request)
    {
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

            $data = new ProductCategory();
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
            return $this->sendResponse($data, "Product Category List.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in this API");
    }

    /**
     * Product Sub Category List
     * company_id is Required
     * filter_by_status is all, active, inactive
     */
    public function sub_category_list(Request $request)
    {
        try {
            $validator =  Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'product_category_id' => [
                    'required',
                    Rule::exists((new ProductCategory())->getTable(), 'id')
                ]
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $data = new ProductSubcategory();
            $data = $data->with(['company', 'productCategory']);
            $data = $data->where('company_id', $request?->company_id);
            $data = $data->where('product_category_id', $request?->product_category_id);

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
                $temp['product_category_id'] = $row?->product_category_id . "";
                $temp['product_category_name'] = $row?->product_category_name?->name . "";
                $temp['company_id'] = $row?->company_id . "";
                $temp['company_name'] = $row?->company->company_name . "";
                return $temp;
            });
            return $this->sendResponse($data, "Product Sub Category List.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in this API");
    }

    public function get_product_with_variant(Request $request)
    {
        try {
            $validator =  Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'category_id' => [
                    'required',
                    Rule::exists((new ProductCategory())->getTable(), 'id')
                ],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            /* Pagination */
            $pagination = (isset($request?->page)) ? true : false;
            $perPage = (isset($request?->per_page)) ? $request?->per_page : env("API_PER_PAGE");
            $page = (isset($request?->page)) ? $request?->page : 1;
            $category_id = $request?->category_id;


            $data = Product::query()
                ->where('company_id', $request?->company_id)
                ->when(
                    $request->filled('filter_by_status') && $request->filter_by_status !== 'all',
                    function ($q) use ($request) {
                        $q->where('status', $request->filter_by_status);
                    },
                    function ($q) use ($request) {
                        $q->where('product_category_id', $request->category_id)->where('status', 'active');
                    }
                );

            $tempResponseData = [];
            if ($pagination) {
                $data = $data->paginate($perPage);

                $tempResponseData['total'] = "" . $data->total();
                $tempResponseData['perPage'] = $perPage;
                $tempResponseData['currentPage'] = $page;
                $tempResponseData['totalPage'] = "" . $data->lastPage();
                $tempResponseData['items'] = (object)$data->items();
                $tempResponseData['items'] = collect($tempResponseData['items']);
            } else {
                $data = $data->get();
                $tempResponseData['total'] = $data->count() . "";
                $tempResponseData['items'] = $data;
            }

            // return $tempResponseData;
            if (isset($tempResponseData['items'])) {
                $tempResponseData['items'] = $tempResponseData['items']->map(function ($record) {
                    $temp = [];

                    $temp['id'] = $record?->id . ""  ?? "";
                    $temp['product_id'] = $record?->id . ""  ?? "";
                    $temp['product_name'] = $record?->product_name . ""  ?? "";
                    $temp['product_category_id'] = $record?->product_category_id . ""  ?? "";
                    $temp['product_sub_category_id'] = $record?->product_sub_category_id . ""  ?? "";
                    $temp['product_tax_id'] = $record?->product_tax_id . ""  ?? "";
                    $temp['unit_id'] = $record?->unit_id . ""  ?? "";
                    $temp['display_unit_id'] = $record?->display_unit_id . ""  ?? "";
                    $temp['hsn_code'] = $record?->hsn_code . ""  ?? "";
                    $temp['attachment'] = $record?->attachment . ""  ?? "";
                    $temp['platform'] = $record?->platform . ""  ?? "";
                    $temp['attachment_url'] = $record?->attachment_url . ""  ?? "";
                    $temp['product_status'] = $record?->status . ""  ?? "";
                    $temp['company_id'] = $record?->company_id . ""  ?? "";
                    $temp['company_name'] = $record?->company?->company_name . "" ?? "";

                    $temp['variant_id'] = "";
                    $temp['variant_name'] = "";
                    $temp['variant_status'] = "";

                    $temp['product_code'] = $record?->part_no . ""  ?? "";
                    $temp['including_gst'] = $record?->including_gst . ""  ?? "";
                    $temp['description'] = $record?->description . ""  ?? "";
                    $temp['product_weight'] = $record?->product_weight . ""  ?? "";
                    $temp['gross_wt'] = $record?->gross_wt . ""  ?? "";
                    $temp['net_wt'] = $record?->net_wt . ""  ?? "";
                    $temp['inner_pack_qty'] = $record?->inner_pack_qty . ""  ?? "";
                    $temp['outer_pack_qty'] = $record?->outer_pack_qty . ""  ?? "";
                    $temp['min_stock_qty'] = $record?->min_stock_qty . ""  ?? "";
                    $temp['max_stock_qty'] = $record?->max_stock_qty . ""  ?? "";
                    $temp['product_variant_prices_status'] = $record?->status . ""  ?? "";
                    return $temp;
                });

                return $this->sendResponse($tempResponseData, "Product list by variant & price");
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in this API");
    }

    /**
     * Product List
     * company_id is nullable when token is exist
     */
    public function product_list(Request $request)
    {
        $modules = $this->modules;

        try {

            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            $company_id = $request?->company_id ?? $loginUser?->company_id;
            $request['company_id'] = $company_id;

            $modules['authLoginUserDetail'] = $loginUser ?? null;
            $modules['company_id'] = $request['company_id'] ?? null;
            $modules['parent_type_id'] = $loginUser?->parent_type_id ?? null;

            foreach (config('constants.app_permissions') as $key => $value) {
                // Helper::checkAppPermission("app", (new RolePermissionsApp())->getTable(), $modules['company_id'], $loginUser?->id, $modules['module_name'], $value.'_flag');
                $modules[$value . '_permission'] = Helper::checkAppPermission("app", (new RolePermissionsApp())->getTable(), $modules['company_id'], $loginUser?->id, $modules['module_name'], $value . '_flag');
            }

            // return $modules;
            if (!$modules['view_permission']) {
                return $this->sendError('You do not have permission to access the ' . $modules['module_name'] . ' module.', [], [], 403);
            }

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

            $module = Product::query();
            // $module = $module->with(['company']);
            $module = $module->where('company_id', $company_id);

            if ($request->filter_by_status && $request->filter_by_status != "all") {
                $module = $module->where('status', $request->filter_by_status);
            }

            if ($request->filter_by_product_category_id) {
                $module = $module->where('product_category_id', $request->filter_by_product_category_id);
            }

            if ($request->filter_by_product_sub_category_id) {
                $module = $module->where('product_sub_category_id', $request->filter_by_product_sub_category_id);
            }

            if ($request->filter_by_product_tax_id) {
                $module = $module->where('product_tax_id', $request->filter_by_product_tax_id);
            }

            if ($request->filter_by_hsn_code) {
                $module = $module->where('hsn_code', "%" . $request->filter_by_hsn_code . "%");
            }
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

                    $temp['name'] = $record?->product_name . "";

                    $temp['product_category_id'] = $record?->product_category_id . "";
                    $temp['product_category_name'] = $record?->product_category?->name . "" ?? '';

                    $temp['product_sub_category_id'] = $record?->product_sub_category_id . "";
                    $temp['product_sub_category_name'] = $record?->product_sub_category?->name . "" ?? '';

                    // $temp['product_variant_prices'] = $record?->product_variant_prices . "" ?? '';


                    $temp['product_tax_id'] = $record?->product_tax_id . "";
                    $temp['unit_id'] = $record?->unit_id . "";
                    $temp['display_unit_id'] = $record?->display_unit_id . "";

                    $temp['hsn_code'] = $record?->hsn_code . "";

                    $temp['attachment'] = $record?->attachment . "";
                    $temp['attachment_url'] = $record?->attachment_url . "";

                    $temp['status'] = $record?->status . "";
                    $temp['platform'] = $record?->platform . "";
                    $temp['created_at'] = $record?->created_at . "";
                    $temp['updated_at'] = $record?->updated_at . "";

                    return $temp;
                });

                return $this->sendResponse($tempResponseData, "Product list by company.");
            }
            return $this->sendError("Something want to wrong in data fetching.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }


    /**
     *Product Add / Edit
     * company_id is nullable when token is exist
     */
    public function product_add_edit(Request $request)
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
            $request['company_id'] = $company_id;

            // return $request->all();
            $validator =  Validator::make($request->all(), [
                'edit_id' => [
                    'nullable',
                    Rule::exists((new Product())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        }),
                ],
                'platform' => ['required', 'in:web,android,ios'],
                'company_id' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'product_category_id' => [
                    'required',
                    Rule::exists((new ProductCategory())->getTable(), 'id')
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        })
                ],
                'product_sub_category_id' => [
                    'required',
                    Rule::exists((new ProductSubcategory())->getTable(), 'id')->where(function ($query) use ($request) {
                        return $query->where('company_id', $request?->company_id);
                    })
                ],
                'product_name' => [
                    'required',
                    Rule::unique((new Product())->getTable())
                        ->ignore($request->edit_id) // ✅ Ignore current record during update
                        ->where(function ($query) use ($request) {
                            return $query->where('company_id', $request?->company_id);
                        }),
                ],
                'product_tax_id' => [
                    'required',
                    Rule::exists((new ProductTAX())->getTable(), 'id')->where(function ($query) use ($request) {
                        return $query->where('company_id', $request?->company_id);
                    })
                ],
                'unit_id' => [
                    'required',
                    Rule::exists((new Orderunit())->getTable(), 'id')->where(function ($query) use ($request) {
                        return $query->where('company_id', $request?->company_id);
                    })
                ],
                'display_unit_id' => [
                    'required',
                    Rule::exists((new MasterUnit())->getTable(), 'id')->where(function ($query) use ($request) {
                        return $query->where('company_id', $request?->company_id);
                    })
                ],
                'hsn_code' => [
                    'nullable',
                    'max:6'
                ],
                'attachment' => [
                    'nullable',
                    'file',
                    'mimes:jpg,jpeg,png,webp',
                    'max:2048',
                ],
                'status' => [
                    'required',
                    'in:active,inactive'
                ],
                'per_bag_quantity' => ['nullable', 'integer', 'min:0'],
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }
            $loginUserId = $loginUser?->id;
            // return Carbon::now()->subWeek()->startOfDay();
            // return $request->all();

            $input = $request->except(['edit_id']);
            $returnMessage = "Add product successfully.";

            if ($request->hasFile('attachment')) {
                // return $image_name = Helper::make_slug('leave-application ' . (string)$loginUser?->id. ' '. (string)$loginUser?->name . ' ' . date('Ymd-His'));
                $image_name = Helper::make_slug(date('Ymd-His') . ' ' . (string)$loginUser?->id . ' ' . (string)$loginUser?->name);

                $file = $request->file('attachment');

                $extenstion = $file->getClientOriginalExtension();

                $filename = $image_name . '.' . $extenstion;

                $sub_folder_path = Helper::fileUploadPath($loginUser, Product::$folderPath);
                $uploadedPath = public_path($sub_folder_path);
                // return $uploadedPath;
                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $sub_folder_path . $filename;
                    if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                        $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $image_name, 50, true);
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

            if ($request?->edit_id) {
                $input['updated_type'] = 'Team';
                $input['updated_by'] = $loginUserId;
                $returnMessage = "Update product successfully.";
                $modelAddEdit = Product::where('id', $request?->edit_id);
                $modelAddEdit = $modelAddEdit->where('company_id', $company_id);
                $modelAddEdit = $modelAddEdit->first();
                $modelAddEdit->update($input);
            } else {
                $input['created_by'] = $loginUserId;
                $input['created_type'] = 'Team';
                // return $input;
                $modelAddEdit = Product::create($input);
            }

            return $this->sendResponse($modelAddEdit, $returnMessage);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }
}
