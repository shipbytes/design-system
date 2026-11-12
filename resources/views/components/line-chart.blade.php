@props([
    /**
     * The values, in order along the x axis. One series: ['Mon' => 12, …].
     * Several: a row per point, ['Mon' => ['Visits' => 12, 'Signups' => 3], …],
     * or a list per point with the names in `series`. Null is a gap: the
     * line breaks there rather than bridging it.
     */
    'data' => [],
    /** The series' names, in order: they take series-1, series-2… A string names a single series. */
    'series' => null,
    /** What the chart shows, "Visits this week": the start of the generated summary and the table's caption. */
    'label' => null,
    /** sm | md | lg — the plot's height. */
    'height' => 'md',
    /** How numbers read on the axis, the readout and the summary: see Support\Chart::format(). */
    'format' => [],
    /** Roughly how many ticks on the value axis (default 5, on round numbers), or the tick values themselves. */
    'yTicks' => null,
    /** Fill under each line, down to zero (the value axis then always includes zero). */
    'area' => false,
    /** linear | smooth — smooth is a monotone curve, which never invents a peak or a dip. */
    'curve' => 'linear',
    /** A dot on every value. */
    'points' => false,
    /** auto (drop labels in halves when crowded) | none | a number n (every nth label). */
    'xLabels' => 'auto',
    /**
     * auto | end | bottom. auto: a legend under the chart from two series
     * up, and each line named at its end as well when there are four or
     * fewer. end: the names at the ends INSTEAD of the legend (four or
     * fewer; more fall back to auto). bottom: the legend only.
     */
    'legend' => 'auto',
    /** neutral | accent | success | warning | danger — ONE series whose colour means something. Ignored with several. */
    'tone' => null,
    /** The text alternative, in place of the generated one. */
    'summary' => null,
    /** Also give the values to assistive technology as a visually hidden table. */
    'table' => false,
    /** With the package's script: a readout on hover and focus, the points reachable by keyboard, and legend toggles. */
    'interactive' => true,
    /** What the empty state says when there is nothing to draw. */
    'empty' => 'No data to show',
])

@use('Shipbytes\BladeUi\Support\Chart')

