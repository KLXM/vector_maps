class VectorMapPicker {
    constructor() {
        this.i18n = this.loadI18n();
        this.modal = null;
        this.map = null;
        this.marker = null;
        this.currentInput = null;
        this.is3dPickerActive = false;
        
        this.initAll();
        
        // Init dynamically if REDAXO reloads DOM via PJAX or jQuery pjax (forcal, yform, etc.)
        document.addEventListener('pjax:success', () => this.initAll());
        if (typeof jQuery !== 'undefined') {
            jQuery(document).on('rex:ready', () => this.initAll());
        }
    }

    loadI18n() {
        const defaultLang = 'de';
        let currentLang = document.documentElement.lang || defaultLang;

        if (currentLang.length > 2) {
            currentLang = currentLang.substring(0, 2);
        }

        const library = window.VectorMapsI18n || {};
        const dict = library[currentLang] || library[defaultLang] || {
            picker_button: 'Map Picker öffnen',
            search_placeholder: 'Adresse suchen...',
            close: 'Schließen',
            confirm: 'Übernehmen'
        };

        if (window.rex && window.rex.vector_maps_i18n) {
            return Object.assign({}, dict, window.rex.vector_maps_i18n);
        }
        
        return dict;
    }

    initModal() {
        if (document.getElementById('vm-picker-modal')) return;

        const overlay = document.createElement('div');
        overlay.id = 'vm-picker-modal';
        overlay.className = 'vm-modal-overlay';
        
        overlay.innerHTML = `
            <div class="vm-modal-content">
                <div class="vm-modal-header">
                    <h4>Vector Maps</h4>
                    <div class="vm-style-switcher">
                        <button type="button" class="btn btn-xs btn-primary vm-style-btn active" data-style="liberty">Liberty</button>
                        <button type="button" class="btn btn-xs btn-default vm-style-btn" data-style="bright">Bright</button>
                        <button type="button" class="btn btn-xs btn-default vm-style-btn" data-style="positron">Positron</button>
                    </div>
                    <button type="button" class="vm-close-btn">&times;</button>
                </div>
                <div class="vm-modal-body">
                    <div class="vm-search-box">
                        <input type="text" id="vm-search-input" placeholder="${this.i18n.search_placeholder}" autocomplete="off">
                        <button type="button" id="vm-search-btn" title="Adresse suchen"><i class="rex-icon rex-icon-search"></i></button>
                        <button type="button" id="vm-locate-btn" title="Meinen Standort anzeigen"><i class="rex-icon rex-icon-crosshairs"></i></button>
                    </div>
                    <div id="vm-picker-suggestions" class="vm-picker-suggestions"></div>
                    <div id="vm-picker-map" class="vm-map-container"></div>
                </div>
                <div class="vm-modal-footer">
                    <button type="button" class="btn btn-default" id="vm-picker-3d-btn" title="3D-Gebäude ein/ausschalten">
                        <i class="rex-icon rex-icon-cube"></i> 3D
                    </button>
                    <button type="button" class="btn btn-default vm-close-btn">${this.i18n.close}</button>
                    <button type="button" class="btn btn-primary" id="vm-confirm-btn">${this.i18n.confirm}</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);

        this.modal = overlay;

        const closeBtns = overlay.querySelectorAll('.vm-close-btn');
        closeBtns.forEach(btn => btn.addEventListener('click', () => this.closeModal()));
        
        document.getElementById('vm-confirm-btn').addEventListener('click', () => this.confirmSelection());
        
        const searchBtn = document.getElementById('vm-search-btn');
        const searchInput = document.getElementById('vm-search-input');
        
        const doSearch = () => this.searchAddress(searchInput.value);
        searchBtn.addEventListener('click', doSearch);
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.hidePickerSuggestions();
                doSearch();
            }
        });

        // Live-Autocomplete (debounced) im Picker
        let _pickerTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(_pickerTimer);
            const q = searchInput.value.trim();
            if (q.length < 3) { this.hidePickerSuggestions(); return; }
            _pickerTimer = setTimeout(() => this.fetchPickerSuggestions(q), 300);
        });

        // Suggestions bei Klick außerhalb schließen
        overlay.addEventListener('click', (e) => {
            if (!e.target.closest('.vm-search-box') && !e.target.closest('.vm-picker-suggestions')) {
                this.hidePickerSuggestions();
            }
        });

        // Standort-Button
        document.getElementById('vm-locate-btn').addEventListener('click', () => this.locateUser());

        // 3D-Toggle im Picker
        document.getElementById('vm-picker-3d-btn').addEventListener('click', () => this.toggle3DPicker());

        // Style switcher in picker
        overlay.querySelectorAll('.vm-style-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                overlay.querySelectorAll('.vm-style-btn').forEach(b => {
                    b.className = 'btn btn-xs btn-default vm-style-btn';
                });
                btn.className = 'btn btn-xs btn-primary vm-style-btn active';
                if (this.map) {
                    this.map.setStyle(this.proxyStyleUrl(btn.dataset.style));
                }
            });
        });
    }

    initAll() {
        document.querySelectorAll('input[data-vector-picker="1"]:not(.vm-initialized)').forEach(input => {
            input.classList.add('vm-initialized');
            this.setupInput(input);
        });
        
        const demoMap = document.getElementById('vm-demo-map');
        if (demoMap && !demoMap.classList.contains('vm-initialized')) {
            demoMap.classList.add('vm-initialized');
            this.initDemoMap();
        }

        // <vectormap> (ohne Bindestrich) – kein Custom Element, manuell initialisieren
        document.querySelectorAll('vectormap:not(.vm-initialized)').forEach(el => {
            initVectorMap(el);
        });
        // <vector-map> ohne connectedCallback (z.B. PJAX-Nachlader)
        document.querySelectorAll('vector-map:not(.vm-initialized)').forEach(el => {
            initVectorMap(el);
        });

        // Demo 11: Ladestation-Demo (nur wenn Element auf der Seite vorhanden)
        initChargingStationDemo();

        // Demo 12: 3D Overfly Berlin
        initOverflyDemo();
    }

    setupInput(input) {
        const wrapper = document.createElement('div');
        wrapper.className = 'input-group';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const btnWrap = document.createElement('span');
        btnWrap.className = 'input-group-btn';
        const btn = document.createElement('button');
        btn.className = 'btn btn-default';
        btn.innerHTML = `<i class="rex-icon rex-icon-map"></i> ${this.i18n.picker_button}`;
        btn.type = 'button';
        btnWrap.appendChild(btn);
        wrapper.appendChild(btnWrap);

        btn.addEventListener('click', () => this.openModal(input));
    }

    openModal(input) {
        this.initModal();
        this.currentInput = input;
        this.modal.classList.add('vm-active');
        
        setTimeout(() => this.initPickerMap(input), 150);
    }
    
    closeModal() {
        if(this.modal) this.modal.classList.remove('vm-active');
        this.currentInput = null;
    }
    
    confirmSelection() {
        if (this.marker && this.currentInput) {
            const lngLat = this.marker.getLngLat();
            this.currentInput.value = `${lngLat.lat.toFixed(6)},${lngLat.lng.toFixed(6)}`;
            this.currentInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
        this.closeModal();
    }

    /**
     * Baut eine absolute Proxy-URL (MapLibre lehnt relative URLs ab).
     */
    proxyUrl(targetUrl) {
        return window.location.origin + '/?rex_api_vector_maps_proxy=1&target_url=' + encodeURIComponent(targetUrl);
    }

    proxyStyleUrl(styleName) {
        if (styleName === 'satellite') return VM_SATELLITE_STYLE;
        return this.proxyUrl('https://tiles.openfreemap.org/styles/' + styleName);
    }

    createTransformRequest() {
        return (url, resourceType) => {
            if (url.includes('tiles.openfreemap.org/')) {
                return { url: this.proxyUrl(url) };
            }
            return { url };
        };
    }

    initPickerMap(input) {
        // Initialer Ort (z.B. Kassel, Mitte Deutschland)
        let startLat = 51.1656;
        let startLng = 10.4515;
        let startZoom = 5.5;

        // Falls wir schon einen Wert im Input-Feld haben:
        if (input.value) {
            const parts = input.value.split(',');
            if (parts.length === 2) {
                const lat = parseFloat(parts[0]);
                const lng = parseFloat(parts[1]);
                if (!isNaN(lat) && !isNaN(lng)) {
                    startLat = lat;
                    startLng = lng;
                    startZoom = 14; 
                }
            }
        }

        if (!this.map) {
            this.map = new maplibregl.Map({
                container: 'vm-picker-map',
                style: this.proxyStyleUrl('liberty'),
                center: [startLng, startLat],
                zoom: startZoom,
                transformRequest: this.createTransformRequest()
            });

            this.map.addControl(new maplibregl.NavigationControl());

            this.marker = new maplibregl.Marker({
                draggable: true,
                color: '#d9534f'
            }).setLngLat([startLng, startLat]).addTo(this.map);

            this.map.on('click', (e) => {
                this.marker.setLngLat(e.lngLat);
            });
            
            this.map.on('load', () => {
                this.map.resize();
                // Picker-Karte auf Seitensprache setzen
                const uiLang = (document.documentElement.lang || navigator.language || 'de').slice(0, 2);
                vmSetLanguage(this.map, uiLang);
            });

            // 3D-Gebäude nach Style-Wechsel wiederherstellen
            this.map.on('styledata', () => {
                if (this.is3dPickerActive && this.map.isStyleLoaded()) {
                    this.add3dBuildingsPicker();
                }
            });
        } else {
            // Map gibt es schon, nur neu zentrieren und Marker setzen
            this.map.resize();
            this.map.setCenter([startLng, startLat]);
            this.map.setZoom(startZoom);
            this.marker.setLngLat([startLng, startLat]);
        }
    }

    async searchAddress(query) {
        if (!query || !this.map) return;
        const btn = document.getElementById('vm-search-btn');
        const origIcon = btn ? btn.innerHTML : '';
        if (btn) btn.innerHTML = '\u2026';
        try {
            const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(query);
            const resp = await fetch(this.proxyUrl(url));
            const data = await resp.json();
            if (btn) btn.innerHTML = origIcon;
            if (data && data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lng = parseFloat(data[0].lon);
                this.map.flyTo({ center: [lng, lat], zoom: 15 });
                if (this.marker) this.marker.setLngLat([lng, lat]);
            }
        } catch (e) {
            if (btn) btn.innerHTML = origIcon;
            console.error('Picker-Suche fehlgeschlagen', e);
        }
    }

    async fetchPickerSuggestions(query) {
        const sugDiv = document.getElementById('vm-picker-suggestions');
        if (!sugDiv) return;
        try {
            const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=5&q=' + encodeURIComponent(query);
            const resp = await fetch(this.proxyUrl(url));
            const data = await resp.json();
            this.showPickerSuggestions(data);
        } catch (_) {}
    }

    showPickerSuggestions(results) {
        const sugDiv = document.getElementById('vm-picker-suggestions');
        if (!sugDiv) return;
        sugDiv.innerHTML = '';
        if (!results || !results.length) { sugDiv.classList.remove('vm-picker-suggestions--open'); return; }
        results.forEach(r => {
            const item = document.createElement('div');
            item.className = 'vm-picker-suggestion-item';
            item.textContent = r.display_name;
            item.addEventListener('mousedown', (e) => {
                e.preventDefault();
                const searchInput = document.getElementById('vm-search-input');
                if (searchInput) searchInput.value = r.display_name;
                const lat = parseFloat(r.lat);
                const lng = parseFloat(r.lon);
                if (this.map) this.map.flyTo({ center: [lng, lat], zoom: 15 });
                if (this.marker) this.marker.setLngLat([lng, lat]);
                this.hidePickerSuggestions();
            });
            sugDiv.appendChild(item);
        });
        sugDiv.classList.add('vm-picker-suggestions--open');
    }

    hidePickerSuggestions() {
        const sugDiv = document.getElementById('vm-picker-suggestions');
        if (sugDiv) {
            sugDiv.classList.remove('vm-picker-suggestions--open');
            sugDiv.innerHTML = '';
        }
    }

    locateUser() {
        if (!navigator.geolocation) {
            alert('Ihr Browser unterstützt keine Geolokalisierung.');
            return;
        }
        const btn = document.getElementById('vm-locate-btn');
        if (btn) btn.innerHTML = '...';
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                if (btn) btn.innerHTML = '<i class="rex-icon rex-icon-crosshairs"></i>';
                const { latitude: lat, longitude: lng } = pos.coords;
                if (this.map) {
                    this.map.flyTo({ center: [lng, lat], zoom: 15 });
                }
                if (this.marker) {
                    this.marker.setLngLat([lng, lat]);
                }
            },
            () => {
                if (btn) btn.innerHTML = '<i class="rex-icon rex-icon-crosshairs"></i>';
                alert('Standort konnte nicht ermittelt werden. Bitte Berechtigung im Browser prüfen.');
            },
            { timeout: 8000, maximumAge: 60000 }
        );
    }

    toggle3DPicker() {
        this.is3dPickerActive = !this.is3dPickerActive;
        const btn = document.getElementById('vm-picker-3d-btn');
        if (this.is3dPickerActive) {
            if (btn) btn.className = 'btn btn-primary';
            if (this.map) {
                if (this.map.isStyleLoaded()) {
                    this.add3dBuildingsPicker();
                }
                this.map.easeTo({ pitch: 50 });
            }
        } else {
            if (btn) btn.className = 'btn btn-default';
            if (this.map) {
                if (this.map.getLayer('vm-picker-3d-buildings')) {
                    this.map.removeLayer('vm-picker-3d-buildings');
                }
                this.map.easeTo({ pitch: 0 });
            }
        }
    }

    add3dBuildingsPicker() {
        if (!this.map || this.map.getLayer('vm-picker-3d-buildings')) return;
        const sources = this.map.getStyle().sources;
        const srcName = Object.keys(sources).find(k => sources[k].type === 'vector') || 'openmaptiles';
        this.map.addLayer({
            'id': 'vm-picker-3d-buildings',
            'source': srcName,
            'source-layer': 'building',
            'filter': ['==', 'extrude', 'true'],
            'type': 'fill-extrusion',
            'minzoom': 15,
            'paint': {
                'fill-extrusion-color': '#aaa',
                'fill-extrusion-height': ['interpolate', ['linear'], ['zoom'], 15, 0, 15.05, ['get', 'height']],
                'fill-extrusion-base': ['interpolate', ['linear'], ['zoom'], 15, 0, 15.05, ['get', 'min_height']],
                'fill-extrusion-opacity': 0.6
            }
        });
    }

    initDemoMap() {
        this.demoMap = new maplibregl.Map({
            container: 'vm-demo-map',
            style: this.proxyStyleUrl('liberty'),
            center: [10.4515, 51.1656],
            zoom: 5.5,
            pitch: 45,
            bearing: -17,
            maxPitch: 60,
            transformRequest: this.createTransformRequest()
        });
        const map = this.demoMap;
        // Initiale Sprache: Backend-UI-Sprache (via rex.vector_maps_lang) → 'de'
        let _demoCurrentLang = (window.rex && rex.vector_maps_lang) ? rex.vector_maps_lang : 'de';

        map.addControl(new maplibregl.NavigationControl());

        // Fehlende Sprite-Icons stumm ignorieren (verhindert Console-Spam)
        map.on('styleimagemissing', (e) => {
            if (!map.hasImage(e.id)) map.addImage(e.id, { width: 1, height: 1, data: new Uint8ClampedArray(4) });
        });

        const add3dBuildings = () => {
            if (map.getLayer('3d-buildings')) return;
            // Source-Layer-Name prüfen
            const sources = map.getStyle().sources;
            const srcName = Object.keys(sources).find(k => sources[k].type === 'vector') || 'openmaptiles';
            map.addLayer({
                'id': '3d-buildings',
                'source': srcName,
                'source-layer': 'building',
                'filter': ['==', 'extrude', 'true'],
                'type': 'fill-extrusion',
                'minzoom': 15,
                'paint': {
                    'fill-extrusion-color': '#aaa',
                    'fill-extrusion-height': ['interpolate', ['linear'], ['zoom'], 15, 0, 15.05, ['get', 'height']],
                    'fill-extrusion-base': ['interpolate', ['linear'], ['zoom'], 15, 0, 15.05, ['get', 'min_height']],
                    'fill-extrusion-opacity': .6
                }
            });
        };

        map.on('load', () => {
            new maplibregl.Marker({color: "#ff0000"}).setLngLat([10.4515, 51.1656]).addTo(map);
            add3dBuildings();
            // Demo-Karte auf aktuelle Sprache setzen
            vmSetLanguage(map, _demoCurrentLang);
            // Flug nach Mainhattan (Frankfurt Bankenviertel)
            setTimeout(() => {
                map.flyTo({ center: [8.682127, 50.110924], zoom: 16, pitch: 60, bearing: 60, duration: 6000 });
                new maplibregl.Marker({color: "#0000ff"})
                    .setLngLat([8.682127, 50.110924])
                    .setPopup(new maplibregl.Popup().setHTML("<h5>Mainhattan</h5><p>Vector Maps via REDAXO-Proxy!</p>"))
                    .addTo(map)
                    .togglePopup();
            }, 1000);
        });

        // styledata: NUR 3D-Gebäude (idempotent durch getLayer-Check).
        // setLanguage + vmApplyTheme feuern selbst styledata-Events → NICHT hier aufrufen!
        // Nach Stil-Wechsel werden sie stattdessen via map.once('idle', ...) aufgerufen.
        // Auf Satellitenbild gibt es keinen Vektor-Layer → kein 3D möglich.
        const _isSatellite = () => map.isStyleLoaded() && !!map.getSource('satellite');
        map.on('styledata', () => {
            if (!map.isStyleLoaded()) return;
            if (!_isSatellite()) add3dBuildings();
        });

        // Hilfsfunktion: Sprache + Theme nach Stil-Wechsel einmalig sauber anwenden
        const _demoApplyLangTheme = () => {
            if (!map.isStyleLoaded()) return;
            if (_isSatellite()) return; // Kein Sprach-/Theme-Layer auf Satellitenbild
            vmSetLanguage(map, _demoCurrentLang);
            if (map._vmThemeColors) vmApplyTheme(map, map._vmThemeColors);
        };

        // Sprach-Umschalter
        document.querySelectorAll('.vm-demo-lang-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.vm-demo-lang-btn').forEach(b => {
                    b.className = 'btn btn-xs btn-default vm-demo-lang-btn';
                });
                btn.className = 'btn btn-xs btn-primary vm-demo-lang-btn active';
                _demoCurrentLang = btn.dataset.lang;
                vmSetLanguage(map, btn.dataset.lang);
            });
        });

        // Theme-Umschalter (Dark / Warm / Mono / Reset)
        document.querySelectorAll('.vm-demo-theme-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.vm-demo-theme-btn').forEach(b => {
                    b.className = 'btn btn-xs btn-default vm-demo-theme-btn';
                });
                btn.className = 'btn btn-xs btn-primary vm-demo-theme-btn active';
                if (btn.dataset.theme === 'none') {
                    map._vmThemeColors = null;
                    // Style neu laden, um Original-Farben wiederherzustellen
                    const activeStyleBtn = document.querySelector('.vm-demo-style-btn.active');
                    const styleName = activeStyleBtn ? activeStyleBtn.dataset.style : 'liberty';
                    map.setStyle(this.proxyStyleUrl(styleName));
                    map.once('idle', _demoApplyLangTheme);
                } else {
                    const theme = VM_BUILT_IN_THEMES[btn.dataset.theme];
                    if (theme) {
                        map._vmThemeColors = theme.colors;
                        if (map.isStyleLoaded()) vmApplyTheme(map, theme.colors);
                    }
                }
            });
        });

        // Style-Umschalter Events
        document.querySelectorAll('.vm-demo-style-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.vm-demo-style-btn').forEach(b => {
                    b.className = 'btn btn-sm btn-default vm-demo-style-btn';
                });
                btn.className = 'btn btn-sm btn-primary vm-demo-style-btn active';
                map.setStyle(this.proxyStyleUrl(btn.dataset.style));
                // Sprache + Theme nach Stil-Wechsel sauber via idle wiederherstellen
                map.once('idle', _demoApplyLangTheme);
            });
        });

        // 3D Toggle
        const toggle3d = document.getElementById('vm-demo-3d-toggle');
        if (toggle3d) {
            toggle3d.addEventListener('click', () => {
                const on = toggle3d.classList.toggle('active');
                if (on) {
                    add3dBuildings();
                    map.easeTo({ pitch: 60 });
                } else {
                    if (map.getLayer('3d-buildings')) map.removeLayer('3d-buildings');
                    map.easeTo({ pitch: 0 });
                }
            });
        }
    }
}

/* =============================================================================
   <vector-map> / <vectormap> Web Component
   Attribute:
     center="lat,lng"           Kartenmittelpunkt (Standard: Deutschland-Mitte)
     zoom="6"                   Zoom-Stufe
     map-style="liberty"        Stil: liberty|bright|positron|satellite oder vollständige URL
     pitch="0"                  Kamerakippung (0–85°)
     bearing="0"                Kameraausrichtung
     height="400"               Höhe in px (oder "60vh", "100%" usw.)
     3d                         3D-Gebäude aktivieren
     locate                     Standort-Button anzeigen
     no-navigation              Zoom-/Dreh-Steuerung ausblenden
     no-attribution             Quellenangabe ausblenden
     interactive="false"        Karte statisch (kein Scrollen/Ziehen)
     markers='[{lat,lng,popup,color,icon,html,anchor,size}]'   JSON-Marker-Array
       – color   Standardfarbe (#hex)
       – icon    URL zu Bild-Datei → Custom-Pin als <img>
       – html    HTML-String (Emoji, SVG, <div class="...">…) → Custom-Pin als HTML-Element
       – anchor  Ankerpunkt des Custom-Pins: bottom (Standard) | center | top | left | right
       – size    [Breite, Höhe] in px für icon-Marker, z.B. [40,40]
     cluster                    Marker clustern
     show-satellite             Satellitenbild-Toggle-Button einblenden (ESRI World Imagery, kein API-Key)
     route-from="lat,lng"       Routing-Startpunkt (Koordinaten oder Adresse)
     route-to="lat,lng"         Routing-Zielpunkt  (Koordinaten oder Adresse)
     route-mode="driving"       driving | walking | cycling
     route-panel               Interaktives Route-Panel mit Adresssuche einblenden
     route-to-locked           Zieladresse im Route-Panel fixieren (nicht änderbar, kein Autocomplete)
     route-no-steps            Abbiegehinweise im Route-Panel ausblenden
     nearby="amenity=charging_station" Overpass-Filter für POI-Umgebungssuche
     nearby-radius="1000"       Suchradius in Metern (Standard: 1000)
     nearby-label="Ladestn."    Label für Marker-Popups (optional, sonst OSM-Tags)
     nearby-locate              Statt Karten-Mittelpunkt: Standort des Nutzers nutzen
     geojson="URL_oder_JSON"    Externes GeoJSON: relative/absolute URL oder Inline-JSON-String
     geojson-color="#2b7095"    Standardfarbe für GeoJSON-Layer (Punkte, Linien, Flächen)
     geojson-opacity="0.3"      Transparenz der Flächen-Füllung (0–1)
     geojson-popup="name"       Property-Name für automatische Popups (leer = alle Properties)
     fly-to="lat,lng,zoom"      Nach Laden dorthin fliegen
     fly-delay="0"              Verzögerung in ms vor dem Flug (mit fly-to)
   ============================================================================= */

/** Bekannte OpenFreeMap-Stil-Namen (werden direkt an OFM weitergeleitet) */
const VM_OFM_STYLES = ['liberty', 'bright', 'positron'];

/** Raster-Stile (kein Vektor, kein Sprach-/Theme-Layer) */
const VM_RASTER_STYLES = ['satellite'];

/**
 * ESRI World Imagery — kostenloses Satellitenbild (kein API-Key erforderlich).
 * Nur die Basiskacheln; keine Beschriftungen, kein Proxy nötig.
 */
const VM_SATELLITE_STYLE = {
    version: 8,
    sources: {
        satellite: {
            type: 'raster',
            tiles: ['https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'],
            tileSize: 256,
            attribution: '© Esri — Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
            maxzoom: 19,
        },
    },
    layers: [{ id: 'satellite', type: 'raster', source: 'satellite' }],
};

/**
 * Eingebaute Farb-Themes — werden nach dem Style-Load per setPaintProperty angewendet.
 * Alle Farben sind CSS-Hex-Werte.
 */
const VM_BUILT_IN_THEMES = {
    dark: {
        label: 'Dark',
        colors: {
            land:              '#16161d',
            water:             '#0a1520',
            green:             '#1a2a18',
            farmland:          '#1e2b1a',
            road_major:        '#2a3850',
            road_minor:        '#1e1e2a',
            road_casing:       '#1a1a2a',
            rail:              '#252538',
            building:          '#1e1e2e',
            outline:           '#2a2a3a',
            label:             '#b8c8d8',
            label_halo:        '#16161d',
            road_label:        '#c8d8e8',
            customize_outlines: true,
        },
    },
    warm: {
        label: 'Warm',
        colors: {
            land:              '#f4ead6',
            water:             '#a8c8e0',
            green:             '#9abf6a',
            farmland:          '#cfe8a0',
            road_major:        '#e0a048',
            road_minor:        '#e8d8b8',
            road_casing:       '#c89040',
            rail:              '#c09060',
            building:          '#d0b88c',
            outline:           '#c0a878',
            label:             '#3c2c18',
            label_halo:        '#f4ead6',
            road_label:        '#3c2c18',
            customize_outlines: true,
        },
    },
    mono: {
        label: 'Mono',
        colors: {
            land:              '#eeeeee',
            water:             '#c0ccd4',
            green:             '#c4d4c0',
            farmland:          '#dce4d8',
            road_major:        '#aaaaaa',
            road_minor:        '#d0d0d0',
            road_casing:       '#888888',
            rail:              '#909090',
            building:          '#c4c4c4',
            outline:           '#aaaaaa',
            label:             '#282828',
            label_halo:        '#ffffff',
            road_label:        '#222222',
            customize_outlines: true,
        },
    },
};

/**
 * Allgemeiner Proxy-URL-Helper (außerhalb beider Klassen nutzbar).
 * @param {string} url
 * @returns {string}
 */
function vmProxyUrl(url) {
    return window.location.origin + '/?rex_api_vector_maps_proxy=1&target_url=' + encodeURIComponent(url);
}

function vmProxyStyleUrl(nameOrUrl) {
    if (nameOrUrl === 'satellite') return VM_SATELLITE_STYLE;
    if (nameOrUrl.startsWith('http')) return vmProxyUrl(nameOrUrl);
    // Bekannte OFM-Styles direkt weiterleiten
    if (VM_OFM_STYLES.includes(nameOrUrl)) {
        return vmProxyUrl('https://tiles.openfreemap.org/styles/' + nameOrUrl);
    }
    // Theme-Name (eingebaut oder custom) → liberty als Basis; Farben werden nach Load angewendet
    return vmProxyUrl('https://tiles.openfreemap.org/styles/liberty');
}

/** Fonts, die OpenFreeMap tatsächlich hostet */
const VM_OFM_KNOWN_FONTS = ['Noto Sans Regular', 'Noto Sans Bold', 'Noto Sans Italic', 'Noto Sans Bold Italic'];

function vmTransformRequest(url) {
    // OFM-Glyphen: nur ersten Font verwenden + auf bekannte OFM-Fonts normalisieren
    if (url.includes('tiles.openfreemap.org/fonts/')) {
        url = url.replace(/(openfreemap\.org\/fonts\/)([^/]+)(\/\d)/, (_, pre, stack, after) => {
            const first = decodeURIComponent(stack).split(',')[0].trim();
            const isBold = /bold/i.test(first);
            const mapped = VM_OFM_KNOWN_FONTS.includes(first)
                ? first
                : isBold ? 'Noto Sans Bold' : 'Noto Sans Regular';
            return pre + encodeURIComponent(mapped) + after;
        });
    }
    const proxied = [
        'tiles.openfreemap.org/',
        'api.maptiler.com/',
        'tiles.stadiamaps.com/',
        'cdn.protomaps.com/',
        'tile.openstreetmap.org/',
        'router.project-osrm.org/',
        'overpass-api.de/',
    ];
    if (proxied.some(h => url.includes(h))) return { url: vmProxyUrl(url) };
    return { url };
}

/**
 * Wendet ein Theme-Farbobjekt auf eine geladene MapLibre-Karte an.
 * Iteriert alle Layer und überschreibt Farb-Properties nach Source-Layer und Typ.
 * @param {maplibregl.Map} map
 * @param {Object} colors  — { land, water, green, road_major, road_minor, building, label, label_halo, road_label }
 */
function vmApplyTheme(map, colors) {
    if (!colors) return;
    // isStyleLoaded() prueft auch ob Tiles geladen sind -> gibt false waehrend Tiles streamen.
    // Wir brauchen nur die Layer-Definitionen (Style-JSON), nicht geladene Tiles.
    const layers = map.getStyle()?.layers;
    if (!layers || !layers.length) return;
    // Halo-Breite: numerischer Wert oder null (→ Stil-Standard beibehalten)
    const haloWidth = colors.halo_width !== undefined ? parseFloat(colors.halo_width) : null;
    // Outline/Casing-Overrides: nur anwenden wenn customize_outlines nicht explizit false
    const applyOutlines = colors.customize_outlines !== false;
    for (const layer of layers) {
        const type = layer.type;
        const sl   = layer['source-layer'] || '';
        try {
            if (type === 'background') {
                map.setPaintProperty(layer.id, 'background-color', colors.land);
            } else if (type === 'symbol') {
                // transportation_name-Layer (Straßen-/Autobahnbeschriftungen): road_label statt label
                const isRoadLabel = sl === 'transportation_name';
                const textColor   = isRoadLabel && colors.road_label ? colors.road_label : colors.label;
                map.setPaintProperty(layer.id, 'text-color', textColor);
                map.setPaintProperty(layer.id, 'text-halo-color', colors.label_halo);
                if (haloWidth !== null && !isNaN(haloWidth)) {
                    map.setPaintProperty(layer.id, 'text-halo-width', haloWidth);
                }
                // Hinweis: icon-color wirkt nur bei SDF-Sprites — OFM Liberty nutzt PNG-Sprites
                // für Autobahn-Schilder, daher kein road_shield-Feld.
            } else if (sl === 'water' || sl === 'waterway') {
                if (type === 'fill') {
                    map.setPaintProperty(layer.id, 'fill-color', colors.water);
                    try { map.setPaintProperty(layer.id, 'fill-outline-color', colors.water); } catch (_) {}
                }
                if (type === 'line') map.setPaintProperty(layer.id, 'line-color', colors.water);
            } else if (sl === 'park') {
                // Schutzgebiete / Nationalparks (eigener Source-Layer in OFM Liberty)
                const parkColor   = colors.farmland !== undefined ? colors.farmland : colors.green;
                const parkOutline = (applyOutlines && colors.outline !== undefined) ? colors.outline : parkColor;
                if (type === 'fill') {
                    map.setPaintProperty(layer.id, 'fill-color', parkColor);
                    if (applyOutlines) try { map.setPaintProperty(layer.id, 'fill-outline-color', parkOutline); } catch (_) {}
                }
                // park_outline ist ein separater line-Layer (rgba(228,241,215,1) im OFM-Standard)
                if (type === 'line' && applyOutlines) map.setPaintProperty(layer.id, 'line-color', parkOutline);
            } else if (sl === 'landcover' || sl === 'landuse') {
                // 3 Grün-Klassen: dunkles Grün (Wald), helles Grün (Wiesen/Parks), Land (sonstige Landnutzung)
                const isDark  = /wood|forest|scrub/i.test(layer.id);
                const isLight = /grass|park|nature|meadow|farm|agri|allot|wetland|crop|orchard|vineyard|garden/i.test(layer.id);
                let c;
                if (isDark)       c = colors.green;
                else if (isLight) c = colors.farmland !== undefined ? colors.farmland : colors.green;
                else              c = colors.land;
                if (type === 'fill') {
                    map.setPaintProperty(layer.id, 'fill-color', c);
                    try { map.setPaintProperty(layer.id, 'fill-outline-color', c); } catch (_) {}
                } else if (type === 'line') {
                    // Separate Outline-Layer (z.B. landuse_overlay_line) → gleiche Farbe
                    map.setPaintProperty(layer.id, 'line-color', c);
                }
            } else if (sl === 'aeroway') {
                // Flughafen-Flächen und Rollwege ähnlich wie Nebenstraßen einfärben
                if (type === 'fill') {
                    map.setPaintProperty(layer.id, 'fill-color', colors.road_minor);
                    try { map.setPaintProperty(layer.id, 'fill-outline-color', colors.road_minor); } catch (_) {}
                }
                if (type === 'line') map.setPaintProperty(layer.id, 'line-color', colors.road_minor);
            } else if (sl === 'boundary') {
                // Ländergrenzen / Verwaltungsgrenzen in Beschriftungsfarbe (gedämpft)
                if (type === 'line') {
                    try { map.setPaintProperty(layer.id, 'line-color', colors.label); } catch (_) {}
                }
            } else if (sl === 'transportation') {
                const isMajor = /motorway|trunk|primary/i.test(layer.id);
                const isCase  = /case|casing/i.test(layer.id);
                const isRail  = /rail|transit|subway|tram|light_rail|monorail|funicular/i.test(layer.id);
                if (type === 'line') {
                    if (isCase) {
                        // Casing/Outline-Layer der Straßen — nur wenn customize_outlines aktiv
                        if (applyOutlines) {
                            const casingColor = colors.road_casing !== undefined ? colors.road_casing : colors.road_minor;
                            map.setPaintProperty(layer.id, 'line-color', casingColor);
                        }
                    } else {
                        let lineColor;
                        if (isRail)       lineColor = colors.rail !== undefined ? colors.rail : colors.road_minor;
                        else if (isMajor) lineColor = colors.road_major;
                        else              lineColor = colors.road_minor;
                        map.setPaintProperty(layer.id, 'line-color', lineColor);
                    }
                }
            } else if (sl === 'building') {
                const buildingOutline = (applyOutlines && colors.outline !== undefined) ? colors.outline : colors.building;
                if (type === 'fill') {
                    map.setPaintProperty(layer.id, 'fill-color', colors.building);
                    if (applyOutlines) try { map.setPaintProperty(layer.id, 'fill-outline-color', buildingOutline); } catch (_) {}
                } else if (type === 'line') {
                    // Separate Gebäude-Outline-Layer — nur wenn customize_outlines aktiv
                    if (applyOutlines) map.setPaintProperty(layer.id, 'line-color', buildingOutline);
                } else if (type === 'fill-extrusion') {
                    // 3D-Gebäude (dynamisch via vmAdd3dBuildings / add3dBuildings hinzugefügt)
                    map.setPaintProperty(layer.id, 'fill-extrusion-color', colors.building);
                }
            }
        } catch (_) { /* Einige Paint-Properties sind schreibgeschützt — ignorieren */ }
    }
}

/**
 * Lädt ein Theme (eingebaut oder custom vom Server) und wendet es auf die Karte an.
 * Speichert die Farben in map._vmThemeColors für styledata-Wiederherstellung.
 * @param {maplibregl.Map} map
 * @param {string} themeName
 */
async function vmLoadAndApplyTheme(map, themeName) {
    let colors;
    if (VM_BUILT_IN_THEMES[themeName]) {
        colors = VM_BUILT_IN_THEMES[themeName].colors;
    } else {
        try {
            const resp = await fetch(window.location.origin + '/?rex_api_vector_maps_theme=' + encodeURIComponent(themeName), { cache: 'no-store' });
            if (!resp.ok) { console.warn('<vectormap> Theme nicht gefunden:', themeName); return; }
            const data = await resp.json();
            colors = data.colors;
        } catch (e) {
            console.warn('<vectormap> Theme-Ladefehler:', e);
            return;
        }
    }
    // Farben speichern BEVOR vmApplyTheme, damit styledata-Handler sie wiederherstellen kann
    map._vmThemeColors = colors;
    vmApplyTheme(map, colors);
    // Safety-Net: beim idle-Event nochmals anwenden falls Tiles-Ladevorgang
    // die Anwendung verzoegert hat (Race Condition mit isStyleLoaded())
    map.once('idle', () => { if (map._vmThemeColors) vmApplyTheme(map, map._vmThemeColors); });
}

/**
 * Setzt die Kartensprache durch direktes Manipulieren der text-field-Expressions
 * aller Symbol-Layer. Implementiert die offizielle OFM-Empfehlung (Issue #22):
 *   – name_XX  (OFM-internes Unterstriche-Feld, z. B. name_de)
 *   – name:XX  (OSM-Standard, z. B. name:de)
 *   – name     (lokaler/nativer Name als Fallback)
 * Non-Latin-Schriften (CJK, Arabisch …) werden zweizeilig dargestellt:
 *   name:latin + Zeilenumbruch + name:nonlatin
 * Straßen-/Autobahn-Nummern ('ref'-Expressions) werden übersprungen.
 * @param {maplibregl.Map} map
 * @param {string} lang  — ISO-639-1 (z. B. 'de', 'en', 'fr')
 */
function vmSetLanguage(map, lang) {
    if (!lang || !map.isStyleLoaded()) return;
    // Zeilentrennzeichen: bei Linien/Highways Leerzeichen, sonst Zeilenumbruch
    const sepLine  = ' ';
    const sepOther = '\n';
    const layers = map.getStyle().layers;
    for (const layer of layers) {
        if (layer.type !== 'symbol') continue;
        try {
            const tf = map.getLayoutProperty(layer.id, 'text-field');
            // Leer oder nur ref-Expressions (Straßennummern) → überspringen
            if (tf === undefined || tf === null || tf === '') continue;
            if (Array.isArray(tf) && tf[0] === 'to-string' &&
                Array.isArray(tf[1]) && tf[1][0] === 'get' && tf[1][1] === 'ref') continue;

            const isLine = layer.id.includes('line') || layer.id.includes('highway');
            const sep    = isLine ? sepLine : sepOther;

            // Reihenfolge: OFM-Unterstriche → OSM-Doppelpunkt → nativer Name
            const parts = [
                ['get', 'name_' + lang],
                ['get', 'name:' + lang],
                ['get', 'name'],
            ];

            map.setLayoutProperty(layer.id, 'text-field', [
                'case',
                ['has', 'name:nonlatin'],
                // Zweizeilig: bevorzugte-Sprache\nnon-latin (für Arabisch, CJK …)
                ['concat',
                    ['coalesce', ['get', 'name:' + lang], ['get', 'name:latin']],
                    sep,
                    ['get', 'name:nonlatin'],
                ],
                // Einzeilig: coalesce über name_XX, name:XX, name
                ['coalesce', ...parts],
            ]);
        } catch (_) { /* schreibgeschützte Layer ignorieren */ }
    }
}

/**
 * Wird vom Custom Element (connectedCallback) und von VectorMapPicker.initAll() aufgerufen.
 * Setzt Höhe sofort, baut die eigentliche Karte lazily via IntersectionObserver.
 * @param {HTMLElement} el
 */
function initVectorMap(el) {
    if (el._vmInitialized) return;
    el._vmInitialized = true;
    el.classList.add('vm-initialized');

    // Höhe sofort setzen — Layout-Stabilität auch vor dem WebGL-Aufbau
    const height = el.getAttribute('height') || '400';
    el.style.display   = 'block';
    el.style.height    = /^\d+$/.test(height) ? height + 'px' : height;
    el.style.position  = 'relative';
    el.style.overflow  = 'hidden';

    // Lazy init via IntersectionObserver — verhindert „Too many active WebGL contexts"
    // wenn viele <vectormap>-Elemente gleichzeitig auf der Seite vorhanden sind
    if ('IntersectionObserver' in window) {
        const obs = new IntersectionObserver((entries, o) => {
            if (!entries[0].isIntersecting) return;
            o.disconnect();
            vmEnqueueBuild(el);
        }, { rootMargin: '50px 0px' });
        obs.observe(el);
        return;
    }
    vmEnqueueBuild(el);
}

/**
 * Erstellt die MapLibre-Karte für ein <vectormap>-Element.
 * Wird von initVectorMap() aufgerufen — lazy sobald das Element in den Viewport scrollt.
 * @param {HTMLElement} el
 */
/** Alle aktiven Map-Instanzen (FIFO – älteste wird bei Limit destroyed) */
const VM_ACTIVE_MAPS = [];
const VM_MAX_MAPS = 10;

function vmRegisterMap(map) {
    VM_ACTIVE_MAPS.push(map);
    while (VM_ACTIVE_MAPS.length > VM_MAX_MAPS) {
        const oldest = VM_ACTIVE_MAPS.shift();
        try { oldest.remove(); } catch (_) {}
    }
}

/** Sequentielle Build-Queue — verhindert gleichzeitigen WebGL-Context-Aufbau */
const VM_BUILD_QUEUE = [];
let   VM_BUILD_RUNNING = false;

function vmEnqueueBuild(el) {
    VM_BUILD_QUEUE.push(el);
    if (!VM_BUILD_RUNNING) vmDrainBuildQueue();
}

function vmDrainBuildQueue() {
    if (VM_BUILD_QUEUE.length === 0) { VM_BUILD_RUNNING = false; return; }
    VM_BUILD_RUNNING = true;
    const el = VM_BUILD_QUEUE.shift();
    vmBuildMap(el);
    // 150 ms Pause zwischen Maps — WebGL-Context sauber aufgebaut
    setTimeout(vmDrainBuildQueue, 150);
}

function vmBuildMap(el) {
    if (el._vmMapBuilt) return;
    el._vmMapBuilt = true;

    // center="lat,lng" hat Priorität, dann einzelne lat/lng-Attribute, sonst Deutschland-Mitte
    let startLat, startLng;
    const centerStr = el.getAttribute('center');
    const latAttr   = el.getAttribute('lat');
    const lngAttr   = el.getAttribute('lng');
    if (centerStr) {
        [startLat, startLng] = centerStr.split(',').map(Number);
    } else if (latAttr && lngAttr) {
        startLat = parseFloat(latAttr);
        startLng = parseFloat(lngAttr);
    } else {
        startLat = 51.165691;
        startLng = 10.451526;
    }
    const zoom        = parseFloat(el.getAttribute('zoom')      || '6');
    const pitch       = parseFloat(el.getAttribute('pitch')     || '0');
    const bearing     = parseFloat(el.getAttribute('bearing')   || '0');
    const mapStyle    = el.getAttribute('map-style') || 'liberty';
    const interactive = el.getAttribute('interactive') !== 'false';
    const minZoom     = el.hasAttribute('min-zoom') ? parseFloat(el.getAttribute('min-zoom')) : 0;
    const maxZoom     = el.hasAttribute('max-zoom') ? parseFloat(el.getAttribute('max-zoom')) : 22;
    // Sprache: Attribut > html[lang]-Attribut > navigator.language > 'de'
    const lang = el.getAttribute('language')
        || (document.documentElement.lang || navigator.language || 'de').slice(0, 2);
    // Theme: 'theme'-Attribut ODER map-style falls kein bekannter OFM-Stil und kein Raster-Stil
    const isRaster    = VM_RASTER_STYLES.includes(mapStyle);
    const isThemeStyle = !isRaster && !VM_OFM_STYLES.includes(mapStyle) && !mapStyle.startsWith('http');
    const activeTheme  = el.getAttribute('theme') || (isThemeStyle ? mapStyle : null);

    // Karten-Container
    const container = document.createElement('div');
    container.style.width  = '100%';
    container.style.height = '100%';
    el.appendChild(container);

    const map = new maplibregl.Map({
        container,
        style: vmProxyStyleUrl(mapStyle),
        center: [startLng, startLat],
        zoom,
        minZoom,
        maxZoom,
        pitch,
        bearing,
        maxPitch: 60,
        interactive,
        attributionControl: !el.hasAttribute('no-attribution'),
        transformRequest: (url) => vmTransformRequest(url),
    });

    el._vmMap = map;
    vmRegisterMap(map);

    // Fehlende Sprite-Icons stumm ignorieren (verhindert Console-Spam)
    map.on('styleimagemissing', (e) => {
        if (!map.hasImage(e.id)) map.addImage(e.id, { width: 1, height: 1, data: new Uint8ClampedArray(4) });
    });

    if (!el.hasAttribute('no-navigation')) {
        map.addControl(new maplibregl.NavigationControl());
    }

    if (el.hasAttribute('locate')) {
        vmAddLocateButton(el, map);
    }

    if (el.hasAttribute('show-satellite')) {
        vmAddSatelliteToggle(el, map, mapStyle);
    }

    // Route-Panel (interaktive Adresssuche) — vor map.on('load') damit Panel sofort im DOM ist
    if (el.hasAttribute('route-panel')) {
        vmAddRoutePanel(el, map);
    }

    const has3d = el.hasAttribute('3d');

    // Nearby-Suche hat eigenen load-Handler, damit Geolocation-Wartezeit korrekt gehandelt wird
    if (el.hasAttribute('nearby')) {
        const nearbyFilter = el.getAttribute('nearby');
        const nearbyRadius = parseInt(el.getAttribute('nearby-radius') || '1000', 10);
        const nearbyLabel  = el.getAttribute('nearby-label') || '';
        const nearbyLocate = el.hasAttribute('nearby-locate');

        map.on('load', () => {
            if (!isRaster) vmSetLanguage(map, lang);
            if (has3d && !isRaster) vmAdd3dBuildings(map);
            vmAddMarkers(el, map);
            vmLoadGeoJson(el, map);
            if (activeTheme && !isRaster) vmLoadAndApplyTheme(map, activeTheme);
            if (nearbyLocate) {
                vmStartNearbyWithLocate(el, map, nearbyFilter, nearbyRadius, nearbyLabel);
            } else {
                const c = map.getCenter();
                vmFetchNearby(el, map, nearbyFilter, nearbyRadius, nearbyLabel, c.lat, c.lng);
            }
        });
    } else {
        map.on('load', () => {
            // Sprache setzen (nur bei Vektor-Stilen)
            if (!isRaster) vmSetLanguage(map, lang);
            if (has3d && !isRaster) vmAdd3dBuildings(map);
            vmAddMarkers(el, map);
            vmLoadGeoJson(el, map);

            // Automatisches Routing nur wenn KEIN interaktives Panel vorhanden
            const routeFrom = el.getAttribute('route-from');
            const routeTo   = el.getAttribute('route-to');
            if (routeFrom && routeTo && !el.hasAttribute('route-panel')) {
                vmDrawRoute(el, map, routeFrom, routeTo, el.getAttribute('route-mode') || 'driving');
            }

            const flyTo = el.getAttribute('fly-to');
            if (flyTo) {
                const [fLat, fLng, fZoom] = flyTo.split(',').map(Number);
                const delay = parseInt(el.getAttribute('fly-delay') || '0', 10);

                // Erst fliegen wenn das Element wirklich im Viewport sichtbar ist
                const doFly = () => setTimeout(() => map.flyTo({
                    center:   [fLng, fLat],
                    zoom:     fZoom || (zoom + 5),
                    duration: 2500,
                }), delay);

                if ('IntersectionObserver' in window) {
                    let hasFired = false;
                    const obs = new IntersectionObserver((entries) => {
                        if (!hasFired && entries[0].isIntersecting) {
                            hasFired = true;
                            obs.disconnect();
                            doFly();
                        }
                    }, { threshold: 0.25 });
                    obs.observe(el);
                } else {
                    // Fallback: sofort fliegen
                    doFly();
                }
            }

            // Theme anwenden (nach Routing/Markern, damit Layer-Reihenfolge stimmt)
            if (activeTheme && !isRaster) vmLoadAndApplyTheme(map, activeTheme);
        });
    }

    // styledata: 3D-Gebäude + Theme-Wiederherstellung (z.B. nach Satellit-Toggle)
    // Debounce-Flag verhindert Endlosschleife: setPaintProperty feuert selbst styledata
    map.on('styledata', () => {
        if (!map.isStyleLoaded() || map._vmApplyingTheme) return;
        if (has3d && !isRaster) vmAdd3dBuildings(map);
        if (map._vmThemeColors && !isRaster) {
            map._vmApplyingTheme = true;
            vmApplyTheme(map, map._vmThemeColors);
            // Asynchron zuruecksetzen: styledata aus setPaintProperty kommt via rAF
            requestAnimationFrame(() => { map._vmApplyingTheme = false; });
        }
    });

    // Sprache über JS-API änderbar machen
    el._vmLang = lang;
    el.setLanguage = (newLang) => {
        el._vmLang = newLang;
        vmSetLanguage(map, newLang);
    };
}

function vmAdd3dBuildings(map) {
    if (map.getLayer('vm-el-3d')) return;
    const sources = map.getStyle().sources;
    const srcName = Object.keys(sources).find(k => sources[k].type === 'vector') || 'openmaptiles';
    map.addLayer({
        id:            'vm-el-3d',
        source:        srcName,
        'source-layer': 'building',
        filter:        ['==', 'extrude', 'true'],
        type:          'fill-extrusion',
        minzoom:       15,
        paint: {
            'fill-extrusion-color':   '#aaa',
            'fill-extrusion-height':  ['interpolate', ['linear'], ['zoom'], 15, 0, 15.05, ['get', 'height']],
            'fill-extrusion-base':    ['interpolate', ['linear'], ['zoom'], 15, 0, 15.05, ['get', 'min_height']],
            'fill-extrusion-opacity': 0.6,
        },
    });
}

/**
 * Erstellt ein DOM-Element für einen benutzerdefinierten Marker (icon / html).
 * @param {{icon?: string, html?: string, size?: number[], anchor?: string}} m
 * @returns {HTMLElement|null}
 */
function vmCreateMarkerEl(m) {
    if (m.html) {
        const div = document.createElement('div');
        div.className = 'vm-custom-marker';
        div.innerHTML = m.html;
        return div;
    }
    if (m.icon) {
        const img = document.createElement('img');
        img.src = m.icon;
        img.className = 'vm-icon-marker';
        img.alt = '';
        if (m.size && m.size.length >= 2) {
            img.style.width  = m.size[0] + 'px';
            img.style.height = m.size[1] + 'px';
        } else {
            img.style.width  = '32px';
            img.style.height = '32px';
        }
        return img;
    }
    return null;
}

function vmAddMarkers(el, map) {
    const markerStr = el.getAttribute('markers');
    if (!markerStr) return;
    let markers;
    try { markers = JSON.parse(markerStr); } catch (e) {
        console.warn('<vectormap> markers JSON ungültig:', e); return;
    }
    if (el.hasAttribute('cluster')) {
        vmAddClusteredMarkers(map, markers);
    } else {
        markers.forEach(m => {
            const markerEl   = vmCreateMarkerEl(m);
            const markerOpts = markerEl
                ? { element: markerEl, anchor: m.anchor || 'bottom' }
                : { color: m.color || '#2b7095' };
            const marker = new maplibregl.Marker(markerOpts)
                .setLngLat([parseFloat(m.lng), parseFloat(m.lat)]);
            if (m.popup || m.label) {
                marker.setPopup(new maplibregl.Popup({ offset: 25 }).setHTML(m.popup || m.label));
            }
            marker.addTo(map);
        });
    }
    // Kartenausschnitt an alle Marker anpassen (wenn > 1 Marker und nicht deaktiviert)
    if (markers.length > 1 && el.getAttribute('fit-bounds') !== 'false') {
        vmFitMarkerBounds(map, markers);
    }
}

/**
 * Passt den Kartenausschnitt so an, dass alle Marker sichtbar sind.
 * @param {maplibregl.Map} map
 * @param {Array<{lat: number|string, lng: number|string}>} markers
 */
function vmFitMarkerBounds(map, markers) {
    const first = [parseFloat(markers[0].lng), parseFloat(markers[0].lat)];
    const bounds = markers.reduce(
        (b, m) => b.extend([parseFloat(m.lng), parseFloat(m.lat)]),
        new maplibregl.LngLatBounds(first, first)
    );
    map.fitBounds(bounds, { padding: 60, maxZoom: 16, duration: 500 });
}

function vmAddClusteredMarkers(map, markers) {
    const geojson = {
        type: 'FeatureCollection',
        features: markers.map(m => ({
            type: 'Feature',
            properties: { popup: m.popup || m.label || '', color: m.color || '#2b7095' },
            geometry:   { type: 'Point', coordinates: [parseFloat(m.lng), parseFloat(m.lat)] },
        })),
    };

    // Einzigartiger Quell-Name pro Karte (mehrere Karten auf einer Seite)
    const srcId = 'vm-cluster-' + Math.random().toString(36).slice(2, 7);
    map.addSource(srcId, {
        type: 'geojson', data: geojson,
        cluster: true, clusterMaxZoom: 14, clusterRadius: 50,
    });

    // Cluster-Kreis
    map.addLayer({ id: srcId + '-c', type: 'circle', source: srcId, filter: ['has', 'point_count'],
        paint: {
            'circle-color':   ['step', ['get', 'point_count'], '#2b7095', 10, '#1a5276', 30, '#0e3347'],
            'circle-radius':  ['step', ['get', 'point_count'], 18, 10, 26, 30, 36],
            'circle-opacity': 0.88,
        },
    });
    // Anzahl im Cluster
    map.addLayer({ id: srcId + '-n', type: 'symbol', source: srcId, filter: ['has', 'point_count'],
        layout: { 'text-field': ['get', 'point_count_abbreviated'], 'text-size': 12 },
        paint:  { 'text-color': '#fff' },
    });
    // Einzel-Punkte
    map.addLayer({ id: srcId + '-p', type: 'circle', source: srcId, filter: ['!', ['has', 'point_count']],
        paint: { 'circle-color': '#2b7095', 'circle-radius': 7, 'circle-stroke-width': 2, 'circle-stroke-color': '#fff' },
    });

    // Klick auf Cluster → zoom rein
    map.on('click', srcId + '-c', async (e) => {
        const clusterId = e.features[0].properties.cluster_id;
        const zoom = await map.getSource(srcId).getClusterExpansionZoom(clusterId);
        map.easeTo({ center: e.features[0].geometry.coordinates, zoom });
    });

    // Klick auf Einzel-Punkt → Popup
    map.on('click', srcId + '-p', (e) => {
        const { popup } = e.features[0].properties;
        if (!popup) return;
        new maplibregl.Popup()
            .setLngLat(e.features[0].geometry.coordinates.slice())
            .setHTML(popup)
            .addTo(map);
    });

    [srcId + '-c', srcId + '-p'].forEach(layer => {
        map.on('mouseenter', layer, () => { map.getCanvas().style.cursor = 'pointer'; });
        map.on('mouseleave', layer, () => { map.getCanvas().style.cursor = ''; });
    });
}

/**
 * Entfernt eine bestehende Route (Layer, Source, Marker, Badge) von der Karte.
 * @param {HTMLElement} el
 * @param {maplibregl.Map} map
 */
function vmClearRoute(el, map) {
    // Unterstützt sowohl einzelne OSRM-Layer als auch mehrere Transit-Legs
    const layers  = el._vmRouteLayers  || ['vm-route-casing', 'vm-route-line'];
    const sources = el._vmRouteSources || ['vm-route'];
    layers.forEach(id  => { try { if (map.getLayer(id))  map.removeLayer(id);  } catch (_) {} });
    sources.forEach(id => { try { if (map.getSource(id)) map.removeSource(id); } catch (_) {} });
    el._vmRouteLayers  = null;
    el._vmRouteSources = null;
    if (el._vmRouteMarkers) {
        el._vmRouteMarkers.forEach(m => m.remove());
        el._vmRouteMarkers = [];
    }
    const badge = el.querySelector('.vm-route-badge');
    if (badge) badge.remove();
}

/**
 * Konvertiert einen Eingabe-String in [lat, lng].
 * Akzeptiert:
 *   - Koordinaten: "52.52,13.405" (lat,lng)
 *   - Adressen:    "Unter den Linden 1, Berlin"
 * Adressen werden über den Proxy an Nominatim geocodiert und gecacht.
 * @param {string} input
 * @returns {Promise<[number, number, string]>} [lat, lng, label]
 */
async function vmResolveLocation(input) {
    const trimmed = input.trim();
    // Koordinaten-Pattern: -?Zahl,Zahl (Lat,Lng)
    if (/^-?\d+\.?\d*\s*,\s*-?\d+\.?\d*$/.test(trimmed)) {
        const [lat, lng] = trimmed.split(',').map(Number);
        return [lat, lng, trimmed];
    }
    // Adresse → Nominatim via Proxy)
    const nominatimUrl = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(trimmed);
    const resp = await fetch(vmProxyUrl(nominatimUrl));
    if (!resp.ok) throw new Error('Geocoding-Fehler: HTTP ' + resp.status);
    const data = await resp.json();
    if (!data || !data[0]) throw new Error('Adresse nicht gefunden: ' + trimmed);
    const lat = parseFloat(data[0].lat);
    const lng = parseFloat(data[0].lon);
    const label = data[0].display_name
        ? data[0].display_name.split(',').slice(0, 2).join(', ') // körzen
        : trimmed;
    return [lat, lng, label];
}

/**
 * Gibt ein Pfeil-/Symbol-Zeichen für ein OSRM-Maneuver-Objekt zurück.
 * @param {{type:string, modifier:string}} maneuver
 */
function vmManeuverIcon(maneuver) {
    const t = maneuver.type     || '';
    const m = maneuver.modifier || '';
    if (t === 'depart')  return '▶';
    if (t === 'arrive')  return '⚑';
    if (t === 'roundabout' || t === 'rotary') return '↻';
    if (t === 'exit roundabout' || t === 'exit rotary') return '↗';
    if (m === 'uturn')          return '↩';
    if (m === 'sharp left')     return '↰';
    if (m === 'sharp right')    return '↱';
    if (m === 'left')           return '←';
    if (m === 'right')          return '→';
    if (m === 'slight left')    return '↖';
    if (m === 'slight right')   return '↗';
    return '↑'; // straight, new name, continue, end of road
}

/**
 * Gibt einen deutschen Beschriftungstext für einen OSRM-Step zurück.
 * @param {{name:string, maneuver:{type:string, modifier:string}}} step
 */
function vmManeuverLabel(step) {
    const t    = step.maneuver.type     || '';
    const m    = step.maneuver.modifier || '';
    const name = step.name || '';
    const dirs = {
        'left':         'links',
        'right':        'rechts',
        'slight left':  'leicht links',
        'slight right': 'leicht rechts',
        'sharp left':   'scharf links',
        'sharp right':  'scharf rechts',
        'straight':     'geradeaus',
        'uturn':        'wenden',
    };
    if (t === 'depart')                                     return name ? 'Start: ' + name : 'Startpunkt';
    if (t === 'arrive')                                     return name ? 'Ziel: '  + name : 'Ziel erreicht';
    if (t === 'roundabout' || t === 'rotary')               return 'Kreisverkehr' + (name ? ': ' + name : '');
    if (t === 'exit roundabout' || t === 'exit rotary')     return name || 'Ausfahrt';
    if (t === 'off ramp' || t === 'on ramp')                return name || (t === 'off ramp' ? 'Ausfahrt' : 'Auffahrt');
    if (name) return name;
    const dir = dirs[m];
    return dir ? dir.charAt(0).toUpperCase() + dir.slice(1) : 'Weiterfahren';
}

/**
 * Formatiert eine Meter-Distanz als lesbaren String.
 * @param {number} meters
 */
function vmFormatDistStep(meters) {
    if (!meters) return '';
    if (meters >= 1000) return (meters / 1000).toFixed(1) + ' km';
    return Math.round(meters) + ' m';
}

/**
 * Rendert die Abbiegehinweis-Liste in das Route-Panel.
 * Ersetzt eine evtl. vorhandene ältere Liste.
 * @param {HTMLElement} panel  Das .vm-route-panel-Element
 * @param {Array}       steps  OSRM-Step-Array
 * @param {maplibregl.Map} map
 */
function vmRenderSteps(panel, steps, map) {
    const old = panel.querySelector('.vm-rp-steps');
    if (old) old.remove();
    if (!steps || !steps.length) return;

    const wrap = document.createElement('div');
    wrap.className = 'vm-rp-steps';

    const header = document.createElement('div');
    header.className = 'vm-rp-steps-header';
    header.innerHTML = `<span class="vm-rp-steps-toggle">▾</span><span>Abbiegehinweise</span><span class="vm-rp-steps-count">${steps.length}</span>`;

    const list = document.createElement('div');
    list.className = 'vm-rp-steps-list';

    steps.forEach(step => {
        const [lng, lat] = step.maneuver.location;
        const icon  = vmManeuverIcon(step.maneuver);
        const label = vmManeuverLabel(step);
        const dist  = vmFormatDistStep(step.distance);
        const item  = document.createElement('div');
        item.className = 'vm-rp-step';
        item.innerHTML =
            `<span class="vm-rp-step-icon">${icon}</span>` +
            `<span class="vm-rp-step-text">${label}</span>` +
            `<span class="vm-rp-step-dist">${dist}</span>`;
        item.addEventListener('click', () => map.flyTo({ center: [lng, lat], zoom: 16, duration: 700 }));
        list.appendChild(item);
    });

    let open = true;
    header.addEventListener('click', () => {
        open = !open;
        list.style.display = open ? '' : 'none';
        header.querySelector('.vm-rp-steps-toggle').textContent = open ? '▾' : '▸';
    });

    wrap.appendChild(header);
    wrap.appendChild(list);
    panel.appendChild(wrap);
}

async function vmDrawRoute(el, map, fromStr, toStr, mode) {
    let fromLat, fromLng, fromLabel, toLat, toLng, toLabel;
    try {
        [fromLat, fromLng, fromLabel] = await vmResolveLocation(fromStr);
        [toLat,   toLng,   toLabel]   = await vmResolveLocation(toStr);
    } catch (resErr) {
        console.warn('<vectormap> Geocoding fehlgeschlagen:', resErr);
        return;
    }

    // Bestehende Route entfernen bevor neue gezeichnet wird
    vmClearRoute(el, map);

    // OSRM-Backends: Jedes Fahrprofil hat einen eigenen öffentlichen Server
    const vmOsrmBackends = {
        driving: 'https://router.project-osrm.org/route/v1/driving',
        cycling: 'https://routing.openstreetmap.de/routed-bike/route/v1/driving',
        walking: 'https://routing.openstreetmap.de/routed-foot/route/v1/driving',
    };
    const backendUrl = vmOsrmBackends[mode] || vmOsrmBackends.driving;
    const osrmUrl = `${backendUrl}/${fromLng},${fromLat};${toLng},${toLat}?overview=full&geometries=geojson&steps=true`;
    try {
        const resp = await fetch(vmProxyUrl(osrmUrl));
        const data = await resp.json();
        if (!data.routes || !data.routes[0]) return;

        const route = data.routes[0];
        const geom  = route.geometry;

        map.addSource('vm-route', { type: 'geojson', data: geom });
        // Weißes Casing + blaue Linie
        map.addLayer({ id: 'vm-route-casing', type: 'line', source: 'vm-route',
            layout: { 'line-join': 'round', 'line-cap': 'round' },
            paint:  { 'line-color': '#fff', 'line-width': 7, 'line-opacity': 0.7 },
        });
        map.addLayer({ id: 'vm-route-line', type: 'line', source: 'vm-route',
            layout: { 'line-join': 'round', 'line-cap': 'round' },
            paint:  { 'line-color': '#2b7095', 'line-width': 4, 'line-opacity': 0.95 },
        });
        el._vmRouteLayers  = ['vm-route-casing', 'vm-route-line'];
        el._vmRouteSources = ['vm-route'];

        // Start- und Ziel-Marker (getrackt für vmClearRoute)
        const fromMarker = new maplibregl.Marker({ color: '#27ae60' }).setLngLat([fromLng, fromLat])
            .setPopup(new maplibregl.Popup().setText('Start: ' + fromLabel)).addTo(map);
        const toMarker = new maplibregl.Marker({ color: '#e74c3c' }).setLngLat([toLng, toLat])
            .setPopup(new maplibregl.Popup().setText('Ziel: ' + toLabel)).addTo(map);
        el._vmRouteMarkers = [fromMarker, toMarker];

        // Info-Badge
        const dist = (route.distance / 1000).toFixed(1);
        const mins = Math.round(route.duration / 60);
        const modeLabels = { driving: 'Auto', cycling: 'Rad', walking: 'Fuß' };
        const badge = document.createElement('div');
        badge.className = 'vm-route-badge';
        badge.innerHTML = `<span>${modeLabels[mode] || mode} ${dist}&thinsp;km</span><span>~&thinsp;${mins}&thinsp;min</span>`;
        el.appendChild(badge);

        // Steps für Turn-by-Turn speichern (flach über alle Legs)
        el._vmRouteSteps = (route.legs || []).flatMap(leg => leg.steps || []);

        // Bounds → Route komplett sichtbar
        const coords = geom.coordinates;
        const bounds = coords.reduce((b, c) => b.extend(c),
            new maplibregl.LngLatBounds(coords[0], coords[0]));
        map.fitBounds(bounds, { padding: 50 });
    } catch (err) {
        console.warn('<vectormap> Routing fehlgeschlagen:', err);
    }
}

/**
 * Fügt ein interaktives Route-Panel mit Adress-Autocomplete zur Karte hinzu.
 * Aktiviert durch das Boolean-Attribut `route-panel`.
 * Initiale Werte aus route-from / route-to / route-mode werden übernommen.
 * @param {HTMLElement} el
 * @param {maplibregl.Map} map
 */



function vmAddRoutePanel(el, map) {
    const initFrom  = el.getAttribute('route-from') || '';
    const initTo    = el.getAttribute('route-to')   || '';
    const initMode  = el.getAttribute('route-mode') || 'driving';
    const toLocked  = el.hasAttribute('route-to-locked');
    const noSteps   = el.hasAttribute('route-no-steps');

    const panel = document.createElement('div');
    panel.className = 'vm-route-panel';
    panel.innerHTML = `
        <div class="vm-rp-field">
            <span class="vm-rp-label">Von</span>
            <div class="vm-rp-input-wrap">
                <input class="vm-rp-from" type="text" autocomplete="off"
                    placeholder="Startadresse oder Koordinaten …"
                    value="${initFrom.replace(/"/g, '&quot;')}">
                <div class="vm-rp-suggestions"></div>
            </div>
        </div>
        <div class="vm-rp-field">
            <span class="vm-rp-label">Nach</span>
            <div class="vm-rp-input-wrap">
                <input class="vm-rp-to" type="text" autocomplete="off"
                    placeholder="Zieladresse oder Koordinaten …"
                    value="${initTo.replace(/"/g, '&quot;')}"
                    ${toLocked ? 'readonly tabindex="-1"' : ''}>
                <div class="vm-rp-suggestions"></div>
            </div>
        </div>
        <div class="vm-rp-controls">
            <div class="vm-rp-modes">
                <button type="button" class="vm-rp-mode ${initMode === 'driving'  ? 'active' : ''}" data-mode="driving"  title="Auto"><svg viewBox='0 0 24 24' fill='currentColor' width='18' height='18' aria-hidden='true'><path d='M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z'/></svg></button>
                <button type="button" class="vm-rp-mode ${initMode === 'walking'  ? 'active' : ''}" data-mode="walking"  title="Zu Fuß"><svg viewBox='0 0 24 24' fill='currentColor' width='18' height='18' aria-hidden='true'><path d='M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z'/></svg></button>
                <button type="button" class="vm-rp-mode ${initMode === 'cycling'  ? 'active' : ''}" data-mode="cycling"  title="Fahrrad"><svg viewBox='0 0 24 24' fill='currentColor' width='18' height='18' aria-hidden='true'><path d='M15.5 5.5c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zM5 12c-2.8 0-5 2.2-5 5s2.2 5 5 5 5-2.2 5-5-2.2-5-5-5zm0 8.5c-1.9 0-3.5-1.6-3.5-3.5s1.6-3.5 3.5-3.5 3.5 1.6 3.5 3.5-1.6 3.5-3.5 3.5zm5.8-10l2.4-2.4.8.8c1.3 1.3 3 2.1 5 2.1V9c-1.5 0-2.7-.6-3.6-1.5l-1.9-1.9c-.5-.4-1-.6-1.6-.6s-1.1.2-1.4.6L7.8 8.4C7.3 8.8 7 9.4 7 10c0 .6.3 1.2.8 1.6L11 14v5h2v-6l-2.2-2.5zM19 12c-2.8 0-5 2.2-5 5s2.2 5 5 5 5-2.2 5-5-2.2-5-5-5zm0 8.5c-1.9 0-3.5-1.6-3.5-3.5s1.6-3.5 3.5-3.5 3.5 1.6 3.5 3.5-1.6 3.5-3.5 3.5z'/></svg></button>
            </div>
            <button type="button" class="vm-rp-calc">Route</button>
            <button type="button" class="vm-rp-clear" style="display:none" title="Route l\u00f6schen">✕</button>
        </div>`;

    el.appendChild(panel);

    const fromInput = panel.querySelector('.vm-rp-from');
    const toInput   = panel.querySelector('.vm-rp-to');
    const calcBtn   = panel.querySelector('.vm-rp-calc');
    const clearBtn  = panel.querySelector('.vm-rp-clear');
    let currentMode = initMode;

    // gesperrtes Ziel-Feld visuell kennzeichnen
    if (toLocked) {
        toInput.style.cssText += ';cursor:default;background:rgba(0,0,0,.04);color:inherit;';
    }

    // Modus-Buttons
    panel.querySelectorAll('.vm-rp-mode').forEach(btn => {
        btn.addEventListener('click', () => {
            panel.querySelectorAll('.vm-rp-mode').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentMode = btn.dataset.mode;
        });
    });

    // Autocomplete-Logik
    function setupSuggest(input) {
        const sugDiv = input.closest('.vm-rp-input-wrap').querySelector('.vm-rp-suggestions');
        let timer;

        input.addEventListener('input', () => {
            clearTimeout(timer);
            const q = input.value.trim();
            // Koordinaten nicht geocodieren
            if (q.length < 3 || /^-?\d/.test(q)) { hideSug(sugDiv); return; }
            timer = setTimeout(() => fetchSug(q, input, sugDiv), 300);
        });

        // Suggestions schließen bei Klick außerhalb
        document.addEventListener('click', (e) => {
            if (!panel.contains(e.target)) hideSug(sugDiv);
        }, { capture: true });
    }

    async function fetchSug(q, input, sugDiv) {
        const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=5&q=' + encodeURIComponent(q);
        try {
            const resp = await fetch(vmProxyUrl(url));
            if (!resp.ok) return;
            const data = await resp.json();
            showSug(data, input, sugDiv);
        } catch (_) {}
    }

    function showSug(results, input, sugDiv) {
        sugDiv.innerHTML = '';
        if (!results.length) { hideSug(sugDiv); return; }
        results.forEach(r => {
            const item = document.createElement('div');
            item.className = 'vm-rp-suggestion';
            item.textContent = r.display_name;
            item.addEventListener('mousedown', (e) => {
                e.preventDefault(); // Blur verhindern
                input.value = r.display_name;
                hideSug(sugDiv);
            });
            sugDiv.appendChild(item);
        });
        sugDiv.classList.add('vm-rp-suggestions--open');
    }

    function hideSug(sugDiv) {
        sugDiv.classList.remove('vm-rp-suggestions--open');
        sugDiv.innerHTML = '';
    }

    setupSuggest(fromInput);
    if (!toLocked) setupSuggest(toInput);  // kein Autocomplete wenn gesperrt

    // Route berechnen
    calcBtn.addEventListener('click', async () => {
        const fromVal = fromInput.value.trim();
        const toVal   = toInput.value.trim();
        if (!fromVal || !toVal) return;

        calcBtn.disabled = true;
        calcBtn.textContent = '\u2026';
        clearBtn.style.display = 'none';
        try {
            await vmDrawRoute(el, map, fromVal, toVal, currentMode);
            if (!noSteps) vmRenderSteps(panel, el._vmRouteSteps, map);
            clearBtn.style.display = '';
        } catch (e) {
            console.warn('Route-Panel Fehler:', e);
        } finally {
            calcBtn.disabled = false;
            calcBtn.textContent = 'Route';
        }
    });

    // Route löschen
    clearBtn.addEventListener('click', () => {
        vmClearRoute(el, map);
        const stepsDiv = panel.querySelector('.vm-rp-steps');
        if (stepsDiv) stepsDiv.remove();
        clearBtn.style.display = 'none';
        // Bei gesperrtem Ziel: Ziel-Marker + Popup wiederherstellen
        if (toLocked && initTo) showDestination();
    });

    // Auto-Berechnung wenn initiale Werte vorhanden
    if (initFrom && initTo) {
        const autoCalc = async () => {
            await vmDrawRoute(el, map, initFrom, initTo, currentMode);
            if (!noSteps) vmRenderSteps(panel, el._vmRouteSteps, map);
            clearBtn.style.display = '';
        };
        if (map.isStyleLoaded()) {
            autoCalc();
        } else {
            map.once('load', autoCalc);
        }
    }

    // Nur Ziel bekannt → Adresse geocodieren, Marker + Kartenzentrierung
    if (!initFrom && initTo) {
        const customPopup = el.getAttribute('route-to-popup') || null;
        const showDestination = async () => {
            try {
                const [lat, lng, label] = await vmResolveLocation(initTo);
                map.flyTo({ center: [lng, lat], zoom: 14, duration: 0 });
                const popupHtml = customPopup || ('<strong>' + label + '</strong>');
                const destPopup = new maplibregl.Popup({ offset: 25, closeOnClick: false })
                    .setHTML(popupHtml);
                const destMarker = new maplibregl.Marker({ color: '#e74c3c' })
                    .setLngLat([lng, lat])
                    .setPopup(destPopup)
                    .addTo(map);
                destMarker.togglePopup(); // Popup sofort öffnen
                if (!el._vmRouteMarkers) el._vmRouteMarkers = [];
                el._vmRouteMarkers.push(destMarker);
            } catch (_) {}
        };
        if (map.isStyleLoaded()) {
            showDestination();
        } else {
            map.once('load', showDestination);
        }
    }
}

function vmAddLocateButton(el, map) {
    const btn = document.createElement('button');
    btn.className  = 'vm-locate-widget';
    btn.type       = 'button';
    btn.title      = 'Meinen Standort anzeigen';
    btn.innerHTML  = `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/></svg>`;

    btn.addEventListener('click', () => {
        if (!navigator.geolocation) {
            alert('Ihr Browser unterstützt keine Geolokalisierung.'); return;
        }
        btn.classList.add('vm-locating');
        navigator.geolocation.getCurrentPosition(
            pos => {
                btn.classList.remove('vm-locating');
                const { longitude, latitude } = pos.coords;
                map.flyTo({ center: [longitude, latitude], zoom: 15 });
                new maplibregl.Marker({ color: '#2b7095' })
                    .setLngLat([longitude, latitude])
                    .setPopup(new maplibregl.Popup().setText('Ihr Standort'))
                    .addTo(map)
                    .togglePopup();
            },
            () => { btn.classList.remove('vm-locating'); }
        );
    });

    el.appendChild(btn);
}

/**
 * Fügt einen Satellitenbild-Toggle-Button zur Karte hinzu.
 * Wechselt zwischen dem aktuellen Vektorstil und ESRI World Imagery.
 * Nach Rückkehr zum Vektorstil werden Sprache und GeoJSON neu gesetzt.
 * Marker (HTML-Elemente) überleben Style-Wechsel automatisch.
 * @param {HTMLElement} el
 * @param {maplibregl.Map} map
 * @param {string} baseStyleName - Name des Basisstils (z.B. 'liberty')
 */
function vmAddSatelliteToggle(el, map, baseStyleName) {
    let isSat = false;

    const btn = document.createElement('button');
    btn.type  = 'button';
    btn.className = 'vm-satellite-widget';
    btn.title = 'Satellitenbild umschalten';
    btn.innerHTML = `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="2"/>
        <path d="M6.3 6.3 3 3"/>
        <path d="M17.7 6.3 21 3"/>
        <path d="M6.3 17.7 3 21"/>
        <path d="M17.7 17.7 21 21"/>
        <path d="M9 9a4 4 0 0 1 6 0"/>
        <path d="M6 6a8 8 0 0 1 12 0"/>
    </svg>`;

    btn.addEventListener('click', () => {
        isSat = !isSat;
        btn.classList.toggle('vm-satellite-widget--active', isSat);
        btn.title = isSat ? 'Zur Vektorkarte wechseln' : 'Satellitenbild umschalten';

        if (isSat) {
            map.setStyle(VM_SATELLITE_STYLE);
        } else {
            const vectorStyle = vmProxyStyleUrl(baseStyleName);
            map.setStyle(vectorStyle);
            map.once('idle', () => {
                vmSetLanguage(map, el._vmLang || 'de');
                vmLoadGeoJson(el, map);
            });
        }
    });

    el.appendChild(btn);
}

/**
 * Unterstützt einfache Ausdrücke wie: amenity=charging_station, shop=supermarket,
 * leisure=park, natural=tree sowie composite: amenity=fuel|amenity=charging_station
 * @param {string} filter   Overpass-Filter-Ausdruck
 * @param {number} radius   Suchradius in Metern
 * @param {number} lat
 * @param {number} lng
 * @returns {string} Overpass QL Query
 */
function vmBuildOverpassQuery(filter, radius, lat, lng) {
    const parts = filter.split('|').map(f => f.trim());
    // nwr = node + way + relation damit auch Supermärkte/Ladestationen als Gebäudeflächen gefunden werden.
    // out center liefert für ways/relations den Mittelpunkt als center.lat / center.lon.
    const queries = parts.flatMap(p => {
        const [k, v] = p.split('=');
        const cond = v ? `["${k}"="${v}"]` : `["${k}"]`;
        return [
            `node${cond}(around:${radius},${lat},${lng});`,
            `way${cond}(around:${radius},${lat},${lng});`,
            `relation${cond}(around:${radius},${lat},${lng});`,
        ];
    });
    return `[out:json][timeout:30];\n(\n  ${queries.join('\n  ')}\n);\nout center;`;
}

/**
 * Fragt Overpass ab und rendert Marker mit Popup-Infos auf der Karte.
 * @param {HTMLElement} el
 * @param {maplibregl.Map} map
 * @param {string} filter    Overpass-Filter z.B. "amenity=charging_station"
 * @param {number} radius    Meter
 * @param {string} labelAttr custom Popup-Label (optional)
 * @param {number} lat
 * @param {number} lng
 */
async function vmFetchNearby(el, map, filter, radius, labelAttr, lat, lng) {
    // Ladeanzeige
    const badge = document.createElement('div');
    badge.className = 'vm-nearby-badge vm-nearby-loading';
    badge.textContent = 'Suche …';
    el.appendChild(badge);

    const query = vmBuildOverpassQuery(filter, radius, lat, lng);
    const url = 'https://overpass-api.de/api/interpreter?data=' + encodeURIComponent(query);

    try {
        const resp = await fetch(vmProxyUrl(url));
        if (!resp.ok) throw new Error('HTTP ' + resp.status);
        const data = await resp.json();
        badge.remove();

        // nodes haben lat/lon direkt; ways/relations mit out center haben center.lat/center.lon
        const elements = (data.elements || []).filter(e =>
            (e.lat != null && e.lon != null) || (e.center?.lat != null)
        );
        if (!elements.length) {
            const noResult = document.createElement('div');
            noResult.className = 'vm-nearby-badge';
            noResult.textContent = 'Keine Ergebnisse gefunden';
            el.appendChild(noResult);
            setTimeout(() => noResult.remove(), 4000);
            return;
        }

        // Marker rendern
        const bounds = new maplibregl.LngLatBounds();
        elements.forEach(elem => {
            // nodes: lat/lon direkt; ways/relations: center-Koordinaten aus out center
            const eLat = elem.lat ?? elem.center?.lat;
            const eLon = elem.lon ?? elem.center?.lon;
            if (eLat == null || eLon == null) return;

            const tags    = elem.tags || {};
            const name    = tags.name || labelAttr || filter.split('=')[1] || filter;
            const address = [tags['addr:street'], tags['addr:housenumber'], tags['addr:city']]
                .filter(Boolean).join(' ');
            const phone   = tags.phone || tags['contact:phone'] || '';
            const website = tags.website || tags['contact:website'] || '';
            const operator = tags.operator || '';

            let popupHtml = `<strong>${name}</strong>`;
            if (operator && operator !== name) popupHtml += `<br><small>${operator}</small>`;
            if (address) popupHtml += `<br>${address}`;
            if (phone)   popupHtml += `<br><a href="tel:${phone}">${phone}</a>`;
            if (website) popupHtml += `<br><a href="${website}" target="_blank" rel="noopener">Website</a>`;

            const m = new maplibregl.Marker({ color: '#2b7095', scale: 0.85 })
                .setLngLat([eLon, eLat])
                .setPopup(new maplibregl.Popup({ offset: 22 }).setHTML(popupHtml))
                .addTo(map);

            // Marker tracken für späteren Reset
            if (!el._vmNearbyMarkers) el._vmNearbyMarkers = [];
            el._vmNearbyMarkers.push(m);
            bounds.extend([eLon, eLat]);
        });

        // Ergebnis-Badge (Anzahl + Radius)
        const resultBadge = document.createElement('div');
        resultBadge.className = 'vm-nearby-badge';
        resultBadge.innerHTML = `<span>📍 ${elements.length} gefunden</span><span>${(radius / 1000).toFixed(1)}&thinsp;km Radius</span>`;
        el.appendChild(resultBadge);

        // Bounds anpassen
        if (!bounds.isEmpty()) {
            map.fitBounds(bounds, { padding: 60, maxZoom: 15, duration: 600 });
        }

        // Standort-Marker (Kreuz)
        const locMarker = new maplibregl.Marker({ color: '#e74c3c' })
            .setLngLat([lng, lat])
            .setPopup(new maplibregl.Popup().setText('Suchzentrum'))
            .addTo(map);
        if (!el._vmNearbyMarkers) el._vmNearbyMarkers = [];
        el._vmNearbyMarkers.push(locMarker);

        // Suchradius-Kreis als GeoJSON
        const circleId = 'vm-nearby-radius-' + Math.random().toString(36).slice(2, 6);
        const circleGeo = vmCreateCircleGeoJson(lat, lng, radius);
        map.addSource(circleId, { type: 'geojson', data: circleGeo });
        map.addLayer({ id: circleId + '-fill', type: 'fill', source: circleId,
            paint: { 'fill-color': '#2b7095', 'fill-opacity': 0.07 } });
        map.addLayer({ id: circleId + '-line', type: 'line', source: circleId,
            paint: { 'line-color': '#2b7095', 'line-width': 1.5, 'line-dasharray': [3, 3] } });

    } catch (err) {
        badge.remove();
        console.warn('<vectormap> Nearby-Suche fehlgeschlagen:', err);
        const errBadge = document.createElement('div');
        errBadge.className = 'vm-nearby-badge vm-nearby-error';
        errBadge.textContent = 'Suche fehlgeschlagen';
        el.appendChild(errBadge);
        setTimeout(() => errBadge.remove(), 4000);
    }
}

/**
 * Erstellt ein annäherndes Kreis-GeoJSON (64-Eck) um einen Punkt.
 * @param {number} lat @param {number} lng @param {number} radiusM
 * @returns {GeoJSON}
 */
function vmCreateCircleGeoJson(lat, lng, radiusM) {
    const n = 64;
    const coords = [];
    const R = 6371000; // Erdradius in Metern
    for (let i = 0; i <= n; i++) {
        const angle = (i / n) * 2 * Math.PI;
        const dLat  = (radiusM / R) * (180 / Math.PI);
        const dLng  = (radiusM / R) * (180 / Math.PI) / Math.cos(lat * Math.PI / 180);
        coords.push([lng + dLng * Math.sin(angle), lat + dLat * Math.cos(angle)]);
    }
    return { type: 'Feature', geometry: { type: 'Polygon', coordinates: [coords] } };
}

/**
 * Startet vmFetchNearby nach Ermittlung des Nutzerstandorts (Geolocation API).
 * Wenn Geolocation verweigert wird, fällt es auf den Karten-Mittelpunkt zurück.
 * @param {HTMLElement} el
 * @param {maplibregl.Map} map
 * @param {string} filter
 * @param {number} radius
 * @param {string} labelAttr
 */
function vmStartNearbyWithLocate(el, map, filter, radius, labelAttr) {
    const badge = document.createElement('div');
    badge.className = 'vm-nearby-badge vm-nearby-loading';
    badge.textContent = 'Standort wird ermittelt …';
    el.appendChild(badge);

    if (!navigator.geolocation) {
        badge.remove();
        const c = map.getCenter();
        vmFetchNearby(el, map, filter, radius, labelAttr, c.lat, c.lng);
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            badge.remove();
            const { latitude: lat, longitude: lng } = pos.coords;
            // Karte auf Nutzerstandort zentrieren
            map.setCenter([lng, lat]);
            map.setZoom(13);
            vmFetchNearby(el, map, filter, radius, labelAttr, lat, lng);
        },
        () => {
            badge.remove();
            // Fallback: Karten-Mittelpunkt nutzen
            const c = map.getCenter();
            vmFetchNearby(el, map, filter, radius, labelAttr, c.lat, c.lng);
        },
        { timeout: 8000, maximumAge: 60000 }
    );
}

/* ---------- GeoJSON-Support ---------- */

/**
 * Lädt und rendert externes oder inline GeoJSON auf der Karte.
 * Relative URLs werden direkt gefetcht, absolute URLs laufen über den REDAXO-Proxy.
 * @param {HTMLElement} el
 * @param {maplibregl.Map} map
 */
async function vmLoadGeoJson(el, map) {
    const geojsonAttr = el.getAttribute('geojson');
    if (!geojsonAttr) return;

    let data;
    const trimmed = geojsonAttr.trim();
    if (trimmed.startsWith('{') || trimmed.startsWith('[')) {
        // Inline-JSON
        try { data = JSON.parse(trimmed); } catch (err) {
            console.warn('<vectormap> geojson: JSON ungültig:', err); return;
        }
    } else {
        // URL: relativ → direkt, absolut → via Proxy
        const url = trimmed.startsWith('http') ? vmProxyUrl(trimmed) : trimmed;
        try {
            const res = await fetch(url);
            if (!res.ok) throw new Error('HTTP ' + res.status);
            data = await res.json();
        } catch (err) {
            console.warn('<vectormap> geojson: URL konnte nicht geladen werden:', err); return;
        }
    }

    vmRenderGeoJson(el, map, data);
}

/**
 * Rendert GeoJSON-Daten als Map-Layer (Flächen, Linien, Punkte).
 * @param {HTMLElement} el
 * @param {maplibregl.Map} map
 * @param {object} data - GeoJSON Feature oder FeatureCollection
 */
function vmRenderGeoJson(el, map, data) {
    const color     = el.getAttribute('geojson-color')   || '#2b7095';
    const opacity   = parseFloat(el.getAttribute('geojson-opacity') || '0.3');
    const popupProp = el.getAttribute('geojson-popup') || '';

    // Auf FeatureCollection normalisieren
    if (data.type === 'Feature')  data = { type: 'FeatureCollection', features: [data] };
    if (!data.features)           data = { type: 'FeatureCollection', features: [] };

    const id = 'vm-geojson-' + Math.random().toString(36).slice(2, 7);
    map.addSource(id, { type: 'geojson', data });

    // Legacy-Filter-Syntax ($type) — stabil in MapLibre v2–v4
    // $type gruppiert automatisch: 'Polygon' trifft auch MultiPolygon, etc.
    // fill- und circle-Layer brauchen keinen Filter (MapLibre ignoriert inkompatible Geometrien)

    // Flächen (Polygon / MultiPolygon)
    map.addLayer({
        id: id + '-fill', type: 'fill', source: id,
        paint: { 'fill-color': color, 'fill-opacity': opacity },
    });
    map.addLayer({
        id: id + '-fill-outline', type: 'line', source: id,
        filter: ['==', '$type', 'Polygon'],
        paint: { 'line-color': color, 'line-width': 2 },
    });

    // Linien (LineString / MultiLineString)
    map.addLayer({
        id: id + '-line', type: 'line', source: id,
        filter: ['==', '$type', 'LineString'],
        paint: { 'line-color': color, 'line-width': 3, 'line-opacity': Math.min(opacity * 3, 1) },
        layout: { 'line-join': 'round', 'line-cap': 'round' },
    });

    // Punkte (Point / MultiPoint) → echte maplibregl.Marker-Pins (Simplestyle-kompatibel)
    // Unterstützte GeoJSON-Properties: marker-color, marker-symbol
    const pointFeatures = data.features.filter(
        f => f.geometry && (f.geometry.type === 'Point' || f.geometry.type === 'MultiPoint')
    );
    pointFeatures.forEach(f => {
        const props     = f.properties || {};
        const mColor    = props['marker-color'] || color;
        const mSymbol   = props['marker-symbol'] || '';
        const coords    = f.geometry.type === 'Point'
            ? [f.geometry.coordinates]
            : f.geometry.coordinates;

        // Popup-Inhalt aufbauen
        const buildPopup = () => {
            let content = '';
            if (popupProp && props[popupProp] !== undefined) {
                content = String(props[popupProp]);
            } else {
                const rows = Object.entries(props)
                    .filter(([k, v]) => v !== null && v !== '' && !k.startsWith('marker-'))
                    .map(([k, v]) => `<tr><th style="padding-right:8px;font-weight:600;white-space:nowrap">${k}</th><td>${String(v)}</td></tr>`);
                if (rows.length) content = `<table style="font-size:13px;border-collapse:collapse">${rows.join('')}</table>`;
            }
            return content;
        };

        coords.forEach(coord => {
            let markerEl = null;
            if (mSymbol) {
                markerEl = document.createElement('div');
                markerEl.className = 'vm-custom-marker vm-geojson-pin';
                markerEl.style.cssText = [
                    `background:${mColor}`,
                    'border:2px solid #fff',
                    'border-radius:50%',
                    'width:30px',
                    'height:30px',
                    'display:flex',
                    'align-items:center',
                    'justify-content:center',
                    'font-size:15px',
                    'cursor:pointer',
                    'box-shadow:0 2px 6px rgba(0,0,0,.4)',
                ].join(';');
                markerEl.textContent = mSymbol;
            }
            const markerOpts = markerEl ? { element: markerEl } : { color: mColor };
            const m = new maplibregl.Marker(markerOpts).setLngLat([coord[0], coord[1]]);
            const popupHtml = buildPopup();
            if (popupHtml) {
                m.setPopup(new maplibregl.Popup({ offset: 25 }).setHTML(popupHtml));
            }
            m.addTo(map);
        });
    });

    // Auto-Bounds: Karte auf alle GeoJSON-Features ausrichten (wenn kein fit-bounds="false")
    if (el.getAttribute('fit-bounds') !== 'false') {
        try {
            const coords = [];
            data.features.forEach(f => {
                const g = f.geometry;
                if (!g) return;
                const flat = (arr) => {
                    if (typeof arr[0] === 'number') { coords.push(arr); return; }
                    arr.forEach(flat);
                };
                if (g.type === 'Point') coords.push(g.coordinates);
                else if (g.coordinates) flat(g.coordinates);
            });
            if (coords.length > 0) {
                const first = [coords[0][0], coords[0][1]];
                const bounds = coords.reduce(
                    (b, c) => b.extend([c[0], c[1]]),
                    new maplibregl.LngLatBounds(first, first)
                );
                // Nur wenn kein markers-Attribut sitzt (sonst vmFitMarkerBounds kümmert sich)
                if (!el.getAttribute('markers')) {
                    map.fitBounds(bounds, { padding: 50, maxZoom: 16, duration: 500 });
                }
            }
        } catch (_) { /* Bounds-Fehler ignorieren */ }
    }

    // Klick-Popups für Linien + Flächen (Punkte nutzen Marker-Popup)
    const clickLayers = [id + '-line', id + '-fill'];
    clickLayers.forEach(layer => {
        map.on('click', layer, (e) => {
            const props = e.features[0].properties || {};
            let content = '';
            if (popupProp && props[popupProp] !== undefined) {
                content = String(props[popupProp]);
            } else {
                // Auto-Popup: alle Properties als Tabelle
                const rows = Object.entries(props)
                    .filter(([, v]) => v !== null && v !== '')
                    .map(([k, v]) => `<tr><th style="padding-right:8px;font-weight:600;white-space:nowrap">${k}</th><td>${String(v)}</td></tr>`);
                if (rows.length) content = `<table style="font-size:13px;border-collapse:collapse">${rows.join('')}</table>`;
            }
            if (!content) return;
            new maplibregl.Popup()
                .setLngLat(e.lngLat)
                .setHTML(content)
                .addTo(map);
        });
        map.on('mouseenter', layer, () => { map.getCanvas().style.cursor = 'pointer'; });
        map.on('mouseleave', layer, () => { map.getCanvas().style.cursor = ''; });
    });
}

/* ---------- Demo 12: 3D Overfly Berlin ---------- */

/**
 * Cinematic 3D-Überflug durch Berliner Wahrzeichen.
 * Wartet via Polling auf el._vmMap (lazy init via IntersectionObserver),
 * dann startet die animierte Sequenz nach 800 ms.
 */
function initOverflyDemo() {
    const el = document.getElementById('vm-overfly-map');
    if (!el || el._vmOverflyInit) return;
    el._vmOverflyInit = true;

    const pollTimer = setInterval(() => {
        if (!el._vmMap) return;
        clearInterval(pollTimer);
        const map = el._vmMap;
        // Play-Button: Überflug bei Klick (auch Neustart) anstoßen
        const btn = document.getElementById('vm-overfly-play');
        if (btn) {
            btn.addEventListener('click', () => {
                map._vmOverflyRunning = false; // Reset erlaubt Neustart
                vmStartBerlinOverfly(map);
            });
        }
        if (map.isStyleLoaded()) {
            vmStartBerlinOverfly(map);
        } else {
            map.once('load', () => vmStartBerlinOverfly(map));
        }
    }, 100);
}

/**
 * Berliner Overfly-Waypoints.
 * Jeder Waypoint: { center, zoom, pitch, bearing, duration, label }
 */
const VM_BERLIN_WAYPOINTS = [
    // Start: Deutschland-Übersicht → Berlin heranfliegen
    { center: [13.405,  52.52],  zoom: 5,    pitch: 20,  bearing: 0,   duration: 2500, label: '' },
    // Brandenburger Tor
    { center: [13.3777, 52.5163], zoom: 16.5, pitch: 65,  bearing: -30, duration: 5000, label: 'Brandenburger Tor' },
    // Reichstag + Bundestag
    { center: [13.3761, 52.5186], zoom: 17,   pitch: 70,  bearing: 40,  duration: 4000, label: 'Reichstag' },
    // Humboldt Forum / Berliner Schloss
    { center: [13.4003, 52.5166], zoom: 16.5, pitch: 65,  bearing: -60, duration: 5500, label: 'Humboldt Forum' },
    // Berliner Dom
    { center: [13.4014, 52.5191], zoom: 17,   pitch: 70,  bearing: 20,  duration: 3500, label: 'Berliner Dom' },
    // Alexanderplatz / Fernsehturm
    { center: [13.4094, 52.5219], zoom: 17,   pitch: 70,  bearing: 80,  duration: 4500, label: 'Fernsehturm' },
    // Potsdamer Platz
    { center: [13.3762, 52.5096], zoom: 16.5, pitch: 65,  bearing: -20, duration: 5000, label: 'Potsdamer Platz' },
    // East Side Gallery / Oberbaumbrücke
    { center: [13.4397, 52.5050], zoom: 16,   pitch: 60,  bearing: 110, duration: 5500, label: 'East Side Gallery' },
    // Auszoomen: Überblick Berlin
    { center: [13.405,  52.52],  zoom: 12.5, pitch: 30,  bearing: 0,   duration: 4000, label: '' },
];

/**
 * @param {maplibregl.Map} map
 */
function vmStartBerlinOverfly(map) {
    const statusEl = document.getElementById('vm-overfly-status');
    const btnEl    = document.getElementById('vm-overfly-play');
    if (map._vmOverflyRunning) return;

    // 3D-Gebäude sicherstellen
    const ensure3d = () => {
        if (map.getLayer('vm-overfly-3d')) return;
        const sources = map.getStyle().sources;
        const srcName = Object.keys(sources).find(k => sources[k].type === 'vector') || 'openmaptiles';
        map.addLayer({
            id:             'vm-overfly-3d',
            source:         srcName,
            'source-layer': 'building',
            filter:         ['==', 'extrude', 'true'],
            type:           'fill-extrusion',
            minzoom:        14,
            paint: {
                'fill-extrusion-color':   '#a8b4c0',
                'fill-extrusion-height':  ['interpolate', ['linear'], ['zoom'], 14, 0, 14.5, ['get', 'height']],
                'fill-extrusion-base':    ['interpolate', ['linear'], ['zoom'], 14, 0, 14.5, ['get', 'min_height']],
                'fill-extrusion-opacity': 0.8,
            },
        });
    };

    if (map.isStyleLoaded()) ensure3d();
    map.on('styledata', () => { if (map.isStyleLoaded()) ensure3d(); });

    let step = 0;
    map._vmOverflyRunning = true;
    if (btnEl) { btnEl.disabled = true; btnEl.textContent = '▶ Läuft …'; }

    const fly = () => {
        if (step >= VM_BERLIN_WAYPOINTS.length) {
            map._vmOverflyRunning = false;
            if (btnEl) { btnEl.disabled = false; btnEl.textContent = '▶ Nochmal abspielen'; }
            if (statusEl) statusEl.textContent = '';
            return;
        }
        const wp = VM_BERLIN_WAYPOINTS[step];
        if (statusEl && wp.label) statusEl.textContent = '📍 ' + wp.label;
        else if (statusEl)        statusEl.textContent = '';
        map.flyTo({
            center:   wp.center,
            zoom:     wp.zoom,
            pitch:    wp.pitch,
            bearing:  wp.bearing,
            duration: wp.duration,
            essential: true,
        });
        step++;
        setTimeout(fly, wp.duration + 600);
    };

    setTimeout(fly, 800);
}

/* ---------- Demo 11: Ladestationen (Overpass API) ---------- */

/**
 * Initialisiert die dynamische Ladestation-Demo auf der Demo-Seite.
 * Wartet auf die lazy-gebaute MapLibre-Instanz des Elements #vm-cs-map,
 * richtet dann GeoJSON-Source + Layer ein und lädt Ladesäulen bei moveend.
 */
function initChargingStationDemo() {
    const el = document.getElementById('vm-cs-map');
    if (!el || el._vmCsInit) return;
    el._vmCsInit = true;

    // Warte auf die MapLibre-Instanz (lazy init via IntersectionObserver)
    const pollTimer = setInterval(() => {
        if (!el._vmMap) return;
        clearInterval(pollTimer);
        const map = el._vmMap;
        if (map.isStyleLoaded()) {
            vmSetupCsDemo(el, map);
        } else {
            map.once('load', () => vmSetupCsDemo(el, map));
        }
    }, 100);
}

/**
 * @param {HTMLElement} el
 * @param {maplibregl.Map} map
 */
function vmSetupCsDemo(el, map) {
    map.addSource('vm-cs', {
        type: 'geojson',
        data: { type: 'FeatureCollection', features: [] },
    });

    // Halo-Ring (weich, nur bei näherer Zoom-Stufe sichtbar)
    map.addLayer({
        id: 'vm-cs-halo',
        type: 'circle',
        source: 'vm-cs',
        paint: {
            'circle-radius':  ['interpolate', ['linear'], ['zoom'], 9, 12, 14, 24],
            'circle-color':   '#27ae60',
            'circle-opacity': 0.12,
        },
    });

    // Haupt-Punkte
    map.addLayer({
        id: 'vm-cs-dots',
        type: 'circle',
        source: 'vm-cs',
        paint: {
            'circle-radius': ['interpolate', ['linear'], ['zoom'], 9, 5, 14, 11],
            'circle-color': [
                'case',
                ['>', ['to-number', ['coalesce', ['get', 'capacity'], '0'], 0], 3],
                '#16a085',   // viele Stecker → dunkelgrün
                '#27ae60',   // Standard → grün
            ],
            'circle-stroke-color': '#fff',
            'circle-stroke-width': 2,
        },
    });

    const popup = new maplibregl.Popup({ closeButton: false, maxWidth: '260px' });
    map.on('click', 'vm-cs-dots', (e) => {
        const p = e.features[0].properties;
        const parts = [];
        if (p.name)     parts.push('<strong>' + p.name + '</strong>');
        if (p.operator) parts.push('<span style="color:#555">' + p.operator + '</span>');
        if (p.capacity) parts.push('⚡ ' + p.capacity + ' Stecker');
        if (p.network)  parts.push('Netz: ' + p.network);
        popup.setLngLat(e.features[0].geometry.coordinates)
             .setHTML(parts.join('<br>') || '<em>E-Ladestation</em>')
             .addTo(map);
    });
    map.on('mouseenter', 'vm-cs-dots', () => { map.getCanvas().style.cursor = 'pointer'; });
    map.on('mouseleave', 'vm-cs-dots', () => { map.getCanvas().style.cursor = ''; });

    const badge   = document.getElementById('vm-cs-count');
    const spinner = document.getElementById('vm-cs-loading');
    let debTimer;

    function loadStations() {
        clearTimeout(debTimer);
        debTimer = setTimeout(async () => {
            // Erst ab Zoom 9 laden (sonst Millionen Ergebnisse)
            if (map.getZoom() < 9) {
                if (badge) badge.textContent = 'Bitte näher heranzoomen (ab Zoom 9)';
                map.getSource('vm-cs').setData({ type: 'FeatureCollection', features: [] });
                return;
            }
            if (spinner) spinner.style.display = '';
            const b = map.getBounds();
            const q = `[out:json][timeout:10];node["amenity"="charging_station"](${b.getSouth()},${b.getWest()},${b.getNorth()},${b.getEast()});out 200;`;
            const url = vmProxyUrl('https://overpass-api.de/api/interpreter?data=' + encodeURIComponent(q));
            try {
                const data = await fetch(url).then(r => r.json());
                const features = (data.elements || []).map(n => ({
                    type: 'Feature',
                    geometry: { type: 'Point', coordinates: [n.lon, n.lat] },
                    properties: {
                        name:     (n.tags && n.tags.name)     || null,
                        operator: (n.tags && n.tags.operator) || null,
                        capacity: (n.tags && n.tags.capacity) || null,
                        network:  (n.tags && n.tags.network)  || null,
                    },
                }));
                map.getSource('vm-cs').setData({ type: 'FeatureCollection', features });
                if (badge) {
                    if (features.length === 0) {
                        badge.textContent = 'Keine Ladesäulen in diesem Bereich';
                    } else {
                        const more = features.length >= 200 ? '+ ' : ' ';
                        badge.textContent = features.length + more + 'Ladesäule' + (features.length !== 1 ? 'n' : '') + ' gefunden';
                    }
                }
            } catch (_) {
                if (badge) badge.textContent = 'API nicht erreichbar';
            } finally {
                if (spinner) spinner.style.display = 'none';
            }
        }, 600);
    }

    map.on('moveend', loadStations);
    loadStations(); // Sofort laden beim Start
}

/* ---------- Custom Element ---------- */
class VectorMapElement extends HTMLElement {
    connectedCallback()    { initVectorMap(this); }
    disconnectedCallback() { if (this._vmMap) this._vmMap.remove(); }
}

if (!customElements.get('vector-map')) {
    customElements.define('vector-map', VectorMapElement);
}

document.addEventListener("DOMContentLoaded", () => {
    new VectorMapPicker();
});
