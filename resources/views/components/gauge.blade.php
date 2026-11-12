@props([
    /** The reading. Clamped into `min`..`max` for the arc and the ARIA value; printed as given. */
    'value' => null,
    /** The bottom of the range. */
    'min' => 0,
    /** The top of the range. */
    'max' => 100,
    /** What is being measured. Always the accessible name; shown under the gauge unless `hideLabel`. */
    'label' => null,
    /** Keep the name for assistive technology, drop it from the screen. */
    'hideLabel' => false,
    /** semi | ring */
    'variant' => 'semi',
    /**
     * Bands of the range, each ['at' => n, 'tone' => …, 'label' => …]: from
     * `at` upward the arc takes the tone, and the label (or the tone's word)
     * is said with the value. Wins over `tone`.
     */
    'thresholds' => null,
    /** neutral | accent | success | warning | danger — the arc's colour when there are no thresholds. */
    'tone' => 'neutral',
    /** sm | md | lg */
    'size' => 'md',
    /** Print the value in the middle. */
    'showValue' => true,
    /** How the value reads: see Support\Chart::format(). `['suffix' => '%']` for a percentage. */
    'format' => [],
])

@use('Shipbytes\BladeUi\Support\Chart')

@php
    /*
     * A meter with no name is announced as "meter, 72" with nothing to say
     * what is 72, and two on a page cannot be told apart. A template mistake,
     * never data, so it fails loud, as ds::progress does.
     */
    if (($label === null || trim((string) $label) === '')
        && ! $attributes->has('aria-label')
        && ! $attributes->has('aria-labelledby')) {
        throw new \InvalidArgumentException(
            'ds::gauge needs a label, the name a screen reader announces. '
            .'Pass label="CPU" (add :hideLabel="true" to keep it off screen), '
            .'or aria-labelledby pointing at text already on the page.'
        );
    }

    $format = is_array($format) ? $format : [];
    $min = is_numeric($min) ? (float) $min : 0.0;
    $max = is_numeric($max) ? (float) $max : 100.0;
    if ($max < $min) {
        [$min, $max] = [$max, $min];
    }

    $known = is_numeric($value);
    $now = $known ? max($min, min($max, (float) $value)) : $min;
    $fraction = $max > $min ? ($now - $min) / ($max - $min) : 0.0;

    /*
     * The band the value is in: the highest `at` it has reached. Each band
     * says what the colour means in a word, because the colour alone says
     * nothing to a reader who cannot see it or tell amber from green.
     */
    $words = ['success' => 'Good', 'warning' => 'Warning', 'danger' => 'Critical'];
    $bands = collect(is_iterable($thresholds) ? $thresholds : [])
        ->filter(fn ($band) => is_numeric(data_get($band, 'at')))
        ->map(fn ($band) => [
            'at' => (float) data_get($band, 'at'),
            'tone' => Chart::tone(data_get($band, 'tone')),
            'label' => data_get($band, 'label'),
        ])
        ->sortBy('at')
        ->values();

    $band = $known ? $bands->last(fn ($band) => (float) $value >= $band['at']) : null;
    $tone = $bands->isNotEmpty() ? ($band['tone'] ?? 'neutral') : Chart::tone($tone);
    $meaning = $band === null ? null : ($band['label'] ?? ($words[$band['tone']] ?? null));
    $meaning = $meaning !== null && trim((string) $meaning) !== '' ? trim((string) $meaning) : null;

    $printed = $known ? Chart::format($value, $format) : '—';

    // The arc stops at the end of the range and the printed value does not,
    // so a reading past either end is said in words: the meter's own value
    // has to sit inside the range and cannot carry it.
    $outside = match (true) {
        ! $known => null,
        (float) $value > $max => 'above the maximum of '.Chart::format($max, $format),
        (float) $value < $min => 'below the minimum of '.Chart::format($min, $format),
        default => null,
    };
    $plain = fn (float $n): string => rtrim(rtrim(number_format($n, 4, '.', ''), '0'), '.');

    $ring = $variant === 'ring';
    $size = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';

    /*
     * The arc is the track's own path, drawn again with a dash as long as
     * the reading. Its length is computed here rather than left to
     * pathLength, which older Safari ignores.
     */
    [$cx, $cy, $r] = [50.0, 50.0, 42.0];
    [$from, $to] = $ring ? [0.0, 360.0] : [-90.0, 90.0];
    $arc = Chart::arc($cx, $cy, $r, $from, $to);
    $length = Chart::arcLength($r, $to - $from);
    $dash = Chart::n($length * $fraction).' '.Chart::n($length + 1);
    $stroke = ['sm' => '12', 'md' => '9', 'lg' => '8'][$size];
    $viewBox = $ring ? '0 0 100 100' : '0 0 100 56';

    // Literal classes, looked up: see the icon trap in CLAUDE.md.
    $boxes = $ring
        ? ['sm' => 'size-12', 'md' => 'size-24', 'lg' => 'size-32']
        : ['sm' => 'w-20', 'md' => 'w-36', 'lg' => 'w-48'];
    $figures = ['sm' => 'text-meta font-medium', 'md' => 'text-title', 'lg' => 'text-display'];

    $recipe = Chart::recipe();
    $detail = $size !== 'sm';
    // The word goes inside a semi gauge, under the value, where the open
    // bottom leaves room. A ring's hole is narrower than "Overloaded" at
    // the medium size, and a small gauge has no room inside at all, so
    // there it goes under the label: shown at every size, because the
    // colour must never be the only thing saying it.
    $inside = ! $ring && $detail;
    $labelShown = ! $hideLabel && $label !== null && trim((string) $label) !== '';

    $meter = $attributes
        ->merge(array_filter([
            'role' => 'meter',
            'aria-label' => $attributes->has('aria-labelledby') ? null : $label,
            'aria-valuemin' => $plain($min),
            'aria-valuemax' => $plain($max),
            'aria-valuenow' => $known ? $plain($now) : null,
            'aria-valuetext' => $known ? implode(', ', array_filter([$printed, $outside, $meaning])) : 'No reading',
        ], fn ($v) => $v !== null))
        ->class(['inline-flex flex-col items-center gap-1 align-middle']);
