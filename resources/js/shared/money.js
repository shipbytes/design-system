/*
 * Amounts of money as strings of digits, never as numbers. 0.1 + 0.2 is
 * 0.30000000000000004 in floating point, and an amount past 2^53 minor
 * units loses its last digits; neither can happen to a string. The one
 * arithmetic done, comparing with min, max and step, is on BigInt.
 * Mirrors Support\Money, which writes the field's first paint. Used by
 * dsMoneyInput; registers nothing.
 *
 * An amount is { sign: '-' or '', int: '1234', frac: '5', point: true }:
 * the digits as typed, `point` whether a decimal point was. For a machine
 * it is written "1234.50", or "123450" in minor units.
 */

/** A locale's decimal point, group separator and grouping, from Intl. */
export function shape(locale) {
    let nf;
    try {
        nf = new Intl.NumberFormat(locale || 'en', { numberingSystem: 'latn', minimumFractionDigits: 1 });
    } catch {
        nf = new Intl.NumberFormat('en', { minimumFractionDigits: 1 });
    }
    const parts = nf.formatToParts(1234567890.5);
    const ints = parts.filter((p) => p.type === 'integer').map((p) => p.value.length);
    return {
        decimal: parts.find((p) => p.type === 'decimal')?.value ?? '.',
        group: parts.find((p) => p.type === 'group')?.value ?? '',
        // 12,34,56,789 in en-IN: three digits, then twos.
        primary: ints.length > 1 ? ints[ints.length - 1] : 0,
        secondary: ints.length > 2 ? ints[ints.length - 2] : ints[ints.length - 1],
        // Spanish writes 1234 but 12.345: a group only from two digits up.
        min: nf.formatToParts(1000).some((p) => p.type === 'group') ? 1 : 2,
    };
}

export function group(int, s) {
    if (!s.primary || !s.group || int.length < s.primary + s.min) return int;
    let out = int.slice(-s.primary);
    let rest = int.slice(0, -s.primary);
    while (rest) {
        out = rest.slice(-s.secondary) + s.group + out;
        rest = rest.slice(0, -s.secondary);
    }
    return out;
}

const trim = (digits) => digits.replace(/^0+(?=\d)/, '');

/*
 * What the field holds while someone types in it: the locale's own decimal
 * point, everything else that is not a digit dropped. null for nothing.
 */
export function typed(text, decimal) {
    const s = String(text ?? '');
    const sign = /^\s*[-−]/.test(s) ? '-' : '';
    const at = s.indexOf(decimal);
    const int = trim((at < 0 ? s : s.slice(0, at)).replace(/\D/g, ''));
    const frac = at < 0 ? '' : s.slice(at + 1).replace(/\D/g, '');
    if (!int && !frac && at < 0 && !sign) return null;
    return { sign, int, frac, point: at >= 0 };
}

/*
 * An amount from anywhere else: a paste, a host's value. Symbols, spaces
 * and grouping are dropped, and either "." or "," can be the decimal
 * point: with both, the last one is; with one used several times it is
 * grouping; used once it is the point unless three digits follow it and it
 * is not the locale's own point ("1,234" in en is a thousand).
 */
