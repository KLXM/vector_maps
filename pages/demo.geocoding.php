<?php
// Geocoding, Picker und Integrationen
?>
<?php
use KLXM\VectorMaps\BackendHero;

echo BackendHero::renderCompact(
    'geocoding',
    'Vector Maps · Geocoding',
    'Picker, YForm und Builder',
    'Hier geht es ausschließlich um Koordinatenwahl, Adresssuche und die Integration in Formulare und Builder-Elemente.',
    ['Adresssuche', 'Picker', 'YForm', 'Builder']
);
?>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="rex-icon rex-icon-map-marker"></i>
            Geocoding &amp; Picker
        </h3>
    </div>
    <div class="panel-body">
        <div class="alert alert-info" style="margin-bottom:18px">
            Diese Seite zeigt den Vector-Maps-Picker als allgemeinen Input-Ersatz, als YForm-Feld und als Grundlage für Builder-Elemente.
            Die Adresssuche läuft über Nominatim via REDAXO-Proxy, die Kartenstile über OpenFreeMap und optionale Themes aus dem AddOn.
        </div>

        <div class="row" style="margin-bottom:22px">
            <div class="col-md-6">
                <h4 style="margin-top:0">1. Allgemeiner Input-Picker</h4>
                <p class="text-muted" style="font-size:13px">Ein normales Input-Feld reicht aus. Der Picker setzt Koordinaten nach Klick auf Karte oder per Adresssuche.</p>
                <pre style="font-size:12px">&lt;input type="text"
  name="location"
  class="form-control"
  data-vector-picker="1"
  data-vector-picker-style="bright"
  data-vector-picker-theme="warm"
  value="52.520008,13.404954"&gt;</pre>

                <div class="form-group">
                    <label for="vm-geocode-demo-1">Standard mit Bright + Warm</label>
                    <input type="text"
                           id="vm-geocode-demo-1"
                           class="form-control"
                           data-vector-picker="1"
                           data-vector-picker-style="bright"
                           data-vector-picker-theme="warm"
                           data-vector-picker-themes='{"dark":"Dark","redaxo":"REDAXO","bright":"Bright","warm":"Warm","mono":"Mono"}'
                           value="52.520008,13.404954">
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label for="vm-geocode-demo-2">Satellit ohne Theme</label>
                    <input type="text"
                           id="vm-geocode-demo-2"
                           class="form-control"
                           data-vector-picker="1"
                           data-vector-picker-style="satellite"
                           data-vector-picker-theme=""
                           data-vector-picker-themes='{"dark":"Dark","redaxo":"REDAXO","bright":"Bright","warm":"Warm","mono":"Mono"}'
                           placeholder="48.137154,11.576124">
                </div>
                <p class="help-block" style="margin-bottom:0">Bei <code>satellite</code> wird die Theme-Auswahl im Picker deaktiviert, da Themes nur auf Vektorstile wirken.</p>
            </div>
            <div class="col-md-6">
                <h4 style="margin-top:0">2. PHP-Helper</h4>
                <p class="text-muted" style="font-size:13px">Für Backend- oder Frontend-Formulare kann der Picker direkt per PHP erzeugt werden.</p>
                <pre style="font-size:12px">&lt;?php
echo \KLXM\VectorMaps\Picker\PickerWidget::factory('location')
    -&gt;setValue('52.520008,13.404954')
    -&gt;setMapStyle('bright')
    -&gt;setTheme('warm')
    -&gt;parse();</pre>

                <div class="well well-sm" style="margin-bottom:10px">
                    <?= \KLXM\VectorMaps\Picker\PickerWidget::factory('vm_demo_php_picker', 'vm-demo-php-picker')
                        ->setValue('50.110924,8.682127')
                        ->setMapStyle('positron')
                        ->setTheme('redaxo')
                        ->parse() ?>
                </div>

                <p class="text-muted" style="font-size:12px;margin-bottom:0">Auch hier ist die Theme-Auswahl im Modal verfügbar. Gespeichert wird immer ein einfacher <code>lat,lng</code>-String.</p>
            </div>
        </div>

        <div class="row" style="margin-bottom:22px">
            <div class="col-md-6">
                <h4 style="margin-top:0">3. YForm-Value</h4>
                <p class="text-muted" style="font-size:13px">Das Feld <code>vector_map_location</code> rendert denselben Picker direkt im YForm-Formular.</p>
                <pre style="font-size:12px">value|vector_map_location|location|Standort|liberty|warm|1</pre>
                <pre style="font-size:12px">value|vector_map_location|location|Standort|bright|redaxo|1|Bitte Position wählen</pre>
                <p class="text-muted" style="font-size:12px;margin-bottom:0">Parameter: <code>name|label|[map_style]|[theme]|[required]|[notice]</code></p>
            </div>
            <div class="col-md-6">
                <h4 style="margin-top:0">4. Builder-Element</h4>
                <p class="text-muted" style="font-size:13px">Das Element <strong>Vector Map</strong> nutzt einen eigenen Builder-Feldtyp für Koordinaten und liefert Templates für uikit, bootstrap und plain.</p>
                <pre style="font-size:12px">Element: Vector Map
- Koordinaten via Picker
- Kartenstil wählbar
- Theme wählbar
- Marker + Popup
- optionales Info-Fenster
- Controls / Locate / Satellit / Fullscreen</pre>
                <p class="text-muted" style="font-size:12px;margin-bottom:0">Damit ersetzt das AddOn in vielen Fällen die bisherige Geolocation-Lösung für einfache Standort- und Adress-Anwendungsfälle.</p>
            </div>
        </div>

        <div class="panel panel-default" style="margin-bottom:0">
            <div class="panel-heading">
                <h3 class="panel-title">5. Ausgabe-Beispiel aus gewählten Koordinaten</h3>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <pre style="font-size:12px">&lt;vectormap
  center="52.520008,13.404954"
  zoom="13"
  height="320"
  locate
  show-satellite
  theme="warm"
  markers='[{"lat":52.520008,"lng":13.404954,
    "popup":"&lt;strong&gt;Berlin&lt;/strong&gt;"}]'&gt;
&lt;/vectormap&gt;</pre>
                    </div>
                    <div class="col-md-6">
                        <vectormap
                            center="52.520008,13.404954"
                            zoom="13"
                            height="320"
                            locate
                            show-satellite
                            theme="warm"
                            markers='[{"lat":52.520008,"lng":13.404954,"popup":"<strong>Berlin</strong><br>Beispiel aus Picker-Koordinaten"}]'>
                        </vectormap>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>