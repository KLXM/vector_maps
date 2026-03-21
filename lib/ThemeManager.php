<?php

namespace KLXM\VectorMaps;

use rex_addon;
use rex_dir;
use rex_file;
use rex_request;
use rex_response;

/**
 * Verwaltet benutzerdefinierte Karten-Themes (Farbpaletten).
 * Themes werden als JSON-Dateien im addon-Datenverzeichnis gespeichert.
 */
class ThemeManager
{
    /** Alle erlaubten Farb-Schlüssel eines Themes */
    public const COLOR_KEYS = [
        'land', 'water', 'green', 'farmland',
        'road_major', 'road_minor', 'road_casing', 'rail',
        'building', 'outline',
        'label', 'label_halo', 'road_label',
    ];

    /**
     * Gibt das Themes-Verzeichnis zurück und legt es bei Bedarf an.
     */
    private static function getThemesDir(): string
    {
        $dir = rex_addon::get('vector_maps')->getDataPath('themes');
        rex_dir::create($dir);
        return $dir;
    }

    /**
     * Bereinigt einen Theme-Namen auf alphanumerische Zeichen, Bindestrich und Unterstrich.
     */
    public static function sanitizeName(string $name): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9_-]/i', '-', $name));
    }

    /**
     * Gibt alle gespeicherten Custom-Themes zurück.
     * @return array<string, array<string, mixed>>
     */
    public static function getCustomThemes(): array
    {
        $dir    = self::getThemesDir();
        $themes = [];
        foreach (glob($dir . '/*.json') ?: [] as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $raw  = rex_file::get($file);
            if ($raw === false || $raw === '') {
                continue;
            }
            $data = json_decode($raw, true);
            if (is_array($data)) {
                $themes[$name] = $data;
            }
        }
        return $themes;
    }

    /**
     * Liest ein einzelnes Theme aus dem Verzeichnis.
     * @return array<string, mixed>|null
     */
    public static function getTheme(string $name): ?array
    {
        $name = self::sanitizeName($name);
        if ($name === '') {
            return null;
        }
        $file = self::getThemesDir() . '/' . $name . '.json';
        $raw  = rex_file::get($file);
        if ($raw === false || $raw === '') {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Speichert ein Theme. Validiert Name und Farben.
     * Alle Farb-Schlüssel sind optional — es muss mindestens einer gesetzt sein.
     * Unterstützt die vorhandenen 8 Legacy-Schlüssel und alle 13 erweiterten Schlüssel.
     *
     * @param array<string, mixed> $colors
     */
    public static function saveTheme(string $name, array $colors): bool
    {
        $name = self::sanitizeName($name);
        if ($name === '') {
            return false;
        }

        // Nur bekannte Farb-Schlüssel zulassen; vorhandene Werte müssen gültige Hex-Farben sein
        $validated    = [];
        $hasAnyColor  = false;
        foreach (self::COLOR_KEYS as $key) {
            if (!isset($colors[$key]) || $colors[$key] === '') {
                continue; // Optionale Schlüssel dürfen fehlen
            }
            $value = (string) $colors[$key];
            // Nur CSS-Hex-Farben akzeptieren (#rgb, #rrggbb, #rrggbbaa)
            if (!preg_match('/^#[0-9a-fA-F]{3,8}$/', $value)) {
                return false;
            }
            $validated[$key] = $value;
            $hasAnyColor = true;
        }

        if (!$hasAnyColor) {
            return false;
        }

        // Halo-Stärke (numerisch, 0–6)
        if (isset($colors['halo_width']) && $colors['halo_width'] !== '') {
            $validated['halo_width'] = max(0.0, min(6.0, round((float) $colors['halo_width'], 1)));
        }

        // customize_outlines (Boolean; FormData sendet 'true'/'false' als String)
        if (isset($colors['customize_outlines'])) {
            $v = $colors['customize_outlines'];
            $validated['customize_outlines'] = ($v === true || $v === 1 || $v === '1' || $v === 'true');
        }

        $data = [
            'name'    => $name,
            'colors'  => $validated,
            'created' => date('Y-m-d H:i:s'),
        ];

        return false !== rex_file::put(
            self::getThemesDir() . '/' . $name . '.json',
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Löscht ein gespeichertes Theme.
     */
    public static function deleteTheme(string $name): bool
    {
        $name = self::sanitizeName($name);
        if ($name === '') {
            return false;
        }
        $file = self::getThemesDir() . '/' . $name . '.json';
        return rex_file::delete($file);
    }

    /**
     * Sendet ein Theme als JSON-Response (für Frontend-Fetch).
     * Gibt HTTP 404 zurück, wenn das Theme nicht gefunden wird.
     */
    public static function serveTheme(string $name): void
    {
        rex_response::cleanOutputBuffers();

        $theme = self::getTheme($name);
        if ($theme === null) {
            rex_response::setStatus(rex_response::HTTP_NOT_FOUND);
            rex_response::sendContent('Theme not found.', 'text/plain');
            exit;
        }

        rex_response::sendJson($theme);
        exit;
    }

    /**
     * Verarbeitet Theme-CRUD-API-Anfragen aus dem Backend.
     * Erlaubte Actions: save, delete, list
     */
    public static function handleApiRequest(): void
    {
        rex_response::cleanOutputBuffers();

        $action = rex_request('rex_api_vm_theme_action', 'string', '');

        switch ($action) {
            case 'save':
                $name   = rex_request('vm_theme_name', 'string', '');
                /** @var array<string, string> $colors */
                $colors = rex_request('vm_theme_colors', 'array', []);
                // Sicherstellen dass Colors ein flaches String-Array sind
                $safeColors = [];
                foreach ($colors as $k => $v) {
                    if (is_string($k) && is_string($v)) {
                        $safeColors[$k] = $v;
                    }
                }
                $ok = self::saveTheme($name, $safeColors);
                rex_response::sendJson(['success' => $ok, 'name' => self::sanitizeName($name)]);
                break;

            case 'delete':
                $name = rex_request('vm_theme_name', 'string', '');
                $ok   = self::deleteTheme($name);
                rex_response::sendJson(['success' => $ok]);
                break;

            case 'list':
                rex_response::sendJson(self::getCustomThemes());
                break;

            case 'import':
                $json         = rex_request('vm_theme_json', 'string', '');
                $nameOverride = rex_request('vm_theme_name', 'string', '');
                if ($json === '') {
                    rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
                    rex_response::sendJson(['error' => 'Keine Daten empfangen']);
                    exit;
                }
                $imported = json_decode($json, true);
                if (!is_array($imported) || !isset($imported['colors']) || !is_array($imported['colors'])) {
                    rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
                    rex_response::sendJson(['error' => 'Ungültiges Theme-Format (colors-Objekt fehlt)']);
                    exit;
                }
                $importName = $nameOverride !== '' ? $nameOverride : ($imported['name'] ?? '');
                $ok = self::saveTheme($importName, $imported['colors']);
                rex_response::sendJson(['success' => $ok, 'name' => self::sanitizeName($importName)]);
                break;

            default:
                rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
                rex_response::sendJson(['error' => 'Unknown action']);
        }
        exit;
    }
}
