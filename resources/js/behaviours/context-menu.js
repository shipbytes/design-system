/*
 * dsContextMenu: a menu opened at a place in a region, behind ds::context-menu.
 *
 *   x-data="dsContextMenu({ native: true })"
 *
 * The markup is the component's, rendered complete by PHP: the region is the
 * root, holding the consumer's content and, last, the panel marked
 * data-ds-context-menu-panel, role=menu, hidden by x-show="dsOpen" and placed
 * by x-ds-anchor at `dsAt`. The arrow keys inside it are the dropdown's,
 * bound in the view from partials/menu.php.
 *
 * It opens:
 *
 *   - on a right-click, at the pointer, with the browser's menu prevented
 *   - on Shift+F10 or the Menu key, below the element that has focus
 *   - on a touch held still for 500ms, at the finger; moving more than 10px
 *     first is a scroll or a drag and cancels it, and the click that follows
 *     the hold is swallowed, so a held link or button is not also pressed
 *
 * and closes on Escape, Tab, a choice, or a press outside it. Focus goes to the
 * first item on open and back to whatever had it on close. A press outside
 * that lands on nothing focusable would leave focus on the body; it is put
 * back too.
 *
 * `native` (default true) leaves the browser's own menu alone over a field,
 * an editable area or a link inside the region: copy, paste, spelling, open in
 * a new tab. False opens this menu there as well.
 *
 * A region inside another region: the inner one takes the event, and the
 * outer sees it handled (defaultPrevented) and stands down.
 *
 * The scope exposes `dsOpen`, `dsAt` (the box the panel hangs off, from the
 * region's corner) and `dsTarget` (the element that was pressed, for an item
 * that has to know which row it is acting on).
 */

const NATIVE = 'input, textarea, select, [contenteditable]:not([contenteditable="false"]), a[href]';

const HOLD_MS = 500;
const SLOP_PX = 10;

export function contextMenu(Alpine) {
    Alpine.data('dsContextMenu', (options = {}) => {
        const native = options.native !== false;
        let wired = null;

        return {
            dsOpen: false,
            dsAt: { x: 0, y: 0, width: 0, height: 0, offset: 2 },
            dsTarget: null,

            init() {
                wired = wire(this.$el, {
                    native,
                    isOpen: () => this.dsOpen,
                    open: (at, target) => {
                        this.dsAt = at;
                        this.dsTarget = target;
                        this.dsOpen = true;
                    },
                    close: () => {
                        this.dsOpen = false;
                    },
                    later: (fn) => this.$nextTick(fn),
                });
                this.$watch('dsOpen', (open) => wired.changed(open));
            },

            destroy() {
                wired?.teardown();
            },
        };
    });
}

