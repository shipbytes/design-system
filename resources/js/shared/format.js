/*
 * A number as Support\Chart::format() writes it, for a behaviour that has to
 * write one in the browser and agree with the server: dsRoll's counter and
 * dsSlider's readout. Registers nothing.
 *
 *   const format = formatter(options);   options: Chart::formatOptions(), as JSON
 *   format(1234.5)                       "1,234.5"
 *
 * The same Intl options, the same compact units, a real minus before the
 * prefix, and a long dash for nothing. PHP rounds half away from zero to match
 * Intl, not the other way.
 */

const MINUS = '−';
const UNITS = ['', 'K', 'M', 'B', 'T'];

export function formatter(o) {
    const max = Number.isInteger(o.maximumFractionDigits) ? o.maximumFractionDigits : 2;
    const min = Number.isInteger(o.minimumFractionDigits) ? Math.min(o.minimumFractionDigits, max) : 0;
    let nf;
    try {
        nf = new Intl.NumberFormat(o.locale || 'en', { minimumFractionDigits: min, maximumFractionDigits: max, useGrouping: o.useGrouping !== false });
    } catch {
        nf = new Intl.NumberFormat('en', { minimumFractionDigits: min, maximumFractionDigits: max, useGrouping: o.useGrouping !== false });
    }
    const round = (n) => Math.round(n * 10 ** max) / 10 ** max;

    return (value) => {
        const number = typeof value === 'string' && value.trim() === '' ? NaN : Number(value);
        if (value === null || value === undefined || !Number.isFinite(number)) return '—';

        let n = number;
        let unit = 0;
        if (o.compact) {
            while (Math.abs(n) >= 1000 && unit < UNITS.length - 1) {
                n /= 1000;
                unit++;
            }
            if (round(Math.abs(n)) >= 1000 && unit < UNITS.length - 1) {
                n /= 1000;
                unit++;
            }
        }

        const rounded = round(Math.abs(n));
        const sign = n < 0 && rounded > 0 ? MINUS : '';
        return `${sign}${o.prefix ?? ''}${nf.format(rounded)}${UNITS[unit]}${o.suffix ?? ''}`;
    };
}
