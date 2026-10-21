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
                        {{-- Company --}}
                        <div class="col-md-4 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Select Company <span class="text-danger">*</span></label>
                                <select id="company_id"
                                    class="form-control select2 search_by_company @error('company_id') is-invalid @enderror"
                                    name="company_id"
                                    data-selectedCompanyId="{{ old('company_id', $edit->company_id ?? '') }}">
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

                    {{-- Team Person Dropdown (already present) --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Team Person <span class="text-danger">*</span></label>
                            <select id="team_person_ids"
                                class="form-control select2 team_person_select @error('team_person_id') is-invalid @enderror"
                                name="team_person_id"
                                data-selectedids="{{ old('team_person_id', $edit->team_person_id ?? '') }}">
                                <option value="">Select Team Person</option>
                            </select>
                            @error('team_person_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Expense Category --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Expense Category <span class="text-danger">*</span></label>
                            <select id="expense_category_id"
                                class="form-control select2 @error('expense_category_id') is-invalid @enderror"
                                name="expense_category_id"
                                data-selectedcategoryid="{{ old('expense_category_id', $edit->expense_category_id ?? '') }}">
                                <option value="">Select Expense Category</option>
                            </select>
                            @error('expense_category_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Expense SubCategory --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Expense SubCategory <span class="text-danger">*</span></label>
                            <select id="expense_subcategory_id"
                                class="form-control select2 @error('expense_subcategory_id') is-invalid @enderror"
                                name="expense_subcategory_id"
                                data-selectedsubcategoryid="{{ old('expense_subcategory_id', $edit->expense_subcategory_id ?? '') }}">
                                <option value="">Select Expense SubCategory</option>
                            </select>
                            @error('expense_subcategory_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-4 col-sm-12 mb-2">

                        {{-- <div class="col-md-4"> --}}
                        <label class="form-label">Date</label>
                        <input type="text" id="date_display" class="form-control @error('date') is-invalid @enderror"
                            value="{{ old('date', isset($edit->date) ? date('d-m-Y', strtotime($edit->date)) : date('d-m-Y')) }}"
                            placeholder="DD-MM-YYYY" readonly />

                        <input type="hidden" name="date" id="date"
                            value="{{ old('date', isset($edit->date) ? date('Y-m-d', strtotime($edit->date)) : date('Y-m-d')) }}" />

                    </div>

                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Request Amount <span class="text-danger">*</span></label>
                            <input id="req_amount" type="number" step="0.01"
                                class="form-control @error('req_amount') is-invalid @enderror" name="req_amount"
                                value="{{ isset($edit) ? old('req_amount', $edit->req_amount) : old('req_amount') }}"
                                data-oldValue="{{ isset($edit) ? old('req_amount', $edit->req_amount) : old('req_amount') }}"
                                placeholder="Enter Request Amount">
                            @error('req_amount')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Attachment</label>
                            <div class="input-group">
                                <input type="file" name="image" id="image"
                                    class="form-control @error('image') is-invalid @enderror">
                                @if (isset($edit) && $edit?->attachment && $edit?->attachment_url)
                                    <a href="{{ $edit?->attachment_url ?? '#' }}" target="_blank" class="input-group-text"
                                        for="inputGroupFile02">View File</a>
                                @endif
                            </div>
                            @error('image')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Remark</label>
                            <textarea id="remark" class="form-control autosize @error('remark') is-invalid @enderror" name="remark"
                                rows="1" autocomplete="remark" placeholder="Enter remark">{{ isset($edit) ? $edit->remark : old('remark') }}</textarea>
                            @error('remark')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/autosize@4.0.2/dist/autosize.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getTeamPerson')
    @include('utils.getExpenseCategory')
    @include('utils.getTeamPersonByCategory')
    <script>
        flatpickr("#date_display", {
            dateFormat: "d-m-Y",
            defaultDate: "{{ old('date', isset($edit->date) ? date('d-m-Y', strtotime($edit->date)) : '') }}",
            maxDate: "today",
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length > 0) {
                    const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                    document.getElementById('date').value = ymd;
                }
            }
        });
    </script>




    <script>
        $(document).ready(function() {
            $('#company_id, #team_person_ids, #expense_category_id, #expense_subcategory_id').on('change',
                function() {
                    $('#req_amount').val('');
                    if ($('#req_amount').attr('data-oldValue')) {
                        $('#req_amount').val($('#req_amount').attr('data-oldValue'));
                    }
                });
        });

        $(document).on("change", "#expense_subcategory_id", function() {
            let instanceItreableData = $(this).find("option:selected").attr('data-categorydata');
            if (!instanceItreableData) {
                // toastr.error("Data not found");
                // $("#image").removeAttr("required"); // Make not required if no data
                // return;
            }

            instanceItreableData = JSON.parse(instanceItreableData);
            // console.log("Conole - log 242", instanceItreableData);

            // if (instanceItreableData.is_image_required == 1) {
            //     $(".image-upload-field").show();
            // } else {
            //     $(".image-upload-field").hide();
            // }
            if (instanceItreableData.expense_type === "Food" && instanceItreableData.fix_amount) {
                $('#req_amount').val(instanceItreableData.fix_amount).prop('readonly', true).removeAttr('min')
                    .removeAttr('max');
                minAmount = 0;
                maxAmount = 0;
            } else if (instanceItreableData.expense_type === "General") {
                minAmount = parseFloat(instanceItreableData.min_amount) || 0;
                maxAmount = parseFloat(instanceItreableData.max_amount) || 999999;

                $('#req_amount').val('').prop('readonly', false).attr('min', minAmount).attr('max', maxAmount);

                perKmRate = 0;
                // } else if (instanceItreableData.expense_type === "KM" && instanceItreableData.per_km_rate) {
                //     perKmRate = parseFloat(instanceItreableData.per_km_rate);
                //     $('#req_amount')
                //         .val('')
                //         .prop('readonly', false)
                //         .removeAttr('min')
                //         .removeAttr('max');
                //     minAmount = 0;
                //     maxAmount = 0;
            } else {
                $('#req_amount').val('')
                    .prop('readonly', false)
                    .removeAttr('min')
                    .removeAttr('max');
                minAmount = 0;
                maxAmount = 0;
            }

            // Handle the case where there is no category data initially
            let amountFromDB = "{{ old('req_amount', isset($edit) ? $edit->req_amount : '') }}";
            if (amountFromDB) {
                $('#req_amount').val(amountFromDB);
            }
            console.log("L-264", amountFromDB);
            if ($('#req_amount').attr('data-oldValue')) {
                $('#req_amount').val($('#req_amount').attr('data-oldValue'));
            }
        });
        $('form').on('submit', function(e) {
            $('#req_amount').trigger('input'); // trigger validation logic

            if (!isAmountValid) {
                e.preventDefault(); // just block submission, don't show duplicate toastr
            }
        });

        $(document).on("input change", "#req_amount", function() {
            let val = parseFloat($(this).val());
            isAmountValid = true; // Reset at start

            // General type validation
            if (minAmount && maxAmount && (val < minAmount || val > maxAmount)) {
                toastr.error(`Request Amount must be between ${minAmount} and ${maxAmount}`);
                $(this).addClass('is-invalid');
                isAmountValid = false;
                return;
            }

            // KM type validation
            if (perKmRate > 0 && (val % perKmRate !== 0)) {
                toastr.error(`Request  Amount must be a Enter ${perKmRate}`);
                $(this).addClass('is-invalid');
                isAmountValid = false;
            } else {
                $(this).removeClass('is-invalid');
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
    <script>
        $(document).ready(function() {
            autosize($('.autosize'));
        });
    </script>
@endpush
