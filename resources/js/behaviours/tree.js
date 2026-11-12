/*
 * dsTree: the keyboard, the opening and closing, and the choosing of
 * ds::tree, after the WAI-ARIA tree pattern.
 *
 *   x-data="dsTree({ select: 'none' | 'single' | 'multiple' })"
 *
 * The markup is the component's, rendered complete by PHP: rows marked
 * data-ds-tree-item, role=treeitem, with aria-level, -setsize and -posinset
 * already counted, aria-expanded on a parent, aria-selected or aria-checked
 * as the mode has it, and each parent's children in the row's sibling
 * ul[data-ds-tree-group], `hidden` when closed. Nothing here adds a class:
 * the chevron, the selection and the checkboxes are drawn from those
 * attributes in CSS.
 *
 * One tab stop for the tree, kept where the reader last was. Then:
 *
 *   Down / Up        the next or previous row that is showing
 *   Right            a closed parent opens; an open one moves to its first child
 *   Left             an open parent closes; anything else moves to its parent
 *                    (the two swap on a right-to-left page)
 *   Home / End       the first and last rows showing
 *   a letter         the next row whose words start with it; typed quickly,
 *                    the letters run together
 *   *                opens every parent beside this one
 *   Enter            follows a link, selects (single), checks (multiple), or
 *                    opens and closes a parent (none)
 *   Space            the same, except on a link
 *
 * A press on a row does what Enter does; a press on its chevron only opens or
 * closes it. Disabled rows can be focused and opened but never chosen.
 *
 * Opening or closing a parent dispatches `expand` or `collapse` from its row,
 * bubbling, with `{ value }`: a host can load the children of an item drawn
 * `expandable` when it hears it.
 *
 * In a checkbox tree a parent's box is worked out from its leaves, all,
 * some (aria-checked="mixed") or none; checking a parent checks every
 * enabled leaf under it. A checked leaf's hidden input is enabled, so it
 * posts; the single selection writes the tree's one hidden input.
 *
 * Two-way binding goes through `dsValue`: the selected value (single) or the
 * checked leaves' values (multiple). The view puts x-modelable="dsValue" on
 * the root beside the host's x-model or wire:model. The scope exposes
 * `dsValue` and nothing else.
 */

const TYPE_AHEAD_FOR = 500;

