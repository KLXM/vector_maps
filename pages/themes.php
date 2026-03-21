<?php

use KLXM\VectorMaps\ThemeManager;

// Custom-Themes laden
$customThemes = ThemeManager::getCustomThemes();

// Theme-Liste und Built-in-Themes für JS bereitstellen
rex_view::setJsProperty('vector_maps_themes', $customThemes);

?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="rex-icon rex-icon-vector-maps"></i>
            Theme-Editor &mdash; eigene Farbpaletten erstellen
        </h3>
    </div>
    <div class="panel-body">

        <div class="row">
            <!-- ── Linke Spalte: Farbpicker-Formular ── -->
            <div class="col-md-4">

                <div class="form-group">
                    <label for="vm-te-name">Theme-Name <small class="text-muted">(nur a–z, 0–9, Bindestrich)</small></label>
                    <input type="text" id="vm-te-name" class="form-control"
                           placeholder="mein-theme" maxlength="40"
                           pattern="[a-z0-9_\-]+"
                           title="Nur Kleinbuchstaben, Ziffern, Bindestrich">
                </div>

                <h5 style="margin-top:20px;margin-bottom:10px">Farben</h5>

                <?php
                $colorFieldsMain = [
                    'land'        => 'Land / Hintergrund',
                    'water'       => 'Wasser',
                    'green'       => 'Wald / dichtes Grün',
                    'farmland'    => 'Wiesen / Felder / Parks',
                    'road_major'  => 'Hauptstraßen',
                    'road_minor'  => 'Nebenstraßen',
                    'rail'        => 'Eisenbahn / Schienen',
                    'building'    => 'Gebäude',
                    'label'       => 'Beschriftung',
                    'label_halo'  => 'Beschriftungs-Halo',
                    'road_label'  => 'Straßen-Nummern (Text)',
                ];
                $colorFieldsOutline = [
                    'road_casing' => 'Straßen-Outline (Casing)',
                    'outline'     => 'Konturen (Gebäude, Parks)',
                ];
                foreach ($colorFieldsMain as $key => $label): ?>
                <div class="vm-te-color-row">
                    <input type="color"
                           id="vm-te-<?= rex_escape($key) ?>"
                           data-color-key="<?= rex_escape($key) ?>"
                           value="#cccccc"
                           title="<?= rex_escape($label) ?>">
                    <label for="vm-te-<?= rex_escape($key) ?>"><?= rex_escape($label) ?></label>
                </div>
                <?php endforeach; ?>

                <!-- Konturen optional aktivierbar -->
                <div style="margin-top:12px;padding-top:10px;border-top:1px solid #ddd">
                    <label style="font-weight:normal;cursor:pointer;display:flex;align-items:center;gap:8px">
                        <input type="checkbox" id="vm-te-customize_outlines">
                        Konturen &amp; Straßen-Casing anpassen
                    </label>
                </div>
                <div id="vm-te-outline-fields" style="display:none;margin-top:4px">
                <?php foreach ($colorFieldsOutline as $key => $label): ?>
                <div class="vm-te-color-row">
                    <input type="color"
                           id="vm-te-<?= rex_escape($key) ?>"
                           data-color-key="<?= rex_escape($key) ?>"
                           value="#cccccc"
                           title="<?= rex_escape($label) ?>">
                    <label for="vm-te-<?= rex_escape($key) ?>"><?= rex_escape($label) ?></label>
                </div>
                <?php endforeach; ?>
                </div>

                <!-- Halo-Stärke (0 = kein Halo) -->
                <div class="vm-te-color-row" style="align-items:center;margin-top:8px">
                    <input type="range" id="vm-te-halo_width"
                           min="0" max="4" step="0.5" value="1"
                           style="width:80px;cursor:pointer;margin-right:8px">
                    <label for="vm-te-halo_width" style="margin:0">
                        Halo-Stärke: <strong><span id="vm-te-halo_width_val">1</span></strong>
                        <small class="text-muted">&nbsp;(0 = kein Halo, max. 4)</small>
                    </label>
                </div>

                <div class="vm-te-presets">
                    <span>Vorlage:</span>
                    <button type="button" class="btn btn-xs btn-default vm-te-preset" data-preset="default">Standard</button>
                    <button type="button" class="btn btn-xs btn-default vm-te-preset" data-preset="dark">Dark</button>
                    <button type="button" class="btn btn-xs btn-default vm-te-preset" data-preset="warm">Warm</button>
                    <button type="button" class="btn btn-xs btn-default vm-te-preset" data-preset="mono">Mono</button>
                </div>

                <button type="button" class="btn btn-primary btn-block" id="vm-te-save" style="margin-top:16px">
                    <i class="rex-icon rex-icon-save"></i> Theme speichern
                </button>

                <div id="vm-te-msg"></div>

            </div>
            <!-- ── Rechte Spalte: Live-Vorschaukarte ── -->
            <div class="col-md-8">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                    <strong>Live-Vorschau</strong>
                    <small class="text-muted">Aktualisiert sich bei jeder Farbänderung automatisch</small>
                </div>
                <div id="vm-te-preview-map"
                     style="width:100%;height:430px;border-radius:4px;overflow:hidden;border:1px solid #ddd">
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ── Gespeicherte Themes ── -->
<div class="panel panel-default">
    <div class="panel-heading" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <h3 class="panel-title">
            <i class="rex-icon rex-icon-layers"></i>
            Verfügbare Themes
        </h3>
        <div style="display:flex;gap:6px;align-items:center">
            <?php if (!empty($customThemes)): ?>
            <button type="button" class="btn btn-sm btn-default" id="vm-te-export-all-btn" title="Alle eigenen Themes als JSON-Datei herunterladen">
                <i class="rex-icon rex-icon-download"></i> Alle exportieren
            </button>
            <?php endif; ?>
            <button type="button" class="btn btn-sm btn-default" id="vm-te-import-btn" title="Theme-JSON-Datei importieren">
                <i class="rex-icon rex-icon-upload"></i> Importieren
            </button>
            <input type="file" id="vm-te-import-file" accept=".json" style="display:none">
        </div>
    </div>
    <div class="panel-body">
        <div id="vm-te-import-msg"></div>

        <!-- Eingebaute Themes -->
        <h5 style="margin-top:0">Eingebaute Themes <small class="text-muted">(schreibgeschützt)</small></h5>
        <div class="vm-te-card-grid" style="margin-bottom:24px">
            <?php
            $builtIn = [
                'dark' => ['label' => 'Dark', 'desc' => 'Nacht / Dark Mode', 'preview' => '#16161d', 'water' => '#0a1520'],
                'warm' => ['label' => 'Warm', 'desc' => 'Sandstein / Warm', 'preview' => '#f4ead6', 'water' => '#a8c8e0'],
                'mono' => ['label' => 'Mono', 'desc' => 'Graustufen',        'preview' => '#eeeeee', 'water' => '#c0ccd4'],
            ];
            foreach ($builtIn as $id => $info): ?>
            <div class="vm-te-card">
                <div class="vm-te-card-swatch">
                    <span style="background:<?= $info['preview'] ?>"></span>
                    <span style="background:<?= $info['water'] ?>"></span>
                </div>
                <div class="vm-te-card-body">
                    <strong><?= $info['label'] ?></strong><br>
                    <span class="text-muted" style="font-size:12px"><?= $info['desc'] ?></span><br>
                    <code style="font-size:11px">map-style=&quot;<?= $id ?>&quot;</code>
                    <div style="margin-top:8px">
                        <button type="button"
                                class="btn btn-xs btn-default vm-te-export-builtin"
                                data-theme="<?= $id ?>"
                                title="Als JSON herunterladen">
                            <i class="rex-icon rex-icon-download"></i> Export
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Custom Themes -->
        <?php if (!empty($customThemes)): ?>
        <h5>Eigene Themes</h5>
        <div class="vm-te-card-grid" id="vm-te-custom-cards">
            <?php foreach ($customThemes as $name => $theme): ?>
            <?php
            $colors = $theme['colors'] ?? [];
            $landColor  = rex_escape($colors['land']  ?? '#cccccc');
            $waterColor = rex_escape($colors['water'] ?? '#aaaaaa');
            ?>
            <div class="vm-te-card" data-theme="<?= rex_escape($name) ?>">
                <div class="vm-te-card-swatch">
                    <span style="background:<?= $landColor ?>"></span>
                    <span style="background:<?= $waterColor ?>"></span>
                </div>
                <div class="vm-te-card-body">
                    <strong><?= rex_escape($name) ?></strong><br>
                    <code style="font-size:11px">map-style=&quot;<?= rex_escape($name) ?>&quot;</code>
                    <div style="margin-top:8px;display:flex;gap:4px;flex-wrap:wrap">
                        <button type="button"
                                class="btn btn-xs btn-default vm-te-load-theme"
                                data-theme="<?= rex_escape($name) ?>">
                            <i class="rex-icon rex-icon-edit"></i> Bearbeiten
                        </button>
                        <button type="button"
                                class="btn btn-xs btn-default vm-te-export-theme"
                                data-theme="<?= rex_escape($name) ?>"
                                title="Als JSON herunterladen">
                            <i class="rex-icon rex-icon-download"></i> Export
                        </button>
                        <button type="button"
                                class="btn btn-xs btn-danger vm-te-delete-theme"
                                data-theme="<?= rex_escape($name) ?>">
                            <i class="rex-icon rex-icon-delete"></i> Löschen
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted" style="margin:0">Noch keine eigenen Themes gespeichert.</p>
        <?php endif; ?>

        <div class="alert alert-info" style="margin-top:20px;margin-bottom:0">
            <strong>Verwendung:</strong><br>
            Im Web-Component einfach den Theme-Namen als <code>map-style</code> angeben
            (eingebaut oder gespeichert):<br>
            <code>&lt;vectormap map-style=&quot;<em>theme-name</em>&quot; center=&quot;52.52,13.40&quot; zoom=&quot;12&quot;&gt;&lt;/vectormap&gt;</code><br><br>
            Oder explizit mit separatem <code>theme</code>-Attribut auf einem OFM-Basis-Stil:<br>
            <code>&lt;vectormap map-style=&quot;liberty&quot; theme=&quot;<em>theme-name</em>&quot; center=&quot;52.52,13.40&quot; zoom=&quot;12&quot;&gt;&lt;/vectormap&gt;</code>
        </div>

    </div>
</div>
