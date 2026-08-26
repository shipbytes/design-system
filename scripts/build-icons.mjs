/**
 * Icon manifest.
 *
 * Derives icons/icons.json by reading an app that inlines raw <svg> blocks and
 * resolving each path back to its Heroicon name.
 *
 * The system this was extracted from carried 694 inlined SVGs and no icon
 * package. The paths turned out to be Heroicons, which is why the icon layer
 * ports across frameworks for free — the same set exists as blade-heroicons,
 * @heroicons/react and @heroicons/vue.
 *
 * icons/icons.json is already generated and checked in. Re-run this only to
 * derive the manifest from a different app.
 *
 * This resolves each inlined path back to its Heroicon name by matching the
 * literal `d` attribute against the heroicons package, so the manifest is
 * derived rather than guessed. Anything that does not match is reported as
 * custom artwork that a consuming project has to carry itself.
 *
 *   node scripts/build-icons.mjs <path-to-app>
 */
import { readFile, readdir, writeFile, mkdir } from 'node:fs/promises';
import { join, dirname, basename } from 'node:path';
import { fileURLToPath } from 'node:url';
import { normalizePath } from './svg-path.mjs';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const APP = process.argv[2];
if (!APP) {
    console.error('usage: node scripts/build-icons.mjs <path-to-app>');
    process.exit(1);
}
const VIEWS = join(APP, 'resources/views');
const HERO = join(ROOT, 'node_modules/heroicons');

// ---------------------------------------------------------- index heroicons

/** `d` attribute -> [{name, style}] for every icon in the package. */
const index = new Map();

// The app predates Heroicons v2, which redrew most of the set — v1 outline is
// stroke-width 2, v2 is 1.5, and the path data differs. Index BOTH so the
// manifest can say which generation each icon came from, since that decides
// whether a port gets the same shape or a redrawn one.
const SOURCES = [
    { root: join(ROOT, 'node_modules/heroicons-v1'), version: 'v1', dirs: [['24', 'outline'], ['20', 'solid']] },
    { root: HERO, version: 'v2', dirs: [['24', 'outline'], ['24', 'solid'], ['20', 'solid'], ['16', 'solid']] },
];

for (const { root, version, dirs } of SOURCES) {
    for (const [size, style] of dirs) {
        // v1 lays out as outline/ and solid/; v2 as 24/outline etc.
        const dir = version === 'v1' ? join(root, style) : join(root, size, style);
        let files;
        try {
            files = await readdir(dir);
        } catch {
            continue;
        }
        for (const f of files.filter((x) => x.endsWith('.svg'))) {
            const svg = await readFile(join(dir, f), 'utf8');
            for (const m of svg.matchAll(/\sd="([^"]+)"/g)) {
                const key = norm(m[1]);
                if (!index.has(key)) index.set(key, []);
                index.get(key).push({ name: basename(f, '.svg'), size, style, version });
            }
        }
    }
}

/**
 * Compare on geometry, not text. Heroicons v2 ships optimised path data, so the
 * same shape is written differently in the app and in the package — see
 * svg-path.mjs. Falls back to whitespace-collapsed text if a path will not parse.
 */
function norm(d) {
    return normalizePath(d) ?? d.replace(/\s+/g, ' ').trim();
}

// ---------------------------------------------------------- scan the app

async function* blades(dir) {
    for (const e of await readdir(dir, { withFileTypes: true })) {
        const p = join(dir, e.name);
        if (e.isDirectory()) yield* blades(p);
        else if (e.name.endsWith('.blade.php')) yield p;
    }
}

const seen = new Map(); // normalised d -> { count, files:Set }

for await (const file of blades(VIEWS)) {
    const src = await readFile(file, 'utf8');
    const rel = file.slice(APP.length + 1);
    for (const m of src.matchAll(/\sd="(M[^"]{8,})"/g)) {
        const key = norm(m[1]);
        const rec = seen.get(key) ?? { count: 0, files: new Set() };
        rec.count++;
        rec.files.add(rel);
        seen.set(key, rec);
    }
}

/**
 * Heroicons v1 -> v2 renames, for the names the app actually uses.
 *
 * The dashboard was built against v1 and picked up v2 icons later, so it now
 * runs a mix. v2 renamed a third of the set, and blade-heroicons /
 * @heroicons/react / @heroicons/vue all ship v2 — so without this map those
 * names simply fail to resolve. Standardising on v2 also redraws a handful of
 * icons that kept their name; that is accepted, and flagged per icon below.
 */
