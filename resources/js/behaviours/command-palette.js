/*
 * dsCommandPalette: a searchable list of actions and destinations in a modal
 * layer, behind ds::command-palette.
 *
 *   x-data="dsCommandPalette({ name: 'main', shortcut: 'mod+k', filter: true })"
 *
 * The markup is the view's, rendered complete by PHP: the modal layer
 * (partials/modal.php, shown by x-show="dsOpen"), the field marked
 * data-ds-command-input with role=combobox, the list data-ds-command-list
 * with role=listbox, the items data-ds-command-item (role=option, an id, a
 * label in data-ds-label, keywords in data-ds-keywords, a value, an action
 * name, aria-disabled), the groups data-ds-command-group, and the empty row
 * data-ds-command-empty inside a status region.
 *
 *   - It opens on `shortcut` anywhere on the page ("mod" is Cmd on a Mac,
 *     Ctrl elsewhere), and on an `open-command-palette` event at the window
 *     whose detail.name is this palette's name (both absent counts as a
 *     match); `close-command-palette` closes it. A shortcut with Ctrl or Cmd
 *     works while typing in another field, and toggles; one without them
 *     does nothing in a field. The key's default is prevented only when the
 *     palette acts on it.
 *   - The modal layer does the rest of being a dialog: focus in, the trap,
 *     Escape and the backdrop, the scroll lock, focus back out.
 *   - Focus stays in the field. The arrows (wrapping), Home and End move the
 *     active option, which the field names in aria-activedescendant and
 *     which carries aria-selected; disabled options are passed over. A
 *     pointer moving over an option makes it the active one.
 *   - Typing filters: case-insensitive, over the label and the keywords,
 *     ranked prefix, then word start, then anywhere, then the letters in
 *     order; keywords rank just below the label. Options are ranked inside
 *     their group, and groups by their best option, by CSS `order`, so the
 *     DOM is left as the host rendered it. A group with nothing left hides,
 *     and with nothing at all the empty row shows. `filter: false` leaves
 *     all of that to the host.
 *   - Typing also dispatches `search` with { query }, debounced, from the
 *     root, bubbling: the host's way to fetch and re-render the options. The
 *     options can change at any time; the active one is kept if it is still
 *     there, by id, else the first.
 *   - Enter, or a press, activates the active option: a link is followed (a
 *     click on it, so the host's own listeners and wire:navigate hear it),
 *     an option with an action dispatches that event with { value },
 *     bubbling, and either way the palette closes.
 *   - Escape with a query empties it and is stopped there; on an empty field
 *     it reaches the layer, which closes. Closing empties the query.
 *
 * The scope exposes `dsOpen` and nothing else.
 */

const EDITABLE = 'input, textarea, select, [contenteditable]:not([contenteditable="false"])';
const SEARCH_DELAY = 200;

export function commandPalette(Alpine) {
    Alpine.data('dsCommandPalette', (options = {}) => {
        let control = null;

        return {
            dsOpen: false,

            init() {
                control = wire(this.$el, options, {
                    isOpen: () => this.dsOpen,
                    setOpen: (open) => {
                        this.dsOpen = open;
                    },
                });
                this.$watch('dsOpen', (open) => control.changed(open));
            },

            destroy() {
                control?.teardown();
            },
        };
    });
}

