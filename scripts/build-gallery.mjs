/**
 * Component gallery.
 *
 * Renders the REAL Blade components through a host Laravel app, compiles
 * exactly the CSS that output needs, and assembles a self-contained page.
 *
 * The point is that it cannot lie. A hand-drawn gallery drifts from the
 * components the moment either changes; this one breaks when they break.
 *
 *   node scripts/build-gallery.mjs <path-to-host-app>
 *
 * The host app is any Laravel project with this package installed — it is only
 * there to render Blade, and nothing about it ends up in the output. A throwaway
 * `laravel new` plus `composer require shipbytes/blade-ui` is enough.
 *
 * Writes dist/gallery.html.
 */
import { readFile, writeFile, mkdir, rm } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

const run = promisify(execFile);
const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const TMP = join(ROOT, '.gallery-tmp');

await rm(TMP, { recursive: true, force: true });
await mkdir(TMP, { recursive: true });

// ---------------------------------------------------------------- 1. render

console.log('rendering specimens through Laravel…');

/*
  Rendered by scripts/render.php, which boots Laravel through testbench — so the
  gallery needs no host application. NOT `php artisan tinker --execute`: PsySH
  rewrites the markup on its way out, turning `...` into `..`, which breaks every
  spread operator in a component and produced a gallery that invented a syntax
  error the shipped code did not have.
*/
let specimens;
try {
    const { stdout } = await run('php', [join(ROOT, 'scripts/render.php'), join(ROOT, 'scripts/gallery.blade.php')], {
        cwd: ROOT,
        maxBuffer: 32 * 1024 * 1024,
    });
    specimens = stdout;
} catch (err) {
    console.error('render failed:');
    console.error(String(err.stderr || err).slice(0, 1200));
    console.error('\nIf this is a fresh clone, run `composer install` first.');
    process.exit(1);
}

// The canary, before anything else looks at the markup.
if (!specimens.includes('<!--ds-canary [...ok]-->')) {
    console.error(
        'the renderer altered the markup on its way out — the gallery would be a lie.\n' +
        'expected the canary `[...ok]`, got: ' +
        (specimens.match(/<!--ds-canary[^>]*-->/)?.[0] ?? '(no canary at all)'),
    );
    process.exit(1);
}
specimens = specimens.replace('<!--ds-canary [...ok]-->', '');

// A Blade error renders as text rather than throwing, and an uncompiled
// component tag comes through literally. Both are silent; catch them here.
if (/<x-ds::|<x-dynamic-component/.test(specimens)) {
    console.error('render produced uncompiled component tags — the gallery would be a lie.');
    console.error(specimens.match(/<x-[^>]{0,120}/)?.[0]);
    process.exit(1);
}
if (!/<button|<span/.test(specimens)) {
    console.error('render produced no components:');
    console.error(specimens.slice(0, 600));
    process.exit(1);
}

console.log(`  ${(specimens.length / 1024).toFixed(1)} kB of specimen markup`);

await writeFile(join(TMP, 'specimens.html'), specimens);

// ---------------------------------------------------------------- 2. css

console.log('compiling the CSS that markup needs…');

await writeFile(
    join(TMP, 'in.css'),
    `@import "tailwindcss";
@import "${join(ROOT, 'dist/tokens.css')}";
@import "${join(ROOT, 'dist/theme.css')}";
@source "${join(TMP, 'specimens.html')}";
`,
);

await run(
    'npx',
    ['@tailwindcss/cli', '-i', join(TMP, 'in.css'), '-o', join(TMP, 'out.css'), '--minify'],
    { cwd: ROOT, maxBuffer: 32 * 1024 * 1024 },
);

const css = await readFile(join(TMP, 'out.css'), 'utf8');
console.log(`  ${(css.length / 1024).toFixed(1)} kB of CSS`);

// ---------------------------------------------------------------- 3. tokens

const tokens = JSON.parse(await readFile(join(ROOT, 'dist/tokens.json'), 'utf8'));

const SWATCH_ORDER = [
    ['surface', 'the card content sits on'],
    ['surface-sunken', 'the ground behind it'],
    ['surface-subtle', 'table heads, row hover'],
    ['surface-inverse', 'primary buttons, the FAB'],
    ['border', 'the default hairline'],
    ['border-strong', 'a card edge that must read as raised'],
    ['fg', 'headings'],
    ['fg-body', 'running copy'],
    ['fg-muted', 'labels, meta, timestamps'],
    ['fg-subtle', 'icons that repeat an adjacent label'],
    ['accent', 'links'],
    ['success', 'success'],
    ['warning', 'warning'],
    ['danger', 'danger'],
];

