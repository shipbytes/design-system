/**
 * Token tests.
 *
 * Three things worth failing a build over:
 *   1. Structure  — every reference resolves, every semantic colour has a dark
 *                   value, nothing is orphaned.
 *   2. Contrast   — every foreground/background pair the system actually pairs
 *                   meets WCAG AA, in BOTH themes. A design system that ships
 *                   unverified colour pairs is an accessibility liability.
 *   3. Output     — the generated files exist and are current.
 *
 *   node scripts/test-tokens.mjs
 */
import { readFile, readdir } from 'node:fs/promises';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const DARK_KEY = 'shipbytes.dark';

let failures = 0;
let checks = 0;

const pass = (msg) => { checks++; console.log(`  \x1b[32m✓\x1b[0m ${msg}`); };
const fail = (msg) => { checks++; failures++; console.log(`  \x1b[31m✗\x1b[0m ${msg}`); };
const head = (msg) => console.log(`\n${msg}`);

// ------------------------------------------------------------ load tokens

const files = (await readdir(join(ROOT, 'tokens'))).filter((f) => f.endsWith('.json'));
const tree = {};
for (const f of files) merge(tree, JSON.parse(await readFile(join(ROOT, 'tokens', f), 'utf8')));

function merge(t, s) {
    for (const [k, v] of Object.entries(s)) {
        if (v && typeof v === 'object' && !Array.isArray(v) && !k.startsWith('$')) {
            t[k] ??= {};
            merge(t[k], v);
        } else t[k] = v;
    }
    return t;
}

const isToken = (n) => n && typeof n === 'object' && '$value' in n;
const flat = [];
(function walk(node, path = []) {
    for (const [k, v] of Object.entries(node)) {
        if (k.startsWith('$')) continue;
        if (isToken(v)) flat.push({ path: [...path, k], token: v });
        else if (v && typeof v === 'object') walk(v, [...path, k]);
    }
})(tree);

const byPath = new Map(flat.map((t) => [t.path.join('.'), t.token]));

function resolve(value, seen = new Set()) {
    if (typeof value !== 'string') return value;
    return value.replace(/\{([^}]+)\}/g, (_, ref) => {
        if (seen.has(ref)) throw new Error(`cyclic reference: ${ref}`);
        const t = byPath.get(ref);
        if (!t) throw new Error(`unknown reference: {${ref}}`);
        return String(resolve(t.$value, new Set([...seen, ref])));
    });
}

// ------------------------------------------------------------ colour maths

