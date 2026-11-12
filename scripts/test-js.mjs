/**
 * Script tests.
 *
 * The behaviour tests prove the script WORKS in a browser. These prove it is
 * the script we think it is, and that the components and the script agree on
 * what it contains:
 *
 *   1. dist/ is current. ds::scripts serves the committed file, so a stale one
 *      ships stale behaviour with nothing to say so.
 *   2. It registers only through Alpine.data and Alpine.directive, under the
 *      `ds` prefix, once however many times it is loaded.
 *   3. Every behaviour a component asks for is registered, and every behaviour
 *      registered is used by a component or documented. A component asking for
 *      a name the script lacks fails in the browser with an error; a directive
 *      it lacks fails with NOTHING, because Alpine skips a directive it has no
 *      handler for.
 *
 *   node scripts/test-js.mjs
 */
import { readFile, readdir } from 'node:fs/promises';
import { join, dirname, relative } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import vm from 'node:vm';
import { bundle, sizes } from './build-js.mjs';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');

let failures = 0;
let checks = 0;

const pass = (msg) => { checks++; console.log(`  \x1b[32m✓\x1b[0m ${msg}`); };
const fail = (msg) => { checks++; failures++; console.log(`  \x1b[31m✗\x1b[0m ${msg}`); };
const head = (msg) => console.log(`\n${msg}`);
const expect = (ok, yes, no) => (ok ? pass(yes) : fail(no));

const DATA_NAME = /^ds[A-Z][A-Za-z0-9]*$/;
const DIRECTIVE_NAME = /^ds-[a-z][a-z0-9-]*$/;

// ------------------------------------------------------------ 1. dist is current

head('build output');

let built;
try {
    built = await bundle();
    pass('resources/js/ builds');
} catch (error) {
    fail(`resources/js/ does not build: ${error.message}`);
    finish();
}

for (const [file, fresh] of [['blade-ui.js', built.esm], ['blade-ui.global.js', built.classic]]) {
    let shipped = null;
    try {
        shipped = await readFile(join(ROOT, 'dist', file), 'utf8');
    } catch { /* reported below */ }

    if (shipped === null) fail(`dist/${file} is missing — run npm run build`);
    else expect(shipped === fresh, `dist/${file} matches resources/js/`, `dist/${file} is stale — run npm run build`);
}

try {
    new vm.Script(built.classic, { filename: 'blade-ui.global.js' });
    pass('the classic build parses as a classic script (no import or export left in it)');
} catch (error) {
    fail(`the classic build does not parse as a script: ${error.message}`);
}

/*
  The size getting-started tells a reader the inline script costs. It said
  "about 18 kB (6 kB compressed)" while the file grew to 71 and 18, because
  nothing read it. Within a tenth, so a small behaviour does not mean an edit
  and a large one does.
*/
{
    const guide = await readFile(join(ROOT, 'docs/getting-started.md'), 'utf8');
    const stated = guide.match(/about (\d+(?:\.\d+)?) kB \((\d+(?:\.\d+)?) kB compressed\)/);
    const real = sizes(built.classic);
    const near = (said, is) => Math.abs(Number(said) - is) <= is * 0.1;
    expect(stated && near(stated[1], real.raw) && near(stated[2], real.compressed),
        `getting-started states the script's size: about ${stated?.[1]} kB (${stated?.[2]} kB compressed)`,
        stated
            ? `getting-started says "about ${stated[1]} kB (${stated[2]} kB compressed)"; the file is ${real.raw.toFixed(1)} kB, ${real.compressed.toFixed(1)} kB gzipped`
            : 'getting-started no longer states the script\'s size as "about N kB (M kB compressed)"');
}

// ------------------------------------------------------------ 2. what it registers

head('registrations');

