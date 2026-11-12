/**
 * Behaviour tests.
 *
 * The render tests check that a component emits the right markup. These check
 * that it DOES the right thing — in a real browser, with real Alpine, driven by
 * real presses.
 *
 * That gap is why they exist. A component's markup can be perfect while its
 * handler throws: the modal's focus trap was dead for an afternoon and the
 * "is Tab trapped?" check passed anyway, because `.prevent` had already stopped
 * the default. Every assertion here that can pass vacuously says so and tests
 * the other half too.
 *
 *   npm run test:behaviour
 *
 * Needs a Chromium. It SKIPS rather than fails when there is none, so a clean
 * clone with no browser still gets a green `npm test` and a clear line saying
 * what was not run.
 *
 *   npx playwright install chromium
 */
import { readFile, writeFile, mkdir, rm } from 'node:fs/promises';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { findChromium, NO_BROWSER } from './chromium.mjs';

const run = promisify(execFile);
const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const TMP = join(ROOT, '.behaviour-tmp');

const CHROME = await findChromium();
if (!CHROME) {
    console.log(`\n\x1b[33m!\x1b[0m behaviour tests skipped — ${NO_BROWSER.split('\n')[0]}`);
    console.log('  Every interactive component is unverified in this run.');
    console.log('  npx playwright install chromium\n');
    process.exit(0);
}

let chromium;
try {
    ({ chromium } = await import('playwright-core'));
} catch {
    console.log('\n\x1b[33m!\x1b[0m behaviour tests skipped — run `npm install`.\n');
    process.exit(0);
}

// ------------------------------------------------------------ 1. build a page

await rm(TMP, { recursive: true, force: true });
await mkdir(TMP, { recursive: true });

const render = async (file) => {
    const { stdout } = await run('php', [join(ROOT, 'scripts/render.php'), file], {
        cwd: ROOT,
        maxBuffer: 32 * 1024 * 1024,
    });
    return stdout.replace('<!--ds-canary [...ok]-->', '');
};

let specimens;
let include;
let includeWithNonce;
try {
    specimens = await render(join(ROOT, 'scripts/behaviour-specimens.blade.php'));
    // The include, rendered through the component a consumer writes in their
    // layout rather than read from dist/, so the page gets exactly what a host
    // gets: a script element whose src is the package's route.
    await writeFile(join(TMP, 'include.blade.php'), '<x-ds::scripts />');
    include = await render(join(TMP, 'include.blade.php'));
    await writeFile(join(TMP, 'include-nonce.blade.php'), '<x-ds::scripts nonce="t3st" />');
    includeWithNonce = await render(join(TMP, 'include-nonce.blade.php'));
} catch (err) {
    console.error('\nrender failed — run `composer install`?\n');
    console.error(String(err.stderr || err).slice(0, 800));
    process.exit(1);
}

await writeFile(join(TMP, 'specimens.html'), specimens);

/*
  The overlays whose x-ds-anchor is an enhancement, for a page with Alpine and
  NO include: each must still open, where its absolute classes put it, and
  say nothing in the console. Rendered here so its classes are compiled into
  the page's CSS with everything else.
*/
await writeFile(join(TMP, 'fallback.blade.php'), `<div class="p-8">
<div class="mt-6">
    <x-ds::dropdown id="fb-dropdown">
        <x-slot:trigger><x-ds::button id="fb-dropdown-trigger" size="sm">Actions</x-ds::button></x-slot:trigger>
        <x-ds::dropdown-item id="fb-dropdown-first" href="#a">Edit</x-ds::dropdown-item>
        <x-ds::dropdown-item href="#b">Duplicate</x-ds::dropdown-item>
    </x-ds::dropdown>
</div>
<div class="mt-6 max-w-xs">
    <x-ds::select name="fb-select" id="fb-select" label="Plan" :options="['free' => 'Free', 'pro' => 'Pro']" value="pro" />
</div>
<div class="mt-6 flex justify-center">
    <x-ds::tooltip text="Delete this report permanently">
        <x-ds::button id="fb-tip-trigger" variant="secondary" size="sm" iconOnly aria-label="Delete">
            <x-ds::icon name="trash" size="4" />
        </x-ds::button>
    </x-ds::tooltip>
</div>
<div class="mt-6 max-w-xs">
    <x-ds::date-picker name="fb-date" id="fb-date" label="Due" value="2026-09-10" />
</div>
<div class="mt-6 max-w-xs">
    <x-ds::combobox name="fb-combobox" id="fb-combobox" label="Tags" :options="['a11y' => 'Accessibility', 'billing' => 'Billing']" />
</div>
</div>`);
const fallbackMarkup = await render(join(TMP, 'fallback.blade.php'));
await writeFile(join(TMP, 'fallback.html'), fallbackMarkup);

// One of each focusable component, for the focus-indicator walk.
const focusMarkup = await render(join(ROOT, 'scripts/focus-specimens.blade.php'));
await writeFile(join(TMP, 'focus.html'), focusMarkup);

/*
  The theme switcher and the pre-paint script, for host pages of their own:
  they own <html>, localStorage and the colour scheme, which the main page
  must not have changed under it. Two switchers on the default key, one on
  another, and a probe that only a `dark:` utility colours.
*/
await writeFile(join(TMP, 'theme.blade.php'), `<div class="p-8">
<x-ds::theme-switcher id="ts-a" />
<x-ds::theme-switcher id="ts-b" variant="button" />
<x-ds::theme-switcher id="ts-other" storageKey="other-theme" label="Other" />
<p id="ts-probe" class="text-fg dark:text-accent">probe</p>
</div>`);
const themeMarkup = await render(join(TMP, 'theme.blade.php'));
await writeFile(join(TMP, 'theme.html'), themeMarkup);
await writeFile(join(TMP, 'theme-script.blade.php'), '<x-ds::theme-script />');
const themeScript = await render(join(TMP, 'theme-script.blade.php'));
await writeFile(join(TMP, 'theme-script-nonce.blade.php'), '<x-ds::theme-script nonce="t3st" />');
const themeScriptNonce = await render(join(TMP, 'theme-script-nonce.blade.php'));

/*
  The guards against a destructive press, for host pages WITHOUT the script
  (Alpine and no include) and without Alpine at all: in neither may one press
  act. Each form records a submit rather than leaving the page.
*/
await writeFile(join(TMP, 'guards.blade.php'), `<div class="p-8">
<x-ds::announcement-bar id="g-ab" dismissKey="guards-ab">Remembered.</x-ds::announcement-bar>
<form id="g-form" onsubmit="event.preventDefault(); window.gSent = (window.gSent ?? 0) + 1">
    <x-ds::confirm-button id="g-cb" @click="window.gClicks = (window.gClicks ?? 0) + 1" />
    <x-ds::hold-button id="g-hb" type="submit" label="Hold to delete" />
    <x-ds::confirm-popover id="g-cp" title="Delete?">
        <x-slot:trigger><x-ds::button id="g-cp-trigger" size="sm">Delete</x-ds::button></x-slot:trigger>
    </x-ds::confirm-popover>
</form>
</div>`);
const guardsMarkup = await render(join(TMP, 'guards.blade.php'));
await writeFile(join(TMP, 'guards.html'), guardsMarkup);

// A remembered bar for the pre-paint check, with and without the nonce.
await writeFile(join(TMP, 'announce.blade.php'), '<x-ds::announcement-bar id="pre-ab" dismissKey="pre-ab">Remembered.</x-ds::announcement-bar>');
const announceMarkup = await render(join(TMP, 'announce.blade.php'));
await writeFile(join(TMP, 'announce-nonce.blade.php'), '<x-ds::announcement-bar id="pre-ab" dismissKey="pre-ab" nonce="t3st">Remembered.</x-ds::announcement-bar>');
const announceNonceMarkup = await render(join(TMP, 'announce-nonce.blade.php'));

/*
  Counters for host pages of their own: one below the fold for animateIn, and
  a server counter at two values, the second for Alpine.morph to turn the
  first into, exactly as a Livewire re-render would.
*/
await writeFile(join(TMP, 'roll-in.blade.php'), '<div class="p-8"><div style="height: 2400px"></div><x-ds::counter id="ct-in" :value="742" :animateIn="true" size="xl" /></div>');
const rollInMarkup = await render(join(TMP, 'roll-in.blade.php'));
await writeFile(join(TMP, 'roll-a.blade.php'), '<x-ds::counter id="ct-morph" :value="998" />');
const rollBefore = await render(join(TMP, 'roll-a.blade.php'));
await writeFile(join(TMP, 'roll-b.blade.php'), '<x-ds::counter id="ct-morph" :value="1003" />');
const rollAfter = await render(join(TMP, 'roll-b.blade.php'));
await writeFile(join(TMP, 'roll.html'), rollInMarkup + rollBefore + rollAfter);

await writeFile(
    join(TMP, 'in.css'),
    `@import "tailwindcss";
@import "${join(ROOT, 'dist/tokens.css')}";
@import "${join(ROOT, 'dist/theme.css')}";
@source "${join(TMP, 'specimens.html')}";
@source "${join(TMP, 'fallback.html')}";
@source "${join(TMP, 'focus.html')}";
@source "${join(TMP, 'theme.html')}";
@source "${join(TMP, 'guards.html')}";
@source "${join(TMP, 'roll.html')}";
`,
);

await run('npx', ['@tailwindcss/cli', '-i', join(TMP, 'in.css'), '-o', join(TMP, 'out.css')], {
    cwd: ROOT,
    maxBuffer: 32 * 1024 * 1024,
});

const css = await readFile(join(TMP, 'out.css'), 'utf8');

/*
  Alpine as a file with `defer`, exactly as the CDN snippet in getting-started
  loads it, and ds::scripts above it in the head: host setup (a).

  This used to inline Alpine into the head, where `defer` does nothing — an
  inline classic script runs at once and starts Alpine before the body exists.
  That was harmless while nothing had to register before Alpine started. The
  shared script does, and an inlined Alpine would have fired alpine:init before
  any include below it could listen for it.
*/
await writeFile(join(TMP, 'alpine.js'), await readFile(join(ROOT, 'node_modules/alpinejs/dist/cdn.min.js')));

/*
  Whether the include had run, and registered, by the moment Alpine announced
  it was starting. Written as an inline script straight AFTER the include, so
  its listener is added after the include's: if the include ran first, its
  listener runs first and the plugin's mark is already on Alpine when this one
  looks. An include that ran late, deferred or async, adds its listener after
  this one, and this one sees no mark.
*/
const orderProbe = `<script>
document.addEventListener('alpine:init', () => {
    window.dsRegisteredAtInit = Boolean(window.Alpine && window.Alpine[Symbol.for('shipbytes.blade-ui')]);
});
</script>`;

await writeFile(
    join(TMP, 'page.html'),
    `<!doctype html><html><head><meta charset="utf-8">
<style>${css}</style>
<!-- No hand-written [x-cloak] rule here on purpose. It ships in dist/theme.css,
    which is imported above, so this page proves the SHIPPED one works — an
    injected copy would hide a regression in the thing consumers actually get. -->
${include}
${orderProbe}
<script defer src="alpine.js"></script>
</head><body>${specimens}</body></html>`,
);

await writeFile(
    join(TMP, 'focus-page.html'),
    `<!doctype html><html><head><meta charset="utf-8">
<style>${css}</style>
${include}
<script defer src="alpine.js"></script>
</head><body class="bg-surface">${focusMarkup}</body></html>`,
);

// ------------------------------------------------------------ 2. drive it

const browser = await chromium.launch({ executablePath: CHROME });
const context = await browser.newContext({
    viewport: { width: 1200, height: 1000 },
    permissions: ['clipboard-read', 'clipboard-write'],
});
const page = await context.newPage();

/*
  Every request for http://localhost/… is answered by Laravel, through
  scripts/request.php: the include's src is the package's route, and the
  browser gets what the route returns, headers and all. Serving a copy of
  dist/ here instead would prove the script works and nothing about the URL
  the include actually writes.
*/
const LARAVEL = 'http://localhost';
const fromLaravel = [];

const serveFromLaravel = async (route) => {
    const request = route.request();
    const headers = {};
    const etag = await request.headerValue('if-none-match');
    if (etag) headers['If-None-Match'] = etag;

    const { stdout } = await run('php', [join(ROOT, 'scripts/request.php'), request.url(), JSON.stringify(headers)], {
        cwd: ROOT,
        maxBuffer: 64 * 1024 * 1024,
        env: { ...process.env, DS_PAGES: TMP },
    });
    const response = JSON.parse(stdout);
    fromLaravel.push({ path: new URL(request.url()).pathname, status: response.status, headers: response.headers, cookies: response.cookies });

    await route.fulfill({ status: response.status, headers: response.headers, body: Buffer.from(response.body, 'base64') });
};

await context.route(`${LARAVEL}/**`, serveFromLaravel);

const jsErrors = [];
page.on('pageerror', (e) => jsErrors.push('uncaught: ' + e.message));
page.on('console', (m) => m.type() === 'error' && jsErrors.push('console: ' + m.text()));

await page.goto('file://' + join(TMP, 'page.html'), { waitUntil: 'networkidle' });
await page.waitForTimeout(500);

let passed = 0;
let failed = 0;

const group = (name) => console.log(`\n${name}`);
const check = async (name, fn) => {
    try {
        const result = await fn();
        if (result === true) {
            passed++;
            console.log(`  \x1b[32m✓\x1b[0m ${name}`);
        } else {
            failed++;
            console.log(`  \x1b[31m✗\x1b[0m ${name}  → ${result}`);
        }
    } catch (error) {
        failed++;
        console.log(`  \x1b[31m✗\x1b[0m ${name}  → threw ${error.message.split('\n')[0]}`);
    }
};

const active = () => page.evaluate(() =>
    document.activeElement?.id || document.activeElement?.dataset?.day || document.activeElement?.tagName);

/** Everything the form would actually post. */
const posted = () => page.evaluate(() => {
    const data = new FormData(document.getElementById('form'));
    const out = {};
    for (const [key, value] of data.entries()) {
        (out[key] ||= []).push(typeof value === 'string' ? value : value.name);
    }
    return out;
});

const settle = (ms = 300) => page.waitForTimeout(ms);

// ---------------------------------------------------------------- modal

group('modal');

await check('closed on load', async () =>
    (await page.locator('[role=dialog]').first().isVisible()) === false || 'visible before opening');

await page.click('#open-modal');
await settle(350);

await check('opens', async () => (await page.locator('#modal-cancel').isVisible()) || 'not visible');

await check('focus moves into the panel', async () =>
    (await page.evaluate(() => document.querySelector('[data-ds-modal-panel]').contains(document.activeElement)))
    || `focus is on ${await active()}`);

await check('the page behind is scroll-locked', async () =>
    (await page.evaluate(() => document.body.classList.contains('overflow-hidden'))) || 'body not locked');

await check('the visible title is the accessible name', async () => {
    const result = await page.evaluate(() => {
        const dialog = document.querySelector('[role=dialog]');
        const id = dialog.getAttribute('aria-labelledby');
        return { label: dialog.getAttribute('aria-label'), text: id && document.getElementById(id)?.textContent?.trim() };
    });
    if (result.label) return 'uses aria-label, which drifts from the visible title';
    return result.text === 'Delete report?' || `labelledby resolves to ${JSON.stringify(result.text)}`;
});

await check('Tab is trapped — and focus actually moves', async () => {
    /*
     * Both halves matter. A handler that THROWS still passes the "never left"
     * half, because .prevent already stopped the default — which is exactly how
     * a dead focus trap looked green the first time this ran.
     */
    const seen = new Set();
    for (let i = 0; i < 12; i++) {
        await page.keyboard.press('Tab');
        const inside = await page.evaluate(() =>
            document.querySelector('[data-ds-modal-panel]').contains(document.activeElement));
        if (!inside) return `escaped to ${await active()} after ${i + 1} tabs`;
        seen.add(await active());
    }
    return seen.size > 1 || `focus never moved — it sat on ${[...seen]}, so the handler is throwing`;
});

await check('Escape closes it', async () => {
    await page.keyboard.press('Escape');
    await settle();
    return (await page.locator('#modal-cancel').isVisible()) === false || 'still visible';
});

await check('focus returns to the trigger', async () =>
    (await active()) === 'open-modal' || `focus landed on ${await active()}`);

await check('the scroll lock is released', async () =>
    (await page.evaluate(() => !document.body.classList.contains('overflow-hidden'))) || 'body still locked');

// ---------------------------------------------------------------- drawer

group('drawer');

await page.click('#open-drawer');
await settle(400);

await check('opens and takes focus', async () =>
    (await page.evaluate(() => document.querySelector('[data-ds-drawer-panel]').contains(document.activeElement)))
    || `focus is on ${await active()}`);

await check('Tab is trapped — and focus actually moves', async () => {
    const seen = new Set();
    for (let i = 0; i < 10; i++) {
        await page.keyboard.press('Tab');
        const inside = await page.evaluate(() =>
            document.querySelector('[data-ds-drawer-panel]').contains(document.activeElement));
        if (!inside) return `escaped to ${await active()}`;
        seen.add(await active());
    }
    return seen.size > 1 || 'focus never moved — the handler is throwing';
});

await check('Escape closes and restores focus', async () => {
    await page.keyboard.press('Escape');
    await settle(350);
    return (await active()) === 'open-drawer' || `focus on ${await active()}`;
});

/*
 * The three dismiss paths, each pressed for real.
 *
 * These would not have caught the bug that prompted them — that one was in the
 * CONSUMER, who passed a comparison as `open` so that `{{ $open }} = false`
 * compiled to an assignment to an expression. But all three dismiss paths write
 * to `open` through the same mechanism, so any change to how that is emitted
 * breaks all three at once and in silence: the dialog opens, and nothing closes
 * it. Cheap insurance on the half of the contract we own.
 */
const drawerOpen = () => page.locator('#drawer-apply').isVisible();

await check('the close button closes it', async () => {
    await page.click('#open-drawer');
    await settle(400);
    if (!(await drawerOpen())) return 'did not open';

    await page.locator('[data-ds-drawer-panel] button[aria-label=Close]').click();
    await settle(400);
    return (await drawerOpen()) === false || 'the ✕ did nothing';
});

await check('the backdrop closes it', async () => {
    await page.click('#open-drawer');
    await settle(400);
    if (!(await drawerOpen())) return 'did not open';

    // The backdrop is the sibling of the panel, so a corner click cannot land on
    // the panel by accident whichever side the drawer is on.
    await page.mouse.click(8, 8);
    await settle(400);
    return (await drawerOpen()) === false || 'clicking the backdrop did nothing';
});

// ---------------------------------------------------------------- dropdown

group('dropdown');

const trigger = page.locator('#menu-trigger');

await check('the trigger advertises the menu', async () =>
    (await trigger.getAttribute('aria-haspopup')) === 'menu' || 'no aria-haspopup');

await check('aria-expanded is false when closed', async () =>
    (await trigger.getAttribute('aria-expanded')) === 'false'
    || `got ${await trigger.getAttribute('aria-expanded')}`);

await trigger.click();
await settle();

await check('aria-expanded flips to true', async () =>
    (await trigger.getAttribute('aria-expanded')) === 'true' || 'still false');

await check('focus lands on the first item', async () =>
    (await active()) === 'item-edit' || `focus on ${await active()}`);

await check('ArrowDown moves to the next item', async () => {
    await page.keyboard.press('ArrowDown');
    return (await active()) === 'item-dup' || `focus on ${await active()}`;
});

await check('ArrowDown SKIPS the disabled item', async () => {
    await page.keyboard.press('ArrowDown');
    const at = await active();
    return at === 'item-delete' || (at === 'item-archive' ? 'landed on the disabled item' : `focus on ${at}`);
});

await check('Escape closes and returns focus to the trigger', async () => {
    await page.keyboard.press('Escape');
    await settle();
    return (await active()) === 'menu-trigger' || `focus on ${await active()}`;
});

// ---------------------------------------------------------------- tooltip

group('tooltip');

// Scoped to this specimen's tip: the toolbar further down has tips of its own.
const tip = () => page.locator('[data-ds-tooltip]:has(#tip-trigger) [role=tooltip]');

await check('hidden until asked for', async () =>
    (await tip().isVisible()) === false || 'visible on load');

await check('describes its trigger without stealing its name', async () => {
    const result = await page.evaluate(() => {
        const button = document.querySelector('#tip-trigger');
        const id = button.closest('[data-ds-tooltip]')?.querySelector('[aria-describedby]')
            ?.getAttribute('aria-describedby');
        return { text: id && document.getElementById(id)?.textContent?.trim(), label: button.getAttribute('aria-label') };
    });
    if (result.text !== 'Delete this report permanently') return `describedby resolves to ${JSON.stringify(result.text)}`;
    return result.label === 'Delete' || 'the trigger lost its own accessible name';
});

await check('appears on FOCUS, not only on hover', async () => {
    await page.focus('#tip-trigger');
    await settle(200);
    return (await tip().isVisible()) || 'invisible to a keyboard';
});

await check('Escape dismisses it (WCAG 1.4.13)', async () => {
    await page.keyboard.press('Escape');
    await settle(200);
    return (await tip().isVisible()) === false || 'still visible';
});

// ---------------------------------------------------------------- select

group('select');

await check('the trigger shows the selected label', async () =>
    (await page.locator('#plan').innerText()).trim() === 'Pro'
    || `shows ${(await page.locator('#plan').innerText()).trim()}`);

await check('the hidden input carries the value', async () =>
    (await posted()).plan?.[0] === 'pro' || `posted ${JSON.stringify((await posted()).plan)}`);

await check('ArrowDown opens it', async () => {
    await page.locator('#plan').focus();
    await page.keyboard.press('ArrowDown');
    await settle();
    return (await page.locator('#plan-listbox, [role=listbox]').first().isVisible()) || 'not open';
});

await check('focus lands on the SELECTED option, not the first', async () => {
    const value = await page.evaluate(() => document.activeElement?.dataset?.value);
    return value === 'pro' || `focus on option ${value}`;
});

await check('Enter chooses, and the form posts the new value', async () => {
    await page.keyboard.press('ArrowDown');
    await page.keyboard.press('Enter');
    await settle();
    return (await posted()).plan?.[0] === 'team' || `posted ${JSON.stringify((await posted()).plan)}`;
});

// ---------------------------------------------------------------- combobox

group('combobox');

await check('posts the initial selection', async () =>
    JSON.stringify((await posted())['tags[]']) === '["a11y"]'
    || `posted ${JSON.stringify((await posted())['tags[]'])}`);

await check('typing filters the list', async () => {
    await page.click('#tags');
    await page.fill('#tags', 'comp');
    await settle(200);
    const shown = await page.locator('[role=option]:visible').allInnerTexts();
    return (shown.length === 1 && shown[0].includes('Compliance')) || `showing ${JSON.stringify(shown)}`;
});

await check('choosing adds a chip AND a posted value', async () => {
    await page.click('[role=option]:visible');
    await settle(200);
    return JSON.stringify((await posted())['tags[]']) === '["a11y","compliance"]'
        || `posted ${JSON.stringify((await posted())['tags[]'])}`;
});

await check('backspace on an empty query removes the last chip', async () => {
    await page.click('#tags');
    await page.keyboard.press('Backspace');
    await settle(200);
    return JSON.stringify((await posted())['tags[]']) === '["a11y"]'
        || `posted ${JSON.stringify((await posted())['tags[]'])}`;
});

await check('no matches says so rather than rendering an empty box', async () => {
    await page.fill('#tags', 'zzzz');
    await settle(200);
    const text = await page.locator('#tags').evaluate((el) =>
        el.closest('[x-data]').querySelector('[role=listbox]').innerText);
    return text.includes('No matches') || `the listbox reads: ${text.trim()}`;
});

await page.fill('#tags', '');
await page.keyboard.press('Escape');

// ---------------------------------------------------------------- switch

group('switch');

await check('announces as a switch, not a checkbox', async () =>
    (await page.locator('#notify').getAttribute('role')) === 'switch' || 'no role=switch');

await check('posts 0 when off — the reason the hidden input exists', async () =>
    JSON.stringify((await posted()).notify) === '["0"]'
    || `posted ${JSON.stringify((await posted()).notify)}`);

await check('posts 1 when on', async () => {
    await page.locator('#notify').check();
    await settle(100);
    return JSON.stringify((await posted()).notify) === '["0","1"]'
        || `posted ${JSON.stringify((await posted()).notify)}`;
});

// ---------------------------------------------------------------- checkbox, hidden label

group('checkbox, hidden label');

await check('the hidden label is still the accessible name', async () => {
    const name = await page.locator('#pick').evaluate((el) => el.labels?.[0]?.textContent.trim());
    return name === 'Select Invoice 42' || `named "${name}"`;
});

await check('and it is not visible', async () => {
    const box = await page.locator('label[for=pick] span').boundingBox();
    return (box && box.width <= 1 && box.height <= 1) || `the label text renders at ${JSON.stringify(box)}`;
});

await check('a press BESIDE the box toggles it — the 44px target', async () => {
    const box = await page.locator('#pick').boundingBox();
    // 10px left of the box: outside the 18px control, inside the 44px square.
    await page.mouse.click(box.x - 10, box.y + box.height / 2);
    await settle(100);
    return (await page.locator('#pick').isChecked()) || 'unchanged — the margin is not a target';
});

await check('a press ON the box still reaches the input itself', async () => {
    const box = await page.locator('#pick').boundingBox();
    const hit = await page.evaluate(([x, y]) => document.elementFromPoint(x, y)?.id, [box.x + box.width / 2, box.y + box.height / 2]);
    await page.locator('#pick').click();
    await settle(100);
    return (hit === 'pick' && !(await page.locator('#pick').isChecked()))
        || `the topmost element at the box is #${hit}, so the box's hover state never fires`;
});

await check('the target is 44px, not the whole cell', async () => {
    const t = await page.locator('label[for=pick]').boundingBox();
    return (Math.round(t.width) === 44 && Math.round(t.height) === 44) || `${t.width}x${t.height}`;
});

// ---------------------------------------------------------------- accordion

group('accordion');

await check('the open section is expanded before anything is pressed', async () =>
    (await page.locator('#ds-acc-billing-trigger').getAttribute('aria-expanded')) === 'true' || 'not expanded');

await check('a collapsed panel is NOT reachable by keyboard', async () => {
    // visibility:hidden, not overflow:hidden — the difference between a
    // collapsed section and a collapsed section whose links are still tabbable.
    const visible = await page.evaluate(() =>
        getComputedStyle(document.getElementById('in-security').closest('[role=region]')).visibility);
    return visible === 'hidden' || `the collapsed panel is ${visible} — its links are still tabbable`;
});

await check('opening one closes the other', async () => {
    await page.click('#ds-acc-security-trigger');
    await settle(350);
    const security = await page.locator('#ds-acc-security-trigger').getAttribute('aria-expanded');
    const billing = await page.locator('#ds-acc-billing-trigger').getAttribute('aria-expanded');
    return (security === 'true' && billing === 'false') || `security=${security} billing=${billing}`;
});

await check('the newly opened panel IS reachable', async () =>
    (await page.evaluate(() =>
        getComputedStyle(document.getElementById('in-security').closest('[role=region]')).visibility)) === 'visible'
    || 'still hidden');

// ---------------------------------------------------------------- date picker

group('date picker');

await check('posts start and end as two separate fields', async () => {
    const form = await posted();
    return (form.period_start?.[0] === '2026-09-10' && form.period_end?.[0] === '')
        || `posted ${JSON.stringify(form)}`;
});

await page.click('#period');
await settle();

await check('the grid opens on the month of the value', async () =>
    (await page.locator('[x-data]:has(> #period) [aria-live=polite]').innerText()).includes('September 2026')
    || `shows ${await page.locator('[x-data]:has(> #period) [aria-live=polite]').innerText()}`);

await check('picking a later day completes the range', async () => {
    await page.click('[data-day="2026-09-17"]');
    await settle();
    const form = await posted();
    return (form.period_start?.[0] === '2026-09-10' && form.period_end?.[0] === '2026-09-17')
        || `posted ${JSON.stringify(form)}`;
});

await page.click('#period');
await settle();

await check('the days between are marked', async () =>
    (await page.locator('[data-day="2026-09-14"]').getAttribute('class')).includes('accent-wash')
    || 'the mid-range day is unmarked');

await check('arrow keys move a day — and land on the right one', async () => {
    /*
     * The focus happens in $nextTick, so this HAS to wait. Reading
     * activeElement synchronously reports the previous keypress's result, which
     * looks exactly like an off-by-one bug in the component.
     */
    await page.locator('[data-day="2026-09-17"]').focus();
    await page.keyboard.press('ArrowRight');
    await settle(150);
    return (await active()) === '2026-09-18' || `focus on ${await active()}`;
});

await check('ArrowDown moves a week', async () => {
    await page.keyboard.press('ArrowDown');
    await settle(150);
    return (await active()) === '2026-09-25' || `focus on ${await active()}`;
});

await check('crossing a month boundary follows the grid', async () => {
    await page.keyboard.press('ArrowDown');
    await settle(250);
    return (await active()) === '2026-10-02' || `focus on ${await active()}`;
});

await check('Clear empties both fields', async () => {
    await page.click('text=Clear');
    await settle();
    const form = await posted();
    return (form.period_start?.[0] === '' && form.period_end?.[0] === '') || `posted ${JSON.stringify(form)}`;
});

await page.keyboard.press('Escape');

// ---------------------------------------------------------------- file upload

group('file upload');

await check('accepts files and lists them', async () => {
    await page.setInputFiles('#files', [
        join(ROOT, 'docs/images/badge.png'),
        join(ROOT, 'docs/images/icon.png'),
    ]);
    await settle();
    const rows = await page.locator('ul[aria-live] li').allInnerTexts();
    return rows.length === 2 || `listed ${JSON.stringify(rows)}`;
});

await check('an image gets a real local thumbnail', async () =>
    (await page.locator('ul[aria-live] img').first().getAttribute('src')).startsWith('blob:')
    || 'no preview was generated');

await check('removing takes it out of the INPUT, not just the list', async () => {
    // A FileList is read-only. Without rebuilding it through a DataTransfer the
    // row disappears and the file is still submitted, which is worse than
    // having no remove control at all.
    await page.locator('ul[aria-live] button').first().click();
    await settle();
    return JSON.stringify((await posted())['files[]']) === '["icon.png"]'
        || `the form still carries ${JSON.stringify((await posted())['files[]'])}`;
});

