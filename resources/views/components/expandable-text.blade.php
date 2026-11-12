@props([
    /** How many lines show before "Read more". */
    'lines' => 3,
    /** The button's words while the text is held to `lines`. */
    'moreLabel' => 'Read more',
    /** The button's words once it is let out. */
    'lessLabel' => 'Show less',
])

@php
    $lines = max(1, (int) $lines);

    // Hashed from the text rather than random, so a Livewire morph patches
    // the box instead of replacing it.
    $id = ($attributes->get('id') ?? 'ds-clamp-'.substr(md5((string) $slot.$lines), 0, 8)).'-body';

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsClamp', [
        'lines' => $lines,
        'more' => $moreLabel,
        'less' => $lessLabel,
    ]);

    /*
     * Nothing here clamps. dsClamp sets the max-height once it has measured,
     * so without the script, or before it runs, the whole text shows: the
     * failure that loses nothing. The fade is a mask rather than a gradient
     * drawn over the text, so it fades to whatever is behind the box, on a
     * panel, on the page or on a tint, in either theme, and only while the
     * text is held.
     */
    $body = implode(' ', [
        'overflow-hidden',
        'motion-safe:transition-[max-height] motion-safe:duration-dialog-enter motion-safe:ease-enter',
        'group-data-[ds-state=clamped]/clamp:[mask-image:linear-gradient(to_bottom,#000_calc(100%-1.5em),transparent)]',
    ]);
@endphp

<div
    x-data="{{ $state }}"
    data-ds-clamp
    {{ $attributes->merge(['class' => 'group/clamp min-w-0']) }}
>
    <div id="{{ $id }}" data-ds-clamp-body class="{{ $body }}">{{ $slot }}</div>

    {{-- hidden until the text is measured as longer than `lines`: a button
         that reveals nothing would be a lie. --}}
    <button
        type="button"
        hidden
        data-ds-clamp-toggle
        aria-expanded="false"
        aria-controls="{{ $id }}"
        class="mt-1 inline-flex items-center gap-1 rounded-chip text-meta font-medium text-accent hover:text-accent-hover hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
    >
        <span data-ds-clamp-label>{{ $moreLabel }}</span>
        <x-ds::icon name="chevron-down" variant="micro" size="3.5" class="group-data-[ds-state=expanded]/clamp:rotate-180 motion-safe:transition-transform motion-safe:duration-popover-enter motion-safe:ease-enter" />
    </button>
</div>
