/*
 * dsTimePicker: a time typed or picked from a list, posted as HH:MM.
 *
 *   x-data="dsTimePicker({ twelve, meridiem, min, max, step, words })"
 *
 * The view renders a native time field (data-ds-time-input) that works with
 * no script, a hidden input with no name (data-ds-time-value) and, hidden,
 * a listbox of times at `step` (data-ds-time-list), all from PHP. Starting,
 * this turns the field into a text field with the listbox under it: the name
 * moves to the hidden input, which always holds HH:MM (24-hour) or nothing,
 * whatever the field shows. The view binds every attribute that change
 * touches to `dsReady`, so a Livewire morph keeps it.
 *
 * Typing is forgiving: 9, 930, 9:30, 9.30pm and 21:30 all read. The reader's
 * text is settled when it is committed (Enter, leaving the component, a
 * `change` such as dsClear's, or a press on an option): a time is shown the
 * way the list shows it ("09:30", or "9:30 AM"); text that is not a time, or
 * a time outside min and max or off the step, KEEPS the reader's text, sets
 * aria-invalid and a validity message (so the form does not submit, as a
 * native time field would not), and is never quietly turned into some other
 * time. A time outside the bounds still posts, as typed; text that is not a
 * time posts nothing.
 *
 * The list follows the ARIA combobox pattern ds::combobox uses, and its
 * arrow keys are the listbox recipe's (partials/listbox.php), written in the
 * view: ArrowDown or Alt+ArrowDown in the field opens it and focuses the
 * chosen time, or the one nearest what is typed; the arrows, Home and End
 * move; Enter or Space picks; Escape closes it and returns to the field;
 * typing a character in the list sends it back to the field.
 *
 * The scope exposes `dsValue` (HH:MM or '', for x-modelable), `dsOpen` and
 * `dsReady`, which the view's bindings read, and nothing else. A value the
 * host sets is shown, not announced: no events, as setting a field from
 * script fires none. A committed change dispatches `input` and `change` from
 * the hidden input, bubbling.
 */

