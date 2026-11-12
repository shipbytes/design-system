/*
 * x-ds-sheet: snap points and drag for ds::sheet (partials/sheet-layer).
 *
 *   x-ds-sheet="{ open: 'show.map', snap: ['25%', '60%', 'full'], initial: 0, draggable, dismissible, words }"
 *
 * An enhancement: without it the sheet opens at its natural height. With it
 * the panel is drawn as tall as its highest point and slid down by a
 * transform to show the one it is at; the move between points is the
 * panel's CSS transition, on the motion tokens.
 *
 *   - Drag the handle or the header, any pointer. Below the highest point a
 *     finger on the content drags too, and the content does not scroll; at
 *     the highest it scrolls, and a pull down from its top drags the sheet
 *     (touch-action pan-down there, so the browser leaves that gesture to
 *     us). Release settles on the nearest point by where it is and how fast
 *     it was going; past the lowest, a dismissible sheet closes.
 *   - The handle is a button: Enter, Space and a press step up through the
 *     points and wrap, ArrowUp and ArrowDown move one, and the state is spoken from
 *     `words` (expanded, half, collapsed) in the status region. Keyboard
 *     focus moving into the content below the highest point raises it.
 *   - Percentages are of the visual viewport, and the frame sits on top of an
 *     on-screen keyboard, where the browser reports one.
 *
 * Dispatches `snap` from the panel, bubbling, with { index, value } (the
 * index into `snap` as written) whenever the reader moves it.
 */
import { drag } from '../shared/drag.js';

const LOOKAHEAD = 200;

export function sheet(Alpine) {
    Alpine.directive('ds-sheet', (el, { expression }, { evaluate, effect, cleanup }) => {
        const options = evaluate(expression) ?? {};
        const control = wire(el, options, () => evaluate(`${options.open} = false`));
        effect(() => control.toggle(Boolean(evaluate(options.open))));
        cleanup(() => control.teardown());
    });
}

