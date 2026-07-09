<?php

declare(strict_types=1);

namespace KLXM\VectorMaps;

use rex_addon;
use rex_dir;
use rex_file;
use rex_request;
use rex_response;

final class RegionGroupManager
{
    private static function getDir(): string
    {
        $dir = rex_addon::get('vector_maps')->getDataPath('region_groups');
        rex_dir::create($dir);

        return $dir;
    }

    public static function sanitizeKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = (string) preg_replace('/[^a-z0-9_-]+/', '-', $key);

        return trim($key, '-');
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $key): ?array
    {
        $key = self::sanitizeKey($key);
        if ('' === $key) {
            return null;
        }

        $file = self::getDir() . '/' . $key . '.json';
        $raw = rex_file::get($file);
        if (!is_string($raw) || '' === $raw) {
            return null;
        }

        $data = json_decode($raw, true);

        return is_array($data) ? $data : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        $items = [];

        foreach (glob(self::getDir() . '/*.json') ?: [] as $file) {
            $raw = rex_file::get($file);
            if (!is_string($raw) || '' === $raw) {
                continue;
            }

            $data = json_decode($raw, true);
            if (!is_array($data)) {
                continue;
            }

            $items[] = $data;
        }

        usort(
            $items,
            static fn (array $a, array $b): int => strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''))
        );

        return $items;
    }

    public static function delete(string $key): bool
    {
        $key = self::sanitizeKey($key);
        if ('' === $key) {
            return false;
        }

        return rex_file::delete(self::getDir() . '/' . $key . '.json');
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function save(string $key, string $name, array $payload): bool
    {
        $key = self::sanitizeKey($key);
        if ('' === $key) {
            return false;
        }

        $name = trim($name);
        if ('' === $name) {
            $name = $key;
        }

        $normalizedPayload = self::normalizePayload($payload);
        $geojson = self::buildGeoJson($key, $name, $normalizedPayload);

        $payloadToStore = [
            'key' => $key,
            'name' => $name,
            'description' => (string) ($normalizedPayload['description'] ?? ''),
            'region_count' => count($normalizedPayload['regions']),
            'city_count' => (int) ($normalizedPayload['city_count'] ?? 0),
            'area_total_km2' => (float) ($normalizedPayload['area_total_km2'] ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
            'payload' => $normalizedPayload,
            'geojson' => $geojson,
        ];

        $json = json_encode($payloadToStore, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return is_string($json)
            && false !== rex_file::put(self::getDir() . '/' . $key . '.json', $json);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function normalizePayload(array $payload): array
    {
        $description = trim((string) ($payload['description'] ?? ''));
        $regionsRaw = $payload['regions'] ?? [];

        $regions = [];
        $regionIndex = 0;
        $cityCount = 0;
        $areaTotal = 0.0;

        if (!is_array($regionsRaw)) {
            $regionsRaw = [];
        }

        foreach ($regionsRaw as $regionRaw) {
            if (!is_array($regionRaw)) {
                continue;
            }

            ++$regionIndex;

            $regionKey = self::sanitizeKey((string) ($regionRaw['key'] ?? 'region-' . $regionIndex));
            if ('' === $regionKey) {
                $regionKey = 'region-' . $regionIndex;
            }

            $regionName = trim((string) ($regionRaw['name'] ?? $regionKey));
            if ('' === $regionName) {
                $regionName = $regionKey;
            }

            $regionColor = trim((string) ($regionRaw['color'] ?? '#2f855a'));
            if (1 !== preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $regionColor)) {
                $regionColor = '#2f855a';
            }

            $regionUrl = trim((string) ($regionRaw['url'] ?? ''));
            if ('' !== $regionUrl && 1 !== preg_match('#^(https?://|/)#i', $regionUrl)) {
                $regionUrl = '';
            }

            $regionInfo = trim((string) ($regionRaw['info'] ?? ''));

            $citiesRaw = $regionRaw['cities'] ?? [];
            if (!is_array($citiesRaw)) {
                $citiesRaw = [];
            }

            $cities = [];
            $regionArea = 0.0;

            foreach ($citiesRaw as $cityRaw) {
                if (!is_array($cityRaw)) {
                    continue;
                }

                $geometry = $cityRaw['geometry'] ?? null;
                if (!is_array($geometry)) {
                    continue;
                }

                $geometryType = (string) ($geometry['type'] ?? '');
                if (!in_array($geometryType, ['Polygon', 'MultiPolygon'], true)) {
                    continue;
                }

                $cityName = trim((string) ($cityRaw['name'] ?? ''));
                if ('' === $cityName) {
                    $cityName = 'Unbekannter Ort';
                }

                $cityUrl = trim((string) ($cityRaw['url'] ?? ''));
                if ('' !== $cityUrl && 1 !== preg_match('#^(https?://|/)#i', $cityUrl)) {
                    $cityUrl = '';
                }

                $cityInfo = trim((string) ($cityRaw['info'] ?? ''));

                $cityArea = (float) ($cityRaw['area_km2'] ?? 0);
                if ($cityArea < 0) {
                    $cityArea = 0;
                }

                $cities[] = [
                    'name' => $cityName,
                    'display_name' => trim((string) ($cityRaw['display_name'] ?? $cityName)),
                    'osm_type' => strtoupper(trim((string) ($cityRaw['osm_type'] ?? ''))),
                    'osm_id' => (int) ($cityRaw['osm_id'] ?? 0),
                    'geometry' => $geometry,
                    'url' => $cityUrl,
                    'info' => $cityInfo,
                    'area_km2' => $cityArea,
                ];

                ++$cityCount;
                $regionArea += $cityArea;
                $areaTotal += $cityArea;
            }

            if ([] === $cities) {
                continue;
            }

            $regions[] = [
                'key' => $regionKey,
                'name' => $regionName,
                'color' => $regionColor,
                'url' => $regionUrl,
                'info' => $regionInfo,
                'area_km2' => round($regionArea, 3),
                'cities' => $cities,
            ];
        }

        return [
            'description' => $description,
            'regions' => $regions,
            'city_count' => $cityCount,
            'area_total_km2' => round($areaTotal, 3),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{type: string, features: array<int, array<string, mixed>>}
     */
    public static function buildGeoJson(string $groupKey, string $groupName, array $payload): array
    {
        $features = [];
        $regions = $payload['regions'] ?? [];

        if (!is_array($regions)) {
            $regions = [];
        }

        foreach ($regions as $region) {
            if (!is_array($region)) {
                continue;
            }

            $regionKey = (string) ($region['key'] ?? '');
            $regionName = (string) ($region['name'] ?? $regionKey);
            $regionColor = (string) ($region['color'] ?? '#2f855a');
            $regionUrl = (string) ($region['url'] ?? '');
            $regionInfo = (string) ($region['info'] ?? '');
            $regionArea = (float) ($region['area_km2'] ?? 0);

            $cities = $region['cities'] ?? [];
            if (!is_array($cities) || [] === $cities) {
                continue;
            }

            $allPolygons = [];

            foreach ($cities as $city) {
                if (!is_array($city) || !isset($city['geometry']) || !is_array($city['geometry'])) {
                    continue;
                }

                $geometry = $city['geometry'];
                $cityName = (string) ($city['name'] ?? 'Ort');
                $cityDisplayName = (string) ($city['display_name'] ?? $cityName);
                $cityUrl = (string) ($city['url'] ?? '');
                $cityInfo = (string) ($city['info'] ?? '');
                $cityArea = (float) ($city['area_km2'] ?? 0);

                $features[] = [
                    'type' => 'Feature',
                    'geometry' => $geometry,
                    'properties' => [
                        'level' => 'city',
                        'group_key' => $groupKey,
                        'group_name' => $groupName,
                        'region_key' => $regionKey,
                        'region_name' => $regionName,
                        'name' => $cityName,
                        'display_name' => $cityDisplayName,
                        'fill' => $regionColor,
                        'url' => $cityUrl,
                        'region_url' => $regionUrl,
                        'info' => $cityInfo,
                        'area_km2' => $cityArea,
                        'osm_type' => (string) ($city['osm_type'] ?? ''),
                        'osm_id' => (int) ($city['osm_id'] ?? 0),
                    ],
                ];

                if (($geometry['type'] ?? '') === 'Polygon' && isset($geometry['coordinates']) && is_array($geometry['coordinates'])) {
                    $allPolygons[] = $geometry['coordinates'];
                }

                if (($geometry['type'] ?? '') === 'MultiPolygon' && isset($geometry['coordinates']) && is_array($geometry['coordinates'])) {
                    foreach ($geometry['coordinates'] as $polygon) {
                        if (is_array($polygon)) {
                            $allPolygons[] = $polygon;
                        }
                    }
                }
            }

            if ([] === $allPolygons) {
                continue;
            }

            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'MultiPolygon',
                    'coordinates' => $allPolygons,
                ],
                'properties' => [
                    'level' => 'region',
                    'group_key' => $groupKey,
                    'group_name' => $groupName,
                    'region_key' => $regionKey,
                    'region_name' => $regionName,
                    'name' => $regionName,
                    'fill' => $regionColor,
                    'url' => $regionUrl,
                    'info' => $regionInfo,
                    'area_km2' => $regionArea,
                    'city_count' => count($cities),
                ],
            ];
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    public static function serveApi(): void
    {
        rex_response::cleanOutputBuffers();

        $action = rex_request('action', 'string', 'get');
        $key = self::sanitizeKey(rex_request('key', 'string', ''));

        if ('' === $key) {
            rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
            rex_response::sendJson(['error' => 'Missing key']);
            exit;
        }

        $item = self::get($key);
        if (!is_array($item)) {
            rex_response::setStatus(rex_response::HTTP_NOT_FOUND);
            rex_response::sendJson(['error' => 'Group not found']);
            exit;
        }

        if ('geojson' === $action) {
            rex_response::setHeader('Content-Type', 'application/json; charset=utf-8');
            rex_response::sendContent((string) json_encode($item['geojson'] ?? ['type' => 'FeatureCollection', 'features' => []], JSON_UNESCAPED_UNICODE));
            exit;
        }

        if ('get' !== $action) {
            rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
            rex_response::sendJson(['error' => 'Unknown action']);
            exit;
        }

        rex_response::sendJson([
            'key' => (string) ($item['key'] ?? $key),
            'name' => (string) ($item['name'] ?? $key),
            'description' => (string) ($item['description'] ?? ''),
            'region_count' => (int) ($item['region_count'] ?? 0),
            'city_count' => (int) ($item['city_count'] ?? 0),
            'area_total_km2' => (float) ($item['area_total_km2'] ?? 0),
            'updated_at' => (string) ($item['updated_at'] ?? ''),
            'payload' => $item['payload'] ?? ['regions' => []],
            'geojson' => $item['geojson'] ?? ['type' => 'FeatureCollection', 'features' => []],
        ]);
        exit;
    }
}
