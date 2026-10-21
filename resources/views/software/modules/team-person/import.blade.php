@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;

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
            <form action="{{ route('import-team-person.store') }}" method="POST" enctype="multipart/form-data" id="import-form">
                @csrf
                <div class="row">
                    @if (!$company_id)
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
                    @else
                        <input type="hidden" class="form-control search_by_company" name="company_id"
                            value="{{ $company_id }}" />
                    @endif
                    <div class="col-md-4 col-sm-12">
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


                    {{-- Sample File Download --}}
                    <div class="col-md-4 d-flex align-items-end mb-3 mt-4 bg-custom-light text-center" id="sampleFileDiv"
                        style="{{ $company_id ? '' : 'display: none;' }} background-color: #e9ecef;">

                        <a href="{{ asset('software/sample-file/import-team-person-sample.xlsx') }}"
                            class="btn text-primary mx-auto" download>
                            <i class="fa fa-download"></i> Download Sample File
                        </a>
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

            <div class="row">
                <div class="col-md-12">
                    <ul>
                        @if (session('team_person_summary'))
                            @php $summary = session('team_person_summary'); @endphp
                            <p class="mt-3" style="font-weight: 600;">
                                Total Team Person: {{ $summary['total_team_persons'] ?? 0 }} &nbsp;
                                Successful Imports: <span
                                    class="text-success">{{ $summary['total_success_team_persons'] ?? 0 }}</span> &nbsp;
                                Failed Imports: <span
                                    class="text-danger">{{ $summary['total_failed_team_persons'] ?? 0 }}</span>
                            </p>
                        @endif
                        @if (session('teampersonImportFileMessages'))
                            @foreach (session('teampersonImportFileMessages') as $record)
                                <li
                                    class="{{ is_array($record) && ($record['status'] ?? '') == 'success' ? 'text-success' : 'text-danger' }}">
                                    {{ is_array($record) ? $record['message'] ?? 'Unknown error' : $record }}
                                </li>
                            @endforeach
                        @endif
                    </ul>

                </div>
            </div>
        </div>




    </div>
@endsection
@push('page_scripts')
    @if (!$company_id)
        @include('utils.getCompany')
    @endif

    <script>
        $(document).ready(function() {
            $('#submitBtn').click(function() {
                $(this).prop('disabled', true).html('Processing...');

                $('#import-form').submit();
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            function toggleSampleDiv() {
                if ($('#company_id').val()) {
                    console.log($('#company_id').val());

                    $('#sampleFileDiv').show();
                } else {
                    $('#sampleFileDiv').hide();
                }
            }
            toggleSampleDiv();
            $('#company_id').on('change', toggleSampleDiv);
        });
    </script>
@endpush
