/*
 * dsInlineEdit: text that turns into a field in place.
 *
 *   x-data="dsInlineEdit({ submit: 'enter', multiline: false, label: 'Title' })"
 *
 * The view renders the field (data-ds-inline-input) visible and editable,
 * inside the form it draws for `action`, so with no script the text can
 * still be changed and posted; and, hidden, the read state: a button
 * (data-ds-inline-read) holding the text (data-ds-inline-text). Starting,
 * this shows the button instead of the field. The view binds what changes to
 * `dsReady` and `dsEditing`, so a Livewire morph keeps the state the reader
 * is in.
 *
 * Pressing the button opens the field, at the width the text took, with the
 * text selected. Enter saves (Ctrl or Cmd+Enter when multiline) unless
 * `submit` is blur; leaving the component saves unless `submit` is buttons;
 * the Save button saves and Cancel cancels; Escape cancels and puts the text
 * back. Focus goes back to the button after a save or a cancel from the
 * keyboard or the buttons, and stays wherever the reader went after one by
 * leaving.
 *
 * A save that changed nothing just closes. Otherwise it dispatches `save`
 * from the root (bubbling, cancelable, detail { value, previous }), shows the
 * new text and writes dsValue, and that is all it assumes: the host decides.
 * Inside the view's form, a save nobody cancelled submits the form, marked
 * as saving. A cancel dispatches `cancel` with { value }, the text kept.
 *
 * Saving and failing are the host's to say, three ways, all ending in the
 * same two attributes on the root: the props render them, a host sets them
 * (Livewire's wire:loading.attr="data-ds-saving"), or an Alpine host sets
 * dsSaving and dsError in this scope. data-ds-saving draws a spinner and
 * aria-busy; data-ds-error opens the field again on the text the reader
 * tried, marks it invalid and shows the message under it, and it stays open
 * until a save succeeds or the reader cancels. A morph that brings or clears
 * either is heard by an observer.
 *
 * The scope exposes dsValue (for x-modelable), dsEditing, dsReady, dsSaving
 * and dsError, and nothing else.
 */

export function inlineEdit(Alpine) {
    Alpine.data('dsInlineEdit', (options = {}) => {
        let control = null;

        return {
            dsValue: '',
            dsEditing: false,
            dsReady: false,
            dsSaving: false,
            dsError: '',

            init() {
                control = wire(this.$el, options, this);
                this.dsReady = true;
                this.$watch('dsValue', (value) => control.set(value));
                this.$watch('dsSaving', (saving) => control.saving(saving));
                this.$watch('dsError', (error) => control.error(error));
            },

            destroy() {
                control?.teardown();
            },
        };
    });
}

