/*
 * x-ds-chart: a chart's values, reachable one at a time. Behind ds::bar-chart,
 * ds::line-chart and ds::donut-chart when `interactive` (the default):
 *
 *   x-data x-ds-chart
 *
 * The chart is complete without this. PHP draws it as an SVG with
 * role="img", named by its generated summary, with the values in a visually
 * hidden table when `table` is on: a correct picture for every reader. This
 * adds, on top:
 *
 *   - a readout beside the mark under the pointer or keyboard focus: its
 *     label, its formatted value and its series, on the tooltip's surface,
 *     placed by the anchor x-ds-anchor uses (shared/anchor.js);
 *   - every mark as a stop for the keyboard, through one tab stop per chart:
 *     the arrows along the categories move along a series (shared/roving.js),
 *     the arrows across them move to the next series at the same category,
 *     Home and End go to the ends, Escape puts the readout away;
 *   - legend entries that show and hide their series, when the view asks for
 *     it (data-ds-chart-toggles). Pure show and hide: nothing is re-scaled,
 *     so a value keeps its height and the reader keeps their bearings.
 *
 * Once marks can take focus they cannot sit inside an image, whose children
 * are presentational, so the plot becomes a group named by the same summary
 * and each mark an image named by the aria-label PHP already wrote on it
 * ("Mar, Revenue: $30K"). Without the script none of that happens and the
 * chart is the image it was rendered as.
 *
 * The markup is the view's:
 *
 *   root      x-data x-ds-chart; data-ds-chart-axis (horizontal | vertical |
 *             both: which arrows run along the categories), data-ds-chart-next
 *             (the arrow that moves to the next series), data-ds-chart-nearest
 *             (a line: the pointer picks the nearest point, not the one under
 *             it), data-ds-chart-toggles
 *   plot      data-ds-chart-plot, the svg
 *   mark      data-ds-chart-mark, with data-ds-at (category index), data-ds-in
 *             (series index), data-ds-label, data-ds-value (formatted by PHP),
 *             data-ds-series (its name, or empty) and data-ds-slot (1 to 8, or
 *             empty), and an aria-label
 *   series    data-ds-chart-series="n" on everything drawn for series n
 *   key       data-ds-chart-key="n", a legend entry
 *   readout   data-ds-chart-readout, hidden, holding
 *             data-ds-chart-readout-label, -series, -value and -swatch
 *
 * Visual state is attributes the view's classes read: data-ds-active on the
 * mark being read, data-ds-off on a hidden series, aria-pressed on a key,
 * data-series on the readout's swatch. Nothing exposed to the markup.
 *
 * An ENHANCEMENT (test-js.mjs): a host without the script gets the static
 * chart, which is right, and Alpine skips the directive in silence.
 */
import { roving } from '../shared/roving.js';
import { anchor } from '../shared/anchor.js';

const MARK = '[data-ds-chart-mark]';
const OPPOSITE = { ArrowDown: 'ArrowUp', ArrowUp: 'ArrowDown', ArrowLeft: 'ArrowRight', ArrowRight: 'ArrowLeft' };

export function chart(Alpine) {
    Alpine.directive('ds-chart', (el, _, { cleanup }) => {
        const stop = enhance(el);
        cleanup(stop);
    });
}

