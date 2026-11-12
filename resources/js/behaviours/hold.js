/*
 * dsHold: confirm by holding the button down, or by pressing it twice.
 *
 *   x-data="dsHold({ duration: 1500, submit: false })"
 *
 * Behind ds::hold-button. The button is marked data-ds-hold and carries the
 * host's attributes. The fill the view draws over it reads the custom
 * property --ds-hold-rest, the part of the button still to fill (100% at
 * rest, 0% when full), which this writes on the button.
 *
 * Holding: a pointer held on the button, or Space or Enter held while it has
 * focus, fills it over `duration` ms. Letting go early, or sliding the
 * pointer off it, rewinds it. A full button is the confirmation.
 *
 * Pressing twice: holding is not something everyone can do. A screen reader
 * in browse mode, voice control and switch access activate a button with a
 * single synthetic click and never hold anything. So a click that no held
 * press of ours began, and a key press let go before the button filled, ARM
 * it: `dsArmed` is set, the view swaps the instruction for "Press again to
 * confirm" and its polite live region says so. The next such press confirms.
 * Focus leaving, or Escape, disarms. A pointer let go early only rewinds:
 * two quick clicks of a mouse must not delete anything.
 *
 * Confirming dispatches `confirm` from the button, bubbling, and then lets
 * one click through to it: the host's @click or wire:click hears that click
 * and nothing before it, and with `submit` it submits the form around it with
 * the button as the submitter. Every other click on the button is stopped
 * before those listeners and before the form. The button is rendered
 * type=button and becomes a submitter only for that click, so a page without
 * this script cannot submit anything from one press.
 *
 * Reduced motion: the fill moves in quarters rather than sweeping, and
 * rewinds at once.
 *
 * The scope exposes `dsArmed` and nothing else.
 */
import { swallow, announce, onRestore } from '../shared/confirm.js';

const KEYS = new Set([' ', 'Enter']);

// The rewind runs at three times the fill's pace: letting go should read as
// "nothing happened", quickly.
const REWIND = 3;

// Steps instead of a sweep, under reduced motion.
const STEPS = 4;

