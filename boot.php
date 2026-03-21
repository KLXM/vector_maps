<?php

namespace KLXM\VectorMaps;

use rex;
use rex_addon;
use rex_be_controller;
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
    ]);

    // Assets im Backend laden (für Picker etc.)
    $addon = rex_addon::get('vector_maps');
    // filemtime-Cache-Busting: Browser lädt neue Version sobald Datei geändert wird
    $vmVer = static function(string $rel) use ($addon): string {
        $path = rex_path::addonAssets('vector_maps', $rel);
        return $addon->getAssetsUrl($rel) . '?v=' . (file_exists($path) ? filemtime($path) : 0);
    };
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
    
    // YForm Plugin registrieren
    if (rex_addon::get('yform')->isAvailable()) {
        \rex_yform::addTemplatePath(__DIR__ . '/ytemplates');
    }
}

// Frontend: Assets für <vector-map> / <vectormap> Custom Element laden
// Kann in package.yml oder settings.php per 'load_frontend' => false deaktiviert werden
if (!rex::isBackend()) {
    /** @var rex_addon $addon */
    $addon = rex_addon::get('vector_maps');
    if ((bool) $addon->getConfig('load_frontend', true)) {
        // filemtime-Cache-Busting: Browser lädt neue Version sobald Datei geändert wird
        $vmFe = static function(string $rel) use ($addon): string {
            $path = rex_path::addonAssets('vector_maps', $rel);
            return $addon->getAssetsUrl($rel) . '?v=' . (file_exists($path) ? filemtime($path) : 0);
        };
        rex_view::addCssFile($vmFe('maplibre/maplibre-gl.css'));
        rex_view::addCssFile($vmFe('build/vectormaps.css'));
        rex_view::addJsFile($vmFe('maplibre/maplibre-gl.js'), ['defer' => true]);
        rex_view::addJsFile($vmFe('build/vectormaps.js'), ['defer' => true]);
    }
}