@php
    $format = is_array($format) ? $format : [];
    $set = Chart::dataset(is_iterable($data) ? $data : [], $series);
    [$labels, $names, $rows] = [$set['labels'], $set['series'], $set['rows']];
    $n = count($labels);
    $k = count($names);
    $multi = $k > 1;
    $size = in_array($height, ['sm', 'md', 'lg'], true) ? $height : 'md';
    $smooth = $curve === 'smooth';

    $present = collect($rows)->flatten()->filter(fn ($v) => $v !== null);
    $nothing = $present->isEmpty();

    $recipe = Chart::recipe();
    $name = fn (int $s) => $names[$s] !== '' ? $names[$s] : null;
    $colours = $multi || $tone === null
        ? array_map(fn ($s) => Chart::colour(null, $s + 1), range(0, $k - 1))
        : [Chart::colour($tone)];
    $f = fn (?float $v) => Chart::format($v, $format);

    $text = $summary !== null && trim((string) $summary) !== ''
        ? trim((string) $summary)
        : ($multi
            ? trim(($label !== null && trim((string) $label) !== '' ? trim((string) $label).'. ' : '')
                .collect($names)->map(fn ($nm, $s) => Chart::summary($rows[$s], $format, $nm))->implode(' '))
            : Chart::summary($rows[0], $format, $label ?? $name(0)));

    // Identity in words, always, from two series up: a legend, the names at
    // the lines' ends, or both (specs/chart-series.md). Never neither.
    $legend = in_array($legend, ['auto', 'end', 'bottom'], true) ? $legend : 'auto';
    if ($legend === 'end' && $k > 4) {
        $legend = 'auto';
    }
    $showLegend = $multi && $legend !== 'end';
    $showEnds = $multi && $k <= 4 && $legend !== 'bottom';

    if (! $nothing) {
        $scale = Chart::scale(
            $present->all(),
            zero: (bool) $area,
            ticks: is_array($yTicks) ? $yTicks : (is_numeric($yTicks) ? (int) $yTicks : null),
        );
        [$lo, $hi] = [$scale['min'], $scale['max']];
        $tickText = array_map(fn ($t) => $f($t), $scale['ticks']);

        /*
         * The same frame as ds::bar-chart: no viewBox on the outer SVG, so
         * text and dots keep their size; x as a percentage of the plot,
         * y in px. A line cannot be drawn in percentages, so the paths go in
         * a nested SVG whose viewBox is 100 wide and stretched to the plot
         * (preserveAspectRatio none), with strokes that do not scale.
         */
        $top = 8;
        $plot = ['sm' => 128, 'md' => 192, 'lg' => 256][$size];
        $x = fn (int $i) => $n === 1 ? 50.0 : $i / ($n - 1) * 100;
        $y = fn (float $v) => (1 - Chart::fraction($v, $lo, $hi)) * $plot;
        $baseline = $y(max($lo, min($hi, 0.0)));

        $lines = [];
        foreach ($rows as $s => $row) {
            $coords = array_map(fn ($v, $i) => $v === null ? null : [$x($i), $y($v)], $row, array_keys($row));
            $lines[$s] = [
                'line' => Chart::line($coords, $smooth),
                'area' => $area ? Chart::area($coords, $baseline, $smooth) : '',
                'points' => $coords,
            ];
        }

        // The names at the ends of the lines: at each line's last value,
        // nudged apart where two end close together, kept inside the plot.
        $ends = [];
        if ($showEnds) {
            foreach ($lines as $s => $line) {
                $last = collect($line['points'])->filter()->keys()->last();
                if ($last !== null) {
                    $ends[] = ['series' => $s, 'x' => $line['points'][$last][0], 'y' => $line['points'][$last][1], 'text' => Chart::truncate($names[$s], 112), 'full' => $names[$s]];
                }
            }
            // Down the page first, each at least a line below the one above;
            // then back up from the floor, so none is pushed out of the plot.
            usort($ends, fn ($a, $b) => $a['y'] <=> $b['y']);
            for ($i = 1; $i < count($ends); $i++) {
                $ends[$i]['y'] = max($ends[$i]['y'], $ends[$i - 1]['y'] + 14);
            }
            $limit = (float) $plot;
            for ($i = count($ends) - 1; $i >= 0; $i--) {
                $ends[$i]['y'] = min($ends[$i]['y'], $limit);
                $limit = $ends[$i]['y'] - 14;
            }
        }

        $cut = array_map(fn ($l) => Chart::truncate($l, 96), $labels);
        $leftPx = max(max(array_map(fn ($t) => Chart::textWidth($t), $tickText)) + 12, Chart::textWidth($cut[0]) / 2 + 2);
        $rightPx = max(
            $ends === [] ? 0 : max(array_map(fn ($e) => Chart::textWidth($e['text']), $ends)) + 14,
            Chart::textWidth(end($cut)) / 2 + 2,
        );
        $gutter = Chart::gutter($leftPx);
        $right = Chart::gutter($rightPx, 'right');

        // Labels along the bottom: thinned in halves by container query as
        // the box narrows (auto), every nth (a number), or none.
        $every = is_numeric($xLabels) && (int) $xLabels > 0 ? (int) $xLabels : null;
        $hides = $xLabels === 'none'
            ? array_fill(0, $n, 'hidden')
            : ($every !== null
                ? array_map(fn ($i) => $i % $every === 0 ? '' : 'hidden', range(0, $n - 1))
                : array_map(fn ($w) => Chart::below($w), Chart::thinning($n, max(array_map(fn ($l) => Chart::textWidth($l), $cut)) + 8, $leftPx + $rightPx, max(1, $n - 1))));
        $categories = array_map(fn ($i) => ['text' => $cut[$i], 'full' => $labels[$i], 'hide' => $hides[$i]], range(0, $n - 1));

        $svgHeight = $top + $plot + 26;
    }

    $tableRows = $table
        ? collect($labels)->map(fn ($l, $i) => array_merge([$l], array_map(fn ($row) => $f($row[$i]), $rows)))->all()
        : [];

    $legendItems = $showLegend
        ? collect($names)->map(fn ($nm, $s) => ['name' => $nm, 'colour' => $colours[$s], 'series' => $s])->all()
        : [];

    $interactive = (bool) $interactive && ! $nothing;
    $root = $attributes->class(['relative flex flex-col gap-3 [direction:ltr]']);
    if ($interactive) {
        $root = $root->merge(array_filter([
            'x-data' => '',
            'x-ds-chart' => '',
            'data-ds-chart-axis' => 'horizontal',
            'data-ds-chart-next' => $multi ? 'ArrowDown' : null,
            'data-ds-chart-nearest' => '',
            'data-ds-chart-toggles' => $showLegend ? '' : null,
        ], fn ($v) => $v !== null));
    }
    // A dot sits on its line with a ring of the surface round it, so it
    // reads as a point on the line and not a blot. Without `points` the dots
    // are there for the keyboard and the pointer, and show only while read.
    // A value with a gap on both sides has no line to be a point of, so its
    // dot is always drawn: without it the value would not be on the chart.
    $dot = fn (bool $lone) => implode(' ', array_filter([
        'fill-current stroke-surface',
        $points || $lone ? '' : 'opacity-0 data-ds-active:opacity-100',
        $interactive ? 'focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-focus-ring' : '',
    ]));
