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
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="rex-icon rex-icon-vector-maps"></i>
            <code>&lt;vectormap&gt;</code> Web Component
            <small class="text-muted" style="font-size:12px"> &mdash; Karten per HTML-Attribut konfigurieren</small>
        </h3>
    </div>
    <div class="panel-body">

        <div class="alert alert-success" style="margin-bottom:20px">
            <strong>So einfach geht's:</strong>
            Schreibe <code>&lt;vectormap center="lat,lng" zoom="12" 3d&gt;&lt;/vectormap&gt;</code>
            direkt in dein REDAXO-Modul &ndash; fertig. Kein PHP, kein JavaScript.
            Alle Tiles laufen automatisch durch den DSGVO-konformen REDAXO-Proxy.
        </div>

        <!-- ATTRIBUTE-REFERENZ -->
        <div style="margin-bottom:24px">
            <h4 style="margin-top:0">Attribute&nbsp;&ndash;&nbsp;Schnellreferenz</h4>
            <div class="table-responsive">
                <table class="table table-condensed table-bordered" style="font-size:13px">
                    <thead><tr><th>Attribut</th><th>Standard</th><th>Beispiel</th><th>Beschreibung</th></tr></thead>
                    <tbody>
                        <tr><td><code>center</code></td><td><code>51.16,10.45</code></td><td><code>center="52.52,13.405"</code></td><td>Kartenmittelpunkt als <em>lat,lng</em></td></tr>
                        <tr><td><code>zoom</code></td><td><code>6</code></td><td><code>zoom="14"</code></td><td>Anfangs-Zoom-Stufe (1–22)</td></tr>
                        <tr><td><code>min-zoom</code></td><td><code>0</code></td><td><code>min-zoom="5"</code></td><td>Minimaler Zoom-Level (Benutzer kann nicht weiter herauszoomen)</td></tr>
                        <tr><td><code>max-zoom</code></td><td><code>22</code></td><td><code>max-zoom="18"</code></td><td>Maximaler Zoom-Level (Benutzer kann nicht weiter hineinzoomen)</td></tr>
                        <tr><td><code>map-style</code></td><td><code>liberty</code></td><td><code>map-style="bright"</code></td><td>Stil: <code>liberty</code> | <code>bright</code> | <code>positron</code> oder gespeicherter Theme-Name</td></tr>
                        <tr><td><code>height</code></td><td><code>400</code></td><td><code>height="60vh"</code></td><td>Höhe in px, vh, % …</td></tr>
                        <tr><td><code>pitch</code></td><td><code>0</code></td><td><code>pitch="60"</code></td><td>Kamerakippung 0–85°</td></tr>
                        <tr><td><code>bearing</code></td><td><code>0</code></td><td><code>bearing="45"</code></td><td>Kameraausrichtung in Grad</td></tr>
                        <tr><td><code>3d</code></td><td><code>false</code></td><td><code>3d</code></td><td>3D-Gebäude aktivieren (Boolean-Attribut)</td></tr>
                        <tr><td><code>locate</code></td><td><code>false</code></td><td><code>locate</code></td><td>Standort-Button einblenden (Boolean-Attribut)</td></tr>
                        <tr><td><code>no-navigation</code></td><td><code>false</code></td><td><code>no-navigation</code></td><td>Zoom-/Dreh-Buttons ausblenden (Boolean-Attribut)</td></tr>
                        <tr><td><code>no-attribution</code></td><td><code>false</code></td><td><code>no-attribution</code></td><td>Quellenangabe ausblenden (Boolean-Attribut)</td></tr>
                        <tr><td><code>interactive</code></td><td><code>"true"</code></td><td><code>interactive="false"</code></td><td>Statische Karte (kein Scrollen/Ziehen) &ndash; Wert <code>"false"</code> als String übergeben</td></tr>
                        <tr><td><code>markers</code></td><td>&ndash;</td><td><code>markers='[{"lat":52.52,"lng":13.4,"popup":"Hi"}]'</code></td><td>JSON-Array mit Markern &mdash; Standard-Properties: <code>lat</code>, <code>lng</code>, <code>popup</code>, <code>color</code></td></tr>
                        <tr><td></td><td></td><td><code>"icon":"/assets/pin.svg"</code></td><td>Custom-Pin &mdash; Bild-URL (PNG, SVG) → wird als <code>&lt;img&gt;</code>-Element gerendert</td></tr>
                        <tr><td></td><td></td><td><code>"html":"&#x1F3E0;"</code></td><td>Custom-Pin &mdash; beliebiges HTML oder Emoji → direkt als DOM-Element</td></tr>
                        <tr><td></td><td></td><td><code>"size":[40,40]</code></td><td>Breite &times; H&ouml;he in px f&uuml;r <code>icon</code>-Marker (Standard&nbsp;32&times;32)</td></tr>
                        <tr><td></td><td></td><td><code>"anchor":"center"</code></td><td>Ankerpunkt des Custom-Pins: <code>bottom</code> (Standard) | <code>center</code> | <code>top</code> | <code>left</code> | <code>right</code></td></tr>
                        <tr><td><code>cluster</code></td><td><code>false</code></td><td><code>cluster</code></td><td>Marker automatisch clustern (Boolean-Attribut)</td></tr>
                        <tr><td><code>fit-bounds</code></td><td><em>auto</em></td><td><code>fit-bounds="false"</code></td><td>Auto-Bounds bei mehreren Markern deaktivieren &ndash; Wert <code>"false"</code> als String &uuml;bergeben</td></tr>
                        <tr><td><code>geojson</code></td><td>&ndash;</td><td><code>geojson="/assets/data.geojson"</code></td><td>Externes GeoJSON: relative/absolute URL oder Inline-JSON-String. Absolute URLs laufen &uuml;ber den REDAXO-Proxy</td></tr>
                        <tr><td><code>geojson-color</code></td><td><code>#2b7095</code></td><td><code>geojson-color="#e74c3c"</code></td><td>Standardfarbe f&uuml;r Punkte, Linien und Fl&auml;chen</td></tr>
                        <tr><td><code>geojson-opacity</code></td><td><code>0.3</code></td><td><code>geojson-opacity="0.5"</code></td><td>F&uuml;ll-Transparenz der Fl&auml;chen (0&ndash;1)</td></tr>
                        <tr><td><code>geojson-popup</code></td><td><em>alle</em></td><td><code>geojson-popup="name"</code></td><td>Property-Name f&uuml;r Klick-Popup &mdash; leer = automatische Tabelle aller Properties</td></tr>
                        <tr><td><code>route-from</code></td><td>&ndash;</td><td><code>route-from="52.52,13.405"</code></td><td>Routing-Start <em>lat,lng</em></td></tr>
                        <tr><td><code>route-to</code></td><td>&ndash;</td><td><code>route-to="48.14,11.58"</code></td><td>Routing-Ziel <em>lat,lng</em></td></tr>
                        <tr><td><code>route-mode</code></td><td><code>driving</code></td><td><code>route-mode="walking"</code></td><td><code>driving</code> | <code>walking</code> | <code>cycling</code></td></tr>
                        <tr><td><code>route-panel</code></td><td><code>false</code></td><td><code>route-panel</code></td><td>Interaktives Adresssuch-Panel einblenden (Boolean-Attribut) &ndash; Nutzer k&ouml;nnen Von/Nach-Adressen direkt in der Karte eingeben</td></tr>
                        <tr><td><code>nearby</code></td><td>&ndash;</td><td><code>nearby="amenity=charging_station"</code></td><td>Overpass-Filter für POI-Umgebungssuche. Mehrere mit <code>|</code> trennen: <code>amenity=fuel|amenity=charging_station</code></td></tr>
                        <tr><td><code>nearby-radius</code></td><td><code>1000</code></td><td><code>nearby-radius="2000"</code></td><td>Suchradius in Metern (Standard: 1000&thinsp;m)</td></tr>
                        <tr><td><code>nearby-label</code></td><td><em>OSM-Tags</em></td><td><code>nearby-label="Ladestation"</code></td><td>Fallback-Label für Marker-Popups wenn kein <code>name</code>-Tag vorhanden</td></tr>
                        <tr><td><code>nearby-locate</code></td><td><code>false</code></td><td><code>nearby-locate</code></td><td>Nutzer-Standort (Geolocation API) als Suchzentrum verwenden statt Karten-Mittelpunkt (Boolean-Attribut)</td></tr>
                        <tr><td><code>language</code></td><td><em>Browser-Sprache</em></td><td><code>language="de"</code></td><td>Kartenbeschriftungssprache: <code>de</code> · <code>en</code> · <code>fr</code> · <code>es</code> · <code>it</code> · <code>ja</code> · <code>ar</code> …</td></tr>
                        <tr><td><code>fly-to</code></td><td>&ndash;</td><td><code>fly-to="48.14,11.58,14"</code></td><td>Automatisch dorthin fliegen sobald die Karte im Viewport sichtbar ist (25&thinsp;% Schwelle)</td></tr>
                        <tr><td><code>fly-delay</code></td><td><code>0</code></td><td><code>fly-delay="800"</code></td><td>Zus&auml;tzliche Verz&ouml;gerung in ms nach dem Erscheinen im Viewport</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- BEISPIEL 1: Einfache Karte -->
        <div class="row" style="margin-bottom:24px">
            <div class="col-md-6">
                <h4 style="margin-top:0">1. Einfache Karte</h4>
                <pre style="font-size:12px">&lt;vectormap
    center="52.520008,13.404954"
    zoom="12"
    height="280"&gt;
&lt;/vectormap&gt;</pre>
                <vectormap center="52.520008,13.404954" zoom="12" height="280"></vectormap>
            </div>
            <div class="col-md-6">
                <h4 style="margin-top:0">2. 3D + Standort-Button</h4>
                <pre style="font-size:12px">&lt;vectormap
    center="50.110924,8.682127"
    zoom="15"
    pitch="60"
    bearing="30"
    height="280"
    3d
    locate&gt;
