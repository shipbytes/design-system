<?php

/*
 * The menu recipe: the panel ds::dropdown and ds::context-menu draw their
 * items in, how it enters and leaves, and the arrow-key handler that makes
 * role="menu" true.
 *
 * Read through Support\Menu::recipe(), never copied. A plain PHP file under
 * resources/views for the reason partials/field.php is: a consumer's Tailwind
 * scans resources/views and nothing else, so every class here is a literal it
 * can see. Every string is what ds::dropdown emitted before this file existed.
 */

return [
    /*
     * `float`, not `overlay`: this hovers over the page, it does not block it.
     * The shadow scale says which of those a surface is doing.
     */
    'panel' => 'z-50 min-w-52 overflow-hidden rounded-control border border-border bg-surface py-1 shadow-float',

    'transition' => [
        'x-transition:enter' => 'motion-safe:transition motion-safe:duration-popover-enter motion-safe:ease-enter',
        'x-transition:enter-start' => 'opacity-0 scale-95',
        'x-transition:enter-end' => 'opacity-100 scale-100',
        'x-transition:leave' => 'motion-safe:transition motion-safe:duration-popover-leave motion-safe:ease-leave',
        'x-transition:leave-start' => 'opacity-100 scale-100',
        'x-transition:leave-end' => 'opacity-0 scale-95',
    ],

    /*
     * Arrow keys move between items, Home/End jump to the ends. role="menu" is a
     * PROMISE that this works: a menu that only responds to Tab is a keyboard
     * user being told one thing and given another. Bound to the four keys with
     * `.prevent`, on the panel.
     */
    'move' => <<<'JS'
            const items = [...$el.querySelectorAll('[role="menuitem"]:not([aria-disabled="true"])')];
            if (!items.length) return;
            const at = items.indexOf(document.activeElement);
            const to = $event.key === 'Home' ? 0
                : $event.key === 'End' ? items.length - 1
                : $event.key === 'ArrowUp' ? (at <= 0 ? items.length - 1 : at - 1)
                : (at === items.length - 1 ? 0 : at + 1);
            items[to].focus();
        JS,
];