export function tree(Alpine) {
    Alpine.data('dsTree', (options = {}) => {
        let teardown = null;

        return {
            dsValue: null,

            init() {
                const control = wire(this.$el, options.select ?? 'none', (value) => {
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

function wire(root, select, write) {
    const mine = (el) => el.closest('[data-ds-tree]') === root;
    const rows = () => [...root.querySelectorAll('[data-ds-tree-item]')].filter(mine);
    const showing = (row) => !row.closest('[data-ds-tree-group][hidden]') || !root.contains(row.closest('[data-ds-tree-group][hidden]'));
    const shown = () => rows().filter(showing);

    const isParent = (row) => row.hasAttribute('aria-expanded');
    const isOpen = (row) => row.getAttribute('aria-expanded') === 'true';
    const isDisabled = (row) => row.getAttribute('aria-disabled') === 'true';
    const isLink = (row) => row.tagName === 'A';
    const valueOf = (row) => row.dataset.dsTreeValue ?? '';
    const groupOf = (row) => row.parentElement.querySelector(':scope > [data-ds-tree-group]');
    const parentOf = (row) => {
        const group = row.parentElement.parentElement?.closest('[data-ds-tree-group]');
        if (!group || !root.contains(group)) return null;
        return group.parentElement.querySelector(':scope > [data-ds-tree-item]');
    };
    const childrenOf = (row) => [...(groupOf(row)?.children ?? [])]
        .map((li) => li.querySelector(':scope > [data-ds-tree-item]'))
        .filter(Boolean);
    const below = (row) => [...(groupOf(row)?.querySelectorAll('[data-ds-tree-item]') ?? [])];
    const labelOf = (row) => (row.querySelector('[data-ds-tree-label]')?.textContent ?? '').trim().toLowerCase();

    let stop = null;
    let typed = '';
    let typedAt = 0;

    // ---------------------------------------------------------- the tab stop

    function settle(preferred = null) {
        const all = rows();
        const visible = all.filter(showing);
        let next = preferred ?? stop;

        if (!next || !all.includes(next)) {
            next = all.find((r) => r.getAttribute('aria-current') === 'page')
                ?? all.find((r) => r.getAttribute('aria-selected') === 'true')
                ?? visible[0] ?? null;
        }
        // A stop inside a closed parent moves up to the nearest one showing.
        while (next && !showing(next)) next = parentOf(next) ?? visible[0] ?? null;

        stop = next;
        for (const row of all) {
            const want = row === stop ? '0' : '-1';
            if (row.getAttribute('tabindex') !== want) row.setAttribute('tabindex', want);
        }
    }

    function focus(row) {
        if (!row) return;
        settle(row);
        row.focus();
    }

    // ------------------------------------------------------ open and close

    function setOpen(row, open, { announce = true } = {}) {
        if (!isParent(row) || isOpen(row) === open) return;
        row.setAttribute('aria-expanded', open ? 'true' : 'false');
        const group = groupOf(row);
        if (group) group.hidden = !open;

        if (!open && group?.contains(document.activeElement)) focus(row);
        if (!open && stop && group?.contains(stop)) settle(row);

        if (announce) {
            row.dispatchEvent(new CustomEvent(open ? 'expand' : 'collapse', { bubbles: true, detail: { value: valueOf(row) } }));
        }
    }

    // ---------------------------------------------------------- choosing

    function selectRow(row) {
        if (isDisabled(row)) return;
        for (const r of rows()) {
            if (r.hasAttribute('aria-selected')) r.setAttribute('aria-selected', r === row ? 'true' : 'false');
        }
        writeInput(valueOf(row));
        write(valueOf(row));
    }

    function writeInput(value) {
        const input = [...root.querySelectorAll('input[data-ds-tree-field]')].find(mine);
        if (input) input.value = value ?? '';
    }

    const leaves = (within) => within.filter((r) => (r.dataset.dsTreeCheck ?? '').startsWith('leaf'));

    function check(row) {
        if (isDisabled(row)) return;
        if (row.dataset.dsTreeCheck === 'leaf') {
            row.setAttribute('aria-checked', row.getAttribute('aria-checked') === 'true' ? 'false' : 'true');
        } else {
            // On while any leaf the reader can change is off, else off: judged
            // by the enabled leaves alone, or a parent over a disabled,
            // unchecked leaf would stay mixed and could never be cleared.
            const under = leaves(below(row)).filter((l) => l.dataset.dsTreeCheck === 'leaf');
            const on = under.some((l) => l.getAttribute('aria-checked') !== 'true');
            for (const leaf of under) leaf.setAttribute('aria-checked', on ? 'true' : 'false');
        }

        derive();
        write(checkedValues());
    }

    // Every parent's box from the leaves under it, deepest first, and every
    // leaf's input from its box.
    function derive() {
        const all = rows();
        for (const row of [...all].reverse()) {
            if (row.dataset.dsTreeCheck !== 'parent') continue;
            const under = leaves(below(row));
            const on = under.filter((l) => l.getAttribute('aria-checked') === 'true').length;
            const state = under.length === 0 ? row.getAttribute('aria-checked') : on === 0 ? 'false' : on === under.length ? 'true' : 'mixed';
            row.setAttribute('aria-checked', state);
        }
        for (const leaf of leaves(all)) {
            const input = leaf.parentElement.querySelector(':scope > [data-ds-tree-input]');
            if (input) input.disabled = leaf.getAttribute('aria-checked') !== 'true';
        }
    }

    const checkedValues = () => leaves(rows()).filter((l) => l.getAttribute('aria-checked') === 'true').map(valueOf);

    // What the reader did to a row: Enter, Space without a link, or a press.
    function act(row) {
        if (select === 'single') return selectRow(row);
        if (select === 'multiple') return check(row);
        if (isParent(row)) setOpen(row, !isOpen(row));
    }

    // ---------------------------------------------------------- the keys

    function step(row, by) {
        const list = shown();
        const at = list.indexOf(row);
        return list[at + by] ?? null;
    }

    function typeAhead(row, key) {
        const now = Date.now();
        typed = now - typedAt > TYPE_AHEAD_FOR ? key : typed + key;
        typedAt = now;

        // The same letter again cycles through the rows that start with it.
        const same = [...typed].every((c) => c === typed[0]);
        const wanted = same ? typed[0] : typed;
        const list = shown();
        const at = list.indexOf(row);
        const from = same || typed.length === 1 ? at + 1 : at;
        const order = [...list.slice(from), ...list.slice(0, from)];
        return order.find((r) => labelOf(r).startsWith(wanted)) ?? null;
    }

    function onKeyDown(event) {
        const row = event.target.closest?.('[data-ds-tree-item]');
        if (!row || !mine(row) || event.target !== row) return;
        if (event.altKey || event.ctrlKey || event.metaKey) return;

        const rtl = getComputedStyle(root).direction === 'rtl';
        const inward = rtl ? 'ArrowLeft' : 'ArrowRight';
        const outward = rtl ? 'ArrowRight' : 'ArrowLeft';
        const key = event.key;

        if (key === 'ArrowDown' || key === 'ArrowUp') {
            event.preventDefault();
            focus(step(row, key === 'ArrowDown' ? 1 : -1) ?? row);
        } else if (key === 'Home' || key === 'End') {
            event.preventDefault();
            const list = shown();
            focus(key === 'Home' ? list[0] : list[list.length - 1]);
        } else if (key === inward) {
            event.preventDefault();
            if (!isParent(row)) return;
            if (!isOpen(row)) setOpen(row, true);
            else focus(childrenOf(row)[0] ?? row);
        } else if (key === outward) {
            event.preventDefault();
            if (isParent(row) && isOpen(row)) setOpen(row, false);
            else focus(parentOf(row) ?? row);
        } else if (key === '*') {
            event.preventDefault();
            const siblings = [...row.parentElement.parentElement.children]
                .map((li) => li.querySelector(':scope > [data-ds-tree-item]'))
                .filter(Boolean);
            for (const sibling of siblings) setOpen(sibling, true);
        } else if (key === 'Enter') {
            // A link follows itself, natively.
            if (isLink(row)) return;
            event.preventDefault();
            act(row);
        } else if (key === ' ') {
            if (isLink(row)) return;
            event.preventDefault();
            act(row);
        } else if (key.length === 1 && key.trim() !== '') {
            const found = typeAhead(row, key.toLowerCase());
            if (found) {
                event.preventDefault();
                focus(found);
            }
        }
    }

    function onClick(event) {
        const row = event.target.closest?.('[data-ds-tree-item]');
        if (!row || !mine(row)) return;

        settle(row);

        if (event.target.closest('[data-ds-tree-toggle]') && isParent(row)) {
            // The chevron opens and closes, and never follows the link.
            event.preventDefault();
            setOpen(row, !isOpen(row));
            return;
        }
        if (isLink(row)) return;
        act(row);
    }

    function onFocusIn(event) {
        const row = event.target.closest?.('[data-ds-tree-item]');
        if (row && mine(row) && row !== stop) settle(row);
    }

    root.addEventListener('keydown', onKeyDown);
    root.addEventListener('click', onClick);
    root.addEventListener('focusin', onFocusIn);

    // Rows a host adds later (children loaded on `expand`, a Livewire
    // update) join the one tab stop rather than each being one.
    const observer = new MutationObserver(() => settle());
    observer.observe(root, { childList: true, subtree: true });

    settle();

    return {
        value() {
            if (select === 'single') {
                const chosen = rows().find((r) => r.getAttribute('aria-selected') === 'true');
                return chosen ? valueOf(chosen) : null;
            }
            if (select === 'multiple') return checkedValues();
            return null;
        },

        // The host set it: redraw, and say nothing.
        set(value) {
            if (select === 'single') {
                const wanted = value === null || value === undefined ? null : String(value);
                for (const r of rows()) {
                    if (r.hasAttribute('aria-selected')) r.setAttribute('aria-selected', valueOf(r) === wanted ? 'true' : 'false');
                }
                writeInput(wanted);
            } else if (select === 'multiple') {
                const wanted = new Set((Array.isArray(value) ? value : []).map(String));
                for (const leaf of leaves(rows())) leaf.setAttribute('aria-checked', wanted.has(valueOf(leaf)) ? 'true' : 'false');
                derive();
            }
        },

        teardown() {
            observer.disconnect();
            root.removeEventListener('keydown', onKeyDown);
            root.removeEventListener('click', onClick);
            root.removeEventListener('focusin', onFocusIn);
        },
    };
}
