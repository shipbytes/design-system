/*
 * Colour arithmetic for dsColour, mirrored by Support\Colour (reading and
 * writing) and Support\Ink (contrast). Registers nothing. Both sides are held
 * to tests/Fixtures/colour.json, by test-js.mjs here and ColourTest there, so
 * the server's first paint and the browser's every change agree.
 *
 * A colour is { r, g, b, a }: whole channels 0 to 255 and alpha 0 to 1. sRGB
 * only; no colour space beyond it.
 */

// The sixteen basic CSS names (and grey), not the 148: a table of every
// name costs more than the field is worth, and a canvas would parse them
// but is not something a text field should create on every key.
const NAMED = {
    black: '000', silver: 'c0c0c0', gray: '808080', grey: '808080', white: 'fff', maroon: '800000', red: 'f00', purple: '800080',
    fuchsia: 'f0f', green: '008000', lime: '0f0', olive: '808000', yellow: 'ff0', navy: '000080', blue: '00f', teal: '008080', aqua: '0ff',
};

const NUMBER = /^[+-]?(\d+\.?\d*|\.\d+)(%|deg)?$/;
const clamp = (n, lo, hi) => Math.min(hi, Math.max(lo, n));

/*
 * What a person typed, read forgivingly: "abc", "#ABC", "#aabbcc80",
 * "rgb(1, 2, 3)", "rgb(1 2 3 / 50%)", "hsl(210deg 50% 40%)", "teal". Out of
 * range channels are clamped, as CSS clamps them. Null for anything else.
 */
export function parse(text) {
    let s = String(text ?? '').trim().toLowerCase();
    if (NAMED[s]) s = NAMED[s];
    const hex = s.match(/^#?([\da-f]{3,4}|[\da-f]{6}|[\da-f]{8})$/);
    if (hex) {
        let h = hex[1];
        if (h.length < 5) h = [...h].map((c) => c + c).join('');
        const n = (i) => parseInt(h.slice(i, i + 2), 16);
        return { r: n(0), g: n(2), b: n(4), a: h.length === 8 ? n(6) / 255 : 1 };
    }
    const fn = s.match(/^(rgba?|hsla?)\((.*)\)$/);
    if (!fn) return null;
    const parts = fn[2].split(/[\s,/]+/).filter(Boolean);
    if ((parts.length !== 3 && parts.length !== 4) || !parts.every((p) => NUMBER.test(p))) return null;
    const [x, y, z, w] = parts.map((p) => [parseFloat(p), p.endsWith('%')]);
    const a = w ? clamp(w[1] ? w[0] / 100 : w[0], 0, 1) : 1;
    if (fn[1].startsWith('rgb')) {
        const ch = ([v, pct]) => Math.round(clamp(pct ? (v * 255) / 100 : v, 0, 255));
        return { r: ch(x), g: ch(y), b: ch(z), a };
    }
    return { ...fromHsl(x[0], clamp(y[0], 0, 100), clamp(z[0], 0, 100)), a };
}

const pair = (n) => Math.round(n).toString(16).padStart(2, '0');
const decimal = (n) => String(Math.round(n * 100) / 100);

/** #rrggbb, or #rrggbbaa with `alpha`. */
export function hex(c, alpha = false) {
    return `#${pair(c.r)}${pair(c.g)}${pair(c.b)}${alpha ? pair(c.a * 255) : ''}`;
}

/** The string a form posts: hex, or rgb() / hsl() in the comma syntax every parser reads. */
export function write(c, format = 'hex', alpha = false) {
    if (format === 'rgb') return alpha ? `rgba(${c.r}, ${c.g}, ${c.b}, ${decimal(c.a)})` : `rgb(${c.r}, ${c.g}, ${c.b})`;
    if (format === 'hsl') {
        const [h, s, l] = toHsl(c).map(Math.round);
        return alpha ? `hsla(${h}, ${s}%, ${l}%, ${decimal(c.a)})` : `hsl(${h}, ${s}%, ${l}%)`;
    }
    return hex(c, alpha);
}

/** [hue 0–360, saturation 0–100, lightness 0–100] */
export function toHsl({ r, g, b }) {
    [r, g, b] = [r / 255, g / 255, b / 255];
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    const l = (max + min) / 2;
    const d = max - min;
    const s = d === 0 ? 0 : d / (1 - Math.abs(2 * l - 1));
    return [hue(r, g, b, max, d), s * 100, l * 100];
}

export function fromHsl(h, s, l) {
    s /= 100;
    l /= 100;
    const k = (n) => (n + h / 30) % 12;
    const f = (n) => l - s * Math.min(l, 1 - l) * Math.max(-1, Math.min(k(n) - 3, 9 - k(n), 1));
    return { r: Math.round(f(0) * 255), g: Math.round(f(8) * 255), b: Math.round(f(4) * 255) };
}

/** [hue 0–360, saturation 0–100, brightness 0–100], what the picker's area and hue slider hold. */
export function toHsv({ r, g, b }) {
    [r, g, b] = [r / 255, g / 255, b / 255];
    const max = Math.max(r, g, b);
    const d = max - Math.min(r, g, b);
    return [hue(r, g, b, max, d), max === 0 ? 0 : (d / max) * 100, max * 100];
}

export function fromHsv(h, s, v) {
    s /= 100;
    v /= 100;
    const f = (n) => {
        const k = (n + h / 60) % 6;
        return v - v * s * Math.max(0, Math.min(k, 4 - k, 1));
    };
    return { r: Math.round(f(5) * 255), g: Math.round(f(3) * 255), b: Math.round(f(1) * 255) };
}

function hue(r, g, b, max, d) {
    if (d === 0) return 0;
    const h = max === r ? ((g - b) / d) % 6 : max === g ? (b - r) / d + 2 : (r - g) / d + 4;
    return (h * 60 + 360) % 360;
}

/*
 * WCAG contrast, as Support\Ink::contrast() works it out on the server.
 * A translucent colour is laid over `against` first, which is how it will
 * be seen on it.
 */
export function contrast(c, against) {
    const over = (ch) => Math.round(c[ch] * c.a + against[ch] * (1 - c.a));
    const lum = (x) => {
        const lin = (v) => {
            v /= 255;
            return v <= 0.04045 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4;
        };
        return 0.2126 * lin(x.r) + 0.7152 * lin(x.g) + 0.0722 * lin(x.b);
    };
    const [hi, lo] = [lum({ r: over('r'), g: over('g'), b: over('b') }), lum(against)].sort((p, q) => q - p);
    return (hi + 0.05) / (lo + 0.05);
}

/*
 * The ratio as it is shown: cut, not rounded, to two places, so a colour
 * that fails AA at 4.497 never reads "4.50". The pass marks are read from
 * this string, so what is shown and what passes agree. The 1e-4 is Ink's:
 * its path through OKLab puts black on white at 20.9999993.
 */
export const ratio = (n) => (Math.floor(n * 100 + 1e-4) / 100).toFixed(2);
