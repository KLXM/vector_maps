<?php
// Design-Skins & UI-Styling Demos
?>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="rex-icon rex-icon-palette"></i>
            Design-Skins & UI-Styling
        </h3>
    </div>
    <div class="panel-body">
        <div class="alert alert-info" style="margin-bottom:16px">
            Diese Seite zeigt copy-paste-fertige Presets für <code>&lt;vectormap&gt;</code>.
            Fokus: Button-Position/Styling, globale Infofenster und unterschiedliche Route-Panel-Designs.
            Die Kartenhöhen sind bewusst großzügiger gewählt, damit Routing-Panel und Strecke besser wirken.
        </div>

        <style>
        vectormap.vm-skin-editorial {
            --vm-widget-size: 34px;
            --vm-widget-radius: 12px;
            --vm-widget-bg: #0f172a;
            --vm-widget-color: #e2e8f0;
            --vm-widget-hover-bg: #1e293b;
            --vm-widget-hover-color: #93c5fd;
            --vm-widget-active-bg: #2563eb;
            --vm-widget-active-color: #ffffff;

            --vm-info-bg: rgba(15, 23, 42, .88);
            --vm-info-color: #e2e8f0;
            --vm-info-radius: 14px;
            --vm-info-shadow: 0 14px 28px rgba(0, 0, 0, .32);
            --vm-info-max-width: 290px;

            --vm-rp-accent: #60a5fa;
            --vm-rp-radius: 12px;
        }

        vectormap.vm-skin-sunset {
            --vm-widget-size: 32px;
            --vm-widget-radius: 10px;
            --vm-widget-bg: #2b1f1b;
            --vm-widget-color: #ffd9c4;
            --vm-widget-hover-bg: #3a2a24;
            --vm-widget-hover-color: #ffb690;
            --vm-widget-active-bg: #f97316;
            --vm-widget-active-color: #2b1f1b;

            --vm-info-bg: rgba(43, 31, 27, .9);
            --vm-info-color: #ffe8da;
            --vm-info-radius: 12px;
            --vm-info-shadow: 0 10px 24px rgba(34, 17, 10, .4);

            --vm-rp-accent: #f97316;
            --vm-rp-radius: 10px;
        }

        vectormap.vm-skin-mint {
            --vm-widget-size: 34px;
            --vm-widget-radius: 999px;
            --vm-widget-bg: #f1fff9;
            --vm-widget-color: #14532d;
            --vm-widget-hover-bg: #d1fae5;
            --vm-widget-hover-color: #065f46;
            --vm-widget-active-bg: #10b981;
            --vm-widget-active-color: #ffffff;

            --vm-info-bg: rgba(241, 255, 249, .95);
            --vm-info-color: #14532d;
            --vm-info-radius: 18px;
            --vm-info-shadow: 0 12px 26px rgba(16, 185, 129, .2);

            --vm-rp-accent: #10b981;
            --vm-rp-radius: 16px;
        }

        vectormap.vm-skin-nightglass {
            --vm-widget-size: 34px;
            --vm-widget-radius: 11px;
            --vm-widget-bg: rgba(14, 23, 35, .7);
            --vm-widget-color: #dbeafe;
            --vm-widget-hover-bg: rgba(30, 64, 175, .75);
            --vm-widget-hover-color: #dbeafe;
            --vm-widget-active-bg: #38bdf8;
            --vm-widget-active-color: #0c1b2d;
            --vm-widget-shadow: 0 10px 22px rgba(0, 0, 0, .34);

            --vm-info-bg: rgba(14, 23, 35, .72);
            --vm-info-color: #dbeafe;
            --vm-info-radius: 14px;
            --vm-info-shadow: 0 16px 32px rgba(0, 0, 0, .38);

            --vm-rp-accent: #67e8f9;
            --vm-rp-radius: 14px;
        }

        vectormap.vm-skin-route-modern {
            --vm-widget-size: 36px;
            --vm-widget-radius: 10px;
            --vm-widget-bg: #101828;
            --vm-widget-color: #dce7ff;
            --vm-widget-hover-bg: #1d2d4a;
            --vm-widget-hover-color: #9dc4ff;
            --vm-widget-active-bg: #2f6fed;
            --vm-widget-active-color: #ffffff;

            --vm-info-bg: rgba(16, 24, 40, .86);
            --vm-info-color: #e8efff;
            --vm-info-radius: 14px;

            --vm-rp-accent: #2f6fed;
            --vm-rp-accent-contrast: #ffffff;
            --vm-rp-radius: 14px;
        }
        </style>

        <div class="row" style="margin-bottom:20px">
            <div class="col-md-6">
                <h4 style="margin-top:0">1. Editorial Blue</h4>
                <pre style="font-size:11px">&lt;vectormap
  class="vm-skin-editorial"
  locate show-satellite
  controls-position="top-left"
  buttons-position="top-left"
  route-panel route-panel-style="brand"
  route-panel-position="top-right"
  info-position="bottom-left"
  info-html="&lt;strong&gt;Editorial Blue&lt;/strong&gt;&lt;br&gt;Klarer Corporate Look"&gt;
