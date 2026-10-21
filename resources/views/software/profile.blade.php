@extends('software.layout.app')

@php
    $i = 0;
    $page_title = $modules['title'] ?? null;
    $folder_path = $modules['folder_path'] ?? null;
    $route = $modules['route'] ?? null;
    $company_id = $modules['company_id'] ?? null;
    $authLoginUserDetail = $modules['authLoginUserDetail'] ?? null;
    $loginUserId = $authLoginUserDetail?->id ?? null;
    $parent_type_id = $modules['parent_type_id'] ?? null;
@endphp

@section('title', 'Dashboard')

<style>
    .profile-wrapper {
        display: flex;
        align-items: center;
        gap: 2rem;
        flex-wrap: wrap;
    }

    .profile-avatar {
        position: relative;
        width: 120px;
        height: 120px;
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 17%;
        /* border: 3px solid #e0e0e0; */
    }

    .file-upload-wrapper {
        margin-top: 10px;
    }

    .company-info {
        font-size: 14px;
        line-height: 1.7;
    }

    .company-info b {
        color: #495057;
    }

    .form-section {
        margin-top: 2rem;
    }
</style>

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <div class="profile-wrapper">

                        {{-- Profile Avatar --}}
                        <div class="profile-avatar">
                            <img src="{{ $authLoginUserDetail?->profile_image ? asset($authLoginUserDetail->profile_image) : asset('software/img/default/profile.png') }}"
                                alt="user-avatar" id="uploadedAvatar">
                        </div>

                        {{-- Upload Form --}}

                        @if ($modules['currentGuard'] == 'team_person')
                            {{-- Company Info --}}
                            <div class="company-info">
                                <b>Company Name :</b> {{ $company?->company_name ?? '-' }} <br />
                                <b>Email :</b> {{ $company?->email ?? '-' }} <br />
                                <b>Whatsapp :</b> {{ $company?->whatsapp_number ?? '-' }}
                            </div>
                        @endif

                    </div>
                    <div class="row mt-3">
                        <form action="{{ route($route . '.updateProfileImage', [$edit?->id]) }}" method="POST"
                            enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                            @csrf
                            <input type="file" id="upload" name="profile_image" class="form-control"
                                accept="image/png, image/jpeg" style="max-width: 300px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-1"></i> Save
                            </button>
                        </form>

                    </div>
                </div>
            </div>

            {{-- Profile & Password Form --}}
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Account Settings</h5>
                </div>
                <div class="card-body">
                    <form id="formAccountSettings" action="{{ route($route . '.updateProfile', [$edit?->id]) }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row gy-4">
                            <div class="col-md-6">
                                <label class="form-label">Profile Name</label>
                                <input class="form-control" type="text" value="{{ $userName }}" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Username / Role</label>
                                <input class="form-control" type="text" value="{{ $roleName }}" readonly>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Old Password</label>
                                <div class="input-group input-group-merge">
                                    <input class="form-control" type="password" id="old_password" name="old_password"
                                        required>
                                    <span class="input-group-text cursor-pointer toggle-password"
                                        onclick="togglePassword('old_password')">
                                        <i class="ti ti-eye-off" id="togglePasswordIcon_old_password"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">New Password</label>
                                <div class="input-group input-group-merge">
                                    <input class="form-control" type="password" id="new_password" name="new_password"
                                        required>
                                    <span class="input-group-text cursor-pointer toggle-password"
                                        onclick="togglePassword('new_password')">
                                        <i class="ti ti-eye-off" id="togglePasswordIcon_new_password"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group input-group-merge">
                                    <input class="form-control" type="password" id="new_password_confirmation"
                                        name="new_password_confirmation" required>
                                    <span class="input-group-text cursor-pointer toggle-password"
                                        onclick="togglePassword('new_password_confirmation')">
                                        <i class="ti ti-eye-off" id="togglePasswordIcon_new_password_confirmation"></i>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="ti ti-device-floppy me-1"></i> Save Changes
                            </button>
                            <button type="reset" class="btn btn-label-secondary">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('page_scripts')
    <script>
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const icon = document.getElementById('togglePasswordIcon_' + inputId);
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('ti-eye-off', !isPassword);
            icon.classList.toggle('ti-eye', isPassword);
        }

        // Profile image live preview
        $(document).on('change', '#upload', function(event) {
            const input = event.target;
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#uploadedAvatar').attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        });
    </script>
@endpush
