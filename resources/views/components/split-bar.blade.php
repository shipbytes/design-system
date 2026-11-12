@props([
    /**
     * The parts: ['Free' => 70, 'Pro' => 25], or rows with `label`, `value`
     * and an optional `tone` or `series`. Every value must be a number of
     * zero or more; a zero is listed and not drawn.
     */
    'data' => [],
    /** The whole, when it is more than the parts: the rest draws as empty track (storage with room left). */
    'total' => null,
    /** What the whole is, "Plan mix": the start of the generated summary and the table's caption. */
    'label' => null,
    /** How numbers read: see Support\Chart::format(). */
    'format' => [],
    /** below | inline | none — a legend under the bar, each part named above its own segment, or neither. */
    'legend' => 'below',
    /** sm | md | lg — the bar's thickness. */
    'size' => 'md',
    /** value | percent | both — the number written beside each part's name. */
    'showValues' => 'both',
    /** What the empty rest of the track is called, when `total` leaves one. */
    'remainderLabel' => 'Remaining',
    /** The text alternative, in place of the generated one. */
    'summary' => null,
    /** Also give the values to assistive technology as a visually hidden table. */
    'table' => false,
    /** With the package's script: a readout on hover and focus, and the segments reachable by keyboard. */
    'interactive' => true,
    /** What the empty state says when there is nothing to draw. */
    'empty' => 'No data to show',
])

@use('Shipbytes\BladeUi\Support\Chart')

@php
    $format = is_array($format) ? $format : [];
    $f = fn (float $v) => Chart::format($v, $format);

    /*
     * A part of less than nothing would draw backwards over its neighbour,
     * and a word is not an amount: both are the host's to fix, so they
     * throw, naming the part. A zero is a real answer (no blocked tickets
     * this week), so it stays in the legend and draws no segment.
     */
    $parts = [];
    foreach (Chart::rows(is_iterable($data) ? $data : []) as $i => $row) {
        if (! is_numeric($row['value']) || (float) $row['value'] < 0) {
            throw new \InvalidArgumentException(
                'ds::split-bar draws the parts of a whole, so every value must be a number of zero or more; ['
                .$row['label'].'] is '.var_export($row['value'], true).'. Use ds::bar-chart for values that can be negative.'
            );
        }
        $slot = Chart::series($row['series']) ?? ($row['tone'] === null ? $i + 1 : null);
        $parts[] = [
            'label' => $row['label'],
            'value' => (float) $row['value'],
            'colour' => Chart::colour($row['tone'], $slot),
            'slot' => $slot === null ? '' : (string) $slot,
        ];
    }
    if (count($parts) > 8) {
        throw new \InvalidArgumentException(
            'ds::split-bar draws at most 8 parts, one per series colour, and was given '.count($parts).'. '
            .'Fold the smallest into an "Other" part (specs/chart-series.md).'
        );
    }

    $sum = array_sum(array_column($parts, 'value'));
    $whole = max($sum, is_numeric($total) ? (float) $total : 0.0);
    $rest = $whole - $sum;
    $nothing = $parts === [] || $whole <= 0;

    $recipe = Chart::recipe();
    $legend = in_array($legend, ['below', 'inline', 'none'], true) ? $legend : 'below';
    $showValues = in_array($showValues, ['value', 'percent', 'both'], true) ? $showValues : 'both';
    $sizeKey = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';

    // The rest of the track takes part in the rounding, so the shares on
    // screen, the rest's included, always add up to exactly 100.
    $shares = $nothing ? [] : Chart::percentages(array_merge(array_column($parts, 'value'), $rest > 0 ? [$rest] : []));
    $restShare = $rest > 0 ? array_pop($shares) : 0;

    // A part too small to round to a whole percent is still on the bar, so
    // it reads as under one, not as none. The shares underneath still add up.
    $pct = fn (int $share, float $v) => ($share === 0 && $v > 0 ? '<1' : $share).'%';
    $number = fn (float $v, int $share) => match ($showValues) {
        'value' => $f($v),
        'percent' => $pct($share, $v),
        default => $f($v).' · '.$pct($share, $v),
    };

    $segments = [];
    foreach ($parts as $i => $part) {
        $segments[] = $part + [
            'share' => $shares[$i] ?? 0,
            'basis' => $nothing ? 0.0 : $part['value'] / $whole * 100,
            'says' => $f($part['value']).' ('.$pct($shares[$i] ?? 0, $part['value']).')',
            'number' => $number($part['value'], $shares[$i] ?? 0),
            'rest' => false,
        ];
    }
    if ($rest > 0 && ! $nothing) {
        $segments[] = [
            'label' => (string) $remainderLabel, 'value' => $rest, 'colour' => $recipe['track'], 'slot' => '',
            'share' => $restShare, 'basis' => $rest / $whole * 100,
            'says' => $f($rest).' ('.$pct($restShare, $rest).')', 'number' => $number($rest, $restShare), 'rest' => true,
        ];
    }

    /*
     * Inline, a part's name sits over its own segment while the segment is
     * wide enough to hold it, and moves to a line under the bar when it is
     * not. Nothing is measured: the width the name needs is estimated, the
     * box width at which its segment reaches that is worked out from the
     * share, and two container queries that are exact opposites show the
     * one and hide the other. A part is always named somewhere.
     */
    foreach ($segments as &$segment) {
        // Generous: the segment's share is of the bar less its gaps, and an
        // estimate that runs narrow cuts the name to an ellipsis.
        $need = Chart::textWidth($segment['label']) + 4 + Chart::textWidth($segment['number']) + 20;
        $at = $segment['basis'] > 0 ? $need * 100 / $segment['basis'] : INF;
        $segment['inline'] = Chart::below($at);
        $segment['fallback'] = Chart::above($at);
    }
    unset($segment);

    $text = $summary !== null && trim((string) $summary) !== ''
        ? trim((string) $summary)
        : Chart::whole(
            array_map(fn ($p) => ['label' => $p['label'], 'value' => $p['value']], $parts),
            array_map(fn ($s) => rtrim($pct($s['share'], $s['value']), '%'), array_slice($segments, 0, count($parts))),
            $format, $label, $legend === 'none', $rest > 0 ? $whole : null,
        );

    // Thick enough to see, and at least as wide as it is tall at the ends,
    // where the rounding would otherwise shave a sliver to nothing.
    $thick = ['sm' => 'h-2', 'md' => 'h-3', 'lg' => 'h-5'][$sizeKey];
    $least = ['sm' => 'min-w-2', 'md' => 'min-w-3', 'lg' => 'min-w-5'][$sizeKey];

    $items = fn (bool $fallback) => array_map(fn ($s) => [
        'name' => $s['label'],
        'colour' => $s['colour'],
        'series' => null,
        'detail' => $s['number'],
        'class' => $fallback ? $s['fallback'] : '',
    ], $fallback ? array_values(array_filter($segments, fn ($s) => $s['fallback'] !== 'hidden')) : $segments);

    $interactive = (bool) $interactive && ! $nothing;
    $root = $attributes->class(['relative flex flex-col gap-3']);
    if ($interactive) {
        $root = $root->merge(['x-data' => '', 'x-ds-chart' => '', 'data-ds-chart-axis' => 'both']);
    }
    $mark = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring data-ds-active:opacity-85';
