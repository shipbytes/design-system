/*
 * dsPullRefresh: pull down at the top to refresh, behind ds::pull-refresh.
 *
 *   x-data="dsPullRefresh({ threshold: 72, timeout: 10000, updated: 'Updated' })"
 *
 * The markup is the view's: the root data-ds-pull (the scrolling box, or a
 * box the page scrolls), an indicator holding a ring data-ds-pull-ring, the
 * content data-ds-pull-content, the Refresh button data-ds-pull-button and
 * a status region.
 *
 *   - Touch only: a finger pulling down while the box (or the page) is at
 *     its top brings the content down after it, against a resistance that
 *     grows, and fills the ring on the way to `threshold`. Let go past it to
 *     refresh. touch-action is pan-x pan-down at the top, so the browser
 *     scrolls a push up and leaves the pull to us, and pan-x pan-y anywhere
 *     else.
 *   - The button, for every other pointer and the keyboard, refreshes too.
 *
 * Refreshing: the root is data-refreshing and aria-busy, and `refresh` is
 * dispatched from it, bubbling and cancelable. A host that answers calls
 * preventDefault() (x-on:refresh.prevent) and, when it is done, dispatches
 * `refreshed` on the root, or re-renders it, which a morph does by dropping
 * the attribute. Not answered, the button does what it does without the
 * script: follows its link, or submits its form. `timeout` ends a refresh
 * no one ends. The end is said in the status region.
 */
import { drag } from '../shared/drag.js';

const HOLD = 52;

export function pullRefresh(Alpine) {
    Alpine.data('dsPullRefresh', (options = {}) => {
        let control = null;

        return {
            init() {
                control = wire(this.$el, options);
            },

            destroy() {
                control?.teardown();
            },
        };
    });
}

function wire(root, options) {
    const content = root.querySelector('[data-ds-pull-content]');
    const indicator = root.querySelector('[data-ds-pull-indicator]');
    const ring = root.querySelector('[data-ds-pull-ring]');
    const status = root.querySelector('[data-ds-pull-status]');
    const threshold = options.threshold ?? 72;
    let timer = 0;
    let frame = 0;
    let pulled = 0;
    let quiet = false;

    const scroller = () => (root.scrollHeight > root.clientHeight && getComputedStyle(root).overflowY !== 'visible' ? root : document.scrollingElement);
    const atTop = () => scroller().scrollTop <= 0;
    const refreshing = () => root.hasAttribute('data-refreshing');
    // Comes down quickly, then ever slower, and never past twice the threshold.
    const resist = (dy) => threshold * 2 * (1 - Math.exp(-Math.max(0, dy) / (threshold * 2)));

    function draw(y) {
        pulled = y;
        content.style.transform = y ? `translateY(${y}px)` : '';
        indicator.style.transform = y ? `translateY(${y - HOLD}px)` : '';
        ring?.style.setProperty('--ds-progress', String(Math.min(100, Math.round((y / threshold) * 100))));
    }

    function mark(on) {
        quiet = true;
        root.toggleAttribute('data-refreshing', on);
        root.setAttribute('aria-busy', String(on));
        quiet = false;
    }

    function start() {
        if (refreshing()) return;
        mark(true);
        draw(HOLD);
        status.textContent = '';
        clearTimeout(timer);
        if (options.timeout) timer = setTimeout(done, options.timeout);
        const answered = !root.dispatchEvent(new CustomEvent('refresh', { bubbles: true, cancelable: true }));
        if (answered) return;
        const button = root.querySelector('[data-ds-pull-button]');
        if (button?.href) location.assign(button.href);
        else if (button?.form) button.form.requestSubmit(button);
    }

    function done() {
        clearTimeout(timer);
        const was = refreshing() || pulled || root.getAttribute('aria-busy') === 'true';
        mark(false);
        draw(0);
        if (was) status.textContent = options.updated ?? 'Updated';
        scrolled();
    }

    function scrolled() {
        root.style.touchAction = atTop() ? 'pan-x pan-down' : '';
    }

    const dragging = drag(root, {
        axis: 'y',
        slop: 4,
        press(event) {
            return event.pointerType === 'touch' && !refreshing() && atTop() ? root : null;
        },
        start(state) {
            if (state.dy <= 0) return false;
            content.setAttribute('data-dragging', '');
            indicator.setAttribute('data-dragging', '');
            root.setAttribute('data-pulling', '');
        },
        move(state) {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => draw(resist(state.dy)));
        },
        end(state) {
            settle();
            if (resist(state.dy) >= threshold) start();
            else draw(0);
        },
        cancel() {
            settle();
            draw(0);
        },
    });

    function settle() {
        cancelAnimationFrame(frame);
        content.removeAttribute('data-dragging');
        indicator.removeAttribute('data-dragging');
        root.removeAttribute('data-pulling');
    }

    function onClick(event) {
        if (!event.target.closest('[data-ds-pull-button]')) return;
        event.preventDefault();
        start();
    }

    // A re-render that drops data-refreshing ends the refresh; one that
    // brings it starts showing it.
    const watch = new MutationObserver(() => {
        if (quiet) return;
        scrolled();
        if (refreshing() && !pulled) draw(HOLD);
        else if (!refreshing() && pulled) done();
    });

    const onDone = () => done();
    root.addEventListener('click', onClick);
    root.addEventListener('refreshed', onDone);
    root.addEventListener('scroll', scrolled, { passive: true });
    addEventListener('scroll', scrolled, { passive: true });
    watch.observe(root, { attributes: true, attributeFilter: ['data-refreshing'] });
    scrolled();
    if (refreshing()) draw(HOLD);

    return {
        teardown() {
            clearTimeout(timer);
            cancelAnimationFrame(frame);
            dragging.stop();
            watch.disconnect();
            root.removeEventListener('click', onClick);
            root.removeEventListener('refreshed', onDone);
            root.removeEventListener('scroll', scrolled);
            removeEventListener('scroll', scrolled);
        },
    };
}
