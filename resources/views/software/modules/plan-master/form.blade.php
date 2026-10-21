@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
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
        <div class="card-body">
            <form action="{{ isset($edit) ? route($route . '.update', [$edit->id]) : route($route . '.store') }}"
                method="POST">
                @csrf
                @isset($edit)
                    @method('PUT')
                @endisset

                <div class="row">
                    {{-- Plan Name --}}
                    <div class="col-md-4 mb-4">
                        <div class="form-group">
                            <label class="form-label">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $edit->name ?? '') }}"
                                class="form-control @error('name') is-invalid @enderror" placeholder="Enter Plan Name">
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Valid Days --}}
                    <div class="col-md-4 mb-4">
                        <div class="form-group">
                            <label class="form-label">Valid Days <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="plan_valid_day"
                                    value="{{ old('plan_valid_day', $edit->plan_valid_day ?? '') }}"
                                    class="form-control @error('plan_valid_day') is-invalid @enderror"
                                    placeholder="Enter Plan Validity in Days">
                                <span class="input-group-text">days</span>
                            </div>
                            @error('plan_valid_day')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>


                    {{-- Max Team Users --}}
                    <div class="col-md-4 mb-4">
                        <div class="form-group">
                            <label class="form-label">Max Team Users <span class="text-danger">*</span></label>
                            <input type="number" name="max_team_user_count"
                                value="{{ old('max_team_user_count', $edit->max_team_user_count ?? '') }}"
                                class="form-control @error('max_team_user_count') is-invalid @enderror"
                                placeholder="Enter Max Team Users">
                            @error('max_team_user_count')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Max Customers --}}
                    <div class="col-md-4 mb-4">
                        <div class="form-group">
                            <label class="form-label">Max Customers <span class="text-danger">*</span></label>
                            <input type="number" name="max_customer_count"
                                value="{{ old('max_customer_count', $edit->max_customer_count ?? '') }}"
                                class="form-control @error('max_customer_count') is-invalid @enderror"
                                placeholder="Enter Max Customers">
                            @error('max_customer_count')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Max Inquiries --}}
                    <div class="col-md-4 mb-4">
                        <div class="form-group">
                            <label class="form-label">Max Inquiries <span class="text-danger">*</span></label>
                            <input type="number" name="max_inquiry_count"
                                value="{{ old('max_inquiry_count', $edit->max_inquiry_count ?? '') }}"
                                class="form-control @error('max_inquiry_count') is-invalid @enderror"
                                placeholder="Enter Max Inquiries">
                            @error('max_inquiry_count')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Plan Type --}}
                    <div class="col-md-4 mb-4">
                        <div class="form-group">
                            <label class="form-label"> Select Plan Type<span class="text-danger">*</span> </label>
                            <select id="plan_type"
                                class="form-control select2 w-100 @error('plan_type') is-invalid @enderror"
                                name="plan_type">
                                <option value="">Select Plan Type</option>
                                @foreach ($PlanType as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ old('plan_type', $edit->plan_type ?? '') == $key ? 'selected' : '' }}>
                                        {{ $value }}</option>
                                @endforeach
                            </select>

                            @error('plan_type')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    {{-- Status --}}
                    <div class="col-md-4 mb-4">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select class="form-control select2 w-100 @error('status') is-invalid @enderror" name="status"
                                required>
                                <option disabled selected>Select Status</option>
                                @foreach (['active', 'inactive'] as $status)
                                    <option value="{{ $status }}"
                                        @if (isset($edit)) @if ($edit->status == $status) {{ 'selected' }} @endif
                                    @else @if (old('status', 'active') == $status) {{ 'selected' }} @endif @endif> {{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Application Right --}}

                    <div class="d-flex align-items-start gap-3">

                        <input type="hidden" id="app_right" name="app_right" value=""/>
                        {{-- Left Box --}}
                        <div class="w-50">
                            <label class="form-label">Application Right</label>
                            <div class="p-3 border rounded mb-3" style="min-height: 300px; max-height: 300px; overflow-y: auto;">
                            <div class="select2-primary">
                                @foreach ($app_right_list as $item)
                                    @php
                                        $selected = isset($edit) && is_array($edit->app_right) && in_array($item->id, $edit->app_right);
                                    @endphp

                                    @if (!$selected)
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary app-right-item me-2 mb-2"
                                            data-id="{{ $item->id }}"
                                            data-name="{{ $item?->name }}">
                                            {{ $item?->name }}
                                        </button>
                                    @endif
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
                                @foreach ($app_right_list as $item)
                                    @php
                                        $selected = isset($edit) && is_array($edit->app_right) && in_array($item->id, $edit->app_right);
                                    @endphp

                                    @if ($selected)
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary app-right-item-fill me-2 mb-2"
                                            data-id="{{ $item->id }}"
                                            data-name="{{ $item?->name }}">
                                            {{ $item?->name }}
                                        </button>
                                    @endif
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
                                    @foreach ($app_right_list as $item)
                                        <option value="{{ $item->id }}"
                                            {{ in_array($item->id, old('app_right', $edit->app_right ?? []) ?? []) ? 'selected' : '' }}>
                                            {{ $item->name }}</option>
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
                                            $selected = isset($edit) && is_array($edit->panel_right) && in_array($sub_menu->id, $edit->panel_right);
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
                                            $selected = isset($edit) && is_array($edit->panel_right) && in_array($sub_menu->id, $edit->panel_right);
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
                                <select id="panel_right" class="select2 form-select" multiple name="panel_right[]">
                                    @foreach ($panel_right_list as $main_menu)
                                        <optgroup label="{{ $main_menu?->name }}">
                                            @foreach ($main_menu?->sub_menu as $sub_menu)
                                                <option value="{{ $sub_menu->id }}"
                                                    {{ in_array($sub_menu->id, old('panel_right', $edit->panel_right ?? []) ?? []) ? 'selected' : '' }}>
                                                    {{ $main_menu?->name.' - ', '' }} {{ $sub_menu->name }}</option>
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

                    {{-- Divider --}}
                    <div class="divider col-12">
                        <hr />
                    </div>

                    {{-- Submit --}}
                    <div class="col-12 text-center">
                        <button type="submit" class="btn btn-success">{{ isset($edit) ? 'Update' : 'Submit' }}</button>
                        <a href="{{ route($route . '.index') }}" class="btn btn-danger">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('page_scripts')
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
    </script>
@endpush