function enhance(root) {
    const plot = root.querySelector('[data-ds-chart-plot]');
    if (!plot) return () => {};

    const readout = root.querySelector('[data-ds-chart-readout]');
    const axis = root.dataset.dsChartAxis || 'horizontal';
    const next = root.dataset.dsChartNext || '';
    const nearest = root.hasAttribute('data-ds-chart-nearest');

    const all = () => [...plot.querySelectorAll(MARK)];
    const shown = (mark) => !mark.closest('[data-ds-off]');
    const marks = () => all().filter(shown);

    plot.setAttribute('role', 'group');
    // Chromium makes an svg with a role a sequential tab stop of its own,
    // ahead of the marks inside it: Tab would land on the whole plot, with
    // the browser's square ring and no readout. Out of the order, the first
    // stop is the mark roving chose.
    plot.setAttribute('tabindex', '-1');
    for (const mark of all()) mark.setAttribute('role', 'img');

    let active = null;
    let anchored = null;

    function fill(mark) {
        const part = (name) => readout.querySelector(`[data-ds-chart-readout-${name}]`);
        part('label').textContent = mark.dataset.dsLabel ?? '';
        part('series').textContent = mark.dataset.dsSeries ?? '';
        part('value').textContent = mark.dataset.dsValue ?? '';
        const swatch = part('swatch');
        if (swatch) {
            if (mark.dataset.dsSeries && mark.dataset.dsSlot) swatch.setAttribute('data-series', mark.dataset.dsSlot);
            else swatch.removeAttribute('data-series');
        }
    }

    function show(mark) {
        if (mark === active) return;
        active?.removeAttribute('data-ds-active');
        active = mark;
        mark.setAttribute('data-ds-active', '');
        if (!readout) return;

        fill(mark);
        readout.hidden = false;
        const settings = { reference: mark, placement: 'top', offset: 8, clamp: false };
        if (anchored) anchored.update(settings);
        else anchored = anchor(readout, settings);
    }

    function hide() {
        active?.removeAttribute('data-ds-active');
        active = null;
        if (readout) readout.hidden = true;
        anchored?.stop();
        anchored = null;
    }

    const rove = roving(plot, { items: marks, axis: () => axis });

    // Across the series: the same category in the next or previous series
    // that is showing, wrapping. Roving has the arrows along the categories;
    // the two never answer the same key.
    function onKeyDown(event) {
        if (event.key === 'Escape') {
            if (active && readout && !readout.hidden) {
                event.stopPropagation();
                hide();
            }
            return;
        }
        if (!next || (event.key !== next && event.key !== OPPOSITE[next])) return;
        if (event.altKey || event.ctrlKey || event.metaKey) return;

        const from = event.target.closest?.(MARK);
        if (!from) return;
        event.preventDefault();

        const live = marks();
        const series = [...new Set(live.map((m) => m.dataset.dsIn))];
        const by = event.key === next ? 1 : -1;
        const at = series.indexOf(from.dataset.dsIn);

        for (let step = 1; step < series.length; step++) {
            const wanted = series[(at + by * step + series.length * step) % series.length];
            const to = live.find((m) => m.dataset.dsIn === wanted && m.dataset.dsAt === from.dataset.dsAt);
            if (to) {
                to.focus();
                return;
            }
        }
    }

    function onFocusIn(event) {
        const mark = event.target.closest?.(MARK);
        if (mark) show(mark);
    }

    function onFocusOut(event) {
        if (!plot.contains(event.relatedTarget)) hide();
    }

    // A line's points are small and its readout is about a moment in time, so
    // the pointer picks the nearest point across, then the nearest of the
    // series there; bars and segments are read from what is under it.
    function closest(event) {
        let best = null;
        let gap = Infinity;
        for (const mark of marks()) {
            const r = mark.getBoundingClientRect();
            const dx = Math.abs(r.left + r.width / 2 - event.clientX);
            const dy = Math.abs(r.top + r.height / 2 - event.clientY);
            const score = dx * 1000 + dy;
            if (score < gap) {
                gap = score;
                best = mark;
            }
        }
        return best;
    }

    function onPointerMove(event) {
        const under = event.target.closest?.(MARK);
        const mark = nearest ? closest(event) : under && shown(under) ? under : null;
        if (mark) show(mark);
        else if (active && !plot.contains(document.activeElement)) hide();
    }

    function onPointerLeave() {
        if (!plot.contains(document.activeElement)) hide();
    }

    plot.addEventListener('keydown', onKeyDown);
    plot.addEventListener('focusin', onFocusIn);
    plot.addEventListener('focusout', onFocusOut);
    plot.addEventListener('pointermove', onPointerMove);
    // A tap moves nothing, so a finger is read where it lands.
    plot.addEventListener('pointerdown', onPointerMove);
    plot.addEventListener('pointerleave', onPointerLeave);

    const keys = root.hasAttribute('data-ds-chart-toggles') ? [...root.querySelectorAll('[data-ds-chart-key]')] : [];
    const pressed = (key) => key.getAttribute('aria-pressed') !== 'false';

    function toggle(key) {
        const on = pressed(key);
        // The last series showing stays: an empty plot answers nothing.
        if (on && keys.filter(pressed).length === 1) return;
        key.setAttribute('aria-pressed', on ? 'false' : 'true');
        for (const part of root.querySelectorAll(`[data-ds-chart-series="${key.dataset.dsChartKey}"]`)) {
            part.toggleAttribute('data-ds-off', on);
        }
        if (active && !shown(active)) hide();
        rove.settle();
    }

    function onKeyClick(event) {
        toggle(event.currentTarget);
    }

    function onKeyKey(event) {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        event.preventDefault();
        toggle(event.currentTarget);
    }

    for (const key of keys) {
        key.setAttribute('role', 'button');
        key.setAttribute('tabindex', '0');
        key.setAttribute('aria-pressed', 'true');
        key.addEventListener('click', onKeyClick);
        key.addEventListener('keydown', onKeyKey);
    }

    return () => {
        hide();
        rove();
        plot.removeAttribute('tabindex');
        plot.removeEventListener('keydown', onKeyDown);
        plot.removeEventListener('focusin', onFocusIn);
        plot.removeEventListener('focusout', onFocusOut);
        plot.removeEventListener('pointermove', onPointerMove);
        plot.removeEventListener('pointerdown', onPointerMove);
        plot.removeEventListener('pointerleave', onPointerLeave);
        for (const key of keys) {
            key.removeEventListener('click', onKeyClick);
            key.removeEventListener('keydown', onKeyKey);
        }
    };
}
