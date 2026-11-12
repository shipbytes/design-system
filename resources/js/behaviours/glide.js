/*
 * x-ds-glide: one highlight per container, moved to a target child.
 *
 *   x-ds-glide               selected: follows the checked, current, pressed or
 *                            selected item, and is always shown while there is one
 *   x-ds-glide="view"        the same, re-measured whenever `view` changes: the
 *                            host's model, which can check a radio without
 *                            firing an event
 *   x-ds-glide.hover         hover: follows the pointer, and keyboard focus, and
 *                            is hidden when neither is inside
 *
 * The markup is the component's, rendered complete by PHP:
 *
 *   container  data-ds-glide, positioned, and the `group/glide` its items read
 *   highlight  data-ds-glide-highlight, `hidden`, absolute at the top left,
 *              sized and moved by --ds-glide-x, -y, -w and -h
 *   items      data-ds-glide-item
 *
 * This only measures and writes. It never adds a class: what the highlight
 * looks like, and how it moves, is the view's, and travels as attributes the
 * view's variants read. On the highlight, `data-visible` (hover mode),
 * `data-instant` (place without sliding) and `data-pressing` (a press is under
 * way); on the container, `data-ds-glide-ready` once the highlight has taken
 * over from the server-rendered selected style.
 *
 * A glide inside another glide stands down, so a toggle group dropped into a
 * toolbar shows the toolbar's one highlight and not two.
 *
 * In a host without the script, Alpine skips this directive in silence, and
 * that is the design rather than the trap it usually is: every component that
 * uses it renders its state in full from PHP, so a missing glide loses an
 * animation and nothing else. See CLAUDE.md, "Traps".
 */

// Selected mode: what makes an item the one to sit under. A label wrapping a
// checked radio is the segmented control; the attributes are links and
// pressed or checked buttons.
const SELECTED = ':is([aria-current]:not([aria-current="false"]), [aria-checked="true"], [aria-pressed="true"], [aria-selected="true"])';

const DISABLED = ':is(:disabled, [aria-disabled="true"])';

// Attributes whose change can move a selected-mode highlight. Not `style`,
// `hidden` or any data-* attribute: those are the ones this file writes.
const WATCHED = ['aria-current', 'aria-checked', 'aria-pressed', 'aria-selected', 'checked', 'disabled', 'class'];

export function glide(Alpine) {
    Alpine.directive('ds-glide', (el, { modifiers, expression }, { effect, evaluateLater, cleanup }) => {
        if (el.parentElement?.closest('[data-ds-glide]')) return;

        const highlight = [...el.querySelectorAll('[data-ds-glide-highlight]')].find((h) => h.closest('[data-ds-glide]') === el);
        if (!highlight) return;

        const hover = modifiers.includes('hover');
        const glider = hover ? followPointer(el, highlight) : followSelection(el, highlight);

        if (!hover && expression) {
            const read = evaluateLater(expression);
            effect(() => read(() => glider.schedule()));
        }

        cleanup(glider.teardown);
    });
}

