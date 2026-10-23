@props([
    'label',
    'href' => null,
    'active' => false,
    /**
     * A count on the icon, e.g. unread tickets. Pass the RAW number: anything
     * over 9 is drawn as "9+" here, so every consumer caps the same way and a
     * screen reader still hears the real figure. Null, 0, a negative number
     * and an empty string draw nothing, so a bare count needs no guard.
     * A non-numeric string ("new") is drawn as given, uncapped; keep it to a
     * word.
     */
    'badge' => null,
])

@php
    // The label and the icon take DIFFERENT inactive weights on purpose: the
    // icon carries the recognition and can afford to be lighter, the label
    // carries the meaning and cannot. Active pulls both to full strength.
    $tag = $href ? 'a' : 'button';

    // What the pill shows, and what the item's accessible name gains. They
    // differ on purpose once the count passes 9: the pill has room for two
    // characters, a screen reader has room for the number.
    $badgeText = null;
    $badgeSpoken = null;

    if (is_int($badge) || is_float($badge) || (is_string($badge) && is_numeric(trim($badge)))) {
        $count = (int) (is_string($badge) ? trim($badge) : $badge);

        if ($count > 0) {
            $badgeText = $count > 9 ? '9+' : (string) $count;
            $badgeSpoken = "{$count} new";
        }
    } elseif (is_string($badge) && trim($badge) !== '') {
        $badgeText = trim($badge);
        $badgeSpoken = $badgeText;
    }
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @else type="button" @endif
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'flex flex-1 flex-col items-center justify-center py-2',
        'text-fg' => $active,
        'text-fg-muted' => ! $active,
    ]) }}
>
    {{-- The fill has to land on the `svg` element ITSELF, not this wrapper. `fill` is an
         inherited SVG property, but the icons carry fill="currentColor" as a
         presentation attribute, and that beats inheritance — so a fill set here
         is ignored and the icon silently takes the link's text colour instead.
         The child selector reaches past that.

         `relative` so the count pill positions against the icon, not the whole
         item: against the item it would float in the corner of a tab a fifth of
         the screen wide, attached to nothing. --}}
    <span class="relative flex {{ $active ? '[&_svg]:fill-fg' : '[&_svg]:fill-fg-subtle' }}">
        {{ $icon ?? '' }}
        @if ($badgeText !== null)
            {{-- Hidden from assistive tech: "9+" is not a number anyone should
                 hear, and the label carries the real count instead. Anchored by
                 its START edge so a wider "9+" grows outward, away from the
                 glyph, rather than across it. The surface-coloured ring cuts it
                 out of the icon it overlaps. --}}
            <span
                aria-hidden="true"
                class="absolute -top-1.5 start-full -ms-2.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-accent px-1 text-meta font-semibold whitespace-nowrap tabular-nums text-on-accent ring-2 ring-surface"
            >{{ $badgeText }}</span>
        @endif
    </span>
    {{-- The spoken count sits INSIDE the label's span so the accessible name
         reads as one phrase, "Support, 3 new". As a sibling flex item, a
         browser may join the two with a space before the comma. --}}
    <span class="mt-1 text-meta font-medium">{{ $label }}@if ($badgeSpoken !== null)<span class="sr-only">, {{ $badgeSpoken }}</span>@endif</span>
</{{ $tag }}>