function wire(panel, options, close) {
    const frame = panel.closest('[data-ds-sheet-frame]') ?? panel.parentElement;
    const body = panel.querySelector('[data-ds-sheet-body]');
    const handle = panel.querySelector('[data-ds-sheet-handle]');
    const status = panel.querySelector('[data-ds-sheet-status]');
    const points = options.snap ?? [];
    const words = options.words ?? {};
    const viewport = window.visualViewport;

    let heights = [];
    let order = [];
    let tallest = 0;
    let at = options.initial ?? 0;
    let offset = 0;
    let shown = false;
    let frameId = 0;

    const highest = () => order[order.length - 1];
    const lowest = () => order[0];
    const atTop = () => at === highest();

    function measure() {
        const room = viewport?.height ?? innerHeight;
        const cap = Math.min(room, parseFloat(getComputedStyle(panel).maxHeight) || room);
        if (!points.length) {
            panel.style.removeProperty('height');
            heights = [panel.offsetHeight];
        } else {
            heights = points.map((p) => Math.min(cap, p === 'full' ? cap : p.endsWith('%') ? (parseFloat(p) / 100) * room : parseFloat(p)));
        }
        order = heights.map((_, i) => i).sort((a, b) => heights[a] - heights[b]);
        tallest = Math.max(...heights);
        if (points.length) panel.style.height = `${tallest}px`;
        if (viewport) frame.style.bottom = `${Math.max(0, innerHeight - viewport.height - viewport.offsetTop)}px`;
    }

    function draw(y) {
        offset = y;
        panel.style.transform = y ? `translateY(${y}px)` : '';
    }

    // Placed with no transition: the panel's own first frame, or a resize.
    function still(fn) {
        panel.setAttribute('data-dragging', '');
        fn();
        void panel.offsetHeight;
        panel.removeAttribute('data-dragging');
    }

    function scrollRule() {
        if (!body) return;
        body.style.overflowY = atTop() ? '' : 'hidden';
        body.style.touchAction = !atTop() ? 'pan-x' : body.scrollTop > 0 ? '' : 'pan-x pan-down';
    }

    function say() {
        if (!status || order.length < 2) return;
        status.textContent = atTop() ? words.expanded : at === lowest() ? words.collapsed : words.half;
    }

    function go(index, tell = true) {
        const changed = index !== at;
        at = index;
        draw(tallest - heights[at]);
        scrollRule();
        if (!changed) return;
        say();
        if (tell) panel.dispatchEvent(new CustomEvent('snap', { bubbles: true, detail: { index: at, value: points[at] ?? null } }));
    }

    function step(by, wrap = false) {
        const rank = order.indexOf(at) + by;
        if (rank >= order.length) return go(wrap ? lowest() : at);
        go(order[Math.max(0, rank)]);
    }

    function onResize() {
        if (!shown) return;
        still(() => {
            measure();
            draw(tallest - heights[at]);
        });
    }

    function toggle(open) {
        if (open === shown) return;
        shown = open;
        if (!open) {
            viewport?.removeEventListener('resize', onResize);
            viewport?.removeEventListener('scroll', onResize);
            return;
        }
        at = points[options.initial] !== undefined ? options.initial : 0;
        // After x-show has drawn the frame, so there is a height to measure.
        requestAnimationFrame(() => {
            if (!shown) return;
            still(() => {
                measure();
                draw(tallest - heights[at]);
            });
            scrollRule();
            if (status) status.textContent = '';
            viewport?.addEventListener('resize', onResize);
            viewport?.addEventListener('scroll', onResize);
        });
    }

    function onKeyDown(event) {
        if (!event.target.closest('[data-ds-sheet-handle]')) return;
        const by = { ArrowUp: 1, ArrowDown: -1 }[event.key];
        if (by) step(by);
        else if (event.key === 'Enter' || event.key === ' ') step(1, true);
        else return;
        event.preventDefault();
    }

    // A press on the handle, by a pointer, steps as Enter does. (Enter and
    // Space are taken at keydown, so the click they would make never comes.)
    function onClick(event) {
        if (event.detail && event.target.closest('[data-ds-sheet-handle]')) step(1, true);
    }

    // A Space released on the handle would press it a second time.
    function onKeyUp(event) {
        if (event.key === ' ' && event.target.closest('[data-ds-sheet-handle]')) event.preventDefault();
    }

    function onFocusIn(event) {
        if (!atTop() && body?.contains(event.target) && event.target.matches(':focus-visible')) go(highest());
    }

    let from = 0;
    const dragging = drag(panel, {
        axis: 'y',
        slop: 4,
        swallowClick: true,
        press(event) {
            if (!options.draggable || !shown) return null;
            if (event.target.closest('[data-ds-sheet-grip]')) return panel;
            if (event.pointerType !== 'touch' || !body?.contains(event.target)) return null;
            return atTop() && body.scrollTop > 0 ? null : panel;
        },
        start(state) {
            // At the top, content that can scroll up takes an upward drag.
            if (atTop() && state.dy < 0 && body.contains(state.pressed) && body.scrollHeight > body.clientHeight) return false;
            from = offset;
            panel.setAttribute('data-dragging', '');
            return true;
        },
        move(state) {
            const top = tallest - heights[highest()];
            const floor = options.dismissible ? tallest : tallest - heights[lowest()];
            let y = from + state.dy;
            // Past either end it gives a quarter of the way: there is nothing there.
            if (y < top) y = top - (top - y) / 4;
            if (y > floor) y = floor + (y - floor) / 4;
            cancelAnimationFrame(frameId);
            frameId = requestAnimationFrame(() => draw(y));
        },
        end(state) {
            cancelAnimationFrame(frameId);
            panel.removeAttribute('data-dragging');
            const seen = tallest - (from + state.dy) - state.vy * LOOKAHEAD;
            let best = at;
            for (const i of order) if (Math.abs(heights[i] - seen) < Math.abs(heights[best] - seen)) best = i;
            if (options.dismissible && Math.abs(seen) < Math.abs(heights[best] - seen)) {
                close();
                return;
            }
            go(best);
        },
        cancel() {
            cancelAnimationFrame(frameId);
            panel.removeAttribute('data-dragging');
            draw(tallest - heights[at]);
        },
    });

    handle?.removeAttribute('tabindex');
    handle?.removeAttribute('aria-hidden');
    panel.addEventListener('keydown', onKeyDown);
    panel.addEventListener('keyup', onKeyUp);
    panel.addEventListener('click', onClick);
    panel.addEventListener('focusin', onFocusIn);
    body?.addEventListener('scroll', scrollRule, { passive: true });

    return {
        toggle,
        teardown() {
            toggle(false);
            dragging.stop();
            cancelAnimationFrame(frameId);
            panel.removeEventListener('keydown', onKeyDown);
            panel.removeEventListener('keyup', onKeyUp);
        panel.removeEventListener('click', onClick);
            panel.removeEventListener('focusin', onFocusIn);
            body?.removeEventListener('scroll', scrollRule);
        },
    };
}
