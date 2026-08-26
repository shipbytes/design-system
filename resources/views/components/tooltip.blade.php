@props([
    /**
     * The tip. PLAIN TEXT, and short.
     *
     * A tooltip cannot hold anything interactive: it is not focusable, there is
     * no way for a pointer to travel into it reliably, and a keyboard reader has
     * no way in at all. A link inside one is unreachable for everybody who is not
     * using a mouse.
     */
    'text',
    /** top | bottom | left | right */
    'placement' => 'top',
])

@php
    // Closed set. Position and the centring transform belong together — split
    // apart, a placement change silently keeps the previous offset.
    $placements = [
        'top' => 'bottom-full left-1/2 mb-2 -translate-x-1/2',
        'bottom' => 'top-full left-1/2 mt-2 -translate-x-1/2',
        'left' => 'right-full top-1/2 mr-2 -translate-y-1/2',
        'right' => 'left-full top-1/2 ml-2 -translate-y-1/2',
    ];

    $position = $placements[$placement] ?? $placements['top'];

    $id = 'ds-tip-'.substr(md5($text.$placement.uniqid()), 0, 8);
@endphp

{{--
    Hover AND focus, always both.

    A tip that only appears on hover does not exist for a keyboard, and the
    trigger is usually an icon-only button whose whole meaning is in the tip. The
    trigger keeps its own accessible name; this is aria-describedby, which is
    supplementary — a tooltip is never the only place the meaning lives.
--}}
<span
    x-data="{ tipOpen: false }"
    data-ds-tooltip
    @mouseenter="tipOpen = true"
    @mouseleave="tipOpen = false"
    @focusin="tipOpen = true"
    @focusout="tipOpen = false"
    {{-- WCAG 1.4.13: content shown on hover must be dismissible without moving
         the pointer. Escape is that, and it is the part everyone forgets. --}}
    @keydown.escape="tipOpen = false"
    {{ $attributes->merge(['class' => 'relative inline-flex h-fit']) }}
>
    <span class="contents" aria-describedby="{{ $id }}">{{ $slot }}</span>

    <span
        x-show="tipOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        id="{{ $id }}"
        role="tooltip"
        {{-- The tip must never eat the pointer: it sits over the trigger's edge,
             and a hover that lands on the tip instead of the button flickers. --}}
        class="pointer-events-none absolute {{ $position }} z-50 w-max max-w-56 rounded-control bg-surface-inverse px-2 py-1 text-meta text-on-inverse shadow-float"
    >{{ $text }}</span>
</span>
