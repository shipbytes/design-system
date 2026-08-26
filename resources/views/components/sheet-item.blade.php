@props([
    'label',
    'description' => null,
    'href' => null,
    /**
     * neutral | accent | danger
     *
     * Deliberately NOT a free-form colour. The source app gave every feature
     * its own hue — teal, blue, pink, purple, green, amber, indigo — as
     * wayfinding. It was dropped: ten hues is the sprawl this system exists to
     * prevent, and the icon already carries the recognition.
     *
     * The two that keep a tone are the two that mean something. `danger` for
     * signing out, `accent` for a role-gated escape hatch. Both are different
     * in KIND from the features above them, not just different features.
     */
    'tone' => 'neutral',
    /** Dim the row and drop the chevron. For features that are not built yet. */
    'disabled' => false,
    /** Force the element. Sign out is a form submit, not a link. */
    'as' => null,
    /**
     * The chevron is a claim that the row goes somewhere, so it follows the
     * element rather than the styling: a link gets one, a submit button does
     * not. Pass explicitly to override.
     */
    'chevron' => null,
])

@php
    $tones = [
        // A wash, not a tint: at 40px the tile is a small surface but a solid
        // one, and tint strength behind an icon fights the icon.
        'neutral' => ['tile' => 'bg-surface-subtle text-fg-muted', 'label' => 'text-fg'],
        'accent' => ['tile' => 'bg-accent-wash text-accent', 'label' => 'text-fg'],
        'danger' => ['tile' => 'bg-danger-wash text-danger', 'label' => 'text-danger'],
    ];
    $t = $tones[$tone] ?? $tones['neutral'];

    $tag = $as ?? (($href && ! $disabled) ? 'a' : 'div');
    $showChevron = $chevron ?? ($tag === 'a');
@endphp

<{{ $tag }}
    @if ($tag === 'a') href="{{ $href }}" @endif
    @if ($tag === 'button') type="submit" @endif
    {{ $attributes->class([
        'flex items-center gap-4 rounded-panel px-3 py-3.5',
        'w-full text-left' => $tag === 'button',
        'transition-colors hover:bg-surface-subtle' => ! $disabled,
        'opacity-60' => $disabled,
    ]) }}
    @if ($disabled) aria-disabled="true" @endif
>
    <span class="flex size-10 shrink-0 items-center justify-center rounded-panel {{ $t['tile'] }}">
        {{ $icon ?? '' }}
    </span>

    <span class="min-w-0 flex-1">
        {{-- section, not body: 14/20 against 14/24. In a two-line row the looser
             leading pushes the description down and the row four pixels taller. --}}
        <span class="block truncate text-section font-medium {{ $t['label'] }}">{{ $label }}</span>
        @if ($description)
            <span class="block truncate text-meta text-fg-muted">{{ $description }}</span>
        @endif
    </span>

    @if ($showChevron)
        <x-ds::icon name="chevron-right" size="5" class="text-fg-subtle" />
    @endif
</{{ $tag }}>
