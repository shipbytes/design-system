@props([
    /**
     * How far along, from 0 to `max`. Null is indeterminate: work is happening
     * and nobody knows how much is left. Out-of-range values are clamped.
     */
    'value' => null,
    /** What `value` counts up to. */
    'max' => 100,
    /** What is in progress. Always the accessible name; shown unless `hideLabel`. */
    'label' => null,
    /** Keep the name for assistive technology, drop it from the screen. */
    'hideLabel' => false,
    /** Print the percentage. The default slot replaces it with your own wording. */
    'showValue' => false,
    /** neutral | accent | success | warning | danger */
    'tone' => 'neutral',
    /** sm | md */
    'size' => 'md',
    /** bar | circle */
    'variant' => 'bar',
])

@php
    /*
     * A progressbar with no name is announced as "progress bar, 40 percent" with
     * nothing to say WHAT is 40 percent done, and on a page with two of them the
     * reader cannot tell them apart. Fail loud, like a missing slot: it is a
     * template mistake, never data.
     */
    if (($label === null || trim((string) $label) === '')
        && ! $attributes->has('aria-label')
        && ! $attributes->has('aria-labelledby')) {
        throw new \InvalidArgumentException(
            'ds::progress needs a label, the name a screen reader announces. '
            .'Pass label="Uploading report.pdf" (add :hideLabel="true" to keep it off screen), '
            .'or aria-labelledby pointing at text already on the page.'
        );
    }

    $max = is_numeric($max) && (float) $max > 0 ? (float) $max : 100.0;
    $indeterminate = $value === null || $value === '';

    // Clamped HERE, not trusted to the browser: aria-valuenow outside min/max is
    // invalid, and a width over 100% would push the fill out of its track.
    $now = $indeterminate ? null : min(max((float) $value, 0.0), $max);
    $percent = $indeterminate ? null : round($now / $max * 100, 2);

    // 42.0 prints as "42": the attribute is read aloud.
    $number = fn (float $n): string => rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');

    $tone = in_array($tone, ['neutral', 'accent', 'success', 'warning', 'danger'], true) ? $tone : 'neutral';
    $size = $size === 'sm' ? 'sm' : 'md';
    $circle = $variant === 'circle';

    $custom = trim((string) $slot) !== '';
    $valueShown = ! $indeterminate && ($showValue || $custom);
    $valueText = $custom ? $slot : round($percent).'%';
    $labelShown = ! $hideLabel && $label !== null && trim((string) $label) !== '';

    /*
     * The fill reads its length from --ds-progress, a plain number from 0 to 100
     * set on THIS element. That is what lets a host drive it live: one binding on
     * the component tag moves the bar or the ring, with no class to rebuild and
     * no inner element to reach. See docs/components/progress.md.
     */
    $rootAttributes = $attributes
        ->merge(array_filter([
            'role' => 'progressbar',
            'aria-label' => $attributes->has('aria-labelledby') ? null : $label,
            'aria-valuemin' => '0',
            'aria-valuemax' => $number($max),
            'aria-valuenow' => $indeterminate ? null : $number($now),
            'style' => $indeterminate ? null : '--ds-progress: '.$number($percent),
        ], fn ($v) => $v !== null))
        ->class([
            'w-full' => ! $circle,
            'inline-flex items-center gap-2 align-middle' => $circle,
        ]);

    $text = $size === 'sm' ? 'text-meta' : 'text-body';

    // Closed sets of literal classes. size-{$n} built from a prop would be
    // invisible to Tailwind's scanner: the icon trap in CLAUDE.md.
    $rings = ['sm' => 'size-5', 'md' => 'size-14'];
    $strokes = ['sm' => '4', 'md' => '3'];
    $strokeTones = [
        'neutral' => 'text-fg',
        'accent' => 'text-accent',
        'success' => 'text-success',
        'warning' => 'text-warning',
        'danger' => 'text-danger',
    ];
    $valueInside = $circle && $size === 'md' && $valueShown && ! $custom;
@endphp

<div {{ $rootAttributes }}>
    @if ($circle)
        <span class="relative inline-grid shrink-0 place-items-center {{ $rings[$size] }}" aria-hidden="true">
            {{-- r = 100 / 2π, so the circumference is exactly 100 and the dash
                 length IS the percentage. pathLength would do the same, but older
                 Safari ignores it on a circle. --}}
            <svg viewBox="0 0 36 36" fill="none" @class(['size-full -rotate-90', 'motion-safe:animate-spin' => $indeterminate])>
                <circle cx="18" cy="18" r="15.9155" stroke="currentColor" stroke-width="{{ $strokes[$size] }}" class="text-fg/10" />
                @if ($indeterminate)
                    <circle cx="18" cy="18" r="15.9155" stroke="currentColor" stroke-width="{{ $strokes[$size] }}"
                        stroke-dasharray="25 75" class="hidden motion-safe:inline {{ $strokeTones[$tone] }}" />
                    {{-- Reduced motion: a dashed ring, the circle's version of the bar's stripes. --}}
                    <circle cx="18" cy="18" r="15.9155" stroke="currentColor" stroke-width="{{ $strokes[$size] }}"
                        stroke-dasharray="4 4" class="motion-safe:hidden {{ $strokeTones[$tone] }}" />
                @else
                    <circle cx="18" cy="18" r="15.9155" stroke="currentColor" stroke-width="{{ $strokes[$size] }}"
                        style="stroke-dasharray: var(--ds-progress, 0) 100"
                        class="{{ $strokeTones[$tone] }} motion-safe:transition-[stroke-dasharray] motion-safe:duration-slide-enter motion-safe:ease-enter" />
                @endif
            </svg>
            @if ($valueInside)
                <span class="absolute text-meta font-medium tabular-nums text-fg">{{ $valueText }}</span>
            @endif
        </span>

        @if ($labelShown || ($valueShown && ! $valueInside))
            {{-- Hidden from assistive technology because the role above already
                 carries the name and the value; read twice otherwise. --}}
            <span class="flex min-w-0 items-baseline gap-2 {{ $text }}" aria-hidden="true">
                @if ($labelShown)
                    <span class="truncate font-medium text-fg">{{ $label }}</span>
                @endif
                @if ($valueShown && ! $valueInside)
                    <span class="shrink-0 tabular-nums text-fg-muted">{{ $valueText }}</span>
                @endif
            </span>
        @endif
    @else
        @if ($labelShown || $valueShown)
            <div class="mb-1.5 flex items-baseline gap-3 {{ $text }}" aria-hidden="true">
                @if ($labelShown)
                    <span class="min-w-0 truncate font-medium text-fg">{{ $label }}</span>
                @endif
                @if ($valueShown)
                    <span class="ml-auto shrink-0 tabular-nums text-fg-muted">{{ $valueText }}</span>
                @endif
            </div>
        @endif

        @include('ds::partials.bar', [
            'size' => $size,
            'tone' => $tone,
            'indeterminate' => $indeterminate,
            'fills' => [['width' => 'calc(var(--ds-progress, 0) * 1%)', 'tone' => $tone]],
        ])
    @endif
</div>
