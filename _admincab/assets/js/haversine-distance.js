/**
 * HAVERSINE DISTANCE CALCULATOR
 * Menghitung jarak antara 2 koordinat GPS (Latitude, Longitude)
 *
 * Formula: Haversine Formula
 * Input: lat1, lon1, lat2, lon2 (dalam degrees)
 * Output: Jarak dalam KM (desimal)
 */

function calculateDistance(lat1, lon1, lat2, lon2) {
    // Validasi input
    if (!lat1 || !lon1 || !lat2 || !lon2) {
        return 0;
    }

    const R = 6371; // Radius bumi dalam KM
    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);

    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distance = R * c; // Jarak dalam KM

    return distance;
}

function toRad(degrees) {
    return degrees * (Math.PI / 180);
}

/**
 * Extract koordinat dari Google Maps URL
 * Support 2 format:
 * 1. @lat,long (format sharing)
 * 2. q=lat,long (format search)
 */
function extractCoordinatesFromURL(url) {
    let lat = null;
    let long = null;

    // Format 1: @lat,long
    const match1 = url.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
    if (match1) {
        lat = parseFloat(match1[1]);
        long = parseFloat(match1[2]);
    }

    // Format 2: q=lat,long
    const match2 = url.match(/q=(-?\d+\.\d+),(-?\d+\.\d+)/);
    if (match2) {
        lat = parseFloat(match2[1]);
        long = parseFloat(match2[2]);
    }

    return { lat: lat, long: long };
}

/**
 * Hitung tarif jemput berdasarkan jarak dan kondisi motor
 *
 * TARIF MOTOR JALAN:
 * - 1 km pertama: GRATIS
 * - 1.5 km: Rp 8,000
 * - Setiap +0.5 km: +Rp 2,000
 *
 * TARIF MOTOR MOGOK:
 * - 1 km pertama: GRATIS
 * - 1.5 km: Rp 11,000
 * - Setiap +0.5 km: +Rp 3,000
 */
function hitungTarifJemput(jarak, kondisi) {
    let tarif = 0;

    if (jarak <= 1.0) {
        // Gratis untuk jarak <= 1 km
        return 0;
    }

    if (kondisi === 'jalan') {
        // Motor Jalan
        if (jarak >= 1.5) {
            tarif = 8000; // Base 1.5 km
            const jarakLebih = jarak - 1.5;
            if (jarakLebih > 0) {
                const kelipatan = Math.ceil(jarakLebih / 0.5);
                tarif += (kelipatan * 2000);
            }
        } else {
            // Antara 1.0 - 1.5 km
            const selisih = jarak - 1.0;
            tarif = Math.ceil((selisih / 0.5) * 8000);
        }
    } else if (kondisi === 'mogok') {
        // Motor Mogok
        if (jarak >= 1.5) {
            tarif = 11000; // Base 1.5 km
            const jarakLebih = jarak - 1.5;
            if (jarakLebih > 0) {
                const kelipatan = Math.ceil(jarakLebih / 0.5);
                tarif += (kelipatan * 3000);
            }
        } else {
            // Antara 1.0 - 1.5 km
            const selisih = jarak - 1.0;
            tarif = Math.ceil((selisih / 0.5) * 11000);
        }
    }

    return tarif;
}

/**
 * Format angka ke Rupiah
 */
function formatRupiah(angka) {
    return 'Rp ' + angka.toLocaleString('id-ID');
}
