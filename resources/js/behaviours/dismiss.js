/*
 * dsDismiss: a press puts it away, and with a key it stays away.
 *
 *   x-data="dsDismiss({ key: 'maintenance-2026-11' })"
 *
 * Behind ds::announcement-bar. Any button inside marked data-ds-dismiss
 * dismisses: the bar's own close control, or a "Got it" in its action slot.
 * Dismissing sets `dsDismissed`, which the view binds to the attribute its
 * collapse reads, and dispatches `dismiss` from the root, bubbling, so a host
 * that also remembers it on the server hears it.
 *
 * With `key`, the dismissal is written to localStorage under that key (the
 * value is "1"), and a bar whose key is already there starts dismissed. The
 * view also puts a small inline check before the bar's content that hides it
 * before the first paint; this repeats the check for a page where that
 * script did not run (a Content Security Policy without the nonce), so the
 * bar is still put away, if a moment late. Storage that throws (a private
 * window, blocked site data) dismisses for this page only.
 *
 * The scope exposes `dsDismissed` and nothing else. The view reveals its
 * close control only when this has started, so a page without the script
 * shows no control that would do nothing.
 */

export function dismiss(Alpine) {
    Alpine.data('dsDismiss', (options = {}) => {
        const key = typeof options.key === 'string' && options.key ? options.key : null;
        let onClick = null;

        return {
            dsDismissed: false,

            init() {
                if (key && stored(key)) this.dsDismissed = true;

                const root = this.$el;
                onClick = (event) => {
                    const control = event.target.closest('[data-ds-dismiss]');
                    // A dismissible thing nested inside belongs to its own scope.
                    if (!control || control.closest('[data-ds-dismissible]') !== root || this.dsDismissed) return;
                    this.dsDismissed = true;
                    if (key) remember(key);
                    root.dispatchEvent(new CustomEvent('dismiss', { bubbles: true }));
                };
                root.addEventListener('click', onClick);
            },

            destroy() {
                if (onClick) this.$el.removeEventListener('click', onClick);
            },
        };
    });
}

function stored(key) {
    try {
        return localStorage.getItem(key) !== null;
    } catch {
        return false;
    }
}

function remember(key) {
    try {
        localStorage.setItem(key, '1');
    } catch {
        // Still dismissed on this page; only the memory of it is lost.
    }
}
