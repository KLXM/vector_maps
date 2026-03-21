<?php

namespace KLXM\VectorMaps;

use rex_file;
use rex_path;
use rex_request;
use rex_response;

class Proxy
{
    /** @var array<string,string> Extension → MIME-Type */
    private const MIME_MAP = [
        'pbf'  => 'application/vnd.mapbox-vector-tile',
        'mvt'  => 'application/vnd.mapbox-vector-tile',
        'json' => 'application/json',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
    ];

    /** @var array<string,string> MIME-Type → Extension (für Content-Type-Erkennung ohne URL-Extension) */
    private const CT_TO_EXT = [
        'application/vnd.mapbox-vector-tile' => 'pbf',
        'application/x-protobuf'             => 'pbf',
        'application/json'                   => 'json',
        'text/javascript'                    => 'json',
        'application/javascript'             => 'json',
        'image/png'                          => 'png',
        'image/jpeg'                         => 'jpg',
        'image/webp'                         => 'webp',
        'image/gif'                          => 'gif',
    ];

    public static function intercept(): void
    {
        $targetUrl = rex_request('target_url', 'string', '');

        if (!$targetUrl) {
            rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
            rex_response::sendContent('Missing target_url.');
            exit;
        }

        $allowedDomains = [
            // OpenFreeMap – Vektorkacheln, Styles, Fonts (Hauptanbieter)
            'https://tiles.openfreemap.org/',
            // OSM Nominatim – Adresssuche im Picker
            'https://nominatim.openstreetmap.org/',
            // OSRM – Open Source Routing Machine (driving/walking/cycling)
            'https://router.project-osrm.org/',
            // OSM-Routing-Server: Fahrrad- und Fußgänger-Profile (via OSRM)
            'https://routing.openstreetmap.de/',
            // Transitous – ÖPNV-Routing (Motis2-API, Europa-weit)
            'https://api.transitous.org/',
            // MapTiler Cloud – Tiles, Styles, Georeferenzierung
            'https://api.maptiler.com/',
            // Stadia Maps (ex-Stamen) – Vektor- und Raster-Stile
            'https://tiles.stadiamaps.com/',
            // Protomaps CDN – Schriftarten (über PMTiles-Setup)
            'https://cdn.protomaps.com/',
            // OpenSTreetMap Community-Raster-Tiles
            'https://tile.openstreetmap.org/',
            // OSM-Tile-Spiegel
            'https://a.tile.openstreetmap.org/',
            'https://b.tile.openstreetmap.org/',
            'https://c.tile.openstreetmap.org/',
            // Overpass API – OSM-Datenabfragen (POI, Umgebungssuche)
            'https://overpass-api.de/',
            'https://lz4.overpass-api.de/',
            'https://z.overpass-api.de/',
            // Bright Sky – amtliche DWD-Wetterdaten (kostenlos, kein API-Key, Server in Deutschland)
            'https://api.brightsky.dev/',
            // Open-Meteo – weltweite Wettervorhersage (kostenlos, kein API-Key, Open-Source)
            'https://api.open-meteo.com/',
        ];

        $isAllowed = false;
        foreach ($allowedDomains as $domain) {
            if (str_starts_with($targetUrl, $domain)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            rex_response::setStatus(rex_response::HTTP_FORBIDDEN);
            rex_response::sendContent('URL not allowed by proxy.');
            exit;
        }

        $hash = md5($targetUrl);
        $sub1 = substr($hash, 0, 2);
        $sub2 = substr($hash, 2, 2);
        $cacheBase = rex_path::addonCache('vector_maps', "proxy/$sub1/$sub2/$hash");

        // Wildcard-Suche: Extension erst nach dem Fetch bekannt (OpenFreeMap hat keine Extensions in URLs)
        $cached = glob($cacheBase . '.*');
        if (is_array($cached) && !empty($cached) && file_exists($cached[0])) {
            $ext = pathinfo($cached[0], PATHINFO_EXTENSION);
            self::serveFile($cached[0], $ext);
            exit;
        }

        self::fetchAndCache($targetUrl, $cacheBase);
    }

    private static function fetchAndCache(string $url, string $cacheBase): void
    {
        // PHP dekodiert GET-Parameter automatisch – Leerzeichen und Sonderzeichen
        // müssen vor dem cURL-Aufruf re-enkodiert werden, sonst lehnt der Server ab.
        // Robuster Ansatz: Leerzeichen mit preg_replace_callback gezielt enkodieren,
        // ohne andere bereits korrekt enkodierte Sequenzen zu doppelt-enkodieren.
        // OFM nutzt Komma als Fontstack-Trennzeichen: /fonts/Font A,Font B/0-255.pbf
        // Komma ist gültiges Sub-Delimiter (RFC 3986) und darf im Pfad bleiben.
        $safeUrl = preg_replace_callback(
            '/ /',  // nur echte Leerzeichen (kommen durch PHP-Decodierung von %20)
            static fn () => '%20',
            $url
        );
        // Zusätzlich den Pfad-Teil mit parse_url normalisieren, falls vorhanden
        $parts = parse_url($safeUrl);
        if (is_array($parts) && isset($parts['path'])) {
            $encodedPath = implode('/', array_map(static function (string $s): string {
                // Nur noch verbliebene Nicht-ASCII und unsafe Zeichen enkodieren.
                // %XX bereits kodierte Sequenzen bleiben unberührt (rawurlencode würde sie doppelt kodieren).
                return preg_replace_callback('/[^A-Za-z0-9\-._~!$&\'()*+,;=:@%]/', static fn ($m) => rawurlencode($m[0]), $s) ?? $s;
            }, explode('/', $parts['path'])));
            $safeUrl = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '')
                . $encodedPath
                . (isset($parts['query']) ? '?' . $parts['query'] : '');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $safeUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // CURLOPT_ENCODING = '' lässt cURL gzip/brotli automatisch dekomprimieren
        // ohne das schickt CloudFlare komprimierte Daten, MapLibre kann sie nicht parsen
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 REDAXO/vector_maps');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $content = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $rawContentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($httpCode !== 200 || false === $content) {
            rex_response::setStatus(rex_response::HTTP_NOT_FOUND);
            rex_response::sendContent("Proxy upstream failed (HTTP $httpCode).");
            exit;
        }

        // Content-Type ohne Charset etc. extrahieren
        $contentType = strtolower(trim((string) strtok($rawContentType, ';')));

        // Extension aus Content-Type ableiten
        $ext = self::CT_TO_EXT[$contentType] ?? '';

        // Fallback: Extension aus URL-Pfad
        if ('' === $ext) {
            $urlExt = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
            if ('' !== $urlExt && isset(self::MIME_MAP[$urlExt])) {
                $ext = $urlExt;
            }
        }

        // Letzter Fallback
        if ('' === $ext) {
            $ext = 'bin';
        }

        $cacheFile = $cacheBase . '.' . $ext;
        rex_file::put($cacheFile, $content);

        self::serveFile($cacheFile, $ext);
    }

    private static function serveFile(string $filePath, string $ext): void
    {
        $contentType = self::MIME_MAP[$ext] ?? 'application/octet-stream';

        rex_response::cleanOutputBuffers();
        rex_response::sendCacheControl('public, max-age=604800');
        rex_response::setHeader('Access-Control-Allow-Origin', '*');
        rex_response::sendFile($filePath, $contentType);
        exit;
    }
}