function wire(root, options, scope) {
    const read = root.querySelector('[data-ds-inline-read]');
    const words = root.querySelector('[data-ds-inline-text]');
    const field = root.querySelector('[data-ds-inline-input]');
    const box = root.querySelector('[data-ds-inline-field]');
    const message = root.querySelector('[data-ds-inline-message]');
    const messageText = root.querySelector('[data-ds-inline-message-text]');
    const save = root.querySelector('[data-ds-inline-save]');
    const cancel = root.querySelector('[data-ds-inline-cancel]');
    const form = root.querySelector('[data-ds-inline-form]');
    const mode = ['enter', 'blur', 'buttons'].includes(options.submit) ? options.submit : 'enter';
    const multiline = options.multiline === true;
    const label = String(options.label ?? '');
    const placeholder = words.dataset.placeholder ?? '';

    // The text as last saved or rendered; what Escape goes back to.
    let value = field.value;
    let previous = value;
    // The width the text took when the field opened.
    let width = 0;

    function draw() {
        words.textContent = value !== '' ? value : placeholder;
        words.toggleAttribute('data-ds-empty', value === '');
        read.setAttribute('aria-label', `Edit ${label}${value !== '' ? `: ${value}` : ''}`);
    }

    function size() {
        if (multiline) return;
        field.style.minWidth = `${width}px`;
        // Where the browser cannot grow a field to its text, a character
        // wider per character typed.
        if (!CSS.supports?.('field-sizing', 'content')) field.style.width = `${Math.max(field.value.length + 2, 4)}ch`;
    }

    /*
     * Shown and focused at once, ahead of Alpine's redraw (which agrees): a
     * focused button hidden first would drop focus to the page, and the
     * focusout would read as the reader leaving.
     */
    function open() {
        if (read.disabled) return;
        width = read.getBoundingClientRect().width;
        field.value = value;
        box.hidden = false;
        size();
        field.focus();
        field.select();
        read.hidden = true;
        scope.dsEditing = true;
    }

    function close(returnFocus) {
        field.style.minWidth = '';
        field.style.width = '';
        read.hidden = false;
        if (returnFocus) read.focus();
        box.hidden = true;
        scope.dsEditing = false;
    }

    function commit(returnFocus) {
        const next = field.value;
        if (next === value && !scope.dsError) {
            close(returnFocus);
            return;
        }

        previous = value;
        value = next;
        draw();
        scope.dsValue = next;

        const event = new CustomEvent('save', { bubbles: true, cancelable: true, detail: { value: next, previous } });
        root.dispatchEvent(event);

        scope.dsError = '';
        close(returnFocus);

        if (form && !event.defaultPrevented) {
            scope.dsSaving = true;
            form.requestSubmit();
        }
    }

    function discard(returnFocus) {
        field.value = value;
        scope.dsError = '';
        root.dispatchEvent(new CustomEvent('cancel', { bubbles: true, detail: { value } }));
        close(returnFocus);
    }

    function onKeydown(event) {
        if (event.target !== field || !scope.dsEditing) return;

        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            discard(true);
            return;
        }

        if (event.key !== 'Enter' || event.isComposing) return;
        const chord = event.ctrlKey || event.metaKey;

        if (multiline && !chord) return;
        // Enter in a one-line field would submit the form around it.
        event.preventDefault();
        if (mode !== 'blur') commit(true);
    }

    function onFocusOut(event) {
        if (!scope.dsEditing || root.contains(event.relatedTarget)) return;
        if (mode === 'buttons') return;
        commit(false);
    }

    function onClick(event) {
        if (event.target.closest('[data-ds-inline-read]')) {
            open();
        } else if (save && event.target.closest('[data-ds-inline-save]')) {
            event.preventDefault();
            commit(true);
        } else if (cancel && event.target.closest('[data-ds-inline-cancel]')) {
            event.preventDefault();
            discard(true);
        }
    }

    function onInput(event) {
        if (event.target === field) size();
    }

    // The props and a host's attributes, read into the scope. Its own
    // writes come back here too, and change nothing.
    function readAttributes() {
        const saving = root.hasAttribute('data-ds-saving');
        const error = root.getAttribute('data-ds-error') ?? '';
        if (saving !== scope.dsSaving) scope.dsSaving = saving;
        if (error !== scope.dsError) scope.dsError = error;
    }

    function setSaving(saving) {
        root.toggleAttribute('data-ds-saving', Boolean(saving));
        if (saving) root.setAttribute('aria-busy', 'true');
        else root.removeAttribute('aria-busy');
    }

    function setError(error) {
        const text = String(error ?? '');
        if (text) root.setAttribute('data-ds-error', text);
        else root.removeAttribute('data-ds-error');
        messageText.textContent = text;
        message.hidden = !text;
        if (text) {
            field.setAttribute('aria-invalid', 'true');
            // The save did not take: back to the text the reader tried, and
            // into the field again. Focus follows only from the button it
            // left on, never from wherever the reader has gone since.
            const fromButton = document.activeElement === read || document.activeElement === document.body
                || root.contains(document.activeElement);
            field.value = value;
            value = previous;
            draw();
            if (!scope.dsEditing) {
                box.hidden = false;
                if (fromButton) field.focus();
                read.hidden = true;
                scope.dsEditing = true;
            }
        } else {
            field.removeAttribute('aria-invalid');
        }
    }

    const observer = new MutationObserver(readAttributes);
    observer.observe(root, { attributes: true, attributeFilter: ['data-ds-saving', 'data-ds-error'] });

    const listeners = [
        ['keydown', onKeydown],
        ['focusout', onFocusOut],
        ['click', onClick],
        ['input', onInput],
    ];
    for (const [type, fn] of listeners) root.addEventListener(type, fn);

    // What PHP rendered: a failed save comes back already in the field.
    scope.dsValue = value;
    scope.dsSaving = root.hasAttribute('data-ds-saving');
    if (scope.dsSaving) root.setAttribute('aria-busy', 'true');
    const rendered = root.getAttribute('data-ds-error') ?? '';
    if (rendered) {
        scope.dsError = rendered;
        scope.dsEditing = true;
    }

    return {
        /*
         * The host's value, shown. While the reader is typing it is left
         * alone; the next save or cancel works from it.
         */
        set(given) {
            const text = given === null || given === undefined ? '' : String(given);
            if (text === value) return;
            value = text;
            previous = text;
            draw();
            if (!scope.dsEditing) field.value = text;
        },
        saving: setSaving,
        error: setError,
        teardown() {
            observer.disconnect();
            for (const [type, fn] of listeners) root.removeEventListener(type, fn);
        },
    };
}