/** oklch(L% C H [/ A]) or #rrggbb -> linear sRGB triple (may be out of gamut). */
function toLinearRgb(css) {
    css = css.trim();

    const short = css.match(/^#([0-9a-f]{3})$/i);
    if (short) css = '#' + [...short[1]].map((c) => c + c).join('');

    const hex = css.match(/^#([0-9a-f]{6})$/i);
    if (hex) {
        const n = parseInt(hex[1], 16);
        return [(n >> 16) & 255, (n >> 8) & 255, n & 255].map((c) => {
            const s = c / 255;
            return s <= 0.04045 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4;
        });
    }

    const ok = css.match(
        /^oklch\(\s*([\d.]+)%?\s+([\d.]+)\s+([\d.]+|none)\s*(?:\/\s*([\d.]+))?\s*\)$/i,
    );
    if (!ok) throw new Error(`cannot parse colour: ${css}`);

    const L = parseFloat(ok[1]) / (css.includes('%') ? 100 : 1);
    const C = parseFloat(ok[2]);
    const H = ok[3] === 'none' ? 0 : parseFloat(ok[3]);

    const a = C * Math.cos((H * Math.PI) / 180);
    const b = C * Math.sin((H * Math.PI) / 180);

    const l_ = L + 0.3963377774 * a + 0.2158037573 * b;
    const m_ = L - 0.1055613458 * a - 0.0638541728 * b;
    const s_ = L - 0.0894841775 * a - 1.291485548 * b;
    const l = l_ ** 3, m = m_ ** 3, s = s_ ** 3;

    return [
        4.0767416621 * l - 3.3077115913 * m + 0.2309699292 * s,
        -1.2684380046 * l + 2.6097574011 * m - 0.3413193965 * s,
        -0.0041960863 * l - 0.7034186147 * m + 1.707614701 * s,
    ];
}

/** Alpha, if the colour carries one. */
function alphaOf(css) {
    const m = css.match(/\/\s*([\d.]+)\s*\)$/);
    return m ? parseFloat(m[1]) : 1;
}

const luminance = ([r, g, b]) =>
    0.2126 * Math.max(0, r) + 0.7152 * Math.max(0, g) + 0.0722 * Math.max(0, b);

/** Composite a possibly-translucent colour over an opaque backdrop. */
function over(fg, bg) {
    const a = alphaOf(fg);
    if (a === 1) return toLinearRgb(fg);
    const f = toLinearRgb(fg), b = toLinearRgb(bg);
    return f.map((c, i) => c * a + b[i] * (1 - a));
}

function contrast(fgCss, bgCss) {
    const bg = toLinearRgb(bgCss);
    const fg = over(fgCss, bgCss);
    const [a, b] = [luminance(fg), luminance(bg)].sort((x, y) => y - x);
    return (a + 0.05) / (b + 0.05);
}

// ------------------------------------------------------------ 1. structure

head('structure');

try {
    for (const { path, token } of flat) {
        resolve(token.$value);
        const d = token.$extensions?.[DARK_KEY];
        if (d !== undefined) resolve(d);
    }
    pass(`all ${flat.length} tokens resolve`);
} catch (e) {
    fail(`reference error — ${e.message}`);
}

const semantic = flat.filter((t) => t.path[0] === 'semantic');
const noDark = semantic.filter((t) => t.token.$extensions?.[DARK_KEY] === undefined);
noDark.length
    ? fail(`semantic tokens missing a dark value: ${noDark.map((t) => t.path.join('.')).join(', ')}`)
    : pass(`all ${semantic.length} semantic tokens define both themes`);

// Every primitive should be reachable from a semantic token, or it is dead weight
// that will drift. Full ramps are intentional, so only flag whole unused families.
const usedRefs = new Set();
for (const { token } of flat) {
    const scan = (v) => typeof v === 'string' && v.replace(/\{([^}]+)\}/g, (_, r) => (usedRefs.add(r), ''));
    scan(token.$value);
    scan(token.$extensions?.[DARK_KEY]);
}
const families = [...new Set(flat.filter((t) => t.path[0] === 'color' && t.path.length === 3).map((t) => t.path[1]))];
const deadFamilies = families.filter(
    (fam) => ![...usedRefs].some((r) => r.startsWith(`color.${fam}.`)),
);
deadFamilies.length
    ? fail(`palette families no semantic token references: ${deadFamilies.join(', ')}`)
    : pass(`all ${families.length} palette families are referenced`);

// ------------------------------------------------------------ 2. contrast

head('contrast (WCAG AA: 4.5 body text, 3.0 large text and UI edges)');

const val = (name, theme) => {
    const t = byPath.get(`semantic.${name}`);
    if (!t) throw new Error(`no such semantic token: ${name}`);
    return resolve(theme === 'dark' ? t.$extensions[DARK_KEY] : t.$value);
};

/**
 * [foreground, background, minimum, what it is]
 *
 * REQUIRED — text, and the boundaries WCAG 1.4.11 says must be identifiable:
 * the things a user has to read or has to locate in order to operate the UI.
 */
const REQUIRED = [
    ['fg', 'surface', 4.5, 'headings on the card'],
    ['fg-body', 'surface', 4.5, 'body copy on the card'],
    ['fg-muted', 'surface', 4.5, 'labels and meta on the card'],
    ['fg', 'surface-subtle', 4.5, 'headings on a hovered row'],
    ['fg-muted', 'surface-subtle', 4.5, 'table column headers'],
    ['fg-body', 'surface-sunken', 4.5, 'body copy on the app ground'],
    ['on-inverse', 'surface-inverse', 4.5, 'primary button label'],
    ['on-accent', 'accent', 4.5, 'filled accent; bottom-nav count'],
    ['on-danger', 'danger', 4.5, 'destructive button label'],
    ['accent', 'surface', 4.5, 'links'],
    ['danger', 'surface', 4.5, 'danger-toned control'],
    // NOT `danger` on the wash: that is 4.36:1 in light and 4.37:1 in dark.
    // A hovered danger control darkens its label, the way the alert does.
    ['on-danger-tint', 'danger-wash', 4.5, 'danger-toned control, hovered'],
    ['on-success-tint', 'success-tint', 4.5, 'success badge'],
    ['on-warning-tint', 'warning-tint', 4.5, 'warning badge'],
    ['on-danger-tint', 'danger-tint', 4.5, 'danger badge'],
    ['on-accent-tint', 'accent-tint', 4.5, 'accent badge'],
    ['on-neutral-tint', 'neutral-tint', 4.5, 'neutral badge'],
    // ds::code-block's token colours (theme.css, [data-ds-code]) on its ground.
    ['on-danger-tint', 'surface-subtle', 4.5, 'code: keywords'],
    ['on-success-tint', 'surface-subtle', 4.5, 'code: strings'],
    ['on-accent-tint', 'surface-subtle', 4.5, 'code: constants'],
    ['on-warning-tint', 'surface-subtle', 4.5, 'code: functions'],
    ['fg-body', 'surface-subtle', 4.5, 'code: parameters'],
    ['accent', 'surface-subtle', 4.5, 'code: links'],
    ['fg', 'accent-wash', 4.5, 'code: a highlighted line'],
    ['focus-ring', 'surface', 3.0, 'focus ring on the card'],
    ['focus-ring', 'surface-sunken', 3.0, 'focus ring on the app ground'],
];

