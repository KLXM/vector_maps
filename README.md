# Vector Maps – REDAXO AddOn

Interaktive Vektorkarten für REDAXO – datenschutzkonform, ohne API-Key, vollständig selbst gehostet.

![Vector Maps Screenshot](https://raw.githubusercontent.com/KLXM/vector_maps/assets/screen.png)

---

## Features

- **Kein API-Key erforderlich** – nutzt [OpenFreeMap](https://openfreemap.org/) (freie Vektorkacheln)
- **DSGVO-konform** – externer Datenverkehr wird vollständig über einen PHP-Proxy geleitet
- **Web Component** `<vectormap>` – direkt im Template oder Modul verwendbar, kein JavaScript nötig
- **Lazy-Init + Build-Queue** – WebGL-Kontext wird sequentiell für jede Karte aufgebaut, erst wenn sie in den Viewport scrollt (verhindert Browserlimit von ~10 parallelen WebGL-Kontexten)
- **Backend-Koordinatenpicker** – Koordinatenfelder in REDAXO-Modulen mit Karte und Adresssuche
- **Routing** – Koordinaten oder Adressen, mit Auto-, Fuß- und Fahrradmodus (OSRM)
- **Interaktives Routenpanel** – Von/Nach-Adressen direkt auf der Karte eingeben (Autocomplete)
- **Umgebungssuche (nearby)** – POI-Suche via Overpass API (z. B. Ladestationen, Restaurants)
- **Dynamisches Datennachladen** – GeoJSON-Source per `moveend` aktualisieren (z. B. Ladestationen live aus OSM)
- **Freie Wetter-APIs** – Open-Meteo und Bright Sky (DWD) via Proxy, kein API-Key, Badge-Marker mit Live-Daten
- **Standortsuche** – optionale Geolokalisierung des Nutzers als Ausgangspunkt
- **Themes** – `dark`, `warm`, `mono` eingebaut, frei erweiterbar via Theme-Editor im Backend
- **3D-Gebäude** – aktivierbar per Attribut
- **Mehrsprachigkeit** – Sprachcode per `language`-Attribut, steuert Kartenbeschriftungen von OpenFreeMap
- **Cluster** – automatische Marker-Bündelung bei vielen Punkten
- **maxPitch 60°** – Kamerakippung auf 60° begrenzt (verhindert extreme Perspektiven)

---

## Installation

Über den REDAXO-Installer oder manuell:

1. AddOn-Verzeichnis nach `redaxo/src/addons/vector_maps/` kopieren
2. Im REDAXO-Backend unter **Installer > Eigene AddOns** installieren und aktivieren

Keine weiteren Abhängigkeiten oder Node-Build-Schritte notwendig.

---

## Frontend-Einbindung

### Automatisch (Standard)

Nach der Installation lädt das AddOn alle benötigten Assets **automatisch im Frontend** — keine manuelle Einbindung notwendig. Die CSS- und JS-Dateien werden von REDAXO im `<head>` / vor `</body>` ausgegeben:

```
maplibre-gl.css        → MapLibre GL JS Kern-Styles
vectormaps.css         → Web Component Styles + Dark Mode
maplibre-gl.js         → MapLibre GL JS Kern-Script
vectormaps.js          → <vectormap> Custom Element
```

Danach kann `<vectormap>` direkt in jedem Template, Modul oder Artikel verwendet werden:

```html
<!-- Karte einfach per Tag ausgeben – kein JS nötig -->
<vectormap lat="51.43" lng="6.77" zoom="13" height="400px"></vectormap>
```

### Manuell (wenn Auto-Loading deaktiviert)

Über **Vector Maps → Einstellungen** kann das automatische Laden deaktiviert werden (`load_frontend = false`). In diesem Fall müssen die Assets im Template manuell eingebunden werden:

```php
<?php
// In project/templates/template.php oder einem Fragment:
$vmAddon = rex_addon::get('vector_maps');
?>
<!DOCTYPE html>
<html>
<head>
    <!-- MapLibre GL JS Styles -->
    <link rel="stylesheet" href="<?= $vmAddon->getAssetsUrl('maplibre/maplibre-gl.css') ?>">
    <!-- Vector Maps Styles (Dark Mode, Custom Marker, Route-Panel …) -->
    <link rel="stylesheet" href="<?= $vmAddon->getAssetsUrl('build/vectormaps.css') ?>">
</head>
<body>

    <!-- Karten-Tags hier platzieren -->
    <vectormap lat="51.43" lng="6.77" zoom="13"></vectormap>

    <!-- MapLibre GL JS Core (defer empfohlen) -->
    <script defer src="<?= $vmAddon->getAssetsUrl('maplibre/maplibre-gl.js') ?>"></script>
    <!-- Vector Maps Web Component (defer empfohlen) -->
    <script defer src="<?= $vmAddon->getAssetsUrl('build/vectormaps.js') ?>"></script>
</body>
</html>
```

> **Hinweis:** Die tatsächlichen Asset-Pfade variieren je nach REDAXO-Installation. `getAssetsUrl()` liefert immer die korrekte absolute URL.

### Einbindung über REDAXO-Modul

Im Modul-Output kann die Karte direkt mit `REX_VALUE`-Platzhaltern ausgegeben werden:

```html
<?php
// Module output
$lat = rex_escape(REX_VALUE[1]);
$lng = rex_escape(REX_VALUE[2]);
$zoom = rex_escape(REX_VALUE[3] ?: '14');
$popup = rex_escape(REX_VALUE[4]);
?>
<vectormap
    lat="<?= $lat ?>"
    lng="<?= $lng ?>"
    zoom="<?= $zoom ?>"
    height="400px"
    markers='[{"lat":<?= $lat ?>,"lng":<?= $lng ?>,"popup":"<?= $popup ?>"}]'>
</vectormap>
```

### CSP-Hinweis (Content-Security-Policy)

Falls eine Content-Security-Policy aktiv ist, müssen folgende Direktiven ergänzt werden — **nur** für Seiten mit Karten:

```
worker-src blob:;
```

> Alle Tile-Requests laufen über den REDAXO-Proxy (`/?rex_api_vector_maps_proxy=1`), daher sind **keine** externen `connect-src`-Einträge für OpenFreeMap, Nominatim oder OSRM notwendig.

---



```html
<!-- Einfache Karte -->
<vectormap lat="51.51" lng="-0.12" zoom="12"></vectormap>

<!-- Mit Markern und Popup -->
<vectormap lat="51.51" lng="-0.12" zoom="13"
    markers='[{"lat":51.51,"lng":-0.12,"popup":"London"}]'>
</vectormap>

<!-- Vollbreite, 3D, Dark-Theme -->
<vectormap lat="48.85" lng="2.35" zoom="15"
    height="500px" theme="dark" 3d>
</vectormap>
```

### Alle Attribute

| Attribut | Standard | Beispiel | Beschreibung |
|---|---|---|---|
| `lat` | `51.505` | `lat="51.51"` | Breitengrad des Kartenzentrums |
| `lng` | `7.0` | `lng="-0.12"` | Längengrad des Kartenzentrums |
| `center` | – | `center="51.51,-0.12"` | Kartenmitte als `lat,lng` (Alternative zu `lat`+`lng`) |
| `zoom` | `6` | `zoom="15"` | Anfangszoomstufe (0–22) |
| `height` | `400` | `height="600px"` | CSS-Höhe des Karten-Containers |
| `pitch` | `0` | `pitch="45"` | Kamerakippung in Grad (0–60) |
| `bearing` | `0` | `bearing="45"` | Kartenausrichtung in Grad |
| `map-style` | `liberty` | `map-style="bright"` | OFM-Stilname: `liberty`, `bright`, `positron` – oder Theme-Name |
| `theme` | – | `theme="dark"` | Farb-Theme: `dark`, `warm`, `mono` oder eigener Theme-Name |
| `language` | `de` | `language="en"` | Sprachcode für Kartenbeschriftungen (ISO 639-1) |
| `markers` | – | `markers='[{"lat":51.51,"lng":-0.12}]'` | JSON-Array mit Marker-Objekten |
| `fit-bounds` | auto | `fit-bounds="false"` | Kartenausschnitt an alle Marker anpassen. Standard: aktiv bei mehreren Markern; mit `fit-bounds="false"` deaktivieren |
| `cluster` | `false` | `cluster` | Marker bei hoher Dichte clustern |
| `3d` | `false` | `3d` | 3D-Gebäude aktivieren |
| `interactive` | `true` | `interactive="false"` | Karte scrollbar/zoombar (Standard: aktiv). Mit `interactive="false"` deaktivieren |
| `locate` | `false` | `locate` | Standort-Button anzeigen |
| `no-navigation` | `false` | `no-navigation` | Zoom-/Kompass-Controls ausblenden |
| `no-attribution` | `false` | `no-attribution` | Attributionszeile ausblenden |
| `fly-to` | – | `fly-to="48.85,2.35"` | Kartenansicht nach dem Laden animiert zentrieren |
| `min-zoom` | `0` | `min-zoom="5"` | Minimaler Zoom-Level |
| `max-zoom` | `22` | `max-zoom="18"` | Maximaler Zoom-Level |

---

## Routing

Routen zwischen zwei Punkten berechnen – sowohl mit Koordinaten als auch mit Adressen. Alle Routing-Anfragen laufen über [OSRM](http://project-osrm.org/) und werden DSGVO-konform über den PHP-Proxy geleitet.

```html
<!-- Routing mit Koordinaten -->
<vectormap lat="51.43" lng="6.77" zoom="13"
    route-from="51.4298,6.7742"
    route-to="51.4539,6.7658"
    route-mode="walking">
</vectormap>

<!-- Routing mit Adressen -->
<vectormap lat="48.85" lng="2.35" zoom="6"
    route-from="Eiffelturm, Paris"
    route-to="Duisburg Hauptbahnhof"
    route-mode="driving">
</vectormap>
```

### Routing-Attribute

| Attribut | Standard | Beispiel | Beschreibung |
|---|---|---|---|
| `route-from` | – | `route-from="51.43,6.77"` | Startpunkt: `lat,lng` oder Adresse als Text |
| `route-to` | – | `route-to="51.45,6.76"` | Zielpunkt: `lat,lng` oder Adresse als Text |
| `route-mode` | `driving` | `route-mode="walking"` | Routing-Profil: `driving`, `walking`, `cycling` |
| `route-panel` | `false` | `route-panel` | Interaktives Von/Nach-Panel auf der Karte einblenden |
| `route-to-locked` | `false` | `route-to-locked` | Zieladresse im Panel fixieren – nicht editierbar, kein Autocomplete |
| `route-no-steps` | `false` | `route-no-steps` | Abbiegehinweise (Turn-by-Turn) im Panel ausblenden |

### Interaktives Routenpanel (`route-panel`)

Das Routenpanel wird als schwebende Seitenleiste auf der Karte angezeigt und ermöglicht es, Start- und Zielpunkt direkt per Texteingabe (mit Autocomplete) sowie das Profil per Klick zu wählen:

```html
<vectormap lat="51.43" lng="6.77" zoom="13"
    height="500px" interactive route-panel
    route-from="Duisburg Hauptbahnhof"
    route-to="Duisburg Innenstadt">
</vectormap>
```

> Das Routenpanel zeigt SVG-Icons für die drei Modi Auto, Zu Fuß und Fahrrad. Es überschreibt `route-from`/`route-to`-Attribute nicht, sondern verwendet sie als Startwerte.

### Fixiertes Ziel (`route-to-locked`)

Wenn das Ziel fest vorgegeben ist (z.B. ein Firmenstandort), kann das Zielfeld eingesperrt werden. Der Nutzer gibt nur seine Startadresse ein — das Ziel ist nicht editierbar und hat kein Autocomplete:

```html
<vectormap lat="51.4298" lng="6.7742" zoom="12"
    height="450px"
    interactive
    route-panel
    route-to="Duisburg Hauptbahnhof"
    route-to-locked
    route-no-steps>
</vectormap>
```

> Mit `route-no-steps` wird nur der berechnete Weg auf der Karte angezeigt — ohne Schritt-für-Schritt-Abbiegehinweise darunter. Ideal für Anfahrtskarten auf Kontaktseiten.

---

## Umgebungssuche (nearby)

Mit dem `nearby`-Attribut können Points of Interest (POI) aus OpenStreetMap rund um den Kartenmittelpunkt oder den Gerätestandort des Nutzers geladen werden. Die Abfragen laufen über die [Overpass API](https://overpass-api.de/) und werden DSGVO-konform über den PHP-Proxy geleitet.

```html
<!-- E-Ladestationen im Umkreis von 2 km um Duisburg HBF -->
<vectormap lat="51.4298" lng="6.7742" zoom="13"
    height="500px"
    nearby="amenity=charging_station"
    nearby-radius="2000"
    nearby-label="Ladestation">
</vectormap>

<!-- Supermärkte und Discounter in der Nähe des Nutzers -->
<vectormap lat="51.43" lng="6.77" zoom="13"
    height="500px"
    nearby="shop=supermarket|shop=discount"
    nearby-locate>
</vectormap>
```

### Nearby-Attribute

| Attribut | Standard | Beispiel | Beschreibung |
|---|---|---|---|
| `nearby` | – | `nearby="amenity=restaurant"` | Overpass-Filter (`key=value`), mehrere mit `\|` trennen |
| `nearby-radius` | `1000` | `nearby-radius="2000"` | Suchradius in Metern |
| `nearby-label` | OSM-Tags | `nearby-label="Restaurant"` | Fallback-Label für Popups (wenn kein OSM-Name) |
| `nearby-locate` | `false` | `nearby-locate` | Nutzerstandort (Geolokalisierung) als Suchmittelpunkt verwenden |

### Häufige Overpass-Filter

| Filter | Beschreibung |
|---|---|
| `amenity=charging_station` | E-Ladestationen |
| `amenity=fuel` | Tankstellen |
| `amenity=restaurant` | Restaurants |
| `amenity=cafe` | Cafés |
| `amenity=pharmacy` | Apotheken |
| `amenity=hospital` | Krankenhäuser |
| `amenity=bank` | Banken |
| `shop=supermarket` | Supermärkte |
| `shop=supermarket\|shop=discount` | Supermärkte und Discounter |
| `leisure=park` | Parks |
| `tourism=hotel` | Hotels |
| `tourism=museum` | Museen |

> **Hinweis:** Bei `nearby-locate` fragt die Karte den Browser nach dem Gerätestandort. Fällt die Geolokalisierung fehl, wird automatisch auf den Kartenmittelpunkt zurückgegriffen.

---

## Dynamisches Datennachladen

Über die globale Hilfsfunktion `vmProxyUrl()` können externe APIs auch **client-seitig per JavaScript** angesprochen werden – DSGVO-konform über den REDAXO-Proxy, ohne CORS-Probleme.

### Ladestationen live nachladen (Overpass API)

Das folgende Muster lädt E-Ladesäulen dynamisch für den aktuellen Kartenausschnitt, sobald die Karte bewegt wird:

```javascript
// Zugriff auf die MapLibre-Instanz eines <vectormap>-Elements
const el  = document.getElementById('meine-karte');
const map = el._vmMap; // nach dem 'load'-Event verfügbar

// GeoJSON-Source + Layer anlegen
map.addSource('stationen', {
  type: 'geojson',
  data: { type: 'FeatureCollection', features: [] }
});
map.addLayer({
  id: 'stationen-punkte', type: 'circle', source: 'stationen',
  paint: { 'circle-color': '#27ae60', 'circle-stroke-color': '#fff', 'circle-stroke-width': 2 }
});

// Bei Kartenbewegung Daten nachladen (debounced)
let timer;
map.on('moveend', () => {
  clearTimeout(timer);
  timer = setTimeout(async () => {
    if (map.getZoom() < 9) return; // erst ab Zoom 9 laden
    const b = map.getBounds();
    const q = `[out:json][timeout:10];`
      + `node["amenity"="charging_station"]`
      + `(${b.getSouth()},${b.getWest()},${b.getNorth()},${b.getEast()});`
      + `out 200;`;
    const data = await fetch(vmProxyUrl(
      'https://overpass-api.de/api/interpreter?data=' + encodeURIComponent(q)
    )).then(r => r.json());
    const features = (data.elements || []).map(n => ({
      type: 'Feature',
      geometry: { type: 'Point', coordinates: [n.lon, n.lat] },
      properties: n.tags
    }));
    map.getSource('stationen').setData({ type: 'FeatureCollection', features });
  }, 600);
});
```

> **Hinweis:** `vmProxyUrl` ist nach dem Laden von `vectormaps.js` global verfügbar. `overpass-api.de` ist bereits in der Proxy-Whitelist (`lib/Proxy.php`) eingetragen.

### Wetter-Daten via Open-Meteo / Bright Sky (DWD)

```javascript
// Aktuelles Wetter für Berlin via Open-Meteo
const omUrl = 'https://api.open-meteo.com/v1/forecast'
    + '?latitude=52.52&longitude=13.405&current=temperature_2m,weather_code&timezone=auto';
const omData = await fetch(vmProxyUrl(omUrl)).then(r => r.json());
console.log('Berlin:', omData.current.temperature_2m, '°C');

// Aktuelles DWD-Wetter für Hamburg via Bright Sky
const bsUrl = 'https://api.brightsky.dev/current_weather?lat=53.55&lon=10.0';
const bsData = await fetch(vmProxyUrl(bsUrl)).then(r => r.json());
console.log('Hamburg:', bsData.weather.temperature, '°C', bsData.weather.condition);
```

Beide Dienste sind bereits in der Proxy-Whitelist eingetragen (`api.open-meteo.com`, `api.brightsky.dev`).

---

## Themes

Drei eingebaute Themes stehen sofort zur Verfügung:

| Theme | Attribut | Beschreibung |
|---|---|---|
| Standard | _(leer)_ | Helles Standardlayout (OpenFreeMap Bright) |
| `dark` | `theme="dark"` | Dunkles Layout (OpenFreeMap Dark) |
| `warm` | `theme="warm"` | Warmes Orange-/Beigelayout |
| `mono` | `theme="mono"` | Graustufen-Layout |

Eigene Themes können im Backend unter **Vector Maps → Themes** angelegt und live per Theme-Editor konfiguriert werden.

---

## Marker-Format

Marker werden als JSON-Array übergeben:

```html
<vectormap lat="51.43" lng="6.77" zoom="12"
    markers='[
        {"lat":51.4298,"lng":6.7742,"popup":"<strong>Duisburg HBF</strong>"},
        {"lat":51.4539,"lng":6.7658,"popup":"Duisburg Zoo","color":"#e74c3c"}
    ]'>
</vectormap>
```

Unterstützte Marker-Eigenschaften:

| Eigenschaft | Beschreibung |
|---|---|
| `lat`, `lng` | Position (erforderlich) |
| `popup` | Popup-Inhalt (HTML erlaubt) |
| `color` | Marker-Farbe für Standard-Pin (CSS-Farbwert, Standard: `#2b7095`) |
| `icon` | URL zu einem Bild (PNG, SVG, WebP) → Custom-Pin als `<img>` |
| `html` | Beliebiger HTML-String, Emoji oder SVG → Custom-Pin als HTML-Element |
| `size` | `[breite, höhe]` in px für `icon`-Marker, z. B. `[40, 48]` (Standard: 32×32) |
| `anchor` | Ankerpunkt des Custom-Pins: `bottom` (Standard), `center`, `top`, `left`, `right` |

### Custom Pins / Individuelle Marker

Jeder Marker kann als individueller Pin gestaltet werden – per Emoji, SVG, HTML-Div oder externem Bild:

```html
<!-- Emoji-Pins -->
<vectormap lat="51.43" lng="6.77" zoom="13"
    markers='[
        {"lat":51.4298,"lng":6.7742,"html":"🚉","anchor":"center","popup":"Bahnhof"},
        {"lat":51.4339,"lng":6.7621,"html":"🏛️","anchor":"center","popup":"Museum"},
        {"lat":51.4218,"lng":6.7660,"html":"🏬","anchor":"center","popup":"Einkauf"}
    ]'>
</vectormap>

<!-- HTML-Div-Pins mit eigenem CSS -->
<vectormap lat="51.43" lng="6.77" zoom="13"
    markers='[
        {"lat":51.4298,"lng":6.7742,
         "html":"<div class=\"mein-pin mein-pin--rot\">A</div>",
         "popup":"Punkt A"}
    ]'>
</vectormap>

<!-- Icon-URL (SVG/PNG) mit benutzerdefinierter Größe -->
<vectormap lat="51.43" lng="6.77" zoom="13"
    markers='[
        {"lat":51.4298,"lng":6.7742,
         "icon":"/assets/custom/pin.svg",
         "size":[40,48],
         "anchor":"bottom",
         "popup":"Unser Standort"}
    ]'>
</vectormap>
```

> Custom-Marker (`icon` / `html`) nutzen intern MapLibres `element`-Option und sind vollständig per CSS gestaltbar. Im Cluster-Modus werden sie als Standard-GeoJSON-Punkte dargestellt.

---

## Externes GeoJSON

Mit dem `geojson`-Attribut können externe oder inline GeoJSON-Daten direkt auf der Karte angezeigt werden. Unterstützt werden **Punkte**, **Linien** und **Flächen** (auch gemischt in einer FeatureCollection).

```html
<!-- Relative URL (wird direkt geladen) -->
<vectormap lat="51.43" lng="6.77" zoom="10"
    geojson="/assets/karte-data.geojson"
    geojson-color="#27ae60"
    geojson-popup="name">
</vectormap>

<!-- Externe URL (wird automatisch über den REDAXO-Proxy geleitet) -->
<vectormap lat="51.43" lng="6.77" zoom="6"
    geojson="https://example.com/data.geojson"
    geojson-color="#e74c3c"
    geojson-opacity="0.4"
    geojson-popup="description">
</vectormap>

<!-- Inline-JSON (kein Server-Request) -->
<vectormap lat="51.434" lng="6.763" zoom="14"
    geojson='{"type":"FeatureCollection","features":[
        {"type":"Feature","properties":{"name":"Innenhafen"},"geometry":{"type":"Polygon","coordinates":[...]}},
        {"type":"Feature","properties":{"name":"Museum"},"geometry":{"type":"Point","coordinates":[6.762,51.434]}}
    ]}'
    geojson-color="#2980b9"
    geojson-opacity="0.25">
</vectormap>
```

### GeoJSON-Attribute

| Attribut | Standard | Beispiel | Beschreibung |
|---|---|---|---|
| `geojson` | – | `geojson="/data.geojson"` | Relative/absolute URL oder Inline-JSON-String |
| `geojson-color` | `#2b7095` | `geojson-color="#e74c3c"` | Standardfarbe für Punkte, Linien und Flächen |
| `geojson-opacity` | `0.3` | `geojson-opacity="0.5"` | Transparenz der Flächen-Füllung (0–1) |
| `geojson-popup` | _(alle)_ | `geojson-popup="name"` | Property-Name für Klick-Popup; leer = automatische Tabelle aller Properties |

> `geojson` und `markers` lassen sich kombinieren — GeoJSON-Layer und individuelle Marker erscheinen gleichzeitig auf der Karte.

### Individuelle Marker-Pins via GeoJSON (Simplestyle)

Punkte können pro Feature individuell gestaltet werden — direkt über GeoJSON-Properties ohne zusätzliche Attribute:

```html
<vectormap lat="51.434" lng="6.763" zoom="13"
    geojson='{
        "type": "FeatureCollection",
        "features": [
            {
                "type": "Feature",
                "properties": {
                    "name": "Ladestation",
                    "Betreiber": "Stadtwerke",
                    "marker-color": "#27ae60",
                    "marker-symbol": "⚡"
                },
                "geometry": { "type": "Point", "coordinates": [6.763, 51.434] }
            },
            {
                "type": "Feature",
                "properties": {
                    "name": "Museum",
                    "marker-color": "#8e44ad",
                    "marker-symbol": "🏛"
                },
                "geometry": { "type": "Point", "coordinates": [6.770, 51.430] }
            },
            {
                "type": "Feature",
                "properties": { "name": "Stadtpark" },
                "geometry": {
                    "type": "Polygon",
                    "coordinates": [[[6.760, 51.435],[6.780, 51.435],[6.780, 51.425],[6.760, 51.425],[6.760, 51.435]]]
                }
            }
        ]
    }'
    geojson-color="#2980b9"
    geojson-opacity="0.25"
    geojson-popup="name">
</vectormap>
```

**Simplestyle-Properties** (pro GeoJSON-Feature, nur für Punkte):

| Property | Beispiel | Beschreibung |
|---|---|---|
| `marker-color` | `"#e74c3c"` | Pin-Farbe — überschreibt `geojson-color` für diesen Punkt |
| `marker-symbol` | `"⚡"` `"🏥"` `"A"` | Emoji oder Text-Zeichen im Pin. Ohne Angabe: Standard-Farb-Pin |

Flächen und Linien nutzen weiterhin `geojson-color` und `geojson-opacity` global.

In PHP lässt sich das komfortabel mit `json_encode()` aufbauen:

```php
$locations = [
    ['name' => 'Rathaus',    'lat' => 51.434, 'lng' => 6.762, 'symbol' => '🏛', 'color' => '#8e44ad'],
    ['name' => 'Ladestation','lat' => 51.437, 'lng' => 6.770, 'symbol' => '⚡', 'color' => '#27ae60'],
    ['name' => 'Parkplatz',  'lat' => 51.431, 'lng' => 6.758, 'symbol' => '🅿', 'color' => '#2980b9'],
];

$features = array_map(static function(array $loc): array {
    return [
        'type'       => 'Feature',
        'properties' => [
            'name'          => $loc['name'],
            'marker-color'  => $loc['color'],
            'marker-symbol' => $loc['symbol'],
        ],
        'geometry' => [
            'type'        => 'Point',
            'coordinates' => [$loc['lng'], $loc['lat']],
        ],
    ];
}, $locations);

$geoJson = json_encode(['type' => 'FeatureCollection', 'features' => $features]);

echo '<vectormap lat="51.434" lng="6.763" zoom="13"'
   . ' geojson=\'' . rex_escape($geoJson) . '\''
   . ' geojson-popup="name">'
   . '</vectormap>';
```

---

## Backend-Koordinatenpicker

Für REDAXO-Module kann ein Koordinatenpicker aktiviert werden, der ein einfaches Eingabefeld mit Kartenpicker-Button ausstattet.

### Im Modul-Input

```html
<div class="form-group">
    <label>Koordinaten</label>
    <input type="text"
        name="REX_INPUT_VALUE[1]"
        value="REX_VALUE[1]"
        class="form-control"
        data-vector-picker="1"
        placeholder="lat,lng">
</div>
```

Durch `data-vector-picker="1"` erscheint neben dem Feld ein Karten-Button. Beim Klick öffnet sich ein Modal mit einer Vollbild-Karte, Adresssuche (Autocomplete via Nominatim) und Koordinatenübernahme per Klick oder Suche.

### Im Modul-Output

```html
<vectormap lat="REX_VALUE[1]" lng="REX_VALUE[2]" zoom="14"
    markers='[{"lat":REX_VALUE[1],"lng":REX_VALUE[2],"popup":"Unser Standort"}]'>
</vectormap>
```

Alternativ: `lat,lng` aus einem einzelnen Feld parsen:

```php
<?php
$coords = explode(',', 'REX_VALUE[1]');
$lat = trim($coords[0] ?? '51.5');
$lng = trim($coords[1] ?? '7.0');
?>
<vectormap lat="<?= $lat ?>" lng="<?= $lng ?>" zoom="14"></vectormap>
```

---

## Proxy-Architektur (DSGVO)

Alle externen Dienste werden über den internen PHP-Proxy geleitet, sodass keine IP-Adressen der Websitebesucher an Dritte übertragen werden:

| Dienst | Verwendung |
|---|---|
| [OpenFreeMap](https://openfreemap.org/) | Vektorkacheln (Karte) |
| [Nominatim / OpenStreetMap](https://nominatim.openstreetmap.org/) | Adresssuche (Geocoding) |
| [OSRM](http://router.project-osrm.org/) | Routing (Fahrt-, Fuß-, Radwege) |
| [Overpass API](https://overpass-api.de/) | POI-Suche & dynamisches Datennachladen |
| [Open-Meteo](https://open-meteo.com/) | Weltweite Wettervorhersage (kostenlos, kein API-Key) |
| [Bright Sky / DWD](https://brightsky.dev/) | Deutsche Wetterdaten (DWD Open Data, Server in DE) |

Der Proxy unter `lib/Proxy.php` validiert alle Anfragen gegen eine Whitelist und leitet nur Anfragen an erlaubte Domains weiter. SSRF-Attacks werden durch strikte Domain-Prüfung verhindert.

---

## Vollständiges Beispiel

```html
<vectormap
    lat="51.4298"
    lng="6.7742"
    zoom="14"
    height="500px"
    theme="dark"
    3d
    interactive
    locate
    markers='[
        {"lat":51.4298,"lng":6.7742,"popup":"<strong>Duisburg Hauptbahnhof</strong><br>Bahnhofsvorplatz, 47119 Duisburg","color":"#3498db"}
    ]'
    route-from="51.4298,6.7742"
    route-to="51.4539,6.7658"
    route-mode="walking">
</vectormap>
```

---

## Backend-Seiten

| Seite | Beschreibung |
|---|---|
| **Demo** | Interaktive Übersicht aller Features mit Live-Beispielen |
| **Themes** | Theme-Editor zum Erstellen und Anpassen benutzerdefinierter Themes |
| **Einstellungen** | Proxy-Konfiguration, Standard-Koordinaten |

---

## Voraussetzungen

- REDAXO >= 5.14
- PHP >= 8.1
- PHP-Extensions: `curl`, `json`

---

## Credits

- **[MapLibre GL JS](https://maplibre.org/)** – Open-Source-Kartenbibliothek (BSD-2-Clause)
- **[OpenFreeMap](https://openfreemap.org/)** – Kostenlose Vektorkacheln ohne API-Key
- **[Nominatim / OSM](https://nominatim.openstreetmap.org/)** – Adress-Geocoding (ODbL)
- **[OSRM](http://project-osrm.org/)** – Open Source Routing Machine (BSD-2-Clause)
- **[Overpass API](https://overpass-api.de/)** – OSM-Datenbankabfragen (AGPL)
- **[Open-Meteo](https://open-meteo.com/)** – Kostenlose Wetter-API, kein API-Key (CC BY 4.0)
- **[Bright Sky](https://brightsky.dev/)** – DWD-Wetterdaten für Deutschland (CC BY 4.0)

---

## Lizenz

MIT License – siehe [LICENSE.md](LICENSE.md)

Entwickelt von [KLXM Crossmedia](https://klxm.de)
