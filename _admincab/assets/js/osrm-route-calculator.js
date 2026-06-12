/**
 * OSRM ROUTE DISTANCE CALCULATOR + LEAFLET MAP PREVIEW
 * 
 * Menghitung jarak rute jalan raya menggunakan OSRM API (Open Source Routing Machine)
 * dan menampilkan preview peta rute menggunakan Leaflet.js
 * 
 * Dependencies:
 * - Leaflet.js (https://leafletjs.com/)
 * - OSRM public API (https://router.project-osrm.org/)
 * 
 * @author Claude Code
 * @date 2025-12-15
 */

// OSRM API endpoint (public demo server)
const OSRM_API_URL = 'https://router.project-osrm.org/route/v1/driving/';

// Leaflet map instance
let routeMap = null;
let routeLayer = null;
let markersLayer = null;

/**
 * Extract koordinat dari berbagai format Google Maps URL
 * 
 * Supported formats:
 * - https://www.google.com/maps/@-6.2088,106.8456,17z
 * - https://www.google.com/maps?q=-6.2088,106.8456
 * - https://maps.google.com/maps?ll=-6.2088,106.8456
 * - https://www.google.com/maps/place/.../@-6.2088,106.8456,17z
 * - Plain coordinates: -6.2088,106.8456
 * 
 * @param {string} url - Google Maps URL or coordinates
 * @returns {object|null} - {lat: number, lng: number} atau null jika tidak valid
 */
function extractCoordinatesFromGMaps(url) {
    if (!url || url.trim() === '') {
        return null;
    }

    let lat = null;
    let lng = null;

    // Pattern 1: @lat,lng (paling umum dari share link)
    const pattern1 = /@(-?\d+\.?\d*),(-?\d+\.?\d*)/;
    const match1 = url.match(pattern1);
    if (match1) {
        lat = parseFloat(match1[1]);
        lng = parseFloat(match1[2]);
    }

    // Pattern 2: q=lat,lng (format search/query)
    if (!lat) {
        const pattern2 = /[?&]q=(-?\d+\.?\d*),(-?\d+\.?\d*)/;
        const match2 = url.match(pattern2);
        if (match2) {
            lat = parseFloat(match2[1]);
            lng = parseFloat(match2[2]);
        }
    }

    // Pattern 3: ll=lat,lng (legacy format)
    if (!lat) {
        const pattern3 = /[?&]ll=(-?\d+\.?\d*),(-?\d+\.?\d*)/;
        const match3 = url.match(pattern3);
        if (match3) {
            lat = parseFloat(match3[1]);
            lng = parseFloat(match3[2]);
        }
    }

    // Pattern 4: Plain coordinates (lat,lng)
    if (!lat) {
        const pattern4 = /^(-?\d+\.?\d*),\s*(-?\d+\.?\d*)$/;
        const match4 = url.trim().match(pattern4);
        if (match4) {
            lat = parseFloat(match4[1]);
            lng = parseFloat(match4[2]);
        }
    }

    // Pattern 5: !3d lat !4d lng format (dari embed)
    if (!lat) {
        const pattern5 = /!3d(-?\d+\.?\d*)!4d(-?\d+\.?\d*)/;
        const match5 = url.match(pattern5);
        if (match5) {
            lat = parseFloat(match5[1]);
            lng = parseFloat(match5[2]);
        }
    }

    // Validasi range koordinat Indonesia
    if (lat && lng) {
        // Indonesia: Lat -11 to 6, Lng 95 to 141
        if (lat >= -12 && lat <= 8 && lng >= 90 && lng <= 145) {
            return { lat: lat, lng: lng };
        }
    }

    return null;
}

/**
 * Hitung jarak rute jalan menggunakan OSRM API
 * 
 * @param {number} lat1 - Latitude origin (bengkel)
 * @param {number} lng1 - Longitude origin (bengkel)
 * @param {number} lat2 - Latitude destination (pelanggan)
 * @param {number} lng2 - Longitude destination (pelanggan)
 * @returns {Promise<object>} - {distance: km, duration: minutes, geometry: geojson}
 */