export function read(text, decimal = '.') {
    let s = String(text ?? '');
    const first = s.search(/\d/);
    if (first < 0) return null;
    const sign = /[-−(]/.test(s.slice(0, first)) ? '-' : '';
    if (decimal !== '.' && decimal !== ',') s = s.split(decimal).join('.');
    const body = s.slice(first).replace(/[^\d.,]/g, '');
    const marks = [...body.matchAll(/[.,]/g)];
    let at = -1;
    if (marks.length) {
        const last = marks[marks.length - 1];
        const after = body.length - last.index - 1;
        if (new Set(marks.map((m) => m[0])).size > 1) at = last.index;
        else if (marks.length === 1 && (last[0] === decimal || after !== 3)) at = last.index;
    }
    const int = trim((at < 0 ? body : body.slice(0, at)).replace(/\D/g, ''));
    const frac = at < 0 ? '' : body.slice(at + 1).replace(/\D/g, '');
    return { sign, int, frac, point: at >= 0 };
}

/** A host's value: a string as read() reads it, a number, or minor units. */
export function given(value, decimals, minor = false) {
    if (value === null || value === undefined || value === '') return null;
    let text = String(value);
    if (typeof value === 'number') {
        if (!Number.isFinite(value)) return null;
        // 1e21 and 1e-7 are how a number prints itself; neither is digits.
        if (/e/i.test(text)) text = Number.isInteger(value) ? BigInt(value).toString() : value.toFixed(20);
    }
    if (minor) {
        const m = /^\s*([-−]?)(\d+)\s*$/.exec(text);
        if (!m) return null;
        const digits = m[2].padStart(decimals + 1, '0');
        return { sign: m[1] ? '-' : '', int: trim(digits.slice(0, digits.length - decimals)), frac: digits.slice(digits.length - decimals), point: decimals > 0 };
    }
    return read(text, '.');
}

function increment(digits) {
    const d = digits.split('');
    let i = d.length - 1;
    while (i >= 0 && d[i] === '9') d[i--] = '0';
    if (i < 0) d.unshift('1');
    else d[i] = String(Number(d[i]) + 1);
    return d.join('');
}

/** To `decimals` places, half away from zero, as the server rounds. */
export function round(a, decimals) {
    if (!a || a.frac.length <= decimals) return a;
    const kept = (a.int || '0') + a.frac.slice(0, decimals);
    const digits = a.frac[decimals] >= '5' ? increment(kept) : kept;
    const cut = digits.length - decimals;
    return { ...a, int: trim(digits.slice(0, cut)) || '0', frac: digits.slice(cut) };
}

/** "1234.50", or "123450" in minor units; '' for no amount. */
export function machine(a, decimals, minor = false) {
    if (!a || (!a.int && !a.frac)) return '';
    const int = a.int || '0';
    const frac = a.frac.padEnd(decimals, '0').slice(0, decimals);
    const sign = /[1-9]/.test(int + frac) ? a.sign : '';
    if (minor) return sign + trim(int + frac);
    return sign + int + (decimals ? `.${frac}` : '');
}

/*
 * The amount as the field shows it. `pad` fills the places, as on leaving
 * the field; without it a point just typed stays, so "12." can become 12.5.
 */
export function write(a, s, decimals, pad = false) {
    if (!a) return '';
    if (pad && !a.int && !a.frac) return '';
    let frac = a.frac.slice(0, decimals);
    if (pad && decimals) frac = frac.padEnd(decimals, '0');
    const int = a.int || (frac || a.point ? '0' : '');
    const point = decimals > 0 && (frac || (a.point && !pad)) ? s.decimal : '';
    const sign = pad && !/[1-9]/.test(int + frac) ? '' : a.sign;
    return sign + group(int, s) + point + frac;
}

/** An amount in minor units at `scale` places, for comparing; null for none. */
export function units(value, scale) {
    const m = /^(-?)(\d+)(?:\.(\d*))?$/.exec(String(value ?? ''));
    if (!m) return null;
    return BigInt(m[1] + m[2] + (m[3] ?? '').padEnd(scale, '0').slice(0, scale));
}

/** 'min', 'max', 'step' or '' for a machine value against the bounds, all strings. */
export function check(value, { min = null, max = null, step = null } = {}) {
    if (value === '') return '';
    const places = (v) => (String(v ?? '').split('.')[1] ?? '').length;
    const scale = Math.max(places(value), places(min), places(max), places(step));
    const v = units(value, scale);
    if (min !== null && v < units(min, scale)) return 'min';
    if (max !== null && v > units(max, scale)) return 'max';
    const by = step === null ? null : units(step, scale);
    if (by && (v - (min === null ? 0n : units(min, scale))) % by !== 0n) return 'step';
    return '';
}
