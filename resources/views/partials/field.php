<?php

/*
 * The field recipe: the class strings ds::input is drawn with, for every
 * component that has to look like it.
 *
 * Read through Support\Field::recipe(), never copied. ds::input, otp-input,
 * tag-input and number-field all take their shell, control, label and message
 * classes from here, so a change to how a field looks is one change. Before
 * 2.0 the input was the only field and owned these strings itself; the first
 * component to need them beside it would otherwise have been a second copy
 * that drifts.
 *
 * A plain PHP file under resources/views, not a class in src/, because a
 * consumer's Tailwind scans resources/views and nothing else (see CLAUDE.md,
 * "Tailwind does not scan vendor/"). Every class here is a literal, so the
 * scanner sees it. Moved to src/ it would compile in this repo and vanish in
 * every app.
 *
 * Every string below is exactly what ds::input emitted in 1.x, in the same
 * order, empty segments included: InputUnchangedTest holds its output byte for
 * byte to a fixture written before this file existed.
 */

$padding = static fn (string $size): string => match ($size) {
    // 32px at every breakpoint: 24px of line, 3px each side, 1px border. The
    // text stays 16px below sm, because iOS zooms the page on a smaller field.
    'sm' => 'px-[calc(--spacing(2.5)-1px)] py-[calc(--spacing(1)-1px)]',
    // calc(step - 1px) absorbs the border so the control lands on the
    // 44px/36px grid rather than 2px over it.
    default => 'px-[calc(--spacing(3.5)-1px)] py-[calc(--spacing(2.5)-1px)] '
        .'sm:px-[calc(--spacing(3)-1px)] sm:py-[calc(--spacing(1.5)-1px)]',
};

$text = 'text-body-touch text-fg placeholder:text-fg-muted sm:text-body';

$border = static fn (bool $error, bool $readonly): string => match (true) {
    $error => 'border-danger hover:border-danger',
    $readonly => 'border-border',
    default => 'border-border hover:border-fg/20',
};

// Readonly is muted, not faded like disabled: the value is still there to be
// read and copied, and fg-muted holds AA on surface-subtle.
$tint = static fn (bool $disabled, bool $readonly): string => $disabled
    ? 'text-fg-subtle'
    : ($readonly ? 'text-fg-muted' : '');

return [
    'label' => 'block text-body font-medium text-fg',

    /*
     * The Catalyst two-layer construction, kept exactly.
     *
     *   ::before  inset by 1px, carries the fill and the shadow, at a radius
     *             one pixel tighter so it nests inside the outline
     *   ::after   at inset 0, carries the focus ring, drawn INWARD
     *
     * Drawing the ring inward is the whole point: the box never changes size on
     * focus, so nothing reflows and the fields below do not shift. A plain
     * `outline` would.
     *
     * `flat` is disabled or readonly: the subtle fill and no shadow.
     */
    'shell' => static fn (bool $flat): string => implode(' ', [
        'relative block w-full',
        'before:absolute before:inset-px before:rounded-[calc(var(--ds-radius-control)-1px)]',
        'before:bg-surface before:shadow-raised',
        'after:pointer-events-none after:absolute after:inset-0 after:rounded-control',
        'after:ring-transparent after:ring-inset',
        'sm:focus-within:after:ring-2 sm:focus-within:after:ring-focus-ring',
        $flat ? 'before:bg-surface-subtle before:shadow-none' : '',
    ]),

    /** The control itself: one element that takes text, inside the shell. */
    'control' => static fn (bool $error, bool $readonly, bool $disabled, string $size = 'md'): string => implode(' ', [
        'relative block w-full appearance-none rounded-control bg-transparent',
        $padding($size),
        $text,
        'border focus:outline-hidden',
        $border($error, $readonly),
        $tint($disabled, $readonly),
    ]),

    /*
     * The pieces of the control, for a field that is not one element: the tag
     * input's frame holds chips and a borderless text box, and has to look like
     * the control while being laid out as a flex row.
     */
    'padding' => $padding,
    'text' => $text,
    'border' => $border,
    'tint' => $tint,

    'error' => 'mt-1.5 flex items-start gap-1.5 text-meta text-danger',
    'help' => 'mt-1.5 text-meta text-fg-muted',
];