async function calculateOSRMDistance(lat1, lng1, lat2, lng2) {
    // OSRM format: lng,lat;lng,lat (longitude first!)
    const url = `${OSRM_API_URL}${lng1},${lat1};${lng2},${lat2}?overview=full&geometries=geojson`;

    try {
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`OSRM API error: ${response.status}`);
        }

        const data = await response.json();

        if (data.code !== 'Ok' || !data.routes || data.routes.length === 0) {
            throw new Error('Tidak dapat menemukan rute');
        }

        const route = data.routes[0];

        return {
            distance: route.distance / 1000, // Convert meters to KM
            duration: route.duration / 60,   // Convert seconds to minutes
            geometry: route.geometry         // GeoJSON LineString
        };
    } catch (error) {
        console.error('OSRM Error:', error);
        throw error;
    }
}

/**
 * Inisialisasi Leaflet map untuk preview rute
 * 
 * @param {string} mapElementId - ID dari container div
 */
function initRouteMap(mapElementId) {
    // Destroy existing map if any
    if (routeMap) {
        routeMap.remove();
        routeMap = null;
    }

    // Initialize new map
    routeMap = L.map(mapElementId).setView([-6.2, 106.8], 12);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(routeMap);

    // Initialize layers
    routeLayer = L.layerGroup().addTo(routeMap);
    markersLayer = L.layerGroup().addTo(routeMap);

    // Force map to recalculate size (important when container was hidden)
    setTimeout(() => {
        routeMap.invalidateSize();
    }, 100);

    return routeMap;
}

/**
 * Tampilkan rute pada peta Leaflet
 * 
 * @param {string} mapElementId - ID dari container div
 * @param {number} lat1 - Latitude origin (bengkel)
 * @param {number} lng1 - Longitude origin (bengkel)
 * @param {number} lat2 - Latitude destination (pelanggan)
 * @param {number} lng2 - Longitude destination (pelanggan)
 * @param {string} bengkelName - Nama bengkel (untuk marker)
 * @param {string} pelangganName - Nama pelanggan (untuk marker)
 * @returns {Promise<object>} - Route info {distance, duration}
 */
async function displayRouteOnMap(mapElementId, lat1, lng1, lat2, lng2, bengkelName = 'Bengkel', pelangganName = 'Pelanggan') {
    // Initialize map if not exists
    if (!routeMap) {
        initRouteMap(mapElementId);
    }

    // Clear previous routes and markers
    if (routeLayer) routeLayer.clearLayers();
    if (markersLayer) markersLayer.clearLayers();

    try {
        // Get route from OSRM
        const routeData = await calculateOSRMDistance(lat1, lng1, lat2, lng2);

        // Convert GeoJSON coordinates [lng, lat] to Leaflet format [lat, lng]
        let routeCoords = [];
        if (routeData.geometry && routeData.geometry.coordinates) {
            routeCoords = routeData.geometry.coordinates.map(coord => [coord[1], coord[0]]);
        }

        // Draw route polyline (using L.polyline for better compatibility)
        if (routeCoords.length > 0) {
            const routeLine = L.polyline(routeCoords, {
                color: '#3388ff',
                weight: 6,
                opacity: 0.8,
                lineJoin: 'round'
            });
            routeLayer.addLayer(routeLine);
        }

        // Custom icons
        const bengkelIcon = L.divIcon({
            className: 'custom-marker',
            html: '<div style="background-color: #dc3545; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><i class="fa fa-wrench"></i></div>',
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });

        const pelangganIcon = L.divIcon({
            className: 'custom-marker',
            html: '<div style="background-color: #28a745; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><i class="fa fa-home"></i></div>',
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });

        // Add markers
        const bengkelMarker = L.marker([lat1, lng1], { icon: bengkelIcon })
            .bindPopup(`<b>${bengkelName}</b><br>Lokasi Bengkel`);
        markersLayer.addLayer(bengkelMarker);

        const pelangganMarker = L.marker([lat2, lng2], { icon: pelangganIcon })
            .bindPopup(`<b>${pelangganName}</b><br>Lokasi Penjemputan`);
        markersLayer.addLayer(pelangganMarker);

        // Debug: Log route coordinates count
        console.log('Route coordinates count:', routeCoords.length);

        // Fit bounds to show entire route using route coordinates or markers
        if (routeCoords.length > 0) {
            const routeBounds = L.latLngBounds(routeCoords);
            routeMap.fitBounds(routeBounds, { padding: [30, 30] });
        } else {
            const bounds = L.latLngBounds([[lat1, lng1], [lat2, lng2]]);
            routeMap.fitBounds(bounds, { padding: [50, 50] });
        }

        // Force re-render after all operations (important for hidden containers)
        setTimeout(() => {
            routeMap.invalidateSize();
        }, 200);

        return {
            distance: routeData.distance,
            duration: routeData.duration
        };

    } catch (error) {
        console.error('Error displaying route:', error);
        throw error;
    }
}

