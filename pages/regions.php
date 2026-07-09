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

$initialPayload = [
    'description' => $currentDescription,
    'regions' => is_array($editGroup) && isset($editGroup['payload']['regions']) && is_array($editGroup['payload']['regions'])
        ? $editGroup['payload']['regions']
        : [],
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

$formHtml = '';
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

$formHtml .= '</fieldset>';

$formHtml .= '<fieldset style="margin-top:20px">';
$formHtml .= '<legend>Regionen-Builder</legend>';
$formHtml .= '<div id="vm-region-group-builder" '
    . 'data-initial-payload="' . rex_escape($initialPayloadJson) . '" '
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
$formSection->setVar('title', '<span class="vm-panel-title--soft"><i class="rex-icon fa-sitemap"></i>Gruppen- und Regionen-Konfiguration</span>', false);
$formSection->setVar('body', $formHtml, false);

echo $formSection->parse('core/page/section.php');

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
    ]);

    $usage = '&lt;vectormap center="51.2,7.3" zoom="8" height="520" geojson="' . rex_escape($geojsonUrl) . '"&gt;&lt;/vectormap&gt;';

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
    $rows .= '<td><code style="font-size:11px">' . $usage . '</code></td>';
    $rows .= '<td style="white-space:nowrap">';
    $rows .= '<a class="btn btn-xs btn-default" href="' . rex_escape($editUrl) . '"><i class="rex-icon rex-icon-edit"></i> Bearbeiten</a> ';
    $rows .= '<a class="btn btn-xs btn-danger" href="' . rex_escape($deleteUrl) . '"><i class="rex-icon rex-icon-delete"></i> Löschen</a>';
    $rows .= '</td>';
    $rows .= '</tr>';
}

if ('' === $rows) {
    $rows = '<tr><td colspan="7" class="text-muted">Noch keine Gruppen vorhanden.</td></tr>';
}

$listHtml = '';
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
