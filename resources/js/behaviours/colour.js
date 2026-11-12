/*
 * dsColour: the panel behind ds::color-picker, in a dsPopover.
 *
 *   x-data="dsColour({ alpha, format, contrastWith, invalid, words })"
 *
 * The view renders everything from PHP: the hidden input that posts
 * (data-ds-colour-value), the text field (data-ds-colour-text="field"), the
 * browser's own colour input it falls back to (data-ds-colour-native), and,
 * hidden until `dsReady`, a ds::popover holding three sliders
 * (data-ds-colour-slider="area", "hue", "alpha"), a tablist of formats
 * (data-ds-colour-tab, panes data-ds-colour-pane) with a hex field and
 * number fields (data-ds-colour-part="r" … "a"), and preset radios
 * (data-ds-colour-preset). Where every thumb sits, and the colour itself,
 * are CSS variables on the root (--ds-colour-h, -s, -v, -a and the colours
 * --ds-colour, --ds-colour-solid, --ds-colour-hue), which PHP writes first
 * and this rewrites.
 *
 *   - the colour is held as hue, saturation, brightness and alpha, so a
 *     grey keeps the hue its thumb was on and black keeps its saturation
 *   - each slider is a role=slider of our own: a press or drag (Pointer
 *     Events, captured) and the keys. The area: Left and Right saturation,
 *     Up and Down brightness, Page Up and Page Down brightness by 10, Home
 *     and End saturation to its ends. The others: the arrows, Page keys by
 *     10, Home and End. Shift makes an arrow 10
 *   - text is read forgivingly (shared/colour.js) and never rewritten while
 *     invalid: aria-invalid instead, and '' posts. A valid one is written
 *     back as hex when the field is left
 *   - the format tabs are a roving tablist (shared/roving.js) that only
 *     changes which fields edit the colour; `format` decides what posts
 *   - the contrast line, against `contrastWith`, is the ratio as shown and
 *     marks read from it (Support\Ink's arithmetic)
 *
 * The hidden input holds the colour in `format`, or ''. A change dispatches
 * `input` from it, and the end of one (a release, a key, leaving a field,
 * a preset) `change`; both bubble, as plain Events.
 *
 * The scope exposes `dsValue` (the posted string, for x-modelable) and
 * `dsReady`. A value the host sets, or a morph writes, is drawn without
 * events.
 */
import { parse, hex, write, toHsl, toHsv, fromHsl, fromHsv, contrast, ratio } from '../shared/colour.js';
import { roving } from '../shared/roving.js';

export function colour(Alpine) {
    Alpine.data('dsColour', (options = {}) => {
        let control = null;

        return {
            dsValue: '',
            dsReady: false,

            init() {
                control = wire(this.$el, options, (value) => {
                    this.dsValue = value;
                });
                this.dsValue = control.value();
                this.dsReady = true;
                this.$watch('dsValue', (value) => control.set(value));
            },

            destroy() {
                control?.teardown();
            },
        };
    });
}

const clamp = (n, lo, hi) => Math.min(hi, Math.max(lo, n));
// The one-dimensional sliders' ranges.
const RANGE = { hue: 360, alpha: 1 };