&lt;/vectormap&gt;</pre>
                <vectormap center="50.110924,8.682127" zoom="15" pitch="60" bearing="30" height="280" 3d locate></vectormap>
            </div>
        </div>

        <!-- BEISPIEL 3: Marker + Cluster -->
        <div class="row" style="margin-bottom:24px">
            <div class="col-md-6">
                <h4 style="margin-top:0">3. Mehrere Marker mit Popups</h4>
                <pre style="font-size:12px">&lt;vectormap
    center="51.165691,10.451526"
    zoom="5"
    height="280"
    map-style="bright"
    markers='[
      {"lat":52.52,"lng":13.405,"color":"#e74c3c",
       "popup":"&lt;b&gt;Berlin&lt;/b&gt;"},
      {"lat":48.14,"lng":11.58,"color":"#3498db",
       "popup":"&lt;b&gt;M&uuml;nchen&lt;/b&gt;"},
      {"lat":53.55,"lng":10.0,"color":"#27ae60",
       "popup":"&lt;b&gt;Hamburg&lt;/b&gt;"},
      {"lat":51.51,"lng":7.47,"color":"#9b59b6",
       "popup":"&lt;b&gt;Dortmund&lt;/b&gt;"}
    ]'&gt;
&lt;/vectormap&gt;</pre>
                <vectormap
                    center="51.165691,10.451526"
                    zoom="5"
                    height="280"
                    map-style="bright"
                    markers='[{"lat":52.52,"lng":13.405,"color":"#e74c3c","popup":"<b>Berlin</b>"},{"lat":48.14,"lng":11.58,"color":"#3498db","popup":"<b>München</b>"},{"lat":53.55,"lng":10.0,"color":"#27ae60","popup":"<b>Hamburg</b>"},{"lat":51.51,"lng":7.47,"color":"#9b59b6","popup":"<b>Dortmund</b>"}]'>
                </vectormap>
            </div>
            <div class="col-md-6">
                <h4 style="margin-top:0">4. Cluster-Modus</h4>
                <pre style="font-size:12px">&lt;vectormap
    center="51.165691,10.451526"
    zoom="4"
    height="280"
    cluster
    markers='[&hellip; viele Punkte &hellip;]'&gt;
&lt;/vectormap&gt;</pre>
                <vectormap
                    center="51.165691,10.451526"
                    zoom="4"
                    height="280"
                    cluster
                    markers='[{"lat":52.52,"lng":13.405,"popup":"Berlin"},{"lat":48.14,"lng":11.58,"popup":"München"},{"lat":53.55,"lng":10.0,"popup":"Hamburg"},{"lat":51.51,"lng":7.47,"popup":"Dortmund"},{"lat":50.94,"lng":6.96,"popup":"Köln"},{"lat":53.08,"lng":8.80,"popup":"Bremen"},{"lat":51.34,"lng":12.38,"popup":"Leipzig"},{"lat":48.78,"lng":9.18,"popup":"Stuttgart"},{"lat":51.23,"lng":6.77,"popup":"Düsseldorf"},{"lat":49.45,"lng":11.08,"popup":"Nürnberg"},{"lat":52.37,"lng":9.73,"popup":"Hannover"},{"lat":51.48,"lng":11.97,"popup":"Halle"},{"lat":47.99,"lng":7.84,"popup":"Freiburg"},{"lat":50.07,"lng":8.24,"popup":"Wiesbaden"},{"lat":54.32,"lng":10.13,"popup":"Kiel"}]'>
                </vectormap>
            </div>
        </div>

        <!-- BEISPIEL 5: Routing -->
        <div class="row" style="margin-bottom:8px">
            <div class="col-md-6">
                <h4 style="margin-top:0">5a. Routing Koordinaten (Berlin → München) <span class="label label-info">via OSRM-Proxy</span></h4>
                <pre style="font-size:12px">&lt;vectormap
    height="320"
    route-from="52.520008,13.404954"
    route-to="48.137154,11.576124"
    route-mode="driving"&gt;
&lt;/vectormap&gt;</pre>
                <vectormap
                    height="320"
                    route-from="52.520008,13.404954"
                    route-to="48.137154,11.576124"
                    route-mode="driving">
                </vectormap>
            </div>
            <div class="col-md-6">
                <h4 style="margin-top:0">5b. Routing Adressen (Paris → Duisburg) <span class="label label-info">via Nominatim + OSRM</span></h4>
                <pre style="font-size:12px">&lt;vectormap
    height="320"
    route-from="Eiffelturm, Paris"
    route-to="Duisburg Hauptbahnhof"
    route-mode="driving"&gt;
&lt;/vectormap&gt;</pre>
                <vectormap
                    height="320"
                    route-from="Eiffelturm, Paris"
                    route-to="Duisburg Hauptbahnhof"
                    route-mode="driving">
                </vectormap>
                <p class="text-muted" style="font-size:12px;margin-top:4px">Adressen werden automatisch per Nominatim geocodiert (proxied, DSGVO-konform). Koordinaten und Adressen können auch gemischt werden.</p>
            </div>
        </div>

        <!-- BEISPIEL 5c: Interaktives Route-Panel -->
        <div class="row" style="margin-bottom:8px">
            <div class="col-md-12">
                <h4 style="margin-top:0">5c. Interaktives Route-Panel <span class="label label-success">route-panel</span></h4>
                <p class="text-muted" style="font-size:13px;margin-bottom:8px">Das Panel ermöglicht die Eingabe von Adressen direkt auf der Karte &ndash; mit Live-Autocomplete (via Nominatim-Proxy). Koordinaten und Adressen sind mischbar. Die initialen Werte aus <code>route-from</code>/<code>route-to</code> werden übernommen.</p>
                <pre style="font-size:12px">&lt;vectormap
    height="380"
    route-from="Eiffelturm, Paris"
    route-to="Duisburg Hauptbahnhof"
    route-mode="driving"
    route-panel&gt;
&lt;/vectormap&gt;</pre>
                <vectormap
                    height="380"
                    route-from="Eiffelturm, Paris"
                    route-to="Duisburg Hauptbahnhof"
                    route-mode="driving"
                    route-panel>
                </vectormap>
            </div>
        </div>

        <div class="row" style="margin-bottom:8px">
            <div class="col-md-6">
                <h4 style="margin-top:0">6. Statische Karte + fly-to-Animation</h4>
                <pre style="font-size:12px">&lt;vectormap
    height="320"
    center="51.165691,10.451526"
    zoom="5"
    map-style="positron"
    no-navigation
    interactive="false"
    fly-to="48.858844,2.294351,14"
    fly-delay="1200"&gt;
&lt;/vectormap&gt;</pre>
                <vectormap
                    height="320"
                    center="51.165691,10.451526"
                    zoom="5"
                    map-style="positron"
                    no-navigation
                    interactive="false"
                    fly-to="48.858844,2.294351,14"
                    fly-delay="1200">
                </vectormap>
                <p class="text-muted" style="font-size:12px;margin-top:4px">Fliegt zum Eiffelturm sobald die Karte in den Viewport scrollt (25&thinsp;% sichtbar + 1,2&thinsp;s Delay) &mdash; interaktionslos, für Hintergrundkarten.</p>
            </div>
            <div class="col-md-6"></div>
        </div>

        <!-- BEISPIEL 7: Umgebungssuche -->
        <div class="row" style="margin-bottom:8px">
            <div class="col-md-6">
                <h4 style="margin-top:0">7a. Umgebungssuche &ndash; Ladestationen <span class="label label-info">nearby via Overpass API</span></h4>
                <p class="text-muted" style="font-size:13px;margin-bottom:8px">Sucht E-Ladestationen im Umkreis von 8&thinsp;km um Rheinberg. Daten kommen aus OpenStreetMap via Overpass API &ndash; proxied und DSGVO-konform.</p>
                <pre style="font-size:12px">&lt;vectormap
    lat="51.5534"
    lng="6.5946"
    zoom="12"
    height="360"
    nearby="amenity=charging_station"
    nearby-radius="8000"
    nearby-label="Ladestation"&gt;
&lt;/vectormap&gt;</pre>
                <vectormap
                    lat="51.5534"
                    lng="6.5946"
                    zoom="12"
                    height="360"
                    nearby="amenity=charging_station"
                    nearby-radius="8000"
                    nearby-label="Ladestation">
                </vectormap>
            </div>
            <div class="col-md-6">
                <h4 style="margin-top:0">7b. Supermarkt-Suche + Nutzerstandort <span class="label label-success">nearby-locate</span></h4>
                <p class="text-muted" style="font-size:13px;margin-bottom:8px">Mit <code>nearby-locate</code> wird der Browser-Standort des Nutzers als Suchzentrum genutzt. Superm&auml;rkte + Discounter im 7&thinsp;km Umkreis. Achtung: Geolocation ben&ouml;tigt <strong>HTTPS</strong> im Produktionsbetrieb; bei Ablehnung wird Duisburg HBF als Fallback-Mittelpunkt verwendet.</p>
                <pre style="font-size:12px">&lt;vectormap
    lat="51.4298"
    lng="6.7742"
    zoom="13"
    height="360"
    nearby="shop=supermarket|shop=discounter"
    nearby-radius="7000"
    nearby-locate&gt;
&lt;/vectormap&gt;</pre>
                <vectormap
                    lat="51.4298"
                    lng="6.7742"
                    zoom="13"
                    height="360"
                    nearby="shop=supermarket|shop=discounter"
                    nearby-radius="7000"
                    nearby-locate>
                </vectormap>
                <p class="text-muted" style="font-size:12px;margin-top:4px">Browser fragt nach Standort-Berechtigung &mdash; bei Ablehnung oder HTTP-Kontext wird der Karten-Mittelpunkt (<code>lat/lng</code>) als Fallback genutzt. Kompatibel mit allen Overpass-Filtern (<a href="https://taginfo.openstreetmap.org/" target="_blank" rel="noopener">taginfo.openstreetmap.org</a>).</p>
            </div>
        </div>

    </div>
</div>

<!-- BEISPIEL 8: Custom Marker / Individuelle Pins -->
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="rex-icon rex-icon-map-marker"></i>
            Custom Marker &mdash; Individuelle Pins
        </h3>
    </div>
    <div class="panel-body">

        <p class="text-muted" style="margin-bottom:16px">
            Jeder Marker im <code>markers</code>-Array kann als Custom-Pin gerendert werden:
            &uuml;ber <code>icon</code> (Bild-URL) oder <code>html</code> (beliebiges HTML, Emoji, SVG).
            Custom-Marker verwenden MapLibre’s <code>element</code>-Option und sind vollst&auml;ndig per CSS gestaltbar.
        </p>

        <div class="row">
            <div class="col-md-6">
                <h4 style="margin-top:0">8a. Emoji-Pins <span class="label label-info">html</span></h4>
                <p class="text-muted" style="font-size:13px;margin-bottom:8px">Jeder Marker hat ein individuelles <code>html</code>-Emoji als Pin. Popup &ouml;ffnet per Klick.</p>
                <pre style="font-size:11px">&lt;vectormap lat="51.43" lng="6.77" zoom="12"
  markers='[
    {"lat":51.4298,"lng":6.7742,
     "html":"&#x1F686;",
     "anchor":"center",
     "popup":"&lt;b&gt;Duisburg HBF&lt;/b&gt;"},
    {"lat":51.4339,"lng":6.7621,
     "html":"&#x1F30A;",
     "anchor":"center",
     "popup":"&lt;b&gt;Innenhafen&lt;/b&gt;"},
    {"lat":51.4218,"lng":6.7660,
     "html":"&#x1F3EC;",
     "anchor":"center",
     "popup":"&lt;b&gt;Stadtmitte&lt;/b&gt;"}
  ]'&gt;