const swatches = SWATCH_ORDER.map(([name, job]) => {
    const t = tokens.semantic[name];
    if (!t) return '';
    return `<tr>
      <td class="sw"><span class="chip" style="background:${t.light}"></span></td>
      <td class="sw"><span class="chip dark" style="background:${t.dark}"></span></td>
      <td class="tk">${name}</td>
      <td class="jb">${job}</td>
    </tr>`;
}).join('\n');

const TYPE_ORDER = ['display', 'heading', 'section', 'body', 'body-touch', 'meta', 'overline'];
const typeRows = TYPE_ORDER.map((name) => {
    const t = tokens.text?.[name];
    if (!t) return '';
    const px = (rem) => `${Math.round(parseFloat(rem) * 16)}`;
    const style = [
        `font-size:${t.fontSize}`,
        `line-height:${t.lineHeight}`,
        `font-weight:${t.fontWeight}`,
        t.letterSpacing ? `letter-spacing:${t.letterSpacing}` : '',
        t.textTransform ? `text-transform:${t.textTransform}` : '',
    ]
        .filter(Boolean)
        .join(';');
    return `<tr>
      <td class="tk">${name}</td>
      <td class="jb">${px(t.fontSize)}/${px(t.lineHeight)} &middot; ${t.fontWeight}</td>
      <td class="samp"><span style="${style}">The quick brown fox jumps</span></td>
    </tr>`;
}).join('\n');

// ---------------------------------------------------------------- 4. page

