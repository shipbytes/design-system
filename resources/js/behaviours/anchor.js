/*
 * x-ds-anchor: keep an overlay beside the element that opened it, and on
 * screen. On the PANEL:
 *
 *   x-ds-anchor="{ open: menuOpen, reference: $refs.button }"
 *   x-ds-anchor="{ open: dsOpen, reference: …, placement: 'top-end', offset: 4 }"
 *   x-ds-anchor="{ open: open, reference: …, width: 'reference', clamp: true }"
 *
 *   open        required, read reactively: placed and listening while true,
 *               listening to nothing while false
 *   reference   the element the panel hangs off; a `contents` wrapper is
 *               measured by its children
 *   placement   top | bottom | left | right, then -start | -end or nothing
 *               for centre; default bottom-start
 *   offset      gap to the reference, px; default 8 (the mt-2 every overlay
 *               used)
 *   margin      closest it comes to the viewport edge, px; default 8
 *   clamp       when neither side has room, cap the height and scroll
 *               inside; default true
 *   width       'reference' to match the reference's width (a listbox under
 *               its field)
 *
 * The rules, flip and shift and clamp, and the fixed positioning that keeps an
 * ancestor's overflow from clipping, are in shared/anchor.js. The panel's
 * resolved side and alignment are written as data-side and data-align, which
 * the view's enter animation reads for its origin.
 *
 * Placed in the frame the panel is shown in. x-show reveals a transitioned
 * panel in an animation frame, and this directive's effect runs after
 * x-show's, so its frame comes after and nothing is painted at the fallback
 * position first. Then again on every scroll (any scroller, captured), resize
 * and size change, once per frame, while open.
 *
 * Designed to be dropped onto the overlays that predate it (dropdown, select,
 * combobox, date picker, tooltip) without changing them where the script is
 * missing: everything it writes is inline style, over the panel's own absolute
 * placement classes, which keep working when Alpine skips the directive.
 */
import { anchor } from '../shared/anchor.js';

export function anchorDirective(Alpine) {
    Alpine.directive('ds-anchor', (el, { expression }, { effect, evaluateLater, cleanup }) => {
        const read = evaluateLater(expression || '{}');
        let anchored = null;

        effect(() => read((options) => {
            const settings = options ?? {};
            if (settings.open) {
                if (!anchored) anchored = anchor(el, settings);
                else anchored.update(settings);
                requestAnimationFrame(() => anchored?.update(settings));
            } else if (anchored) {
                anchored.stop();
                anchored = null;
            }
        }));

        cleanup(() => anchored?.stop());
    });
}
