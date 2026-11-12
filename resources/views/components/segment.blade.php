@aware([
    'name' => null,
    'size' => 'md',
    'fullWidth' => false,
])

@props([
    /** What this segment posts when chosen. */
    'value' => null,
    'checked' => false,
    /** A Heroicons name, drawn before the words. */
    'icon' => null,
    /** The icon alone; the words stay as the accessible name. Needs an `icon`. */
    'iconOnly' => false,
    'disabled' => false,
    /** A link to another view instead of a value to post. Every segment in a control is one or the other. */
    'href' => null,
    /** The link segment for the view on screen: drawn chosen, marked aria-current="page". */
    'active' => false,
])

@php
    $size = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';
    $link = $href !== null;

    if ($iconOnly && ! $icon) {
        throw new \InvalidArgumentException('ds::segment: `iconOnly` needs an `icon` to show.');
    }
    if (! $link) {
        if ($name === null || $name === '') {
            throw new \InvalidArgumentException('ds::segment needs a `name`, normally from its ds::segmented-control, unless it is a link segment with `href`.');
        }
        if ($value === null) {
            throw new \InvalidArgumentException("ds::segment in \"{$name}\" needs a `value` to post.");
        }
    }

    $shape = match (true) {
        $iconOnly => ['sm' => 'px-1.5', 'md' => 'px-2', 'lg' => 'px-2.5'][$size],
        default => ['sm' => 'px-2.5 text-meta', 'md' => 'px-3 text-body', 'lg' => 'px-4 text-body'][$size],
    };

    /*
     * The chosen face: what the glide's highlight looks like, drawn by the
     * segment itself until the highlight is in place (`data-ds-glide-ready` on
     * the track), and for good where the script never runs. Once it is, the
     * segment goes transparent and the highlight slides under it.
     */
    $chosen = 'bg-surface text-fg shadow-raised';
    $handOver = 'group-data-[ds-glide-ready]/glide:bg-transparent group-data-[ds-glide-ready]/glide:shadow-none';

    $segment = implode(' ', array_filter([
        // `relative` lifts the words above the highlight, an absolutely
        // positioned sibling before them.
        'relative inline-flex min-w-0 items-center justify-center gap-1.5 rounded-chip font-medium whitespace-nowrap select-none',
        'motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter',
        $shape,
        $fullWidth ? 'flex-1' : null,
        // A disabled segment does not answer the pointer, so it does not
        // brighten under it either.
        match (true) {
            $link && $active => $disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
            $disabled => 'text-fg-body cursor-not-allowed opacity-50',
            default => 'text-fg-body hover:text-fg cursor-pointer',
        },
        match (true) {
            $link && $active => $chosen.' '.$handOver,
            $link => null,
            // Chosen and disabled stays chosen-looking, faded with the rest.
            default => 'has-checked:bg-surface has-checked:text-fg has-checked:shadow-raised '
                .'group-data-[ds-glide-ready]/glide:has-checked:bg-transparent group-data-[ds-glide-ready]/glide:has-checked:shadow-none',
        },
        $link
            ? 'focus-visible:outline-2 focus-visible:outline-focus-ring'
            : 'has-focus-visible:outline-2 has-focus-visible:outline-focus-ring',
    ]));

    $glyph = $size === 'sm' && ! $iconOnly ? '3.5' : '4';
@endphp

@if ($link)
    <a
        data-ds-segment="link"
        data-ds-glide-item
        @unless ($disabled) href="{{ $href }}" @endunless
        @if ($active) aria-current="page" @endif
        @if ($disabled) aria-disabled="true" @endif
        {{ $attributes->except(['name', 'size', 'fullWidth'])->merge(['class' => $segment]) }}
    >
        @if ($icon)
            <x-ds::icon :name="$icon" variant="micro" :size="$glyph" />
        @endif
        @if ($iconOnly)<span class="sr-only">{{ $slot }}</span>@else<span>{{ $slot }}</span>@endif
    </a>
@else
    <label data-ds-segment="input" data-ds-glide-item {{ $attributes->only('class')->merge(['class' => $segment]) }}>
        {{-- sr-only, not hidden: it keeps focus, the arrow keys and form
             validation, and the label around it carries the look. The hook is
             swapped by the control for the host's wire:model or x-model. --}}
        <input
            data-ds-segment-input
            type="radio"
            name="{{ $name }}"
            value="{{ $value }}"
            {{ $attributes->except(['class', 'name', 'size', 'fullWidth'])->merge(['class' => 'peer sr-only']) }}
            @checked($checked)
            @disabled($disabled)
        />
        @if ($icon)
            <x-ds::icon :name="$icon" variant="micro" :size="$glyph" />
        @endif
        @if ($iconOnly)<span class="sr-only">{{ $slot }}</span>@else<span>{{ $slot }}</span>@endif
    </label>
@endif
