@props([
    'points' => []
])

@once

    @push('scripts')
        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    @endpush
@endonce

@php
    $mapId = 'map_' . uniqid();
@endphp

<div
    id="{{ $mapId }}"
    class="w-full h-[350px] rounded-md z-0"
></div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const points = @json($points);

        // Set default coordinates to Kasama if no points are available
        let defaultLat = -10.2129;
        let defaultLng = 31.1808;

        if (points.length > 0) {
            defaultLat = points[0].lat;
            defaultLng = points[0].lng;
        }

        const map = L.map('{{ $mapId }}')
            .setView([defaultLat, defaultLng], 13);

        L.tileLayer(
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19,
            }
        ).addTo(map);

        const bounds = [];

        points.forEach(point => {

            L.marker([point.lat, point.lng])
                .addTo(map)
                .bindPopup(`
                    <div>
                        <strong>${point.account ?? 'Unknown Account'}</strong><br>
                        ${point.time ?? ''}
                    </div>
                `).openPopup();

            bounds.push([point.lat, point.lng]);
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds);
        }
    });
</script>