// ---------------------------------------------------------------- tabs

/*
 * The host wiring from specs/tabs.md, driven rather than described.
 *
 * The spec used to document `::active="tab === 'overview'"`. That binds an
 * `active` ATTRIBUTE, which nothing reads — the component computes its classes
 * from the PHP prop at render time — so the panel switched and the tab never
 * changed appearance. No error, no warning, a tab row with no selected tab.
 *
 * The obvious repair is worse, because it half works: the STRING form of
 * `:class` only ADDS classes, so the server-rendered `border-transparent`
 * stays on the element and wins, and the element ends up carrying both. The
 * object form is what removes a class it did not add. That is the distinction
 * these checks exist to hold in place, so the spec cannot drift back.
 */
group('tabs (the host wiring the spec documents)');

const tabState = async (id) => page.evaluate((tabId) => {
    const tab = document.getElementById(tabId);
    const panel = document.getElementById(tab.getAttribute('aria-controls'));
    return {
        selected: tab.getAttribute('aria-selected'),
        tabindex: tab.getAttribute('tabindex'),
        classes: tab.className,
        panelHidden: panel.hasAttribute('hidden'),
    };
}, id);

await check('renders the right tab selected BEFORE Alpine could have run', async () => {
    // The PHP `:active` half. Bound-only wiring flashes the wrong tab on load
    // and is simply wrong in anything that never runs the JS.
    const overview = await tabState('tab-overview');
    return (overview.selected === 'true' && !overview.panelHidden)
        || `overview came up selected=${overview.selected}, panel hidden=${overview.panelHidden}`;
});

await check('the unselected tab is out of the tab order', async () => {
    const activity = await tabState('tab-activity');
    return (activity.tabindex === '-1' && activity.panelHidden)
        || `activity has tabindex=${activity.tabindex}, panel hidden=${activity.panelHidden}`;
});

await check('clicking a tab actually CHANGES ITS APPEARANCE', async () => {
    await page.click('#tab-activity');
    await settle();

    const activity = await tabState('tab-activity');

    if (!activity.classes.includes('border-fg')) {
        return `still not marked selected — classes are "${activity.classes}"`;
    }
    // The half the string form of :class fails. Both classes present means the
    // underline is being drawn by whichever rule Tailwind emitted last.
    if (activity.classes.includes('border-transparent')) {
        return 'carries border-fg AND border-transparent — the string form of :class only adds';
    }
    return activity.selected === 'true' || `aria-selected is ${activity.selected}`;
});

await check('the tab it replaced gives up its selection', async () => {
    const overview = await tabState('tab-overview');
    return (overview.selected === 'false'
        && overview.tabindex === '-1'
        && overview.classes.includes('border-transparent')
        && !overview.classes.includes('border-fg'))
        || `overview is still selected=${overview.selected}, classes "${overview.classes}"`;
});

await check('the panels swapped with it', async () => {
    const [overview, activity] = [await tabState('tab-overview'), await tabState('tab-activity')];
    return (overview.panelHidden && !activity.panelHidden)
        || `overview hidden=${overview.panelHidden}, activity hidden=${activity.panelHidden}`;
});

await check('the arrow keys the spec says the host owns actually move it', async () => {
    await page.keyboard.press('ArrowRight');
    await settle();

    const overview = await tabState('tab-overview');
    if (overview.selected !== 'true') return `ArrowRight left activity selected`;

    // Selection without focus is the half that is easy to miss: the reader
    // presses again and moves from the tab they were on two presses ago.
    return (await active()) === 'tab-overview' || `selection moved but focus is on ${await active()}`;
});

// ---------------------------------------------------------------- ds::scripts

group('ds::scripts (served by the package\'s route)');

const scriptSrc = include.match(/src="([^"]+)"/)?.[1] ?? '';
const scriptResponse = () => fromLaravel.find((r) => r.path === new URL(scriptSrc, LARAVEL).pathname);

await check('the include is a src, at a URL carrying a content hash', async () =>
    /\/blade-ui\/blade-ui\.[0-9a-f]{16}\.js$/.test(scriptSrc) || `src is ${JSON.stringify(scriptSrc)}`);

await check('a classic script: no defer, no async, no type=module', async () =>
    !/\b(defer|async)\b|type="module"/.test(include) || include);

await check('the browser fetched it from the route, as JavaScript, cacheable for a year', async () => {
    const r = scriptResponse();
    if (!r) return `no request reached the route: ${JSON.stringify(fromLaravel.map((x) => x.path))}`;
    if (r.status !== 200) return `status ${r.status}`;
    if (!r.headers['Content-Type']?.startsWith('text/javascript')) return `Content-Type ${r.headers['Content-Type']}`;
    if (!/immutable/.test(r.headers['Cache-Control']) || !/max-age=31536000/.test(r.headers['Cache-Control'])) return `Cache-Control ${r.headers['Cache-Control']}`;
    if (!r.headers.ETag) return 'no ETag';
    return r.cookies === 0 || `the route set ${r.cookies} cookie(s)`;
});

await check('it had run, and registered, by the time Alpine announced its start', async () =>
    (await page.evaluate(() => window.dsRegisteredAtInit)) === true
    || `at alpine:init the plugin had${(await page.evaluate(() => window.dsRegisteredAtInit)) === false ? ' not' : ' never been looked for, and had not'} registered`);

// ---------------------------------------------------------------- dsClipboard

/*
 * The one registration the shared script ships with, driven on the main page,
 * which loads Alpine the CDN way: host setup (a). Setups (b) and (c) get pages
 * of their own at the bottom.
 */
group('dsClipboard (the shared script, host setup a: Alpine from a CDN with defer)');

const clipState = () => page.locator('#clip-state').innerText();
const clipboardText = () => page.evaluate(() => navigator.clipboard.readText());

await check('registered before Alpine initialised the element', async () => {
    // The PHP-rendered "idle" would read the same with the script missing, so
    // ask Alpine for the scope rather than reading the text.
    const kind = await page.evaluate(() => typeof Alpine.$data(document.getElementById('clip')).copy);
    return kind === 'function' || `the scope has no copy() — got ${kind}`;
});

await check('copy() writes the default text to the clipboard', async () => {
    await page.click('#clip-copy');
    await settle(100);
    const text = await clipboardText();
    return text === 'sk_live_42' || `the clipboard holds ${JSON.stringify(text)}`;
});

await check('and `copied` goes up', async () => (await clipState()) === 'copied' || `reads ${await clipState()}`);

await check('copy(value) copies the value it was given', async () => {
    await page.waitForTimeout(1000);
    await page.click('#clip-other');
    await settle(100);
    const text = await clipboardText();
    return text === 'other value' || `the clipboard holds ${JSON.stringify(text)}`;
});

await check('a second copy restarts the 1.5 s rather than inheriting what was left', async () => {
    // 1.1 s after the second copy and 2.1 s after the first: up if restarted,
    // already down if the first timer was still the one running.
    await page.waitForTimeout(1000);
    return (await clipState()) === 'copied' || 'fell back on the FIRST copy\'s timer';
});

await check('`copied` falls back on its own', async () => {
    await page.waitForTimeout(700);
    return (await clipState()) === 'idle' || `still reads ${await clipState()} 1.8 s after the last copy`;
});

await check('without the async clipboard API it falls back, copies, and gives focus back', async () => {
    // A plain-http page has no navigator.clipboard at all. The copy event is
    // what execCommand('copy') fires, and its target holds the selection.
    await page.evaluate(() => {
        window.dsCopyEvent = null;
        document.addEventListener('copy', (event) => {
            const area = event.target;
            window.dsCopyEvent = area.value?.slice(area.selectionStart, area.selectionEnd) ?? '(not a field)';
        }, { once: true });
        Object.defineProperty(navigator, 'clipboard', { value: undefined, configurable: true });
    });

    await page.click('#clip-copy');
    await settle(100);

    const result = await page.evaluate(() => ({
        copied: window.dsCopyEvent,
        leftover: document.querySelectorAll('textarea[readonly]').length,
        focus: document.activeElement?.id,
    }));
    await page.evaluate(() => delete navigator.clipboard);

    if (result.copied !== 'sk_live_42') return `the fallback copied ${JSON.stringify(result.copied)}`;
    if ((await clipState()) !== 'copied') return 'copied, but `copied` never went up';
    if (result.leftover) return 'left its textarea in the page';
    return result.focus === 'clip-copy' || `focus ended on ${result.focus}, not the button that was pressed`;
});

// ---------------------------------------------------------------- copy button

group('copy button (on dsClipboard)');

const copyLook = (id) => page.evaluate((buttonId) => {
    const button = document.getElementById(buttonId);
    const shown = (el) => el && getComputedStyle(el).visibility !== 'hidden';
    const [copyIcon, checkIcon] = button.querySelectorAll('[aria-hidden] > span');
    const faces = [...button.querySelectorAll('.grid:not([aria-hidden]) > span')];
    return {
        width: button.getBoundingClientRect().width,
        label: faces.find(shown)?.textContent.trim() ?? null,
        icon: shown(checkIcon) ? 'check' : shown(copyIcon) ? 'clipboard' : 'none',
        live: button.parentElement.querySelector('[aria-live]').textContent,
        name: button.getAttribute('aria-label'),
    };
}, id);

await check('copies its value; label and icon swap, and the change is announced', async () => {
    const before = await copyLook('copy-value');
    await page.click('#copy-value');
    await settle(100);
    const after = await copyLook('copy-value');
    if ((await clipboardText()) !== 'sk_live_42') return `the clipboard holds ${JSON.stringify(await clipboardText())}`;
    if (before.label !== 'Copy' || after.label !== 'Copied') return `label ${before.label} → ${after.label}`;
    if (before.icon !== 'clipboard' || after.icon !== 'check') return `icon ${before.icon} → ${after.icon}`;
    if (before.live !== '' || after.live !== 'Copied') return `live region ${JSON.stringify(before.live)} → ${JSON.stringify(after.live)}`;
    return before.width === after.width || `the button jumped from ${before.width}px to ${after.width}px`;
});

await check('and goes back on its own, with the live region emptied', async () => {
    await page.waitForTimeout(1700);
    const look = await copyLook('copy-value');
    return (look.label === 'Copy' && look.icon === 'clipboard' && look.live === '') || JSON.stringify(look);
});

await check('target by id copies the element\'s text, trimmed', async () => {
    await page.click('#copy-text');
    await settle(100);
    return (await clipboardText()) === 'sk_live_77' || `the clipboard holds ${JSON.stringify(await clipboardText())}`;
});

await check('target by selector copies a field\'s value as it is now, not as rendered', async () => {
    await page.fill('#key-field', 'edited since');
    await page.click('#copy-field');
    await settle(100);
    return (await clipboardText()) === 'edited since' || `the clipboard holds ${JSON.stringify(await clipboardText())}`;
});

await check('icon-only keeps its name; the live region carries the news', async () => {
    const look = await copyLook('copy-field');
    return (look.name === 'Copy field' && look.icon === 'check' && look.live === 'Copied') || JSON.stringify(look);
});

await check('a target not on the page: no false "Copied", and copy-failed reaches the tag and the window', async () => {
    await page.evaluate(() => { window.copyFailedHere = 0; window.copyFailedWindow = 0; });
    await page.click('#copy-missing');
    await settle(100);
    const look = await copyLook('copy-missing');
    const heard = await page.evaluate(() => [window.copyFailedHere, window.copyFailedWindow]);
    if (look.label !== 'Copy' || look.live !== '') return `it claims success: ${JSON.stringify(look)}`;
    return JSON.stringify(heard) === '[1,1]' || `heard on the tag ${heard[0]}, on the window ${heard[1]} times`;
});

await check('a clipboard that refuses: no false "Copied", and copy-failed is dispatched', async () => {
    await page.evaluate(() => {
        window.copyFailedWindow = 0;
        window.dsRealWrite = navigator.clipboard.writeText;
        navigator.clipboard.writeText = () => Promise.reject(new DOMException('denied', 'NotAllowedError'));
        window.dsRealExec = document.execCommand;
        document.execCommand = () => false;
    });
    await page.click('#copy-value');
    await settle(100);
    const look = await copyLook('copy-value');
    const heard = await page.evaluate(() => window.copyFailedWindow);
    await page.evaluate(() => {
        navigator.clipboard.writeText = window.dsRealWrite;
        document.execCommand = window.dsRealExec;
    });
    if (look.label !== 'Copy' || look.icon !== 'clipboard') return `it claims success: ${JSON.stringify(look)}`;
    return heard === 1 || `copy-failed reached the window ${heard} times`;
});

// ---------------------------------------------------------------- progress

/*
 * The live wiring from docs/components/progress.md, driven rather than
 * described. The component computes its width from a number in PHP, so the
 * claim worth proving is that ONE binding on the tag moves the fill: a host
 * that reached for `::value` would bind an attribute nothing reads, the same
 * silence as `::tone` (see CLAUDE.md).
 */
group('progress (driven live by the host)');

const progressState = (id) => page.evaluate((barId) => {
    const bar = document.getElementById(barId);
    const track = bar.querySelector('.bg-fg\\/10');
    const fill = track.querySelector('.h-full.shrink-0');
    return {
        now: bar.getAttribute('aria-valuenow'),
        ratio: Math.round((fill.getBoundingClientRect().width / track.getBoundingClientRect().width) * 100),
        text: bar.textContent.replace(/\s+/g, ' ').trim(),
        transition: getComputedStyle(fill).transitionDuration,
    };
}, id);

await check('renders its value from PHP before anything is bound', async () => {
    const s = await progressState('live-progress');
    return (s.now === '10' && s.ratio === 10) || `aria-valuenow ${s.now}, fill at ${s.ratio}%`;
});

await check('a bound --ds-progress and aria-valuenow move the bar', async () => {
    await page.click('#progress-advance');
    // The width transitions over duration-slide-enter (300ms); read after it.
    await settle(500);
    const s = await progressState('live-progress');
    if (s.now !== '75') return `aria-valuenow is ${s.now}`;
    if (s.ratio !== 75) return `the fill sits at ${s.ratio}% of the track`;
    return s.text.includes('75%') || `the slot still reads "${s.text}"`;
});

await check('the fill animates only when motion is allowed', async () => {
    const moving = (await progressState('live-progress')).transition;
    await page.emulateMedia({ reducedMotion: 'reduce' });
    const still = (await progressState('live-progress')).transition;
    await page.emulateMedia({ reducedMotion: 'no-preference' });
    return (moving !== '0s' && still === '0s') || `transition is ${moving} with motion, ${still} without`;
});

await check('an indeterminate bar sweeps, and under reduced motion stands still in stripes', async () => {
    const look = () => page.evaluate(() => {
        const track = document.querySelector('#busy-progress .bg-fg\\/10');
        const [stripes, sweep] = track.children;
        return {
            stripes: getComputedStyle(stripes).display,
            sweep: getComputedStyle(sweep).display,
            animation: getComputedStyle(sweep).animationName,
            now: document.getElementById('busy-progress').hasAttribute('aria-valuenow'),
        };
    });

    const moving = await look();
    await page.emulateMedia({ reducedMotion: 'reduce' });
    const still = await look();
    await page.emulateMedia({ reducedMotion: 'no-preference' });

    if (moving.now) return 'an indeterminate bar carries aria-valuenow';
    if (moving.animation !== 'ds-progress-indeterminate' || moving.stripes !== 'none') {
        return `with motion: sweep animation "${moving.animation}", stripes display ${moving.stripes}`;
    }
    return (still.sweep === 'none' && still.stripes !== 'none')
        || `reduced motion: sweep display ${still.sweep}, stripes display ${still.stripes}`;
});

// ---------------------------------------------------------------- otp input

/*
 * dsOtp, from the shared script. Everything is checked against what the form
 * would POST, not against what the boxes show: one field, named by the
 * component, carrying the joined code. The boxes carry no name of their own.
 */
group('otp input (dsOtp)');

const otpBox = (n) => `#otp-${n}`;
const otpPosted = async () => (await posted()).otp;

await check('renders the value from PHP into the boxes and the posted field', async () => {
    const boxes = await page.evaluate(() =>
        [...document.querySelectorAll('[id^="otp-"][data-ds-otp-box]')].map((b) => b.value));
    if (JSON.stringify(boxes) !== '["1","2","","","",""]') return `boxes hold ${JSON.stringify(boxes)}`;
    return JSON.stringify(await otpPosted()) === '["12"]' || `posted ${JSON.stringify(await otpPosted())}`;
});

await check('the boxes post nothing themselves: one field, one value', async () => {
    const named = await page.evaluate(() =>
        [...document.querySelectorAll('[data-ds-otp-box]')].filter((b) => b.name).length);
    return named === 0 || `${named} boxes carry a name and would post alongside the code`;
});

await check('named for the job: a group labelled by the label, each box "Digit n of 6"', async () => {
    const a11y = await page.evaluate(() => {
        const group = document.getElementById('otp-1').closest('[role=group]');
        return {
            group: document.getElementById(group?.getAttribute('aria-labelledby'))?.textContent.trim(),
            second: document.getElementById('otp-2').getAttribute('aria-label'),
            first: document.getElementById('otp-1').getAttribute('autocomplete'),
            rest: document.getElementById('otp-2').getAttribute('autocomplete'),
            mode: document.getElementById('otp-1').getAttribute('inputmode'),
        };
    });
    if (a11y.group !== 'Verification code') return `the group is named ${JSON.stringify(a11y.group)}`;
    if (a11y.second !== 'Digit 2 of 6') return `box 2 is named ${JSON.stringify(a11y.second)}`;
    if (a11y.first !== 'one-time-code' || a11y.rest !== 'off') return `autocomplete is ${a11y.first} / ${a11y.rest}`;
    return a11y.mode === 'numeric' || `inputmode is ${a11y.mode}`;
});

await check('typing a digit fills the box and moves on', async () => {
    await page.focus(otpBox(3));
    await page.keyboard.type('3');
    if ((await active()) !== 'otp-4') return `focus is on ${await active()}`;
    return JSON.stringify(await otpPosted()) === '["123"]' || `posted ${JSON.stringify(await otpPosted())}`;
});

await check('a character the field does not take is ignored, and focus stays', async () => {
    await page.keyboard.type('x');
    const value = await page.locator(otpBox(4)).inputValue();
    if (value !== '') return `box 4 holds ${JSON.stringify(value)}`;
    return (await active()) === 'otp-4' || `focus moved to ${await active()}`;
});

await check('typing into a filled box replaces its digit', async () => {
    await page.focus(otpBox(1));
    await page.keyboard.type('9');
    const value = await page.locator(otpBox(1)).inputValue();
    if (value !== '9') return `box 1 holds ${JSON.stringify(value)}`;
    return JSON.stringify(await otpPosted()) === '["923"]' || `posted ${JSON.stringify(await otpPosted())}`;
});

await check('…and does so with the caret after the digit, not only with it selected', async () => {
    await page.focus(otpBox(2));
    await page.evaluate(() => document.getElementById('otp-2').setSelectionRange(1, 1));
    await page.keyboard.type('5');
    const value = await page.locator(otpBox(2)).inputValue();
    return (value === '5' && JSON.stringify(await otpPosted()) === '["953"]')
        || `box 2 holds ${JSON.stringify(value)}, posted ${JSON.stringify(await otpPosted())}`;
});

await check('Backspace on an empty box steps back and clears the one before', async () => {
    await page.focus(otpBox(4));
    await page.keyboard.press('Backspace');
    if ((await active()) !== 'otp-3') return `focus is on ${await active()}`;
    const value = await page.locator(otpBox(3)).inputValue();
    return (value === '' && JSON.stringify(await otpPosted()) === '["95"]')
        || `box 3 holds ${JSON.stringify(value)}, posted ${JSON.stringify(await otpPosted())}`;
});

await check('Left and Right move between boxes', async () => {
    await page.keyboard.press('ArrowLeft');
    const left = await active();
    await page.keyboard.press('ArrowRight');
    const right = await active();
    return (left === 'otp-2' && right === 'otp-3') || `Left went to ${left}, Right to ${right}`;
});

const paste = (selector, text) => page.evaluate(([sel, value]) => {
    const target = document.querySelector(sel);
    target.focus();
    const data = new DataTransfer();
    data.setData('text/plain', value);
    target.dispatchEvent(new ClipboardEvent('paste', { clipboardData: data, bubbles: true, cancelable: true }));
}, [selector, text]);

await check('a whole code pasted into any box fills from the first, separators dropped', async () => {
    await paste(otpBox(3), '123 456');
    if (JSON.stringify(await otpPosted()) !== '["123456"]') return `posted ${JSON.stringify(await otpPosted())}`;
    return (await active()) === 'otp-6' || `focus is on ${await active()}, not the last filled box`;
});

await check('filling every box dispatches `complete`, heard on the component tag', async () => {
    const heard = await page.evaluate(() => window.otpHeard);
    return JSON.stringify(heard) === '["123456"]' || `heard ${JSON.stringify(heard)}`;
});

await check('x-model on the tag follows the code', async () =>
    (await page.locator('#otp-mirror').innerText()) === '123456'
    || `the host's state reads ${await page.locator('#otp-mirror').innerText()}`);

await check('a shorter paste fills from the box it lands in, and a new whole code completes again', async () => {
    await paste(otpBox(5), '78');
    if (JSON.stringify(await otpPosted()) !== '["123478"]') return `posted ${JSON.stringify(await otpPosted())}`;
    const heard = await page.evaluate(() => window.otpHeard);
    return JSON.stringify(heard) === '["123456","123478"]' || `heard ${JSON.stringify(heard)}`;
});

await check('the browser\'s one-time-code autofill, which writes the whole code into one box, is spread out', async () => {
    await page.evaluate(() => {
        const first = document.getElementById('otp-1');
        first.focus();
        first.value = '654321';
        first.dispatchEvent(new InputEvent('input', { inputType: 'insertReplacementText', bubbles: true }));
    });
    return JSON.stringify(await otpPosted()) === '["654321"]' || `posted ${JSON.stringify(await otpPosted())}`;
});

const otpBoxes = () => page.evaluate(() =>
    [...document.querySelectorAll('[id^="otp-"][data-ds-otp-box]')].map((b) => b.value).join(''));

await check('a code the HOST sets through x-model is written into the boxes and the posted field', async () => {
    const heardBefore = JSON.stringify(await page.evaluate(() => window.otpHeard));
    await page.click('#otp-host-set');
    await settle(100);
    if ((await otpBoxes()) !== '246810') return `the boxes hold ${JSON.stringify(await otpBoxes())}`;
    if (JSON.stringify(await otpPosted()) !== '["246810"]') return `posted ${JSON.stringify(await otpPosted())}`;
    // The reader did not complete anything; the host did.
    return JSON.stringify(await page.evaluate(() => window.otpHeard)) === heardBefore || 'a code set by the host dispatched `complete`';
});

await check('what the field cannot hold is dropped, and the host is handed back what is there', async () => {
    await page.click('#otp-host-bad');
    await settle(100);
    if ((await otpBoxes()) !== '12') return `the boxes hold ${JSON.stringify(await otpBoxes())}`;
    return (await page.locator('#otp-mirror').innerText()) === '12'
        || `the host's state reads ${JSON.stringify(await page.locator('#otp-mirror').innerText())}`;
});

await check('masked: a PIN shows dots', async () => {
    const types = await page.evaluate(() =>
        [...document.querySelectorAll('[id^="pin-"][data-ds-otp-box]')].map((b) => b.type));
    return types.every((t) => t === 'password') && types.length === 4 || `box types ${JSON.stringify(types)}`;
});

await check('numeric=false takes letters, refuses the rest, and names its boxes characters', async () => {
    await page.focus('#ref-1');
    await page.keyboard.type('a-B');
    const name = await page.locator('#ref-1').getAttribute('aria-label');
    if (name !== 'Character 1 of 5') return `box 1 is named ${JSON.stringify(name)}`;
    return JSON.stringify((await posted()).ref) === '["aB"]' || `posted ${JSON.stringify((await posted()).ref)}`;
});

// ---------------------------------------------------------------- tag input

group('tag input (dsTagInput)');

const tags = async () => (await posted())['emails[]'] ?? [];
const tagNote = () => page.evaluate(() => {
    const note = document.querySelector('[data-ds-tag-note]');
    return note.hidden ? null : note.textContent.trim();
});
const tagStatus = () => page.locator('[data-ds-tag-status]').innerText();

await check('renders the initial chips from PHP, each posting its own value', async () =>
    JSON.stringify(await tags()) === '["maya@example.com"]' || `posted ${JSON.stringify(await tags())}`);

await check('Enter commits, and the addition is announced', async () => {
    await page.focus('#emails');
    await page.keyboard.type('ada@example.com');
    await page.keyboard.press('Enter');
    if (JSON.stringify(await tags()) !== '["maya@example.com","ada@example.com"]') return `posted ${JSON.stringify(await tags())}`;
    if ((await page.locator('#emails').inputValue()) !== '') return 'the text box was not emptied';
    return (await tagStatus()) === 'Added ada@example.com.' || `the live region says ${JSON.stringify(await tagStatus())}`;
});

await check('a comma commits', async () => {
    await page.keyboard.type('bo@example.com,');
    return JSON.stringify(await tags()) === '["maya@example.com","ada@example.com","bo@example.com"]'
        || `posted ${JSON.stringify(await tags())}`;
});

await check('an invalid entry stays in the box with a message, and is not posted', async () => {
    await page.keyboard.type('nope');
    await page.keyboard.press('Enter');
    const left = await page.locator('#emails').inputValue();
    if (left !== 'nope') return `the box holds ${JSON.stringify(left)} — the entry was dropped`;
    if ((await tags()).includes('nope')) return 'it was posted anyway';
    const note = await tagNote();
    if (note !== 'nope is not an email address.') return `the message reads ${JSON.stringify(note)}`;
    return (await page.locator('#emails').getAttribute('aria-invalid')) === 'true' || 'the box is not marked invalid';
});

await check('the message takes the help line\'s place, and goes when the box is cleared', async () => {
    const helpHidden = await page.evaluate(() => document.querySelector('[data-ds-tag-help]').hidden);
    if (!helpHidden) return 'help and message are stacked';
    await page.fill('#emails', '');
    const note = await tagNote();
    const helpBack = await page.evaluate(() => !document.querySelector('[data-ds-tag-help]').hidden);
    return (note === null && helpBack) || `message ${JSON.stringify(note)}, help shown ${helpBack}`;
});

await check('a duplicate, in any case, stays in the box with a message', async () => {
    await page.keyboard.type('Maya@Example.com');
    await page.keyboard.press('Enter');
    const note = await tagNote();
    return (note === 'Maya@Example.com is already in the list.' && (await tags()).length === 3)
        || `message ${JSON.stringify(note)}, posted ${JSON.stringify(await tags())}`;
});

await check('a pasted list commits every good entry and keeps the bad one', async () => {
    await page.fill('#emails', '');
    await paste('#emails', 'cy@example.com\ndee@example.com, nope2');
    const list = await tags();
    if (JSON.stringify(list.slice(3)) !== '["cy@example.com","dee@example.com"]') return `posted ${JSON.stringify(list)}`;
    const left = await page.locator('#emails').inputValue();
    return left === 'nope2' || `the box holds ${JSON.stringify(left)}`;
});

await check('past `max`, an entry stays in the box with a message', async () => {
    await page.fill('#emails', 'eve@example.com');
    await page.keyboard.press('Enter');
    const note = await tagNote();
    return (note === 'No more than 5 can be added.' && (await tags()).length === 5)
        || `message ${JSON.stringify(note)}, posted ${(await tags()).length}`;
});

await check('each chip\'s remove button is named for its tag', async () =>
    (await page.locator('[data-ds-tag-remove]').first().getAttribute('aria-label')) === 'Remove maya@example.com'
    || `named ${JSON.stringify(await page.locator('[data-ds-tag-remove]').first().getAttribute('aria-label'))}`);

await check('removing one takes its value out, says so, clears "too many", and keeps focus in the field', async () => {
    await page.locator('[data-ds-tag-remove]').first().click();
    const list = await tags();
    if (list.includes('maya@example.com')) return `still posted: ${JSON.stringify(list)}`;
    if ((await tagStatus()) !== 'Removed maya@example.com.') return `the live region says ${JSON.stringify(await tagStatus())}`;
    if ((await tagNote()) !== null) return `the "too many" message is still up: ${await tagNote()}`;
    return (await active()) === 'emails' || `focus fell to ${await active()}`;
});

await check('Backspace in an empty box focuses the last chip, and a second press removes it', async () => {
    await page.fill('#emails', '');
    await page.keyboard.press('Backspace');
    const focused = await page.evaluate(() => document.activeElement.getAttribute('aria-label'));
    if (focused !== 'Remove dee@example.com') return `first press focused ${JSON.stringify(focused)}`;
    if ((await tags()).length !== 4) return 'the first press already removed it';
    await page.keyboard.press('Backspace');
    if ((await tags()).includes('dee@example.com')) return 'the second press did not remove it';
    return (await active()) === 'emails' || `focus is on ${await active()}`;
});

await check('leaving the field commits what was typed', async () => {
    await page.keyboard.type('gus@example.com');
    await page.keyboard.press('Tab');
    return (await tags()).includes('gus@example.com') || `posted ${JSON.stringify(await tags())}`;
});

await check('a comma that arrives as text, as a phone keyboard sends it, commits too', async () => {
    await page.fill('#emails', 'hal@example.com,');
    const left = await page.locator('#emails').inputValue();
    return ((await tags()).includes('hal@example.com') && left === '')
        || `posted ${JSON.stringify(await tags())}, the box holds ${JSON.stringify(left)}`;
});

await check('Enter in an empty box is left alone, so it still submits the form', async () => {
    await page.fill('#emails', '');
    const prevented = await page.evaluate(() => {
        const press = new KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true });
        document.getElementById('emails').dispatchEvent(press);
        return press.defaultPrevented;
    });
    return prevented === false || 'Enter was swallowed with nothing to commit';
});

await check('a chip added by the script is the same markup the server renders', async () => {
    const shape = await page.evaluate(() => [...document.querySelectorAll('[data-ds-tag]')]
        .map((chip) => [chip.className, chip.querySelector('[data-ds-tag-remove]').className].join('|')));
    return new Set(shape).size === 1 || 'server-rendered and script-added chips differ';
});

