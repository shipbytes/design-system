@props([
    /** What the entries under it share: "Today", "12 November", "Release 2.0". */
    'label',
    /** The heading level, 2 to 6, to fit the page's outline. */
    'level' => 3,
])

@php
    $level = max(2, min(6, (int) $level));
@endphp

{{--
    A group is an entry of the timeline holding a list of its own, so the
    outline is a list of lists and a screen reader announces both. Its
    heading sticks to the top of the scrolling box while its entries pass
    under it. The heading needs a background to hide them: the surface by
    default, and --ds-timeline-bg on any ancestor when the timeline sits on
    another colour; --ds-timeline-top offsets it under a sticky page header.
    The rail breaks at a heading, between groups, as the time it marks does.
--}}
<li {{ $attributes->class(['pb-6 last:pb-0 group-data-dense/timeline:pb-4 group-data-dense/timeline:last:pb-0']) }}>
    <h{{ $level }} class="sticky top-(--ds-timeline-top,0px) z-10 mb-3 bg-(--ds-timeline-bg,var(--color-surface)) py-1.5 text-meta font-semibold text-fg-muted group-data-dense/timeline:mb-2">{{ $label }}</h{{ $level }}>
    <ol class="flex flex-col">
        {{ $slot }}
    </ol>
</li>
