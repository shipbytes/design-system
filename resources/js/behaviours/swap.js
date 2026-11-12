/*
 * dsSwap: a button that swaps its face to confirm what it did, "Save" to
 * "Saved", "Follow" to "Following".
 *
 *   x-data="dsSwap({ swapped: false, revert: 2000, click: true, event: 'saved' })"
 *
 * Behind ds::swap-button. The view renders both faces in one cell (the swap
 * recipe, partials/swap.php), the right one showing from the server's
 * `swapped`, and binds data-ds-swapped, aria-hidden and inert to `dsSwapped`,
 * and aria-pressed with it when the button is a toggle. It marks the button
 * data-ds-swap-button, the faces data-ds-swap-face (at rest first), and a
 * role=status region outside the button data-ds-swap-status.
 *
 *   - `click`: a press on the button swaps it. With `revert` 0 a press
 *     swaps it back again, a toggle; otherwise a press while swapped starts
 *     the time again. The press goes on to the form or the link untouched
 *   - `event`: the swap happens when that event reaches the window: a
 *     Livewire dispatch from a save, or anything bubbling up from the page
 *   - `revert`: ms until it swaps back by itself; 0 stays swapped
 *   - every change says the face now showing through the status region,
 *     politely; the first paint says nothing
 *
 * The scope exposes `dsSwapped` and nothing else.
 */
import { timedFlag } from '../shared/flag.js';

export function swap(Alpine) {
    Alpine.data('dsSwap', (options = {}) => {
        const revert = Number.isFinite(options.revert) ? options.revert : 2000;
        const event = typeof options.event === 'string' && options.event !== '' ? options.event : null;
        let teardown = null;

        return {
            dsSwapped: options.swapped === true,

            init() {
                const root = this.$el;
                const button = root.querySelector('[data-ds-swap-button]');
                const faces = root.querySelectorAll('[data-ds-swap-face]');
                const status = root.querySelector('[data-ds-swap-status]');
                const flag = timedFlag((on) => {
                    this.dsSwapped = on;
                }, revert);

                this.$watch('dsSwapped', (on) => {
                    if (status) status.textContent = (faces[on ? 1 : 0]?.textContent ?? '').trim();
                });

                const onClick = (e) => {
                    if (button?.contains(e.target)) flag.set(revert > 0 || !this.dsSwapped);
                };
                const onEvent = () => flag.set(true);

                // Rendered swapped by the server, after a save that reloaded the
                // page: it still puts itself back.
                if (this.dsSwapped && revert > 0) flag.set(true);

                if (options.click === true) root.addEventListener('click', onClick);
                if (event) window.addEventListener(event, onEvent);

                teardown = () => {
                    flag.stop();
                    root.removeEventListener('click', onClick);
                    if (event) window.removeEventListener(event, onEvent);
                };
            },

            destroy() {
                teardown?.();
            },
        };
    });
}