await check('x-model on the tag followed every add and remove', async () =>
    (await page.locator('#emails-mirror').innerText()) === (await tags()).join(' ')
    || `the host holds ${JSON.stringify(await page.locator('#emails-mirror').innerText())}, the form posts ${JSON.stringify(await tags())}`);

await check('a list the HOST sets through x-model is rendered as chips, and posted', async () => {
    const said = await tagStatus();
    await page.click('#emails-host-set');
    await settle(100);
    const labels = await page.locator('[data-ds-tag-label]').allInnerTexts();
    if (JSON.stringify(labels) !== '["zoe@example.com","yan@example.com"]') return `chips read ${JSON.stringify(labels)}`;
    if (JSON.stringify(await tags()) !== '["zoe@example.com","yan@example.com"]') return `posted ${JSON.stringify(await tags())}`;
    if ((await page.locator('#emails-mirror').innerText()) !== 'zoe@example.com yan@example.com') return 'the blank entry was handed back to the host';
    return (await tagStatus()) === said || `the live region announced the host's change: ${JSON.stringify(await tagStatus())}`;
});

await check('and an empty list from the host empties the field and hides the list', async () => {
    await page.click('#emails-host-clear');
    await settle(100);
    const hidden = await page.evaluate(() => document.querySelector('#emails').closest('[x-data]').querySelector('[data-ds-tag-list]').hidden);
    return ((await tags()).length === 0 && hidden) || `posted ${JSON.stringify(await tags())}, list hidden ${hidden}`;
});

// ---------------------------------------------------------------- rating

/*
 * No script: what is pressed and posted is native radios, and every star's
 * look is CSS reading them. So "lit" is read from the computed fill, which is
 * the thing a reader sees.
 */
group('rating (radios and CSS, no script)');

const ratingLit = () => page.evaluate(() =>
    [...document.querySelectorAll('#rating .group\\/stars > label')]
        .map((star) => (getComputedStyle(star).fill === 'rgba(0, 0, 0, 0)' ? 0 : 1)).join(''));
const score = async () => JSON.stringify((await posted()).score ?? null);
const star = (n) => `#rating input[name=score][value="${n}"]`;

await page.mouse.move(0, 0);

await check('posts the value it was rendered with, and lights that many stars', async () =>
    ((await score()) === '["2"]' && (await ratingLit()) === '11000') || `posted ${await score()}, lit ${await ratingLit()}`);

await check('a press on a star chooses it, natively', async () => {
    await page.click(`${star(4)} >> xpath=..`);
    await page.mouse.move(0, 0);
    await settle(150);
    return ((await score()) === '["4"]' && (await ratingLit()) === '11110') || `posted ${await score()}, lit ${await ratingLit()}`;
});

await check('hovering previews "up to here" over the chosen value, and leaving restores it', async () => {
    await page.hover(`${star(2)} >> xpath=..`);
    await settle(150);
    const preview = await ratingLit();
    await page.mouse.move(0, 0);
    await settle(150);
    const after = await ratingLit();
    return (preview === '11000' && after === '11110') || `while hovering 2: ${preview}; after: ${after}`;
});

await check('the arrow keys move the rating, as in any radio group', async () => {
    await page.focus(star(4));
    await page.keyboard.press('ArrowRight');
    await settle(150);
    return ((await score()) === '["5"]' && (await ratingLit()) === '11111') || `posted ${await score()}, lit ${await ratingLit()}`;
});

await check('past the last star is "No rating": posts empty, lights nothing, and is named', async () => {
    await page.keyboard.press('ArrowRight');
    await settle(150);
    const name = await page.evaluate(() => document.activeElement.parentElement.textContent.trim());
    if (name !== 'No rating') return `focus is on ${JSON.stringify(name)}`;
    return ((await score()) === '[""]' && (await ratingLit()) === '00000') || `posted ${await score()}, lit ${await ratingLit()}`;
});

await check('focus shows on the star it is on, before anything is chosen there', async () => {
    await page.keyboard.press('ArrowRight');
    await settle(150);
    const ring = await page.evaluate(() => getComputedStyle(document.activeElement.parentElement).outlineStyle);
    return ((await score()) === '["1"]' && ring !== 'none') || `posted ${await score()}, outline ${ring}`;
});

await check('readonly is one image, named for the value, half a star drawn as half', async () => {
    const r = await page.evaluate(() => {
        const img = document.querySelector('#avg [role=img]');
        const half = img.children[3].querySelector('.overflow-hidden');
        return {
            name: img.getAttribute('aria-label'),
            focusable: img.querySelectorAll('input, button, [tabindex]').length,
            ratio: half ? half.getBoundingClientRect().width / img.children[3].getBoundingClientRect().width : null,
        };
    });
    if (r.name !== '3.5 out of 5') return `named ${JSON.stringify(r.name)}`;
    if (r.focusable) return 'a readonly rating has something to focus';
    return r.ratio === 0.5 || `the fourth star is ${r.ratio} filled`;
});

// ---------------------------------------------------------------- chip group

group('chip group (inputs and CSS, no script)');

const checkWidths = (scope) => page.evaluate((sel) =>
    [...document.querySelectorAll(`${sel} label > span[aria-hidden]`)].map((c) => Math.round(c.getBoundingClientRect().width)), scope);
const chip = (scope, value) => `${scope} input[value="${value}"] >> xpath=..`;

await check('posts what it was rendered with: one value from radios, an array from checkboxes', async () => {
    const p = await posted();
    return (JSON.stringify(p.status) === '["open"]' && JSON.stringify(p['labels[]']) === '["billing"]') || JSON.stringify(p);
});

await check('the chosen chip shows its check, the others give it no width', async () =>
    JSON.stringify(await checkWidths('#chips-single')) === '[20,0,0]' || `check widths ${JSON.stringify(await checkWidths('#chips-single'))}`);

await check('a press chooses it natively; the check moves, by a width transition', async () => {
    await page.click(chip('#chips-single', 'closed'));
    const style = await page.evaluate(() => {
        const c = getComputedStyle(document.querySelector('#chips-single input[value="closed"] ~ span'));
        return `${c.transitionProperty} ${c.transitionDuration}`;
    });
    await settle(300);
    const p = await posted();
    if (JSON.stringify(p.status) !== '["closed"]') return `posted ${JSON.stringify(p.status)}`;
    if (style !== 'width 0.15s') return `the check moves by ${style}`;
    return JSON.stringify(await checkWidths('#chips-single')) === '[0,20,0]' || `check widths ${JSON.stringify(await checkWidths('#chips-single'))}`;
});

await check('with reduced motion the check appears at once', async () => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    const duration = await page.evaluate(() =>
        getComputedStyle(document.querySelector('#chips-single input[value="closed"] ~ span')).transitionDuration);
    await page.emulateMedia({ reducedMotion: 'no-preference' });
    return duration === '0s' || `transition-duration ${duration}`;
});

await check('a disabled chip cannot be chosen', async () => {
    await page.click(chip('#chips-single', 'spam'), { force: true });
    return JSON.stringify((await posted()).status) === '["closed"]' || `posted ${JSON.stringify((await posted()).status)}`;
});

await check('single: the arrow keys move the choice, past the disabled chip', async () => {
    await page.focus('#chips-single input[value="closed"]');
    await page.keyboard.press('ArrowRight');
    await settle(50);
    return JSON.stringify((await posted()).status) === '["open"]' || `posted ${JSON.stringify((await posted()).status)}`;
});

await check('multiple: Space toggles the focused chip, and focus is drawn on the chip', async () => {
    await page.focus('#chips-multi input[value="bug"]');
    await page.keyboard.press('Space');
    await settle(250);
    const ring = await page.evaluate(() => getComputedStyle(document.activeElement.parentElement).outlineStyle);
    const p = await posted();
    if (JSON.stringify(p['labels[]']) !== '["billing","bug"]') return `posted ${JSON.stringify(p['labels[]'])}`;
    if (ring === 'none') return 'no outline on the focused chip';
    return JSON.stringify(await checkWidths('#chips-multi')) === '[18,18,0]' || `check widths ${JSON.stringify(await checkWidths('#chips-multi'))}`;
});

await check('x-model on the group: a press reaches the host, and the host sets the chips', async () => {
    await page.click(chip('#chips-model', 'billing'));
    await settle(50);
    const mirrored = await page.textContent('#picked-mirror');
    if (mirrored !== '["bug","billing"]') return `the host has ${mirrored}`;
    await page.click('#picked-host-set');
    await settle(250);
    const p = await posted();
    return (JSON.stringify(p['picked[]']) === '["sales"]' && JSON.stringify(await checkWidths('#chips-model')) === '[0,0,20]')
        || `posted ${JSON.stringify(p['picked[]'])}, check widths ${JSON.stringify(await checkWidths('#chips-model'))}`;
});

// ---------------------------------------------------------------- radio cards

group('radio cards (radios and CSS, no script)');

const card = (value) => `#cards input[value="${value}"] >> xpath=ancestor::label`;
const plan = async () => JSON.stringify((await posted()).tier ?? null);
const cardLook = (value) => page.evaluate((v) => {
    const style = getComputedStyle(document.querySelector(`#cards input[value="${v}"]`).closest('label'));
    return { ring: style.boxShadow !== 'none', outline: style.outlineStyle !== 'none' };
}, value);

await check('posts the card it was rendered with, and only that card wears the ring', async () => {
    const [free, pro] = [await cardLook('free'), await cardLook('pro')];
    return ((await plan()) === '["pro"]' && pro.ring && !free.ring) || `posted ${await plan()}, ring free ${free.ring} pro ${pro.ring}`;
});

await check('a press anywhere on the card chooses it, its description included', async () => {
    await page.click('#cards input[value="free"] >> xpath=ancestor::label >> text=For trying it out.');
    await settle(250);
    return ((await plan()) === '["free"]' && (await cardLook('free')).ring) || `posted ${await plan()}`;
});

await check('the arrow keys move the choice, past the disabled card; focus is drawn on the card', async () => {
    await page.focus('#cards input[value="pro"]');
    await page.keyboard.press('ArrowLeft');
    await page.keyboard.press('ArrowLeft');
    await settle(250);
    const look = await cardLook('pro');
    const radioRing = await page.evaluate(() => getComputedStyle(document.activeElement).outlineStyle);
    if ((await plan()) !== '["pro"]') return `posted ${await plan()} after Left, Left from Free`;
    return (look.outline && radioRing === 'none') || `card outline ${look.outline}, radio outline ${radioRing}`;
});

await check('a press on a disabled card does nothing', async () => {
    await page.click(card('scale'), { force: true });
    return (await plan()) === '["pro"]' || `posted ${await plan()}`;
});

await check('the name is the title and meta; the description is the description', async () => {
    const r = await page.evaluate(() => {
        const radio = document.querySelector('#cards input[value="pro"]');
        const text = (ids) => ids.split(' ').map((id) => document.getElementById(id).textContent.trim()).join(', ');
        return { name: text(radio.getAttribute('aria-labelledby')), desc: text(radio.getAttribute('aria-describedby')) };
    });
    return (r.name === 'Pro, $12 a month' && r.desc === 'For a small team.') || JSON.stringify(r);
});

await check('required on the group: the browser holds the form until a card is chosen', async () => {
    const missing = await page.evaluate(() => document.querySelector('#cards-required input').validity.valueMissing);
    await page.click('#cards-required input[value="express"] >> xpath=ancestor::label');
    const after = await page.evaluate(() => document.querySelector('#cards-required input').validity.valueMissing);
    return (missing && !after) || `valueMissing before ${missing}, after ${after}`;
});

await check('three columns on a wide screen, one on a phone', async () => {
    const tops = () => page.evaluate(() =>
        [...document.querySelectorAll('#cards label')].map((l) => Math.round(l.getBoundingClientRect().top)));
    const size = page.viewportSize();
    await page.setViewportSize({ width: 1280, height: size.height });
    const wide = new Set(await tops()).size;
    await page.setViewportSize({ width: 375, height: size.height });
    const narrow = new Set(await tops()).size;
    await page.setViewportSize(size);
    return (wide === 1 && narrow === 3) || `rows: ${wide} wide, ${narrow} on a phone`;
});

// ---------------------------------------------------------------- glide

/** Where a container's highlight is, against where an item is: true, or what is off. */
const glideOn = (container, item) => page.evaluate(([c, i]) => {
    const root = document.querySelector(c);
    const h = [...root.querySelectorAll('[data-ds-glide-highlight]')].find((x) => x.closest('[data-ds-glide]') === root);
    const target = typeof i === 'string' ? document.querySelector(i) : null;
    if (!h || h.hidden || getComputedStyle(h).display === 'none') return 'the highlight is hidden';
    if (getComputedStyle(h).opacity === '0') return 'the highlight is transparent';
    const a = h.getBoundingClientRect();
    const b = target.getBoundingClientRect();
    const off = Math.max(Math.abs(a.left - b.left), Math.abs(a.top - b.top), Math.abs(a.width - b.width), Math.abs(a.height - b.height));
    return off < 1 || `the highlight is ${off.toFixed(1)}px off ${i}`;
}, [container, item]);

const glideShown = (container) => page.evaluate((c) => {
    const root = document.querySelector(c);
    const h = [...root.querySelectorAll('[data-ds-glide-highlight]')].find((x) => x.closest('[data-ds-glide]') === root);
    return Boolean(h && !h.hidden && getComputedStyle(h).opacity !== '0');
}, container);

/** The highlight's translate, as it is drawn this moment (mid-transition included). */
const glideX = (container) => page.evaluate((c) => {
    const root = document.querySelector(c);
    const h = [...root.querySelectorAll('[data-ds-glide-highlight]')].find((x) => x.closest('[data-ds-glide]') === root);
    return Math.round(h.getBoundingClientRect().left - root.getBoundingClientRect().left - root.clientLeft);
}, container);

const itemX = (container, item) => page.evaluate(([c, i]) => {
    const root = document.querySelector(c);
    return Math.round(document.querySelector(i).getBoundingClientRect().left - root.getBoundingClientRect().left - root.clientLeft);
}, [container, item]);

group('x-ds-glide on its own (selected mode, markup no component wrote)');

await check('placed under the current item on start, and the container marked ready', async () => {
    const ready = await page.evaluate(() => document.getElementById('glide-raw').hasAttribute('data-ds-glide-ready'));
    if (!ready) return 'no data-ds-glide-ready on the container';
    return glideOn('#glide-raw', '#glide-b');
});

await check('follows the watched expression to the new item, by a 150 ms slide', async () => {
    const from = await glideX('#glide-raw');
    await page.click('#glide-to-c');
    await settle(60);
    const mid = await glideX('#glide-raw');
    const to = await itemX('#glide-raw', '#glide-c');
    const style = await page.evaluate(() => {
        const s = getComputedStyle(document.querySelector('#glide-raw [data-ds-glide-highlight]'));
        return `${s.transitionDuration}`;
    });
    await settle(300);
    if (!(mid > from && mid < to)) return `mid-way it was at ${mid}, between ${from} and ${to}?`;
    if (!style.startsWith('0.15s')) return `transition ${style}`;
    return glideOn('#glide-raw', '#glide-c');
});

await check('an item that changes width is re-measured (ResizeObserver)', async () => {
    await page.click('#glide-grow');
    await settle(300);
    return glideOn('#glide-raw', '#glide-c');
});

await check('with reduced motion it jumps', async () => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    const duration = await page.evaluate(() =>
        getComputedStyle(document.querySelector('#glide-raw [data-ds-glide-highlight]')).transitionDuration);
    await page.emulateMedia({ reducedMotion: 'no-preference' });
    return duration === '0s' || `transition-duration ${duration}`;
});

// ---------------------------------------------------------------- anchor

/** A panel's box, its reference's, the viewport, and the side it took. */
const anchorBoxes = (panel, reference) => page.evaluate(([p, r]) => {
    const el = document.querySelector(p);
    const a = el.getBoundingClientRect();
    const b = document.querySelector(r).getBoundingClientRect();
    return {
        panel: { left: a.left, top: a.top, right: a.right, bottom: a.bottom, width: a.width, height: a.height },
        ref: { left: b.left, top: b.top, right: b.right, bottom: b.bottom },
        vw: document.documentElement.clientWidth,
        vh: document.documentElement.clientHeight,
        side: el.dataset.side,
        shown: getComputedStyle(el).display !== 'none',
        // What is actually painted at the panel's centre: the panel, or
        // whatever clipped it. A tooltip never takes the pointer, and
        // elementFromPoint looks straight through a pointer-events-none
        // element, so that is lifted for the instant of the probe.
        onTop: (() => {
            const was = el.style.pointerEvents;
            el.style.pointerEvents = 'auto';
            const hit = document.elementFromPoint(a.left + a.width / 2, a.top + a.height / 2);
            el.style.pointerEvents = was;
            return el.contains(hit);
        })(),
    };
}, [panel, reference]);

/** Inside the viewport, less the 8 px margin: true, or by how much it is not. */
const insideViewport = (box, margin = 8) => {
    const out = Math.max(
        margin - box.panel.left,
        margin - box.panel.top,
        box.panel.right - (box.vw - margin),
        box.panel.bottom - (box.vh - margin),
    );
    return out <= 0.5 || `${Math.round(out)}px outside the viewport less its margin`;
};

group('x-ds-anchor on its own (markup no component wrote)');

await page.evaluate(() => document.getElementById('anchor-ref').scrollIntoView({ block: 'center' }));
await settle(100);
await page.click('#anchor-ref');
await settle(250);

await check('placed below its reference, start-aligned, 8 px off it', async () => {
    const b = await anchorBoxes('#anchor-panel', '#anchor-ref');
    if (!b.shown) return 'not shown';
    if (b.side !== 'bottom') return `data-side is ${b.side}`;
    const gap = Math.round(b.panel.top - b.ref.bottom);
    const lean = Math.round(b.panel.left - b.ref.left);
    return (gap === 8 && lean === 0) || `${gap}px below and ${lean}px across from the reference`;
});

await check('not clipped by the overflow-hidden box it sits in (position: fixed)', async () => {
    const b = await anchorBoxes('#anchor-panel', '#anchor-ref');
    const clip = await page.evaluate(() => document.getElementById('anchor-clip').getBoundingClientRect().bottom);
    if (b.panel.bottom <= clip) return 'the panel never leaves the box, so this proves nothing';
    return b.onTop || 'something else is painted where the panel is: the box clipped it';
});

await check('follows its reference on scroll', async () => {
    await page.mouse.wheel(0, 120);
    await settle(150);
    const b = await anchorBoxes('#anchor-panel', '#anchor-ref');
    const gap = Math.round(b.panel.top - b.ref.bottom);
    return gap === 8 || `after scrolling it is ${gap}px below the reference`;
});

await check('flips above when the reference reaches the bottom of the window', async () => {
    await page.evaluate(() => document.getElementById('anchor-ref').scrollIntoView({ block: 'end' }));
    await settle(150);
    const b = await anchorBoxes('#anchor-panel', '#anchor-ref');
    if (b.side !== 'top') return `data-side is ${b.side}`;
    const gap = Math.round(b.ref.top - b.panel.bottom);
    if (gap !== 8) return `${gap}px above the reference`;
    return insideViewport(b);
});

await check('stops listening once closed', async () => {
    await page.click('#anchor-ref');
    await settle(250);
    const before = await page.evaluate(() => document.getElementById('anchor-panel').style.top);
    await page.evaluate(() => document.getElementById('anchor-ref').scrollIntoView({ block: 'center' }));
    await settle(150);
    const after = await page.evaluate(() => document.getElementById('anchor-panel').style.top);
    return before === after || `the closed panel moved from ${before} to ${after}`;
});

await check('shifts back inside the viewport when wider than the room beside its reference', async () => {
    await page.evaluate(() => document.getElementById('anchor-wide-ref').scrollIntoView({ block: 'center' }));
    await page.click('#anchor-wide-ref');
    await settle(250);
    const b = await anchorBoxes('#anchor-wide', '#anchor-wide-ref');
    await page.click('#anchor-wide-ref');
    await settle(200);
    if (b.ref.left + b.panel.width < b.vw - 8) return 'there was room after all, so this proves nothing';
    if (Math.round(b.panel.top - b.ref.bottom) !== 8) return 'it moved off its side';
    return insideViewport(b);
});

await check('taller than the window: capped at the room there is, scrolling inside', async () => {
    await page.evaluate(() => document.getElementById('anchor-tall-ref').scrollIntoView({ block: 'center' }));
    await page.click('#anchor-tall-ref');
    await settle(250);
    const b = await anchorBoxes('#anchor-tall', '#anchor-tall-ref');
    const style = await page.evaluate(() => getComputedStyle(document.getElementById('anchor-tall')).overflowY);
    await page.click('#anchor-tall-ref');
    await settle(200);
    if (style !== 'auto') return `overflow-y is ${style}`;
    return insideViewport(b);
});

await check('lands beside its reference inside a transformed ancestor (its containing block)', async () => {
    await page.evaluate(() => document.getElementById('anchor-boxed-ref').scrollIntoView({ block: 'center' }));
    await page.click('#anchor-boxed-ref');
    await settle(250);
    const b = await anchorBoxes('#anchor-boxed', '#anchor-boxed-ref');
    await page.click('#anchor-boxed-ref');
    await settle(200);
    if (b.side !== 'right') return `data-side is ${b.side}`;
    const gap = Math.round(b.panel.left - b.ref.right);
    const lean = Math.round(b.panel.top - b.ref.top);
    return (gap === 4 && lean === 0) || `${gap}px right of and ${lean}px down from the reference`;
});

// ---------------------------------------------------------------- popover

const shownById = (id) => page.evaluate((i) => {
    const el = document.getElementById(i);
    return Boolean(el) && getComputedStyle(el).display !== 'none';
}, id);
const inside = (id) => page.evaluate((i) => document.getElementById(i).contains(document.activeElement), id);

group('popover (dsPopover, x-ds-anchor)');

await page.evaluate(() => document.getElementById('pop-trigger').scrollIntoView({ block: 'center' }));

await check('closed on load, and the trigger says so and names the panel it controls', async () => {
    const r = await page.evaluate(() => {
        const t = document.getElementById('pop-trigger');
        return { expanded: t.getAttribute('aria-expanded'), controls: t.getAttribute('aria-controls') };
    });
    if (await shownById('pop-filters')) return 'visible before opening';
    return (r.expanded === 'false' && r.controls === 'pop-filters') || JSON.stringify(r);
});

await check('a press opens it, flips aria-expanded, and moves focus to the first control inside', async () => {
    await page.click('#pop-trigger');
    await settle(250);
    if (!(await shownById('pop-filters'))) return 'not shown';
    const expanded = await page.getAttribute('#pop-trigger', 'aria-expanded');
    if (expanded !== 'true') return `aria-expanded is ${expanded}`;
    return (await active()) === 'pop-first' || `focus on ${await active()}`;
});

await check('a named, NON-modal dialog', async () => {
    const r = await page.evaluate(() => {
        const p = document.getElementById('pop-filters');
        return { role: p.getAttribute('role'), label: p.getAttribute('aria-label'), modal: p.getAttribute('aria-modal') };
    });
    return (r.role === 'dialog' && r.label === 'Filters' && r.modal === null) || JSON.stringify(r);
});

await check('Tab is not trapped: past the last control it leaves, and the panel closes', async () => {
    await page.keyboard.press('Tab');
    if ((await active()) !== 'pop-last') return `the second Tab stop was ${await active()}`;
    await page.keyboard.press('Tab');
    await settle(250);
    if ((await active()) !== 'pop-after') return `focus went to ${await active()}`;
    return (await shownById('pop-filters')) === false || 'still open after focus left';
});

await check('Escape closes it and returns focus to the trigger', async () => {
    await page.click('#pop-trigger');
    await settle(250);
    await page.keyboard.press('Escape');
    await settle(250);
    if (await shownById('pop-filters')) return 'still open';
    return (await active()) === 'pop-trigger' || `focus on ${await active()}`;
});

await check('a press outside closes it, without pulling focus back to the trigger', async () => {
    await page.click('#pop-trigger');
    await settle(250);
    await page.click('#pop-after');
    await settle(250);
    if (await shownById('pop-filters')) return 'still open';
    return (await active()) === 'pop-after' || `focus on ${await active()}`;
});

await check('a press on the trigger closes it again', async () => {
    await page.click('#pop-trigger');
    await settle(250);
    await page.click('#pop-trigger');
    await settle(250);
    if (await shownById('pop-filters')) return 'still open';
    return (await page.getAttribute('#pop-trigger', 'aria-expanded')) === 'false' || 'aria-expanded stayed true';
});

await check('host-owned: the host opens it, and the panel is named by its heading', async () => {
    await page.click('#pop-share-host');
    await settle(250);
    if (!(await shownById('pop-share'))) return 'the host set it and nothing opened';
    const name = await page.evaluate(() => {
        const id = document.getElementById('pop-share').getAttribute('aria-labelledby');
        return document.getElementById(id)?.textContent.trim();
    });
    if (name !== 'Share this report') return `named ${JSON.stringify(name)}`;
    return (await page.getAttribute('#pop-share-trigger', 'aria-expanded')) === 'true' || 'aria-expanded not in step';
});

await check('host-owned: closed by the host from inside, focus goes back to the trigger', async () => {
    await page.click('#pop-share-done');
    await settle(250);
    if (await shownById('pop-share')) return 'still open';
    if ((await page.textContent('#pop-share-mirror')) !== 'false') return 'the host still thinks it is open';
    return (await active()) === 'pop-share-trigger' || `focus on ${await active()}`;
});

await check('host-owned: the trigger writes back to the host (two-way)', async () => {
    await page.click('#pop-share-trigger');
    await settle(250);
    const mirror = await page.textContent('#pop-share-mirror');
    await page.keyboard.press('Escape');
    await settle(250);
    const after = await page.textContent('#pop-share-mirror');
    return (mirror === 'true' && after === 'false') || `host saw ${mirror}, then ${after}`;
});

await check('nested: a press inside the inner one leaves the outer one open', async () => {
    await page.evaluate(() => document.getElementById('pop-outer-trigger').scrollIntoView({ block: 'center' }));
    await page.click('#pop-outer-trigger');
    await settle(250);
    await page.click('#pop-inner-trigger');
    await settle(250);
    await page.click('#pop-inner-button');
    await settle(150);
    const outer = await shownById('pop-outer');
    const inner = await shownById('pop-inner');
    return (outer && inner) || `outer ${outer ? 'open' : 'closed'}, inner ${inner ? 'open' : 'closed'}`;
});

await check('nested: Escape closes the inner one only, and focus returns to ITS trigger', async () => {
    await page.keyboard.press('Escape');
    await settle(250);
    if (await shownById('pop-inner')) return 'the inner one is still open';
    if (!(await shownById('pop-outer'))) return 'Escape closed the outer one too';
    return (await active()) === 'pop-inner-trigger' || `focus on ${await active()}`;
});

await check('a combobox inside: choosing an option leaves the popover open', async () => {
    await page.click('#pop-tags');
    await settle(200);
    await page.locator('#pop-outer [role=option]:visible').first().click();
    await settle(200);
    if (!(await shownById('pop-outer'))) return 'the popover closed';
    const chosen = await page.evaluate(() => [...document.querySelectorAll('#pop-outer input[type=hidden][name="pop-tags[]"]')].map((i) => i.value));
    return chosen.includes('a11y') || `chosen ${JSON.stringify(chosen)}`;
});

await check('a combobox inside: its Escape closes its list, not the popover', async () => {
    await page.click('#pop-tags');
    await settle(200);
    await page.keyboard.press('Escape');
    await settle(250);
    if (!(await shownById('pop-outer'))) return 'the popover closed too';
    await page.keyboard.press('Escape');
    await settle(250);
    return (await shownById('pop-outer')) === false || 'a second Escape did not close the popover';
});

await check('nothing focusable inside: focus goes to the panel itself', async () => {
    await page.evaluate(() => document.getElementById('pop-pinned-trigger').scrollIntoView({ block: 'center' }));
    await page.click('#pop-pinned-trigger');
    await settle(250);
    return (await active()) === 'pop-pinned' || `focus on ${await active()}`;
});

await check('not dismissible: Escape and a press outside leave it open; the trigger closes it', async () => {
    await page.keyboard.press('Escape');
    await settle(150);
    if (!(await shownById('pop-pinned'))) return 'Escape closed it';
    await page.click('#pop-after');
    await settle(150);
    if (!(await shownById('pop-pinned'))) return 'a press outside closed it';
    await page.click('#pop-pinned-trigger');
    await settle(250);
    return (await shownById('pop-pinned')) === false || 'the trigger did not close it';
});

await check('no name given: the panel is named by its trigger', async () => {
    const name = await page.evaluate(() => {
        const id = document.getElementById('pop-edge').getAttribute('aria-labelledby');
        return id && document.getElementById(id)?.textContent.trim();
    });
    return name === 'Edge' || `named ${JSON.stringify(name)}`;
});

await check('with reduced motion it appears at once (no transition)', async () => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.click('#pop-pinned-trigger');
    await settle(50);
    const r = await page.evaluate(() => {
        const s = getComputedStyle(document.getElementById('pop-pinned'));
        return { duration: s.transitionDuration, opacity: s.opacity };
    });
    await page.click('#pop-pinned-trigger');
    await settle(100);
    await page.emulateMedia({ reducedMotion: 'no-preference' });
    return (r.duration === '0s' && r.opacity === '1') || JSON.stringify(r);
});

// ---------------------------------------------------------------- hover card

const cardShown = () => shownById('hc-card');

/** The pointer somewhere that is neither the trigger nor the card. */
const pointerAway = async () => {
    const box = await page.locator('#hc-away').boundingBox();
    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2, { steps: 4 });
};

group('hover card (dsHoverCard, x-ds-anchor)');

await page.evaluate(() => document.getElementById('hc-prose').scrollIntoView({ block: 'center' }));
await pointerAway();
await settle(100);

await check('hidden on load, and the trigger keeps its own semantics', async () => {
    if (await cardShown()) return 'visible on load';
    const r = await page.evaluate(() => {
        const a = document.getElementById('hc-trigger');
        return ['aria-expanded', 'aria-describedby', 'aria-haspopup', 'aria-controls', 'role'].filter((n) => a.hasAttribute(n));
    });
    return !r.length || `the trigger gained ${r.join(', ')}`;
});

