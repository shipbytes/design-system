/*
 * A roving tabindex: one tab stop for a set of items, and the arrow keys to
 * move between them. The keyboard model of the ARIA toolbar and of a radio
 * group, shared by dsToolbar and dsToggleGroup. Registers nothing.
 *
 *   const stop = roving(root, {
 *       items: () => [...],            the items, in order, read on every key
 *       axis: () => 'horizontal',      'horizontal' | 'vertical' | 'both'
 *       current: () => el | null,      where the tab stop starts (the checked
 *                                      radio); the first usable item otherwise
 *       moved: (item) => {},           after a key moved focus to `item`
 *   });
 *   stop();                            remove the listeners
 *
 * The arrows wrap, skip disabled items and follow the reading direction: in a
 * right-to-left page ArrowLeft is the next item. Home and End go to the ends.
 * The tab stop is the last item focused, however it was focused, so Tab out
 * and back in returns to it.
 *
 * Without the script every item is a tab stop of its own: slower, and still
 * every item reachable.
 */

const UNUSABLE = ':is(:disabled, [aria-disabled="true"])';

export function roving(root, options) {
    const axis = options.axis ?? (() => 'horizontal');
    const current = options.current ?? (() => null);
    const moved = options.moved ?? (() => {});

    let stop = null;

    const enabled = (item) => !item.matches(UNUSABLE);
    // Not shown is not reachable by the arrows: an item the host has hidden is
    // skipped as a disabled one is. Only for the arrows, though, and not for
    // the tab stop, which is settled while a toolbar in a closed modal has no
    // boxes at all.
    const usable = (item) => enabled(item) && item.getClientRects().length > 0;

    function settle() {
        const all = options.items();
        const live = all.filter(enabled);
        if (!stop || !live.includes(stop)) {
            const preferred = current();
            stop = preferred && live.includes(preferred) ? preferred : live[0] ?? null;
        }
        for (const item of all) {
            const want = item === stop ? '0' : '-1';
            // Written only when it differs: the observer below hears every
            // write, and an unchanged one would wake it for nothing.
            if (item.getAttribute('tabindex') !== want) item.setAttribute('tabindex', want);
        }
    }

    function step(key) {
        const along = axis();
        const rtl = getComputedStyle(root).direction === 'rtl';
        const across = along !== 'vertical';
        const down = along !== 'horizontal';

        if (key === 'Home') return 'first';
        if (key === 'End') return 'last';
        if (across && key === 'ArrowRight') return rtl ? -1 : 1;
        if (across && key === 'ArrowLeft') return rtl ? 1 : -1;
        if (down && key === 'ArrowDown') return 1;
        if (down && key === 'ArrowUp') return -1;
        return null;
    }

    function onKeyDown(event) {
        if (event.defaultPrevented || event.altKey || event.ctrlKey || event.metaKey) return;

        const live = options.items().filter(usable);
        const at = live.indexOf(event.target);
        if (at < 0) return;

        const by = step(event.key);
        if (by === null) return;

        event.preventDefault();
        const to = by === 'first' ? 0
            : by === 'last' ? live.length - 1
            : (at + by + live.length) % live.length;

        stop = live[to];
        settle();
        live[to].focus();
        moved(live[to]);
    }

    function onFocusIn(event) {
        if (!options.items().includes(event.target)) return;
        stop = event.target;
        settle();
    }

    root.addEventListener('keydown', onKeyDown);
    root.addEventListener('focusin', onFocusIn);

    // Items a host adds or disables later (a Livewire update) join the set
    // with the right tabindex rather than as extra tab stops.
    const observer = new MutationObserver(settle);
    observer.observe(root, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'aria-disabled', 'hidden'] });

    settle();

    return () => {
        observer.disconnect();
        root.removeEventListener('keydown', onKeyDown);
        root.removeEventListener('focusin', onFocusIn);
    };
}
