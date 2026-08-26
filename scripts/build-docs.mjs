/**
 * Documentation screenshots.
 *
 * Renders the REAL components through a host Laravel app, compiles exactly the
 * CSS that output needs, and photographs each one — light and dark side by side
 * in a single image, so the two themes can never drift apart in the docs.
 *
 *   node scripts/build-docs.mjs <path-to-host-app>
 *
 * Writes docs/images/<component>.png.
 *
 * The images are COMMITTED, because regenerating them needs a host app and a
 * browser and most people reading the docs have neither. Re-run this whenever a
 * component's appearance changes; `npm test` cannot tell you that it has.
 *
 * Needs a Chromium. It looks for one in DS_CHROME, then in the Playwright
 * browser cache, then in the usual system locations.
 */
import { readFile, writeFile, mkdir, rm, readdir } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { homedir } from 'node:os';
import { fileURLToPath } from 'node:url';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { findChromium, NO_BROWSER } from './chromium.mjs';

const run = promisify(execFile);
const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const TMP = join(ROOT, '.docs-tmp');
const OUT = join(ROOT, 'docs/images');

const CHROME = await findChromium();
if (!CHROME) {
    console.error('\n' + NO_BROWSER);
    process.exit(1);
}

let chromium;
try {
    ({ chromium } = await import('playwright-core'));
} catch {
    console.error('\nplaywright-core is not installed — run `npm install`.\n');
    process.exit(1);
}

await rm(TMP, { recursive: true, force: true });
await mkdir(TMP, { recursive: true });
await mkdir(OUT, { recursive: true });

// ---------------------------------------------------------------- 1. render

console.log('\nrendering specimens through Laravel…');

