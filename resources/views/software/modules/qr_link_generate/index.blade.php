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
    // dd($modules);
@endphp

@section('title', $page_title)

@section('page_leavel_style')
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
                'show_export_btn' => false,
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
                            <div class="col-md-3 col-sm-12">
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

                        <div class="col-md-3 d-flex align-items-end mt-2">
                            <div>
                                <button type="button" title="Clear Filter" id="cilory_filter"
                                    class="btn btn-outline-danger btn-icon mt-2">
                                    <i class="ti ti-x"></i>
                                </button>
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

        <div class="modal fade" id="qrDetailsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">QR Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Name:</strong> <span id="qrName"></span></p>
                    <p><strong>Short URL:</strong> <a href="#" target="_blank" id="qrShortUrl"></a></p>
                    <p><strong>Created:</strong> <span id="qrCreated"></span></p>
                    <p><strong>Scans:</strong> <span id="qrScans"></span></p>
                    <div id="qrCodeContainer" style="text-align: center;"></div>
                </div>
                </div>
            </div>
        </div>



        <div class="modal fade" id="downloadModal" tabindex="-1" aria-labelledby="downloadModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="downloadModalLabel">Download QR Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="qr-download-box text-left">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="format" class="form-label mt-2">Image Format</label>
                                <select id="downloadFormat" class="form-select">
                                    <option value="png">PNG</option>
                                    <option value="svg">SVG</option>
                                    <option value="pdf">PDF</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="size" class="form-label mt-2">Size</label>
                                <select id="downloadSize" class="form-select">
                                    <option value="200">200 x 200 px</option>
                                    <option value="500">500 x 500 px</option>
                                    <option value="1000" selected>1000 x 1000 px</option>
                                    <option value="1500">1500 x 1500 px</option>
                                    <option value="2000">2000 x 2000 px</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button id="downloadBtn" class="btn btn-success" data-id="">
                    <i class="fa fa-download"></i> Download
                    </button>
                </div>

                </div>
            </div>
        </div>

    @endsection


    @section('page_leavel_script')
        <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
        <script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
     <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
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
                    // dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center"<"ps-3"l>i<"pe-4"p>>',
                    dom: '<"table-responsive"t><"datatable-footer d-flex justify-content-between align-items-center flex-wrap px-3 py-2"l i p>',

                    order: [
                        [0, 'ASC']
                    ],
                    ajax: {
                        url: "{{ route($route . '.index') }}",
                        type: "GET",
                        beforeSend: function(request) {
                            request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                                'content'));
                        },
                        data: function(data) {
                            data.search = $('input[name="search"]').val();
                            data.filter_company = $('select[name="company_id"] option:selected').val();
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

            $(document).ready(function() {
                $("#cilory_filter").click(function() {
                    $('.select_filter').val(null).trigger('change');
                    $('.search').val('');
                    dtable.draw();
                });

            });

            $(document).on('click', '.viewQrBtn', function () {
                let id = $(this).data('id');

                $.ajax({
                    url: "{{ route($route . '.get_qr_details') }}",
                    type: "POST",
                    datatype: 'json',
                    data: {
                        id: id,
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        if(response.status == true) {
                            let data = response.data;
                            $('#qrName').text(data.name);
                            $('#qrShortUrl').attr('href', data.short_link).text(data.short_link);
                            $('#qrCreated').text(data.created_at);
                            $('#qrScans').text(data.scan_count);

                            // Clear previous QR
                            $('#qrCodeContainer').html('');

                            // Generate QR code
                            if(data.qr_code_image){
                                $('#qrCodeContainer').html('<img src="'+data.qr_code_image+'" width="150" height="150" />');
                            }

                            $('#qrDetailsModal').modal('show');
                        }
                    },
                    error: function () {
                        $('#order_modal .modal-body').html('<p class="text-danger">Failed to load data.</p>');
                    }
                });
            });

            let currentQrId = null;

            // when clicking download icon
            $(document).on("click", ".download-qr", function () {
                currentQrId = $(this).data("id");
                $("#downloadBtn").attr("data-id",currentQrId);
                $("#downloadModal").modal("show");
            });

            // when selecting format
            document.getElementById("downloadBtn").addEventListener("click", function () {
                let qrId = this.getAttribute("data-id");
                let format = document.getElementById("downloadFormat").value;
                let size = document.getElementById("downloadSize").value;

                if (!format || !size) {
                    alert("Please select format and size.");
                    return;
                }

                // Laravel route with placeholder, replaced in JS
                let baseUrl = "{{ route($route . '.download', ':id') }}";
                let url = baseUrl.replace(':id', qrId) + "?type=" + format + "&size=" + size;

                // AJAX request to download file
                fetch(url, { method: "GET" })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("QR code not found in this format.");
                    }
                    return response.blob();
                })
                .then(blob => {
                    // Trigger file download
                    let fileUrl = window.URL.createObjectURL(blob);
                    let a = document.createElement("a");
                    a.href = fileUrl;
                    a.download = `qrcode-${qrId}.${format}`;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                })
                .catch(error => {
                    alert(error.message);
                });
            });
        </script>

        @if (!$company_id)
            @include('utils.getCompany')
        @endif
        @include('software.inlcudes.script-delete-record')
        @include('software.inlcudes.script-restore-record')
        @include('software.inlcudes.script-update-status')
    @endpush
