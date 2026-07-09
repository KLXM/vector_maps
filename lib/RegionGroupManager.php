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
     * Erlaubt Hex (inkl. Alpha), rgb(a) und hsl(a). Leerer String bei ungültiger Eingabe.
     */
    private static function sanitizeCssColor(string $color, string $fallback = ''): string
    {
        $color = trim($color);

        if (1 === preg_match('/^#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color)) {
            return $color;
        }

        if (1 === preg_match('/^(rgb|rgba|hsl|hsla)\(\s*[\d.,%\s\/-]+\)$/i', $color)) {
            return $color;
        }

        return $fallback;
    }

    private static function clampOpacity(mixed $value, float $fallback): float
    {
        if (!is_numeric($value)) {
            return $fallback;
        }

        return min(1.0, max(0.0, (float) $value));
    }

    /**
     * @return array{enabled: bool, precision: int}
     */
    private static function normalizeOptimize(mixed $raw): array
    {
        $defaults = [
            'enabled' => false,
            'precision' => 6,
        ];

        if (!is_array($raw)) {
            return $defaults;
        }

        $precision = (int) ($raw['precision'] ?? $defaults['precision']);
        if ($precision < 4) {
            $precision = 4;
        }
        if ($precision > 7) {
            $precision = 7;
        }

        return [
            'enabled' => false !== ($raw['enabled'] ?? false),
            'precision' => $precision,
        ];
    }

    /**
     * @param array<int, array{0: float|int, 1: float|int}> $points
     * @return array<int, array{0: float, 1: float}>
     */
    private static function simplifyRing(array $points, float $epsilon): array
    {
        if (count($points) < 4) {
            return array_map(
                static fn (array $p): array => [(float) $p[0], (float) $p[1]],
                $points
            );
        }

        $closed = $points[0][0] === $points[count($points) - 1][0]
            && $points[0][1] === $points[count($points) - 1][1];

        $work = $closed ? array_slice($points, 0, -1) : $points;
        if (count($work) < 3) {
            return array_map(
                static fn (array $p): array => [(float) $p[0], (float) $p[1]],
                $points
            );
        }

        $keep = array_fill(0, count($work), false);
        $keep[0] = true;
        $keep[count($work) - 1] = true;

        $stack = [[0, count($work) - 1]];
        while ([] !== $stack) {
            [$start, $end] = array_pop($stack);
            $maxDist = 0.0;
            $index = -1;

            $ax = (float) $work[$start][0];
            $ay = (float) $work[$start][1];
            $bx = (float) $work[$end][0];
            $by = (float) $work[$end][1];

            $dx = $bx - $ax;
            $dy = $by - $ay;
            $den = ($dx * $dx) + ($dy * $dy);

            for ($i = $start + 1; $i < $end; ++$i) {
                $px = (float) $work[$i][0];
                $py = (float) $work[$i][1];

                if ($den > 0.0) {
                    $t = (($px - $ax) * $dx + ($py - $ay) * $dy) / $den;
                    $t = max(0.0, min(1.0, $t));
                    $projX = $ax + ($t * $dx);
                    $projY = $ay + ($t * $dy);
                } else {
                    $projX = $ax;
                    $projY = $ay;
                }

                $dist = sqrt((($px - $projX) ** 2) + (($py - $projY) ** 2));
                if ($dist > $maxDist) {
                    $maxDist = $dist;
                    $index = $i;
                }
            }

            if ($index >= 0 && $maxDist > $epsilon) {
                $keep[$index] = true;
                $stack[] = [$start, $index];
                $stack[] = [$index, $end];
            }
        }

        $out = [];
        foreach ($work as $i => $point) {
            if ($keep[$i]) {
                $out[] = [(float) $point[0], (float) $point[1]];
            }
        }

        if (count($out) < 3) {
            $out = array_map(
                static fn (array $p): array => [(float) $p[0], (float) $p[1]],
                $work
            );
        }

        if ($closed) {
            $out[] = $out[0];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $geometry
     * @return array<string, mixed>
     */
    private static function optimizeGeometry(array $geometry, int $precision): array
    {
        $type = (string) ($geometry['type'] ?? '');
        $coordinates = $geometry['coordinates'] ?? null;

        if (!is_array($coordinates) || !in_array($type, ['Polygon', 'MultiPolygon'], true)) {
            return $geometry;
        }

        $factor = 10 ** $precision;
        $epsilon = 1 / $factor;

        $roundPoint = static fn (array $point): array => [
            round((float) $point[0], $precision),
            round((float) $point[1], $precision),
        ];

        $optimizePolygon = static function (array $polygon) use ($roundPoint, $epsilon): array {
            $rings = [];
            foreach ($polygon as $ringIndex => $ring) {
                if (!is_array($ring)) {
                    continue;
                }

                $points = [];
                foreach ($ring as $point) {
                    if (is_array($point) && isset($point[0], $point[1])) {
                        $points[] = $roundPoint($point);
                    }
                }

                if (count($points) < 4) {
                    continue;
                }

                $points = self::simplifyRing($points, $epsilon);
                if (count($points) < 4) {
                    continue;
                }

                if ($ringIndex > 0) {
                    $outer = $rings[0] ?? [];
                    if ([] !== $outer) {
                        $outerArea = abs(self::ringArea($outer));
                        $innerArea = abs(self::ringArea($points));
                        if ($innerArea > ($outerArea * 0.98)) {
                            continue;
                        }
                    }
                }

                $rings[] = $points;
            }

            return $rings;
        };

        if ('Polygon' === $type) {
            $polygon = $optimizePolygon($coordinates);
            if ([] !== $polygon) {
                $geometry['coordinates'] = $polygon;
            }

            return $geometry;
        }

        $polygons = [];
        foreach ($coordinates as $polygonCoords) {
            if (!is_array($polygonCoords)) {
                continue;
            }

            $polygon = $optimizePolygon($polygonCoords);
            if ([] !== $polygon) {
                $polygons[] = $polygon;
            }
        }

        if ([] !== $polygons) {
            $geometry['coordinates'] = $polygons;
        }

        return $geometry;
    }

    /**
     * @return array{
     *   active: string,
     *   inactive: string,
     *   active_opacity: float,
     *   inactive_opacity: float,
    *   levels: array{country: string, state: string, county: string, city: string},
    *   levels_opacity: array{country: float, state: float, county: float, city: float}
     * }
     */
    private static function normalizeColors(mixed $raw): array
    {
        $defaults = [
            'active' => '#2f855a',
            'inactive' => '#9ca3af',
            'active_opacity' => 0.42,
            'inactive_opacity' => 0.15,
            'levels' => [
                'country' => '#0f766e',
                'state' => '#2563eb',
                'county' => '#f59e0b',
                'city' => '#2f855a',
            ],
            'levels_opacity' => [
                'country' => 0.42,
                'state' => 0.42,
                'county' => 0.42,
                'city' => 0.42,
            ],
        ];

        if (!is_array($raw)) {
            return $defaults;
        }

        $rawLevels = [];
        if (isset($raw['levels']) && is_array($raw['levels'])) {
            $rawLevels = $raw['levels'];
        }

        $rawLevelsOpacity = [];
        if (isset($raw['levels_opacity']) && is_array($raw['levels_opacity'])) {
            $rawLevelsOpacity = $raw['levels_opacity'];
        }

        return [
            'active' => self::sanitizeCssColor((string) ($raw['active'] ?? ''), $defaults['active']),
            'inactive' => self::sanitizeCssColor((string) ($raw['inactive'] ?? ''), $defaults['inactive']),
            'active_opacity' => self::clampOpacity($raw['active_opacity'] ?? null, $defaults['active_opacity']),
            'inactive_opacity' => self::clampOpacity($raw['inactive_opacity'] ?? null, $defaults['inactive_opacity']),
            'levels' => [
                'country' => self::sanitizeCssColor((string) ($rawLevels['country'] ?? ''), $defaults['levels']['country']),
                'state' => self::sanitizeCssColor((string) ($rawLevels['state'] ?? ''), $defaults['levels']['state']),
                'county' => self::sanitizeCssColor((string) ($rawLevels['county'] ?? ''), $defaults['levels']['county']),
                'city' => self::sanitizeCssColor((string) ($rawLevels['city'] ?? ''), $defaults['levels']['city']),
            ],
            'levels_opacity' => [
                'country' => self::clampOpacity($rawLevelsOpacity['country'] ?? null, $defaults['levels_opacity']['country']),
                'state' => self::clampOpacity($rawLevelsOpacity['state'] ?? null, $defaults['levels_opacity']['state']),
                'county' => self::clampOpacity($rawLevelsOpacity['county'] ?? null, $defaults['levels_opacity']['county']),
                'city' => self::clampOpacity($rawLevelsOpacity['city'] ?? null, $defaults['levels_opacity']['city']),
            ],
        ];
    }

    private static function normalizeBoundaryLevel(mixed $level): string
    {
        $normalized = strtolower(trim((string) $level));
        if (in_array($normalized, ['country', 'state', 'county', 'city'], true)) {
            return $normalized;
        }

        return 'city';
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
        $colors = self::normalizeColors($payload['colors'] ?? null);
        $optimize = self::normalizeOptimize($payload['optimize'] ?? null);
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

            $regionColor = self::sanitizeCssColor((string) ($regionRaw['color'] ?? ''));

            $regionUrl = trim((string) ($regionRaw['url'] ?? ''));
            if ('' !== $regionUrl && 1 !== preg_match('#^(https?://|/)#i', $regionUrl)) {
                $regionUrl = '';
            }

            $regionInfo = trim((string) ($regionRaw['info'] ?? ''));
            $regionLabel = array_key_exists('label', $regionRaw)
                ? trim((string) $regionRaw['label'])
                : $regionName;
            $regionCountrycodes = strtolower(trim((string) ($regionRaw['countrycodes'] ?? '')));
            if ('' !== $regionCountrycodes) {
                $regionCountrycodes = (string) preg_replace('/[^a-z,]+/', '', $regionCountrycodes);
            }

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

                if ($optimize['enabled']) {
                    $geometry = self::optimizeGeometry($geometry, $optimize['precision']);
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
                $cityLabel = array_key_exists('label', $cityRaw)
                    ? trim((string) $cityRaw['label'])
                    : $cityName;
                $cityBoundaryLevel = self::normalizeBoundaryLevel($cityRaw['boundary_level'] ?? null);

                $cityArea = (float) ($cityRaw['area_km2'] ?? 0);
                if ($cityArea < 0) {
                    $cityArea = 0;
                }

                $cities[] = [
                    'name' => $cityName,
                    'label' => $cityLabel,
                    'display_name' => trim((string) ($cityRaw['display_name'] ?? $cityName)),
                    'boundary_level' => $cityBoundaryLevel,
                    'osm_type' => strtoupper(trim((string) ($cityRaw['osm_type'] ?? ''))),
                    'osm_id' => (int) ($cityRaw['osm_id'] ?? 0),
                    'geometry' => $geometry,
                    'url' => $cityUrl,
                    'info' => $cityInfo,
                    'area_km2' => $cityArea,
                    'active' => false !== ($cityRaw['active'] ?? true),
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
                'label' => $regionLabel,
                'color' => $regionColor,
                'url' => $regionUrl,
                'info' => $regionInfo,
                'countrycodes' => $regionCountrycodes,
                'area_km2' => round($regionArea, 3),
                'cities' => $cities,
            ];
        }

        return [
            'description' => $description,
            'colors' => $colors,
            'optimize' => $optimize,
            'regions' => $regions,
            'city_count' => $cityCount,
            'area_total_km2' => round($areaTotal, 3),
        ];
    }

    /**
     * Sphärische Ringfläche in m² (Vorzeichen je Ringrichtung).
     *
     * @param array<int, array{0: float|int, 1: float|int}> $ring
     */
    private static function ringArea(array $ring): float
    {
        if (count($ring) < 3) {
            return 0.0;
        }

        $toRad = static fn (float $value): float => $value * M_PI / 180;
        $area = 0.0;

        $count = count($ring);
        for ($i = 0; $i < $count; ++$i) {
            $lowerIndex = $i;
            $middleIndex = ($i + 1) % $count;
            $upperIndex = ($i + 2) % $count;

            $p1 = $ring[$lowerIndex] ?? null;
            $p2 = $ring[$middleIndex] ?? null;
            $p3 = $ring[$upperIndex] ?? null;
            if (!is_array($p1) || !is_array($p2) || !is_array($p3)) {
                continue;
            }

            $lng1 = isset($p1[0]) ? (float) $p1[0] : 0.0;
            $lat2 = isset($p2[1]) ? (float) $p2[1] : 0.0;
            $lng3 = isset($p3[0]) ? (float) $p3[0] : 0.0;

            $area += ($toRad($lng3) - $toRad($lng1)) * sin($toRad($lat2));
        }

        return $area * 6378137.0 * 6378137.0 / 2.0;
    }

    /**
     * @param array<string, mixed> $geometry
     * @return array{0: float, 1: float}|null
     */
    private static function geometryCenter(array $geometry): ?array
    {
        $minLng = INF;
        $minLat = INF;
        $maxLng = -INF;
        $maxLat = -INF;

        $scan = static function ($coords) use (&$scan, &$minLng, &$minLat, &$maxLng, &$maxLat): void {
            if (!is_array($coords)) {
                return;
            }

            if (isset($coords[0], $coords[1]) && is_numeric($coords[0]) && is_numeric($coords[1])) {
                $lng = (float) $coords[0];
                $lat = (float) $coords[1];
                if (!is_finite($lng) || !is_finite($lat)) {
                    return;
                }

                if ($lng < $minLng) {
                    $minLng = $lng;
                }
                if ($lat < $minLat) {
                    $minLat = $lat;
                }
                if ($lng > $maxLng) {
                    $maxLng = $lng;
                }
                if ($lat > $maxLat) {
                    $maxLat = $lat;
                }
                return;
            }

            foreach ($coords as $child) {
                $scan($child);
            }
        };

        $scan($geometry['coordinates'] ?? null);

        if (!is_finite($minLng) || !is_finite($minLat) || !is_finite($maxLng) || !is_finite($maxLat)) {
            return null;
        }

        return [($minLng + $maxLng) / 2, ($minLat + $maxLat) / 2];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{type: string, features: array<int, array<string, mixed>>}
     */
    public static function buildGeoJson(string $groupKey, string $groupName, array $payload): array
    {
        $features = [];
        $colors = self::normalizeColors($payload['colors'] ?? null);
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
            $regionLabel = trim((string) ($region['label'] ?? $regionName));
            $regionColorOverride = self::sanitizeCssColor((string) ($region['color'] ?? ''));
            $resolveActiveFill = static function (string $boundaryLevel) use ($colors, $regionColorOverride): string {
                if ('' !== $regionColorOverride) {
                    return $regionColorOverride;
                }

                return (string) ($colors['levels'][$boundaryLevel] ?? $colors['active']);
            };
            $resolveActiveOpacity = static function (string $boundaryLevel) use ($colors): float {
                return (float) ($colors['levels_opacity'][$boundaryLevel] ?? $colors['active_opacity']);
            };
            $activeFill = '' !== $regionColorOverride ? $regionColorOverride : $colors['active'];
            $inactiveFill = $colors['inactive'];
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
                $cityLabel = trim((string) ($city['label'] ?? $cityName));
                $cityDisplayName = (string) ($city['display_name'] ?? $cityName);
                $cityUrl = (string) ($city['url'] ?? '');
                $cityInfo = (string) ($city['info'] ?? '');
                $cityArea = (float) ($city['area_km2'] ?? 0);
                $cityActive = false !== ($city['active'] ?? true);
                $cityBoundaryLevel = self::normalizeBoundaryLevel($city['boundary_level'] ?? null);
                $cityActiveFill = $resolveActiveFill($cityBoundaryLevel);
                $cityActiveOpacity = $resolveActiveOpacity($cityBoundaryLevel);

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
                        'boundary_level' => $cityBoundaryLevel,
                        'active' => $cityActive,
                        'fill' => $cityActive ? $cityActiveFill : $inactiveFill,
                        'fill_opacity' => $cityActive ? $cityActiveOpacity : $colors['inactive_opacity'],
                        'url' => $cityUrl,
                        'region_url' => $regionUrl,
                        'info' => $cityInfo,
                        'osm_type' => (string) ($city['osm_type'] ?? ''),
                        'osm_id' => (int) ($city['osm_id'] ?? 0),
                    ],
                ];

                if ('' !== $cityLabel) {
                    $cityCenter = self::geometryCenter($geometry);
                    if (null !== $cityCenter) {
                        $features[] = [
                            'type' => 'Feature',
                            'geometry' => [
                                'type' => 'Point',
                                'coordinates' => $cityCenter,
                            ],
                            'properties' => [
                                'level' => 'label',
                                'vm_label' => true,
                                'label_kind' => 'city',
                                'label' => $cityLabel,
                                'label_color' => '#111827',
                                'group_key' => $groupKey,
                                'group_name' => $groupName,
                                'region_key' => $regionKey,
                                'region_name' => $regionName,
                            ],
                        ];
                    }
                }

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
                    'fill' => $activeFill,
                    'fill_opacity' => round($colors['active_opacity'] * 0.45, 3),
                    'url' => $regionUrl,
                    'info' => $regionInfo,
                    'city_count' => count($cities),
                ],
            ];

            if ('' !== $regionLabel) {
                $regionCenter = self::geometryCenter([
                    'type' => 'MultiPolygon',
                    'coordinates' => $allPolygons,
                ]);

                if (null !== $regionCenter) {
                    $features[] = [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => $regionCenter,
                        ],
                        'properties' => [
                            'level' => 'label',
                            'vm_label' => true,
                            'label_kind' => 'region',
                            'label' => $regionLabel,
                            'label_bg' => '#111827',
                            'label_color' => '#ffffff',
                            'group_key' => $groupKey,
                            'group_name' => $groupName,
                            'region_key' => $regionKey,
                            'region_name' => $regionName,
                        ],
                    ];
                }
            }
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
