@props([
    /**
     * The values, oldest first. A list, or keyed by what each one is
     * (['Mon' => 3, 'Tue' => 5]), which names the rows of `table`. Null, or
     * anything not numeric, is a gap.
     */
    'values' => [],
    /** line | area | bar */
    'variant' => 'line',
    /** neutral | accent | success | warning | danger — for a trend whose colour means something. */
    'tone' => 'neutral',
    /** 1 to 8 — the chart-series slot, for a trend that is one of several. Wins over `tone`. */
    'series' => null,
    /** sm | md | lg | full — 64, 96 or 128px, or the container's width. */
    'width' => 'md',
    /** sm | md | lg — 16, 24 or 32px. */
    'height' => 'md',
    /** The bottom of the scale. Default: the lowest value (zero, for bars). */
    'min' => null,
    /** The top of the scale. Default: the highest value (zero, for bars that are all negative). */
    'max' => null,
    /** A dot on the latest value. */
    'showLast' => false,
    /** What the values are, "Signups": the start of the generated summary, and the table's caption. */
    'label' => null,
    /** The text alternative, in place of the generated one. */
    'summary' => null,
    /** Also give the values to assistive technology as a visually hidden table. */
    'table' => false,
    /** How numbers read in the summary and the table: see Support\Chart::format(). */
    'format' => [],
])

@use('Shipbytes\BladeUi\Support\Chart')

@php
    $values = Chart::values(is_iterable($values) ? $values : []);
    $format = is_array($format) ? $format : [];
    $variant = in_array($variant, ['line', 'area', 'bar'], true) ? $variant : 'line';
    $min = is_numeric($min) ? (float) $min : null;
    $max = is_numeric($max) ? (float) $max : null;

    // Literal classes, looked up: a size built from a prop is invisible to
    // Tailwind's scanner (the icon trap in CLAUDE.md).
    $widths = ['sm' => 'w-16', 'md' => 'w-24', 'lg' => 'w-32', 'full' => 'w-full'];
    $heights = ['sm' => 'h-4', 'md' => 'h-6', 'lg' => 'h-8'];

    /*
     * One viewBox for every size, stretched to the box with
     * preserveAspectRatio="none": the chart fills whatever width it is given,
     * a stat tile's included. Stretching would thicken a stroke unevenly, so
     * every stroke is non-scaling, which keeps it at its CSS width in any box.
     */
    [$w, $h] = [100.0, 30.0];

    $present = array_filter($values, fn ($v) => $v !== null);
    $empty = $present === [];

    $line = $area = '';
    $bars = [];
    $last = null;
    $baseline = null;

    if (! $empty && $variant === 'bar') {
        $bars = array_filter(Chart::bars($values, $w, $h, $min, $max, gap: count($values) > 24 ? 1 : 2));
        [$lo, $hi] = Chart::domain($values, $min, $max, zero: true);
        // A visible zero only where it is not the floor: with negatives, the
        // bars hang from it and the reader needs to see where it is.
        $baseline = $lo < 0 ? Chart::baseline($h, $lo, $hi) : null;
        $tail = array_key_last($present);
        $lastBar = $bars[$tail] ?? null;
        $last = $lastBar ? [$lastBar['x'] + $lastBar['width'] / 2, $present[$tail] < 0 ? $lastBar['y'] + $lastBar['height'] : $lastBar['y']] : null;
    } elseif (! $empty) {
        $points = Chart::points($values, $w, $h, $min, $max);
        $line = Chart::line($points);
        if ($variant === 'area') {
            [$lo, $hi] = Chart::domain($values, $min, $max);
            $area = Chart::area($points, Chart::baseline($h, $lo, $hi));
        }
        $last = $points[array_key_last($present)];
    }

    // A single value is a dot whatever was asked for: there is no line to draw.
    $dot = $last !== null && ($showLast || count($present) === 1);

    $text = $summary !== null && trim((string) $summary) !== ''
        ? trim((string) $summary)
        : Chart::summary($values, $format, $label);

    $rows = [];
    if ($table) {
        $i = 0;
        foreach ($values as $key => $value) {
            $i++;
            $rows[] = [is_string($key) ? $key : (string) $i, Chart::format($value, $format)];
        }
    }

    $recipe = Chart::recipe();
    $classes = implode(' ', [
        'relative inline-flex shrink-0 align-middle',
        $widths[$width] ?? $widths['md'],
        $heights[$height] ?? $heights['md'],
        Chart::colour($tone, $series),
    ]);

    // A table cannot sit in a span; with one, the root is a div.
    $tag = $table ? 'div' : 'span';
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $classes]) }}>
    <svg viewBox="0 0 {{ Chart::n($w) }} {{ Chart::n($h) }}" preserveAspectRatio="none" role="img" aria-label="{{ $text }}"
        class="size-full overflow-visible" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
        @if ($empty)
            {{-- An empty trend still takes its space, with a faint rule, so a
                 row of tiles does not jump when one has nothing yet. --}}
            <path d="M0 {{ Chart::n($h / 2) }}H{{ Chart::n($w) }}" stroke-width="1" stroke-dasharray="2 3" vector-effect="non-scaling-stroke" class="{{ $recipe['track'] }}" />
        @elseif ($variant === 'bar')
            @if ($baseline !== null)
                <path d="M0 {{ Chart::n($baseline) }}H{{ Chart::n($w) }}" stroke-width="1" vector-effect="non-scaling-stroke" class="{{ $recipe['track'] }}" />
            @endif
            <g fill="currentColor" stroke="none">
                @foreach ($bars as $bar)
                    <rect x="{{ Chart::n($bar['x']) }}" y="{{ Chart::n($bar['y']) }}" width="{{ Chart::n($bar['width']) }}" height="{{ Chart::n($bar['height']) }}" />
                @endforeach
            </g>
        @else
            @if ($area !== '')
                <path d="{{ $area }}" fill="currentColor" fill-opacity="0.15" stroke="none" />
            @endif
            <path d="{{ $line }}" stroke-width="1.5" vector-effect="non-scaling-stroke" />
        @endif

        @if ($dot)
            {{-- A zero-length segment with a round cap, not a circle: under
                 preserveAspectRatio="none" a circle stretches into an
                 ellipse, and a non-scaling stroke stays round. --}}
            <path d="M{{ Chart::n($last[0]) }} {{ Chart::n($last[1]) }}h0" stroke-width="5" vector-effect="non-scaling-stroke" />
        @endif
    </svg>

    @if ($table)
        @include('ds::partials.chart-table', [
            'caption' => $label !== null && trim((string) $label) !== '' ? $label : $text,
            'headers' => ['Point', 'Value'],
            'rows' => $rows,
        ])
    @endif
</{{ $tag }}>
