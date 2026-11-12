<?php

/*
 * The tray recipe: the class strings ds::toolbar and ds::toggle-group are
 * drawn with, and the soft buttons inside them (ds::toolbar-button and
 * ds::toggle), which have to look alike because a toggle group is dropped
 * into a toolbar as often as it stands alone.
 *
 * Read through Support\Tray::recipe(), never copied. A plain PHP file under
 * resources/views for the reason partials/field.php is: a consumer's Tailwind
 * scans resources/views and nothing else, so every class here is a literal it
 * can see.
 */

$item = static fn (string $size, bool $iconOnly): string => implode(' ', [
    // `relative` puts the item above the highlight, which is the container's
    // first child and absolutely positioned: without it the highlight paints
    // over the item's words.
    'relative inline-flex shrink-0 items-center justify-center gap-1.5 rounded-control',
    'font-medium whitespace-nowrap select-none text-fg-body hover:text-fg',
    // On is a darker wash than the hover highlight, and the words go to full
    // strength, so a pressed item still reads as pressed under the pointer.
    'aria-pressed:bg-fg/10 aria-pressed:text-fg aria-checked:bg-fg/10 aria-checked:text-fg',
    'focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-focus-ring',
    'disabled:cursor-not-allowed disabled:opacity-50 aria-disabled:cursor-not-allowed aria-disabled:opacity-50',
    'motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter',
    match (true) {
        $size === 'sm' && $iconOnly => 'size-control-sm',
        $size === 'sm' => 'h-control-sm px-2.5 text-meta',
        $iconOnly => 'size-control-md',
        default => 'h-control-md px-3 text-body',
    },
]);

return [
    /*
     * The tray. Its radius is the item's plus the 4px of padding, so the
     * corners of an item at either end run parallel to the tray's. Muted
     * carries a transparent border so it is the same size as floating.
     */
    'tray' => static fn (string $variant, bool $vertical): string => implode(' ', [
        'group/glide relative inline-flex max-w-full gap-0.5 rounded-panel border p-1',
        $vertical ? 'flex-col items-stretch' : 'items-center',
        $variant === 'floating'
            ? 'border-border bg-surface shadow-overlay'
            : 'border-transparent bg-fg/5',
    ]),

    /*
     * A toggle group is a muted tray of its own when it stands alone, and
     * no tray at all inside a toolbar, where its toggles sit among the
     * toolbar's own items.
     */
    'group' => implode(' ', [
        'group/glide relative inline-flex max-w-full items-center gap-0.5 rounded-panel border border-transparent bg-fg/5 p-1',
        'in-data-ds-toolbar:border-0 in-data-ds-toolbar:bg-transparent in-data-ds-toolbar:p-0',
    ]),

    /*
     * The highlight x-ds-glide.hover moves. Hidden until the script shows
     * it, transparent until it is over an item. A press darkens it and
     * shrinks it a little; the item above it never moves, so its words stay
     * crisp. Only `motion-safe` slides: with reduced motion it jumps.
     */
    'highlight' => implode(' ', [
        'pointer-events-none absolute top-0 left-0 rounded-control bg-fg/5 opacity-0',
        'h-(--ds-glide-h) w-(--ds-glide-w) translate-x-(--ds-glide-x) translate-y-(--ds-glide-y)',
        'data-[visible]:opacity-100 data-[pressing]:scale-95 data-[pressing]:bg-fg/10',
        'motion-safe:transition-[translate,width,height,scale,background-color,opacity]',
        'motion-safe:duration-popover-enter motion-safe:ease-enter',
        // Appearing, it fades in where it is instead of sliding in from
        // wherever it was last hidden.
        'motion-safe:data-[instant]:transition-opacity',
    ]),

    'item' => $item,

    'separator' => static fn (bool $vertical): string => $vertical
        ? 'mx-1 my-1 h-px shrink-0 self-stretch bg-divider'
        : 'mx-1 h-5 w-px shrink-0 self-center bg-divider',
];
