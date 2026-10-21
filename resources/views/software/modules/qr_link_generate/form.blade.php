@extends('software.layout.app')

@php
    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;

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
            <form
                action="{{ isset($edit) && $edit?->id ? route($route . '.update', [$edit?->id]) : route($route . '.store') }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                @isset($edit)
                    @method('PUT')
                @endisset

                <div class="row">

                    @if (!$company_id)
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Select Company</label>
                            <select name="company_id" id="company_id"
                                class="form-control @error('company_id') is-invalid @enderror search_by_company select2"
                                data-append="search_by_company"
                                data-selectedCompanyId="{{ isset($edit) && $edit?->company_id ? $edit?->company_id : old('company_id') }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('company_id', $edit->company_id ?? '') ? '' : 'selected' }}>
                                    Select Company
                                </option>
                            </select>
                            @error('company_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    @else
                        <input type="hidden" class="form-control search_by_company" name="company_id"
                            value="{{ $company_id }}" />
                    @endif

                    <div class="col-md-3 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">QR Type</label>
                            <select class="form-control select2 w-100 qr_type_id @error('qr_type_id') is-invalid @enderror"
                                name="qr_type_id" required>
                                <option value="">Select Qr Type</option>
                                @if (isset($QRTypes))
                                    @foreach ($QRTypes as $QRTypes)
                                        <option value="{{ $QRTypes->id }}"
                                            @if (isset($edit)) @if ($edit->qr_type_id == $QRTypes->id) {{ 'selected' }} @endif
                                        @else @if (old('qr_type_id', 'active') == $QRTypes->id) {{ 'selected' }} @endif @endif data-slug="{{ strtolower($QRTypes->slug) }}">
                                            {{ ucfirst($QRTypes->name) }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    {{-- Name --}}
                    <div class="col-md-3 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Name</label>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                                name="name" value="{{ isset($edit) && $edit?->name ? $edit?->name : old('name') }}"
                                placeholder="Enter name">
                            @error('name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="col-md-3 col-sm-12 mb-3">
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
                </div>


                <div class="qr-types qr-type-link" style="display: none;">
                    <div class="row">
                        {{-- link --}}
                        <div class="col-md-3 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Link <span class="text-danger">*</span> </label>
                                <input id="link" type="url"
                                    class="form-control @error('qr_type_fields.link') is-invalid @enderror"
                                    name="link"
                                    value="{{ old('qr_type_fields.link', $edit?->link ?? '') }}"
                                    placeholder="Enter link">
                                @error('qr_type_fields.link')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Text QR --}}
                <div class="qr-types qr-type-text" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Text <span class="text-danger">*</span> </label>
                                <textarea rows="3" cols="3" id="text"
                                    class="ckeditor_common_cls form-control @error('qr_type_fields.text') is-invalid @enderror"
                                    name="text" placeholder="Enter Text"> {{ old('qr_type_fields.text', $edit?->text ?? '') }}
                                </textarea>
                                @error('qr_type_fields.text')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>


                {{-- Email QR --}}
                <div class="qr-types qr-type-email" style="display:none;">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Email To <span class="text-danger">*</span> </label>
                            <input type="email" name="email_to"
                                value="{{ old('qr_type_fields.email_to', $edit?->email_to ?? '') }}"
                                class="form-control @error('qr_type_fields.email_to') is-invalid @enderror">
                            @error('qr_type_fields.email_to')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Subject <span class="text-danger">*</span> </label>
                            <input type="text" name="subject"
                                value="{{ old('qr_type_fields.subject', $edit?->subject ?? '') }}"
                                class="form-control @error('qr_type_fields.subject') is-invalid @enderror">
                            @error('qr_type_fields.subject')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Message <span class="text-danger">*</span> </label>
                            <textarea rows="3" cols="3" name="body" class="ckeditor_common_cls form-control @error('qr_type_fields.body') is-invalid @enderror">{{ old('qr_type_fields.body', $edit?->body ?? '') }}</textarea>
                            @error('qr_type_fields.body')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- WhatsApp QR --}}
                <div class="qr-types qr-type-whatsapp" style="display:none;">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone_whatsapp"
                                value="{{ old('qr_type_fields.phone_whatsapp', $edit?->phone_whatsapp ?? '') }}"
                                class="form-control @error('qr_type_fields.phone_whatsapp') is-invalid @enderror">
                            @error('qr_type_fields.phone_whatsapp')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-2"></div>
                        <div class="col-md-6 mb-2">
                            <label>Message <span class="text-danger">*</span></label>
                            <textarea rows="3" cols="3" name="message_whatsapp" class="ckeditor_common_cls_whatsapp form-control @error('qr_type_fields.message_whatsapp') is-invalid @enderror">{{ old('qr_type_fields.message_whatsapp', $edit?->message_whatsapp ?? '') }}</textarea>
                            @error('qr_type_fields.message_whatsapp')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- WiFi QR --}}
                <div class="qr-types qr-type-wifi" style="display:none;">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label>SSID <span class="text-danger">*</span></label>
                            <input type="text" name="ssid"
                                value="{{ old('qr_type_fields.ssid', $edit?->ssid ?? '') }}"
                                class="form-control @error('qr_type_fields.ssid') is-invalid @enderror">
                            @error('qr_type_fields.ssid')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Password <span class="text-danger">*</span></label>
                            <input type="password" name="password"
                                value="{{ old('qr_type_fields.password', $edit?->password ?? '') }}"
                                class="form-control @error('qr_type_fields.password') is-invalid @enderror">
                            @error('qr_type_fields.password')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Encryption <span class="text-danger">*</span></label>
                            <select name="encryption"
                                class="form-control @error('qr_type_fields.encryption') is-invalid @enderror">
                                <option value="WEP" {{ old('qr_type_fields.encryption', $edit?->encryption ?? '') == 'WEP' ? 'selected' : '' }}>WEP</option>
                                <option value="WPA" {{ old('qr_type_fields.encryption', $edit?->encryption ?? '') == 'WPA' ? 'selected' : '' }}>WPA/WPA2</option>
                                <option value="RAW" {{ old('qr_type_fields.encryption', $edit?->encryption ?? '') == 'RAW' ? 'selected' : '' }}>RAW</option>
                                <option value="nopass" {{ old('qr_type_fields.encryption', $edit?->encryption ?? '') == 'nopass' ? 'selected' : '' }}>No Password</option>
                            </select>
                            @error('qr_type_fields.encryption')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- PDF Upload --}}
                <div class="qr-types qr-type-pdf" style="display:none;">
                    <div class="col-md-6">
                        <label>Upload PDF <span class="text-danger">*</span></label>
                        <input type="file" name="pdf" class="form-control @error('qr_type_fields.pdf') is-invalid @enderror" accept="application/pdf">
                        @if(!empty($edit?->pdf))
                            <small class="text-muted">Current: <a href="{{ url($edit->pdf) }}" target="_blank">View PDF</a></small>
                        @endif
                        @error('qr_type_fields.pdf')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                {{-- Image Upload --}}
                <div class="qr-types qr-type-image" style="display:none;">
                    <div class="col-md-6">
                        <label>Upload Image <span class="text-danger">*</span></label>
                        <input type="file" name="image" class="form-control @error('qr_type_fields.image') is-invalid @enderror" accept="image/*">
                        @if(!empty($edit?->image))
                            <small class="text-muted">Current: <a href="{{ url($edit->image) }}" target="_blank">View Image</a></small>
                        @endif
                        @error('qr_type_fields.image')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                {{-- vCard QR --}}
                <div class="qr-types qr-type-vcard" style="display:none;">

                    {{-- First & Last Name --}}
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name"
                                value="{{ old('qr_type_fields.first_name', $edit?->first_name ?? '') }}"
                                class="form-control @error('qr_type_fields.first_name') is-invalid @enderror">
                            @error('qr_type_fields.first_name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name"
                                value="{{ old('qr_type_fields.last_name', $edit?->last_name ?? '') }}"
                                class="form-control @error('qr_type_fields.last_name') is-invalid @enderror">
                            @error('qr_type_fields.last_name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Corporate Info --}}
                    <h6 class="mt-3"><strong>Corporate Information (Optional)</strong></h6>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Company Name <span class="text-danger">*</span></label>
                            <input type="text" name="company"
                                value="{{ old('qr_type_fields.company', $edit?->company ?? '') }}"
                                class="form-control @error('qr_type_fields.company') is-invalid @enderror">
                            @error('qr_type_fields.company')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Job Title</label>
                            <input type="text" name="job_title"
                                value="{{ old('qr_type_fields.job_title', $edit?->job_title ?? '') }}"
                                class="form-control @error('qr_type_fields.job_title') is-invalid @enderror">
                            @error('qr_type_fields.job_title')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Personal Info --}}
                    <h6 class="mt-3"><strong>Personal Information (Optional)</strong></h6>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Date of Birth</label>
                            <input type="date" name="dob"
                                value="{{ old('qr_type_fields.dob', $edit?->dob ?? '') }}"
                                class="form-control @error('qr_type_fields.dob') is-invalid @enderror">
                            @error('qr_type_fields.dob')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Profile Image</label>
                            @if(!empty($edit?->profile_image))
                            <input type="hidden" name="profile_image_hidden" value="{{ $edit?->profile_image }}"/>
                            @endif
                            <input type="file" name="profile_image"
                                class="form-control @error('qr_type_fields.profile_image') is-invalid @enderror" accept="image/*">
                            @error('qr_type_fields.profile_image')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror

                            {{-- Show existing image if editing --}}
                            @if(!empty($edit?->profile_image))
                                <div class="mt-2">
                                    <small class="text-muted">Current: <a href="{{ url($edit->profile_image) }}" target="_blank">View Image</a></small>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Contacts --}}
                    <h6 class="mt-3"><strong>Contacts</strong></h6>
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label>Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone_vcard"
                                value="{{ old('qr_type_fields.phone_vcard', $edit?->phone_vcard ?? '') }}"
                                class="form-control @error('qr_type_fields.phone_vcard') is-invalid @enderror">
                            @error('qr_type_fields.phone_vcard')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Email <span class="text-danger">*</span></label>
                            <input type="email" name="email"
                                value="{{ old('qr_type_fields.email', $edit?->email ?? '') }}"
                                class="form-control @error('qr_type_fields.email') is-invalid @enderror">
                            @error('qr_type_fields.email')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Website</label>
                            <input type="url" name="website"
                                value="{{ old('qr_type_fields.website', $edit?->website ?? '') }}"
                                class="form-control @error('qr_type_fields.website') is-invalid @enderror">
                            @error('qr_type_fields.website')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12 mb-2">
                            <label>Address <span class="text-danger">*</span></label>
                            <textarea name="address" rows="3" cols="3"
                                    class="ckeditor_common_cls form-control @error('qr_type_fields.address') is-invalid @enderror">{{ old('qr_type_fields.address', $edit?->address ?? '') }}</textarea>
                            @error('qr_type_fields.address')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- vCard Design --}}
                    <h6 class="mt-3"><strong>vCard Design (Optional)</strong></h6>
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label>Card Color</label>
                            <input type="color" name="card_color"
                                value="{{ old('qr_type_fields.card_color', $edit?->card_color ?? '#000000') }}"
                                class="form-control @error('qr_type_fields.card_color') is-invalid @enderror" style="height: 38px; width: 100%; padding: 2px 10px;">
                            @error('qr_type_fields.card_color')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                </div>


                {{-- Phone Call QR --}}
                <div class="qr-types qr-type-phonecall" style="display:none;">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone_call"
                                value="{{ old('qr_type_fields.phone_call', $edit?->phone_call ?? '') }}"
                                class="form-control @error('qr_type_fields.phone_call') is-invalid @enderror">
                            @error('qr_type_fields.phone_call')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Map QR --}}
                <div class="qr-types qr-type-map" style="display:none;">
                    <div class="row">
                        <div class="col-md-12 mb-2">
                            <label>Google Map Link (optional)</label>
                            <input type="text" name="map_link" id="map_link"
                                value="{{ old('qr_type_fields.map_link', $edit?->map_link ?? '') }}"
                                class="form-control @error('qr_type_fields.map_link') is-invalid @enderror">
                            @error('qr_type_fields.map_link')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Latitude <span class="text-danger">*</span></label>
                            <input type="text" name="latitude" id="latitude"
                                value="{{ old('qr_type_fields.latitude', $edit?->latitude ?? '') }}"
                                class="form-control @error('qr_type_fields.latitude') is-invalid @enderror">
                            @error('qr_type_fields.latitude')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Longitude <span class="text-danger">*</span></label>
                            <input type="text" name="longitude" id="longitude"
                                value="{{ old('qr_type_fields.longitude', $edit?->longitude ?? '') }}"
                                class="form-control @error('qr_type_fields.longitude') is-invalid @enderror">
                            @error('qr_type_fields.longitude')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div id="map" style="width:100%; height:400px; margin-top:10px;"></div>
                    </div>
                </div>

                {{-- Audio QR --}}
                <div class="qr-types qr-type-audio" style="display:none;">
                    <div class="col-md-6 mb-2">
                        <label>Upload Audio <span class="text-danger">*</span></label>
                        <input type="file" name="audio"
                            class="form-control @error('qr_type_fields.audio') is-invalid @enderror"
                            accept="audio/*">
                        @if(!empty($edit?->audio))
                            <small class="text-muted">Current: <a href="{{ url($edit->audio) }}" target="_blank">Play Audio</a></small>
                        @endif
                        @error('qr_type_fields.audio')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                {{-- Facebook QR --}}
                <div class="qr-types qr-type-facebook" style="display:none;">
                    <div class="col-md-6 mb-2">
                        <label>Facebook Profile/Page URL <span class="text-danger">*</span></label>
                        <input type="url" name="facebook_url"
                            value="{{ old('qr_type_fields.facebook_url', $edit?->facebook_url ?? '') }}"
                            class="form-control @error('qr_type_fields.facebook_url') is-invalid @enderror">
                        @error('qr_type_fields.facebook_url')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                {{-- Instagram QR --}}
                <div class="qr-types qr-type-instagram" style="display:none;">
                    <div class="col-md-6 mb-2">
                        <label>Instagram Profile URL <span class="text-danger">*</span></label>
                        <input type="url" name="instagram_url"
                            value="{{ old('qr_type_fields.instagram_url', $edit?->instagram_url ?? '') }}"
                            class="form-control @error('qr_type_fields.instagram_url') is-invalid @enderror">
                        @error('qr_type_fields.instagram_url')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                {{-- YouTube QR --}}
                <div class="qr-types qr-type-youtube" style="display:none;">
                    <div class="col-md-6 mb-2">
                        <label>YouTube Video/Channel URL <span class="text-danger">*</span></label>
                        <input type="url" name="youtube_url"
                            value="{{ old('qr_type_fields.youtube_url', $edit?->youtube_url ?? '') }}"
                            class="form-control @error('qr_type_fields.youtube_url') is-invalid @enderror">
                        @error('qr_type_fields.youtube_url')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                {{-- Telegram QR --}}
                <div class="qr-types qr-type-telegram" style="display:none;">
                    <div class="col-md-6 mb-2">
                        <label>Telegram Username / Link <span class="text-danger">*</span></label>
                        <input type="text" name="telegram"
                            value="{{ old('qr_type_fields.telegram', $edit?->telegram ?? '') }}"
                            class="form-control @error('qr_type_fields.telegram') is-invalid @enderror">
                        @error('qr_type_fields.telegram')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>


                {{-- Play Market / App Store QR --}}
                <div class="qr-types qr-type-appstore" style="display:none;">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Link For App Store <span class="text-danger">*</span></label>
                            <input type="text" name="app_store_link"
                                value="{{ old('qr_type_fields.app_store_link', $edit?->app_store_link ?? '') }}"
                                class="form-control @error('qr_type_fields.app_store_link') is-invalid @enderror">
                            @error('qr_type_fields.app_store_link')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Link For Google Play <span class="text-danger">*</span></label>
                            <input type="text" name="google_play_link"
                                value="{{ old('qr_type_fields.google_play_link', $edit?->google_play_link ?? '') }}"
                                class="form-control @error('qr_type_fields.google_play_link') is-invalid @enderror">
                            @error('qr_type_fields.google_play_link')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Link For Other Devices <span class="text-danger">*</span></label>
                            <input type="text" name="other_device_link"
                                value="{{ old('qr_type_fields.other_device_link', $edit?->other_device_link ?? '') }}"
                                class="form-control @error('qr_type_fields.other_device_link') is-invalid @enderror">
                            @error('qr_type_fields.other_device_link')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- TikTok Link --}}
                <div class="qr-types qr-type-tiktok" style="display:none;">
                    <div class="col-md-6 mb-2">
                        <label>TikTok Link <span class="text-danger">*</span></label>
                        <input type="url" name="tiktok_url"  placeholder="TikTok Link"
                            value="{{ old('qr_type_fields.tiktok_url', $edit?->tiktok_url ?? '') }}"
                            class="form-control @error('qr_type_fields.tiktok_url') is-invalid @enderror">
                        @error('qr_type_fields.tiktok_url')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                {{-- Video Link --}}
                <div class="qr-types qr-type-video" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <div class="form-group">
                                <label class="form-label">Video URL <span class="text-danger">*</span></label>
                                <input type="url" name="video_url" class="form-control @error('qr_type_fields.video_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.video_url', $edit?->video_url ?? '') }}"
                                    placeholder="Enter video link (MP4, YouTube, etc.)">
                                @error('qr_type_fields.video_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Google Form --}}
                <div class="qr-types qr-type-googleform" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Google Form Link <span class="text-danger">*</span></label>
                                <input type="url" name="googleform_url" class="form-control @error('qr_type_fields.googleform_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.googleform_url', $edit?->googleform_url ?? '') }}"
                                    placeholder="https://forms.gle/...">
                                @error('qr_type_fields.googleform_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Twitter/X --}}
                <div class="qr-types qr-type-twitter" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Twitter/X Profile/URL <span class="text-danger">*</span></label>
                                <input type="url" name="twitter_url" class="form-control @error('qr_type_fields.twitter_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.twitter_url', $edit?->twitter_url ?? '') }}"
                                    placeholder="https://x.com/username">
                                @error('qr_type_fields.twitter_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Snapchat --}}
                <div class="qr-types qr-type-snapchat" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Snapchat Username/Link <span class="text-danger">*</span></label>
                                <input type="text" name="snapchat_url" class="form-control @error('qr_type_fields.snapchat_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.snapchat_url', $edit?->snapchat_url ?? '') }}"
                                    placeholder="Enter Snapchat username or link">
                                @error('qr_type_fields.snapchat_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>


                {{-- Spotify --}}
                <div class="qr-types qr-type-spotify" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Spotify Link <span class="text-danger">*</span></label>
                                <input type="url" name="spotify_url" class="form-control @error('qr_type_fields.spotify_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.spotify_url', $edit?->spotify_url ?? '') }}"
                                    placeholder="https://open.spotify.com/...">
                                @error('qr_type_fields.spotify_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Google Doc --}}
                <div class="qr-types qr-type-googledoc" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Google Doc Link <span class="text-danger">*</span></label>
                                <input type="url" name="googledoc_url" class="form-control @error('qr_type_fields.googledoc_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.googledoc_url', $edit?->googledoc_url ?? '') }}"
                                    placeholder="https://docs.google.com/document/...">
                                @error('qr_type_fields.googledoc_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>


                {{-- Googlereview --}}
                <div class="qr-types qr-type-googlereview" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Google Review Link <span class="text-danger">*</span></label>
                                <input type="url" name="googlereview_url" class="form-control @error('qr_type_fields.googlereview_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.googlereview_url', $edit?->googlereview_url ?? '') }}"
                                    placeholder="https://search.google.com/local/writereview?...">
                                @error('qr_type_fields.googlereview_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Google Sheet --}}
                <div class="qr-types qr-type-googlesheet" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Google Sheet Link <span class="text-danger">*</span></label>
                                <input type="url" name="googlesheet_url" class="form-control @error('qr_type_fields.googlesheet_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.googlesheet_url', $edit?->googlesheet_url ?? '') }}"
                                    placeholder="https://docs.google.com/spreadsheets/...">
                                @error('qr_type_fields.googlesheet_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>


                {{--Payment --}}
                <div class="qr-types qr-type-payment" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Payment Link <span class="text-danger">*</span></label>
                                <input type="url" name="payment_url" class="form-control @error('qr_type_fields.payment_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.payment_url', $edit?->payment_url ?? '') }}"
                                    placeholder="Payment Link">
                                @error('qr_type_fields.payment_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>


                {{--SMS --}}
                <div class="qr-types qr-type-sms" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Phone Number<span class="text-danger">*</span></label>
                            <input type="text" name="phone_sms" placeholder="Phone Number"
                                value="{{ old('qr_type_fields.phone_sms', $edit?->phone_sms ?? '') }}"
                                class="form-control @error('qr_type_fields.phone_sms') is-invalid @enderror">
                            @error('qr_type_fields.phone_sms')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-2"></div>
                        <div class="col-md-6 mb-2">
                            <label>Text Message <span class="text-danger">*</span></label>
                            <textarea rows="3" cols="3" name="phone_message" class="ckeditor_common_cls form-control @error('qr_type_fields.phone_message') is-invalid @enderror">{{ old('qr_type_fields.phone_message', $edit?->phone_message ?? '') }}</textarea>
                            @error('qr_type_fields.phone_message')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{--Logo --}}
                <div class="qr-types qr-type-logo" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Logo Link <span class="text-danger">*</span></label>
                                <input type="url" name="logo_url" class="form-control @error('qr_type_fields.logo_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.logo_url', $edit?->logo_url ?? '') }}"
                                    placeholder="Logo Link">
                                @error('qr_type_fields.logo_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{--Office 365 --}}
                <div class="qr-types qr-type-office365" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Office 365 Link <span class="text-danger">*</span></label>
                                <input type="url" name="office365_url" class="form-control @error('qr_type_fields.office365_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.office365_url', $edit?->office365_url ?? '') }}"
                                    placeholder="https://office.com/...">
                                @error('qr_type_fields.office365_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{--Shaped --}}
                <div class="qr-types qr-type-shaped" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Shaped Link <span class="text-danger">*</span></label>
                                <input type="url" name="shaped_url" class="form-control @error('qr_type_fields.shaped_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.shaped_url', $edit?->shaped_url ?? '') }}"
                                    placeholder="Shaped Link">
                                @error('qr_type_fields.shaped_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>


                {{--PayPal Payment --}}
                <div class="qr-types qr-type-paypal" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">PayPal Payment Link <span class="text-danger">*</span></label>
                                <input type="url" name="paypal_url" class="form-control @error('qr_type_fields.paypal_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.paypal_url', $edit?->paypal_url ?? '') }}"
                                    placeholder="https://paypal.me/username">
                                @error('qr_type_fields.paypal_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{--Etsy Shop --}}
                <div class="qr-types qr-type-etsy" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Etsy Shop Link <span class="text-danger">*</span></label>
                                <input type="url" name="etsy_url" class="form-control @error('qr_type_fields.etsy_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.etsy_url', $edit?->etsy_url ?? '') }}"
                                    placeholder="https://www.etsy.com/shop/...">
                                @error('qr_type_fields.etsy_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>


                {{-- PNG --}}
                <div class="qr-types qr-type-png" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Upload PNG <span class="text-danger">*</span></label>
                                <input type="file" name="png_file" class="form-control @error('qr_type_fields.png_file') is-invalid @enderror" accept="image/png">
                                @error('qr_type_fields.png_file')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- LinkedIn --}}
                <div class="qr-types qr-type-linkedin" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">LinkedIn Profile/Company URL <span class="text-danger">*</span></label>
                                <input type="url" name="linkedin_url" class="form-control @error('qr_type_fields.linkedin_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.linkedin_url', $edit?->linkedin_url ?? '') }}"
                                    placeholder="https://linkedin.com/in/username">
                                @error('qr_type_fields.linkedin_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>


                {{-- Cryptocurrency --}}
                <div class="qr-types qr-type-crypto" style="display: none;">
                    <div class="row">
                        <div class="col-md-3 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Cryptocurrency <span class="text-danger">*</span></label>
                                <div>
                                    <label class="me-3">
                                        <input type="radio" name="crypto_type" value="bitcoin"
                                            {{ old('qr_type_fields.crypto_type', $edit?->crypto_type ?? '') == 'bitcoin' ? 'checked' : '' }}>
                                        Bitcoin
                                    </label>
                                    <label class="me-3">
                                        <input type="radio" name="crypto_type" value="ethereum"
                                            {{ old('qr_type_fields.crypto_type', $edit?->crypto_type ?? '') == 'ethereum' ? 'checked' : '' }}>
                                        Ethereum
                                    </label>
                                </div>
                                @error('qr_type_fields.crypto_type')
                                    <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <input type="text" name="crypto_amount" class="form-control"
                                    value="{{ old('qr_type_fields.crypto_amount', $edit?->crypto_amount ?? '') }}"
                                    placeholder="0.00">
                                @error('qr_type_fields.crypto_amount')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Receiver Cryptocurrency Address <span class="text-danger">*</span></label>
                                <input type="text" name="wallet_address" class="form-control"
                                    value="{{ old('qr_type_fields.wallet_address', $edit?->wallet_address ?? '') }}"
                                    placeholder="Enter Receiver Cryptocurrency Address">
                                @error('qr_type_fields.wallet_address')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label"> Message</label>
                                <textarea rows="3" cols="3" name="crypto_message" class="ckeditor_common_cls form-control @error('qr_type_fields.crypto_message') is-invalid @enderror">{{ old('qr_type_fields.crypto_message', $edit?->crypto_message ?? '') }}</textarea>
                                @error('qr_type_fields.crypto_message')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Calender --}}
                <div class="qr-types qr-type-calendar" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Calender URL <span class="text-danger">*</span></label>
                                <input type="url" name="calendar_url" class="form-control @error('qr_type_fields.calendar_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.calendar_url', $edit?->calendar_url ?? '') }}"
                                    placeholder="Calender URL">
                                @error('qr_type_fields.calendar_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Social Media --}}
                <div class="qr-types qr-type-social" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Social Media URL <span class="text-danger">*</span></label>
                                <input type="url" name="social_media_url" class="form-control @error('qr_type_fields.social_media_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.social_media_url', $edit?->social_media_url ?? '') }}"
                                    placeholder="Social Media URL">
                                @error('qr_type_fields.social_media_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Reddit --}}
                <div class="qr-types qr-type-reddit" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Reddit Post/Profile URL <span class="text-danger">*</span></label>
                                <input type="url" name="reddit_url" class="form-control @error('qr_type_fields.reddit_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.reddit_url', $edit?->reddit_url ?? '') }}"
                                    placeholder="https://reddit.com/...">
                                @error('qr_type_fields.reddit_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- File --}}
                <div class="qr-types qr-type-file" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Upload File <span class="text-danger">*</span></label>
                                <input type="file" name="file" class="form-control @error('qr_type_fields.file') is-invalid @enderror">
                                @if(!empty($edit?->file))
                                    <small class="text-muted">Current: <a href="{{ url($edit->file) }}" target="_blank">View File</a></small>
                                @endif
                                @error('qr_type_fields.file')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>


                 {{-- Excel File --}}
                <div class="qr-types qr-type-excel" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Upload Excel File<span class="text-danger">*</span></label>
                                <input type="file" name="excel" class="form-control @error('qr_type_fields.excel') is-invalid @enderror">
                                @if(!empty($edit?->excel))
                                    <small class="text-muted">Current: <a href="{{ url($edit->excel) }}" target="_blank">View excel</a></small>
                                @endif
                                @error('qr_type_fields.excel')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>


                {{-- Venmo --}}
                <div class="qr-types qr-type-venmo" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Venmo Profile URL <span class="text-danger">*</span></label>
                                <input type="url" name="venmo_url" class="form-control @error('qr_type_fields.venmo_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.venmo_url', $edit?->venmo_url ?? '') }}"
                                    placeholder="Venmo Profile URL">
                                @error('qr_type_fields.venmo_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>


                {{-- Amazon --}}
                <div class="qr-types qr-type-amazon" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Amazon Product/Store URL <span class="text-danger">*</span></label>
                                <input type="url" name="amazon_url" class="form-control @error('qr_type_fields.amazon_url') is-invalid @enderror"
                                    value="{{ old('qr_type_fields.amazon_url', $edit?->amazon_url ?? '') }}"
                                    placeholder="Amazon Product/Store URL">
                                @error('qr_type_fields.amazon_url')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>


                {{-- PPTX Upload --}}
                <div class="qr-types qr-type-pptx" style="display:none;">
                    <div class="col-md-6">
                        <label>Upload PPTX File<span class="text-danger">*</span></label>
                        <input type="file" name="pptx_file" class="form-control @error('qr_type_fields.pptx_file') is-invalid @enderror" accept="application/pdf">
                        @if(!empty($edit?->pptx_file))
                            <small class="text-muted">Current: <a href="{{ url($edit->pptx_file) }}" target="_blank">View PPTX File</a></small>
                        @endif
                        @error('qr_type_fields.pptx_file')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>


                {{-- 2D Barcode --}}
                <div class="qr-types qr-type-2dbarcode" style="display: none;">

                    <div class="col-md-6 mb-2 barcode-text-field" style="display: none;">
                        <label>Text / GTIN Code <span class="text-danger">*</span></label>
                        <input type="text" name="gtin_code"
                            value="{{ old('qr_type_fields.gtin_code', $edit?->gtin_code ?? '') }}"
                            class="form-control @error('qr_type_fields.gtin_code') is-invalid @enderror"
                            placeholder="Enter GTIN or custom text">
                        @error('qr_type_fields.gtin_code')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="mb-2">
                        <label><strong>Encode As:</strong></label><br>
                        <div class="form-check form-check-inline">
                            <input type="radio" class="form-check-input" name="barcode_encode_type" value="link"
                                {{ old('barcode_encode_type', $edit?->barcode_encode_type ?? 'link') == 'link' ? 'checked' : '' }}>
                            <label class="form-check-label">Link to Product Page</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input type="radio" class="form-check-input" name="barcode_encode_type" value="text"
                                {{ old('barcode_encode_type', $edit?->barcode_encode_type ?? '') == 'text' ? 'checked' : '' }}>
                            <label class="form-check-label">Text Only</label>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Product Page Link --}}
                        <div class="col-md-6 mb-2 barcode-link-field">
                            <label>Product Page Link <span class="text-danger">*</span></label>
                            <input type="url" name="product_link"
                                value="{{ old('qr_type_fields.product_link', $edit?->product_link ?? '') }}"
                                class="form-control @error('qr_type_fields.product_link') is-invalid @enderror"
                                placeholder="https://example.com/product">
                            @error('qr_type_fields.product_link')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                    </div>
                </div>


                {{-- UPI --}}
                <div class="qr-types qr-type-upi" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>UPI ID <span class="text-danger">*</span></label>
                            <input type="text" name="upi_id"
                                value="{{ old('qr_type_fields.upi_id', $edit?->upi_id ?? '') }}"
                                class="form-control @error('qr_type_fields.upi_id') is-invalid @enderror"
                                placeholder="example@upi">
                            @error('qr_type_fields.upi_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-2">
                            <label>Currency</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio"
                                        name="upi_currency" id="currency_inr"
                                        value="INR"
                                        {{ old('qr_type_fields.upi_currency', $edit?->upi_currency ?? 'INR') == 'INR' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="currency_inr">INR</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-2">
                            <label>Payee Name</label>
                            <input type="text" name="payee_name"
                                value="{{ old('qr_type_fields.payee_name', $edit?->payee_name ?? '') }}"
                                class="form-control @error('qr_type_fields.payee_name') is-invalid @enderror"
                                placeholder="Receiver Name">
                            @error('qr_type_fields.payee_name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-2">
                            <label>Amount</label>
                            <input type="number" step="0.01" name="upi_amount"
                                value="{{ old('qr_type_fields.upi_amount', $edit?->upi_amount ?? '') }}"
                                class="form-control @error('qr_type_fields.upi_amount') is-invalid @enderror"
                                placeholder="0.00">
                            @error('qr_type_fields.upi_amount')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-2">
                            <label>Transaction Remark</label>
                            <input type="text" name="upi_remark"
                                value="{{ old('qr_type_fields.upi_remark', $edit?->upi_remark ?? '') }}"
                                class="form-control @error('qr_type_fields.upi_remark') is-invalid @enderror"
                                placeholder="Payment for services">
                            @error('qr_type_fields.upi_remark')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>



                {{-- PCR --}}
                <div class="qr-types qr-type-pcr" style="display:none;">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <label>Upload PCR File<span class="text-danger">*</span></label>
                            @if(!empty($edit?->pcr_file))
                                <input type="hidden" name="pcr_file_hidden" value="{{ $edit?->pcr_file }}"/>
                            @endif
                            <input type="file" name="pcr_file" class="form-control @error('qr_type_fields.pcr_file') is-invalid @enderror">
                            @if(!empty($edit?->pcr_file))
                                <small class="text-muted">Current: <a href="{{ url($edit->pcr_file) }}" target="_blank">View PCR File</a></small>
                            @endif
                            @error('qr_type_fields.pcr_file')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Expire Time <span class="text-danger">*</span></label>
                            <input type="date" name="expire_time" id="expire_time"
                                value="{{ old('qr_type_fields.expire_time', $edit?->expire_time ?? '') }}"
                                class="form-control @error('qr_type_fields.expire_time') is-invalid @enderror">
                            @error('qr_type_fields.expire_time')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-md-3 mb-2">
                            <label>Password <span class="text-danger">*</span></label>
                            <input type="password" name="pcr_password"
                                value="{{ old('qr_type_fields.pcr_password', $edit?->pcr_password ?? '') }}"
                                class="form-control @error('qr_type_fields.pcr_password') is-invalid @enderror">
                            @error('qr_type_fields.pcr_password')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- custom url --}}
               {{-- <div class="qr-types qr-type-customurl" style="display:none;">
                    <div class="bg-white rounded-xl shadow-md p-6">

                        <div class="col-md-3 mb-2">
                            <label class="form-label font-semibold">Default Link <span class="text-danger">*</span></label>
                            <input type="url" name="default_link"
                                value="{{ old('qr_type_fields.default_link', $edit?->default_link ?? '') }}"
                                class="form-control @error('qr_type_fields.default_link') is-invalid @enderror"
                                placeholder="https://example.com" required>
                            @error('qr_type_fields.default_link')
                                <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>



                        <div id="location-wrapper" class="mt-2">
                            <div class="form-control location-item border border-gray-200 rounded-lg p-4 mb-4 bg-gray-50">
                                <h5 class="font-bold text-lg mb-3 text-primary"> URL for Location 1</h5>
                                <div class="row g-3">
                                    <div class="col-md-2">
                                        <label class="form-label">Country</label>
                                        <select name="locations[0][country]" class="form-control search_by_country select2 select_filter" data-filterByStatus="active" data-append="search_by_country">
                                            <option value="">Filter by Country</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">State</label>
                                        <select name="locations[0][state]" class="form-control search_by_state select2 select_filter"
                                            data-append="search_by_state">
                                            <option value="">Filter by State</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">City</label>
                                        <select name="locations[0][city]"
                                            class="form-control search_by_city select2 select_filter" data-append="search_by_city">
                                            <option value="">Filter by City</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">URL Location</label>
                                        <input type="url" name="locations[0][url]" class="form-control" placeholder="https://example.com/location" required>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="mt-3">
                            <button type="button" id="add-location"
                                class="btn btn-primary rounded px-4 py-2">
                                + Add More Location
                            </button>
                        </div>
                    </div>
                </div> --}}


                <div class="row">
                    {{-- Divider --}}
                    <div class="divider">
                        <hr />
                    </div>

                    {{-- Submit Buttons --}}
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
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("#expire_time", {
            dateFormat: "d-m-Y",
            defaultDate: "{{ old('expire_time', isset($edit->expire_time) ? date('d-m-Y') : '') }}",
            onChange: function(selectedDates, dateStr, instance) {
                const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                document.getElementById('expire_time').value = ymd; // hidden input for submission
            }
        });
    </script>
    @if (!$company_id)
        @include('utils.getCompany')
    @endif

    <script>
        let basicItems = [
            'undo', 'redo', '|',
            'bold', 'italic', '|',
            'link', '|',
            'bulletedList', 'numberedList', '|',
            'alignment', '|',
        ]
        initializeCKEditor(".ckeditor_common_cls_whatsapp",basicItems);
        initializeCKEditor(".ckeditor_common_cls");

        $(".qr-types").hide();
        $(document).on('change', '.qr_type_id', function() {
            $(".qr-types").hide();
            if ($(this).find(':selected').attr('data-slug')) {
                $(".qr-type-" + $(this).find(':selected').attr('data-slug')).show();
            } else {
                toastr.error('Some thing want to wrong.');
            }
        });

        @isset($edit)
            $(".qr_type_id").trigger("change");
            var url = $('#map_link').val();
            if(url) {
                loadMap();
            }
        @endisset

        $(document).on('keyup', '#map_link', function() {
            var url = $('#map_link').val();
            if(url) {
                // Match pattern like /@lat,lng,zoomz
                var match = url.match(/@(-?\d+\.\d+),(-?\d+\.\d+),/);
                if(match) {
                    var lat = match[1];
                    var lng = match[2];

                    $('#latitude').val(lat);
                    $('#longitude').val(lng);

                    if(!lat || !lng) {
                        alert('Please enter valid latitude and longitude');
                        return;
                    }

                    // Initialize map
                    var map = L.map('map').setView([lat, lng], 14);

                    // Load OpenStreetMap tiles
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                    }).addTo(map);

                    // Add marker
                    L.marker([lat, lng]).addTo(map)
                        .bindPopup('Location: ' + lat + ', ' + lng)
                        .openPopup();
                } else {
                    alert('Could not extract latitude and longitude from the URL. Please paste a Google Maps URL.');
                }
            } else {
                alert('Please paste a Google Maps URL.');
            }
        });


        function loadMap(){
            var lat = parseFloat($('#latitude').val());
            var lng = parseFloat($('#longitude').val());

            if(!lat || !lng) {
                alert('Please enter valid latitude and longitude');
                return;
            }

            // Initialize map
            var map = L.map('map').setView([lat, lng], 14);

            // Load OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // Add marker
            L.marker([lat, lng]).addTo(map)
                .bindPopup('Location: ' + lat + ', ' + lng)
                .openPopup();
        }

    </script>
    <script>


    document.getElementById('add-location').addEventListener('click', function () {
        let wrapper = document.getElementById('location-wrapper');
        let locationIndex = $('#location-wrapper').find(".location-item").length;

        let locationHtml = `
        <div class="form-control location-item border border-gray-200 rounded-lg p-4 mb-4 bg-gray-50">
            <h5 class="font-bold text-lg mb-3 text-primary"> URL for Location ${locationIndex + 1}</h5>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Country</label>
                    <select name="locations[${locationIndex}][country]" class="form-control search_by_country select2 select_filter" data-filterByStatus="active" data-append="search_by_country">
                        <option value="">Filter by Country</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">State</label>
                    <select name="locations[${locationIndex}][state]" class="form-control search_by_state select2 select_filter"
                        data-append="search_by_state">
                        <option value="">Filter by State</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">City</label>
                    <select name="locations[${locationIndex}][city]"
                        class="form-control search_by_city select2 select_filter" data-append="search_by_city">
                        <option value="">Filter by City</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">URL Location</label>
                    <input type="url" name="locations[${locationIndex}][url]" class="form-control" placeholder="https://example.com/location" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="remove-location btn btn-primary rounded px-4 py-2 waves-effect waves-light mt-4">
                        Remove
                    </button>
                </div>
            </div>
        </div>

        `;

        wrapper.insertAdjacentHTML('beforeend', locationHtml);
        let newItem = wrapper.lastElementChild; // get just-added item

        // ✅ Re-init the dropdowns only for the new row
        initCountryDropdown(newItem);
        initCountryStateDropdown(newItem);
        initStateCityDropdown(newItem);
    });

    // Remove Location
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-location')) {
            e.target.closest('.location-item').remove();
        }
    });
    </script>

    @include('utils.getCountry')
    @include('utils.getStateByCountry')
    @include('utils.getCityByState')

    <script>
    function initCountryDropdown(context = document) {
        let stateCountry = $(context).find('.search_by_country');
        stateCountry.empty().append('<option value="">Loading...</option>');

        $.ajax({
            url: '{{ env('API_URL') }}get-country',
            success: function (response) {
                stateCountry.empty().append('<option value="">Select Country</option>');
                response.data.forEach(function (country) {
                    stateCountry.append('<option value="'+country.id+'">'+country.name+'</option>');
                });
            }
        });
    }

    function initCountryStateDropdown(context = document) {
        $(context).find('.search_by_country').select2();

        $(context).find('.search_by_country').on('change', function() {
            let countryId = $(this).val();
            let stateSelect = $(context).find('.search_by_state');
            stateSelect.empty().append('<option value="">Loading...</option>');

            if (countryId) {
                $.ajax({
                    type: 'POST',
                    url: '{{ env('API_URL') }}get-state',
                    data: {
                        country_id: countryId
                    },
                    success: function (response) {
                        stateSelect.empty().append('<option value="">Select State</option>');
                        response.data.forEach(function (state) {
                            stateSelect.append('<option value="'+state.id+'">'+state.name+'</option>');
                        });
                    }
                });
            }
        });
    }

    function initStateCityDropdown(context = document) {
        $(context).find('.search_by_state').on('change', function() {
            let countryId = $(context).find('.search_by_country').val();
            let stateId = $(this).val();
            let citySelect = $(context).find('.search_by_city');
            citySelect.empty().append('<option value="">Loading...</option>');

            if (stateId && countryId) {
                $.ajax({
                    type: 'POST',
                    url: '{{ env('API_URL') }}get-city',
                    data: {
                        country_id: countryId,
                        state_id: stateId
                    },
                    success: function (response) {
                        citySelect.empty().append('<option value="">Select City</option>');
                        response.data.forEach(function (city) {
                            citySelect.append('<option value="'+city.id+'">'+city.name+'</option>');
                        });
                    }
                });
            }
        });
    }
    </script>
@endpush
