/*
 * dsPhoneInput: a country picked from a searchable list and a national
 * number, posted as E.164.
 *
 *   x-data="dsPhoneInput({ country, primary, words })"
 *
 * The view renders, from PHP, a native select of countries
 * (data-ds-phone-select) whose options carry each country's name, dial
 * code, mask, lengths and flag in data-ds-* attributes; a tel field
 * (data-ds-phone-input); a hidden input with no name (data-ds-phone-value);
 * and, hidden until `dsReady`, a ds::popover whose trigger
 * (data-ds-phone-trigger) shows the country and whose empty listbox
 * (data-ds-phone-list) is filled from the select's options, through a
 * template, the first time it opens. The select stays the one place the
 * country is held, and the bindings to `dsReady` move the names, so a
 * morph keeps both.
 *
 *   - the list's keys, filter and choosing are the shared picker's
 *     (shared/picker.js). It searches the name (ranked as the command
 *     palette ranks), the ISO code (an exact one first) and the dial code
 *   - typing is formatted with the country's mask as it goes, the caret
 *     kept (shared/caret.js); a leading 0 stays on screen and is dropped
 *     from E.164, except where the country keeps it
 *   - a number starting "+" or "00", typed or pasted, chooses the country
 *     by its code (the longest that fits, then the current country, then
 *     the primary one of a shared code) and leaves the rest in the field
 *   - leaving the field checks the length only, against the country's
 *     generous bounds: aria-invalid and a note when clearly wrong. It is no
 *     more than that, and the server validates
 *
 * The hidden input holds E.164 or ''. A change dispatches `input` from it,
 * and leaving the field, or choosing a country, `change`; both bubble.
 *
 * The scope exposes `dsValue` (E.164, for x-modelable) and `dsReady`. A
 * value the host sets is shown, its country chosen, without events.
 */
import { fold, rank } from '../shared/rank.js';
import { picker } from '../shared/picker.js';
import { rewrite, skipSeparators } from '../shared/caret.js';