&lt;/vectormap&gt;</pre>
                <vectormap
                    class="vm-skin-editorial"
                    center="52.520008,13.404954"
                    zoom="12"
                    height="400"
                    locate
                    show-satellite
                    controls-position="top-left"
                    buttons-position="top-left"
                    route-panel
                    route-panel-style="brand"
                    route-panel-position="top-right"
                    route-from="Berlin HBF"
                    route-to="Alexanderplatz Berlin"
                    info-position="bottom-left"
                    info-html="<strong>Editorial Blue</strong><br>Klarer Corporate Look"
                    info-closable>
                </vectormap>
            </div>
            <div class="col-md-6">
                <h4 style="margin-top:0">2. Sunset Contrast</h4>
                <pre style="font-size:11px">&lt;vectormap
  class="vm-skin-sunset"
  locate show-satellite
  controls-position="bottom-right"
  buttons-position="bottom-left"
  route-panel route-panel-style="contrast"
  route-panel-position="bottom-right"
  info-position="top-right"&gt;
&lt;/vectormap&gt;</pre>
                <vectormap
                    class="vm-skin-sunset"
                    center="50.110924,8.682127"
                    zoom="12"
                    height="400"
                    locate
                    show-satellite
                    controls-position="bottom-right"
                    buttons-position="bottom-left"
                    route-panel
                    route-panel-style="contrast"
                    route-panel-position="bottom-right"
                    route-from="Frankfurt HBF"
                    route-to="Römerberg Frankfurt"
                    info-position="top-right"
                    info-html="<strong>Sunset Contrast</strong><br>Kräftige Kontraste mit warmem Akzent"
                    info-closable>
                </vectormap>
            </div>
        </div>

        <div class="row" style="margin-bottom:20px">
            <div class="col-md-6">
                <h4 style="margin-top:0">3. Mint Soft</h4>
                <pre style="font-size:11px">&lt;vectormap
  class="vm-skin-mint"
  locate show-satellite
  controls-position="top-right"
  buttons-position="bottom-right"
  route-panel route-panel-style="glass"
  route-panel-position="top-left"
  info-position="top-left"&gt;
&lt;/vectormap&gt;</pre>
                <vectormap
                    class="vm-skin-mint"
                    center="48.137154,11.576124"
                    zoom="12"
                    height="400"
                    locate
                    show-satellite
                    controls-position="top-right"
                    buttons-position="bottom-right"
                    route-panel
                    route-panel-style="glass"
                    route-panel-position="top-left"
                    route-from="Marienplatz München"
                    route-to="Englischer Garten München"
                    info-position="top-left"
                    info-html="<strong>Mint Soft</strong><br>Runde Buttons, leichte UI"
                    info-closable>
                </vectormap>
            </div>
            <div class="col-md-6">
                <h4 style="margin-top:0">4. Night Glass</h4>
                <pre style="font-size:11px">&lt;vectormap
  class="vm-skin-nightglass"
  locate show-satellite
  controls-position="top-right"
  buttons-position="top-right"
  route-panel route-panel-style="glass"
  route-panel-position="bottom-left"
  info-position="top-left"&gt;
&lt;/vectormap&gt;</pre>
                <vectormap
                    class="vm-skin-nightglass"
                    center="53.551086,9.993682"
                    zoom="12"
                    height="400"
                    locate
                    show-satellite
                    controls-position="top-right"
                    buttons-position="top-right"
                    route-panel
                    route-panel-style="glass"
                    route-panel-position="bottom-left"
                    route-from="Hamburg HBF"
                    route-to="Landungsbrücken Hamburg"
                    info-position="top-left"
                    info-html="<strong>Night Glass</strong><br>Transparente Overlays und kalte Akzente"
                    info-closable>
                </vectormap>
            </div>
        </div>

        <div class="row" style="margin-bottom:20px">
            <div class="col-md-12">
                <h4 style="margin-top:0">5. Routing-Variante: Modern Brand</h4>
                <p class="text-muted" style="font-size:13px;margin-bottom:8px">Alternative Stilrichtung nur fürs Routing, mit größerer Fläche und klaren Kontrasten.</p>
                <pre style="font-size:11px">&lt;vectormap
  class="vm-skin-route-modern"
  center="51.165691,10.451526"
  zoom="6"
  height="430"
  locate
  show-satellite
  route-panel
  route-panel-style="brand"
  route-panel-position="bottom-left"
  route-from="Berlin HBF"
  route-to="Leipzig Hauptbahnhof"
  info-position="top-right"
  info-html="&lt;strong&gt;Modern Routing&lt;/strong&gt;&lt;br&gt;Neue Stilvariante"&gt;
