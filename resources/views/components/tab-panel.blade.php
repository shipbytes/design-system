@props([
    /** Must match the controlling tab's `controls`. */
    'id',
    /** id of the tab that controls this panel, so the panel names itself. */
    'labelledby' => null,
    'active' => false,
])

{{--
    Hidden with the `hidden` ATTRIBUTE rather than a class, so a panel that is
    not showing is out of the accessibility tree and out of the tab order for
    free — no aria-hidden to keep in sync, and nothing focusable inside it that
    Tab can still reach.
--}}
<div
    id="{{ $id }}"
    {{ $attributes->merge(['class' => 'py-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring']) }}
    role="tabpanel"
    @if ($labelledby) aria-labelledby="{{ $labelledby }}" @endif
    {{-- tabindex=0 so a panel with no focusable content is still reachable —
         otherwise a keyboard reader tabs straight past the content they just
         selected. --}}
    tabindex="0"
    @unless ($active) hidden @endunless
>
    {{ $slot }}
</div>
