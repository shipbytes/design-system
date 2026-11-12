@props([
    /** Heroicon name for the leading mark. Optional — a menu of plain verbs is fine. */
    'icon' => null,
    /** Navigates. Anything that goes somewhere should be an `a` element, not a button. */
    'href' => null,
    /**
     * neutral | danger
     *
     * Two tones only, and the same rule as the sheet: a tone is a claim about
     * what the item IS, not a way to tell items apart. `danger` is for the one
     * that destroys something. Everything else is neutral.
     */
    'tone' => 'neutral',
    'disabled' => false,
    /** Force the element. A "Delete" that POSTs is a submit, not a link. */
    'as' => null,
])

@php
    $tones = [
        'neutral' => 'text-fg-body hover:bg-surface-subtle hover:text-fg',
        // The label darkens on the wash: `danger` on it is 4.36:1, under AA.
        'danger' => 'text-danger hover:bg-danger-wash hover:text-on-danger-tint',
    ];

    $tone = array_key_exists($tone, $tones) ? $tone : 'neutral';

    // A disabled item is a `div`: a disabled `a` is still focusable and still
    // followable by keyboard, so the only reliable way to stop it going
    // anywhere is for it not to be a link at all.
    $tag = $as ?? (($href && ! $disabled) ? 'a' : 'button');

    $classes = implode(' ', [
        'flex w-full items-center gap-2.5 px-3 py-2 text-left text-body',
        // The ring is drawn INSIDE the item: an outer one would be clipped by
        // the menu's overflow and its rounded edge. The fill alone is not
        // enough to find the focus by: surface-subtle on surface is about
        // 1.04:1 in the light theme.
        'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus-ring',
        $disabled
            ? 'cursor-not-allowed text-fg-subtle'
            : $tones[$tone].' '.($tone === 'danger' ? 'focus-visible:bg-danger-wash focus-visible:text-on-danger-tint' : 'focus-visible:bg-surface-subtle'),
    ]);
@endphp

<{{ $tag }}
    @if ($tag === 'a' && ! $disabled) href="{{ $href }}" @endif
    @if ($tag === 'button') type="{{ $as === 'button' ? 'submit' : 'button' }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
    {{-- The menu's arrow-key handler walks `[role=menuitem]` and skips
         aria-disabled, so this attribute is what keeps a dead item out of the
         keyboard path rather than merely looking dim. --}}
    role="menuitem"
    {{-- Roving tabindex: every item is -1 and the MENU moves focus with the
         arrow keys. Leaving them tabbable would make Tab walk the whole menu
         one item at a time, which is the behaviour role="menu" exists to
         replace. --}}
    tabindex="-1"
    @if ($disabled) aria-disabled="true" @endif
>
    @if ($icon)
        {{-- fg-subtle is allowed here because the label beside it says the same
             thing — the redundant-information exception. It would not be
             allowed on an icon that was the only thing carrying the meaning. --}}
        <x-ds::icon :name="$icon" size="4" class="{{ $tone === 'danger' ? 'text-danger' : 'text-fg-subtle' }}" />
    @endif

    <span class="min-w-0 flex-1 truncate">{{ $slot }}</span>
</{{ $tag }}>
