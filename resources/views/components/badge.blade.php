@props([
    /** neutral | accent | success | warning | danger */
    'tone' => 'neutral',
    /** solid | tint | outline */
    'variant' => 'tint',
    /** Leading status dot. Use when the badge reports a live state. */
    'dot' => false,
])

@php
    // Tints are the default because a badge is an annotation, not an action.
    // Light uses the fixed 100/700 pair; dark uses 15% of the base over the
    // surface, so both themes come from one rule instead of two ramps that
    // can drift. See specs/color.md.
    $tints = [
        'neutral' => 'bg-neutral-tint text-on-neutral-tint',
        'accent' => 'bg-accent-tint text-on-accent-tint',
        'success' => 'bg-success-tint text-on-success-tint',
        'warning' => 'bg-warning-tint text-on-warning-tint',
        'danger' => 'bg-danger-tint text-on-danger-tint',
    ];

    $solids = [
        'neutral' => 'bg-surface-inverse text-on-inverse',
        'accent' => 'bg-accent text-on-accent',
        'success' => 'bg-success text-on-inverse',
        'warning' => 'bg-warning text-on-inverse',
        'danger' => 'bg-danger text-on-danger',
    ];

    $dots = [
        'neutral' => 'bg-fg-muted',
        'accent' => 'bg-accent',
        'success' => 'bg-success',
        'warning' => 'bg-warning',
        'danger' => 'bg-danger',
    ];

    $tone = array_key_exists($tone, $tints) ? $tone : 'neutral';

    $look = match ($variant) {
        'solid' => $solids[$tone],
        'outline' => 'border border-border text-fg-body',
        default => $tints[$tone],
    };

    $classes = implode(' ', [
        'inline-flex items-center gap-1.5 rounded-full',
        'px-2 py-0.5 text-meta font-medium whitespace-nowrap',
        $look,
    ]);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if ($dot)
        {{-- Decorative: the label already says what the state is. --}}
        <span class="size-1.5 rounded-full {{ $dots[$tone] }}" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>
