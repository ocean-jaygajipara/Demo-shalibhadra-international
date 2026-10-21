@extends('software.layout.app')

@php
    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;

    $isIdOne = \App\Models\AdminSoftware::where('id', 1)->exists() && ($edit->id ?? 0) == 1;
@endphp

@section('title', $page_title)

@section('content')
   <div class="px-1">
        <div class="d-flex flex-column flex-lg-row justify-content-lg-between align-items-start align-items-lg-center">
            @include('software.inlcudes.breadcrumb', [
                'breadcrumbArray' => [
                    ['title' => $page_title, 'url' => route($route . '.index')],
                    [
                        'title' => isset($edit) && $edit?->id ? 'Edit ' . $page_title : 'Create ' . $page_title,
                        'url' => '',
                    ],
                ],
            ])
            <a class="btn btn-primary waves-effect waves-light text-white" href="{{ url()->previous() }}">
                <i class="menu-icon ti ti-chevrons-left"></i> Back
            </a>
        </div>
    </div>

    <div class="card my-3 mb-4">
        <div class="card-body">
            <form id="forminfo"
                action="{{ isset($edit) && $edit?->id ? route($route . '.update', [$edit?->id]) : route($route . '.store') }}"
                method="POST" enctype="multipart/form-data">

                <input type="hidden" id="edit_id" name="id"
                    value="{{ isset($edit) && $edit?->id ? $edit?->id : '' }}" />

                @csrf
                @isset($edit)
                    @method('PUT')
                @endisset

                <div class="row">

                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                                name="name" value="{{ old('name', $edit->name ?? '') }}" autocomplete="name"
                                placeholder="Name">
                            @error('name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input id="username" type="text"
                                class="form-control @error('username') is-invalid @enderror" name="username"
                                value="{{ old('username', $edit->username ?? '') }}" autocomplete="name"
                                placeholder="Username">
                            @error('username')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input id="email" type="email"
                                class="form-control @error('email') is-invalid @enderror" name="email"
                                value="{{ old('email', $edit->email ?? '') }}" autocomplete="name" placeholder="Email">
                            @error('email')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input id="phone" type="text"
                                class="form-control @error('phone') is-invalid @enderror" name="phone"
                                value="{{ old('phone', $edit->phone ?? '') }}" autocomplete="name"
                                placeholder="Phone" maxlength="10" onkeypress="return isNumber(event)">
                            @error('phone')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Password @if (!isset($edit)) <span class="text-danger">*</span>
                                @endif </label>
                            <div class="input-group input-group-merge">
                                <input type="password" id="password" class="form-control" name="password"
                                    placeholder="Password" aria-describedby="password" />
                                <span class="input-group-text cursor-pointer toggle-password" onclick="togglePassword()">
                                    <i class="ti ti-eye-off" id="togglePasswordIcon"></i>
                                </span>
                            </div>
                            @error('password')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">
                                Type
                                @unless($isIdOne)
                                    <span class="text-danger">*</span>
                                @endunless
                            </label>
                            <select class="form-control select2 w-100 @error('type') is-invalid @enderror"
                                name="type" {{ $isIdOne ? '' : 'required' }}>
                                <option disabled {{ old('type', $edit->type ?? '') ? '' : 'selected' }}>Select Type</option>
                                @foreach ($user_type_arr as $key => $value)
                                    <option value="{{ $key }}" {{ old('type', $edit->type ?? '') == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select class="form-control select2 w-100 @error('status') is-invalid @enderror"
                                name="status" required>
                                <option disabled selected>Select Status</option>
                                @foreach (['active', 'inactive'] as $status)
                                    <option value="{{ $status }}"
                                        @if (isset($edit)) @if ($edit->status == $status) {{ 'selected' }} @endif
                                    @else @if (old('status', 'active') == $status) {{ 'selected' }} @endif @endif> {{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="divider">
                        <hr />
                    </div>

                    <div class="col-md-12 text-center">
                        <button type="submit" class="btn btn-success mt-1 mb-1">
                            {{ isset($edit) ? 'Update' : 'Submit' }}
                        </button>
                        <a href="{{ route($route . '.index') }}" class="btn btn-danger mt-1 mb-1">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('page_scripts')
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.getElementById('togglePasswordIcon');
            const isPassword = passwordInput.type === 'password';

            passwordInput.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('ti-eye-off', !isPassword);
            icon.classList.toggle('ti-eye', isPassword);
        }
    </script>
@endpush
