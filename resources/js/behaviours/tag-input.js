/*
 * dsTagInput: short values typed into a field and kept as removable chips.
 *
 *   x-data="dsTagInput({ separators: ['Enter', ','], unique: true, max: null,
 *                        validate: 'email', messages: { … } })"
 *
 * The DOM is the state. Each chip is a list item carrying its own hidden input,
 * rendered by PHP for the initial value and cloned from the component's
 * template for every new one, so what is on screen and what the form posts
 * cannot disagree, and a page whose script never runs still posts what it was
 * rendered with. Text goes in with textContent and .value only, never as
 * markup.
 *
 * An entry that cannot be added (invalid, a duplicate, one over `max`) stays in
 * the text box with a message saying why. Nothing typed is ever dropped.
 *
 * Every word it shows or announces comes from `messages`, written by the view,
 * so this file holds no copy. `:tag` and `:max` are filled in.
 *
 * Two-way binding goes through `dsValue`, an array of the tags and the one name
 * the scope exposes beside Alpine's own init and destroy; the listeners are
 * attached to the root here. The view puts `x-modelable="dsValue"` on the root
 * with the host's x-model or wire:model, so a tag the reader adds or removes
 * reaches the host, and a list the host sets is rendered as chips, without a
 * word to the live region: the reader did not do it. Prefixed for the reason
 * dsOtp's is: the host's expression on the root is read in this scope.
 */

export function tagInput(Alpine) {
    Alpine.data('dsTagInput', (options = {}) => {
        let teardown = null;

        return {
            dsValue: [],

            init() {
                const control = wire(this.$el, settings(options), (tags) => {
                    this.dsValue = tags;
                });
                this.dsValue = control.value();
                this.$watch('dsValue', (tags) => control.set(tags));
                teardown = control.teardown;
            },

            destroy() {
                teardown?.();
            },
        };
    });
}

function settings(options) {
    const separators = options.separators ?? ['Enter', ','];

    return {
        enter: separators.includes('Enter'),
        characters: separators.filter((s) => s !== 'Enter' && s.length === 1),
        unique: options.unique !== false,
        max: Number.isInteger(options.max) ? options.max : null,
        validate: options.validate ?? null,
        messages: options.messages ?? {},
    };
}

