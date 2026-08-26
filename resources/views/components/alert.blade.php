@props([
    /** success | warning | danger | accent */
    'tone' => 'accent',
    /** Bold first line. Omit for the single-line form. */
    'title' => null,
    /** Heroicon name. Defaults to one that matches the tone. */
    'icon' => null,
    /**
     * Show a dismiss control. Only for alerts the reader may safely ignore —
     * a blocking error has no dismiss, because dismissing it would hide the
     * reason the thing they asked for did not happen.
     */
    'dismissible' => false,
])

@php
    $tone = in_array($tone, ['success', 'warning', 'danger', 'accent'], true) ? $tone : 'accent';

    // A wash, not a tint: this is a large surface, and the badge-strength fill
    // reads as shouting at panel size. See color.md.
    $tones = [
        'success' => ['bg' => 'bg-success-wash', 'border' => 'border-success/25', 'fg' => 'text-on-success-tint', 'icon' => 'check-circle'],
        'warning' => ['bg' => 'bg-warning-wash', 'border' => 'border-warning/25', 'fg' => 'text-on-warning-tint', 'icon' => 'exclamation-triangle'],
        'danger' => ['bg' => 'bg-danger-wash', 'border' => 'border-danger/25', 'fg' => 'text-on-danger-tint', 'icon' => 'exclamation-circle'],
        'accent' => ['bg' => 'bg-accent-wash', 'border' => 'border-accent/25', 'fg' => 'text-on-accent-tint', 'icon' => 'information-circle'],
    ];

    $t = $tones[$tone];
    $classes = "flex items-start gap-3 rounded-control border p-4 {$t['bg']} {$t['border']} {$t['fg']}";
@endphp

<div
    {{ $attributes->merge(['class' => $classes]) }}
    {{-- `status` for good news, `alert` for bad: an assertive live region
         interrupts whatever a screen reader is saying, which is right for a
         failure and rude for a confirmation. --}}
    role="{{ $tone === 'danger' ? 'alert' : 'status' }}"
>
    <x-ds::icon :name="$icon ?? $t['icon']" size="4.5" class="mt-0.5" />

    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="text-body font-semibold">{{ $title }}</p>
            <div class="mt-0.5 text-body">{{ $slot }}</div>
        @else
            <div class="text-body">{{ $slot }}</div>
        @endif
    </div>

    @if ($dismissible)
        <button
            type="button"
            class="-m-1 shrink-0 rounded-chip p-1 opacity-60 transition-opacity hover:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
            aria-label="Dismiss"
            {{-- A plain handler, not Alpine's @click.
                 Nothing else in this component needs JavaScript, and an alert is
                 the kind of thing that lands in a page that has none. With
                 `@click` the ✕ rendered, looked pressable, and did nothing at
                 all — no error, no clue. Removing the element is self-contained
                 enough not to be worth a dependency. --}}
            onclick="this.closest('[role]').remove()"
        >
            <x-ds::icon name="x-mark" />
        </button>
    @endif
</div>
