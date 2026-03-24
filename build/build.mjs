/**
 * Build-Script für das vector_maps REDAXO-AddOn
 *
 * Usage:
 *   node build.mjs                 – Eigene Assets minifizieren
 *   node build.mjs --update-vendor – Vendor-Assets von node_modules aktualisieren + minifizieren
 *   node build.mjs --watch         – Watch-Modus (eigene Assets)
 *
 * Voraussetzung: npm install (im build/ Verzeichnis)
 */

import * as esbuild from 'esbuild';
import { copyFileSync, mkdirSync, existsSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const addonRoot = resolve(__dirname, '..');
const assetsDir = resolve(addonRoot, 'assets');
const nodeModulesDir = resolve(__dirname, 'node_modules');

const isWatch        = process.argv.includes('--watch');
const isUpdateVendor = process.argv.includes('--update-vendor');

// ---------------------------------------------------------------------------
// Eigene Assets: Source liegt in assets/build/, Output als *.min.{js,css}
// ---------------------------------------------------------------------------

const ownJsFiles = [
    'build/vectormaps.js',
    'build/vectormaps_i18n.js',
    'build/theme-editor.js',
];

const ownCssFiles = [
    'build/vectormaps.css',
];

/**
 * Gibt den Pfad der minifizierten Version zurück.
 * assets/build/vectormaps.js → assets/build/vectormaps.min.js
 */
function minPath(rel) {
    return resolve(assetsDir, rel).replace(/\.(js|css)$/, '.min.$1');
}

async function buildOwnAssets() {
    console.log('\n📦 Eigene Assets minifizieren…\n');

    for (const file of ownJsFiles) {
        const src = resolve(assetsDir, file);
        if (!existsSync(src)) {
            console.warn(`  ⚠ Nicht gefunden: ${file}`);
            continue;
        }
        await esbuild.build({
            entryPoints: [src],
            outfile: minPath(file),
            bundle: false,
            minify: true,
            target: ['es2020'],
            sourcemap: false,
        });
        console.log(`  ✓ ${file} → ${file.replace(/\.js$/, '.min.js')}`);
    }

    for (const file of ownCssFiles) {
        const src = resolve(assetsDir, file);
        if (!existsSync(src)) {
            console.warn(`  ⚠ Nicht gefunden: ${file}`);
            continue;
        }
        await esbuild.build({
            entryPoints: [src],
            outfile: minPath(file),
            bundle: false,
            minify: true,
            sourcemap: false,
        });
        console.log(`  ✓ ${file} → ${file.replace(/\.css$/, '.min.css')}`);
    }
}

// ---------------------------------------------------------------------------
// Vendor-Update: dist-Dateien aus node_modules nach assets/maplibre/ kopieren
// ---------------------------------------------------------------------------

/**
 * Vendor-Dateien die aus node_modules nach assets/maplibre/ kopiert werden.
 * [npm-Paket/Pfad, Zieldateiname, minifizieren?]
 */
const vendorFiles = [
    ['maplibre-gl/dist/maplibre-gl.js',  'maplibre-gl.js',  true],
    ['maplibre-gl/dist/maplibre-gl.css', 'maplibre-gl.css', true],
    ['pmtiles/dist/pmtiles.js',          'pmtiles.js',       true],
];

async function updateVendor() {
    console.log('\n🔄 Vendor-Assets aktualisieren…\n');

    const maplibreDir = resolve(assetsDir, 'maplibre');
    mkdirSync(maplibreDir, { recursive: true });

    for (const [srcRel, destName, minify] of vendorFiles) {
        const src  = resolve(nodeModulesDir, srcRel);
        const dest = resolve(maplibreDir, destName);

        if (!existsSync(src)) {
            console.warn(`  ⚠ Nicht gefunden: ${srcRel}`);
            console.warn('    → "npm install" im build/ Verzeichnis ausführen');
            continue;
        }

        if (minify) {
            await esbuild.build({
                entryPoints: [src],
                outfile: dest,
                bundle: false,
                minify: true,
                allowOverwrite: true,
            });
        } else {
            copyFileSync(src, dest);
        }

        console.log(`  ✓ ${srcRel} → assets/maplibre/${destName}`);
    }
}

// ---------------------------------------------------------------------------
// Watch-Modus
// ---------------------------------------------------------------------------

async function watch() {
    console.log('\n👀 Watch-Modus gestartet (Ctrl+C zum Beenden)…\n');

    const contexts = [];

    for (const file of [...ownJsFiles, ...ownCssFiles]) {
        const src = resolve(assetsDir, file);
        if (!existsSync(src)) continue;

        const isJs = file.endsWith('.js');
        const ctx  = await esbuild.context({
            entryPoints: [src],
            outfile: minPath(file),
            bundle: false,
            minify: true,
            sourcemap: true,   // Source Maps nur im Watch/Dev-Modus
            target: isJs ? ['es2020'] : undefined,
        });

        await ctx.watch();
        console.log(`  👁 ${file}`);
        contexts.push(ctx);
    }

    process.on('SIGINT', async () => {
        for (const ctx of contexts) await ctx.dispose();
        console.log('\nBuild-Watch beendet.');
        process.exit(0);
    });
}

// ---------------------------------------------------------------------------
// Hauptprogramm
// ---------------------------------------------------------------------------

if (isWatch) {
    await watch();
} else {
    if (isUpdateVendor) {
        await updateVendor();
    }
    await buildOwnAssets();
    console.log('\n✅ Build abgeschlossen.\n');
}
