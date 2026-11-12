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

await writeFile(
    join(TMP, 'in.css'),
    `@import "tailwindcss";
@import "${join(ROOT, 'dist/tokens.css')}";
@import "${join(ROOT, 'dist/theme.css')}";
@source "${join(TMP, 'specimens.html')}";
@source "${join(TMP, 'fallback.html')}";
@source "${join(TMP, 'focus.html')}";
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

await context.route(`${ORIGIN}/**`, async (route) => {
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
});

const segMarkup = [
    specimens.match(/<div class="mt-6 w-96" id="seg-rtl"[\s\S]*?<\/fieldset>\s*<\/div>/)?.[0],
    specimens.match(/<div class="mt-6" id="seg-links"[\s\S]*?<\/nav>\s*<\/div>/)?.[0],
].join('');
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
        const expected = ['dsClipboard', 'dsOtp', 'dsTagInput', 'dsNumberField', 'dsPassword', 'dsPasswordStrength', 'dsToolbar', 'dsToggleGroup', 'dsPopover', 'dsHoverCard', 'dsContextMenu'];
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
