/*
 * dsMention: a list of people or things to mention, opened by a trigger
 * character in a plain textarea, behind ds::mention-input.
 *
 *   x-data="dsMention({ triggers: ['@'], insert: '@label', minChars, max, atomic })"
 *
 * The view renders everything from PHP: the field (data-ds-mention-input),
 * a panel (data-ds-mention-panel, shown by x-show="dsOpen" and hung off the
 * caret by x-ds-anchor with `at: dsCaret`), its listbox of every row
 * (data-ds-mention-list, options data-ds-mention-option with an id, a
 * data-value, the label in data-ds-label, the meta in data-ds-keywords and
 * the trigger in data-ds-trigger), an empty row (data-ds-mention-empty), and
 * one hidden input per mention (in data-ds-mention-ids, each with its text
 * in data-ds-mention-text) and a template for another.
 *
 *   - typing a trigger at the start of a word opens the list for that
 *     trigger, once `minChars` follow it; what follows filters it, ranked as
 *     the command palette ranks (shared/rank.js), at most `max` rows. Only
 *     typing opens it: moving the caret into old text does not
 *   - it closes on a space typed when nothing matches, on the caret leaving
 *     the word, on Escape (stopped there, so a modal around it stays open),
 *     on leaving the field and on choosing
 *   - while open, Up and Down move the active row (aria-activedescendant;
 *     focus stays in the field) and Enter or Tab writes it in; a press on a
 *     row does too. Closed, every key is the field's own
 *   - choosing replaces the trigger and query with the `insert` template
 *     ("@" the trigger, "label" and "id" the row's) and a space, through the
 *     browser's own editing where it can, so Undo takes it back; and adds a
 *     hidden input with the row's id. An edit that breaks a mention's text
 *     disables its input until the text is whole again (Undo). With
 *     `atomic`, Backspace just after a mention deletes all of it
 *   - each query dispatches `search` with { trigger, query } from the
 *     field, bubbling, debounced, as the command palette does: a host with
 *     too many rows to send fetches and re-renders them; rows that change
 *     while it is open are filtered again. From the field because the
 *     host's attributes, an x-on:search among them, are put on it
 *
 * The scope exposes `dsValue` (the text, for x-modelable), `dsReady`,
 * `dsOpen` and `dsCaret`, the box x-ds-anchor reads (shared/caret.js).
 */
import { fold, rank } from '../shared/rank.js';
import { caretBox } from '../shared/caret.js';

const SEARCH_DELAY = 200;
// How far back from the caret a trigger is looked for.
const REACH = 64;