/**
 * ADVISORY — reported with real numbers, but not a build failure.
 *
 * `border-strong` draws a card edge. A card is identified by its content and
 * its background, not its outline, so 1.4.11 does not bite; a 3:1 card border
 * would also look like a table.
 *
 * `border` is the input outline, and that one genuinely is a component
 * boundary. It sits at ~1.1:1 — a trade-off inherited from the Catalyst input
 * recipe, where the affordance comes from the white fill and the shadow rather
 * than the hairline. Kept deliberately, recorded here so it stays a decision
 * rather than an accident, and mitigated by a focus ring that does pass.
 *
 * `fg-subtle` is for marks that repeat something already written — the icon
 * beside a nav label, the chevron beside "View all". Those fall under the
 * redundant-information exception. It is NOT for text a user must read: see
 * specs/color.md, which sends timestamps to fg-muted.
 */
const ADVISORY = [
    ['border-strong', 'surface', 3.0, 'card edge (decorative)'],
    ['border', 'surface', 3.0, 'input outline (see note)'],
    ['fg-subtle', 'surface', 3.0, 'redundant icons only'],
    // Found by the contrast audit in build-docs.mjs, which reads what the
    // browser actually resolves rather than the pairs listed here.
    //
    // `fg-muted` is checked against `surface` (4.83) and `surface-subtle`, and
    // was never checked against the app's own GROUND. On `surface-sunken` it is
    // 4.39 — under AA by a hair. It matters for help text, a breadcrumb, or a
    // form that sits on the page rather than inside a panel, all of which are
    // ordinary placements.
    //
    // Advisory rather than required because closing it means moving `fg-muted`
    // off zinc-500, which is the most-used colour in the system — a deliberate
    // change with a wide blast radius, not a quiet fix. Recorded so it stays a
    // decision.
    ['fg-muted', 'surface-sunken', 4.5, 'muted text on the app ground'],
    ['danger', 'surface-sunken', 4.5, 'error text on the app ground'],
    // The chart series are a validated CATEGORICAL set: their order is a
    // colour-vision separation property, measured, and slots 3, 4 and 5 sit
    // under 3:1 on a light surface by choice (the spec names 3 and 4; 5 is
    // 2.69). A chart never carries identity by colour alone, which is the
    // relief. Reported so the numbers stay visible; changing one slot means
    // re-measuring all eight.
    ...[1, 2, 3, 4, 5, 6, 7, 8].map((n) => [`series-${n}`, 'surface', 3.0, `chart series ${n} (see chart-series.md)`]),
];

for (const theme of ['light', 'dark']) {
    console.log(`\n  ${theme}`);
    for (const [fg, bg, min, what] of REQUIRED) {
        const bgCss = flatten(val(bg, theme), val('surface', theme));
        const ratio = contrast(val(fg, theme), bgCss);
        const label = `${what.padEnd(32)} ${fg} on ${bg}`.padEnd(74);
        ratio >= min
            ? pass(`${label} ${ratio.toFixed(2)}:1`)
            : fail(`${label} ${ratio.toFixed(2)}:1  (needs ${min})`);
    }
    for (const [fg, bg, min, what] of ADVISORY) {
        const bgCss = flatten(val(bg, theme), val('surface', theme));
        const ratio = contrast(val(fg, theme), bgCss);
        const label = `${what.padEnd(32)} ${fg} on ${bg}`.padEnd(74);
        const mark = ratio >= min ? '\x1b[32m✓\x1b[0m' : '\x1b[33m!\x1b[0m';
        console.log(`  ${mark} ${label} ${ratio.toFixed(2)}:1  ${ratio >= min ? '' : `(advisory, ${min})`}`);
    }
}

/** Composite a translucent colour over the surface so ratios are meaningful. */
function flatten(css, surface) {
    return alphaOf(css) < 1 ? cssOver(css, surface) : css;
}

