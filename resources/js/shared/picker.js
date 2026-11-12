/*
 * A searchable list of options in a dsPopover panel, the way
 * ds::team-switcher and ds::phone-input open one: its keys, its filter and
 * its choosing. Registers nothing.
 *
 *   const list = picker(root, { option, search, empty, score, choose });
 *
 * `option`, `search` and `empty` are selectors inside root. Each option sits
 * in an item of its own (an li), which is what is hidden and ordered.
 * `score(option, q)` ranks an option against the folded query, above 0 to be
 * shown; `choose(option)` is called on Enter, Space or a press.
 *
 *   - ArrowDown and ArrowUp move between the options shown, wrapping, and
 *     Home and End go to the ends; ArrowDown from the field enters the list,
 *     ArrowUp from its first option goes back to the field. Enter in the
 *     field chooses the first match
 *   - typing in the field filters, best first, by CSS `order`, so the DOM
 *     stays as rendered for a morph; the empty row shows when nothing is left
 *   - list.reset() empties the field and shows every option, for an opening
 *
 * Everything is read from the DOM on every event, so options re-rendered by
 * a morph, or added later, are simply there.
 */
import { fold } from './rank.js';

export function picker(root, { option, search, empty, score, choose }) {
    const find = (selector) => root.querySelector(selector);
    const all = () => [...root.querySelectorAll(option)];
    const item = (o) => o.closest('li') ?? o;
    const order = (o) => Number(item(o).style.order || 0);
    const shown = () => all().filter((o) => !item(o).hidden).sort((a, b) => order(a) - order(b));

    function filter() {
        const q = fold((find(search)?.value ?? '').trim());
        const scored = all().map((o, i) => [o, q ? score(o, q) : 1, i]);
        scored.sort((a, b) => b[1] - a[1] || a[2] - b[2]);
        scored.forEach(([o, s], i) => {
            item(o).hidden = s <= 0;
            if (q) item(o).style.setProperty('order', String(i + 1));
            else item(o).style.removeProperty('order');
        });
        const none = find(empty);
        if (none) none.hidden = scored.some(([, s]) => s > 0);
    }

    function reset() {
        const field = find(search);
        if (field) field.value = '';
        filter();
    }

    const onClick = (event) => {
        const o = event.target.closest(option);
        if (o && root.contains(o)) choose(o);
    };

    const onKeyDown = (event) => {
        const field = find(search);
        const list = shown();
        if (field && event.target === field) {
            if (event.key === 'ArrowDown' && list.length) {
                event.preventDefault();
                list[0].focus();
            } else if (event.key === 'Enter') {
                event.preventDefault();
                list[0]?.click();
            }
            return;
        }
        const at = list.indexOf(event.target);
        if (at < 0) return;
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            list[at].click();
            return;
        }
        const keys = { Home: 0, End: list.length - 1, ArrowDown: (at + 1) % list.length, ArrowUp: at - 1 };
        if (!(event.key in keys)) return;
        event.preventDefault();
        const to = keys[event.key];
        (to < 0 ? field ?? list[list.length - 1] : list[to]).focus();
    };

    const onInput = (event) => {
        if (event.target.matches?.(search)) filter();
    };

    root.addEventListener('click', onClick);
    root.addEventListener('keydown', onKeyDown);
    root.addEventListener('input', onInput);

    return {
        filter,
        reset,
        teardown() {
            root.removeEventListener('click', onClick);
            root.removeEventListener('keydown', onKeyDown);
            root.removeEventListener('input', onInput);
        },
    };
}
