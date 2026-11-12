/*
 * dsSortable: rows put in order by drag, by keyboard or by buttons, behind
 * ds::sortable-list.
 *
 *   x-data="dsSortable({ handle: true, disabled: false, words: { picked, moved, dropped, cancelled } })"
 *
 * The markup is the view's, rendered complete by PHP in the server's order:
 * the list data-ds-sortable-list, its rows data-ds-sortable-item (a value, a
 * label in data-ds-label, data-ds-sortable-fixed on one that stays put),
 * each with its hidden input, a handle data-ds-sortable-handle and the
 * buttons data-ds-sortable-up / -down; a placeholder and a status region.
 * Rows are moved in the DOM, so the hidden inputs post the order shown.
 *
 *   - Pointer: a press on a handle (or with handle: false, from a mouse or
 *     pen anywhere on the row that is not a control) and a move of a few
 *     pixels picks the row up. It follows the pointer under pointer capture,
 *     the others slide out of its way by transforms, the placeholder marks
 *     where it will land, and the nearest scrolling box scrolls near its
 *     edges. Release drops it and it settles into place; Escape or a
 *     cancelled pointer puts everything back.
 *   - Keyboard, on a handle: Space or Enter picks the row up, the arrows
 *     move it, Space or Enter drops it, Escape puts it back; leaving the
 *     handle drops it where it is. Each step is announced in the status
 *     region from `words` (:item, :position, :total).
 *   - The buttons move a row one place at once.
 *
 * After every drop that changed the order it dispatches `reorder` from the
 * root, bubbling, with { order: [values], moved: value, from, to }, the
 * positions counted from 0. A host that persists it and re-renders is
 * morphed back in its own order; the rows are read afresh every time, so
 * nothing here is held across a morph.
 *
 * The scope exposes `dsReady`, which the handles and buttons are shown by,
 * and nothing else.
 */

import { drag } from '../shared/drag.js';

const CONTROL = 'a[href], button, input, select, textarea, label, [contenteditable]';
// How far the pointer travels before a press becomes a drag, so a click on
// the handle is still a click.
const SLOP = 4;
const EDGE = 48;

export function sortable(Alpine) {
    Alpine.data('dsSortable', (options = {}) => {
        let control = null;

        return {
            dsReady: false,

            init() {
                control = wire(this.$el, options);
                this.dsReady = true;
            },

            destroy() {
                control?.teardown();
            },
        };
    });
}

