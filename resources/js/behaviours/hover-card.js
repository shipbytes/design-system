/*
 * dsHoverCard: a preview that opens on hover or keyboard focus, behind
 * ds::hover-card.
 *
 *   x-data="dsHoverCard"
 *
 * The markup is the component's, rendered complete by PHP: the consumer's
 * trigger (a link to a person, a record) inside a `contents` wrapper marked
 * data-ds-hover-card-trigger, and the card marked data-ds-hover-card-panel,
 * hidden by x-show="dsOpen" and placed by x-ds-anchor.
 *
 *   - a mouse or pen resting on the trigger for OPEN_DELAY opens it; passing
 *     over does not
 *   - keyboard focus on the trigger (`:focus-visible`) opens it after the
 *     same delay, so tabbing along a list does not flash a card at each stop
 *   - leaving the trigger and the card closes it after CLOSE_DELAY, which is
 *     long enough to cross the gap between them; arriving in either cancels it
 *   - touch never opens it: a tap is the trigger's own link or action, and
 *     it runs
 *   - Escape closes it from anywhere (WCAG 1.4.13), and focus leaving the
 *     trigger and the card closes it
 *
 * The trigger keeps its own role, name and semantics: nothing is added to it,
 * no aria-expanded (it expands nothing when activated) and no
 * aria-describedby (the card can hold links, and a description is read as
 * flat text). The card follows the trigger in the page, so Tab from the
 * trigger moves into its links while it is open; it is never a trap and never
 * the only way to reach what it shows. The scope exposes `dsOpen` only.
 */

// Long enough that a pointer crossing a list of names on its way somewhere
// else opens nothing; short enough that resting on one feels deliberate.
const OPEN_DELAY = 500;

// The time to travel from the trigger, across the gap, into the card.
const CLOSE_DELAY = 300;

export function hoverCard(Alpine) {
    Alpine.data('dsHoverCard', () => {
        let control = null;

        return {
            dsOpen: false,

            init() {
                control = wire(this.$el, {
                    isOpen: () => this.dsOpen,
                    setOpen: (open) => {
                        this.dsOpen = open;
                    },
                });
                this.$watch('dsOpen', (open) => control.changed(open));
            },

            destroy() {
                control?.teardown();
            },
        };
    });
}

function wire(root, api) {
    const own = (selector) => [...root.querySelectorAll(selector)].find((el) => el.closest('[data-ds-hover-card]') === root) ?? null;
    const trigger = own('[data-ds-hover-card-trigger]');
    const panel = own('[data-ds-hover-card-panel]');

    let openTimer = 0;
    let closeTimer = 0;

    const inTrigger = (node) => Boolean(node && trigger?.contains(node));
    const inPanel = (node) => Boolean(node && panel?.contains(node));
    const within = (node) => inTrigger(node) || inPanel(node);

    function cancel() {
        clearTimeout(openTimer);
        clearTimeout(closeTimer);
        openTimer = 0;
        closeTimer = 0;
    }

    function openSoon() {
        clearTimeout(closeTimer);
        closeTimer = 0;
        if (api.isOpen() || openTimer) return;
        openTimer = setTimeout(() => {
            openTimer = 0;
            api.setOpen(true);
        }, OPEN_DELAY);
    }

    function closeSoon() {
        clearTimeout(openTimer);
        openTimer = 0;
        if (!api.isOpen() || closeTimer) return;
        closeTimer = setTimeout(() => {
            closeTimer = 0;
            // The reader tabbed into the card and then moved the mouse away:
            // the card is where their focus is, so it stays.
            if (inPanel(document.activeElement)) return;
            api.setOpen(false);
        }, CLOSE_DELAY);
    }

    function closeNow() {
        cancel();
        api.setOpen(false);
    }

    function onPointerOver(event) {
        if (event.pointerType === 'touch' || !within(event.target)) return;
        if (inTrigger(event.target)) openSoon();
        else {
            clearTimeout(closeTimer);
            closeTimer = 0;
        }
    }

    function onPointerOut(event) {
        if (event.pointerType === 'touch' || within(event.relatedTarget)) return;
        closeSoon();
    }

    function onPointerDown(event) {
        // Pressing the trigger is using it, not previewing it.
        if (inTrigger(event.target)) {
            clearTimeout(openTimer);
            openTimer = 0;
        }
    }

    function onFocusIn(event) {
        if (inPanel(event.target)) {
            clearTimeout(closeTimer);
            closeTimer = 0;
            return;
        }
        // :focus-visible is false for focus that came with a tap or a click,
        // so a tap on the trigger never opens the card through this door
        // either.
        if (inTrigger(event.target) && event.target.matches(':focus-visible')) openSoon();
    }

    function onFocusOut(event) {
        if (within(event.relatedTarget)) return;
        // Focus left for somewhere else entirely. If the pointer is resting
        // on the trigger or the card, the hover keeps it; otherwise it goes.
        // :hover matches every ancestor of what is under the pointer in the
        // DOM, and the fixed card is still inside the root there.
        if (root.matches(':hover')) return;
        closeNow();
    }

    function onKeyDown(event) {
        if (event.key !== 'Escape' || (!api.isOpen() && !openTimer)) return;
        const ours = root.contains(document.activeElement);
        closeNow();
        // Taken from the page only when focus is in this card or its trigger:
        // a card shown by hover must not swallow the Escape meant for a
        // dialog the reader is typing in.
        if (ours) event.stopPropagation();
    }

    function changed(open) {
        if (open) document.addEventListener('keydown', onKeyDown, true);
        else if (!openTimer) document.removeEventListener('keydown', onKeyDown, true);
    }

    root.addEventListener('pointerover', onPointerOver);
    root.addEventListener('pointerout', onPointerOut);
    root.addEventListener('pointerdown', onPointerDown);
    root.addEventListener('focusin', onFocusIn);
    root.addEventListener('focusout', onFocusOut);
    // Escape also cancels a card that is about to open, so the listener is
    // on while one is pending as well as while one is shown.
    root.addEventListener('keydown', onKeyDown);

    return {
        changed,

        teardown() {
            cancel();
            document.removeEventListener('keydown', onKeyDown, true);
            root.removeEventListener('pointerover', onPointerOver);
            root.removeEventListener('pointerout', onPointerOut);
            root.removeEventListener('pointerdown', onPointerDown);
            root.removeEventListener('focusin', onFocusIn);
            root.removeEventListener('focusout', onFocusOut);
            root.removeEventListener('keydown', onKeyDown);
        },
    };
}
