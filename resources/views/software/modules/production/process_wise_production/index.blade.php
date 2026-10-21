@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
@endphp

@section('title', $modules['title'])

@section('page_leavel_style')
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
@endsection

@section('content')
<div class="px-1">
    <div class="d-lg-flex justify-content-lg-between flex-column flex-lg-row">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [['title' => $modules['title'], 'url' => '']],
            'route' => $modules['route'],
            'show_add_btn' => $modules['add_permission'] ?? false,
            'show_filter_btn' => true,
            'show_back_btn' => false,
            'show_export_btn' => false,
            'show_excal_btn' => false,
            'show_print_btn' => false,
        ])
    </div>
</div>
<div class="row my-3">
    <div class="col-md-12 mb-5" id="filter_section" style="display: none;">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="search" class="form-control search" name="search" placeholder="Search..." autofocus>
                    </div>
                    <div class="col-md-3 d-flex align-items-end mb-2">
                        <div class="flex-grow-1 me-2">
                            <label for="status_filter" class="form-label">Filter by Status</label>
                            <select id="status_filter" name="status" class="form-select select2">
                                <option value="all">All</option>
                                <option value="Pending">Pending</option>
                                <option value="Approve">Approve</option>
                            </select>
                        </div>
                        <div>
                            <button type="button" title="Clear Filter" id="cilory_filter" class="btn btn-outline-danger btn-icon mt-2"><i class="ti ti-x"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-datatable text-nowrap mt-3">
                <div class="card-datatable table-responsive">
                    <table id="yajra-datatables" class="dt-responsive table table-hover">
                        <thead>
                            <tr>
                                @foreach ($columns as $column)
                                    <th class="{{ $column->className ?? '' }}">{{ $column->td_label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page_leavel_script')
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
        dtable = $('#yajra-datatables').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route($modules['route'].'.index') }}",
                type: 'GET',
                beforeSend: function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                },
                data: function(data) {
                    data.search = $('input[name="search"]').val();
                    data.status = $('#status_filter').val();
                }
            },
            dom: '<"table-responsive"t><"datatable-footer d-flex justify-content-between align-items-center flex-wrap px-3 py-2"l i p>',
            columns: [
                @foreach ($columns as $column)
                    {
                        data: '{{ $column->data }}',
                        name: '{{ $column->name }}',
                        orderable: {{ isset($column->orderable) && !$column->orderable ? 'false' : 'true' }},
                        searchable: {{ isset($column->searchable) && !$column->searchable ? 'false' : 'true' }},
                        className: '{{ $column->className ?? "" }}'
                    },
                @endforeach
            ],
            order: [[0, 'desc']]
        });
        
        $('body').on('click', '.deletebutton', function () {
            var id = $(this).data("id");
            var url = $(this).data("did");
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "DELETE",
                        url: url,
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (data) {
                            dtable.draw();
                            Swal.fire("Deleted!", data.message, "success");
                        },
                        error: function (data) {
                            console.log('Error:', data);
                        }
                    });
                }
            });
        });

        $('input[name="search"]').keyup(function() {
            dtable.draw();
        });

        $('#status_filter').on('change', function() {
            dtable.draw();
        });

        $("#cilory_filter").click(function() {
            $('#status_filter').val('all').trigger('change');
            $('.search').val('');
            dtable.draw();
        });

        $('body').on('click', '.change-status', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var status = $(this).data('status');
            
            $.ajax({
                url: "{{ route($modules['route'].'.change-status') }}",
                type: "POST",
                data: {
                    id: id,
                    status: status,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if(response.success) {
                        dtable.draw();
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    toastr.error("Something went wrong");
                }
            });
        });
    });
</script>
@include('software.inlcudes.script-delete-record')
@include('software.inlcudes.script-restore-record')
@include('software.inlcudes.script-update-status')
@endpush