/** An Alpine that records what is registered on it and refuses everything else. */
function recordingAlpine() {
    const calls = [];
    const alpine = new Proxy({}, {
        get(target, key) {
            if (typeof key === 'symbol') return target[key];
            if (key === 'data' || key === 'directive') {
                return (name) => {
                    calls.push({ kind: key, name });
                    return { before() {} };
                };
            }
            return () => calls.push({ kind: key, name: '(unexpected)' });
        },
        set(target, key, value) {
            target[key] = value;
            return true;
        },
    });
    return { alpine, calls };
}

const { default: plugin } = await import(pathToFileURL(join(ROOT, 'dist/blade-ui.js')).href);

expect(typeof plugin === 'function', 'dist/blade-ui.js default-exports the plugin', 'dist/blade-ui.js has no default export function');

const esm = recordingAlpine();
plugin(esm.alpine);
plugin(esm.alpine);

const registered = esm.calls;
const unexpected = registered.filter((c) => c.kind !== 'data' && c.kind !== 'directive');
expect(!unexpected.length, 'registers through Alpine.data and Alpine.directive only',
    `also calls Alpine.${unexpected.map((c) => c.kind).join(', Alpine.')} — add it to the conventions first`);

const badNames = registered.filter((c) =>
    (c.kind === 'data' && !DATA_NAME.test(c.name)) || (c.kind === 'directive' && !DIRECTIVE_NAME.test(c.name)));
expect(!badNames.length, 'every name carries the ds prefix (dsName for data, ds-name for a directive)',
    `off-convention names: ${badNames.map((c) => c.name).join(', ')}`);

const counts = new Map();
for (const c of registered) counts.set(`${c.kind}:${c.name}`, (counts.get(`${c.kind}:${c.name}`) ?? 0) + 1);
const twice = [...counts].filter(([, n]) => n > 1).map(([k]) => k);
expect(!twice.length, `Alpine.plugin() twice registers each of the ${counts.size} names once`,
    `registered more than once: ${twice.join(', ')}`);

/** Run the classic build in a sandbox with a fake document, as a page would. */
function runClassic(context) {
    vm.runInContext(built.classic, context, { filename: 'blade-ui.global.js' });
}
function sandbox() {
    const listeners = [];
    const context = vm.createContext({
        document: { addEventListener: (type, fn) => listeners.push({ type, fn }) },
    });
    context.window = context;
    const dispatch = (type) => listeners.filter((l) => l.type === type).forEach((l) => l.fn());
    return { context, dispatch };
}

{
    // Alpine from a CDN with `defer`: this runs first and waits.
    const { context, dispatch } = sandbox();
    runClassic(context);
    const late = recordingAlpine();
    context.Alpine = late.alpine;
    dispatch('alpine:init');
    expect(late.calls.length === registered.length,
        'the classic build registers on alpine:init when Alpine arrives after it',
        `registered ${late.calls.length} of ${registered.length} on alpine:init`);
}

{
    // Livewire's script already ran: window.Alpine exists, not yet started.
    const { context, dispatch } = sandbox();
    const early = recordingAlpine();
    context.Alpine = early.alpine;
    runClassic(context);
    const atOnce = early.calls.length;
    runClassic(context);
    dispatch('alpine:init');
    expect(atOnce === registered.length && early.calls.length === registered.length,
        'and at once when window.Alpine already exists — loaded twice, still once each',
        `registered ${atOnce} at once and ${early.calls.length} in total, for ${registered.length} names`);
}

// ------------------------------------------------------------ 3. components and docs agree

head('components ↔ script');

async function bladeFiles(dir) {
    const out = [];
    for (const entry of await readdir(dir, { withFileTypes: true })) {
        const path = join(dir, entry.name);
        if (entry.isDirectory()) out.push(...(await bladeFiles(path)));
        else if (entry.name.endsWith('.blade.php')) out.push(path);
    }
    return out;
}

