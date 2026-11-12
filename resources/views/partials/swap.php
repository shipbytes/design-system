<?php

/*
 * The swap recipe: two faces of one control in one grid cell, the one at
 * rest and the one that replaces it, crossfading with a short slide.
 * ds::copy-button ("Copy" to "Copied") and ds::swap-button ("Save" to
 * "Saved", "Follow" to "Following") draw their faces with it.
 *
 * One cell, so the control is as wide as the wider face from the first
 * paint and nothing beside it moves on the swap. The face on show is chosen
 * by a data-ds-swapped attribute on an ancestor marked group/swap, which the
 * server renders and the script keeps in step: a class lookup done in PHP
 * cannot follow Alpine state (see CLAUDE.md). The hidden face is also
 * `invisible` once its fade is over, which takes it out of the accessibility
 * tree and out of find-in-page; the views add aria-hidden and inert on top.
 *
 * Read through Support\Swap::recipe(), never copied. A plain PHP file under
 * resources/views so a consumer's Tailwind sees every class.
 */

return [
    'stack' => 'grid',

    // visibility is in the list so it flips at the end of a fade out and the
    // start of a fade in, which is what lets an invisible face crossfade.
    'rest' => 'col-start-1 row-start-1 motion-safe:transition-[opacity,translate,visibility] motion-safe:duration-popover-enter motion-safe:ease-enter '
        .'group-data-ds-swapped/swap:invisible group-data-ds-swapped/swap:opacity-0 group-data-ds-swapped/swap:-translate-y-1',

    'swapped' => 'col-start-1 row-start-1 motion-safe:transition-[opacity,translate,visibility] motion-safe:duration-popover-enter motion-safe:ease-enter '
        .'invisible opacity-0 translate-y-1 group-data-ds-swapped/swap:visible group-data-ds-swapped/swap:opacity-100 group-data-ds-swapped/swap:translate-y-0',
];