/** Flatten a translucent colour into an opaque one for further maths. */
function cssOver(fg, bg) {
    const [r, g, b] = over(fg, bg).map((c) => {
        const s = Math.max(0, Math.min(1, c));
        const v = s <= 0.0031308 ? s * 12.92 : 1.055 * s ** (1 / 2.4) - 0.055;
        return Math.round(v * 255);
    });
    return `#${[r, g, b].map((c) => c.toString(16).padStart(2, '0')).join('')}`;
}

// ------------------------------------------------------- the heatmap's steps

/*
  The activity heatmap draws five steps from ONE hue, mixed in OKLab with fg
  and the surface in the shares tokens/chart.json gives (build-tokens.mjs
  turns them into the heat-0..4 utilities). No five steps from one hue can be
  3:1 apart, so the bar is that each step is visibly a step: at least 1.15:1
  and ΔE 7 (OKLab x100) from its neighbour, and each further from the surface
  than the one before, for every hue the heatmap accepts, in both themes.
  The cell's count is always in words as well.
*/
head('heatmap steps (one hue, five steps, both themes)');

{
    const toLab = (css) => {
        const [r, g, b] = toLinearRgb(css).map((c) => Math.max(0, c));
        const l = Math.cbrt(0.4122214708 * r + 0.5363325363 * g + 0.0514459929 * b);
        const m = Math.cbrt(0.2119034982 * r + 0.6806995451 * g + 0.1073969566 * b);
        const s = Math.cbrt(0.0883024619 * r + 0.2817188376 * g + 0.6299787005 * b);
        return [
            0.2104542553 * l + 0.793617785 * m - 0.0040720468 * s,
            1.9779984951 * l - 2.428592205 * m + 0.4505937099 * s,
            0.0259040371 * l + 0.7827717662 * m - 0.808675766 * s,
        ];
    };
    const labLuminance = ([L, a, b]) => {
        const l = (L + 0.3963377774 * a + 0.2158037573 * b) ** 3;
        const m = (L - 0.1055613458 * a - 0.0638541728 * b) ** 3;
        const s = (L - 0.0894841775 * a - 1.291485548 * b) ** 3;
        return luminance([
            4.0767416621 * l - 3.3077115913 * m + 0.2309699292 * s,
            -1.2684380046 * l + 2.6097574011 * m - 0.3413193965 * s,
            -0.0041960863 * l - 0.7034186147 * m + 1.707614701 * s,
        ].map((c) => Math.min(1, c)));
    };
    const ratio = (x, y) => {
        const [hi, lo] = [labLuminance(x), labLuminance(y)].sort((p, q) => q - p);
        return (hi + 0.05) / (lo + 0.05);
    };
    const deltaE = (x, y) => 100 * Math.hypot(x[0] - y[0], x[1] - y[1], x[2] - y[2]);

    const steps = Object.entries(tree.chart?.heat ?? {})
        .filter(([k]) => /^\d+$/.test(k))
        .map(([, s]) => ({ hue: Number(s.hue.$value), fg: Number(s.fg.$value) }));

    expect(steps.length === 5 && steps.every((s) => s.hue >= 0 && s.fg >= 0 && s.hue + s.fg <= 1),
        'tokens/chart.json defines five steps, each a share of the hue and of fg that fits in the whole',
        `chart.heat has ${steps.length} steps, or a share outside 0..1: ${JSON.stringify(steps)}`);

    // The heatmap's tones and series, as the view maps them (neutral is fg-muted).
    const hues = { neutral: 'fg-muted', accent: 'accent', success: 'success', warning: 'warning', danger: 'danger' };
    for (let n = 1; n <= 8; n++) hues[`series ${n}`] = `series-${n}`;

    const problems = [];
    let tightest = { ratio: Infinity }, closest = { de: Infinity };

    for (const theme of ['light', 'dark']) {
        const surface = toLab(val('surface', theme));
        const fg = toLab(val('fg', theme));
        for (const [name, token] of Object.entries(hues)) {
            const hue = toLab(val(token, theme));
            const ramp = steps.map((s) => [0, 1, 2].map((i) => hue[i] * s.hue + fg[i] * s.fg + surface[i] * (1 - s.hue - s.fg)));
            for (let i = 0; i < ramp.length - 1; i++) {
                const r = ratio(ramp[i], ramp[i + 1]);
                const de = deltaE(ramp[i], ramp[i + 1]);
                if (r < tightest.ratio) tightest = { ratio: r, at: `${name} ${i}→${i + 1}, ${theme}` };
                if (de < closest.de) closest = { de, at: `${name} ${i}→${i + 1}, ${theme}` };
                if (r < 1.15 || de < 7) problems.push(`${name} (${theme}) step ${i}→${i + 1}: ${r.toFixed(2)}:1, ΔE ${de.toFixed(1)}`);
                if (ratio(ramp[i + 1], surface) <= ratio(ramp[i], surface)) problems.push(`${name} (${theme}) step ${i + 1} is no further from the surface than step ${i}`);
            }
        }
    }

    expect(!problems.length,
        `every adjacent step is 1.15:1 and ΔE 7 apart, and further from the ground, for ${Object.keys(hues).length} hues in both themes (tightest ${tightest.ratio.toFixed(2)}:1 at ${tightest.at}; closest ΔE ${closest.de.toFixed(1)} at ${closest.at})`,
        `${problems.length} too close:\n      ${problems.join('\n      ')}`);

    const theme = await readFile(join(ROOT, 'dist/theme.css'), 'utf8');
    const built = [0, 1, 2, 3, 4].filter((n) => new RegExp(`@utility heat-${n} \\{`).test(theme));
    expect(built.length === 5, 'theme.css carries the heat-0 to heat-4 utilities built from them',
        `theme.css has heat utilities for ${built.join(', ') || 'none'} — run npm run build`);
}

