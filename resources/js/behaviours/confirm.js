/*
 * dsConfirm: a destructive button that asks, in place, before it acts.
 *
 *   x-data="dsConfirm({ timeout: 5000, loading: true })"
 *
 * Behind ds::confirm-button. The view renders both faces: the trigger
 * (data-ds-confirm-trigger), shown at rest, and the question
 * (data-ds-confirm-ask) holding Cancel (data-ds-confirm-cancel) and the
 * confirm control (data-ds-confirm-confirm), which carries the host's
 * attributes, its wire:click, its type=submit. The view binds the faces to
 * `dsAsking` and the confirm control's busy look to `dsBusy`.
 *
 *   - a press on the trigger asks: the question shows, focus moves to Cancel,
 *     the safe choice, and the polite live region the view binds says the
 *     question
 *   - Cancel, Escape, or `timeout` ms with neither the pointer over it nor
 *     focus in it, puts it back; Cancel and Escape give focus to the trigger,
 *     and every one of them dispatches `cancel` from the confirm control
 *   - a press on the confirm control acts. It dispatches `confirm`, then the
 *     click goes on to the control's own listeners and the form, untouched.
 *     With `loading`, the control then shows its spinner and refuses every
 *     later press, for a form that is submitting or a row about to go;
 *     without, the button is put back at rest once the click is done
 *
 * Until it asks, the confirm control is not on screen, so nothing the host
 * hung on it can run from one press: without this script, or without Alpine,
 * the destructive action is unreachable rather than one click away.
 *
 * The scope exposes `dsAsking` and `dsBusy` and nothing else.
 */
import { swallow, announce, onRestore, own, focusWhenShown } from '../shared/confirm.js';

const SCOPE = '[data-ds-confirm]';

export function inlineConfirm(Alpine) {
    Alpine.data('dsConfirm', (options = {}) => {
        const timeout = Number.isFinite(options.timeout) ? options.timeout : 5000;
        const loading = options.loading === true;
        let parts = null;

        return {
            dsAsking: false,
            dsBusy: false,

            init() {
                const root = this.$el;
                const trigger = own(root, '[data-ds-confirm-trigger]', SCOPE);
                const cancel = own(root, '[data-ds-confirm-cancel]', SCOPE);
                const control = own(root, '[data-ds-confirm-confirm]', SCOPE);

                let timer = null;
                let hovered = false;
                let focused = false;

                const stopTimer = () => {
                    clearTimeout(timer);
                    timer = null;
                };

                // Restarted in full once both the pointer and focus have
                // left, not resumed: whoever moved away may have been reading.
                const startTimer = () => {
                    stopTimer();
                    if (timeout <= 0 || !this.dsAsking || this.dsBusy || hovered || focused) return;
                    timer = setTimeout(() => putBack(false), timeout);
                };

                const putBack = (toTrigger) => {
                    if (!this.dsAsking || this.dsBusy) return;
                    stopTimer();
                    this.dsAsking = false;
                    announce(control, 'cancel');
                    if (toTrigger || root.contains(document.activeElement)) focusWhenShown(trigger);
                };

                const ask = () => {
                    this.dsAsking = true;
                    focusWhenShown(cancel);
                    this.$nextTick(startTimer);
                };

                const onClick = (event) => {
                    if (control?.contains(event.target)) {
                        if (this.dsBusy || !this.dsAsking) return swallow(event);
                        stopTimer();
                        announce(control, 'confirm');
                        if (loading) {
                            this.dsBusy = true;
                            return;
                        }
                        // After the click has reached the form and the
                        // host's listeners: hiding the control first would
                        // not stop a submit, but it is not worth finding out
                        // which browsers agree.
                        setTimeout(() => {
                            const had = root.contains(document.activeElement);
                            this.dsAsking = false;
                            if (had) focusWhenShown(trigger);
                        });
                        return;
                    }
                    if (cancel?.contains(event.target)) {
                        putBack(true);
                        return;
                    }
                    if (trigger?.contains(event.target) && !this.dsAsking) ask();
                };

                const onKeyDown = (event) => {
                    if (event.key !== 'Escape' || !this.dsAsking || this.dsBusy) return;
                    // Only when it is consumed, so a dialog around this one
                    // still hears an Escape pressed at rest.
                    event.stopPropagation();
                    event.preventDefault();
                    putBack(true);
                };

                const onEnter = () => {
                    hovered = true;
                    stopTimer();
                };
                const onLeave = () => {
                    hovered = false;
                    startTimer();
                };
                const onFocusIn = () => {
                    focused = true;
                    stopTimer();
                };
                const onFocusOut = (event) => {
                    if (event.relatedTarget && root.contains(event.relatedTarget)) return;
                    focused = false;
                    startTimer();
                };

                // Capturing, so a press that must not act yet is stopped
                // before the control's own listeners hear it.
                root.addEventListener('click', onClick, true);
                root.addEventListener('keydown', onKeyDown);
                root.addEventListener('pointerenter', onEnter);
                root.addEventListener('pointerleave', onLeave);
                root.addEventListener('focusin', onFocusIn);
                root.addEventListener('focusout', onFocusOut);
                const unRestore = onRestore(() => {
                    stopTimer();
                    this.dsBusy = false;
                    this.dsAsking = false;
                });

                parts = () => {
                    stopTimer();
                    unRestore();
                    root.removeEventListener('click', onClick, true);
                    root.removeEventListener('keydown', onKeyDown);
                    root.removeEventListener('pointerenter', onEnter);
                    root.removeEventListener('pointerleave', onLeave);
                    root.removeEventListener('focusin', onFocusIn);
                    root.removeEventListener('focusout', onFocusOut);
                };
            },

            destroy() {
                parts?.();
            },
        };
    });
}
