@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : 'Form';
    $route = isset($modules['route']) ? $modules['route'] : null;

    $permissions = ['view', 'add', 'update', 'delete'];
    if (count(config('constants.permissions'))) {
        $permissions = config('constants.permissions');
    }
@endphp

@section('title', $page_title)

@section('content')
    <div class="d-flex justify-content-lg-between px-1">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [
                ['title' => $page_title, 'url' => route($route . '.index')],
                ['title' => 'Assign Panel Permission', 'url' => ''],
            ],
        ])
        <a class="btn btn-primary" href="{{ url()->previous() }}">
            <i class="menu-icon ti ti-chevrons-left"></i> Back
        </a>
    </div>

    <div class="card my-3 mb-4">
        <div class="card-body">

            <div class="row">
                <div class="col-md-4">
                    <b>Company Name : </b> {{ $team_role?->company?->company_name ?? '-'}}
                </div>
                <div class="col-md-4">
                    <b>Parent : </b> {{ $team_role?->parent_name ?? '-'}}
                </div>
                <div class="col-md-4">
                    <b>Team Role : </b> {{ $team_role?->name ?? '-'}}
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
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Select Module Type <span class="text-danger">*</span></label>
                            <select id="main_menu_id"
                                class="form-control select2 @error('main_menu_id') is-invalid @enderror" name="main_menu_id"
                                data-selectedCompanyId="{{ old('main_menu_id') ?? ($edit->main_menu_id ?? '') }}">
                                <option value="">Select Module Type</option>
                                @foreach ($mainMenu as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>

                            @error('main_menu_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-bordered bg-white" id="submenuTable">
                                <thead class="bg-light text-dark">
                                    <tr>
                                        <th scope="col">Submenu Name</th>
                                        @if (isset($permissions) && count($permissions))
                                            @foreach ($permissions as $key => $value)
                                                <th class="text-center">{{ str_replace("_", " ", $value) ?? '-' }}</th>
                                            @endforeach
                                            <th class="text-center">Check All</th>
                                        @else
                                            <th class="text-center">View</th>
                                            <th class="text-center">Add</th>
                                            <th class="text-center">Update</th>
                                            {{-- <th class="text-center">Delete</th>
                                            <th class="text-center">Print</th>
                                            <th class="text-center">excel</th>
                                            <th class="text-center">Approval</th>
                                            <th class="text-center">All Data</th>
                                            <th class="text-center">Personal Data</th> --}}
                                            <th class="text-center">Check All</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-12 text-center mt-3">
                        <button type="submit" class="btn btn-success">
                            {{ isset($edit) ? 'Update' : 'Submit' }}
                        </button>
                        <a href="{{ route($route . '.index') }}" class="btn btn-danger">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('page_scripts')
    @include('utils.getSubmenu')
    <script>
        $(document).on('change', '.check-all', function() {
            var row = $(this).closest('tr');
            row.find('.permission-checkbox').prop('checked', $(this).prop('checked'));
        });
    </script>
@endpush
