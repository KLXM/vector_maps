<?php

declare(strict_types=1);

namespace KLXM\VectorMaps;

use rex_addon;
use rex_url;

final class BackendHero
{
    /**
     * @param array<int, string> $chips
     * @param array<int, array{value:string, label:string}> $stats
     * @param array<int, array{url:string, label:string, icon?:string}> $links
     * @param array<int, array{url:string, label:string, icon:string, active:bool}> $navigation
     */
    public static function render(
        string $current,
        string $kicker,
        string $title,
        string $lead,
        array $chips = [],
        array $stats = [],
        array $links = [],
        array $navigation = [],
        bool $compact = false
    ): string {
        $heroClass = 'vm-admin-hero';
        if ($compact) {
            $heroClass .= ' vm-admin-hero--compact';
        }

        $html = '<section class="' . $heroClass . '">';
        $html .= '<div class="vm-admin-hero__mesh" aria-hidden="true">';
        $html .= '<div class="vm-admin-hero__route-map">';
        $html .= '<svg class="vm-admin-hero__route-svg" viewBox="0 0 220 132" xmlns="http://www.w3.org/2000/svg">';
        $html .= '<defs>';
        $html .= '<path id="vm-route-a" d="M16 102 C 58 24, 124 22, 196 68" />';
        $html .= '<path id="vm-route-b" d="M40 114 C 96 120, 132 84, 184 28" />';
        $html .= '</defs>';
        $html .= '<path class="vm-admin-hero__route-path vm-admin-hero__route-path--a" d="M16 102 C 58 24, 124 22, 196 68" />';
        $html .= '<path class="vm-admin-hero__route-path vm-admin-hero__route-path--b" d="M40 114 C 96 120, 132 84, 184 28" />';
        $html .= '<circle class="vm-admin-hero__route-node vm-admin-hero__route-node--start" cx="16" cy="102" r="4" />';
        $html .= '<circle class="vm-admin-hero__route-node vm-admin-hero__route-node--end" cx="196" cy="68" r="4" />';
        $html .= '<circle class="vm-admin-hero__route-node vm-admin-hero__route-node--start" cx="40" cy="114" r="3.5" />';
        $html .= '<circle class="vm-admin-hero__route-node vm-admin-hero__route-node--end" cx="184" cy="28" r="3.5" />';
        $html .= '<g class="vm-admin-hero__route-traveler vm-admin-hero__route-traveler--a">';
        $html .= '<circle cx="0" cy="0" r="3.5"></circle>';
        $html .= '<animateMotion dur="8.4s" repeatCount="indefinite" rotate="auto"><mpath href="#vm-route-a" /></animateMotion>';
        $html .= '</g>';
        $html .= '<g class="vm-admin-hero__route-traveler vm-admin-hero__route-traveler--b">';
        $html .= '<path d="M-1 -3 L5 0 L-1 3 z"></path>';
        $html .= '<animateMotion dur="11.2s" repeatCount="indefinite" rotate="auto"><mpath href="#vm-route-b" /></animateMotion>';
        $html .= '</g>';
        $html .= '<text class="vm-admin-hero__route-label vm-admin-hero__route-label--a" x="8" y="96">A</text>';
        $html .= '<text class="vm-admin-hero__route-label vm-admin-hero__route-label--b" x="202" y="63">B</text>';
        $html .= '</svg>';
        $html .= '</div>';
        $html .= '<span class="vm-admin-hero__shape vm-admin-hero__shape--a"></span>';
        $html .= '<span class="vm-admin-hero__shape vm-admin-hero__shape--b"></span>';
        $html .= '<span class="vm-admin-hero__shape vm-admin-hero__shape--c"></span>';
        $html .= '<span class="vm-admin-hero__shape vm-admin-hero__shape--d"></span>';
        $html .= '</div>';
        $html .= '<div class="vm-admin-hero__inner">';
        $html .= '<div class="vm-admin-hero__main">';
        $html .= '<div class="vm-admin-hero__brand">';
        $html .= '<span class="vm-admin-hero__logo-shell" aria-hidden="true">';
        $html .= '<span class="vector-maps-icon-logo vm-admin-hero__logo"></span>';
        $html .= '</span>';
        $html .= '<div class="vm-admin-hero__copy">';
        $html .= '<p class="vm-admin-hero__kicker">' . rex_escape($kicker) . '</p>';
        $html .= '<h1 class="vm-admin-hero__title">' . rex_escape($title) . '</h1>';
        $html .= '<p class="vm-admin-hero__lead">' . rex_escape($lead) . '</p>';
        $html .= '</div>';
        $html .= '</div>';

        if ($chips !== []) {
            $html .= '<div class="vm-admin-hero__chips">';
            foreach ($chips as $chip) {
                $html .= '<span class="vm-admin-chip">' . rex_escape($chip) . '</span>';
            }
            $html .= '</div>';
        }

        if ($links !== []) {
            $html .= '<div class="vm-admin-hero__links">';
            foreach ($links as $link) {
                $icon = isset($link['icon']) && $link['icon'] !== ''
                    ? '<i class="rex-icon ' . rex_escape($link['icon']) . '"></i>'
                    : '';
                $html .= '<a class="vm-admin-linkcard" href="' . rex_escape($link['url']) . '">'
                    . $icon
                    . '<span>' . rex_escape($link['label']) . '</span>'
                    . '</a>';
            }
            $html .= '</div>';
        }

        if ($navigation !== []) {
            $html .= '<nav class="vm-admin-hero__nav" aria-label="Vector Maps Navigation">';
            foreach ($navigation as $item) {
                $classes = 'vm-admin-nav__item';
                if ($item['active']) {
                    $classes .= ' is-active';
                }
                $html .= '<a class="' . $classes . '" href="' . rex_escape($item['url']) . '">';
                $html .= '<i class="rex-icon ' . rex_escape($item['icon']) . '"></i>';
                $html .= '<span>' . rex_escape($item['label']) . '</span>';
                $html .= '</a>';
            }
            $html .= '</nav>';
        }
        $html .= '</div>';

        if ($stats !== []) {
            $html .= '<div class="vm-admin-hero__stats">';
            foreach ($stats as $stat) {
                $html .= '<div class="vm-admin-stat">';
                $html .= '<strong>' . rex_escape($stat['value']) . '</strong>';
                $html .= '<span>' . rex_escape($stat['label']) . '</span>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /**
     * @param array<int, string> $chips
     * @param array<int, array{value:string, label:string}> $stats
     */
    public static function renderCompact(
        string $current,
        string $kicker,
        string $title,
        string $lead,
        array $chips = [],
        array $stats = []
    ): string {
        return self::render($current, $kicker, $title, $lead, $chips, $stats, [], [], true);
    }

    /**
     * @return array<int, array{url:string, label:string, icon:string, active:bool}>
     */
    public static function demoNavigation(string $current): array
    {
        $addon = rex_addon::get('vector_maps');

        return [
            [
                'url' => rex_url::backendPage('vector_maps/demo/overview'),
                'label' => $addon->i18n('demo_overview'),
                'icon' => 'fa-compass',
                'active' => $current === 'overview',
            ],
            [
                'url' => rex_url::backendPage('vector_maps/demo/geocoding'),
                'label' => $addon->i18n('demo_geocoding'),
                'icon' => 'fa-crosshairs',
                'active' => $current === 'geocoding',
            ],
            [
                'url' => rex_url::backendPage('vector_maps/demo/basic'),
                'label' => $addon->i18n('demo_basic'),
                'icon' => 'fa-road',
                'active' => $current === 'basic',
            ],
            [
                'url' => rex_url::backendPage('vector_maps/demo/nearby'),
                'label' => $addon->i18n('demo_nearby'),
                'icon' => 'fa-map-marker',
                'active' => $current === 'nearby',
            ],
            [
                'url' => rex_url::backendPage('vector_maps/demo/advanced'),
                'label' => $addon->i18n('demo_advanced'),
                'icon' => 'fa-magic',
                'active' => $current === 'advanced',
            ],
            [
                'url' => rex_url::backendPage('vector_maps/demo/skins'),
                'label' => $addon->i18n('demo_skins'),
                'icon' => 'fa-paint-brush',
                'active' => $current === 'skins',
            ],
        ];
    }

    /**
     * @return array<int, array{url:string, label:string, icon:string, active:bool}>
     */
    private static function getPrimaryNavigation(string $current): array
    {
        $addon = rex_addon::get('vector_maps');

        return [
            [
                'url' => rex_url::backendPage('vector_maps/demo/overview'),
                'label' => $addon->i18n('demo_overview'),
                'icon' => 'fa-compass',
                'active' => $current === 'overview',
            ],
            [
                'url' => rex_url::backendPage('vector_maps/demo/geocoding'),
                'label' => $addon->i18n('demo_geocoding'),
                'icon' => 'fa-crosshairs',
                'active' => $current === 'geocoding',
            ],
            [
                'url' => rex_url::backendPage('vector_maps/themes'),
                'label' => $addon->i18n('themes'),
                'icon' => 'fa-tint',
                'active' => $current === 'themes',
            ],
            [
                'url' => rex_url::backendPage('vector_maps/settings'),
                'label' => $addon->i18n('settings'),
                'icon' => 'fa-sliders',
                'active' => $current === 'settings',
            ],
        ];
    }
}