await check('a pointer passing over opens nothing', async () => {
    await page.hover('#hc-trigger');
    await settle(200);
    await pointerAway();
    await settle(600);
    return (await cardShown()) === false || 'opened on a pass';
});

await check('a pointer resting on the trigger opens it after the delay, not before', async () => {
    await page.hover('#hc-trigger');
    await settle(250);
    if (await cardShown()) return 'open at 250 ms: no delay';
    await settle(500);
    return (await cardShown()) || 'not open after 750 ms';
});

await check('the pointer can travel from the trigger into the card', async () => {
    const card = await page.locator('#hc-card').boundingBox();
    await page.mouse.move(card.x + card.width / 2, card.y + card.height / 2, { steps: 6 });
    await settle(500);
    return (await cardShown()) || 'it closed on the way';
});

await check('leaving both closes it, after a short delay', async () => {
    await pointerAway();
    await settle(100);
    if (!(await cardShown())) return 'closed at once: no time to cross the gap';
    await settle(450);
    return (await cardShown()) === false || 'still open';
});

await check('Escape dismisses a card shown by hover, the pointer still on it (WCAG 1.4.13)', async () => {
    await page.hover('#hc-trigger');
    await settle(750);
    if (!(await cardShown())) return 'did not open';
    await page.keyboard.press('Escape');
    await settle(200);
    const shut = (await cardShown()) === false;
    await pointerAway();
    await settle(400);
    return shut || 'still open';
});

await check('keyboard focus opens it, after the same delay', async () => {
    await page.focus('#hc-before');
    await page.keyboard.press('Tab');
    if ((await active()) !== 'hc-trigger') return `Tab went to ${await active()}`;
    await settle(250);
    if (await cardShown()) return 'open at 250 ms';
    await settle(500);
    return (await cardShown()) || 'not open after 750 ms';
});

await check('Tab moves into the card\'s link, and the card stays open', async () => {
    await page.keyboard.press('Tab');
    await settle(150);
    if ((await active()) !== 'hc-link') return `Tab went to ${await active()}`;
    return (await cardShown()) || 'closed when focus went in';
});

await check('Tab on past it closes it: nothing is trapped', async () => {
    await page.keyboard.press('Tab');
    await settle(250);
    if ((await active()) !== 'hc-after') return `focus on ${await active()}`;
    return (await cardShown()) === false || 'still open';
});

await check('a mouse click on the trigger (focus without :focus-visible) does not open it by focus', async () => {
    await page.evaluate(() => document.getElementById('hc-trigger').addEventListener('click', (e) => e.preventDefault(), { once: true }));
    await page.click('#hc-trigger');
    await pointerAway();
    await settle(750);
    return (await cardShown()) === false || 'opened from the focus a click left behind';
});

group('hover card on a touch screen');
{
    const touch = await browser.newContext({ viewport: { width: 1200, height: 1000 }, hasTouch: true, isMobile: true });
    await touch.route(`${LARAVEL}/**`, serveFromLaravel);
    const tab = await touch.newPage();
    await tab.goto('file://' + join(TMP, 'page.html'), { waitUntil: 'networkidle' });
    await tab.waitForTimeout(400);

    await check('a tap runs the trigger\'s own link and opens no card', async () => {
        await tab.evaluate(() => document.getElementById('hc-trigger').scrollIntoView({ block: 'center' }));
        await tab.tap('#hc-trigger');
        await tab.waitForTimeout(800);
        const r = await tab.evaluate(() => ({
            hash: location.hash,
            shown: getComputedStyle(document.getElementById('hc-card')).display !== 'none',
        }));
        if (r.hash !== '#ada') return `the link did not run (hash ${r.hash})`;
        return r.shown === false || 'a card opened on touch';
    });
    await touch.close();
}

// ---------------------------------------------------------------- sortable table

const sortIconOpacity = (n) => page.evaluate((i) =>
    getComputedStyle(document.querySelectorAll('#sort-table th svg')[i]).opacity, n);

group('sortable table (links, no script)');

await page.evaluate(() => document.getElementById('sort-table').scrollIntoView({ block: 'center' }));
await pointerAway();
await settle(200);

await check('one header says how the rows are sorted, with its arrow showing', async () => {
    const r = await page.evaluate(() => [...document.querySelectorAll('#sort-table th')].map((th) => th.getAttribute('aria-sort')));
    if (JSON.stringify(r) !== JSON.stringify([null, 'descending'])) return `aria-sort ${JSON.stringify(r)}`;
    return (await sortIconOpacity(1)) === '1' || 'the sorted arrow is hidden';
});

await check('an unsorted sortable header shows its affordance on hover only', async () => {
    if ((await sortIconOpacity(0)) !== '0') return 'visible at rest';
    await page.hover('#sort-table th:first-child a');
    await settle(250);
    const shown = await sortIconOpacity(0);
    await pointerAway();
    await settle(250);
    return shown === '1' || `opacity ${shown} under the pointer`;
});

await check('and on keyboard focus', async () => {
    await page.focus('#hc-after');
    await page.evaluate(() => document.querySelector('#sort-table th:first-child a').focus());
    await page.keyboard.press('Shift+Tab');
    await page.keyboard.press('Tab');
    await settle(250);
    const focused = await page.evaluate(() => document.activeElement === document.querySelector('#sort-table th:first-child a'));
    if (!focused) return `focus on ${await active()}`;
    return (await sortIconOpacity(0)) === '1' || 'hidden while focused';
});

await check('a press asks for the other direction on the sorted column, ascending on the rest', async () => {
    await page.click('#sort-table th:nth-child(2) a');
    const second = await page.evaluate(() => location.hash);
    await page.click('#sort-table th:first-child a');
    const first = await page.evaluate(() => location.hash);
    return (second === '#sort-total-asc' && first === '#sort-name-asc') || `went to ${second} and ${first}`;
});

// ---------------------------------------------------------------- sub-nav

group('sub-nav (inline Alpine)');

const subNav = (id) => page.evaluate((id) => {
    const root = document.getElementById(id);
    const button = root.querySelector(':scope > button');
    const pages = root.querySelector(':scope > [role="group"]');
    const link = root.querySelector(':scope > a');
    // The button's own child: the section's icon is an svg too, in a span.
    const chevron = button.querySelector(':scope > svg');
    return {
        expanded: button.getAttribute('aria-expanded'),
        controls: button.getAttribute('aria-controls') === pages.id,
        buttonShown: button.offsetParent !== null,
        pagesShown: pages.offsetParent !== null,
        linkShown: link ? link.offsetParent !== null : null,
        linkHref: link ? link.getAttribute('href') : null,
        rotated: getComputedStyle(chevron).rotate,
    };
}, id);

await page.evaluate(() => document.getElementById('sub-rail').scrollIntoView({ block: 'center' }));
await settle(100);

await check('the section holding the current page is open; the other is closed', async () => {
    const [reports, settings] = [await subNav('sub-reports'), await subNav('sub-settings')];
    if (!reports.controls || !settings.controls) return 'aria-controls does not name the pages';
    if (reports.expanded !== 'true' || !reports.pagesShown) return `Reports ${JSON.stringify(reports)}`;
    if (settings.expanded !== 'false' || settings.pagesShown) return `Settings ${JSON.stringify(settings)}`;
    const current = await page.evaluate(() => [...document.querySelectorAll('#sub-rail [aria-current]')].map((a) => a.textContent.trim()));
    return JSON.stringify(current) === '["Churn"]' || `aria-current on ${JSON.stringify(current)}`;
});

await check('a closed section\'s pages are out of the tab order', async () => {
    await page.focus('#sub-settings > button');
    await page.keyboard.press('Tab');
    return (await page.evaluate(() => document.activeElement.id)) === 'sub-rail-toggle' || `Tab went to ${await active()}`;
});

await check('Enter opens it, the chevron turns, and Tab then reaches its pages', async () => {
    await page.focus('#sub-settings > button');
    await page.keyboard.press('Enter');
    await settle(250);
    const s = await subNav('sub-settings');
    if (s.expanded !== 'true' || !s.pagesShown) return `after Enter ${JSON.stringify(s)}`;
    if (s.rotated !== '180deg') return `chevron rotate ${s.rotated}`;
    await page.keyboard.press('Tab');
    return (await page.evaluate(() => document.activeElement.getAttribute('href'))) === '#sub-profile' || `Tab went to ${await active()}`;
});

await check('a click closes it again', async () => {
    await page.click('#sub-settings > button');
    await settle(250);
    const s = await subNav('sub-settings');
    return (s.expanded === 'false' && !s.pagesShown && s.rotated !== '180deg') || JSON.stringify(s);
});

await check('collapsed, each section is a link to its first page and no pages show', async () => {
    await page.click('#sub-rail-toggle');
    await settle(100);
    const [reports, settings] = [await subNav('sub-reports'), await subNav('sub-settings')];
    for (const [name, s, href] of [['Reports', reports, '#sub-revenue'], ['Settings', settings, '#sub-profile']]) {
        if (s.buttonShown || s.pagesShown || !s.linkShown) return `${name} ${JSON.stringify(s)}`;
        if (s.linkHref !== href) return `${name} links to ${s.linkHref}`;
    }
    const named = await page.evaluate(() => document.querySelector('#sub-reports > a').getAttribute('title'));
    return named === 'Reports' || `the collapsed link's title is ${named}`;
});

await check('expanded again, each section is as it was left', async () => {
    await page.click('#sub-rail-toggle');
    await settle(100);
    const [reports, settings] = [await subNav('sub-reports'), await subNav('sub-settings')];
    if (reports.linkShown || settings.linkShown) return 'a collapsed link is still showing';
    return (reports.pagesShown && !settings.pagesShown) || `Reports ${reports.pagesShown}, Settings ${settings.pagesShown}`;
});

// ---------------------------------------------------------------- segmented control

group('segmented control (radios and CSS; x-ds-glide when the script is there)');

const segLabel = (scope, value) => `${scope} input[value="${value}"] >> xpath=..`;
const segBg = (scope, value) => page.evaluate(([s, v]) =>
    getComputedStyle(document.querySelector(`${s} input[value="${v}"]`).parentElement).backgroundColor, [scope, value]);

await check('posts the checked value, and the highlight sits under it', async () => {
    const p = await posted();
    if (JSON.stringify(p.view) !== '["list"]') return `posted ${JSON.stringify(p.view)}`;
    return glideOn('#seg [data-ds-glide]', '#seg label:has(input[value="list"])');
});

await check('once the highlight is in place the chosen segment stops drawing its own face', async () => {
    const bg = await segBg('#seg', 'list');
    return bg === 'rgba(0, 0, 0, 0)' || `the chosen segment still paints ${bg} under a highlight`;
});

await check('a press chooses natively, reaches x-model, and the highlight slides over', async () => {
    await page.click(segLabel('#seg', 'board'));
    await settle(300);
    const p = await posted();
    const mirror = await page.textContent('#seg-mirror');
    if (JSON.stringify(p.view) !== '["board"]' || mirror !== 'board') return `posted ${JSON.stringify(p.view)}, host has ${mirror}`;
    return glideOn('#seg [data-ds-glide]', '#seg label:has(input[value="board"])');
});

await check('the arrow keys are the radios\' own, and skip the disabled segment', async () => {
    await page.focus('#seg input[value="board"]');
    await page.keyboard.press('ArrowRight');
    await page.keyboard.press('ArrowRight');
    await settle(300);
    const p = await posted();
    if (JSON.stringify(p.view) !== '["list"]') return `posted ${JSON.stringify(p.view)} after two presses from board`;
    const ring = await page.evaluate(() => getComputedStyle(document.activeElement.parentElement).outlineStyle);
    if (ring === 'none') return 'no ring on the focused segment';
    return glideOn('#seg [data-ds-glide]', '#seg label:has(input[value="list"])');
});

await check('a value the host sets through x-model moves the highlight too, with no event fired', async () => {
    await page.click('#seg-host-set');
    await settle(300);
    const checked = await page.evaluate(() => document.querySelector('#seg input:checked')?.value);
    if (checked !== 'calendar') return `checked ${checked}`;
    return glideOn('#seg [data-ds-glide]', '#seg label:has(input[value="calendar"])');
});

await check('right to left: the highlight is under the chosen segment, not its mirror image', async () =>
    glideOn('#seg-rtl [data-ds-glide]', '#seg-rtl label:has(input[value="week"])'));

await check('fullWidth: the segments share the width equally', async () => {
    const widths = await page.evaluate(() => [...document.querySelectorAll('#seg-rtl label')].map((l) => Math.round(l.getBoundingClientRect().width)));
    return new Set(widths).size === 1 || `widths ${JSON.stringify(widths)}`;
});

await check('links: the highlight sits under the aria-current="page" segment', async () =>
    glideOn('#seg-links [data-ds-glide]', '#seg-links a[aria-current="page"]'));

// ---------------------------------------------------------------- button group

group('button group (plain Blade) and the split button');

await check('joined: the outer corners are rounded and the inner ones square', async () => {
    const r = await page.evaluate(() => ['bgroup-save', 'bgroup-more'].map((id) => {
        const s = getComputedStyle(document.getElementById(id));
        return [s.borderStartStartRadius, s.borderStartEndRadius].join(' ');
    }));
    return JSON.stringify(r) === '["8px 0px","0px 8px"]' || `radii ${JSON.stringify(r)}`;
});

await check('collapsed borders: the two buttons share one hairline', async () => {
    const [a, b] = await page.evaluate(() => ['bgroup-save', 'bgroup-more'].map((id) => document.getElementById(id).getBoundingClientRect()));
    return Math.round((a.right - b.left) * 10) / 10 === 1 || `overlap ${a.right - b.left}px`;
});

await check('the hovered button rises above its neighbour, so the shared border is drawn whole', async () => {
    await page.hover('#bgroup-save');
    const z = await page.evaluate(() => getComputedStyle(document.getElementById('bgroup-save')).zIndex);
    return z === '10' || `z-index ${z}`;
});

await check('the split trigger opens its menu and says so', async () => {
    await page.click('#bgroup-more');
    await settle(200);
    const expanded = await page.getAttribute('#bgroup-more', 'aria-expanded');
    const open = await page.isVisible('#bgroup-draft');
    await page.keyboard.press('Escape');
    await settle(200);
    return (expanded === 'true' && open) || `aria-expanded ${expanded}, menu visible ${open}`;
});

// ---------------------------------------------------------------- toolbar

group('toolbar (dsToolbar, x-ds-glide.hover) with a toggle group inside');

const tabStops = (scope) => page.evaluate((s) =>
    [...document.querySelectorAll(`${s} [data-ds-toolbar-item]`)].filter((i) => i.getAttribute('tabindex') === '0').map((i) => i.id), scope);

await check('one tab stop, the first usable item; every other item, toggles included, is -1', async () => {
    const stops = await tabStops('#tb');
    const all = await page.evaluate(() => [...document.querySelectorAll('#tb [data-ds-toolbar-item]')].map((i) => i.getAttribute('tabindex')).join(''));
    return (JSON.stringify(stops) === '["tb-undo"]' && all === '0-1-1-1-1-1') || `stops ${JSON.stringify(stops)}, tabindex ${all}`;
});

await check('the arrows move, skip the disabled button, and wrap; Home and End go to the ends', async () => {
    await page.focus('#tb-undo');
    const seen = [];
    for (const key of ['ArrowRight', 'ArrowRight', 'ArrowRight', 'ArrowRight', 'ArrowRight', 'ArrowLeft', 'End', 'Home', 'ArrowUp']) {
        await page.keyboard.press(key);
        seen.push(await active());
    }
    const want = ['tb-bold', 'tb-italic', 'tb-star', 'tb-share', 'tb-undo', 'tb-share', 'tb-share', 'tb-undo', 'tb-undo'];
    return JSON.stringify(seen) === JSON.stringify(want) || `focus went ${seen.join(' → ')}`;
});

await check('the tab stop follows focus, so Tab leaves the toolbar and Shift+Tab comes back to the same item', async () => {
    await page.focus('#tb-undo');
    await page.keyboard.press('ArrowRight');
    await page.keyboard.press('Tab');
    const out = await page.evaluate(() => Boolean(document.activeElement.closest('#tb')));
    await page.keyboard.press('Shift+Tab');
    return (!out && (await active()) === 'tb-bold') || `left the toolbar: ${!out}, came back to ${await active()}`;
});

await check('keyboard focus is marked by the highlight as well as the ring', async () => {
    await page.focus('#tb-undo');
    await page.keyboard.press('ArrowRight');
    await settle(250);
    return glideOn('#tb', '#tb-bold');
});

await check('the highlight follows the pointer, and holds over the gap between items', async () => {
    await page.mouse.move(0, 0);
    await page.focus('#seg-host-set');
    await settle(250);
    await page.hover('#tb-italic');
    await settle(250);
    const on = await glideOn('#tb', '#tb-italic');
    if (on !== true) return on;
    const gap = await page.evaluate(() => {
        const a = document.getElementById('tb-bold').getBoundingClientRect();
        const b = document.getElementById('tb-italic').getBoundingClientRect();
        return { x: (a.right + b.left) / 2, y: a.top + a.height / 2 };
    });
    await page.mouse.move(gap.x, gap.y);
    await settle(250);
    return glideOn('#tb', '#tb-italic');
});

await check('it hides when the pointer leaves, and appears in place rather than sliding in', async () => {
    await page.mouse.move(5, 5);
    await settle(250);
    if (await glideShown('#tb')) return 'still shown after the pointer left';
    await page.hover('#tb-share');
    await settle(40);
    const x = await glideX('#tb');
    const target = await itemX('#tb', '#tb-share');
    return Math.abs(x - target) < 1 || `40 ms after appearing it was at ${x}, the item is at ${target}: it slid in`;
});

await check('between two items it slides', async () => {
    await page.hover('#tb-share');
    await settle(250);
    const from = await glideX('#tb');
    await page.hover('#tb-undo');
    await settle(40);
    const mid = await glideX('#tb');
    const to = await itemX('#tb', '#tb-undo');
    await settle(250);
    return (mid < from && mid > to) || `mid-way at ${mid}, from ${from} to ${to}`;
});

await check('a press darkens and shrinks the highlight, never the button', async () => {
    await page.hover('#tb-star');
    await settle(250);
    await page.mouse.down();
    await settle(250);
    const during = await page.evaluate(() => {
        const h = document.querySelector('#tb > [data-ds-glide-highlight]');
        const b = document.getElementById('tb-star');
        return { pressing: h.hasAttribute('data-pressing'), scale: getComputedStyle(h).scale, button: getComputedStyle(b).scale + ' ' + getComputedStyle(b).transform };
    });
    await page.mouse.up();
    await settle(250);
    const after = await page.evaluate(() => document.querySelector('#tb > [data-ds-glide-highlight]').hasAttribute('data-pressing'));
    return (during.pressing && parseFloat(during.scale) === 0.95 && during.button === 'none none' && !after) || JSON.stringify({ during, after });
});

await check('touch input draws no highlight', async () => {
    await page.mouse.move(5, 5);
    await settle(250);
    await page.evaluate(() => {
        const item = document.getElementById('tb-italic');
        for (const type of ['pointerover', 'pointermove', 'pointerdown']) {
            item.dispatchEvent(new PointerEvent(type, { bubbles: true, pointerType: 'touch', isPrimary: true, button: 0 }));
        }
    });
    await settle(250);
    return !(await glideShown('#tb')) || 'a touch drew the highlight';
});

await check('the toggle group inside stands its own highlight down; there is one, the toolbar\'s', async () => {
    const own = await page.evaluate(() => document.querySelector('#tb-marks > [data-ds-glide-highlight]').hidden);
    return own === true || 'the toggle group drew a second highlight';
});

await check('a toggle in the toolbar: reached by the toolbar\'s arrows, pressed by Space, posted', async () => {
    await page.focus('#tb-undo');
    await page.keyboard.press('ArrowRight');
    await page.keyboard.press('ArrowRight');
    await page.keyboard.press('Space');
    await settle(100);
    const pressed = await page.getAttribute('#tb-italic', 'aria-pressed');
    const p = await posted();
    return (pressed === 'true' && JSON.stringify(p['marks[]']) === '["bold","italic"]') || `aria-pressed ${pressed}, posted ${JSON.stringify(p['marks[]'])}`;
});

await check('an icon-only button\'s tip shows on keyboard focus, and its name stays its label', async () => {
    await page.focus('#tb-undo');
    await settle(200);
    const shown = await page.locator('[data-ds-tooltip]:has(#tb-undo) [role=tooltip]').isVisible();
    const name = await page.evaluate(() => document.getElementById('tb-undo').textContent.trim());
    await page.keyboard.press('Escape');
    return (shown && name === 'Undo') || `tip shown ${shown}, name ${JSON.stringify(name)}`;
});

await check('vertical: Up and Down move, Left and Right do not', async () => {
    await page.focus('#tbv-a');
    await page.keyboard.press('ArrowRight');
    const across = await active();
    await page.keyboard.press('ArrowDown');
    const down = await active();
    await page.keyboard.press('ArrowUp');
    await page.keyboard.press('ArrowUp');
    const wrapped = await active();
    return (across === 'tbv-a' && down === 'tbv-b' && wrapped === 'tbv-c') || `right → ${across}, down → ${down}, up twice → ${wrapped}`;
});

// ---------------------------------------------------------------- toggle group

group('toggle group on its own (dsToggleGroup)');

await check('single: posts the pressed value, and the tab stop is the checked toggle', async () => {
    const p = await posted();
    const stops = await page.evaluate(() =>
        [...document.querySelectorAll('#tg-single [data-ds-toggle]')].filter((t) => t.tabIndex === 0).map((t) => t.id));
    return (JSON.stringify(p.align) === '["left"]' && JSON.stringify(stops) === '["tg-left"]') || `posted ${JSON.stringify(p.align)}, stops ${JSON.stringify(stops)}`;
});

await check('single: the arrows move the choice, as a radio group\'s do, past the disabled toggle', async () => {
    await page.focus('#tg-left');
    await page.keyboard.press('ArrowRight');
    const first = [await active(), (await posted()).align?.join()];
    await page.keyboard.press('ArrowRight');
    const second = [await active(), (await posted()).align?.join()];
    const checked = await page.evaluate(() =>
        [...document.querySelectorAll('#tg-single [data-ds-toggle]')].map((t) => t.getAttribute('aria-checked')).join(' '));
    return (first.join() === 'tg-center,center' && second.join() === 'tg-right,right' && checked === 'false false false true')
        || `${first} then ${second}; aria-checked ${checked}`;
});

await check('single: pressing the chosen toggle leaves it chosen', async () => {
    await page.click('#tg-right');
    return JSON.stringify((await posted()).align) === '["right"]' || `posted ${JSON.stringify((await posted()).align)}`;
});

await check('multiple, x-model: a press reaches the host, and the host sets the toggles', async () => {
    await page.click('#tgm-bold');
    await settle(50);
    const mirror = await page.textContent('#tg-mirror');
    if (mirror !== '["bold","italic"]') return `the host has ${mirror}`;
    await page.click('#tg-host-set');
    await settle(50);
    const pressed = await page.evaluate(() =>
        [...document.querySelectorAll('#tg-model [data-ds-toggle]')].map((t) => t.getAttribute('aria-pressed')).join(' '));
    return pressed === 'false false true' || `aria-pressed ${pressed}`;
});

await check('multiple: the arrows move focus without pressing, and Space presses', async () => {
    await page.focus('#tgm-bold');
    await page.keyboard.press('ArrowRight');
    const moved = await active();
    const before = await page.getAttribute('#tgm-italic', 'aria-pressed');
    await page.keyboard.press('Space');
    const after = await page.getAttribute('#tgm-italic', 'aria-pressed');
    return (moved === 'tgm-italic' && before === 'false' && after === 'true') || `moved to ${moved}, pressed ${before} → ${after}`;
});

await check('standing alone, the group has its own highlight under the pointer', async () => {
    await page.hover('#tg-center');
    await settle(250);
    return glideOn('#tg-single', '#tg-center');
});

// ---------------------------------------------------------------- number field

group('number field (dsNumberField)');

const seats = () => page.locator('#seats').inputValue();
const seatButton = (step) => page.locator(`#seats ~ span [data-ds-step="${step}"]`);

await page.evaluate(() => {
    window.seatEvents = { input: 0, change: 0 };
    const field = document.getElementById('seats');
    field.addEventListener('input', () => window.seatEvents.input++);
    field.addEventListener('change', () => window.seatEvents.change++);
});
const seatEvents = () => page.evaluate(() => ({ ...window.seatEvents }));
const resetSeatEvents = () => page.evaluate(() => { window.seatEvents = { input: 0, change: 0 }; });

await check('the buttons are named, never submit, and stay out of the tab order', async () => {
    const attrs = await page.evaluate(() =>
        [...document.querySelectorAll('#seats ~ span [data-ds-step]')].map((b) =>
            [b.getAttribute('aria-label'), b.type, b.getAttribute('tabindex')].join('/')));
    return JSON.stringify(attrs) === '["Decrease Seats/button/-1","Increase Seats/button/-1"]' || `got ${JSON.stringify(attrs)}`;
});

await check('+ steps up, dispatches input and change once each, and leaves focus in the field', async () => {
    await page.focus('#seats');
    await resetSeatEvents();
    await seatButton('up').click();
    const events = await seatEvents();
    if ((await seats()) !== '3') return `value is ${await seats()}`;
    if (events.input !== 1 || events.change !== 1) return `dispatched ${JSON.stringify(events)}`;
    return (await active()) === 'seats' || `focus moved to ${await active()}`;
});

await check('− down to the minimum, where it disables itself', async () => {
    await seatButton('down').click();
    await seatButton('down').click();
    if ((await seats()) !== '1') return `value is ${await seats()}`;
    return (await seatButton('down').isDisabled()) && !(await seatButton('up').isDisabled())
        || `− disabled ${await seatButton('down').isDisabled()}, + disabled ${await seatButton('up').isDisabled()}`;
});

await check('holding + repeats after a delay, and stops when released', async () => {
    await resetSeatEvents();
    await seatButton('up').scrollIntoViewIfNeeded();
    const box = await seatButton('up').boundingBox();
    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
    await page.mouse.down();
    await page.waitForTimeout(560);
    await page.mouse.up();
    const released = Number(await seats());
    await page.waitForTimeout(400);
    const later = Number(await seats());
    const events = await seatEvents();
    if (released < 3) return `only reached ${released}: it never repeated`;
    if (later !== released) return `kept going after release, ${released} → ${later}`;
    return events.change === 1 || `one press dispatched change ${events.change} times`;
});

await check('held to the maximum, it stops there and disables +', async () => {
    await seatButton('up').scrollIntoViewIfNeeded();
    const box = await seatButton('up').boundingBox();
    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
    await page.mouse.down();
    await page.waitForTimeout(900);
    await page.mouse.up();
    if ((await seats()) !== '5') return `value is ${await seats()}`;
    return (await seatButton('up').isDisabled()) || '+ is still enabled at the maximum';
});

await check('the native arrow keys still work, and the buttons follow them', async () => {
    await page.focus('#seats');
    await page.keyboard.press('ArrowDown');
    if ((await seats()) !== '4') return `ArrowDown left ${await seats()}`;
    return !(await seatButton('up').isDisabled()) || '+ stayed disabled after the value left the maximum';
});

await check('a press with no pointer, as a screen reader sends it, steps once', async () => {
    await resetSeatEvents();
    await page.evaluate(() => document.querySelector('#seats ~ span [data-ds-step="down"]').click());
    const events = await seatEvents();
    return ((await seats()) === '3' && events.input === 1 && events.change === 1)
        || `value ${await seats()}, dispatched ${JSON.stringify(events)}`;
});

await check('posts the stepped value', async () =>
    JSON.stringify((await posted()).seats) === '["3"]' || `posted ${JSON.stringify((await posted()).seats)}`);

// ---------------------------------------------------------------- password

group('password: reveal (dsPassword) and strength (dsPasswordStrength)');

const revealButton = page.locator('#password ~ button[aria-controls="password"]');

await check('the reveal is a named toggle button that never submits', async () => {
    const a = await revealButton.evaluate((b) => [b.type, b.getAttribute('aria-pressed'), b.getAttribute('aria-label')]);
    return JSON.stringify(a) === '["button","false","Show password"]' || `got ${JSON.stringify(a)}`;
});

await check('pressing it shows the password, says so, and keeps focus and caret in the field', async () => {
    await page.fill('#password', 'abcdef');
    await page.evaluate(() => document.getElementById('password').setSelectionRange(2, 2));
    await revealButton.click();
    const state = await page.evaluate(() => {
        const field = document.getElementById('password');
        return { type: field.type, caret: field.selectionStart, focus: document.activeElement.id };
    });
    if (state.type !== 'text') return `the field is type=${state.type}`;
    if ((await revealButton.getAttribute('aria-pressed')) !== 'true') return 'aria-pressed did not flip';
    if (state.focus !== 'password') return `focus moved to ${state.focus}`;
    return state.caret === 2 || `the caret moved to ${state.caret}`;
});

await check('the icon swaps with it', async () => {
    const shown = await revealButton.evaluate((b) => [...b.querySelectorAll('svg')].map((s) => getComputedStyle(s).display));
    return JSON.stringify(shown) === '["none","block"]' || `icon display ${JSON.stringify(shown)}`;
});

await check('the name stays "Show password": aria-pressed carries the state', async () =>
    (await revealButton.getAttribute('aria-label')) === 'Show password'
    || `renamed to ${await revealButton.getAttribute('aria-label')}`);

await check('pressing again hides it', async () => {
    await revealButton.click();
    return (await page.locator('#password').getAttribute('type')) === 'password'
        && (await revealButton.getAttribute('aria-pressed')) === 'false'
        || 'still showing';
});

await check('submitting the form hides it again', async () => {
    await revealButton.click();
    await page.evaluate(() => {
        const form = document.getElementById('form');
        form.addEventListener('submit', (e) => e.preventDefault(), { once: true });
        form.requestSubmit();
    });
    return (await page.locator('#password').getAttribute('type')) === 'password' || 'submitted as plain text';
});

const strength = () => page.evaluate(() => {
    const root = document.getElementById('strength');
    return {
        level: root.dataset.level,
        words: root.querySelector('[aria-live]').textContent.replace(/\s+/g, ' ').trim(),
    };
});

