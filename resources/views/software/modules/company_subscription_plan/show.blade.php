@extends('software.layout.app')

@php
$i = 0;
$page_title = isset($modules['title']) ? $modules['title'] : null;
$folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
$route = isset($modules['route']) ? $modules['route'] : null;
$company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
$authLoginUserDetail = isset($modules['authLoginUserDetail']) ? $modules['authLoginUserDetail'] : null;
$loginUserId = isset($authLoginUserDetail?->id) ? $authLoginUserDetail?->id : null;
$parent_type_id = isset($modules['parent_type_id']) ? $modules['parent_type_id'] : null;
@endphp

@section('title', $page_title)

@section('page_leavel_style')
<link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
@endsection

@section('content')
<div class="d-flex justify-content-lg-between px-1">
    @include('software.inlcudes.breadcrumb', [
        'breadcrumbArray' => [['title' => $page_title, 'url' => '']],
        'route' => $route,
        'show_add_btn' => false,
        'show_filter_btn' => true,
        'show_back_btn' => false,
    ])
</div>
<div class="row my-3">
    <div class="col-md-12 mb-5" id="filter_section">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Filter by Name</label>
                        <input type="search" class="form-control search" name="search" placeholder="search..." autofocus>
                    </div>
                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Filter by Plan</label>
                            <select id="plan_id" name="plan_id"
                                class="form-control search_by_plan select2 select_filter"
                                data-append="search_by_plan">
                                <option value="">Filter by Plan</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 mb-2">
                        <label class="form-label">Filter by Plan Expire Date</label>
                        <input type="text" name="plan_date" class="form-control my_daterangepicker table_filter"
                            value="" placeholder="Filter by date range">
                    </div>
                    <div class="col-md-3 col-sm-12">
                        <label for="subscription_status_id" class="form-label">Filter by Status</label>
                        <select id="subscription_status_id" name="subscription_status_id" class="form-select select2 select_filter">
                            <option value="all">Select Subscription Status</option>
                            <option value="active">Active</option>
                            <option value="expire">Expire</option>
                        </select>
                    </div>
                    <div class="col-md-1 text-center">
                        <button type="button" title="Cilory Filter" id="cilory_filter"
                            class="btn btn-outline-danger btn-icon ms-75 me-75 mt-4"><i class="ti ti-x"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-datatable text-nowrap mt-3">
                <div class="card-datatable table-responsive">
                    <table id="yajra-datatables" class="dt-responsive table table-hover">
                        @if (isset($columns))
                            <thead>
                                <tr>
                                    @foreach ($columns as $item)
                                        <th class="{{ $item?->className ?? '' }}">
                                            {{ ucfirst($item?->td_label ?? $item?->name) ?? '' }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="add_days_subscription_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="modalCenterTitle">Add days on Subscription Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form name="AddDaysSubsriptionForm" id="AddDaysSubsriptionForm" method="POST">
                    @csrf
                    <input type="hidden" name="company_id" id="company_id">
                    <input type="hidden" name="subscription_id" id="subscription_id">
                    <input type="hidden" name="plan_id" id="plan_id">

                    <div class="row g-4">
                        <div class="col mb-4">
                            <label for="company_name" class="form-label">Company Name <span class="text-danger">*</span></label>
                            <input type="text" id="company_name" name="company_name" class="form-control required" placeholder="Company Name" disabled="">
                        </div>

                        <div class="col mb-4">
                            <label for="plan_name" class="form-label">Plan Name <span class="text-danger">*</span></label>
                            <input id="plan_name" name="plan_name" class="form-control required" placeholder="Plan Name" value="" disabled="">
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col mb-4">
                            <label for="add_days" class="form-label">Add days</label>
                            <div class="input-group">
                                <input type="number" name="add_days" value="" class="form-control required @error('add_days') is-invalid @enderror" placeholder="Enter Add Days">
                                <span class="input-group-text">days</span>
                            </div>
                        </div>
                        <div class="col mb-4">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" id="add_days_subscription_submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page_leavel_script')
<!-- Data tables -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endsection

@push('page_scripts')

@include('utils.getPlans')

<script type="text/javascript">
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $(function() {
        var picker = $('.my_daterangepicker').data('daterangepicker');
        if (picker) {
            picker.maxDate = false;

            var startDate = moment().startOf('month');
            var endDate = moment().add(2, 'year').endOf('year');

            picker.setStartDate(startDate);
            picker.setEndDate(endDate);
            picker.callback(startDate, endDate, 'Initial range');
        }
    });

    var dtable = null;
    $(document).ready(function() {

        dtable = $('#yajra-datatables').DataTable({
            processing: true,
            serverSide: true,
            dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center"<"ps-3"l>i<"pe-4"p>>',
            order: [[0, 'DESC']],
            ajax: {
                "url": "{{ route($route . '.show', $id) }}",
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                type: "GET",
                data: function(data) {
                    data.search = $('input[name="search"]').val();
                    data.filter_plan = $('select[name="plan_id"] option:selected').val();
                    data.filter_plan_date = $('input[name="plan_date"]').val().replace(' - ', ' to ');
                    data.filter_subscription_status_id = $('select[name="subscription_status_id"] option:selected').val();
                },
            },
            columns: {!! isset($columns) ? json_encode($columns) : [] !!},
            createdRow: function (row, data, dataIndex) {
                $(row).attr('data-company_id', data.company_id);
                $(row).attr('data-plan_id', data.plan?.id);
                $(row).attr('data-subscription_id', data.id);
            },
            language: {
                searchPlaceholder: 'Search...',
            }
        });
    });

    $(document).on('change', '.select_filter, .table_filter', function(event) {
        event.preventDefault();
        if (dtable) {
            dtable.draw();
        }
    });

    $('input[name="search"]').keyup(function() {
        dtable.draw();
    });

    $("#cilory_filter").click(function() {
        $('.select_filter').val(null).trigger('change');
        $('#status_filter').val('all').trigger('change');
        $('.search').val('');
        $('#filter_by_date').val('');
        $("#subscription_status_id").html("<option value=''>Select Sales Status</option>");

        var picker = $('.my_daterangepicker').data('daterangepicker');
        if(picker){
            picker.maxDate = false;
            let startOfMonth = moment().startOf('month');
            let today = moment().add(2, 'year').endOf('year');

            picker.setStartDate(startOfMonth);
            picker.setEndDate(today);
            picker.callback(startOfMonth, today, 'This Month');
        }
        dtable.draw();
    });

    $(document).on('click', '.open-add-days-assign-modal', function() {
        var company_id = $(this).attr("data-company_id");
        var plan_id = $(this).attr("data-plan_id");
        var subscription_id = $(this).attr("data-subscription_id");

        $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-subscription-plan',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]')
                        .attr('content'));
                },
                data: {
                    company_id: company_id,
                    plan_id: plan_id,
                    subscription_id: subscription_id,
                },
                success: function(response) {
                    if (response.status) {
                        if (response.data && response.data.length > 0) {
                            $('#add_days_subscription_modal #company_name').val(response.data[0].company_name);
                            $('#add_days_subscription_modal #plan_name').val(response.data[0].plan_name);

                            $('#add_days_subscription_modal #company_id').val(company_id);
                            $('#add_days_subscription_modal #plan_id').val(plan_id);
                            $('#add_days_subscription_modal #subscription_id').val(subscription_id);

                            const assignModal = new bootstrap.Modal(document.getElementById('add_days_subscription_modal'));
                            assignModal.show();
                        }
                    }
                }
        });
    });

    $(document).on('click', '#add_days_subscription_submit', function() {
            var isValid = true;
            $('.is-invalid').removeClass('is-invalid');
            $('.error_laravel').remove();

            $('#AddDaysSubsriptionForm .required').each(function() {
                var value = $(this).val();
                if (value === '' || value == 0) {
                    $(this).addClass('is-invalid');
                    isValid = false;
                    if(value == 0){
                        toastr.error($(this).attr("placeholder") + " must be at least 1.");
                    }else{
                        toastr.error($(this).attr("placeholder") + " is required");
                    }
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (isValid == true) {
                $('#add_days_subscription_submit').attr('disabled', true).text('Submitting...');
                var formData = new FormData($('#AddDaysSubsriptionForm')[0]);

                $('#add_days_subscription_submit').prop('disabled', true);
                $('#add_days_subscription_submit').attr('disabled', 'disabled');
                $('#add_days_subscription_submit').css({
                    'background': '#514ff1',
                    'opacity': '0.65',
                    'cursor': 'none'
                });

                $.ajax({
                    url: "{{ route($route . '.update_subscription_plan') }}",
                    type: "POST",
                    data: formData,
                    datatype: 'json',
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#add_days_subscription_submit').attr('disabled', false).text('Submit');
                        $('#add_days_subscription_submit').css({
                            'background': '',
                            'opacity': '1',
                            'cursor': 'pointer'
                        });
                        if (response.status == true) {
                            $('#AddDaysSubsriptionForm')[0].reset();
                            toastr.success(response?.message);
                            $('#add_days_subscription_modal').modal('hide');
                            dtable.draw();
                        } else {
                            toastr.error(response?.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#add_days_subscription_submit').attr('disabled', false).text('Submit');
                        $('#add_days_subscription_submit').css({
                            'background': '',
                            'opacity': '1',
                            'cursor': 'pointer'
                        });
                        $('.text-danger').remove();
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            for (let field in errors) {
                                if (errors.hasOwnProperty(field)) {
                                    $('#' + field).after(
                                        '<div class="text-danger error_laravel">' +
                                        errors[field].join('<br>') + '</div>');
                                }
                            }
                        } else {
                            toastr.error(xhr.responseJSON.message || 'An unexpected error occurred.');
                        }
                    }
                });
            }
        });

        $(document).on('click', '.company_sub_addon_expand', function () {
            const $icon = $(this);
            const companyId = $icon.data('company_id');
            const planId = $icon.data('plan_id');
            const subscriptionId = $icon.data('subscription_id');
            const $currentRow = $icon.closest('tr');

            const $nextRow = $currentRow.next();

            // If the next row is the expanded one, toggle (close it)
            if ($nextRow.hasClass('inner-table-row')) {
                $nextRow.remove();
                return;
            }

            // Remove any other open inner rows before appending new one
            $('.inner-table-row').remove();

            $.ajax({
                url: "{{ route($route . '.get_subscription_addons') }}",
                method: "POST",
                data: {
                    company_id: companyId,
                    plan_id: planId,
                    subscription_id: subscriptionId,
                },
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                datatype: 'json',
                success: function (response) {
                    if(response.status){
                        var resData = response.data;
                        var innerTableHtml = '';
                        if(resData.length > 0){
                            innerTableHtml += '<tr class="inner-table-row" style="background-color:#e2e6e8;"><td colspan="7"><table class="table table-bordered mb-0"><thead><tr><th colspan="7" style="text-align: center;"><h5 style="font-weight: bold;">Subscription Plan Addon Days</h5></th></tr><tr><th>Company Name</th><th>Plan Name</th><th>Plan From</th><th>Plan Expire</th><th>Add Days</th><th>Created Date</th></tr></thead><tbody>';

                            $.each(response.data, function(index, item) {
                                innerTableHtml += '<tr>';
                                        innerTableHtml += '<td>'+ item.company?.company_name ?? +'</td>';
                                        innerTableHtml += '<td>'+ item.plan?.name ?? +'</td>';
                                        innerTableHtml += '<td>'+ item.plan_from ?? +'</td>';
                                        innerTableHtml += '<td>'+ item.plan_to ?? +'</td>';
                                        innerTableHtml += '<td>'+ item.add_days ?? +'</td>';
                                        innerTableHtml += '<td>'+ item.created_at_org ?? +'</td>';
                                innerTableHtml += '</tr>';
                            });
                            innerTableHtml += '</tbody></table></td></tr>';
                            $(innerTableHtml).insertAfter($currentRow);
                        }
                    }
                }
            });
        });
</script>
@include('software.inlcudes.script-update-status')
@endpush
