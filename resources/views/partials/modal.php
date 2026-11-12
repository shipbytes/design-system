<?php

/*
 * The modal layer: what ds::modal and ds::command-palette share to be a
 * modal dialog, so there is one of it. The backdrop and the panel, how they
 * enter and leave, the focus trap, and what happens to focus and scrolling
 * on open and on close.
 *
 * Read through Support\Modal::recipe(), never copied. A plain PHP file under
 * resources/views for the reason partials/field.php is: a consumer's Tailwind
 * scans resources/views and nothing else, so every class here is a literal it
 * can see. Every string is what ds::modal emitted before this file existed;
 * tests/ModalUnchangedTest holds the modal to it.
 */

return [
    /*
     * `scrim` stays dark in both themes. Deriving it from `fg` would make it
     * white in dark, lighting the page up instead of pushing it back.
     */
    'backdrop' => 'absolute inset-0 bg-scrim in-data-ds-stacked:opacity-0',

    'backdropTransition' => [
        'x-transition:enter' => 'motion-safe:transition motion-safe:duration-dialog-enter motion-safe:ease-enter',
        'x-transition:enter-start' => 'opacity-0',
        'x-transition:enter-end' => 'opacity-100',
        'x-transition:leave' => 'motion-safe:transition motion-safe:duration-dialog-leave motion-safe:ease-leave',
        'x-transition:leave-start' => 'opacity-100',
        'x-transition:leave-end' => 'opacity-0',
    ],

    'panelTransition' => [
        'x-transition:enter' => 'motion-safe:transition motion-safe:duration-dialog-enter motion-safe:ease-enter',
        'x-transition:enter-start' => 'opacity-0 scale-95',
        'x-transition:enter-end' => 'opacity-100 scale-100',
        'x-transition:leave' => 'motion-safe:transition motion-safe:duration-dialog-leave motion-safe:ease-leave',
        'x-transition:leave-start' => 'opacity-100 scale-100',
        'x-transition:leave-end' => 'opacity-0 scale-95',
    ],

    /*
     * Focus trap, written out rather than delegated to @alpinejs/focus.
     *
     * The plugin's `x-trap` is the usual answer, but Alpine SKIPS a directive
     * it has no handler for, so in a host that forgot the plugin the trap
     * silently does not exist, and `aria-modal="true"` becomes a lie a screen
     * reader believes. Ten lines inline depend on nothing. Bound to Tab with
     * `.prevent`, on the panel.
     *
     * A layer with another open above it leaves Tab alone: a dialog written
     * inside another one's body hears every Tab of the one on top as well,
     * and two traps moving focus in turn would skip a stop each press.
     */
    'trap' => <<<'JS'
            if ($el.closest('[data-ds-layer]')?.hasAttribute('data-ds-covered')) return;
            const focusable = [...$el.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            )].filter((node) => node.offsetParent !== null);
            if (!focusable.length) return;
            const at = focusable.indexOf(document.activeElement);
            const step = $event.shiftKey ? -1 : 1;
            focusable[(at + step + focusable.length) % focusable.length].focus();
        JS,

    /*
     * Escape, on the window, for the open-state expression: only the layer on
     * top closes. The event is marked as it is used, because the layer below
     * stops being covered the moment the top one's state changes, and a real
     * key press runs Alpine's effects between one listener and the next.
     */
    'escape' => fn (string $open) => "if ({$open} && !\$event.dsLayer && !\$el.hasAttribute('data-ds-covered')) { \$event.dsLayer = true; {$open} = false }",

    /*
     * On the panel: the dim a layer takes while another is open above it,
     * drawn by a pseudo-element over its content. Not a filter and not the
     * backdrop: a filter makes the panel the containing block of a dialog
     * written inside it, and the backdrop is under the panel, not over it.
     */
    'covered' => 'after:pointer-events-none after:absolute after:inset-0 after:bg-scrim after:opacity-0 data-ds-covered:after:opacity-50 motion-safe:after:transition-opacity motion-safe:after:duration-dialog-enter motion-safe:after:ease-enter',

    /*
     * The x-effect on the layer's root, given the open state's expression,
     * the panel's hook and what in it takes focus first.
     *
     * Open: remember what had focus, move focus into the panel (to the
     * element marked autofocus, else the panel itself; the palette names its
     * field instead, because the browser also acts on a real autofocus
     * attribute at page load), and stop the page behind from scrolling: a
     * background that scrolls under a modal is the single most common way
     * one feels broken.
     * Close: put focus back where it came from, or the reader is returned to
     * the top of the document with no idea where they were.
     *
     * The stack, kept in the DOM so there is one of it whichever overlays
     * are on the page and whether or not the package's script is: an open
     * layer's root carries data-ds-layer, its order of opening. The highest
     * is on top. The others are data-ds-covered (root and panel: the panel's
     * dim, Escape and Tab read it) and, unless the reader asked for less
     * motion or one holds another in its markup, scaled back. One opened over
     * another is data-ds-stacked, which drops its own backdrop (the page is
     * dimmed once) and is lifted one z-index above the highest below it, so
     * the order on screen is the order of opening, not of the source. Closing
     * a layer that is not on top moves nobody's focus, and hands what it
     * would have returned to on to the layer that was going to return into
     * it. The scroll lock goes with the last one.
     */
    'focus' => fn (string $open, string $panel = '[data-ds-modal-panel]', string $first = '[autofocus]') => <<<JS
            if ({$open}) {
                \$el._returnFocusTo = document.activeElement;
                const others = [...document.querySelectorAll('[data-ds-layer]')].filter((n) => n !== \$el);
                \$el.dataset.dsLayer = others.length ? Math.max(...others.map((n) => +n.dataset.dsLayer)) + 1 : 0;
                \$el.toggleAttribute('data-ds-stacked', others.length > 0);
                \$el.style.zIndex = others.length ? Math.max(...others.map((n) => +getComputedStyle(n).zIndex || 0)) + 1 : '';
                document.body.classList.add('overflow-hidden');
                \$nextTick(() => {
                    const panel = \$el.querySelector('{$panel}');
                    (panel.querySelector('{$first}') ?? panel).focus();
                });
            } else {
                const was = \$el.dataset.dsLayer;
                delete \$el.dataset.dsLayer;
                \$el.removeAttribute('data-ds-stacked');
                \$el.style.zIndex = '';
                const others = [...document.querySelectorAll('[data-ds-layer]')];
                others.forEach((n) => \$el.contains(n._returnFocusTo) && (n._returnFocusTo = \$el._returnFocusTo));
                if (!others.length) document.body.classList.remove('overflow-hidden');
                if (!others.some((n) => +n.dataset.dsLayer > was)) \$el._returnFocusTo?.focus();
            }
            const layers = [...document.querySelectorAll('[data-ds-layer]')];
            const top = Math.max(...layers.map((n) => +n.dataset.dsLayer));
            for (const n of new Set([\$el, ...layers])) {
                const covered = n.hasAttribute('data-ds-layer') && +n.dataset.dsLayer < top;
                const panel = n.querySelector('[role=dialog]');
                const back = covered && !layers.some((o) => o !== n && n.contains(o)) && !matchMedia('(prefers-reduced-motion: reduce)').matches;
                n.toggleAttribute('data-ds-covered', covered);
                panel?.toggleAttribute('data-ds-covered', covered);
                if (!panel || (panel.style.scale !== '') === back) continue;
                panel.style.transition = 'scale var(--ds-motion-duration-dialog-enter) var(--ds-motion-ease-enter)';
                panel.style.scale = back ? '0.94' : '';
                clearTimeout(panel._dsLayerTimer);
                panel._dsLayerTimer = setTimeout(() => (panel.style.transition = ''), 300);
            }
        JS,
];
