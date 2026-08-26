@props([
    /** text | block | circle */
    'variant' => 'text',
    /** Number of bars, for `text`. The last one is short, the way a paragraph ends. */
    'lines' => 3,
    /** sm | md | lg — the height of a `block`, or the diameter of a `circle`. */
    'size' => 'md',
])

@php
    // Closed sets, mapped to literal classes. Nothing here is interpolated.
    $blocks = ['sm' => 'h-16', 'md' => 'h-24', 'lg' => 'h-40'];
    $circles = ['sm' => 'size-8', 'md' => 'size-10', 'lg' => 'size-12'];

    /*
     * `fg/10` rather than surface-subtle: a skeleton has to be visible on the
     * card AND on the sunken ground behind it, and a fixed surface token is only
     * ever right on one of them. A translucent foreground adapts to both, and to
     * dark, from one value.
     */
    $fill = 'bg-fg/10';

    // motion-safe: a pulsing block is exactly the kind of thing that triggers
    // vestibular symptoms, and a loading state is not worth that. Still visible
    // when the animation is off — it is a grey bar either way.
    $pulse = 'motion-safe:animate-pulse';

    $lines = max(1, (int) $lines);
@endphp

{{--
    aria-hidden, with `aria-busy` on the region it stands in.

    A skeleton has no content to announce — it is a picture of content that does
    not exist yet. Left visible to assistive technology it reads as a run of
    empty elements, which is worse than silence. The HOST tells the reader the
    region is loading; see specs/skeleton.md.
--}}
<div {{ $attributes->merge(['class' => 'w-full']) }} aria-hidden="true">
    @if ($variant === 'circle')
        <div class="{{ $circles[$size] ?? $circles['md'] }} shrink-0 rounded-full {{ $fill }} {{ $pulse }}"></div>
    @elseif ($variant === 'block')
        <div class="w-full rounded-control {{ $blocks[$size] ?? $blocks['md'] }} {{ $fill }} {{ $pulse }}"></div>
    @else
        <div class="flex w-full flex-col gap-2">
            @for ($i = 0; $i < $lines; $i++)
                {{-- The last bar is short. A block of equal-length bars reads as a
                     table; a paragraph ends mid-line, and that is what makes this
                     look like the text it is standing in for. --}}
                <div class="h-3 rounded-chip {{ $fill }} {{ $pulse }} {{ $i === $lines - 1 && $lines > 1 ? 'w-3/5' : 'w-full' }}"></div>
            @endfor
        </div>
    @endif
</div>
