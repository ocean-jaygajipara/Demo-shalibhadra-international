@extends('software.layout.app')

@php
    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $form_route = isset($modules['form_route']) ? $modules['form_route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    $authLoginUserDetail = isset($modules['authLoginUserDetail']) ? $modules['authLoginUserDetail'] : null;
    $loginUserId = isset($authLoginUserDetail?->id) ? $authLoginUserDetail?->id : null;
    $parent_type_id = isset($modules['parent_type_id']) ? $modules['parent_type_id'] : null;

    $maring_bottom = 'mb-3';
@endphp

@section('title', $page_title)


@section('page_leavel_style')
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.css" />

    <style>
        .select2-container {
            display: block !important;
        }

        .bootstrap-tagsinput {
            width: 100%;
            min-height: 40px;
            padding: 6px 10px;
            line-height: 22px;
            border: 1px solid #ccc;
            border-radius: 0.25rem;
            background: #fff;
        }

        .bootstrap-tagsinput .tag {
            margin-right: 2px;
            color: white;
            background-color: #29c3c0;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }
    </style>

@endsection

@section('content')
    <div class="d-flex justify-content-lg-between px-1">
        @php
            $tab = request('tab');
            $tabTitles = [
                'profile-tab' => 'Company',
                'integration-tab' => 'Third Party Integration',
                'mail-tab' => 'Mail Configuration',
                'subscription-tab' => 'Subscription History',
            ];
        @endphp
        {{-- @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [
                ['title' => $page_title, 'url' => route($route . '.index')],
                [
                    'title' => $tabTitles[$tab] ?? 'Company Detail',
                    'url' => '',
                ],
            ],
            'route' => $route,
            'show_add_btn' => false,
            'show_back_btn' => false,
            'show_filter_btn' => true,
        ]) --}}
    </div>

    <!-- Default -->
    <div class="row my-3">
        <!-- Default Wizard -->

        <div class="row">
            <div class="col-md-12">
            <div class="nav-align-top">
                <ul class="nav nav-pills flex-column flex-sm-row mb-6 gap-sm-0 gap-2" id="profile-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ request('tab') === 'profile-tab' ? 'active' : '' }}" href="{{ route($route . '.detail', [$edit?->id]) . '?tab=profile-tab' }}">
                            <i class="icon-base ti tabler-user-check icon-sm me-1_5"></i> Company
                        </a>
                    </li>
                    {{-- <li class="nav-item">
                        <a class="nav-link {{ request('tab') === 'integration-tab' ? 'active' : '' }}" href="{{ route($route . '.detail', [$edit?->id]) . '?tab=integration-tab' }}">
                            <i class="icon-base ti tabler-layout-grid icon-sm me-1_5"></i> Third Party Integration
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('tab') === 'mail-tab' ? 'active' : '' }}" href="{{ route($route . '.detail', [$edit?->id]) . '?tab=mail-tab' }}">
                            <i class="icon-base ti tabler-link icon-sm me-1_5"></i> Mail Configuration
                        </a>
                    </li> --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request('tab') === 'subscription-tab' ? 'active' : '' }}" href="{{ route($route . '.detail', [$edit?->id]) . '?tab=subscription-tab' }}">
                            <i class="icon-base ti tabler-link icon-sm me-1_5"></i> Subscription History
                        </a>
                    </li>
                </ul>
            </div>
            </div>
        </div>


        @if(request('tab') != 'subscription-tab')
        <div class="col-12 mb-6 pt-4">
            <div class="card mb-6">
                <div class="card-body pt-4">
                    <div class="tab-content p-0">
                        @if(request('tab') === 'profile-tab')
                            <div class="tab-pane fade {{ request('tab') === 'profile-tab' ? 'show active' : '' }}" id="profile-tab">
                                <form id="CompanyDetailsForm" action="{{ route($route . '.update', [$edit?->id]) }}" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf
                                    @isset($edit)
                                        @method('PUT')
                                    @endisset

                                    <input type="hidden" id="company_name" name="company_name" value="{{ $edit->company_name }}" />
                                    <input type="hidden" id="person_name" name="person_name" value="{{ $edit->person_name }}" />
                                    <input type="hidden" id="whatsapp_number" name="whatsapp_number" value="{{ $edit->whatsapp_number }}" />
                                    <input type="hidden" id="email" name="email" value="{{ $edit->email }}" />
                                    <input type="hidden" id="country_id" name="country_id" value="{{ $edit->country_id }}" />
                                    <input type="hidden" name="team_set" value="team_update" />
                                    <input type="hidden" name="tab" value="profile-tab" />
                                    <div class="row gy-4 gx-6 mb-6">
                                        <div class="col-md-6 col-sm-12 mb-3">
                                            <label class="form-label" for="bank_details">Bank Details</label>
                                            <textarea name="bank_details" id="bank_details" class="ckeditor_common_cls form-control @error('bank_details') is-invalid @enderror"
                                                placeholder="Bank Details">{{ $edit->bank_details }}</textarea>
                                            @error('bank_details')
                                                <span class="invalid-feedback">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 col-sm-12 mb-3">
                                            <label class="form-label" for="address">Address</label>
                                            <textarea name="address" id="address" class="ckeditor_common_cls form-control @error('address') is-invalid @enderror"
                                                placeholder="Address">{{ $edit->address }}</textarea>
                                            @error('address')
                                                <span class="invalid-feedback">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                        <div class="col-md-4 col-sm-12 mb-3">
                                            <label class="form-label">
                                                Company Logo
                                                <small class="text-muted">minimum image size 400 x 300</small>
                                            </label>
                                            <div class="border border-secondary rounded d-flex align-items-center justify-content-center"
                                                style="height: 150px; background-color: #f9f9f9;position: relative">
                                                <input type="file" class="form-control"
                                                    style="opacity: 0; height: 150px; width: 100%; position: absolute;"
                                                    id="company_logo" name="company_logo"
                                                    onchange="previewImage(event, 'companyLogoPreview')" accept="image/*">
                                                <p class="text-muted m-0">Drop in your image or click the box to add one!</p>
                                            </div>
                                            <!-- Image Preview Box -->
                                            <div style="margin-top: 10px;">
                                                <img id="companyLogoPreview" src="<?php echo $edit->company_logo && $edit->company_logo_url ? $edit->company_logo_url : '#'; ?>" alt="Image Preview"
                                                    style="display: <?php echo $edit->company_logo ? 'block' : 'none'; ?>; width: 200px;  object-fit: cover; border: 1px solid #ddd;"
                                                    onerror="this.onerror=null; this.src=''; this.style.display='none';">
                                                <input type="hidden" name="company_logo_old"
                                                    value="{{ $edit->company_logo }}">
                                            </div>
                                        </div>

                                        <div class="col-md-4 col-sm-12 mb-3">
                                            <label class="form-label">
                                                White Labeling Logo
                                                <!-- <small class="text-muted">minimum image size 800 x 120</small> -->
                                            </label>
                                            <div class="border border-secondary rounded d-flex align-items-center justify-content-center"
                                                style="height: 150px; background-color: #f9f9f9;position: relative">
                                                <input type="file" class="form-control"
                                                    style="opacity: 0; height: 150px; width: 100%; position: absolute;"
                                                    id="white_labeling_logo" name="white_labeling_logo"
                                                    onchange="previewImage(event, 'whiteLabelingLogoPreview')" accept="image/*">
                                                <p class="text-muted m-0">Drop in your image or click the box to add one!</p>
                                            </div>
                                            <!-- Image Preview Box -->
                                            <div style="margin-top: 10px;">
                                                <img id="whiteLabelingLogoPreview" src="<?php echo $edit->white_labeling_logo && $edit->white_labeling_logo_url ? $edit->white_labeling_logo_url : '#'; ?>" alt="Image Preview"
                                                    style="display: <?php echo $edit->white_labeling_logo ? 'block' : 'none'; ?>; width: 200px;  object-fit: cover; border: 1px solid #ddd;"
                                                    onerror="this.onerror=null; this.src=''; this.style.display='none';">
                                                <input type="hidden" name="white_labeling_logo_old"
                                                    value="{{ $edit->white_labeling_logo }}">
                                            </div>
                                        </div>

                                        <div class="col-md-4 col-sm-12 mb-3">
                                            <label class="form-label">
                                                Favicon Icon
                                                <!-- <small class="text-muted">minimum image size 800 x 120</small> -->
                                            </label>
                                            <div class="border border-secondary rounded d-flex align-items-center justify-content-center"
                                                style="height: 150px; background-color: #f9f9f9;position: relative">
                                                <input type="file" class="form-control"
                                                    style="opacity: 0; height: 150px; width: 100%; position: absolute;"
                                                    id="company_favicon" name="company_favicon"
                                                    onchange="previewImage(event, 'CompanyFaviconPreview')" accept="image/*">
                                                <p class="text-muted m-0">Drop in your image or click the box to add one!</p>
                                            </div>
                                            <!-- Image Preview Box -->
                                            <div style="margin-top: 10px;">
                                                <img id="CompanyFaviconPreview" src="<?php echo $edit->company_favicon && $edit->company_favicon_url ? $edit->company_favicon_url : '#'; ?>" alt="Image Preview"
                                                    style="display: <?php echo $edit->company_favicon ? 'block' : 'none'; ?>; width: 200px;  object-fit: cover; border: 1px solid #ddd;"
                                                    onerror="this.onerror=null; this.src=''; this.style.display='none';">
                                                <input type="hidden" name="company_favicon_old"
                                                    value="{{ $edit->company_favicon }}">
                                            </div>
                                        </div>

                                        <div class="col-md-4 col-sm-12 mb-3">
                                            <label class="form-label">
                                                App Logo
                                                <small class="text-muted">minimum image size 512 x 512</small>
                                            </label>
                                            <div class="border border-secondary rounded d-flex align-items-center justify-content-center"
                                                style="height: 150px; background-color: #f9f9f9;position: relative">
                                                <input type="file" class="form-control"
                                                    style="opacity: 0; height: 150px; width: 100%; position: absolute;"
                                                    id="app_logo" name="app_logo"
                                                    onchange="previewImage(event, 'appLogoPreview')" accept="image/*">
                                                <p class="text-muted m-0">Drop in your image or click the box to add one!</p>
                                            </div>
                                            <!-- Image Preview Box -->
                                            <div style="margin-top: 10px;">
                                                <img id="appLogoPreview" src="<?php echo $edit->app_logo && $edit->app_logo_url ? $edit->app_logo : '#'; ?>" alt="Image Preview"
                                                    style="<?php echo $edit->app_logo ? 'block' : 'none'; ?>; width: 200px;  object-fit: cover; border: 1px solid #ddd;"
                                                    onerror="this.onerror=null; this.src=''; this.style.display='none';">
                                                <input type="hidden" name="app_logo_old" value="{{ $edit->app_logo }}">
                                            </div>
                                        </div>

                                        <div class="col-md-4 col-sm-12 mb-3">
                                            <label class="form-label">
                                                Order / Quotation / Dispatch Header
                                                <small class="text-muted">minimum image size 800 x 120</small>
                                            </label>
                                            <div class="border border-secondary rounded d-flex align-items-center justify-content-center"
                                                style="height: 150px; background-color: #f9f9f9;position: relative">
                                                <input type="file" class="form-control"
                                                    style="opacity: 0; height: 150px; width: 100%; position: absolute;"
                                                    id="header_image" name="header_image"
                                                    onchange="previewImage(event, 'headerimagePreview')" accept="image/*">
                                                <p class="text-muted m-0">Drop in your image or click the box to add one!</p>
                                            </div>
                                            <!-- Image Preview Box -->
                                            <div style="margin-top: 10px;">
                                                <img id="headerimagePreview" src="<?php echo $edit->order_header_logo && $edit->order_header_logo_url ? $edit->order_header_logo_url : '#'; ?>" alt="Image Preview"
                                                    style="<?php echo $edit->order_header_logo ? 'block' : 'none'; ?>; width: 200px;  object-fit: cover; border: 1px solid #ddd;"
                                                    onerror="this.onerror=null; this.src=''; this.style.display='none';">
                                                <input type="hidden" name="header_image_old"
                                                    value="{{ $edit->order_header_logo }}">
                                            </div>
                                        </div>

                                        <div class="col-md-4 col-sm-12 mb-3">
                                            <label class="form-label">
                                                Order / Quotation / Dispatch Footer
                                                <small class="text-muted">minimum image size 800 x 100</small>
                                            </label>
                                            <div class="border border-secondary rounded d-flex align-items-center justify-content-center"
                                                style="height: 150px; background-color: #f9f9f9;position: relative">
                                                <input type="file" class="form-control"
                                                    style="opacity: 0; height: 150px; width: 100%; position: absolute;"
                                                    id="footer_image" name="footer_image"
                                                    onchange="previewImage(event, 'footerimagePreview')" accept="image/*">
                                                <p class="text-muted m-0">Drop in your image or click the box to add one!</p>
                                            </div>
                                            <!-- Image Preview Box -->
                                            <div style="margin-top: 10px;">
                                                <img id="footerimagePreview" src="<?php echo $edit->order_footer_logo && $edit->order_footer_logo_url ? $edit->order_footer_logo_url : '#'; ?>" alt="Image Preview"
                                                    style="<?php echo $edit->order_footer_logo ? 'block' : 'none'; ?>; width: 200px;  object-fit: cover; border: 1px solid #ddd;"
                                                    onerror="this.onerror=null; this.src=''; this.style.display='none';">
                                                <input type="hidden" name="footer_image_old"
                                                    value="{{ $edit->order_footer_logo }}">
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-sm-12 mb-3">
                                            <label class="form-label">
                                                Company Brochure (WhatsApp Auto-Reply PDF)
                                                <small class="text-muted">PDF format</small>
                                            </label>
                                            <div class="border border-secondary rounded d-flex align-items-center justify-content-center"
                                                style="height: 150px; background-color: #f9f9f9;position: relative">
                                                <input type="file" class="form-control"
                                                    style="opacity: 0; height: 150px; width: 100%; position: absolute;"
                                                    id="brochure" name="brochure"
                                                    onchange="previewBrochure(event)" accept="application/pdf">
                                                <p class="text-muted m-0 text-center px-2" id="brochureDropText">
                                                    <i class="fa-solid fa-file-pdf text-danger fa-2x mb-1 d-block"></i>
                                                    Drop in your PDF brochure or click to upload
                                                </p>
                                            </div>
                                            <!-- Brochure Preview / Info Box -->
                                            <div style="margin-top: 10px;" id="brochureInfoBox">
                                                @if(!empty($edit->brochure) && file_exists(public_path($edit->brochure)))
                                                    <div class="d-flex align-items-center justify-content-between p-2 border rounded bg-light">
                                                        <div class="d-flex align-items-center text-truncate me-2">
                                                            <i class="fa-solid fa-file-pdf text-danger fs-4 me-2"></i>
                                                            <span class="text-truncate fw-medium" title="{{ basename($edit->brochure) }}">{{ basename($edit->brochure) }}</span>
                                                        </div>
                                                        <a href="{{ asset($edit->brochure) }}" target="_blank" class="btn btn-sm btn-outline-primary text-nowrap">
                                                            <i class="fa-solid fa-eye me-1"></i> View PDF
                                                        </a>
                                                    </div>
                                                @endif
                                                <input type="hidden" name="brochure_old" value="{{ $edit->brochure }}">
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-sm-12 mb-3">
                                            <label class="form-label">
                                                Rate Sheet (Excel File)
                                                <small class="text-muted">.xlsx / .xls format</small>
                                            </label>
                                            <div class="border border-secondary rounded d-flex align-items-center justify-content-center"
                                                style="height: 150px; background-color: #f9f9f9;position: relative">
                                                <input type="file" class="form-control"
                                                    style="opacity: 0; height: 150px; width: 100%; position: absolute; cursor: pointer;"
                                                    id="rate_sheet" name="rate_sheet"
                                                    onchange="previewRateSheet(event)" accept=".xlsx, .xls">
                                                <p class="text-muted m-0 text-center px-2" id="rateSheetDropText">
                                                    <i class="fa-solid fa-file-excel text-success fa-2x mb-1 d-block"></i>
                                                    Drop in your Excel rate sheet or click to upload
                                                </p>
                                            </div>
                                            <!-- Rate Sheet Preview / Info Box -->
                                            <div style="margin-top: 10px;" id="rateSheetInfoBox">
                                                @php
                                                    $hasRateSheet = false;
                                                    $rateSheetPath = '';
                                                    $rateSheetName = '';
                                                    $rateSheetSize = '';
                                                    $rateSheetTime = '';
                                                    
                                                    if (!empty($edit->rate_sheet) && file_exists(public_path($edit->rate_sheet))) {
                                                        $hasRateSheet = true;
                                                        $rateSheetPath = asset($edit->rate_sheet);
                                                        $rateSheetName = basename($edit->rate_sheet);
                                                        $rateSheetSize = round(filesize(public_path($edit->rate_sheet)) / 1024, 1) . ' KB';
                                                        $rateSheetTime = date('d-m-Y H:i', filemtime(public_path($edit->rate_sheet)));
                                                    }
                                                @endphp

                                                @if($hasRateSheet)
                                                    <div class="d-flex align-items-center justify-content-between p-2 border rounded bg-light" id="existingRateSheetContainer">
                                                        <div class="d-flex align-items-center text-truncate me-2">
                                                            <i class="fa-solid fa-file-excel text-success fs-4 me-2"></i>
                                                            <div class="text-truncate">
                                                                <span class="text-truncate fw-medium d-block" title="{{ $rateSheetName }}" style="font-size: 13px;">{{ $rateSheetName }}</span>
                                                                <small class="text-muted" style="font-size: 11px;">{{ $rateSheetSize }} &bull; {{ $rateSheetTime }}</small>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-1">
                                                            <a href="{{ $rateSheetPath }}" class="btn btn-sm btn-outline-success text-nowrap" title="Download Rate Sheet">
                                                                <i class="fa-solid fa-download me-1"></i> Download
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-outline-danger text-nowrap" id="btnRemoveRateSheet" onclick="removeRateSheet()" title="Remove Rate Sheet">
                                                                <i class="fa-solid fa-trash me-1"></i> Remove
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif
                                                <input type="hidden" name="rate_sheet_removed" id="rate_sheet_removed" value="0">
                                                <input type="hidden" name="rate_sheet_old" id="rate_sheet_old" value="{{ $edit->rate_sheet }}">
                                            </div>
                                        </div>

                                        <div class="col-12 d-flex ">
                                            <button type="submit" id="submit" class="btn btn-primary btn-submit waves-effect waves-light">Submit</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endif

                        @if(request('tab') === 'integration-tab')
                            <div class="tab-pane fade {{ request('tab') === 'integration-tab' ? 'show active' : '' }}" id="integration-tab">
                                <!-- Third Party Integration Content -->
                                <form id="integrationForm" action="{{ route($modules['route'] . '.thirdpartyintegration', [$edit?->id]) }}" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="team_set" value="team_update" />
                                    <input type="hidden" name="tab" value="integration-tab" />
                                    <input type="hidden" name="company_id" value="{{ $edit?->id ?? '' }}" />
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="nav-align-top">
                                            <!-- Tabs -->
                                            <ul class="nav nav-pills mb-3" id="integration-tabs" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                <button class="nav-link active" id="indiamart-tab" data-bs-toggle="pill" data-bs-target="#indiamart" type="button" role="tab"> IndiaMart</button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="tradeindia-tab" data-bs-toggle="pill" data-bs-target="#tradeindia" type="button" role="tab"> TradeIndia</button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="justdial-tab" data-bs-toggle="pill" data-bs-target="#justdial" type="button" role="tab"> JustDial</button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="linkedin-tab" data-bs-toggle="pill" data-bs-target="#linkedin" type="button" role="tab"> LinkedIn</button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="facebook-tab" data-bs-toggle="pill" data-bs-target="#facebook" type="button" role="tab"> Facebook</button>
                                                </li>
                                            </ul>

                                                <!-- Tab content -->
                                                <div class="tab-content">

                                                    <div class="tab-pane fade show active" id="indiamart" role="tabpanel">
                                                        <div class="row mb-3">
                                                            <div class="col-md-4 col-sm-12">
                                                                <label class="form-label">Platform</label>
                                                                <input type="text" readonly class="form-control" value="IndiaMart"
                                                                    name="indiamart_platform">
                                                            </div>
                                                            <div class="col-md-4 col-sm-12">
                                                                <label class="form-label">API Key</label>
                                                                <input type="text" class="form-control" name="indiamart_api_key"
                                                                    value="{{ old('indiamart_api_key', $company_social['indiamart']->api_key ?? '') }}"
                                                                    placeholder="IndiaMart API Key">
                                                            </div>
                                                            <div class="col-md-4 col-sm-12">
                                                                <label class="form-label">API Run Time (In minutes)</label>
                                                                <div class="input-group">
                                                                    <input type="text" class="form-control" name="indiamart_minutes_time"
                                                                        value="{{ old('indiamart_minutes_time', $company_social['indiamart']->minutes_time ?? '') }}"
                                                                        placeholder="IndiaMart API Run Time">
                                                                    <span class="input-group-text">days</span>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 d-flex justify-content-end">
                                                                <button type="submit" id="submit"
                                                                    class="btn btn-primary btn-submit waves-effect waves-light mt-3">Submit</button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="tab-pane fade" id="tradeindia" role="tabpanel">
                                                        <div class="row mb-3">
                                                            <div class="col-md-4 col-sm-12">
                                                                <label class="form-label">Platform</label>
                                                                <input type="text" readonly class="form-control" value="TradeIndia"
                                                                    name="tradeindia_platform">
                                                            </div>
                                                            <div class="col-md-4 col-sm-12">
                                                                <label class="form-label">API Key</label>
                                                                <input type="text" class="form-control" name="tradeindia_api_key"
                                                                    value="{{ old('tradeindia_api_key', $company_social['tradeindia']->api_key ?? '') }}"
                                                                    placeholder="TradeIndia API Key">
                                                            </div>
                                                            <div class="col-md-4 col-sm-12">
                                                                <label class="form-label">User ID</label>
                                                                <input type="text" class="form-control" name="tradeindia_user_id"
                                                                    value="{{ old('tradeindia_user_id', $company_social['tradeindia']->user_id ?? '') }}"
                                                                    placeholder="TradeIndia User ID">
                                                            </div>
                                                            <div class="col-md-4 col-sm-12">
                                                                <label class="form-label">Profile ID</label>
                                                                <input type="text" class="form-control" name="tradeindia_profile_id"
                                                                    value="{{ old('tradeindia_profile_id', $company_social['tradeindia']->profile_id ?? '') }}"
                                                                    placeholder="TradeIndia Profile ID">
                                                            </div>
                                                            <div class="col-12 d-flex justify-content-end">
                                                                <button type="submit" id="submit"
                                                                    class="btn btn-primary btn-submit waves-effect waves-light mt-3">Submit</button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="tab-pane fade" id="justdial" role="tabpanel">
                                                        <div class="row mb-3">
                                                            <div class="col-md-4 col-sm-12">
                                                                <label class="form-label">Platform</label>
                                                                <input type="text" readonly class="form-control" value="JustDial"
                                                                    name="justdial_platform">
                                                            </div>
                                                            <div class="col-md-4 col-sm-12">
                                                                <label class="form-label">API Key</label>
                                                                <input type="text" class="form-control" name="justdial_api_key"
                                                                    value="{{ old('justdial_api_key', $company_social['justdial']->api_key ?? '') }}"
                                                                    placeholder="JustDial API Key">
                                                            </div>
                                                            <div class="col-12 d-flex justify-content-end">
                                                                <button type="submit" id="submit"
                                                                    class="btn btn-primary btn-submit waves-effect waves-light mt-3">Submit</button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="tab-pane fade" id="linkedin" role="tabpanel">
                                                        <div class="row mb-3">
                                                                <div class="col-md-4 col-sm-12">
                                                                    <label class="form-label">Platform</label>
                                                                    <input type="text" readonly class="form-control" value="LinkedIn"
                                                                        name="linkedin_platform">
                                                                </div>
                                                                <div class="col-md-4 col-sm-12">
                                                                    <label class="form-label">API Key</label>
                                                                    <input type="text" class="form-control" name="linkedin_api_key"
                                                                        value="{{ old('linkedin_api_key', $company_social['linkedin']->api_key ?? '') }}"
                                                                        placeholder="LinkedIn API Key">
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-end">
                                                                    <button type="submit" id="submit"
                                                                        class="btn btn-primary btn-submit waves-effect waves-light mt-3">Submit</button>
                                                                </div>
                                                        </div>
                                                    </div>

                                                    <div class="tab-pane fade" id="facebook" role="tabpanel">
                                                        <div class="row mb-3">
                                                            <div class="col-md-4 col-sm-12">
                                                                <label class="form-label">Platform</label>
                                                                <input type="text" readonly class="form-control" value="Facebook"
                                                                    name="facebook_platform">
                                                            </div>
                                                            <div class="col-md-4 col-sm-12">
                                                                <label class="form-label">API Key</label>
                                                                <input type="text" class="form-control" name="facebook_api_key"
                                                                    value="{{ old('facebook_api_key', $company_social['facebook']->api_key ?? '') }}"
                                                                    placeholder="Facebook API Key">
                                                            </div>
                                                            <div class="col-12 d-flex justify-content-end">
                                                                <button type="submit" id="submit"
                                                                    class="btn btn-primary btn-submit waves-effect waves-light mt-3">Submit</button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endif

                        @if(request('tab') === 'mail-tab')
                        <div class="tab-pane fade {{ request('tab') === 'mail-tab' ? 'show active' : '' }}" id="mail-tab">
                            <!-- Mail Config Content -->
                            <form id="myEmailSettingForm" action="{{ route($modules['route'] . '.mail_setting', [$edit?->id]) }}" method="POST"
                                    enctype="multipart/form-data">
                                    <input type="hidden" name="team_set" value="team_update" />
                                    <input type="hidden" name="tab" value="mail-tab" />
                                    <input type="hidden" name="company_id" value="{{ $edit?->id ?? '' }}" />
                                    @csrf
                            <div class="row g-7">
                                <div class="col-md-4 col-sm-12 mb-3">
                                    <label class="form-label" for="mailer">Mailer</label>
                                    <input type="text" id="mailer" name="mailer"
                                        class="form-control @error('mailer') is-invalid @enderror"
                                        value="{{ old('mailer', $mail_setting->mailer ?? '') }}" placeholder="Mailer" />
                                    @error('mailer')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 mb-3">
                                    <label class="form-label" for="host">Host</label>
                                    <input type="text" id="host" name="host"
                                        class="form-control @error('host') is-invalid @enderror"
                                        value="{{ old('host', $mail_setting?->host ?? '') }}" placeholder="Host" />
                                    @error('host')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 mb-3">
                                    <label class="form-label" for="app_key">Port</label>
                                    <input type="text" id="port" name="port"
                                        class="form-control @error('port') is-invalid @enderror"
                                        value="{{ old('port', $mail_setting?->port ?? '') }}" placeholder="Port" />
                                    @error('port')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 mb-3">
                                    <label class="form-label" for="username">Username</label>
                                    <input type="text" id="username" name="username"
                                        class="form-control @error('username') is-invalid @enderror"
                                        value="{{ old('host', $mail_setting?->username ?? '') }}" placeholder="Username" />
                                    @error('username')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>


                                <div class="col-md-4 col-sm-12 mb-3">
                                    <label class="form-label" for="mail_password">Password</label>
                                    <input type="text" id="mail_password" name="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        value="{{ old('host', $mail_setting?->password ?? '') }}" placeholder="Password" />
                                    @error('password')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 mb-3">
                                    <label class="form-label" for="encryption">Encryption</label>
                                    <select id="encryption" name="encryption"
                                        class="form-select @error('encryption') is-invalid @enderror">
                                        <option value="tls"
                                            {{ old('encryption', $mail_setting?->encryption) == 'tls' ? 'selected' : '' }}>TLS
                                        </option>
                                        <option value="ssl"
                                            {{ old('encryption', $mail_setting?->encryption) == 'ssl' ? 'selected' : '' }}>SSL
                                        </option>
                                    </select>
                                    @error('encryption')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 mb-3">
                                    <label class="form-label" for="from_address">From Address</label>
                                    <input type="text" id="from_address" name="from_address"
                                        class="form-control @error('from_address') is-invalid @enderror"
                                        value="{{ old('from_address', $mail_setting->from_address ?? '') }}"
                                        placeholder="From Address" />
                                    @error('from_address')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 mb-3">
                                    <label class="form-label" for="from_name">From Name</label>
                                    <input type="text" id="from_name" name="from_name"
                                        class="form-control @error('from_name') is-invalid @enderror"
                                        value="{{ old('from_name', $mail_setting->from_name ?? '') }}"
                                        placeholder="From Name" />
                                    @error('from_name')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4 col-sm-12 mb-3">
                                    <label class="form-label" for="bcc">BCC <small class="muted">Press Enter add More
                                            than One BCC
                                            Mail</small></label>
                                    <input type="text" id="bcc" name="bcc" data-role="tagsinput"
                                        class="input-tags form-control  @error('bcc') is-invalid @enderror"
                                        value="{{ old('bcc', $mail_setting->bcc ?? '') }}" />
                                    @error('bcc')
                                        <span class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- Submit and Cancel Buttons -->
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" name="btn_submit" value="submit_and_exit"
                                        class="btn btn-primary btn-submit waves-effect waves-light mt-3">
                                        Submit
                                    </button>
                                </div>
                                </div>
                            </form>
                        </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(request('tab') === 'subscription-tab')
            <div class="tab-pane fade {{ request('tab') === 'subscription-tab' ? 'show active' : '' }}" id="subscription-tab">
                <!-- Subscription History -->
                <div class="row my-3">
                    <div class="col-md-12 mb-5" id="filter_section">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-2">
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
                                <div class="card-datatable table-responsive" style="overflow-x: unset;">
                                    <table id="yajra-datatables" class="dt-responsive table table-hover">
                                            <thead>
                                            <tr>
                                                <th>Plan Name</th>
                                                <th>Plan From</th>
                                                <th>Plan To</th>
                                                <th>Plan Expiry Date</th>
                                                <th>Subscription Status</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>


