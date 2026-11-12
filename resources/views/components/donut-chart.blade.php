@props([
    /** The parts of a whole: ['Direct' => 1200, 'Search' => 800, …]. Every value must be above zero. */
    'data' => [],
    /** What the whole is, "Traffic by source": the start of the generated summary and the table's caption. */
    'label' => null,
    /** sm | md | lg — the ring's width. */
    'thickness' => 'md',
    /** sm | md | lg — 128, 160 or 208px across. */
    'size' => 'md',
    /** right | bottom | none — the legend, with each part's value and share. */
    'legend' => 'right',
    /** The most segments drawn (2 to 8). Past it, the smallest fold into one "Other". */
    'maxSegments' => 6,
    /** What the folded segment is called. */
    'otherLabel' => 'Other',
    /** Print the total in the middle (the `centre` slot replaces it). */
    'showTotal' => true,
    /** The word under the total. */
    'totalLabel' => 'Total',
    /** How numbers read: see Support\Chart::format(). */
    'format' => [],
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
     * A donut is parts of a whole, and a part of nothing or less than
     * nothing has no arc: a zero would draw an invisible segment the legend
     * still lists, a negative would draw backwards over its neighbour. Both
     * are data the host should have filtered, so it is said loudly.
     */
    $parts = [];
    foreach (is_iterable($data) ? $data : [] as $name => $value) {
        if (! is_numeric($value) || (float) $value <= 0) {
            throw new \InvalidArgumentException(
                'ds::donut-chart draws parts of a whole, so every value must be a number above zero; ['
                .$name.'] is '.var_export($value, true).'. Leave out the parts that are empty, and use '
                .'ds::bar-chart for values that can be zero or negative.'
            );
        }
        $parts[] = ['name' => (string) $name, 'value' => (float) $value];
    }

    /*
     * Past `maxSegments`, the largest keep their place, in the order given,
     * and the rest fold into one segment at the end. Colours stay with the
     * parts by position (specs/chart-series.md); the folded one is neutral,
     * because "Other" is not one more thing.
     */
    $max = max(2, min(8, (int) $maxSegments));
    if (count($parts) > $max) {
        $keep = collect($parts)->sortByDesc('value')->keys()->take($max - 1)->sort()->values()->all();
        $rest = collect($parts)->except($keep);
        $parts = array_merge(
            array_values(array_map(fn ($i) => $parts[$i], $keep)),
            [['name' => (string) $otherLabel, 'value' => $rest->sum('value'), 'other' => true, 'folded' => $rest->count()]],
        );
    }

    $nothing = $parts === [];
    $total = array_sum(array_column($parts, 'value'));
    $shares = Chart::percentages(array_column($parts, 'value'));

    $recipe = Chart::recipe();
    foreach ($parts as $i => &$part) {
        $part['share'] = $shares[$i];
        $part['colour'] = ($part['other'] ?? false) ? $recipe['tones']['neutral'] : Chart::colour(null, $i + 1);
        $part['slot'] = ($part['other'] ?? false) ? 'other' : (string) ($i + 1);
        $part['says'] = $f($part['value']).' ('.$part['share'].'%)';
    }
    unset($part);

    $lead = $label !== null && trim((string) $label) !== '' ? trim((string) $label).': ' : '';
    $legend = in_array($legend, ['right', 'bottom', 'none'], true) ? $legend : 'right';
    $largest = collect($parts)->sortByDesc('value')->first();
    // With a legend, every part is already written out beside the ring, so
    // the image's name is the gist; without one it lists them all.
    $generated = $nothing ? '' : ($legend === 'none'
        ? $lead.collect($parts)->map(fn ($p) => $p['name'].' '.$p['says'])->implode(', ').'; total '.$f($total).'.'
        : $lead.count($parts).' parts, total '.$f($total).'; the largest is '.$largest['name'].' at '.$largest['share'].'%.');
    $text = $summary !== null && trim((string) $summary) !== '' ? trim((string) $summary) : ucfirst($generated);

    /*
     * Each segment is the same circle, dashed: a dash as long as its share
     * of the circumference, started where the one before it ended. The
     * circle is turned so that start is twelve o'clock. A px of the ring's
     * own surface is left between segments, as a gap in the dash, so two
     * neighbours never touch; one part alone is the whole ring.
     */
    $px = ['sm' => 128, 'md' => 160, 'lg' => 208][in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md'];
    $ring = ['sm' => 10, 'md' => 16, 'lg' => 22][in_array($thickness, ['sm', 'md', 'lg'], true) ? $thickness : 'md'];
    // Literal: the stroke a segment is drawn at while it is being read, a
    // little wider. The radius leaves room for it inside the box.
    $wider = ['sm' => 'data-ds-active:stroke-14', 'md' => 'data-ds-active:stroke-20', 'lg' => 'data-ds-active:stroke-26'][in_array($thickness, ['sm', 'md', 'lg'], true) ? $thickness : 'md'];
    $r = 50 - $ring / 2 - 2;
    $circumference = 2 * M_PI * $r;
    $gap = count($parts) > 1 ? 100 / $px : 0.0;
    $start = 0.0;
    foreach ($parts as &$part) {
        $length = $circumference * $part['value'] / $total;
        $part['dash'] = Chart::n(max(0.01, $length - $gap)).' '.Chart::n($circumference);
        $part['offset'] = Chart::n(-($start + $gap / 2));
        $start += $length;
    }
    unset($part);

    $boxes = ['sm' => 'size-32', 'md' => 'size-40', 'lg' => 'size-52'];
    $figures = ['sm' => 'text-body', 'md' => 'text-title', 'lg' => 'text-display'];
    // The centre is the hole, not the box: its edge is the ring's inner edge,
    // as a share of the box, so a long figure wraps inside the hole rather
    // than running over the segments.
    $hole = Chart::n($ring + 2);
    $sizeKey = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';

    $items = collect($parts)->map(fn ($p) => [
        'name' => $p['name'],
        'colour' => $p['colour'],
        'series' => null,
        'detail' => $f($p['value']).' · '.$p['share'].'%',
    ])->all();

    $interactive = (bool) $interactive && ! $nothing;
    $root = $attributes->class([
        'relative flex gap-x-6 gap-y-4 [direction:ltr]',
        'flex-wrap items-center' => $legend === 'right',
        'flex-col items-center' => $legend !== 'right',
    ]);
    if ($interactive) {
        $root = $root->merge(['x-data' => '', 'x-ds-chart' => '', 'data-ds-chart-axis' => 'both']);
    }
@endphp

<div {{ $root }}>
    @if ($nothing)
        <x-ds::empty-state :title="(string) $empty" icon="chart-pie" :bare="true" class="w-full" />
    @else
        {{-- The ring the keyboard draws when a segment is focused goes round
             the whole donut: an outline on a segment would be the circle's
             square box, the same for every segment. Which one it is shows
             in its width and in the readout. --}}
        <div class="relative shrink-0 rounded-full {{ $boxes[$sizeKey] }} has-focus-visible:outline-2 has-focus-visible:outline-offset-4 has-focus-visible:outline-focus-ring">
            <svg data-ds-chart-plot role="img" aria-label="{{ $text }}" viewBox="0 0 100 100" class="block size-full overflow-visible">
                @foreach ($parts as $i => $part)
                    <circle cx="50" cy="50" r="{{ Chart::n($r) }}" fill="none" stroke="currentColor" stroke-width="{{ $ring }}"
                        stroke-dasharray="{{ $part['dash'] }}" stroke-dashoffset="{{ $part['offset'] }}" transform="rotate(-90 50 50)"
                        class="{{ $part['colour'] }} {{ $interactive ? 'outline-none '.$wider : '' }}"
                        @if ($interactive)
                            data-ds-chart-mark data-ds-at="{{ $i }}" data-ds-in="0"
                            data-ds-label="{{ $part['name'] }}" data-ds-value="{{ $part['says'] }}" data-ds-series="" data-ds-slot="{{ $part['slot'] }}"
                            aria-label="{{ $part['name'] }}: {{ $part['says'] }}"
                        @endif
                    />
                @endforeach
            </svg>

            @if (isset($centre) || $showTotal)
                <div class="pointer-events-none absolute flex flex-col items-center justify-center px-1 text-center leading-tight break-words" style="inset: {{ $hole }}%">
                    @isset($centre)
                        {{ $centre }}
                    @else
                        {{-- The total is in the image's name already. --}}
                        <span aria-hidden="true" class="font-semibold tabular-nums text-fg {{ $figures[$sizeKey] }}">{{ $f($total) }}</span>
                        @if ($sizeKey !== 'sm' && trim((string) $totalLabel) !== '')
                            <span aria-hidden="true" class="mt-0.5 text-meta text-fg-muted">{{ $totalLabel }}</span>
                        @endif
                    @endisset
                </div>
            @endif
        </div>

        @if ($legend !== 'none')
            <div @class(['min-w-40 flex-1' => $legend === 'right', 'w-full max-w-xs' => $legend === 'bottom'])>
                @include('ds::partials.chart-legend', ['items' => $items, 'toggles' => false, 'column' => true])
            </div>
        @endif

        @if ($table)
            @include('ds::partials.chart-table', [
                'caption' => $label !== null && trim((string) $label) !== '' ? $label : $text,
                'headers' => ['Part', 'Value', 'Share'],
                'rows' => array_merge(
                    array_map(fn ($p) => [$p['name'], $f($p['value']), $p['share'].'%'], $parts),
                    [[(string) $totalLabel ?: 'Total', $f($total), '100%']],
                ),
            ])
        @endif

        @if ($interactive)
            @include('ds::partials.chart-readout')
        @endif
    @endif
</div>