/** The parts both modes share: measuring, writing, and one write per frame. */
function mover(root, highlight) {
    let frame = 0;
    let instant = true;
    let settle = 0;

    const items = () => [...root.querySelectorAll('[data-ds-glide-item]')];

    /** The item's border box, in the container's padding box, in CSS pixels. */
    function measure(item) {
        let x = 0;
        let y = 0;
        let node = item;

        // offsetLeft is relative to the nearest POSITIONED ancestor, which is
        // the container only when nothing positioned sits between: a tooltip
        // around a toolbar button is, so the offsets are summed up the chain.
        while (node && node !== root) {
            x += node.offsetLeft;
            y += node.offsetTop;
            node = node.offsetParent;
            if (node && node !== root && !root.contains(node)) {
                node = null;
            }
        }

        if (node !== root) {
            // The container is not the offset parent at all (not positioned):
            // the slower rectangle reading, which still gives the right answer.
            const a = item.getBoundingClientRect();
            const b = root.getBoundingClientRect();
            x = a.left - b.left - root.clientLeft;
            y = a.top - b.top - root.clientTop;
        }

        return { x, y, w: item.offsetWidth, h: item.offsetHeight };
    }

    function write(box) {
        // Physical left and top, in both directions: offsetLeft is physical
        // in a right-to-left page too, so the highlight needs no mirroring.
        const style = highlight.style;
        style.setProperty('--ds-glide-x', `${box.x}px`);
        style.setProperty('--ds-glide-y', `${box.y}px`);
        style.setProperty('--ds-glide-w', `${box.w}px`);
        style.setProperty('--ds-glide-h', `${box.h}px`);
    }

    /**
     * Put the highlight on `item` now, without sliding when this is its first
     * placement, when it is appearing, or when the layout itself changed.
     */
    function moveTo(item, { jump = false } = {}) {
        const box = measure(item);
        if (!box.w || !box.h) return false;

        if (instant || jump) {
            // The attribute turns the movement transitions off for the frame
            // the new position is drawn in, and comes off in the next one, so
            // the change after this one slides again.
            highlight.setAttribute('data-instant', '');
            cancelAnimationFrame(settle);
            settle = requestAnimationFrame(() => {
                settle = requestAnimationFrame(() => highlight.removeAttribute('data-instant'));
            });
        }
        instant = false;
        write(box);
        return true;
    }

    function schedule(run, { relayout = false } = {}) {
        if (relayout) instant = true;
        // One measurement per frame however many events asked for it: a
        // pointer crossing a row fires dozens, and each read after a write
        // would force a layout.
        if (!frame) {
            frame = requestAnimationFrame(() => {
                frame = 0;
                run();
            });
        }
    }

    /** Re-measure when the container or any item changes size, or items come and go. */
    function observe(onChange, attributes = []) {
        let sizes = null;
        const watchSizes = () => {
            if (typeof ResizeObserver !== 'function') return;
            sizes?.disconnect();
            sizes = new ResizeObserver(() => onChange(true));
            sizes.observe(root);
            for (const item of items()) sizes.observe(item);
        };
        watchSizes();

        // No ResizeObserver: the window's own resize, which is most of what
        // moves an item without moving the DOM.
        const onResize = () => onChange(true);
        if (typeof ResizeObserver !== 'function') window.addEventListener('resize', onResize);

        const mutations = new MutationObserver((records) => {
            if (records.some((r) => r.type === 'childList')) watchSizes();
            onChange(false);
        });
        mutations.observe(root, {
            childList: true,
            subtree: true,
            attributes: attributes.length > 0,
            ...(attributes.length ? { attributeFilter: attributes } : {}),
        });

        return () => {
            sizes?.disconnect();
            mutations.disconnect();
            window.removeEventListener('resize', onResize);
        };
    }

    function stop() {
        cancelAnimationFrame(frame);
        cancelAnimationFrame(settle);
        frame = 0;
        highlight.removeAttribute('data-instant');
    }

    return { items, moveTo, schedule, observe, stop };
}

/** Selected mode: under the chosen item, always. */
function followSelection(root, highlight) {
    const m = mover(root, highlight);

    const chosen = () => m.items().find((item) => item.matches(SELECTED) || item.querySelector('input:checked')) ?? null;

    function place() {
        const item = chosen();
        if (!item || !m.moveTo(item)) {
            highlight.hidden = true;
            return;
        }
        if (highlight.hidden) highlight.hidden = false;
        // Set only once the highlight sits where the server-rendered style
        // was, so the swap from one to the other is never seen.
        if (!root.hasAttribute('data-ds-glide-ready')) root.setAttribute('data-ds-glide-ready', '');
    }

    const schedule = (relayout = false) => m.schedule(place, { relayout });
    const onChange = () => schedule();

    root.addEventListener('change', onChange);
    const unobserve = m.observe(schedule, WATCHED);
    schedule(true);

    return {
        schedule: () => schedule(),
        teardown() {
            m.stop();
            unobserve();
            root.removeEventListener('change', onChange);
            root.removeAttribute('data-ds-glide-ready');
            highlight.hidden = true;
        },
    };
}