function wire(root, options, api) {
    const input = root.querySelector('[data-ds-command-input]');
    const list = root.querySelector('[data-ds-command-list]');
    const empty = root.querySelector('[data-ds-command-empty]');
    const filtering = options.filter !== false;
    const combo = parse(options.shortcut);

    let active = null;
    let shown = [];
    let timer = null;
    let sent = '';

    const items = () => [...list.querySelectorAll('[data-ds-command-item]')];
    const usable = (item) => item.getAttribute('aria-disabled') !== 'true';

    function query() {
        return fold(input.value).trim();
    }

    function setActive(item, scroll = true) {
        if (active && active !== item) active.setAttribute('aria-selected', 'false');
        active = item ?? null;
        if (active) {
            active.setAttribute('aria-selected', 'true');
            input.setAttribute('aria-activedescendant', active.id);
            if (scroll) active.scrollIntoView({ block: 'nearest' });
        } else {
            input.removeAttribute('aria-activedescendant');
        }
        observer.takeRecords();
    }

    /*
     * Hide, rank and order the options for the query, then read back the
     * order they are drawn in: that, not the DOM, is what the arrows walk.
     */
    function arrange() {
        const q = query();
        const all = items();
        const scores = new Map();

        for (const item of all) {
            const score = !filtering || !q ? 1 : Math.max(rank(fold(item.dataset.dsLabel ?? item.textContent), q), rank(fold(item.dataset.dsKeywords ?? ''), q) - 0.5);
            scores.set(item, score);
            item.hidden = score <= 0;
        }

        // Rank among siblings; a group by the best option inside it.
        const groups = [...list.querySelectorAll('[data-ds-command-group]')];
        const best = new Map(groups.map((group) => [group, Math.max(0, ...all.filter((i) => i.closest('[data-ds-command-group]') === group).map((i) => scores.get(i)))]));
        for (const group of groups) group.hidden = best.get(group) <= 0;
        for (const box of [list, ...groups]) {
            const kids = [...box.children].filter((el) => scores.has(el) || best.has(el));
            order(kids, (el) => scores.get(el) ?? best.get(el), q);
        }

        shown = all.filter((item) => !item.hidden && item.getClientRects().length);
        shown.sort((a, b) => a.getBoundingClientRect().top - b.getBoundingClientRect().top);

        const busy = list.getAttribute('aria-busy') === 'true';
        if (empty) empty.hidden = busy || all.some((item) => !item.hidden);
    }

    // Our own writes are not changes the host made: their records are
    // dropped, here and in setActive. A morph that resets them is one.
    function refresh(keep = true) {
        arrange();
        const still = keep && active && shown.includes(active) ? active : keep && active ? shown.find((item) => item.id === active.id) : null;
        setActive(still && usable(still) ? still : shown.find(usable), false);
        observer.takeRecords();
    }

    function move(key) {
        const live = shown.filter(usable);
        if (!live.length) return;
        const at = live.indexOf(active);
        const to = key === 'Home' ? 0
            : key === 'End' ? live.length - 1
            : key === 'ArrowUp' ? (at <= 0 ? live.length - 1 : at - 1)
            : (at === live.length - 1 ? 0 : at + 1);
        setActive(live[to]);
    }

    function search() {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const q = input.value.trim();
            if (q === sent) return;
            sent = q;
            root.dispatchEvent(new CustomEvent('search', { bubbles: true, detail: { query: q } }));
        }, SEARCH_DELAY);
    }

    function clear() {
        if (!input.value) return;
        input.value = '';
        search();
    }

    function onKeyDown(event) {
        if (event.isComposing) return;
        if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
            event.preventDefault();
            move(event.key);
        } else if (event.key === 'Enter') {
            event.preventDefault();
            if (active && usable(active)) active.click();
        } else if (event.key === 'Escape' && input.value) {
            event.preventDefault();
            event.stopPropagation();
            clear();
            refresh(false);
        }
    }

    function onInput() {
        refresh(false);
        list.scrollTop = 0;
        search();
    }

    function onClick(event) {
        const item = event.target.closest('[data-ds-command-item]');
        if (!item || !list.contains(item)) return;
        if (!usable(item)) {
            event.preventDefault();
            return;
        }
        const action = item.dataset.dsAction;
        if (action) item.dispatchEvent(new CustomEvent(action, { bubbles: true, detail: { value: item.dataset.value ?? null } }));
        api.setOpen(false);
    }

    function onPointerMove(event) {
        const item = event.target.closest('[data-ds-command-item]');
        if (item && item !== active && usable(item)) setActive(item, false);
    }

    // A press on the list must not take focus out of the field.
    function onPointerDown(event) {
        event.preventDefault();
    }

    function onShortcut(event) {
        if (!combo || event.defaultPrevented || event.isComposing || !matches(combo, event)) return;
        if (!combo.chord && event.target.closest?.(EDITABLE)) return;
        event.preventDefault();
        api.setOpen(!api.isOpen());
    }

    const named = (event) => (event.detail?.name ?? null) === (options.name ?? null);
    const onOpen = (event) => named(event) && api.setOpen(true);
    const onClose = (event) => named(event) && api.setOpen(false);

    const observer = new MutationObserver(() => refresh());
    observer.observe(list, { childList: true, subtree: true, attributes: true, attributeFilter: ['hidden', 'style', 'aria-selected', 'aria-disabled', 'aria-busy', 'data-ds-label', 'data-ds-keywords'] });

    input.addEventListener('keydown', onKeyDown);
    input.addEventListener('input', onInput);
    list.addEventListener('click', onClick);
    list.addEventListener('pointermove', onPointerMove);
    list.addEventListener('mousedown', onPointerDown);
    window.addEventListener('keydown', onShortcut);
    window.addEventListener('open-command-palette', onOpen);
    window.addEventListener('close-command-palette', onClose);

    return {
        changed(open) {
            if (open) {
                // Drawn by now (x-show runs first), so the order can be read.
                requestAnimationFrame(() => refresh(false));
            } else {
                clear();
                refresh(false);
                setActive(null);
            }
        },
        teardown() {
            clearTimeout(timer);
            observer.disconnect();
            input.removeEventListener('keydown', onKeyDown);
            input.removeEventListener('input', onInput);
            list.removeEventListener('click', onClick);
            list.removeEventListener('pointermove', onPointerMove);
            list.removeEventListener('mousedown', onPointerDown);
            window.removeEventListener('keydown', onShortcut);
            window.removeEventListener('open-command-palette', onOpen);
            window.removeEventListener('close-command-palette', onClose);
        },
    };
}