await check('says nothing until something is typed', async () => {
    await page.fill('#password', '');
    const s = await strength();
    return (s.level === '0' && s.words === 'Password strength:') || `level ${s.level}, reads "${s.words}"`;
});

for (const [typed, level, word] of [
    ['abc', '1', 'Weak'],
    ['P@ssw0rd!', '1', 'Weak'],
    ['aaaaaaaa1', '1', 'Weak'],
    ['Sunflower7', '2', 'Fair'],
    ['Tr0ub4dor&3', '3', 'Good'],
    ['correct horse battery staple', '4', 'Strong'],
]) {
    await check(`"${typed}" scores ${word}, in words a screen reader hears`, async () => {
        await page.fill('#password', typed);
        const s = await strength();
        return (s.level === level && s.words === `Password strength: ${word}`)
            || `level ${s.level}, reads "${s.words}"`;
    });
}

await check('the bars light up to the level', async () => {
    await page.fill('#password', 'Tr0ub4dor&3');
    // The colour transitions, so it is read once the bars have arrived.
    await settle(300);
    const lit = await page.evaluate(() => {
        const bars = [...document.querySelectorAll('#strength [aria-hidden] > span')];
        const off = getComputedStyle(bars[3]).backgroundColor;
        return bars.filter((bar) => getComputedStyle(bar).backgroundColor !== off).length;
    });
    return lit === 3 || `${lit} bars lit for Good`;
});

await check('the live region changes only when the level does, not on every key', async () => {
    await page.fill('#password', '');
    const changes = await page.evaluate(async () => {
        const words = document.querySelector('#strength [data-ds-strength-text]');
        let n = 0;
        const watch = new MutationObserver((m) => { n += m.length; });
        watch.observe(words, { childList: true, characterData: true, subtree: true });
        const field = document.getElementById('password');
        for (const c of 'abcdefg') {
            field.value += c;
            field.dispatchEvent(new Event('input', { bubbles: true }));
        }
        await new Promise((r) => setTimeout(r, 0));
        watch.disconnect();
        return n;
    });
    return changes === 1 || `the region changed ${changes} times for seven keys at one level`;
});

// ---------------------------------------------------------------- x-cloak

/*
 * The rule ships in dist/theme.css, and this page deliberately injects no copy
 * of its own — see where page.html is written.
 *
 * Without it every overlay marked x-cloak renders in full from first paint until
 * Alpine boots and x-show takes over. On the consumer gallery that prompted this
 * it was every modal on the page at once, stacked with their backdrops.
 */
group('[x-cloak] (the rule the package now ships)');

await check('the shipped stylesheet hides a cloaked element', async () => {
    const display = await page.evaluate(() => {
        const probe = document.createElement('div');
        probe.setAttribute('x-cloak', '');
        // `flex` is what the modal root carries, and it sets display at the same
        // specificity — so this probes the rule that has to WIN, not just exist.
        probe.className = 'flex';
        document.body.appendChild(probe);
        const result = getComputedStyle(probe).display;
        probe.remove();
        return result;
    });

    return display === 'none'
        || `a cloaked .flex element computes display:${display} — the shipped rule is missing or is being overridden`;
});

// ---------------------------------------------------------------- announcement bar

const shownAt = (selector) => page.evaluate((s) => {
    const el = document.querySelector(s);
    if (!el) return null;
    const style = getComputedStyle(el);
    return style.display !== 'none' && style.visibility !== 'hidden';
}, selector);

group('announcement bar (dsDismiss)');

await page.evaluate(() => {
    localStorage.removeItem('behaviour-ab');
    document.getElementById('ab-box').scrollIntoView({ block: 'center' });
});

await check('a named region, not a live region; an urgent outage is an alert', async () => {
    const r = await page.evaluate(() => ({
        role: document.getElementById('ab').getAttribute('role'),
        label: document.getElementById('ab').getAttribute('aria-label'),
        live: document.getElementById('ab').getAttribute('aria-live'),
        urgent: document.getElementById('ab-urgent').getAttribute('role'),
    }));
    return (r.role === 'region' && r.label === 'Announcement' && r.live === null && r.urgent === 'alert') || JSON.stringify(r);
});

await check('the close control is shown once the script has started, and only on a dismissible bar', async () => {
    const r = await page.evaluate(() => ({
        ab: getComputedStyle(document.querySelector('#ab [data-ds-dismiss]')).visibility,
        plain: document.querySelector('#ab-plain [data-ds-dismiss]'),
        name: document.querySelector('#ab [data-ds-dismiss]').getAttribute('aria-label'),
    }));
    return (r.ab === 'visible' && r.plain === null && r.name === 'Dismiss') || JSON.stringify(r);
});

await check('the keyboard dismisses it: Enter on the close control collapses it to nothing', async () => {
    await page.focus('#ab [data-ds-dismiss]');
    await page.keyboard.press('Enter');
    await settle(400);
    const r = await page.evaluate(() => {
        const el = document.getElementById('ab');
        return { height: el.getBoundingClientRect().height, visibility: getComputedStyle(el).visibility, state: el.dataset.dsState };
    });
    return (r.height === 0 && r.visibility === 'hidden' && r.state === 'dismissed') || JSON.stringify(r);
});

await check('remembered under its key, and the host hears one `dismiss`', async () => {
    const r = await page.evaluate(() => ({ stored: localStorage.getItem('behaviour-ab'), events: window.abDismissed }));
    return (r.stored === '1' && r.events === 1) || JSON.stringify(r);
});

await check('collapsed means gone for the keyboard too: nothing in it takes focus', async () => {
    await page.focus('#ab-box');
    const reachable = await page.evaluate(() => {
        const link = document.querySelector('#ab a[href]');
        link.focus();
        return document.activeElement === link;
    });
    return reachable === false || 'the link in a dismissed bar still takes focus';
});

await check('a button in the action slot marked data-ds-dismiss dismisses too, with no key to remember', async () => {
    await page.click('#ab-got');
    await settle(400);
    const r = await page.evaluate(() => ({
        height: document.getElementById('ab-action').getBoundingClientRect().height,
        keys: Object.keys(localStorage).filter((k) => !k.startsWith('behaviour-ab')),
    }));
    return (r.height === 0 && !r.keys.length) || JSON.stringify(r);
});

await check('with motion allowed it collapses over the dialog-leave token', async () => {
    const r = await page.evaluate(() => {
        const s = getComputedStyle(document.getElementById('ab-plain'));
        return { property: s.transitionProperty, duration: s.transitionDuration };
    });
    return (r.property.includes('grid-template-rows') && r.duration.split(',')[0].trim() === '0.15s') || JSON.stringify(r);
});

await check('with reduced motion there is no transition: it is simply gone', async () => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    const duration = await page.evaluate(() => getComputedStyle(document.getElementById('ab-plain')).transitionDuration);
    await page.emulateMedia({ reducedMotion: 'no-preference' });
    return duration === '0s' || `transition-duration ${duration}`;
});

// ---------------------------------------------------------------- confirm button

const cbAsk = (id) => `[data-ds-confirm]:has(#${id}) [data-ds-confirm-ask]`;
const cbTrigger = (id) => `[data-ds-confirm]:has(#${id}) [data-ds-confirm-trigger]`;
const isActive = (selector) => page.evaluate((s) => document.activeElement === document.querySelector(s), selector);

group('confirm button (dsConfirm)');

await page.evaluate(() => document.getElementById('cb-form').scrollIntoView({ block: 'center' }));
await settle(100);

await check('at rest: the button alone, and the confirm control not on screen to press', async () => {
    const r = await page.evaluate(([ask, trigger]) => ({
        ask: getComputedStyle(document.querySelector(ask)).display,
        trigger: getComputedStyle(document.querySelector(trigger)).display,
        control: document.getElementById('cb').getClientRects().length,
        type: document.querySelector(trigger).type,
    }), [cbAsk('cb'), cbTrigger('cb')]);
    return (r.ask === 'none' && r.trigger !== 'none' && r.control === 0 && r.type === 'button') || JSON.stringify(r);
});

let cbEdge = null;
let cbHeight = null;
await check('a press asks in place: the question, focus on Cancel, and the question said aloud', async () => {
    const before = await page.evaluate(() => {
        const r = document.querySelector('[data-ds-confirm]:has(#cb)').getBoundingClientRect();
        return { right: r.right, height: r.height };
    });
    cbEdge = before.right;
    cbHeight = before.height;
    await page.click(cbTrigger('cb'));
    await settle(250);
    const r = await page.evaluate(() => ({
        focus: document.activeElement?.textContent.trim(),
        live: document.querySelector('[data-ds-confirm]:has(#cb) [aria-live]').textContent,
        shown: document.getElementById('cb').getClientRects().length > 0,
    }));
    return (r.focus === 'Cancel' && r.live === 'Delete this report?' && r.shown) || JSON.stringify(r);
});

await check('in an end-aligned row it grows into the free space: its end edge and its height stay put', async () => {
    const r = await page.evaluate(() => {
        const b = document.querySelector('[data-ds-confirm]:has(#cb)').getBoundingClientRect();
        return { right: b.right, height: b.height, width: b.width };
    });
    return (Math.abs(r.right - cbEdge) < 0.5 && Math.abs(r.height - cbHeight) < 0.5) || `${JSON.stringify(r)} vs right ${cbEdge}, height ${cbHeight}`;
});

await check('Escape puts it back, gives focus to the button, and dispatches `cancel`', async () => {
    await page.keyboard.press('Escape');
    await settle(150);
    const shown = await shownAt(cbAsk('cb'));
    if (shown) return 'still asking';
    const cancelled = await page.evaluate(() => window.cbCancelled);
    return ((await isActive(cbTrigger('cb'))) && cancelled === 1) || `focus on ${await active()}, ${cancelled} cancel events`;
});

await check('Cancel does the same', async () => {
    await page.click(cbTrigger('cb'));
    await settle(200);
    await page.keyboard.press('Enter');
    await settle(150);
    if (await shownAt(cbAsk('cb'))) return 'still asking';
    const cancelled = await page.evaluate(() => window.cbCancelled);
    return ((await isActive(cbTrigger('cb'))) && cancelled === 2) || `focus on ${await active()}, ${cancelled} cancel events`;
});

await check('confirming dispatches `confirm` and submits the form, with its own name and value, once', async () => {
    await page.click(cbTrigger('cb'));
    await settle(200);
    await page.click('#cb');
    await settle(200);
    const r = await page.evaluate(() => ({ sent: window.cbSent, confirmed: window.cbConfirmed, cancelled: window.cbCancelled }));
    return (JSON.stringify(r.sent) === '["intent=delete"]' && r.confirmed === 1 && r.cancelled === 2) || JSON.stringify(r);
});

await check('and without `loading` it is back at rest, focus on the button', async () => {
    const shown = await shownAt(cbAsk('cb'));
    return (!shown && (await isActive(cbTrigger('cb')))) || `asking ${shown}, focus on ${await active()}`;
});

await check('`timeout`: left alone, it puts itself away, says `cancel`, and moves no focus', async () => {
    await page.click(cbTrigger('cb-busy'));
    await settle(150);
    await page.click('#cb-away');
    await page.mouse.move(2, 2);
    await settle(1300);
    const r = await page.evaluate(() => ({ cancelled: window.cbBusyCancelled, focus: document.activeElement?.id }));
    const shown = await shownAt(cbAsk('cb-busy'));
    return (!shown && r.cancelled === 1 && r.focus === 'cb-away') || `asking ${shown}, ${JSON.stringify(r)}`;
});

await check('`timeout` waits while focus is inside it', async () => {
    await page.click(cbTrigger('cb-busy'));
    await settle(150);
    await page.mouse.move(2, 2);
    await settle(1300);
    const shown = await shownAt(cbAsk('cb-busy'));
    return shown || 'it timed out under focus';
});

await check('`timeout` waits while the pointer is over it, and runs again once both have left', async () => {
    const box = await page.locator(cbAsk('cb-busy')).boundingBox();
    await page.mouse.move(box.x + 4, box.y + box.height / 2);
    await page.evaluate(() => document.getElementById('cb-away').focus());
    await settle(1300);
    const held = await shownAt(cbAsk('cb-busy'));
    await page.mouse.move(2, 2);
    await settle(1300);
    const after = await shownAt(cbAsk('cb-busy'));
    return (held && !after) || `under the pointer: ${held ? 'asking' : 'gone'}; after: ${after ? 'still asking' : 'gone'}`;
});

await check('the confirm control carries the host @click; the first press never runs it', async () => {
    const before = await page.evaluate(() => window.cbClicks ?? 0);
    await page.click(cbTrigger('cb-busy'));
    await settle(150);
    const afterAsk = await page.evaluate(() => window.cbClicks ?? 0);
    await page.click('#cb-busy');
    await settle(100);
    const afterConfirm = await page.evaluate(() => window.cbClicks ?? 0);
    return (before === 0 && afterAsk === 0 && afterConfirm === 1) || `clicks: ${before} at rest, ${afterAsk} after asking, ${afterConfirm} after confirming`;
});

await check('`loading`: a spinner, aria-busy, Cancel disabled, and a second press goes nowhere', async () => {
    await page.click('#cb-busy', { force: true });
    await settle(100);
    const r = await page.evaluate(() => {
        const control = document.getElementById('cb-busy');
        return {
            clicks: window.cbClicks,
            busy: control.getAttribute('aria-busy'),
            spinner: getComputedStyle(control.querySelector('svg')).display,
            cancel: document.querySelector('[data-ds-confirm]:has(#cb-busy) [data-ds-confirm-cancel]').disabled,
        };
    });
    return (r.clicks === 1 && r.busy === 'true' && r.spinner !== 'none' && r.cancel) || JSON.stringify(r);
});

await check('`loading`: neither Escape nor the timeout puts it back while it works', async () => {
    await page.focus('#cb-busy');
    await page.keyboard.press('Escape');
    await page.mouse.move(2, 2);
    await page.click('#cb-away');
    await settle(1300);
    return (await shownAt(cbAsk('cb-busy'))) || 'it went back to rest mid-action';
});

await check('a page restored from the back-forward cache comes back at rest', async () => {
    await page.evaluate(() => window.dispatchEvent(new PageTransitionEvent('pageshow', { persisted: true })));
    await settle(100);
    const r = await page.evaluate(() => document.getElementById('cb-busy').getAttribute('aria-busy'));
    const shown = await shownAt(cbAsk('cb-busy'));
    return (!shown && r === null) || `asking ${shown}, aria-busy ${r}`;
});

await check('Escape at rest is not swallowed: the host around it still hears it', async () => {
    const before = Number(await page.textContent('#cb-outer-escapes'));
    await page.focus(cbTrigger('cb-busy'));
    await page.keyboard.press('Escape');
    const after = Number(await page.textContent('#cb-outer-escapes'));
    return after === before + 1 || `the host heard ${after - before} of one Escape`;
});

await check('timeout 0 never times out', async () => {
    await page.click(cbTrigger('cb-still'));
    await page.click('#cb-away');
    await page.mouse.move(2, 2);
    await settle(5300);
    const shown = await shownAt(cbAsk('cb-still'));
    await page.focus('#cb-still');
    await page.keyboard.press('Escape');
    return shown || 'it timed out';
});

await check('with reduced motion the question appears at once', async () => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.click(cbTrigger('cb-still'));
    await settle(30);
    const r = await page.evaluate((s) => {
        const st = getComputedStyle(document.querySelector(s));
        return { duration: st.transitionDuration, opacity: st.opacity };
    }, cbAsk('cb-still'));
    await page.keyboard.press('Escape');
    await page.emulateMedia({ reducedMotion: 'no-preference' });
    return (r.duration === '0s' && r.opacity === '1') || JSON.stringify(r);
});

// ---------------------------------------------------------------- hold button

const rest = (id) => page.evaluate((i) => document.getElementById(i).style.getPropertyValue('--ds-hold-rest') || '100%', id);
const holdOn = async (id, ms) => {
    const box = await page.locator(`#${id}`).boundingBox();
    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
    await page.mouse.down();
    await settle(ms);
    await page.mouse.up();
};
const armedText = (id) => page.evaluate((i) => document.querySelector(`span:has(> #${i}) > [aria-live]`).textContent, id);

group('hold button (dsHold)');

await page.evaluate(() => document.getElementById('hb-form').scrollIntoView({ block: 'center' }));
await settle(100);

await check('rendered type=button, named by its instruction', async () => {
    const r = await page.evaluate(() => {
        const b = document.getElementById('hb');
        return { type: b.type, name: b.innerText.split('\n')[0].trim() };
    });
    return (r.type === 'button' && r.name === 'Hold to delete') || JSON.stringify(r);
});

await check('let go early: nothing happens, and it rewinds', async () => {
    await holdOn('hb', 500);
    const mid = await rest('hb');
    await settle(500);
    const r = await page.evaluate(() => ({ sent: window.hbSent, confirmed: window.hbConfirmed }));
    const after = await rest('hb');
    return (!r.sent && !r.confirmed && parseFloat(mid) < 100 && after === '100.00%') || `${JSON.stringify(r)}, rest ${mid} then ${after}`;
});

await check('two quick clicks of a mouse do nothing', async () => {
    await page.click('#hb');
    await page.click('#hb');
    await settle(300);
    const r = await page.evaluate(() => ({ sent: window.hbSent, confirmed: window.hbConfirmed }));
    return (!r.sent && !r.confirmed) || JSON.stringify(r);
});

await check('sliding off the button while holding rewinds it', async () => {
    const box = await page.locator('#hb').boundingBox();
    await page.mouse.move(box.x + 10, box.y + box.height / 2);
    await page.mouse.down();
    await settle(400);
    await page.mouse.move(box.x + 10, box.y + box.height + 40, { steps: 3 });
    await settle(1400);
    await page.mouse.up();
    await settle(100);
    const r = await page.evaluate(() => ({ sent: window.hbSent, confirmed: window.hbConfirmed }));
    return (!r.sent && !r.confirmed && (await rest('hb')) === '100.00%') || `${JSON.stringify(r)}, rest ${await rest('hb')}`;
});

await check('held for the duration: `confirm`, then the form submits with its name and value, once', async () => {
    await holdOn('hb', 1800);
    await settle(150);
    const r = await page.evaluate(() => ({ sent: window.hbSent, confirmed: window.hbConfirmed, type: document.getElementById('hb').type }));
    return (JSON.stringify(r.sent) === '["intent=delete"]' && r.confirmed === 1 && r.type === 'button') || JSON.stringify(r);
});

await check('a submitted one stays full and refuses another hold until the page comes back', async () => {
    const full = await rest('hb');
    await holdOn('hb', 1800);
    const sent = await page.evaluate(() => window.hbSent.length);
    await page.evaluate(() => window.dispatchEvent(new PageTransitionEvent('pageshow', { persisted: true })));
    await settle(50);
    const restored = await rest('hb');
    return (full === '0.00%' && sent === 1 && restored === '100.00%') || `full ${full}, sent ${sent}, restored ${restored}`;
});

await check('the host @click runs on confirmation only', async () => {
    await page.click('#hb-quick');
    await settle(200);
    const quick = await page.evaluate(() => window.hbClicks ?? 0);
    await holdOn('hb-quick', 900);
    await settle(100);
    const held = await page.evaluate(() => ({ clicks: window.hbClicks, confirmed: window.hbQuickConfirmed }));
    return (quick === 0 && held.clicks === 1 && held.confirmed === 1) || `after a click ${quick}; after a hold ${JSON.stringify(held)}`;
});

await check('Space held for the duration confirms', async () => {
    await settle(300);
    await page.focus('#hb-quick');
    await page.keyboard.down(' ');
    await settle(900);
    await page.keyboard.up(' ');
    await settle(100);
    const r = await page.evaluate(() => ({ clicks: window.hbClicks, confirmed: window.hbQuickConfirmed }));
    return (r.clicks === 2 && r.confirmed === 2) || JSON.stringify(r);
});

await check('a key pressed and let go early arms it: the words change, and are said aloud', async () => {
    await settle(300);
    await page.keyboard.press('Enter');
    await settle(100);
    const r = await page.evaluate(() => {
        const faces = [...document.querySelectorAll('#hb-quick > .grid > span')];
        return { shown: faces.find((f) => getComputedStyle(f).visibility === 'visible')?.textContent, clicks: window.hbClicks };
    });
    const said = await armedText('hb-quick');
    return (r.shown === 'Press again to confirm' && said === 'Press again to confirm' && r.clicks === 2) || `${JSON.stringify(r)}, said ${JSON.stringify(said)}`;
});

await check('the next press confirms', async () => {
    await page.keyboard.press('Enter');
    await settle(100);
    const r = await page.evaluate(() => ({ clicks: window.hbClicks, confirmed: window.hbQuickConfirmed }));
    const said = await armedText('hb-quick');
    return (r.clicks === 3 && r.confirmed === 3 && said === '') || `${JSON.stringify(r)}, said ${JSON.stringify(said)}`;
});

await check('Escape disarms, and so does focus leaving', async () => {
    await settle(300);
    await page.keyboard.press(' ');
    await page.keyboard.press('Escape');
    await page.keyboard.press(' ');
    await settle(100);
    const afterEscape = await page.evaluate(() => window.hbClicks);
    await page.keyboard.press('Tab');
    await page.focus('#hb-quick');
    await page.keyboard.press(' ');
    await settle(100);
    const afterBlur = await page.evaluate(() => window.hbClicks);
    await page.keyboard.press('Escape');
    return (afterEscape === 3 && afterBlur === 3) || `clicks after Escape ${afterEscape}, after focus left ${afterBlur}`;
});

await check('assistive technology: a click no press began arms it, and the next one confirms', async () => {
    await page.evaluate(() => document.getElementById('hb-quick').click());
    await settle(50);
    const armed = await armedText('hb-quick');
    const once = await page.evaluate(() => window.hbClicks);
    await page.evaluate(() => document.getElementById('hb-quick').click());
    await settle(100);
    const twice = await page.evaluate(() => window.hbClicks);
    return (armed === 'Press again to confirm' && once === 3 && twice === 4) || `armed ${JSON.stringify(armed)}, clicks ${once} then ${twice}`;
});

await check('a form that would refuse the submit: nothing confirmed, and the browser says why', async () => {
    await holdOn('hb-invalid', 700);
    await settle(100);
    const r = await page.evaluate(() => ({
        sent: window.hbRequiredSent ?? false,
        confirmed: window.hbInvalidConfirmed ?? false,
        focus: document.activeElement?.id,
    }));
    return (!r.sent && !r.confirmed && r.focus === 'hb-required') || JSON.stringify(r);
});

await check('with reduced motion the fill moves in quarters, not a sweep', async () => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    const box = await page.locator('#hb-quick').boundingBox();
    await settle(300);
    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
    await page.mouse.down();
    const seen = new Set();
    for (let i = 0; i < 10; i++) {
        seen.add(await rest('hb-quick'));
        await settle(40);
    }
    await page.mouse.up();
    await settle(50);
    const after = await rest('hb-quick');
    await page.emulateMedia({ reducedMotion: 'no-preference' });
    const allowed = ['100.00%', '75.00%', '50.00%', '25.00%', '100%'];
    const odd = [...seen].filter((v) => !allowed.includes(v));
    return (!odd.length && seen.size > 1 && after === '100.00%') || `values ${[...seen].join(', ')}, then ${after}`;
});

// ---------------------------------------------------------------- confirm popover

const cpPanel = (trigger) => `[data-ds-popover]:has(#${trigger}) [data-ds-popover-panel]`;
const cpShown = (trigger) => shownAt(cpPanel(trigger));

group('confirm popover (dsConfirmDialog, on dsPopover)');

await page.evaluate(() => document.getElementById('cp-form').scrollIntoView({ block: 'center' }));
await settle(100);

await check('an alertdialog, named by its title and described by its consequence', async () => {
    const r = await page.evaluate((p) => {
        const panel = document.querySelector(p);
        const text = (attr) => document.getElementById(panel.getAttribute(attr))?.textContent.trim();
        return { role: panel.getAttribute('role'), name: text('aria-labelledby'), description: text('aria-describedby'), modal: panel.getAttribute('aria-modal') };
    }, cpPanel('cp-trigger'));
    return (r.role === 'alertdialog' && r.name === 'Delete this report?' && r.description === 'Its 12 runs go with it.' && r.modal === null) || JSON.stringify(r);
});

await check('opening puts focus on Cancel, the safe choice', async () => {
    await page.click('#cp-trigger');
    await settle(250);
    if (!(await cpShown('cp-trigger'))) return 'did not open';
    return (await page.evaluate(() => document.activeElement?.textContent.trim())) === 'Cancel' || `focus on ${await active()}`;
});

await check('Escape closes it, dispatches `cancel`, and gives focus back to the trigger', async () => {
    await page.keyboard.press('Escape');
    await settle(250);
    const r = await page.evaluate(() => ({ cancelled: window.cpCancelled, confirmed: window.cpConfirmed }));
    if (await cpShown('cp-trigger')) return 'still open';
    return (r.cancelled === 1 && !r.confirmed && (await active()) === 'cp-trigger') || `${JSON.stringify(r)}, focus on ${await active()}`;
});

await check('a press outside cancels too', async () => {
    await page.click('#cp-trigger');
    await settle(250);
    await page.click('#cp-away');
    await settle(250);
    const cancelled = await page.evaluate(() => window.cpCancelled);
    return (!(await cpShown('cp-trigger')) && cancelled === 2) || `cancel events ${cancelled}`;
});

await check('Cancel cancels, focus back to the trigger', async () => {
    await page.click('#cp-trigger');
    await settle(250);
    await page.keyboard.press('Enter');
    await settle(250);
    const cancelled = await page.evaluate(() => window.cpCancelled);
    return (!(await cpShown('cp-trigger')) && cancelled === 3 && (await active()) === 'cp-trigger') || `cancel events ${cancelled}, focus on ${await active()}`;
});

await check('confirming dispatches `confirm`, submits with its name and value, and closes without a `cancel`', async () => {
    await page.click('#cp-trigger');
    await settle(250);
    await page.click('#cp');
    await settle(250);
    const r = await page.evaluate(() => ({ sent: window.cpSent, confirmed: window.cpConfirmed, cancelled: window.cpCancelled }));
    if (await cpShown('cp-trigger')) return 'still open';
    return (JSON.stringify(r.sent) === '["intent=delete"]' && r.confirmed === 1 && r.cancelled === 3 && (await active()) === 'cp-trigger')
        || `${JSON.stringify(r)}, focus on ${await active()}`;
});

await check('type to confirm: focus in the field, and the confirm control disabled', async () => {
    await page.click('#cp-typed-trigger');
    await settle(250);
    const r = await page.evaluate(() => ({
        focus: document.activeElement?.matches('[data-ds-confirm-field]'),
        disabled: document.getElementById('cp-typed').disabled,
        name: document.activeElement?.name,
        label: document.activeElement?.labels?.[0]?.textContent.replace(/\s+/g, ' ').trim(),
    }));
    return (r.focus && r.disabled && !r.name && r.label === 'Type acme-prod to confirm') || JSON.stringify(r);
});

await check('the wrong case, or a near miss, keeps it disabled; Enter then submits nothing at all', async () => {
    await page.keyboard.type('Acme-prod');
    const wrongCase = await page.evaluate(() => document.getElementById('cp-typed').disabled);
    await page.keyboard.press('Enter');
    await settle(100);
    const sent = await page.evaluate(() => window.cpSent.length);
    await page.fill('[data-ds-popover]:has(#cp-typed-trigger) [data-ds-confirm-field]', 'acme-pro');
    const nearMiss = await page.evaluate(() => document.getElementById('cp-typed').disabled);
    return (wrongCase && nearMiss && sent === 1) || `wrong case disabled ${wrongCase}, near miss disabled ${nearMiss}, sent ${sent}`;
});

await check('the exact text enables it, and Enter confirms', async () => {
    await page.keyboard.type('d');
    const enabled = await page.evaluate(() => !document.getElementById('cp-typed').disabled);
    await page.keyboard.press('Enter');
    await settle(250);
    const sent = await page.evaluate(() => window.cpSent);
    return (enabled && JSON.stringify(sent) === '["intent=delete","intent=delete-project"]' && !(await cpShown('cp-typed-trigger')))
        || `enabled ${enabled}, sent ${JSON.stringify(sent)}`;
});

await check('opened again, it starts afresh: the field empty, the control disabled', async () => {
    await page.click('#cp-typed-trigger');
    await settle(250);
    const r = await page.evaluate(() => ({
        value: document.querySelector('[data-ds-popover]:has(#cp-typed-trigger) [data-ds-confirm-field]').value,
        disabled: document.getElementById('cp-typed').disabled,
    }));
    await page.keyboard.press('Escape');
    await settle(250);
    return (r.value === '' && r.disabled) || JSON.stringify(r);
});

await check('strict off: letter case and the spaces around it do not matter', async () => {
    await page.click('#cp-loose-trigger');
    await settle(250);
    await page.keyboard.type('  lEAVE ');
    const enabled = await page.evaluate(() => !document.getElementById('cp-loose').disabled);
    await page.keyboard.press('Enter');
    await settle(250);
    const confirmed = await page.evaluate(() => window.cpLooseConfirmed);
    return (enabled && confirmed === 1) || `enabled ${enabled}, confirmed ${confirmed}`;
});

await check('`loading`: it stays open with a spinner, and a second press goes nowhere', async () => {
    await page.click('#cp-busy-trigger');
    await settle(250);
    await page.click('#cp-busy');
    await page.click('#cp-busy', { force: true });
    await settle(100);
    const r = await page.evaluate(() => ({
        clicks: window.cpBusyClicks,
        busy: document.getElementById('cp-busy').getAttribute('aria-busy'),
        spinner: getComputedStyle(document.querySelector('#cp-busy svg')).display,
    }));
    const open = await cpShown('cp-busy-trigger');
    await page.keyboard.press('Escape');
    await settle(250);
    return (open && r.clicks === 1 && r.busy === 'true' && r.spinner !== 'none') || `${JSON.stringify(r)}, open ${open}`;
});

await check('closed, the busy state is gone', async () => {
    await page.click('#cp-busy-trigger');
    await settle(250);
    const busy = await page.evaluate(() => document.getElementById('cp-busy').getAttribute('aria-busy'));
    await page.keyboard.press('Escape');
    await settle(250);
    return busy === null || `aria-busy ${busy}`;
});