/*
  Rendered by scripts/render.php, which boots Laravel through testbench — so this
  needs no host application. NOT `php artisan tinker --execute`: PsySH rewrites
  the markup on its way out, turning `...` into `..`, which breaks every spread
  operator in a component. See CLAUDE.md.
*/
let specimens;
try {
    const { stdout } = await run('php', [join(ROOT, 'scripts/render.php'), join(ROOT, 'scripts/docs-specimens.blade.php')], {
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

if (!specimens.includes('<!--ds-canary [...ok]-->')) {
    console.error('the renderer altered the markup on its way out — the images would be a lie.');
    process.exit(1);
}
specimens = specimens.replace('<!--ds-canary [...ok]-->', '');

if (/<x-ds::|<x-dynamic-component/.test(specimens)) {
    console.error('render produced uncompiled component tags:');
    console.error(specimens.match(/<x-[^>]{0,120}/)?.[0]);
    process.exit(1);
}

await writeFile(join(TMP, 'specimens.html'), specimens);
console.log(`  ${(specimens.length / 1024).toFixed(1)} kB of specimen markup`);

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

await run('npx', ['@tailwindcss/cli', '-i', join(TMP, 'in.css'), '-o', join(TMP, 'out.css')], {
    cwd: ROOT,
    maxBuffer: 32 * 1024 * 1024,
});

const css = await readFile(join(TMP, 'out.css'), 'utf8');

// ---------------------------------------------------------------- 3. page

/*
  The viewport is 1000px WIDE on purpose: under Tailwind's `lg` (1024px) so the
  bottom nav and the sheet — both `lg:hidden` — actually render, and over `sm`
  (640px) so everything else takes its desktop size. A media query reads the
  viewport, not the container, so this is the only lever there is.
*/
/*
  Alpine is loaded, so components that manage their OWN state photograph in the
  state a reader actually meets them in — a select and a tooltip render closed,
  and the script below opens the ones whose image should show them open.

  Overlays are unaffected: `open="true"` with no x-data ancestor means Alpine
  never claims them, so they render visible, which is what their image needs.
*/
const alpine = await readFile(join(ROOT, 'node_modules/alpinejs/dist/cdn.min.js'), 'utf8');

const page = `<style>
${css}
</style>
<style>
  body { margin: 0; background: #fff; }
  [x-cloak] { display: none !important; }
  .shot { width: 1000px; display: grid; grid-template-columns: 1fr 1fr; }
  .shot-wide { width: 1360px; }
  .pane { padding: 28px; overflow: hidden; }
  /* A divider between the two themes, so the seam is deliberate rather than
     looking like a rendering artefact in the image. */
  .pane + .pane { box-shadow: inset 1px 0 0 rgb(128 128 128 / 0.35); }
  .shot-overlay .pane { height: 400px; padding: 0; }
  .shot-short .pane { height: 220px; padding: 0; }
  .shot-tall .pane { height: 320px; }
  .shot-calendar .pane { height: 460px; }
  /* Animations are frozen: a pulsing skeleton or a spinning button photographs
     at a random frame, so the image would change on every run and the diff
     would be noise.

     TRANSITIONS are left alone. Alpine drives x-transition by reading the
     computed duration, and killing transitions globally can leave an element
     parked at its enter-start state — opacity-0 — which photographs as an
     empty box. They are waited out below instead. */
  *, *::before, *::after { animation: none !important; }
</style>
${specimens}
<script>${alpine}</script>
`;

await writeFile(join(TMP, 'page.html'), page);

// ---------------------------------------------------------------- 4. shoot

console.log(`photographing with ${CHROME}…\n`);

const browser = await chromium.launch({ executablePath: CHROME });
const tab = await browser.newPage({
    viewport: { width: 1000, height: 900 },
    deviceScaleFactor: 2,
});

const problems = [];
tab.on('pageerror', (e) => problems.push(String(e)));

await tab.goto('file://' + join(TMP, 'page.html'), { waitUntil: 'networkidle' });
await tab.waitForTimeout(600);

const names = await tab.$$eval('[data-shot]', (nodes) => nodes.map((n) => n.dataset.shot));

/*
  Components whose open state lives inside themselves have to be operated, not
  configured — which is the point: the image then shows what the component
  actually does, not a pose arranged for the photograph.

  Both panes of a shot get the same treatment, so light and dark match.
*/
const OPERATE = {
    /*
     * Dispatched directly on the element, and NOT bubbling.
     *
     * A real click reaches the document, and every open dropdown and listbox is
     * listening there for exactly that in order to close itself — so operating
     * the second pane closed the first, and the light half of the image came
     * back empty. `focus()` has the same problem for a different reason: focus
     * lives on one element at a time, so focusing the dark pane's trigger blanks
     * the light one's tooltip.
     *
     * A non-bubbling event still fires the listener on the element itself, which
     * is where Alpine put it, and nothing else ever hears it.
     */
    select: async (pane) =>
        pane.locator('[data-ds-select-trigger]').dispatchEvent('click', { bubbles: false }),
    tooltip: async (pane) => pane.locator('[data-ds-tooltip]').dispatchEvent('mouseenter'),
    'date-picker': async (pane) =>
        pane.locator('[data-ds-date-trigger]').dispatchEvent('click', { bubbles: false }),
    combobox: async (pane) => pane.locator('input[type="text"]').dispatchEvent('focus'),
    // Real files, set on the real input, so the list and the thumbnail are the
    // component's own doing rather than a pose arranged for the photograph.
    'file-upload': async (pane) =>
        pane.locator('input[type="file"]').setInputFiles([
            join(ROOT, 'docs/images/badge.png'),
            join(ROOT, 'docs/images/breadcrumb.png'),
        ]),
};

/*
  Operated shots go LAST.

  Belt and braces on top of the non-bubbling dispatch above: whatever a future
  interaction turns out to need, the components that were photographed without
  being touched are already safely captured. The failure this guards against
  costs a blank image and reports nothing.
*/
const order = [...names].sort((a, b) => Boolean(OPERATE[a]) - Boolean(OPERATE[b]));

let total = 0;
for (const name of order) {
    const target = tab.locator(`[data-shot="${name}"]`);

    if (OPERATE[name]) {
        const panes = target.locator('.pane');
        for (let i = 0; i < (await panes.count()); i++) {
            await OPERATE[name](panes.nth(i));
        }
        await tab.waitForTimeout(400);
    }

    const box = await target.boundingBox();

    if (!box || box.height < 20) {
        problems.push(`${name}: rendered with no height — the specimen is empty`);
        continue;
    }

    await target.screenshot({ path: join(OUT, `${name}.png`) });
    const { size } = await readFile(join(OUT, `${name}.png`)).then((b) => ({ size: b.length }));
    total += size;
    console.log(`  ${name.padEnd(14)} ${String(Math.round(box.width)).padStart(4)}×${String(Math.round(box.height)).padEnd(4)}  ${(size / 1024).toFixed(0)} kB`);
}

// ---------------------------------------------------------------- 5. contrast

/*
  Every specimen is on screen in both themes right now, which makes this the one
  cheap moment to ask the browser what the text ACTUALLY resolves to.

  This exists because the panel painted its own background and left the text
  colour to be inherited, so a row rendered at the browser's default black —
  1.4:1 on the dark surface, invisible — and every other check passed. The token
  tests verify the PAIRS the system defines; they cannot see an element that
  ended up using no token at all.

  Colours are composited through canvas so alpha resolves properly: the tints are
  translucent in dark, and comparing against the undiluted colour reports failures
  that are not there.
*/
console.log('\nchecking what the text actually resolves to…');

const lowContrast = await tab.evaluate(() => {
    const cv = document.createElement('canvas');
    cv.width = cv.height = 1;
    const cx = cv.getContext('2d', { willReadFrequently: true });

    const rgba = (css) => {
        cx.clearRect(0, 0, 1, 1);
        cx.fillStyle = css;
        cx.fillRect(0, 0, 1, 1);
        return [...cx.getImageData(0, 0, 1, 1).data];
    };
    const over = (f, b) => f.slice(0, 3).map((c, i) => c * (f[3] / 255) + b[i] * (1 - f[3] / 255));
    const lum = (c) => {
        const [r, g, b] = c.map((v) => {
            v /= 255;
            return v <= 0.04045 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4;
        });
        return 0.2126 * r + 0.7152 * g + 0.0722 * b;
    };

    const ground = (el) => {
        const layers = [];
        for (let e = el; e; e = e.parentElement) {
            const c = getComputedStyle(e).backgroundColor;
            if (c && c !== 'rgba(0, 0, 0, 0)') layers.push(rgba(c));
        }
        layers.push([255, 255, 255, 255]);
        return layers.reduceRight((under, layer) => over(layer, under), [255, 255, 255]);
    };

    const found = [];

    for (const el of document.querySelectorAll('[data-shot] .pane *')) {
        const hasOwnText = [...el.childNodes].some((n) => n.nodeType === 3 && n.textContent.trim());
        if (!hasOwnText) continue;

        const style = getComputedStyle(el);
        if (style.visibility === 'hidden' || style.display === 'none') continue;

        // Inactive controls are exempt from WCAG 1.4.3, and dimming them is the
        // point — a disabled menu item that reads at full strength is worse.
        if (el.closest('[aria-disabled="true"], [disabled], :disabled')) continue;

        const bg = ground(el);
        const fg = over(rgba(style.color), bg);
        const [hi, lo] = [lum(fg), lum(bg)].sort((a, b) => b - a);
        const ratio = (hi + 0.05) / (lo + 0.05);

        // 3.0 for large text, per WCAG: 24px, or 18.66px at 700+.
        const px = parseFloat(style.fontSize);
        const large = px >= 24 || (px >= 18.66 && parseInt(style.fontWeight, 10) >= 700);

        if (ratio < (large ? 3 : 4.5)) {
            found.push({
                shot: el.closest('[data-shot]').dataset.shot,
                theme: el.closest('.pane').classList.contains('dark') ? 'dark' : 'light',
                text: el.textContent.trim().slice(0, 40),
                color: style.color,
                ratio: Math.round(ratio * 100) / 100,
                needs: large ? 3 : 4.5,
            });
        }
    }

    return found;
});

/*
  Under 3:1 FAILS the build; 3:1 to AA is reported and allowed through.

  The split is the difference between broken and marginal. The panel's inherited
  black was 1.4:1 — text that is simply not there, and no image should ever ship
  carrying it. The known shortfalls are `fg-muted` and `danger` on the app's
  sunken ground at ~4.39, which are a palette decision recorded as advisory in
  test-tokens.mjs; blocking every docs build on them would just teach whoever
  hits it to skip the check.
*/
const seen = new Set();
let broken = 0;

for (const row of lowContrast) {
    const key = `${row.shot}|${row.theme}|${row.text}`;
    if (seen.has(key)) continue;
    seen.add(key);

    const line = `${row.shot} (${row.theme}): "${row.text.replace(/\s+/g, ' ')}" is ${row.ratio}:1, needs ${row.needs}`;

    if (row.ratio < 3) {
        broken++;
        problems.push(`UNREADABLE — ${line} — ${row.color}`);
    } else {
        console.log(`  \x1b[33m!\x1b[0m ${line}`);
    }
}

if (!seen.size) {
    console.log('  every specimen reads at 4.5:1 or better, in both themes');
} else if (!broken) {
    console.log(`  ${seen.size} below AA, none below 3:1 — see ADVISORY in test-tokens.mjs`);
}

await browser.close();

// DS_KEEP_PAGE leaves the assembled page behind, for probing computed styles —
// which is how the panel's invisible text was found.
if (process.env.DS_KEEP_PAGE) {
    console.log(`\n  page kept at ${join(TMP, 'page.html')}`);
} else {
    await rm(TMP, { recursive: true, force: true });
}

if (problems.length) {
    console.error('\n\x1b[31mproblems:\x1b[0m\n' + problems.join('\n'));
    process.exit(1);
}

console.log(`\n  → docs/images/  (${names.length} images, ${(total / 1024 / 1024).toFixed(1)} MB)\n`);
