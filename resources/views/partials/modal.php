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
    'backdrop' => 'absolute inset-0 bg-scrim',

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
     */
    'trap' => <<<'JS'
            const focusable = [...$el.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            )].filter((node) => node.offsetParent !== null);
            if (!focusable.length) return;
            const at = focusable.indexOf(document.activeElement);
            const step = $event.shiftKey ? -1 : 1;
            focusable[(at + step + focusable.length) % focusable.length].focus();
        JS,

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
     */
    'focus' => fn (string $open, string $panel = '[data-ds-modal-panel]', string $first = '[autofocus]') => <<<JS
            if ({$open}) {
                \$el._returnFocusTo = document.activeElement;
                document.body.classList.add('overflow-hidden');
                \$nextTick(() => {
                    const panel = \$el.querySelector('{$panel}');
                    (panel.querySelector('{$first}') ?? panel).focus();
                });
            } else {
                document.body.classList.remove('overflow-hidden');
                \$el._returnFocusTo?.focus();
            }
        JS,
];
