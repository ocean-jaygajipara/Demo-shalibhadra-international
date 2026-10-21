<!DOCTYPE html>
<html>
<head>
    <title>Route Map</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />

    <style>
        #map {
            height: 100vh;
            width: 100%;
        }
    </style>
</head>
<body>

<div id="map"></div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.min.js"></script>

<script>
    const coordinates = [
        [22.254813, 70.788579],
        [22.256449, 70.788464],
        [22.257347, 70.786675],
        [22.260689, 70.781521]
    ];

    const map = L.map('map').setView(coordinates[0], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Add perfect driving route
    L.Routing.control({
        waypoints: coordinates.map(coord => L.latLng(coord[0], coord[1])),
        routeWhileDragging: false,
        show: false,
        addWaypoints: false,
        draggableWaypoints: false,
        fitSelectedRoutes: true
    }).addTo(map);
</script>

</body>
</html>