/**
 * Hitung tarif jemput berdasarkan jarak dan kondisi motor
 * (Duplikasi dari haversine-distance.js untuk standalone use)
 * 
 * @param {number} jarak - Jarak dalam KM
 * @param {string} kondisi - 'jalan' atau 'mogok'
 * @returns {number} - Tarif dalam Rupiah
 */
function hitungTarifJemputOSRM(jarak, kondisi) {
    let tarif = 0;

    if (jarak <= 1.0) {
        return 0; // Gratis untuk <= 1 km
    }

    if (kondisi === 'jalan') {
        if (jarak >= 1.5) {
            tarif = 8000;
            const jarakLebih = jarak - 1.5;
            if (jarakLebih > 0) {
                tarif += Math.ceil(jarakLebih / 0.5) * 2000;
            }
        } else {
            tarif = Math.ceil((jarak - 1.0) / 0.5) * 8000;
        }
    } else if (kondisi === 'mogok') {
        if (jarak >= 1.5) {
            tarif = 11000;
            const jarakLebih = jarak - 1.5;
            if (jarakLebih > 0) {
                tarif += Math.ceil(jarakLebih / 0.5) * 3000;
            }
        } else {
            tarif = Math.ceil((jarak - 1.0) / 0.5) * 11000;
        }
    }

    return tarif;
}

/**
 * Format durasi ke string yang readable
 * 
 * @param {number} minutes - Durasi dalam menit
 * @returns {string} - Formatted string
 */
function formatDuration(minutes) {
    if (minutes < 60) {
        return Math.round(minutes) + ' menit';
    } else {
        const hours = Math.floor(minutes / 60);
        const mins = Math.round(minutes % 60);
        return hours + ' jam ' + mins + ' menit';
    }
}

/**
 * Format jarak ke string dengan 1 desimal
 * 
 * @param {number} km - Jarak dalam KM  
 * @returns {string} - Formatted string
 */
function formatDistance(km) {
    return km.toFixed(1) + ' KM';
}

// Export for use in other scripts
if (typeof window !== 'undefined') {
    /**
     * Force resize map (fix grey tiles issue)
     */
    function resizeMap() {
        if (routeMap) {
            routeMap.invalidateSize();
            // Re-fit bounds if layers exist
            if (markersLayer && markersLayer.getLayers().length > 0) {
                // Optional: re-center
            }
        }
    }

    window.OSRMCalculator = {
        extractCoordinatesFromGMaps,
        calculateOSRMDistance,
        initRouteMap,
        displayRouteOnMap,
        hitungTarifJemputOSRM,
        formatDuration,
        formatDistance,
        resizeMap
    };
}
