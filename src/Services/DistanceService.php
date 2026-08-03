<?php
// src/Services/DistanceService.php — distance routière depuis Bordeaux (APIs ouvertes)

class DistanceService {
    private array $config;
    private string $userAgent = 'ViteEtGourmand/1.0 (ECF; vitegourmand322@gmail.com)';

    public function __construct() {
        $this->config = require __DIR__ . '/../../config/commandeconfig.php';
    }

    /**
     * Distance routière (km) depuis le centre de Bordeaux jusqu'à l'adresse / ville.
     * Retourne null si le calcul est impossible.
     */
    public function calculerKm(string $adresse, string $ville): ?float {
        $ville = trim($ville);
        if ($ville === '') {
            return null;
        }

        if (mb_strtolower($ville) === mb_strtolower($this->config['ville_livraison_gratuite'] ?? 'bordeaux')) {
            return 0.0;
        }

        $dest = $this->geocoder($adresse, $ville);
        if ($dest === null) {
            return null;
        }

        $originLat = (float) ($this->config['bordeaux_lat'] ?? 44.8378);
        $originLon = (float) ($this->config['bordeaux_lon'] ?? -0.5792);

        $km = $this->itineraireKm($originLon, $originLat, $dest['lon'], $dest['lat']);
        if ($km === null) {
            // Secours si OSRM est bloqué chez l'hébergeur : estimation vol d'oiseau × 1,3
            $km = $this->haversineKm($originLat, $originLon, $dest['lat'], $dest['lon']) * 1.3;
        }

        return round(max(0, $km), 1);
    }

    /** @return array{lat: float, lon: float}|null */
    private function geocoder(string $adresse, string $ville): ?array {
        $coords = $this->geocodeNominatim($adresse, $ville);
        if ($coords !== null) {
            return $coords;
        }

        // Secours (souvent plus permissif chez les hébergeurs mutualisés)
        return $this->geocodeOpenMeteo($ville);
    }

    /** @return array{lat: float, lon: float}|null */
    private function geocodeNominatim(string $adresse, string $ville): ?array {
        $parts = array_filter([trim($adresse), trim($ville), 'France']);
        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'q'            => implode(', ', $parts),
            'format'       => 'json',
            'limit'        => 1,
            'countrycodes' => 'fr',
        ]);

        $data = $this->httpJson($url);
        if (!is_array($data) || empty($data[0]['lat']) || empty($data[0]['lon'])) {
            return null;
        }

        return [
            'lat' => (float) $data[0]['lat'],
            'lon' => (float) $data[0]['lon'],
        ];
    }

    /** @return array{lat: float, lon: float}|null */
    private function geocodeOpenMeteo(string $ville): ?array {
        $url = 'https://geocoding-api.open-meteo.com/v1/search?' . http_build_query([
            'name'        => $ville,
            'count'       => 1,
            'language'    => 'fr',
            'format'      => 'json',
            'countryCode' => 'FR',
        ]);

        $data = $this->httpJson($url);
        if (!is_array($data) || empty($data['results'][0]['latitude']) || empty($data['results'][0]['longitude'])) {
            return null;
        }

        return [
            'lat' => (float) $data['results'][0]['latitude'],
            'lon' => (float) $data['results'][0]['longitude'],
        ];
    }

    private function itineraireKm(float $lon1, float $lat1, float $lon2, float $lat2): ?float {
        $url = sprintf(
            'https://router.project-osrm.org/route/v1/driving/%.6f,%.6f;%.6f,%.6f?overview=false',
            $lon1,
            $lat1,
            $lon2,
            $lat2
        );

        $data = $this->httpJson($url);
        if (!is_array($data) || ($data['code'] ?? '') !== 'Ok') {
            return null;
        }

        $metres = $data['routes'][0]['distance'] ?? null;
        if ($metres === null) {
            return null;
        }

        return ((float) $metres) / 1000;
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function httpJson(string $url): ?array {
        $raw = $this->httpGet($url);
        if ($raw === null || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function httpGet(string $url): ?string {
        // cURL en priorité (souvent le seul canal HTTP sortant autorisé chez Infomaniak)
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT        => 12,
                CURLOPT_HTTPHEADER     => [
                    'Accept: application/json',
                    'User-Agent: ' . $this->userAgent,
                ],
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $raw  = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($raw !== false && $err === '' && $code > 0 && $code < 400 && $raw !== '') {
                return $raw;
            }
        }

        if (!ini_get('allow_url_fopen')) {
            return null;
        }

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 12,
                'header'  => "User-Agent: {$this->userAgent}\r\nAccept: application/json\r\n",
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $raw = @file_get_contents($url, false, $ctx);
        return ($raw === false || $raw === '') ? null : $raw;
    }
}
