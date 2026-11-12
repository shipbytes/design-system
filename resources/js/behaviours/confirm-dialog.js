/*
 * dsConfirmDialog: the question inside an anchored confirmation, and the
 * type-to-confirm field when there is one.
 *
 *   x-data="dsConfirmDialog({ text: 'acme-prod', strict: true, loading: false })"
 *
 * Behind ds::confirm-popover. It sits inside a dsPopover panel and drives the
 * popover's `dsOpen` through the scope it inherits; opening, Escape, a press
 * outside, focus leaving and the anchoring are the popover's. The view marks
 * Cancel (data-ds-confirm-cancel), the confirm control
 * (data-ds-confirm-confirm), which carries the host's attributes, and the
 * field (data-ds-confirm-field). It binds the control's disabled state to
 * `dsMatched` and its busy look to `dsBusy`.
 *
 *   - with `text`, the control is disabled until the field holds exactly
 *     that text, letter case included; `strict: false` ignores case and the
 *     spaces around it. Enter in the field confirms when it matches and
 *     does nothing when not: it never submits the form around it some other
 *     way. The field posts nothing, having no name
 *   - a press on the control dispatches `confirm` from it, then the click
 *     goes on to its own listeners and the form. With `loading` the panel
 *     stays open with a spinner and refuses every later press until it
 *     closes; without, it closes and focus goes back to the trigger
 *   - Cancel closes it, focus back to the trigger. However it closes without
 *     a confirmation (Cancel, Escape, a press outside, focus leaving), it
 *     dispatches `cancel` from the control
 *   - every opening starts afresh: the field empty, the control disabled
 *
 * The scope exposes `dsMatched` and `dsBusy` and nothing else.
 */
import { swallow, announce, onRestore, own } from '../shared/confirm.js';

const SCOPE = '[data-ds-confirm-dialog]';

export function confirmDialog(Alpine) {
    Alpine.data('dsConfirmDialog', (options = {}) => {
        const text = typeof options.text === 'string' && options.text !== '' ? options.text : null;
        const strict = options.strict !== false;
        const loading = options.loading === true;
        let teardown = null;

        const matches = (value) => text === null
            || (strict ? value === text : value.trim().toLowerCase() === text.trim().toLowerCase());

        return {
            dsMatched: text === null,
            dsBusy: false,

            init() {
                const root = this.$el;
                const field = own(root, '[data-ds-confirm-field]', SCOPE);
                const cancel = own(root, '[data-ds-confirm-cancel]', SCOPE);
                const control = own(root, '[data-ds-confirm-confirm]', SCOPE);
                let confirmed = false;

                const close = () => {
                    this.dsOpen = false;
                };

                const onClick = (event) => {
                    if (control?.contains(event.target)) {
                        if (this.dsBusy || !this.dsMatched) return swallow(event);
                        confirmed = true;
                        announce(control, 'confirm');
                        if (loading) {
                            this.dsBusy = true;
                            return;
                        }
                        // After the click has reached the form and the host.
                        setTimeout(close);
                        return;
                    }
                    if (cancel?.contains(event.target)) close();
                };

                const onInput = () => {
                    this.dsMatched = matches(field.value);
                };

                const onFieldKey = (event) => {
                    if (event.key !== 'Enter') return;
                    // Implicit submission would press the form's FIRST submit
                    // button, which need not be this one.
                    event.preventDefault();
                    if (this.dsMatched && !this.dsBusy) control?.click();
                };

                root.addEventListener('click', onClick, true);
                field?.addEventListener('input', onInput);
                field?.addEventListener('keydown', onFieldKey);

                this.$watch('dsOpen', (open) => {
                    if (open) {
                        confirmed = false;
                        this.dsBusy = false;
                        if (field) field.value = '';
                        this.dsMatched = text === null;
                        return;
                    }
                    if (!confirmed) announce(control, 'cancel');
                    confirmed = false;
                    this.dsBusy = false;
                });

                const unRestore = onRestore(() => {
                    confirmed = true;
                    this.dsBusy = false;
                    close();
                });

                teardown = () => {
                    unRestore();
                    root.removeEventListener('click', onClick, true);
                    field?.removeEventListener('input', onInput);
                    field?.removeEventListener('keydown', onFieldKey);
                };
            },

            destroy() {
                teardown?.();
            },
        };
    });
}
