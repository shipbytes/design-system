@props([
    /** Image URL. Without one, the initials are the avatar. */
    'src' => null,
    /** The person or thing. Supplies both the initials and the accessible name. */
    'name' => null,
    /** xs | sm | md | lg */
    'size' => 'md',
    /**
     * Square instead of round. For a company or a project — a circle reads as a
     * person, and a logo in a circle gets its corners eaten.
     */
    'square' => false,
    /**
     * The avatar repeats a name written next to it, so it says nothing new.
     * Hides it from assistive technology instead of announcing the name twice.
     */
    'decorative' => false,
])

@php
    // A CLOSED set, so these are literal class strings a scanner can read.
    // "size-{$size}" would be invisible to Tailwind — see specs/icon.md.
    $sizes = [
        'xs' => 'size-6 text-[0.625rem]',
        'sm' => 'size-8 text-meta',
        'md' => 'size-10 text-body',
        'lg' => 'size-12 text-section',
    ];

    $box = $sizes[$size] ?? $sizes['md'];

    /*
     * Two initials, from the first and last word.
     *
     * mb_* throughout: `strtoupper` corrupts a multi-byte first letter, and a
     * name is the single most likely place in an interface to meet one. It also
     * takes the first GRAPHEME rather than the first byte, so an emoji or an
     * accented letter survives.
     */
    $initials = '';

    if ($name !== null && trim($name) !== '') {
        $words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);
        $first = mb_substr($words[0], 0, 1);
        $last = count($words) > 1 ? mb_substr($words[count($words) - 1], 0, 1) : '';
        $initials = mb_strtoupper($first.$last);
    }

    $shape = $square ? 'rounded-control' : 'rounded-full';

    $classes = implode(' ', [
        'inline-flex shrink-0 items-center justify-center overflow-hidden',
        'bg-neutral-tint font-medium text-on-neutral-tint select-none',
        $shape,
        $box,
    ]);
@endphp

<span
    {{ $attributes->merge(['class' => $classes]) }}
    @if ($decorative || $name === null) aria-hidden="true" @endif
>
    @if ($src)
        {{-- object-cover, not contain: a portrait cropped to a circle is the
             point, and contain letterboxes it against the tint. --}}
        <img
            src="{{ $src }}"
            alt="{{ $decorative ? '' : ($name ?? '') }}"
            class="size-full object-cover"
            loading="lazy"
            decoding="async"
        />
    @elseif ($initials !== '')
        {{-- The tracking is negative on purpose: two capitals at these sizes sit
             visibly right of centre with default letter-spacing, because the
             trailing side bearing is not balanced by anything. --}}
        <span class="-tracking-[0.02em]">{{ $initials }}</span>
    @else
        {{-- No image and no name. A blank circle is a bug the reader cannot
             report, so it renders the placeholder mark instead. --}}
        <x-ds::icon name="user" variant="mini" size="4" class="text-fg-subtle" />
    @endif
</span>
