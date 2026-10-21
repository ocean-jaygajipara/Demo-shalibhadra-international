<?php

namespace App\Http\Controllers\software;

use App\Helpers\Helper;
use App\Exports\ProductExport;
use App\Http\Controllers\Controller;
use App\Models\CompanyDetails;
use App\Models\Product;
use App\Models\Company;
use App\Models\ProductVariantPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ProductRequest;
use App\Imports\ProductImport;
use App\Models\ProductBOM;
use App\Models\ProductCustomerItem;
use App\Models\ProductImage;
use App\Models\ProductImportFile;
use App\Models\ProductRmDetail;
use App\Models\ProductSupplierDetail;
use App\Models\ProductGrade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public $modules = [];

    public function __construct()
    {

        parent::__construct();

        $this->modules = [
            'title' => 'Product',
            'folder_path' => 'software.modules.product.products',
            'route' => 'products',
            'table_name' => (new Product())->getTable(),
            'permisstion_prefix' => 'products',
            'module_name' => 'Product',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null

        ];
    }

    public function index(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['view_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        View::share('modules', $modules);
        try {

            $columns = [
                (object) ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'td_label' => 'Sr No.', 'orderable' => false, 'searchable' => false],
                (object) ['data' => "product_name", 'name' => 'product_name', 'td_label' => 'Product Name', 'className' => ''],
                (object) ['data' => "category", 'name' => 'category', 'td_label' => 'Product Category', 'className' => ''],
                (object) ['data' => "part_no", 'name' => 'part_no', 'td_label' => 'Part No.', 'className' => ''],
                //(object)['data' => "product_mrp", 'name' => 'product_mrp', 'td_label' => 'Product MRP', 'className' =>  ''],
                (object) ['data' => "status", 'name' => 'status', 'td_label' => 'Status', 'className' => 'w-5 text-start'],
                (object) ['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'w-10 text-start'],
            ];
            if ($modules['company_id']) {
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'company_id';
                });

                $columns = array_values($columns);
            }
            View::share("columns", $columns);

            //dd($request->all());
            if ($request->ajax()) {
                if (!$modules['view_permission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }
                $data = Product::select('products.*', 'products.id as product_key')
                    ->with(['company', 'product_category']);

                $data = $data->where(function ($query) use ($modules, $loginUserId) {

                    if (Auth::guard('team_person')->check() && $modules['company_id']) {
                        $query->where('company_id', $modules['company_id']);

                        if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                            $query->where('products.created_by', $loginUserId);
                        }
                    }
                })
                    ->orderBy('product_key', 'desc');
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }

                return Datatables::of($data)
                    ->addIndexColumn()
                    ->filter(function ($query) use ($request) {

                        if ($request->has('status') && $request->status !== null && $request->status !== 'all') {
                            $query->where('status', $request->status);
                        }
                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('products.company_id', $request->filter_company);
                        }
                        if ($request->has('filter_product_category') && $request->filter_product_category) {
                            $query->where('products.product_category_id', $request->filter_product_category);
                        }
                        if ($request->has('filter_product_grade') && $request->filter_product_grade) {
                            $query->where('products.product_grade_id', $request->filter_product_grade);
                        }

                        if ($request->has('search')) {
                            $search = $request->search;
                            $search_arr = explode("-", $search);

                            $query->where(function ($q) use ($search_arr, $search) {
                                $q->orWhere('product_name', 'like', "%{$search}%")
                                    ->orWhere('part_no', 'like', "%{$search}%")
                                    ->orWhere('hsn_code', 'like', "%{$search}%");
                                //->orWhere('product_mrp', 'like', "%{$search}%")
                            });
                        }
                    })
                    ->editColumn('status', function ($row) use ($modules) {
                        $btn = '';
                        $dropdown = "";
                        $dropdown .= '<ul class="dropdown-menu" style="">';

                        if ($row->status == "active") {
                            $btn .= '<button type="button" class="btn btn-success btn-sm dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item waves-effect btn-label-danger update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="inactive">Inactive</a></li>';
                        } elseif ($row->status == "inactive") {
                            $btn .= '<button type="button" class="btn btn-danger btn-sm dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item waves-effect btn-label-success update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="active">Active</a></li>';
                        } else {
                            return $btn;
                        }
                        $dropdown .= '</ul>';
                        $btn .= $dropdown;
                        return $btn;
                    })
                    ->addColumn('category', function ($row) {
                        return $row?->product_category?->name;
                    })
                    ->addColumn('part_no', function ($row) {
                        return $row?->part_no;
                    })
                    // ->addColumn('product_mrp', function ($row) {
                    //     return $row?->product_mrp;
                    // })
                    // ->addColumn('company', function ($row) {
                    //     return $row?->company->name;
                    // })
                    ->addColumn('action', function ($row) use ($modules) {
                        $btn = '';
                        if (!$row?->deleted_at) {
                            if ($modules['update_permission']) {
                                $btn .= '<a href="' . route($modules["route"] . ".edit", [$row["product_key"]]) . '" class="btn btn-light btn-icon mx-1"><i class="fa-solid fa-pen-to-square"></i></a>';
                            }
                            if ($modules['delete_permission']) {
                                $btn .= '<a href="javascript:void(0)" data-id="' . $row->product_key . '" data-did="' . route($modules["route"] . ".destroy", [$row["product_key"]]) . '" class="btn btn-danger btn-icon deletebutton mx-1"><i class="fa-solid fa-trash"></i></i></a>';
                            }
                        } else {
                            $btn .= '<a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["product_key"]]) . '" class="btn btn-light mx-1 record-restore" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Restore Data"><i class="ti ti-history"></i> Restore</a>';
                        }
                        if ($btn == '') {
                            $btn = '-';
                        }
                        return $btn;
                    })
                    ->rawColumns(['status', 'action'])
                    ->make(true);
            }
            if (!$modules['view_permission']) {
                abort(403, 'Unauthorized');
            }

            return view($modules['folder_path'] . '.index');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return Redirect::route('software.dashboard')->withErrors($e->getMessage());
        }
    }

    public function create()
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['add_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        try {

            View::share('modules', $modules);
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function store(ProductRequest $request)
    {

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['add_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);
        $validated = $request->validated();

        try {

            $validated['created_by'] = $loginUserId;

            $attachmentsList = [];
            if ($request->hasFile('attachments')) {
                $company = Company::find($validated['company_id']);
                $company_name = $company->company_name ?? 'default-company';
                $company_slug = Str::slug($company_name);

                $year = now()->format('Y');
                $month = now()->format('m');
                $folder = "uploads/{$validated['company_id']}-{$company_slug}/product-attachments/{$year}/{$month}/";
                $uploadedPath = public_path($folder);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0777, true);
                }

                foreach ($request->file('attachments') as $file) {
                    $originalName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    $filename = now()->format('dmYHis') . rand() . '.' . $extension;
                    $file->move($uploadedPath, $filename);

                    $attachmentsList[] = [
                        'file_path' => "{$year}/{$month}/{$filename}",
                        'original_name' => $originalName,
                        'uploaded_at' => now()->format('d-m-Y')
                    ];
                }
            }
            $validated['attachment'] = count($attachmentsList) > 0 ? json_encode($attachmentsList) : null;

            $company_id = $validated['company_id'];
            $product = Product::create($validated);
            $this->syncProductBackToExcel($product);
            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function show(string $id)
    {
        if ($id === "print") {
            return $this->print(request());
        }
        return redirect()->route('products.index');
    }

    public function edit(string $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['update_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        try {

            View::share('modules', $modules);
            $edit = Product::findOrFail($id);
            View::share('edit', $edit);
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function update(ProductRequest $request, string $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['update_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        View::share('modules', $modules);
        $request['id'] = $id;

        $validated = $request->validated();
        try {

            $validated['updated_by'] = $loginUserId;
            $updateData = Product::findOrFail($id);

            if ($updateData) {
                unset($validated['id']);

                $existingAttachments = $updateData->attachments_list;
                if ($request->has('removed_attachments')) {
                    $removed = $request->input('removed_attachments');
                    $existingAttachments = array_filter($existingAttachments, function ($item) use ($removed, $updateData) {
                        $isRemoved = in_array($item['file_path'], $removed);
                        if ($isRemoved) {
                            $company = Company::find($updateData->company_id);
                            $company_name = $company->company_name ?? 'default-company';
                            $company_slug = Str::slug($company_name);
                            $fullPath = public_path(str_replace('/', DIRECTORY_SEPARATOR, "uploads/{$updateData->company_id}-{$company_slug}/product-attachments/{$item['file_path']}"));
                            if (file_exists($fullPath)) {
                                @unlink($fullPath);
                            }
                        }
                        return !$isRemoved;
                    });
                    $existingAttachments = array_values($existingAttachments);
                }

                if ($request->hasFile('attachments')) {
                    $company = Company::find($validated['company_id']);
                    $company_name = $company->company_name ?? 'default-company';
                    $company_slug = Str::slug($company_name);

                    $year = now()->format('Y');
                    $month = now()->format('m');
                    $folder = "uploads/{$validated['company_id']}-{$company_slug}/product-attachments/{$year}/{$month}/";
                    $uploadedPath = public_path($folder);

                    if (!file_exists($uploadedPath)) {
                        mkdir($uploadedPath, 0777, true);
                    }

                    foreach ($request->file('attachments') as $file) {
                        $originalName = $file->getClientOriginalName();
                        $extension = $file->getClientOriginalExtension();
                        $filename = now()->format('dmYHis') . rand() . '.' . $extension;
                        $file->move($uploadedPath, $filename);

                        $existingAttachments[] = [
                            'file_path' => "{$year}/{$month}/{$filename}",
                            'original_name' => $originalName,
                            'uploaded_at' => now()->format('d-m-Y')
                        ];
                    }
                }
                $validated['attachment'] = count($existingAttachments) > 0 ? json_encode($existingAttachments) : null;
                $updateData->update($validated);
                $this->syncProductBackToExcel($updateData);
                $company_id = $validated['company_id'];

                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' update successfully');
            }
            return Redirect::back()->withErrors('something went wrong please try again later')->withInput();
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function destroy(Request $request, string $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['delete_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        $dataDelete = Product::findOrFail($id);
        $isAjax = ($request->ajax()) ? true : false;

        try {
            if ($dataDelete) {

                $validated['deleted_by'] = $loginUserId;
                $dataDelete->update($validated);

                if ($dataDelete->delete()) {
                    ProductBOM::where('product_id', $id)->delete();
                    ProductCustomerItem::where('product_id', $id)->delete();
                    ProductImage::where('product_id', $id)->delete();
                    ProductSupplierDetail::where('product_id', $id)->delete();
                    ProductRmDetail::where('product_id', $id)->delete();

                    if ($isAjax) {
                        return $this->sendResponse([], $modules['title'] . ' delete successfully');
                    }
                    return true;
                }
            }
            if ($isAjax) {
                return $this->sendResponse([], "something went wrong please try again later");
            }
            return false;
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function status_update(Request $request)
    {
        $isAjax = ($request->ajax()) ? true : false;

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['update_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        $validator = Validator::make($request->all(), [
            'id' => ['required', Rule::exists($modules['table_name'], 'id')],
            'update_status' => ['required', 'in:active,inactive']
        ]);

        if ($validator->fails() && $isAjax) {
            return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
        }

        try {
            $product = Product::withTrashed()->findOrFail($request?->id);
            if ($product) {
                $product->status = $request->update_status;
                $product->updated_by = $loginUserId;
                $product->save();
                if ($isAjax) {
                    return $this->sendResponse($product, $modules['title'] . ' status update successfully.');
                }
                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' status update successfully.');
            }
            if ($isAjax) {
                return $this->sendError('something went wrong please try again later');
            }
            return Redirect::back()->withErrors('something went wrong please try again later')->withInput();
        } catch (\Exception $e) {
            if ($isAjax) {
                return $this->sendError($e->getMessage(), $e->getMessage(), [], 401);
            }
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function restore($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['restore_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        try {
            $product = Product::withTrashed()->findOrFail($id);
            $product->restore();
            return Redirect::back()->withSuccess($modules['title'] . ' restored successfully!');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function import()
    {
        $modules = $this->modules;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;

        View::share('modules', $modules);

        return view($modules['folder_path'] . '.import');
    }


    public function import_store(Request $request)
    {
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $authLoginUserDetail = $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $company_Id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        View::share('modules', $modules);
        View::share('company_id', $modules['company_id']);

        $validator = Validator::make($request->all(), [
            'company_id' => 'required',
            'import_file' => 'required|mimes:xls,xlsx|max:20480', // 20MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 422,
                'errors' => $validator->errors()
            ]);
        }

        try {
            set_time_limit(300);
            ini_set('memory_limit', '512M');

            if ($request->hasFile('import_file')) {
                $company = Company::find($request->company_id);
                $company_name = $company->company_name;
                $company_slug = Str::slug($company_name);

                $file = $request->file('import_file');
                $extension = $file->getClientOriginalExtension();
                $filename = now()->format('dmYHis') . rand() . '.' . $extension;

                $year = now()->format('Y');
                $month = now()->format('m');
                $folder = "uploads/" . $request->company_id . "-" . $company_slug . "/product/{$year}-{$month}/import-product-file";
                $path = $file->storeAs($folder, $filename, 'public');

                $filename_path = "{$folder}/{$filename}";
            }

            $productImportFileId = ProductImportFile::insertGetId([
                'company_id' => $request->company_id,
                'filename' => $filename_path,
                'total_products' => 0,
                'total_success_products' => 0,
                'total_failed_products' => 0,
                'errors' => '',
                'created_by' => $loginUserId,
            ]);

            $company_id = $request->company_id;

            Excel::import(
                new ProductImport($company_id, $loginUserId, $modules['currentGuard'], $productImportFileId),
                $request->file('import_file')
            );

            $productImportFile = ProductImportFile::find($productImportFileId);

            // Flash summary
            session()->flash('product_summary', [
                'total_products' => $productImportFile->total_products,
                'total_success_products' => $productImportFile->total_success_products,
                'total_failed_products' => $productImportFile->total_failed_products,
            ]);


            if (!empty($productImportFile->errors)) {
                if ($productImportFile->total_success_products > 0) {

                    return Redirect::back()
                        ->with('productImportFileMessages', json_decode($productImportFile->errors))
                        ->with('success', 'Products imported successfully with some errors!');
                } else {

                    return Redirect::back()
                        ->with('productImportFileMessages', json_decode($productImportFile->errors));
                }
            }


            return back()->with('success', 'Products imported successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }



    public function exportExcel(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['excel_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        return Excel::download(
            new ProductExport($request->all(), $this->authenticateLoginUserDetails, $modules),
            'ProductExport-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx'
        );
    }

    public function remove_product_variant(Request $request)
    {
        $product_variant_price_id = $request->product_variant_price_id;
        $product_variant_price = ProductVariantPrice::find($product_variant_price_id);
        if ($product_variant_price) {
            $product_variant_price->delete();
            return $this->sendResponse([], 'Variant removed successfully!');
        }
        return $this->sendError('Variant not found!');
    }
    public function print(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $company_id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['print_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        try {
            View::share('modules', $modules);
            $product = Product::select('products.*', 'products.id as product_key')
                ->with(['company', 'product_category']);

            if ($request->has('company') && $request->company) {
                $product->where('products.company_id', $request->company);
            }
            if ($request->has('product_category') && $request->product_category) {
                $product->where('products.product_category_id', $request->product_category);
            }
            if ($request->has('product_grade') && $request->product_grade) {
                $product->where('products.product_grade_id', $request->product_grade);
            }
            if ($request->has('status') && $request->status !== null && $request->status !== 'all') {
                $product->where('status', $request->status);
            }
            if ($request->has('search')) {
                $search = $request->search;
                $search_arr = explode("-", $search);

                $product->where(function ($q) use ($search_arr, $search) {
                    $q->orWhere('product_name', 'like', "%{$search}%")
                        ->orWhere('part_no', 'like', "%{$search}%")
                        ->orWhere('hsn_code', 'like', "%{$search}%")
                        //->orWhere('product_mrp', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%")
                        ->orWhere('variant_of', 'like', "%{$search}%");
                });
            }
            $product->where(function ($query) use ($modules, $loginUserId) {
                if (Auth::guard('team_person')->check() && $modules['company_id']) {
                    $query->where('company_id', $modules['company_id']);

                    if (!empty($modules['personal_data_permission']) && empty($modules['all_data_permission'])) {
                        $query->where('created_by', $loginUserId);
                    }
                }
            });
            //dd($product->withTrashed()->orderBy('id', 'DESC')->toSql());
            $product = $product->get();

            return view($modules['folder_path'] . '.print', compact('product', 'company_id', 'modules'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function rate_sheet_index(Request $request)
    {
        $modules = $this->modules;
        $modules['title'] = 'SIPL Rate Sheet Manager';
        View::share('modules', $modules);

        $filePath = Helper::getRateSheetPath();
        $sheetData = [];
        $fileInfo = null;
        $syncStats = [
            'total' => 0,
            'matched' => 0,
            'unmatched' => 0,
            'unmatched_list' => [],
            'matched_list' => []
        ];

        if (file_exists($filePath)) {
            $fileInfo = [
                'exists' => true,
                'name' => basename($filePath),
                'last_modified' => date('d-m-Y H:i:s', filemtime($filePath)),
                'size' => round(filesize($filePath) / 1024, 2) . ' KB'
            ];

            try {
                $allProducts = Product::all(['id', 'product_name', 'part_no', 'gross_wt', 'net_wt', 'product_grade_id']);
                $dbByPartExact = [];
                $dbByNameExact = [];
                $dbByPartNorm = [];
                $dbByNameNorm = [];

                foreach ($allProducts as $prod) {
                    if ($prod->part_no) {
                        $dbByPartExact[$prod->part_no] = $prod;
                        $dbByPartNorm[strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $prod->part_no))] = $prod;
                    }
                    if ($prod->product_name) {
                        $dbByNameExact[$prod->product_name] = $prod;
                        $dbByNameNorm[strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $prod->product_name))] = $prod;
                    }
                }

                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                $sheetNames = $spreadsheet->getSheetNames();
                foreach ($sheetNames as $sheetName) {
                    $sheet = $spreadsheet->getSheetByName($sheetName);
                    if (!$sheet) continue;
                    $rows = $sheet->toArray();
                    $items = [];
                    for ($i = 2; $i < count($rows); $i++) {
                        $itemCode = trim((string)($rows[$i][1] ?? ''));
                        $name = trim((string)($rows[$i][2] ?? ''));
                        if (empty($itemCode) && empty($name)) continue;

                        $rawGrossWeight = floatval(preg_replace('/[^0-9.]/', '', (string)($rows[$i][4] ?? 0)));
                        $rawKgsIntoKg = floatval(preg_replace('/[^0-9.]/', '', (string)($rows[$i][8] ?? 0)));
                        $calcNetWt = $rawGrossWeight > 0 ? round($rawGrossWeight / 144, 3) : 0;
                        $calcGrossWt = ($calcNetWt > 0 && $rawKgsIntoKg > 0) ? round($calcNetWt * $rawKgsIntoKg, 3) : $calcNetWt;

                        $dbProduct = null;
                        if (!empty($itemCode) && isset($dbByPartExact[$itemCode])) {
                            $dbProduct = $dbByPartExact[$itemCode];
                        }
                        if (!$dbProduct && !empty($name) && isset($dbByNameExact[$name])) {
                            $dbProduct = $dbByNameExact[$name];
                        }
                        if (!$dbProduct && !empty($itemCode)) {
                            $normCode = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $itemCode));
                            if (isset($dbByPartNorm[$normCode])) {
                                $dbProduct = $dbByPartNorm[$normCode];
                            }
                        }
                        if (!$dbProduct && !empty($name)) {
                            $normName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
                            if (isset($dbByNameNorm[$normName])) {
                                $dbProduct = $dbByNameNorm[$normName];
                            }
                        }

                        $itemDetails = [
                            'excel_row' => $i + 1,
                            'sheet_name' => $sheetName,
                            'no' => $rows[$i][0] ?? ($i - 1),
                            'item_code' => $itemCode,
                            'item_name' => $name,
                            'scrap_rate' => trim((string)($rows[$i][3] ?? '')),
                            'gross_weight' => trim((string)($rows[$i][4] ?? '')),
                            'kg_in_gross' => trim((string)($rows[$i][5] ?? '')),
                            'job_work_rate' => trim((string)($rows[$i][6] ?? '')),
                            'gross_labour_charges' => trim((string)($rows[$i][7] ?? '')),
                            'kgs_into_kg' => trim((string)($rows[$i][8] ?? '')),
                            'burning_loss' => trim((string)($rows[$i][9] ?? '')),
                            'casting_charges' => trim((string)($rows[$i][10] ?? '')),
                            'complete_cost_kg' => trim((string)($rows[$i][11] ?? '')),
                            'gross_rate' => trim((string)($rows[$i][12] ?? '')),
                            'pcs_rate' => trim((string)($rows[$i][13] ?? '')),
                            'calculated_net_wt' => number_format($calcNetWt, 3, '.', ''),
                            'calculated_gross_wt' => number_format($calcGrossWt, 3, '.', ''),
                            'db_product' => $dbProduct
                        ];

                        $items[] = $itemDetails;
                        $syncStats['total']++;
                        if ($dbProduct) {
                            $syncStats['matched']++;
                            $syncStats['matched_list'][] = $itemDetails;
                        } else {
                            $syncStats['unmatched']++;
                            $syncStats['unmatched_list'][] = $itemDetails;
                        }
                    }
                    $sheetData[$sheetName] = $items;
                }
            } catch (\Exception $e) {
                \Log::error("Rate Sheet Error: " . $e->getMessage());
            }
        }

        $company_id = $this->authenticateLoginUserDetails?->company_id;
        $productGradesQuery = ProductGrade::where('status', 'active');
        if ($company_id) {
            $productGradesQuery->where('company_id', $company_id);
        }
        $productGrades = $productGradesQuery->orderBy('name', 'asc')->get();
        if ($productGrades->isEmpty()) {
            $productGrades = ProductGrade::orderBy('name', 'asc')->get();
        }

        return view('software.modules.product.products.rate_sheet', compact('modules', 'sheetData', 'fileInfo', 'syncStats', 'productGrades'));
    }

    public function rate_sheet_update_cell(Request $request)
    {
        $request->validate([
            'sheet_name' => 'required|string',
            'excel_row' => 'required|integer|min:3',
            'field' => 'required|string',
            'value' => 'required'
        ]);

        $filePath = Helper::getRateSheetPath();
        if (!file_exists($filePath)) {
            return response()->json(['status' => false, 'message' => 'Excel file not found'], 404);
        }

        try {
            $fieldColumnMap = [
                'item_code' => 'B',
                'item_name' => 'C',
                'scrap_rate' => 'D',
                'gross_weight' => 'E',
                'job_work_rate' => 'G',
                'kgs_into_kg' => 'I'
            ];

            $field = $request->input('field');
            if (!isset($fieldColumnMap[$field])) {
                return response()->json(['status' => false, 'message' => 'Invalid field: ' . $field], 400);
            }

            $col = $fieldColumnMap[$field];
            $row = intval($request->input('excel_row'));
            $sheetName = $request->input('sheet_name');
            $val = trim((string)$request->input('value'));

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (!$sheet) {
                return response()->json(['status' => false, 'message' => 'Sheet not found'], 404);
            }

            if (in_array($field, ['scrap_rate', 'gross_weight', 'job_work_rate', 'kgs_into_kg'])) {
                $numVal = floatval(preg_replace('/[^0-9.]/', '', $val));
                $sheet->setCellValue($col . $row, $numVal);
            } else {
                $sheet->setCellValue($col . $row, $val);
            }

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($filePath);

            $itemCode = trim((string)$sheet->getCell('B' . $row)->getValue());
            $itemName = trim((string)$sheet->getCell('C' . $row)->getValue());
            $scrapRate = trim((string)$sheet->getCell('D' . $row)->getCalculatedValue());
            $grossWeight = floatval($sheet->getCell('E' . $row)->getCalculatedValue());
            $kgInGross = round(floatval($sheet->getCell('F' . $row)->getCalculatedValue()), 2);
            $jobWorkRate = trim((string)$sheet->getCell('G' . $row)->getCalculatedValue());
            $grossLabourCharges = round(floatval($sheet->getCell('H' . $row)->getCalculatedValue()), 2);
            $kgsIntoKgVal = floatval(preg_replace('/[^0-9.]/', '', (string)$sheet->getCell('I' . $row)->getCalculatedValue()));
            $kgsIntoKg = trim((string)$sheet->getCell('I' . $row)->getCalculatedValue());
            $burningLoss = round(floatval($sheet->getCell('J' . $row)->getCalculatedValue()), 2);
            $castingCharges = round(floatval($sheet->getCell('K' . $row)->getCalculatedValue()), 2);
            $completeCostKg = round(floatval($sheet->getCell('L' . $row)->getCalculatedValue()), 2);
            $grossRate = round(floatval($sheet->getCell('M' . $row)->getCalculatedValue()), 2);
            $pcsRate = round(floatval($sheet->getCell('N' . $row)->getCalculatedValue()), 2);

            $calcNetWt = $grossWeight > 0 ? round($grossWeight / 144, 3) : 0;
            $calcGrossWt = ($calcNetWt > 0 && $kgsIntoKgVal > 0) ? round($calcNetWt * $kgsIntoKgVal, 3) : $calcNetWt;

            $dbProduct = null;
            if (!empty($itemCode)) {
                $dbProduct = Product::where('part_no', $itemCode)->first();
            }
            if (!$dbProduct && !empty($itemName)) {
                $dbProduct = Product::where('product_name', $itemName)->first();
            }

            if ($dbProduct) {
                $dbProduct->net_wt = number_format($calcNetWt, 3, '.', '');
                $dbProduct->gross_wt = number_format($calcGrossWt, 3, '.', '');
                $dbProduct->grosse_per_qty = 144;
                $dbProduct->save();
            }

            cache()->flush();

            return response()->json([
                'status' => true,
                'message' => "Row #{$row} updated successfully in Excel & CRM DB!",
                'data' => [
                    'excel_row' => $row,
                    'item_code' => $itemCode,
                    'item_name' => $itemName,
                    'scrap_rate' => $scrapRate,
                    'gross_weight' => $grossWeight,
                    'kg_in_gross' => number_format($kgInGross, 2),
                    'job_work_rate' => $jobWorkRate,
                    'gross_labour_charges' => '₹ ' . number_format($grossLabourCharges, 2),
                    'kgs_into_kg' => $kgsIntoKg,
                    'burning_loss' => '₹ ' . number_format($burningLoss, 2),
                    'casting_charges' => '₹ ' . number_format($castingCharges, 2),
                    'complete_cost_kg' => '₹ ' . number_format($completeCostKg, 2),
                    'gross_rate' => '₹ ' . number_format($grossRate, 2),
                    'pcs_rate' => '₹ ' . number_format($pcsRate, 2),
                    'calculated_net_wt' => number_format($calcNetWt, 3, '.', ''),
                    'calculated_gross_wt' => number_format($calcGrossWt, 3, '.', ''),
                    'db_updated' => (bool)$dbProduct,
                    'db_product_id' => $dbProduct?->id
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function rate_sheet_update_global_scrap_rate(Request $request)
    {
        $request->validate([
            'scrap_rate' => 'required|numeric|min:1',
            'product_grade_id' => 'nullable'
        ]);

        $newRate = floatval($request->input('scrap_rate'));
        $gradeId = $request->input('product_grade_id');
        $filePath = Helper::getRateSheetPath();
        if (!file_exists($filePath)) {
            return response()->json(['status' => false, 'message' => 'sipl rate.xlsx not found'], 404);
        }

        try {
            $allProducts = Product::all(['id', 'product_name', 'part_no', 'product_grade_id', 'gross_wt', 'net_wt', 'grosse_per_qty']);
            $dbByPartExact = [];
            $dbByNameExact = [];
            $dbByPartNorm = [];
            $dbByNameNorm = [];

            foreach ($allProducts as $prod) {
                if ($prod->part_no) {
                    $dbByPartExact[$prod->part_no] = $prod;
                    $dbByPartNorm[strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $prod->part_no))] = $prod;
                }
                if ($prod->product_name) {
                    $dbByNameExact[$prod->product_name] = $prod;
                    $dbByNameNorm[strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $prod->product_name))] = $prod;
                }
            }

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $totalUpdated = 0;
            $sheetNames = $spreadsheet->getSheetNames();

            foreach ($sheetNames as $sheetName) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                if (!$sheet) continue;
                $highestRow = $sheet->getHighestRow();

                for ($r = 3; $r <= $highestRow; $r++) {
                    $itemCode = trim((string)$sheet->getCell('B' . $r)->getValue());
                    $itemName = trim((string)$sheet->getCell('C' . $r)->getValue());
                    if (empty($itemCode) && empty($itemName)) continue;

                    if (!empty($gradeId)) {
                        $matchedProduct = null;
                        if (!empty($itemCode) && isset($dbByPartExact[$itemCode])) {
                            $matchedProduct = $dbByPartExact[$itemCode];
                        }
                        if (!$matchedProduct && !empty($itemName) && isset($dbByNameExact[$itemName])) {
                            $matchedProduct = $dbByNameExact[$itemName];
                        }
                        if (!$matchedProduct && !empty($itemCode)) {
                            $normCode = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $itemCode));
                            if (isset($dbByPartNorm[$normCode])) {
                                $matchedProduct = $dbByPartNorm[$normCode];
                            }
                        }
                        if (!$matchedProduct && !empty($itemName)) {
                            $normName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $itemName));
                            if (isset($dbByNameNorm[$normName])) {
                                $matchedProduct = $dbByNameNorm[$normName];
                            }
                        }

                        if (!$matchedProduct || (string)$matchedProduct->product_grade_id !== (string)$gradeId) {
                            continue;
                        }
                    }

                    $sheet->setCellValue('D' . $r, $newRate);
                    $totalUpdated++;
                }
            }

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($filePath);

            cache()->flush();
            $syncResult = $this->perform_rate_sheet_sync($gradeId);

            $gradeName = '';
            if (!empty($gradeId)) {
                $gradeObj = ProductGrade::find($gradeId);
                $gradeName = $gradeObj ? " for Grade '{$gradeObj->name}'" : " for Selected Grade";
            }

            return response()->json([
                'status' => true,
                'message' => "Brass Scrap Rate successfully updated to ₹ " . number_format($newRate, 2) . "{$gradeName} across {$totalUpdated} items in Excel & {$syncResult['updated_count']} products in CRM DB!",
                'new_scrap_rate' => $newRate,
                'updated_count' => $totalUpdated
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function rate_sheet_download()
    {
        $filePath = Helper::getRateSheetPath();
        if (!empty($filePath) && file_exists($filePath)) {
            return response()->download($filePath, basename($filePath));
        }
        return redirect()->back()->withErrors('Rate sheet file not found.');
    }

    public function rate_sheet_upload(Request $request)
    {
        $request->validate([
            'rate_file' => 'required|file|mimes:xlsx,xls|max:20480'
        ]);

        try {
            $file = $request->file('rate_file');
            $targetPath = Helper::getRateSheetPath();
            
            if (file_exists($targetPath)) {
                $backupDir = storage_path('app/rate_sheet_backups');
                if (!file_exists($backupDir)) {
                    mkdir($backupDir, 0777, true);
                }
                copy($targetPath, $backupDir . '/sipl_rate_backup_' . date('Ymd_His') . '.xlsx');
            }

            $targetDir = public_path('uploads/rate_sheet');
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $origName = $file->getClientOriginalName();
            $ext = $file->getClientOriginalExtension();
            $baseName = pathinfo($origName, PATHINFO_FILENAME);
            $cleanName = Helper::make_slug($baseName) . '_' . date('Ymd_His') . '.' . $ext;
            $file->move($targetDir, $cleanName);

            try {
                $company = Company::find(2);
                if ($company) {
                    $company->rate_sheet = 'uploads/rate_sheet/' . $cleanName;
                    $company->save();
                }
            } catch (\Throwable $th) {}

            cache()->flush();

            if ($request->input('auto_sync', 1)) {
                $syncResult = $this->perform_rate_sheet_sync();
                return redirect()->route('products.rate-sheet.index')
                    ->withSuccess("New SIPL Rate Sheet uploaded & {$syncResult['updated_count']} products updated in CRM database successfully!");
            }

            return redirect()->route('products.rate-sheet.index')
                ->withSuccess("SIPL Rate Sheet uploaded successfully.");
        } catch (\Exception $e) {
            return redirect()->back()->withErrors("Upload failed: " . $e->getMessage());
        }
    }

    public function rate_sheet_sync(Request $request)
    {
        try {
            cache()->flush();
            $result = $this->perform_rate_sheet_sync();
            if ($request->ajax()) {
                return response()->json([
                    'status' => true,
                    'message' => "Successfully synchronized {$result['updated_count']} products from SIPL Rate Sheet!",
                    'data' => $result
                ]);
            }
            return redirect()->back()->withSuccess("Successfully synchronized {$result['updated_count']} products from SIPL Rate Sheet!");
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->withErrors("Sync failed: " . $e->getMessage());
        }
    }

    public function perform_rate_sheet_sync($gradeId = null)
    {
        $filePath = Helper::getRateSheetPath();
        if (!file_exists($filePath)) {
            throw new \Exception('Rate sheet file not found.');
        }

        $allProducts = Product::all(['id', 'product_name', 'part_no', 'product_grade_id', 'gross_wt', 'net_wt', 'grosse_per_qty']);
        $dbByPartExact = [];
        $dbByNameExact = [];
        $dbByPartNorm = [];
        $dbByNameNorm = [];

        foreach ($allProducts as $prod) {
            if ($prod->part_no) {
                $dbByPartExact[$prod->part_no] = $prod;
                $dbByPartNorm[strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $prod->part_no))] = $prod;
            }
            if ($prod->product_name) {
                $dbByNameExact[$prod->product_name] = $prod;
                $dbByNameNorm[strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $prod->product_name))] = $prod;
            }
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $updatedCount = 0;
        $notFound = [];
        $updatedList = [];
        $sheetNames = $spreadsheet->getSheetNames();

        foreach ($sheetNames as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (!$sheet) continue;
            $rows = $sheet->toArray();
            for ($i = 2; $i < count($rows); $i++) {
                $itemCode = trim((string)($rows[$i][1] ?? ''));
                $itemName = trim((string)($rows[$i][2] ?? ''));
                if (empty($itemCode) && empty($itemName)) continue;

                $rawGrossWeight = floatval(preg_replace('/[^0-9.]/', '', (string)($rows[$i][4] ?? 0)));
                $rawKgsIntoKg = floatval(preg_replace('/[^0-9.]/', '', (string)($rows[$i][8] ?? 0)));
                if ($rawGrossWeight <= 0) continue;

                $calcNetWt = round($rawGrossWeight / 144, 3);
                $calcGrossWt = ($calcNetWt > 0 && $rawKgsIntoKg > 0) ? round($calcNetWt * $rawKgsIntoKg, 3) : $calcNetWt;

                $product = null;
                if (!empty($itemCode) && isset($dbByPartExact[$itemCode])) {
                    $product = $dbByPartExact[$itemCode];
                }
                if (!$product && !empty($itemName) && isset($dbByNameExact[$itemName])) {
                    $product = $dbByNameExact[$itemName];
                }
                if (!$product && !empty($itemCode)) {
                    $normCode = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $itemCode));
                    if (isset($dbByPartNorm[$normCode])) {
                        $product = $dbByPartNorm[$normCode];
                    }
                }
                if (!$product && !empty($itemName)) {
                    $normName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $itemName));
                    if (isset($dbByNameNorm[$normName])) {
                        $product = $dbByNameNorm[$normName];
                    }
                }

                if ($product) {
                    if (!empty($gradeId) && (string)$product->product_grade_id !== (string)$gradeId) {
                        continue;
                    }

                    $product->net_wt = number_format($calcNetWt, 3, '.', '');
                    $product->gross_wt = number_format($calcGrossWt, 3, '.', '');
                    $product->grosse_per_qty = 144;
                    $product->save();
                    $updatedCount++;
                    $updatedList[] = [
                        'sheet' => $sheetName,
                        'row' => $i + 1,
                        'code' => $itemCode,
                        'name' => $itemName,
                        'product_id' => $product->id,
                        'net_wt' => $product->net_wt,
                        'gross_wt' => $product->gross_wt
                    ];
                } else {
                    $notFound[] = [
                        'sheet' => $sheetName,
                        'row' => $i + 1,
                        'code' => $itemCode,
                        'name' => $itemName
                    ];
                }
            }
        }

        return [
            'updated_count' => $updatedCount,
            'not_found' => $notFound,
            'updated_list' => $updatedList
        ];
    }

    private function findDbProduct($itemCode, $itemName)
    {
        if (!empty($itemCode)) {
            $p = Product::where('part_no', $itemCode)->first();
            if ($p) return $p;
        }
        if (!empty($itemName)) {
            $p = Product::where('product_name', $itemName)->first();
            if ($p) return $p;
        }
        if (!empty($itemCode)) {
            $normCode = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $itemCode));
            $all = Product::whereNotNull('part_no')->get(['id', 'part_no', 'product_name', 'gross_wt', 'net_wt']);
            foreach ($all as $item) {
                if (strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $item->part_no)) === $normCode) {
                    return $item;
                }
            }
        }
        if (!empty($itemName)) {
            $normName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $itemName));
            $all = Product::whereNotNull('product_name')->get(['id', 'part_no', 'product_name', 'gross_wt', 'net_wt']);
            foreach ($all as $item) {
                if (strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $item->product_name)) === $normName) {
                    return $item;
                }
            }
        }
        return null;
    }

    /**
     * Reflect updated product weights back into sipl rate.xlsx Excel sheet
     *
     * @param Product $product
     * @return bool
     */
    public function syncProductBackToExcel($product)
    {
        try {
            $filePath = Helper::getRateSheetPath();
            if (!file_exists($filePath)) {
                return false;
            }

            $partNo = trim((string)($product->part_no ?? ''));
            $productName = trim((string)($product->product_name ?? ''));
            if (empty($partNo) && empty($productName)) {
                return false;
            }

            $grossWt = floatval($product->gross_wt ?? 0);
            $netWt = floatval($product->net_wt ?? 0);
            if ($grossWt <= 0 && $netWt <= 0) {
                return false;
            }

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $modified = false;

            $normPartNo = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $partNo));
            $normProductName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $productName));
            $sheetNames = $spreadsheet->getSheetNames();

            foreach ($sheetNames as $sheetName) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                if (!$sheet) continue;

                $highestRow = $sheet->getHighestRow();

                for ($r = 3; $r <= $highestRow; $r++) {
                    $itemCode = trim((string)$sheet->getCell('B' . $r)->getValue());
                    $itemName = trim((string)$sheet->getCell('C' . $r)->getValue());
                    if (empty($itemCode) && empty($itemName)) continue;

                    $match = false;
                    if (!empty($partNo) && $itemCode === $partNo) {
                        $match = true;
                    } elseif (!empty($productName) && $itemName === $productName) {
                        $match = true;
                    } elseif (!empty($normPartNo) && strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $itemCode)) === $normPartNo) {
                        $match = true;
                    } elseif (!empty($normProductName) && strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $itemName)) === $normProductName) {
                        $match = true;
                    }

                    if ($match) {
                        // Update Column E (GROSS WEIGHT = Net Weight per piece * 144)
                        if ($netWt > 0) {
                            $calcGrossWeightColE = round($netWt * 144, 2);
                            $sheet->setCellValue('E' . $r, $calcGrossWeightColE);
                            $modified = true;
                        }

                        // Update Column I (KGS INTO KG = Gross Weight / Net Weight)
                        if ($grossWt > 0 && $netWt > 0) {
                            $calcKgsIntoKgColI = round($grossWt / $netWt, 2);
                            $sheet->setCellValue('I' . $r, $calcKgsIntoKgColI);
                            $modified = true;
                        }

                        \Log::info("Synced Product #{$product->id} ({$partNo}) back to sipl rate.xlsx row {$r} in sheet '{$sheetName}': Net Wt={$netWt} -> Col E, Gross Wt={$grossWt} -> Col I");
                        break;
                    }
                }
            }

            if ($modified) {
                $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($filePath);
                cache()->flush();
                return true;
            }

            return false;
        } catch (\Exception $e) {
            \Log::warning("Failed to sync Product #{$product->id} back to sipl rate.xlsx: " . $e->getMessage());
            return false;
        }
    }
}
