<?php

namespace KLXM\VectorMaps;

use rex;
use rex_addon;
use rex_be_controller;
use rex_extension;
use rex_path;
use rex_view;
use rex_request;

// API Intercept for the Vector Maps Proxy
if (rex_request('rex_api_vector_maps_proxy', 'int', 0) === 1) {
    Proxy::intercept();
}

// Theme serve endpoint (public, kein Login erforderlich – nur Farbdaten)
if (rex_request('rex_api_vector_maps_theme', 'string', '') !== '') {
    ThemeManager::serveTheme(rex_request('rex_api_vector_maps_theme', 'string', ''));
}

// Theme CRUD API (nur für eingeloggte Backend-User)
if (rex_request('rex_api_vm_theme_action', 'string', '') !== '') {
    if (!rex::getUser()) {
        \rex_response::cleanOutputBuffers();
        \rex_response::setStatus(\rex_response::HTTP_UNAUTHORIZED);
        \rex_response::sendJson(['error' => 'Unauthorized']);
        exit;
    }
    ThemeManager::handleApiRequest();
}

// Regionen-Gruppen API (abrufbare GeoJSON-Ausgabe)
if (rex_request('rex_api_vector_maps_regions', 'int', 0) === 1) {
    RegionGroupManager::serveApi();
}

if (rex_addon::get('builder')->isAvailable()) {
    require_once __DIR__ . '/lib/Fields/VectorMapPickerField.php';

    if (class_exists(\FriendsOfREDAXO\Builder\Fields\FieldRegistry::class)) {
        $fieldClass = 'KLXM\\VectorMaps\\Fields\\VectorMapPickerField';
        if (class_exists($fieldClass)) {
            /** @var \FriendsOfREDAXO\Builder\Fields\FieldInterface $field */
            $field = new $fieldClass();
            \FriendsOfREDAXO\Builder\Fields\FieldRegistry::register($field);
        }
    }

    rex_extension::register(
        'BUILDER_ELEMENT_PATHS',
        static function (\rex_extension_point $ep): array {
            $paths = (array) $ep->getSubject();
            $paths['vector_maps'] = rex_path::addon('vector_maps', 'elements');
            return $paths;
        },
        rex_extension::EARLY
    );
}

if (rex_addon::get('yform')->isAvailable()) {
    \rex_yform::addTemplatePath(__DIR__ . '/ytemplates');
}

if (rex::isBackend() && rex::getUser()) {
    // JS Translations für den Picker bereitstellen
    // Backend-UI-Sprache (z. B. 'de', 'en') für Demo-Karte und Picker
    $beLocale = \rex_i18n::getLocale(); // z. B. 'de_de'
    rex_view::setJsProperty('vector_maps_lang', substr($beLocale, 0, 2));

    rex_view::setJsProperty('vector_maps_i18n', [
        'picker_button' => \rex_i18n::msg('vector_maps_picker_button'),
        'search_placeholder' => \rex_i18n::msg('vector_maps_search_placeholder'),
        'close' => \rex_i18n::msg('vector_maps_close'),
        'confirm' => \rex_i18n::msg('vector_maps_confirm'),
        'theme' => \rex_i18n::msg('vector_maps_picker_theme'),
        'no_theme' => \rex_i18n::msg('vector_maps_picker_no_theme'),
        'theme_vector_only' => \rex_i18n::msg('vector_maps_picker_theme_vector_only'),
    ]);

    // Assets im Backend laden (für Picker etc.)
    $addon = rex_addon::get('vector_maps');
    // filemtime-Cache-Busting: Browser lädt neue Version sobald Datei geändert wird
    $vmVer = static function(string $rel) use ($addon): string {
        $path = rex_path::addonAssets('vector_maps', $rel);
        return $addon->getAssetsUrl($rel) . '?v=' . (file_exists($path) ? filemtime($path) : 0);
    };
    rex_view::addCssFile($vmVer('build/vector_maps_backend.css'));
    rex_view::addCssFile($vmVer('build/vectormaps.css'));
    // Zuerst MapLibre Core laden
    rex_view::addCssFile($vmVer('maplibre/maplibre-gl.css'));
    rex_view::addJsFile($vmVer('maplibre/maplibre-gl.js'));
    // Dann unsere Custom Scripts
    rex_view::addJsFile($vmVer('build/vectormaps_i18n.js'));
    rex_view::addJsFile($vmVer('build/vectormaps.js'));

    // Theme-Editor-Script nur auf der Themes-Seite laden
    if (rex_be_controller::getCurrentPage() === 'vector_maps/themes') {
        rex_view::addJsFile($vmVer('build/theme-editor.js'));
    }

    // Regionen-Builder nur auf der Regionen-Seite laden
    if (rex_be_controller::getCurrentPage() === 'vector_maps/regions') {
        rex_view::addJsFile($vmVer('build/regions-editor.js'));
    }
    
}

// Frontend: Assets für <vector-map> / <vectormap> Custom Element laden
// Kann in den Einstellungen per 'load_frontend' => 0 deaktiviert werden
if (!rex::isBackend()) {
    /** @var rex_addon $addon */
    $addon = rex_addon::get('vector_maps');
    if ((int) $addon->getConfig('load_frontend', 1) === 1) {
        // Im Frontend minifizierte Versionen bevorzugen (*.min.js / *.min.css)
        $vmFe = static function(string $rel) use ($addon): string {
            $minRel  = (string) preg_replace('/\.(js|css)$/', '.min.$1', $rel);
            $minPath = rex_path::addonAssets('vector_maps', $minRel);
            $useRel  = file_exists($minPath) ? $minRel : $rel;
            $path    = rex_path::addonAssets('vector_maps', $useRel);
            return $addon->getAssetsUrl($useRel) . '?v=' . (file_exists($path) ? filemtime($path) : 0);
        };

        \rex_extension::register('OUTPUT_FILTER', static function(\rex_extension_point $ep) use ($vmFe): void {
            $subject = $ep->getSubject();
            // Assets nur einbinden wenn eine Karte auf der Seite vorhanden ist
            if (!str_contains($subject, '<vector-map')
                && !str_contains($subject, '<vectormap')
                && !str_contains($subject, 'data-vector-picker="1"')
                && !str_contains($subject, "data-vector-picker='1'")) {
                return;
            }
            $css = '<link rel="stylesheet" href="' . $vmFe('maplibre/maplibre-gl.css') . "\">
"
                 . '<link rel="stylesheet" href="' . $vmFe('build/vectormaps.css') . "\">
";
            $js  = '<script defer src="' . $vmFe('maplibre/maplibre-gl.js') . "\"></script>
"
                 . '<script defer src="' . $vmFe('build/vectormaps.js') . "\"></script>
";
            $subject = str_replace('</head>', $css . '</head>', $subject);
            $subject = str_replace('</body>', $js . '</body>', $subject);
            $ep->setSubject($subject);
        });
    }
}

