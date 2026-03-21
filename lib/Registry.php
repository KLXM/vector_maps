<?php

namespace KLXM\VectorMaps;

use rex_extension;

class Registry
{
    /** @var array<string, array{url: string, type: string, attribution: string}> */
    private static array $providers = [];

    public static function init(): void
    {
        // Core providers eintragen.
        self::$providers = [
            'openfreemap' => [
                'url' => 'https://tiles.openfreemap.org/planet',
                'type' => 'vector',
                'attribution' => '<a href="https://openfreemap.org/">OpenFreeMap</a> &copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>',
            ],
            'osm_raster' => [
                'url' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                'type' => 'raster',
                'attribution' => '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>',
            ]
        ];

        // Über Extension Point anpassbar / erweiterbar machen.
        self::$providers = rex_extension::registerPoint(new \rex_extension_point('VECTOR_MAPS_PROVIDERS', self::$providers));
    }

    public static function getProvider(string $key): ?array
    {
        if (empty(self::$providers)) {
            self::init();
        }

        return self::$providers[$key] ?? null;
    }

    public static function getAll(): array
    {
        if (empty(self::$providers)) {
            self::init();
        }

        return self::$providers;
    }
}
