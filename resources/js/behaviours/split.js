/*
 * dsSplit: dividers that resize the panes either side of them, behind
 * ds::split.
 *
 *   x-data="dsSplit({ name: 'editor' })"
 *
 * The markup is the view's, rendered complete by PHP: the root
 * data-ds-split with data-orientation, its panes data-ds-split-pane (sized
 * by --ds-split-size, limited by min-width / max-width or the heights,
 * data-ds-collapsible on one that folds away), each followed by a divider
 * data-ds-split-divider with role=separator, and a shield
 * data-ds-split-shield. Only the root's own children are its panes, so a
 * split inside a pane is a split of its own.
 *
 * A divider moves the boundary between the pane before it and the pane
 * after it, the two sizes always adding up to what they had; each pane's
 * own limits hold. It names and values ONE of them, the pane it controls:
 * the one before it, unless only the one after it folds away. Its value is
 * that pane's share of the split, in percent.
 *
 *   - Pointer, under capture: drag the divider. The shield covers the window
 *     meanwhile, so an iframe in a pane cannot take the pointer, and the
 *     root is data-dragging (no text selection).
 *   - Keyboard, on the divider: the arrows move it 1%, Shift 10%; Home and
 *     End take the controlled pane to its smallest and largest; Enter folds
 *     a collapsible pane away and brings it back. Double-click does too.
 *   - A divider not drawn (the split is stacked below `stackBelow`) does
 *     nothing.
 *
 * Sizes are written back as percentages of the split, so a pane keeps its
 * share when the window changes. It dispatches `resize` while a drag moves,
 * at most once a frame, and `resized` when it ends and after each key, both
 * from the root, bubbling, with { name, sizes: [percent per pane] }. It
 * stores nothing: persisting is the host's.
 *
 * The scope exposes `dsReady`, which the dividers' tabindex is bound to,
 * and nothing else.
 */

const STEP = 1;
const BIG_STEP = 10;

export function split(Alpine) {
    Alpine.data('dsSplit', (options = {}) => {
        let control = null;

        return {
            dsReady: false,

            init() {
                control = wire(this.$el, options);
                this.dsReady = true;
                this.$nextTick(() => control.value());
            },

            destroy() {
                control?.teardown();
            },
        };
    });
}