// ---------------------------------------------------------------- overlay edges

/*
  Whether an overlay collides with the viewport, ENFORCED for every overlay,
  all of them placed by x-ds-anchor. Until batch 06 the five that predate it
  were only REPORTED here, as "runs N px below the fold", because each took
  a placement and trusted it. Each is now opened at the bottom right of the
  window, inside a box with overflow hidden, and must come back wholly on
  screen (less the anchor's 8px margin), flipped away from the edge, and
  painted rather than clipped.

  Run from a bottom-of-the-window start, not the floor this page happens to
  have: the trigger is scrolled to the bottom edge, which any element deep in
  the page can reach.
*/
group('overlay edges, enforced (every overlay, on x-ds-anchor)');

const pressEscape = async () => {
    await page.keyboard.press('Escape');
};

/*
  Each entry: the trigger, the panel, how to open and close it, and the side
  it must have flipped to. `trigger` is also the reference the panel is
  measured against unless `reference` names another.
*/
const anchored = [
    {
        name: 'popover', trigger: '#pop-edge-trigger', panel: '#pop-edge',
        open: () => page.click('#pop-edge-trigger'), close: pressEscape,
    },
    {
        name: 'confirm popover', trigger: '#cp-edge-trigger', panel: '[data-ds-popover]:has(#cp-edge-trigger) [data-ds-popover-panel]',
        open: () => page.click('#cp-edge-trigger'), close: pressEscape,
    },
    {
        name: 'hover card', trigger: '#hc-edge-trigger', panel: '#hc-edge',
        open: () => page.hover('#hc-edge-trigger'),
        close: async () => {
            await page.keyboard.press('Escape');
            await page.mouse.move(5, 5);
        },
    },
    {
        name: 'dropdown', trigger: '#dd-edge-trigger', panel: '#dd-edge [role=menu]',
        open: () => page.click('#dd-edge-trigger'), close: pressEscape,
    },
    {
        name: 'select', trigger: '#sel-edge', panel: '#sel-edge-listbox',
        open: () => page.click('#sel-edge'), close: pressEscape,
    },
    {
        name: 'combobox', trigger: '#cb-edge', panel: '#cb-edge-listbox',
        open: () => page.click('#cb-edge'), close: pressEscape,
    },
    {
        name: 'date picker', trigger: '#dp-edge', panel: '[x-data]:has(> #dp-edge) [role=dialog]',
        open: () => page.click('#dp-edge'), close: pressEscape,
    },
    {
        name: 'tooltip', trigger: '#tt-edge-trigger', panel: '[data-ds-tooltip]:has(#tt-edge-trigger) [role=tooltip]',
        open: () => page.hover('#tt-edge-trigger'),
        close: async () => {
            await page.keyboard.press('Escape');
            await page.mouse.move(5, 5);
        },
    },
];

/*
  The other half: with room, an overlay moved onto the anchor must land where
  its absolute classes put it in 1.x, so nothing a reader sees has moved. The
  panel is measured as placed, then again with the anchor's inline placement
  taken off, in the same frame. Half a pixel is the anchor's rounding to whole
  device pixels.
*/
const roomy = [
    { name: 'dropdown', trigger: '#menu-trigger', panel: '[data-ds-dropdown]:has(#menu-trigger) [role=menu]' },
    { name: 'select', trigger: '#plan', panel: '#plan-listbox' },
    { name: 'combobox', trigger: '#tags', panel: '#tags-listbox' },
    { name: 'date picker', trigger: '#period', panel: '[x-data]:has(> #period) [role=dialog]' },
    {
        name: 'tooltip', trigger: '#tip-trigger', panel: '[data-ds-tooltip]:has(#tip-trigger) [role=tooltip]',
        open: () => page.focus('#tip-trigger'), close: pressEscape,
    },
];

const PLACED = ['position', 'left', 'top', 'right', 'bottom', 'margin', 'translate', 'width', 'maxHeight', 'overflowY'];

for (const { name, trigger, panel, open, close } of roomy) {
    await check(`${name}: with room, it opens exactly where its fallback classes put it`, async () => {
        await page.evaluate((s) => document.querySelector(s).scrollIntoView({ block: 'center' }), trigger);
        await settle(150);
        await (open ? open() : page.click(trigger));
        await settle(400);
        const r = await page.evaluate(([p, props]) => {
            const el = document.querySelector(p);
            const placed = el.getBoundingClientRect();
            const style = el.getAttribute('style');
            for (const prop of props) el.style[prop] = '';
            const fallback = el.getBoundingClientRect();
            el.setAttribute('style', style);
            return {
                anchored: el.style.position === 'fixed',
                off: Math.max(...['left', 'top', 'width', 'height'].map((k) => Math.abs(placed[k] - fallback[k]))),
            };
        }, [panel, PLACED]);
        await (close ? close() : pressEscape());
        await settle(250);
        if (!r.anchored) return 'the anchor never placed it, so this proves nothing';
        return r.off <= 0.5 || `${r.off.toFixed(2)}px from where its fallback puts it`;
    });
}

for (const { name, trigger, reference, panel, open, close, side = 'top' } of anchored) {
    await check(`${name}: at the bottom right of the window, clipped box and all, it stays on screen`, async () => {
        await page.evaluate((s) => document.querySelector(s).scrollIntoView({ block: 'end', inline: 'end' }), trigger);
        await settle(150);
        await open();
        await settle(700);
        const b = await anchorBoxes(panel, reference ?? trigger);
        await close();
        await settle(350);
        if (!b.shown) return 'it did not open';
        if (b.side !== side) return `data-side is ${b.side}: it did not flip to ${side}`;
        if (!b.onTop) return 'something else is painted where it is: clipped';
        return insideViewport(b);
    });
}

/*
  The second half of what the anchor was for: a combobox in a modal whose body
  scrolls. The body is overflow-y auto, so before the anchor a list opening
  below a field at the bottom of the body was cut off at the body's edge, with
  the modal's footer painted where the options should be.
*/
group('combobox in a scrolling modal body (overflow-y-auto, x-ds-anchor)');
{
    const listbox = '#cb-modal-listbox';
    const bodyBox = () => page.evaluate(() => {
        const r = document.getElementById('cb-modal-spacer').parentElement.getBoundingClientRect();
        return { top: r.top, bottom: r.bottom };
    });

    await page.evaluate(() => document.getElementById('cb-modal-open').scrollIntoView({ block: 'center' }));
    await page.click('#cb-modal-open');
    await settle(450);
    // The field at the very bottom of the body's visible area, so the room
    // below it is the footer's and the window's gutter, nothing more.
    await page.evaluate(() => document.getElementById('cb-modal').scrollIntoView({ block: 'end' }));
    await settle(150);

    await check('the body really scrolls, and the field sits at the bottom of it', async () => {
        const r = await page.evaluate(() => {
            const body = document.getElementById('cb-modal-spacer').parentElement;
            const field = document.getElementById('cb-modal').getBoundingClientRect();
            return { scrolls: body.scrollHeight > body.clientHeight, gap: Math.round(body.getBoundingClientRect().bottom - field.bottom) };
        });
        return (r.scrolls && r.gap >= 0 && r.gap <= 8) || JSON.stringify(r);
    });

    await check('with every option, the list flips above the field and stays on screen', async () => {
        await page.click('#cb-modal');
        await settle(400);
        const b = await anchorBoxes(listbox, '#cb-modal');
        if (!b.shown) return 'it did not open';
        if (b.side !== 'top') return `data-side is ${b.side}`;
        if (!b.onTop) return 'something else is painted where it is';
        return insideViewport(b);
    });

    await check('filtered to one option, it opens below, over the body\'s clipped edge, and is painted there', async () => {
        await page.fill('#cb-modal', 'comp');
        await settle(300);
        const b = await anchorBoxes(listbox, '#cb-modal');
        const body = await bodyBox();
        if (b.side !== 'bottom') return `data-side is ${b.side}: there was room below for one option`;
        if (b.panel.bottom <= body.bottom) return 'the list ends inside the body, so this proves nothing';
        return b.onTop || 'the body clipped it: the footer is painted where the option is';
    });

    await check('follows the field as the body scrolls under it', async () => {
        await page.evaluate(() => { document.getElementById('cb-modal-spacer').parentElement.scrollTop += 60; });
        await settle(150);
        const b = await anchorBoxes(listbox, '#cb-modal');
        const field = await page.evaluate(() => document.getElementById('cb-modal').parentElement.getBoundingClientRect().bottom);
        const gap = Math.round(b.panel.top - field);
        if (b.side !== 'bottom') return `data-side is ${b.side}`;
        return gap === 4 || `${gap}px below the field after scrolling the body`;
    });

    await page.fill('#cb-modal', '');
    await page.keyboard.press('Escape');
    await settle(150);
    await page.click('#cb-modal-close');
    await settle(400);
    await check('closing the modal leaves nothing open behind it', async () =>
        !(await page.locator(listbox).isVisible()) || 'the list is still showing');
}

// ---------------------------------------------------------------- context menu

group('context menu (dsContextMenu, x-ds-anchor at a point)');

{
    const panel = '#cm [data-ds-context-menu-panel]';
    const wide = '#cm-wide [data-ds-context-menu-panel]';
    const menuShown = (sel = panel) => page.locator(sel).isVisible();
    const box = (sel) => page.evaluate((q) => {
        const r = document.querySelector(q).getBoundingClientRect();
        return { left: r.left, top: r.top, right: r.right, bottom: r.bottom };
    }, sel);
    const closeAll = async () => {
        if (await menuShown()) await page.keyboard.press('Escape');
        if (await menuShown(wide)) await page.keyboard.press('Escape');
        await settle(150);
    };

    // Whether anything in the page asked the browser not to show its menu,
    // read after every handler has had its turn.
    await page.evaluate(() => {
        window.addEventListener('contextmenu', (e) => { window.cmPrevented = e.defaultPrevented; });
    });

    await page.locator('#cm').scrollIntoViewIfNeeded();
    await settle(100);

    await check('closed until asked, and the region renders its content', async () =>
        (!(await menuShown()) && (await page.locator('#cm-text').isVisible())) || 'the menu shows before anything opened it');

    const text = await box('#cm-text');
    const px = Math.round(text.left + 40);
    const py = Math.round(text.top + 5);
    await page.mouse.click(px, py, { button: 'right' });
    await settle(250);

    await check('a right-click opens it, with the browser\'s menu prevented', async () =>
        ((await menuShown()) && (await page.evaluate(() => window.cmPrevented)) === true)
        || `shown ${await menuShown()}, prevented ${await page.evaluate(() => window.cmPrevented)}`);

    await check('at the pointer: its top-left corner beside the point pressed', async () => {
        const p = await box(panel);
        const dx = Math.round(p.left - px);
        const dy = Math.round(p.top - py);
        return (dx >= 0 && dx <= 1 && dy >= 1 && dy <= 3) || `${dx}px across and ${dy}px down from the pointer`;
    });

    await check('role=menu, named, and focus on the first item', async () => {
        const a = await page.evaluate((q) => {
            const el = document.querySelector(q);
            return { role: el.getAttribute('role'), name: el.getAttribute('aria-label'), orientation: el.getAttribute('aria-orientation') };
        }, panel);
        const on = await active();
        return (a.role === 'menu' && a.name === 'Report actions' && a.orientation === 'vertical' && on === 'cm-edit')
            || `${JSON.stringify(a)}, focus on ${on}`;
    });

    await check('the dropdown\'s keys: arrows skip the disabled item and wrap, Home and End', async () => {
        const seen = [];
        for (const key of ['ArrowDown', 'ArrowDown', 'ArrowUp', 'End', 'Home']) {
            await page.keyboard.press(key);
            seen.push(await active());
        }
        return seen.join() === 'cm-delete,cm-edit,cm-delete,cm-delete,cm-edit' || seen.join(' → ');
    });

    await check('a right-click inside the menu keeps it open and the browser\'s menu away', async () => {
        const item = await box('#cm-delete');
        await page.mouse.click(item.left + 10, item.top + 5, { button: 'right' });
        await settle(100);
        return ((await menuShown()) && (await page.evaluate(() => window.cmPrevented)) === true) || 'it closed, or the browser menu was allowed';
    });

    await check('a second right-click in the region moves it there, focus on the first item', async () => {
        await page.mouse.click(px + 120, py, { button: 'right' });
        await settle(200);
        const p = await box(panel);
        return ((await menuShown()) && Math.round(p.left - (px + 120)) <= 1 && (await active()) === 'cm-edit')
            || `left ${Math.round(p.left)}, expected ${px + 120}; focus on ${await active()}`;
    });

    await check('Escape closes it', async () => {
        await page.keyboard.press('Escape');
        await settle(200);
        return !(await menuShown()) || 'still open';
    });

    // The row takes focus from the press itself, so it is what had focus.
    const row = await box('#cm-row');
    await page.mouse.click(row.left + 20, row.top + 5, { button: 'right' });
    await settle(200);
    await check('on a focusable row: Escape gives focus back to the row', async () => {
        if (!(await menuShown())) return 'did not open';
        await page.keyboard.press('Escape');
        await settle(200);
        return (await active()) === 'cm-row' || `focus on ${await active()}`;
    });

    await check('Shift+F10 on the focused row opens it below the row, on its start edge', async () => {
        await page.focus('#cm-row');
        await page.keyboard.press('Shift+F10');
        await settle(250);
        if (!(await menuShown())) return 'did not open';
        const p = await box(panel);
        const r = await box('#cm-row');
        const dy = Math.round(p.top - r.bottom);
        const dx = Math.round(p.left - r.left);
        return (dy === 4 && Math.abs(dx) <= 1 && (await active()) === 'cm-edit') || `${dx}px across, ${dy}px below, focus on ${await active()}`;
    });

    await check('choosing an item runs it, with dsTarget the row, closes, and focus goes back', async () => {
        await page.keyboard.press('Enter');
        await settle(200);
        const chosen = await page.locator('#cm-chosen').innerText();
        return (chosen === 'edit q3' && !(await menuShown()) && (await active()) === 'cm-row')
            || `chose "${chosen}", open ${await menuShown()}, focus on ${await active()}`;
    });

    await check('the Menu key opens it too, and Tab closes it back to the row', async () => {
        await page.focus('#cm-row');
        await page.keyboard.press('ContextMenu');
        await settle(250);
        if (!(await menuShown())) return 'the Menu key did not open it';
        await page.keyboard.press('Tab');
        await settle(200);
        return (!(await menuShown()) && (await active()) === 'cm-row') || `open ${await menuShown()}, focus on ${await active()}`;
    });

    await check('a press outside on nothing focusable closes it and puts focus back', async () => {
        await page.focus('#cm-row');
        await page.keyboard.press('Shift+F10');
        await settle(250);
        await page.mouse.click(5, 5);
        await settle(200);
        return (!(await menuShown()) && (await active()) === 'cm-row') || `open ${await menuShown()}, focus on ${await active()}`;
    });

    await check('a press outside on a button closes it and leaves focus on that button', async () => {
        await page.focus('#cm-row');
        await page.keyboard.press('Shift+F10');
        await settle(250);
        await page.click('#cm-before');
        await settle(200);
        return (!(await menuShown()) && (await active()) === 'cm-before') || `open ${await menuShown()}, focus on ${await active()}`;
    });

    for (const [id, what] of [['cm-link', 'a link'], ['cm-input', 'a field']]) {
        await check(`on ${what} inside the region the browser's own menu is left alone`, async () => {
            const b = await box(`#${id}`);
            await page.mouse.click(b.left + 5, b.top + 5, { button: 'right' });
            await settle(150);
            return (!(await menuShown()) && (await page.evaluate(() => window.cmPrevented)) === false)
                || `menu shown ${await menuShown()}, prevented ${await page.evaluate(() => window.cmPrevented)}`;
        });
    }

    await check('Shift+F10 in a field inside the region is left to the browser too', async () => {
        await page.focus('#cm-input');
        await page.keyboard.press('Shift+F10');
        await settle(150);
        return !(await menuShown()) || 'opened over the field';
    });

    await check('with native off, a link inside opens this menu instead', async () => {
        await page.locator('#cm-wide').scrollIntoViewIfNeeded();
        const b = await box('#cm-wide-link');
        await page.mouse.click(b.left + 5, b.top + 5, { button: 'right' });
        await settle(200);
        const shown = await menuShown(wide);
        await page.keyboard.press('Escape');
        await settle(150);
        return (shown && (await page.evaluate(() => window.cmPrevented)) === true) || 'the browser menu was left, or nothing opened';
    });

    await check('at the bottom-right corner it opens up and back across the pointer, on screen', async () => {
        // The region's foot on the window's foot.
        await page.evaluate(() => {
            const r = document.getElementById('cm-wide').getBoundingClientRect();
            window.scrollBy(0, r.bottom - document.documentElement.clientHeight + 2);
        });
        await settle(100);
        const region = await box('#cm-wide');
        const vw = await page.evaluate(() => document.documentElement.clientWidth);
        const vh = await page.evaluate(() => document.documentElement.clientHeight);
        if (vw - region.right > 60 || vh - region.bottom > 20) return `the region is not at the corner: ${Math.round(vw - region.right)}px, ${Math.round(vh - region.bottom)}px off`;
        const x = Math.round(region.right - 4);
        const y = Math.round(region.bottom - 4);
        await page.mouse.click(x, y, { button: 'right' });
        await settle(250);
        const b = await anchorBoxes(wide, '#cm-wide');
        const align = await page.evaluate((q) => document.querySelector(q).dataset.align, wide);
        if (!b.shown) return 'did not open';
        if (b.side !== 'top' || align !== 'end') return `took ${b.side}-${align}`;
        if (Math.round(x - b.panel.right) > 1 || Math.round(y - b.panel.bottom) > 3) {
            return `its corner is ${Math.round(x - b.panel.right)}px, ${Math.round(y - b.panel.bottom)}px from the pointer`;
        }
        return insideViewport(b);
    });

    await check('it grows from the corner at the pointer (its transform origin)', async () => {
        const o = await page.evaluate((q) => {
            const el = document.querySelector(q);
            const [x, y] = getComputedStyle(el).transformOrigin.split(' ').map(parseFloat);
            return { x: Math.round(x - el.offsetWidth), y: Math.round(y - el.offsetHeight) };
        }, wide);
        return (o.x === 0 && o.y === 0) || `${o.x}px, ${o.y}px from its bottom-right corner`;
    });

    await check('it moves with the region when the page scrolls', async () => {
        const before = await box(wide);
        await page.evaluate(() => window.scrollBy(0, -40));
        await settle(150);
        const after = await box(wide);
        await page.keyboard.press('Escape');
        await settle(150);
        return Math.round(after.top - before.top) === 40 || `moved ${Math.round(after.top - before.top)}px for a 40px scroll`;
    });

    // Touch, as pointer events: the main page has no touch screen.
    await page.locator('#cm').scrollIntoViewIfNeeded();
    await settle(100);
    const touch = (type, x, y) => page.evaluate(([t, cx, cy]) => {
        const target = document.elementFromPoint(cx, cy);
        target.dispatchEvent(new PointerEvent(t, { bubbles: true, cancelable: true, pointerType: 'touch', isPrimary: true, clientX: cx, clientY: cy }));
    }, [type, x, y]);
    const t = await box('#cm-text');
    const tx = Math.round(t.left + 30);
    const ty = Math.round(t.top + 5);

    await check('a touch held still for half a second opens it at the finger', async () => {
        await touch('pointerdown', tx, ty);
        await page.waitForTimeout(650);
        const shown = await menuShown();
        const p = await box(panel);
        await touch('pointerup', tx, ty);
        return (shown && Math.round(p.left - tx) <= 1) || `shown ${shown}, ${Math.round(p.left - tx)}px from the finger`;
    });

    await check('and the click that ends the hold is swallowed', async () => {
        await page.evaluate(() => {
            window.cmClicks = 0;
            document.getElementById('cm-text').addEventListener('click', () => { window.cmClicks++; }, { once: true });
            document.getElementById('cm-text').click();
        });
        const clicks = await page.evaluate(() => window.cmClicks);
        await page.keyboard.press('Escape');
        await settle(150);
        return clicks === 0 || 'the click went through';
    });

    await check('a short tap does not open it', async () => {
        await touch('pointerdown', tx, ty);
        await page.waitForTimeout(200);
        await touch('pointerup', tx, ty);
        await page.waitForTimeout(500);
        return !(await menuShown()) || 'opened on a tap';
    });

    await check('moving more than 10px during the hold cancels it (a scroll, not a press)', async () => {
        await touch('pointerdown', tx, ty);
        await page.waitForTimeout(100);
        await touch('pointermove', tx, ty + 20);
        await page.waitForTimeout(550);
        const shown = await menuShown();
        await touch('pointerup', tx, ty + 20);
        return !shown || 'opened after the finger moved';
    });

    await closeAll();
}

// ---------------------------------------------------------------- counter

/** What a counter shows, what it says, and whether it is rolling. */
const counterState = (tab, id) => tab.evaluate((id) => {
    const el = document.getElementById(id);
    const face = el.querySelector('[data-ds-counter-face]');
    return {
        text: el.querySelector('[data-ds-counter-text]').textContent,
        face: face.textContent,
        hidden: face.getAttribute('aria-hidden'),
        columns: face.querySelectorAll('[data-ds-counter-strip]').length,
        digits: [...face.querySelectorAll('[data-ds-counter-strip]')].map((s) => s.style.getPropertyValue('--ds-digit')),
        width: el.getBoundingClientRect().width,
        value: el.dataset.dsValue,
    };
}, id);

/** Records, from now on, whether a counter ever showed rolling columns. */
const watchRoll = (tab, id) => tab.evaluate((id) => {
    const el = document.getElementById(id);
    window.rolled = false;
    new MutationObserver(() => {
        if (el.querySelector('[data-ds-counter-face] [data-ds-counter-strip]')) window.rolled = true;
    }).observe(el, { childList: true, subtree: true });
}, id);

group('counter (x-ds-roll from the server, dsRoll from Alpine state)');
{
    await check('the server\'s number is plain text: read once from the hidden span, the face aria-hidden', async () => {
        const s = await counterState(page, 'ct-server');
        return (s.text === '998' && s.face === '998' && s.hidden === 'true' && s.columns === 0) || JSON.stringify(s);
    });
    await check('bound to Alpine: the first reading is put in place, formatted as PHP formats it, not rolled', async () => {
        const s = await counterState(page, 'ct-bound');
        return (s.text === '$40.00' && s.face === '$40.00' && s.columns === 0) || JSON.stringify(s);
    });

    let rolling;
    await check('a change rolls: a column per digit, and the hidden text already says the destination', async () => {
        await page.click('#ct-add');
        await page.waitForTimeout(60);
        rolling = await counterState(page, 'ct-bound');
        return (rolling.columns === 6 && rolling.text === '$1,274.50' && rolling.value === '1274.5') || JSON.stringify(rolling);
    });
    await check('each strip slides to its new digit', async () => {
        const want = '127450'.split('').map((d) => `${-Number(d) * 10}%`);
        return JSON.stringify(rolling?.digits) === JSON.stringify(want) || `${JSON.stringify(rolling?.digits)}, wanted ${JSON.stringify(want)}`;
    });
    await check('then settles to plain text, exactly as wide as it was mid-roll (no jitter)', async () => {
        await page.waitForTimeout(600);
        const s = await counterState(page, 'ct-bound');
        const drift = Math.abs(s.width - (rolling?.width ?? 0));
        return (s.columns === 0 && s.face === '$1,274.50' && drift < 0.5) || `${JSON.stringify(s)}, drift ${drift}px`;
    });
    await check('a negative reads with a real minus, before the prefix', async () => {
        await page.click('#ct-negative');
        await page.waitForTimeout(600);
        const s = await counterState(page, 'ct-bound');
        return (s.face === '−$5.00' && s.text === '−$5.00') || JSON.stringify(s);
    });
    await check('under reduced motion the new number replaces the old at once', async () => {
        await page.emulateMedia({ reducedMotion: 'reduce' });
        await watchRoll(page, 'ct-bound');
        await page.click('#ct-add');
        await page.waitForTimeout(30);
        const s = await counterState(page, 'ct-bound');
        const rolled = await page.evaluate(() => window.rolled);
        await page.emulateMedia({ reducedMotion: 'no-preference' });
        return (!rolled && s.columns === 0 && s.face === '$1,229.50' && s.text === '$1,229.50') || `${JSON.stringify(s)}, rolled ${rolled}`;
    });
}

// ---------------------------------------------------------------- activity heatmap

group('activity heatmap (a table, scrolling in its own box)');
{
    const heat = (fn) => page.evaluate(fn);

    await check('a year in a narrow box opens on the most recent weeks, with no script', async () => {
        const r = await heat(() => {
            const region = document.querySelector('#hm-narrow [role="region"]');
            const cell = (text) => [...region.querySelectorAll('td')].find((td) => td.textContent.includes(text));
            const box = region.getBoundingClientRect();
            const last = cell('8 March 2026').getBoundingClientRect();
            const first = cell('3 March 2025').getBoundingClientRect();
            return {
                scrolls: region.scrollWidth > region.clientWidth,
                lastIn: last.left >= box.left && last.right <= box.right,
                firstOut: first.right <= box.left,
            };
        });
        return (r.scrolls && r.lastIn && r.firstOut) || JSON.stringify(r);
    });
    await check('a day says its count in words, as text and as its native tooltip', async () => {
        const r = await heat(() => {
            const link = document.querySelector('#hm-narrow a[href="/day/2026-03-03"]');
            const quiet = [...document.querySelectorAll('#hm-narrow td')].find((td) => td.textContent.includes('5 March 2026'));
            return { link: link?.textContent.trim(), title: link?.title, one: document.querySelector('#hm-narrow a[href="/day/2026-03-04"]')?.textContent.trim(), quiet: quiet?.textContent.trim(), quietTitle: quiet?.title };
        });
        return (r.link === '12 commits on 3 March 2026' && r.title === r.link && r.one === '1 commit on 4 March 2026'
            && r.quiet === 'No commits on 5 March 2026' && r.quietTitle === r.quiet) || JSON.stringify(r);
    });
    await check('only the days with a count are links', async () => {
        const n = await heat(() => document.querySelectorAll('#hm-narrow a').length);
        return n === 3 || `${n} links`;
    });
    await check('a table to assistive technology: roles kept under the grid, a weekday header per column, weeks in time order', async () => {
        const r = await page.evaluate(() => {
            const table = document.querySelector('#hm-narrow table');
            const rows = [...table.querySelectorAll('tbody [role="row"]')];
            const firstDay = (row) => [...row.querySelectorAll('td')].find((td) => td.textContent.trim())?.textContent.trim();
            return {
                role: table.getAttribute('role'),
                headers: table.querySelectorAll('thead [role="columnheader"]').length,
                first: firstDay(rows[0]),
                last: [...rows.at(-1).querySelectorAll('td')].map((td) => td.textContent.trim()).filter(Boolean).at(-1),
            };
        });
        return (r.role === 'table' && r.headers === 8 && r.first === 'No commits on 3 March 2025' && r.last === 'No commits on 8 March 2026') || JSON.stringify(r);
    });
    await check('the scroll box is a focusable region named by the caption', async () => {
        const r = await heat(() => {
            const region = document.querySelector('#hm-narrow [role="region"]');
            const caption = document.getElementById(region.getAttribute('aria-labelledby'));
            return { tab: region.tabIndex, caption: caption?.tagName, text: caption?.textContent.trim().slice(0, 30) };
        });
        return (r.tab === 0 && r.caption === 'CAPTION' && r.text.startsWith('Commits. 15 commits from 3 Mar')) || JSON.stringify(r);
    });
}

// ---------------------------------------------------------------- charts

/** The focused element as a chart reads it, and the readout beside it. */
const chartState = (tab, id) => tab.evaluate((id) => {
    const root = document.getElementById(id);
    const readout = root.querySelector('[data-ds-chart-readout]');
    const part = (n) => readout?.querySelector(`[data-ds-chart-readout-${n}]`)?.textContent ?? null;
    const a = document.activeElement;
    return {
        focused: root.contains(a) ? (a.getAttribute('aria-label') ?? a.tagName) : null,
        active: root.querySelector('[data-ds-active]')?.getAttribute('aria-label') ?? null,
        shown: readout ? !readout.hidden : null,
        label: part('label'), series: part('series'), value: part('value'),
        swatch: readout?.querySelector('[data-ds-chart-readout-swatch]')?.getAttribute('data-series') ?? null,
    };
}, id);

