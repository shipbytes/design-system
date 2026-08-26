@props([
    'href',
    'label',
    'active' => false,
    /**
     * Alpine expression that is true when the rail is collapsed to icons.
     * Passed in rather than assumed: one shell has hover-to-peek and a studio
     * mode, the other has neither, and a presentation component should not know
     * which store either of them uses.
     *
     * Null renders a permanently expanded item.
     */
    'collapsedWhen' => null,
    /** Wrap the icon in a tinted chip. The admin shell does; the user shell does not. */
    'chip' => false,
    /** Trailing count, e.g. open tickets. Hidden with the label when collapsed. */
    'badge' => null,
])

@php
    // The active item is a raised white card, not a coloured one. Colour alone
    // would have to fight the icon and the label for the same signal; a change
    // of elevation reads instantly and survives both themes.
    $state = $active
        ? 'border-border-strong bg-surface text-fg shadow-raised'
        : 'border-transparent text-fg-body hover:bg-fg/5 hover:text-fg';

    $iconState = $active ? 'text-fg-body' : 'text-fg-subtle';

    // 14/20 rather than the body 14/24: a nav rail is a list of short labels and
    // the looser leading makes every row four pixels taller for nothing.
    $base = 'flex items-center rounded-control border text-left text-section font-medium transition-colors';

    $expandedClasses = 'w-full gap-3 px-2 py-2';
    $collapsedClasses = 'size-8 justify-center p-2';
@endphp

<a
    href="{{ $href }}"
    @if ($collapsedWhen)
        :class="{{ $collapsedWhen }} ? '{{ $collapsedClasses }}' : '{{ $expandedClasses }}'"
    @endif
    @if ($active) aria-current="page" @endif
    title="{{ $label }}"
    {{ $attributes->class([$base, $state, $expandedClasses => ! $collapsedWhen]) }}
>
    @isset($icon)
        @if ($chip)
            {{-- The chipped icon keeps ONE colour whatever the state. The chip is
                 already a container; making it also change colour gives the active
                 item three simultaneous signals (card, chip, icon) for one fact. --}}
            <span class="flex size-5 shrink-0 items-center justify-center rounded-chip bg-surface-sunken text-fg-muted">
                {{ $icon }}
            </span>
        @else
            <span class="shrink-0 {{ $iconState }}">{{ $icon }}</span>
        @endif
    @endisset

    <span
        @if ($collapsedWhen) x-show="!({{ $collapsedWhen }})" @endif
        class="truncate"
    >{{ $label }}</span>

    @if ($badge)
        <span
            @if ($collapsedWhen) x-show="!({{ $collapsedWhen }})" @endif
            class="ms-auto shrink-0"
        >{{ $badge }}</span>
    @endif
</a>