/** Hover mode: under the pointer, or under keyboard focus, or nowhere. */
function followPointer(root, highlight) {
    const m = mover(root, highlight);

    let pointerItem = null;
    let focusItem = null;
    let pressed = null;

    const itemAt = (target) => {
        const item = target instanceof Element ? target.closest('[data-ds-glide-item]') : null;
        return item && root.contains(item) && !item.matches(DISABLED) ? item : null;
    };

    const focusVisible = (el) => {
        try {
            return el.matches(':focus-visible');
        } catch {
            // No :focus-visible: every focus counts as visible, which shows a
            // highlight after a click as well. Better than none for a keyboard.
            return true;
        }
    };

    // An item can be removed, or disabled by its own press, while it is the one
    // under the highlight.
    const live = (item) => (item?.isConnected && !item.matches(DISABLED) ? item : null);

    function place() {
        const item = live(pointerItem) ?? live(focusItem);
        const visible = highlight.hasAttribute('data-visible');

        if (!item) {
            if (visible) highlight.removeAttribute('data-visible');
            setPressing(false);
            return;
        }

        // Appearing: jump to the item, then fade in where it is, rather than
        // sliding in from wherever it was last hidden.
        if (!m.moveTo(item, { jump: !visible })) return;
        if (!visible) highlight.setAttribute('data-visible', '');
    }

    const schedule = (relayout = false) => m.schedule(place, { relayout });

    function setPressing(on) {
        if (on) highlight.setAttribute('data-pressing', '');
        else highlight.removeAttribute('data-pressing');
    }

    function release() {
        pressed?.abort();
        pressed = null;
        setPressing(false);
    }

    // Touch has no hover, and a highlight left behind under a lifted finger
    // reads as a stuck press: touch input is ignored throughout.
    const onPointerMove = (event) => {
        if (event.pointerType === 'touch') return;
        const item = itemAt(event.target);
        // Over a gap between items, or a disabled one: hold where it is.
        if (!item || item === pointerItem) return;
        pointerItem = item;
        schedule();
    };

    const onPointerLeave = (event) => {
        if (event.pointerType === 'touch') return;
        pointerItem = null;
        release();
        schedule();
    };

    const onPointerDown = (event) => {
        if (event.pointerType === 'touch' || event.button !== 0) return;
        const item = itemAt(event.target);
        if (!item) return;
        pointerItem = item;
        schedule();
        release();
        setPressing(true);
        pressed = new AbortController();
        // On the window: the pointer can be released anywhere.
        window.addEventListener('pointerup', release, { signal: pressed.signal });
        window.addEventListener('pointercancel', release, { signal: pressed.signal });
    };

    const onFocusIn = (event) => {
        const item = itemAt(event.target);
        if (item && focusVisible(event.target)) {
            focusItem = item;
            // The keyboard has taken over from a pointer resting on another
            // item; the pointer takes it back by moving.
            pointerItem = null;
        } else {
            focusItem = null;
        }
        schedule();
    };

    const onFocusOut = (event) => {
        if (root.contains(event.relatedTarget)) return;
        focusItem = null;
        schedule();
    };

    const onKeyDown = (event) => {
        if ((event.key === ' ' || event.key === 'Enter') && itemAt(event.target)) setPressing(true);
    };
    const onKeyUp = (event) => {
        if (event.key === ' ' || event.key === 'Enter') setPressing(false);
    };

    const listeners = [
        ['pointermove', onPointerMove],
        ['pointerleave', onPointerLeave],
        ['pointerdown', onPointerDown],
        ['focusin', onFocusIn],
        ['focusout', onFocusOut],
        ['keydown', onKeyDown],
        ['keyup', onKeyUp],
    ];
    for (const [type, fn] of listeners) root.addEventListener(type, fn);

    const unobserve = m.observe(schedule, ['disabled', 'aria-disabled']);
    highlight.hidden = false;
    root.setAttribute('data-ds-glide-ready', '');

    return {
        schedule: () => schedule(),
        teardown() {
            m.stop();
            release();
            unobserve();
            for (const [type, fn] of listeners) root.removeEventListener(type, fn);
            root.removeAttribute('data-ds-glide-ready');
            highlight.removeAttribute('data-visible');
            highlight.hidden = true;
        },
    };
}
