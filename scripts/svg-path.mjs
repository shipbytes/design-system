/**
 * SVG path normalisation.
 *
 * Comparing icon `d` attributes as strings does not work: the same shape is
 * written differently by different tools. Heroicons v2 ships optimised data —
 *
 *   app:      M4.5 12.75l6 6 9-13.5
 *   package:  m4.5 12.75 6 6 9-13.5
 *
 *   app:      M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607Z
 *   package:  m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z
 *
 * — differing in relative-vs-absolute commands, implicit linetos, and arc-flag
 * packing (`0 105.196` is `0 1 0 5.196`, because flags are single digits and
 * need no separator).
 *
 * So: tokenize, resolve every command to absolute coordinates, round, and
 * re-serialize. Two paths that draw the same shape then compare equal.
 */

const ARGS = { M: 2, L: 2, H: 1, V: 1, C: 6, S: 4, Q: 4, T: 2, A: 7, Z: 0 };

/** Split a `d` string into [command, ...numbers] groups, honouring arc flags. */
function tokenize(d) {
    const out = [];
    let i = 0;

    const skipSep = () => {
        while (i < d.length && /[\s,]/.test(d[i])) i++;
    };

    const readNumber = () => {
        skipSep();
        const m = /^[-+]?(?:\d*\.\d+|\d+\.?)(?:[eE][-+]?\d+)?/.exec(d.slice(i));
        if (!m) return null;
        i += m[0].length;
        return parseFloat(m[0]);
    };

    // Arc flags are a single character each and may be written with no
    // separator at all, so they cannot go through readNumber.
    const readFlag = () => {
        skipSep();
        const c = d[i];
        if (c !== '0' && c !== '1') return null;
        i++;
        return Number(c);
    };

    while (i < d.length) {
        skipSep();
        if (i >= d.length) break;

        const ch = d[i];
        if (!/[a-zA-Z]/.test(ch)) throw new Error(`unexpected "${ch}" at ${i}`);
        i++;

        const upper = ch.toUpperCase();
        const relative = ch !== upper;
        const arity = ARGS[upper];
        if (arity === undefined) throw new Error(`unknown command "${ch}"`);

        if (arity === 0) {
            out.push({ cmd: 'Z', rel: false, args: [] });
            continue;
        }

        // A command may be followed by several argument sets; the command
        // repeats implicitly. `M` repeating means `L`.
        let first = true;
        for (;;) {
            const before = i;
            const args = [];

            for (let k = 0; k < arity; k++) {
                const isFlag = upper === 'A' && (k === 3 || k === 4);
                const v = isFlag ? readFlag() : readNumber();
                if (v === null) {
                    i = before;
                    args.length = 0;
                    break;
                }
                args.push(v);
            }

            if (args.length !== arity) break;

            const cmd = !first && upper === 'M' ? 'L' : upper;
            out.push({ cmd, rel: relative, args });
            first = false;

            skipSep();
            if (i >= d.length || /[a-zA-Z]/.test(d[i])) break;
        }
    }

    return out;
}

/** Resolve to absolute coordinates and round, so shapes compare by geometry. */
export function normalizePath(d, precision = 2) {
    let tokens;
    try {
        tokens = tokenize(d);
    } catch {
        return null;
    }
    if (!tokens.length) return null;

    const r = (n) => {
        const v = Number(n.toFixed(precision));
        return Object.is(v, -0) ? 0 : v;
    };

    let x = 0, y = 0;      // current point
    let sx = 0, sy = 0;    // subpath start
    const parts = [];

    for (const { cmd, rel, args } of tokens) {
        const a = [...args];

        switch (cmd) {
            case 'M':
            case 'L':
            case 'T':
                if (rel) { a[0] += x; a[1] += y; }
                x = a[0]; y = a[1];
                if (cmd === 'M') { sx = x; sy = y; }
                parts.push(`${cmd}${r(a[0])},${r(a[1])}`);
                break;

            case 'H':
                if (rel) a[0] += x;
                x = a[0];
                parts.push(`H${r(a[0])}`);
                break;

            case 'V':
                if (rel) a[0] += y;
                y = a[0];
                parts.push(`V${r(a[0])}`);
                break;

            case 'C':
                if (rel) { a[0] += x; a[1] += y; a[2] += x; a[3] += y; a[4] += x; a[5] += y; }
                x = a[4]; y = a[5];
                parts.push(`C${a.map(r).join(',')}`);
                break;

            case 'S':
            case 'Q':
                if (rel) { a[0] += x; a[1] += y; a[2] += x; a[3] += y; }
                x = a[2]; y = a[3];
                parts.push(`${cmd}${a.map(r).join(',')}`);
                break;

            case 'A':
                if (rel) { a[5] += x; a[6] += y; }
                x = a[5]; y = a[6];
                parts.push(
                    `A${r(a[0])},${r(a[1])},${r(a[2])},${a[3]},${a[4]},${r(a[5])},${r(a[6])}`,
                );
                break;

            case 'Z':
                x = sx; y = sy;
                parts.push('Z');
                break;
        }
    }

    return parts.join('');
}

export default normalizePath;