function wire(root, api) {
    const panel = [...root.querySelectorAll('[data-ds-context-menu-panel]')]
        .find((el) => el.closest('[data-ds-context-menu]') === root) ?? null;

    let returnTo = null;
    let keyed = 0;
    let hold = null;
    let swallowClick = false;
    let pressedOutside = false;

    const inPanel = (node) => Boolean(panel && node instanceof Node && panel.contains(node));
    const leftNative = (node) => {
        if (!api.native || !(node instanceof Element)) return false;
        const control = node.closest(NATIVE);
        return Boolean(control && root.contains(control) && !inPanel(control));
    };

    function at(x, y, width = 0, height = 0, offset = 2) {
        const r = root.getBoundingClientRect();
        return { x: x - r.left, y: y - r.top, width, height, offset };
    }

    function open(box, target) {
        if (!api.isOpen()) returnTo = document.activeElement;
        api.open(box, target);
        // Every time, not only on the first open: a second right-click in the
        // region has already taken focus out of the menu with its press.
        api.later(() => {
            const first = panel?.querySelector('[role="menuitem"]:not([aria-disabled="true"])') ?? panel;
            first?.focus({ preventScroll: true });
        });
    }

    function close() {
        if (api.isOpen()) api.close();
    }

    function restore() {
        const to = returnTo;
        returnTo = null;
        if (to && to !== document.body && to.isConnected) to.focus({ preventScroll: true });
    }

    function onContextMenu(event) {
        if (inPanel(event.target)) {
            // No browser menu over this one.
            event.preventDefault();
            return;
        }
        if (event.defaultPrevented || leftNative(event.target)) return;
        event.preventDefault();
        cancelHold();
        // The browser may follow Shift+F10 with a contextmenu of its own,
        // already answered by the keydown.
        if (Date.now() - keyed < 500) return;
        open(at(event.clientX, event.clientY), event.target);
    }

    function onKeyDown(event) {
        if (inPanel(event.target)) {
            if (event.key === 'Escape') {
                event.stopPropagation();
                close();
            } else if (event.key === 'Tab') {
                // Back to where the reader was, not on to whatever follows
                // the panel in the source.
                event.preventDefault();
                close();
            }
            return;
        }
        const menuKey = event.key === 'ContextMenu' || (event.key === 'F10' && event.shiftKey);
        if (!menuKey || event.defaultPrevented || leftNative(event.target)) return;
        event.preventDefault();
        keyed = Date.now();
        const r = event.target.getBoundingClientRect();
        open(at(r.left, r.top, r.width, r.height, 4), event.target);
    }

    function cancelHold() {
        if (hold) clearTimeout(hold.timer);
        hold = null;
    }

    function onPointerDown(event) {
        swallowClick = false;
        if (event.pointerType !== 'touch' || inPanel(event.target) || leftNative(event.target)) return;
        cancelHold();
        const { clientX: x, clientY: y, target } = event;
        hold = {
            x,
            y,
            timer: setTimeout(() => {
                hold = null;
                swallowClick = true;
                open(at(x, y), target);
            }, HOLD_MS),
        };
    }

    function onPointerMove(event) {
        if (hold && Math.hypot(event.clientX - hold.x, event.clientY - hold.y) > SLOP_PX) cancelHold();
    }

    function onClick(event) {
        if (swallowClick && !inPanel(event.target)) {
            event.preventDefault();
            event.stopPropagation();
            swallowClick = false;
            return;
        }
        // A choice closes the menu. Not prevented: a link still navigates and
        // a submit still submits.
        if (inPanel(event.target) && event.target.closest('[role="menuitem"]:not([aria-disabled="true"])')) close();
    }

    function onOutside(event) {
        if (inPanel(event.target)) return;
        // A second right-click inside the region moves the menu; the
        // contextmenu that follows does that.
        if (event.button === 2 && root.contains(event.target)) return;
        // Held until the timeout below: Alpine runs the watcher that calls
        // changed(false) later than this line.
        pressedOutside = true;
        close();
        // After the press has moved focus wherever it was going.
        setTimeout(() => {
            pressedOutside = false;
            if (!document.activeElement || document.activeElement === document.body) restore();
            else returnTo = null;
        });
    }

    function changed(open) {
        if (open) {
            document.addEventListener('pointerdown', onOutside, true);
            return;
        }
        document.removeEventListener('pointerdown', onOutside, true);
        if (pressedOutside) return;
        if (inPanel(document.activeElement) || document.activeElement === document.body) restore();
    }

    root.addEventListener('contextmenu', onContextMenu);
    root.addEventListener('keydown', onKeyDown);
    root.addEventListener('pointerdown', onPointerDown);
    root.addEventListener('pointermove', onPointerMove, { passive: true });
    root.addEventListener('pointerup', cancelHold);
    root.addEventListener('pointercancel', cancelHold);
    root.addEventListener('click', onClick, true);

    return {
        changed,

        teardown() {
            cancelHold();
            document.removeEventListener('pointerdown', onOutside, true);
            root.removeEventListener('contextmenu', onContextMenu);
            root.removeEventListener('keydown', onKeyDown);
            root.removeEventListener('pointerdown', onPointerDown);
            root.removeEventListener('pointermove', onPointerMove);
            root.removeEventListener('pointerup', cancelHold);
            root.removeEventListener('pointercancel', cancelHold);
            root.removeEventListener('click', onClick, true);
        },
    };
}
