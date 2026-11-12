/*
 * dsSwipe: a row that slides sideways to show the actions under it, behind
 * ds::swipe-actions.
 *
 *   x-data="dsSwipe({ threshold: 56, full: false, disabled: false, group: null })"
 *
 * The markup is the view's: the root data-ds-swipe, its content
 * data-ds-swipe-content (with the More button data-ds-swipe-more in it) and
 * up to two groups of real buttons, data-ds-swipe-actions="start" and "end".
 * Without this the buttons sit after the content; marking the root
 * data-ds-ready puts them under it.
 *
 *   - Pointer: a horizontal drag on the content (touch-action pan-y, so a
 *     drag that starts vertical is the page's scroll). Swiping toward the
 *     inline end shows `start`, toward the inline start shows `end`, which
 *     flips in a right-to-left page. On release past `threshold` (or flung)
 *     it opens to the width of the actions; with `full`, past most of the
 *     row's width it dispatches `swipe` with { side } and presses the action
 *     at that side's outer edge (start's first, end's last).
 *   - More: opens the end side, then the start side, then closes.
 *   - Keyboard focus on an action opens its side; Escape closes the row and
 *     puts focus on More; focus leaving the row closes it.
 *   - A press on the content of an open row closes it and goes no further.
 *
 * Opening one row closes the others of its group: the rows with the same
 * data-ds-swipe-group, or else the rows of the nearest list.
 */
import { drag } from '../shared/drag.js';

const FULL = 0.6;
const FLING = 150;

export function swipe(Alpine) {
    Alpine.data('dsSwipe', (options = {}) => {
        let control = null;

        return {
            init() {
                if (!options.disabled) control = wire(this.$el, options);
            },

            destroy() {
                control?.teardown();
            },
        };
    });
}

function wire(root, options) {
    const content = root.querySelector('[data-ds-swipe-content]');
    const more = root.querySelector('[data-ds-swipe-more]');
    const sides = {
        start: root.querySelector('[data-ds-swipe-actions="start"]'),
        end: root.querySelector('[data-ds-swipe-actions="end"]'),
    };
    let open = null;
    let x = 0;
    let from = 0;
    let frame = 0;

    // +1 when showing that side moves the content right.
    const rtl = () => getComputedStyle(root).direction === 'rtl';
    const sign = (side) => ((side === 'start') !== rtl() ? 1 : -1);
    const sideAt = (px) => (px === 0 ? null : (px > 0) !== rtl() ? 'start' : 'end');
    const width = (side) => sides[side]?.offsetWidth ?? 0;

    // Only the side being shown is drawn: at rest the edges of a solid
    // action under the content bleed round it at a fractional pixel ratio.
    function draw(px) {
        x = px;
        content.style.transform = px ? `translateX(${px}px)` : '';
        for (const side in sides) if (sides[side]) sides[side].style.opacity = sideAt(px) === side ? '' : '0';
    }

    function set(side) {
        open = side && sides[side] ? side : null;
        draw(open ? sign(open) * width(open) : 0);
        root.toggleAttribute('data-open', Boolean(open));
        more?.setAttribute('aria-expanded', String(Boolean(open)));
        if (!open) return;
        const list = options.group
            ? document.querySelectorAll(`[data-ds-swipe-group="${CSS.escape(options.group)}"]`)
            : root.parentElement?.closest('ul, ol, [role=list]')?.querySelectorAll('[data-ds-swipe]') ?? [];
        for (const other of list) if (other !== root) other.dispatchEvent(new CustomEvent('ds-swipe-close'));
    }

    function press(side) {
        // The action at the edge the row slid away from: start's first, end's last.
        const all = sides[side]?.querySelectorAll('button, a[href]') ?? [];
        const first = side === 'start' ? all[0] : all[all.length - 1];
        root.dispatchEvent(new CustomEvent('swipe', { bubbles: true, detail: { side } }));
        set(null);
        first?.click();
    }

    const dragging = drag(content, {
        axis: 'x',
        slop: 6,
        swallowClick: true,
        press(event) {
            if (event.target.closest('input, select, textarea, [contenteditable]')) return null;
            // No text selection on the way to a mouse drag.
            if (event.pointerType === 'mouse') event.preventDefault();
            return content;
        },
        start() {
            from = x;
            content.setAttribute('data-dragging', '');
        },
        move(state) {
            let px = from + state.dx;
            const side = sideAt(px);
            const room = options.full ? content.offsetWidth : width(side);
            // Past what there is to show it gives a quarter of the way.
            if (!sides[side]) px /= 4;
            else if (Math.abs(px) > room) px = sign(side) * (room + (Math.abs(px) - room) / 4);
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => draw(px));
        },
        end(state) {
            cancelAnimationFrame(frame);
            content.removeAttribute('data-dragging');
            const px = from + state.dx;
            const side = sideAt(px);
            if (!sides[side]) return set(null);
            const reach = Math.abs(px + state.vx * FLING);
            if (options.full && Math.abs(px) > content.offsetWidth * FULL) return press(side);
            set(sign(side) * (px + state.vx * FLING) > 0 && reach >= (options.threshold ?? 56) ? side : null);
        },
        cancel() {
            cancelAnimationFrame(frame);
            content.removeAttribute('data-dragging');
            set(open);
        },
    });

    function onClick(event) {
        if (event.defaultPrevented) return;
        if (event.target.closest('[data-ds-swipe-more]')) {
            const both = sides.end && sides.start;
            set(!open ? (sides.end ? 'end' : 'start') : open === 'end' && both ? 'start' : null);
            return;
        }
        if (open && content.contains(event.target)) {
            event.preventDefault();
            event.stopPropagation();
            set(null);
        }
    }

    function onFocusIn(event) {
        const side = event.target.closest?.('[data-ds-swipe-actions]')?.dataset.dsSwipeActions;
        if (side && side !== open) set(side);
    }

    function onFocusOut(event) {
        if (open && !root.contains(event.relatedTarget)) set(null);
    }

    function onKeyDown(event) {
        if (event.key !== 'Escape' || !open) return;
        event.stopPropagation();
        const had = !content.contains(document.activeElement);
        set(null);
        if (had) more?.focus();
    }

    const close = () => set(null);
    const listeners = [
        [content, 'click', onClick, true],
        [root, 'focusin', onFocusIn],
        [root, 'focusout', onFocusOut],
        [root, 'keydown', onKeyDown],
        [root, 'ds-swipe-close', close],
        // A link's own drag would take the pointer away mid-swipe.
        [content, 'dragstart', (event) => event.preventDefault()],
    ];
    for (const [target, type, fn, capture] of listeners) target.addEventListener(type, fn, capture);
    root.setAttribute('data-ds-ready', '');
    draw(0);

    return {
        teardown() {
            cancelAnimationFrame(frame);
            dragging.stop();
            for (const [target, type, fn, capture] of listeners) target.removeEventListener(type, fn, capture);
            root.removeAttribute('data-ds-ready');
            for (const side in sides) sides[side]?.style.removeProperty('opacity');
        },
    };
}
