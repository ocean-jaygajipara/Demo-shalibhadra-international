@extends('software.layout.app')

@php
    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;

    // dd($modules);

@endphp
@section('title', $page_title)

@section('page_leavel_style')
    {{-- <link rel="stylesheet" href="https://cdn.datatables.net/1.12.4/css/dataTables.bootstrap5.min.css" /> --}}
    {{-- <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css"> --}}
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
@endsection

@section('content')
  <div class="px-1">
        <div class="d-lg-flex justify-content-lg-between flex-column flex-lg-row">
            @include('software.inlcudes.breadcrumb', [
                'breadcrumbArray' => [['title' => $page_title, 'url' => '']],
                'route' => $route,
                'show_add_btn' => isset($modules['add_permission']) ? $modules['add_permission'] : false,
                'show_filter_btn' => true,
                'show_back_btn' => false,
                'show_export_btn' =>
                    isset($modules['excel_permission']) &&
                    isset($modules['print_permission']) &&
                    ($modules['excel_permission'] || $modules['print_permission'])
                        ? true
                        : false,
                'show_excal_btn' => isset($modules['excel_permission']) ? $modules['excel_permission'] : false,
                'show_print_btn' => isset($modules['print_permission']) ? $modules['print_permission'] : false,
            ])
        </div>
    </div>
    <div class="row my-3">
        <div class="col-md-12 mb-5" id="filter_section">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Filter by Name</label>
                            <input type="search" class="form-control search" name="search" placeholder="search..."
                                autofocus>
                        </div>
                        @if (!$company_id)
                            <div class="col-md-3 mb-2 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label">Filter by Company</label>
                                    <select id="company_id" name="company_id"
                                        class="form-control search_by_company select2 select_filter"
                                        data-append="search_by_company">
                                        <option value="">Filter by Company</option>
                                    </select>
                                </div>
                            </div>
                        @endif
                        <div class="col-md-3 mb-2 col-sm-12">
                            <label class="form-label">Filter by Visitor Type</label>
                            <select name="visitor_type_id"
                                class="form-control search_by_visitor_type select2 select_filter @error('visitor_type_id') is-invalid @enderror"
                                data-selectedvisitortypeid="{{ old('visitor_type_id', $edit->visitor_type_id ?? '') }}">
                                <option value="">Select Visitor Type</option>
                            </select>
                        </div>

                        <div class="col-md-3 mb-2 col-sm-12">
                            <label class="form-label">Filter by Purpose of Visit</label>
                            <select name="purpose_of_visit_id"
                                class="form-control search_by_purpose_visit select2 select_filter @error('purpose_of_visit_id') is-invalid @enderror"
                                data-selectedpurposevisitid="{{ old('purpose_of_visit_id', $edit->purpose_of_visit_id ?? '') }}">
                                <option value="">Select Purpose of Visit</option>
                            </select>
                        </div>

                        <div class="col-md-3 mb-2 col-sm-12">
                            <label class="form-label">Filter by Person to Meet</label>
                                <select name="team_person_id"
                            class="form-control select2 select_filter search_by_assigned_to select2 @error('team_person_id') is-invalid @enderror"
                            data-selectedperson="{{ old('team_person_id', $edit->team_person_id ?? '') }}">
                            <option value="">Select Team Person</option>
                        </select>
                        </div>

                        <div class="col-md-3 mb-2 col-sm-12">
                            <label class="form-label">Filter by Department</label>
                            <select name="department_id"
                                class="form-control search_by_department select2 select_filter @error('department_id') is-invalid @enderror"
                                data-selecteddepartmentid="{{ old('department_id', $edit->department_id ?? '') }}">
                                <option value="">Select Department</option>
                            </select>
                        </div>

                        <div class="col-md-3 d-flex align-items-end mb-2">
                            <div class="flex-grow-1 me-2">
                                <label for="status_filter" class="form-label">Filter by Status</label>
                                <select id="status_filter" name="status" class="form-select select2 select_filter">
                                    <option value="all">All</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div>
                                <button type="button" title="Clear Filter" id="cilory_filter"
                                    class="btn btn-outline-danger btn-icon mt-2"><i class="ti ti-x"></i></button>
                            </div>
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
@endsection

@section('page_leavel_script')
    <!-- Data tables -->
    {{-- <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.4/js/dataTables.bootstrap5.min.js"></script> --}}
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endsection

@push('page_scripts')
    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        var dtable = null;
        $(document).ready(function() {
            console.log("Console 62");

            dtable = $('#yajra-datatables').DataTable({
                processing: true,
                serverSide: true,
                // dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center"<"ps-3"l>i<"pe-4"p>>',
              dom: '<"table-responsive"t><"datatable-footer d-flex justify-content-between align-items-center flex-wrap px-3 py-2"l i p>',

                order: [
                    [0, 'ASC']
                ],
                ajax: {
                    "url": "{{ route($route . '.index') }}",
                    'beforeSend': function(request) {
                        request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                            'content'));
                        console.log("Console 73");
                    },
                    type: "GET",
                    data: function(data) {
                        data.search = $('input[name="search"]').val();
                        data.filter_company = $('select[name="company_id"] option:selected').val();
                        data.status = $('#status_filter').val();
                        data.visitor_type_id = $('select[name="visitor_type_id"]').val();
                        data.purpose_visit_id = $('select[name="purpose_of_visit_id"]').val();
                        data.person_to_meet = $('select[name="team_person_id"]').val();
                        data.department_id = $('select[name="department_id"]').val();


                    },
                },

                columns: {!! isset($columns) ? json_encode($columns) : [] !!},
                language: {
                    searchPlaceholder: 'Search...',
                }
            });
        });

        $(document).on('change', '.select_filter', function(event) {
            event.preventDefault();
            dtable.draw();
        });
        $('input[name="search"]').keyup(function() {
            dtable.draw();
        });
        $('#status_filter').on('change', function() {
            dtable.draw();
        });

        $(document).ready(function() {
            $("#cilory_filter").click(function() {
                $('.select_filter').val(null).trigger('change');
                $('#status_filter').val('all').trigger('change');
                $('.search').val('');
                dtable.draw();
            });
        });
        $('#export_excel_btn').on('click', function(e) {
            e.preventDefault();

            let search = $('input[name="search"]').val();
            let status = $('#status_filter').val();
            let company = $('select[name="company_id"]').val();
            let visitor_type_id = $('select[name="visitor_type_id"]').val();
            let purpose_visit_id = $('select[name="purpose_of_visit_id"]').val();
            let person_to_meet = $('select[name="team_person_id"]').val();
            let department_id = $('select[name="department_id"]').val();


            let queryParams = $.param({
                search,
                status,
                company,
                visitor_type_id,
                purpose_visit_id,
                person_to_meet,
                department_id,
            });


            let url = "{{ route($route . '.export.excel') }}" + "?" + queryParams;
            window.location.href = url;
        });
        $('#print_btn').on('click', function(e) {
            e.preventDefault();

            let search = $('input[name="search"]').val();
            let status = $('#status_filter').val();
            let company = $('select[name="company_id"]').val();
            let visitor_type_id = $('select[name="visitor_type_id"]').val();
            let purpose_visit_id = $('select[name="purpose_of_visit_id"]').val();
            let person_to_meet = $('select[name="team_person_id"]').val();
            let department_id = $('select[name="department_id"]').val();


            let queryParams = $.param({
                search,
                status,
                company,
                visitor_type_id,
                purpose_visit_id,
                person_to_meet,
                department_id,
            });


            let url = "{{ route($route . '.print') }}" + "?" + queryParams;
            window.open(url, '_blank');
        });
    </script>
     <script>
    $(document).ready(function () {
        if ($('meta[name="company_id"]').attr('value')) {
            fetchTeams();
        }

        $(document).on('change', '.search_by_company', function () {
            const selectedCompanyId = $(".search_by_company option:selected").val();
            if (selectedCompanyId) {
                fetchTeams(selectedCompanyId);
            }
        });
    });

    function fetchTeams(company_id = null) {
        const instance = $('.search_by_assigned_to');
        const selected_id = instance.data("selectedperson") || '';

        if (!company_id && $('meta[name="company_id"]').attr('value')) {
            company_id = $('meta[name="company_id"]').attr('value');
        }

        if (!company_id) {
            instance.html('<option value="">Select Team Person</option>').trigger('change.select2');
            return;
        }

        $.ajax({
            type: 'POST',
            url: '{{ env('API_URL') }}get-teams',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: { company_id },
            success: function(response) {
                if (response.status && response.data && response.data.length > 0) {
                    let options = '<option value="">Select Team Person</option>';
                    $.each(response.data, function(i, item) {
                        const selected = selected_id == item.id ? 'selected' : '';
                        options += `<option value="${item.id}" ${selected}>${item.team_name}</option>`;
                    });
                    instance.html(options).trigger('change.select2');
                } else {
                    instance.html('<option value="">No team persons found</option>').trigger('change.select2');
                }
            },
            error: function() {
                instance.html('<option value="">Error fetching team persons</option>').trigger('change.select2');
            }
        });
    }
</script>
<script>
    $(document).on('click', '.mark-in-out-btn', function () {
        const $button = $(this);
        const id = $button.data('id');
        const type = $button.data('type');
        const box = $('#entry-box-' + id);

        $.ajax({
            url: "{{ route('visitor-gatepass.markTime') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                id: id,
                type: type
            },
            success: function (response) {
                if (response.success) {
                    let html = '';

                    if (type === 'in') {
                        html += `<strong>In:</strong> ${response.time}<br>`;
                        html += `<button type='button' class='btn btn-sm btn-danger mt-1 mark-in-out-btn' data-id='${id}' data-type='out'>Out</button>`;
                    } else if (type === 'out') {
                        const currentHtml = box.html();
                        const inTime = currentHtml.split('<br>')[0]; // preserve In line
                        html += inTime + `<br><strong>Out:</strong> ${response.time}`;
                    }

                    box.html(html);
                } else {
                    alert(response.message);
                }
            },
            error: function () {
                alert('Server error.');
            }
        });
    });
</script>



    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getVisitorType')
    @include('utils.getDepartment')
    @include('utils.getPurposeVisit')
    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-restore-record')
    @include('software.inlcudes.script-update-status')
@endpush