const V1_TO_V2 = {
    x: 'x-mark',
    'location-marker': 'map-pin',
    mail: 'envelope',
    adjustments: 'adjustments-horizontal',
    template: 'rectangle-group',
    exclamation: 'exclamation-triangle',
    'view-grid': 'squares-2x2',
    'external-link': 'arrow-top-right-on-square',
    search: 'magnifying-glass',
    support: 'lifebuoy',
    duplicate: 'square-2-stack',
    'pencil-alt': 'pencil-square',
    globe: 'globe-americas',
    'badge-check': 'check-badge',
    collection: 'rectangle-stack',
    logout: 'arrow-right-on-rectangle',
    chat: 'chat-bubble-oval-left',
    reply: 'arrow-uturn-left',
    'document-download': 'document-arrow-down',
    'menu-alt-1': 'bars-3-center-left',
    'color-swatch': 'swatch',
    'volume-off': 'speaker-x-mark',
    'dots-vertical': 'ellipsis-vertical',
    'eye-off': 'eye-slash',
    refresh: 'arrow-path',
    photograph: 'photo',
    save: 'inbox-arrow-down',
    'switch-horizontal': 'arrows-right-left',
    'office-building': 'building-office',
    menu: 'bars-3',
    upload: 'arrow-up-tray',
    'plus-sm': 'plus',
    filter: 'funnel',
};

// ---------------------------------------------------------- resolve

const matched = [];
const custom = [];

for (const [d, rec] of seen) {
    const hit = index.get(d);
    if (hit) {
        matched.push({
            heroicon: hit[0].name,
            style: hit[0].style,
            size: hit[0].size,
            version: hit[0].version,
            uses: rec.count,
            files: rec.files.size,
        });
    } else {
        custom.push({ uses: rec.count, files: rec.files.size, d: d.slice(0, 70) });
    }
}

// Collapse duplicates: the same icon appears in several sizes/styles.
const byName = new Map();
for (const m of matched) {
    const cur = byName.get(m.heroicon) ?? {
        heroicon: m.heroicon, uses: 0, styles: new Set(), versions: new Set(),
    };
    cur.uses += m.uses;
    cur.styles.add(`${m.size}/${m.style}`);
    cur.versions.add(m.version);
    byName.set(m.heroicon, cur);
}

const v2Names = new Set(
    (await readdir(join(HERO, '24/outline')).catch(() => [])).map((f) => basename(f, '.svg')),
);

const icons = [...byName.values()]
    .map((i) => {
        const version = [...i.versions].sort().join('+');
        const v1Only = version === 'v1';
        const renamed = V1_TO_V2[i.heroicon];
        return {
            heroicon: i.heroicon,
            uses: i.uses,
            styles: [...i.styles].sort(),
            heroiconsVersion: version,
            // What to import when standardising on v2.
            v2Name: renamed ?? i.heroicon,
            ...(renamed ? { renamedInV2: true } : {}),
            ...(v1Only && !renamed && v2Names.has(i.heroicon)
                ? { redrawnInV2: true }
                : {}),
            ...(v1Only && !renamed && !v2Names.has(i.heroicon)
                ? { missingInV2: true }
                : {}),
        };
    })
    .sort((a, b) => b.uses - a.uses);

const manifest = {
    $description:
        'Heroicons used by the dashboard, derived by matching inlined SVG path data against the heroicons package. Consume via blade-heroicons, @heroicons/react or @heroicons/vue — the names are identical across all three.',
    generated: 'scripts/build-icons.mjs',
    target: 'heroicons v2 — blade-heroicons, @heroicons/react and @heroicons/vue all ship v2',
    totals: {
        distinctPaths: seen.size,
        resolvedToHeroicons: matched.length,
        distinctIcons: icons.length,
        customArtwork: custom.length,
    },
    icons,
    custom: custom
        .sort((a, b) => b.uses - a.uses)
        .map((c) => ({ uses: c.uses, files: c.files, pathStart: c.d })),
};

await mkdir(join(ROOT, 'icons'), { recursive: true });
await writeFile(join(ROOT, 'icons/icons.json'), JSON.stringify(manifest, null, 2) + '\n');

console.log(`scanned ${VIEWS}`);
console.log(`  ${seen.size} distinct SVG paths`);
console.log(`  ${matched.length} resolved to ${icons.length} distinct Heroicons`);
console.log(`  ${custom.length} custom paths (bespoke artwork — illustrations, logos, sparklines)`);
console.log(`  → icons/icons.json`);
console.log(`\ntop 15 by usage:`);
for (const i of icons.slice(0, 15)) {
    console.log(`  ${String(i.uses).padStart(3)}  ${i.heroicon.padEnd(28)} ${i.heroiconsVersion.padEnd(5)} ${i.styles.join(' ')}`);
}
