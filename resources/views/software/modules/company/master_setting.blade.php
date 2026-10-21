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
    $maring_bottom = 'mb-3';
@endphp
@section('title', $page_title)


@section('page_leavel_style')
@endsection

@section('content')
    <div class="d-flex justify-content-lg-between px-1">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [['title' => $page_title, 'url' => '']],
            'route' => $route,
            'show_add_btn' => false,
            'show_back_btn' => true,
        ])
    </div>


    <!-- Default -->
    <div class="row">
        <!-- Default Wizard -->
        <div class="col-12 mb-6">
            <div class="card p-3">
                <div class="d-flex justify-content-between ">
                    @if (!$company_id)
                        <div class="col-md-3 mb-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Select Company </label>
                                <select name="company_id" id="company_id"
                                    class="form-control @error('company_id') is-invalid @enderror search_by_company select2"
                                    data-append="search_by_company"
                                    data-selectedCompanyId="{{ $company->company_id ?? session('selected_company_id') ?? '' }}"
                                    autofocus>
                                    <option value="" disabled
                                        {{ old('company_id', $company->company_id ?? '') ? '' : 'selected' }}>
                                        Select Company
                                    </option>
                                </select>
                                @error('company_id')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    @else
                        <h4 class="mb-0">Company name :- {{ $company?->company_name ?? '' }}</h4>
                    @endif
                    <h4 class="mb-0 ml-auto">Platform :- {{ $platform ?? '' }}</h4>
                </div>
            </div>
        </div>

        <div class="col-12 mb-6">
            <div class="card mt-3">
                <div class="card-body">
                    <form id="form-master-platform"
                        action="{{ route('company.master_config', [$platform, $company?->id]) }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="platform" value="{{ $platform }}" />
                        <input type="hidden" name="company_id" value="{{ $company?->id ?? '' }}" />

                        @if ($platform == 'facebook')
                            {{-- Facebook Section --}}
                            <div class="content">
                                <div class="row g-6 {{ $maring_bottom }}">
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">Platform</label>
                                        <input type="text" readonly class="form-control" value="Facebook">
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">APP ID</label>
                                        <input type="text" class="form-control" name="facebook_app_id"
                                            value="{{ old('facebook_app_id', $company_social['facebook']->app_id ?? '') }}"
                                            placeholder="Facebook APP ID">
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">APP SECRET</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="facebook_app_secret"
                                                value="{{ old('facebook_app_secret', $company_social['facebook']->app_secret ?? '') }}"
                                                placeholder="Facebook APP SECRET">
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">REDIRECT URL</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="facebook_redirect_url"
                                                value="{{ old('facebook_redirect_url', $company_social['facebook']->redirect_url ?? '') }}"
                                                placeholder="Facebook REDIRECT URL">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        @endif

                        <div class="content">
                            <div class="col-12 d-flex justify-content-center">
                                <button type="submit" id="submit"
                                    class="btn btn-success btn-submit waves-effect waves-light">Submit</button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- /Default Wizard -->
    </div>
@endsection

@push('page_scripts')
    @if (!$company_id)
        @include('utils.getCompany')
    @endif
@endpush

@section('page_leavel_script')
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        if ($('meta[name="company_id"]').attr('value')) {
            fetch_master_social();
        }

        $(document).on('change', '.search_by_company', function() {
            if ($(".search_by_company option:selected").val() != null && $(".search_by_company option:selected")
                .val() !== "") {
                fetch_master_social();
            }
        });

        function fetch_master_social() {
            let instance = $('.search_by_team_role');
            let company_id = $(".search_by_company option:selected").val();
            let platform = $("input[name='platform']").val();

            let is_required = instance.attr('required');
            let is_select2 = instance.hasClass('select2');

            if (!company_id && $('meta[name="company_id"]').attr('value')) {
                company_id = $('meta[name="company_id"]').attr('value');
            }

            if (company_id) {
                $('input[name="company_id"]').val(company_id);

                $.ajax({
                    type: 'POST',
                    datatype: 'json',
                    url: "{{ route($route . '.get_master_social') }}",
                    data: {
                        company_id: company_id,
                        platform: platform
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $("#form-master-platform")[0].reset();
                        if (response.status) {
                            let filteredData = response?.data.filter(item => item.platform.toLowerCase() === platform.toLowerCase());
                            if (filteredData?.length > 0 && filteredData[0]) {
                                let platformData = filteredData[0];
                                if (platformData?.id) {
                                    $("input[name='" + platform + "_app_id']").val(platformData?.app_id);
                                    $("input[name='" + platform + "_app_secret']").val(platformData?.app_secret);
                                    $("input[name='" + platform + "_redirect_url']").val(platformData?.redirect_url);
                                }
                            }
                        }
                    },
                    error: function(err) {
                        console.error("Failed to fetch company third party ", err);
                    }
                });
            }
        }
    </script>
@endsection