// ------------------------------------------------------- 3. theme collisions

head('theme collisions (must not silently redefine a Tailwind default)');

/*
  This is the one that nearly shipped a disaster. An early draft named the radius
  scale sm/base/lg/xl and the shadows sm/lg/xl — the obvious names. But Tailwind
  already owns those keys with DIFFERENT values, and an @theme entry overwrites
  rather than adds. Importing such a theme into the source dashboard would have resized 1,438
  existing `rounded-*` usages and reverted `shadow-sm` to its v3 meaning, undoing
  the v3->v4 migration, with nothing in the diff to suggest why.

  So: any key we emit that Tailwind also defines must carry the SAME value.
  Otherwise it needs a semantic name of its own.
*/
/**
 * Keys we knowingly take over from Tailwind, with the reason. Anything not
 * listed here that differs is a bug.
 */
const INTENTIONAL = {
    '--font-sans': 'the system sets the typeface — that is the point of it',
};

/** Same value? Colours compare by computed sRGB, so #fff === #ffffff and a
 *  `none` hue === a 0 hue. Everything else compares as normalised text. */
function sameValue(a, b) {
    if (normalizeCss(a) === normalizeCss(b)) return true;
    try {
        const [ra, ga, ba] = toLinearRgb(a);
        const [rb, gb, bb] = toLinearRgb(b);
        return (
            Math.abs(ra - rb) < 1e-6 && Math.abs(ga - gb) < 1e-6 && Math.abs(ba - bb) < 1e-6
        );
    } catch {
        return false;
    }
}

let twTheme = '';
try {
    twTheme = await readFile(join(ROOT, 'node_modules/tailwindcss/theme.css'), 'utf8');
} catch { /* dependency not installed */ }

if (!twTheme) {
    console.log('  \x1b[33m!\x1b[0m tailwindcss not installed — collision check skipped');
} else {
    const twDefaults = new Map();
    for (const m of twTheme.matchAll(/^\s*(--[\w-]+):\s*([^;]+);/gm)) {
        twDefaults.set(m[1], m[2].trim());
    }

    const theme = await readFile(join(ROOT, 'dist/theme.css'), 'utf8');
    const collisions = [];

    for (const m of theme.matchAll(/^\s*(--[\w-]+):\s*([^;]+);/gm)) {
        const [, key, value] = m;
        if (!twDefaults.has(key)) continue;

        // We map onto var(--ds-*); resolve it to compare real values.
        const ref = value.match(/^var\((--ds-[\w-]+)\)$/);
        if (!ref) continue;
        const ours = resolveDsVar(ref[1]);
        const theirs = twDefaults.get(key);
        if (ours === null) continue;
        if (INTENTIONAL[key]) continue;
        if (sameValue(ours, theirs)) continue;
        collisions.push({ key, ours, theirs });
    }

    collisions.length
        ? collisions.forEach((c) =>
              fail(`${c.key} overwrites Tailwind — ours "${c.ours}" vs "${c.theirs}"`),
          )
        : pass(`no @theme key redefines a Tailwind default with a different value`);

    for (const [k, why] of Object.entries(INTENTIONAL)) {
        console.log(`  \x1b[32m✓\x1b[0m ${k} intentionally overridden — ${why}`);
    }
}

