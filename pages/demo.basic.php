<?php
// Grundkarten, Marker, Routing - Beispiele 1-6
?>
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
                        <tr><td><code>map-style</code></td><td><code>liberty</code></td><td><code>map-style="satellite"</code></td><td>Stil: <code>liberty</code> | <code>bright</code> | <code>positron</code> | <code>satellite</code> (ESRI World Imagery) oder gespeicherter Theme-Name</td></tr>
                        <tr><td><code>show-satellite</code></td><td><code>false</code></td><td><code>show-satellite</code></td><td>Satellitenbild-Toggle-Button einblenden (ESRI World Imagery, kein API-Key, Boolean-Attribut)</td></tr>
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
                        <tr><td><code>route-to-locked</code></td><td><code>false</code></td><td><code>route-to-locked</code></td><td>Zieladresse fixieren: Feld readonly, kein Autocomplete (Boolean-Attribut) &ndash; ideal für Anfahrtskarten mit festem Ziel</td></tr>
                        <tr><td><code>route-no-steps</code></td><td><code>false</code></td><td><code>route-no-steps</code></td><td>Abbiegehinweise (Turn-by-Turn) ausblenden (Boolean-Attribut)</td></tr>
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

        <!-- BEISPIEL 5d: Anfahrtskarte (route-to-locked + route-no-steps) -->
        <div class="row" style="margin-bottom:8px">
            <div class="col-md-12">
                <h4 style="margin-top:0">5d. Anfahrtskarte – fixiertes Ziel <span class="label label-success">route-to-locked</span> <span class="label label-default">route-no-steps</span></h4>
                <p class="text-muted" style="font-size:13px;margin-bottom:8px">Das Ziel ist fest vorgegeben (z.&nbsp;B. Firmenstandort). Der Nutzer gibt nur seinen Startpunkt ein &ndash; kein Autocomplete am Zielfeld, keine Abbiegehinweise.</p>
                <pre style="font-size:12px">&lt;vectormap
    lat="51.4298" lng="6.7742" zoom="11"
    height="420px"
    route-panel
    route-to="Duisburg Hauptbahnhof"
    route-to-locked
    route-no-steps&gt;
&lt;/vectormap&gt;</pre>
                <vectormap
                    lat="51.4298" lng="6.7742" zoom="11"
                    height="420px"
                    route-panel
                    route-to="Duisburg Hauptbahnhof"
                    route-to-locked
                    route-no-steps>
                </vectormap>
                <p class="text-muted" style="font-size:12px;margin-top:4px">Zieladresse per <code>route-to</code> vorgeben + <code>route-to-locked</code> sperrt das Feld. <code>route-no-steps</code> blendet Turn-by-Turn aus &mdash; ideal f&uuml;r Kontaktseiten.</p>
            </div>
        </div>

        <!-- BEISPIEL 5e: Satellitenbild (map-style + show-satellite) -->
        <div class="row" style="margin-bottom:8px">
            <div class="col-md-6">
                <h4 style="margin-top:0">5e. Satellitenbild <span class="label label-success">map-style="satellite"</span></h4>
                <p class="text-muted" style="font-size:13px;margin-bottom:8px">ESRI World Imagery &ndash; kein API-Key, kostenlos, Attribution automatisch. Marker &uuml;berleben den Style-Wechsel.</p>
                <pre style="font-size:12px">&lt;vectormap
    lat="48.858844" lng="2.294351" zoom="15"
    height="340"
    map-style="satellite"&gt;
  &lt;marker lat="48.858844" lng="2.294351"
          popup="Eiffelturm aus der Vogelperspektive" /&gt;
&lt;/vectormap&gt;</pre>
                <vectormap
                    lat="48.858844" lng="2.294351" zoom="15"
                    height="340"
                    map-style="satellite">
                    <marker lat="48.858844" lng="2.294351" popup="Eiffelturm aus der Vogelperspektive"></marker>
                </vectormap>
            </div>
            <div class="col-md-6">
                <h4 style="margin-top:0">5f. Vektor + Satellite Toggle <span class="label label-success">show-satellite</span></h4>
                <p class="text-muted" style="font-size:13px;margin-bottom:8px">Mit <code>show-satellite</code> erscheint ein Knopf zum Umschalten zwischen Vektorkarte und Satellitenbild. Marker bleiben beim Wechsel erhalten.</p>
                <pre style="font-size:12px">&lt;vectormap
    lat="52.520008" lng="13.404954" zoom="13"
    height="340"
    show-satellite&gt;
  &lt;marker lat="52.520008" lng="13.404954"
          popup="Brandenburger Tor" /&gt;
&lt;/vectormap&gt;</pre>
                <vectormap
                    lat="52.520008" lng="13.404954" zoom="13"
                    height="340"
                    show-satellite>
                    <marker lat="52.520008" lng="13.404954" popup="Brandenburger Tor"></marker>
                </vectormap>
                <p class="text-muted" style="font-size:12px;margin-top:4px">Satellite-Button unten rechts (&#x1F6F0;) &mdash; Satel&shy;litenbild via ESRI World Imagery, kein API-Key ben&ouml;tigt.</p>
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

    </div>
</div>