export function timePicker(Alpine) {
    Alpine.data('dsTimePicker', (options = {}) => {
        let control = null;

        return {
            dsValue: '',
            dsOpen: false,
            dsReady: false,

            init() {
                control = wire(this.$el, options, {
                    report: (value) => {
                        this.dsValue = value;
                    },
                    open: (open) => {
                        this.dsOpen = open;
                    },
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

function wire(root, options, hooks) {
    const field = root.querySelector('[data-ds-time-input]');
    const posted = root.querySelector('[data-ds-time-value]');
    const list = root.querySelector('[data-ds-time-list]');
    const words = options.words ?? {};
    const twelve = options.twelve === true;
    const meridiem = Array.isArray(options.meridiem) ? options.meridiem : ['AM', 'PM'];
    const min = typeof options.min === 'string' ? options.min : null;
    const max = typeof options.max === 'string' ? options.max : null;
    const step = Number(options.step) > 0 ? Number(options.step) : 15;
    // aria-invalid that the server rendered for its own error stays put: a
    // time that parses here does not answer what the server objected to.
    const serverInvalid = field.getAttribute('aria-invalid') === 'true';

    let open = false;

    const items = () => [...list.querySelectorAll('[role="option"]')];
    const minutes = (t) => Number(t.slice(0, 2)) * 60 + Number(t.slice(3, 5));

    function shown(t) {
        if (!t) return '';
        if (!twelve) return t;
        const h = Number(t.slice(0, 2));
        return `${h % 12 || 12}:${t.slice(3, 5)} ${meridiem[h >= 12 ? 1 : 0]}`;
    }

    /** What is wrong with a time, as a message; '' when nothing is. */
    function problem(t) {
        if ((min && t < min) || (max && t > max)) return words.range || words.invalid || '';
        if ((minutes(t) - (min ? minutes(min) : 0)) % step !== 0) return words.step || '';
        return '';
    }

    function invalid(message) {
        field.setCustomValidity(message);
        if (message || serverInvalid) field.setAttribute('aria-invalid', 'true');
        else field.removeAttribute('aria-invalid');
    }

    function mark(value) {
        for (const option of items()) option.setAttribute('aria-selected', String(option.dataset.value === value));
    }

    /** Write the value the form posts; whether it changed. */
    function post(value) {
        if (posted.value === value) return false;
        posted.value = value;
        mark(value);
        return true;
    }

    function announce() {
        posted.dispatchEvent(new Event('input', { bubbles: true }));
        posted.dispatchEvent(new Event('change', { bubbles: true }));
    }

    /** Settle what the reader typed. */
    function commit() {
        const text = field.value.trim();
        let value = '';

        if (text === '') {
            invalid('');
        } else {
            const time = parse(text, meridiem);
            if (time === null) {
                invalid(words.invalid || '');
            } else {
                const message = problem(time);
                invalid(message);
                // A time the field takes is shown the way the list shows it;
                // one it does not take keeps the reader's own text.
                if (!message) field.value = shown(time);
                value = time;
            }
        }

        if (post(value)) {
            hooks.report(value);
            announce();
        }
    }

    function setOpen(next) {
        if (open === next) return;
        open = next;
        hooks.open(next);
    }

    /** The option for the chosen time, or the one nearest what is typed. */
    function target() {
        const all = items();
        if (!all.length) return null;
        const wanted = parse(field.value.trim(), meridiem) ?? (posted.value || null);
        if (!wanted) return all.find((o) => o.getAttribute('aria-selected') === 'true') ?? all[0];
        const at = minutes(wanted);
        return all.reduce((best, o) => (Math.abs(minutes(o.dataset.value) - at) < Math.abs(minutes(best.dataset.value) - at) ? o : best));
    }

    // After x-show has drawn the list, so it has a box to scroll in.
    const afterOpen = (fn) => requestAnimationFrame(() => requestAnimationFrame(fn));

    function reveal(focus) {
        setOpen(true);
        afterOpen(() => {
            const option = target();
            if (!option) return;
            if (focus) option.focus({ preventScroll: true });
            scrollInto(option);
        });
    }

    // Within the list only: scrollIntoView would scroll the page as well.
    function scrollInto(option) {
        const top = option.offsetTop;
        const bottom = top + option.offsetHeight;
        if (top < list.scrollTop) list.scrollTop = top;
        else if (bottom > list.scrollTop + list.clientHeight) list.scrollTop = bottom - list.clientHeight;
    }

    function pick(option) {
        field.value = shown(option.dataset.value);
        invalid('');
        setOpen(false);
        field.focus();
        if (post(option.dataset.value)) {
            hooks.report(option.dataset.value);
            announce();
        }
    }

    function onFieldKeydown(event) {
        if (field.disabled) return;
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                reveal(true);
                return;
            case 'ArrowUp':
                if (!open) return;
                event.preventDefault();
                reveal(true);
                return;
            case 'Enter':
                // Closing the list is what this Enter was for; with it closed
                // the reader may mean the form, which submits as usual, with
                // the time settled first.
                if (open) event.preventDefault();
                commit();
                setOpen(false);
                return;
            case 'Escape':
                // Only when it consumes the key: with the list closed, Escape
                // belongs to whatever holds the field.
                if (!open) return;
                event.stopPropagation();
                setOpen(false);
                return;
        }
    }

    function onListKeydown(event) {
        const option = event.target.closest('[role="option"]');
        if (!option) return;
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            pick(option);
        } else if (event.key === 'Escape') {
            event.stopPropagation();
            setOpen(false);
            field.focus();
        } else if (event.key === 'Tab') {
            setOpen(false);
        } else if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
            // Typing in the list types in the field: focus moves before the
            // character is inserted, so it lands there.
            field.focus();
        }
    }

    function onListClick(event) {
        const option = event.target.closest('[role="option"]');
        if (option) pick(option);
    }

    // A press on an option would blur the field first and close the list
    // under the pointer; keep focus where it is until the click lands.
    function onListPointerDown(event) {
        if (event.target.closest('[role="option"]')) event.preventDefault();
    }

    function onFieldClick() {
        if (field.disabled) return;
        if (open) setOpen(false);
        else reveal(false);
    }

    // The reader typing opens the list at the nearest time. A script
    // emptying the field (dsClear) does not.
    function onFieldInput(event) {
        if (!event.isTrusted) return;
        if (!open) setOpen(true);
        afterOpen(() => {
            const option = target();
            if (option) scrollInto(option);
        });
    }

    function onFocusOut(event) {
        if (root.contains(event.relatedTarget)) return;
        setOpen(false);
        commit();
    }

    const listeners = [
        [field, 'keydown', onFieldKeydown],
        [field, 'click', onFieldClick],
        [field, 'input', onFieldInput],
        [field, 'change', commit],
        [list, 'keydown', onListKeydown],
        [list, 'click', onListClick],
        [list, 'pointerdown', onListPointerDown],
        [root, 'focusout', onFocusOut],
    ];

    /*
     * The upgrade itself, done here at once rather than waiting for the
     * bindings: the field must be a text field before a 12-hour time is
     * written into it, or the browser would sanitise it to nothing.
     */
    const start = field.value;
    const name = field.getAttribute('name');
    field.type = 'text';
    if (name !== null) {
        posted.name = name;
        field.removeAttribute('name');
    }
    posted.value = start;
    field.value = shown(start);
    mark(start);

    for (const [el, type, fn] of listeners) el.addEventListener(type, fn);

    /*
     * The host's value, shown. One that is not a time is cleared, and the
     * host is handed back what the field now holds, so the two agree.
     */
    function set(given) {
        const text = given === null || given === undefined ? '' : String(given);
        const time = text === '' ? '' : parse(text, meridiem);
        const value = time ?? '';
        if (value !== posted.value) {
            post(value);
            field.value = shown(value);
            invalid(value ? problem(value) : '');
        }
        if (value !== text) hooks.report(value);
    }

    return {
        value: () => posted.value,
        set,
        teardown() {
            for (const [el, type, fn] of listeners) el.removeEventListener(type, fn);
        },
    };
}

/*
 * HH:MM from what a person types, or null. Hours alone ("9"), hours and
 * minutes run together ("930", "2130"), or split by a colon, a point, a
 * space or an "h" ("9:30", "9.30", "9 30", "9h30"); seconds are dropped; a
 * trailing am or pm (a, p, a.m., P.M.) or the view's own words for them make
 * it a 12-hour time, where 12 am is midnight.
 */
function parse(text, meridiem = ['AM', 'PM']) {
    let s = String(text).trim().toLowerCase();
    if (!s) return null;

    let half = null;
    const own = meridiem.map((w) => String(w).toLowerCase());
    for (const [i, word] of own.entries()) {
        if (word && s.endsWith(word)) {
            half = i;
            s = s.slice(0, -word.length).trim();
            break;
        }
    }
    if (half === null) {
        const suffix = s.match(/\s*([ap])\.?\s*(m\.?)?$/);
        if (suffix) {
            half = suffix[1] === 'a' ? 0 : 1;
            s = s.slice(0, suffix.index).trim();
        }
    }

    const m = s.match(/^(\d{1,2})(?:[:.h\s]?(\d{2})(?::\d{2})?)?$/);
    if (!m) return null;

    let hours = Number(m[1]);
    const mins = m[2] === undefined ? 0 : Number(m[2]);
    if (mins > 59) return null;

    if (half !== null) {
        if (hours < 1 || hours > 12) return null;
        hours = (hours % 12) + (half === 1 ? 12 : 0);
    } else if (hours > 23) {
        return null;
    }

    return `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;
}
