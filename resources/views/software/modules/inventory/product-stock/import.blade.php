@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;
    // dd($modules);
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
        {{-- <h5 class="card-header"></h5> --}}
        <div class="card-body">
            <form action="{{ route('import-product-stock.store') }}" method="POST" enctype="multipart/form-data"
                id="import-form">
                @csrf
                <div class="row">
                    {{-- Company --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Select Company <span class="text-danger">*</span></label>
                            <select id="company_id"
                                class="form-control select2 search_by_company @error('company_id') is-invalid @enderror"
                                name="company_id"
                                data-selectedCompanyId="{{ old('company_id') ?? ($edit->company_id ?? '') }}">
                                <option value="">Select Company</option>
                            </select>

                            @error('company_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Select Warehouse <span class="text-danger">*</span></label>
                            <select name="warehouse_id" id="warehouse_id"
                                class="form-control select_filter search_by_warehouse select2"
                                data-append="search_by_warehouse" autofocus>
                                <option value="" disabled selected>
                                    Select Warehouse
                                </option>
                            </select>

                            @error('company_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>



                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label"> Import File <span class="text-danger">*</span> </label>
                            <input id="import_file" type="file"
                                class="form-control @error('import_file') is-invalid @enderror" name="import_file"
                                value="{{ isset($edit?->import_file) ? $edit?->import_file : old('import_file') }}">

                            @error('import_file')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <a href="javascript:void(0);" id="sample-download-link" class="h4 text-primary">
                                <i class="fa fa-download"></i> sample file
                            </a>


                        </div>
                    </div>


                    <div class="divider">
                        <hr />
                    </div>
                    <div class="col-md-12 text-center">
                        <button type="button" id="submitBtn" class="btn btn-success mt-1 mb-1">
                            Submit
                        </button>
                        <a href="{{ route('products.index') }}" class="btn btn-danger mt-1 mb-1">Cancel</a>
                    </div>
                </div>
            </form>
            @if (session('importErrors'))
                <ul class="text-danger">
                    @foreach (session('importErrors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="row">
                <div class="col-md-12">
                    <ul>
                        @if (session('importRecords'))
                            @foreach (session('importRecords') as $record)
                                <li class="{{ $record->status == 'success' ? 'text-success' : 'text-danger' }}">
                                    {{ $record->message }}</li>
                            @endforeach
                        @endif
                    </ul>
                </div>
            </div>
        </div>




    </div>
@endsection
@push('page_scripts')
    @include('utils.getCompany')
    @include('utils.getWarehouse')
    <script>
        $(document).ready(function() {
            $('#submitBtn').click(function() {
                $(this).prop('disabled', true).html('Processing...');

                $('#import-form').submit();
            });
        });
    </script>
    <script>
        function toggleSampleLink() {
            const companyId = $('#company_id').val();
            const warehouseId = $('#warehouse_id').val();

            if (companyId && warehouseId) {
                $('#sample-download-link').removeClass('d-none');
            } else {
                $('#sample-download-link').addClass('d-none');
            }
        }

        $(document).ready(function() {
            // Initial check
            toggleSampleLink();

            // Re-check on change
            $('#company_id, #warehouse_id').on('change', function() {
                toggleSampleLink();
            });

            $('#sample-download-link').on('click', function(e) {
                e.preventDefault();
                const companyId = $('#company_id').val();
                const warehouseId = $('#warehouse_id').val();

                if (!companyId || !warehouseId) {
                    toastr.error(
                        'Please select both Company and Warehouse before downloading the sample file.');
                    return;
                }

                const downloadUrl =
                    `{{ route('product-stock.sample-download') }}?company_id=${companyId}&warehouse_id=${warehouseId}`;
                window.location.href = downloadUrl;
            });
        });
    </script>
@endpush