// Lower case, accents off, one space: "Café  Menu" finds "cafe m".
function fold(text) {
    return String(text).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/\s+/g, ' ');
}

/*
 * 4 the text starts with the query, 3 a word in it does, 2 it is somewhere
 * inside, 1 its letters are there in order, 0 no match.
 */
function rank(text, q) {
    let at = text.indexOf(q);
    if (at === 0) return 4;
    const inside = at > 0;
    while (at > 0) {
        if (!/[\p{L}\p{N}]/u.test(text[at - 1])) return 3;
        at = text.indexOf(q, at + 1);
    }
    if (inside) return 2;
    const letters = q.replace(/ /g, '');
    let i = 0;
    for (const ch of text) if (ch === letters[i] && ++i === letters.length) return 1;
    return 0;
}

/*
 * CSS order among siblings, best first, ties in the host's order. Starts at
 * 1 so a group's heading, at 0 and first in the DOM, stays above its options.
 * Cleared with no query, which leaves the host's order exactly. An option the
 * host wrapped in an element of its own keeps its place: only the arrows'
 * order, read from the screen, has to be right.
 */
function order(nodes, score, q) {
    const ranked = nodes.map((node, i) => [node, score(node), i]).sort((a, b) => b[1] - a[1] || a[2] - b[2]);
    ranked.forEach(([node], i) => (q ? node.style.setProperty('order', String(i + 1)) : node.style.removeProperty('order')));
}

function parse(shortcut) {
    if (!shortcut) return null;
    const parts = String(shortcut).toLowerCase().split('+').map((p) => p.trim()).filter(Boolean);
    const key = parts.pop();
    if (!key) return null;
    const mac = /mac|iphone|ipad|ipod/i.test(navigator.userAgentData?.platform ?? navigator.platform ?? '');
    const want = { key, ctrl: false, meta: false, alt: false, shift: false };
    for (const part of parts) {
        if (part === 'mod') want[mac ? 'meta' : 'ctrl'] = true;
        else if (part === 'cmd' || part === 'meta') want.meta = true;
        else if (part === 'ctrl' || part === 'control') want.ctrl = true;
        else if (part === 'alt' || part === 'option') want.alt = true;
        else if (part === 'shift') want.shift = true;
    }
    want.chord = want.ctrl || want.meta;
    return want;
}

// Shift is only compared for a letter or a named key: "?" is typed with it.
function matches(combo, event) {
    if (String(event.key).toLowerCase() !== combo.key) return false;
    if (event.ctrlKey !== combo.ctrl || event.metaKey !== combo.meta || event.altKey !== combo.alt) return false;
    return combo.key.length === 1 && !/[a-z]/.test(combo.key) ? true : event.shiftKey === combo.shift;
}