function wire(root, o, report) {
    const find = (selector) => root.querySelector(selector);
    const all = (selector) => [...root.querySelectorAll(selector)];
    const posted = find('[data-ds-colour-value]');
    const native = find('[data-ds-colour-native]');
    const tabs = all('[data-ds-colour-tab]');
    const words = o.words ?? {};
    const against = o.contrastWith ? parse(o.contrastWith) : null;
    const style = root.style;

    // Where the server put the thumbs, so a grey's hue is the one it chose.
    const css = (key) => Number(style.getPropertyValue(`--ds-colour-${key}`)) || 0;
    let h = css('h') * 360;
    let s = css('s') * 100;
    let v = css('v') * 100;
    let a = o.alpha ? (style.getPropertyValue('--ds-colour-a') === '' ? 1 : css('a')) : 1;
    let empty = posted.value === '';
    let committed = posted.value;
    let mine = posted.value;
    let dragging = null;

    const now = () => ({ ...fromHsv(h, s, v), a });
    const shown = () => (empty ? '' : hex(now(), o.alpha && a < 1));

    // Grey has no hue and black no saturation: keep the ones the thumbs had.
    function take(c) {
        const [hue, sat, val] = toHsv(c);
        if (sat > 0) h = hue;
        if (val > 0) s = sat;
        v = val;
        a = o.alpha ? c.a : 1;
        empty = false;
    }

    function mark(field, bad) {
        if (bad || (o.invalid && field.dataset.dsColourText === 'field')) field.setAttribute('aria-invalid', 'true');
        else field.removeAttribute('aria-invalid');
    }

    /*
     * Everything that shows the colour, but `from`, the field or pane being
     * typed in: rewriting that would move the caret and undo a half-typed
     * value. Then the posted value, which says whether it changed.
     */
    function draw(from = null) {
        const c = now();
        const skip = (el) => from && (el === from || from.contains(el));
        const rgb = `rgb(${c.r} ${c.g} ${c.b}`;
        const vars = { '': empty ? 'transparent' : `${rgb} / ${a})`, solid: empty ? 'transparent' : `${rgb})`, hue: `hsl(${h} 100% 50%)`, h: h / 360, s: s / 100, v: v / 100, a };
        for (const [key, value] of Object.entries(vars)) style.setProperty(key ? `--ds-colour-${key}` : '--ds-colour', String(value));

        for (const field of all('[data-ds-colour-text]')) {
            if (skip(field)) continue;
            field.value = shown();
            mark(field, false);
        }
        const [, sl, l] = toHsl(c);
        const parts = { r: c.r, g: c.g, b: c.b, h, s: sl, l, a: a * 100 };
        for (const part of all('[data-ds-colour-part]')) if (!skip(part)) part.value = empty ? '' : String(Math.round(parts[part.dataset.dsColourPart]));

        const aria = { area: [s, `${words.saturation} ${Math.round(s)}%, ${words.brightness} ${Math.round(v)}%`], hue: [h, `${Math.round(h)}°`], alpha: [a * 100, `${Math.round(a * 100)}%`] };
        for (const slider of all('[data-ds-colour-slider]')) {
            const [value, text] = aria[slider.dataset.dsColourSlider];
            slider.setAttribute('aria-valuenow', String(Math.round(value)));
            slider.setAttribute('aria-valuetext', text);
        }

        const said = find('[data-ds-colour-said]');
        if (said) said.textContent = empty ? words.none : shown().toUpperCase();
        for (const preset of all('[data-ds-colour-preset]')) preset.checked = !empty && preset.value === shown();
        if (native && !empty) native.value = hex(c);

        if (against) {
            const r = empty ? null : ratio(contrast(c, against));
            const out = find('[data-ds-colour-ratio]');
            if (out) out.textContent = r ? `${r}:1` : '–';
            for (const m of all('[data-ds-colour-mark]')) m.toggleAttribute('data-pass', r !== null && Number(r) >= Number(m.dataset.dsColourMark));
        }

        const value = empty ? '' : write(c, o.format, o.alpha);
        if (value === posted.value) return false;
        posted.value = mine = value;
        return true;
    }

    // A change the reader made: drawn, handed to the host and announced.
    function changed(from) {
        if (!draw(from)) return;
        report(posted.value);
        posted.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function commit() {
        if (posted.value === committed) return;
        committed = posted.value;
        posted.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const usable = (slider) => slider.getAttribute('aria-disabled') !== 'true';

    function point(slider, event) {
        const box = slider.getBoundingClientRect();
        const x = clamp((event.clientX - box.left) / box.width, 0, 1);
        const y = clamp((event.clientY - box.top) / box.height, 0, 1);
        const name = slider.dataset.dsColourSlider;
        if (name === 'area') [s, v] = [x * 100, (1 - y) * 100];
        else if (name === 'hue') h = x * 360;
        else a = Math.round(x * 100) / 100;
        empty = false;
        changed();
    }

    function onPointerDown(event) {
        const slider = event.target.closest('[data-ds-colour-slider]');
        if (!slider || !usable(slider) || event.button !== 0) return;
        event.preventDefault();
        slider.focus();
        slider.setPointerCapture?.(event.pointerId);
        dragging = slider;
        point(slider, event);
    }

    function onPointerMove(event) {
        if (dragging) point(dragging, event);
    }

    function onPointerUp() {
        if (!dragging) return;
        dragging = null;
        commit();
    }

    function onKeyDown(event) {
        const slider = event.target.closest?.('[data-ds-colour-slider]');
        if (!slider || !usable(slider) || event.altKey || event.ctrlKey || event.metaKey) return;
        const name = slider.dataset.dsColourSlider;
        const by = event.shiftKey ? 10 : 1;
        const k = event.key;
        if (name === 'area') {
            const moves = { ArrowRight: [by, 0], ArrowLeft: [-by, 0], ArrowUp: [0, by], ArrowDown: [0, -by], PageUp: [0, 10], PageDown: [0, -10] };
            if (k === 'Home' || k === 'End') s = k === 'Home' ? 0 : 100;
            else if (moves[k]) [s, v] = [clamp(s + moves[k][0], 0, 100), clamp(v + moves[k][1], 0, 100)];
            else return;
        } else {
            const max = RANGE[name];
            const unit = name === 'alpha' ? 0.01 : 1;
            const step = { ArrowRight: by, ArrowUp: by, ArrowLeft: -by, ArrowDown: -by, PageUp: 10, PageDown: -10 }[k];
            const was = name === 'hue' ? h : a;
            const next = k === 'Home' ? 0 : k === 'End' ? max : step ? clamp(Math.round((was + step * unit) / unit) * unit, 0, max) : null;
            if (next === null) return;
            if (name === 'hue') h = next;
            else a = next;
        }
        event.preventDefault();
        empty = false;
        changed();
        commit();
    }

    function onInput(event) {
        const t = event.target;
        if (t.matches('[data-ds-colour-text]')) {
            const text = t.value.trim();
            const c = parse(text);
            mark(t, Boolean(text) && !c);
            // Text that is not a colour posts nothing, as the server's does:
            // a form must not send a colour the field no longer shows.
            if (c) take(c);
            else empty = true;
            changed(t);
        } else if (t.matches('[data-ds-colour-part]')) {
            const pane = t.closest('[data-ds-colour-pane]');
            const read = (k) => {
                const field = pane.querySelector(`[data-ds-colour-part="${k}"]`);
                return field ? Number(field.value === '' ? NaN : field.value) : null;
            };
            const keys = pane.dataset.dsColourPane === 'rgb' ? ['r', 'g', 'b'] : ['h', 's', 'l'];
            const n = keys.map(read);
            const alpha = read('a');
            if (n.some((x) => !Number.isFinite(x)) || Number.isNaN(alpha)) return;
            const [p, q, r] = n;
            const c = pane.dataset.dsColourPane === 'rgb'
                ? { r: clamp(Math.round(p), 0, 255), g: clamp(Math.round(q), 0, 255), b: clamp(Math.round(r), 0, 255) }
                : fromHsl(clamp(p, 0, 360), clamp(q, 0, 100), clamp(r, 0, 100));
            take({ ...c, a: alpha === null ? a : clamp(alpha, 0, 100) / 100 });
            if (pane.dataset.dsColourPane === 'hsl' && q > 0) h = clamp(p, 0, 360);
            changed(pane);
        }
    }

    function onChange(event) {
        const t = event.target;
        if (t.matches('[data-ds-colour-preset]')) {
            const c = parse(t.value);
            if (c) take(c);
            changed();
        } else if (t.matches('[data-ds-colour-text]')) {
            // Left with a colour in it: written back as hex. Left invalid: as typed.
            if (!t.value.trim() || parse(t.value)) draw();
        } else if (t.matches('[data-ds-colour-part]')) {
            draw();
        } else {
            return;
        }
        commit();
    }

    function choose(tab) {
        for (const t of tabs) t.setAttribute('aria-selected', String(t === tab));
        for (const pane of all('[data-ds-colour-pane]')) pane.hidden = pane.dataset.dsColourPane !== tab.dataset.dsColourTab;
    }

    function onClick(event) {
        const tab = event.target.closest('[data-ds-colour-tab]');
        if (tab) {
            choose(tab);
            return;
        }
        if (!event.target.closest('[data-ds-colour-eyedropper]') || typeof window.EyeDropper !== 'function') return;
        new window.EyeDropper().open().then(({ sRGBHex }) => {
            const c = parse(sRGBHex);
            if (!c) return;
            take({ ...c, a });
            changed();
            commit();
        }, () => {});
    }

    const stopRoving = tabs.length
        ? roving(find('[data-ds-colour-tabs]'), { items: () => tabs, current: () => tabs.find((t) => t.getAttribute('aria-selected') === 'true'), moved: choose })
        : null;

    function set(given) {
        const text = String(given ?? '').trim();
        // Our own report, coming back through x-modelable.
        if (text === posted.value) return;
        const c = parse(text);
        if (!text) empty = true;
        else if (c) take(c);
        draw();
        committed = posted.value;
        if (posted.value !== text) report(posted.value);
    }

    // A hidden input's value is its attribute, so a morph writing the
    // server's colour shows up as a change that is not this script's.
    const observer = new MutationObserver(() => {
        if (posted.value !== mine) set(posted.value);
    });
    observer.observe(posted, { attributes: true, attributeFilter: ['value'] });

    const listeners = [
        [root, 'pointerdown', onPointerDown],
        [root, 'pointermove', onPointerMove],
        [root, 'pointerup', onPointerUp],
        [root, 'pointercancel', onPointerUp],
        [root, 'keydown', onKeyDown],
        [root, 'input', onInput],
        [root, 'change', onChange],
        [root, 'click', onClick],
    ];
    for (const [el, type, fn] of listeners) el.addEventListener(type, fn);

    return {
        value: () => posted.value,
        set,
        teardown() {
            observer.disconnect();
            stopRoving?.();
            for (const [el, type, fn] of listeners) el.removeEventListener(type, fn);
        },
    };
}
