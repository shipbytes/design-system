@props([
    'href' => null,
    /**
     * The page you are on. It is rendered as TEXT, not a link — a link to the
     * current page is a promise of somewhere to go that goes nowhere.
     */
    'current' => false,
])

<li class="flex min-w-0 items-center gap-1.5">
    {{-- Hidden from assistive technology: the list structure already says these
         are steps, and a screen reader reading "chevron right" between every
         crumb is noise. The parent hides the first one visually. --}}
    <x-ds::icon name="chevron-right" variant="micro" size="3.5" class="shrink-0 text-fg-subtle" />

    @if ($current || ! $href)
        {{-- aria-current is what tells a screen reader which crumb is the page.
             Weight alone does not carry across. --}}
        <span
            {{ $attributes->merge(['class' => 'truncate font-medium text-fg']) }}
            @if ($current) aria-current="page" @endif
        >{{ $slot }}</span>
    @else
        <a
            href="{{ $href }}"
            {{ $attributes->merge([
                'class' => 'truncate rounded-chip text-fg-muted transition-colors hover:text-fg '
                    .'focus-visible:outline-2 focus-visible:outline-offset-2 '
                    .'focus-visible:outline-focus-ring',
            ]) }}
        >{{ $slot }}</a>
    @endif
</li>
