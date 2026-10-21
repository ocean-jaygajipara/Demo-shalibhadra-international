@php
    $isFollowup = isset($modules['module_name']) && strtolower($modules['module_name']) === 'follow up';

@endphp
<!-- Basic Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('software.dashboard') }}">Home</a>
        </li>
        @isset($breadcrumbArray)
        @foreach ($breadcrumbArray as $item)
        <a class="breadcrumb-item"
            @if (isset($item['url']) && !empty($item['url'])) href="{{ $item['url'] ?? '#' }}" @endif>{{ $item['title'] ?? '' }}</a>
        @endforeach
        @endisset
    </ol>
</nav>
@if ($isFollowup)
    <span class="mx-auto fw-semibold text-danger">
        Showing data for the current date by default.
    </span>
@endif
<!-- Basic Breadcrumb -->

<div class="ms-auto">

    @if (request()->routeIs('inquiry.index'))
        <div class="form-check form-check-inline align-self-center me-2">
            <input class="form-check-input" type="checkbox" name="show_lost" value="1" id="show_lost"
                {{ request()->get('show_lost') ? 'checked' : '' }}>
            <label class="form-check-label" for="show_lost">Show Lost Data</label>
        </div>
    @endif

    @if (isset($show_add_btn) && $show_add_btn && isset($route))
    <a id="show_add_btn" class="btn btn-primary waves-effect waves-light text-white btn-sm mt-lg-0"
        href="{{ route($route . '.create') }}">
        <i class="menu-icon ti ti-plus"></i>
        <span class="d-none d-lg-inline"> Add</span>
    </a>
    @endif

    @if (isset($show_bulk_download_btn) && $show_bulk_download_btn)
    <a href="javascript:void(0)" id="bulk_download_btn" class="btn btn-primary waves-effect waves-light text-white btn-sm mt-lg-0">
        <i class="ti ti-download me-1"></i>
        <span class="d-none d-lg-inline">Bulk Download PDF</span>
    </a>
    @endif

    @if (isset($show_packing_slip) && $show_packing_slip && isset($route))
    <a class="btn btn-success waves-effect waves-light text-white btn-sm mt-lg-0" id="add_packing_slip"
        href="javascript:void(0)">
        <i class="menu-icon ti ti-plus"></i>
        <span class="d-none d-lg-inline">Create Packing Slip</span>
        <form id="packing-slip-form" action="" method="POST"
            class="d-none">
            @csrf
            <input type="hidden" id="dispatch_id" name="dispatch_id" value="" autocomplete="off">
            <input type="hidden" id="dispatch_company_id" name="dispatch_company_id" value="" autocomplete="off">
            <input type="hidden" id="dispatch_customer_id" name="dispatch_customer_id" value="" autocomplete="off">
        </form>
    </a>
    @endif

    @if (isset($show_assign_to_team_btn) && $show_assign_to_team_btn && isset($route))
        <a href="javascript:void(0);" id="assign-to-team-btn"
            class="btn btn-primary btn-sm waves-effect waves-light text-white d-none open-assign-modal">
            <i class="menu-icon ti ti-user"></i> Assign to team
        </a>
    @endif

    @if (isset($show_bulk_delete_btn) && $show_bulk_delete_btn && isset($route))
        <a href="javascript:void(0);" id="delete-selected-btn"
            class="btn btn-danger btn-sm waves-effect waves-light text-white d-none">
            <i class="menu-icon ti ti-user"></i> Delete Selected
        </a>
    @endif


    @if (isset($show_filter_btn) && $show_filter_btn && isset($route))
    <button type="button" title="Search" id="show_filter"
        class="btn btn-outline-primary btn-dm waves-effect waves-light btn-icon ms-75 me-75 "><i
            class="ti ti-filter"></i></button>
    @endif

    @if (isset($show_export_btn) && $show_export_btn && isset($route))

    {{-- Dropdown --}}

        <button class="btn btn-sm btn-info btn-sm waves-effect waves-light text-white    " type="button"
            id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false" style="width: 38px; height: 38px;">
            <i class="ti ti-settings"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="dropdownMenuButton">
            @if (isset($show_excal_btn) && $show_excal_btn && isset($route))
                {{-- <li>
                <a class="dropdown-item" href="{{ route($route . '.export.excel') }}">
                    <i class="fa fa-file-excel me-2 text-success"></i>Export Excel
                </a>
            </li> --}}
                <li>
                    <a class="dropdown-item" href="#" id="export_excel_btn">
                        <i class="fa fa-file-excel me-2 text-success"></i>Export Excel
                    </a>
                </li>
            @endif

            @if (isset($show_print_btn) && $show_print_btn && isset($route))
                <li>
                    <a href="#" class="dropdown-item" id="print_btn" target="_blank">
                        <i class="fa fa-print me-2 text-primary"></i>Print
                    </a>
                </li>
            @endif
        </ul>
    @endif


    @if (isset($show_back_btn) && $show_back_btn && isset($route))
    <a class="btn btn-primary btn-sm waves-effect waves-light text-white mt-2 mt-lg-0 d-flex align-items-center"
        href="{{ route($route . '.index') }}">
        <i class="menu-icon ti ti-chevrons-left"></i>
        <span class="d-none d-lg-inline ms-2">Back</span>
    </a>
    @endif
</div>