const page = `<title>Shipbytes Component Gallery</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&family=Inter:wght@400;500;600;700&display=swap">

<style>
${css}
</style>

<style>
/*
  Gallery chrome.

  Deliberately unlike the specimens: the system is cool zinc set in Inter, so
  the frame around it is a warm stone set in a serif. And the chrome carries NO
  accent colour of its own — every bit of colour on this page belongs to the
  thing being judged. Structure comes from rules, weight and letter-spacing.
*/
:root {
  --g-ground: #faf9f7;
  --g-panel: #f2efea;
  --g-ink: #1c1917;
  --g-body: #57534e;
  --g-mute: #8a827b;
  --g-rule: #e3ded7;
  --g-rule-firm: #cfc8bf;
}
:root:not([data-theme="light"]) { }
@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    --g-ground: #16130f;
    --g-panel: #1f1b16;
    --g-ink: #f5f1ea;
    --g-body: #b8b0a6;
    --g-mute: #857d73;
    --g-rule: #302a23;
    --g-rule-firm: #453d33;
  }
}
:root[data-theme="dark"] {
  --g-ground: #16130f;
  --g-panel: #1f1b16;
  --g-ink: #f5f1ea;
  --g-body: #b8b0a6;
  --g-mute: #857d73;
  --g-rule: #302a23;
  --g-rule-firm: #453d33;
}

body {
  margin: 0;
  background: var(--g-ground);
  color: var(--g-body);
  font-family: "IBM Plex Sans", ui-sans-serif, system-ui, sans-serif;
  font-size: 15px;
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
}

.wrap { max-width: 60rem; margin: 0 auto; padding: 0 2rem 8rem; }

/* ---- masthead ---- */
.mast { padding: 5rem 0 2rem; border-bottom: 1px solid var(--g-rule-firm); }
.eyebrow {
  font-family: "IBM Plex Mono", ui-monospace, monospace;
  font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase;
  color: var(--g-mute); margin: 0 0 1.25rem;
}
.mast h1 {
  font-family: "Instrument Serif", Georgia, serif;
  font-weight: 400; font-size: clamp(2.75rem, 6vw, 4.25rem); line-height: 1.02;
  letter-spacing: -0.015em; color: var(--g-ink); margin: 0; text-wrap: balance;
}
.mast .lede {
  margin: 1.25rem 0 0; max-width: 34rem; font-size: 1.0625rem; line-height: 1.65;
  color: var(--g-body);
}
.mast .lede em { font-family: "Instrument Serif", Georgia, serif; font-style: italic; font-size: 1.15em; color: var(--g-ink); }

/* ---- controls ---- */
.controls {
  position: sticky; top: 0; z-index: 20;
  display: flex; flex-wrap: wrap; align-items: center; gap: 1rem;
  padding: 0.875rem 0; margin-bottom: 2.5rem;
  background: color-mix(in srgb, var(--g-ground) 88%, transparent);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--g-rule);
}
.controls .label {
  font-family: "IBM Plex Mono", ui-monospace, monospace;
  font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--g-mute);
}
.seg { display: inline-flex; gap: 2px; padding: 3px; border-radius: 8px; background: var(--g-panel); border: 1px solid var(--g-rule); }
.seg button {
  appearance: none; border: 0; background: transparent; cursor: pointer;
  font: inherit; font-size: 13px; font-weight: 500; color: var(--g-body);
  padding: 0.3rem 0.7rem; border-radius: 6px; line-height: 1.4;
}
.seg button[aria-pressed="true"] { background: var(--g-ground); color: var(--g-ink); box-shadow: 0 1px 2px rgb(0 0 0 / 0.06); }
.seg button:focus-visible { outline: 2px solid var(--g-ink); outline-offset: 1px; }
.jump { margin-left: auto; display: flex; flex-wrap: wrap; gap: 1.1rem; }
.jump a { font-size: 13px; color: var(--g-mute); text-decoration: none; border-bottom: 1px solid transparent; }
.jump a:hover { color: var(--g-ink); border-bottom-color: var(--g-rule-firm); }
.jump a:focus-visible { outline: 2px solid var(--g-ink); outline-offset: 2px; }

/* ---- sections ---- */
.ds-spec { padding: 3.5rem 0; border-bottom: 1px solid var(--g-rule); }
.ds-spec:last-of-type { border-bottom: 0; }
.ds-spec-head h2 {
  font-family: "Instrument Serif", Georgia, serif; font-weight: 400;
  font-size: 2.25rem; line-height: 1.1; letter-spacing: -0.01em;
  color: var(--g-ink); margin: 0 0 0.6rem;
}
.ds-spec-head p { margin: 0; max-width: 42rem; color: var(--g-body); }
.ds-note {
  margin: 1.5rem 0 0; padding-left: 0.9rem; border-left: 2px solid var(--g-rule-firm);
  font-size: 13.5px; color: var(--g-mute);
}
.ds-note em { font-style: normal; color: var(--g-ink); font-weight: 500; }

/* ---- specimen plate ---- */
.ds-plate { margin-top: 1.75rem; border: 1px solid var(--g-rule); border-radius: 10px; overflow: hidden; }
.ds-plate-label {
  font-family: "IBM Plex Mono", ui-monospace, monospace;
  font-size: 11px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--g-mute);
  padding: 0.6rem 1rem; border-bottom: 1px solid var(--g-rule);
}
.ds-plate-label span { text-transform: none; letter-spacing: 0.02em; }
/* The specimen sits on the SYSTEM's own surface, not the gallery's, so what you
   see is what the app renders. Its theme is switched independently below.

   The ground goes on the plate itself rather than on its first child — putting
   it on the child silently overrode the background of any specimen that happened
   to be the direct child, which made a white feature panel render grey. */
.ds-plate { background: var(--ds-surface-sunken); }
.ds-plate-label { background: var(--g-panel); }
.ds-plate > :not(.ds-plate-label) {
  padding: 1.75rem 1rem;
  font-family: Inter, ui-sans-serif, system-ui, sans-serif;
}
.ds-tick {
  font-family: "IBM Plex Mono", ui-monospace, monospace;
  font-size: 10.5px; color: var(--ds-fg-muted); letter-spacing: 0.02em;
}
.ds-tick code { font-family: inherit; color: var(--ds-fg); }

/* ---- reference tables ---- */
.tbl-wrap { margin-top: 1.75rem; overflow-x: auto; border: 1px solid var(--g-rule); border-radius: 10px; }
table.ref { width: 100%; border-collapse: collapse; font-size: 13.5px; background: var(--g-panel); }
table.ref td { padding: 0.55rem 0.9rem; border-bottom: 1px solid var(--g-rule); vertical-align: middle; }
table.ref tr:last-child td { border-bottom: 0; }
table.ref .sw { width: 2.75rem; }
table.ref .tk { font-family: "IBM Plex Mono", ui-monospace, monospace; color: var(--g-ink); white-space: nowrap; }
table.ref .jb { color: var(--g-mute); }
table.ref .samp { color: var(--g-ink); font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
.chip { display: block; width: 1.55rem; height: 1.55rem; border-radius: 5px; border: 1px solid rgb(0 0 0 / 0.14); }
.chip.dark { border-color: rgb(255 255 255 / 0.18); }

/* ---- footer ---- */
.foot { padding-top: 3rem; font-size: 13px; color: var(--g-mute); }
.foot code { font-family: "IBM Plex Mono", ui-monospace, monospace; color: var(--g-body); }

@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
}
</style>

<div class="wrap">
  <header class="mast">
    <p class="eyebrow">Shipbytes design system &middot; v0.1</p>
    <h1>Every component, every state, in both themes.</h1>
    <p class="lede">
      Rendered from the actual Blade components rather than drawn to look like them &mdash;
      so if one is broken, <em>this page is broken too</em>. That is the only kind of gallery
      worth reviewing against.
    </p>
  </header>

  <div class="controls">
    <span class="label">Specimen theme</span>
    <div class="seg" role="group" aria-label="Specimen theme">
      <button type="button" data-theme-btn="light" aria-pressed="true">Light</button>
      <button type="button" data-theme-btn="dark" aria-pressed="false">Dark</button>
    </div>
    <nav class="jump" aria-label="Sections">
      <a href="#button">Button</a>
      <a href="#badge">Badge</a>
      <a href="#icon">Icon</a>
      <a href="#input">Input</a>
      <a href="#alert">Alert</a>
      <a href="#modal">Modal</a>
      <a href="#dropdown">Dropdown</a>
      <a href="#drawer">Drawer</a>
      <a href="#select">Select</a>
      <a href="#choice">Choice</a>
      <a href="#switch">Switch</a>
      <a href="#combobox">Combobox</a>
      <a href="#date-picker">Date</a>
      <a href="#file-upload">Upload</a>
      <a href="#accordion">Accordion</a>
      <a href="#tabs">Tabs</a>
      <a href="#tooltip">Tooltip</a>
      <a href="#toast">Toast</a>
      <a href="#avatar">Avatar</a>
      <a href="#breadcrumb">Breadcrumb</a>
      <a href="#empty-state">Empty</a>
      <a href="#skeleton">Skeleton</a>
      <a href="#sheet">Sheet</a>
      <a href="#stat-tile">Stat tile</a>
      <a href="#panel">Panel</a>
      <a href="#table">Table</a>
      <a href="#nav-item">Nav item</a>
      <a href="#in-situ">Together</a>
      <a href="#tokens">Tokens</a>
    </nav>
  </div>

  <div id="specimens">
${specimens}
  </div>

  <section class="ds-spec" id="tokens">
    <header class="ds-spec-head">
      <h2>Tokens</h2>
      <p>Every token names a job, not a colour. Both themes are shown side by side because that is the only way drift between them is visible.</p>
    </header>

    <div class="tbl-wrap">
      <table class="ref">
        <tbody>
${swatches}
        </tbody>
      </table>
    </div>

    <div class="ds-note">
      <em>fg-subtle</em> is 2.63:1 on the card in light &mdash; below the 3:1 a meaningful mark needs.
      It is allowed only where the information is also written next to it. Timestamps are not
      redundant, so they use <em>fg-muted</em>.
    </div>

    <div class="tbl-wrap">
      <table class="ref">
        <tbody>
${typeRows}
        </tbody>
      </table>
    </div>
  </section>

  <footer class="foot">
    Regenerate with <code>npm run build:gallery</code>. Specimens come from
    <code>packages/blade/resources/views/gallery.blade.php</code>; the values in the
    tables come from <code>dist/tokens.json</code>.
  </footer>
</div>

<script>
(function () {
  var root = document.getElementById('specimens');
  var buttons = document.querySelectorAll('[data-theme-btn]');

  function apply(theme) {
    // The gallery chrome keeps the viewer's own theme; only the specimens flip,
    // so there is always a fixed reference to judge against.
    root.classList.toggle('dark', theme === 'dark');
    document.querySelectorAll('.ds-plate').forEach(function (p) {
      p.classList.toggle('dark', theme === 'dark');
    });
    buttons.forEach(function (b) {
      b.setAttribute('aria-pressed', String(b.dataset.themeBtn === theme));
    });
    try { localStorage.setItem('ds-gallery-theme', theme); } catch (e) {}
  }

  buttons.forEach(function (b) {
    b.addEventListener('click', function () { apply(b.dataset.themeBtn); });
  });

  var saved = null;
  try { saved = localStorage.getItem('ds-gallery-theme'); } catch (e) {}
  apply(saved === 'dark' ? 'dark' : 'light');
})();
</script>
`;

await mkdir(join(ROOT, 'dist'), { recursive: true });
await writeFile(join(ROOT, 'dist/gallery.html'), page);
await rm(TMP, { recursive: true, force: true });

console.log(`  → dist/gallery.html (${(page.length / 1024).toFixed(1)} kB)`);