/*
  Directives a view may use with no Behaviour::data() scope, because skipping
  them costs an animation and nothing else. The rule below exists because a
  directive Alpine has no handler for is skipped in silence; that is a trap
  when the directive carries behaviour, and harmless when the view renders the
  whole state from PHP and the directive only moves something to match it.

  `ds-glide` slides a highlight to the item the server already drew as chosen.
  The segmented control is radios and CSS, so it must work, and not throw,
  where the script is missing: test-behaviour.mjs loads it on a page with no
  include and checks both. A directive goes on this list only with that test.

  `ds-anchor` keeps an overlay on screen. The overlays that predate it
  (dropdown, select, combobox, date picker, tooltip) keep their absolute
  placement classes under it, which is how they opened in 1.x, so skipping it
  costs the flip and the escape from a clipping box and nothing else:
  test-behaviour.mjs opens each on a page with no include. popover and
  hover-card use it too, inside a Behaviour::data() scope, so a host without
  the script still hears about them.

  `ds-roll` turns a counter's digits to a new value. ds::counter renders the
  formatted number from PHP, and a server re-render (a Livewire morph)
  changes it, so skipping the directive costs the roll and nothing else:
  test-behaviour.mjs morphs one on a page with no include. A counter bound to
  Alpine state is a different route, dsRoll, through Behaviour::data().

  `ds-chart` makes a chart's marks reachable and readable one at a time, and
  its legend a set of toggles. PHP draws the chart as an image named by its
  summary, with the values in a hidden table on request, so skipping the
  directive costs the readout and the keyboard and nothing else:
  test-behaviour.mjs renders the three charts on a page with no include.
*/
const ENHANCEMENTS = new Set(['ds-glide', 'ds-anchor', 'ds-roll', 'ds-chart', 'ds-scroll-shadows', 'ds-shortcut']);

const used = new Map(); // "data:dsX" -> [files]
const rawData = [];
const loneDirectives = [];

for (const file of await bladeFiles(join(ROOT, 'resources/views'))) {
    const src = await readFile(file, 'utf8');
    const rel = relative(ROOT, file);
    const note = (key) => used.set(key, [...(used.get(key) ?? []), rel]);

    const viaHelper = [...src.matchAll(/Behaviour::data\(\s*'([^']+)'/g)].map((m) => m[1]);
    viaHelper.forEach((name) => note(`data:${name}`));

    for (const m of src.matchAll(/x-data(?:\s*=\s*"|'\s*=>\s*['"])\s*(ds[A-Z]\w*)/g)) {
        rawData.push(`${rel}: x-data="${m[1]}…"`);
        note(`data:${m[1]}`);
    }

    const directives = [...src.matchAll(/\bx-(ds-[a-z][a-z0-9-]*)/g)].map((m) => m[1]);
    directives.forEach((name) => note(`directive:${name}`));
    if (directives.some((name) => !ENHANCEMENTS.has(name)) && !viaHelper.length) loneDirectives.push(rel);
}

expect(!rawData.length, 'every component asks for a behaviour through Support\\Behaviour::data()',
    `written as a bare x-data, so a missing script reports only "is not defined":\n      ${rawData.join('\n      ')}`);

expect(!loneDirectives.length, 'no component relies on a ds directive alone',
    `a missing directive is skipped in SILENCE, so these need a Behaviour::data() scope as well:\n      ${loneDirectives.join('\n      ')}`);

const missing = [...used.keys()].filter((key) => !counts.has(key));
expect(!missing.length, `every behaviour the components ask for is registered (${used.size} in use)`,
    `asked for and never registered:\n      ${missing.map((k) => `${k} — ${used.get(k).join(', ')}`).join('\n      ')}`);

const guide = await readFile(join(ROOT, 'docs/getting-started.md'), 'utf8');
const orphans = [...counts.keys()].filter((key) => {
    const name = key.split(':')[1];
    return !used.has(key) && !guide.includes(`\`${key.startsWith('directive') ? 'x-' : ''}${name}`);
});
expect(!orphans.length, 'every registered behaviour is used by a component or documented in docs/getting-started.md',
    `registered, used by nothing and documented nowhere: ${orphans.join(', ')}`);

