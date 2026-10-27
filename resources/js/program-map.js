import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const root = document.querySelector('[data-program-map]');

if (root) {
    const lat = Number.parseFloat(root.dataset.lat ?? '');
    const lng = Number.parseFloat(root.dataset.lng ?? '');

    if (Number.isFinite(lat) && Number.isFinite(lng)) {
        const map = L.map(root, { scrollWheelZoom: false }).setView([lat, lng], 15);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);

        const marker = L.circleMarker([lat, lng], {
            radius: 11,
            color: '#3f5e48',
            weight: 2,
            fillColor: '#c9a227',
            fillOpacity: 0.95,
        }).addTo(map);

        if (root.dataset.venue) {
            const label = document.createElement('span');
            label.textContent = root.dataset.venue;
            marker.bindPopup(label);
        }

        window.setTimeout(() => map.invalidateSize(), 0);
    }
}
