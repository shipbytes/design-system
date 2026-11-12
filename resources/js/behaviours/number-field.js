/*
 * dsNumberField: the decrease and increase buttons beside a native number input.
 *
 *   x-data="dsNumberField"
 *
 * The input is a real type=number, rendered complete by PHP with its min, max
 * and step, so the arrow keys, typing and what the form posts are the
 * browser's. This adds only what the buttons do: one step per press, clamped by
 * the browser's own stepUp() and stepDown(), repeating while held, and each
 * button disabled at its bound.
 *
 * A step dispatches `input` on the input, and the press dispatches `change`
 * once when it ends, the way a native spinner reports: wire:model and x-model
 * follow every step, and wire:model.change sends one request per press rather
 * than one per repeat.
 *
 * The scope exposes nothing but Alpine's own init and destroy.
 */

// Long enough that a deliberate single press never repeats; the repeat after
// it is quick enough to cross a range of fifty in a few seconds.
const HOLD_DELAY = 400;
const HOLD_EVERY = 75;

export function numberField(Alpine) {
    Alpine.data('dsNumberField', () => {
        let teardown = null;

        return {
            init() {
                teardown = wire(this.$el);
            },

            destroy() {
                teardown?.();
            },
        };
    });
}

function wire(root) {
    const input = root.querySelector('[data-ds-number-input]');
    const buttons = [...root.querySelectorAll('[data-ds-step]')];

    let press = null;
    let moved = false;

    const limit = (name) => {
        const raw = input.getAttribute(name);
        return raw === null || raw === '' || Number.isNaN(Number(raw)) ? null : Number(raw);
    };

    function refresh() {
        const value = input.value === '' ? null : Number(input.value);
        for (const button of buttons) {
            const up = button.dataset.dsStep === 'up';
            const bound = limit(up ? 'max' : 'min');
            button.disabled = input.disabled
                || (value !== null && bound !== null && (up ? value >= bound : value <= bound));
        }
    }

    /** One step; whether the value moved. */
    function step(direction) {
        const before = input.value;

        try {
            if (direction > 0) input.stepUp();
            else input.stepDown();
        } catch {
            // step="any" has no step to take, and the browser throws rather than
            // guess. One whole unit, inside the bounds, is what a person expects.
            const min = limit('min');
            const max = limit('max');
            let next = (input.value === '' ? 0 : Number(input.value)) + direction;
            if (max !== null) next = Math.min(next, max);
            if (min !== null) next = Math.max(next, min);
            input.value = String(next);
        }

        if (input.value === before) return false;

        input.dispatchEvent(new Event('input', { bubbles: true }));
        moved = true;
        return true;
    }

    function end() {
        if (press) {
            clearTimeout(press.delay);
            clearInterval(press.repeat);
            press.listeners.abort();
            press = null;
        }
        if (moved) {
            moved = false;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function onPointerDown(event) {
        const button = event.target.closest('[data-ds-step]');
        if (!button || button.disabled || event.button !== 0) return;

        // Focus stays where it was, so a caret in the field keeps its place,
        // and a long press on a phone selects no text and opens no menu.
        event.preventDefault();
        end();

        const direction = button.dataset.dsStep === 'up' ? 1 : -1;
        step(direction);

        const listeners = new AbortController();
        press = {
            listeners,
            repeat: null,
            delay: setTimeout(() => {
                press.repeat = setInterval(() => {
                    if (!step(direction)) end();
                }, HOLD_EVERY);
            }, HOLD_DELAY),
        };

        // On the window, not the button: a button that reaches its bound is
        // disabled mid-press, and a disabled button hears no pointerup.
        window.addEventListener('pointerup', end, { signal: listeners.signal });
        window.addEventListener('pointercancel', end, { signal: listeners.signal });
        button.addEventListener('pointerleave', end, { signal: listeners.signal });
    }

    function onClick(event) {
        const button = event.target.closest('[data-ds-step]');
        if (!button || button.disabled) return;

        // A pointer's press was handled on pointerdown. A click with no pointer
        // behind it (detail 0) is a screen reader activating the button, which
        // never sends pointerdown: one step, and its change.
        if (event.detail !== 0) return;
        step(button.dataset.dsStep === 'up' ? 1 : -1);
        end();
    }

    function onMouseDown(event) {
        if (event.target.closest('[data-ds-step]')) event.preventDefault();
    }

    function onContextMenu(event) {
        if (event.target.closest('[data-ds-step]')) event.preventDefault();
    }

    const listeners = [
        ['pointerdown', onPointerDown],
        ['mousedown', onMouseDown],
        ['click', onClick],
        ['contextmenu', onContextMenu],
        // Typing and the native arrow keys move the value too, and the buttons
        // have to follow it to their bounds.
        ['input', refresh],
        ['change', refresh],
    ];
    for (const [type, fn] of listeners) root.addEventListener(type, fn);

    refresh();

    return () => {
        end();
        for (const [type, fn] of listeners) root.removeEventListener(type, fn);
    };
}