group('charts (x-ds-chart: readout, roving tabindex, legend toggles)');
{
    await check('enhanced: the plot is a group named by the summary, each mark an img named by its value', async () => {
        const r = await page.evaluate(() => {
            const plot = document.querySelector('#ch-bar [data-ds-chart-plot]');
            const marks = [...plot.querySelectorAll('[data-ds-chart-mark]')];
            return {
                role: plot.getAttribute('role'), name: plot.getAttribute('aria-label'), tab: plot.getAttribute('tabindex'),
                roles: [...new Set(marks.map((m) => m.getAttribute('role')))],
                first: marks[0].getAttribute('aria-label'),
                stops: marks.filter((m) => m.tabIndex === 0).length, count: marks.length,
            };
        });
        return (r.role === 'group' && r.name.startsWith('Revenue and costs. Revenue: highest Feb') && r.tab === '-1'
            && JSON.stringify(r.roles) === '["img"]' && r.first === 'Jan, Revenue: $12K' && r.stops === 1 && r.count === 6) || JSON.stringify(r);
    });
    await check('one Tab stop per chart: Tab lands on the first bar (not the svg), and the legend keys come next', async () => {
        await page.focus('#ch-before');
        await page.keyboard.press('Tab');
        const first = await chartState(page, 'ch-bar');
        await page.keyboard.press('Tab');
        const key = await page.evaluate(() => document.activeElement.getAttribute('data-ds-chart-key'));
        await page.keyboard.press('Tab');
        await page.keyboard.press('Tab');
        const next = await chartState(page, 'ch-stack');
        return (first.focused === 'Jan, Revenue: $12K' && key === '0' && next.focused === 'W1, Open: 5') || JSON.stringify({ first, key, next });
    });
    await check('focus shows the readout: label, value and series, with the series swatch, and marks the bar', async () => {
        await page.focus('#ch-before');
        await page.keyboard.press('Tab');
        const s = await chartState(page, 'ch-bar');
        const hidden = await page.evaluate(() => document.querySelector('#ch-bar [data-ds-chart-readout]').getAttribute('aria-hidden'));
        return (s.shown && s.label === 'Jan' && s.value === '$12K' && s.series === 'Revenue' && s.swatch === '1'
            && s.active === 'Jan, Revenue: $12K' && hidden === 'true') || JSON.stringify({ ...s, hidden });
    });
    await check('ArrowRight moves along the series, ArrowDown across to the same month in the next, wrapping', async () => {
        await page.keyboard.press('ArrowRight');
        const along = await chartState(page, 'ch-bar');
        await page.keyboard.press('ArrowDown');
        const across = await chartState(page, 'ch-bar');
        await page.keyboard.press('ArrowDown');
        const wrapped = await chartState(page, 'ch-bar');
        return (along.focused === 'Feb, Revenue: $18K' && along.value === '$18K' && across.focused === 'Feb, Costs: $10K'
            && across.series === 'Costs' && across.swatch === '2' && wrapped.focused === 'Feb, Revenue: $18K') || JSON.stringify({ along, across, wrapped });
    });
    await check('Home and End go to the ends; the tab stop follows focus', async () => {
        await page.keyboard.press('End');
        const end = await chartState(page, 'ch-bar');
        await page.keyboard.press('Home');
        const home = await chartState(page, 'ch-bar');
        await page.keyboard.press('End');
        const stops = await page.evaluate(() => [...document.querySelectorAll('#ch-bar [data-ds-chart-mark]')].filter((m) => m.tabIndex === 0).map((m) => m.getAttribute('aria-label')));
        return (end.focused === 'Mar, Costs: $11K' && home.focused === 'Jan, Revenue: $12K' && JSON.stringify(stops) === '["Mar, Costs: $11K"]') || JSON.stringify({ end, home, stops });
    });
    await check('Escape puts the readout away and keeps focus; a second Escape is left to the page', async () => {
        await page.evaluate(() => {
            window.escSeen = 0;
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') window.escSeen++; });
        });
        await page.keyboard.press('Escape');
        const after = await chartState(page, 'ch-bar');
        const first = await page.evaluate(() => window.escSeen);
        await page.keyboard.press('Escape');
        const second = await page.evaluate(() => window.escSeen);
        return (after.shown === false && after.focused === 'Mar, Costs: $11K' && after.active === null && first === 0 && second === 1) || JSON.stringify({ after, first, second });
    });
    await check('stacked: ArrowUp goes up the stack to the next series', async () => {
        await page.focus('#ch-stack [data-ds-chart-mark][tabindex="0"]');
        await page.keyboard.press('ArrowUp');
        const s = await chartState(page, 'ch-stack');
        return s.focused === 'W1, Closed: 3' || JSON.stringify(s);
    });
    await check('a legend entry is a toggle button: hides its series, skipped by the arrows, nothing re-scaled', async () => {
        const bar = '#ch-bar [data-ds-chart-mark][data-ds-in="0"][data-ds-at="1"]';
        const before = await page.evaluate((b) => document.querySelector(b).getBoundingClientRect().height, bar);
        const key = page.locator('#ch-bar [data-ds-chart-key="1"]');
        const r0 = await key.evaluate((k) => ({ role: k.getAttribute('role'), pressed: k.getAttribute('aria-pressed'), tab: k.tabIndex }));
        await key.focus();
        await page.keyboard.press('Enter');
        const r = await page.evaluate((b) => ({
            pressed: document.querySelector('#ch-bar [data-ds-chart-key="1"]').getAttribute('aria-pressed'),
            off: document.querySelector('#ch-bar [data-ds-chart-series="1"]').hasAttribute('data-ds-off'),
            hiddenStop: [...document.querySelectorAll('#ch-bar [data-ds-chart-series="1"] [data-ds-chart-mark]')].some((m) => m.tabIndex === 0),
            height: document.querySelector(b).getBoundingClientRect().height,
        }), bar);
        await page.focus('#ch-bar [data-ds-chart-mark][tabindex="0"]');
        await page.keyboard.press('End');
        const end = await chartState(page, 'ch-bar');
        return (r0.role === 'button' && r0.pressed === 'true' && r0.tab === 0 && r.pressed === 'false' && r.off && !r.hiddenStop
            && Math.abs(r.height - before) < 0.5 && end.focused === 'Mar, Revenue: $15K') || JSON.stringify({ r0, r, before, end });
    });
    await check('the last series shown cannot be hidden; Space shows a hidden one again', async () => {
        await page.locator('#ch-bar [data-ds-chart-key="0"]').click();
        const last = await page.evaluate(() => document.querySelector('#ch-bar [data-ds-chart-key="0"]').getAttribute('aria-pressed'));
        await page.locator('#ch-bar [data-ds-chart-key="1"]').focus();
        await page.keyboard.press(' ');
        const back = await page.evaluate(() => !document.querySelector('#ch-bar [data-ds-chart-series="1"]').hasAttribute('data-ds-off'));
        return (last === 'true' && back) || JSON.stringify({ last, back });
    });
    await check('a line: the pointer reads the nearest point, and leaving hides the readout', async () => {
        await page.evaluate(() => document.getElementById('ch-line').scrollIntoView({ block: 'center' }));
        const box = await page.evaluate(() => {
            const r = document.querySelector('#ch-line [data-ds-chart-mark][data-ds-at="2"][data-ds-in="0"]').getBoundingClientRect();
            return { x: r.left + r.width / 2, y: r.top + r.height / 2 };
        });
        await page.mouse.move(box.x + 12, box.y - 6);
        await page.waitForTimeout(50);
        const over = await chartState(page, 'ch-line');
        await page.mouse.move(2, 2);
        await page.waitForTimeout(50);
        const away = await chartState(page, 'ch-line');
        return (over.shown && over.label === 'Wed' && over.value === '6' && over.series === 'New' && away.shown === false) || JSON.stringify({ over, away });
    });
    await check('a line: ArrowDown moves to the same day on the next line', async () => {
        await page.focus('#ch-line [data-ds-chart-mark][tabindex="0"]');
        await page.keyboard.press('ArrowRight');
        await page.keyboard.press('ArrowDown');
        const s = await chartState(page, 'ch-line');
        return (s.focused === 'Tue, Returning: 2' && s.shown) || JSON.stringify(s);
    });
    await check('a donut: Tab reaches the first segment, any arrow moves on, the ring is drawn round the whole', async () => {
        await page.focus('#ch-line [data-ds-chart-mark][tabindex="0"]');
        let first = null;
        for (let i = 0; i < 6 && !first?.focused; i++) {
            await page.keyboard.press('Tab');
            first = await chartState(page, 'ch-donut');
        }
        await page.keyboard.press('ArrowDown');
        const next = await chartState(page, 'ch-donut');
        const ring = await page.evaluate(() => getComputedStyle(document.querySelector('#ch-donut [data-ds-chart-plot]').parentElement).outlineStyle);
        return (first.focused === 'Direct: 1,200 (50%)' && first.value === '1,200 (50%)' && first.series === ''
            && next.focused === 'Search: 800 (33%)' && ring === 'solid') || JSON.stringify({ first, next, ring });
    });
    await check('interactive off: an image, no tab stops, no readout', async () => {
        const r = await page.evaluate(() => {
            const root = document.getElementById('ch-static');
            return { role: root.querySelector('svg').getAttribute('role'), x: root.hasAttribute('x-ds-chart'), focusable: root.querySelectorAll('[tabindex]').length, readout: root.querySelectorAll('[data-ds-chart-readout]').length };
        });
        return (r.role === 'img' && !r.x && r.focusable === 0 && r.readout === 0) || JSON.stringify(r);
    });
}

// ---------------------------------------------------------------- focus indicators

/*
  Tab from the top of a page holding one of each focusable component, in both
  themes and at a desktop and a phone width, and require every stop to DRAW
  something: an outline or a ring that was not there before focus, on the
  element, a pseudo-element or one of its near ancestors (a field's ring is on
  its shell, a chip's on its label). The browser's own `auto` ring fails: it is
  not the system's ring, and it is the sign of an element nobody styled. So
  does anything drawn on a transparent element, the file input being one.

  The comparison is focused against unfocused, element by element, so a
  permanent shadow on a card never counts as a ring. Reduced motion, so a ring
  colour with a transition is measured at its end and not on its way there.
*/
const fpage = await context.newPage();
await fpage.emulateMedia({ reducedMotion: 'reduce' });
fpage.on('pageerror', (e) => jsErrors.push('focus page, uncaught: ' + e.message));
await fpage.goto('file://' + join(TMP, 'focus-page.html'), { waitUntil: 'networkidle' });
await fpage.waitForTimeout(300);

await fpage.evaluate(() => {
    const shown = (node) => {
        for (let n = node; n && n.nodeType === 1; n = n.parentElement) {
            const s = getComputedStyle(n);
            if (parseFloat(s.opacity) === 0 || s.visibility === 'hidden') return false;
        }
        return true;
    };
    // Everything that could be a focus indicator, on the element and three
    // ancestors, as strings to diff.
    window.dsFocusMarks = (el) => {
        const out = [];
        let n = el;
        for (let depth = 0; n && n.nodeType === 1 && depth < 4; depth++, n = n.parentElement) {
            if (!shown(n)) continue;
            for (const pseudo of [null, '::before', '::after']) {
                const cs = getComputedStyle(n, pseudo);
                if (pseudo && cs.content === 'none') continue;
                const at = `${depth}${pseudo ?? ''}`;
                if (cs.outlineStyle !== 'none' && parseFloat(cs.outlineWidth) > 0) {
                    out.push(`${at} outline ${cs.outlineStyle} ${parseFloat(cs.outlineWidth)} ${cs.outlineColor}`);
                }
                if (cs.boxShadow !== 'none') out.push(`${at} shadow ${cs.boxShadow}`);
            }
        }
        return out;
    };
    window.dsDescribe = (el) => {
        const what = el.getAttribute('aria-label') || el.textContent.replace(/\s+/g, ' ').trim().slice(0, 24) || el.name || '';
        return `${el.closest('[data-focus]')?.dataset.focus ?? '?'}: ${el.tagName.toLowerCase()}${el.getAttribute('role') ? `[role=${el.getAttribute('role')}]` : ''} "${what}"`;
    };
});

const clearColour = (c) => /rgba\([^)]*,\s*0\)/.test(c) || /\/\s*0\)/.test(c) || c.includes('transparent');
/** true for a drawn ring, 'auto' for the browser's own, false for nothing. */
const isRing = (mark) => {
    const parts = mark.split(' ');
    if (parts[1] === 'outline') {
        if (parts[2] === 'auto') return 'auto';
        return parseFloat(parts[3]) >= 1 && !clearColour(parts.slice(4).join(' '));
    }
    return mark.replace(/^\S+ shadow /, '').split(/,(?![^(]*\))/).some((shadow) => {
        const spread = shadow.match(/(-?[\d.]+)px (-?[\d.]+)px (-?[\d.]+)px (-?[\d.]+)px/);
        return spread && parseFloat(spread[4]) >= 1 && !clearColour(shadow);
    });
};
const verdict = (on, off) => {
    const added = on.filter((m) => !off.includes(m)).map(isRing);
    return added.includes(true) ? 'ring' : added.includes('auto') ? "only the browser's own ring" : 'nothing';
};

const tabWalk = async () => {
    await fpage.evaluate(() => {
        document.activeElement?.blur();
        document.querySelectorAll('[data-stop]').forEach((el) => delete el.dataset.stop);
    });
    await fpage.mouse.click(1, 1);
    const stops = [];
    for (let i = 0; i < 200; i++) {
        await fpage.keyboard.press('Tab');
        const stop = await fpage.evaluate((n) => {
            const el = document.activeElement;
            if (!el || el === document.body || el.id === 'f-end') return null;
            el.dataset.stop = n;
            return { n, section: el.closest('[data-focus]')?.dataset.focus, what: window.dsDescribe(el), on: window.dsFocusMarks(el) };
        }, i);
        if (!stop) break;
        stops.push(stop);
    }
    await fpage.evaluate(() => document.activeElement?.blur());
    for (const stop of stops) {
        const off = await fpage.evaluate((n) => window.dsFocusMarks(document.querySelector(`[data-stop="${n}"]`)), stop.n);
        stop.verdict = verdict(stop.on, off);
    }
    return stops;
};

/*
  Inside an overlay, opened from its trigger with the keyboard. Each element
  is measured focused, then again once `move` has taken focus off it. A dialog
  panel is skipped: it takes focus on open so a screen reader starts at the
  top of it, and is not a Tab stop.
*/
const overlayWalk = async (opener, open, move, count) => {
    await fpage.keyboard.press('Escape');
    await fpage.focus(opener);
    await fpage.keyboard.press('Shift+Tab');
    await fpage.keyboard.press('Tab');
    await fpage.keyboard.press(open);
    await fpage.waitForTimeout(100);
    const bad = [];
    for (let i = 0; i < count; i++) {
        const now = await fpage.evaluate((n) => {
            const el = document.activeElement;
            el.dataset.inside = n;
            return { dialog: el.getAttribute('role') === 'dialog' && el.tabIndex < 0, what: window.dsDescribe(el), on: window.dsFocusMarks(el) };
        }, `${opener}-${i}`);
        await fpage.keyboard.press(move);
        await fpage.waitForTimeout(40);
        if (now.dialog) continue;
        const off = await fpage.evaluate((n) => {
            const el = document.querySelector(`[data-inside="${n}"]`);
            return document.activeElement === el ? null : window.dsFocusMarks(el);
        }, `${opener}-${i}`);
        if (off === null) bad.push(`${now.what}: ${move} did not move focus off it`);
        else if (verdict(now.on, off) !== 'ring') bad.push(`${now.what}: ${verdict(now.on, off)}`);
    }
    await fpage.keyboard.press('Escape');
    await fpage.waitForTimeout(100);
    return bad;
};

const overlays = [
    ['dropdown items', '#f-dd', 'Enter', 'ArrowDown', 2],
    ['select options', '#f-select', 'ArrowDown', 'ArrowDown', 2],
    ['combobox options', '#f-combobox', 'ArrowDown', 'ArrowDown', 2],
    ['date picker month buttons and days', '[data-ds-date-trigger]', 'Enter', 'Tab', 5],
    ['popover content', '#f-pop', 'Enter', 'Tab', 1],
    ['confirm button, asking', '[data-focus=confirm-button] [data-ds-confirm-trigger]', 'Enter', 'Tab', 2],
    ['confirm popover content', '#f-cp', 'Enter', 'Tab', 2],
    ['context menu items', '#f-cm-row', 'Shift+F10', 'ArrowDown', 2],
    ['modal, round the trap', '#f-modal-open', 'Enter', 'Tab', 4],
    ['drawer, round the trap', '#f-drawer-open', 'Enter', 'Tab', 3],
];

const sections = await fpage.evaluate(() => [...document.querySelectorAll('[data-focus]')].map((el) => el.dataset.focus));

for (const [width, label] of [[1200, 'desktop'], [375, 'phone']]) {
    await fpage.setViewportSize({ width, height: 900 });
    for (const theme of ['light', 'dark']) {
        group(`focus indicators (${theme}, ${label} width)`);
        await fpage.evaluate((t) => document.documentElement.classList.toggle('dark', t === 'dark'), theme);
        const stops = await tabWalk();

        await check(`every Tab stop draws a ring (${stops.length} stops)`, async () => {
            const bad = stops.filter((s) => s.verdict !== 'ring').map((s) => `${s.what}: ${s.verdict}`);
            return !bad.length || bad.join('; ');
        });

        if (label === 'desktop') {
            await check(`Tab reached every one of the ${sections.length} components on the page`, async () => {
                const reached = new Set(stops.map((s) => s.section));
                const missed = sections.filter((name) => !reached.has(name));
                return !missed.length || `never reached: ${missed.join(', ')}`;
            });
        }

        for (const [name, ...walk] of overlays) {
            await check(`${name}: each focused one draws a ring`, async () => {
                const bad = await overlayWalk(...walk);
                return !bad.length || bad.join('; ');
            });
        }
    }
}

await fpage.evaluate(() => document.documentElement.classList.remove('dark'));
await fpage.close();

// ---------------------------------------------------------------- the other two host setups

/*
 * Every page above loads Alpine the CDN way. These load it the other two ways a
 * host can, each on a page of its own, served from a fake localhost origin —
 * module imports are refused from file://, and localhost is a secure context,
 * so the async clipboard API exists there.
 *
 *   (b) Alpine bundled by the host: Alpine.plugin() on dist/blade-ui.js. The
 *       include is on the page as well, so loading the script twice is
 *       exercised in its worst form — two different builds, one Alpine.
 *   (c) Livewire's own script, which bundles Alpine and starts it on
 *       DOMContentLoaded. The include is in the head TWICE.
 *
 * And the failure a host without the script gets, which must name the fix.
 */
const ORIGIN = 'http://localhost:7357';
const SERVED = {
    '/alpine.js': join(ROOT, 'node_modules/alpinejs/dist/cdn.min.js'),
    '/alpine.esm.js': join(ROOT, 'node_modules/alpinejs/dist/module.esm.js'),
    '/blade-ui.js': join(ROOT, 'dist/blade-ui.js'),
    '/livewire.js': join(ROOT, 'vendor/livewire/livewire/dist/livewire.js'),
};

const serveHostPage = async (route) => {
    const path = new URL(route.request().url()).pathname;
    const file = SERVED[path] ?? join(TMP, path);
    try {
        await route.fulfill({
            body: await readFile(file),
            contentType: path.endsWith('.html') ? 'text/html' : 'text/javascript',
            // Alpine's CDN build evaluates expressions with Function(), so a
            // policy that runs it needs 'unsafe-eval'; every script element
            // still has to carry the nonce.
            headers: path.startsWith('/csp') ? { 'Content-Security-Policy': "script-src 'nonce-t3st' 'unsafe-eval'" } : {},
        });
    } catch {
        await route.fulfill({ status: 404, body: '' });
    }
};
await context.route(`${ORIGIN}/**`, serveHostPage);

const segMarkup = [
    specimens.match(/<div class="mt-6 w-96" id="seg-rtl"[\s\S]*?<\/fieldset>\s*<\/div>/)?.[0],
    specimens.match(/<div class="mt-6" id="seg-links"[\s\S]*?<\/nav>\s*<\/div>/)?.[0],
].join('');
const chartsMarkup = specimens.match(/<div class="mt-6 w-\[640px\]" id="charts"[\s\S]*?<!-- \/charts -->/)?.[0];
const clipMarkup = specimens.match(/<div class="mt-6" id="clip"[\s\S]*?<\/span>\s*<\/div>/)?.[0];

const hostPages = {
    'setup-b.html': `<!doctype html><html><head><meta charset="utf-8">
${include}
<script type="module">
    import Alpine from './alpine.esm.js';
    import bladeUi from './blade-ui.js';

    window.dsRegistered = [];
    const data = Alpine.data;
    Alpine.data = (name, callback) => (window.dsRegistered.push(name), data(name, callback));

    Alpine.plugin(bladeUi);
    Alpine.plugin(bladeUi);
    window.Alpine = Alpine;
    Alpine.start();
</script>
</head><body>${clipMarkup}</body></html>`,

    'setup-c.html': `<!doctype html><html><head><meta charset="utf-8">
${include}
${orderProbe}
${include}
</head><body>${clipMarkup}
<script src="livewire.js"></script>
</body></html>`,

    'csp.html': `<!doctype html><html><head><meta charset="utf-8">
${includeWithNonce}
<script nonce="t3st" defer src="alpine.js"></script>
</head><body>${clipMarkup}</body></html>`,

    // The other half: the same policy and the include WITHOUT the nonce. If
    // this one still ran, the policy above was never enforced and the nonce
    // check proves nothing.
    'csp-without-nonce.html': `<!doctype html><html><head><meta charset="utf-8">
${include}
<script nonce="t3st" defer src="alpine.js"></script>
</head><body>${clipMarkup}</body></html>`,

    'missing.html': `<!doctype html><html><head><meta charset="utf-8">
<script defer src="alpine.js"></script>
</head><body>${clipMarkup}</body></html>`,

    // The segmented control, with Alpine and no include: it must look chosen
    // and post, from PHP and CSS alone, and say nothing in the console.
    'glide-missing.html': `<!doctype html><html><head><meta charset="utf-8">
<style>${css}</style>
<script defer src="alpine.js"></script>
</head><body><form id="form">${segMarkup}</form></body></html>`,

    // The overlays that take x-ds-anchor as an enhancement, with Alpine and
    // no include: they open where their absolute classes put them.
    'anchor-missing.html': `<!doctype html><html><head><meta charset="utf-8">
<style>${css}</style>
<script defer src="alpine.js"></script>
</head><body>${fallbackMarkup}</body></html>`,

    // The charts with Alpine and no include, and with neither: each is the
    // static image PHP drew, and says nothing in the console.
    'charts-missing.html': `<!doctype html><html><head><meta charset="utf-8">
<style>${css}</style>
<script defer src="alpine.js"></script>
</head><body>${chartsMarkup}</body></html>`,

    'charts-static.html': `<!doctype html><html><head><meta charset="utf-8">
<style>${css}</style>
</head><body>${chartsMarkup}</body></html>`,

    // The pre-paint script FIRST, then a probe that records <html>'s classes
    // before any stylesheet or body exists, then the stylesheet. No Alpine,
    // no package script: the snippet must stand alone.
    'theme-pre.html': `<!doctype html><html><head><meta charset="utf-8">
${themeScript}
<script>window.atParse = { classes: document.documentElement.className, scheme: document.documentElement.style.colorScheme };</script>
<style>${css}</style>
</head><body class="bg-surface text-fg">before Alpine, before the body</body></html>`,

    'theme.html': `<!doctype html><html><head><meta charset="utf-8">
${themeScript}
<style>${css}</style>
${include}
<script defer src="alpine.js"></script>
</head><body class="bg-surface">${themeMarkup}</body></html>`,

    // The guards with Alpine and no include, and with neither.
    'guards-missing.html': `<!doctype html><html><head><meta charset="utf-8">
<style>${css}</style>
<script defer src="alpine.js"></script>
</head><body>${guardsMarkup}</body></html>`,

    'guards-static.html': `<!doctype html><html><head><meta charset="utf-8">
<style>${css}</style>
</head><body>${guardsMarkup}</body></html>`,

    // The remembered bar, then a probe that records whether it was hidden
    // at the moment the parser passed it: before Alpine, before the body
    // was done. Alpine and the include come after, as a host has them.
    'announce-pre.html': `<!doctype html><html><head><meta charset="utf-8">
<style>${css}</style>
${include}
<script defer src="alpine.js"></script>
</head><body>${announceMarkup}
<script>window.atParse = { hidden: document.getElementById('pre-ab').hidden, height: document.getElementById('pre-ab').getBoundingClientRect().height };</script>
<p id="pre-after">below the bar</p></body></html>`,

    // Under the nonce policy: the check carries the nonce and runs.
    'csp-announce.html': `<!doctype html><html><head><meta charset="utf-8">
<style>${css}</style>
${includeWithNonce}
<script nonce="t3st" defer src="alpine.js"></script>
</head><body>${announceNonceMarkup}
<script nonce="t3st">window.atParse = { hidden: document.getElementById('pre-ab').hidden };</script></body></html>`,

    // And without it: the policy blocks the check, and dsDismiss puts the
    // bar away once Alpine starts instead.
    'csp-announce-without.html': `<!doctype html><html><head><meta charset="utf-8">
<style>${css}</style>
${includeWithNonce}
<script nonce="t3st" defer src="alpine.js"></script>
</head><body>${announceMarkup}
<script nonce="t3st">window.atParse = { hidden: document.getElementById('pre-ab').hidden };</script></body></html>`,

    // A counter below the fold, for animateIn.
    'roll-in.html': `<!doctype html><html><head><meta charset="utf-8">
<style>${css}</style>
${include}
<script defer src="alpine.js"></script>
</head><body>${rollInMarkup}</body></html>`,

    // A server counter under Livewire's Alpine, which carries Alpine.morph,
    // with the include, and without it: the roll is an enhancement, so the
    // morphed number must arrive either way.
    'roll-morph.html': `<!doctype html><html><head><meta charset="utf-8">
<style>${css}</style>
${include}
</head><body>${rollBefore}
<script>window.rollNext = ${JSON.stringify(rollAfter)};</script>
<script src="livewire.js"></script>
</body></html>`,

    'roll-morph-missing.html': `<!doctype html><html><head><meta charset="utf-8">
<style>${css}</style>
</head><body>${rollBefore}
<script>window.rollNext = ${JSON.stringify(rollAfter)};</script>
<script src="livewire.js"></script>
</body></html>`,

    // Served under the policy the CSP pages use: only a script with the
    // nonce runs.
    'csp-theme.html': `<!doctype html><html><head><meta charset="utf-8">
${themeScriptNonce}
</head><body></body></html>`,
};

for (const [name, html] of Object.entries(hostPages)) await writeFile(join(TMP, name), html);

/*
  A real Livewire page, rendered and served by Laravel through
  scripts/request.php: Livewire injects its own script at the end of the body
  and serves it from its own route, so this is host setup (c) exactly as a host
  runs it, and the component is a real one, bound with wire:model.
*/
await writeFile(join(TMP, 'livewire.blade.php'), `<!doctype html><html><head><meta charset="utf-8">
<x-ds::scripts />
${orderProbe}
</head><body><livewire:binding-host /></body></html>`);

/** Open a host page with its own error log, so its errors are judged on their own. */
async function openHost(name, origin = ORIGIN) {
    const tab = await context.newPage();
    const errors = [];
    const uncaught = [];
    tab.on('pageerror', (e) => (errors.push(e.message), uncaught.push(e.message)));
    tab.on('console', (m) => ['error', 'warning'].includes(m.type()) && errors.push(m.text()));
    await tab.goto(`${origin}/${name}`, { waitUntil: 'networkidle' });
    await tab.waitForTimeout(300);
    return { tab, errors, uncaught };
}

const copiesOn = async (tab) => {
    await tab.click('#clip-copy');
    await tab.waitForTimeout(100);
    const text = await tab.evaluate(() => navigator.clipboard.readText());
    const state = await tab.locator('#clip-state').innerText();
    return (text === 'sk_live_42' && state === 'copied') || `clipboard ${JSON.stringify(text)}, state ${state}`;
};

group('host setup (b): Alpine bundled by the host, Alpine.plugin(bladeUi)');
{
    const { tab, errors } = await openHost('setup-b.html');

    await check('the plugin works', () => copiesOn(tab));
    await check('plugin twice plus the include: each name registered exactly once', async () => {
        const names = await tab.evaluate(() => window.dsRegistered);
        const expected = ['dsClipboard', 'dsOtp', 'dsTagInput', 'dsNumberField', 'dsPassword', 'dsPasswordStrength', 'dsToolbar', 'dsToggleGroup', 'dsPopover', 'dsHoverCard', 'dsContextMenu', 'dsTheme', 'dsDismiss', 'dsConfirm', 'dsHold', 'dsConfirmDialog', 'dsRoll'];
        return JSON.stringify(names) === JSON.stringify(expected) || `registered ${JSON.stringify(names)}`;
    });
    await check('no errors or warnings on the page', async () => !errors.length || errors.join(' | '));
    await tab.close();
}

group('host setup (c): Livewire\'s bundled Alpine, include loaded twice');
{
    const { tab, errors } = await openHost('setup-c.html');

    await check('Livewire started the Alpine it bundles', async () =>
        (await tab.evaluate(() => Boolean(window.Livewire && window.Alpine?.version))) || 'no Livewire or no Alpine on the page');
    await check('the include, from the head, had registered when Livewire started Alpine', async () =>
        (await tab.evaluate(() => window.dsRegisteredAtInit)) === true || 'registered late, or never');
    await check('the behaviour registered on it and works', () => copiesOn(tab));
    await check('no errors or warnings on the page', async () => !errors.length || errors.join(' | '));
    await tab.close();
}

