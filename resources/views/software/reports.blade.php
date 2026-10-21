@extends('software.layout.app')

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />

<style>
    #map {
        height: 100vh;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        margin: 0 auto;
    }
</style>
@section('title', 'Dashboard')

@section('content')
    <div class="row">
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="badge p-2 bg-label-info mb-2 rounded">
                        <i class="ti ti-report-analytics ti-md"></i>
                    </div>
                    <h5 class="card-title mb-1 pt-2">Total Order</h5>
                    <small class="text-muted">Last week</small>
                    <p class="mb-2 mt-1">1.28k</p>
                    <div class="pt-1">
                        <span class="badge bg-label-secondary">-12.2%</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="badge p-2 bg-label-danger mb-2 rounded">
                        <i class="ti ti-user-pin ti-md"></i>

                    </div>
                    <h5 class="card-title mb-1 pt-2">Total Visit</h5>
                    <small class="text-muted">Last week</small>
                    <p class="mb-2 mt-1">1.28k</p>
                    <div class="pt-1">
                        <span class="badge bg-label-secondary">-12.2%</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="badge p-2 bg-label-warning  mb-2 rounded">
                        <i class="ti ti-calendar-check ti-md"></i>
                    </div>
                    <h5 class="card-title mb-1 pt-2">Total Follow-Up</h5>
                    <small class="text-muted">Last week</small>
                    <p class="mb-2 mt-1">1.28k</p>
                    <div class="pt-1">
                        <span class="badge bg-label-secondary">-12.2%</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="badge p-2 bg-label-success  mb-2 rounded">
                        <i class="ti ti-sitemap ti-md"></i>
                    </div>
                    <h5 class="card-title mb-1 pt-2">Total Team Person</h5>
                    <small class="text-muted">Last week</small>
                    <p class="mb-2 mt-1">1.28k</p>
                    <div class="pt-1">
                        <span class="badge bg-label-secondary">-12.2%</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="badge p-2 bg-label-primary  mb-2 rounded">
                        <i class="ti ti-users-group ti-md"></i>
                    </div>
                    <h5 class="card-title mb-1 pt-2">Total Customer</h5>
                    <small class="text-muted">Last week</small>
                    <p class="mb-2 mt-1">1.28k</p>
                    <div class="pt-1">
                        <span class="badge bg-label-secondary">-12.2%</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="badge p-2 bg-label-Dark  mb-2 rounded">
                        <i class="ti ti-truck ti-md"></i>
                    </div>
                    <h5 class="card-title mb-1 pt-2">Total Dispatch</h5>
                    <small class="text-muted">Last week</small>
                    <p class="mb-2 mt-1">1.28k</p>
                    <div class="pt-1">
                        <span class="badge bg-label-secondary">-12.2%</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Customer Location</h5>
                </div>
                <div class="card-body p-0">
                    <!-- Map container with a fixed height for proper display -->
                    <div id="map" style="height: 350px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header header-elements">
                    <h5 class="card-title mb-0">Average Skills</h5>
                    <div class="card-header-elements ms-auto py-0 dropdown">
                        <button type="button" class="btn dropdown-toggle hide-arrow p-0" id="heat-chart-dd"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-dots-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="heat-chart-dd">
                            <a class="dropdown-item" href="javascript:void(0);">Last 28 Days</a>
                            <a class="dropdown-item" href="javascript:void(0);">Last Month</a>
                            <a class="dropdown-item" href="javascript:void(0);">Last Year</a>
                        </div>
                    </div>
                </div>
               <div class="card-body">
                      <canvas id="polarChart" class="chartjs" data-height="337" height="337" width="636" style="display: block; box-sizing: border-box; height: 337px; width: 636px;"></canvas>
                    </div>
            </div>
        </div>
    </div>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>

    <script>
        // Centered on India
        var map = L.map('map').setView([22.9734, 78.6569], 5);

        // Use CartoDB light tiles for better design
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '© OceanInfotechMap',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(map);

        // Cluster group
        var markers = L.markerClusterGroup();

        // Marker list
        var locations = [
            [23.0225, 72.5714],
            [23.0250, 72.5720],
            [23.0240, 72.5700], // Ahmedabad
            [19.0760, 72.8777], // Mumbai
            [28.6139, 77.2090], // Delhi
            [13.0827, 80.2707], // Chennai
            [22.5726, 88.3639], // Kolkata
            [26.9124, 75.7873], // Jaipur
            [17.3850, 78.4867], // Hyderabad
            [21.1702, 72.8311], // Surat
            [18.5204, 73.8567], // Pune
        ];

        // Add to cluster
        locations.forEach(function(loc) {
            var marker = L.marker(new L.LatLng(loc[0], loc[1]));
            marker.bindPopup("Location: [" + loc[0] + ", " + loc[1] + "]");
            markers.addLayer(marker);
        });

        map.addLayer(markers);
    </script>
@endsection
