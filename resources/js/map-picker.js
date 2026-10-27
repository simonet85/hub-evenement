import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const root = document.querySelector('[data-map-picker]');

if (root) {
    const latInput = document.getElementById(root.dataset.latInput);
    const lngInput = document.getElementById(root.dataset.lngInput);
    const status = document.querySelector('[data-map-status]');
    const clearButton = document.querySelector('[data-map-clear]');
    const fallback = [5.3599, -4.0083];

    const map = L.map(root, { scrollWheelZoom: false }).setView(fallback, 11);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    let marker = null;

    const coordinates = () => {
        const lat = Number.parseFloat(latInput?.value ?? '');
        const lng = Number.parseFloat(lngInput?.value ?? '');

        return Number.isFinite(lat) && Number.isFinite(lng) ? [lat, lng] : null;
    };

    const renderStatus = () => {
        if (!status) {
            return;
        }

        const coords = coordinates();
        status.textContent = coords
            ? `Repère placé à ${coords[0].toFixed(5)}, ${coords[1].toFixed(5)}.`
            : 'Aucun repère placé.';
    };

    const placeMarker = (coords, center = false) => {
        if (marker) {
            marker.setLatLng(coords);
        } else {
            marker = L.circleMarker(coords, {
                radius: 10,
                color: '#3f5e48',
                weight: 2,
                fillColor: '#c9a227',
                fillOpacity: 0.9,
            }).addTo(map);
        }

        if (center) {
            map.setView(coords, Math.max(map.getZoom(), 14));
        }

        renderStatus();
    };

    const initial = coordinates();

    if (initial) {
        placeMarker(initial, true);
    } else {
        renderStatus();
    }

    map.on('click', (event) => {
        latInput.value = event.latlng.lat.toFixed(6);
        lngInput.value = event.latlng.lng.toFixed(6);
        placeMarker([Number(latInput.value), Number(lngInput.value)]);
    });

    [latInput, lngInput].forEach((input) => {
        input?.addEventListener('input', () => {
            const coords = coordinates();

            if (coords) {
                placeMarker(coords);
            } else {
                renderStatus();
            }
        });
    });

    clearButton?.addEventListener('click', () => {
        latInput.value = '';
        lngInput.value = '';

        if (marker) {
            map.removeLayer(marker);
            marker = null;
        }

        renderStatus();
    });

    window.setTimeout(() => map.invalidateSize(), 0);
}
