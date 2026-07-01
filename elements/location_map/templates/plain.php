<?php

use FriendsOfREDAXO\Builder\Starter\StarterConfig;

$headline = (string) ($elementData['headline'] ?? '');
$location = trim((string) ($elementData['location'] ?? ''));
$popupTitle = trim((string) ($elementData['popup_title'] ?? ''));
$popupText = trim((string) ($elementData['popup_text'] ?? ''));
$infoHtml = trim((string) ($elementData['info_html'] ?? ''));
$zoom = trim((string) ($elementData['zoom'] ?? '14'));
$height = trim((string) ($elementData['height'] ?? '420'));
$mapStyle = trim((string) ($elementData['map_style'] ?? 'liberty'));
$theme = trim((string) ($elementData['theme'] ?? ''));
$controlsCluster = trim((string) ($elementData['controls_cluster'] ?? 'right'));
$controlsStyle = trim((string) ($elementData['controls_style'] ?? ''));
$routeFrom = trim((string) ($elementData['route_from'] ?? ''));
$routeTo = trim((string) ($elementData['route_to'] ?? ''));
$routeMode = trim((string) ($elementData['route_mode'] ?? 'driving'));
$routeToPopup = trim((string) ($elementData['route_to_popup'] ?? ''));
$routePanelLayout = trim((string) ($elementData['route_panel_layout'] ?? 'overlay'));
$routePanelWidth = trim((string) ($elementData['route_panel_width'] ?? '340'));
$routePanelPosition = trim((string) ($elementData['route_panel_position'] ?? 'top-left'));
$routePanelStyle = trim((string) ($elementData['route_panel_style'] ?? ''));

$locate = !empty($elementData['locate']);
$showSatellite = !empty($elementData['show_satellite']);
$fullscreen = !empty($elementData['fullscreen']);
$routePanel = !empty($elementData['route_panel']);
$routeToLocked = !empty($elementData['route_to_locked']);
$routeNoSteps = !empty($elementData['route_no_steps']);

$sectionBg = (string) ($elementData['section_bg'] ?? '');
$sectionPadding = (string) ($elementData['section_padding'] ?? '');
$containerWidth = (string) ($elementData['container_width'] ?? 'uk-container');
$sectionLight = !empty($elementData['section_light']);
$enableSection = !isset($elementData['enable_section']) || !empty($elementData['enable_section']);
$enableContainer = !isset($elementData['enable_container']) || !empty($elementData['enable_container']);

if ($location === '' && $routeFrom === '' && $routeTo === '') {
    return;
}

$popupParts = [];
if ($popupTitle !== '') {
    $popupParts[] = '<strong>' . rex_escape($popupTitle) . '</strong>';
}
if ($popupText !== '') {
    $popupParts[] = nl2br(rex_escape($popupText));
}

$markerJson = null;
if ($location !== '') {
    $markerJson = json_encode([
        [
            'lat' => trim((string) explode(',', $location)[0]),
            'lng' => trim((string) explode(',', $location)[1] ?? ''),
            'popup' => implode('<br>', $popupParts),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

$sectionStyle = StarterConfig::mapBg($sectionBg, 'plain');
$sectionStyle .= StarterConfig::mapPadding($sectionPadding, 'plain');
if ($sectionLight) {
    $sectionStyle .= 'color:#fff;';
}
$containerStyle = StarterConfig::mapContainer($containerWidth, 'plain');

$heightValue = preg_match('/^\d+$/', $height) ? $height : $height;
$routePanelWidthValue = preg_match('/^\d+$/', $routePanelWidth) ? $routePanelWidth : '340';
?>
<?php if ($enableSection): ?>
<section<?= $sectionStyle !== '' ? ' style="' . rex_escape($sectionStyle) . '"' : '' ?>>
<?php endif; ?>
<?php if ($enableContainer): ?>
    <div style="<?= rex_escape($containerStyle) ?>">
<?php endif; ?>
        <?php if ($headline !== ''): ?>
            <h2><?= rex_escape($headline) ?></h2>
        <?php endif; ?>
        <vectormap
            <?= $location !== '' ? 'center="' . rex_escape($location) . '"' : '' ?>
            zoom="<?= rex_escape($zoom) ?>"
            height="<?= rex_escape($heightValue) ?>"
            map-style="<?= rex_escape($mapStyle) ?>"
            controls-cluster="<?= rex_escape($controlsCluster) ?>"
            <?= $theme !== '' ? ' theme="' . rex_escape($theme) . '"' : '' ?>
            <?= $controlsStyle !== '' ? ' controls-style="' . rex_escape($controlsStyle) . '"' : '' ?>
            <?= $infoHtml !== '' ? ' info-html="' . rex_escape($infoHtml) . '" info-position="top-left" info-closable' : '' ?>
            <?= $routeFrom !== '' ? ' route-from="' . rex_escape($routeFrom) . '"' : '' ?>
            <?= $routeTo !== '' ? ' route-to="' . rex_escape($routeTo) . '"' : '' ?>
            <?= $routeMode !== '' ? ' route-mode="' . rex_escape($routeMode) . '"' : '' ?>
            <?= $routePanel ? ' route-panel' : '' ?>
            <?= $routeToLocked ? ' route-to-locked' : '' ?>
            <?= $routeNoSteps ? ' route-no-steps' : '' ?>
            <?= $routeToPopup !== '' ? ' route-to-popup="' . rex_escape($routeToPopup) . '"' : '' ?>
            <?= $routePanelLayout !== '' ? ' route-panel-layout="' . rex_escape($routePanelLayout) . '"' : '' ?>
            <?= $routePanelWidthValue !== '' ? ' route-panel-width="' . rex_escape($routePanelWidthValue) . '"' : '' ?>
            <?= $routePanelPosition !== '' ? ' route-panel-position="' . rex_escape($routePanelPosition) . '"' : '' ?>
            <?= $routePanelStyle !== '' ? ' route-panel-style="' . rex_escape($routePanelStyle) . '"' : '' ?>
            <?= $locate ? ' locate' : '' ?>
            <?= $showSatellite ? ' show-satellite' : '' ?>
            <?= $fullscreen ? ' fullscreen' : '' ?>
            <?= $markerJson !== null ? ' markers=\'' . rex_escape((string) $markerJson) . '\'' : '' ?>>
        </vectormap>
<?php if ($enableContainer): ?>
    </div>
<?php endif; ?>
<?php if ($enableSection): ?>
</section>
<?php endif; ?>