export function hold(Alpine) {
    Alpine.data('dsHold', (options = {}) => {
        const duration = Number.isFinite(options.duration) && options.duration > 0 ? options.duration : 1500;
        const submit = options.submit === true;
        let teardown = null;

        return {
            dsArmed: false,

            init() {
                const root = this.$el;
                const button = root.querySelector('[data-ds-hold]');
                if (!button) return;

                const still = () => typeof matchMedia === 'function' && matchMedia('(prefers-reduced-motion: reduce)').matches;

                let progress = 0;
                let frame = 0;
                let pressing = null; // 'pointer' | 'key' | null
                let pointerId = null;
                let pressed = false; // a press of ours began the click that follows
                let letThrough = false;
                let done = false;

                const paint = () => {
                    const shown = still() ? Math.floor(progress * STEPS) / STEPS : progress;
                    button.style.setProperty('--ds-hold-rest', `${((1 - shown) * 100).toFixed(2)}%`);
                };

                const run = (direction) => {
                    cancelAnimationFrame(frame);
                    let last = performance.now();
                    const step = (now) => {
                        const elapsed = now - last;
                        last = now;
                        progress = Math.min(1, Math.max(0, progress + (direction * elapsed * (direction < 0 ? REWIND : 1)) / duration));
                        paint();
                        if (direction > 0 && progress >= 1) return complete();
                        if (direction < 0 && progress <= 0) return;
                        frame = requestAnimationFrame(step);
                    };
                    frame = requestAnimationFrame(step);
                };

                const rewind = () => {
                    pressing = null;
                    if (still()) {
                        cancelAnimationFrame(frame);
                        progress = 0;
                        paint();
                        return;
                    }
                    run(-1);
                };

                const start = (by) => {
                    if (done) return;
                    pressing = by;
                    run(1);
                };

                const arm = () => {
                    this.dsArmed = true;
                };

                const disarm = () => {
                    this.dsArmed = false;
                };

                // A form that would refuse the submit says so, and nothing
                // is confirmed: a full button over a form that never went is
                // a lie.
                const refused = () => submit && button.form && !button.form.checkValidity();

                const complete = () => {
                    cancelAnimationFrame(frame);
                    pressing = null;
                    disarm();
                    if (refused()) {
                        button.form.reportValidity();
                        progress = 0;
                        paint();
                        return;
                    }
                    progress = 1;
                    paint();
                    announce(button, 'confirm');

                    // Its own task: a click() made while another click on the
                    // button is being dispatched (the second press of a
                    // screen reader's two) is ignored by the browser.
                    setTimeout(() => {
                        letThrough = true;
                        if (submit) button.type = 'submit';
                        try {
                            button.click();
                        } finally {
                            if (submit) button.type = 'button';
                            letThrough = false;
                        }
                    });

                    if (submit) {
                        // Held full while the page goes; a return from the
                        // back-forward cache puts it back.
                        done = true;
                        button.setAttribute('aria-disabled', 'true');
                        return;
                    }
                    setTimeout(rewind, 150);
                };

                const onPointerDown = (event) => {
                    if (event.button !== 0 || !event.isPrimary || pressing) return;
                    pressed = true;
                    pointerId = event.pointerId;
                    try {
                        button.setPointerCapture(pointerId);
                    } catch {
                        // Not every pointer can be captured; the release still arrives.
                    }
                    start('pointer');
                };

                const onPointerMove = (event) => {
                    if (pressing !== 'pointer' || event.pointerId !== pointerId) return;
                    const r = button.getBoundingClientRect();
                    const off = event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom;
                    if (off) rewind();
                };

                const onPointerUp = (event) => {
                    if (event.pointerId !== pointerId) return;
                    pointerId = null;
                    if (pressing === 'pointer') rewind();
                    // The click this release makes, if it makes one, comes
                    // first; one let go off the button makes none, and the
                    // next click must not be taken for this press's.
                    setTimeout(() => {
                        pressed = false;
                    });
                };

                const onKeyDown = (event) => {
                    if (event.key === 'Escape') {
                        if (this.dsArmed || pressing) {
                            event.stopPropagation();
                            disarm();
                            if (pressing) rewind();
                        }
                        return;
                    }
                    if (!KEYS.has(event.key)) return;
                    // Enter would click on keydown, and again on every repeat;
                    // Space on keyup. Both are answered here instead.
                    event.preventDefault();
                    if (event.repeat || pressing || done) return;
                    start('key');
                };

                const onKeyUp = (event) => {
                    if (!KEYS.has(event.key)) return;
                    event.preventDefault();
                    if (pressing !== 'key') return;
                    if (this.dsArmed) return complete();
                    rewind();
                    arm();
                };

                const onClick = (event) => {
                    if (letThrough) return;
                    swallow(event);
                    if (pressed) {
                        pressed = false;
                        return;
                    }
                    // A click no press of ours began: assistive technology,
                    // voice control, a switch.
                    if (done || pressing) return;
                    if (this.dsArmed) complete();
                    else arm();
                };

                const onFocusOut = () => {
                    disarm();
                    if (pressing === 'key') rewind();
                };

                const onContextMenu = (event) => {
                    // A long press on a touch screen opens the browser's menu.
                    if (pressing === 'pointer') event.preventDefault();
                };

                const onBlurWindow = () => {
                    if (pressing) rewind();
                };

                button.addEventListener('pointerdown', onPointerDown);
                button.addEventListener('pointermove', onPointerMove);
                button.addEventListener('pointerup', onPointerUp);
                button.addEventListener('pointercancel', onPointerUp);
                button.addEventListener('keydown', onKeyDown);
                button.addEventListener('keyup', onKeyUp);
                button.addEventListener('focusout', onFocusOut);
                button.addEventListener('contextmenu', onContextMenu);
                // Capturing on the root, so a click that must not act is
                // stopped before the button's own listeners hear it.
                root.addEventListener('click', onClick, true);
                window.addEventListener('blur', onBlurWindow);
                const unRestore = onRestore(() => {
                    done = false;
                    button.removeAttribute('aria-disabled');
                    progress = 0;
                    paint();
                });

                teardown = () => {
                    cancelAnimationFrame(frame);
                    unRestore();
                    button.removeEventListener('pointerdown', onPointerDown);
                    button.removeEventListener('pointermove', onPointerMove);
                    button.removeEventListener('pointerup', onPointerUp);
                    button.removeEventListener('pointercancel', onPointerUp);
                    button.removeEventListener('keydown', onKeyDown);
                    button.removeEventListener('keyup', onKeyUp);
                    button.removeEventListener('focusout', onFocusOut);
                    button.removeEventListener('contextmenu', onContextMenu);
                    root.removeEventListener('click', onClick, true);
                    window.removeEventListener('blur', onBlurWindow);
                };
            },

            destroy() {
                teardown?.();
            },
        };
    });
}
