/*
 * dsPopover: a non-modal dialog opened by a trigger, behind ds::popover.
 *
 *   x-data="dsPopover({ dismissible: true })"
 *
 * The markup is the component's, rendered complete by PHP: the consumer's
 * trigger inside a `contents` wrapper marked data-ds-popover-trigger, and the
 * panel marked data-ds-popover-panel, role=dialog, its id and its name set,
 * hidden by x-show="dsOpen" and placed by x-ds-anchor. The first button,
 * link or field in the trigger slot is the control.
 *
 * The contract, after the ARIA dialog pattern without the modality:
 *
 *   - the control gets aria-expanded, kept in step, and aria-controls; a
 *     panel given no name is named by the control
 *   - a press on the trigger opens and closes it
 *   - opening moves focus to the first focusable thing in the panel, or to
 *     the panel itself; closing gives it back to the control, if it was in
 *     the panel
 *   - Escape closes, a press outside closes, and focus moving outside
 *     closes, Tab included: nothing is trapped. `dismissible: false` turns
 *     all three off, for a panel the host closes itself
 *
 * "Outside" is outside this component's root, and a popover opened from
 * inside another is inside its root, so nesting works: a press in the child
 * leaves the parent open, and Escape closes the innermost one only. A
 * combobox or select inside behaves the same way, its list being inside too.
 *
 * Two-way binding goes through `dsOpen`, as dsValue does elsewhere: the view
 * puts x-modelable="dsOpen" beside the host's x-model when the host owns the
 * state. A popover already open when the page starts does not take focus.
 * The scope exposes `dsOpen` and nothing else.
 */

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"]), [contenteditable="true"]';

// Which element in the trigger slot is the control: a ds::button, a link, or
// a field, possibly inside a tooltip wrapper.
const CONTROL = 'button, a[href], input, select, textarea, [role="button"], [tabindex]';

export function popover(Alpine) {
    Alpine.data('dsPopover', (options = {}) => {
        const dismissible = options.dismissible !== false;
        let control = null;

        return {
            dsOpen: false,

            init() {
                control = wire(this.$el, {
                    dismissible,
                    isOpen: () => this.dsOpen,
                    setOpen: (open) => {
                        this.dsOpen = open;
                    },
                    later: (fn) => this.$nextTick(fn),
                });
                control.changed(this.dsOpen);
                this.$watch('dsOpen', (open) => control.changed(open));
            },

            destroy() {
                control?.teardown();
            },
        };
    });
}

function wire(root, api) {
    const own = (selector) => [...root.querySelectorAll(selector)].find((el) => el.closest('[data-ds-popover]') === root) ?? null;
    const trigger = own('[data-ds-popover-trigger]');
    const panel = own('[data-ds-popover-panel]');
    const control = trigger?.querySelector(CONTROL) ?? null;

    if (control && panel) {
        control.setAttribute('aria-controls', panel.id);
        if (!panel.hasAttribute('aria-label') && !panel.hasAttribute('aria-labelledby')) {
            if (!control.id) control.id = `${panel.id}-trigger`;
            panel.setAttribute('aria-labelledby', control.id);
        }
    }

    // Opened by the host before the first frame is the page's starting
    // state, not something the reader did: focus stays where it is.
    let ready = false;
    const frame = requestAnimationFrame(() => {
        ready = true;
    });

    // Whether closing should hand focus back to the control. A press outside
    // or a Tab away has already put focus where the reader wanted it.
    let giveBack = true;

    function close(toControl) {
        giveBack = toControl;
        api.setOpen(false);
    }

    function onClick(event) {
        if (!trigger || !trigger.contains(event.target)) return;
        giveBack = true;
        api.setOpen(!api.isOpen());
    }

    function onKeyDown(event) {
        if (event.key !== 'Escape' || !api.dismissible || !api.isOpen()) return;
        // The innermost open popover takes Escape; its parent never hears it.
        event.stopPropagation();
        close(true);
    }

    function onFocusOut(event) {
        if (!api.dismissible || !api.isOpen()) return;
        const to = event.relatedTarget;
        // No relatedTarget is the window losing focus, or a press on nothing
        // focusable, which the pointer listener answers. Neither is leaving.
        if (!to || root.contains(to)) return;
        close(false);
    }

    function onPointerDown(event) {
        if (root.contains(event.target)) return;
        close(false);
    }

    function focusIn() {
        if (!panel) return;
        const visible = (el) => el.getClientRects().length > 0;
        const first = panel.querySelector('[autofocus]')
            ?? [...panel.querySelectorAll(FOCUSABLE)].find(visible)
            ?? panel;
        first.focus();
    }

    function changed(open) {
        control?.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (open) {
            // Captured, so a handler that stops propagation on its own
            // element cannot keep this one from hearing a press outside.
            if (api.dismissible) document.addEventListener('pointerdown', onPointerDown, true);
            if (ready) api.later(focusIn);
            return;
        }

        document.removeEventListener('pointerdown', onPointerDown, true);
        if (giveBack && panel?.contains(document.activeElement)) control?.focus();
        giveBack = true;
    }

    root.addEventListener('click', onClick);
    root.addEventListener('keydown', onKeyDown);
    root.addEventListener('focusout', onFocusOut);

    return {
        changed,

        teardown() {
            cancelAnimationFrame(frame);
            document.removeEventListener('pointerdown', onPointerDown, true);
            root.removeEventListener('click', onClick);
            root.removeEventListener('keydown', onKeyDown);
            root.removeEventListener('focusout', onFocusOut);
        },
    };
}
