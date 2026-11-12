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
try {
    specimens = await render(join(ROOT, 'scripts/behaviour-specimens.blade.php'));
    // The include, rendered through the component a consumer writes in their
    // layout rather than read from dist/, so the page gets exactly what a host
    // gets.
    await writeFile(join(TMP, 'include.blade.php'), '<x-ds::scripts />');
    include = await render(join(TMP, 'include.blade.php'));
} catch (err) {
    console.error('\nrender failed — run `composer install`?\n');
    console.error(String(err.stderr || err).slice(0, 800));
    process.exit(1);
}

await writeFile(join(TMP, 'specimens.html'), specimens);

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

await writeFile(
    join(TMP, 'page.html'),
    `<!doctype html><html><head><meta charset="utf-8">
<style>${css}</style>
<!-- No hand-written [x-cloak] rule here on purpose. It ships in dist/theme.css,
    which is imported above, so this page proves the SHIPPED one works — an
    injected copy would hide a regression in the thing consumers actually get. -->
${include}
<script defer src="alpine.js"></script>
</head><body>${specimens}</body></html>`,
);

// ------------------------------------------------------------ 2. drive it

const browser = await chromium.launch({ executablePath: CHROME });
const context = await browser.newContext({
    viewport: { width: 1200, height: 1000 },
    permissions: ['clipboard-read', 'clipboard-write'],
});
const page = await context.newPage();

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

await check('hidden until asked for', async () =>
    (await page.locator('[role=tooltip]').isVisible()) === false || 'visible on load');

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
    return (await page.locator('[role=tooltip]').isVisible()) || 'invisible to a keyboard';
});

await check('Escape dismisses it (WCAG 1.4.13)', async () => {
    await page.keyboard.press('Escape');
    await settle(200);
    return (await page.locator('[role=tooltip]').isVisible()) === false || 'still visible';
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
    (await page.locator('[role=dialog] [aria-live=polite]').innerText()).includes('September 2026')
    || `shows ${await page.locator('[role=dialog] [aria-live=polite]').innerText()}`);

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
  How to check whether an overlay collides with the viewport.
  
  No popover in this system does collision detection: each takes a `placement`
  and trusts it. This measures the consequence rather than asserting anything
  about it — each trigger is scrolled to the bottom of the window, opened, and
  the popover's box is compared against the viewport.
  
  It REPORTS and does not fail, because the behaviour is known and recorded as a
  gap. It exists so the cost is a number somebody can look at before deciding
  whether floating-ui is worth taking on.
*/
group('overlay edges (reported, not enforced — no popover here does collision detection)');

const overlays = [
    ['dropdown', '#menu-trigger', '[role=menu]'],
    ['select', '#plan', '[role=listbox]'],
    ['date-picker', '#period', '[role=dialog][aria-modal="false"]'],
];

for (const [name, triggerSelector, popoverSelector] of overlays) {
    const overflow = await (async () => {
        await page.evaluate((selector) => {
            document.querySelector(selector).scrollIntoView({ block: 'end' });
        }, triggerSelector);
        await settle(200);

        await page.locator(triggerSelector).click();
        await settle(350);

        const box = await page.locator(popoverSelector).first().boundingBox();
        const height = page.viewportSize().height;

        await page.keyboard.press('Escape');
        await settle(200);

        if (!box) return null;
        return Math.round(box.y + box.height - height);
    })();

    if (overflow === null) {
        console.log(`  \x1b[33m?\x1b[0m ${name.padEnd(12)} could not be measured`);
    } else if (overflow > 0) {
        console.log(`  \x1b[33m!\x1b[0m ${name.padEnd(12)} runs \x1b[33m${overflow}px\x1b[0m below the fold at the bottom of the window`);
    } else {
        console.log(`  \x1b[32m✓\x1b[0m ${name.padEnd(12)} fits (${-overflow}px to spare)`);
    }
}

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
        });
    } catch {
        await route.fulfill({ status: 404, body: '' });
    }
});

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
${include}
</head><body>${clipMarkup}
<script src="livewire.js"></script>
</body></html>`,

    'missing.html': `<!doctype html><html><head><meta charset="utf-8">
<script defer src="alpine.js"></script>
</head><body>${clipMarkup}</body></html>`,
};

for (const [name, html] of Object.entries(hostPages)) await writeFile(join(TMP, name), html);

/** Open a host page with its own error log, so its errors are judged on their own. */
async function openHost(name) {
    const tab = await context.newPage();
    const errors = [];
    const uncaught = [];
    tab.on('pageerror', (e) => (errors.push(e.message), uncaught.push(e.message)));
    tab.on('console', (m) => ['error', 'warning'].includes(m.type()) && errors.push(m.text()));
    await tab.goto(`${ORIGIN}/${name}`, { waitUntil: 'networkidle' });
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
        const expected = ['dsClipboard', 'dsOtp', 'dsTagInput', 'dsNumberField', 'dsPassword', 'dsPasswordStrength'];
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
    await check('the behaviour registered on it and works', () => copiesOn(tab));
    await check('no errors or warnings on the page', async () => !errors.length || errors.join(' | '));
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