export function phoneInput(Alpine) {
    Alpine.data('dsPhoneInput', (options = {}) => {
        let control = null;

        return {
            dsValue: '',
            dsReady: false,

            init() {
                control = wire(this.$el, options, Alpine, (value) => {
                    this.dsValue = value;
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

const digit = (ch) => ch >= '0' && ch <= '9';

function wire(root, o, Alpine, report) {
    const find = (selector) => root.querySelector(selector);
    const select = find('[data-ds-phone-select]');
    const field = find('[data-ds-phone-input]');
    const posted = find('[data-ds-phone-value]');
    const words = o.words ?? {};
    const serverInvalid = field.getAttribute('aria-invalid') === 'true';
    let committed = posted.value;

    const countries = () => [...select.options];
    const current = () => select.selectedOptions[0] ?? select.options[0];
    const code = (c) => c.dataset.dsDial.replace(/\D/g, '');
    const national = (digits, c = current()) => (!c.hasAttribute('data-ds-zero') && digits.startsWith('0') ? digits.slice(1) : digits);

    function mask(digits, pattern) {
        if (!pattern || !digits) return digits;
        let out = '';
        let at = 0;
        for (const ch of pattern) {
            if (at >= digits.length) break;
            out += ch === '#' ? digits[at++] : ch;
        }
        return out + digits.slice(at);
    }

    // A leading 0 the reader typed is shown before the masked number.
    const shown = (digits, c = current()) => {
        const rest = national(digits, c);
        return digits.slice(0, digits.length - rest.length) + mask(rest, c.dataset.dsMask);
    };

    const e164 = (digits, c = current()) => (national(digits, c) ? `+${code(c)}${national(digits, c)}` : '');

    /** The country an international number starts with, and the rest of it. */
    function match(digits) {
        const order = (c) => (c === current() ? 0 : c.value === o.country ? 1 : (o.primary ?? []).includes(c.value) ? 2 : 3);
        const fits = countries().filter((c) => digits.startsWith(code(c)));
        fits.sort((a, b) => code(b).length - code(a).length || order(a) - order(b));
        return fits[0] ? [fits[0], digits.slice(code(fits[0]).length)] : null;
    }

    function face(c) {
        const trigger = find('[data-ds-phone-trigger]');
        for (const part of ['name', 'iso', 'dial', 'flag']) {
            const el = trigger?.querySelector(`[data-ds-phone-${part}]`);
            if (el) el.textContent = part === 'iso' ? c.value : part === 'dial' ? `+${c.dataset.dsDial}` : part === 'name' ? c.dataset.dsName : c.dataset.dsFlag ?? '';
        }
        for (const option of root.querySelectorAll('[data-ds-phone-option]')) option.setAttribute('aria-selected', String(option.dataset.value === c.value));
    }

    function choose(c) {
        if (select.value !== c.value) select.value = c.value;
        face(c);
    }

    function note(message) {
        const box = find('[data-ds-phone-note]');
        const text = find('[data-ds-phone-note-text]');
        if (text) text.textContent = message;
        if (box) box.hidden = !message;
        if (message || serverInvalid) field.setAttribute('aria-invalid', 'true');
        else field.removeAttribute('aria-invalid');
    }

    function post(value) {
        if (posted.value === value) return;
        posted.value = value;
        report(value);
        posted.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function settle() {
        if (posted.value === committed) return;
        committed = posted.value;
        posted.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function onInput() {
        const text = field.value;
        let digits = text.replace(/\D/g, '');
        if (/^\s*(\+|00)/.test(text)) {
            const found = match(text.trim().startsWith('+') ? digits : digits.slice(2));
            if (!found) {
                post('');
                return;
            }
            choose(found[0]);
            field.value = shown(found[1]);
            field.setSelectionRange(field.value.length, field.value.length);
            post(e164(found[1]));
            return;
        }
        rewrite(field, shown(digits), digit);
        post(e164(digits));
    }

    function onBeforeInput(event) {
        skipSeparators(event, field, digit);
        // Digits, and what a pasted or dictated number carries with them.
        if (!event.defaultPrevented && event.inputType === 'insertText' && event.data && /[^\d\s+()./-]/.test(event.data)) event.preventDefault();
    }

    function check() {
        const c = current();
        const length = national(field.value.replace(/\D/g, ''), c).length;
        const problem = !length ? '' : length < Number(c.dataset.dsMin) ? 'short' : Number(c.dataset.dsMax) && length > Number(c.dataset.dsMax) ? 'long' : '';
        note(problem ? (words[problem] ?? '').replace(':country', c.dataset.dsName) : '');
    }

    function onBlur() {
        check();
        settle();
    }

    // The list, from the select's options, through the template.
    function fill() {
        const list = find('[data-ds-phone-list]');
        const template = find('template[data-ds-phone-template]');
        if (!list || !template || list.querySelector('[data-ds-phone-option]')) return;
        const items = countries().map((c) => {
            const item = template.content.firstElementChild.cloneNode(true);
            const option = item.querySelector('[data-ds-phone-option]');
            option.id = `${list.id}-${c.value}`;
            option.dataset.value = c.value;
            option.dataset.dsLabel = c.dataset.dsName;
            option.dataset.dsDial = c.dataset.dsDial;
            option.setAttribute('aria-selected', String(c === current()));
            option.querySelector('[data-ds-phone-flag]').textContent = c.dataset.dsFlag ?? '';
            option.querySelector('[data-ds-phone-name]').textContent = c.dataset.dsName;
            option.querySelector('[data-ds-phone-dial]').textContent = `+${c.dataset.dsDial}`;
            return item;
        });
        list.replaceChildren(...items);
    }

    const list = picker(root, {
        option: '[data-ds-phone-option]',
        search: '[data-ds-phone-search]',
        empty: '[data-ds-phone-empty]',
        score(option, q) {
            if (q === option.dataset.value.toLowerCase()) return 10;
            const dial = q.replace(/[\s+]/g, '');
            if (/^\d+$/.test(dial)) return option.dataset.dsDial.replace(/\D/g, '').startsWith(dial) ? 5 : 0;
            return rank(fold(option.dataset.dsLabel), q) * 2;
        },
        choose(option) {
            const c = countries().find((x) => x.value === option.dataset.value);
            const popover = find('[data-ds-popover]');
            if (popover) Alpine.$data(popover).dsOpen = false;
            if (!c) return;
            const digits = field.value.replace(/\D/g, '');
            choose(c);
            field.value = shown(digits);
            post(e164(digits));
            if (field.value) check();
            settle();
        },
    });

    // Each opening starts from the whole list, with the current country in
    // it marked; with no search field, focus starts on that country.
    function onClick(event) {
        if (!event.target.closest('[data-ds-phone-trigger]')) return;
        fill();
        list.reset();
        face(current());
        if (!find('[data-ds-phone-search]')) {
            for (const option of root.querySelectorAll('[data-ds-phone-option]')) option.toggleAttribute('data-ds-autofocus', option.dataset.value === select.value);
        }
    }

    const listeners = [
        [field, 'beforeinput', onBeforeInput],
        [field, 'input', onInput],
        [field, 'blur', onBlur],
        [root, 'click', onClick],
    ];
    for (const [el, type, fn] of listeners) el.addEventListener(type, fn);

    /*
     * The host's value, shown without events: an E.164 number chooses its
     * country; one with no "+" is a national number where the field is.
     */
    function set(given) {
        const text = given === null || given === undefined ? '' : String(given).trim();
        let c = current();
        let digits = text.replace(/\D/g, '');
        if (/^(\+|00)/.test(text)) {
            const found = match(text.startsWith('+') ? digits : digits.slice(2));
            [c, digits] = found ?? [c, ''];
        }
        const next = e164(digits, c);
        if (next !== posted.value) {
            choose(c);
            field.value = shown(national(digits, c), c);
            posted.value = next;
            committed = next;
            note('');
        }
        if (next !== text) report(next);
    }

    return {
        value: () => posted.value,
        set,
        teardown() {
            list.teardown();
            for (const [el, type, fn] of listeners) el.removeEventListener(type, fn);
        },
    };
}
