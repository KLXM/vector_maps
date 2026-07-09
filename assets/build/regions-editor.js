(function vmRegionsBuilder() {
    function $(selector, root) {
        return (root || document).querySelector(selector);
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function safeJsonParse(raw, fallback) {
        try {
            var parsed = JSON.parse(String(raw || ''));
            if (parsed && typeof parsed === 'object') {
                return parsed;
            }
        } catch (_err) {
            // ignore
        }

        return fallback;
    }

    function uid(prefix) {
        return prefix + '-' + Math.random().toString(36).slice(2, 10);
    }

    function hasOwn(obj, key) {
        return !!obj && Object.prototype.hasOwnProperty.call(obj, key);
    }

    function normalizeOsmType(osmType) {
        var val = String(osmType || '').toLowerCase();
        if (val === 'relation' || val === 'r') return 'R';
        if (val === 'way' || val === 'w') return 'W';
        if (val === 'node' || val === 'n') return 'N';
        return '';
    }

    function toRad(value) {
        return value * Math.PI / 180;
    }

    // Sphärische Polygonfläche in m² (adaptiert nach geojson-area Ansatz).
    function ringArea(ring) {
        var area = 0;
        if (!Array.isArray(ring) || ring.length < 3) {
            return 0;
        }

        for (var i = 0; i < ring.length; i += 1) {
            var lowerIndex = i;
            var middleIndex = (i + 1) % ring.length;
            var upperIndex = (i + 2) % ring.length;

            var p1 = ring[lowerIndex];
            var p2 = ring[middleIndex];
            var p3 = ring[upperIndex];

            if (!Array.isArray(p1) || !Array.isArray(p2) || !Array.isArray(p3)) {
                continue;
            }

            area += (toRad(p3[0]) - toRad(p1[0])) * Math.sin(toRad(p2[1]));
        }

        return area * 6378137 * 6378137 / 2;
    }

    function polygonArea(coords) {
        if (!Array.isArray(coords) || !coords.length) {
            return 0;
        }

        var area = Math.abs(ringArea(coords[0]));
        for (var i = 1; i < coords.length; i += 1) {
            area -= Math.abs(ringArea(coords[i]));
        }

        return Math.max(0, area);
    }

    function geometryAreaSqm(geometry) {
        if (!geometry || typeof geometry !== 'object') {
            return 0;
        }

        if (geometry.type === 'Polygon') {
            return polygonArea(geometry.coordinates);
        }

        if (geometry.type === 'MultiPolygon') {
            return (geometry.coordinates || []).reduce(function (sum, polygonCoords) {
                return sum + polygonArea(polygonCoords);
            }, 0);
        }

        return 0;
    }

    function ensureHexColor(value, fallback) {
        var color = String(value || '').trim();
        if (/^#(?:[0-9a-fA-F]{3}){1,2}$/.test(color)) {
            return color;
        }

        return fallback || '#2f855a';
    }

    // Erlaubt Hex (inkl. Alpha), rgb(a) und hsl(a) – für freie Eingabe inkl. Transparenz.
    function ensureCssColor(value, fallback) {
        var color = String(value || '').trim();
        if (/^#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/.test(color)) {
            return color;
        }
        if (/^(rgb|rgba|hsl|hsla)\(\s*[\d.,%\s\/-]+\)$/i.test(color)) {
            return color;
        }

        return fallback;
    }

    function clampOpacity(value, fallback) {
        var num = Number(value);
        if (!Number.isFinite(num)) {
            return fallback;
        }

        return Math.min(1, Math.max(0, num));
    }

    function defaultColors() {
        return {
            active: '#2f855a',
            inactive: '#9ca3af',
            active_opacity: 0.42,
            inactive_opacity: 0.15,
        };
    }

    function defaultOptimize() {
        return {
            enabled: false,
            precision: 6,
        };
    }

    function normalizeOptimize(raw) {
        var defaults = defaultOptimize();
        if (!raw || typeof raw !== 'object') {
            return defaults;
        }

        var precision = Number(raw.precision);
        if (!Number.isFinite(precision)) {
            precision = defaults.precision;
        }
        precision = Math.round(precision);
        precision = Math.max(4, Math.min(7, precision));

        return {
            enabled: !!raw.enabled,
            precision: precision,
        };
    }

    function normalizeColors(raw) {
        var defaults = defaultColors();
        if (!raw || typeof raw !== 'object') {
            return defaults;
        }

        return {
            active: ensureCssColor(raw.active, defaults.active),
            inactive: ensureCssColor(raw.inactive, defaults.inactive),
            active_opacity: clampOpacity(raw.active_opacity, defaults.active_opacity),
            inactive_opacity: clampOpacity(raw.inactive_opacity, defaults.inactive_opacity),
        };
    }

    function buildProxyUrl(proxyBase, targetUrl) {
        var url = new URL(proxyBase, window.location.origin);
        url.searchParams.set('target_url', targetUrl);
        return url.toString();
    }

    function buildNominatimUrl(path, params) {
        var url = new URL('https://nominatim.openstreetmap.org' + path);
        Object.keys(params || {}).forEach(function (key) {
            var value = params[key];
            if (value !== undefined && value !== null && String(value) !== '') {
                url.searchParams.set(key, String(value));
            }
        });
        return url.toString();
    }

    function buildCountryLandFallbackUrl(countryCode3) {
        var code = String(countryCode3 || '').trim().toUpperCase();
        if (!/^[A-Z]{3}$/.test(code)) {
            return '';
        }

        return 'https://raw.githubusercontent.com/johan/world.geo.json/master/countries/' + code + '.geo.json';
    }

    function buildGeoBoundariesMetaUrl(countryCode3) {
        var code = String(countryCode3 || '').trim().toUpperCase();
        if (!/^[A-Z]{3}$/.test(code)) {
            return '';
        }

        return 'https://www.geoboundaries.org/api/current/gbOpen/' + code + '/ADM0/';
    }

    function extractPolygonGeometryFromGeoJson(raw) {
        if (!raw || typeof raw !== 'object') {
            return null;
        }

        if ((raw.type === 'Polygon' || raw.type === 'MultiPolygon') && Array.isArray(raw.coordinates)) {
            return raw;
        }

        if (raw.type === 'Feature' && raw.geometry && (raw.geometry.type === 'Polygon' || raw.geometry.type === 'MultiPolygon')) {
            return raw.geometry;
        }

        if (raw.type === 'FeatureCollection' && Array.isArray(raw.features)) {
            for (var i = 0; i < raw.features.length; i += 1) {
                var feature = raw.features[i];
                if (feature && feature.geometry && (feature.geometry.type === 'Polygon' || feature.geometry.type === 'MultiPolygon')) {
                    return feature.geometry;
                }
            }
        }

        return null;
    }

    function fetchNominatimLookupGeometry(proxyBase, searchItem) {
        var lookupUrl = buildNominatimUrl('/lookup', {
            format: 'jsonv2',
            polygon_geojson: 1,
            polygon_threshold: resolvePolygonThreshold(searchItem),
            osm_ids: String(searchItem.osm_type) + String(searchItem.osm_id),
        });

        return fetch(buildProxyUrl(proxyBase, lookupUrl), { credentials: 'same-origin' })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                var entry = Array.isArray(data) && data[0] ? data[0] : null;
                if (!entry || !entry.geojson) {
                    throw new Error('Keine GeoJSON-Antwort für die Stadtgrenze');
                }

                return {
                    geometry: entry.geojson,
                    entry: entry,
                    source: 'nominatim',
                };
            });
    }

    function fetchCountryLandGeometry(proxyBase, searchItem) {
        var countryCode3 = searchItem && searchItem.country_code3;
        var metaUrl = buildGeoBoundariesMetaUrl(countryCode3);
        var fallbackUrl = buildCountryLandFallbackUrl(countryCode3);
        if (!metaUrl) {
            throw new Error('Kein gültiger ISO3-Ländercode für Landflächen-Import');
        }

        function fetchGeoJsonGeometry(sourceUrl) {
            if (!sourceUrl) {
                return Promise.reject(new Error('Keine Landflächen-URL verfügbar'));
            }

            return fetch(buildProxyUrl(proxyBase, sourceUrl), { credentials: 'same-origin' })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Landflächen-Daten konnten nicht geladen werden');
                    }
                    return response.json();
                })
                .then(function (raw) {
                    var geometry = extractPolygonGeometryFromGeoJson(raw);
                    if (!geometry) {
                        throw new Error('Landflächen-Daten enthalten keine Polygon-Geometrie');
                    }

                    return {
                        geometry: geometry,
                        entry: null,
                        source: 'land',
                    };
                });
        }

        return fetch(buildProxyUrl(proxyBase, metaUrl), { credentials: 'same-origin' })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('geoBoundaries-Metadaten konnten nicht geladen werden');
                }
                return response.json();
            })
            .then(function (meta) {
                var sourceUrl = String(meta && (meta.simplifiedGeometryGeoJSON || meta.gjDownloadURL) || '');
                return fetchGeoJsonGeometry(sourceUrl);
            })
            .catch(function () {
                // Robuster Fallback, falls geoBoundaries temporär nicht erreichbar ist.
                return fetchGeoJsonGeometry(fallbackUrl);
            });
    }

    function isCountryLikeResult(item) {
        var addresstype = String(item && item.addresstype || '').toLowerCase();
        var type = String(item && item.type || '').toLowerCase();
        var placeRank = Number(item && item.place_rank || 0);
        return addresstype === 'country' || type === 'country' || (placeRank > 0 && placeRank <= 6);
    }

    function countGeometryPoints(geometry) {
        var count = 0;

        function scan(coords) {
            if (!Array.isArray(coords)) {
                return;
            }

            if (typeof coords[0] === 'number' && typeof coords[1] === 'number') {
                count += 1;
                return;
            }

            coords.forEach(scan);
        }

        if (!geometry || typeof geometry !== 'object') {
            return 0;
        }

        scan(geometry.coordinates);
        return count;
    }

    function resolvePolygonThreshold(searchItem) {
        var addresstype = String(searchItem && searchItem.addresstype || '').toLowerCase();
        var placeRank = Number(searchItem && searchItem.place_rank || 0);

        // Gröbere Vereinfachung für große Flächen, feinere für Städte.
        if (addresstype === 'country' || placeRank <= 6) {
            return '0.05';
        }
        if (addresstype === 'state' || addresstype === 'province' || addresstype === 'region' || placeRank <= 10) {
            return '0.012';
        }
        if (addresstype === 'county' || addresstype === 'district' || placeRank <= 14) {
            return '0.004';
        }

        return '0.0015';
    }

    function simplifyRingToBudget(ring, step) {
        if (!Array.isArray(ring) || ring.length < 8 || step <= 1) {
            return ring;
        }

        var out = [];
        var lastIndex = ring.length - 1;
        var i;

        out.push(ring[0]);
        for (i = 1; i < lastIndex; i += step) {
            out.push(ring[i]);
        }
        out.push(ring[lastIndex]);

        if (out.length < 4) {
            return ring;
        }

        return out;
    }

    function simplifyGeometryToPointBudget(geometry, maxPoints) {
        var pointCount = countGeometryPoints(geometry);
        if (pointCount <= maxPoints) {
            return geometry;
        }

        var step = Math.ceil(pointCount / maxPoints);
        var type = geometry && geometry.type;
        if (type !== 'Polygon' && type !== 'MultiPolygon') {
            return geometry;
        }

        var clone = JSON.parse(JSON.stringify(geometry));
        if (type === 'Polygon') {
            clone.coordinates = (clone.coordinates || []).map(function (ring) {
                return simplifyRingToBudget(ring, step);
            });
        } else {
            clone.coordinates = (clone.coordinates || []).map(function (polygon) {
                return (polygon || []).map(function (ring) {
                    return simplifyRingToBudget(ring, step);
                });
            });
        }

        return clone;
    }

    function flattenGeometryPolygons(geometry) {
        if (!geometry || typeof geometry !== 'object') {
            return [];
        }

        if (geometry.type === 'Polygon' && Array.isArray(geometry.coordinates)) {
            return [geometry.coordinates];
        }

        if (geometry.type === 'MultiPolygon' && Array.isArray(geometry.coordinates)) {
            return geometry.coordinates.filter(function (polygon) {
                return Array.isArray(polygon);
            });
        }

        return [];
    }

    function geometryCenter(geometry) {
        var minLng = Infinity;
        var minLat = Infinity;
        var maxLng = -Infinity;
        var maxLat = -Infinity;

        function scan(coords) {
            if (!Array.isArray(coords)) {
                return;
            }

            if (typeof coords[0] === 'number' && typeof coords[1] === 'number') {
                var lng = Number(coords[0]);
                var lat = Number(coords[1]);
                if (!Number.isFinite(lng) || !Number.isFinite(lat)) {
                    return;
                }

                if (lng < minLng) minLng = lng;
                if (lat < minLat) minLat = lat;
                if (lng > maxLng) maxLng = lng;
                if (lat > maxLat) maxLat = lat;
                return;
            }

            coords.forEach(scan);
        }

        if (!geometry || typeof geometry !== 'object') {
            return null;
        }

        scan(geometry.coordinates);

        if (!Number.isFinite(minLng) || !Number.isFinite(minLat) || !Number.isFinite(maxLng) || !Number.isFinite(maxLat)) {
            return null;
        }

        return [(minLng + maxLng) / 2, (minLat + maxLat) / 2];
    }

    function regionLabelCenterFromPolygons(regionPolygons) {
        if (!Array.isArray(regionPolygons) || !regionPolygons.length) {
            return null;
        }

        return geometryCenter({ type: 'MultiPolygon', coordinates: regionPolygons });
    }

    function createEmptyRegion() {
        return {
            id: uid('region'),
            key: '',
            name: '',
            label: '',
            color: '',
            url: '',
            info: '',
            countrycodes: 'de',
            search: '',
            searchResults: [],
            cities: [],
            showAllCities: false,
            busy: false,
        };
    }

    function normalizeState(raw) {
        var state = {
            description: String(raw && raw.description ? raw.description : ''),
            colors: normalizeColors(raw && raw.colors),
            optimize: normalizeOptimize(raw && raw.optimize),
            regions: [],
        };

        var regionsRaw = raw && Array.isArray(raw.regions) ? raw.regions : [];
        regionsRaw.forEach(function (regionRaw) {
            var region = createEmptyRegion();
            region.id = String(regionRaw.id || uid('region'));
            region.key = String(regionRaw.key || '');
            region.name = String(regionRaw.name || '');
            region.label = hasOwn(regionRaw, 'label') ? String(regionRaw.label || '') : String(regionRaw.name || '');
            region.color = ensureCssColor(regionRaw.color, '');
            region.url = String(regionRaw.url || '');
            region.info = String(regionRaw.info || '');
            region.countrycodes = String(regionRaw.countrycodes || '');
            region.showAllCities = !!regionRaw.showAllCities;
            region.cities = Array.isArray(regionRaw.cities) ? regionRaw.cities.map(function (cityRaw) {
                var geometry = cityRaw && cityRaw.geometry && typeof cityRaw.geometry === 'object'
                    ? cityRaw.geometry
                    : null;
                return {
                    id: String(cityRaw.id || uid('city')),
                    name: String(cityRaw.name || ''),
                    label: hasOwn(cityRaw, 'label') ? String(cityRaw.label || '') : String(cityRaw.name || ''),
                    display_name: String(cityRaw.display_name || cityRaw.name || ''),
                    osm_type: normalizeOsmType(cityRaw.osm_type || ''),
                    osm_id: Number(cityRaw.osm_id || 0),
                    geometry: geometry,
                    url: String(cityRaw.url || ''),
                    info: String(cityRaw.info || ''),
                    area_km2: Number(cityRaw.area_km2 || 0),
                    active: cityRaw.active !== false,
                };
            }).filter(function (city) {
                return city.geometry && city.name;
            }) : [];

            state.regions.push(region);
        });

        return state;
    }

    function stateToPayload(state) {
        return {
            description: String(state.description || ''),
            colors: normalizeColors(state.colors),
            optimize: normalizeOptimize(state.optimize),
            regions: state.regions.map(function (region) {
                return {
                    key: String(region.key || ''),
                    name: String(region.name || ''),
                    label: String(region.label || ''),
                    color: ensureCssColor(region.color, ''),
                    url: String(region.url || ''),
                    info: String(region.info || ''),
                    countrycodes: String(region.countrycodes || ''),
                    cities: region.cities.map(function (city) {
                        return {
                            name: city.name,
                            label: String(city.label || ''),
                            display_name: city.display_name,
                            osm_type: city.osm_type,
                            osm_id: city.osm_id,
                            geometry: city.geometry,
                            url: city.url,
                            info: city.info,
                            area_km2: city.area_km2,
                            active: city.active !== false,
                        };
                    }),
                };
            }),
        };
    }

    function gatherStats(state) {
        var regionCount = state.regions.length;
        var cityCount = 0;
        var areaTotal = 0;

        state.regions.forEach(function (region) {
            region.cities.forEach(function (city) {
                cityCount += 1;
                areaTotal += Number(city.area_km2 || 0);
            });
        });

        return {
            regionCount: regionCount,
            cityCount: cityCount,
            areaTotal: Number(areaTotal.toFixed(3)),
        };
    }

    function createBuilder(root) {
        var MAX_PRETTY_GEOJSON_FEATURES = 120;
        var MAX_BOUNDS_POINTS = 4000;
        var MAX_PREVIEW_CITY_FEATURES = 280;
        var MAX_VISIBLE_CITY_ROWS = 180;

        var regionList = $('#vm-region-list', root);
        var addRegionButton = $('#vm-add-region', root);
        var payloadInput = $('#vm-group-payload');
        var groupDescription = $('#vm-group-description');
        var groupKeyInput = $('#vm-group-key');
        var groupNameInput = $('#vm-group-name');
        var generatedGeoJson = $('#vm-generated-geojson');
        var statRegions = $('#vm-stat-regions');
        var statCities = $('#vm-stat-cities');
        var statArea = $('#vm-stat-area');
        var form = $('#vm-region-group-form');
        var colorControls = {
            active: { text: $('#vm-color-active'), picker: $('#vm-color-active-picker') },
            inactive: { text: $('#vm-color-inactive'), picker: $('#vm-color-inactive-picker') },
            activeOpacity: $('#vm-opacity-active'),
            inactiveOpacity: $('#vm-opacity-inactive'),
        };
        var optimizeControls = {
            enabled: $('#vm-optimize-enabled'),
            precision: $('#vm-optimize-precision'),
        };

        var proxyBase = root.getAttribute('data-proxy-url') || '';
        var initialGroupKey = String(root.getAttribute('data-initial-group-key') || '').trim();
        var initialPayloadRaw = root.getAttribute('data-initial-payload') || '{}';
        var initialState = normalizeState(safeJsonParse(initialPayloadRaw, { description: '', regions: [] }));

        var state = initialState;
        var map = null;
        var popup = null;
        var sourceId = 'vm-group-preview-source';
        var syncQueued = false;
        var syncOptions = { fit: false, persistPayload: false };
        var fitBoundsToken = 0;
        var mapEnabled = false;
        var mapReady = false;
        var labelMarkers = [];
        var mapContainer = $('#vm-builder-map');
        var latestPreviewGeojson = { type: 'FeatureCollection', features: [] };
        var dataLoadedFromApi = false;

        if (groupDescription) {
            groupDescription.value = state.description || '';
        }

        function syncColorControlsFromState() {
            var colors = state.colors;

            if (colorControls.active.text) {
                colorControls.active.text.value = colors.active;
            }
            if (colorControls.active.picker && /^#[0-9a-fA-F]{6}$/.test(colors.active)) {
                colorControls.active.picker.value = colors.active;
            }
            if (colorControls.inactive.text) {
                colorControls.inactive.text.value = colors.inactive;
            }
            if (colorControls.inactive.picker && /^#[0-9a-fA-F]{6}$/.test(colors.inactive)) {
                colorControls.inactive.picker.value = colors.inactive;
            }
            if (colorControls.activeOpacity) {
                colorControls.activeOpacity.value = String(Math.round(colors.active_opacity * 100));
            }
            if (colorControls.inactiveOpacity) {
                colorControls.inactiveOpacity.value = String(Math.round(colors.inactive_opacity * 100));
            }
        }

        function bindColorControls() {
            ['active', 'inactive'].forEach(function (kind) {
                var pair = colorControls[kind];

                if (pair.text) {
                    pair.text.addEventListener('input', function () {
                        var valid = ensureCssColor(pair.text.value, '');
                        if (valid) {
                            state.colors[kind] = valid;
                            if (pair.picker && /^#[0-9a-fA-F]{6}$/.test(valid)) {
                                pair.picker.value = valid;
                            }
                            queueSync();
                        }
                    });
                }

                if (pair.picker) {
                    pair.picker.addEventListener('input', function () {
                        state.colors[kind] = pair.picker.value;
                        if (pair.text) {
                            pair.text.value = pair.picker.value;
                        }
                        queueSync();
                    });
                }
            });

            if (colorControls.activeOpacity) {
                colorControls.activeOpacity.addEventListener('input', function () {
                    state.colors.active_opacity = clampOpacity(Number(colorControls.activeOpacity.value) / 100, state.colors.active_opacity);
                    queueSync();
                });
            }

            if (colorControls.inactiveOpacity) {
                colorControls.inactiveOpacity.addEventListener('input', function () {
                    state.colors.inactive_opacity = clampOpacity(Number(colorControls.inactiveOpacity.value) / 100, state.colors.inactive_opacity);
                    queueSync();
                });
            }
        }

        function syncOptimizeControlsFromState() {
            var optimize = normalizeOptimize(state.optimize);
            state.optimize = optimize;

            if (optimizeControls.enabled) {
                optimizeControls.enabled.checked = optimize.enabled;
            }
            if (optimizeControls.precision) {
                optimizeControls.precision.value = String(optimize.precision);
                optimizeControls.precision.disabled = !optimize.enabled;
            }
        }

        function bindOptimizeControls() {
            if (optimizeControls.enabled) {
                optimizeControls.enabled.addEventListener('change', function () {
                    state.optimize.enabled = !!optimizeControls.enabled.checked;
                    if (optimizeControls.precision) {
                        optimizeControls.precision.disabled = !state.optimize.enabled;
                    }
                    queueSync({ persistPayload: true });
                });
            }

            if (optimizeControls.precision) {
                optimizeControls.precision.addEventListener('input', function () {
                    var precision = Number(optimizeControls.precision.value);
                    if (!Number.isFinite(precision)) {
                        return;
                    }
                    precision = Math.max(4, Math.min(7, Math.round(precision)));
                    state.optimize.precision = precision;
                    optimizeControls.precision.value = String(precision);
                    queueSync({ persistPayload: true });
                });
            }
        }

        function renderMapPlaceholder(statusText) {
            if (!mapContainer || mapEnabled) {
                return;
            }

            mapContainer.innerHTML = ''
                + '<div style="height:100%;display:flex;align-items:center;justify-content:center;text-align:center;padding:14px;background:#f8f9fb;border:1px dashed #d5dce3;border-radius:6px">'
                + '  <div>'
                + '    <p style="margin:0 0 10px 0;color:#5f6b7a">' + escapeHtml(statusText || 'Live-Preview ist pausiert, um den Editor flüssig zu halten.') + '</p>'
                + '    <button type="button" class="btn btn-primary btn-sm" data-action="enable-live-preview">Live-Preview aktivieren</button>'
                + '  </div>'
                + '</div>';
        }

        function mapStyleUrl() {
            return buildProxyUrl(proxyBase, 'https://tiles.openfreemap.org/styles/liberty');
        }

        function initMap() {
            if (!mapEnabled) {
                return;
            }

            if (!window.maplibregl) {
                return;
            }

            if (map) {
                return;
            }

            if (mapContainer) {
                mapContainer.innerHTML = '';
            }

            map = new maplibregl.Map({
                container: 'vm-builder-map',
                style: mapStyleUrl(),
                center: [10.4, 51.2],
                zoom: 5.3,
                transformRequest: function (url) {
                    if (url.indexOf('tiles.openfreemap.org/') !== -1 || url.indexOf('nominatim.openstreetmap.org/') !== -1) {
                        return { url: buildProxyUrl(proxyBase, url) };
                    }
                    return { url: url };
                },
            });

            map.addControl(new maplibregl.NavigationControl({ visualizePitch: true }), 'top-right');

            map.on('load', function () {
                map.addSource(sourceId, {
                    type: 'geojson',
                    data: { type: 'FeatureCollection', features: [] },
                });

                map.addLayer({
                    id: 'vm-region-fill',
                    type: 'fill',
                    source: sourceId,
                    filter: ['==', ['get', 'level'], 'region'],
                    paint: {
                        'fill-color': ['coalesce', ['get', 'fill'], '#2f855a'],
                        'fill-opacity': ['coalesce', ['get', 'fill_opacity'], 0.18],
                    },
                });

                map.addLayer({
                    id: 'vm-region-line',
                    type: 'line',
                    source: sourceId,
                    filter: ['==', ['get', 'level'], 'region'],
                    paint: {
                        'line-color': ['coalesce', ['get', 'fill'], '#1f5f43'],
                        'line-width': 2,
                    },
                });

                map.addLayer({
                    id: 'vm-city-fill',
                    type: 'fill',
                    source: sourceId,
                    filter: ['==', ['get', 'level'], 'city'],
                    paint: {
                        'fill-color': ['coalesce', ['get', 'fill'], '#2f855a'],
                        'fill-opacity': ['coalesce', ['get', 'fill_opacity'], 0.42],
                    },
                });

                map.addLayer({
                    id: 'vm-city-line',
                    type: 'line',
                    source: sourceId,
                    filter: ['==', ['get', 'level'], 'city'],
                    paint: {
                        'line-color': '#ffffff',
                        'line-width': 1,
                    },
                });

                map.addLayer({
                    id: 'vm-label-region-bg',
                    type: 'circle',
                    source: sourceId,
                    filter: ['all', ['==', '$type', 'Point'], ['==', ['get', 'vm_label'], true], ['==', ['get', 'label_kind'], 'region']],
                    paint: {
                        'circle-radius': 16,
                        'circle-color': ['coalesce', ['get', 'label_bg'], '#111827'],
                        'circle-opacity': 0.92,
                    },
                });

                map.addLayer({
                    id: 'vm-labels',
                    type: 'symbol',
                    source: sourceId,
                    filter: ['all', ['==', '$type', 'Point'], ['==', ['get', 'vm_label'], true]],
                    layout: {
                        'text-field': ['coalesce', ['get', 'label'], ['get', 'name'], ''],
                        'text-size': ['case', ['==', ['get', 'label_kind'], 'region'], 13, 11],
                        'text-font': ['Open Sans Semibold', 'Arial Unicode MS Bold'],
                        'text-allow-overlap': true,
                        'text-ignore-placement': true,
                    },
                    paint: {
                        'text-color': ['coalesce', ['get', 'label_color'], '#111827'],
                        'text-halo-color': '#ffffff',
                        'text-halo-width': ['case', ['==', ['get', 'label_kind'], 'region'], 0.2, 1.2],
                        'text-halo-blur': 0.4,
                    },
                });

                var clickHandler = function (event) {
                    var features = map.queryRenderedFeatures(event.point, {
                        layers: ['vm-city-fill', 'vm-region-fill'],
                    });
                    if (!features.length) {
                        return;
                    }

                    var feature = features[0];
                    var props = feature.properties || {};
                    var label = props.name || 'Fläche';
                    var regionLabel = props.region_name || '';
                    var info = props.info || '';
                    var url = props.url || '';
                    var area = Number(props.area_km2 || 0);

                    var html = '<strong>' + escapeHtml(label) + '</strong>';
                    if (regionLabel && regionLabel !== label) {
                        html += '<br><small class="text-muted">Region: ' + escapeHtml(regionLabel) + '</small>';
                    }
                    if (area > 0) {
                        html += '<br><small class="text-muted">Fläche: ' + area.toFixed(2) + ' km²</small>';
                    }
                    if (info) {
                        html += '<div style="margin-top:6px">' + escapeHtml(info) + '</div>';
                    }
                    if (url) {
                        html += '<div style="margin-top:6px"><a href="' + escapeHtml(url) + '" target="_blank" rel="noopener">Link öffnen</a></div>';
                    }

                    if (popup) {
                        popup.remove();
                    }

                    popup = new maplibregl.Popup({ closeButton: true, closeOnClick: true })
                        .setLngLat(event.lngLat)
                        .setHTML(html)
                        .addTo(map);
                };

                map.on('click', 'vm-city-fill', clickHandler);
                map.on('click', 'vm-region-fill', clickHandler);

                mapReady = true;
                updatePreview(latestPreviewGeojson, true);
            });
        }

        function enableLivePreview() {
            if (mapEnabled) {
                return;
            }

            mapEnabled = true;
            initMap();
        }

        function fitGeoJsonBounds(geojson) {
            if (!map || !mapReady) {
                return;
            }

            if (!geojson.features.length) {
                return;
            }

            var bounds = new maplibregl.LngLatBounds();
            var pointCount = 0;

            function extendCoords(coords) {
                if (!Array.isArray(coords)) {
                    return;
                }

                if (pointCount > MAX_BOUNDS_POINTS) {
                    return;
                }

                if (typeof coords[0] === 'number' && typeof coords[1] === 'number') {
                    bounds.extend([coords[0], coords[1]]);
                    pointCount += 1;
                    return;
                }

                coords.forEach(extendCoords);
            }

            geojson.features.forEach(function (feature) {
                if (feature && feature.geometry) {
                    extendCoords(feature.geometry.coordinates);
                }
            });

            if (!bounds.isEmpty()) {
                map.fitBounds(bounds, { padding: 42, duration: 350, maxZoom: 10.5 });
            }
        }

        function updatePreview(geojson, shouldFitBounds) {
            latestPreviewGeojson = geojson;

            if (!mapEnabled) {
                return;
            }

            if (!map || !mapReady) {
                return;
            }

            var source = map.getSource(sourceId);
            if (source) {
                source.setData(geojson);
                renderLabelMarkers(geojson);
                if (shouldFitBounds) {
                    var token = ++fitBoundsToken;
                    window.setTimeout(function () {
                        if (token !== fitBoundsToken) {
                            return;
                        }
                        fitGeoJsonBounds(geojson);
                    }, 0);
                }
            }
        }

        function clearLabelMarkers() {
            labelMarkers.forEach(function (marker) {
                try {
                    marker.remove();
                } catch (_err) {
                    // ignore
                }
            });
            labelMarkers = [];
        }

        function renderLabelMarkers(geojson) {
            clearLabelMarkers();

            if (!map || !mapReady || !geojson || !Array.isArray(geojson.features)) {
                return;
            }

            var labelFeatures = geojson.features.filter(function (feature) {
                if (!feature || !feature.geometry || feature.geometry.type !== 'Point') {
                    return false;
                }

                var props = feature.properties || {};
                if (props.vm_label !== true && props.vm_label !== 1 && props.vm_label !== '1') {
                    return false;
                }

                var text = String(props.label || '').trim();
                return text !== '';
            });

            // Sicherheitsbremse gegen UI-Freeze bei sehr vielen Labels
            if (labelFeatures.length > 120) {
                labelFeatures = labelFeatures.slice(0, 120);
            }

            labelFeatures.forEach(function (feature) {
                var props = feature.properties || {};
                var coords = feature.geometry.coordinates;
                if (!Array.isArray(coords) || coords.length < 2) {
                    return;
                }

                var isRegion = String(props.label_kind || '') === 'region';
                var el = document.createElement('div');
                el.className = 'vm-dom-label';
                el.textContent = String(props.label || '').trim();
                el.style.pointerEvents = 'none';
                el.style.whiteSpace = 'nowrap';
                el.style.transform = 'translate(-50%, -50%)';
                el.style.fontFamily = 'system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
                el.style.fontSize = isRegion ? '13px' : '11px';
                el.style.fontWeight = isRegion ? '700' : '600';
                el.style.padding = isRegion ? '4px 8px' : '2px 6px';
                el.style.borderRadius = isRegion ? '999px' : '8px';
                el.style.boxShadow = isRegion ? '0 1px 4px rgba(0,0,0,.35)' : '0 1px 2px rgba(0,0,0,.2)';

                if (isRegion) {
                    el.style.background = ensureCssColor(props.label_bg, '#111827');
                    el.style.color = ensureCssColor(props.label_color, '#ffffff');
                    el.style.border = '1px solid rgba(255,255,255,.28)';
                } else {
                    el.style.background = 'rgba(255,255,255,.9)';
                    el.style.color = ensureCssColor(props.label_color, '#111827');
                    el.style.border = '1px solid rgba(17,24,39,.2)';
                }

                var marker = new maplibregl.Marker({ element: el, anchor: 'center' })
                    .setLngLat([Number(coords[0]), Number(coords[1])])
                    .addTo(map);

                labelMarkers.push(marker);
            });
        }

        function renderLoadingState(message) {
            if (!regionList) {
                return;
            }

            regionList.innerHTML = '<div class="alert alert-info" style="margin-bottom:12px">'
                + '<i class="rex-icon fa-spinner fa-spin"></i> '
                + escapeHtml(message || 'Lade Gruppendaten...')
                + '</div>';
        }

        function loadGroupPayloadByApi(groupKey) {
            if (!groupKey || !root || !regionList) {
                return Promise.resolve(false);
            }

            var apiBase = root.getAttribute('data-regions-api-url') || '';
            if (!apiBase) {
                return Promise.resolve(false);
            }

            var url = new URL(apiBase, window.location.origin);
            url.searchParams.set('action', 'get');
            url.searchParams.set('key', groupKey);

            renderLoadingState('Gruppendaten werden nachgeladen...');

            return fetch(url.toString(), { credentials: 'same-origin' })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    var payload = data && typeof data === 'object' && data.payload && typeof data.payload === 'object'
                        ? data.payload
                        : null;

                    if (!payload) {
                        throw new Error('Ungültige API-Antwort');
                    }

                    state = normalizeState(payload);
                    dataLoadedFromApi = true;

                    if (groupDescription) {
                        groupDescription.value = state.description || '';
                    }

                    syncColorControlsFromState();
                    syncOptimizeControlsFromState();
                    renderRegions();
                    sanitizeRegionKeys();
                    queueSync({ fit: true, persistPayload: true });

                    if (gatherStats(state).cityCount > 0) {
                        window.setTimeout(function () {
                            enableLivePreview();
                            queueSync({ fit: true });
                        }, 20);
                    }

                    return true;
                })
                .catch(function (error) {
                    window.console.error(error);
                    regionList.innerHTML = '<div class="alert alert-danger" style="margin-bottom:12px">'
                        + 'Gruppendaten konnten nicht geladen werden. Bitte Seite neu laden.'
                        + '</div>';
                    return false;
                });
        }

        function buildPreviewGeoJson(stateData, groupKey, groupName, maxCityFeatures) {
            var features = [];
            var cityFeatureCount = 0;
            var truncated = false;
            var colors = normalizeColors(stateData.colors);

            stateData.regions.forEach(function (region) {
                if (truncated) {
                    return;
                }

                var regionKey = String(region.key || '').trim() || region.id;
                var regionName = String(region.name || '').trim() || regionKey;
                var regionLabel = String(region.label || '').trim();
                var activeFill = ensureCssColor(region.color, '') || colors.active;
                var inactiveFill = colors.inactive;
                var regionUrl = String(region.url || '').trim();
                var regionInfo = String(region.info || '').trim();
                var regionPolygons = [];
                var regionArea = 0;
                var regionCityCount = 0;

                region.cities.forEach(function (city) {
                    if (truncated || !city.geometry) {
                        return;
                    }

                    if (cityFeatureCount >= maxCityFeatures) {
                        truncated = true;
                        return;
                    }

                    var cityActive = city.active !== false;
                    var cityArea = Number(city.area_km2 || 0);
                    var cityLabel = String(city.label || '').trim();
                    regionArea += cityArea;
                    regionCityCount += 1;
                    cityFeatureCount += 1;

                    features.push({
                        type: 'Feature',
                        geometry: city.geometry,
                        properties: {
                            level: 'city',
                            group_key: groupKey,
                            group_name: groupName,
                            region_key: regionKey,
                            region_name: regionName,
                            name: city.name,
                            display_name: city.display_name,
                            active: cityActive,
                            fill: cityActive ? activeFill : inactiveFill,
                            fill_opacity: cityActive ? colors.active_opacity : colors.inactive_opacity,
                            url: String(city.url || '').trim(),
                            region_url: regionUrl,
                            info: String(city.info || '').trim(),
                            area_km2: cityArea,
                            osm_type: city.osm_type,
                            osm_id: city.osm_id,
                        },
                    });

                    if (cityLabel) {
                        var cityCenter = geometryCenter(city.geometry);
                        if (cityCenter) {
                            features.push({
                                type: 'Feature',
                                geometry: {
                                    type: 'Point',
                                    coordinates: cityCenter,
                                },
                                properties: {
                                    level: 'label',
                                    vm_label: true,
                                    label_kind: 'city',
                                    label: cityLabel,
                                    label_color: '#111827',
                                    group_key: groupKey,
                                    group_name: groupName,
                                    region_key: regionKey,
                                    region_name: regionName,
                                },
                            });
                        }
                    }

                    flattenGeometryPolygons(city.geometry).forEach(function (polygon) {
                        regionPolygons.push(polygon);
                    });
                });

                if (regionPolygons.length) {
                    features.push({
                        type: 'Feature',
                        geometry: {
                            type: 'MultiPolygon',
                            coordinates: regionPolygons,
                        },
                        properties: {
                            level: 'region',
                            group_key: groupKey,
                            group_name: groupName,
                            region_key: regionKey,
                            region_name: regionName,
                            name: regionName,
                            fill: activeFill,
                            fill_opacity: Number((colors.active_opacity * 0.45).toFixed(3)),
                            url: regionUrl,
                            info: regionInfo,
                            area_km2: Number(regionArea.toFixed(3)),
                            city_count: regionCityCount,
                        },
                    });

                    if (regionLabel) {
                        var regionCenter = regionLabelCenterFromPolygons(regionPolygons);
                        if (regionCenter) {
                            features.push({
                                type: 'Feature',
                                geometry: {
                                    type: 'Point',
                                    coordinates: regionCenter,
                                },
                                properties: {
                                    level: 'label',
                                    vm_label: true,
                                    label_kind: 'region',
                                    label: regionLabel,
                                    label_bg: '#111827',
                                    label_color: '#ffffff',
                                    group_key: groupKey,
                                    group_name: groupName,
                                    region_key: regionKey,
                                    region_name: regionName,
                                },
                            });
                        }
                    }
                }
            });

            return {
                geojson: {
                    type: 'FeatureCollection',
                    features: features,
                },
                truncated: truncated,
                cityFeatureCount: cityFeatureCount,
            };
        }

        function writePayloadNow() {
            if (!payloadInput) {
                return;
            }

            payloadInput.value = JSON.stringify(stateToPayload(state));
        }

        function syncOutputs(options) {
            var opts = options || {};
            state.description = groupDescription ? String(groupDescription.value || '') : '';
            if (opts.persistPayload) {
                writePayloadNow();
            }

            var groupKey = groupKeyInput ? String(groupKeyInput.value || '') : '';
            var groupName = groupNameInput ? String(groupNameInput.value || '') : '';
            var preview = buildPreviewGeoJson(state, groupKey, groupName, MAX_PREVIEW_CITY_FEATURES);
            var geojson = preview.geojson;

            if (generatedGeoJson) {
                var pretty = geojson.features.length <= MAX_PRETTY_GEOJSON_FEATURES;
                generatedGeoJson.value = pretty
                    ? JSON.stringify(geojson, null, 2)
                    : JSON.stringify(geojson);

                if (preview.truncated) {
                    generatedGeoJson.value += '\n\nHinweis: Live-Vorschau gekürzt (' + preview.cityFeatureCount + ' Stadtflächen geladen), um die Oberfläche flüssig zu halten.';
                }
            }

            var stats = gatherStats(state);
            if (statRegions) statRegions.textContent = stats.regionCount + ' Regionen';
            if (statCities) statCities.textContent = stats.cityCount + ' Stadtflächen';
            if (statArea) statArea.textContent = stats.areaTotal.toFixed(2) + ' km²';

            updatePreview(geojson, !!opts.fit);
        }

        function queueSync(options) {
            var opts = options || {};
            if (opts.fit) {
                syncOptions.fit = true;
            }
            if (opts.persistPayload) {
                syncOptions.persistPayload = true;
            }

            if (syncQueued) {
                return;
            }

            syncQueued = true;
            window.setTimeout(function () {
                var runOptions = {
                    fit: syncOptions.fit,
                    persistPayload: syncOptions.persistPayload,
                };
                syncQueued = false;
                syncOptions.fit = false;
                syncOptions.persistPayload = false;
                syncOutputs(runOptions);
            }, 30);
        }

        function renderSearchResults(region, target) {
            if (!target) {
                return;
            }

            if (region.busy) {
                target.innerHTML = '<div class="list-group-item text-muted">Suche läuft…</div>';
                return;
            }

            if (!region.searchResults.length) {
                target.innerHTML = '';
                return;
            }

            target.innerHTML = region.searchResults.map(function (item, index) {
                var subtitle = [item.addresstype || '', item.type || '', item.class || ''].filter(Boolean).join(' · ');
                var isLargeArea = !!item.isLargeArea;
                return ''
                    + '<div class="list-group-item">'
                    + '  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">'
                    + '    <div>'
                    + '      <strong>' + escapeHtml(item.name || item.display_name || 'Ort') + '</strong><br>'
                    + '      <small class="text-muted">' + escapeHtml(item.display_name || '') + '</small>'
                    + (subtitle ? '<br><small class="text-muted">' + escapeHtml(subtitle) + '</small>' : '')
                    + (isLargeArea ? '<br><small class="text-warning">Große Fläche: wird beim Import automatisch stark vereinfacht.</small>' : '')
                    + '    </div>'
                    + '    <button type="button" class="btn btn-xs btn-primary" data-action="add-city" data-region-id="' + escapeHtml(region.id) + '" data-result-index="' + index + '">Stadtgrenze hinzufügen</button>'
                    + '  </div>'
                    + '</div>';
            }).join('');
        }

        function updateRegionSearchPanel(region) {
            if (!regionList) {
                return;
            }

            var target = regionList.querySelector('[data-search-results="' + region.id + '"]');
            renderSearchResults(region, target);
        }

        function renderRegions() {
            if (!regionList) {
                return;
            }

            if (!state.regions.length) {
                regionList.innerHTML = '<div class="alert alert-warning" style="margin-bottom:12px">Noch keine Region angelegt. Lege mit dem Button unten die erste Region an.</div>';
                return;
            }

            regionList.innerHTML = state.regions.map(function (region, regionIndex) {
                var visibleCities = region.showAllCities ? region.cities : region.cities.slice(0, MAX_VISIBLE_CITY_ROWS);
                var cityRows = visibleCities.length
                    ? visibleCities.map(function (city, cityIndex) {
                        return ''
                            + '<tr' + (city.active === false ? ' class="text-muted" style="opacity:.65"' : '') + '>'
                            + '<td><strong>' + escapeHtml(city.name) + '</strong><br><small class="text-muted">' + escapeHtml(city.display_name || '') + '</small></td>'
                            + '<td><input type="text" class="form-control input-sm" data-field="city-label" data-region-id="' + escapeHtml(region.id) + '" data-city-index="' + cityIndex + '" value="' + escapeHtml(city.label || '') + '" placeholder="leer = kein Label"></td>'
                            + '<td>' + Number(city.area_km2 || 0).toFixed(2) + ' km²</td>'
                            + '<td style="text-align:center"><input type="checkbox" data-field="city-active" data-region-id="' + escapeHtml(region.id) + '" data-city-index="' + cityIndex + '"' + (city.active !== false ? ' checked' : '') + ' title="Stadtfläche aktiv/inaktiv"></td>'
                            + '<td><input type="text" class="form-control input-sm" data-field="city-url" data-region-id="' + escapeHtml(region.id) + '" data-city-index="' + cityIndex + '" value="' + escapeHtml(city.url || '') + '" placeholder="Optionaler Link je Stadtgebiet"></td>'
                            + '<td><input type="text" class="form-control input-sm" data-field="city-info" data-region-id="' + escapeHtml(region.id) + '" data-city-index="' + cityIndex + '" value="' + escapeHtml(city.info || '') + '" placeholder="Optionaler Info-Text"></td>'
                            + '<td style="width:1%"><button type="button" class="btn btn-xs btn-danger" data-action="remove-city" data-region-id="' + escapeHtml(region.id) + '" data-city-index="' + cityIndex + '">Entfernen</button></td>'
                            + '</tr>';
                    }).join('')
                    : '<tr><td colspan="7" class="text-muted">Noch keine Stadtgrenze hinzugefügt.</td></tr>';

                if (!region.showAllCities && region.cities.length > MAX_VISIBLE_CITY_ROWS) {
                    cityRows += ''
                        + '<tr>'
                        + '<td colspan="7" class="text-muted">'
                        + 'Zur Performance werden ' + MAX_VISIBLE_CITY_ROWS + ' von ' + region.cities.length + ' Stadtflächen angezeigt. '
                        + '<button type="button" class="btn btn-xs btn-default" data-action="show-all-cities" data-region-id="' + escapeHtml(region.id) + '">Alle anzeigen</button>'
                        + '</td>'
                        + '</tr>';
                }

                var regionArea = region.cities.reduce(function (sum, city) {
                    return sum + Number(city.area_km2 || 0);
                }, 0);

                return ''
                    + '<div class="panel panel-default" data-region-id="' + escapeHtml(region.id) + '">'
                    + '  <div class="panel-heading" style="display:flex;justify-content:space-between;align-items:center;gap:10px">'
                    + '    <strong>Region ' + (regionIndex + 1) + '</strong>'
                    + '    <span class="label label-default">Fläche: ' + regionArea.toFixed(2) + ' km²</span>'
                    + '    <button type="button" class="btn btn-xs btn-danger" data-action="remove-region" data-region-id="' + escapeHtml(region.id) + '">Region löschen</button>'
                    + '  </div>'
                    + '  <div class="panel-body">'
                    + '    <div class="row" style="margin-bottom:8px">'
                    + '      <div class="col-sm-3"><label>Name</label><input type="text" class="form-control" data-field="region-name" data-region-id="' + escapeHtml(region.id) + '" value="' + escapeHtml(region.name || '') + '" placeholder="z. B. Kreis Dortmund"></div>'
                    + '      <div class="col-sm-2"><label>Region-Label</label><input type="text" class="form-control" data-field="region-label" data-region-id="' + escapeHtml(region.id) + '" value="' + escapeHtml(region.label || '') + '" placeholder="leer = kein Label"></div>'
                    + '      <div class="col-sm-3"><label>Schlüssel</label><input type="text" class="form-control" data-field="region-key" data-region-id="' + escapeHtml(region.id) + '" value="' + escapeHtml(region.key || '') + '" placeholder="kreis-dortmund"></div>'
                    + '      <div class="col-sm-2"><label>Farbe (überschreibt global)</label>'
                    + '        <div style="display:flex;gap:4px;align-items:center">'
                    + '          <input type="color" data-field="region-color-picker" data-region-id="' + escapeHtml(region.id) + '" value="' + escapeHtml(/^#[0-9a-fA-F]{6}$/.test(region.color || '') ? region.color : '#2f855a') + '" style="width:34px;height:30px;padding:1px;border:1px solid #ccc;border-radius:4px" title="Farbe wählen">'
                    + '          <input type="text" class="form-control" data-field="region-color" data-region-id="' + escapeHtml(region.id) + '" value="' + escapeHtml(region.color || '') + '" placeholder="leer = global">'
                    + '        </div>'
                    + '      </div>'
                    + '      <div class="col-sm-3"><label>Komplette Region verlinken</label><input type="text" class="form-control" data-field="region-url" data-region-id="' + escapeHtml(region.id) + '" value="' + escapeHtml(region.url || '') + '" placeholder="optional /region/kreis-1"></div>'
                    + '    </div>'
                    + '    <div class="row" style="margin-bottom:12px">'
                    + '      <div class="col-sm-12"><label>Info zur Region (optional)</label><input type="text" class="form-control" data-field="region-info" data-region-id="' + escapeHtml(region.id) + '" value="' + escapeHtml(region.info || '') + '" placeholder="Optionaler Beschreibungstext für Popup"></div>'
                    + '    </div>'
                    + '    <div class="well well-sm" style="margin-bottom:10px">'
                    + '      <div class="row">'
                    + '        <div class="col-sm-6"><label>Stadt suchen</label><input type="text" class="form-control" data-field="city-search" data-region-id="' + escapeHtml(region.id) + '" value="' + escapeHtml(region.search || '') + '" placeholder="z. B. Dortmund"></div>'
                    + '        <div class="col-sm-3"><label>Ländercodes</label><input type="text" class="form-control" data-field="countrycodes" data-region-id="' + escapeHtml(region.id) + '" value="' + escapeHtml(region.countrycodes || '') + '" placeholder="z. B. de oder leer = global"></div>'
                    + '        <div class="col-sm-3"><label>&nbsp;</label><button type="button" class="btn btn-primary btn-block" data-action="search-city" data-region-id="' + escapeHtml(region.id) + '">Suche starten</button></div>'
                    + '      </div>'
                    + '      <div class="text-muted" style="margin-top:6px;font-size:12px">Suche wird erst bei Bestätigung (Button oder Enter) ausgeführt.</div>'
                    + '      <div class="list-group" style="margin-top:10px;margin-bottom:0" data-search-results="' + escapeHtml(region.id) + '"></div>'
                    + '    </div>'
                    + '    <div class="table-responsive">'
                    + '      <table class="table table-condensed table-striped" style="margin-bottom:0">'
                    + '        <thead><tr><th>Stadtfläche</th><th>Label</th><th>Fläche</th><th>Aktiv</th><th>Link je Stadtgebiet</th><th>Info je Stadtgebiet</th><th></th></tr></thead>'
                    + '        <tbody>' + cityRows + '</tbody>'
                    + '      </table>'
                    + '    </div>'
                    + '  </div>'
                    + '</div>';
            }).join('');

            state.regions.forEach(function (region) {
                var searchTarget = regionList.querySelector('[data-search-results="' + region.id + '"]');
                renderSearchResults(region, searchTarget);
            });
        }

        function findRegion(regionId) {
            return state.regions.find(function (item) {
                return item.id === regionId;
            }) || null;
        }

        function sanitizeRegionKeys() {
            state.regions.forEach(function (region, index) {
                var raw = String(region.key || '').toLowerCase().trim();
                raw = raw.replace(/[^a-z0-9_-]+/g, '-').replace(/(^-|-$)/g, '');
                if (!raw) {
                    var fallback = String(region.name || '').toLowerCase().trim();
                    fallback = fallback.replace(/[^a-z0-9_-]+/g, '-').replace(/(^-|-$)/g, '');
                    raw = fallback || ('region-' + (index + 1));
                }
                region.key = raw;
            });
        }

        function searchCities(region) {
            var query = String(region.search || '').trim();
            if (query.length < 2) {
                region.searchResults = [];
                region.busy = false;
                updateRegionSearchPanel(region);
                return;
            }

            region.busy = true;
            updateRegionSearchPanel(region);

            var searchUrl = buildNominatimUrl('/search', {
                format: 'jsonv2',
                addressdetails: 1,
                extratags: 1,
                limit: 8,
                q: query,
                countrycodes: String(region.countrycodes || '').trim(),
            });

            fetch(buildProxyUrl(proxyBase, searchUrl), { credentials: 'same-origin' })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    var items = Array.isArray(data) ? data : [];
                    region.searchResults = items.map(function (entry) {
                        var address = entry && entry.address && typeof entry.address === 'object' ? entry.address : {};
                        var extratags = entry && entry.extratags && typeof entry.extratags === 'object' ? entry.extratags : {};
                        var candidate = {
                            name: String(entry.name || entry.display_name || 'Ort'),
                            display_name: String(entry.display_name || ''),
                            type: String(entry.type || ''),
                            class: String(entry.class || entry.category || ''),
                            addresstype: String(entry.addresstype || ''),
                            place_rank: Number(entry.place_rank || 0),
                            osm_type: normalizeOsmType(entry.osm_type || ''),
                            osm_id: Number(entry.osm_id || 0),
                            country_code: String(entry.country_code || address.country_code || '').trim().toUpperCase(),
                            country_code3: String(extratags['ISO3166-1:alpha3'] || '').trim().toUpperCase(),
                        };

                        candidate.isLargeArea = isCountryLikeResult(candidate);
                        return candidate;
                    }).filter(function (entry) {
                        return !!entry.osm_type && entry.osm_id > 0;
                    });
                })
                .catch(function (error) {
                    window.console.error(error);
                    region.searchResults = [];
                })
                .finally(function () {
                    region.busy = false;
                    updateRegionSearchPanel(region);
                });
        }

        function addCityBoundary(region, searchItem) {
            if (!searchItem || !searchItem.osm_type || !searchItem.osm_id) {
                return;
            }

            region.busy = true;
            renderRegions();

            var useLandSource = !!searchItem.isLargeArea && /^[A-Z]{3}$/.test(String(searchItem.country_code3 || '').trim().toUpperCase());
            var geometryPromise = useLandSource
                ? fetchCountryLandGeometry(proxyBase, searchItem)
                : fetchNominatimLookupGeometry(proxyBase, searchItem);

            geometryPromise
                .then(function (result) {
                    var entry = result.entry;
                    var geometry = result.geometry;
                    if (!geometry || (geometry.type !== 'Polygon' && geometry.type !== 'MultiPolygon')) {
                        throw new Error('Gefundener Ort hat keine Polygon-Geometrie');
                    }

                    var geometryPointCount = countGeometryPoints(geometry);
                    var maxPoints = searchItem.isLargeArea ? 14000 : 38000;
                    if (geometryPointCount > maxPoints) {
                        geometry = simplifyGeometryToPointBudget(geometry, maxPoints);
                        geometryPointCount = countGeometryPoints(geometry);
                    }

                    if (geometryPointCount > 90000) {
                        window.alert('Die ausgewählte Fläche ist selbst nach Reduktion noch zu groß (' + geometryPointCount + ' Punkte). Bitte kleinere Ebene wählen.');
                        return;
                    }

                    var alreadyExists = region.cities.some(function (city) {
                        return city.osm_type === searchItem.osm_type && city.osm_id === searchItem.osm_id;
                    });
                    if (alreadyExists) {
                        return;
                    }

                    var areaSqm = geometryAreaSqm(geometry);
                    var fallbackName = String(searchItem.name || (entry && entry.name) || 'Ort');
                    var fallbackDisplayName = String(searchItem.display_name || (entry && entry.display_name) || fallbackName);
                    region.cities.push({
                        id: uid('city'),
                        name: fallbackName,
                        label: fallbackName,
                        display_name: fallbackDisplayName,
                        osm_type: searchItem.osm_type,
                        osm_id: searchItem.osm_id,
                        geometry: geometry,
                        url: '',
                        info: '',
                        area_km2: Number((areaSqm / 1000000).toFixed(3)),
                    });
                })
                .catch(function (error) {
                    window.alert('Stadtgrenze konnte nicht geladen werden. Details siehe Konsole.');
                    window.console.error(error);
                })
                .finally(function () {
                    region.busy = false;
                    renderRegions();
                    sanitizeRegionKeys();
                    if (gatherStats(state).cityCount > 0) {
                        enableLivePreview();
                    }
                    queueSync({ fit: true });
                });
        }

        function bindEvents() {
            if (addRegionButton) {
                addRegionButton.addEventListener('click', function () {
                    state.regions.push(createEmptyRegion());
                    renderRegions();
                    queueSync();
                });
            }

            if (mapContainer) {
                mapContainer.addEventListener('click', function (event) {
                    var target = event.target;
                    if (!(target instanceof HTMLElement)) {
                        return;
                    }

                    var actionNode = target.closest('[data-action="enable-live-preview"]');
                    if (!(actionNode instanceof HTMLElement)) {
                        return;
                    }

                    enableLivePreview();
                    queueSync({ fit: true });
                });
            }

            if (groupDescription) {
                groupDescription.addEventListener('input', function () {
                    queueSync();
                });
            }

            if (groupKeyInput) {
                groupKeyInput.addEventListener('input', function () {
                    queueSync();
                });
            }

            if (groupNameInput) {
                groupNameInput.addEventListener('input', function () {
                    queueSync();
                });
            }

            if (form) {
                form.addEventListener('submit', function () {
                    state.description = groupDescription ? String(groupDescription.value || '') : '';
                    sanitizeRegionKeys();
                    writePayloadNow();
                });
            }

            regionList.addEventListener('input', function (event) {
                var target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                var field = target.getAttribute('data-field');
                if (!field) {
                    return;
                }

                var regionId = target.getAttribute('data-region-id') || '';
                var region = findRegion(regionId);
                if (!region) {
                    return;
                }

                var shouldSync = true;

                if (field === 'region-name') {
                    region.name = target.value;
                } else if (field === 'region-label') {
                    region.label = target.value;
                } else if (field === 'region-key') {
                    region.key = target.value;
                } else if (field === 'region-color') {
                    region.color = ensureCssColor(target.value, '');
                    var pickerEl = regionList.querySelector('[data-field="region-color-picker"][data-region-id="' + region.id + '"]');
                    if (pickerEl && /^#[0-9a-fA-F]{6}$/.test(region.color)) {
                        pickerEl.value = region.color;
                    }
                } else if (field === 'region-color-picker') {
                    region.color = target.value;
                    var textEl = regionList.querySelector('[data-field="region-color"][data-region-id="' + region.id + '"]');
                    if (textEl) {
                        textEl.value = target.value;
                    }
                } else if (field === 'region-url') {
                    region.url = target.value;
                } else if (field === 'region-info') {
                    region.info = target.value;
                } else if (field === 'countrycodes') {
                    region.countrycodes = target.value;
                    shouldSync = false;
                } else if (field === 'city-search') {
                    region.search = target.value;
                    shouldSync = false;
                } else if (field === 'city-url' || field === 'city-info' || field === 'city-label') {
                    var cityIndex = Number(target.getAttribute('data-city-index'));
                    if (!Number.isFinite(cityIndex) || cityIndex < 0 || cityIndex >= region.cities.length) {
                        return;
                    }

                    if (field === 'city-url') {
                        region.cities[cityIndex].url = target.value;
                    } else if (field === 'city-label') {
                        region.cities[cityIndex].label = target.value;
                    } else {
                        region.cities[cityIndex].info = target.value;
                    }
                }

                if (shouldSync) {
                    sanitizeRegionKeys();
                    queueSync();
                }
            });

            regionList.addEventListener('change', function (event) {
                var target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                if (target.getAttribute('data-field') !== 'city-active') {
                    return;
                }

                var regionId = target.getAttribute('data-region-id') || '';
                var region = findRegion(regionId);
                if (!region) {
                    return;
                }

                var cityIndex = Number(target.getAttribute('data-city-index'));
                if (!Number.isFinite(cityIndex) || cityIndex < 0 || cityIndex >= region.cities.length) {
                    return;
                }

                region.cities[cityIndex].active = target.checked;

                var row = target.closest('tr');
                if (row) {
                    row.style.opacity = target.checked ? '' : '.65';
                    row.classList.toggle('text-muted', !target.checked);
                }

                queueSync();
            });

            regionList.addEventListener('click', function (event) {
                var target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                var actionNode = target.closest('[data-action]');
                if (!(actionNode instanceof HTMLElement)) {
                    return;
                }

                var action = actionNode.getAttribute('data-action');
                if (!action) {
                    return;
                }

                var regionId = actionNode.getAttribute('data-region-id') || '';
                var region = findRegion(regionId);
                if (!region) {
                    return;
                }

                if (action === 'remove-region') {
                    state.regions = state.regions.filter(function (entry) {
                        return entry.id !== region.id;
                    });
                    renderRegions();
                    queueSync({ fit: true });
                    return;
                }

                if (action === 'remove-city') {
                    var cityIndex = Number(actionNode.getAttribute('data-city-index'));
                    if (Number.isFinite(cityIndex) && cityIndex >= 0 && cityIndex < region.cities.length) {
                        region.cities.splice(cityIndex, 1);
                        renderRegions();
                        queueSync({ fit: true });
                    }
                    return;
                }

                if (action === 'show-all-cities') {
                    region.showAllCities = true;
                    renderRegions();
                    return;
                }

                if (action === 'search-city') {
                    searchCities(region);
                    return;
                }

                if (action === 'add-city') {
                    var resultIndex = Number(actionNode.getAttribute('data-result-index'));
                    if (Number.isFinite(resultIndex) && resultIndex >= 0 && resultIndex < region.searchResults.length) {
                        addCityBoundary(region, region.searchResults[resultIndex]);
                    }
                }
            });

            regionList.addEventListener('keydown', function (event) {
                var target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                if (event.key !== 'Enter') {
                    return;
                }

                var field = target.getAttribute('data-field');
                if (field !== 'city-search') {
                    return;
                }

                event.preventDefault();

                var regionId = target.getAttribute('data-region-id') || '';
                var region = findRegion(regionId);
                if (!region) {
                    return;
                }

                region.search = target.value;
                searchCities(region);
            });
        }

        renderMapPlaceholder('Live-Preview startet automatisch, sobald Orte vorhanden sind. So bleibt die Seite auch bei großen Datensätzen sofort bedienbar.');
        syncColorControlsFromState();
        syncOptimizeControlsFromState();
        bindColorControls();
        bindOptimizeControls();
        bindEvents();
        renderRegions();
        sanitizeRegionKeys();
        queueSync();

        if (state.regions.length === 0 && !initialGroupKey) {
            state.regions.push(createEmptyRegion());
            renderRegions();
            queueSync();
        }

        if (!initialGroupKey && gatherStats(state).cityCount > 0) {
            window.setTimeout(function () {
                enableLivePreview();
                queueSync({ fit: true });
            }, 60);
        }

        if (initialGroupKey && !dataLoadedFromApi) {
            loadGroupPayloadByApi(initialGroupKey);
        }
    }

    function boot() {
        var root = $('#vm-region-group-builder');
        if (!root) {
            return;
        }

        createBuilder(root);
    }

    // Copy-Buttons (z. B. Einbindungscode in der Gruppenliste) – unabhängig vom Builder.
    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        var button = target.closest('[data-vm-copy-text]');
        if (!(button instanceof HTMLElement)) {
            return;
        }

        var text = button.getAttribute('data-vm-copy-text') || '';

        function showFeedback(ok) {
            var original = button.innerHTML;
            button.innerHTML = ok ? '<i class="rex-icon fa-check"></i>' : '<i class="rex-icon fa-times"></i>';
            button.disabled = true;
            window.setTimeout(function () {
                button.innerHTML = original;
                button.disabled = false;
            }, 1200);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                showFeedback(true);
            }, function () {
                showFeedback(false);
            });
            return;
        }

        // Fallback für unsichere Kontexte
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        var ok = false;
        try {
            ok = document.execCommand('copy');
        } catch (_err) {
            ok = false;
        }
        document.body.removeChild(textarea);
        showFeedback(ok);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
