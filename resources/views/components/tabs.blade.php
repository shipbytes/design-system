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
    // -mb-px pulls the row onto the divider so the active tab's 2px underline
    // covers the 1px rule rather than sitting below it and drawing two lines.
    $row = 'flex min-w-0 items-center gap-1 overflow-x-auto border-b border-divider';
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
