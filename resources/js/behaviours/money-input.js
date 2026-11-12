/*
 * dsMoneyInput: an amount typed in the locale's own grouping, posted as
 * digits.
 *
 *   x-data="dsMoneyInput({ locale, decimals, fixed, negative, minor, min, max, step, words })"
 *
 * The view renders a text field (data-ds-money-input) that works with no
 * script, named `name` and holding the amount as Support\Money writes it; a
 * hidden input with no name (data-ds-money-value) holding it as digits; the
 * symbol beside the field, or a currency select (data-ds-money-currency)
 * whose options carry their places in data-ds-decimals. Starting, `name`
 * moves to the hidden input, by bindings to `dsReady` a morph keeps.
 *
 *   - typing groups the digits as Intl groups them for the locale (en-IN:
 *     12,34,567.00), the caret staying after the digit it was after, and
 *     Backspace beside a separator deleting the digit beyond it
 *   - "." or "," typed is the decimal point, whichever the locale uses; a
 *     letter is not taken; a minus only at the start and only with
 *     `negative`; decimals past the places are not taken
 *   - a paste takes symbols, spaces, grouping and either point
 *     (shared/money.js read()), rounded to the places
 *   - leaving the field writes the places out ("12" to "12.00") and checks
 *     min, max and step, in BigInt minor units: one outside them keeps its
 *     text and gets aria-invalid and a validity message, as a native field
 *     would; it is never changed into another amount
 *   - a currency chosen in the select sets the places (unless `fixed`) and
 *     rounds the amount to them
 *
 * The hidden input holds "1234.50" (or minor units "123450" with `minor`),
 * '' for nothing, and is never written from floating point. Every keystroke
 * that changes it dispatches `input` from it; leaving the field, if it
 * changed, `change`. Both bubble.
 *
 * The scope exposes `dsValue` (the posted string, for x-modelable) and
 * `dsReady`. A value the host sets, a string or a number, is shown and
 * handed back normalised, without events.
 */
import { shape, typed, read, given, round, machine, write, check } from '../shared/money.js';
import { rewrite, skipSeparators } from '../shared/caret.js';

