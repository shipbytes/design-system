@props([
    /** Hide the bar entirely — the studio supplies its own action bar. */
    'hidden' => false,
])

{{--
    Fixed to the bottom below the lg breakpoint. `safe-area-bottom` is not
    decoration: without it the bar sits under the home indicator on a notched
    phone and the last few pixels of every tab are untappable.

    The page reserves room for this with padding, not margin — a fixed bar
    cannot push content, so the shell pads its content area instead.
--}}
<nav
    {{ $attributes->class([
        'fixed inset-x-0 bottom-0 z-40 border-t border-border-strong bg-surface lg:hidden safe-area-bottom',
        'hidden' => $hidden,
    ]) }}
    aria-label="Primary"
>
    <div class="flex h-16 items-center justify-around px-2">
        {{ $slot }}
    </div>
</nav>
