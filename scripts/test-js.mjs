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
import { bundle } from './build-js.mjs';

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
*/
const ENHANCEMENTS = new Set(['ds-glide', 'ds-anchor']);

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

finish();

function finish() {
    console.log(
        failures
            ? `\n\x1b[31m${failures} of ${checks} checks failed\x1b[0m\n`
            : `\n\x1b[32mall ${checks} checks passed\x1b[0m\n`,
    );
    process.exit(failures ? 1 : 0);
}
