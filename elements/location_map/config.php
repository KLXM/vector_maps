<?php

use FriendsOfREDAXO\Builder\Starter\StarterConfig;
use KLXM\VectorMaps\ThemeManager;

$config = StarterConfig::class;

$themeChoices = ThemeManager::getThemeChoices(true);
$mapStyleChoices = [
    'liberty' => 'Liberty',
    'bright' => 'Bright',
    'positron' => 'Positron',
    'satellite' => 'Satellite',
];

return [
    'label' => 'Vector Map',
    'icon' => 'fa fa-map-marker',
    'description' => 'Karte mit Marker, Picker, Theme-Auswahl, optionalem Infofenster und Routenplaner.',
    'version' => '1.2.0',
    'category' => 'media',
    'field_groups' => [
        'content_tab' => [
            'label' => 'Inhalt',
            'icon' => 'fa-map-o',
            'fields' => ['headline', 'location', 'popup_title', 'popup_text', 'info_html'],
        ],
        'map_tab' => [
            'label' => 'Karte',
            'icon' => 'fa-globe',
            'fields' => ['zoom', 'height', 'map_style', 'theme', 'locate', 'show_satellite', 'fullscreen', 'controls_cluster', 'controls_style'],
        ],
        'route_tab' => [
            'label' => 'Routing',
            'icon' => 'fa-road',
            'fields' => [
                'route_panel',
                'route_from',
                'route_to',
                'route_mode',
                'route_to_locked',
                'route_no_steps',
                'route_to_popup',
                'route_panel_layout',
                'route_panel_width',
                'route_panel_position',
                'route_panel_style',
            ],
        ],
        'section_tab' => [
            'label' => 'Sektion',
            'icon' => 'fa-columns',
            'fields' => $config::getOptionalSectionFieldNames(),
        ],
    ],
    'fields' => array_merge([
        'headline' => [
            'type' => 'text',
            'label' => 'Überschrift',
        ],
        'location' => [
            'type' => 'vector_map_picker',
            'label' => 'Koordinaten',
            'placeholder' => '52.520008,13.404954',
            'notice' => 'Picker öffnet Karte mit Adresssuche. Speicherformat: lat,lng',
            'map_style_field' => 'map_style',
            'theme_field' => 'theme',
        ],
        'popup_title' => [
            'type' => 'text',
            'label' => 'Popup-Titel',
        ],
        'popup_text' => [
            'type' => 'textarea',
            'label' => 'Popup-Text',
            'rows' => 3,
        ],
        'info_html' => [
            'type' => 'textarea',
            'label' => 'Infofenster (HTML)',
            'rows' => 4,
            'notice' => 'Optionales globales Infofenster innerhalb der Karte.',
        ],
        'zoom' => [
            'type' => 'text',
            'label' => 'Zoom',
            'default' => '14',
        ],
        'height' => [
            'type' => 'text',
            'label' => 'Höhe',
            'default' => '420',
            'notice' => 'Zahl (Pixel) oder CSS-Wert wie 520px.',
        ],
        'map_style' => [
            'type' => 'choice',
            'label' => 'Kartenstil',
            'choices' => $mapStyleChoices,
            'default' => 'liberty',
        ],
        'theme' => [
            'type' => 'choice',
            'label' => 'Theme',
            'choices' => $themeChoices,
            'default' => '',
        ],
        'locate' => [
            'type' => 'checkbox',
            'label' => 'Standort-Button anzeigen',
        ],
        'show_satellite' => [
            'type' => 'checkbox',
            'label' => 'Satelliten-Toggle anzeigen',
        ],
        'fullscreen' => [
            'type' => 'checkbox',
            'label' => 'Fullscreen-Button anzeigen',
        ],
        'route_panel' => [
            'type' => 'checkbox',
            'label' => 'Interaktiven Routenplaner anzeigen',
            'notice' => 'Blendet das Von/Nach-Panel direkt auf oder neben der Karte ein.',
        ],
        'route_from' => [
            'type' => 'text',
            'label' => 'Route von',
            'placeholder' => 'Berlin HBF oder 52.520008,13.404954',
            'notice' => 'Startpunkt als Adresse oder lat,lng.',
        ],
        'route_to' => [
            'type' => 'text',
            'label' => 'Route nach',
            'placeholder' => 'Leipzig Hauptbahnhof oder 51.339695,12.373075',
            'notice' => 'Zielpunkt als Adresse oder lat,lng.',
        ],
        'route_mode' => [
            'type' => 'choice',
            'label' => 'Routing-Profil',
            'choices' => [
                'driving' => 'Auto',
                'walking' => 'Zu Fuß',
                'cycling' => 'Fahrrad',
            ],
            'default' => 'driving',
        ],
        'route_to_locked' => [
            'type' => 'checkbox',
            'label' => 'Ziel im Panel fixieren',
        ],
        'route_no_steps' => [
            'type' => 'checkbox',
            'label' => 'Abbiegehinweise ausblenden',
        ],
        'route_to_popup' => [
            'type' => 'textarea',
            'label' => 'Ziel-Popup (HTML)',
            'rows' => 3,
            'notice' => 'Wird am Ziel-Marker verwendet, z. B. für Kontaktkarten mit festem Ziel.',
        ],
        'route_panel_layout' => [
            'type' => 'choice',
            'label' => 'Panel-Layout',
            'choices' => [
                'overlay' => 'Overlay in der Karte',
                'side-right' => 'Eigene Spalte rechts',
                'side-left' => 'Eigene Spalte links',
            ],
            'default' => 'overlay',
        ],
        'route_panel_width' => [
            'type' => 'text',
            'label' => 'Panel-Breite',
            'default' => '340',
            'notice' => 'Breite der Seiten-Spalte bei side-left/side-right in Pixeln.',
        ],
        'route_panel_position' => [
            'type' => 'choice',
            'label' => 'Panel-Position',
            'choices' => [
                'top-left' => 'Oben links',
                'top-right' => 'Oben rechts',
                'bottom-left' => 'Unten links',
                'bottom-right' => 'Unten rechts',
            ],
            'default' => 'top-left',
        ],
        'route_panel_style' => [
            'type' => 'choice',
            'label' => 'Panel-Stil',
            'choices' => [
                '' => 'Standard',
                'glass' => 'Glass',
                'contrast' => 'Contrast',
                'brand' => 'Brand',
            ],
            'default' => '',
        ],
        'controls_cluster' => [
            'type' => 'choice',
            'label' => 'Control-Cluster',
            'choices' => [
                'right' => 'Oben rechts (Default)',
                'left' => 'Oben links',
                'bottom-left' => 'Unten links',
                'bottom-right' => 'Unten rechts',
            ],
            'default' => 'right',
        ],
        'controls_style' => [
            'type' => 'choice',
            'label' => 'Control-Stil',
            'choices' => [
                '' => 'Standard',
                'soft' => 'Soft',
                'rail' => 'Rail',
                'minimal' => 'Minimal',
                'bold' => 'Bold',
            ],
            'default' => '',
        ],
    ], $config::getOptionalSectionFields()),
];
