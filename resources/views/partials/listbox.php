<?php

/*
 * The listbox recipe: the popup list ds::combobox and ds::time-picker open
 * under their text field, its options, and the arrow-key
 * handler that walks it; and the option ds::command-palette lists, whose
 * focus never leaves its field.
 *
 * Read through Support\Listbox::recipe(), never copied. A plain PHP file under
 * resources/views for the reason partials/field.php is: a consumer's Tailwind
 * scans resources/views and nothing else, so every class here is a literal it
 * can see. Every string is what ds::combobox emitted before this file
 * existed; the time picker is the second field to need them, and a second
 * copy of an arrow-key handler is the kind that drifts.
 */

return [
    /*
     * Placed by its absolute classes, under the field, until x-ds-anchor
     * takes over with the script: then it flips above when there is no room
     * below and escapes a clipping ancestor. data-side is the anchor's.
     */
    'panel' => 'absolute z-50 mt-1 max-h-60 w-full origin-top data-[side=top]:origin-bottom overflow-y-auto rounded-control border border-border bg-surface py-1 shadow-float',

    /*
     * An option takes real focus as the arrows reach it, so its ring is drawn
     * inward: the list scrolls, and an outward ring would be cut off by it.
     */
    'option' => 'flex cursor-pointer items-center justify-between gap-2 px-3 py-2 text-body text-fg-body motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter hover:bg-surface-subtle hover:text-fg focus-visible:bg-surface-subtle focus-visible:text-fg focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus-ring aria-selected:font-medium aria-selected:text-fg',

    /*
     * An option in a list whose focus stays in its field (the command
     * palette): the field names it in aria-activedescendant and it carries
     * aria-selected, so the ring the option above draws on focus is drawn
     * here on aria-selected, the same ring in the same place. The text keeps
     * its weight as the active row moves, unlike a CHOSEN option's.
     */
    'activeOption' => 'mx-1.5 flex cursor-pointer items-center gap-3 rounded-control px-2.5 py-2 text-body text-fg-body aria-selected:bg-surface-subtle aria-selected:text-fg aria-selected:outline-2 aria-selected:-outline-offset-2 aria-selected:outline-focus-ring aria-disabled:cursor-not-allowed aria-disabled:opacity-50',

    /** The row an empty list shows instead: an empty list with no words reads as a broken control. */
    'empty' => 'px-3 py-2 text-body text-fg-muted',

    /*
     * The arrow keys walk the options ON SCREEN, so the handler reads the DOM
     * rather than any list in the state: once a filter is applied what is
     * shown is the only correct source. Bound to ArrowDown, ArrowUp, Home and
     * End with `.prevent`, on the list. Wraps at both ends.
     */
    'move' => <<<'JS'
            const items = [...$el.querySelectorAll('[role="option"]')];
            if (!items.length) return;
            const at = items.indexOf(document.activeElement);
            const to = $event.key === 'Home' ? 0
                : $event.key === 'End' ? items.length - 1
                : $event.key === 'ArrowUp' ? (at <= 0 ? items.length - 1 : at - 1)
                : (at === items.length - 1 ? 0 : at + 1);
            items[to].focus();
        JS,
];