&lt;/vectormap&gt;</pre>
                <vectormap
                    class="vm-skin-route-modern"
                    center="51.165691,10.451526"
                    zoom="6"
                    height="430"
                    locate
                    show-satellite
                    route-panel
                    route-panel-style="brand"
                    route-panel-position="bottom-left"
                    route-from="Berlin HBF"
                    route-to="Leipzig Hauptbahnhof"
                    info-position="top-right"
                    info-html="<strong>Modern Routing</strong><br>Neue Stilvariante"
                    info-closable>
                </vectormap>
            </div>
        </div>

        <div class="alert alert-success" style="margin-bottom:14px">
            <strong>JS-API zur Laufzeit:</strong>
            <code>el.setInfoHtml(...)</code>, <code>el.setInfoPosition(...)</code>, <code>el.showInfoWindow()</code>, <code>el.hideInfoWindow()</code>
            &mdash; mit <code>el = document.getElementById('deine-karte')</code>.
        </div>

        <hr>

        <h4 style="margin-top:0">6. Zwei funktionierende Infofenster-Varianten (Code-Auszug)</h4>
        <div class="row" style="margin-bottom:14px">
            <div class="col-md-6">
                <h5 style="margin-top:0">A) Attribut-Variante (<code>info-html</code>)</h5>
                <pre style="font-size:11px">&lt;vectormap
  center="51.43,6.77"
  zoom="12"
  height="380"
  info-position="top-left"
  info-html="&lt;strong&gt;Service&lt;/strong&gt;&lt;br&gt;Mo-Fr 08:00-18:00"
  info-closable&gt;
&lt;/vectormap&gt;</pre>
            </div>
            <div class="col-md-6">
                <h5 style="margin-top:0">B) Child-Variante (<code>.mapinfo</code>)</h5>
                <pre style="font-size:11px">&lt;vectormap center="51.43,6.77" zoom="12" height="380"&gt;
  &lt;div class="mapinfo" data-position="top-left" data-closable&gt;
    &lt;strong&gt;Kontakt&lt;/strong&gt;&lt;br&gt;Mo-Fr 08:00-18:00
  &lt;/div&gt;
&lt;/vectormap&gt;</pre>
            </div>
        </div>

        <h4 style="margin-top:0">7. Inline-Variante: Infofenster als Child-Element <code>.mapinfo</code></h4>
        <p class="text-muted" style="font-size:13px;margin-bottom:8px">
            Diese Variante funktioniert ohne <code>info-html</code>-Attribut direkt im Markup der Karte.
            Optional: <code>data-position</code>, <code>data-class</code>, <code>data-closable</code>.
        </p>
        <div class="row">
            <div class="col-md-6">
                <pre style="font-size:11px">&lt;vectormap center="51.43,6.77" zoom="12" height="320"&gt;
  &lt;div class="mapinfo" data-position="top-left" data-closable&gt;
    &lt;strong&gt;Kontakt&lt;/strong&gt;&lt;br&gt;
    Mo-Fr 08:00-18:00&lt;br&gt;
    &lt;a href="/kontakt"&gt;Zur Kontaktseite&lt;/a&gt;
  &lt;/div&gt;
&lt;/vectormap&gt;</pre>
            </div>
            <div class="col-md-6">
                <vectormap center="51.43,6.77" zoom="12" height="380" class="vm-skin-editorial">
                    <div class="mapinfo" data-position="top-left" data-closable>
                        <strong>Kontakt</strong><br>
                        Mo-Fr 08:00-18:00<br>
                        <a href="#" onclick="return false;">Zur Kontaktseite</a>
                    </div>
                    <marker lat="51.4298" lng="6.7742" popup="Duisburg HBF"></marker>
                </vectormap>
            </div>
        </div>
    </div>
</div>