function wire(root, s, report) {
    const text = root.querySelector('[data-ds-tag-text]');
    const list = root.querySelector('[data-ds-tag-list]');
    const template = root.querySelector('[data-ds-tag-template]');
    const note = root.querySelector('[data-ds-tag-note]');
    const noteText = note.querySelector('[data-ds-tag-note-text]');
    const help = root.querySelector('[data-ds-tag-help]');
    const status = root.querySelector('[data-ds-tag-status]');

    // The server's verdict on the field, which a message about one entry
    // overrides only while it is showing.
    const invalid = text.getAttribute('aria-invalid');

    // Entries that could not be added go back into the box joined the way the
    // reader would have typed them, so committing again splits them the same.
    const first = s.characters[0];
    const joiner = !first || first === ' ' ? ' ' : first + ' ';

    // Which kind of message is showing, so a removal can take back a "too
    // many" without also hiding a "not an email address" that still stands.
    let showing = null;

    // The browser's own idea of an email address, the one it enforces on a
    // type=email field, rather than a regular expression of ours.
    const probe = document.createElement('input');
    probe.type = 'email';

    const say = (key, tag = '') =>
        String(s.messages[key] ?? '').replaceAll(':tag', tag).replaceAll(':max', String(s.max ?? ''));

    const chips = () => [...list.querySelectorAll('[data-ds-tag]')];
    const valueOf = (chip) => chip.querySelector('[data-ds-tag-value]').value;
    const values = () => chips().map(valueOf);
    const removeButton = (chip) => chip?.querySelector('[data-ds-tag-remove]');

    function announce(message) {
        status.textContent = message;
    }

    function tell(message, kind = null) {
        showing = message ? kind : null;
        noteText.textContent = message;
        note.hidden = !message;
        // The message takes the help line's place while it shows, the way an
        // error does: two lines of guidance under one field is one too many.
        if (help) help.hidden = Boolean(message);
        if (message) text.setAttribute('aria-invalid', 'true');
        else if (invalid === null) text.removeAttribute('aria-invalid');
        else text.setAttribute('aria-invalid', invalid);
    }

    /** Why `value` cannot be added, as [kind, message], or null when it can. */
    function problem(value) {
        if (s.max !== null && chips().length >= s.max) return ['max', say('max', value)];
        if (s.validate === 'email') {
            probe.value = value;
            if (!probe.checkValidity()) return ['invalid', say('invalid', value)];
        }
        // Case-insensitive: Maya@Example.com and maya@example.com are one
        // address, and Billing and billing are one tag.
        const lower = value.toLowerCase();
        if (s.unique && chips().some((chip) => valueOf(chip).toLowerCase() === lower)) {
            return ['duplicate', say('duplicate', value)];
        }
        return null;
    }

    function chipFor(value) {
        const chip = template.content.firstElementChild.cloneNode(true);
        chip.querySelector('[data-ds-tag-label]').textContent = value;
        removeButton(chip).setAttribute('aria-label', say('remove', value));
        chip.querySelector('[data-ds-tag-value]').value = value;
        // The server renders a disabled field's chips disabled; one the host
        // sets later must match them.
        if (text.disabled) {
            removeButton(chip).disabled = true;
            chip.querySelector('[data-ds-tag-value]').disabled = true;
        }
        return chip;
    }

    function add(value) {
        list.append(chipFor(value));
        list.hidden = false;
        announce(say('added', value));
    }

    function remove(chip) {
        const value = valueOf(chip);
        chip.remove();
        list.hidden = chips().length === 0;
        report(values());
        announce(say('removed', value));
        if (showing === 'max') tell('');
        text.focus();
    }

    /**
     * Add every entry in `raw`. Leaves what could not be added in the box, and
     * the first reason why under it; `tail` is text still being typed.
     */
    function commit(raw, splitters = s.characters, tail = '') {
        const kept = [];
        let wrong = null;

        let added = false;

        for (const part of split(raw, splitters)) {
            const value = part.trim();
            if (!value) continue;

            const why = problem(value);
            if (why) {
                kept.push(value);
                wrong ??= why;
            } else {
                add(value);
                added = true;
            }
        }

        if (added) report(values());

        if (tail) kept.push(tail);
        text.value = kept.join(joiner);
        tell(wrong?.[1] ?? '', wrong?.[0]);
    }

    function onTextKeydown(event) {
        if (event.isComposing) return;
        const caretAtStart = text.selectionStart === 0 && text.selectionEnd === 0;

        if (event.key === 'Enter' && s.enter) {
            // An empty box lets Enter do what it does in any field: submit.
            if (!text.value.trim()) return;
            event.preventDefault();
            commit(text.value);
            return;
        }

        if (s.characters.includes(event.key)) {
            event.preventDefault();
            commit(text.value);
            return;
        }

        // Backspace in an empty box reaches the last chip, which shows its
        // focus ring and is announced; a second press removes it. One press
        // deleting a whole entry would be one press too easy to make by
        // mistake. ArrowLeft from the start of the box goes the same way.
        const back = event.key === 'Backspace' ? text.value === '' : event.key === 'ArrowLeft';
        if (back && caretAtStart && chips().length) {
            event.preventDefault();
            removeButton(chips().at(-1)).focus();
        }
    }

    function onChipKeydown(event, chip) {
        const all = chips();
        const at = all.indexOf(chip);

        switch (event.key) {
            case 'Backspace':
            case 'Delete':
                event.preventDefault();
                remove(chip);
                return;
            case 'ArrowLeft':
                event.preventDefault();
                if (at > 0) removeButton(all[at - 1]).focus();
                return;
            case 'ArrowRight':
                event.preventDefault();
                if (at < all.length - 1) removeButton(all[at + 1]).focus();
                else text.focus();
                return;
        }
    }

    function onKeydown(event) {
        if (event.target === text) return onTextKeydown(event);
        const chip = event.target.closest('[data-ds-tag-remove]')?.closest('[data-ds-tag]');
        if (chip) onChipKeydown(event, chip);
    }

    function onInput(event) {
        if (event.target !== text) return;
        if (text.value.trim() === '' && !note.hidden) tell('');

        // A phone's keyboard reports no key for the comma, so a separator can
        // arrive only as text. Everything up to the last one is committed; what
        // follows it is still being typed.
        const at = Math.max(...s.characters.map((c) => text.value.lastIndexOf(c)));
        if (at < 0) return;

        commit(text.value.slice(0, at), s.characters, text.value.slice(at + 1).trimStart());
    }

    function onPaste(event) {
        if (event.target !== text) return;
        const pasted = event.clipboardData?.getData('text') ?? '';

        // A single value pastes like text into any field. A list (a column from
        // a spreadsheet, a line of addresses) commits every entry at once. A
        // line break always splits: a one-line box cannot hold one, and the
        // browser would otherwise join the lines together.
        if (!/[\r\n]/.test(pasted) && !s.characters.some((c) => pasted.includes(c))) return;

        event.preventDefault();
        const whole = text.value.slice(0, text.selectionStart) + pasted + text.value.slice(text.selectionEnd);
        commit(whole, [...s.characters, '\n', '\r']);
    }

    function onClick(event) {
        const button = event.target.closest('[data-ds-tag-remove]');
        if (button) {
            remove(button.closest('[data-ds-tag]'));
            return;
        }
        // Anywhere else in the field puts the caret in the box. A click on the
        // padding or a chip's text leaving focus nowhere feels broken.
        if (event.target.closest('[data-ds-tag-field]') && !text.disabled) text.focus();
    }

    function onFocusOut(event) {
        if (event.target === text && text.value.trim()) commit(text.value);
    }

    const listeners = [
        ['keydown', onKeydown],
        ['input', onInput],
        ['paste', onPaste],
        ['click', onClick],
        ['focusout', onFocusOut],
    ];
    for (const [type, fn] of listeners) root.addEventListener(type, fn);

    /*
     * The host's list, rendered as chips. Blank entries are dropped, as the
     * server drops them, and the host is handed back the list as it now
     * stands. A list the host sets is not checked against `max`, `unique` or
     * `validate`: those guard what a reader types, and the host is the one
     * deciding here.
     */
    function set(tags) {
        const wanted = normalise(tags);

        if (JSON.stringify(wanted) !== JSON.stringify(values())) {
            for (const chip of chips()) chip.remove();
            for (const value of wanted) list.append(chipFor(value));
            list.hidden = wanted.length === 0;
            if (showing === 'max') tell('');
        }

        if (JSON.stringify(wanted) !== JSON.stringify(tags)) report(wanted);
    }

    return {
        value: values,
        set,
        teardown() {
            for (const [type, fn] of listeners) root.removeEventListener(type, fn);
        },
    };
}

function normalise(tags) {
    const list = Array.isArray(tags) ? tags : tags === null || tags === undefined || tags === '' ? [] : [tags];
    return list.map((tag) => String(tag ?? '').trim()).filter((tag) => tag !== '');
}

function split(raw, splitters) {
    let parts = [raw];
    for (const c of splitters) parts = parts.flatMap((part) => part.split(c));
    return parts;
}
