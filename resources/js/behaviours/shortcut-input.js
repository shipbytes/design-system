/*
 * dsShortcutInput: a keyboard shortcut, recorded by pressing it.
 *
 *   x-data="dsShortcutInput({ portable, by, bare, reserved, conflicts, words })"
 *
 * The view renders, from PHP, a text field (data-ds-shortcut-text) holding
 * the normalised string, which is the control with no script and the one
 * that posts with it; a recorder button (data-ds-shortcut-record), hidden
 * until `dsReady`, showing a placeholder, a prompt or the keys (a ds::kbd
 * with `shortcut`, redrawn by x-ds-shortcut); a clear button; a live region
 * and a note.
 *
 *   - a press, Enter or Space on the recorder starts recording; the next
 *     combination is taken on keydown, and nothing else sees that key
 *   - a modifier alone does not complete one; with no Ctrl, Cmd or Alt it is
 *     refused unless `bare`. Shift alone is not enough, it types
 *   - Escape cancels, Backspace or Delete with no modifier clears, and Tab is
 *     never taken: it cancels and moves on, so focus is never trapped. A
 *     press elsewhere, or leaving, cancels too
 *   - a `reserved` shortcut is refused with a message; one in `conflicts`
 *     is kept, with "Already used by …"
 *
 * A change sets the text field and dispatches, from it, `input` and then a
 * plain `change` Event whose `detail` is { value, previous }: a CustomEvent's
 * detail would be taken as the value by x-model and wire:model. Both bubble.
 * The scope exposes `dsValue`, `dsReady` and `dsRecording`. A value the host
 * sets is shown without events.
 */
import { caps, isMac, normalise, record, same } from '../shared/shortcut.js';

export function shortcutInput(Alpine) {
    Alpine.data('dsShortcutInput', (options = {}) => {
        let control = null;

        return {
            dsValue: '',
            dsReady: false,
            dsRecording: false,

            init() {
                control = wire(this.$el, options, {
                    report: (value) => (this.dsValue = value),
                    recording: (on) => (this.dsRecording = on),
                });
                this.dsValue = control.value();
                this.dsReady = true;
                this.$watch('dsValue', (value) => control.set(value));
            },

            destroy() {
                control?.teardown();
            },
        };
    });
}

function wire(root, o, scope) {
    const find = (selector) => root.querySelector(selector);
    const text = find('[data-ds-shortcut-text]');
    const button = find('[data-ds-shortcut-record]');
    const keys = find('[data-ds-shortcut-keys]');
    const words = o.words ?? {};
    const mac = isMac();
    const said = (value) => caps(value, mac).map((part) => part.word).join('+');
    let recording = false;

    function show() {
        const value = text.value;
        keys.dataset.dsShortcut = value;
        keys.hidden = recording || !value;
        find('[data-ds-shortcut-placeholder]').hidden = recording || Boolean(value);
        find('[data-ds-shortcut-prompt]').hidden = !recording;
        button.toggleAttribute('data-ds-recording', recording);
    }

    function say(message) {
        // Cleared first, so the same words twice are said twice.
        const live = find('[data-ds-shortcut-live]');
        live.textContent = '';
        requestAnimationFrame(() => (live.textContent = message));
    }

    function note(message) {
        find('[data-ds-shortcut-note-text]').textContent = message;
        find('[data-ds-shortcut-note]').hidden = !message;
    }

    function setRecording(on) {
        if (recording === on) return;
        recording = on;
        scope.recording(on);
        show();
    }

    function commit(value) {
        const previous = text.value;
        if (value === previous) return;
        text.value = value;
        scope.report(value);
        show();
        text.dispatchEvent(new Event('input', { bubbles: true }));
        const change = new Event('change', { bubbles: true });
        change.detail = { value, previous };
        text.dispatchEvent(change);
    }

    function cancel(quiet) {
        if (!recording) return;
        setRecording(false);
        if (!quiet) say(words.cancelled ?? '');
    }

    function clear() {
        note('');
        if (text.value) say(words.cleared ?? '');
        commit('');
    }

    function onClick() {
        if (recording) return cancel();
        note('');
        setRecording(true);
        say(words.recording ?? '');
    }

    function onKeyDown(event) {
        if (!recording) {
            if ((event.key === 'Backspace' || event.key === 'Delete') && text.value) {
                event.preventDefault();
                clear();
            }
            return;
        }
        if (event.key === 'Tab') return cancel(true);
        // Nothing else sees a key pressed while recording: not the page's
        // own shortcuts, not a dialog's Escape, and where it can, not the
        // browser.
        event.preventDefault();
        event.stopPropagation();
        const bare = !event.ctrlKey && !event.metaKey && !event.altKey;
        if (event.key === 'Escape' && bare && !event.shiftKey) return cancel();
        if ((event.key === 'Backspace' || event.key === 'Delete') && bare && !event.shiftKey) {
            setRecording(false);
            return clear();
        }
        const combo = record(event, { portable: o.portable !== false, by: o.by });
        if (!combo) return;
        if (bare && !o.bare) {
            const message = mac ? words.bareMac : words.bare;
            note(message ?? '');
            return say(message ?? '');
        }
        if ((o.reserved ?? []).some((r) => same(r, combo, mac))) {
            const message = (words.reserved ?? '').replace(':keys', said(combo));
            note(message);
            return say(message);
        }
        const taken = Object.keys(o.conflicts ?? {}).find((c) => same(c, combo, mac));
        const warning = taken ? (words.conflict ?? '').replace(':use', o.conflicts[taken]) : '';
        setRecording(false);
        note(warning);
        commit(combo);
        say(`${(words.set ?? '').replace(':keys', said(combo))} ${warning}`.trim());
    }

    // The label is for the text field, hidden now: it moves to the recorder.
    function onRootClick(event) {
        if (event.target.closest('label')) {
            event.preventDefault();
            button.focus();
        }
        if (event.target.closest('[data-ds-shortcut-clear]')) {
            cancel(true);
            clear();
            button.focus();
        }
    }

    // A required field with nothing recorded: said here, as the browser
    // cannot point at a hidden field.
    function onInvalid(event) {
        event.preventDefault();
        note(text.validationMessage);
        button.focus();
    }

    const listeners = [
        [button, 'click', onClick],
        [button, 'keydown', onKeyDown],
        [button, 'blur', () => cancel(true)],
        [root, 'click', onRootClick],
        [text, 'invalid', onInvalid],
    ];
    for (const [el, type, fn] of listeners) el.addEventListener(type, fn);
    show();

    function set(given) {
        const value = normalise(given ?? '');
        if (value !== text.value) {
            text.value = value;
            note('');
            show();
        }
        if (value !== given) scope.report(value);
    }

    return {
        value: () => text.value,
        set,
        teardown() {
            for (const [el, type, fn] of listeners) el.removeEventListener(type, fn);
        },
    };
}
