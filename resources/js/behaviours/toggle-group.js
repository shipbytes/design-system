/*
 * dsToggleGroup: pressed buttons, one of or any of, behind ds::toggle-group.
 *
 *   x-data="dsToggleGroup({ type: 'single' })"    radio semantics: role=radio, aria-checked
 *   x-data="dsToggleGroup({ type: 'multiple' })"  independent buttons, aria-pressed
 *
 * The markup is the component's, rendered complete by PHP: each toggle a
 * button marked data-ds-toggle with its value in data-ds-value and its state
 * in aria-checked or aria-pressed, holding (when the group has a name) a
 * hidden input marked data-ds-toggle-input that is disabled while the toggle
 * is off. A press flips the attribute and the input's disabled flag, so the
 * form posts what is pressed and the look follows from the attribute.
 *
 * Single is a radio group: a press on the pressed one leaves it pressed, the
 * arrow keys move the choice, and the tab stop is the chosen toggle. Multiple
 * moves focus with the arrows and leaves Space and Enter to press. Inside a
 * ds::toolbar the toolbar's arrow keys move between all its items, these
 * included, and Space or Enter chooses, as in the ARIA toolbar pattern.
 *
 * Every press the reader makes dispatches `input` and `change` from the
 * toggle, bubbling. Two-way binding goes through `dsValue`, as with dsOtp: a
 * string for single ('' for none), an array for multiple. A value the host
 * sets is drawn, not announced.
 */
import { roving } from '../shared/roving.js';

export function toggleGroup(Alpine) {
    Alpine.data('dsToggleGroup', (options = {}) => {
        const single = options.type !== 'multiple';
        let control = null;

        return {
            dsValue: single ? '' : [],

            init() {
                control = wire(this.$el, single, (value) => {
                    this.dsValue = value;
                });
                this.dsValue = control.value();
                this.$watch('dsValue', (value) => control.set(value));
            },

            destroy() {
                control?.teardown();
            },
        };
    });
}

function wire(root, single, report) {
    const state = single ? 'aria-checked' : 'aria-pressed';
    const toggles = () => [...root.querySelectorAll('[data-ds-toggle]')]
        .filter((t) => t.closest('[data-ds-toggle-group]') === root);
    const isOn = (t) => t.getAttribute(state) === 'true';

    function draw(t, on) {
        t.setAttribute(state, on ? 'true' : 'false');
        const input = t.querySelector('[data-ds-toggle-input]');
        // A disabled toggle posts nothing, pressed or not, as a disabled
        // checkbox does.
        if (input) input.disabled = !on || t.disabled;
    }

    function value() {
        const on = toggles().filter(isOn).map((t) => t.dataset.dsValue);
        return single ? on[0] ?? '' : on;
    }

    function announce(t) {
        report(value());
        t.dispatchEvent(new Event('input', { bubbles: true }));
        t.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function choose(t) {
        if (single) {
            if (isOn(t)) return;
            for (const other of toggles()) draw(other, other === t);
        } else {
            draw(t, !isOn(t));
        }
        announce(t);
    }

    function onClick(event) {
        const t = event.target.closest('[data-ds-toggle]');
        if (!t || !toggles().includes(t) || t.disabled) return;
        choose(t);
    }

    root.addEventListener('click', onClick);

    // In a toolbar, its keyboard is the one; a second set of arrow keys on the
    // same items would move focus twice per press.
    const inToolbar = Boolean(root.parentElement?.closest('[data-ds-toolbar]'));
    const unrove = inToolbar ? null : roving(root, {
        items: toggles,
        // A radio group answers all four arrows, as native radios do, and a
        // row of toggles has no second axis to keep them for.
        axis: () => 'both',
        current: () => (single ? toggles().find(isOn) ?? null : null),
        moved: (t) => {
            if (single) choose(t);
        },
    });

    return {
        value,

        set(next) {
            const wanted = new Set((Array.isArray(next) ? next : next === null || next === undefined || next === '' ? [] : [next]).map(String));
            let first = true;
            for (const t of toggles()) {
                // Single holds one at most, however many the host asked for.
                const on = wanted.has(t.dataset.dsValue) && (!single || first);
                if (on) first = false;
                if (isOn(t) !== on || t.querySelector('[data-ds-toggle-input]')) draw(t, on);
            }
        },

        teardown() {
            unrove?.();
            root.removeEventListener('click', onClick);
        },
    };
}
