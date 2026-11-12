/*
 * dsClamp: a box held to a few lines, and the button that lets it out.
 * Behind ds::expandable-text, and ds::code-block's `expandable`.
 *
 *   x-data="dsClamp({ lines: 3, more: 'Read more', less: 'Show less' })"
 *
 * The view renders the box marked data-ds-clamp-body and a button marked
 * data-ds-clamp-toggle, `hidden`, with aria-expanded="false" and
 * aria-controls naming the box; the words go in its data-ds-clamp-label.
 *
 * With `lines`, this clamps the box itself: a max-height of that many lines
 * as inline style, so a page without the script shows all of the text, which
 * is the safe way to fail. Without `lines` the view caps the box in CSS (the
 * code block's scroll), and this only lifts the cap.
 *
 * The button is shown only when the content really is taller than the limit,
 * measured, and measured again when the box changes width, its content
 * changes or the fonts arrive. The state is written to the root as
 * data-ds-state, for the view's classes to read:
 *
 *   fits       nothing to hide; no button, no fade
 *   clamped    held to the limit; the button says `more`
 *   expanded   let out; the button says `less`
 *
 * Pressing moves no focus. Closing again can move the button a long way up
 * the page, so once the box has closed the button is scrolled back into view
 * if it went out of it. The height animates by the view's own transition
 * class, which is motion-safe, so with reduced motion it simply changes.
 *
 * The scope exposes nothing but Alpine's own init and destroy.
 */

export function clamp(Alpine) {
    Alpine.data('dsClamp', (options = {}) => {
        let teardown = null;

        return {
            init() {
                teardown = hold(this.$el, options);
            },

            destroy() {
                teardown?.();
            },
        };
    });
}

function hold(root, options) {
    const own = (selector) => [...root.querySelectorAll(selector)].find((el) => el.closest('[data-ds-clamp], [data-ds-code]') === root) ?? null;
    const body = own('[data-ds-clamp-body]');
    const toggle = own('[data-ds-clamp-toggle]');
    if (!body || !toggle) return () => {};

    const words = toggle.querySelector('[data-ds-clamp-label]') ?? toggle;
    const lines = Number.isInteger(options.lines) && options.lines > 0 ? options.lines : 0;
    const more = String(options.more ?? 'Show more');
    const less = String(options.less ?? 'Show less');

    let expanded = false;
    let overflowing = false;
    let frame = 0;

    // The height of `lines` lines and the box's own padding, in px.
    function limit() {
        const style = getComputedStyle(body);
        let line = parseFloat(style.lineHeight);
        if (Number.isNaN(line)) line = parseFloat(style.fontSize) * 1.2;
        return lines * line + parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
    }

    function measure() {
        if (lines) {
            overflowing = body.scrollHeight > limit() + 1;
        } else if (!expanded) {
            // The cap is the view's CSS, so the box at rest is the measure.
            // While let out there is nothing to compare, and the button stays.
            overflowing = body.scrollHeight > body.clientHeight + 1;
        }
        draw();
    }

    function draw() {
        if (!overflowing) expanded = false;
        root.dataset.dsState = !overflowing ? 'fits' : expanded ? 'expanded' : 'clamped';
        toggle.hidden = !overflowing;
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        words.textContent = expanded ? less : more;

        if (!lines) return;
        // Px both ways, so the transition has two numbers to run between.
        // Nothing to hide is no limit at all: the box is its own height.
        body.style.maxHeight = !overflowing ? '' : `${expanded ? body.scrollHeight : limit()}px`;
    }

    function backIntoView() {
        const box = toggle.getBoundingClientRect();
        if (box.bottom < 0 || box.top > window.innerHeight) toggle.scrollIntoView({ block: 'nearest' });
    }

    function onClick() {
        if (!overflowing) return;
        expanded = !expanded;
        draw();
        if (expanded) return;

        const duration = parseFloat(getComputedStyle(body).transitionDuration) || 0;
        if (!lines || duration === 0) {
            requestAnimationFrame(backIntoView);
            return;
        }
        // Once the box has finished closing, whichever comes first: its own
        // transitionend, or a moment after the time it should take.
        let done = false;
        const finish = () => {
            if (done) return;
            done = true;
            body.removeEventListener('transitionend', finish);
            backIntoView();
        };
        body.addEventListener('transitionend', finish);
        setTimeout(finish, duration * 1000 + 100);
    }

    // Once per frame, however many observers fire in it.
    const later = () => {
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(measure);
    };

    toggle.addEventListener('click', onClick);

    const resized = typeof ResizeObserver === 'function' ? new ResizeObserver(later) : null;
    resized?.observe(root);
    // Without ResizeObserver, the window's own resize is the next best thing.
    if (!resized) window.addEventListener('resize', later);

    // A Livewire morph or a host's script changing the text.
    const changed = new MutationObserver(later);
    changed.observe(body, { childList: true, subtree: true, characterData: true });

    document.fonts?.ready?.then(later);
    measure();

    return () => {
        cancelAnimationFrame(frame);
        toggle.removeEventListener('click', onClick);
        resized?.disconnect();
        window.removeEventListener('resize', later);
        changed.disconnect();
    };
}
