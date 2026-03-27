<?php
// Vektorkarten Demo - Uebersicht & Einbindung
?>
<?php
// no addon var needed, all text is hardcoded for simplicity
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="rex-icon rex-icon-map"></i>
            Vektorkarten Demo
            <span class="label label-success" style="margin-left:6px;font-weight:normal;font-size:11px">MapLibre GL JS</span>
            <span class="label label-default" style="margin-left:4px;font-weight:normal;font-size:11px">OpenFreeMap</span>
        </h3>
    </div>
    <div class="panel-body">

        <div class="row" style="margin-bottom:16px">
            <div class="col-md-4">
                <div class="alert alert-success" style="margin:0">
                    <strong>Vorteile von Vektorkarten</strong>
                    <ul style="margin:8px 0 0 0;padding-left:18px;font-size:13px">
                        <li>Stufenlos zoombar ohne Pixelrauschen</li>
                        <li>Client-seitig gestylte Vektorkarten</li>
                        <li>Viel kleinere Datenmengen als Raster</li>
                        <li>Rotation, Pitch &amp; 3D-Gebäude möglich</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-info" style="margin:0">
                    <strong>DSGVO-konformer Proxy</strong>
                    <ul style="margin:8px 0 0 0;padding-left:18px;font-size:13px">
                        <li>Kein direkter Kontakt zu externen Servern</li>
                        <li>Alle Tiles laufen durch lokalen PHP-Proxy</li>
                        <li>IP-Adressen der Besucher bleiben privat</li>
                        <li>Server-seitiges Caching der Tile-Daten</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-warning" style="margin:0">
                    <strong>Voraussetzungen</strong>
                    <p style="margin:8px 0 0 0;font-size:13px">
                        MapLibre GL JS benötigt WebGL-Unterstützung im Browser.
                        Alle Kacheln (<code>.pbf</code>) werden via REDAXO-Proxy
                        von OpenFreeMap geladen und gecacht.
                    </p>
                </div>
            </div>
        </div>

        <div style="margin-bottom:12px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <div>
                <label style="font-weight:bold;margin-right:8px;margin-bottom:0">Stil:</label>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-primary vm-demo-style-btn active" data-style="liberty">Liberty</button>
                    <button type="button" class="btn btn-sm btn-default vm-demo-style-btn" data-style="bright">Bright</button>
                    <button type="button" class="btn btn-sm btn-default vm-demo-style-btn" data-style="positron">Positron</button>
                    <button type="button" class="btn btn-sm btn-default vm-demo-style-btn" data-style="satellite" title="ESRI World Imagery &ndash; kein API-Key">&#x1F6F0; Satellit</button>
                </div>
            </div>
            <div>
                <label style="font-weight:bold;margin-right:8px;margin-bottom:0">Sprache:</label>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-xs btn-primary vm-demo-lang-btn active" data-lang="de" title="Deutsch">DE</button>
                    <button type="button" class="btn btn-xs btn-default vm-demo-lang-btn" data-lang="en" title="English">EN</button>
                    <button type="button" class="btn btn-xs btn-default vm-demo-lang-btn" data-lang="fr" title="Français">FR</button>
                    <button type="button" class="btn btn-xs btn-default vm-demo-lang-btn" data-lang="es" title="Español">ES</button>
                    <button type="button" class="btn btn-xs btn-default vm-demo-lang-btn" data-lang="it" title="Italiano">IT</button>
                    <button type="button" class="btn btn-xs btn-default vm-demo-lang-btn" data-lang="nl" title="Nederlands">NL</button>
                    <button type="button" class="btn btn-xs btn-default vm-demo-lang-btn" data-lang="ja" title="日本語">JA</button>
                    <button type="button" class="btn btn-xs btn-default vm-demo-lang-btn" data-lang="ar" title="العربية">AR</button>
                </div>
            </div>
            <div>
                <label style="font-weight:bold;margin-right:8px;margin-bottom:0">Ansicht:</label>
                <button type="button" class="btn btn-sm btn-primary active" id="vm-demo-3d-toggle" title="3D-Gebäude an/aus">
                    <i class="rex-icon rex-icon-layers"></i> 3D Gebäude
                </button>
            </div>
            <div>
                <label style="font-weight:bold;margin-right:8px;margin-bottom:0">Theme:</label>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-xs btn-primary vm-demo-theme-btn active" data-theme="none">&mdash; Kein Theme</button>
                    <button type="button" class="btn btn-xs btn-default vm-demo-theme-btn" data-theme="dark">Dark</button>
                    <button type="button" class="btn btn-xs btn-default vm-demo-theme-btn" data-theme="warm">Warm</button>
                    <button type="button" class="btn btn-xs btn-default vm-demo-theme-btn" data-theme="mono">Mono</button>
                </div>
            </div>
        </div>

        <div id="vm-demo-map" style="width:100%;height:500px;border-radius:4px;overflow:hidden;"></div>

        <p class="text-muted" style="margin-top:8px;font-size:12px">
            OpenFreeMap &middot; DSGVO-konform via REDAXO-Proxy &middot; kein API-Key erforderlich
        </p>

    </div>
</div>

<!-- ================================================================
     FRONTEND-EINBINDUNG
     ================================================================ -->
