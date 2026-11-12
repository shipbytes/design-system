/*
 * dsOtp: a one-time code typed into one box per character, posted as ONE field.
 *
 *   x-data="dsOtp({ numeric: true })"
 *
 * The markup is the component's, rendered complete by PHP: the boxes, each
 * marked data-ds-otp-box and carrying no name, and one hidden input marked
 * data-ds-otp-value that the form posts. This moves characters and focus
 * between the boxes and keeps the hidden input in step with them.
 *
 * Every change the reader makes dispatches `input` and `change` on the hidden
 * input, as a typed field would, and a value that fills every box dispatches
 * `complete` (bubbling, detail { value }) so a host can submit without a button
 * press.
 *
 * Two-way binding goes through `dsValue`, the one name the scope exposes beside
 * Alpine's own init and destroy. The view puts `x-modelable="dsValue"` on the
 * root together with the host's x-model or wire:model, so Alpine entangles the
 * host's property with it: a code the reader types reaches the host, and a
 * code the host sets is written into the boxes. A value the host sets is shown,
 * not announced: it dispatches no `input`, `change` or `complete`, the way
 * setting a field's value from script fires nothing. The name carries the
 * prefix because the host's own expression on that element is read in this
 * scope, and a host with a `value` of its own would otherwise bind to ours.
 */

const DIGIT = /^[0-9]$/;
const ALPHANUMERIC = /^[A-Za-z0-9]$/;

export function otp(Alpine) {
    Alpine.data('dsOtp', (options = {}) => {
        const allowed = options.numeric === false ? ALPHANUMERIC : DIGIT;
        let teardown = null;

        return {
            dsValue: '',

            init() {
                const control = wire(this.$el, allowed, (value) => {
                    this.dsValue = value;
                });
                this.dsValue = control.value();
                this.$watch('dsValue', (value) => control.set(value));
                teardown = control.teardown;
            },

            destroy() {
                teardown?.();
            },
        };
    });
}

function wire(root, allowed, report) {
    const boxes = [...root.querySelectorAll('[data-ds-otp-box]')];
    const field = root.querySelector('[data-ds-otp-value]');

    // What each box held after the last accepted edit, so a rejected character
    // can be taken back out rather than left beside the one it tried to join.
    const held = boxes.map((box) => box.value);

    const accept = (text) => [...text].filter((c) => allowed.test(c));
    const indexOf = (el) => boxes.indexOf(el);

    function focusBox(i) {
        const box = boxes[Math.max(0, Math.min(i, boxes.length - 1))];
        box.focus();
        box.select();
    }

    /** Fill boxes from `from` onwards; the index of the last one written. */
    function write(from, chars) {
        let at = from;
        for (const c of chars) {
            if (at >= boxes.length) break;
            boxes[at].value = c;
            held[at] = c;
            at++;
        }
        return at - 1;
    }

    /*
     * A code the length of the whole field fills it from the first box, wherever
     * it was pasted: someone pasting the code they were sent means the code, not
     * "these six characters from box three on". A shorter run fills from the box
     * it landed in.
     */
    function distribute(from, chars) {
        const last = write(chars.length >= boxes.length ? 0 : from, chars);
        sync();
        focusBox(last);
    }

    function sync() {
        const value = boxes.map((box) => box.value).join('');
        if (value === field.value) return;

        field.value = value;
        report(value);
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));

        if (boxes.every((box) => box.value !== '')) {
            field.dispatchEvent(new CustomEvent('complete', { bubbles: true, detail: { value } }));
        }
    }

    function onInput(event) {
        const box = event.target;
        const i = indexOf(box);
        if (i < 0) return;

        // A key typed into a box that already holds a character arrives with
        // both in it, in caret order. `data` is the one just typed, which is the
        // one wanted: typing into a filled box replaces it.
        const typed = event.inputType === 'insertText' && event.data ? event.data : box.value;
        const chars = accept(typed);

        if (!chars.length) {
            // A deletion empties the box; anything else was a character the
            // field does not take, and the box goes back to what it held.
            box.value = String(event.inputType ?? '').startsWith('delete') ? '' : held[i];
            held[i] = box.value;
            sync();
            return;
        }

        if (chars.length === 1) {
            box.value = chars[0];
            held[i] = chars[0];
            sync();
            if (i < boxes.length - 1) focusBox(i + 1);
            return;
        }

        // Several characters at once into one box: the browser's one-time-code
        // autofill, which writes the whole code into the box that asked for it.
        distribute(i, chars);
    }

    function onPaste(event) {
        const i = indexOf(event.target);
        if (i < 0) return;

        event.preventDefault();
        // "123 456" and "123-456" are how codes are printed; anything the field
        // does not take is dropped, so both paste as 123456.
        const chars = accept(event.clipboardData?.getData('text') ?? '');
        if (chars.length) distribute(i, chars);
    }

    function onKeydown(event) {
        const box = event.target;
        const i = indexOf(box);
        if (i < 0 || event.ctrlKey || event.metaKey || event.altKey) return;

        switch (event.key) {
            case 'Backspace':
                // On a filled box the browser deletes the character and the input
                // handler above records it. On an empty one there is nothing to
                // delete, so it steps back and clears the box before.
                if (box.value === '' && i > 0) {
                    event.preventDefault();
                    boxes[i - 1].value = '';
                    held[i - 1] = '';
                    sync();
                    focusBox(i - 1);
                }
                return;
            case 'ArrowLeft':
                event.preventDefault();
                focusBox(i - 1);
                return;
            case 'ArrowRight':
                event.preventDefault();
                focusBox(i + 1);
                return;
            case 'Home':
                event.preventDefault();
                focusBox(0);
                return;
            case 'End':
                event.preventDefault();
                focusBox(boxes.length - 1);
                return;
        }
    }

    // Arriving in a filled box selects its character, so it shows what the next
    // key will replace.
    function onFocus(event) {
        if (indexOf(event.target) >= 0) event.target.select();
    }

    const listeners = [
        ['input', onInput],
        ['paste', onPaste],
        ['keydown', onKeydown],
        ['focusin', onFocus],
    ];
    for (const [type, fn] of listeners) root.addEventListener(type, fn);

    /*
     * The host's value, written into the boxes. What the field cannot hold, a
     * letter in a numeric code or a character past the last box, is dropped,
     * and the host is handed back what is actually there, so its state and the
     * boxes never disagree.
     */
    function set(value) {
        const given = String(value ?? '');
        const chars = accept(given).slice(0, boxes.length);
        const joined = chars.join('');

        if (joined !== field.value) {
            boxes.forEach((box, i) => {
                box.value = chars[i] ?? '';
                held[i] = box.value;
            });
            field.value = joined;
        }

        if (joined !== given) report(joined);
    }

    return {
        value: () => field.value,
        set,
        teardown() {
            for (const [type, fn] of listeners) root.removeEventListener(type, fn);
        },
    };
}