group('wire:model on otp-input, tag-input, chip-group, radio-cards, segmented-control and toggle-group, in a real Livewire component');
{
    const { tab, errors } = await openHost('_ds/livewire', LARAVEL);
    const wire = (name) => tab.evaluate((n) => JSON.parse(JSON.stringify(window.Livewire.first()[n])), name);
    const lwBoxes = () => tab.evaluate(() =>
        [...document.querySelectorAll('[id^="lw-otp-"][data-ds-otp-box]')].map((b) => b.value).join(''));
    const lwTags = () => tab.evaluate(() =>
        [...document.querySelectorAll('#lw-tags ~ * [data-ds-tag-value], [data-ds-tag-value]')]
            .filter((i) => !i.closest('template')).map((i) => i.value));

    await check('Livewire injected its own script, and the include had registered when it started Alpine', async () => {
        if (!(await tab.evaluate(() => Boolean(window.Livewire?.first())))) return 'no Livewire component on the page';
        return (await tab.evaluate(() => window.dsRegisteredAtInit)) === true || 'registered late, or never';
    });

    await check('otp: a digit the reader types reaches $wire.code', async () => {
        await tab.focus('#lw-otp-3');
        await tab.keyboard.type('3');
        await tab.waitForTimeout(100);
        return (await wire('code')) === '123' || `$wire.code is ${JSON.stringify(await wire('code'))}`;
    });

    await check('otp: a code Livewire sets on $wire.code is written into the boxes', async () => {
        await tab.evaluate(() => { window.Livewire.first().code = '987654'; });
        await tab.waitForTimeout(100);
        const posted = await tab.locator('input[name="code"]').inputValue();
        return ((await lwBoxes()) === '987654' && posted === '987654') || `boxes ${JSON.stringify(await lwBoxes())}, posted ${JSON.stringify(posted)}`;
    });

    await check('tags: a tag the reader adds reaches $wire.tags', async () => {
        await tab.focus('#lw-tags');
        await tab.keyboard.type('ada@example.com');
        await tab.keyboard.press('Enter');
        await tab.waitForTimeout(100);
        return JSON.stringify(await wire('tags')) === '["maya@example.com","ada@example.com"]' || `$wire.tags is ${JSON.stringify(await wire('tags'))}`;
    });

    await check('tags: a list Livewire sets on $wire.tags is rendered as chips', async () => {
        await tab.evaluate(() => { window.Livewire.first().tags = ['zed@example.com']; });
        await tab.waitForTimeout(100);
        return JSON.stringify(await lwTags()) === '["zed@example.com"]' || `the chips post ${JSON.stringify(await lwTags())}`;
    });

    await check('chips: a chip the reader presses reaches $wire.filters', async () => {
        await tab.click('#lw-chips input[value="billing"] >> xpath=..');
        await tab.waitForTimeout(100);
        return JSON.stringify(await wire('filters')) === '["bug","billing"]' || `$wire.filters is ${JSON.stringify(await wire('filters'))}`;
    });

    await check('chips: a list Livewire sets on $wire.filters checks those chips', async () => {
        await tab.evaluate(() => { window.Livewire.first().filters = ['billing']; });
        await tab.waitForTimeout(100);
        const on = await tab.evaluate(() => [...document.querySelectorAll('#lw-chips input:checked')].map((i) => i.value));
        return JSON.stringify(on) === '["billing"]' || `checked: ${JSON.stringify(on)}`;
    });

    await check('radio cards: the card the reader presses reaches $wire.plan', async () => {
        await tab.click('#lw-plan input[value="pro"] >> xpath=ancestor::label');
        await tab.waitForTimeout(100);
        return (await wire('plan')) === 'pro' || `$wire.plan is ${JSON.stringify(await wire('plan'))}`;
    });

    await check('radio cards: a plan Livewire sets on $wire.plan checks that card', async () => {
        await tab.evaluate(() => { window.Livewire.first().plan = 'free'; });
        await tab.waitForTimeout(100);
        const on = await tab.evaluate(() => document.querySelector('#lw-plan input:checked')?.value);
        return on === 'free' || `checked: ${JSON.stringify(on)}`;
    });

    await check('segmented control: the segment the reader presses reaches $wire.view', async () => {
        await tab.click('#lw-view input[value="board"] >> xpath=..');
        await tab.waitForTimeout(100);
        return (await wire('view')) === 'board' || `$wire.view is ${JSON.stringify(await wire('view'))}`;
    });

    await check('segmented control: a view Livewire sets on $wire.view checks it, and the highlight follows', async () => {
        await tab.evaluate(() => { window.Livewire.first().view = 'list'; });
        await tab.waitForTimeout(300);
        return tab.evaluate(() => {
            const input = document.querySelector('#lw-view input:checked');
            if (input?.value !== 'list') return `checked: ${input?.value}`;
            const h = document.querySelector('#lw-view [data-ds-glide-highlight]').getBoundingClientRect();
            const l = input.parentElement.getBoundingClientRect();
            return Math.abs(h.left - l.left) < 1 || `the highlight is ${h.left - l.left}px off the chosen segment`;
        });
    });

    await check('toggle group: a toggle the reader presses reaches $wire.marks', async () => {
        await tab.click('#lw-marks [data-ds-value="italic"]');
        await tab.waitForTimeout(100);
        return JSON.stringify(await wire('marks')) === '["bold","italic"]' || `$wire.marks is ${JSON.stringify(await wire('marks'))}`;
    });

    await check('toggle group: a list Livewire sets on $wire.marks presses those toggles', async () => {
        await tab.evaluate(() => { window.Livewire.first().marks = ['italic']; });
        await tab.waitForTimeout(100);
        const on = await tab.evaluate(() =>
            [...document.querySelectorAll('#lw-marks [data-ds-toggle]')].map((t) => t.getAttribute('aria-pressed')).join(' '));
        return on === 'false true' || `aria-pressed: ${on}`;
    });

    await check('no errors or warnings on the page', async () => !errors.length || errors.join(' | '));
    await tab.close();
}

group('a host with a Content Security Policy');
{
    const { tab, errors } = await openHost('csp.html');

    await check('the nonce reaches the script element, and the policy lets it run', () => copiesOn(tab));
    await check('no violations reported', async () => !errors.length || errors.join(' | '));
    await tab.close();
}
{
    const { tab, errors } = await openHost('csp-without-nonce.html');

    await check('the same policy blocks the include without the nonce, so the check above means something', async () =>
        errors.some((e) => /Content Security Policy/i.test(e)) || `nothing was blocked: ${errors.join(' | ') || 'no errors at all'}`);
    await tab.close();
}

group('a segmented control in a host WITHOUT the script (the glide is an enhancement)');
{
    const { tab, errors } = await openHost('glide-missing.html');

    await check('the markup reached the page', async () =>
        (await tab.locator('#seg-rtl input').count()) === 3 || 'the specimen was not found in the rendered page');
    await check('nothing in the console: a skipped glide is not an error', async () => !errors.length || errors.join(' | '));
    await check('the chosen segment and the current link draw their own face, and the highlight stays hidden', async () => {
        const r = await tab.evaluate(() => ({
            week: getComputedStyle(document.querySelector('#seg-rtl input[value="week"]').parentElement).backgroundColor,
            day: getComputedStyle(document.querySelector('#seg-rtl input[value="day"]').parentElement).backgroundColor,
            link: getComputedStyle(document.querySelector('#seg-links a[aria-current="page"]')).backgroundColor,
            hidden: [...document.querySelectorAll('[data-ds-glide-highlight]')].every((h) => getComputedStyle(h).display === 'none'),
        }));
        return (r.week === 'rgb(255, 255, 255)' && r.link === 'rgb(255, 255, 255)' && r.day === 'rgba(0, 0, 0, 0)' && r.hidden) || JSON.stringify(r);
    });
    await check('a press still chooses and posts', async () => {
        await tab.click('#seg-rtl input[value="month"] >> xpath=..');
        await tab.waitForTimeout(300);
        const r = await tab.evaluate(() => ({
            posted: new FormData(document.getElementById('form')).get('period'),
            bg: getComputedStyle(document.querySelector('#seg-rtl input[value="month"]').parentElement).backgroundColor,
        }));
        return (r.posted === 'month' && r.bg === 'rgb(255, 255, 255)') || JSON.stringify(r);
    });
    await tab.close();
}

for (const [page, what] of [['charts-missing.html', 'Alpine and no include'], ['charts-static.html', 'no Alpine at all']]) {
    group(`charts in a host with ${what} (x-ds-chart is an enhancement)`);
    const { tab, errors } = await openHost(page);

    await check('the markup reached the page', async () =>
        (await tab.locator('#charts [data-ds-chart-plot]').count()) === 5 || 'the specimen was not found in the rendered page');
    await check('nothing in the console', async () => !errors.length || errors.join(' | '));
    await check('every chart is an image named by its summary; no mark is focusable, no readout shows', async () => {
        const r = await tab.evaluate(() => {
            const plots = [...document.querySelectorAll('#charts [data-ds-chart-plot]')];
            return {
                roles: [...new Set(plots.map((p) => p.getAttribute('role')))],
                named: plots.every((p) => (p.getAttribute('aria-label') ?? '').length > 10),
                focusable: document.querySelectorAll('#charts svg [tabindex]').length,
                shown: [...document.querySelectorAll('#charts [data-ds-chart-readout]')].filter((r) => !r.hidden).length,
                keys: document.querySelectorAll('#charts [data-ds-chart-key][role="button"]').length,
            };
        });
        return (JSON.stringify(r.roles) === '["img"]' && r.named && r.focusable === 0 && r.shown === 0 && r.keys === 0) || JSON.stringify(r);
    });
    await check('Tab passes the charts by', async () => {
        await tab.focus('#ch-before');
        await tab.keyboard.press('Tab');
        const at = await tab.evaluate(() => document.activeElement.closest('#charts') ? document.activeElement.tagName : 'left');
        return at === 'left' || `focus went to ${at}`;
    });
    await tab.close();
}

group('the overlays that predate x-ds-anchor, in a host WITHOUT the script (the anchor is an enhancement)');
{
    const { tab, errors } = await openHost('anchor-missing.html');

    /*
      Each opens where its absolute classes put it: `position: absolute`, no
      inline placement written over it, and beside its trigger on the side
      its placement names. Exactly how every one of them behaved in 1.x.
    */
    const fallbacks = [
        { name: 'dropdown', trigger: '#fb-dropdown-trigger', panel: '#fb-dropdown [role=menu]', side: 'bottom', gap: 8 },
        { name: 'select', trigger: '#fb-select', panel: '#fb-select-listbox', side: 'bottom', gap: 4 },
        {
            name: 'tooltip', trigger: '#fb-tip-trigger', panel: '[data-ds-tooltip]:has(#fb-tip-trigger) [role=tooltip]', side: 'top', gap: 8,
            open: (t) => t.focus('#fb-tip-trigger'), close: (t) => t.keyboard.press('Escape'),
        },
        { name: 'date picker', trigger: '#fb-date', panel: '[x-data]:has(> #fb-date) [role=dialog]', side: 'bottom', gap: 4 },
        // Measured from the field the list hangs from, the text box's parent.
        { name: 'combobox', trigger: '#fb-combobox', panel: '#fb-combobox-listbox', side: 'bottom', gap: 4, from: 'parent' },
    ];

    await check('the markup reached the page', async () =>
        (await tab.locator('#fb-dropdown-trigger').count()) === 1 || 'the fallback specimens were not found');

    for (const { name, trigger, panel, side, gap, open, close, from } of fallbacks) {
        await check(`${name}: opens at its fallback position, beside its trigger`, async () => {
            await (open ? open(tab) : tab.click(trigger));
            await tab.waitForTimeout(350);
            const r = await tab.evaluate(([p, t, parent]) => {
                const el = document.querySelector(p);
                const a = el.getBoundingClientRect();
                const at = document.querySelector(t);
                const b = (parent ? at.parentElement : at).getBoundingClientRect();
                const s = getComputedStyle(el);
                return {
                    shown: s.display !== 'none',
                    position: s.position,
                    inline: el.style.top || el.style.left,
                    below: Math.round(a.top - b.bottom),
                    above: Math.round(b.top - a.bottom),
                };
            }, [panel, trigger, from === 'parent']);
            await (close ? close(tab) : tab.keyboard.press('Escape'));
            await tab.waitForTimeout(250);
            if (!r.shown) return 'it did not open';
            if (r.position !== 'absolute' || r.inline) return `position ${r.position}, inline ${JSON.stringify(r.inline)}: something placed it`;
            const measured = side === 'bottom' ? r.below : r.above;
            return measured === gap || `${measured}px ${side === 'bottom' ? 'below' : 'above'} its trigger, not ${gap}`;
        });
    }

    await check('nothing in the console: a skipped anchor is not an error', async () => !errors.length || errors.join(' | '));
    await tab.close();
}

group('the destructive-action guards in a host WITHOUT the script, and with no Alpine at all');
for (const [name, label] of [['guards-missing.html', 'no script'], ['guards-static.html', 'no Alpine']]) {
    const { tab } = await openHost(name);

    await check(`${label}: the markup reached the page`, async () =>
        (await tab.locator('#g-hb').count()) === 1 || 'the guard specimens were not found');

    await check(`${label}: the confirm button's confirm control is not on screen, and its button does nothing`, async () => {
        await tab.click('[data-ds-confirm]:has(#g-cb) [data-ds-confirm-trigger]');
        await tab.waitForTimeout(200);
        const r = await tab.evaluate(() => ({
            control: document.getElementById('g-cb').getClientRects().length,
            sent: window.gSent ?? 0,
            clicks: window.gClicks ?? 0,
        }));
        return (r.control === 0 && r.sent === 0 && r.clicks === 0) || JSON.stringify(r);
    });

    await check(`${label}: a press on the hold button submits nothing`, async () => {
        await tab.click('#g-hb');
        await tab.keyboard.press('Enter');
        await tab.waitForTimeout(200);
        const r = await tab.evaluate(() => ({ sent: window.gSent ?? 0, type: document.getElementById('g-hb').type }));
        return (r.sent === 0 && r.type === 'button') || JSON.stringify(r);
    });

    await check(`${label}: the confirm popover never opens, so its confirm control is out of reach`, async () => {
        await tab.click('#g-cp-trigger');
        await tab.waitForTimeout(200);
        const r = await tab.evaluate(() => ({ control: document.getElementById('g-cp').getClientRects().length, sent: window.gSent ?? 0 }));
        return (r.control === 0 && r.sent === 0) || JSON.stringify(r);
    });

    await check(`${label}: the bar shows no close control that would do nothing`, async () => {
        const visibility = await tab.evaluate(() => getComputedStyle(document.querySelector('#g-ab [data-ds-dismiss]')).visibility);
        return visibility === 'hidden' || `the close control is ${visibility}`;
    });

    await tab.close();
}

group('announcement bar: a remembered dismissal, before the first paint');
{
    const { tab, errors } = await openHost('announce-pre.html');

    await check('not dismissed yet: shown, and the check left it alone', async () => {
        const r = await tab.evaluate(() => ({ atParse: window.atParse, height: document.getElementById('pre-ab').getBoundingClientRect().height }));
        return (!r.atParse.hidden && r.height > 0) || JSON.stringify(r);
    });

    await tab.click('#pre-ab [data-ds-dismiss]');
    await tab.waitForTimeout(400);
    await tab.reload({ waitUntil: 'networkidle' });
    await tab.waitForTimeout(300);

    await check('dismissed, then reloaded: hidden at parse, before Alpine, taking no room', async () => {
        const r = await tab.evaluate(() => ({ atParse: window.atParse, height: document.getElementById('pre-ab').getBoundingClientRect().height }));
        return (r.atParse.hidden === true && r.atParse.height === 0 && r.height === 0) || JSON.stringify(r);
    });

    await check('storage that throws: the check is caught and the bar shows', async () => {
        await tab.addInitScript(() => {
            Object.defineProperty(window, 'localStorage', { get() { throw new Error('blocked'); } });
        });
        await tab.reload({ waitUntil: 'networkidle' });
        await tab.waitForTimeout(300);
        const r = await tab.evaluate(() => ({ atParse: window.atParse, height: document.getElementById('pre-ab').getBoundingClientRect().height }));
        return (!r.atParse.hidden && r.height > 0) || JSON.stringify(r);
    });

    await check('nothing in the console', async () => !errors.length || errors.join(' | '));
    await tab.close();
}

group('announcement bar under a Content Security Policy');
{
    const seed = await openHost('csp-announce.html');
    await seed.tab.evaluate(() => localStorage.setItem('pre-ab', '1'));
    await seed.tab.close();

    const { tab, errors } = await openHost('csp-announce.html');
    await check('with the nonce, the check runs before the first paint', async () => {
        const hidden = await tab.evaluate(() => window.atParse.hidden);
        return hidden === true || 'it was not hidden at parse';
    });
    await check('and nothing was blocked', async () => !errors.length || errors.join(' | '));
    await tab.close();

    const without = await openHost('csp-announce-without.html');
    await check('without the nonce the check is blocked, and dsDismiss puts the bar away when Alpine starts', async () => {
        const r = await without.tab.evaluate(() => ({
            atParse: window.atParse.hidden,
            height: document.getElementById('pre-ab').getBoundingClientRect().height,
            blocked: true,
        }));
        const blocked = without.errors.some((e) => /Content Security Policy/i.test(e));
        return (r.atParse === false && r.height === 0 && blocked) || `${JSON.stringify(r)}, blocked ${blocked}`;
    });
    await without.tab.evaluate(() => localStorage.removeItem('pre-ab'));
    await without.tab.close();
}

// ---------------------------------------------------------------- theme

/*
  A context of its own: the theme lives in localStorage and on <html>, and
  two tabs of one context share the storage, which is what the cross-tab
  check needs. Starts in a light system with nothing stored.
*/
const themeContext = await browser.newContext({ viewport: { width: 1000, height: 700 }, colorScheme: 'light' });
await themeContext.route(`${ORIGIN}/**`, serveHostPage);
await themeContext.route(`${LARAVEL}/**`, serveFromLaravel);

const themeTab = async (name = 'theme.html') => {
    const tab = await themeContext.newPage();
    const errors = [];
    tab.on('pageerror', (e) => errors.push(e.message));
    tab.on('console', (m) => ['error', 'warning'].includes(m.type()) && errors.push(m.text()));
    await tab.goto(`${ORIGIN}/${name}`, { waitUntil: 'networkidle' });
    await tab.waitForTimeout(250);
    return { tab, errors };
};
const rootState = (tab) => tab.evaluate(() => ({
    dark: document.documentElement.classList.contains('dark'),
    light: document.documentElement.classList.contains('light'),
    scheme: document.documentElement.style.colorScheme,
    stored: localStorage.getItem('ds-theme'),
}));
const checkedIn = (tab, id) => tab.evaluate((i) => document.querySelector(`#${i} input:checked`)?.value ?? null, id);
const cycleLabel = (tab) => tab.locator('#ts-b [data-ds-theme-cycle]').getAttribute('aria-label');

group('theme script (<x-ds::theme-script />, before the first paint, no Alpine)');
{
    const { tab, errors } = await themeTab('theme-pre.html');

    await check('nothing stored, a light system: `light` on <html> before the stylesheet or the body', async () => {
        const at = await tab.evaluate(() => window.atParse);
        return (at.classes === 'light' && at.scheme === 'light') || `at parse: ${JSON.stringify(at)}`;
    });

    await tab.emulateMedia({ colorScheme: 'dark' });
    await tab.reload({ waitUntil: 'networkidle' });
    await check('nothing stored, a dark system: `dark`, and color-scheme dark', async () => {
        const at = await tab.evaluate(() => window.atParse);
        return (at.classes === 'dark' && at.scheme === 'dark') || `at parse: ${JSON.stringify(at)}`;
    });

    await tab.evaluate(() => localStorage.setItem('ds-theme', 'light'));
    await tab.reload({ waitUntil: 'networkidle' });
    await check('a stored "light" beats a dark system', async () =>
        (await tab.evaluate(() => window.atParse.classes)) === 'light' || `at parse: ${await tab.evaluate(() => window.atParse.classes)}`);

    await tab.emulateMedia({ colorScheme: 'light' });
    await tab.evaluate(() => localStorage.setItem('ds-theme', 'dark'));
    await tab.reload({ waitUntil: 'networkidle' });
    await check('a stored "dark" beats a light system, and the page paints dark', async () => {
        const at = await tab.evaluate(() => window.atParse.classes);
        const ground = await tab.evaluate(() => getComputedStyle(document.body).backgroundColor);
        await tab.evaluate(() => localStorage.removeItem('ds-theme'));
        return (at === 'dark' && ground !== 'rgb(255, 255, 255)') || `at parse ${at}, body ${ground}`;
    });

    await check('nothing in the console', async () => !errors.length || errors.join(' | '));
    await tab.close();

    // localStorage that throws on access, as a blocked-cookies browser has it.
    const blocked = await themeContext.newPage();
    const blockedErrors = [];
    blocked.on('pageerror', (e) => blockedErrors.push(e.message));
    await blocked.addInitScript(() => {
        Object.defineProperty(window, 'localStorage', { get() { throw new DOMException('denied', 'SecurityError'); } });
    });
    await blocked.goto(`${ORIGIN}/theme-pre.html`, { waitUntil: 'networkidle' });
    await check('storage that throws: no error, and the system\'s theme', async () => {
        const at = await blocked.evaluate(() => window.atParse.classes);
        return (at === 'light' && !blockedErrors.length) || `at parse ${at}, errors ${blockedErrors.join(' | ')}`;
    });
    await blocked.close();
}

{
    const csp = await themeContext.newPage();
    await csp.goto(`${ORIGIN}/csp-theme.html`, { waitUntil: 'networkidle' });
    await check('under a nonce-only Content Security Policy, the nonce lets it run', async () =>
        (await csp.evaluate(() => document.documentElement.className)) === 'light' || 'the snippet did not run');
    await csp.close();
}

group('theme switcher (dsTheme): light, dark, system');
{
    const { tab, errors } = await themeTab();

    await check('nothing stored: every switcher shows "system", the button says so', async () => {
        const a = await checkedIn(tab, 'ts-a');
        const label = await cycleLabel(tab);
        return (a === 'system' && label === 'Theme: System') || `segmented ${a}, button "${label}"`;
    });

    await check('the segmented one is the segmented control: a fieldset of three radios, named', async () => {
        const r = await tab.evaluate(() => {
            const set = document.querySelector('#ts-a fieldset');
            return { legend: set?.querySelector('legend')?.textContent.trim(), radios: set?.querySelectorAll('input[type=radio]').length };
        });
        return (r.legend === 'Theme' && r.radios === 3) || JSON.stringify(r);
    });

    await tab.click('#ts-a label:has(input[value=dark])');
    await tab.waitForTimeout(400);
    await check('choosing dark: stored, `dark` on <html>, color-scheme dark, and dark: utilities apply', async () => {
        const s = await rootState(tab);
        const probe = await tab.evaluate(() => getComputedStyle(document.getElementById('ts-probe')).color);
        const accent = await tab.evaluate(() => {
            const el = document.createElement('span');
            el.className = 'text-accent';
            document.body.append(el);
            const c = getComputedStyle(el).color;
            el.remove();
            return c;
        });
        return (s.dark && !s.light && s.scheme === 'dark' && s.stored === 'dark' && probe === accent) || `${JSON.stringify(s)}, probe ${probe} vs ${accent}`;
    });

    await check('the other switcher on the same key follows; the one on another key does not', async () => {
        const label = await cycleLabel(tab);
        const other = await checkedIn(tab, 'ts-other');
        const otherStored = await tab.evaluate(() => localStorage.getItem('other-theme'));
        return (label === 'Theme: Dark' && other === 'system' && otherStored === null) || `button "${label}", other ${other}, other key ${otherStored}`;
    });

    await check('the button steps on: dark, system, light, and the segmented one follows', async () => {
        const seen = [];
        for (let i = 0; i < 3; i++) {
            await tab.click('#ts-b [data-ds-theme-cycle]');
            await tab.waitForTimeout(350);
            seen.push(`${await cycleLabel(tab)}/${await checkedIn(tab, 'ts-a')}/${(await rootState(tab)).stored}`);
        }
        return seen.join(', ') === 'Theme: System/system/null, Theme: Light/light/light, Theme: Dark/dark/dark' || seen.join(', ');
    });

    await check('"system" clears the key rather than storing it', async () => {
        await tab.click('#ts-a label:has(input[value=system])');
        await tab.waitForTimeout(350);
        const s = await rootState(tab);
        return (s.stored === null && s.light && !s.dark) || JSON.stringify(s);
    });

    await check('on "system", it follows the system as it changes', async () => {
        await tab.emulateMedia({ colorScheme: 'dark' });
        await tab.waitForTimeout(150);
        const dark = await rootState(tab);
        await tab.emulateMedia({ colorScheme: 'light' });
        await tab.waitForTimeout(150);
        const light = await rootState(tab);
        return (dark.dark && !dark.light && light.light && !light.dark) || `dark system: ${JSON.stringify(dark)}, light: ${JSON.stringify(light)}`;
    });

    await check('a chosen theme does not follow the system', async () => {
        await tab.click('#ts-a label:has(input[value=light])');
        await tab.waitForTimeout(350);
        await tab.emulateMedia({ colorScheme: 'dark' });
        await tab.waitForTimeout(150);
        const s = await rootState(tab);
        await tab.emulateMedia({ colorScheme: 'light' });
        return (s.light && !s.dark) || JSON.stringify(s);
    });

    await check('another tab follows through the storage event: page and switchers', async () => {
        const { tab: second } = await themeTab();
        const before = await checkedIn(second, 'ts-a');
        await tab.click('#ts-a label:has(input[value=dark])');
        await tab.waitForTimeout(400);
        const s = await rootState(second);
        const a = await checkedIn(second, 'ts-a');
        const label = await cycleLabel(second);
        await second.close();
        return (before === 'light' && s.dark && a === 'dark' && label === 'Theme: Dark') || `started ${before}; then ${JSON.stringify(s)}, ${a}, "${label}"`;
    });

    await check('a reload starts from the stored choice', async () => {
        await tab.reload({ waitUntil: 'networkidle' });
        await tab.waitForTimeout(250);
        const s = await rootState(tab);
        return (s.dark && (await checkedIn(tab, 'ts-a')) === 'dark' && (await cycleLabel(tab)) === 'Theme: Dark') || JSON.stringify(s);
    });

    // Counted around the browser's own, which still runs the change.
    const countTransitions = () => tab.evaluate(() => {
        window.vtCalls = 0;
        window.vtFlag = [];
        const original = document.startViewTransition.bind(document);
        document.startViewTransition = (update) => {
            window.vtCalls++;
            window.vtFlag.push(document.documentElement.hasAttribute('data-ds-theme-changing'));
            return original(update);
        };
    });

    await check('with motion allowed: one view transition per change, flagged for the timing rule, flag removed after', async () => {
        await countTransitions();
        await tab.click('#ts-a label:has(input[value=light])');
        await tab.waitForTimeout(600);
        const r = await tab.evaluate(() => ({ calls: window.vtCalls, flagged: window.vtFlag, left: document.documentElement.hasAttribute('data-ds-theme-changing') }));
        const s = await rootState(tab);
        return (r.calls === 1 && r.flagged[0] === true && !r.left && s.light) || `${JSON.stringify(r)}, ${JSON.stringify(s)}`;
    });

    await check('the timing rule is the dialog\'s, in the shipped theme.css', async () => {
        const rule = await tab.evaluate(() => [...document.styleSheets].flatMap((sheet) => [...sheet.cssRules])
            .find((r) => r.selectorText?.includes('data-ds-theme-changing'))?.style.animationDuration);
        return rule === 'var(--ds-motion-duration-dialog-enter)' || `found ${rule}`;
    });

    await check('with reduced motion: no view transition, the change at once', async () => {
        await tab.emulateMedia({ reducedMotion: 'reduce' });
        await countTransitions();
        await tab.click('#ts-a label:has(input[value=dark])');
        await tab.waitForTimeout(200);
        const calls = await tab.evaluate(() => window.vtCalls);
        const s = await rootState(tab);
        await tab.emulateMedia({ reducedMotion: 'no-preference' });
        return (calls === 0 && s.dark) || `${calls} transitions, ${JSON.stringify(s)}`;
    });

    await check('nothing in the console', async () => !errors.length || errors.join(' | '));
    await tab.evaluate(() => localStorage.clear());
    await tab.close();
}

await themeContext.close();

group('counter animateIn (x-ds-roll.in), below the fold');
{
    const { tab, errors } = await openHost('roll-in.html');

    await check('unseen, the face shows zeros of the same shape and the hidden text the number', async () => {
        const s = await counterState(tab, 'ct-in');
        return (s.face === '000' && s.text === '742') || JSON.stringify(s);
    });
    await check('scrolled into view, it rolls up to the number and settles', async () => {
        await watchRoll(tab, 'ct-in');
        await tab.locator('#ct-in').scrollIntoViewIfNeeded();
        await tab.waitForTimeout(700);
        const s = await counterState(tab, 'ct-in');
        const rolled = await tab.evaluate(() => window.rolled);
        return (rolled && s.face === '742' && s.columns === 0) || `${JSON.stringify(s)}, rolled ${rolled}`;
    });
    await check('nothing in the console', async () => !errors.length || errors.join(' | '));
    await tab.close();
}

group('counter re-rendered by a morph (Livewire\'s Alpine.morph), with the script');
{
    const { tab, errors } = await openHost('roll-morph.html');

    await check('Livewire started its Alpine, which carries morph', async () =>
        (await tab.evaluate(() => typeof window.Alpine?.morph === 'function')) || 'no Alpine.morph on the page');
    await check('the morph rolls the digits and settles on the new number', async () => {
        await watchRoll(tab, 'ct-morph');
        await tab.evaluate(() => window.Alpine.morph(document.getElementById('ct-morph'), window.rollNext));
        await tab.waitForTimeout(700);
        const s = await counterState(tab, 'ct-morph');
        const rolled = await tab.evaluate(() => window.rolled);
        return (rolled && s.face === '1,003' && s.text === '1,003' && s.value === '1003' && s.columns === 0) || `${JSON.stringify(s)}, rolled ${rolled}`;
    });
    await check('nothing in the console', async () => !errors.length || errors.join(' | '));
    await tab.close();
}

group('counter re-rendered by a morph in a host WITHOUT the script (the roll is an enhancement)');
{
    const { tab, errors } = await openHost('roll-morph-missing.html');

    await check('the morphed number arrives, face and hidden text, with no roll', async () => {
        if (!(await tab.evaluate(() => typeof window.Alpine?.morph === 'function'))) return 'no Alpine.morph on the page';
        await watchRoll(tab, 'ct-morph');
        await tab.evaluate(() => window.Alpine.morph(document.getElementById('ct-morph'), window.rollNext));
        await tab.waitForTimeout(200);
        const s = await counterState(tab, 'ct-morph');
        const rolled = await tab.evaluate(() => window.rolled);
        return (!rolled && s.face === '1,003' && s.text === '1,003') || `${JSON.stringify(s)}, rolled ${rolled}`;
    });
    await check('nothing in the console: a skipped roll is not an error', async () => !errors.length || errors.join(' | '));
    await tab.close();
}

group('a host WITHOUT the script');
{
    const { tab, errors, uncaught } = await openHost('missing.html');

    await check('the console names the missing include, not just the missing name', async () => {
        const said = errors.find((e) => e.includes('dsClipboard is not registered'));
        if (!said) return `nothing said why — the page logged: ${errors.join(' | ') || 'nothing'}`;
        return said.includes('<x-ds::scripts />') || `the error does not name the include: ${said}`;
    });
    await check('as an uncaught error too, so an error tracker sees it', async () =>
        uncaught.some((e) => e.includes('dsClipboard is not registered'))
        || `uncaught: ${uncaught.join(' | ') || 'nothing'}`);
    await tab.close();
}

// ---------------------------------------------------------------- summary

await browser.close();
await rm(TMP, { recursive: true, force: true });

if (jsErrors.length) {
    console.log('\n\x1b[31mJavaScript errors on the page:\x1b[0m');
    for (const error of [...new Set(jsErrors)]) console.log(`  ${error}`);
    failed += new Set(jsErrors).size;
}

console.log(
    failed
        ? `\n\x1b[31m${failed} of ${passed + failed} behaviour checks failed\x1b[0m\n`
        : `\n\x1b[32mall ${passed} behaviour checks passed\x1b[0m\n`,
);

process.exit(failed ? 1 : 0);
