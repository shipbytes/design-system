@props([
    /** The one line that says what is not here. */
    'title',
    /** Why it is empty, or what to do about it. One sentence. */
    'description' => null,
    /** Heroicon for the mark above the title. */
    'icon' => null,
    /** accent | success | warning | danger | neutral */
    'tone' => 'neutral',
    /** Drop the border and padding, for an empty state already inside a panel. */
    'bare' => false,
])

@php
    $tones = [
        'neutral' => 'bg-neutral-tint text-on-neutral-tint',
        'accent' => 'bg-accent-tint text-on-accent-tint',
        'success' => 'bg-success-tint text-on-success-tint',
        'warning' => 'bg-warning-tint text-on-warning-tint',
        'danger' => 'bg-danger-tint text-on-danger-tint',
    ];

    $tile = $tones[$tone] ?? $tones['neutral'];

    // An empty state is not an error. `neutral` is the default because most
    // emptiness is simply the beginning — nothing has gone wrong yet, and a
    // coloured tile on a brand-new account tells the reader it has.
    $classes = implode(' ', [
        'flex flex-col items-center justify-center text-center',
        $bare
            ? 'px-4 py-8'
            : 'rounded-panel border border-dashed border-border-strong bg-surface px-6 py-12',
    ]);
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    @if ($icon)
        {{-- Decorative: the title below says the same thing in words, and an icon
             announced before it delays the sentence that matters. --}}
        <span class="mb-4 flex size-12 items-center justify-center rounded-full {{ $tile }}">
            <x-ds::icon :name="$icon" size="6" />
        </span>
    @endif

    {{-- section, not heading: this sits INSIDE a page that already has a
         heading, and a second display-weight line competes with it. --}}
    <p class="text-section font-semibold text-fg">{{ $title }}</p>

    @if ($description)
        {{-- max-w so the sentence wraps at a readable measure instead of
             stretching the full width of a table it replaced. --}}
        <p class="mt-1.5 max-w-sm text-body text-fg-muted">{{ $description }}</p>
    @endif

    @if (isset($action))
        <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
            {{ $action }}
        </div>
    @endif
</div>