<div class="panel panel-info">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="rex-icon rex-icon-code"></i>
            Frontend-Einbindung &mdash; So kommt die Karte auf die Website
        </h3>
    </div>
    <div class="panel-body">

        <div class="alert alert-success" style="margin-bottom:20px">
            <strong>Automatisch aktiv:</strong> Nach der Installation werden CSS und JS automatisch in jede Frontend-Seite geladen &mdash; du musst nichts weiter tun.
            Einfach <code>&lt;vectormap&gt;</code> in Modul-Output oder Template schreiben, fertig.
        </div>

        <div class="row">
            <div class="col-md-6">
                <h4 style="margin-top:0">Schritt 1: Im Modul-Input (Backend)</h4>
                <p class="text-muted" style="font-size:13px">Koordinatenfeld mit automatischem Karten-Picker:</p>
                <pre style="font-size:12px">&lt;div class="form-group"&gt;
  &lt;label&gt;Koordinaten&lt;/label&gt;
  &lt;input type="text"
    name="REX_INPUT_VALUE[1]"
    value="REX_VALUE[1]"
    class="form-control"
    data-vector-picker="1"
    placeholder="lat,lng"&gt;
&lt;/div&gt;</pre>
                <p class="text-muted" style="font-size:12px">Der Button neben dem Feld &ouml;ffnet die Kartenauswahl mit Adresssuche.</p>
            </div>
            <div class="col-md-6">
                <h4 style="margin-top:0">Schritt 2: Im Modul-Output (Frontend)</h4>
                <p class="text-muted" style="font-size:13px">Karte ausgeben &mdash; kein PHP, kein JS:</p>
                <pre style="font-size:12px">&lt;vectormap
  lat="REX_VALUE[1]"
  lng="REX_VALUE[2]"
  zoom="14"
  height="400px"
  markers='[{
    "lat":REX_VALUE[1],
    "lng":REX_VALUE[2],
    "popup":"Unser Standort"
  }]'&gt;
&lt;/vectormap&gt;

&lt;!-- Alternativ: lat,lng aus einem Feld --&gt;
&lt;?php
  [$lat,$lng] = explode(',', 'REX_VALUE[1]' . ',0');
?&gt;
&lt;vectormap lat="&lt;?= (float)$lat ?&gt;"
           lng="&lt;?= (float)$lng ?&gt;"
           zoom="14"&gt;
&lt;/vectormap&gt;</pre>
            </div>
        </div>

        <hr>

        <h4>Manuell einbinden (falls Auto-Loading deaktiviert)</h4>
        <p class="text-muted" style="font-size:13px">
            Wenn das automatische Laden unter <a href="<?= rex_url::backendPage('vector_maps/settings') ?>">Einstellungen</a> deaktiviert ist,
            die Assets manuell im Template einf&uuml;gen:
        </p>
        <?php $vmAddon = rex_addon::get('vector_maps'); ?>
        <pre style="font-size:12px">&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;
  &lt;!-- 1. MapLibre GL CSS --&gt;
  &lt;link rel="stylesheet" href="&lt;?= $vmAddon-&gt;getAssetsUrl('maplibre/maplibre-gl.css') ?&gt;"&gt;
  &lt;!-- 2. Vector Maps Styles --&gt;
  &lt;link rel="stylesheet" href="&lt;?= $vmAddon-&gt;getAssetsUrl('build/vectormaps.css') ?&gt;"&gt;
&lt;/head&gt;
&lt;body&gt;

  &lt;vectormap lat="51.43" lng="6.77" zoom="13"&gt;&lt;/vectormap&gt;

  &lt;!-- 3. MapLibre GL JS (defer) --&gt;
  &lt;script defer src="&lt;?= $vmAddon-&gt;getAssetsUrl('maplibre/maplibre-gl.js') ?&gt;"&gt;&lt;/script&gt;
  &lt;!-- 4. Vector Maps Web Component (defer) --&gt;
  &lt;script defer src="&lt;?= $vmAddon-&gt;getAssetsUrl('build/vectormaps.js') ?&gt;"&gt;&lt;/script&gt;
&lt;/body&gt;
&lt;/html&gt;</pre>

        <div class="alert alert-info" style="margin-bottom:0">
            <strong>CSP-Hinweis:</strong> Falls eine Content-Security-Policy aktiv ist, gen&uuml;gt
            <code>worker-src blob:</code> &mdash; alle Tile-Anfragen laufen &uuml;ber den REDAXO-Proxy,
            keine externen <code>connect-src</code>-Eintr&auml;ge n&ouml;tig.
        </div>

    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="rex-icon rex-icon-edit"></i>
            Koordinaten-Picker Demo
        </h3>
    </div>
    <div class="panel-body">
        <p>Klicke auf den Button, um den Koordinaten-Picker zu öffnen. Die gewählten Koordinaten werden ins Textfeld übernommen.</p>

        <div class="form-group">
            <label for="vm-demo-picker-input">Koordinaten (Lat,Lng)</label>
            <input type="text"
                   id="vm-demo-picker-input"
                   class="form-control"
                   placeholder="51.165691,10.451526"
                   data-vector-picker="1">
            <p class="help-block">Wähle einen Ort auf der Karte – die Koordinaten werden automatisch eingetragen.</p>
        </div>

    </div>
</div>

<!-- ================================================================
     <vectormap> WEB COMPONENT
     ================================================================ -->
