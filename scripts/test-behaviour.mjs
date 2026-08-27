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

let specimens;
try {
    const { stdout } = await run(
        'php',
        [join(ROOT, 'scripts/render.php'), join(ROOT, 'scripts/behaviour-specimens.blade.php')],
        { cwd: ROOT, maxBuffer: 32 * 1024 * 1024 },
    );
    specimens = stdout.replace('<!--ds-canary [...ok]-->', '');
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
const alpine = await readFile(join(ROOT, 'node_modules/alpinejs/dist/cdn.min.js'), 'utf8');

await writeFile(
    join(TMP, 'page.html'),
    `<!doctype html><html><head><meta charset="utf-8">
<style>${css}</style>
<!-- No hand-written [x-cloak] rule here on purpose. It ships in dist/theme.css,
    which is imported above, so this page proves the SHIPPED one works — an
    injected copy would hide a regression in the thing consumers actually get. -->
<script defer>${alpine}</script>
</head><body>${specimens}</body></html>`,
);

// ------------------------------------------------------------ 2. drive it

const browser = await chromium.launch({ executablePath: CHROME });
const page = await browser.newPage({ viewport: { width: 1200, height: 1000 } });

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
