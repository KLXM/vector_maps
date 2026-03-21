<?php

namespace KLXM\VectorMaps;

use rex_i18n;

class UI
{
    /**
     * Gibt die JS-Konfiguration für das Frontend als fertigen HTML-Script-Tag zurück.
     * Kann vom Entwickler im Template platziert werden, bevor das JS geladen wird.
     * 
     * @return string
     */
    public static function getJsConfig(): string
    {
        $i18n = [
            'picker_button' => rex_i18n::msg('vector_maps_picker_button'),
            'search_placeholder' => rex_i18n::msg('vector_maps_search_placeholder'),
            'close' => rex_i18n::msg('vector_maps_close'),
            'confirm' => rex_i18n::msg('vector_maps_confirm'),
        ];

        return '<script>window.vector_maps_i18n = ' . json_encode($i18n, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) . ';</script>';
    }
}
