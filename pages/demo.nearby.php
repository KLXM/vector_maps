<?php
// Umgebungssuche - Beispiele 7a, 7b
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="rex-icon rex-icon-map-marker"></i>
            POI-Umgebungssuche <small class="text-muted" style="font-size:12px">&mdash; Overpass API via REDAXO-Proxy</small>
        </h3>
    </div>
    <div class="panel-body">
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
