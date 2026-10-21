@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    $authLoginUserDetail = isset($modules['authLoginUserDetail']) ? $modules['authLoginUserDetail'] : null;
    $loginUserId = isset($authLoginUserDetail?->id) ? $authLoginUserDetail?->id : null;
    $parent_type_id = isset($modules['parent_type_id']) ? $modules['parent_type_id'] : null;

    $permissions = ['view', 'add', 'update', 'delete'];
    if (count(config('constants.permissions'))) {
        $permissions = config('constants.permissions');
    }
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

    <div class="card my-3 mb-4">
        <div class="card-body">

            <div class="row">
                <div class="col-md-4">
                    <b>Company Name : </b> {{ $team_role?->company?->company_name ?? '-' }}
                </div>
                <div class="col-md-4">
                    <b>Parent : </b> {{ $team_role?->parent_name ?? '-' }}
                </div>
                <div class="col-md-4">
                    <b>Team Role : </b> {{ $team_role?->name ?? '-' }}
                </div>
            </div>
            <hr>
            <form action="{{ route($route . '.store-permission') }}" method="POST" id="forminfo">
                @csrf
                @isset($edit)
                    @method('PUT')
                @endisset

                <input type="hidden" name="id" value="{{ $edit->id ?? '' }}">
                <input type="hidden" id="team_role_id" name="team_role_id" value="{{ $team_role->id ?? '' }}">
                <input type="hidden" id="company_id" name="company_id" value="{{ $team_role->company_id ?? '' }}">

                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-bordered bg-white" id="submenuTable">
                                <thead class="bg-light text-dark">
                                    <tr>
                                        <th scope="col">Submenu Name</th>
                                        @if (isset($permissions) && count($permissions))
                                            @foreach ($permissions as $key => $value)
                                                <th class="text-center">{{ str_replace('_', ' ', $value) ?? '-' }}</th>
                                            @endforeach
                                            <th class="text-center">Check All</th>
                                        @else
                                            <th class="text-center">View</th>
                                            <th class="text-center">Add</th>
                                            <th class="text-center">Update</th>
                                            <th class="text-center">Delete</th>
                                            <th class="text-center">Check All</th>
                                        @endif
                                    </tr>
                                </thead>
                                @if (isset($panel_sub_modules))
                                    @foreach ($panel_sub_modules as $item)
                                        @if ($item && $item?->sub_menus && count($item?->sub_menus))
                                            <tr class="table-info" data-id="{{ $item?->id }}">
                                                <td colspan="{{ count($permissions) + 2 }}">
                                                    <b>{{ $item?->name ?? 'Main Menu' }}</b>
                                                </td>
                                            </tr>
                                            @foreach ($item?->sub_menus as $item_sub_menu)
                                                @php
                                                    $item_sub_menu = (object) $item_sub_menu;
                                                    $checked_all = count($permissions);
                                                @endphp
                                                <tr>
                                                    <td data-company_id="{{ $team_role?->company_id ?? '-' }}"
                                                        data-team_role_id="{{ $team_role?->id ?? '' }}"
                                                        data-main_menu_id="{{ $item_sub_menu?->main_menu_id }}"
                                                        data-id="{{ $item_sub_menu?->id }}">
                                                        {{ $item_sub_menu?->name ?? 'Sub Menu' }}</td>
                                                    @foreach ($permissions as $permission_key => $permission_value)
                                                        @php

                                                            $tr_uuid = [];
                                                            $tr_uuid[] = $team_role?->company_id;
                                                            $tr_uuid[] = $team_role?->id;
                                                            $tr_uuid[] = $item_sub_menu?->main_menu_id;
                                                            $tr_uuid[] = $item_sub_menu?->id;

                                                            $tr_uuid = implode('-', $tr_uuid);

                                                            $tr_permission_check = null;
                                                            $permission_checked = false;
                                                            if (isset($assignedPermission[$tr_uuid])) {
                                                                $tr_permission_check = $assignedPermission[$tr_uuid];
                                                                $tr_array = $tr_permission_check->toArray();

                                                                if ($tr_permission_check && $tr_permission_check?->id) {
                                                                    $tr_array = $tr_permission_check->toArray();
                                                                    $flag_key = $permission_key . '_flag';

                                                                    if (isset($assignedPermission[$tr_uuid])) {
                                                                        // if (true && ($flag_key = 'restore_flag')) {
                                                                        //     dd( 'Check 122', $flag_key, $tr_array, array_key_exists($flag_key, $tr_array), $tr_array[$flag_key], $permission_checked, );
                                                                        // }
                                                                        if (
                                                                            isset($tr_array['id']) &&
                                                                            array_key_exists($flag_key, $tr_array) &&
                                                                            isset($tr_array[$flag_key])
                                                                        ) {
                                                                            $checked_all--;
                                                                            $permission_checked = $tr_array[$flag_key];
                                                                            // dd(
                                                                            //     'Flag exists and can be accessed',
                                                                            //     $flag_key,
                                                                            //     $tr_array[$flag_key],
                                                                            // );
                                                                        } else {
                                                                            dd(
                                                                                'Flag missing or not set',
                                                                                $flag_key,
                                                                                $tr_array,
                                                                            );
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        @endphp
                                                        <td class="text-center">
                                                            {{-- <input type="hidden" name="permissions[{{ $item_sub_menu?->id }}][assigned_permission_id]" value="{{ $tr_permission_check?->id ?? '' }}"> --}}
                                                            <input type="hidden"
                                                                name="permissions[{{ $item_sub_menu?->id }}][uuid]"
                                                                value="{{ $tr_uuid }}">
                                                            {{-- value="permissions[{{ $item_sub_menu?->id }}][{{ $permission_key }}]" --}}
                                                            <input type="hidden"
                                                                name="permissions[{{ $item_sub_menu?->id }}][{{ $permission_key }}]"
                                                                value="0">
                                                            <input type="checkbox"
                                                                id="{{ $tr_uuid . '_' . $permission_key }}"
                                                                class="{{ !in_array($permission_key, ['personal_data']) ? 'permission-checkbox' : '' }}"
                                                                name="permissions[{{ $item_sub_menu?->id }}][{{ $permission_key }}]"
                                                                data-name="{{ $permission_value }}" value="1"
                                                                {{ $permission_checked ? 'checked' : '' }} />
                                                            {{-- {!! $tr_uuid !!} --}}
                                                            <label
                                                                for="{{ $tr_uuid . '_' . $permission_key }}">{{ $permission_key }}</label>
                                                        </td>
                                                    @endforeach
                                                    {{-- {{ dd(!$checked_all) }} --}}
                                                    {{-- {{ !$checked_all ? 'checked' : '' }} --}}
                                                    <td class="text-center"><input type="checkbox" class="check-all"></td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    @endforeach
                                @endif
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>



                <div class="row">
                    <div class="col-12 text-center mt-3">
                        <button type="submit" value="submit" class="btn btn-success">
                            {{ isset($edit) ? 'Update' : 'Submit' }}
                        </button>
                        <button type="submit" name="btn_submit" value="submit_and_exit" class="btn btn-primary mt-1 mb-1">
                            Submit and Exit
                        </button>
                        <a href="{{ route($route . '.index') }}" class="btn btn-danger">Cancel</a>
                    </div>
                </div>


            </form>
        </div>
    </div>
@endsection

@push('page_scripts')
    <script>
        $(document).on('change', '.check-all', function() {
            var row = $(this).closest('tr');
            row.find('.permission-checkbox').prop('checked', $(this).prop('checked'));
        });
    </script>
@endpush
