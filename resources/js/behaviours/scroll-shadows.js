/*
 * x-ds-scroll-shadows: ds::scroll-area's edge shadows, where CSS cannot.
 *
 *   x-data x-ds-scroll-shadows   on the area (data-ds-scroll-area), whose
 *                                data-ds-scroll-viewport scrolls and whose
 *                                data-ds-scroll-edge spans are the shadows
 *
 * A browser with scroll-driven animations draws the shadows from theme.css
 * alone, and this does nothing there. Elsewhere it marks each edge
 * data-ds-shown while there is content past it, measured on scroll and
 * whenever the viewport or its content changes size (ResizeObserver, or the
 * window's resize without one). In a right-to-left page "start" is the
 * right edge, as it is for the CSS.
 *
 * An ENHANCEMENT (test-js.mjs): without it there are no shadows, and the
 * content scrolls exactly the same. It registers nothing else and exposes
 * nothing.
 */

// A pixel of slack: a zoomed page reports fractional scroll positions, and
// one that is a hair short of the end is at the end.
const SLACK = 1;

export function scrollShadows(Alpine) {
    Alpine.directive('ds-scroll-shadows', (el, _directive, { cleanup }) => {
        if (globalThis.CSS?.supports?.('animation-timeline: scroll()')) return;

        const viewport = el.querySelector('[data-ds-scroll-viewport]');
        if (!viewport) return;
        const edges = Object.fromEntries(
            [...el.querySelectorAll('[data-ds-scroll-edge]')].map((edge) => [edge.dataset.dsScrollEdge, edge]),
        );

        let frame = 0;

        function measure() {
            frame = 0;
            // In a right-to-left box scrollLeft runs from 0 down to negative,
            // so its size is how far from the start either way.
            const across = Math.abs(viewport.scrollLeft);
            const room = {
                top: viewport.scrollTop > SLACK,
                bottom: viewport.scrollTop + viewport.clientHeight < viewport.scrollHeight - SLACK,
                start: across > SLACK,
                end: across + viewport.clientWidth < viewport.scrollWidth - SLACK,
            };
            for (const [side, edge] of Object.entries(edges)) edge.toggleAttribute('data-ds-shown', Boolean(room[side]));
        }

        const soon = () => {
            if (!frame) frame = requestAnimationFrame(measure);
        };

        viewport.addEventListener('scroll', soon, { passive: true });

        let observer = null;
        if (typeof ResizeObserver === 'function') {
            observer = new ResizeObserver(soon);
            observer.observe(viewport);
            for (const child of viewport.children) observer.observe(child);
        } else {
            window.addEventListener('resize', soon);
        }

        measure();

        cleanup(() => {
            if (frame) cancelAnimationFrame(frame);
            viewport.removeEventListener('scroll', soon);
            observer?.disconnect();
            window.removeEventListener('resize', soon);
        });
    });
}
