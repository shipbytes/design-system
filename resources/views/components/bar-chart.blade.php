@props([
    /**
     * The values. One series: ['Jan' => 12, 'Feb' => 18]. Several: a row
     * per category, ['Jan' => ['Revenue' => 12, 'Costs' => 8], …], or a list
     * per category with the names in `series`. Null, or anything not
     * numeric, is a missing value.
     */
    'data' => [],
    /** The series' names, in order: they take series-1, series-2… A string names a single series. */
    'series' => null,
    /** What the chart shows, "Revenue by month": the start of the generated summary and the table's caption. */
    'label' => null,
    /** vertical | horizontal. Horizontal for many categories or long names. */
    'orientation' => 'vertical',
    /** Stack the series in one bar per category, instead of side by side. */
    'stacked' => false,
    /** sm | md | lg — the plot's height (vertical), or each category's band (horizontal). */
    'height' => 'md',
    /** How numbers read on the axis, the bars, the readout and the summary: see Support\Chart::format(). */
    'format' => [],
    /** Roughly how many ticks on the value axis (default 5, on round numbers), or the tick values themselves. */
    'yTicks' => null,
    /** Print each bar's value at its end (a stack's total, when stacked). */
    'showValues' => false,
    /** neutral | accent | success | warning | danger — ONE series whose colour means something. Ignored with several. */
    'tone' => null,
    /** The text alternative, in place of the generated one. */
    'summary' => null,
    /** Also give the values to assistive technology as a visually hidden table. */
    'table' => false,
    /** With the package's script: a readout on hover and focus, the bars reachable by keyboard, and legend toggles. */
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
    $horizontal = $orientation === 'horizontal';
    $stacked = (bool) $stacked && $multi;
    $size = in_array($height, ['sm', 'md', 'lg'], true) ? $height : 'md';

    $present = collect($rows)->flatten()->filter(fn ($v) => $v !== null);
    $nothing = $present->isEmpty();

    $recipe = Chart::recipe();
    $name = fn (int $s) => $names[$s] !== '' ? $names[$s] : null;
    // Colour by position, never by meaning: a series keeps its slot whatever
    // its neighbours do. One series is series-1 unless a tone says something.
    $colours = $multi || $tone === null
        ? array_map(fn ($s) => Chart::colour(null, $s + 1), range(0, $k - 1))
        : [Chart::colour($tone)];
    $f = fn (?float $v) => Chart::format($v, $format);

    $text = $summary !== null && trim((string) $summary) !== ''
        ? trim((string) $summary)
        : ($multi
            ? trim(($label !== null && trim((string) $label) !== '' ? trim((string) $label).'. ' : '')
                .collect($names)->map(fn ($nm, $s) => Chart::ranked($rows[$s], $labels, $format, $nm))->implode(' '))
            : Chart::ranked($rows[0], $labels, $format, $label ?? $name(0)));

    if (! $nothing) {
        $scale = Chart::scale(
            $stacked ? Chart::stack($rows)['totals'] : $present->all(),
            zero: true,
            ticks: is_array($yTicks) ? $yTicks : (is_numeric($yTicks) ? (int) $yTicks : null),
        );
        [$lo, $hi] = [$scale['min'], $scale['max']];
        $segments = $stacked
            ? Chart::stack($rows)['segments']
            : array_map(fn ($row) => array_map(fn ($v) => $v === null ? null : [0.0, $v], $row), $rows);

        /*
         * Measure-free and scaling with its box, text included at its own
         * size: the SVG has no viewBox, so nothing in it is scaled. Along
         * the categories everything is a percentage of the plot's width (or
         * height, horizontally, where each category is a band of fixed px);
         * across them, px. Labels beside the plot sit in the padding of the
         * box around the SVG, at negative x, so the plot itself is the whole
         * 0..100%. The gutter is sized from the labels' estimated widths.
         */
        $tickText = array_map(fn ($t) => $f($t), $scale['ticks']);
        $valueText = [];
        $top = $showValues && ! $horizontal ? 20 : 8;

        if ($horizontal) {
            $band = ['sm' => 24, 'md' => 32, 'lg' => 40][$size];
            $plot = $n * $band;
            $categoryRoom = min(120.0, max(array_map(fn ($l) => Chart::textWidth($l), $labels)));
            $room = $categoryRoom + 12;
            $at = fn (float $v) => Chart::fraction($v, $lo, $hi) * 100;
        } else {
            $plot = ['sm' => 128, 'md' => 192, 'lg' => 256][$size];
            $band = null;
            $room = max(array_map(fn ($t) => Chart::textWidth($t), $tickText)) + 12;
            $at = fn (float $v) => $top + (1 - Chart::fraction($v, $lo, $hi)) * $plot;
        }

        $gutter = Chart::gutter($room);
        $zero = $at(max($lo, min($hi, 0.0)));
        $svgHeight = $top + $plot + 24;

        /*
         * Each category a slot; inside it, one bar (single or stacked) or a
         * group of bars side by side, with air between groups and a hair
         * between neighbours.
         */
        $slot = $horizontal ? $band : 100 / max(1, $n);
        $grouped = $multi && ! $stacked;
        $groupShare = $grouped ? 0.76 : 0.62;
        $width = $slot * $groupShare / ($grouped ? $k : 1);
        $shrink = $grouped ? 0.88 : 1.0;

        $bars = [];
        foreach ($segments as $s => $row) {
            foreach ($row as $i => $segment) {
                if ($segment === null) {
                    continue;
                }
                $value = $rows[$s][$i];
                $offset = $i * $slot + $slot * (1 - $groupShare) / 2 + ($grouped ? $s * $width : 0) + $width * (1 - $shrink) / 2;
                [$a, $b] = [$at($segment[0]), $at($segment[1])];
                $bars[$s][] = [
                    'at' => $i,
                    'value' => $value,
                    'cross' => $offset,
                    'thick' => $width * $shrink,
                    'from' => min($a, $b),
                    'length' => abs($b - $a),
                    'end' => $b,
                    'says' => trim($labels[$i].($name($s) !== null && $multi ? ', '.$name($s) : '')).': '.$f($value),
                ];
            }
        }

        // The values printed at the bars' ends: each bar's, or each stack's
        // total once, at the end of its outermost segment.
        $printed = [];
        if ($showValues) {
            foreach (range(0, $n - 1) as $i) {
                $column = array_map(fn ($row) => $row[$i], $rows);
                if (collect($column)->filter(fn ($v) => $v !== null)->isEmpty()) {
                    continue;
                }
                if ($stacked) {
                    $total = array_sum(array_filter($column, fn ($v) => $v !== null));
                    $printed[] = ['cross' => $i * $slot + $slot / 2, 'end' => $at($total), 'negative' => $total < 0, 'text' => $f($total)];
                } else {
                    foreach ($bars as $s => $list) {
                        foreach ($list as $bar) {
                            if ($bar['at'] === $i) {
                                $printed[] = ['cross' => $bar['cross'] + $bar['thick'] / 2, 'end' => $bar['end'], 'negative' => $bar['value'] < 0, 'text' => $f($bar['value'])];
                            }
                        }
                    }
                }
            }
            $valueText = array_column($printed, 'text');
        }

        // Room on the right: for the values printed past the longest bar,
        // and for half the last tick label, which is centred on the edge.
        $rightPx = $horizontal
            ? max($showValues ? max(array_map(fn ($t) => Chart::textWidth($t), $valueText ?: ['0'])) + 8 : 0, Chart::textWidth(end($tickText)) / 2 + 2)
            : 8;
        $right = Chart::gutter($rightPx, 'right');

        // Printed values all go together once the bars are narrower than
        // the widest of them: a value that might be any bar's is worse than
        // none, and the readout and the table still have every one.
        $valuesHide = '';
        if ($printed !== [] && ! $horizontal) {
            $across = $n * ($grouped ? $k : 1) / ($groupShare * $shrink);
            $valuesHide = Chart::below(max(array_map(fn ($t) => Chart::textWidth($t), $valueText)) * $across + $room + $rightPx + 16);
        }

        // Horizontally the value axis runs along the bottom, where the
        // tick labels can crowd a narrow box as category labels do.
        $tickHide = $horizontal
            ? Chart::thinning(count($tickText), max(array_map(fn ($t) => Chart::textWidth($t), $tickText)) + 12, $room + $rightPx, max(1, count($tickText) - 1))
            : array_fill(0, count($tickText), null);

        /*
         * Category labels. Horizontally each has its own band and is cut to
         * the gutter. Vertically, a label is cut at 96px and the crowded
         * ones are dropped in halves by container query as the box
         * narrows, never by measuring: horizontal is the answer for many or
         * long names, and the docs say so.
         */
        $categories = [];
        if ($horizontal) {
            foreach ($labels as $i => $l) {
                $categories[] = ['text' => Chart::truncate($l, $categoryRoom), 'full' => $l, 'hide' => ''];
            }
        } else {
            $cut = array_map(fn ($l) => Chart::truncate($l, 96), $labels);
            $need = max(array_map(fn ($l) => Chart::textWidth($l), $cut)) + 8;
            foreach (Chart::thinning($n, $need, $room + 8, $n) as $i => $width) {
                $categories[] = ['text' => $cut[$i], 'full' => $labels[$i], 'hide' => Chart::below($width)];
            }
        }
    }

    $tableRows = $table
        ? collect($labels)->map(fn ($l, $i) => array_merge([$l], array_map(fn ($row) => $f($row[$i]), $rows)))->all()
        : [];

    $legend = $multi
        ? collect($names)->map(fn ($nm, $s) => ['name' => $nm, 'colour' => $colours[$s], 'series' => $s])->all()
        : [];

    $interactive = (bool) $interactive && ! $nothing;
    $root = $attributes->class(['relative flex flex-col gap-3 [direction:ltr]']);
    if ($interactive) {
        $root = $root->merge(array_filter([
            'x-data' => '',
            'x-ds-chart' => '',
            'data-ds-chart-axis' => $horizontal ? 'vertical' : 'horizontal',
            'data-ds-chart-next' => $multi ? ($horizontal ? 'ArrowRight' : ($stacked ? 'ArrowUp' : 'ArrowDown')) : null,
            'data-ds-chart-toggles' => $multi ? '' : null,
        ], fn ($v) => $v !== null));
    }
    $mark = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring data-ds-active:opacity-85';
@endphp

<div {{ $root }}>
    @if ($nothing)
        <x-ds::empty-state :title="(string) $empty" icon="chart-bar" :bare="true" />
    @else
        <div class="@container">
            <div class="{{ $gutter }} {{ $right }}">
                <svg data-ds-chart-plot role="img" aria-label="{{ $text }}" width="100%" height="{{ $svgHeight }}"
                    class="block overflow-visible">
                    {{-- The axes: drawn for the eye, read through the summary. --}}
                    <g aria-hidden="true">
                        @foreach ($scale['ticks'] as $t => $tick)
                            @php $p = Chart::n($at($tick)); @endphp
                            @if ($horizontal)
                                <line x1="{{ $p }}%" x2="{{ $p }}%" y1="{{ $top }}" y2="{{ $top + $plot }}" stroke="currentColor" shape-rendering="crispEdges" class="{{ $tick == 0 ? $recipe['zero'] : $recipe['grid'] }}" />
                                <text x="{{ $p }}%" y="{{ $top + $plot + 8 }}" text-anchor="middle" dominant-baseline="hanging" class="{{ trim($recipe['tick'].' '.Chart::below($tickHide[$t])) }}">{{ $tickText[$t] }}</text>
                            @else
                                <line x1="0" x2="100%" y1="{{ $p }}" y2="{{ $p }}" stroke="currentColor" shape-rendering="crispEdges" class="{{ $tick == 0 ? $recipe['zero'] : $recipe['grid'] }}" />
                                <text x="-8" y="{{ $p }}" text-anchor="end" dominant-baseline="central" class="{{ $recipe['tick'] }}">{{ $tickText[$t] }}</text>
                            @endif
                        @endforeach
                        @if (! in_array(0.0, $scale['ticks'], true))
                            @if ($horizontal)
                                <line x1="{{ Chart::n($zero) }}%" x2="{{ Chart::n($zero) }}%" y1="{{ $top }}" y2="{{ $top + $plot }}" stroke="currentColor" shape-rendering="crispEdges" class="{{ $recipe['zero'] }}" />
                            @else
                                <line x1="0" x2="100%" y1="{{ Chart::n($zero) }}" y2="{{ Chart::n($zero) }}" stroke="currentColor" shape-rendering="crispEdges" class="{{ $recipe['zero'] }}" />
                            @endif
                        @endif
                        @foreach ($categories as $i => $category)
                            @if ($horizontal)
                                <text x="-8" y="{{ Chart::n($top + ($i + 0.5) * $band) }}" text-anchor="end" dominant-baseline="central" class="{{ $recipe['tick'] }}">@if ($category['text'] !== $category['full'])<title>{{ $category['full'] }}</title>@endif{{ $category['text'] }}</text>
                            @else
                                <text x="{{ Chart::n(($i + 0.5) * $slot) }}%" y="{{ $top + $plot + 8 }}" text-anchor="middle" dominant-baseline="hanging" class="{{ trim($recipe['tick'].' '.$category['hide']) }}">@if ($category['text'] !== $category['full'])<title>{{ $category['full'] }}</title>@endif{{ $category['text'] }}</text>
                            @endif
                        @endforeach
                    </g>

                    @foreach ($bars as $s => $list)
                        <g fill="currentColor" @if ($stacked) stroke-width="1" @endif data-ds-chart-series="{{ $s }}"
                            class="{{ $colours[$s] ?? $colours[0] }} data-ds-off:hidden">
                            @foreach ($list as $bar)
                                @php
                                    $geometry = $horizontal
                                        ? ['x' => Chart::n($bar['from']).'%', 'y' => Chart::n($top + $bar['cross']), 'width' => Chart::n($bar['length']).'%', 'height' => Chart::n($bar['thick'])]
                                        : ['x' => Chart::n($bar['cross']).'%', 'y' => Chart::n($bar['from']), 'width' => Chart::n($bar['thick']).'%', 'height' => Chart::n($bar['length'])];
                                @endphp
                                <rect x="{{ $geometry['x'] }}" y="{{ $geometry['y'] }}" width="{{ $geometry['width'] }}" height="{{ $geometry['height'] }}"
                                    @if ($stacked || $interactive) class="{{ trim(($stacked ? 'stroke-surface ' : '').($interactive ? $mark : '')) }}" @endif
                                    @if ($interactive)
                                        data-ds-chart-mark data-ds-at="{{ $bar['at'] }}" data-ds-in="{{ $s }}"
                                        data-ds-label="{{ $labels[$bar['at']] }}" data-ds-value="{{ $f($bar['value']) }}"
                                        data-ds-series="{{ $multi ? $names[$s] : '' }}" data-ds-slot="{{ $multi || $tone === null ? $s + 1 : '' }}"
                                        aria-label="{{ $bar['says'] }}"
                                    @endif
                                />
                            @endforeach
                        </g>
                    @endforeach

                    @if ($printed !== [])
                        <g aria-hidden="true" @if ($valuesHide !== '') class="{{ $valuesHide }}" @endif>
                            @foreach ($printed as $value)
                                @if ($horizontal)
                                    <text x="{{ Chart::n($value['end']) }}%" dx="{{ $value['negative'] ? -4 : 4 }}" y="{{ Chart::n($top + $value['cross']) }}" text-anchor="{{ $value['negative'] ? 'end' : 'start' }}" dominant-baseline="central" class="{{ $recipe['value'] }}">{{ $value['text'] }}</text>
                                @else
                                    <text x="{{ Chart::n($value['cross']) }}%" y="{{ Chart::n($value['end'] + ($value['negative'] ? 4 : -4)) }}" text-anchor="middle" dominant-baseline="{{ $value['negative'] ? 'hanging' : 'auto' }}" class="{{ $recipe['value'] }}">{{ $value['text'] }}</text>
                                @endif
                            @endforeach
                        </g>
                    @endif
                </svg>
            </div>
        </div>

        @if ($legend !== [])
            @include('ds::partials.chart-legend', ['items' => $legend, 'toggles' => $interactive, 'column' => false])
        @endif

        @if ($table)
            @include('ds::partials.chart-table', [
                'caption' => $label !== null && trim((string) $label) !== '' ? $label : $text,
                'headers' => array_merge(['Category'], $multi ? $names : [$name(0) ?? 'Value']),
                'rows' => $tableRows,
            ])
        @endif

        @if ($interactive)
            @include('ds::partials.chart-readout')
        @endif
    @endif
</div>