@endsection

@section('page_leavel_script')
<!-- Data tables -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endsection

@push('page_scripts')

@include('utils.getPlans')
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        initializeCKEditor(".ckeditor_common_cls");

        function previewImage(event, previewId) {
            const input = event.target;
            const preview = document.getElementById(previewId);
            const file = input.files[0];

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                preview.src = '#';
                preview.style.display = 'none';
            }
        }

        function previewBrochure(event) {
            const input = event.target;
            const file = input.files[0];
            const dropText = document.getElementById('brochureDropText');
            const infoBox = document.getElementById('brochureInfoBox');

            if (file) {
                if (dropText) {
                    dropText.innerHTML = '<i class="fa-solid fa-file-pdf text-danger fa-2x mb-1 d-block"></i><span class="text-primary fw-bold">' + file.name + '</span><br><small class="text-muted">' + (file.size / 1024 / 1024).toFixed(2) + ' MB</small>';
                }
                if (infoBox) {
                    infoBox.innerHTML = '<div class="alert alert-success py-2 px-3 m-0 small"><i class="fa-solid fa-check-circle me-1"></i> Selected: ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)</div>';
                }
            }
        }

        function previewRateSheet(event) {
            const input = event.target;
            const file = input.files[0];
            const dropText = document.getElementById('rateSheetDropText');
            const infoBox = document.getElementById('rateSheetInfoBox');

            if (file) {
                if (dropText) {
                    dropText.innerHTML = '<i class="fa-solid fa-file-excel text-success fa-2x mb-1 d-block"></i><span class="text-success fw-bold">' + file.name + '</span><br><small class="text-muted">' + (file.size / 1024 / 1024).toFixed(2) + ' MB</small>';
                }
                if (infoBox) {
                    infoBox.innerHTML = '<div class="d-flex align-items-center justify-content-between p-2 border rounded bg-light">' +
                        '<div class="d-flex align-items-center text-truncate me-2">' +
                            '<i class="fa-solid fa-file-excel text-success fs-4 me-2"></i>' +
                            '<div class="text-truncate">' +
                                '<span class="text-truncate fw-medium d-block" style="font-size: 13px;">' + file.name + '</span>' +
                                '<small class="text-muted" style="font-size: 11px;">' + (file.size / 1024).toFixed(1) + ' KB &bull; Selected (Click Submit to save)</small>' +
                            '</div>' +
                        '</div>' +
                        '<div>' +
                            '<button type="button" class="btn btn-sm btn-outline-danger text-nowrap" onclick="clearSelectedRateSheet()" title="Remove selected file">' +
                                '<i class="fa-solid fa-trash me-1"></i> Remove' +
                            '</button>' +
                        '</div>' +
                    '</div>' +
                    '<input type="hidden" name="rate_sheet_removed" id="rate_sheet_removed" value="0">' +
                    '<input type="hidden" name="rate_sheet_old" id="rate_sheet_old" value="">';
                }
            }
        }

        function clearSelectedRateSheet() {
            const input = document.getElementById('rate_sheet');
            if (input) input.value = '';
            const dropText = document.getElementById('rateSheetDropText');
            if (dropText) {
                dropText.innerHTML = '<i class="fa-solid fa-file-excel text-success fa-2x mb-1 d-block"></i> Drop in your Excel rate sheet or click to upload';
            }
            const infoBox = document.getElementById('rateSheetInfoBox');
            if (infoBox) {
                infoBox.innerHTML = '<input type="hidden" name="rate_sheet_removed" id="rate_sheet_removed" value="0"><input type="hidden" name="rate_sheet_old" id="rate_sheet_old" value="">';
            }
        }

        function removeRateSheet() {
            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you want to remove this Rate Sheet?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, remove it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('company.remove-rate-sheet', $edit->id ?? 0) }}",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        beforeSend: function() {
                            $('#btnRemoveRateSheet').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Removing...');
                        },
                        success: function(response) {
                            if (response.status) {
                                $('#rateSheetInfoBox').html('<input type="hidden" name="rate_sheet_removed" id="rate_sheet_removed" value="1"><input type="hidden" name="rate_sheet_old" id="rate_sheet_old" value="">');
                                $('#rate_sheet').val('');
                                $('#rateSheetDropText').html('<i class="fa-solid fa-file-excel text-success fa-2x mb-1 d-block"></i> Drop in your Excel rate sheet or click to upload');
                                Swal.fire({
                                    title: 'Removed!',
                                    text: response.message || 'Rate sheet has been removed successfully.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire('Error!', response.message || 'Failed to remove rate sheet.', 'error');
                                $('#btnRemoveRateSheet').prop('disabled', false).html('<i class="fa-solid fa-trash me-1"></i> Remove');
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error!', 'Something went wrong while removing rate sheet.', 'error');
                            $('#btnRemoveRateSheet').prop('disabled', false).html('<i class="fa-solid fa-trash me-1"></i> Remove');
                        }
                    });
                }
            });
        }

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
                url: "{{ route('company.detail', $edit?->id) }}",
                type: "GET",
                beforeSend: function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                },
                data: function(data) {
                    data.search = $('input[name="search"]').val();
                    data.filter_plan = $('select[name="plan_id"] option:selected').val();
                    data.filter_plan_date = $('input[name="plan_date"]').val().replace(' - ', ' to ');
                    data.filter_subscription_status_id = $('select[name="subscription_status_id"] option:selected').val();
                    data.tab = 'subscription-tab';
                },
                dataSrc: function(json) {
                    if (json.meta) {
                        $('#company-info-name').text(json.meta.company_name);
                        $('#company-info-plan').text(json.meta.plan_name);
                        $('#company-info-email').text(json.meta.company_email);
                    }
                    return json.data;
                }
            },
            columns: [
                // { data: 'company_arrow', name: 'company_id' },
                { data: 'company_arrow', name: 'plan_id' },
                { data: 'plan_from', name: 'plan_from' },
                { data: 'plan_to', name: 'plan_to' },
                { data: 'plan_expiry_date', name: 'plan_expiry_date' },
                { data: 'subscription_status', name: 'subscription_status' },
            ],
            createdRow: function (row, data) {
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

    setTimeout(function() {
        $("#filter_section").show();
    }, 1000);

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
            let today =  moment().add(2, 'year').endOf('year');

            picker.setStartDate(startOfMonth);
            picker.setEndDate(today);
            picker.callback(startOfMonth, today, 'This Month');
        }
        dtable.draw();
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
            url: "{{ route('company-subscription-plan.get_subscription_addons') }}",
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
                        innerTableHtml += '<tr class="inner-table-row" style="background-color:#e2e6e8;"><td colspan="6"><table class="table table-bordered mb-0"><thead><tr><th colspan="6" style="text-align: center;"><h5 style="font-weight: bold;">Subscription Plan Addon Days</h5></th></tr><tr><th>Plan Name</th><th>Plan From</th><th>Plan Expire</th><th>Add Days</th><th>Created Date</th></tr></thead><tbody>';

                        $.each(response.data, function(index, item) {
                            innerTableHtml += '<tr>';
                                    // innerTableHtml += '<td>'+ item.company?.company_name ?? +'</td>';
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
@endpush