function wire(root, options) {
    const across = root.dataset.orientation === 'vertical' ? 'height' : 'width';
    const own = (selector) => [...root.children].filter((el) => el.matches(selector));
    const shield = own('[data-ds-split-shield]')[0];
    const panes = () => own('[data-ds-split-pane]');
    const dividers = () => own('[data-ds-split-divider]');

    // What a pane had before it was folded away, to bring it back to.
    const folded = new WeakMap();
    let drag = null;
    let frame = 0;

    const px = (el) => el.getBoundingClientRect()[across];
    const room = () => panes().reduce((sum, pane) => sum + px(pane), 0);

    // A limit the CSS holds, read back in pixels: min-width, max-width.
    function limit(pane, which) {
        if (pane.hasAttribute('data-collapsed')) return which === 'min' ? 0 : Infinity;
        const value = getComputedStyle(pane)[`${which}${across === 'width' ? 'Width' : 'Height'}`];
        if (!value || value === 'none' || value === 'auto') return which === 'min' ? 0 : Infinity;
        return value.endsWith('%') ? (parseFloat(value) / 100) * px(root) : parseFloat(value);
    }

    function pair(divider) {
        const all = panes();
        const before = divider.previousElementSibling;
        const at = all.indexOf(before);
        const after = all[at + 1];
        if (at < 0 || !after) return null;
        const folds = (p) => p.hasAttribute('data-ds-collapsible');
        const controlled = folds(after) && !folds(before) ? after : before;
        return { before, after, controlled };
    }

    function write(pane, size, total) {
        pane.style.setProperty('--ds-split-size', `${Math.round((size / total) * 10000) / 100}%`);
    }

    /*
     * Move the boundary to give `before` this many pixels, inside both panes'
     * limits, the other taking what is left of the two together.
     */
    function resizeTo(p, want, start = null) {
        const total = room();
        const a = start?.a ?? px(p.before);
        const b = start?.b ?? px(p.after);
        const sum = a + b;
        const low = Math.max(limit(p.before, 'min'), sum - limit(p.after, 'max'));
        const high = Math.min(limit(p.before, 'max'), sum - limit(p.after, 'min'));
        const next = Math.max(low, Math.min(high, want));
        write(p.before, next, total);
        write(p.after, sum - next, total);
        value();
    }

    // The controlled pane's share, and the range it may take.
    function value() {
        const total = room();
        if (!total) return;
        for (const divider of dividers()) {
            const p = pair(divider);
            if (!p) continue;
            const share = (pane) => Math.round((px(pane) / total) * 100);
            const folds = p.controlled.hasAttribute('data-ds-collapsible');
            const min = folds ? 0 : Math.round((limit(p.controlled, 'min') / total) * 100);
            const max = Math.min(100, Math.round((limit(p.controlled, 'max') / total) * 100));
            divider.setAttribute('aria-controls', p.controlled.id);
            divider.setAttribute('aria-valuenow', String(share(p.controlled)));
            divider.setAttribute('aria-valuemin', String(min));
            divider.setAttribute('aria-valuemax', String(max));
        }
    }

    function tell(type) {
        const total = room();
        const sizes = panes().map((pane) => (total ? Math.round((px(pane) / total) * 10000) / 100 : 0));
        root.dispatchEvent(new CustomEvent(type, { bubbles: true, detail: { name: options.name ?? null, sizes } }));
    }

    function fold(divider) {
        const p = pair(divider);
        if (!p || !p.controlled.hasAttribute('data-ds-collapsible')) return;
        const pane = p.controlled;
        const other = pane === p.before ? p.after : p.before;
        const total = room();
        if (pane.hasAttribute('data-collapsed')) {
            pane.removeAttribute('data-collapsed');
            const back = folded.get(pane) ?? total / 2;
            const sum = px(pane) + px(other);
            resizeTo(p, pane === p.before ? back : sum - back);
        } else {
            folded.set(pane, px(pane));
            const sum = px(pane) + px(other);
            pane.setAttribute('data-collapsed', '');
            write(pane, 0, total);
            write(other, sum, total);
            value();
        }
        tell('resized');
    }

    const usable = (divider) => divider.getClientRects().length > 0;
    const target = (event) => {
        const divider = event.target.closest?.('[data-ds-split-divider]');
        return divider && divider.parentElement === root && usable(divider) ? divider : null;
    };

    function onKeyDown(event) {
        const divider = target(event);
        const p = divider && pair(divider);
        if (!p) return;
        const total = room();
        const now = px(p.before);
        const grow = p.controlled === p.before ? 1 : -1;
        let want = null;
        if (['ArrowLeft', 'ArrowUp', 'ArrowRight', 'ArrowDown'].includes(event.key)) {
            const by = ((event.shiftKey ? BIG_STEP : STEP) / 100) * total;
            want = now + (['ArrowRight', 'ArrowDown'].includes(event.key) ? by : -by);
        } else if (event.key === 'Home' || event.key === 'End') {
            // The controlled pane to its smallest or largest.
            const small = event.key === 'Home';
            want = (grow > 0) === small ? -Infinity : Infinity;
        } else if (event.key === 'Enter') {
            event.preventDefault();
            fold(divider);
            return;
        } else {
            return;
        }
        event.preventDefault();
        if (p.controlled.hasAttribute('data-collapsed')) p.controlled.removeAttribute('data-collapsed');
        resizeTo(p, want);
        tell('resize');
        tell('resized');
    }

    function onDoubleClick(event) {
        const divider = target(event);
        if (divider) fold(divider);
    }

    function onPointerDown(event) {
        const divider = target(event);
        const p = divider && pair(divider);
        if (!p || event.button !== 0) return;
        event.preventDefault();
        divider.focus({ preventScroll: true });
        const at = across === 'width' ? event.clientX : event.clientY;
        drag = { divider, p, at, start: { a: px(p.before), b: px(p.after) }, id: event.pointerId, now: at };
        try {
            divider.setPointerCapture(event.pointerId);
        } catch {}
        divider.setAttribute('data-dragging', '');
        root.setAttribute('data-dragging', '');
        if (shield) shield.hidden = false;
    }

    function onPointerMove(event) {
        if (!drag || event.pointerId !== drag.id) return;
        drag.now = across === 'width' ? event.clientX : event.clientY;
        if (frame) return;
        frame = requestAnimationFrame(() => {
            frame = 0;
            if (!drag) return;
            if (drag.p.controlled.hasAttribute('data-collapsed')) drag.p.controlled.removeAttribute('data-collapsed');
            resizeTo(drag.p, drag.start.a + drag.now - drag.at, drag.start);
            tell('resize');
        });
    }

    function onPointerUp(event) {
        if (!drag || event.pointerId !== drag.id) return;
        const { divider } = drag;
        cancelAnimationFrame(frame);
        frame = 0;
        resizeTo(drag.p, drag.start.a + drag.now - drag.at, drag.start);
        drag = null;
        if (divider.hasPointerCapture?.(event.pointerId)) divider.releasePointerCapture(event.pointerId);
        divider.removeAttribute('data-dragging');
        root.removeAttribute('data-dragging');
        if (shield) shield.hidden = true;
        tell('resized');
    }

    root.addEventListener('keydown', onKeyDown);
    root.addEventListener('dblclick', onDoubleClick);
    root.addEventListener('pointerdown', onPointerDown);
    root.addEventListener('pointermove', onPointerMove);
    root.addEventListener('pointerup', onPointerUp);
    root.addEventListener('pointercancel', onPointerUp);

    return {
        value,
        teardown() {
            cancelAnimationFrame(frame);
            root.removeEventListener('keydown', onKeyDown);
            root.removeEventListener('dblclick', onDoubleClick);
            root.removeEventListener('pointerdown', onPointerDown);
            root.removeEventListener('pointermove', onPointerMove);
            root.removeEventListener('pointerup', onPointerUp);
            root.removeEventListener('pointercancel', onPointerUp);
        },
    };
}