export function moneyInput(Alpine) {
    Alpine.data('dsMoneyInput', (options = {}) => {
        let control = null;

        return {
            dsValue: '',
            dsReady: false,

            init() {
                control = wire(this.$el, options, (value) => {
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

function wire(root, o, report) {
    const field = root.querySelector('[data-ds-money-input]');
    const posted = root.querySelector('[data-ds-money-value]');
    const currency = root.querySelector('[data-ds-money-currency]');
    const s = shape(o.locale);
    const words = o.words ?? {};
    const minor = o.minor === true;
    const serverInvalid = field.getAttribute('aria-invalid') === 'true';
    let decimals = Number.isInteger(o.decimals) ? o.decimals : 2;
    let committed = posted.value;

    const keep = (ch) => /\d/.test(ch) || ch === s.decimal || ch === '-' || ch === '−';
    const amount = () => typed(field.value, s.decimal);

    function invalid(message) {
        field.setCustomValidity(message);
        if (message || serverInvalid) field.setAttribute('aria-invalid', 'true');
        else field.removeAttribute('aria-invalid');
    }

    // The bounds are in whole units, so a minor value is read back first.
    function validate(value) {
        const whole = minor && value !== '' ? machine(given(value, decimals, true), decimals) : value;
        invalid(words[check(whole, o)] ?? '');
    }

    function post(value) {
        if (posted.value === value) return;
        posted.value = value;
        report(value);
        posted.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function onInput() {
        let a = amount();
        if (a && !o.negative) a.sign = '';
        if (a) a.frac = a.frac.slice(0, decimals);
        rewrite(field, write(a, s, decimals), keep);
        post(machine(a, decimals, minor));
    }

    function onBeforeInput(event) {
        skipSeparators(event, field, keep);
        if (event.defaultPrevented || !event.data || /^\d+$/.test(event.data)) return;
        if (event.inputType !== 'insertText' || event.data.length > 1) {
            if (!['insertText', 'insertReplacementText', 'insertFromDrop'].includes(event.inputType)) return;
            event.preventDefault();
            arrive(event.data);
            return;
        }
        const ch = event.data;
        event.preventDefault();
        const start = field.selectionStart;
        const end = field.selectionEnd;
        let insert = '';
        if ((ch === '.' || ch === ',' || ch === s.decimal) && decimals > 0 && !field.value.slice(0, start).concat(field.value.slice(end)).includes(s.decimal)) insert = s.decimal;
        else if ((ch === '-' || ch === '−') && o.negative && start === 0 && !/^[-−]/.test(field.value.slice(end))) insert = '-';
        if (!insert) return;
        field.setRangeText(insert, start, end, 'end');
        field.dispatchEvent(new Event('input', { bubbles: true }));
    }

    // Text that arrives whole, a paste or a drop: read leniently, then
    // typed in as digits and the locale's point.
    function arrive(text) {
        const a = round(read(text, s.decimal), decimals);
        if (!a) return;
        const insert = (a.sign && o.negative ? '-' : '') + (a.int || '0') + (a.frac ? s.decimal + a.frac : '');
        field.setRangeText(insert, field.selectionStart, field.selectionEnd, 'end');
        field.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function onPaste(event) {
        event.preventDefault();
        arrive(event.clipboardData?.getData('text') ?? '');
    }

    function commit() {
        const a = amount();
        const value = machine(a, decimals, minor);
        field.value = write(a, s, decimals, true);
        post(value);
        validate(value);
        if (value !== committed) {
            committed = value;
            posted.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    // Minor units mean something else once the places change, so the
    // amount is read at the old places and written at the new.
    function onCurrency() {
        const before = decimals;
        if (!o.fixed) decimals = Number(currency.selectedOptions[0]?.dataset.dsDecimals ?? decimals);
        const a = round(given(posted.value, before, minor), decimals);
        field.value = write(a, s, decimals, true);
        post(machine(a, decimals, minor));
        validate(posted.value);
        committed = posted.value;
    }

    // A press on the symbol or the frame's padding is a press on the field.
    function onFramePointerDown(event) {
        if (event.target === field || event.target.closest('select') || field.disabled) return;
        event.preventDefault();
        field.focus();
    }

    /*
     * The host's value, shown with its places, without events. Handed back
     * normalised when it was not ("12.5" for 12.5), so the two agree; the
     * echo of the field's own report changes nothing, so typing "12." is
     * not padded under the caret.
     */
    function set(value) {
        const a = round(given(value, decimals, minor), decimals);
        const next = machine(a, decimals, minor);
        if (next !== posted.value) {
            posted.value = next;
            committed = next;
            field.value = write(a, s, decimals, true);
            validate(next);
        }
        if (next !== String(value ?? '')) report(next);
    }

    const frame = root.querySelector('[data-ds-money-frame]');
    const listeners = [
        [field, 'beforeinput', onBeforeInput],
        [field, 'input', onInput],
        [field, 'paste', onPaste],
        [field, 'blur', commit],
        [frame, 'pointerdown', onFramePointerDown],
    ];
    if (currency) listeners.push([currency, 'change', onCurrency]);
    for (const [el, type, fn] of listeners) el?.addEventListener(type, fn);

    // The upgrade: the field is written in the browser's own grouping, which
    // is the one typing will keep, from the digits the server posted.
    if (currency && !o.fixed) decimals = Number(currency.selectedOptions[0]?.dataset.dsDecimals ?? decimals);
    field.value = write(round(given(posted.value, decimals, minor), decimals), s, decimals, true);

    return {
        value: () => posted.value,
        set,
        teardown() {
            for (const [el, type, fn] of listeners) el?.removeEventListener(type, fn);
        },
    };
}
