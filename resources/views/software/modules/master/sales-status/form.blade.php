@extends('software.layout.app')

@php
    use App\Models\Company;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;
    // dd($modules);
@endphp
@section('title', $page_title)


@section('content')
<style>
    .hide{
    display: none;
    }
</style>
   <div class="px-1">
    <div class="d-flex flex-column flex-lg-row justify-content-lg-between align-items-start align-items-lg-center">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [
                ['title' => $page_title, 'url' => route($route . '.index')],
                ['title' => (isset($edit) && $edit?->id) ? "Edit ".$page_title : "Create ".$page_title , 'url' => ''],
            ],
        ])
        <a class="btn btn-primary waves-effect waves-light text-white" href="{{ url()->previous() }}">
            <i class="menu-icon ti ti-chevrons-left"></i> Back
        </a>
    </div>
</div>
    <div class="card my-3 mb-4">
        {{-- <h5 class="card-header"></h5> --}}
        <div class="card-body">
            <form
                action="{{ isset($edit) && $edit?->id ? route($route . '.update', [$edit?->id]) : route($route . '.store') }}"
                method="POST" enctype="multipart/form-data">

                <input type="hidden" id="db_parent_status_id" name="db_parent_status_id" value="{{ $edit->parent_status_id ?? '' }}">
                @csrf
                @isset($edit)
                    @method('PUT')
                @endisset
                <div class="row">
                    @php
                        $companies = Company::pluck('company_name', 'id'); // [id => name]
                        $selectedCompanyId = old('company_id', $edit->company_id ?? '');
                    @endphp

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Select Company <span class="text-danger">*</span></label>
                            <select id="company_id" class="form-control select2 @error('company_id') is-invalid @enderror"
                                name="company_id" required>
                                <option value="">Select Company</option>
                                @foreach ($companies as $id => $name)
                                    <option value="{{ $id }}" {{ $selectedCompanyId == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach

                            </select>

                            @error('company_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Sales Type <span class="text-danger">*</span></label>
                            <select id="salestype" name="salestype" class="form-control select2 @error('salestype') is-invalid @enderror" required>
                                <option value="">Select Sales Type</option>
                                <option value="Quotation"
                                    {{ old('salestype') == 'Quotation' || (isset($edit) && $edit->salestype == 'Quotation') ? 'selected' : '' }}>
                                    Quotation</option>
                                <option value="Sales Orders"
                                    {{ old('salestype') == 'Sales Orders' || (isset($edit) && $edit->salestype == 'Sales Orders') ? 'selected' : '' }}>
                                    Sales Orders</option>
                                <option value="Dispatch Orders"
                                    {{ old('salestype') == 'Dispatch Orders' || (isset($edit) && $edit->salestype == 'Dispatch Orders') ? 'selected' : '' }}>
                                    Dispatch Orders</option>
                                <option value="Packing Slip"
                                    {{ old('salestype') == 'Packing Slip' || (isset($edit) && $edit->salestype == 'Packing Slip') ? 'selected' : '' }}>
                                    Packing Slip</option>
                                <option value="Invoice"
                                    {{ old('salestype') == 'Invoice' || (isset($edit) && $edit->salestype == 'Invoice') ? 'selected' : '' }}>
                                    Invoice</option>
                                <option value="Purchase Order"
                                    {{ old('salestype') == 'Purchase Order' || (isset($edit) && $edit->salestype == 'Purchase Order') ? 'selected' : '' }}>
                                    Purchase Order</option>
                                <option value="Job Work Issue"
                                    {{ old('salestype') == 'Job Work Issue' || (isset($edit) && $edit->salestype == 'Job Work Issue') ? 'selected' : '' }}>
                                    Job Work Issue</option>
                                <option value="GRN"
                                    {{ old('salestype') == 'GRN' || (isset($edit) && $edit->salestype == 'GRN') ? 'selected' : '' }}>
                                    GRN</option>
                            </select>

                            @error('salestype')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Status Name <span class="text-danger">*</span></label>
                            <input id="statusname" type="text"
                                class="form-control @error('statusname') is-invalid @enderror" name="statusname"
                                value="{{ isset($edit?->statusname) ? $edit?->statusname : old('statusname') }}"
                                autocomplete="off" placeholder="Enter Status Name" required>

                            @error('statusname')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label"></label>

                            <div class="btn-group w-100" role="group" aria-label="Button group with nested dropdown">

                                <div class="btn-group w-100" role="group">
                                    <button id="btnGroupDrop1" type="button" class="btn btn-primary dropdown-toggle w-100" data-bs-toggle="dropdown" aria-expanded="false">
                                        {{-- {{ isset($edit?->statusslug) ? $edit?->statusslug : 'Select Slug' }} --}}
                                        @php
                                            $selectedSlug = $edit->statusslug ?? null;
                                            $salestype = $edit->salestype ?? null;

                                            $label = 'Select Slug';

                                            if ($selectedSlug && $salestype) {
                                                if ($salestype === 'Quotation' && isset($modules['quotation_status_slug'][$selectedSlug])) {
                                                    $label = $modules['quotation_status_slug'][$selectedSlug];
                                                } elseif ($salestype === 'Sales Orders' && isset($modules['sales_order_status_slug'][$selectedSlug])) {
                                                    $label = $modules['sales_order_status_slug'][$selectedSlug];
                                                } elseif ($salestype === 'Dispatch Orders' && isset($modules['dispatch_order_status_slug'][$selectedSlug])) {
                                                    $label = $modules['dispatch_order_status_slug'][$selectedSlug];
                                                } elseif ($salestype === 'Packing Slip' && isset($modules['packing_slip_status_slug'][$selectedSlug])) {
                                                    $label = $modules['packing_slip_status_slug'][$selectedSlug];
                                                } elseif ($salestype === 'Purchase Order' && isset($modules['purchase_order_status_slug'][$selectedSlug])) {
                                                    $label = $modules['purchase_order_status_slug'][$selectedSlug];
                                                } elseif ($salestype === 'Job Work Issue' && isset($modules['job_work_issue_status_slug'][$selectedSlug])) {
                                                    $label = $modules['job_work_issue_status_slug'][$selectedSlug];
                                                }elseif ($salestype === 'Invoice' && isset($modules['invoice_status_slug'][$selectedSlug])) {
                                                    $label = $modules['invoice_status_slug'][$selectedSlug];
                                                }elseif ($salestype === 'GRN' && isset($modules['grn_status_slug'][$selectedSlug])) {
                                                    $label = $modules['grn_status_slug'][$selectedSlug];
                                                }
                                            }
                                        @endphp

                                        {{ $label }}
                                    </button>
                                    <ul class="dropdown-menu status_slug_dropdown" aria-labelledby="btnGroupDrop1">
                                        @foreach($modules['quotation_status_slug'] as $key => $label)
                                            <li class="{{ (isset($edit) && $edit->salestype == 'Quotation') ? '' : 'hide' }}" data-status="Quotation">
                                                <a class="dropdown-item status-option" href="javascript:;" data-value="{{ $key }}">{{ $label }}</a>
                                            </li>
                                        @endforeach
                                        @foreach($modules['sales_order_status_slug'] as $key => $label)
                                            <li class="{{ (isset($edit) && $edit->salestype == 'Sales Orders') ? '' : 'hide' }}" data-status="Sales Orders">
                                                <a class="dropdown-item status-option" href="javascript:;" data-value="{{ $key }}">{{ $label }}</a>
                                            </li>
                                        @endforeach
                                        @foreach($modules['dispatch_order_status_slug'] as $key => $label)
                                            <li class="{{ (isset($edit) && $edit->salestype == 'Dispatch Orders') ? '' : 'hide' }}" data-status="Dispatch Orders">
                                                <a class="dropdown-item status-option" href="javascript:;" data-value="{{ $key }}">{{ $label }}</a>
                                            </li>
                                        @endforeach
                                        @foreach($modules['packing_slip_status_slug'] as $key => $label)
                                            <li class="{{ (isset($edit) && $edit->salestype == 'Packing Slip') ? '' : 'hide' }}" data-status="Packing Slip">
                                                <a class="dropdown-item status-option" href="javascript:;" data-value="{{ $key }}">{{ $label }}</a>
                                            </li>
                                        @endforeach
                                        @foreach($modules['purchase_order_status_slug'] as $key => $label)
                                            <li class="{{ (isset($edit) && $edit->salestype == 'Purchase Order') ? '' : 'hide' }}" data-status="Purchase Order">
                                                <a class="dropdown-item status-option" href="javascript:;" data-value="{{ $key }}">{{ $label }}</a>
                                            </li>
                                        @endforeach
                                        @foreach($modules['job_work_issue_status_slug'] as $key => $label)
                                            <li class="{{ (isset($edit) && $edit->salestype == 'Job Work Issue') ? '' : 'hide' }}" data-status="Job Work Issue">
                                                <a class="dropdown-item status-option" href="javascript:;" data-value="{{ $key }}">{{ $label }}</a>
                                            </li>
                                        @endforeach
                                        @foreach($modules['invoice_status_slug'] as $key => $label)
                                            <li class="{{ (isset($edit) && $edit->salestype == 'Invoice') ? '' : 'hide' }}" data-status="Invoice">
                                                <a class="dropdown-item status-option" href="javascript:;" data-value="{{ $key }}">{{ $label }}</a>
                                            </li>
                                        @endforeach
                                        @foreach($modules['grn_status_slug'] as $key => $label)
                                            <li class="{{ (isset($edit) && $edit->salestype == 'GRN') ? '' : 'hide' }}" data-status="GRN">
                                                <a class="dropdown-item status-option" href="javascript:;" data-value="{{ $key }}">{{ $label }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>

                            {{-- Hidden input to capture selected value --}}
                            <input type="hidden" name="statusslug" id="statusslug"
                                value="{{ isset($edit?->statusslug) ? $edit?->statusslug : old('statusslug') }}" required>

                            @error('statusslug')
                                <span class="invalid-feedback d-block">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Status Color <span class="text-danger">*</span></label>
                            <input id="status_color" type="color"
                                class="form-control form-control-color w-100 @error('status_color') is-invalid @enderror"
                                name="status_color"
                                value="{{ isset($edit?->status_color) ? $edit?->status_color : old('status_color', '#000000') }}"
                                title="Choose a color" required>

                            @error('status_color')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select class="form-control select2 w-100 @error('status') is-invalid @enderror" name="status"
                                required>
                                <option disabled selected>Select Status</option>
                                @foreach (['active', 'inactive'] as $status)
                                    <option value="{{ $status }}"
                                        @if (isset($edit)) @if ($edit->status == $status) {{ 'selected' }} @endif
                                    @else @if (old('status', 'active') == $status) {{ 'selected' }} @endif @endif> {{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Parent Status </label>
                            <select class="form-control select2 w-100 @error('parent_status_id') is-invalid @enderror" name="parent_status_id"
                                 id="parent_status_id">
                                <option disabled selected>Select Parent Status</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2 col-sm-12">
                        <div class="form-group">
                            <input type="hidden" name="is_redirect" value="0">
                            <input type="checkbox" name="is_redirect" id="is_redirect" value="1" class="form-check-input" {{ isset($edit) && $edit->is_redirect == 1 ? 'checked' : '' }}>
                            <label class="form-label">Is Redirect? </label>
                        </div>
                    </div>

                    <div class="col-md-2 col-sm-12">
                        <div class="form-group">
                            <input type="hidden" name="is_revise" value="0">
                            <input type="checkbox" name="is_revise" id="is_revise" value="1" class="form-check-input" {{ isset($edit) && $edit->is_revise == 1 ? 'checked' : '' }}>
                            <label class="form-label">Is Revise? </label>
                        </div>
                    </div>

                    <div class="col-md-2 col-sm-12">
                        <div class="form-group">
                            <input type="hidden" name="is_repeat" value="0">
                            <input type="checkbox" name="is_repeat" id="is_repeat" value="1" class="form-check-input" {{ isset($edit) && $edit->is_repeat == 1 ? 'checked' : '' }}>
                            <label class="form-label">Is Repeat? </label>
                        </div>
                    </div>

                    <div class="divider">
                        <hr />
                    </div>

                    <div class="col-md-12 text-center">
                        <button type="submit" class="btn btn-success mt-1 mb-1">
                            {{ isset($edit) ? 'Update' : 'Submit' }}
                        </button>
                        <a href="{{ route($route . '.index') }}" class="btn btn-danger mt-1 mb-1">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('page_scripts')
@include('utils.getSalesStatus')
<script>
$(document).ready(function () {
    $('.status-option').on('click', function () {
        var selectedValue = $(this).data('value');
        var selectedText = $(this).text().trim();

        $('#statusslug').val(selectedValue);
        $('#btnGroupDrop1').text(selectedText);
    });
});
</script>
@endpush
