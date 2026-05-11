# Changelog – Vector Maps

Alle nennenswerten Änderungen an diesem AddOn werden hier dokumentiert.  
Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/), Versionierung nach [Semantic Versioning](https://semver.org/).

---

## [1.1.2] – 2026-05-11

### Gefixt

- **Performance-Regression bei 3D-Gebäuden und Kamera-Animationen (`flyTo`)** – der in 1.1.1 eingeführte `vmFixExtrusionLayers()`-Helper wurde auf jedes `styledata`-Event gehängt und bei Animationen sehr häufig ausgeführt. Die enthaltene `JSON.stringify`-Schleife über alle Layer hat den Browser stark ausgelastet und Animationen ruckeln lassen. Der Patch wird jetzt nur noch **einmal pro Style-Load** ausgeführt und bei `style.load` (z.B. nach Satellit-Toggle) sauber resettet. (#1)

---

## [1.1.1] – 2026-04-16

### Gefixt

- **MapLibre-Warnungen "Expected value to be of type number, but found null"** – `fill-extrusion`-Layer (3D-Gebäude) verwendeten `['get', 'height']` / `['get', 'min_height']` ohne `null`-Absicherung. OSM-Gebäude ohne Höhendaten liefern `null`, was MapLibres `interpolate`-Expression nicht akzeptiert. Neuer Helper `vmFixExtrusionLayers()` patcht alle `fill-extrusion`-Layer im geladenen Style (inkl. OFM-eigene Layer) mit `['coalesce', ['get', 'height'], 0]` als Fallback. Wird als früher `styledata`-Handler ohne `isStyleLoaded()`-Guard registriert, damit der Fix vor dem ersten Tile-Render greift.

---

## [1.1.0] – 2026-03-27

### Hinzugefügt

- **Locate-Button im Routenpanel** – neben dem „Von"-Feld erscheint (wenn `geolocation` verfügbar) ein GPS-Button, der den aktuellen Gerätestandort als Startpunkt übernimmt
- **Routing-Linie im Theme-Editor konfigurierbar** – neues Farbfeld `route_line` im Theme-Builder und in allen Built-in-Themes (`dark`, `warm`, `mono`); Farbe wird beim Zeichnen der Route aus dem aktiven Theme übernommen
- **Proxy-Whitelist erweiterbar per Extension Point** – andere AddOns können über `rex_extension::register('VECTOR_MAPS_PROXY_DOMAINS', ...)` eigene Domains zur Proxy-Whitelist hinzufügen
- **Proxy-Whitelist manuell erweiterbar via Settings** – im Backend unter „Einstellungen" können zusätzliche erlaubte Proxy-URLs (je eine pro Zeile) hinterlegt werden
- **Esri ArcGIS / Satellite-Tiles durch Proxy** – `server.arcgisonline.com` und `services.arcgisonline.com` werden nun ebenfalls über den REDAXO-Proxy geleitet (DSGVO-konform)
- **Aktive Mode-Buttons im Routenpanel** – Fahrt-/Fuß-/Fahrrad-Buttons zeigen aktiven Zustand nun in Schwarz/Weiß statt Blau

### Gefixt

- **Routing-Linie Farbe wurde nicht gespeichert** – `route_line` fehlte in `ThemeManager::COLOR_KEYS` und wurde beim Speichern eines Themes gefiltert
- **Flash of Unstyled Map** – Karte bleibt bei Theme-Karten initial transparent (`vm-has-theme`) und blendet sich erst nach vollständigem Theme-Load ein (`vm-theme-ready`)
- **Race Condition bei Theme-Anwendung** – `vmApplyTheme` prüft jetzt `map.getStyle()?.layers?.length` statt `map.isStyleLoaded()` (das schlägt während des Tile-Streamings fehl); `styledata`-Handler stellt Theme mit `requestAnimationFrame`-Debounce wieder her
- **Browser-Cache verhinderte Theme-Updates** – Theme-Fetch-Requests senden `cache: no-store`, `ThemeManager::serveTheme()` setzt `Cache-Control: no-store`-Header
- **Orphaner Popup bei `route-to-locked`** – Ziel-Popup wird jetzt per `setPopup()` + `togglePopup()` am Marker eingebunden (kein losgelöster Popup mehr); Route-Clear stellt den Destination-Marker korrekt wieder her

### Geändert

- `vmApplyTheme`: erkennt eigene Route-Layer (`vm-route-line`) und färbt sie mit `colors.route_line`
- `vmAddRoutePanel`: Route-Zeichnung nutzt `map._vmThemeColors?.route_line` als Fallback statt fest verdrahtetes `#2b7095`
- `theme-transition`-Attribut steuert die Einblend-Dauer der Theme-Fade-in-Animation in ms (Standard: 350)

---

## [1.0.0] – 2026-03 (Initial Release)

### Hinzugefügt

- `<vectormap>` / `<vector-map>` Web Component mit Lazy-Init + Build-Queue (sequentieller WebGL-Kontext-Aufbau)
- Routing mit OSRM (Auto, Fuß, Fahrrad) inkl. interaktivem Routenpanel, Autocomplete, `route-to-locked`, `route-to-popup`
- Umgebungssuche (nearby) via Overpass API
- Dynamisches Datennachladen (GeoJSON per `moveend`)
- Wetter-Integration (Open-Meteo, Bright Sky/DWD) via Proxy
- Theme-System mit Editor (`dark`, `warm`, `mono`) + Theme-Fade-in
- 3D-Gebäude, Cluster, Custom Pins, GeoJSON-Layer (inkl. Simplestyle)
- Backend-Koordinatenpicker mit Adresssuche (Nominatim)
- Satellitenbild (ESRI World Imagery, kein API-Key)
- Vollständiger PHP-Proxy für DSGVO-konformen externen Datenverkehr
- Tile-Cache mit automatischer Content-Type-Erkennung
