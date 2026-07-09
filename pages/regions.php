<?php

declare(strict_types=1);

use KLXM\VectorMaps\BackendHero;
use KLXM\VectorMaps\RegionGroupManager;

$addon = rex_addon::get('vector_maps');
$func = rex_request('func', 'string', '');
$token = rex_csrf_token::factory('vector_maps_regions');

if ('save' === $func && rex::getUser()?->isAdmin()) {
    if (!$token->isValid()) {
        echo rex_view::error($addon->i18n('vector_maps_regions_error'));
    } else {
        $groupKey = RegionGroupManager::sanitizeKey(rex_post('group_key', 'string', ''));
        $groupName = trim(rex_post('group_name', 'string', ''));
        $payloadRaw = trim(rex_post('payload_json', 'string', ''));

        if ('' === $groupKey) {
            echo rex_view::error($addon->i18n('vector_maps_regions_missing_key'));
        } else {
            $payloadDecoded = json_decode($payloadRaw, true);
            if (!is_array($payloadDecoded)) {
                $payloadDecoded = ['regions' => []];
            }

            if (RegionGroupManager::save($groupKey, $groupName, $payloadDecoded)) {
                echo rex_view::success($addon->i18n('vector_maps_regions_saved'));
            } else {
                echo rex_view::error($addon->i18n('vector_maps_regions_error'));
            }
        }
    }
}

if ('delete' === $func && rex::getUser()?->isAdmin()) {
    if (!$token->isValid()) {
        echo rex_view::error($addon->i18n('vector_maps_regions_error'));
    } else {
        $key = RegionGroupManager::sanitizeKey(rex_request('key', 'string', ''));
        if ('' !== $key && RegionGroupManager::delete($key)) {
            echo rex_view::success($addon->i18n('vector_maps_regions_deleted'));
        } else {
            echo rex_view::error($addon->i18n('vector_maps_regions_error'));
        }
    }
}

$editKey = RegionGroupManager::sanitizeKey(rex_request('edit', 'string', ''));
$editGroup = '' !== $editKey ? RegionGroupManager::get($editKey) : null;
$allGroups = RegionGroupManager::all();

$currentKey = is_array($editGroup) ? (string) ($editGroup['key'] ?? '') : '';
$currentName = is_array($editGroup) ? (string) ($editGroup['name'] ?? '') : '';
$currentDescription = is_array($editGroup) ? (string) ($editGroup['description'] ?? '') : '';

$initialColors = is_array($editGroup) && isset($editGroup['payload']['colors']) && is_array($editGroup['payload']['colors'])
    ? $editGroup['payload']['colors']
    : [
        'active' => '#2f855a',
        'inactive' => '#9ca3af',
        'active_opacity' => 0.42,
        'inactive_opacity' => 0.15,
    ];

$initialOptimize = is_array($editGroup) && isset($editGroup['payload']['optimize']) && is_array($editGroup['payload']['optimize'])
    ? $editGroup['payload']['optimize']
    : [
        'enabled' => false,
        'precision' => 6,
    ];

$initialPayload = [
    // Große Regions-Geometrien werden für bestehende Gruppen per AJAX nachgeladen,
    // damit die Seite sofort rendert und der initiale HTML-Payload klein bleibt.
    'description' => '' !== $currentKey ? '' : $currentDescription,
    'colors' => $initialColors,
    'optimize' => $initialOptimize,
    'regions' => [],
];

$initialPayloadJson = (string) json_encode($initialPayload, JSON_UNESCAPED_UNICODE);
$proxyApiUrl = rex_url::backendController(['rex_api_vector_maps_proxy' => 1]);
$regionsApiUrl = rex_url::backendController(['rex_api_vector_maps_regions' => 1]);

echo BackendHero::render(
    'regions',
    'Vector Maps · Regionen-Builder',
    'Gruppen, Regionen und Stadtgrenzen visuell verwalten',
    'Lege Gruppen an, füge pro Region mehrere Stadtgrenzen hinzu und prüfe das Ergebnis sofort in der Live-Vorschau.',
    ['Live-Preview', 'Mehrere Stadtgrenzen', 'GeoJSON-Export', 'API-Abruf'],
    [
        ['value' => (string) count($allGroups), 'label' => 'Gruppen'],
        ['value' => (string) array_sum(array_map(static fn (array $item): int => (int) ($item['region_count'] ?? 0), $allGroups)), 'label' => 'Regionen gesamt'],
        ['value' => (string) array_sum(array_map(static fn (array $item): int => (int) ($item['city_count'] ?? 0), $allGroups)), 'label' => 'Stadtflächen'],
    ]
);