/*
  The shared helpers that do arithmetic on what someone typed, run here in
  Node rather than only through a page, so every case is spelled out. The
  money cases are tests/MoneyTest.php's, so the browser and the server are
  held to the same answers.
*/
head('shared helpers: amounts as digits, shortcuts as strings, colours');
{
    const money = await import(pathToFileURL(join(ROOT, 'resources/js/shared/money.js')).href);
    const out = (value, decimals = 2, minor = false, givenMinor = false) =>
        money.machine(money.round(money.given(value, decimals, givenMinor), decimals), decimals, minor);
    const cases = [
        [out(0.1 + 0.2), '0.30', '0.1 + 0.2 is 0.30, not 0.30000000000000004'],
        [out('0.1') === '0.10' && out('0.2') === '0.20' && money.check('0.3', { step: '0.1' }) === '', true, 'a step of 0.1 takes 0.3 (in floating point 0.3 % 0.1 is not 0)'],
        [money.check('0.35', { step: '0.1' }), 'step', '0.35 is off a 0.1 step'],
        [out('12345678901234567890.129'), '12345678901234567890.13', 'a twenty-digit amount keeps every digit'],
        [out('12345678901234567890.129', 2, true), '1234567890123456789013', 'and in minor units'],
        [out(123450, 2, false, true), '1234.50', 'minor units read back'],
        [out('9.995'), '10.00', 'rounding carries through the units'],
        [out('-1.005'), '-1.01', 'half away from zero, below zero too'],
        [out('-0.001'), '0.00', 'no negative zero'],
        [out('1234', 0), '1234', 'no places for JPY'],
        [out('1.2345', 3), '1.235', 'three for KWD'],
        [out(1e21), '1000000000000000000000.00', 'a number past 1e21 is digits, not an exponent'],
        [out(0.0000001, 8), '0.00000010', 'and a tiny one'],
        [out('$1,234.56'), '1234.56', 'a paste with a symbol and grouping'],
        [money.machine(money.read('1.234,56 €', ','), 2), '1234.56', 'German grouping and point'],
        [money.machine(money.read('1,234', '.'), 2), '1234.00', 'one comma and three digits after is grouping'],
        [money.machine(money.read('1,5', '.'), 2), '1.50', 'one comma and one digit after is the point, whatever the locale'],
        [money.machine(money.read('(12.00)', '.'), 2), '-12.00', 'accounting brackets are a minus'],
        [money.group('1234567', money.shape('en-IN')), '12,34,567', 'Indian grouping'],
        [money.write(money.typed('1234567.5', '.'), money.shape('en-IN'), 2, true), '12,34,567.50', 'written for en-IN, places filled'],
        [money.write(money.typed('12.', '.'), money.shape('en'), 2), '12.', 'a point just typed stays while typing'],
        [money.check('9.99', { min: '10.00' }), 'min', 'below min'],
        [money.check('100000.01', { max: '100000' }), 'max', 'above max'],
        [money.check('10.00', { min: '10', max: '10.00' }), '', 'on both bounds'],
    ];
    for (const [got, want, what] of cases) expect(got === want, what, `${what}: got ${JSON.stringify(got)}, wanted ${JSON.stringify(want)}`);

    const sc = await import(pathToFileURL(join(ROOT, 'resources/js/shared/shortcut.js')).href);
    const key = (k, mods = {}, code = '') => ({ key: k, code, ctrlKey: false, metaKey: false, altKey: false, shiftKey: false, ...mods });
    const shortcuts = [
        [sc.normalise('Shift+Mod+K'), 'mod+shift+k', 'modifiers in a fixed order, lower case'],
        [sc.normalise('cmd+option+p'), 'meta+alt+p', 'cmd and option are meta and alt'],
        [sc.record(key('K', { ctrlKey: true, shiftKey: true })), 'mod+shift+k', 'Ctrl+Shift+K off a Mac records mod+shift+k'],
        [sc.record(key('k', { ctrlKey: true }), { portable: false }), 'ctrl+k', 'not portable, it is ctrl'],
        [sc.record(key('?', { ctrlKey: true, shiftKey: true })), 'mod+?', 'a character Shift typed does not repeat the Shift'],
        [sc.record(key('Shift', { shiftKey: true })), null, 'a modifier alone completes nothing'],
        [sc.record(key('ø', { altKey: true }, 'KeyO'), { by: 'code' }), 'alt+o', 'by code, the key where it is'],
        [sc.record(key(' ', { ctrlKey: true })), 'mod+space', 'space is written space'],
        [sc.same('mod+w', 'ctrl+w', false) && !sc.same('mod+w', 'ctrl+w', true), true, 'mod+w is ctrl+w off a Mac and not on one'],
        [sc.caps('mod+shift+k', true).map((c) => c.cap).join(''), '⌘⇧K', 'symbols on a Mac'],
        [sc.caps('mod+shift+k', false).map((c) => c.word).join(' '), 'Control Shift K', 'words elsewhere, and always to be read'],
        [sc.caps('alt+arrowup', true).map((c) => `${c.cap}/${c.word}`).join(' '), '⌥/Option ↑/Up Arrow', 'a named key has a symbol and a word'],
    ];
    for (const [got, want, what] of shortcuts) expect(got === want, what, `${what}: got ${JSON.stringify(got)}, wanted ${JSON.stringify(want)}`);

    // tests/Fixtures/colour.json is ColourTest's too: one set of answers for
    // Support\Colour and Support\Ink and for shared/colour.js.
    const colour = await import(pathToFileURL(join(ROOT, 'resources/js/shared/colour.js')).href);
    const fixture = JSON.parse(await readFile(join(ROOT, 'tests/Fixtures/colour.json'), 'utf8'));
    const wrong = [];
    for (const [text, want] of fixture.parse) {
        const c = colour.parse(text);
        if ((c && colour.hex(c, c.a < 1)) !== want) wrong.push(`parse(${JSON.stringify(text)})`);
    }
    for (const [hex, rgb, hsl, hsv] of fixture.convert) {
        const c = colour.parse(hex);
        const round = (list) => JSON.stringify(list.map(Math.round));
        if (JSON.stringify([c.r, c.g, c.b]) !== JSON.stringify(rgb) || round(colour.toHsl(c)) !== JSON.stringify(hsl) || round(colour.toHsv(c)) !== JSON.stringify(hsv)) wrong.push(`convert ${hex}`);
        if (colour.hex(colour.fromHsl(...colour.toHsl(c))) !== hex || colour.hex(colour.fromHsv(...colour.toHsv(c))) !== hex) wrong.push(`round trip ${hex}`);
    }
    for (const [hex, format, alpha, want] of fixture.write) if (colour.write(colour.parse(hex), format, alpha) !== want) wrong.push(`write ${hex} ${format}`);
    for (const [a, b, want] of fixture.contrast) if (colour.ratio(colour.contrast(colour.parse(a), colour.parse(b))) !== want) wrong.push(`contrast ${a} on ${b}`);
    const total = ['parse', 'convert', 'write', 'contrast'].reduce((n, k) => n + fixture[k].length, 0);
    expect(!wrong.length, `colours: the ${total} cases of tests/Fixtures/colour.json, the server's answers`, `shared/colour.js disagrees with tests/Fixtures/colour.json: ${wrong.join(', ')}`);
}

finish();

function finish() {
    console.log(
        failures
            ? `\n\x1b[31m${failures} of ${checks} checks failed\x1b[0m\n`
            : `\n\x1b[32mall ${checks} checks passed\x1b[0m\n`,
    );
    process.exit(failures ? 1 : 0);
}
