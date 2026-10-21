@extends('software.layout.app')

@php
    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;

@endphp
@section('title', $page_title)

@section('content')
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
        <div class="card-body">
            <form
                action="{{ isset($edit) && $edit?->id ? route($route . '.update', [$edit?->id]) : route($route . '.store') }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                @isset($edit)
                    @method('PUT')
                @endisset

                <div class="row">
                    @if (!$company_id)
                        {{-- Our Company --}}
                        <div class="col-md-4 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Select Company <span class="text-danger">*</span></label>
                                <select id="company_id"
                                    class="form-control select2 search_by_company @error('company_id') is-invalid @enderror"
                                    name="company_id"
                                    data-selectedCompanyId="{{ old('company_id') ?? ($edit->company_id ?? '') }}"
                                    data-parent_type_id="all">
                                    <option value="">Select Company</option>
                                </select>

                                @error('company_id')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    @else
                        <input type="hidden" class="form-control search_by_company" name="company_id"
                            value="{{ $company_id }}" />
                    @endif

                    {{-- Expense Category --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Select Expense Category <span class="text-danger">*</span></label>
                            <select id="expense_category_id"
                                class="form-control search_by_expense_category select2 @error('expense_category_id') is-invalid @enderror"
                                name="expense_category_id"
                                data-selectedCategoryId="{{ old('expense_category_id', isset($edit) ? $edit->expense_category_id : '') }}">
                                <option value="">Select Expense Category</option>
                                {{-- Options will be populated dynamically --}}
                            </select>

                            @error('expense_category_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>




                    {{-- Expense SubCategory Name --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Expense SubCategory Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" class="form-control @error('name') is-invalid @enderror"
                                name="name" value="{{ old('name', isset($edit) && $edit ? $edit->name : '') }}"
                                placeholder="Enter expense category name">
                            @error('name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    {{-- Team Person --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Team Person <span class="text-danger">*</span></label>
                            <select id="team_person_ids"
                                class="form-control search_by_team_person select2 @error('team_person_ids') is-invalid @enderror"
                                name="team_person_ids[]" multiple placeholder="Select Team Person"
                                data-placeholder="Select Team Person"
                                data-selectedids="{{ implode(',', old('team_person_ids', isset($edit) ? explode(',', $edit->team_person_ids) : [])) }}">
                            </select>

                            @error('team_person_ids')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>



                    {{-- Expense Type --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Expense Type <span class="text-danger">*</span></label>
                            <select id="expense_type" onchange="toggleExpenseFields()"
                                class="form-control select2 @error('expense_type') is-invalid @enderror"
                                name="expense_type">
                                <option value="">Select Expense Type</option>

                                @foreach ($expense_type as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ old('expense_type', $edit->expense_type ?? '') == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            @error('expense_type')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Min  Amount --}}
                    <div class="col-md-4 col-sm-12 mb-2" id="min_amount" style="display:none;">
                        <div class="form-group">
                            <label class="form-label">Min Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('min_amount') is-invalid @enderror"
                                name="min_amount" value="{{ old('min_amount', isset($edit) ? $edit->min_amount : '') }}"
                                placeholder="Enter minimum amount">
                            @error('min_amount')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Max Amount --}}
                    <div class="col-md-4 col-sm-12 mb-2" id="max_amount" style="display:none;">
                        <div class="form-group">
                            <label class="form-label">Max Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('max_amount') is-invalid @enderror"
                                name="max_amount" value="{{ old('max_amount', isset($edit) ? $edit->max_amount : '') }}"
                                placeholder="Enter maximum amount">
                            @error('max_amount')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Per KM Rate --}}
                    <div class="col-md-4 col-sm-12 mb-2" id="per_km_rate" style="display: none;">
                        <div class="form-group">
                            <label class="form-label">Per KM Rate <span class="text-danger">*</span></label>
                            <input type="number" step="0.01"
                                class="form-control @error('per_km_rate') is-invalid @enderror" name="per_km_rate"
                                value="{{ old('per_km_rate', isset($edit) ? $edit->per_km_rate : '') }}"
                                placeholder="Enter per KM rate">
                            @error('per_km_rate')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    {{-- Fix Amount --}}
                    <div class="col-md-4 col-sm-12 mb-2" id="fix_amount" style="display: none;">
                        <div class="form-group">
                            <label class="form-label">Fix Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('fix_amount') is-invalid @enderror"
                                name="fix_amount" value="{{ old('fix_amount', isset($edit) ? $edit->fix_amount : '') }}"
                                placeholder="Enter amount">
                            @error('fix_amount')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    {{-- From Time --}}
                    <div class="col-md-4 col-sm-12 mb-2" id="from_time" style="display: none;">
                        <div class="form-group">
                            <label class="form-label">From Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('from_time') is-invalid @enderror"
                                name="from_time" value="{{ old('from_time', isset($edit) ? $edit->from_time : '') }}">
                            @error('from_time')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- To Time --}}
                    <div class="col-md-4 col-sm-12 mb-2" id="to_time" style="display: none;">
                        <div class="form-group">
                            <label class="form-label">To Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('to_time') is-invalid @enderror"
                                name="to_time" value="{{ old('to_time', isset($edit) ? $edit->to_time : '') }}">
                            @error('to_time')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>



                    {{-- Status --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select class="form-control select2 @error('status') is-invalid @enderror" name="status"
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

                    {{-- is_image_required Checkbox --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Is Image Required?</label><br>
                            <input type="checkbox" name="is_image_required" id="is_image_required" value="1"
                                {{ old('is_image_required', isset($edit) && $edit->is_image_required ? 'checked' : '') }}>
                        </div>
                    </div>

                    {{-- Divider --}}
                    <div class="divider">
                        <hr />
                    </div>

                    {{-- Submit  --}}
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
    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getExpenseCategory')
    @include('utils.getTeamPerson')

    <script>
        $(document).ready(function() {
            let isRequired = $('#is_image_required').val();
            if (isRequired == 1) {
                $('#image_upload_section').show();
            } else {
                $('#image_upload_section').hide();
            }
        });
        $(document).ready(function() {
            toggleExpenseFields();


            $('#expense_type').change(function() {
                clearExpenseFields();
                toggleExpenseFields();
            });

            $('#is_image_required').change(function() {

            });
        });

        function toggleExpenseFields() {
            var expenseType = $('#expense_type').val();

            $('#min_amount').hide();
            $('#max_amount').hide();
            $('#per_km_rate').hide();
            $('#fix_amount').hide();
            $('#from_time').hide();
            $('#to_time').hide();

            if (expenseType === 'General') {
                $('#min_amount').show();
                $('#max_amount').show();
            } else if (expenseType === 'KM') {
                $('#per_km_rate').show();
            } else if (expenseType === 'Food') {
                $('#fix_amount').show();
                $('#from_time').show();
                $('#to_time').show();
            }
        }

        function clearExpenseFields() {

            $('#min_amount input').val('');
            $('#max_amount input').val('');
            $('#per_km_rate input').val('');
            $('#fix_amount input').val('');
            $('#from_time input').val('');
            $('#to_time input').val('');
        }
    </script>
@endpush