&lt;/vectormap&gt;</pre>
                <vectormap lat="51.43" lng="6.77" zoom="12" height="360"
                    markers='[
                        {"lat":51.4298,"lng":6.7742,"html":"&#x1F686;","anchor":"center","popup":"<b>Duisburg HBF</b><br>Zentraler Bahnhof"},
                        {"lat":51.4339,"lng":6.7621,"html":"&#x1F30A;","anchor":"center","popup":"<b>Innenhafen</b><br>Kultur &amp; Gastronomie"},
                        {"lat":51.4218,"lng":6.7660,"html":"&#x1F3EC;","anchor":"center","popup":"<b>Stadtmitte</b><br>Shopping"}
                    ]'>
                </vectormap>
            </div>
            <div class="col-md-6">
                <h4 style="margin-top:0">8b. HTML-Div-Pins <span class="label label-success">html mit CSS-Klasse</span></h4>
                <p class="text-muted" style="font-size:13px;margin-bottom:8px">Custom-Marker als gestaltete <code>&lt;div&gt;</code>-Elemente mit eigenem CSS &mdash; beliebige Formen m&ouml;glich.</p>
                <pre style="font-size:11px">&lt;!-- Im Template/CSS: --&gt;
.mein-pin {
  background: #e74c3c;
  color: #fff;
  padding: 4px 8px;
  border-radius: 4px;
  font-weight: bold;
  font-size: 12px;
  white-space: nowrap;
  box-shadow: 0 2px 6px rgba(0,0,0,.3);
}

&lt;vectormap lat="51.43" lng="6.77" zoom="12"
  markers='[
    {"lat":51.4298,"lng":6.7742,
     "html":"&lt;div class=&bsol;&quot;mein-pin&bsol;&quot;&gt;A&lt;/div&gt;",
     "popup":"Station A"}
  ]'&gt;
&lt;/vectormap&gt;</pre>
                <?php
                // Inline-Style-Demo für Custom-Labels
                $labelPins = json_encode([
                    ['lat' => 51.4298, 'lng' => 6.7742, 'html' => '<div style="background:#e74c3c;color:#fff;padding:3px 10px;border-radius:4px;font-weight:bold;font-size:12px;box-shadow:0 2px 6px rgba(0,0,0,.3)">A</div>', 'anchor' => 'bottom', 'popup' => '<b>Punkt A</b><br>Duisburg HBF'],
                    ['lat' => 51.4339, 'lng' => 6.7621, 'html' => '<div style="background:#3498db;color:#fff;padding:3px 10px;border-radius:4px;font-weight:bold;font-size:12px;box-shadow:0 2px 6px rgba(0,0,0,.3)">B</div>', 'anchor' => 'bottom', 'popup' => '<b>Punkt B</b><br>Innenhafen'],
                    ['lat' => 51.4218, 'lng' => 6.7660, 'html' => '<div style="background:#27ae60;color:#fff;padding:3px 10px;border-radius:4px;font-weight:bold;font-size:12px;box-shadow:0 2px 6px rgba(0,0,0,.3)">C</div>', 'anchor' => 'bottom', 'popup' => '<b>Punkt C</b><br>Stadtmitte'],
                ]); ?>
                <vectormap lat="51.43" lng="6.77" zoom="12" height="360"
                    markers='<?= rex_escape($labelPins) ?>'>
                </vectormap>
            </div>
        </div>

        <div class="alert alert-info" style="margin-top:16px;margin-bottom:0">
            <strong>Tipp:</strong> <code>icon</code>-Marker akzeptieren jede Bild-URL (PNG, SVG, WebP):
            <code>"icon":"/assets/addons/vector_maps/pin-rot.svg","size":[40,48]</code> &mdash;
            der Ankerpunkt l&auml;sst sich mit <code>"anchor":"bottom"</code> (Standard), <code>"center"</code>, <code>"top"</code>, <code>"left"</code>, <code>"right"</code> steuern.
        </div>

    </div>
</div>

