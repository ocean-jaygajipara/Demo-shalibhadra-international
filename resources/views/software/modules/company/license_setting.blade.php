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
                    'title' => 'License Setting',
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
                        <h4 class="mb-0">License Setting</h4>
                    </div>
                </div>

                <div class="card-body mt-3">
                    <form id="myEmailSettingForm" action="{{ $form_route ?? '#' }}" method="POST"
                        enctype="multipart/form-data">
                        <input type="hidden" name="company_id" value="{{ $company?->id ?? '' }}" />
                        @csrf

                        <!-- License Setting -->
                        <div class="row g-7 {{ $maring_bottom }}">

                            {{-- <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                <label class="form-label" for="plan">Select Plan</label>
                                <select id="plan" class="form-select @error('plan_id') is-invalid @enderror"
                                    name="plan_id" disabled>
                                    @if (isset($plans))
                                        @foreach ($plans as $plan)
                                            <option value="{{ $plan->id }}"
                                                {{ $company?->plan_id == $plan->id ? 'selected' : '' }}>{{ $plan->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('plan_id')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div> --}}

                            <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                <label class="form-label" for="plan_id">Current Plan Name</label>
                                <input type="hidden" id="plan_id" name="plan_id"
                                    class="form-control readonly-look @error('plan_id') is-invalid @enderror"
                                    value="{{ $company?->plan_id ?? '' }}" placeholder="Plan name" />
                                <input type="text"
                                    class="form-control readonly-look @error('plan_id') is-invalid @enderror"
                                    value="{{ $company?->plan?->name ?? '' }}" placeholder="Plan name" />
                                @error('plan_id')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                <label class="form-label" for="plan_from">Plan From</label>
                                <input type="text" id="plan_from" name="plan_from"
                                    class="form-control datepicker plan-form readonly-look @error('plan_from') is-invalid @enderror"
                                    value="{{ $company?->plan_from }}" placeholder="Plan From" />
                                @error('plan_from')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                <label class="form-label" for="plan_to">Plan To</label>
                                <input type="text" id="plan_to" name="plan_to"
                                    class="form-control datepicker plan-to readonly-look @error('plan_to') is-invalid @enderror"
                                    value="{{ $company?->plan_to }}" placeholder="Plan To" />
                                @error('plan_to')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

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


                            <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                <label class="form-label" for="max_team_user_count">Max. Team User Count</label>
                                <input type="text" id="max_team_user_count" name="max_team_user_count"
                                    class="form-control @error('max_team_user_count') is-invalid @enderror"
                                    value="{{ $company?->max_team_user_count }}" placeholder="Max. Team User Count"
                                    onkeypress="return isNumber(event)" />
                                @error('max_team_user_count')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                <label class="form-label" for="max_customer_count">Max. Customer Count</label>
                                <input type="text" id="max_customer_count" name="max_customer_count"
                                    class="form-control @error('max_customer_count') is-invalid @enderror"
                                    value="{{ $company?->max_customer_count }}" placeholder="Max. Customer Count"
                                    onkeypress="return isNumber(event)" />
                                @error('max_customer_count')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-3 col-sm-12 {{ $maring_bottom }}">
                                <label class="form-label" for="max_inquiry_count">Max. Inquiry Count</label>
                                <input type="text" id="max_inquiry_count" name="max_inquiry_count"
                                    class="form-control @error('max_inquiry_count') is-invalid @enderror"
                                    value="{{ $company?->max_inquiry_count }}" placeholder="Max. Inquiry Count"
                                    onkeypress="return isNumber(event)" />
                                @error('max_inquiry_count')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            {{-- Application  Right --}}

                        <div class="d-flex align-items-start gap-3">

                            <input type="hidden" id="app_right" name="app_right" value=""/>
                            {{-- Left Box --}}
                            <div class="w-50">
                                <label class="form-label">Application Right</label>
                                <div class="p-3 border rounded mb-3" style="min-height: 300px; max-height: 300px; overflow-y: auto;">
                                <div class="select2-primary">
                                    @foreach ($app_right_list as $main_menu)
                                        @foreach ($main_menu?->sub_menu as $sub_menu)
                                            @php
                                                $selected = in_array($sub_menu->id,  $company?->app_right) ?? [];
                                            @endphp

                                            @if (!$selected)
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary app-right-item me-2 mb-2"
                                                    data-id="{{ $sub_menu->id }}"
                                                    data-name="{{ $main_menu?->name }} - {{ $sub_menu->name }}">
                                                    {{ $main_menu?->name }} - {{ $sub_menu->name }}
                                                </button>
                                            @endif
                                        @endforeach
                                    @endforeach
                                </div>
                                </div>
                            </div>


                            {{-- Arrow Buttons --}}
                            <div class="d-flex flex-column justify-content-center align-items-center gap-2 mt-4">
                                <button type="button" id="moveRight" class="btn btn-primary">&gt;&gt;</button>
                                <button type="button" id="moveLeft" class="btn btn-danger">&lt;&lt;</button>
                            </div>

                            {{-- Right Box --}}
                            <div class="w-50">
                                <label class="form-label">Application Right</label>
                                <div class="p-3 border rounded mb-3" style="min-height: 300px; max-height: 300px; overflow-y: auto;">
                                <div class="select2-primary">
                                    @foreach ($app_right_list as $main_menu)
                                        @foreach ($main_menu?->sub_menu as $sub_menu)
                                            @php
                                                $selected = in_array($sub_menu->id,  $company?->app_right) ?? [];
                                            @endphp

                                            @if ($selected)
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary app-right-item-fill me-2 mb-2"
                                                    data-id="{{ $sub_menu->id }}"
                                                    data-name="{{ $main_menu?->name }} - {{ $sub_menu->name }}">
                                                    {{ $main_menu?->name }} - {{ $sub_menu->name }}
                                                </button>
                                            @endif
                                        @endforeach
                                    @endforeach
                                </div>
                                </div>

                            </div>
                        </div>


                            {{-- <div class="col-md-12 col-sm-12 mb-4">
                                <div class="form-group">
                                    <label class="form-label">Application Right</label>
                                    <div class="select2-primary">
                                        <select id="app_right" class="select2 form-select" multiple name="app_right[]">
                                            @foreach ($app_right_list as $main_menu)
                                                <optgroup label="{{ $main_menu?->name }}">
                                                    @foreach ($main_menu?->sub_menu as $sub_menu)
                                                        <option value="{{ $sub_menu->id }}"
                                                            {{ in_array($sub_menu->id, old('app_right', $company?->app_right ?? []) ?? []) ? 'selected' : '' }}>
                                                            {{ $main_menu?->name . ' - ', '' }} {{ $sub_menu->name }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('app_right')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div> --}}


                            {{-- Panel Right --}}
                            <div class="d-flex align-items-start gap-3">

                                <input type="hidden" id="panel_right" name="panel_right" value=""/>
                                {{-- Left Box --}}
                                <div class="w-50">
                                    <label class="form-label">Panel Right</label>
                                    <div class="p-3 border rounded mb-3" style="min-height: 300px; max-height: 300px; overflow-y: auto;">
                                    <div class="select2-primary-panel">
                                        @foreach ($panel_right_list as $main_menu)
                                            @foreach ($main_menu?->sub_menu as $sub_menu)
                                                @php
                                                    $selected = in_array($sub_menu->id,  $company?->panel_right) ?? [];
                                                @endphp

                                                @if (!$selected)
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-primary panel-right-item me-2 mb-2"
                                                        data-id="{{ $sub_menu->id }}"
                                                        data-name="{{ $main_menu?->name }} - {{ $sub_menu->name }}">
                                                        {{ $main_menu?->name }} - {{ $sub_menu->name }}
                                                    </button>
                                                @endif
                                            @endforeach
                                        @endforeach
                                    </div>
                                    </div>
                                </div>


                                {{-- Arrow Buttons --}}
                                <div class="d-flex flex-column justify-content-center align-items-center gap-2 mt-4">
                                    <button type="button" id="moveRightPanel" class="btn btn-primary">&gt;&gt;</button>
                                    <button type="button" id="moveLeftPanel" class="btn btn-danger">&lt;&lt;</button>
                                </div>

                                {{-- Right Box --}}
                                <div class="w-50">
                                    <label class="form-label">Panel Right</label>
                                    <div class="p-3 border rounded mb-3" style="min-height: 300px; max-height: 300px; overflow-y: auto;">
                                    <div class="select2-primary-panel">
                                        @foreach ($panel_right_list as $main_menu)
                                            @foreach ($main_menu?->sub_menu as $sub_menu)
                                                @php
                                                    $selected = in_array($sub_menu->id,  $company?->panel_right) ?? [];
                                                @endphp

                                                @if ($selected)
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-primary panel-right-item-fill me-2 mb-2"
                                                        data-id="{{ $sub_menu->id }}"
                                                        data-name="{{ $main_menu?->name }} - {{ $sub_menu->name }}">
                                                        {{ $main_menu?->name }} - {{ $sub_menu->name }}
                                                    </button>
                                                @endif
                                            @endforeach
                                        @endforeach
                                    </div>
                                    </div>

                                </div>
                            </div>



                            {{-- <div class="col-md-12 col-sm-12 mb-4">
                                <div class="form-group">
                                    <label class="form-label">Panel Right <span class="text-danger">*</span></label>
                                    <div class="select2-primary">
                                        <select id="panel_right" class="select2 form-select" multiple
                                            name="panel_right[]">
                                            @foreach ($panel_right_list as $main_menu)
                                                <optgroup label="{{ $main_menu?->name }}">
                                                    @foreach ($main_menu?->sub_menu as $sub_menu)
                                                        <option value="{{ $sub_menu->id }}"
                                                            {{ in_array($sub_menu->id, old('panel_right', $company?->panel_right ?? []) ?? []) ? 'selected' : '' }}>
                                                            {{ $main_menu?->name }} - {{ $sub_menu->name }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>

                                    @error('panel_right')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div> --}}


                            {{-- Customer Panel Right --}}
                            <div class="d-flex align-items-start gap-3">

                                <input type="hidden" id="customer_panel_right" name="customer_panel_right" value=""/>
                                {{-- Left Box --}}
                                <div class="w-50">
                                    <label class="form-label">Customer Panel Right</label>
                                    <div class="p-3 border rounded mb-3" style="min-height: 300px; max-height: 300px; overflow-y: auto;">
                                    <div class="select2-primary-customer-panel">
                                        @foreach ($panel_right_list as $main_menu)
                                            @foreach ($main_menu?->sub_menu as $sub_menu)
                                                @php
                                                    $selected = in_array($sub_menu->id,  $company?->customer_panel_right) ?? [];
                                                @endphp

                                                @if (!$selected)
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-primary customer-panel-right-item me-2 mb-2"
                                                        data-id="{{ $sub_menu->id }}"
                                                        data-name="{{ $main_menu?->name }} - {{ $sub_menu->name }}">
                                                        {{ $main_menu?->name }} - {{ $sub_menu->name }}
                                                    </button>
                                                @endif
                                            @endforeach
                                        @endforeach
                                    </div>
                                    </div>
                                </div>


                                {{-- Arrow Buttons --}}
                                <div class="d-flex flex-column justify-content-center align-items-center gap-2 mt-4">
                                    <button type="button" id="moveRightCustomerPanel" class="btn btn-primary">&gt;&gt;</button>
                                    <button type="button" id="moveLeftCustomerPanel" class="btn btn-danger">&lt;&lt;</button>
                                </div>

                                {{-- Right Box --}}
                                <div class="w-50">
                                    <label class="form-label">Customer Panel Right</label>
                                    <div class="p-3 border rounded mb-3" style="min-height: 300px; max-height: 300px; overflow-y: auto;">
                                    <div class="select2-primary-customer-panel">
                                        @foreach ($panel_right_list as $main_menu)
                                            @foreach ($main_menu?->sub_menu as $sub_menu)
                                                @php
                                                    $selected = in_array($sub_menu->id,  $company?->customer_panel_right) ?? [];
                                                @endphp

                                                @if ($selected)
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-primary customer-panel-right-item-fill me-2 mb-2"
                                                        data-id="{{ $sub_menu->id }}"
                                                        data-name="{{ $main_menu?->name }} - {{ $sub_menu->name }}">
                                                        {{ $main_menu?->name }} - {{ $sub_menu->name }}
                                                    </button>
                                                @endif
                                            @endforeach
                                        @endforeach
                                    </div>
                                    </div>

                                </div>
                            </div>




                            {{-- <div class="col-md-12 col-sm-12 mb-4">
                                <div class="form-group">
                                    <label class="form-label">Customer Panel Right </label>
                                    <div class="select2-primary">
                                        <select id="customer_panel_right" class="select2 form-select" multiple
                                            name="customer_panel_right[]">

                                            @foreach ($panel_right_list as $main_menu)
                                                <optgroup label="{{ $main_menu?->name }}">
                                                    @foreach ($main_menu?->sub_menu as $sub_menu)
                                                        <option value="{{ $sub_menu->id }}"
                                                            {{ in_array($sub_menu->id, old('customer_panel_right', $company?->customer_panel_right ?? []) ?? []) ? 'selected' : '' }}>
                                                            {{ $main_menu?->name }} - {{ $sub_menu->name }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>

                                    @error('customer_panel_right')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div> --}}

                            <!-- Submit and Cancel Buttons -->
                            <div class="col-sm-12 text-center {{ $maring_bottom }}">
                                <button type="submit" value="submit" class="btn btn-success mt-1 mb-1">
                                    {{ isset($mail_setting) && isset($mail_setting?->id) ? 'Update' : 'Submit' }}
                                </button>

                                <button type="submit" name="btn_submit" value="submit_and_exit"
                                    class="btn btn-primary mt-1 mb-1">
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
// application rights code
$(document).ready(function () {
    const $leftBox = $('.select2-primary').eq(0);
    const $rightBox = $('.select2-primary').eq(1);
    const $appRightInput = $('#app_right');

    function updateAppRightInput() {
        const ids = $rightBox.find('.app-right-item-fill').map(function () {
            return $(this).data('id');
        }).get();
        $appRightInput.val(ids.join(','));
    }

    function createButton(id, name, isFilled = false) {
        return $('<button/>', {
            type: 'button',
            class: `btn btn-sm btn-outline-primary ${isFilled ? 'app-right-item-fill' : 'app-right-item'} me-2 mb-2`,
            'data-id': id,
            'data-name': name,
            text: name
        });
    }

    function moveButtons($source, $target, sourceClass, targetClass) {
        $source.find(`.${sourceClass}`).each(function () {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const $btn = createButton(id, name, targetClass === 'app-right-item-fill');
            $target.append($btn);
            $(this).remove();
        });
        updateAppRightInput();
    }

    // Move single button from left to right
    $(document).on('click', '.app-right-item', function () {
        const $btn = createButton($(this).data('id'), $(this).data('name'), true);
        $rightBox.append($btn);
        $(this).remove();
        updateAppRightInput();
    });

    // Move single button from right to left
    $(document).on('click', '.app-right-item-fill', function () {
        const $btn = createButton($(this).data('id'), $(this).data('name'), false);
        $leftBox.append($btn);
        $(this).remove();
        updateAppRightInput();
    });

    // Move all from left to right
    $('#moveRight').on('click', function () {
        moveButtons($leftBox, $rightBox, 'app-right-item', 'app-right-item-fill');
    });

    // Move all from right to left
    $('#moveLeft').on('click', function () {
        moveButtons($rightBox, $leftBox, 'app-right-item-fill', 'app-right-item');
    });

    // Initial value update
    updateAppRightInput();
});


// panel rights code
$(document).ready(function () {
    const $leftBoxPanel = $('.select2-primary-panel').eq(0);
    const $rightBoxPanel = $('.select2-primary-panel').eq(1);
    const $panelRightInput = $('#panel_right');

    function updatePanelRightInput() {
        var ids2 = $rightBoxPanel.find('.panel-right-item-fill').map(function () {
            return $(this).data('id');
        }).get();
        $panelRightInput.val(ids2.join(','));
    }

    function createButtonPanel(id, name, isFilled = false) {
        return $('<button/>', {
            type: 'button',
            class: `btn btn-sm btn-outline-primary ${isFilled ? 'panel-right-item-fill' : 'panel-right-item'} me-2 mb-2`,
            'data-id': id,
            'data-name': name,
            text: name
        });
    }

    function moveButtonsPanel($source, $target, sourceClass, targetClass) {
        $source.find(`.${sourceClass}`).each(function () {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var $btn = createButtonPanel(id, name, targetClass === 'panel-right-item-fill');
            $target.append($btn);
            $(this).remove();
        });
        updatePanelRightInput();
    }

    // Move single button from left to right
    $(document).on('click', '.panel-right-item', function () {
        console.log($(this).data('id'))
        console.log($(this).data('name'))

        var $btn = createButtonPanel($(this).data('id'), $(this).data('name'), true);
        $rightBoxPanel.append($btn);
        $(this).remove();
        updatePanelRightInput();
    });

    // Move single button from right to left
    $(document).on('click', '.panel-right-item-fill', function () {
        var $btn = createButtonPanel($(this).data('id'), $(this).data('name'), false);
        $leftBoxPanel.append($btn);
        $(this).remove();
        updatePanelRightInput();
    });

    // Move all from left to right
    $('#moveRightPanel').on('click', function () {
        moveButtonsPanel($leftBoxPanel, $rightBoxPanel, 'panel-right-item', 'panel-right-item-fill');
    });

    // Move all from right to left
    $('#moveLeftPanel').on('click', function () {
        moveButtonsPanel($rightBoxPanel, $leftBoxPanel, 'panel-right-item-fill', 'panel-right-item');
    });

    // Initial value update
    updatePanelRightInput();
});

// customer panel rights code
$(document).ready(function () {
    const $leftBoxCustomerPanel = $('.select2-primary-customer-panel').eq(0);
    const $rightBoxCustomerPanel = $('.select2-primary-customer-panel').eq(1);
    const $customerPanelRightInput = $('#customer_panel_right');

    function updateCustomerPanelRightInput() {
        var ids3 = $rightBoxCustomerPanel.find('.customer-panel-right-item-fill').map(function () {
            return $(this).data('id');
        }).get();
        $customerPanelRightInput.val(ids3.join(','));
    }

    function createButtonCustomerPanel(id, name, isFilled = false) {
        return $('<button/>', {
            type: 'button',
            class: `btn btn-sm btn-outline-primary ${isFilled ? 'customer-panel-right-item-fill' : 'customer-panel-right-item'} me-2 mb-2`,
            'data-id': id,
            'data-name': name,
            text: name
        });
    }

    function moveButtonsCustomerPanel($source, $target, sourceClass, targetClass) {
        $source.find(`.${sourceClass}`).each(function () {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var $btn = createButtonCustomerPanel(id, name, targetClass === 'customer-panel-right-item-fill');
            $target.append($btn);
            $(this).remove();
        });
        updateCustomerPanelRightInput();
    }

    // Move single button from left to right
    $(document).on('click', '.customer-panel-right-item', function () {
        console.log($(this).data('id'))
        console.log($(this).data('name'))

        var $btn = createButtonCustomerPanel($(this).data('id'), $(this).data('name'), true);
        $rightBoxCustomerPanel.append($btn);
        $(this).remove();
        updateCustomerPanelRightInput();
    });

    // Move single button from right to left
    $(document).on('click', '.customer-panel-right-item-fill', function () {
        var $btn = createButtonCustomerPanel($(this).data('id'), $(this).data('name'), false);
        $leftBoxCustomerPanel.append($btn);
        $(this).remove();
        updateCustomerPanelRightInput();
    });

    // Move all from left to right
    $('#moveRightCustomerPanel').on('click', function () {
        moveButtonsCustomerPanel($leftBoxCustomerPanel, $rightBoxCustomerPanel, 'customer-panel-right-item', 'customer-panel-right-item-fill');
    });

    // Move all from right to left
    $('#moveLeftCustomerPanel').on('click', function () {
        moveButtonsCustomerPanel($rightBoxCustomerPanel, $leftBoxCustomerPanel, 'customer-panel-right-item-fill', 'customer-panel-right-item');
    });

    // Initial value update
    updateCustomerPanelRightInput();
});
</script>
@endsection
