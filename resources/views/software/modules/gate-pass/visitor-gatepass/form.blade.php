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
                        <div class="col-md-4 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Select Company <span class="text-danger">*</span></label>
                                <select id="company_id"
                                    class="form-control select2 search_by_company @error('company_id') is-invalid @enderror"
                                    name="company_id"
                                    data-selectedCompanyId="{{ old('company_id') ?? ($edit->company_id ?? '') }}">
                                    <option value="">Select Company</option>
                                </select>

                                @error('company_id')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    @else
                        <input type="hidden" class="form-control search_by_company" name="company_id"
                            value="{{ $company_id }}" />
                    @endif


                    {{-- Visitor Name --}}
                    <div class="col-md-4 mb-2">
                        <label>Visitor Name <span class="text-danger">*</span></label>
                        <input type="text" name="visitor_name" placeholder="Enter Visitor Name"
                            class="form-control @error('visitor_name') is-invalid @enderror"
                            value="{{ old('visitor_name', $edit->visitor_name ?? '') }}">
                        @error('visitor_name')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    {{-- Contact Number --}}
                    <div class="col-md-4 mb-2">
                        <label>Contact Number <span class="text-danger">*</span></label>
                        <input type="tel" name="contact_number" placeholder="Enter Contact Number"
                            minlength="{{ $mobile_min }}" maxlength="{{ $mobile_max }}"
                            class="form-control @error('contact_number') is-invalid @enderror"
                            value="{{ old('contact_number', $edit->contact_number ?? '') }}" pattern="[0-9]*"
                            inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        @error('contact_number')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>



                    {{-- Email --}}
                    <div class="col-md-4 mb-2">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="Enter Email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $edit->email ?? '') }}">
                        @error('email')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    {{-- Visitor Type --}}
                    <div class="col-md-4 mb-2">
                        <label for="visitor_type_id">Visitor Type</label>
                        <select name="visitor_type_id"
                            class="form-control search_by_visitor_type select2 @error('visitor_type_id') is-invalid @enderror"
                            data-selectedvisitortypeid="{{ old('visitor_type_id', $edit->visitor_type_id ?? '') }}">
                            <option value="">Select Visitor Type</option>
                        </select>
                        @error('visitor_type_id')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>


                    {{-- Organization Name --}}
                    <div class="col-md-4 mb-2">
                        <label>Organization Name</label>
                        <input type="text" name="organization_name" placeholder="Enter Organization Name"
                            class="form-control" value="{{ old('organization_name', $edit->organization_name ?? '') }}">
                    </div>

                    {{-- Visiting Date & Time --}}
                    <div class="col-md-4 mb-2">
                        <label>Visiting Date & Time </label>
                        <input type="datetime-local" name="visiting_datetime"
                            value="{{ old('visiting_datetime', isset($edit->visiting_datetime) ? \Carbon\Carbon::parse($edit->visiting_datetime)->format('Y-m-d\TH:i') : \Carbon\Carbon::now()->format('Y-m-d\TH:i')) }}"
                            class="form-control @error('visiting_datetime') is-invalid @enderror">
                        @error('visiting_datetime')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>


                    {{-- Purpose of Visit --}}
                    <div class="col-md-4 mb-2">
                        <label for="purpose_of_visit_id">Purpose of Visit <span class="text-danger">*</span></label>
                        <select name="purpose_of_visit_id"
                            class="form-control search_by_purpose_visit select2 @error('purpose_of_visit_id') is-invalid @enderror"
                            data-selectedpurposevisitid="{{ old('purpose_of_visit_id', $edit->purpose_of_visit_id ?? '') }}">
                            <option value="">Select Purpose of Visit</option>
                        </select>
                        @error('purpose_of_visit_id')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                    {{-- Person to Meet --}}
                    <div class="col-md-4 mb-2">
                        <label for="team_person_id">Person to Meet <span class="text-danger">*</span></label>
                        <select name="team_person_id"
                            class="form-control select2 search_by_assigned_to select2 @error('team_person_id') is-invalid @enderror"
                            data-selectedperson="{{ old('team_person_id', $edit->team_person_id ?? '') }}">
                            <option value="">Select Team Person</option>
                        </select>
                        @error('team_person_id')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>


                    {{-- Department --}}
                    <div class="col-md-4 mb-2">
                        <label for="department_id">Department</label>
                        <select name="department_id"
                            class="form-control search_by_department select2 @error('department_id') is-invalid @enderror"
                            data-selecteddepartmentid="{{ old('department_id', $edit->department_id ?? '') }}">
                            <option value="">Select Department</option>
                        </select>
                        @error('department_id')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>


                    {{-- ID Proof Type --}}
                    <div class="col-md-4 mb-2">
                        <label>ID Proof Type</label>
                        <select name="id_proof_type" class="form-control select2">
                            <option value="">Select ID Type</option>
                            @foreach (['Aadhar', 'DL', 'Passport', 'Other'] as $proof)
                                <option value="{{ $proof }}"
                                    {{ old('id_proof_type', $edit->id_proof_type ?? '') === $proof ? 'selected' : '' }}>
                                    {{ $proof }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ID Proof Number --}}
                    <div class="col-md-4 mb-2">
                        <label>ID Proof Number</label>
                        <input type="text" name="id_proof_number" placeholder="Enter ID Proof Number"
                            class="form-control" value="{{ old('id_proof_number', $edit->id_proof_number ?? '') }}">
                    </div>

                    {{-- Vehicle Number --}}
                    <div class="col-md-4 mb-2">
                        <label>Vehicle Number</label>
                        <input type="text" name="vehicle_number" placeholder="Enter Vehicle Number"
                            class="form-control" value="{{ old('vehicle_number', $edit->vehicle_number ?? '') }}">
                    </div>

                    {{-- Items Carried In --}}
                    <div class="col-md-4 mb-2">
                        <label>Items Carried In?</label>
                        <select name="items_carried_in" class="form-control select2">
                            <option value="">Select</option>
                            <option value="0"
                                {{ (string) old('items_carried_in', $edit->items_carried_in ?? '') === '0' ? 'selected' : '' }}>
                                No</option>
                            <option value="1"
                                {{ (string) old('items_carried_in', $edit->items_carried_in ?? '') === '1' ? 'selected' : '' }}>
                                Yes</option>
                        </select>
                    </div>

                    {{-- Gate/Location Name --}}
                    <div class="col-md-4 mb-2">
                        <label>Gate/Location Name</label>
                        <input type="text" name="gate_location_name" placeholder="Enter Gate or Location Name"
                            class="form-control"
                            value="{{ old('gate_location_name', $edit->gate_location_name ?? '') }}">
                    </div>

                     {{-- Divider --}}
                    <div class="divider">
                        <hr />
                    </div>
                    <!-- New Card for Photo & Signature -->
                    <div class="card mb-3 mt-3">

                        <div class="card-body row">

                            {{-- Photo --}}
                            <div class="col-md-6 mb-2">
                                <label>Capture Photo</label><br>
                                <video id="video" width="100%" height="240" autoplay></video>
                                <button type="button" class="btn btn-sm btn-primary mt-2"
                                    id="capture-btn">Capture</button>
                                <canvas id="canvas" style="display:none;"></canvas>
                                <input type="hidden" name="photo" id="captured_photo">
                                <div id="preview" class="mt-2"></div>

                                @if (!empty($edit->photo))
                                    <button type="button" class="btn btn-sm btn-info mt-2"
                                        onclick="window.open('{{ asset('/' . $edit->photo) }}', '_blank')">
                                        View Photo
                                    </button>
                                @endif
                            </div>

                            {{-- Signature --}}
                            <div class="col-md-6 mb-2">
                                <label>Signature</label><br />
                                <canvas id="signature-pad" class="border rounded"
                                    style="width: 100%;height: 240px;touch-action: none;"></canvas>
                                <input type="hidden" name="signature" id="signature">

                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-secondary"
                                        id="clear-signature">Clear</button>
                                </div>

                                @if (!empty($edit->signature))
                                    <button type="button" class="btn btn-sm btn-info mt-2"
                                        onclick="window.open('{{ asset('/' . $edit->signature) }}', '_blank')">
                                        View Signature
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>




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
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script>
        let canvas = document.getElementById("signature-pad");
        let signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255, 255, 255)'
        });

        // Resize canvas on load
        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.clear(); // clear after resize
        }

        window.addEventListener("resize", resizeCanvas);
        resizeCanvas();

        // On form submit, put base64 into hidden input
        $('form').on('submit', function(e) {
            if (!signaturePad.isEmpty()) {
                const dataURL = signaturePad.toDataURL();
                $('#signature').val(dataURL);
            }
        });

        // Clear button
        $('#clear-signature').on('click', function() {
            signaturePad.clear();
            $('#signature').val('');
        });
    </script>
    <script>
        $(document).ready(function() {
            if ($('meta[name="company_id"]').attr('value')) {
                fetchTeams();
            }

            $(document).on('change', '.search_by_company', function() {
                const selectedCompanyId = $(".search_by_company option:selected").val();
                if (selectedCompanyId) {
                    fetchTeams(selectedCompanyId);
                }
            });
        });

        function fetchTeams(company_id = null) {
            const instance = $('.search_by_assigned_to');
            const selected_id = instance.data("selectedperson") || '';

            if (!company_id && $('meta[name="company_id"]').attr('value')) {
                company_id = $('meta[name="company_id"]').attr('value');
            }

            if (!company_id) {
                instance.html('<option value="">Select Team Person</option>').trigger('change.select2');
                return;
            }

            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-teams',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    company_id
                },
                success: function(response) {
                    if (response.status && response.data && response.data.length > 0) {
                        let options = '<option value="">Select Team Person</option>';
                        $.each(response.data, function(i, item) {
                            const selected = selected_id == item.id ? 'selected' : '';
                            options +=
                                `<option value="${item.id}" ${selected}>${item.team_name}</option>`;
                        });
                        instance.html(options).trigger('change.select2');
                    } else {
                        instance.html('<option value="">No team persons found</option>').trigger(
                            'change.select2');
                    }
                },
                error: function() {
                    instance.html('<option value="">Error fetching team persons</option>').trigger(
                        'change.select2');
                }
            });
        }
    </script>
    <script>
        $(function() {
            // Open webcam
            navigator.mediaDevices.getUserMedia({
                    video: true
                })
                .then(function(stream) {
                    $('#video').get(0).srcObject = stream;
                })
                .catch(function(err) {
                    alert("Webcam access denied: " + err);
                });

            // Capture image
            $('#capture-btn').on('click', function() {
                const video = $('#video').get(0);
                const canvas = $('#canvas').get(0);
                const context = canvas.getContext('2d');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                const dataURL = canvas.toDataURL('image/png');
                $('#captured_photo').val(dataURL);
                $('#preview').html('<img src="' + dataURL + '" class="img-thumbnail mt-2" width="100%">');
            });
        });
    </script>


    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getVisitorType')
    @include('utils.getDepartment')
    @include('utils.getPurposeVisit')

@endpush
