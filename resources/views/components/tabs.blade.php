@props([
    /** Names the set. Becomes the tablist's or the nav's accessible name. */
    'label',
    /**
     * TRUE when the tabs are LINKS to other pages, false when they switch panels
     * on this one.
     *
     * This is not a styling flag, it is the accessibility contract, and getting
     * it wrong is a real bug rather than a nicety. `role="tablist"` promises a
     * screen reader that arrow keys move between tabs and that the content
     * changes in place. Put that role on a row of page links and the promise is
     * false in both halves: arrow keys do nothing, and following one navigates
     * away. Links get a <nav>, which is what they are.
     */
    'navigation' => false,
])

@php
    // The rule is an INSET SHADOW and the item has no negative margin.
    //
    // `overflow-x-auto` forces the computed `overflow-y` off `visible` to `auto` —
    // CSS has no "scroll one axis only" — so ANY vertical overflow draws a
    // scrollbar. `-mb-px` left each item's border box exactly 1px below the row's
    // padding box, and classic scrollbars rendered 15px of chrome for it. macOS
    // overlay scrollbars hid it, which is how it shipped.
    //
    // A child's border paints over its parent's inset shadow, so the active tab's
    // 2px underline still covers the rule without hanging below the row.
    $row = 'flex min-w-0 items-center gap-1 overflow-x-auto shadow-[inset_0_-1px_0_var(--ds-divider)]';
@endphp

@if ($navigation)
    <nav {{ $attributes->merge(['class' => $row]) }} aria-label="{{ $label }}">
        {{ $slot }}
    </nav>
@else
    <div
        {{ $attributes->merge(['class' => $row]) }}
        role="tablist"
        aria-label="{{ $label }}"
        aria-orientation="horizontal"
    >
        {{ $slot }}
    </div>
@endif