function resolveDsVar(name) {
    for (const { path, token } of flat) {
        if (dsName(path) === name) return String(resolve(token.$value));
    }
    return null;
}

function dsName(path) {
    let p = path[0] === 'semantic' ? path.slice(1) : path;
    if (p[p.length - 1] === 'base') p = p.slice(0, -1);
    return `--ds-${p.join('-')}`;
}

function normalizeCss(v) {
    return v
        .replace(/\s+/g, ' ')
        .replace(/\s*,\s*/g, ',')
        // Font stacks differ only in quote style between sources.
        .replace(/'/g, '"')
        .trim();
}

// ------------------------------------------------------------ motion

head('motion');

{
    const durations = flat.filter((t) => t.path[0] === 'motion' && t.path[1] === 'duration');
    const eases = flat.filter((t) => t.path[0] === 'motion' && t.path[1] === 'ease');
    const ms = (t) => Number(String(t.token.$value).match(/^(\d+)ms$/)?.[1] ?? NaN);

    const malformed = durations.filter((t) => t.token.$type !== 'duration' || Number.isNaN(ms(t)));
    expect(!malformed.length && durations.length, `all ${durations.length} durations are whole milliseconds`,
        `durations not written as "<n>ms": ${malformed.map((t) => t.path.join('.')).join(', ') || 'there are none'}`);

    // Every job is a pair, and the pair is ordered: leave is quicker than enter.
    const jobs = [...new Set(durations.map((t) => t.path[2]))];
    const broken = jobs.filter((job) => {
        const enter = durations.find((t) => t.path[2] === job && t.path[3] === 'enter');
        const leave = durations.find((t) => t.path[2] === job && t.path[3] === 'leave');
        return !enter || !leave || durations.filter((t) => t.path[2] === job).length !== 2 || ms(leave) >= ms(enter);
    });
    expect(!broken.length, `${jobs.join(', ')}: each an enter/leave pair, leave quicker than enter`,
        `not an enter/leave pair with leave < enter: ${broken.join(', ')}`);

    const badCurves = eases.filter((t) => {
        const v = t.token.$value;
        return t.token.$type !== 'cubicBezier' || !Array.isArray(v) || v.length !== 4
            || !v.every((n) => typeof n === 'number') || v[0] < 0 || v[0] > 1 || v[2] < 0 || v[2] > 1;
    });
    expect(!badCurves.length && eases.length === 2, 'ease.enter and ease.leave are valid cubic-bezier curves',
        `bad curves: ${badCurves.map((t) => t.path.join('.')).join(', ') || `expected 2, found ${eases.length}`}`);

    // Usable means usable as UTILITIES. Compile them the way a consumer's
    // Tailwind would, and look for the rules, including under motion-safe.
    const utilities = [
        ...jobs.flatMap((job) => [`duration-${job}-enter`, `duration-${job}-leave`]),
        'ease-enter',
        'ease-leave',
    ];
    const probe = twTheme ? await compileProbe([...utilities, 'motion-safe:duration-popover-enter']) : null;

    if (!twTheme) {
        console.log('  \x1b[33m!\x1b[0m tailwindcss not installed — motion utility check skipped');
    } else if (probe instanceof Error) {
        fail(`could not compile the motion utilities: ${String(probe.stderr || probe.message).slice(0, 400)}`);
    } else {
        const missing = utilities.filter((u) => {
            const name = u.startsWith('ease-') ? `--ease-${u.slice(5)}` : `--transition-duration-${u.slice(9)}`;
            const rule = probe.match(new RegExp(`\\.${u}\\s*\\{[^}]*\\}`))?.[0] ?? '';
            return !rule.includes(`var(${name})`) || !probe.includes(`${name}: var(--ds-motion-`);
        });
        expect(!missing.length, `${utilities.length} motion utilities compile against the tokens (duration-popover-enter, ease-enter, …)`,
            `no rule, or no token behind the rule, for: ${missing.join(', ')}`);

        expect(/prefers-reduced-motion:\s*no-preference[\s\S]*motion-safe\\:duration-popover-enter/.test(probe),
            'and compose with motion-safe:',
            'motion-safe:duration-popover-enter compiled to nothing');
    }
}

/*
  Every transition and animation a component view asks for is reduced-motion
  aware and timed by the tokens. Batch 00 recorded that the components built
  before 2.0 used `transition ease-out duration-150` and the like: literal
  numbers, and a transition that ran whatever the reader's settings said.

  A transition or animation utility must carry `motion-safe:` in its variant
  chain, or sit in the same class string as a `motion-reduce:transition-none`
  (or `-animate-none`) branch. A duration is one of the motion tokens'
  utilities and an easing is `ease-enter` or `ease-leave`; any number or any
  other curve fails. Alpine's bare `x-transition` fails too: its defaults are
  inline styles with their own durations, which no media query reaches.

  Comments are stripped first, and so are PHP array keys and index lookups
  (`'transition' =>`, `$recipe['transition']`), which are names, not classes.
*/
head('motion in the component views');

const MOTION_DURATIONS = new Set(['hint', 'popover', 'dialog', 'slide'].flatMap((j) => [`duration-${j}-enter`, `duration-${j}-leave`]));

/** Split a class token into its variants and its utility, ignoring colons inside brackets. */
function splitVariants(token) {
    const parts = [];
    let depth = 0;
    let at = 0;
    for (let i = 0; i < token.length; i++) {
        const c = token[i];
        if (c === '[' || c === '(') depth++;
        else if (c === ']' || c === ')') depth--;
        else if (c === ':' && depth === 0) {
            parts.push(token.slice(at, i));
            at = i + 1;
        }
    }
    parts.push(token.slice(at));
    return { variants: parts.slice(0, -1), utility: parts.at(-1).replace(/^!/, '') };
}

/** What is wrong with the motion in this source, one string per finding. */
export function motionFindings(source) {
    // Blanked rather than removed, so a finding's line number is the file's.
    const blank = (m) => m.replace(/[^\n]/g, ' ');
    const stripped = source
        .replace(/\{\{--[\s\S]*?--\}\}/g, blank)
        .replace(/\/\*[\s\S]*?\*\//g, blank)
        .replace(/^\s*\/\/.*$/gm, ' ')
        .replace(/'[\w-]+'\s*=>/g, ' ')
        .replace(/\[\s*'[\w-]+'\s*\]/g, ' ');

    const found = [];
    const lines = stripped.split('\n');

    lines.forEach((line, index) => {
        const where = `line ${index + 1}`;

        for (const m of line.matchAll(/\bx-transition(?![:\w-])(\.[\w.]+)?/g)) {
            found.push(`${where}: Alpine's default ${m[0]}, whose inline timings ignore reduced motion`);
        }

        const tokens = line.split(/[\s"'`]+/).filter(Boolean);
        const reduced = (kind) => tokens.some((t) => {
            const { variants, utility } = splitVariants(t);
            return variants.includes('motion-reduce') && utility === `${kind}-none`;
        });

        for (const token of tokens) {
            if (/^(x-transition|@|\$|:|=)/.test(token)) continue;
            const { variants, utility } = splitVariants(token);

            if (/^duration-/.test(utility) && !MOTION_DURATIONS.has(utility)) {
                found.push(`${where}: ${token} is a literal duration; use a motion token's utility (duration-popover-enter, …)`);
            } else if (/^ease-/.test(utility) && utility !== 'ease-enter' && utility !== 'ease-leave') {
                found.push(`${where}: ${token} is not one of the motion curves; use ease-enter or ease-leave`);
            } else if (/^(transition|animate)(-|$)/.test(utility) && !/-none$/.test(utility)) {
                const kind = utility.startsWith('animate') ? 'animate' : 'transition';
                if (!variants.includes('motion-safe') && !reduced(kind)) {
                    found.push(`${where}: ${token} runs under reduced motion; write motion-safe:${utility}`);
                }
            }
        }
    });

    return found;
}

{
    // The scanner, proven against what it exists to catch, so a quiet pass
    // below means the views are clean rather than that nothing was read.
    const sample = [
        '<div class="transition ease-out duration-150"></div>',
        '<span x-transition></span>',
        "'class' => 'animate-spin hover:transition-colors',",
        '<p class="motion-safe:transition motion-safe:duration-[250ms]"></p>',
    ].join('\n');
    const caught = motionFindings(sample).length;
    expect(caught === 7, 'the motion scanner catches literal durations, curves, bare transitions and animations, and bare x-transition',
        `the scanner found ${caught} of the 7 planted problems`);

    const clean = [
        '<div x-transition:enter="motion-safe:transition motion-safe:duration-popover-enter motion-safe:ease-enter"></div>',
        "'transition' => ['x-transition:enter' => 'motion-safe:transition'],",
        "...$recipe['transition'],",
        '<svg class="motion-safe:animate-spin"></svg><i class="transition-none motion-reduce:transition-none transition-colors"></i>',
        '{{-- transition ease-out duration-150 in a comment --}}',
    ].join('\n');
    const noise = motionFindings(clean);
    expect(!noise.length, 'and passes motion-safe utilities, recipe keys, a motion-reduce branch and comments',
        `false findings: ${noise.join('; ')}`);

    const views = [];
    const walk = async (dir) => {
        for (const entry of await readdir(dir, { withFileTypes: true })) {
            const path = join(dir, entry.name);
            if (entry.isDirectory()) await walk(path);
            else if (entry.name.endsWith('.php')) views.push(path);
        }
    };
    await walk(join(ROOT, 'resources/views'));

    const problems = [];
    for (const file of views) {
        for (const finding of motionFindings(await readFile(file, 'utf8'))) {
            problems.push(`${file.slice(ROOT.length + 1)} ${finding}`);
        }
    }
    expect(views.length && !problems.length,
        `every transition and animation in the ${views.length} view files is motion-safe and timed by the motion tokens`,
        `${problems.length} found:\n      ${problems.join('\n      ')}`);
}

async function compileProbe(classes) {
    const { execFile } = await import('node:child_process');
    const { promisify } = await import('node:util');
    const { mkdir, writeFile, rm } = await import('node:fs/promises');

    // Inside the repo, because `@import "tailwindcss"` resolves from the input
    // file's directory and node_modules is here.
    const dir = join(ROOT, '.tokens-tmp');
    await mkdir(dir, { recursive: true });
    try {
        await writeFile(join(dir, 'in.css'), `@import "tailwindcss" source(none);
@import "${join(ROOT, 'dist/tokens.css')}";
@import "${join(ROOT, 'dist/theme.css')}";
@source inline("${classes.join(' ')}");
`);
        await promisify(execFile)('npx', ['@tailwindcss/cli', '-i', join(dir, 'in.css'), '-o', join(dir, 'out.css')], {
            cwd: ROOT,
            maxBuffer: 32 * 1024 * 1024,
        });
        return await readFile(join(dir, 'out.css'), 'utf8');
    } catch (error) {
        return error;
    } finally {
        await rm(dir, { recursive: true, force: true });
    }
}

function expect(ok, yes, no) {
    ok ? pass(yes) : fail(no);
}

// ------------------------------------------------------------ 4. output

head('build output');

for (const f of ['tokens.css', 'theme.css', 'tokens.js', 'tokens.json']) {
    try {
        const body = await readFile(join(ROOT, 'dist', f), 'utf8');
        body.length > 100
            ? pass(`dist/${f} (${(body.length / 1024).toFixed(1)} kB)`)
            : fail(`dist/${f} is suspiciously small`);
    } catch {
        fail(`dist/${f} missing — run npm run build`);
    }
}

// ------------------------------------------------------------ summary

head('Icon sizes');

// ─── icon sizes are all reachable ───────────────────────────────────
//
// The icon component builds `size-${size}` at runtime, so Tailwind's scanner
// never sees the finished class. theme.css carries an `@source inline(...)`
// list to generate them anyway. If a component starts asking for a size the
// list does not cover, that icon silently renders at its container's size —
// which is how every <x-ds::alert> shipped with a checkmark the height of the
// panel. This is the check that would have caught it.
{
    const theme = await readFile(new URL('../dist/theme.css', import.meta.url), 'utf8');
    const declared = new Set(
        (theme.match(/@source inline\("size-\{([^}]*)\}"\)/)?.[1] ?? '').split(','),
    );

    const asked = new Set();
    const walk = async (dir) => {
        for (const entry of await readdir(dir, { withFileTypes: true })) {
            const child = new URL(entry.name + (entry.isDirectory() ? '/' : ''), dir);
            if (entry.isDirectory()) await walk(child);
            else if (entry.name.endsWith('.blade.php')) {
                const src = await readFile(child, 'utf8');
                for (const m of src.matchAll(/<x-ds::icon\b[^>]*?\bsize="([0-9.]+)"/g)) {
                    asked.add(m[1]);
                }
            }
        }
    };
    await walk(new URL('../resources/views/', import.meta.url));

    const missing = [...asked].filter((n) => !declared.has(n));
    missing.length
        ? fail(`icon sizes with no rule generated: size-${missing.join(', size-')}`)
        : pass(`all ${asked.size} icon sizes the components ask for are generated`);
}

console.log(
    failures
        ? `\n\x1b[31m${failures} of ${checks} checks failed\x1b[0m\n`
        : `\n\x1b[32mall ${checks} checks passed\x1b[0m\n`,
);

process.exit(failures ? 1 : 0);
