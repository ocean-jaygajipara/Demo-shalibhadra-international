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
    if (!isset($company)) {
        $company = isset($thirdPartyIntegration) ? $thirdPartyIntegration : null;
    }

    // dd($modules);
    $maring_bottom = 'mb-3';
@endphp
@section('title', $page_title)


@section('page_leavel_style')
@endsection

@section('content')
   <div class="px-1">
    <div class="d-flex flex-column flex-lg-row justify-content-lg-between align-items-start align-items-lg-center">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [
                ['title' => $page_title, 'url' => route($route . '.index')],
                // ['title' => (isset($edit) && $edit?->id) ? "Edit " . $page_title : "Create " . $page_title, 'url' => ''],
            ],
        ])
        @if(isset($route))
            <a class="btn btn-primary waves-effect waves-light text-white mb-3" href="{{ route($route . '.index') }}">
                <i class="menu-icon ti ti-chevrons-left"></i> Back
            </a>
        @endif
    </div>
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
                    <form id="form-thirdparty-platform"
                        action="{{ route('company.thirdparty-integration.platform', [$platform, $company?->id]) }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="platform" value="{{ $platform }}" />
                        <input type="hidden" name="company_id" value="{{ $company?->id ?? '' }}" />

                        @if ($platform == 'indiamart')
                            {{-- IndiaMart Section --}}
                            <div class="content">
                                <div class="row g-6 {{ $maring_bottom }}">
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">Platform</label>
                                        <input type="text" readonly class="form-control" value="IndiaMart">
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">API Key</label>
                                        <input type="text" class="form-control" name="indiamart_api_key"
                                            value="{{ old('indiamart_api_key', $company_social['indiamart']->api_key ?? '') }}"
                                            placeholder="IndiaMart API Key">
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">API Run Time (In minutes)</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="indiamart_minutes_time"
                                                value="{{ old('indiamart_minutes_time', $company_social['indiamart']->minutes_time ?? '') }}"
                                                placeholder="IndiaMart API Run Time">
                                            <span class="input-group-text">Minutes</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @elseif ($platform == 'tradeindia')
                            {{-- IndiaMart Section --}}
                            <div class="content">
                                <div class="row g-6 {{ $maring_bottom }}">
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">Platform</label>
                                        <input type="text" readonly class="form-control" value="TradeIndia">
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">API Key</label>
                                        <input type="text" class="form-control" name="tradeindia_api_key"
                                            value="{{ old('tradeindia_api_key', $company_social['tradeindia']->api_key ?? '') }}"
                                            placeholder="TradeIndia API Key">
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">User ID</label>
                                        <input type="text" class="form-control" name="tradeindia_user_id"
                                            value="{{ old('tradeindia_user_id', $company_social['tradeindia']->user_id ?? '') }}"
                                            placeholder="TradeIndia User ID">
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">Profile ID</label>
                                        <input type="text" class="form-control" name="tradeindia_profile_id"
                                            value="{{ old('tradeindia_profile_id', $company_social['tradeindia']->profile_id ?? '') }}"
                                            placeholder="TradeIndia Profile ID">
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">API Run Time (In minutes)</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="tradeindia_minutes_time"
                                                value="{{ old('tradeindia_minutes_time', $company_social['tradeindia']->minutes_time ?? '') }}"
                                                placeholder="TradeIndia API Run Time">
                                            <span class="input-group-text">Minutes</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @elseif ($platform == 'facebook')
                            {{-- Facebook Section --}}
                            <div id="Facebook-Integration" class="content">
                                <div class="row g-6 {{ $maring_bottom }}">
                                    <div class="col-md-12 col-sm-12 {{ $maring_bottom }}">
                                        <a href="javascript:;" id="facebookLoginBtn" class="btn btn-primary">
                                            <i class="fab fa-facebook"></i> Continue with Facebook
                                        </a>
                                    </div>
                                    <div class="col-md-12 col-sm-12 facebook_pages_wrapper {{ $maring_bottom }}">
                                        @if (!empty($pages) && count($pages) > 0)
                                        <input type="hidden" name="json_response" value="{{ json_encode($pages) }}">
                                        <input type="hidden" name="facebook_user_token" value="{{ $fb_user_token }}">
                                        <input type="hidden" readonly class="form-control" value="Facebook" name="facebook_platform">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Select Pages</th>
                                                        <th>Page Name</th>
                                                        <th>Page ID</th>

                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($pages as $page)
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" name="pages[]" value="{{ $page['id'] }}">
                                                                <input type="hidden" name="access_tokens[{{ $page['id'] }}]" value="{{ $page['access_token'] }}">
                                                                <input type="hidden" name="page_names[{{ $page['id'] }}]" value="{{ $page['name'] }}">
                                                            </td>
                                                            <td>{{ $page['name'] }}</td>
                                                            <td>{{ $page['id'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                            @endif

                                    </div>
                                </div>
                            </div>
                        @elseif ($platform == 'website')
                            {{-- Website Section --}}
                            <div class="content">
                                <div class="row g-6 {{ $maring_bottom }}">
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">Platform</label>
                                        <input type="text" readonly class="form-control" value="Website">
                                    </div>
                                    <div class="col-md-5 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">Website API Code</label>
                                        <div class="d-flex gap-2">
                                            <input type="text" class="form-control" name="website_api_code"
                                                id="website_api_code"
                                                value="{{ old('website_api_code', $company_social['website']->api_code ?? '') }}"
                                                placeholder="Website API Code">
                                            <button type="button" class="btn btn-primary btn-sm waves-effect waves-light text-white mt-2 mt-lg-0 d-flex align-items-center" onclick="verifyWebsiteApiCode()">Generate</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @elseif ($platform == 'eway')
                            {{-- IndiaMart Section --}}
                            <div class="content">
                                <div class="row g-6 {{ $maring_bottom }}">
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">Platform</label>
                                        <input type="text" readonly class="form-control" value="Eway">
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">GSTIN</label>
                                        <input type="text" class="form-control" name="eway_gstin"
                                            value="{{ old('eway_gstin', $company_social['eway']->gstin ?? '') }}"
                                            placeholder="GSTIN">
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">UserName</label>
                                        <input type="text" class="form-control" name="eway_username"
                                            value="{{ old('eway_username', $company_social['eway']->username ?? '') }}"
                                            placeholder="Eway UserName">
                                    </div>
                                    <div class="col-md-4 col-sm-12 mb-3">
                                        <div class="form-group">
                                            <label class="form-label">Email ID</label>
                                            <input type="email" class="form-control @error('eway_email') is-invalid @enderror"
                                                name="eway_email" value="{{ old('eway_email', $company_social['eway']->email ?? '') }}"
                                                placeholder="Enter Email ID">
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">Password</label>
                                        <input type="text" class="form-control" name="eway_password"
                                            value="{{ old('eway_password', $company_social['eway']->password ?? '') }}"
                                            placeholder="Eway Password">
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">API Client ID</label>
                                        <input type="text" class="form-control" name="eway_api_client_id"
                                                value="{{ old('eway_api_client_id', $company_social['eway']->api_client_id ?? '') }}"
                                                placeholder="API Client ID">
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">API Client Secret</label>
                                        <input type="text" class="form-control" name="eway_api_client_secret"
                                                value="{{ old('eway_api_client_secret', $company_social['eway']->api_client_secret ?? '') }}"
                                                placeholder="API Client Secret">
                                    </div>

                                </div>
                            </div>
                         @elseif ($platform == 'einvoice')
                            {{-- IndiaMart Section --}}
                            <div class="content">
                                <div class="row g-6 {{ $maring_bottom }}">
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">Platform</label>
                                        <input type="text" readonly class="form-control" value="EInvoice">
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">GSTIN</label>
                                        <input type="text" class="form-control" name="einvoice_gstin"
                                            value="{{ old('einvoice_gstin', $company_social['einvoice']->gstin ?? '') }}"
                                            placeholder="GSTIN">
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">UserName</label>
                                        <input type="text" class="form-control" name="einvoice_username"
                                            value="{{ old('einvoice_username', $company_social['einvoice']->username ?? '') }}"
                                            placeholder="EInvoice UserName">
                                    </div>
                                    <div class="col-md-4 col-sm-12 mb-3">
                                        <div class="form-group">
                                            <label class="form-label">Email ID</label>
                                            <input type="email" class="form-control @error('einvoice_email') is-invalid @enderror"
                                                name="einvoice_email" value="{{ old('einvoice_email', $company_social['einvoice']->email ?? '') }}"
                                                placeholder="Enter Email ID">
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">Password</label>
                                        <input type="text" class="form-control" name="einvoice_password"
                                            value="{{ old('einvoice_password', $company_social['einvoice']->password ?? '') }}"
                                            placeholder="Einvoice Password">
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">API Client ID</label>
                                        <input type="text" class="form-control" name="einvoice_api_client_id"
                                                value="{{ old('einvoice_api_client_id', $company_social['einvoice']->api_client_id ?? '') }}"
                                                placeholder="API Client ID">
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">API Client Secret</label>
                                        <input type="text" class="form-control" name="einvoice_api_client_secret"
                                                value="{{ old('einvoice_api_client_secret', $company_social['einvoice']->api_client_secret ?? '') }}"
                                                placeholder="API Client Secret">
                                    </div>

                                </div>
                            </div>
                         @else
                            {{-- JustDial Section --}}
                            <div id="JustDial-Integration" class="content">
                                <div class="row g-6 {{ $maring_bottom }}">
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">Platform</label>
                                        <input type="text" readonly class="form-control" value="JustDial"
                                            name="justdial_platform">
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">API Key</label>
                                        <input type="text" class="form-control" name="justdial_api_key"
                                            value="{{ old('justdial_api_key', $company_social['justdial']->api_key ?? '') }}"
                                            placeholder="JustDial API Key">
                                    </div>
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" id="submit"
                                        class="btn btn-success btn-submit waves-effect waves-light">Submit</button>
                                </div>
                            </div>

                            {{-- LinkedIn Section --}}
                            <div id="LinkedIn-Integration" class="content">
                                <div class="row g-6 {{ $maring_bottom }}">
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">Platform</label>
                                        <input type="text" readonly class="form-control" value="LinkedIn"
                                            name="linkedin_platform">
                                    </div>
                                    <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                        <label class="form-label">API Key</label>
                                        <input type="text" class="form-control" name="linkedin_api_key"
                                            value="{{ old('linkedin_api_key', $company_social['linkedin']->api_key ?? '') }}"
                                            placeholder="LinkedIn API Key">
                                    </div>
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" id="submit"
                                        class="btn btn-success btn-submit waves-effect waves-light">Submit</button>
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
            fetch_comany_social();
        }

        $(document).on('change', '.search_by_company', function() {
            if ($(".search_by_company option:selected").val() != null && $(".search_by_company option:selected")
                .val() !== "") {
                fetch_comany_social();
            }
        });


        $(document).on('click', '#facebookLoginBtn', function() {
            let companyId = $("#company_id").val();

            if (!companyId && $('meta[name="company_id"]').attr('value')) {
                companyId = $('meta[name="company_id"]').attr('value');
            }

            if (!companyId) {
                toastr.error('Please select a company first.');
                return;
            }

            $.ajax({
                type: 'POST',
                url: "{{ route($route . '.set_company_session') }}",
                datatype: 'json',
                data: {
                    company_id: companyId
                },
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.status) {
                       window.location.href = "{{ route('facebook.login') }}";
                    }
                }
            });
        });

        function verifyWebsiteApiCode() {
            let companyId = $("#company_id").val();
            if (!companyId && $('meta[name="company_id"]').attr('value')) {
                companyId = $('meta[name="company_id"]').attr('value');
            }
            if (!companyId) {
                toastr.error('Please select a company first.');
                return;
            }

            $.ajax({
                type: 'POST',
                url: "{{ route($route . '.verify_website_api_code') }}",
                datatype: 'json',
                data: {
                    company_id: companyId
                },
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    let website_data = response.data;
                    console.log(website_data)
                    if (response.status) {
                        document.getElementById('website_api_code').value = website_data.api_code;
                    }
                }
            });
        }

        function fetch_comany_social() {
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
                    url: "{{ route($route . '.get_company_social') }}",
                    data: {
                        company_id: company_id,
                        platform: platform
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $("#form-thirdparty-platform")[0].reset();
                        if (response.status) {
                            let filteredData = response?.data.filter(item => item.platform.toLowerCase() === platform.toLowerCase());

                             if (platform.toLowerCase() === 'facebook') {

                                let savedPageIds = [];
                                filteredData.forEach(record => {
                                    if (record.page_id) {
                                        const ids = record.page_id
                                            .split(',')
                                            .map(id => id.trim())
                                            .filter(id => id.length > 0);

                                        savedPageIds = savedPageIds.concat(ids);
                                    }
                                });

                                if (filteredData.length > 0) {
                                    let html = `
                                        <input type="hidden" name="json_response" value='${JSON.stringify(filteredData[0].json_response)}'>
                                        <input type="hidden" name="facebook_user_token" value="${filteredData[0].user_id ?? ''}">
                                        <input type="hidden" readonly class="form-control" value="Facebook" name="facebook_platform">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Select Pages</th>
                                                    <th>Page Name</th>
                                                    <th>Page ID</th>
                                                </tr>
                                            </thead>
                                            <tbody>`;

                                    let rawJson = filteredData[0].json_response;
                                    let json_response;

                                    try {
                                        if (typeof rawJson === 'string') {
                                            let parsed = JSON.parse(rawJson);
                                            json_response = typeof parsed === 'string' ? JSON.parse(parsed) : parsed;
                                        } else {
                                            json_response = rawJson;
                                        }
                                    } catch (e) {
                                        json_response = []; // fallback
                                    }

                                    json_response.forEach(function(page) {
                                        let isChecked = savedPageIds.includes(page.id.toString()) ? 'checked' : '';

                                        html += `
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="pages[]" value="${page.id}" ${isChecked}>
                                                    <input type="hidden" name="access_tokens[${page.id}]" value="${page.access_token}">
                                                    <input type="hidden" name="page_names[${page.id}]" value="${page.name}">
                                                </td>
                                                <td>${page.name}</td>
                                                <td>${page.id}</td>
                                            </tr>`;
                                    });

                                    html += `</tbody></table>`;

                                    $(".facebook_pages_wrapper").html(html);
                                }else{
                                    $(".facebook_pages_wrapper").html('');
                                }
                            }
                            else{
                                if (filteredData?.length > 0 && filteredData[0]) {
                                    let platformData = filteredData[0];
                                    if (platformData?.id) {
                                        $("input[name='" + platform + "_api_key']").val(platformData?.api_key);
                                        $("input[name='" + platform + "_minutes_time']").val(platformData?.minutes_time);
                                        $("input[name='" + platform + "_user_id']").val(platformData?.user_id);
                                        $("input[name='" + platform + "_profile_id']").val(platformData?.profile_id);
                                        $("input[name='" + platform + "_api_code']").val(platformData?.api_code);
                                        $("input[name='" + platform + "_gstin']").val(platformData?.gstin);
                                        $("input[name='" + platform + "_username']").val(platformData?.username);
                                        $("input[name='" + platform + "_email']").val(platformData?.email);
                                        $("input[name='" + platform + "_password']").val(platformData?.password);
                                        $("input[name='" + platform + "_api_client_id']").val(platformData?.api_client_id);
                                        $("input[name='" + platform + "_api_client_secret']").val(platformData?.api_client_secret);
                                    }
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
