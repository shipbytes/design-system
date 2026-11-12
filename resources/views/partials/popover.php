<?php

/*
 * The popover recipe: the floating surface ds::popover and ds::hover-card are
 * drawn with, the widths it comes in, how it enters and leaves, and where it
 * sits when the positioning script has not run.
 *
 * Read through Support\Popover::recipe(), never copied. A plain PHP file under
 * resources/views for the reason partials/field.php is: a consumer's Tailwind
 * scans resources/views and nothing else, so every class here is a literal it
 * can see.
 */

return [
    /*
     * `float`, not `overlay`: it hovers over the page and does not block it,
     * the same elevation as ds::dropdown. rounded-panel rather than the
     * dropdown's rounded-control, because this holds arbitrary content (a
     * form, a profile) and reads as a small card, not a list.
     *
     * The colour is set here as well as the ground: a panel that leaves its
     * text to be inherited takes the browser's black in dark mode, which is
     * how the 1.4:1 row in the contrast audit's history happened.
     */
    'surface' => implode(' ', [
        'z-50 rounded-panel border border-border bg-surface p-4 text-start text-body text-fg-body shadow-float outline-hidden',
        // The origin follows the side x-ds-anchor actually took, so a panel
        // that flipped above its trigger grows upward from it.
        'origin-top data-[side=top]:origin-bottom data-[side=left]:origin-right data-[side=right]:origin-left',
    ]),

    /*
     * Fixed widths, so a panel does not resize as its content changes, and
     * never wider than the window less the 8px the anchor keeps clear.
     */
    'widths' => [
        'sm' => 'w-56 max-w-[calc(100vw-1rem)]',
        'md' => 'w-72 max-w-[calc(100vw-1rem)]',
        'lg' => 'w-96 max-w-[calc(100vw-1rem)]',
    ],

    /*
     * x-transition's attributes, popover-sized and motion-safe: with reduced
     * motion the panel is simply there, and simply gone.
     */
    'transition' => [
        'x-transition:enter' => 'motion-safe:transition motion-safe:duration-popover-enter motion-safe:ease-enter',
        'x-transition:enter-start' => 'opacity-0 scale-95',
        'x-transition:enter-end' => 'opacity-100 scale-100',
        'x-transition:leave' => 'motion-safe:transition motion-safe:duration-popover-leave motion-safe:ease-leave',
        'x-transition:leave-start' => 'opacity-100 scale-100',
        'x-transition:leave-end' => 'opacity-0 scale-95',
    ],

    /*
     * Where the panel sits before x-ds-anchor takes over, as absolute
     * positions against the component's own relative root. A closed set of
     * literal strings, so the scanner sees them. The anchor writes inline
     * style over all of it, the margin and the centring translate included.
     */
    'placements' => [
        'bottom-start' => 'absolute top-full left-0 mt-2',
        'bottom' => 'absolute top-full left-1/2 mt-2 -translate-x-1/2',
        'bottom-end' => 'absolute top-full right-0 mt-2',
        'top-start' => 'absolute bottom-full left-0 mb-2',
        'top' => 'absolute bottom-full left-1/2 mb-2 -translate-x-1/2',
        'top-end' => 'absolute bottom-full right-0 mb-2',
        'left-start' => 'absolute right-full top-0 mr-2',
        'left' => 'absolute right-full top-1/2 mr-2 -translate-y-1/2',
        'left-end' => 'absolute right-full bottom-0 mr-2',
        'right-start' => 'absolute left-full top-0 ml-2',
        'right' => 'absolute left-full top-1/2 ml-2 -translate-y-1/2',
        'right-end' => 'absolute left-full bottom-0 ml-2',
    ],
];
