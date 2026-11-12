@props([
    /**
     * The keys of a shortcut pressed together, in order: ['Ctrl', 'K']. Each
     * is drawn as its own key, nested in one kbd as HTML has it for a
     * combination. Left out, the slot is the one key.
     */
    'keys' => null,
    /** Written between the keys of a combination. '' for none, as macOS writes ⌘K. */
    'separator' => '+',
    /**
     * A stored shortcut, "mod+shift+k", as ds::shortcut-input records it.
     * Drawn Ctrl + Shift + K from the server, and ⌘⇧K on a Mac once Alpine
     * runs (x-ds-shortcut, an enhancement). Read aloud as words either way.
     */
    'shortcut' => null,
])

@php
    /*
     * Sized in em, so a key sits in a line of body text, a button, a menu
     * item or a heading at that text's size: one look, no size prop. The
     * bottom edge is a hair heavier, as a key's is, drawn by an inset shadow
     * so the box keeps its height.
     */
    $key = 'inline-block min-w-[1.75em] rounded-chip border border-border-strong bg-surface-subtle px-[0.4em] text-center align-baseline font-mono text-[0.8125em] leading-[1.6] font-medium text-fg-body shadow-[inset_0_-1px_0_var(--color-border-strong)]';
    $keys = is_array($keys) ? array_values(array_filter($keys, fn ($k) => $k !== null && $k !== '')) : null;
    if ($shortcut !== null) {
        $caps = \Shipbytes\BladeUi\Support\Shortcut::caps($shortcut);
        $combo = $attributes->merge(['class' => 'inline-flex items-baseline gap-[0.25em] font-sans whitespace-nowrap text-fg-muted'])->merge([
            'x-data' => '',
            'x-ds-shortcut' => '',
            'data-ds-shortcut' => \Shipbytes\BladeUi\Support\Shortcut::normalise($shortcut),
            'data-ds-separator' => $separator,
            // Nothing to draw yet: hidden, with the cap's classes for a redraw.
            'hidden' => $caps ? null : true,
            'data-ds-key' => $caps ? null : $key,
        ]);
    }
@endphp

{{-- Each branch ends at its tag: a newline after it would print, and set a
     space between the key and a comma written right after it. --}}
@if ($shortcut !== null)
    {{-- The caps are for the eye, the words are what is said: a Mac's
         symbols are read out inconsistently, or not at all. --}}
    <kbd {{ $combo }}><span class="sr-only" data-ds-kbd-words>{{ implode('+', array_column($caps, 'word')) }}</span><span aria-hidden="true" class="contents" data-ds-kbd-caps>@foreach ($caps as $cap)<kbd class="{{ $key }}">{{ $cap['cap'] }}</kbd>@if (! $loop->last && $separator !== '')<span class="text-[0.8125em]">{{ $separator }}</span>@endif{{ '' }}@endforeach</span></kbd>@elseif ($keys)
    <kbd {{ $attributes->merge(['class' => 'inline-flex items-baseline gap-[0.25em] font-sans whitespace-nowrap text-fg-muted']) }}>@foreach ($keys as $k)<kbd class="{{ $key }}">{{ $k }}</kbd>@if (! $loop->last && $separator !== '')<span class="text-[0.8125em]">{{ $separator }}</span>@endif{{ '' }}@endforeach</kbd>@else
    <kbd {{ $attributes->merge(['class' => $key]) }}>{{ $slot }}</kbd>@endif