@endphp

<div {{ $meter }}>
    <div class="relative {{ $boxes[$size] }}" aria-hidden="true">
        <svg viewBox="{{ $viewBox }}" fill="none" stroke-width="{{ $stroke }}" class="block w-full overflow-visible">
            <path d="{{ $arc }}" stroke="currentColor" class="{{ $recipe['track'] }}" />
            @if ($known && $fraction > 0)
                <path d="{{ $arc }}" stroke="currentColor" stroke-dasharray="{{ $dash }}" class="{{ Chart::colour($tone) }}" />
            @endif
        </svg>

        @if ($showValue || ($meaning !== null && $inside))
            <div @class([
                'absolute inset-x-0 flex flex-col items-center leading-none',
                'inset-y-0 justify-center' => $ring,
                'bottom-0' => ! $ring,
            ])>
                @if ($showValue)
                    <span class="tabular-nums text-fg {{ $figures[$size] }}">{{ $printed }}</span>
                @endif
                @if ($meaning !== null && $inside)
                    <span class="mt-1 max-w-full truncate text-meta font-medium text-fg-muted">{{ $meaning }}</span>
                @endif
            </div>
        @endif
    </div>

    @if (! $ring && $detail)
        <div class="flex w-full justify-between text-meta tabular-nums text-fg-muted {{ $boxes[$size] }}" aria-hidden="true">
            <span>{{ Chart::format($min, $format) }}</span>
            <span>{{ Chart::format($max, $format) }}</span>
        </div>
    @endif

    @if ($labelShown)
        {{-- Hidden from assistive technology: the meter already carries the
             name, and it would be read twice. --}}
        <span class="max-w-full truncate text-body font-medium text-fg" aria-hidden="true">{{ $label }}</span>
    @endif

    @if ($meaning !== null && ! $inside)
        <span class="-mt-1 max-w-full truncate text-meta font-medium text-fg-muted" aria-hidden="true">{{ $meaning }}</span>
    @endif
</div>
