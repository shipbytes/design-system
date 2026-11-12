/*
 * Anchored positioning: put a panel next to the element that opened it, and
 * keep it on screen. Hand-written, so the package keeps its zero dependencies.
 * Registers nothing; x-ds-anchor (behaviours/anchor.js) is how a view asks for
 * it.
 *
 *   const anchored = anchor(panel, {
 *       reference: el,                 what the panel hangs off
 *       placement: 'bottom-start',     top | bottom | left | right, then
 *                                      -start | -end, or nothing for centre
 *       offset: 8,                     the gap to the reference, in px
 *       margin: 8,                     the closest it may come to the viewport edge
 *       clamp: true,                   when neither side fits, cap the height
 *                                      and let the panel scroll inside
 *       width: null,                   'reference' to match the reference's width
 *   });
 *   anchored.update();                 measure and place, now
 *   anchored.stop();                   stop listening
 *
 * Placing means: try the preferred side; FLIP to the opposite one when the
 * preferred lacks room and the opposite has it (a side panel that fits on
 * neither falls back to below or above); SHIFT along the other axis so the
 * panel stays inside the viewport less the margin; and, with `clamp`, cap the
 * height at the room there is when nothing fits. Start and end follow the
 * reading direction, so bottom-start hangs from the right edge in a
 * right-to-left page.
 *
 * The panel is `position: fixed`, written inline, so an ancestor with overflow
 * hidden or auto cannot clip it. Fixed is relative to the viewport UNLESS an
 * ancestor has a transform, filter, perspective, containment or will-change
 * for one of those: that ancestor becomes the panel's containing block. This
 * finds such an ancestor and subtracts its position, so the panel still lands
 * beside the reference. What it cannot undo is that ancestor's clipping (its
 * own overflow hidden) or its scaling; both are documented limits.
 *
 * left and top, not a transform. A transformed panel is itself a containing
 * block, so a popover opened from inside it would be boxed by it, and clipped
 * by it the moment it scrolls; with left and top a nested panel escapes its
 * parent exactly as the parent escapes the page.
 *
 * Writes `data-side` and `data-align` on the panel, the side and alignment it
 * actually took, for an origin or an arrow to read. Never a class.
 */

const OPPOSITE = { top: 'bottom', bottom: 'top', left: 'right', right: 'left' };

export function parsePlacement(value) {
    const [side, align] = String(value ?? '').split('-');
    return {
        side: OPPOSITE[side] ? side : 'bottom',
        align: align === 'start' || align === 'end' ? align : 'center',
    };
}

/** The element's box, or the union of its children's when it has none of its own. */
function rectOf(el) {
    if (getComputedStyle(el).display !== 'contents') return el.getBoundingClientRect();

    // A `contents` wrapper, which is how ds::dropdown and ds::popover hold the
    // consumer's trigger without adding a box to the layout. Its own rectangle
    // is all zeros; its children's are where the trigger really is.
    const boxes = [...el.children].map(rectOf).filter((r) => r.width || r.height);
    if (!boxes.length) return el.getBoundingClientRect();
    const left = Math.min(...boxes.map((r) => r.left));
    const top = Math.min(...boxes.map((r) => r.top));
    const right = Math.max(...boxes.map((r) => r.right));
    const bottom = Math.max(...boxes.map((r) => r.bottom));
    return { left, top, right, bottom, width: right - left, height: bottom - top };
}

/** The nearest ancestor that boxes a fixed descendant, or null for the viewport. */
function containingBlock(el) {
    for (let node = el.parentElement; node && node !== document.documentElement; node = node.parentElement) {
        const s = getComputedStyle(node);
        if (
            s.transform !== 'none'
            || (s.translate && s.translate !== 'none')
            || (s.scale && s.scale !== 'none')
            || (s.rotate && s.rotate !== 'none')
            || s.perspective !== 'none'
            || s.filter !== 'none'
            || (s.backdropFilter && s.backdropFilter !== 'none')
            || /transform|translate|scale|rotate|perspective|filter/.test(s.willChange)
            || /paint|layout|strict|content/.test(s.contain)
            || (s.containerType && s.containerType !== 'normal')
        ) {
            return node;
        }
    }
    return null;
}

function originOf(block) {
    if (!block) return { x: 0, y: 0 };
    const r = block.getBoundingClientRect();
    return {
        x: r.left + block.clientLeft - block.scrollLeft,
        y: r.top + block.clientTop - block.scrollTop,
    };
}

const vertical = (side) => side === 'top' || side === 'bottom';