<!-- BEISPIEL 9: Externes GeoJSON -->
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="rex-icon rex-icon-layers"></i>
            Externes &amp; Inline GeoJSON
        </h3>
    </div>
    <div class="panel-body">

        <p class="text-muted" style="margin-bottom:16px">
            Das <code>geojson</code>-Attribut rendert beliebige GeoJSON-Daten direkt auf der Karte.
            Unterst&uuml;tzt werden <strong>Punkte</strong>, <strong>Linien</strong> und <strong>Fl&auml;chen</strong> &mdash; einzeln und gemischt in einer FeatureCollection.
            Relative URLs werden direkt geladen, absolute URLs laufen automatisch &uuml;ber den REDAXO-Proxy.
        </p>

        <div class="row">
            <div class="col-md-6">
                <h4 style="margin-top:0">9a. Inline GeoJSON &mdash; Punkt &amp; Fl&auml;che <span class="label label-info">inline JSON</span></h4>
                <p class="text-muted" style="font-size:13px;margin-bottom:8px">
                    GeoJSON direkt als JSON-String im <code>geojson</code>-Attribut &mdash; kein Server-Request.
                    Klick auf Fl&auml;che oder Marker zeigt Properties als Popup.
                </p>
                <?php
                $inlineGeoJson = json_encode([
                    'type' => 'FeatureCollection',
                    'features' => [
                        [
                            'type' => 'Feature',
                            'properties' => ['name' => 'Innenhafen Duisburg', 'info' => 'Revitalisiertes Hafengebiet'],
                            'geometry' => [
                                'type' => 'Polygon',
                                'coordinates' => [[[6.7570, 51.4350],[6.7690, 51.4350],[6.7690, 51.4280],[6.7570, 51.4280],[6.7570, 51.4350]]],
                            ],
                        ],
                        [
                            'type' => 'Feature',
                            'properties' => ['name' => 'Museum Küppersmühle', 'Typ' => 'Museum', 'geöffnet' => 'Di–So 10–17 Uhr', 'marker-color' => '#8e44ad', 'marker-symbol' => '🏛'],
                            'geometry' => ['type' => 'Point', 'coordinates' => [6.7621, 51.4339]],
                        ],
                        [
                            'type' => 'Feature',
                            'properties' => ['name' => 'Ladestation', 'Betreiber' => 'Stadtwerke DU', 'marker-color' => '#27ae60', 'marker-symbol' => '⚡'],
                            'geometry' => ['type' => 'Point', 'coordinates' => [6.7660, 51.4320]],
                        ],
                        [
                            'type' => 'Feature',
                            'properties' => ['name' => 'Restaurant', 'Küche' => 'Mediterran', 'marker-color' => '#e74c3c', 'marker-symbol' => '🍽'],
                            'geometry' => ['type' => 'Point', 'coordinates' => [6.7590, 51.4310]],
                        ],
                        [
                            'type' => 'Feature',
                            'properties' => ['name' => 'Hafenpromenade', 'Länge' => 'ca. 1,2 km'],
                            'geometry' => [
                                'type' => 'LineString',
                                'coordinates' => [[6.7570, 51.4330],[6.7600, 51.4340],[6.7640, 51.4345],[6.7680, 51.4338]],
                            ],
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE);
                ?>
                <pre style="font-size:11px">&lt;vectormap lat="51.434" lng="6.763" zoom="14"
  geojson='{
    "type":"FeatureCollection",
    "features":[
      { "type":"Feature",
        "properties":{ "name":"Museum", "marker-color":"#8e44ad", "marker-symbol":"🏛" },
        "geometry":{ "type":"Point", "coordinates":[6.762,51.434] }
      },
      { "type":"Feature",
        "properties":{ "name":"Ladestation", "marker-color":"#27ae60", "marker-symbol":"⚡" },
        "geometry":{ "type":"Point", "coordinates":[6.766,51.432] }
      },
      { "type":"Feature",
        "properties":{ "name":"Fläche" },
        "geometry":{ "type":"Polygon", "coordinates":[[...]] }
      }
    ]
  }'
  geojson-color="#2980b9"
  geojson-opacity="0.25"
  geojson-popup="name"&gt;
&lt;/vectormap&gt;</pre>
                <p class="text-muted" style="font-size:12px;margin:4px 0 6px">
                    <strong>marker-color</strong> und <strong>marker-symbol</strong> (Emoji oder Text) k&ouml;nnen pro Feature gesetzt werden
                    und &uuml;berschreiben die globale Farbe (<code>geojson-color</code>).
                </p>
                <vectormap lat="51.434" lng="6.763" zoom="14" height="380"
                    geojson='<?= rex_escape($inlineGeoJson) ?>'
                    geojson-color="#2980b9"
                    geojson-opacity="0.25"
                    geojson-popup="name">
                </vectormap>
            </div>
            <div class="col-md-6">
                <h4 style="margin-top:0">9b. GeoJSON-URL <span class="label label-default">relative &amp; externe URL</span></h4>
                <p class="text-muted" style="font-size:13px;margin-bottom:8px">
                    Relative Pfade werden direkt geladen, externe URLs automatisch &uuml;ber den REDAXO-Proxy (DSGVO-konform).
                </p>
                <pre style="font-size:12px">&lt;!-- Relative URL (direkt) --&gt;
&lt;vectormap lat="51.43" lng="6.77" zoom="10"
  geojson="/assets/karte-data.geojson"
  geojson-color="#27ae60"
  geojson-popup="name"&gt;
&lt;/vectormap&gt;

&lt;!-- Externe URL (via Proxy) --&gt;
&lt;vectormap lat="51.43" lng="6.77" zoom="6"
  geojson="https://example.com/data.geojson"
  geojson-color="#e74c3c"
  geojson-opacity="0.4"
  geojson-popup="description"&gt;
&lt;/vectormap&gt;</pre>
                <div class="alert alert-success" style="margin-top:8px;margin-bottom:0">
                    <strong>geojson-popup</strong> gibt den Property-Namen f&uuml;r den Popup-Inhalt an.
                    Ohne dieses Attribut wird automatisch eine Tabelle aller Feature-Properties angezeigt.<br><br>
                    <strong>geojson + markers</strong> lassen sich kombinieren &mdash;
                    GeoJSON-Layer und individuelle Marker erscheinen gleichzeitig auf der Karte.
                </div>
            </div>
        </div>

        <hr>

        <h4>9c. Inline GeoJSON aus PHP-Array &mdash; dynamische Marker-Pins <span class="label label-warning">PHP</span></h4>
        <p class="text-muted" style="font-size:13px;margin-bottom:12px">
            Per <code>json_encode()</code> k&ouml;nnen beliebige PHP-Daten (z.&nbsp;B. aus YForm oder rex_sql) als GeoJSON-FeatureCollection ausgegeben werden.
            Jeder Punkt erh&auml;lt individuelles <strong>marker-color</strong> und <strong>marker-symbol</strong>-Emoji.
        </p>
        <div class="row">
            <div class="col-md-6">
                <pre style="font-size:12px">&lt;?php
$locations = [
    ['name' =&gt; 'Rathaus',     'lat' =&gt; 51.4298, 'lng' =&gt; 6.7742, 'symbol' =&gt; '🏛', 'color' =&gt; '#8e44ad'],
    ['name' =&gt; 'Ladestation', 'lat' =&gt; 51.4320, 'lng' =&gt; 6.7660, 'symbol' =&gt; '⚡', 'color' =&gt; '#27ae60'],
    ['name' =&gt; 'Parkplatz',   'lat' =&gt; 51.4260, 'lng' =&gt; 6.7700, 'symbol' =&gt; '🅿', 'color' =&gt; '#2980b9'],
    ['name' =&gt; 'Restaurant',  'lat' =&gt; 51.4310, 'lng' =&gt; 6.7590, 'symbol' =&gt; '🍽', 'color' =&gt; '#e74c3c'],
];
$features = array_map(static function(array $loc): array {
    return [
        'type'       =&gt; 'Feature',
        'properties' =&gt; [
            'name'          =&gt; $loc['name'],
            'marker-color'  =&gt; $loc['color'],
            'marker-symbol' =&gt; $loc['symbol'],
        ],
        'geometry' =&gt; ['type' =&gt; 'Point', 'coordinates' =&gt; [$loc['lng'], $loc['lat']]],
    ];
}, $locations);
$geoJson = json_encode(['type' =&gt; 'FeatureCollection', 'features' =&gt; $features]);
?&gt;
&lt;vectormap lat="51.430" lng="6.763" zoom="13"
  geojson='&lt;?= rex_escape($geoJson) ?&gt;'
  geojson-popup="name"&gt;
&lt;/vectormap&gt;</pre>
            </div>
            <div class="col-md-6">
                <?php
                $demoLocations = [
                    ['name' => 'Rathaus',     'lat' => 51.4298, 'lng' => 6.7742, 'symbol' => '🏛', 'color' => '#8e44ad', 'info' => 'Duisburger Rathaus'],
                    ['name' => 'Ladestation', 'lat' => 51.4320, 'lng' => 6.7660, 'symbol' => '⚡', 'color' => '#27ae60', 'info' => 'E-Ladesäule'],
                    ['name' => 'Parkplatz',   'lat' => 51.4260, 'lng' => 6.7700, 'symbol' => '🅿', 'color' => '#2980b9', 'info' => 'Parkhaus Innenstadt'],
                    ['name' => 'Restaurant',  'lat' => 51.4310, 'lng' => 6.7590, 'symbol' => '🍽', 'color' => '#e74c3c', 'info' => 'Mediterrane Küche'],
                    ['name' => 'Hotel',       'lat' => 51.4280, 'lng' => 6.7650, 'symbol' => '🏨', 'color' => '#d35400', 'info' => '4-Sterne-Hotel'],
                ];
                $demoFeatures = array_map(static function(array $loc): array {
                    return [
                        'type'       => 'Feature',
                        'properties' => ['name' => $loc['name'], 'Info' => $loc['info'], 'marker-color' => $loc['color'], 'marker-symbol' => $loc['symbol']],
                        'geometry'   => ['type' => 'Point', 'coordinates' => [$loc['lng'], $loc['lat']]],
                    ];
                }, $demoLocations);
                $demoGeoJson = json_encode(['type' => 'FeatureCollection', 'features' => $demoFeatures], JSON_UNESCAPED_UNICODE);
                ?>
                <vectormap lat="51.430" lng="6.763" zoom="13" height="360"
                    geojson='<?= rex_escape($demoGeoJson) ?>'
                    geojson-popup="name">
                </vectormap>
            </div>
        </div>

    </div>
</div>

<!-- ================================================================
     DEMO 10: Freie Wetter-APIs via Proxy (Open-Meteo & Bright Sky)
     ================================================================ -->
<?php
// Open-Meteo: Wetterdaten für 6 deutsche Städte (Batch-Request, 30 min Cache)
$vmWCities = [
    ['name' => 'Berlin',    'lat' => 52.52,  'lng' => 13.405],
    ['name' => 'Hamburg',   'lat' => 53.55,  'lng' => 10.0],
    ['name' => 'München',   'lat' => 48.14,  'lng' => 11.58],
    ['name' => 'Köln',      'lat' => 50.94,  'lng' => 6.96],
    ['name' => 'Frankfurt', 'lat' => 50.11,  'lng' => 8.68],
    ['name' => 'Duisburg',  'lat' => 51.43,  'lng' => 6.76],
];

$vmOmCacheFile = rex_path::addonCache('vector_maps', 'demo_openmeteo.json');
$vmOmData = null;
if (is_file($vmOmCacheFile) && (time() - filemtime($vmOmCacheFile)) < 1800) {
    $vmOmCached = rex_file::get($vmOmCacheFile);
    $vmOmParsed = is_string($vmOmCached) ? json_decode($vmOmCached, true) : null;
    if (is_array($vmOmParsed) && count($vmOmParsed) > 0) {
        $vmOmData = $vmOmParsed;
    }
}
if ($vmOmData === null) {
    $vmOmLats = implode(',', array_column($vmWCities, 'lat'));
    $vmOmLngs = implode(',', array_column($vmWCities, 'lng'));
    $vmOmUrl  = 'https://api.open-meteo.com/v1/forecast'
        . '?latitude=' . $vmOmLats
        . '&longitude=' . $vmOmLngs
        . '&current=temperature_2m,weather_code'
        . '&timezone=auto';
    $vmOmCtx = stream_context_create(['http' => [
        'timeout' => 5,
        'ignore_errors' => true,
        'header' => "User-Agent: REDAXO/5 VectorMaps-Demo\r\n",
    ]]);
    $vmOmRaw     = @file_get_contents($vmOmUrl, false, $vmOmCtx);
    $vmOmDecoded = is_string($vmOmRaw) ? json_decode($vmOmRaw, true) : null;
    if (is_array($vmOmDecoded)) {
        // Mehrere Koordinaten → API liefert Array; eine Koordinate → Objekt
        $vmOmResponses = isset($vmOmDecoded[0]) ? $vmOmDecoded : [$vmOmDecoded];
        $vmOmData = [];
        foreach ($vmWCities as $vmOmI => $vmOmCity) {
            $vmOmCurrent = $vmOmResponses[$vmOmI]['current'] ?? null;
            if ($vmOmCurrent === null) {
                continue;
            }
            $vmOmData[] = [
                'name' => $vmOmCity['name'],
                'lat'  => $vmOmCity['lat'],
                'lng'  => $vmOmCity['lng'],
                'temp' => $vmOmCurrent['temperature_2m'] ?? null,
                'wmo'  => (int)($vmOmCurrent['weather_code'] ?? 0),
            ];
        }
        if (count($vmOmData) > 0) {
            rex_file::put($vmOmCacheFile, json_encode($vmOmData, JSON_UNESCAPED_UNICODE));
        }
    }
}

// WMO Wettercode (4677) → Emoji
$vmWmoEmoji = static function(int $c): string {
    return match(true) {
        $c === 0 => '☀️',
        $c <= 3  => '🌤',
        $c <= 48 => '🌫',
        $c <= 55 => '🌦',
        $c <= 65 => '🌧',
        $c <= 77 => '🌨',
        $c <= 82 => '🌧',
        $c <= 86 => '🌨',
        $c >= 95 => '⛈',
        default  => '🌡',
    };
};

$vmWeatherMarkers = [];
if (is_array($vmOmData)) {
    foreach ($vmOmData as $vmOmEntry) {
        $vmOmTemp  = isset($vmOmEntry['temp']) ? round((float)$vmOmEntry['temp'], 1) : null;
        $vmOmEmoji = $vmWmoEmoji((int)($vmOmEntry['wmo'] ?? 0));
        $vmOmColor = match(true) {
            $vmOmTemp === null => '#95a5a6',
            $vmOmTemp < 0     => '#3498db',
            $vmOmTemp < 10    => '#2980b9',
            $vmOmTemp < 20    => '#27ae60',
            default           => '#e74c3c',
        };
        $vmOmTempStr = $vmOmTemp !== null ? $vmOmTemp . '°' : 'n/a';
        $vmOmHtml = '<div style="'
            . 'background:' . $vmOmColor . ';'
            . 'border-radius:50px;'
            . 'padding:4px 10px;'
            . 'color:#fff;'
            . 'font-size:13px;'
            . 'font-weight:700;'
            . 'box-shadow:0 2px 8px rgba(0,0,0,.4);'
            . 'display:flex;align-items:center;gap:5px;'
            . 'white-space:nowrap;'
            . 'border:2px solid rgba(255,255,255,.35);'
            . 'line-height:1.2;'
            . '">'
            . '<span style="font-size:18px;line-height:1">' . $vmOmEmoji . '</span>'
            . '<span>' . $vmOmTempStr . '</span>'
            . '</div>';
        $vmWeatherMarkers[] = [
            'lat'    => (float)$vmOmEntry['lat'],
            'lng'    => (float)$vmOmEntry['lng'],
            'html'   => $vmOmHtml,
            'anchor' => 'center',
            'popup'  => '<b>' . rex_escape($vmOmEntry['name']) . '</b><br>'
                      . $vmOmEmoji . ' '
                      . ($vmOmTemp !== null ? $vmOmTemp . ' °C' : 'n/a'),
        ];
    }
}
$vmWeatherMarkersJson = json_encode($vmWeatherMarkers, JSON_UNESCAPED_UNICODE);

// Bright Sky: DWD-Wetter für 6 deutsche Großstädte (30 min Cache)
$vmBsCities = [
    ['name' => 'Hamburg',   'lat' => 53.55, 'lon' => 10.00],
    ['name' => 'Berlin',    'lat' => 52.52, 'lon' => 13.405],
    ['name' => 'München',   'lat' => 48.14, 'lon' => 11.58],
    ['name' => 'Köln',      'lat' => 50.94, 'lon' => 6.96],
    ['name' => 'Frankfurt', 'lat' => 50.11, 'lon' => 8.68],
    ['name' => 'Duisburg',  'lat' => 51.43, 'lon' => 6.76],
];
$vmBsCondEmojis = [
    'dry'          => '☀️',
    'fog'          => '🌫',
    'rain'         => '🌧',
    'sleet'        => '🌧',
    'snow'         => '🌨',
    'hail'         => '🌨',
    'thunderstorm' => '⛈',
    'wind'         => '🌬',
];
$vmBsCacheFile = rex_path::addonCache('vector_maps', 'demo_brightsky_multi.json');
$vmBsMultiData = null;
if (is_file($vmBsCacheFile) && (time() - filemtime($vmBsCacheFile)) < 1800) {
    $vmBsCached = rex_file::get($vmBsCacheFile);
    $vmBsParsed = is_string($vmBsCached) ? json_decode($vmBsCached, true) : null;
    if (is_array($vmBsParsed) && count($vmBsParsed) > 0) {
        $vmBsMultiData = $vmBsParsed;
    }
}
if ($vmBsMultiData === null) {
    $vmBsCtx = stream_context_create(['http' => [
        'timeout' => 5,
        'ignore_errors' => true,
        'header' => "User-Agent: REDAXO/5 VectorMaps-Demo\r\n",
    ]]);
    $vmBsMultiData = [];
    foreach ($vmBsCities as $vmBsCity) {
        $vmBsUrl    = 'https://api.brightsky.dev/current_weather?lat=' . $vmBsCity['lat'] . '&lon=' . $vmBsCity['lon'];
        $vmBsRaw    = @file_get_contents($vmBsUrl, false, $vmBsCtx);
        $vmBsParsed = is_string($vmBsRaw) ? json_decode($vmBsRaw, true) : null;
        if (!is_array($vmBsParsed) || !isset($vmBsParsed['weather'])) {
            continue;
        }
        $vmBsW   = $vmBsParsed['weather'];
        $vmBsSrc = $vmBsParsed['sources'][0] ?? [];
        $vmBsMultiData[] = [
            'city'    => $vmBsCity['name'],
            'station' => $vmBsSrc['station_name'] ?? $vmBsCity['name'],
            'lat'     => (float)($vmBsSrc['lat'] ?? $vmBsCity['lat']),
            'lon'     => (float)($vmBsSrc['lon'] ?? $vmBsCity['lon']),
            'temp'    => isset($vmBsW['temperature']) ? round((float)$vmBsW['temperature'], 1) : null,
            'cond'    => is_string($vmBsW['condition'] ?? null) ? $vmBsW['condition'] : 'dry',
            'wind'    => isset($vmBsW['wind_speed']) ? round((float)$vmBsW['wind_speed']) : null,
        ];
    }
    if (count($vmBsMultiData) > 0) {
        rex_file::put($vmBsCacheFile, json_encode($vmBsMultiData, JSON_UNESCAPED_UNICODE));
    }
}

// Badge-Marker für jede Stadt
$vmBsMarkers = [];
foreach ($vmBsMultiData ?? [] as $vmBsEntry) {
    $vmBsEmoji = $vmBsCondEmojis[$vmBsEntry['cond']] ?? '🌡';
    $vmBsTemp  = $vmBsEntry['temp'];
    $vmBsColor = match(true) {
        $vmBsTemp === null => '#95a5a6',
        $vmBsTemp < 0     => '#3498db',
        $vmBsTemp < 10    => '#2980b9',
        $vmBsTemp < 20    => '#27ae60',
        default           => '#e74c3c',
    };
    $vmBsTempStr = $vmBsTemp !== null ? $vmBsTemp . '°' : 'n/a';
    $vmBsHtml = '<div style="'
        . 'background:' . $vmBsColor . ';'
        . 'border-radius:50px;padding:4px 10px;color:#fff;'
        . 'font-size:13px;font-weight:700;'
        . 'box-shadow:0 2px 8px rgba(0,0,0,.4);'
        . 'display:flex;align-items:center;gap:5px;'
        . 'white-space:nowrap;'
        . 'border:2px solid rgba(255,255,255,.35);line-height:1.2;">'
        . '<span style="font-size:18px;line-height:1">' . $vmBsEmoji . '</span>'
        . '<span>' . $vmBsTempStr . '</span>'
        . '</div>';
    $vmBsMarkers[] = [
        'lat'    => $vmBsEntry['lat'],
        'lng'    => $vmBsEntry['lon'],
        'html'   => $vmBsHtml,
        'anchor' => 'center',
        'popup'  => '<b>' . rex_escape($vmBsEntry['city']) . '</b>'
                  . ' <small>(' . rex_escape($vmBsEntry['station']) . ')</small><br>'
                  . $vmBsEmoji . ' '
                  . ($vmBsTemp !== null ? '<strong>' . $vmBsTemp . ' °C</strong>' : 'n/a')
                  . ($vmBsEntry['wind'] !== null ? ' &nbsp;💨 ' . $vmBsEntry['wind'] . ' km/h' : ''),
    ];
}
$vmBsMarkersJson = json_encode($vmBsMarkers, JSON_UNESCAPED_UNICODE);
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="fa fa-cloud" aria-hidden="true"></i>
            Freie Wetter-APIs via Proxy &mdash; Open-Meteo &amp; Bright Sky (DWD)
            <small class="text-muted" style="font-size:12px">&mdash; kein API-Key, DSGVO-konform, kostenlos</small>
        </h3>
    </div>
    <div class="panel-body">

        <div class="alert alert-info" style="margin-bottom:20px">
            <strong>Freie Wetter-APIs ohne Registrierung</strong> &mdash; vollst&auml;ndig &uuml;ber den REDAXO-Proxy nutzbar:
            <ul style="margin:8px 0 0;padding-left:18px;font-size:13px">
                <li><strong>Open-Meteo</strong> (<code>api.open-meteo.com</code>) &mdash; weltweite Wettervorhersage, WMO-Wettercodes, Open-Source, kein API-Key, keine IP-Bindung</li>
                <li><strong>Bright Sky</strong> (<code>api.brightsky.dev</code>) &mdash; Zugang zu amtlichen DWD-Wetterdaten (Deutscher Wetterdienst), Server in Deutschland &mdash; ideal f&uuml;r DSGVO-konforme Anwendungen</li>
            </ul>
            Beide APIs sind in der Proxy-Whitelist (<code>lib/Proxy.php</code>) eingetragen &mdash; sofort einsatzbereit.
        </div>

        <!-- 10a: Open-Meteo Live-Karte -->
        <h4 style="margin-top:0">10a. Open-Meteo &mdash; Aktuelle Temperaturen in Deutschland <span class="label label-success">Live-Daten</span></h4>
        <p class="text-muted" style="font-size:13px;margin-bottom:12px">
            Batch-Request an <code>api.open-meteo.com/v1/forecast</code> f&uuml;r 6 Gro&szlig;st&auml;dte &mdash; kommagetrennte <code>latitude</code>/<code>longitude</code>-Listen.
            Die API liefert dann ein Array von Objekten zur&uuml;ck. Wettercodes folgen dem <a href="https://open-meteo.com/en/docs#weathervariables" target="_blank" rel="noopener">WMO-Standard 4677</a>.
            Daten werden serverseitig abgerufen und als GeoJSON-Marker eingebettet (30&thinsp;min Cache).
        </p>
        <div class="row">
            <div class="col-md-5">
                <pre style="font-size:11px">&lt;?php
// Open-Meteo: Wetter f&uuml;r mehrere St&auml;dte (Batch)
$cities = [
  ['name'=&gt;'Berlin',  'lat'=&gt;52.52, 'lng'=&gt;13.405],
  ['name'=&gt;'Hamburg', 'lat'=&gt;53.55, 'lng'=&gt;10.0],
  ['name'=&gt;'M&uuml;nchen', 'lat'=&gt;48.14, 'lng'=&gt;11.58],
];
$url = 'https://api.open-meteo.com/v1/forecast'
  . '?latitude=' . implode(',', array_column($cities,'lat'))
  . '&amp;longitude=' . implode(',', array_column($cities,'lng'))
  . '&amp;current=temperature_2m,weather_code&amp;timezone=auto';

$raw = @file_get_contents($url, false,
  stream_context_create(['http'=&gt;['timeout'=&gt;5]]));
$data = json_decode($raw, true);
// Mehrere Koordinaten = Array; eine = Objekt
$responses = isset($data[0]) ? $data : [$data];

// WMO-Code &rarr; Emoji (WMO 4677)
$wmoEmoji = static fn(int $c): string =&gt; match(true) {
  $c === 0 =&gt; '&#x2600;&#xFE0F;', $c &lt;= 3 =&gt; '&#x26C5;',
  $c &lt;= 48 =&gt; '&#x1F32B;',      $c &lt;= 55 =&gt; '&#x1F326;',
  $c &lt;= 65 =&gt; '&#x1F327;',      $c &lt;= 77 =&gt; '&#x1F328;',
  $c &lt;= 86 =&gt; '&#x1F328;',      $c &gt;= 95 =&gt; '&#x26C8;',
  default   =&gt; '&#x1F321;',
};

$features = [];
foreach ($cities as $i =&gt; $city) {
  $temp = $responses[$i]['current']['temperature_2m'] ?? null;
  $wmo  = (int)($responses[$i]['current']['weather_code'] ?? 0);
  $markers = [];
foreach ($cities as $i =&gt; $city) {
  $temp = $responses[$i]['current']['temperature_2m'] ?? null;
  $wmo  = (int)($responses[$i]['current']['weather_code'] ?? 0);
  $emoji = $wmoEmoji($wmo);
  $color = $temp === null ? '#95a5a6'
         : ($temp &lt; 0  ? '#3498db'
         : ($temp &lt; 10 ? '#2980b9'
         : ($temp &lt; 20 ? '#27ae60' : '#e74c3c')));
  $html = '&lt;div style="background:'.$color
    .';border-radius:50px;padding:4px 10px;color:#fff'
    .';font-weight:700;box-shadow:0 2px 8px rgba(0,0,0,.4)'
    .';display:flex;align-items:center;gap:5px;white-space:nowrap'
    .';border:2px solid rgba(255,255,255,.35)"&gt;'
    . '&lt;span style="font-size:18px;line-height:1"&gt;'.$emoji.'&lt;/span&gt;'
    . '&lt;span&gt;'.round($temp,1).'&deg;&lt;/span&gt;&lt;/div&gt;';
  $markers[] = [
    'lat'=&gt;$city['lat'], 'lng'=&gt;$city['lng'],
    'html'=&gt;$html, 'anchor'=&gt;'center',
    'popup'=&gt;'&lt;b&gt;'.$city['name'].'&lt;/b&gt;&lt;br&gt;'.$emoji.' '.round($temp,1).' &deg;C',
  ];
}
$markersJson = json_encode($markers, JSON_UNESCAPED_UNICODE);
?&gt;
&lt;vectormap center="51.165,10.45" zoom="5"
  markers='&lt;?= rex_escape($markersJson) ?&gt;'
  fit-bounds="false"&gt;
&lt;/vectormap&gt;</pre>
            </div>
            <div class="col-md-7">
                <?php if (count($vmWeatherMarkers) > 0): ?>
                <vectormap center="51.165,10.45" zoom="5" height="400"
                    markers='<?= rex_escape($vmWeatherMarkersJson) ?>'
                    fit-bounds="false">
                </vectormap>
                <p class="text-muted" style="font-size:12px;margin-top:6px">
                    Daten: <a href="https://open-meteo.com" target="_blank" rel="noopener">Open-Meteo</a> &mdash;
                    <strong><?= count($vmWeatherMarkers) ?> St&auml;dte</strong> geladen &mdash;
                    Farbe: blau (kalt) &rarr; gr&uuml;n (mild) &rarr; rot (warm)
                </p>
                <?php else: ?>
                <div class="alert alert-warning">
                    <strong>Open-Meteo konnte nicht erreicht werden.</strong><br>
                    Bitte pr&uuml;fe die Internetverbindung des Servers.<br>
                    <small>URL: <code>https://api.open-meteo.com/v1/forecast</code></small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <hr>

        <!-- 10b: Bright Sky / DWD Live-Karte -->
        <h4>10b. Bright Sky (DWD) &mdash; Amtliche Wetterdaten <span class="label label-default">Deutscher Wetterdienst</span></h4>
        <p class="text-muted" style="font-size:13px;margin-bottom:12px">
            Bright Sky bietet kostenlosen Zugang zu den Messdaten des Deutschen Wetterdienstes (DWD).
            Das Endpoint <code>/current_weather?lat=&hellip;&amp;lon=&hellip;</code> liefert aktuelle Werte der n&auml;chstgelegenen DWD-Messstation
            &mdash; einschlie&szlig;lich Stationsname und Koordinaten in <code>sources[0]</code>.
        </p>
        <div class="row">
            <div class="col-md-5">
                <pre style="font-size:11px">&lt;?php
// Bright Sky: aktuelles Wetter von n&auml;chster DWD-Station
$url = 'https://api.brightsky.dev/current_weather'
     . '?lat=53.55&amp;lon=10.0';  // Hamburg

$data = json_decode(@file_get_contents($url, false,
  stream_context_create(['http'=&gt;['timeout'=&gt;5]])), true);

$w   = $data['weather']    ?? [];  // Messwerte
$src = $data['sources'][0] ?? [];  // Stationsinfo

$condEmoji = [
  'dry'=&gt;'&#x2600;&#xFE0F;', 'fog'=&gt;'&#x1F32B;', 'rain'=&gt;'&#x1F327;',
  'sleet'=&gt;'&#x1F327;', 'snow'=&gt;'&#x1F328;',
  'hail'=&gt;'&#x1F328;', 'thunderstorm'=&gt;'&#x26C8;',
  'wind'=&gt;'&#x1F32C;',
];
$cond  = $w['condition'] ?? 'dry';
$emoji = $condEmoji[$cond] ?? '&#x1F321;';
$temp  = $w['temperature'] ?? null;

$geoJson = json_encode(['type'=&gt;'FeatureCollection',
  'features'=&gt;[[
    'type'       =&gt; 'Feature',
    'properties' =&gt; [
      'name'          =&gt; $src['station_name'] ?? 'DWD-Station',
      'Bedingung'     =&gt; $cond,
      'Temperatur'    =&gt; $temp . ' &deg;C',
      'Wind'          =&gt; ($w['wind_speed'] ?? '–') . ' km/h',
      'marker-color'  =&gt; '#1e3a5f',
      'marker-symbol' =&gt; $emoji . ' ' . $temp . '&deg;',
    ],
    'geometry' =&gt; ['type' =&gt; 'Point',
      'coordinates' =&gt; [$src['lon'] ?? 10.0, $src['lat'] ?? 53.55]],
  ]]], JSON_UNESCAPED_UNICODE);
?&gt;
&lt;vectormap lat="53.55" lng="10.0" zoom="8"
  geojson='&lt;?= rex_escape($geoJson) ?&gt;'
  geojson-popup="name"
  geojson-color="#1e3a5f"&gt;
&lt;/vectormap&gt;</pre>
            </div>
            <div class="col-md-7">
                <?php if (count($vmBsMarkers) > 0): ?>
                <vectormap center="51.165,10.45" zoom="5" height="380"
                    markers='<?= rex_escape($vmBsMarkersJson) ?>'
                    fit-bounds="false">
                </vectormap>
                <p class="text-muted" style="font-size:12px;margin-top:6px">
                    <?= count($vmBsMarkers) ?> DWD-Stationen &mdash; Klick auf Badge für Details &mdash;
                    Quelle: <a href="https://brightsky.dev" target="_blank" rel="noopener">Bright Sky</a> /
                    <a href="https://www.dwd.de/DE/leistungen/opendata/opendata.html" target="_blank" rel="noopener">DWD Open Data</a>
                    (Lizenz: <a href="https://creativecommons.org/licenses/by/4.0/" target="_blank" rel="noopener">CC BY 4.0</a>)
                </p>
                <?php else: ?>
                <div class="alert alert-warning">
                    <strong>Bright Sky API konnte nicht erreicht werden.</strong><br>
                    <small>URL: <code>https://api.brightsky.dev/current_weather</code></small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <hr>

        <!-- Client-seitige Proxy-Integration (JS) -->
        <h4>Proxy-Integration im Frontend (JavaScript)</h4>
        <p class="text-muted" style="font-size:13px;margin-bottom:10px">
            F&uuml;r <strong>client-seitiges JavaScript</strong> (z.&nbsp;B. AJAX nach Nutzer-Interaktion oder im Modul-Output)
            werden API-Aufrufe &uuml;ber den REDAXO-Proxy geleitet &mdash; kein CORS-Problem, keine direkte Verbindung vom Browser zur externen API:
        </p>
        <pre style="font-size:12px">// Die proxyUrl()-Funktion ist in vectormaps.js bereits global verf&uuml;gbar.
// Sie leitet externe URLs automatisch durch den REDAXO-Proxy.

// Open-Meteo via Proxy
const omUrl = 'https://api.open-meteo.com/v1/forecast'
    + '?latitude=52.52&amp;longitude=13.405'
    + '&amp;current=temperature_2m,weather_code&amp;timezone=auto';

const omData = await fetch(proxyUrl(omUrl)).then(r => r.json());
console.log('Berlin:', omData.current.temperature_2m, '°C');

// Bright Sky (DWD) via Proxy
const bsUrl = 'https://api.brightsky.dev/current_weather?lat=52.52&amp;lon=13.405';
const bsData = await fetch(proxyUrl(bsUrl)).then(r => r.json());
console.log('DWD-Station:', bsData.sources[0].station_name,
            '| Bedingung:', bsData.weather.condition,
            '| Temp:', bsData.weather.temperature, '°C');</pre>
        <div class="alert alert-success" style="margin-bottom:0">
            <strong>Proxy-Whitelist:</strong>
            <code>https://api.brightsky.dev/</code> und <code>https://api.open-meteo.com/</code>
            sind bereits in <code>lib/Proxy.php</code> eingetragen &mdash; keine weitere Konfiguration n&ouml;tig.
        </div>

    </div>
</div>

<!-- ================================================================
     DEMO 11: Ladestationen dynamisch nachladen (Overpass API / OSM)
     ================================================================ -->
<!-- ================================================================
     DEMO 12: 3D Overfly Berlin
     ================================================================ -->
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="fa fa-paper-plane-o" aria-hidden="true"></i>
            Demo 12: 3D-Überflug Berlin &mdash; Cinematic Flyover
            <small class="text-muted" style="font-size:12px">&mdash; MapLibre <code>flyTo</code>, 3D-Gebäude, animierte Kamerafahrt</small>
        </h3>
    </div>
    <div class="panel-body">

        <div class="alert alert-info" style="margin-bottom:20px">
            <strong>Automatischer Überflug</strong> durch Berliner Wahrzeichen &mdash; vollständig mit MapLibre's <code>flyTo()</code>-API realisiert.
            Kein Video, keine externen Assets &mdash; echte Vektordaten mit 3D-Gebäuden in Echtzeit gerendert.
        </div>

        <div class="row">
            <div class="col-md-5">
                <h4 style="margin-top:0">Funktionsprinzip</h4>
                <p class="text-muted" style="font-size:13px">
                    Eine Sequenz von Waypoints wird nacheinander abgeflogen.
                    Jeder Schritt nutzt <code>map.flyTo()</code> mit individuellen
                    <code>pitch</code>-, <code>bearing</code>- und <code>zoom</code>-Werten
                    für den Kino-Effekt. Das nächste <code>flyTo</code> wird mit <code>setTimeout</code>
                    nach Ablauf der <code>duration</code> getriggert.
                </p>
                <p class="text-muted" style="font-size:12px"><strong>Stationen:</strong></p>
                <ol style="font-size:13px;padding-left:18px;line-height:1.9">
                    <li>Einflug Deutschland &rarr; Berlin</li>
                    <li>Brandenburger Tor</li>
                    <li>Reichstag &amp; Bundestag</li>
                    <li>Humboldt Forum</li>
                    <li>Berliner Dom</li>
                    <li>Fernsehturm Alexanderplatz</li>
                    <li>Potsdamer Platz</li>
                    <li>East Side Gallery</li>
                    <li>Auszoomen &rarr; Stadtübersicht</li>
                </ol>
                <pre style="font-size:11px;line-height:1.6">// Waypoints definieren
const waypoints = [
  { center:[13.3777,52.5163],
    zoom:16.5, pitch:65,
    bearing:-30, duration:5000,
    label:'Brandenburger Tor' },
  { center:[13.3761,52.5186],
    zoom:17, pitch:70,
    bearing:40, duration:4000,
    label:'Reichstag' },
  // ... weitere Stationen
];

// Sequenz abspielen
let step = 0;
const fly = () => {
  if (step >= waypoints.length) return;
  const wp = waypoints[step++];
  map.flyTo({
    center: wp.center, zoom: wp.zoom,
    pitch: wp.pitch, bearing: wp.bearing,
    duration: wp.duration, essential: true
  });
  setTimeout(fly, wp.duration + 600);
};
setTimeout(fly, 800);</pre>
            </div>
            <div class="col-md-7">
                <vectormap id="vm-overfly-map"
                    center="52.52,13.405"
                    zoom="5"
                    pitch="20"
                    3d
                    height="480">
                </vectormap>
                <div style="margin-top:8px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                    <button id="vm-overfly-play"
                            class="btn btn-primary btn-sm"
                            style="display:flex;align-items:center;gap:6px">
                        &#9654; Überflug starten
                    </button>
                    <span id="vm-overfly-status"
                          style="font-size:13px;color:#555;font-style:italic"></span>
                </div>
                <p class="text-muted" style="font-size:12px;margin-top:6px">
                    Karte startet automatisch beim Einscrollen.
                    Karte vollständig interaktiv &mdash; während oder nach dem Überflug frei navigierbar.
                    3D-Gebäude nur ab Zoom&nbsp;14+ sichtbar &mdash; werden live aus OpenFreeMap-Vektorkacheln gerendert.
                </p>
            </div>
        </div>

    </div>
</div>

<!-- ================================================================
     DEMO 11: E-Ladestationen dynamisch nachladen (Overpass API / OSM)
     ================================================================ -->
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="fa fa-bolt" aria-hidden="true"></i>
            Demo 11: E-Ladestationen &mdash; dynamisch nach Kartenposition nachladen
            <small class="text-muted" style="font-size:12px">&mdash; Overpass API (OSM), kein API-Key, DSGVO-konform via Proxy</small>
        </h3>
    </div>
    <div class="panel-body">

        <div class="alert alert-info" style="margin-bottom:20px">
            <strong>Live-Demo:</strong> Karte bewegen oder zoomen → E-Ladesäulen im aktuellen Kartenausschnitt werden automatisch nachgeladen.
            Daten aus <strong>OpenStreetMap via Overpass API</strong> &mdash; kostenlos, kein API-Key, Server in Deutschland.
            Ab <strong>Zoom-Stufe&nbsp;9</strong> werden Stationen geladen (max.&nbsp;200 pro Anfrage, Klick für Details).
        </div>

        <div class="row">
            <div class="col-md-5">
                <h4 style="margin-top:0">Funktionsprinzip</h4>
                <p class="text-muted" style="font-size:13px">
                    Die Karte lauscht auf das <code>moveend</code>-Event von MapLibre.
                    Bei jeder Kartenbewegung fragt das Script die Overpass API
                    für den aktuellen Bounding Box ab; die Ergebnisse werden direkt
                    in eine MapLibre-GeoJSON-Source geschrieben &mdash; ohne Seitenreload.
                </p>
                <pre style="font-size:11px;line-height:1.6">// 1. GeoJSON-Source + Layer anlegen
map.addSource('cs', {
  type: 'geojson',
  data: { type:'FeatureCollection', features:[] }
});
map.addLayer({
  id:'cs-dots', type:'circle', source:'cs',
  paint:{ 'circle-color':'#27ae60',
          'circle-stroke-color':'#fff',
          'circle-stroke-width':2 }
});

// 2. Bei Kartenbewegung neu laden (debounced)
map.on('moveend', async () => {
  const b  = map.getBounds();
  const q  = `[out:json][timeout:10];`
    + `node["amenity"="charging_station"]`
    + `(${b.getSouth()},${b.getWest()},`
    + ` ${b.getNorth()},${b.getEast()});`
    + `out 200;`;
  const url = proxyUrl(
    'https://overpass-api.de/api/interpreter'
    + '?data=' + encodeURIComponent(q)
  );
  const data = await fetch(url).then(r => r.json());
  const features = data.elements.map(n => ({
    type: 'Feature',
    geometry: { type:'Point',
                coordinates:[n.lon, n.lat] },
    properties: n.tags
  }));
  map.getSource('cs').setData(
    { type:'FeatureCollection', features }
  );
});</pre>
                <div class="alert alert-success" style="margin-bottom:0">
                    <strong>Proxy-Whitelist:</strong>
                    <code>https://overpass-api.de/</code> ist bereits in <code>lib/Proxy.php</code>
                    eingetragen &mdash; sofort einsatzbereit.
                </div>
            </div>
            <div class="col-md-7">
                <div style="position:relative">
                    <vectormap id="vm-cs-map"
                        center="51.165,10.45"
                        zoom="7"
                        height="430">
                    </vectormap>
                    <div id="vm-cs-loading"
                         style="display:none;position:absolute;top:10px;right:10px;
                                background:rgba(255,255,255,.92);border-radius:4px;
                                padding:5px 12px;font-size:13px;z-index:100;
                                box-shadow:0 1px 4px rgba(0,0,0,.2)">
                        Lade&hellip;
                    </div>
                </div>
                <div style="margin-top:8px;display:flex;justify-content:space-between;
                            align-items:center;font-size:12px;flex-wrap:wrap;gap:4px">
                    <span class="text-muted" id="vm-cs-count">Näher heranzoomen zum Laden (ab Zoom&nbsp;9)</span>
                    <span class="text-muted">
                        Quelle: <a href="https://www.openstreetmap.org" target="_blank" rel="noopener">OpenStreetMap</a>
                        &mdash; <a href="https://overpass-api.de" target="_blank" rel="noopener">Overpass API</a>
                        &mdash; Lizenz: <a href="https://opendatacommons.org/licenses/odbl/" target="_blank" rel="noopener">ODbL</a>
                    </span>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="rex-icon rex-icon-code"></i>
            Tile-Server Beispiele &amp; Code-Snippets
        </h3>
    </div>
    <div class="panel-body">

        <p class="text-muted" style="margin-bottom:16px">
            MapLibre GL JS funktioniert mit verschiedenen Tile-Servern. Hier sind fertige Initialisierungs-Snippets für die gängigsten Anbieter.
        </p>

        <ul class="nav nav-tabs" role="tablist" style="margin-bottom:0">
            <li role="presentation" class="active"><a href="#vmt-tab-ofm"   data-toggle="tab">OpenFreeMap&nbsp;<span class="label label-success">kostenlos</span></a></li>
            <li role="presentation"><a href="#vmt-tab-maptiler"             data-toggle="tab">MapTiler Cloud</a></li>
            <li role="presentation"><a href="#vmt-tab-pmtiles"              data-toggle="tab">Protomaps/PMTiles</a></li>
            <li role="presentation"><a href="#vmt-tab-stadia"               data-toggle="tab">Stadia Maps</a></li>
            <li role="presentation"><a href="#vmt-tab-osm"                  data-toggle="tab">OSM Raster</a></li>
        </ul>

        <div class="tab-content" style="border:1px solid #ddd;border-top:none;padding:20px;background:#fafafa;border-radius:0 0 4px 4px">

            <!-- OpenFreeMap -->
            <div role="tabpanel" class="tab-pane active" id="vmt-tab-ofm">
                <h4 style="margin-top:0">OpenFreeMap via REDAXO-Proxy <small class="text-success">Kein API-Key · DSGVO-konform</small></h4>
                <p>OpenFreeMap stellt kostenlose Vektorkacheln auf Basis von OpenStreetMap-Daten bereit. Der REDAXO-Proxy leitet alle Anfragen durch den Server – keine direkte Verbindung vom Browser.</p>
                <pre style="margin:0;font-size:12px;line-height:1.6"><code>&lt;!-- MapLibre CSS + JS einbinden --&gt;
&lt;link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4/dist/maplibre-gl.css"&gt;
&lt;script src="https://unpkg.com/maplibre-gl@4/dist/maplibre-gl.js"&gt;&lt;/script&gt;

&lt;div id="map" style="width:100%;height:400px"&gt;&lt;/div&gt;

&lt;script&gt;
// Proxy-URL-Helper (alle Tiles laufen durch REDAXO)
function proxyUrl(url) {
    return window.location.origin
        + '/?rex_api_vector_maps_proxy=1&amp;target_url='
        + encodeURIComponent(url);
}

const map = new maplibregl.Map({
    container: 'map',
    style: proxyUrl('https://tiles.openfreemap.org/styles/liberty'),
    center: [10.45, 51.17],
    zoom: 6,
    transformRequest: (url) =&gt; {
        // Alle OFM-Ressourcen (Tiles, Fonts, Sprites) proxyen
        if (url.includes('tiles.openfreemap.org/')) {
            return { url: proxyUrl(url) };
        }
        return { url };
    }
});

// Verfügbare Styles: liberty | bright | positron
&lt;/script&gt;</code></pre>
                <p class="text-muted" style="margin-top:8px;font-size:12px">
                    Styles: <code>liberty</code>, <code>bright</code>, <code>positron</code> &middot;
                    <a href="https://openfreemap.org" target="_blank" rel="noopener">openfreemap.org</a>
                </p>
            </div>

            <!-- MapTiler -->
            <div role="tabpanel" class="tab-pane" id="vmt-tab-maptiler">
                <h4 style="margin-top:0">MapTiler Cloud <small class="text-muted">API-Key erforderlich</small></h4>
                <p>MapTiler bietet hochwertige Stile und weltweite Tile-Abdeckung. Kostenloser Plan mit 100.000 Map-Loads/Monat. API-Key unter <a href="https://cloud.maptiler.com" target="_blank" rel="noopener">cloud.maptiler.com</a> erstellen. Durch den REDAXO-Proxy bleibt der API-Key serverseitig und Besucher-IPs erreichen MapTiler nicht.</p>
                <pre style="margin:0;font-size:12px;line-height:1.6"><code>&lt;link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4/dist/maplibre-gl.css"&gt;
&lt;script src="https://unpkg.com/maplibre-gl@4/dist/maplibre-gl.js"&gt;&lt;/script&gt;

&lt;div id="map" style="width:100%;height:400px"&gt;&lt;/div&gt;

&lt;script&gt;
const MAPTILER_KEY = 'DEIN_API_KEY'; // https://cloud.maptiler.com

// Proxy-URL-Helper: alle Requests laufen durch REDAXO,
// der API-Key verlässt nie den Browser des Besuchers
function proxyUrl(url) {
    return window.location.origin
        + '/?rex_api_vector_maps_proxy=1&amp;target_url='
        + encodeURIComponent(url);
}

const map = new maplibregl.Map({
    container: 'map',
    // Style-JSON wird proxied, darin enthaltene URLs werden via transformRequest ebenfalls proxied
    style: proxyUrl(`https://api.maptiler.com/maps/streets/style.json?key=${MAPTILER_KEY}`),
    center: [10.45, 51.17],
    zoom: 6,
    transformRequest: (url) =&gt; {
        // Tiles, Sprites, Fonts – alles über den Proxy
        if (url.includes('api.maptiler.com/')) {
            return { url: proxyUrl(url) };
        }
        return { url };
    }
});

map.addControl(new maplibregl.NavigationControl());

// Weitere Styles (ebenfalls proxied):
// maps/satellite, maps/topo, maps/outdoor, maps/dataviz-dark
&lt;/script&gt;</code></pre>
                <p class="text-muted" style="margin-top:8px;font-size:12px">
                    <strong>DSGVO:</strong> Alle Anfragen laufen durch den REDAXO-Proxy – Besucher-IPs und der API-Key bleiben serverseitig.
                </p>
            </div>

            <!-- Protomaps/PMTiles -->
            <div role="tabpanel" class="tab-pane" id="vmt-tab-pmtiles">
                <h4 style="margin-top:0">Protomaps / PMTiles <small class="text-success">Self-hosted · Tiles lokal</small></h4>
                <p>PMTiles ist ein einzelnes Archiv-Format, das alle Kacheln enthält. Die Tile-Datei liegt auf deinem Server – kein externer Tile-Server nötig. Lediglich die Schriftarten (Glyphs) werden über den REDAXO-Proxy von Protomaps geladen.</p>
                <pre style="margin:0;font-size:12px;line-height:1.6"><code>&lt;!-- MapLibre + PMTiles-Plugin --&gt;
&lt;link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4/dist/maplibre-gl.css"&gt;
&lt;script src="https://unpkg.com/maplibre-gl@4/dist/maplibre-gl.js"&gt;&lt;/script&gt;
&lt;script src="https://unpkg.com/pmtiles@3/dist/pmtiles.js"&gt;&lt;/script&gt;

&lt;div id="map" style="width:100%;height:400px"&gt;&lt;/div&gt;

&lt;script&gt;
// PMTiles-Protokoll aktivieren
const protocol = new pmtiles.Protocol();
maplibregl.addProtocol('pmtiles', protocol.tile);

// .pmtiles-Datei liegt im REDAXO-Medienpool:
// Download z.B.: https://maps.protomaps.com/builds/
const tilesUrl = 'pmtiles:///media/germany.pmtiles';

function proxyUrl(url) {
    return window.location.origin
        + '/?rex_api_vector_maps_proxy=1&amp;target_url='
        + encodeURIComponent(url);
}

const map = new maplibregl.Map({
    container: 'map',
    style: {
        version: 8,
        // Schriftarten extern – durch Proxy geladen (DSGVO)
        glyphs: proxyUrl('https://cdn.protomaps.com/fonts/pbf/{fontstack}/{range}.pbf'),
        sources: {
            protomaps: {
                type: 'vector',
                url: tilesUrl,
                attribution: '&amp;copy; OpenStreetMap'
            }
        },
        layers: [
            {
                id: 'background',
                type: 'background',
                paint: { 'background-color': '#e0e0e0' }
            },
            {
                id: 'water',
                source: 'protomaps',
                'source-layer': 'water',
                type: 'fill',
                paint: { 'fill-color': '#9bc4d4' }
            },
            {
                id: 'roads',
                source: 'protomaps',
                'source-layer': 'roads',
                type: 'line',
                paint: { 'line-color': '#fff', 'line-width': 1 }
            }
            // weitere Layer nach Bedarf...
        ]
    },
    center: [10.45, 51.17],
    zoom: 6,
    transformRequest: (url) =&gt; {
        // Externe Glyph-Anfragen (cdn.protomaps.com) proxied
        if (url.includes('cdn.protomaps.com/')) {
            return { url: proxyUrl(url) };
        }
        // pmtiles:// liegt lokal – kein Proxy nötig
        return { url };
    }
});
&lt;/script&gt;</code></pre>
                <p class="text-muted" style="margin-top:8px;font-size:12px">
                    PMTiles-Downloads: <a href="https://maps.protomaps.com/builds/" target="_blank" rel="noopener">maps.protomaps.com/builds</a> &middot;
                    Plugin: <a href="https://github.com/protomaps/PMTiles" target="_blank" rel="noopener">github.com/protomaps/PMTiles</a>
                </p>
            </div>

            <!-- Stadia Maps -->
            <div role="tabpanel" class="tab-pane" id="vmt-tab-stadia">
                <h4 style="margin-top:0">Stadia Maps <small class="text-muted">kostenlos bis 200.000 Kacheln/Monat</small></h4>
                <p>Stadia Maps (ehemals Stamen) bietet hochwertige Raster- und Vektorkacheln. Der Free-Plan erlaubt nicht-kommerzielle Nutzung ohne API-Key. Mit dem REDAXO-Proxy wird die Besucher-IP niemals an Stadia übertragen.</p>
                <pre style="margin:0;font-size:12px;line-height:1.6"><code>&lt;link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4/dist/maplibre-gl.css"&gt;
&lt;script src="https://unpkg.com/maplibre-gl@4/dist/maplibre-gl.js"&gt;&lt;/script&gt;

&lt;div id="map" style="width:100%;height:400px"&gt;&lt;/div&gt;

&lt;script&gt;
function proxyUrl(url) {
    return window.location.origin
        + '/?rex_api_vector_maps_proxy=1&amp;target_url='
        + encodeURIComponent(url);
}

// Stadia Vektor-Style – Style-JSON wird proxied,
// Tiles/Fonts/Sprites via transformRequest ebenfalls
const map = new maplibregl.Map({
    container: 'map',
    // Weitere Styles: alidade_smooth_dark, outdoors, stamen_watercolor
    style: proxyUrl('https://tiles.stadiamaps.com/styles/alidade_smooth.json'),
    center: [10.45, 51.17],
    zoom: 6,
    transformRequest: (url) =&gt; {
        if (url.includes('tiles.stadiamaps.com/') || url.includes('stadiamaps.com/')) {
            return { url: proxyUrl(url) };
        }
        return { url };
    }
});

map.addControl(new maplibregl.NavigationControl());

// Alternative: Raster-Tiles via Proxy
// const map = new maplibregl.Map({
//     container: 'map',
//     style: {
//         version: 8,
//         sources: {
//             stadia: {
//                 type: 'raster',
//                 tiles: ['https://tiles.stadiamaps.com/tiles/alidade_smooth/{z}/{x}/{y}@2x.png'],
//                 tileSize: 512,
//                 attribution: '&amp;copy; Stadia Maps &amp;copy; OpenMapTiles &amp;copy; OpenStreetMap'
//             }
//         },
//         layers: [{ id: 'bg', type: 'raster', source: 'stadia' }]
//     },
//     transformRequest: (url) =&gt; {
//         if (url.includes('tiles.stadiamaps.com/')) return { url: proxyUrl(url) };
//         return { url };
//     }
// });
&lt;/script&gt;</code></pre>
                <p class="text-muted" style="margin-top:8px;font-size:12px">
                    <strong>DSGVO:</strong> Alle Anfragen laufen durch den REDAXO-Proxy – kein direkter Kontakt zwischen Besucher und Stadia-Servern. &middot;
                    <a href="https://docs.stadiamaps.com/map-styles/" target="_blank" rel="noopener">Alle Styles</a>
                </p>
            </div>

            <!-- OSM Raster -->
            <div role="tabpanel" class="tab-pane" id="vmt-tab-osm">
                <h4 style="margin-top:0">OpenStreetMap Raster-Tiles <small class="text-muted">Community-Server · kein API-Key</small></h4>
                <p>Raster-Tiles aus OpenStreetMap. MapLibre löst die <code>{z}/{x}/{y}</code>-Template-URL auf und ruft <code>transformRequest</code> auf – so werden alle Tile-Requests automatisch durch den REDAXO-Proxy geleitet.</p>
                <pre style="margin:0;font-size:12px;line-height:1.6"><code>&lt;link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4/dist/maplibre-gl.css"&gt;
&lt;script src="https://unpkg.com/maplibre-gl@4/dist/maplibre-gl.js"&gt;&lt;/script&gt;

&lt;div id="map" style="width:100%;height:400px"&gt;&lt;/div&gt;

&lt;script&gt;
function proxyUrl(url) {
    return window.location.origin
        + '/?rex_api_vector_maps_proxy=1&amp;target_url='
        + encodeURIComponent(url);
}

// MapLibre ersetzt {z}/{x}/{y} zuerst auf die konkreten Tile-URLs,
// dann greift transformRequest und leitet sie durch den Proxy
const map = new maplibregl.Map({
    container: 'map',
    style: {
        version: 8,
        sources: {
            osm: {
                type: 'raster',
                // Template-URL bleibt unverändert – MapLibre löst sie auf
                tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                tileSize: 256,
                attribution: '&amp;copy; &lt;a href="https://www.openstreetmap.org/copyright"&gt;OpenStreetMap&lt;/a&gt;'
            }
        },
        layers: [{
            id: 'osm-tiles',
            type: 'raster',
            source: 'osm',
            minzoom: 0,
            maxzoom: 19
        }]
    },
    center: [10.45, 51.17],
    zoom: 6,
    transformRequest: (url) =&gt; {
        // Konkrete Tile-URL (z.B. .../6/33/21.png) durch Proxy leiten
        if (url.startsWith('https://tile.openstreetmap.org/')) {
            return { url: proxyUrl(url) };
        }
        return { url };
    }
});

// Tipp: Für Produktion eigene Tile-Server verwenden:
// - tile.openstreetmap.fr   (FR-gehostet)
// - Oder: eigener TileServer via Docker (openmaptiles/openmaptiles-tools)
&lt;/script&gt;</code></pre>
                <p class="text-muted" style="margin-top:8px;font-size:12px">
                    <strong>Achtung:</strong> Nutzungsbedingungen von OpenStreetMap beachten –
                    <a href="https://operations.osmfoundation.org/policies/tiles/" target="_blank" rel="noopener">Tile Usage Policy</a>.
                    OSM-Community-Server nur für Entwicklung/Tests – für Produktion eigenen Server betreiben.
                </p>
            </div>

        </div><!-- /.tab-content -->

    </div>
</div>