@endphp

<div {{ $root }}>
    @if ($nothing)
        <x-ds::empty-state :title="(string) $empty" icon="chart-bar" :bare="true" />
    @else
        <div class="@container">
            <div class="{{ $gutter }} {{ $right }}">
                <svg data-ds-chart-plot role="img" aria-label="{{ $text }}" width="100%" height="{{ $svgHeight }}"
                    class="block overflow-visible">
                    <g aria-hidden="true">
                        @foreach ($scale['ticks'] as $t => $tick)
                            @php $p = Chart::n($top + $y($tick)); @endphp
                            <line x1="0" x2="100%" y1="{{ $p }}" y2="{{ $p }}" stroke="currentColor" shape-rendering="crispEdges" class="{{ $tick == 0 && $lo < 0 ? $recipe['zero'] : $recipe['grid'] }}" />
                            <text x="-8" y="{{ $p }}" text-anchor="end" dominant-baseline="central" class="{{ $recipe['tick'] }}">{{ $tickText[$t] }}</text>
                        @endforeach
                        @foreach ($categories as $i => $category)
                            <text x="{{ Chart::n($x($i)) }}%" y="{{ $top + $plot + 10 }}" text-anchor="middle" dominant-baseline="hanging" class="{{ trim($recipe['tick'].' '.$category['hide']) }}">@if ($category['text'] !== $category['full'])<title>{{ $category['full'] }}</title>@endif{{ $category['text'] }}</text>
                        @endforeach
                    </g>

                    @foreach ($lines as $s => $line)
                        <g data-ds-chart-series="{{ $s }}" class="{{ $colours[$s] ?? $colours[0] }} data-ds-off:hidden">
                            <svg x="0" y="{{ $top }}" width="100%" height="{{ $plot }}" viewBox="0 0 100 {{ $plot }}" preserveAspectRatio="none" overflow="visible" aria-hidden="true">
                                @if ($line['area'] !== '')
                                    <path d="{{ $line['area'] }}" fill="currentColor" fill-opacity="0.12" stroke="none" />
                                @endif
                                <path d="{{ $line['line'] }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                            </svg>
                            @foreach ($line['points'] as $i => $point)
                                @php $lone = $point !== null && ($line['points'][$i - 1] ?? null) === null && ($line['points'][$i + 1] ?? null) === null; @endphp
                                @if ($point !== null && ($points || $interactive || $lone))
                                        <circle cx="{{ Chart::n($point[0]) }}%" cy="{{ Chart::n($top + $point[1]) }}" r="3.5" stroke-width="1.5" class="{{ $dot($lone) }}"
                                            @if ($interactive)
                                                data-ds-chart-mark data-ds-at="{{ $i }}" data-ds-in="{{ $s }}"
                                                data-ds-label="{{ $labels[$i] }}" data-ds-value="{{ $f($rows[$s][$i]) }}"
                                                data-ds-series="{{ $multi ? $names[$s] : '' }}" data-ds-slot="{{ $multi || $tone === null ? $s + 1 : '' }}"
                                                aria-label="{{ $labels[$i].($multi ? ', '.$names[$s] : '').': '.$f($rows[$s][$i]) }}"
                                            @else
                                                aria-hidden="true"
                                            @endif
                                        />
                                @endif
                            @endforeach
                        </g>
                    @endforeach

                    @if ($ends !== [])
                        <g aria-hidden="true">
                            @foreach ($ends as $end)
                                <text x="{{ Chart::n($end['x']) }}%" dx="8" y="{{ Chart::n($top + $end['y']) }}" dominant-baseline="central" data-ds-chart-series="{{ $end['series'] }}"
                                    class="{{ $recipe['value'] }} data-ds-off:hidden">@if ($end['text'] !== $end['full'])<title>{{ $end['full'] }}</title>@endif{{ $end['text'] }}</text>
                            @endforeach
                        </g>
                    @endif
                </svg>
            </div>
        </div>

        @if ($legendItems !== [])
            @include('ds::partials.chart-legend', ['items' => $legendItems, 'toggles' => $interactive, 'column' => false])
        @endif

        @if ($table)
            @include('ds::partials.chart-table', [
                'caption' => $label !== null && trim((string) $label) !== '' ? $label : $text,
                'headers' => array_merge(['Point'], $multi ? $names : [$name(0) ?? 'Value']),
                'rows' => $tableRows,
            ])
        @endif

        @if ($interactive)
            @include('ds::partials.chart-readout')
        @endif
    @endif
</div>