@endphp

<div {{ $root }}>
    @if ($nothing)
        <x-ds::empty-state :title="(string) $empty" icon="chart-bar" :bare="true" />
    @else
        <div class="@container flex flex-col gap-1.5">
            @if ($legend === 'inline')
                {{-- The same flex as the bar under it, so each name starts
                     where its segment does. --}}
                <div class="flex gap-0.5 text-meta">
                    @foreach ($segments as $segment)
                        @if ($segment['basis'] > 0)
                            <div class="min-w-0 {{ $least }}" style="flex: 0 1 {{ Chart::n($segment['basis']) }}%">
                                <span class="{{ trim('block truncate '.$segment['inline']) }}"><span class="{{ $segment['rest'] ? 'text-fg-muted' : 'text-fg' }}">{{ $segment['label'] }}</span><span class="ps-1 tabular-nums text-fg-muted">{{ $segment['number'] }}</span></span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Widths from inline style, never a class: a class built from a
                 number is invisible to Tailwind's scanner. The 2px between
                 segments is the gap showing the surface behind; the outer
                 segments alone are rounded. --}}
            <div data-ds-chart-plot role="img" aria-label="{{ $text }}" class="flex gap-0.5 {{ $thick }}">
                @foreach ($segments as $i => $segment)
                    @if ($segment['basis'] > 0)
                        <div style="flex: 0 1 {{ Chart::n($segment['basis']) }}%"
                            class="{{ trim('h-full shrink first:rounded-s-full last:rounded-e-full bg-current '.$least.' '.$segment['colour'].($interactive ? ' '.$mark : '')) }}"
                            @if ($interactive)
                                data-ds-chart-mark data-ds-at="{{ $i }}" data-ds-in="0"
                                data-ds-label="{{ $segment['label'] }}" data-ds-value="{{ $segment['says'] }}" data-ds-series="" data-ds-slot="{{ $segment['slot'] }}"
                                aria-label="{{ $segment['label'] }}: {{ $segment['says'] }}"
                            @endif
                        ></div>
                    @endif
                @endforeach
            </div>

            @if ($legend === 'inline')
                @php $fallback = $items(true); @endphp
                @if ($fallback !== [])
                    @include('ds::partials.chart-legend', ['items' => $fallback, 'toggles' => false, 'column' => false])
                @endif
            @endif
        </div>

        @if ($legend === 'below')
            @include('ds::partials.chart-legend', ['items' => $items(false), 'toggles' => false, 'column' => false])
        @endif

        @if ($table)
            @include('ds::partials.chart-table', [
                'caption' => $label !== null && trim((string) $label) !== '' ? $label : $text,
                'headers' => ['Part', 'Value', 'Share'],
                'rows' => array_merge(
                    array_map(fn ($s) => [$s['label'], $f($s['value']), $pct($s['share'], $s['value'])], $segments),
                    [['Total', $f($whole), '100%']],
                ),
            ])
        @endif

        @if ($interactive)
            @include('ds::partials.chart-readout')
        @endif
    @endif
</div>