$showForm = null !== $editGroup || 1 === rex_request('add', 'int', 0);

if ($showForm) {

$formHtml = '';
$formHtml .= '<p><a class="btn btn-default" href="' . rex_url::currentBackendPage() . '"><i class="rex-icon fa-arrow-left"></i> Zurück zur Übersicht</a></p>';
$formHtml .= '<form action="' . rex_url::currentBackendPage(array_merge(['func' => 'save'], $token->getUrlParams())) . '" method="post" id="vm-region-group-form">';
$formHtml .= '<fieldset>';
$formHtml .= '<legend>Gruppe</legend>';

$formHtml .= '<div class="rex-form-group form-group">';
$formHtml .= '<label class="control-label col-sm-3" for="vm-group-key">Gruppen-Schlüssel</label>';
$formHtml .= '<div class="col-sm-9">';
$formHtml .= '<input class="form-control" id="vm-group-key" name="group_key" type="text" value="' . rex_escape($currentKey) . '" placeholder="fussballverband-kreise" required>';
$formHtml .= '<p class="help-block">Nur a-z, 0-9, Bindestrich und Unterstrich. Wird für API und Wiederverwendung genutzt.</p>';
$formHtml .= '</div>';
$formHtml .= '</div>';

$formHtml .= '<div class="rex-form-group form-group">';
$formHtml .= '<label class="control-label col-sm-3" for="vm-group-name">Gruppen-Name</label>';
$formHtml .= '<div class="col-sm-9">';
$formHtml .= '<input class="form-control" id="vm-group-name" name="group_name" type="text" value="' . rex_escape($currentName) . '" placeholder="Fußballverband Kreise NRW" required>';
$formHtml .= '</div>';
$formHtml .= '</div>';

$formHtml .= '<div class="rex-form-group form-group">';
$formHtml .= '<label class="control-label col-sm-3" for="vm-group-description">Beschreibung</label>';
$formHtml .= '<div class="col-sm-9">';
$formHtml .= '<textarea class="form-control" id="vm-group-description" rows="3" placeholder="Optionaler Kontext für Redaktion und API"></textarea>';
$formHtml .= '</div>';
$formHtml .= '</div>';

$formHtml .= '<div class="rex-form-group form-group">';
$formHtml .= '<label class="control-label col-sm-3">Globale Farben</label>';
$formHtml .= '<div class="col-sm-9">';
$formHtml .= '<div class="row">';
$formHtml .= '<div class="col-sm-6" style="margin-bottom:8px">';
$formHtml .= '<label style="font-weight:normal">Aktive Flächen</label>';
$formHtml .= '<div style="display:flex;gap:6px;align-items:center">';
$formHtml .= '<input type="color" id="vm-color-active-picker" value="#2f855a" style="width:38px;height:32px;padding:1px;border:1px solid #ccc;border-radius:4px" title="Farbe wählen">';
$formHtml .= '<input type="text" class="form-control" id="vm-color-active" value="#2f855a" placeholder="#2f855a oder rgba(47,133,90,.8)">';
$formHtml .= '<input type="number" class="form-control" id="vm-opacity-active" min="0" max="100" step="5" value="42" style="width:80px" title="Deckkraft in %">';
$formHtml .= '<span class="text-muted">%</span>';
$formHtml .= '</div>';
$formHtml .= '</div>';
$formHtml .= '<div class="col-sm-6" style="margin-bottom:8px">';
$formHtml .= '<label style="font-weight:normal">Inaktive Flächen</label>';
$formHtml .= '<div style="display:flex;gap:6px;align-items:center">';
$formHtml .= '<input type="color" id="vm-color-inactive-picker" value="#9ca3af" style="width:38px;height:32px;padding:1px;border:1px solid #ccc;border-radius:4px" title="Farbe wählen">';
$formHtml .= '<input type="text" class="form-control" id="vm-color-inactive" value="#9ca3af" placeholder="#9ca3af oder rgba(156,163,175,.5)">';
$formHtml .= '<input type="number" class="form-control" id="vm-opacity-inactive" min="0" max="100" step="5" value="15" style="width:80px" title="Deckkraft in %">';
$formHtml .= '<span class="text-muted">%</span>';
$formHtml .= '</div>';
$formHtml .= '</div>';
$formHtml .= '</div>';
$formHtml .= '<p class="help-block" style="margin-bottom:0">Picker oder freie Eingabe (Hex, rgba, hsl – auch transparent). Deckkraft in Prozent. Regionen können die Farbe einzeln überschreiben.</p>';
$formHtml .= '</div>';
$formHtml .= '</div>';

$formHtml .= '<div class="rex-form-group form-group">';
$formHtml .= '<label class="control-label col-sm-3">JSON-Optimierung (optional)</label>';
$formHtml .= '<div class="col-sm-9">';
$formHtml .= '<div class="checkbox" style="margin-top:0">';
$formHtml .= '<label><input type="checkbox" id="vm-optimize-enabled"> Geometrie beim Speichern komprimieren</label>';
$formHtml .= '</div>';
$formHtml .= '<div class="row" style="max-width:380px">';
$formHtml .= '<div class="col-sm-6">';
$formHtml .= '<label for="vm-optimize-precision" style="font-weight:normal">Koordinaten-Präzision</label>';
$formHtml .= '<input type="number" class="form-control" id="vm-optimize-precision" min="4" max="7" step="1" value="6">';
$formHtml .= '</div>';
$formHtml .= '</div>';
$formHtml .= '<p class="help-block" style="margin-bottom:0">Empfehlung: 6. Reduziert Dateigröße spürbar (oft 20-50%), Grenzen bleiben in der Regel visuell stabil.</p>';
$formHtml .= '</div>';
$formHtml .= '</div>';

$formHtml .= '</fieldset>';

$formHtml .= '<fieldset style="margin-top:20px">';
$formHtml .= '<legend>Regionen-Builder</legend>';
$formHtml .= '<div id="vm-region-group-builder" '
    . 'data-initial-payload="' . rex_escape($initialPayloadJson) . '" '
    . 'data-initial-group-key="' . rex_escape($currentKey) . '" '
    . 'data-proxy-url="' . rex_escape($proxyApiUrl) . '" '
    . 'data-regions-api-url="' . rex_escape($regionsApiUrl) . '">';
$formHtml .= '<div class="row">';
$formHtml .= '<div class="col-md-7">';
$formHtml .= '<div id="vm-region-list"></div>';
$formHtml .= '<p><button type="button" class="btn btn-default" id="vm-add-region"><i class="rex-icon rex-icon-add-action"></i> Region hinzufügen</button></p>';
$formHtml .= '</div>';
$formHtml .= '<div class="col-md-5">';
$formHtml .= '<div class="panel panel-default">';
$formHtml .= '<div class="panel-heading"><strong>Live-Preview</strong></div>';
$formHtml .= '<div class="panel-body">';
$formHtml .= '<div id="vm-builder-map" style="height:380px;border-radius:6px;overflow:hidden;"></div>';
$formHtml .= '<div style="margin-top:12px">';
$formHtml .= '<span class="label label-default" id="vm-stat-regions">0 Regionen</span> ';
$formHtml .= '<span class="label label-default" id="vm-stat-cities">0 Stadtflächen</span> ';
$formHtml .= '<span class="label label-default" id="vm-stat-area">0 km²</span>';
$formHtml .= '</div>';
$formHtml .= '</div>';
$formHtml .= '</div>';
$formHtml .= '</div>';
$formHtml .= '</div>';
$formHtml .= '<textarea class="form-control" id="vm-generated-geojson" rows="12" style="margin-top:12px;font-family:monospace;font-size:12px" readonly></textarea>';
$formHtml .= '</div>';
$formHtml .= '</fieldset>';

$formHtml .= '<input type="hidden" name="payload_json" id="vm-group-payload" value="">';
$formHtml .= '<div class="rex-form-panel-footer">';
$formHtml .= '<button class="btn btn-primary" type="submit"><i class="rex-icon rex-icon-save"></i> Gruppe speichern</button>';
$formHtml .= '</div>';
$formHtml .= '</form>';

$formSection = new rex_fragment();
$formSection->setVar('class', 'edit', false);
$formSection->setVar('title', '<span class="vm-panel-title--soft"><i class="rex-icon fa-sitemap"></i>' . ('' !== $currentKey ? 'Gruppe bearbeiten: ' . rex_escape($currentName !== '' ? $currentName : $currentKey) : 'Neue Gruppe anlegen') . '</span>', false);
$formSection->setVar('body', $formHtml, false);

echo $formSection->parse('core/page/section.php');

} else {

$rows = '';
foreach ($allGroups as $group) {
    $key = (string) ($group['key'] ?? '');
    if ('' === $key) {
        continue;
    }

    $name = (string) ($group['name'] ?? $key);
    $regionCount = (int) ($group['region_count'] ?? 0);
    $cityCount = (int) ($group['city_count'] ?? 0);
    $area = (float) ($group['area_total_km2'] ?? 0);
    $updated = (string) ($group['updated_at'] ?? '');

    $geojsonUrl = rex_url::backendController([
        'rex_api_vector_maps_regions' => 1,
        'action' => 'geojson',
        'key' => $key,
    ], false);

    $usagePlain = '<vectormap center="51.2,7.3" zoom="8" height="520" geojson="' . $geojsonUrl . '"></vectormap>';

    $editUrl = rex_url::currentBackendPage(['edit' => $key]);
    $deleteUrl = rex_url::currentBackendPage(array_merge([
        'func' => 'delete',
        'key' => $key,
    ], $token->getUrlParams()));

    $rows .= '<tr>';
    $rows .= '<td><strong>' . rex_escape($name) . '</strong><br><code>' . rex_escape($key) . '</code></td>';
    $rows .= '<td>' . $regionCount . '</td>';
    $rows .= '<td>' . $cityCount . '</td>';
    $rows .= '<td>' . number_format($area, 2, ',', '.') . ' km²</td>';
    $rows .= '<td>' . rex_escape($updated) . '</td>';
    $rows .= '<td style="max-width:260px">';
    $rows .= '<div style="display:flex;gap:6px;align-items:flex-start">';
    $rows .= '<code style="font-size:11px;white-space:normal;word-break:break-all;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;flex:1" title="' . rex_escape($usagePlain) . '">' . rex_escape($usagePlain) . '</code>';
    $rows .= '<button type="button" class="btn btn-xs btn-default" data-vm-copy-text="' . rex_escape($usagePlain) . '" title="Einbindungscode kopieren"><i class="rex-icon fa-copy"></i></button>';
    $rows .= '</div>';
    $rows .= '</td>';
    $rows .= '<td style="white-space:nowrap">';
    $rows .= '<a class="btn btn-xs btn-default" href="' . $editUrl . '"><i class="rex-icon rex-icon-edit"></i> Bearbeiten</a> ';
    $rows .= '<a class="btn btn-xs btn-danger" href="' . $deleteUrl . '"><i class="rex-icon rex-icon-delete"></i> Löschen</a>';
    $rows .= '</td>';
    $rows .= '</tr>';
}

if ('' === $rows) {
    $rows = '<tr><td colspan="7" class="text-muted">Noch keine Gruppen vorhanden.</td></tr>';
}

$listHtml = '';
$listHtml .= '<p><a class="btn btn-primary" href="' . rex_url::currentBackendPage(['add' => 1]) . '"><i class="rex-icon rex-icon-add-action"></i> Neue Gruppe anlegen</a></p>';
$listHtml .= '<div class="table-responsive">';
$listHtml .= '<table class="table table-striped">';
$listHtml .= '<thead><tr><th>Gruppe</th><th>Regionen</th><th>Stadtflächen</th><th>Gesamtfläche</th><th>Aktualisiert</th><th>Einbindung</th><th>Aktionen</th></tr></thead>';
$listHtml .= '<tbody>' . $rows . '</tbody>';
$listHtml .= '</table>';
$listHtml .= '</div>';
$listHtml .= '<div class="alert alert-info" style="margin-top:16px;margin-bottom:0">';
$listHtml .= '<strong>Hinweis:</strong> Jede Stadtfläche kann einen eigenen Link und Info-Text haben. Zusätzlich kann eine Region als gesamte Fläche verlinkt werden.';
$listHtml .= '</div>';

$listSection = new rex_fragment();
$listSection->setVar('title', '<span class="vm-panel-title--soft"><i class="rex-icon rex-icon-layers"></i>Gespeicherte Gruppen</span>', false);
$listSection->setVar('body', $listHtml, false);

echo $listSection->parse('core/page/section.php');

}
