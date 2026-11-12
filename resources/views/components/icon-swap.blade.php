@props([
    /** The icon at rest: a Heroicon name, as ds::icon takes. Not used by the `menu` preset. */
    'from' => null,
    /**
     * The icon it turns into. Left out with `rotate`, `from` turns half a
     * circle instead: a chevron that points the other way when open.
     */
    'to' => null,
    /**
     * Drawn in its second state from the server, whatever the button around
     * it says. For a host that renders the state itself and has no
     * aria-expanded or aria-pressed to read.
     */
    'active' => false,
    /**
     * crossfade | rotate | flip | menu.
     *
     * crossfade: one fades and shrinks as the other grows in. rotate: the
     * same, turning a quarter. flip: a card turned over. menu: three bars
     * that fold into a cross, built from spans, for a menu button.
     */
    'preset' => 'crossfade',
    /** 4 | 5 | 6: 16, 20 or 24px, as ds::icon's size. */
    'size' => '5',
    /** outline | solid | mini | micro, as ds::icon. */
    'variant' => 'outline',
])

@php
    $preset = in_array($preset, ['crossfade', 'rotate', 'flip', 'menu'], true) ? $preset : 'crossfade';
    $size = in_array((string) $size, ['4', '5', '6'], true) ? (string) $size : '5';

    if ($preset !== 'menu' && blank($from)) {
        throw new \InvalidArgumentException(
            'ds::icon-swap needs `from`, the icon at rest (and usually `to`, the one it becomes). Only the `menu` preset draws its own.'
        );
    }

    $single = $preset !== 'menu' && blank($to);
    if ($single && $preset !== 'rotate') {
        throw new \InvalidArgumentException(
            "ds::icon-swap needs `to` with the {$preset} preset: there is nothing to swap to. A chevron that turns over is preset=\"rotate\" with `from` alone."
        );
    }

    /*
     * One custom property carries the state, 0 or 1, and every face reads it
     * through calc(): so the state is ONE selector, not one per face and per
     * property. It is 1 inside an element that says it is on: expanded,
     * pressed, checked, or a data-state of open, on, checked or active, the
     * attributes ds::dropdown, ds::popover and a host's own toggle write. The
     * selector matches any ancestor, so the nearest that says so wins only
     * because nothing else around a button usually does: an icon-swap inside
     * an expanded region's content, rather than its trigger, would turn too.
     */
    $root = implode(' ', [
        'relative inline-grid shrink-0 place-items-center',
        [
            '4' => 'size-4 text-[1rem]',
            '5' => 'size-5 text-[1.25rem]',
            '6' => 'size-6 text-[1.5rem]',
        ][$size],
        'in-[:is([aria-expanded=true],[aria-pressed=true],[aria-checked=true],[data-state=open],[data-state=on],[data-state=checked],[data-state=active])]:[--ds-on:1]',
        $active ? '[--ds-on:1]' : '',
        $preset === 'flip' ? 'perspective-near' : '',
    ]);

    // Behind motion-safe only: with reduced motion the state still changes,
    // at once.
    $moves = 'col-start-1 row-start-1 motion-safe:transition-[opacity,scale,rotate,translate,transform] motion-safe:duration-popover-enter motion-safe:ease-enter';

    $faces = match ($preset) {
        'crossfade' => [
            'opacity-[calc(1-var(--ds-on,0))] scale-[calc(1-var(--ds-on,0)*0.5)]',
            'opacity-(--ds-on,0) scale-[calc(0.5+var(--ds-on,0)*0.5)]',
        ],
        'rotate' => $single
            ? ['rotate-[calc(var(--ds-on,0)*180deg)]', null]
            : [
                'opacity-[calc(1-var(--ds-on,0))] rotate-[calc(var(--ds-on,0)*90deg)] scale-[calc(1-var(--ds-on,0)*0.25)]',
                'opacity-(--ds-on,0) rotate-[calc((var(--ds-on,0)-1)*90deg)] scale-[calc(0.75+var(--ds-on,0)*0.25)]',
            ],
        'flip' => [
            'backface-hidden rotate-y-[calc(var(--ds-on,0)*180deg)]',
            'backface-hidden rotate-y-[calc((var(--ds-on,0)-1)*180deg)]',
        ],
        'menu' => [null, null],
    };

    /*
     * The menu preset's bars, sized in em off the root's font size, which
     * is the icon's size: the lines of the outline bars-3 icon (16.5 of 24
     * wide, 5.25 apart, 1.5 thick), meeting in the middle as a cross the
     * width of x-mark.
     */
    $bar = 'absolute start-[0.15625em] top-1/2 h-[0.0625em] w-[0.6875em] rounded-full bg-current '.$moves;
    $bars = [
        'translate-y-[calc(-50%+(var(--ds-on,0)-1)*0.21875em)] rotate-[calc(var(--ds-on,0)*45deg)]',
        '-translate-y-1/2 opacity-[calc(1-var(--ds-on,0))] scale-x-[calc(1-var(--ds-on,0))]',
        'translate-y-[calc(-50%+(1-var(--ds-on,0))*0.21875em)] rotate-[calc(var(--ds-on,0)*-45deg)]',
    ];
@endphp

{{-- Decorative: the button around it carries the name and the state. --}}
<span aria-hidden="true" data-ds-icon-swap data-preset="{{ $preset }}" {{ $attributes->merge(['class' => $root]) }}>
    @if ($preset === 'menu')
        @foreach ($bars as $move)
            <span class="{{ $bar }} {{ $move }}"></span>
        @endforeach
    @else
        <span class="{{ $moves }} flex {{ $faces[0] }}"><x-ds::icon :name="$from" :variant="$variant" :size="$size" /></span>
        @unless ($single)
            <span class="{{ $moves }} flex {{ $faces[1] }}"><x-ds::icon :name="$to" :variant="$variant" :size="$size" /></span>
        @endunless
    @endif
</span>
