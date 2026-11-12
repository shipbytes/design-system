@aware([
    /*
     * The split's orientation and breakpoint, from the ds::split class: a
     * key no other component has, for the reason given in that class.
     */
    'dsSplit' => null,
])

@props([
    /**
     * Its size along the split: "30%", "240px", or a number of pixels. Left
     * out, it takes the room the others leave; leave one pane without.
     */
    'size' => null,
    /** The smallest it can be dragged to, in the same units. */
    'min' => null,
    /** The largest. */
    'max' => null,
    /**
     * Double-click or Enter on its divider folds it away and brings it back
     * at the size it had. Its divider is the one after it, or before it when
     * it is the last pane.
     */
    'collapsible' => false,
])

@php
    $split = $dsSplit ?? ['orientation' => 'horizontal', 'stackBelow' => null, 'label' => 'Resize'];
    $vertical = $split['orientation'] === 'vertical';

    // A CSS length, or nothing: what goes into the style attribute is only
    // ever a number and a unit.
    $length = function ($value): ?string {
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            return ((float) $value).'px';
        }

        return is_string($value) && preg_match('/^\d+(\.\d+)?(px|%|rem|em)$/', trim($value)) ? trim($value) : null;
    };

    $size = $length($size);
    $min = $length($min);
    $max = $length($max);

    $id = $attributes->get('id') ?? 'ds-pane-'.substr(md5(implode('|', [$size, $min, $max, (string) $slot])), 0, 8);
    // Custom properties inherit, so a pane without a size says so outright:
    // otherwise a pane of a split nested in a sized pane would take that
    // pane's size as its own.
    $style = implode(' ', array_filter([
        $size ? "--ds-split-size: {$size};" : '--ds-split-size: initial;',
        $min ? "--ds-split-min: {$min};" : null,
        $max ? "--ds-split-max: {$max};" : null,
    ]));

    // The pane without a size grows into what is left; the sized ones stay
    // at their size, shrinking together only when they and the dividers
    // overflow the split.
    $pane = implode(' ', array_filter([
        'relative overflow-auto basis-[var(--ds-split-size,0px)] shrink',
        $size ? 'grow-0' : 'grow',
        // Across the split nothing is limited: a pane may always shrink below
        // its content there, or a long line would push the split wider.
        $vertical ? 'min-w-0' : 'min-h-0',
        $vertical ? ($min ? 'min-h-(--ds-split-min)' : 'min-h-0') : ($min ? 'min-w-(--ds-split-min)' : 'min-w-0'),
        $max ? ($vertical ? 'max-h-(--ds-split-max)' : 'max-w-(--ds-split-max)') : null,
        'data-collapsed:invisible data-collapsed:min-h-0 data-collapsed:min-w-0',
        [
            'sm' => 'max-sm:basis-auto max-sm:min-h-0 max-sm:min-w-0 max-sm:max-h-none max-sm:max-w-none max-sm:overflow-visible',
            'md' => 'max-md:basis-auto max-md:min-h-0 max-md:min-w-0 max-md:max-h-none max-md:max-w-none max-md:overflow-visible',
            'lg' => 'max-lg:basis-auto max-lg:min-h-0 max-lg:min-w-0 max-lg:max-h-none max-lg:max-w-none max-lg:overflow-visible',
            'xl' => 'max-xl:basis-auto max-xl:min-h-0 max-xl:min-w-0 max-xl:max-h-none max-xl:max-w-none max-xl:overflow-visible',
        ][$split['stackBelow']] ?? null,
    ]));

    /*
     * The divider is drawn after every pane and hidden after the last, so
     * the split needs no knowledge of how many it holds. A 1px line with a
     * 24px hit area either side of it in ::before; focusable once the
     * script is there to move it, and named and valued for a reader.
     */
    $divider = implode(' ', array_filter([
        'relative z-10 shrink-0 bg-border last:hidden',
        'before:absolute',
        $vertical ? 'h-px w-full before:inset-x-0 before:-inset-y-3' : 'w-px self-stretch before:inset-y-0 before:-inset-x-3',
        $vertical ? 'data-ready:cursor-row-resize' : 'data-ready:cursor-col-resize',
        'data-ready:hover:bg-accent data-dragging:bg-accent focus-visible:bg-accent focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-focus-ring',
        'motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter',
        [
            'sm' => 'max-sm:hidden',
            'md' => 'max-md:hidden',
            'lg' => 'max-lg:hidden',
            'xl' => 'max-xl:hidden',
        ][$split['stackBelow']] ?? null,
    ]));

    $now = $size && str_ends_with($size, '%') ? (float) $size : null;
@endphp

<div
    id="{{ $id }}"
    data-ds-split-pane
    @if ($collapsible) data-ds-collapsible @endif
    style="{{ $style }}"
    {{ $attributes->except('id')->merge(['class' => $pane]) }}
>{{ $slot }}</div>
<div
    role="separator"
    aria-orientation="{{ $vertical ? 'horizontal' : 'vertical' }}"
    aria-controls="{{ $id }}"
    aria-label="{{ $split['label'] }}"
    @if ($now !== null) aria-valuenow="{{ round($now) }}" @endif
    aria-valuemin="0"
    aria-valuemax="100"
    x-bind:tabindex="$data.dsReady ? 0 : null"
    x-bind:data-ready="$data.dsReady ? '' : null"
    data-ds-split-divider
    class="{{ $divider }}"
></div>