export function mention(Alpine) {
    Alpine.data('dsMention', (options = {}) => {
        let control = null;

        return {
            dsValue: '',
            dsReady: false,
            dsOpen: false,
            dsCaret: null,

            init() {
                control = wire(this.$el, options, {
                    report: (value) => {
                        this.dsValue = value;
                    },
                    open: (open, caret) => {
                        if (caret) this.dsCaret = caret;
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

/** The template filled in, in one pass, as Support\Mention::token() does. */
const token = (template, trigger, label, id) => template.replace(/@|\blabel\b|\bid\b/g, (m) => (m === '@' ? trigger : m === 'label' ? label : id));

const WORD = /[\p{L}\p{N}_]/u;

/** Whether `text` stands in `value` as a whole, as Support\Mention::present() asks. */
function present(value, text) {
    for (let at = value.indexOf(text); text && at >= 0; at = value.indexOf(text, at + 1)) {
        if (!WORD.test(value[at - 1] ?? '') && !WORD.test(value[at + text.length] ?? '')) return true;
    }
    return false;
}

function wire(root, o, api) {
    const field = root.querySelector('[data-ds-mention-input]');
    const list = root.querySelector('[data-ds-mention-list]');
    const empty = root.querySelector('[data-ds-mention-empty]');
    const ids = root.querySelector('[data-ds-mention-ids]');
    const template = root.querySelector('[data-ds-mention-template]');
    const triggers = o.triggers ?? ['@'];
    const max = o.max ?? 8;

    let session = null;
    let active = null;
    let shown = [];
    let inserting = false;
    let timer = null;
    let sent = '';
    let open = false;

    const options = () => [...list.querySelectorAll('[data-ds-mention-option]')];
    const mentions = () => [...ids.querySelectorAll('input')];

    /** The trigger the caret is typing after, on this line: { trigger, start, query }. */
    function candidate() {
        const v = field.value;
        const caret = field.selectionStart;
        if (caret !== field.selectionEnd) return null;
        for (let i = caret - 1; i >= 0 && caret - i <= REACH; i--) {
            if (v[i] === '\n') return null;
            if (triggers.includes(v[i]) && (i === 0 || /[\s([{"'“‘]/.test(v[i - 1]))) return { trigger: v[i], start: i, query: v.slice(i + 1, caret) };
        }
        return null;
    }

    function setActive(option) {
        if (active && active !== option) active.setAttribute('aria-selected', 'false');
        active = option ?? null;
        if (active) {
            active.setAttribute('aria-selected', 'true');
            field.setAttribute('aria-activedescendant', active.id);
            active.scrollIntoView({ block: 'nearest' });
        } else {
            field.removeAttribute('aria-activedescendant');
        }
    }

    /* Hide, rank and order the rows for the query; read back how many show. */
    function arrange() {
        const q = fold(session.query.trim());
        const scored = options().map((option, i) => {
            const mine = option.dataset.dsTrigger === session.trigger;
            const score = !mine ? 0 : !q ? 1 : Math.max(rank(fold(option.dataset.dsLabel ?? ''), q), rank(fold(option.dataset.dsKeywords ?? ''), q) - 0.5);
            return [option, score, i];
        });
        scored.sort((a, b) => b[1] - a[1] || a[2] - b[2]);
        shown = [];
        scored.forEach(([option, score], i) => {
            option.hidden = score <= 0 || shown.length >= max;
            if (!option.hidden) shown.push(option);
            option.style.setProperty('order', String(i));
        });
        if (empty) empty.hidden = shown.length > 0 || list.getAttribute('aria-busy') === 'true';
        observer.takeRecords();
        return shown.length;
    }

    function show(caret = true) {
        if (!open) {
            open = true;
            setActive(null);
        }
        if (!shown.includes(active)) setActive(shown[0]);
        api.open(true, caret ? caretBox(field, session.start) : null);
        observer.takeRecords();
    }

    function close() {
        session = null;
        clearTimeout(timer);
        if (!open) return;
        open = false;
        setActive(null);
        api.open(false);
        observer.takeRecords();
    }

    /* After every edit and caret move while a trigger is being typed after. */
    function refresh() {
        const c = candidate();
        if (!session || !c || c.start !== session.start) {
            close();
            return;
        }
        session.query = c.query;
        if (c.query.length < (o.minChars ?? 0)) {
            if (open) {
                open = false;
                setActive(null);
                api.open(false);
            }
            return;
        }
        const count = arrange();
        if (!count && /\s$/.test(c.query) && list.getAttribute('aria-busy') !== 'true') {
            close();
            return;
        }
        show();
        search();
    }

    function search() {
        clearTimeout(timer);
        timer = setTimeout(() => {
            if (!session) return;
            const key = `${session.trigger}\n${session.query}`;
            if (key === sent) return;
            sent = key;
            field.dispatchEvent(new CustomEvent('search', { bubbles: true, detail: { trigger: session.trigger, query: session.query } }));
        }, SEARCH_DELAY);
    }

    /*
     * A mention whose text an edit broke posts no more. Disabled rather than
     * removed, so an Undo that brings the text back brings its id back too.
     */
    function reconcile() {
        for (const input of mentions()) input.disabled = field.disabled || !present(field.value, input.dataset.dsMentionText ?? '');
    }

    function remember(id, text) {
        if (mentions().some((input) => input.value === id && input.dataset.dsMentionText === text)) return;
        const input = template.content.firstElementChild.cloneNode(true);
        input.value = id;
        input.dataset.dsMentionText = text;
        ids.append(input);
    }

    /*
     * Text the host set: the mentions in it, found as Support\Mention::found()
     * finds them for the server's first paint (longest first, each found one
     * blanked so a shorter name inside it is not found again), post their ids.
     */
    function adopt() {
        let rest = field.value;
        const rows = options()
            .map((row) => ({ id: row.dataset.value ?? '', text: token(o.insert ?? '@label', row.dataset.dsTrigger ?? triggers[0], row.dataset.dsLabel ?? '', row.dataset.value ?? '') }))
            .sort((x, y) => y.text.length - x.text.length);
        const hits = [];
        for (const row of rows) {
            if (!row.text) continue;
            let at = -1;
            for (let i = rest.indexOf(row.text); i >= 0; i = rest.indexOf(row.text, i + 1)) {
                if (WORD.test(rest[i - 1] ?? '') || WORD.test(rest[i + row.text.length] ?? '')) continue;
                if (at < 0) at = i;
                rest = rest.slice(0, i) + '\0'.repeat(row.text.length) + rest.slice(i + row.text.length);
            }
            if (at >= 0) hits.push({ ...row, at });
        }
        for (const hit of hits.sort((x, y) => x.at - y.at)) remember(hit.id, hit.text);
    }

    function choose(option) {
        if (!session) return;
        const text = token(o.insert ?? '@label', session.trigger, option.dataset.dsLabel ?? '', option.dataset.value ?? '');
        const start = session.start;
        const end = start + 1 + session.query.length;
        const after = field.value[end];
        const add = text + (after === undefined || !/\s/.test(after) ? ' ' : '');
        close();

        field.focus();
        field.setSelectionRange(start, end);
        inserting = true;
        // The browser's own edit, so Undo takes it back; setRangeText where
        // there is none, which Undo does not see.
        let done = false;
        try {
            done = document.execCommand('insertText', false, add);
        } catch {
            done = false;
        }
        if (!done || field.value.slice(start, start + add.length) !== add) {
            field.setRangeText(add, start, end, 'end');
            field.dispatchEvent(new Event('input', { bubbles: true }));
        }
        inserting = false;

        remember(option.dataset.value ?? '', text);
        reconcile();
    }

    function onInput(event) {
        reconcile();
        api.report(field.value);
        if (inserting) return;
        const c = candidate();
        const typedTrigger = c && c.query === '' && event.inputType?.startsWith('insert') && (event.data ?? '').endsWith(c.trigger);
        if (c && (typedTrigger || (session && c.start === session.start))) session = { trigger: c.trigger, start: c.start, query: c.query };
        refresh();
    }

    function onKeyDown(event) {
        if (event.isComposing) return;
        if (open) {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                if (!shown.length) return;
                const at = shown.indexOf(active);
                setActive(shown[event.key === 'ArrowDown' ? (at + 1) % shown.length : (at <= 0 ? shown.length : at) - 1]);
                return;
            }
            if ((event.key === 'Enter' || event.key === 'Tab') && active && !event.shiftKey) {
                event.preventDefault();
                choose(active);
                return;
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                close();
                return;
            }
        }
        if (o.atomic && event.key === 'Backspace' && !event.ctrlKey && !event.metaKey && !event.altKey) {
            const caret = field.selectionStart;
            if (caret !== field.selectionEnd) return;
            const hit = mentions().filter((input) => !input.disabled).map((input) => input.dataset.dsMentionText ?? '').find((t) => t && field.value.slice(caret - t.length, caret) === t && !WORD.test(field.value[caret - t.length - 1] ?? ''));
            if (!hit) return;
            event.preventDefault();
            field.setSelectionRange(caret - hit.length, caret);
            let done = false;
            try {
                done = document.execCommand('delete');
            } catch {
                done = false;
            }
            if (!done) {
                field.setRangeText('', caret - hit.length, caret, 'end');
                field.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }
    }

    // The caret moved without an edit: an arrow key, a press, Home.
    function onCaret() {
        if (session) refresh();
    }

    function onScroll() {
        if (open && session) api.open(true, caretBox(field, session.start));
    }

    function onClick(event) {
        const option = event.target.closest('[data-ds-mention-option]');
        if (option && list.contains(option)) choose(option);
    }

    function onPointerMove(event) {
        const option = event.target.closest('[data-ds-mention-option]');
        if (option && option !== active && !option.hidden) setActive(option);
    }

    // A press in the list must not take focus out of the field.
    const onPointerDown = (event) => event.preventDefault();

    // Rows a host re-renders (a morph after `search`) are filtered again.
    const observer = new MutationObserver(() => {
        if (session && open) refresh();
    });
    observer.observe(list, { childList: true, subtree: true, attributes: true, attributeFilter: ['hidden', 'style', 'aria-busy', 'data-ds-label', 'data-ds-keywords'] });

    const listeners = [
        [field, 'input', onInput],
        [field, 'keydown', onKeyDown],
        [field, 'keyup', onCaret],
        [field, 'click', onCaret],
        [field, 'blur', close],
        [field, 'scroll', onScroll],
        [list, 'click', onClick],
        [list, 'pointermove', onPointerMove],
        [list, 'mousedown', onPointerDown],
    ];
    for (const [el, type, fn] of listeners) el.addEventListener(type, fn);

    return {
        value: () => field.value,
        set(value) {
            const text = String(value ?? '');
            if (text === field.value) return;
            field.value = text;
            close();
            adopt();
            reconcile();
        },
        teardown() {
            clearTimeout(timer);
            observer.disconnect();
            for (const [el, type, fn] of listeners) el.removeEventListener(type, fn);
        },
    };
}