function wire(root, options) {
    const list = root.querySelector('[data-ds-sortable-list]');
    const placeholder = root.querySelector('[data-ds-sortable-placeholder]');
    const status = root.querySelector('[data-ds-sortable-status]');
    const words = options.words ?? {};
    const reduced = () => matchMedia('(prefers-reduced-motion: reduce)').matches;

    const rows = () => [...list.children].filter((el) => el.matches('[data-ds-sortable-item]'));
    const movable = (row) => !options.disabled && !row.hasAttribute('data-ds-sortable-fixed');
    const handleOf = (row) => row.querySelector('[data-ds-sortable-handle]');

    let lifted = null;
    let dragged = null;
    let moving = false;

    function say(template, row) {
        const all = rows();
        const text = String(template ?? '')
            .replaceAll(':item', row.dataset.dsLabel ?? '')
            .replaceAll(':position', String(all.indexOf(row) + 1))
            .replaceAll(':total', String(all.length));
        // Emptied first, so the same words twice are still spoken twice.
        status.textContent = '';
        requestAnimationFrame(() => {
            status.textContent = text;
        });
    }

    // The ends of the list cannot be moved past; aria-disabled rather than
    // disabled, so a button that just became an end keeps its focus.
    function settle() {
        const all = rows();
        all.forEach((row, i) => {
            row.querySelector('[data-ds-sortable-up]')?.setAttribute('aria-disabled', String(i === 0));
            row.querySelector('[data-ds-sortable-down]')?.setAttribute('aria-disabled', String(i === all.length - 1));
        });
    }

    function commit(row, from) {
        settle();
        const all = rows();
        const to = all.indexOf(row);
        if (to === from) return;
        root.dispatchEvent(new CustomEvent('reorder', {
            bubbles: true,
            detail: { order: all.map((r) => r.dataset.value), moved: row.dataset.value, from, to },
        }));
    }

    /*
     * Move `row` to `to`, animated from where everything is drawn now to
     * where it lands (FLIP): measure, move, measure, start each row at its
     * old place and let its transition carry it home.
     */
    function place(row, to) {
        const all = rows();
        const first = new Map(all.map((r) => [r, r.getBoundingClientRect().top]));
        const had = row.contains(document.activeElement) ? document.activeElement : null;
        const others = all.filter((r) => r !== row);
        moving = true;
        list.insertBefore(row, others[to] ?? null);
        if (had && document.activeElement !== had) had.focus({ preventScroll: true });
        moving = false;

        for (const r of all) {
            r.setAttribute('data-instant', '');
            r.style.removeProperty('transform');
        }
        if (reduced()) {
            for (const r of all) r.removeAttribute('data-instant');
            return;
        }
        for (const r of all) {
            const delta = first.get(r) - r.getBoundingClientRect().top;
            if (delta) r.style.transform = `translateY(${delta}px)`;
        }
        // Read once so the jump back lands before the transitions are on.
        void list.offsetHeight;
        for (const r of all) {
            r.removeAttribute('data-instant');
            r.style.removeProperty('transform');
        }
    }

    // ------------------------------------------------------------ keyboard

    function pick(row) {
        lifted = { row, from: rows().indexOf(row) };
        row.setAttribute('data-lifted', '');
        handleOf(row)?.setAttribute('aria-pressed', 'true');
        say(words.picked, row);
    }

    function put(cancelled) {
        if (!lifted) return;
        const { row, from } = lifted;
        lifted = null;
        if (cancelled && rows().indexOf(row) !== from) place(row, from);
        row.removeAttribute('data-lifted');
        handleOf(row)?.setAttribute('aria-pressed', 'false');
        say(cancelled ? words.cancelled : words.dropped, row);
        if (!cancelled) commit(row, from);
        else settle();
    }

    function step(row, by) {
        const at = rows().indexOf(row);
        const to = at + by;
        if (to < 0 || to >= rows().length) return false;
        place(row, to);
        return true;
    }

    // Escape during a drag, wherever focus is.
    function onDocumentKey(event) {
        if (!dragged?.started || event.key !== 'Escape') return;
        event.preventDefault();
        event.stopPropagation();
        dragging.cancel();
    }

    function onKeyDown(event) {
        if (dragged) return;
        const handle = event.target.closest?.('[data-ds-sortable-handle]');
        const row = handle?.closest('[data-ds-sortable-item]');
        if (!row || !list.contains(row) || !movable(row)) return;
        const key = event.key;

        if (!lifted) {
            if (key === ' ' || key === 'Enter') {
                event.preventDefault();
                pick(row);
            }
            return;
        }
        if (lifted.row !== row) return;
        if (key === 'ArrowUp' || key === 'ArrowDown') {
            event.preventDefault();
            if (step(row, key === 'ArrowUp' ? -1 : 1)) say(words.moved, row);
        } else if (key === ' ' || key === 'Enter') {
            event.preventDefault();
            put(false);
        } else if (key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            put(true);
        }
    }

    // A Space released on a button presses it; the press that picked a row
    // up or dropped it must not also reach the host.
    function onKeyUp(event) {
        if (event.key === ' ' && event.target.closest?.('[data-ds-sortable-handle]')) event.preventDefault();
    }

    function onFocusOut(event) {
        if (moving || !lifted || !lifted.row.contains(event.target)) return;
        if (event.relatedTarget && lifted.row.contains(event.relatedTarget)) return;
        put(false);
    }

    function onClick(event) {
        const button = event.target.closest?.('[data-ds-sortable-up], [data-ds-sortable-down]');
        const row = button?.closest('[data-ds-sortable-item]');
        if (!row || !list.contains(row) || !movable(row) || button.getAttribute('aria-disabled') === 'true') return;
        const from = rows().indexOf(row);
        if (step(row, button.matches('[data-ds-sortable-up]') ? -1 : 1)) {
            say(words.moved, row);
            commit(row, from);
        }
    }

    // ------------------------------------------------------------ pointer

    function press(event) {
        if (dragged || lifted) return null;
        const row = event.target.closest('[data-ds-sortable-item]');
        if (!row || !list.contains(row) || !movable(row)) return null;
        if (!event.target.closest('[data-ds-sortable-handle]')) {
            // Touch only by the handle, whose touch-action is none: anywhere
            // else a finger scrolls the page.
            if (options.handle !== false || event.pointerType === 'touch' || event.target.closest(CONTROL)) return null;
        }
        // No text selection starting under a mouse on its way to a drag.
        if (event.pointerType !== 'touch') event.preventDefault();
        return row;
    }

    function startDrag(state) {
        const row = state.target;
        dragged = { row, started: true, y0: state.y0, y: state.y };
        const all = rows();
        const from = all.indexOf(row);
        const tops = all.map((r) => r.offsetTop);
        const heights = all.map((r) => r.offsetHeight);
        const gap = all.length > 1 ? Math.max(0, tops[1] - tops[0] - heights[0]) : 0;
        Object.assign(dragged, {
            started: true, all, from, to: from, tops, heights, shift: heights[from] + gap,
            origin: root.getBoundingClientRect().top, scroller: scrollParent(root), frame: 0,
        });
        row.setAttribute('data-dragging', '');
        root.setAttribute('data-dragging', '');
        placeholder.style.height = `${heights[from]}px`;
        placeholder.style.transform = `translateY(${tops[from]}px)`;
        placeholder.hidden = false;
    }

    function frame() {
        if (!dragged?.started) return;
        const { row, all, from, tops, heights, shift } = dragged;
        const dy = dragged.y - dragged.y0 - (root.getBoundingClientRect().top - dragged.origin);
        row.style.transform = `translateY(${dy}px)`;

        // A row gives way once the dragged row's leading edge passes its
        // middle: its bottom edge going down, its top edge going up. Its own
        // middle would be a whole row late.
        const top = tops[from] + dy;
        const bottom = top + heights[from];
        let to = from;
        for (let j = from + 1; j < all.length; j++) if (bottom > tops[j] + heights[j] / 2) to = j;
        for (let j = from - 1; j >= 0; j--) if (top < tops[j] + heights[j] / 2) to = j;
        dragged.to = to;

        all.forEach((r, j) => {
            if (r === row) return;
            const by = from < j && j <= to ? -shift : to <= j && j < from ? shift : 0;
            if (by) r.style.transform = `translateY(${by}px)`;
            else r.style.removeProperty('transform');
        });
        const slot = to > from ? tops[to] + heights[to] - heights[from] : tops[to];
        placeholder.style.transform = `translateY(${slot}px)`;

        // Near the scrolling box's edge, scroll it, and keep coming back
        // here while the pointer stays there.
        const box = dragged.scroller === document.scrollingElement
            ? { top: 0, bottom: innerHeight }
            : dragged.scroller.getBoundingClientRect();
        const speed = dragged.y < box.top + EDGE ? -(box.top + EDGE - dragged.y) / 3
            : dragged.y > box.bottom - EDGE ? (dragged.y - box.bottom + EDGE) / 3 : 0;
        if (speed) {
            dragged.scroller.scrollBy(0, Math.max(-24, Math.min(24, speed)));
            dragged.frame = requestAnimationFrame(frame);
        } else {
            dragged.frame = 0;
        }
    }

    function endDrag(cancelled) {
        const current = dragged;
        dragged = null;
        const { row } = current;
        cancelAnimationFrame(current.frame);
        placeholder.hidden = true;
        row.removeAttribute('data-dragging');
        root.removeAttribute('data-dragging');
        // From where it is drawn, under the pointer, to its slot.
        place(row, cancelled ? current.from : current.to);
        if (cancelled) settle();
        else commit(row, current.from);
    }

    const dragging = drag(list, {
        slop: SLOP,
        press,
        start: startDrag,
        move(state) {
            dragged.y = state.y;
            if (!dragged.frame) dragged.frame = requestAnimationFrame(frame);
        },
        end: () => endDrag(false),
        cancel: () => endDrag(true),
    });

    settle();
    list.addEventListener('keydown', onKeyDown);
    list.addEventListener('keyup', onKeyUp);
    list.addEventListener('focusout', onFocusOut);
    list.addEventListener('click', onClick);
    document.addEventListener('keydown', onDocumentKey, true);

    return {
        teardown() {
            dragging.stop();
            list.removeEventListener('keydown', onKeyDown);
            list.removeEventListener('keyup', onKeyUp);
            list.removeEventListener('focusout', onFocusOut);
            list.removeEventListener('click', onClick);
            document.removeEventListener('keydown', onDocumentKey, true);
        },
    };
}

// The box that scrolls the list: the nearest ancestor that scrolls, or the page.
function scrollParent(el) {
    for (let n = el.parentElement; n && n !== document.body; n = n.parentElement) {
        const { overflowY } = getComputedStyle(n);
        if (/(auto|scroll)/.test(overflowY) && n.scrollHeight > n.clientHeight) return n;
    }
    return document.scrollingElement;
}
