{{--
    ds::button-group. Its props are the constructor of
    Shipbytes\BladeUi\View\Components\ButtonGroup (label, size, variant,
    orientation, expanding), which also hands the buttons inside their settings: see that
    class for why this one component is not anonymous.

    The joins are custom properties set on each DIRECT child and read by the
    button's radius: zero on every side that meets a neighbour, the control
    radius on the outer ends. A property inherits, so a button wrapped in a
    ds::dropdown, as the split button's trigger is, gets its end's radius from
    the wrapper it sits in, which is the child the group can see.
--}}
@php
    $vertical = $orientation === 'vertical';

    // A filled button has no border to share, so the hairline between two of
    // them is a 1px gap showing the ground. Outlined ones overlap by their
    // border, which then reads as one line between them.
    $filled = in_array($variant, ['primary', 'danger'], true);

    $classes = implode(' ', [
        /*
         * Expanding, the group is a block as wide as its container (or the
         * width a class gives it) and the buttons sit at its start: a label
         * opening takes room the group already has, so nothing after it on
         * the line moves. The buttons inside slide along, which is the effect.
         */
        $expanding ? 'flex w-full min-w-0' : 'inline-flex max-w-full',
        $vertical ? 'flex-col items-stretch' : 'items-stretch',
        // `*:` rather than an arbitrary `[&>*]:` variant: an ampersand or an
        // angle bracket in a class is written out as an entity in the page,
        // and a Tailwind that scans rendered HTML would read a different class.
        '*:[--ds-join-start:0px] *:[--ds-join-end:0px]',
        '*:first:[--ds-join-start:var(--radius-control)] *:last:[--ds-join-end:var(--radius-control)]',
        match (true) {
            $filled => 'gap-px',
            $vertical => '*:not-first:-mt-px',
            default => '*:not-first:-ms-px',
        },
    ]);
@endphp

<div role="group" aria-label="{{ $label }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
