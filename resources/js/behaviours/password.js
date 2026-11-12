/*
 * The password behaviours, in one file because they serve one field.
 *
 * dsPassword: the reveal button inside ds::input's `reveal`.
 *
 *   x-data="dsPassword"   on the button, which names its input with aria-controls
 *
 * Switches the input between type=password and type=text and says which with
 * aria-pressed. The icons swap in CSS off aria-pressed, so nothing here touches
 * a class. It must not move the reader: a press keeps focus where it was and,
 * when that was the field, the caret and selection where they were. The field
 * goes back to hidden when its form submits, so the browser offers to save a
 * password rather than a line of text, and the page restored by Back does not
 * show it.
 *
 * dsPasswordStrength: the meter of ds::password-strength.
 *
 *   x-data="dsPasswordStrength({ for: 'password', minLength: 8, labels: [...] })"
 *
 * Scores the field named by `for` as it is typed, into four levels, and writes
 * the level as data-level on the root (the bars are coloured from it in CSS)
 * and as words into its live region. The words come from `labels`, written by
 * the view. A hint, not a security control: the score is a small heuristic of
 * our own, and the server's own rules are the ones that count.
 *
 * Both scopes expose nothing but Alpine's own init and destroy.
 */

export function password(Alpine) {
    Alpine.data('dsPassword', () => {
        let teardown = null;

        return {
            init() {
                teardown = reveal(this.$el);
            },

            destroy() {
                teardown?.();
            },
        };
    });

    Alpine.data('dsPasswordStrength', (options = {}) => {
        let teardown = null;

        return {
            init() {
                teardown = meter(this.$el, options);
            },

            destroy() {
                teardown?.();
            },
        };
    });
}

function reveal(button) {
    const input = () => document.getElementById(button.getAttribute('aria-controls'));

    function show(visible) {
        const field = input();
        if (!field) return;

        // Changing type throws the selection away in some browsers, so it is
        // read first and put back.
        const focused = document.activeElement === field;
        const { selectionStart, selectionEnd, selectionDirection } = field;

        field.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));

        if (focused && selectionStart !== null) {
            field.setSelectionRange(selectionStart, selectionEnd, selectionDirection ?? 'none');
        }
    }

    const onClick = () => show(button.getAttribute('aria-pressed') !== 'true');

    // A press with the mouse would otherwise take focus from the field and
    // leave the reader to click back into it.
    const onMouseDown = (event) => {
        if (document.activeElement === input()) event.preventDefault();
    };

    const onSubmit = (event) => {
        const field = input();
        if (field && event.target.contains(field)) show(false);
    };

    button.addEventListener('click', onClick);
    button.addEventListener('mousedown', onMouseDown);
    document.addEventListener('submit', onSubmit, true);

    return () => {
        button.removeEventListener('click', onClick);
        button.removeEventListener('mousedown', onMouseDown);
        document.removeEventListener('submit', onSubmit, true);
    };
}

function meter(root, options) {
    const target = String(options.for ?? '');
    const minLength = Number.isInteger(options.minLength) ? options.minLength : 8;
    const labels = Array.isArray(options.labels) ? options.labels : [];
    const words = root.querySelector('[data-ds-strength-text]');
    let level = null;

    function update(value) {
        const next = score(value, minLength);
        if (next === level) return;

        // Written only when the level changes, so the live region speaks once
        // per change rather than on every key.
        level = next;
        root.dataset.level = String(next);
        words.textContent = next ? String(labels[next - 1] ?? '') : '';
    }

    // Listened for on the document and matched by id, so a field that a
    // Livewire morph replaced is still the field being measured.
    const onInput = (event) => {
        if (event.target?.id === target) update(event.target.value ?? '');
    };

    document.addEventListener('input', onInput);
    update(document.getElementById(target)?.value ?? '');

    return () => document.removeEventListener('input', onInput);
}

/*
 * 0 for nothing typed; otherwise 1 weak, 2 fair, 3 good, 4 strong.
 *
 * Length carries most of the score, because it is what most raises the cost of
 * guessing. Character variety helps a little. Runs ("aaaa", "1234", "qwer") and
 * a password that is one of the commonest ones under a coat of capitals, digits
 * and symbols ("P@ssw0rd!") count against it, because they are what is guessed
 * first.
 */
function score(value, minLength) {
    if (!value) return 0;
    if (value.length < minLength || isCommon(value)) return 1;

    let points = 1;
    if (value.length >= 12) points++;
    if (value.length >= 16) points++;
    if (value.length >= 20) points++;

    const kinds = [/[a-z]/, /[A-Z]/, /[0-9]/, /[^A-Za-z0-9]/].filter((kind) => kind.test(value)).length;
    if (kinds >= 3) points++;
    if (kinds === 4) points++;

    if (/(.)\1\1/.test(value)) points--;
    if (hasRun(value.toLowerCase())) points--;
    // Few distinct characters for the length ("abababab"). Capped, so a long
    // passphrase, which repeats letters as any sentence does, is not marked
    // down for it.
    if (new Set(value).size < Math.min(value.length / 2, 6)) points--;

    return Math.max(1, Math.min(4, points));
}

const RUNS = ['abcdefghijklmnopqrstuvwxyz', '01234567890', 'qwertyuiop', 'asdfghjkl', 'zxcvbnm'];

/** Four characters in a row along the alphabet, the digits or a keyboard row, either way. */
function hasRun(value) {
    for (let i = 0; i + 4 <= value.length; i++) {
        const piece = value.slice(i, i + 4);
        const backwards = [...piece].reverse().join('');
        if (RUNS.some((run) => run.includes(piece) || run.includes(backwards))) return true;
    }
    return false;
}

// The commonest passwords in the public breach lists, lower-cased. Short on
// purpose: a few dozen catch most of what is actually typed, and the script
// ships in every page.
const COMMON = new Set([
    'password', 'passw0rd', 'password1', 'qwerty', 'qwertyuiop', 'asdfghjkl', 'letmein', 'welcome',
    'iloveyou', 'admin', 'administrator', 'login', 'monkey', 'dragon', 'football', 'baseball',
    'master', 'shadow', 'sunshine', 'princess', 'superman', 'batman', 'trustno1', 'starwars',
    'whatever', 'freedom', 'hello', 'secret', 'changeme', 'default', 'abc123', 'qazwsx',
    'zaq12wsx', '1q2w3e4r', '1qaz2wsx', '123456', '1234567', '12345678', '123456789',
    '1234567890', '111111', '000000', '123123', '654321', '666666', '121212', 'michael',
    'jennifer', 'charlie', 'jordan', 'thomas', 'summer', 'internet', 'computer',
]);

// What a password looks like with its decoration taken off: the usual
// substitutions undone, and the digits and symbols tacked on either end gone.
const SWAPS = { 0: 'o', 1: 'i', 3: 'e', 4: 'a', 5: 's', 7: 't', '@': 'a', $: 's', '!': 'i' };

function isCommon(value) {
    const lower = value.toLowerCase();
    if (COMMON.has(lower)) return true;

    const trim = (text) => text.replace(/^[^a-z]+|[^a-z]+$/g, '');
    const plain = (text) => [...text].map((c) => SWAPS[c] ?? c).join('');

    // Both orders: "P@ssw0rd!" is password once the ends are trimmed and then
    // the swaps undone, "Pa55word" only with the swaps undone first.
    return [trim(lower), plain(trim(lower)), trim(plain(lower))]
        .some((candidate) => candidate.length >= 4 && COMMON.has(candidate));
}
