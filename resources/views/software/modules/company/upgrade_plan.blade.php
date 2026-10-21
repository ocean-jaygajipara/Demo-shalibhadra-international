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
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [
                ['title' => $page_title, 'url' => route($route . '.index')],
                [
                    'title' => 'Upgrade Plan',
                    'url' => '',
                ],
            ],
            'route' => $route,
            'show_add_btn' => false,
            'show_back_btn' => true,
        ])
    </div>

    <!-- Default -->
    <div class="row my-3">
        <!-- Default Wizard -->
        <div class="col-12 mb-6">
            <div class="card ">
                <div class="card-header border-bottom">
                    <div class="d-flex justify-content-between ">
                        <h4 class="mb-0">Company name :- {{ $company?->company_name ?? '' }}</h4>
                        <h4 class="mb-0">Upgrade Plan</h4>
                    </div>
                </div>

                <div class="card-body mt-3">
                    <form id="myEmailSettingForm" action="{{ $form_route ?? '#' }}" method="POST"
                        enctype="multipart/form-data">
                        <input type="hidden" name="company_id" value="{{ $company?->id ?? '' }}" />
                        <input type="hidden" name="is_plan_expire" value="{{ (isset($is_plan_expire) && !empty($is_plan_expire)) ? $is_plan_expire : '' }}" />
                        @csrf

                        <!-- License Setting -->
                        <div class="row g-7 {{ $maring_bottom }}">

                            @if(isset($is_plan_expire) && $is_plan_expire == 'true')
                                <div class="col-md-3 col-sm-12 mb-2">
                                    <div class="form-group">
                                        <label class="form-label">Select Plan <span class="text-danger">*</span></label>
                                        <select class="form-control select2 w-100 @error('plan') is-invalid @enderror required"
                                            name="plan_id" data-name="plan" id="plan_id">
                                            <option value="">Select Plan</option>
                                            @foreach ($plans as $row_plan)
                                                <option value="{{ $row_plan->id }}" data-plan_json="{{ json_encode($row_plan); }}">{{ $row_plan->name }}</option>
                                            @endforeach
                                        </select>

                                        @error('plan_id')
                                            <span class="invalid-feedback d-block">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>


                                <div class="col-md-6 col-sm-12">
                                    <div class="row" id="plan_details_section" style="display:none;">

                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-12"></div>

                            @endif


                            <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                <label class="form-label" for="app_key">App Key</label>
                                <input type="text" id="app_key" name="app_key"
                                    class="form-control @error('app_key') is-invalid @enderror"
                                    value="{{ $company?->app_key }}" placeholder="App Key" />
                                @error('app_key')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                <label class="form-label" for="panel_url">Panel URL</label>
                                <input type="text" id="panel_url" name="panel_url"
                                    class="form-control @error('panel_url') is-invalid @enderror"
                                    value="{{ $company?->panel_url }}" placeholder="Panel URL" />
                                @error('panel_url')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <!-- Submit and Cancel Buttons -->
                            <div class="col-sm-12 text-center {{ $maring_bottom }}">
                                <button type="submit" value="submit" class="btn btn-success mt-1 mb-1">
                                    {{ isset($mail_setting) && isset($mail_setting?->id) ? 'Update' : 'Submit' }}
                                </button>

                                <button type="submit" name="btn_submit" value="submit_and_exit" class="btn btn-primary mt-1 mb-1">
                                    Submit and Exit
                                </button>

                                <a href="{{ route($route . '.index') }}" class="btn btn-danger mt-1 mb-1">Cancel</a>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- /Default Wizard -->
    </div>
@endsection

@section('page_leavel_script')
    <script>
        $(document).on('change', '#plan_id', function() {
            const selectedPlans = this.options[this.selectedIndex];
            const jsonDataStr = selectedPlans.getAttribute('data-plan_json');

            var html = '';
            if (jsonDataStr) {
                const planData = JSON.parse(jsonDataStr);

                html += '<h4>Plan Details</h4>';
                html += '<div class="col-md-3">';
                html += '<p><strong>Valid Days:</strong> <span>' + planData.plan_valid_day + '</span></p>';
                html += '<p><strong>Max Team Users:</strong> <span>' + planData.max_team_user_count + '</span></p>';
                html += '<p><strong>Max Customers:</strong> <span>' + planData.max_customer_count + '</span></p>';
                html += '</div>';

                html += '<div class="col-md-3">';
                html += '<p><strong>Max Inquiries:</strong> <span>' + planData.max_inquiry_count + '</span></p>';
                html += '<p><strong>Plan Type:</strong> <span>' + planData.plan_type + '</span></p>';
                html += '</div>';

                $("#plan_details_section").html(html).show();
            } else {
                $("#plan_details_section").html('').hide();
            }
        });
    </script>
@endsection
