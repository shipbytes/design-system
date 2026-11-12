/*
 * The digit roll: a number that changes by turning each digit, like a meter.
 * Behind ds::counter, which renders the number complete from PHP: a visually
 * hidden span carries it for assistive technology and a face shows it. This
 * only animates the face, and puts plain text back when it is done, so at
 * rest the number is selectable text and never ten stacked digits.
 *
 *   x-data x-ds-roll            the value comes from the server: rolls when a
 *                               re-render (a Livewire morph) changes it
 *   x-data x-ds-roll.in         and rolls up from zero the first time it is
 *                               scrolled into view
 *   x-data="dsRoll({ value: () => count, in: false })"
 *                               the value comes from the host's Alpine state,
 *                               formatted here exactly as PHP formats it
 *
 * The markup is the view's:
 *
 *   root      data-ds-counter, and data-ds-format: Support\Chart's
 *             formatOptions() as JSON, Intl.NumberFormat's own keys
 *   text      data-ds-counter-text, the hidden span; the truth
 *   face      data-ds-counter-face, aria-hidden, what the eye sees
 *   template  data-ds-counter-digit: one column, whose
 *             data-ds-counter-strip holds 0 to 9 and slides by --ds-digit
 *
 * Every class is the view's and travels in the template. The strip's slide is
 * motion-safe in the view, and reduced motion is checked here as well, so it
 * is an instant swap either way. Width does not jitter while rolling: the
 * digits are tabular, and a column is sized by an invisible digit.
 *
 * x-ds-roll is an ENHANCEMENT (test-js.mjs): without the script the counter is
 * the formatted number, and a morph still changes it. dsRoll is not: a value
 * the host keeps in Alpine is only written by this, so it is asked for through
 * Behaviour::data() and says so when the script is missing. Neither exposes
 * anything to the markup.
 */

const MINUS = '−';
const UNITS = ['', 'K', 'M', 'B', 'T'];

export function roll(Alpine) {
    Alpine.directive('ds-roll', (el, { modifiers }, { cleanup }) => {
        const counter = roller(el);
        if (!counter) return;

        const face = el.querySelector('[data-ds-counter-face]');
        // A re-render writes the new number into the hidden span (and the
        // face); the span is what this reads, because the face may be
        // mid-roll. Changes inside the face alone are this script's own,
        // the roll and animateIn's zeros, and are not news. Looked up each
        // time, in case a re-render replaced the span rather than its text.
        const observer = new MutationObserver((records) => {
            if (records.every((record) => face.contains(record.target))) return;
            counter.to(el.querySelector('[data-ds-counter-text]').textContent);
        });
        observer.observe(el, { attributes: true, attributeFilter: ['data-ds-value'], childList: true, characterData: true, subtree: true });

        const stopIn = modifiers.includes('in') ? counter.rollIn() : () => {};

        cleanup(() => {
            observer.disconnect();
            stopIn();
            counter.stop();
        });
    });

    Alpine.data('dsRoll', (options = {}) => {
        let counter = null;
        let effect = null;
        let stopIn = () => {};

        return {
            init() {
                counter = roller(this.$el);
                if (!counter || typeof options.value !== 'function') return;

                const text = this.$el.querySelector('[data-ds-counter-text]');
                const format = formatter(readFormat(this.$el));
                let first = true;

                effect = Alpine.effect(() => {
                    const value = options.value();
                    const shown = format(value);
                    text.textContent = shown;
                    this.$el.dataset.dsValue = Number.isFinite(Number(value)) ? String(Number(value)) : '';
                    // The first reading may differ from what the server
                    // drew; it is put in place, not rolled to.
                    if (first) counter.set(shown);
                    else counter.to(shown);
                    first = false;
                });

                if (options.in) stopIn = counter.rollIn();
            },

            destroy() {
                if (effect) Alpine.release(effect);
                stopIn();
                counter?.stop();
            },
        };
    });
}

/** The face's animator. `to` rolls from what is shown; `set` replaces it. */
function roller(root) {
    const face = root.querySelector('[data-ds-counter-face]');
    const template = root.querySelector('template[data-ds-counter-digit]');
    if (!face || !template) return null;

    let shown = face.textContent;
    let timer = 0;
    let frame = 0;

    const reduced = () => typeof matchMedia === 'function' && matchMedia('(prefers-reduced-motion: reduce)').matches;

    function stop() {
        clearTimeout(timer);
        cancelAnimationFrame(frame);
    }

    function set(value) {
        stop();
        shown = value;
        if (face.textContent !== value || face.children.length) face.textContent = value;
    }

    function to(value, from = shown) {
        // Already there, or on the way: a re-render that brings the same
        // number again.
        if (value === shown && value === from) return;
        if (reduced()) return set(value);
        stop();
        shown = value;

        // Digits are matched from the right, the units with the units, so
        // 999 to 1,000 turns three nines to zeros and a one rolls in.
        const fromDigits = from.replace(/[^0-9]/g, '');
        const toDigits = value.replace(/[^0-9]/g, '').length;
        let seen = 0;

        const strips = [];
        const parts = [...value].map((char) => {
            if (!/[0-9]/.test(char)) return document.createTextNode(char);
            const column = template.content.firstElementChild.cloneNode(true);
            const strip = column.querySelector('[data-ds-counter-strip]');
            const place = toDigits - 1 - seen++;
            const start = fromDigits[fromDigits.length - 1 - place] ?? '0';
            strip.style.setProperty('--ds-digit', `${-Number(start) * 10}%`);
            strips.push([strip, char]);
            return column;
        });

        face.replaceChildren(...parts);
        // Placed at the old digit before the slide, so the transition runs
        // from it rather than from wherever the layout first put the strip.
        void face.offsetWidth;
        frame = requestAnimationFrame(() => {
            for (const [strip, char] of strips) strip.style.setProperty('--ds-digit', `${-Number(char) * 10}%`);
        });

        const longest = strips.length ? duration(strips[0][0]) : 0;
        timer = setTimeout(() => set(value), longest + 50);
    }

    /*
     * Up from zero the first time half of it is in view, once. Without an
     * IntersectionObserver, or with reduced motion, it simply stays the number
     * the server drew.
     */
    function rollIn() {
        if (reduced() || typeof IntersectionObserver !== 'function') return () => {};
        const target = shown;
        const zero = target.replace(/[0-9]/g, '0');
        set(zero);
        const io = new IntersectionObserver((entries) => {
            if (!entries.some((entry) => entry.isIntersecting)) return;
            io.disconnect();
            to(shown === zero ? target : shown, zero);
        }, { threshold: 0.5 });
        io.observe(root);
        return () => io.disconnect();
    }

    return { to: (value) => to(value), set, stop, rollIn };
}

/** The longest transition on a strip, in ms, as the view's classes set it. */
function duration(strip) {
    const style = getComputedStyle(strip);
    const seconds = (list) => list.split(',').map((t) => parseFloat(t) * (t.trim().endsWith('ms') ? 1 : 1000) || 0);
    const durations = seconds(style.transitionDuration);
    const delays = seconds(style.transitionDelay);
    return Math.max(0, ...durations.map((d, i) => d + (delays[i] ?? 0)));
}

function readFormat(root) {
    try {
        return JSON.parse(root.dataset.dsFormat || '{}');
    } catch {
        return {};
    }
}

/*
 * A number as Support\Chart::format() writes it: the same Intl options, the
 * same compact units, a real minus before the prefix, and a long dash for
 * nothing. PHP rounds half away from zero to match Intl, not the other way.
 */
function formatter(o) {
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
