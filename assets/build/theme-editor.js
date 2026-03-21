/**
 * Vector Maps Theme Editor
 * Wird nur auf der "Themes"-Backend-Seite geladen.
 * Verwendet vmProxyStyleUrl(), vmTransformRequest(), vmApplyTheme() aus vectormaps.js
 */
(function () {
    'use strict';

    /** Farbfelder mit Bezeichnungen */
    const COLOR_FIELDS = {
        land:        'Land / Hintergrund',
        water:       'Wasser',
        green:       'Wald / dichtes Grün',
        farmland:    'Wiesen / Felder / Parks',
        road_major:  'Hauptstraßen',
        road_minor:  'Nebenstraßen',
        road_casing: 'Straßen-Outline (Casing)',
        rail:        'Eisenbahn / Schienen',
        building:    'Gebäude',
        outline:     'Konturen (Gebäude, Parks)',
        label:       'Beschriftung',
        label_halo:  'Beschriftungs-Halo',
        road_label:  'Straßen-Nummern (Text)',
    };

    /** Standard-Farben (OpenFreeMap Liberty-Näherungswerte) */
    const PRESET_DEFAULT = {
        land:        '#f0ede8',
        water:       '#9bc4d4',
        green:       '#a8c87c',
        farmland:    '#c8ddb0',
        road_major:  '#ffd966',
        road_minor:  '#ffffff',
        road_casing: '#cfcdca',
        rail:        '#bbbbbb',
        building:    '#ccc8bc',
        outline:     '#aaaaaa',
        label:       '#333333',
        label_halo:  '#ffffff',
        road_label:  '#333333',
    };

    let previewMap = null;

    /** Initialisiert den gesamten Theme-Editor */
    function init() {
        initPreviewMap();
        bindColorInputs();
        bindPresetButtons();
        bindSaveButton();
        bindThemeCards();
        bindExportButtons();
        bindImportButton();
        // Standard-Preset als Ausgangspunkt laden
        setColors(PRESET_DEFAULT);
    }

    /** Erstellt die MapLibre-Vorschaukarte */
    function initPreviewMap() {
        const container = document.getElementById('vm-te-preview-map');
        if (!container || typeof maplibregl === 'undefined') return;

        previewMap = new maplibregl.Map({
            container: 'vm-te-preview-map',
            style: vmProxyStyleUrl('liberty'),
            center: [13.405, 52.520], // Berlin
            zoom: 12,
            transformRequest: (url) => vmTransformRequest(url),
        });

        previewMap.addControl(new maplibregl.NavigationControl({ showCompass: false }));

        previewMap.on('load', () => {
            const uiLang = (document.documentElement.lang || navigator.language || 'de').slice(0, 2);
            if (typeof vmSetLanguage === 'function') vmSetLanguage(previewMap, uiLang);
            applyCurrentColors();
        });

        // Nach Style-Wechsel im Editor wieder einfärben
        previewMap.on('styledata', () => {
            if (!previewMap.isStyleLoaded()) return;
            applyCurrentColors();
        });
    }

    /** Liest alle aktuellen Farb- und Einstellungswerte aus den Input-Feldern */
    function getCurrentColors() {
        const colors = {};
        Object.keys(COLOR_FIELDS).forEach(key => {
            const input = document.getElementById('vm-te-' + key);
            if (input) colors[key] = input.value;
        });
        // Halo-Stärke als numerischer Wert
        const hwInput = document.getElementById('vm-te-halo_width');
        if (hwInput) colors.halo_width = parseFloat(hwInput.value);
        // Outline-Checkbox
        const cb = document.getElementById('vm-te-customize_outlines');
        colors.customize_outlines = cb ? cb.checked : false;
        return colors;
    }

    /** Setzt Farb- und Einstellungswerte in die Input-Felder */
    function setColors(colors) {
        Object.keys(COLOR_FIELDS).forEach(key => {
            const input = document.getElementById('vm-te-' + key);
            if (input && colors[key]) {
                // Color-Input erwartet 6-stellige Hex-Farben ohne Alpha
                const hex = colors[key].length === 4
                    ? '#' + [...colors[key].slice(1)].map(c => c + c).join('')
                    : colors[key].slice(0, 7);
                input.value = hex;
            }
        });
        // Halo-Stärke
        const hwInput = document.getElementById('vm-te-halo_width');
        if (hwInput) {
            const hw = parseFloat(colors.halo_width);
            hwInput.value = isNaN(hw) ? 1 : hw;
            const label = document.getElementById('vm-te-halo_width_val');
            if (label) label.textContent = hwInput.value;
        }
        // Outline-Checkbox + Sichtbarkeit der Outline-Felder
        const cb = document.getElementById('vm-te-customize_outlines');
        if (cb) {
            cb.checked = colors.customize_outlines === true;
            const fields = document.getElementById('vm-te-outline-fields');
            if (fields) fields.style.display = cb.checked ? '' : 'none';
        }
    }

    /** Wendet die aktuellen Farbpicker-Werte auf die Vorschaukarte an */
    function applyCurrentColors() {
        if (!previewMap || !previewMap.isStyleLoaded()) return;
        vmApplyTheme(previewMap, getCurrentColors());
    }

    /** Live-Update bei jeder Farb- oder Einstellungsänderung */
    function bindColorInputs() {
        Object.keys(COLOR_FIELDS).forEach(key => {
            const input = document.getElementById('vm-te-' + key);
            if (!input) return;
            input.addEventListener('input', applyCurrentColors);
        });
        // Halo-Stärke Range-Slider
        const hwInput = document.getElementById('vm-te-halo_width');
        if (hwInput) {
            hwInput.addEventListener('input', () => {
                const label = document.getElementById('vm-te-halo_width_val');
                if (label) label.textContent = hwInput.value;
                applyCurrentColors();
            });
        }
        // Outline-Checkbox: Toggle Sichtbarkeit + Live-Update
        const cb = document.getElementById('vm-te-customize_outlines');
        if (cb) {
            cb.addEventListener('change', () => {
                const fields = document.getElementById('vm-te-outline-fields');
                if (fields) fields.style.display = cb.checked ? '' : 'none';
                applyCurrentColors();
            });
        }
    }

    /** Preset-Buttons (Standard, Dark, Warm, Mono) */
    function bindPresetButtons() {
        document.querySelectorAll('.vm-te-preset').forEach(btn => {
            btn.addEventListener('click', () => {
                const preset = btn.dataset.preset;
                if (preset === 'default') {
                    setColors(PRESET_DEFAULT);
                } else if (typeof VM_BUILT_IN_THEMES !== 'undefined' && VM_BUILT_IN_THEMES[preset]) {
                    setColors(VM_BUILT_IN_THEMES[preset].colors);
                }
                applyCurrentColors();
            });
        });
    }

    /** Speichern-Button: sendet Formular-Daten ans Backend-API */
    function bindSaveButton() {
        const btn = document.getElementById('vm-te-save');
        const msg = document.getElementById('vm-te-msg');
        if (!btn) return;

        btn.addEventListener('click', async () => {
            const nameInput = document.getElementById('vm-te-name');
            const name = nameInput ? nameInput.value.trim() : '';
            if (!name) {
                showMsg(msg, 'danger', 'Bitte einen Theme-Namen eingeben.');
                return;
            }

            const colors = getCurrentColors();
            btn.disabled = true;

            const formData = new FormData();
            formData.append('vm_theme_name', name);
            Object.entries(colors).forEach(([k, v]) => formData.append('vm_theme_colors[' + k + ']', v));

            try {
                const url = buildApiUrl('save');
                const resp = await fetch(url, { method: 'POST', body: formData });
                const data = await resp.json();
                if (data.success) {
                    showMsg(msg, 'success',
                        '✓ Theme &ldquo;<strong>' + escHtml(data.name) + '</strong>&rdquo; gespeichert! ' +
                        'Verwendung: <code>map-style=&quot;' + escHtml(data.name) + '&quot;</code>'
                    );
                    setTimeout(() => window.location.reload(), 1800);
                } else {
                    showMsg(msg, 'danger', 'Fehler beim Speichern. Bitte Theme-Name und Farben prüfen.');
                }
            } catch (e) {
                showMsg(msg, 'danger', 'Netzwerkfehler: ' + escHtml(String(e)));
            }
            btn.disabled = false;
        });
    }

    /** Bearbeiten- und Löschen-Buttons auf Theme-Karten */
    function bindThemeCards() {
        document.querySelectorAll('.vm-te-load-theme').forEach(btn => {
            btn.addEventListener('click', async () => {
                const themeName = btn.dataset.theme;
                const origHtml  = btn.innerHTML;
                btn.disabled    = true;
                btn.innerHTML   = '...';
                try {
                    // Direkt vom Server laden – zuverlässiger als rex.vector_maps_themes
                    const resp  = await fetch('/?rex_api_vector_maps_theme=' + encodeURIComponent(themeName));
                    if (!resp.ok) throw new Error('HTTP ' + resp.status);
                    const theme = await resp.json();
                    if (theme && theme.colors) {
                        setColors(theme.colors);
                        const nameInput = document.getElementById('vm-te-name');
                        if (nameInput) nameInput.value = themeName;
                        applyCurrentColors();
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                } catch (e) {
                    alert('Fehler beim Laden des Themes: ' + escHtml(String(e)));
                }
                btn.disabled  = false;
                btn.innerHTML = origHtml;
            });
        });

        document.querySelectorAll('.vm-te-delete-theme').forEach(btn => {
            btn.addEventListener('click', async () => {
                const themeName = btn.dataset.theme;
                if (!confirm('Theme "' + themeName + '" wirklich löschen?')) return;

                const formData = new FormData();
                formData.append('vm_theme_name', themeName);

                try {
                    const resp = await fetch(buildApiUrl('delete'), { method: 'POST', body: formData });
                    const data = await resp.json();
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Fehler beim Löschen.');
                    }
                } catch (e) {
                    alert('Netzwerkfehler beim Löschen.');
                }
            });
        });
    }

    /**
     * Baut die Backend-API-URL für eine Aktion.
     * Nutzt die aktuelle Seiten-URL (REDAXO-Backend).
     */
    function buildApiUrl(action) {
        const url = new URL(window.location.href);
        // Bestehende Parameter (z. B. page=…) beibehalten – entfernt nur den API-Action-Param
        url.searchParams.set('rex_api_vm_theme_action', action);
        return url.toString();
    }

    /**
     * Lädt ein Theme (Name + colors-Objekt) als JSON-Datei herunter.
     * @param {string} name
     * @param {{colors?: object, [key: string]: unknown}} themeData
     */
    function exportTheme(name, themeData) {
        const exportData = {
            name,
            colors: themeData.colors || themeData,
            exported: new Date().toISOString().slice(0, 19).replace('T', ' '),
        };
        const json = JSON.stringify(exportData, null, 2);
        const blob = new Blob([json], { type: 'application/json' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = name + '.theme.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    /**
     * Lädt alle eigenen Themes als gebundelte JSON-Datei herunter.
     */
    function exportAllThemes() {
        const themes = (window.rex && window.rex.vector_maps_themes) ? window.rex.vector_maps_themes : {};
        if (!Object.keys(themes).length) {
            alert('Keine eigenen Themes vorhanden.');
            return;
        }
        const exportData = {
            vector_maps_themes: themes,
            exported: new Date().toISOString().slice(0, 19).replace('T', ' '),
        };
        const json = JSON.stringify(exportData, null, 2);
        const blob = new Blob([json], { type: 'application/json' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = 'vector-maps-themes.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    /** Export-Buttons für eigene und eingebaute Themes verdrahten */
    function bindExportButtons() {
        // Eigene Themes: Daten vom Server über den öffentlichen API-Endpunkt holen
        document.querySelectorAll('.vm-te-export-theme').forEach(btn => {
            btn.addEventListener('click', async () => {
                const themeName = btn.dataset.theme;
                try {
                    const resp = await fetch('/?rex_api_vector_maps_theme=' + encodeURIComponent(themeName));
                    if (!resp.ok) throw new Error('HTTP ' + resp.status);
                    const data = await resp.json();
                    exportTheme(themeName, data);
                } catch (e) {
                    alert('Export-Fehler: ' + String(e));
                }
            });
        });

        // Eingebaute Themes: Daten direkt aus VM_BUILT_IN_THEMES
        document.querySelectorAll('.vm-te-export-builtin').forEach(btn => {
            btn.addEventListener('click', () => {
                const themeName = btn.dataset.theme;
                if (typeof VM_BUILT_IN_THEMES !== 'undefined' && VM_BUILT_IN_THEMES[themeName]) {
                    exportTheme(themeName, VM_BUILT_IN_THEMES[themeName]);
                } else {
                    alert('Theme-Daten nicht gefunden.');
                }
            });
        });

        // Alle-exportieren-Button
        const exportAllBtn = document.getElementById('vm-te-export-all-btn');
        if (exportAllBtn) exportAllBtn.addEventListener('click', exportAllThemes);
    }

    /**
     * Import-Button und verstecktes File-Input verdrahten.
     * Unterstützt sowohl Einzel-Theme-Format ({name, colors})
     * als auch gebundeltes Format ({vector_maps_themes: {name: {colors}}}).
     */
    function bindImportButton() {
        const btn       = document.getElementById('vm-te-import-btn');
        const fileInput = document.getElementById('vm-te-import-file');
        const msg       = document.getElementById('vm-te-import-msg');
        if (!btn || !fileInput) return;

        btn.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', async () => {
            const file = fileInput.files[0];
            if (!file) return;

            let parsed;
            try {
                const text = await file.text();
                parsed = JSON.parse(text);
            } catch (e) {
                showMsg(msg, 'danger', 'Fehler: Datei ist kein gültiges JSON. ' + escHtml(String(e)));
                fileInput.value = '';
                return;
            }

            // Gebundeltes Format ({vector_maps_themes: {...}})
            if (parsed.vector_maps_themes && typeof parsed.vector_maps_themes === 'object') {
                const entries = Object.entries(parsed.vector_maps_themes);
                let ok = 0;
                let fail = 0;
                for (const [themeName, themeData] of entries) {
                    try {
                        const resp = await fetch(buildApiUrl('import'), {
                            method: 'POST',
                            body: (() => {
                                const fd = new FormData();
                                fd.append('vm_theme_name', themeName);
                                fd.append('vm_theme_json', JSON.stringify(themeData));
                                return fd;
                            })(),
                        });
                        const result = await resp.json();
                        result.success ? ok++ : fail++;
                    } catch (e) {
                        fail++;
                    }
                }
                fileInput.value = '';
                if (fail === 0) {
                    showMsg(msg, 'success', '✓ ' + ok + ' Theme(s) erfolgreich importiert!');
                } else {
                    showMsg(msg, 'warning', ok + ' Theme(s) importiert, ' + fail + ' fehlgeschlagen.');
                }
                setTimeout(() => window.location.reload(), 1800);
                return;
            }

            // Einzel-Theme-Format ({name?, colors: {...}})
            if (!parsed.colors || typeof parsed.colors !== 'object') {
                showMsg(msg, 'danger', 'Ungültige Theme-Datei: "colors"-Objekt fehlt.');
                fileInput.value = '';
                return;
            }

            const defaultName = file.name.replace(/\.theme\.json$|\.json$/, '');
            const themeName   = (parsed.name || defaultName).toLowerCase().replace(/[^a-z0-9_-]/g, '-');

            const formData = new FormData();
            formData.append('vm_theme_name', themeName);
            formData.append('vm_theme_json', JSON.stringify(parsed));

            try {
                const resp   = await fetch(buildApiUrl('import'), { method: 'POST', body: formData });
                const result = await resp.json();
                if (result.success) {
                    showMsg(msg, 'success',
                        '✓ Theme &ldquo;<strong>' + escHtml(result.name) + '</strong>&rdquo; importiert!' +
                        ' Verwendung: <code>map-style=&quot;' + escHtml(result.name) + '&quot;</code>'
                    );
                    setTimeout(() => window.location.reload(), 1800);
                } else {
                    showMsg(msg, 'danger', 'Fehler beim Importieren. Bitte Theme-Format prüfen.');
                }
            } catch (e) {
                showMsg(msg, 'danger', 'Netzwerkfehler: ' + escHtml(String(e)));
            }
            fileInput.value = '';
        });
    }

    /** Zeigt eine Bootstrap-Alert-Message */
    function showMsg(el, type, html) {
        if (!el) return;
        el.innerHTML = '<div class="alert alert-' + type + '" style="padding:8px 12px;margin:8px 0 0">' + html + '</div>';
    }

    /** Einfaches HTML-Escaping */
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // jQuery ist im REDAXO-Backend immer verfügbar.
    // $(function(){}) feuert sofort wenn DOM bereits bereit ist (sicherer als DOMContentLoaded).
    $(function () {
        if (document.getElementById('vm-te-preview-map')) {
            init();
        }
    });
})();
