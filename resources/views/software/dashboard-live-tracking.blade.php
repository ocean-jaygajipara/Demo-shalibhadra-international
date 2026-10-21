@extends('software.layout.app')

@php
    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    $authLoginUserDetail = isset($modules['authLoginUserDetail']) ? $modules['authLoginUserDetail'] : null;
    $loginUserId = isset($authLoginUserDetail?->id) ? $authLoginUserDetail?->id : null;
    $parent_type_id = isset($modules['parent_type_id']) ? $modules['parent_type_id'] : null;
    // dd($modules);
@endphp
@section('title', $page_title)

@section('page_leavel_style')

    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/flatpickr/flatpickr.css') }}" />


    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />

@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="row">
                @if (!$company_id)
                    <div class="col-md-3 col-sm-6 mb-3">
                        <!-- Company -->
                        <div class="form-group">
                            <label class="form-label fw-bold">Select Company <span class="text-danger">*</span></label>
                            <select id="company_id"
                                class="form-control select2 search_by_company @error('company_id') is-invalid @enderror"
                                name="company_id" data-selectedCompanyId="{{ old('company_id', $edit->company_id ?? '') }}">
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
                <div class="col-md-3 col-sm-6 mb-3">
                    <label for="date" class="form-label fw-bold">Date</label>
                    <input type="date" id="date" class="form-control" value="2025-04-02">
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <label for="date" class="form-label fw-bold"></label>
                    <button class="btn btn-primary w-100" id="search_route_button">Route</button>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <div id="map" style="min-height: 800px;"></div>
                </div>
            </div>
        </div>

    </div>
    <!-- Leaflet JS -->
    </div>
@endsection


@push('page_scripts')
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.motion/dist/leaflet.motion.min.js"></script>
    <script src="{{ asset('software/vendor/libs/moment/moment.js') }}"></script>
    <script src="{{ asset('software/vendor/libs/flatpickr/flatpickr.js') }}"></script>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>


    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    {{-- @include('utils.getTeamPerson') --}}

    <script>
        flatpickr("#date", {
            dateFormat: "d-m-Y", // Example: 2025-05-13
            defaultDate: new Date()
        });

        // Centered on India
        var map = L.map('map').setView([22.9734, 78.6569], 5);

        // Use CartoDB light tiles for better design
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '© OceanInfotechMap',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(map);

        // ✅ Define cluster group globally ONCE
        var markers = L.markerClusterGroup();
        map.addLayer(markers);

        // Optional: add initial demo markers
        var locations = [
            [23.0225, 72.5714],
            [19.0760, 72.8777],
            [28.6139, 77.2090]
        ];

        locations.forEach(function(loc) {
            var marker = L.marker(new L.LatLng(loc[0], loc[1]));
            marker.bindPopup("Location: [" + loc[0] + ", " + loc[1] + "]");
            markers.addLayer(marker);
        });

        /*


        var markers = [];
        var polyline;
        var motionLine;

        function animateMarker() {

            var animatedMarker;
            var currentStep = 0;
            var totalSteps = 0;
            var animationSpeed = 1000; // Adjust animation speed
            if (currentStep >= totalSteps) return;
            animatedMarker.setLatLng(pathCoordinates[currentStep]);
            currentStep++;
            setTimeout(animateMarker, animationSpeed);
        }


        function animateMarkerAlongRoute(routeCoordinates, stopTypes) {
            let index = 0;
            animatedMarker = L.marker(routeCoordinates[0], {
                icon: customIconTrakingSync
            }).addTo(map);

            function moveMarker() {
                if (index >= routeCoordinates.length) return;

                animatedMarker.setLatLng(routeCoordinates[index]);

                const isVisit = stopTypes[index] === "visits";
                const delay = isVisit ? 7000 : 100;

                index++;
                animationTimeout = setTimeout(moveMarker, delay);
            }

            moveMarker();
        }

        function stopMarkerAnimation() {
            clearTimeout(animationTimeout);
            if (animatedMarker) {
                map.removeLayer(animatedMarker);
                animatedMarker = null;
            }
        }
            */

        $(document).ready(function() {
            $(document).on('click', '#search_route_button', function() {
                let apiPayload = {};
                var team_person_id = $('#team_person_ids').val();
                var date = $('#date').val();
                var sync_pin = $('#sync_pin').is(':checked') ? 1 : 0;
                apiPayload.sync_pin = sync_pin;

                if ($('meta[name="company_id"]').attr('value')) {
                    apiPayload.company_id = $('meta[name="company_id"]').attr('value');
                } else if ($('.search_by_company').find('option:selected').val()) {
                    apiPayload.company_id = $('.search_by_company').find('option:selected').val();
                }

                if (team_person_id == '') {
                    toastr.error("Please select a team person", "Validation Error");
                    return;
                } else {
                    apiPayload.team_person_id = team_person_id;
                }

                if (date == '') {
                    toastr.error("Please select a date", "Validation Error");
                    return;
                } else {
                    apiPayload.date = date;
                }

                $.ajax({
                    url: "{{ route('live-tracking-dashboard') }}",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    type: "POST",
                    data: apiPayload,
                    dataType: 'json',
                    success: function(response) {
                        console.log("L-286", response);
                        if (response.status == true) {
                            let responseData = response?.data;

                            // ✅ Clear old pins before adding new ones
                            markers.clearLayers();

                            responseData.forEach(function(row) {
                                var marker = L.marker(new L.LatLng(row?.latitude, row
                                    ?.longitude));

                                let pinDetail = "";
                                pinDetail += "<b>Code: " + row?.user_employee_code +
                                    "</b><br>";
                                pinDetail += "Name: " + row?.user_name + "<br>";
                                pinDetail += "Email: " + row?.user_email + "<br>";
                                pinDetail += "Last Date Time: " + row?.created_at +
                                    "<br>";
                                if (row?.address && row?.address !== "") {
                                    pinDetail += "Address: " + row?.address + "<br>";
                                }

                                marker.bindPopup(pinDetail);
                                markers.addLayer(marker); // add to cluster
                            });

                        } else {
                            // ✅ Just clear all markers on failure
                            markers.clearLayers();

                            if (polyline) map.removeLayer(polyline);
                            if (motionLine) map.removeLayer(motionLine);

                            $('#routes').html('');
                            $('#team_person_details').hide();
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });
            });

        });
    </script>
@endpush
