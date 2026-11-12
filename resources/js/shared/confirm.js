/*
 * What the three ways to guard a destructive action share: dsConfirm (the
 * inline "Are you sure?"), dsConfirmDialog (the confirm popover) and dsHold
 * (hold to confirm). Registers nothing.
 *
 * The action itself is always the host's: a form the confirm control submits,
 * a wire:click or @click on it, or the `confirm` event. So a press that must
 * not act yet is stopped where all three of those would hear it, and a press
 * that may act is let through untouched.
 */

/*
 * Stops a click before the control's own listeners and the form hear it.
 * Called from a listener CAPTURING on an ancestor: propagation stopped there
 * never reaches the target phase, where Alpine's @click and Livewire's
 * wire:click listen, and the default (submitting) is cancelled.
 */
export function swallow(event) {
    event.preventDefault();
    event.stopImmediatePropagation();
}

/*
 * `confirm` and `cancel`, from the control the host's attributes landed on,
 * bubbling: a listener written on the component tag sits on that control, so
 * it hears its own element's event.
 */
export function announce(control, name) {
    control?.dispatchEvent(new CustomEvent(name, { bubbles: true }));
}

/*
 * A page restored from the back-forward cache comes back exactly as it was
 * left, mid-submit included: a spinner that never stops, a question still
 * asked. `fn` puts the component back at rest. Returns the teardown.
 */
export function onRestore(fn) {
    const listener = (event) => {
        if (event.persisted) fn();
    };
    window.addEventListener('pageshow', listener);
    return () => window.removeEventListener('pageshow', listener);
}

/*
 * The first element under `root` matching `selector` that belongs to this
 * component rather than to one of the same kind nested inside it.
 */
export function own(root, selector, scope) {
    return [...root.querySelectorAll(selector)].find((el) => el.closest(scope) === root) ?? null;
}

/*
 * Focus an element x-show is about to reveal. Alpine shows it in a later
 * microtask than the state change, sometimes later than $nextTick, and a
 * hidden element refuses focus without a word. So: now if it is rendered,
 * else on the next frames, for a few of them, but only while focus is where
 * it was at the call (or fell to the body when x-show hid it): if the reader
 * has put it somewhere else in the meantime, it stays there.
 */
export function focusWhenShown(el, tries = 5, from = document.activeElement) {
    if (!el) return;
    if (el.getClientRects().length) {
        el.focus();
        return;
    }
    if (tries > 0) requestAnimationFrame(() => {
        const now = document.activeElement;
        if (!now || now === document.body || now === from) focusWhenShown(el, tries - 1, from);
    });
}