export function anchor(panel, options = {}) {
    let settings = options;
    let frame = 0;
    let block;
    let observer = null;

    function room(r, offset, margin, vw, vh) {
        return {
            top: r.top - offset - margin,
            bottom: vh - r.bottom - offset - margin,
            left: r.left - offset - margin,
            right: vw - r.right - offset - margin,
        };
    }

    function update() {
        frame = 0;
        const reference = settings.reference;
        if (!reference || !reference.isConnected || !panel.isConnected) return;

        const { side: preferred, align } = parsePlacement(settings.placement);
        const offset = settings.offset ?? 8;
        const margin = settings.margin ?? 8;
        const clamp = settings.clamp !== false;

        const style = panel.style;
        // Fixed, at the containing block's origin, with nothing the server's
        // fallback classes set left to pull it about: their margin, the
        // opposite inset and the centring translate would all still apply.
        style.position = 'fixed';
        style.left = '0px';
        style.top = '0px';
        style.right = 'auto';
        style.bottom = 'auto';
        style.margin = '0';
        style.translate = 'none';
        style.maxHeight = '';
        style.overflowY = '';

        const r = rectOf(reference);
        if (settings.width === 'reference') style.width = `${r.width}px`;

        // offsetWidth and offsetHeight, not the bounding box: the panel is
        // scaled while it animates in, and the box would measure the scale.
        const w = panel.offsetWidth;
        const h = panel.offsetHeight;
        const vw = document.documentElement.clientWidth;
        const vh = document.documentElement.clientHeight;
        const space = room(r, offset, margin, vw, vh);
        const need = (s) => (vertical(s) ? h : w);

        const candidates = [preferred, OPPOSITE[preferred]];
        if (!vertical(preferred)) candidates.push('bottom', 'top');

        let side = candidates.find((s) => space[s] >= need(s));
        let height = h;
        if (!side) {
            // Nothing fits. Take whichever vertical side has more room and,
            // with clamp, cap the height there; without it, keep the preferred
            // side and let it overflow, as every overlay did before this.
            if (clamp) {
                side = space.bottom >= space.top ? 'bottom' : 'top';
                height = Math.max(space[side], 0);
            } else {
                side = preferred;
            }
        }

        const rtl = getComputedStyle(reference).direction === 'rtl';
        let x;
        let y;

        if (vertical(side)) {
            y = side === 'bottom' ? r.bottom + offset : r.top - offset - height;
            const start = rtl ? r.right - w : r.left;
            const end = rtl ? r.left : r.right - w;
            x = align === 'start' ? start : align === 'end' ? end : r.left + r.width / 2 - w / 2;
            x = Math.min(Math.max(x, margin), Math.max(vw - margin - w, margin));
        } else {
            x = side === 'right' ? r.right + offset : r.left - offset - w;
            y = align === 'start' ? r.top : align === 'end' ? r.bottom - h : r.top + r.height / 2 - h / 2;
            if (clamp && h > vh - 2 * margin) height = vh - 2 * margin;
            y = Math.min(Math.max(y, margin), Math.max(vh - margin - height, margin));
        }

        if (height < h) {
            style.maxHeight = `${height}px`;
            style.overflowY = 'auto';
        }

        if (block === undefined) block = containingBlock(panel);
        const origin = originOf(block);

        // Whole device pixels, so text in the panel is not resampled.
        const dpr = window.devicePixelRatio || 1;
        const snap = (v) => Math.round(v * dpr) / dpr;
        style.left = `${snap(x - origin.x)}px`;
        style.top = `${snap(y - origin.y)}px`;

        panel.dataset.side = side;
        panel.dataset.align = align;
    }

    function schedule() {
        if (!frame) frame = requestAnimationFrame(update);
    }

    function onResize() {
        // A resize can change which ancestor boxes the panel (a media query
        // adding a transform), so it is looked for again; a scroll cannot.
        block = undefined;
        schedule();
    }

    window.addEventListener('scroll', schedule, { capture: true, passive: true });
    window.addEventListener('resize', onResize, { passive: true });

    // Content that grows after opening (a list that loads, a combobox inside)
    // and a reference that changes size. Optional: without the observer, the
    // next scroll or resize still corrects it.
    if (typeof ResizeObserver === 'function') {
        observer = new ResizeObserver(schedule);
        observer.observe(panel);
        if (settings.reference) observer.observe(settings.reference);
    }

    return {
        update(next) {
            if (next) settings = next;
            block = undefined;
            update();
        },
        schedule,
        stop() {
            if (frame) cancelAnimationFrame(frame);
            frame = 0;
            observer?.disconnect();
            window.removeEventListener('scroll', schedule, { capture: true });
            window.removeEventListener('resize', onResize);
        },
    };
}
