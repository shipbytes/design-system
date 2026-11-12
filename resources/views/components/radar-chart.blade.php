@props([
    /** The axes, 3 to 8 names, clockwise from twelve o'clock: ['Speed', 'Price', 'Support']. */
    'axes' => [],
    /**
     * The items compared, at most 4: rows with `label`, `values` (one per
     * axis, in axis order or keyed by axis name) and an optional `tone` or
     * `series`; or ['Acme' => [8, 6, 9], …]. Every value must be a number of
     * zero or more.
     */
    'data' => [],
    /** The scale's top: one number for every axis, one per axis (a list, or keyed by axis name), or null for a round number above the data. */
    'max' => null,
    /** How many grid levels between the centre and the edge (2 to 10). */
    'rings' => 4,
    /** polygon | circle — the grid's shape. */
    'shape' => 'polygon',
    /** Fill each item's area, faintly. */
    'fill' => true,
    /** beside | below | none — the legend, from two items up. */
    'legend' => 'beside',
    /** sm | md | lg — 160, 224 or 288px across the grid, plus room for the axis names. */
    'size' => 'md',
    /** What the chart compares, "Vendor scores": the start of the generated summary and the table's caption. */
    'label' => null,
    /** How numbers read: see Support\Chart::format(). */
    'format' => [],
    /** The text alternative, in place of the generated one. */
    'summary' => null,
    /** The values as a visually hidden table, axes by items. On by default: a radar is hard to read exactly, and this is how to. */
    'table' => true,
    /** With the package's script: a readout on hover and focus, every point reachable by keyboard, and legend toggles. */
    'interactive' => true,
    /** What the empty state says when there is nothing to draw. */
    'empty' => 'No data to show',
])

@use('Shipbytes\BladeUi\Support\Chart')
@use('Shipbytes\BladeUi\Support\ChartShapes')

@php
    $format = is_array($format) ? $format : [];
    $f = fn (float $v) => Chart::format($v, $format);

    /*
     * Fewer than three axes is a line, not an area, and past eight the
     * spokes are too close to label; past four items the shapes overprint
     * into one shape, and there are four markers. All three throw, saying
     * what to draw instead.
     */
    $axes = array_values(array_map(fn ($a) => trim((string) $a), is_iterable($axes) ? (is_array($axes) ? $axes : iterator_to_array($axes)) : []));
    $k = count($axes);
    if ($k < 3 || $k > 8) {
        throw new \InvalidArgumentException(
            'ds::radar-chart draws 3 to 8 axes, and was given '.$k.'. With fewer, compare with ds::bar-chart; with more, split them across two charts or use a grouped ds::bar-chart.'
        );
    }

    $items = [];
    foreach (is_iterable($data) ? $data : [] as $key => $row) {
        $row = $row instanceof \Traversable ? iterator_to_array($row) : $row;
        $given = is_array($row) && array_key_exists('values', $row) ? $row : ['values' => $row];
        $name = isset($given['label']) && trim((string) $given['label']) !== '' ? (string) $given['label'] : (is_string($key) ? $key : 'Series '.(count($items) + 1));
        $raw = $given['values'] instanceof \Traversable ? iterator_to_array($given['values']) : $given['values'];
        if (! is_array($raw)) {
            throw new \InvalidArgumentException('ds::radar-chart needs a value per axis for every item; ['.$name.'] has none.');
        }
        $values = [];
        foreach ($axes as $a => $axis) {
            $value = array_key_exists($axis, $raw) ? $raw[$axis] : (array_is_list($raw) ? ($raw[$a] ?? null) : null);
            if (! is_numeric($value) || (float) $value < 0) {
                throw new \InvalidArgumentException(
                    'ds::radar-chart draws every item on every axis from a centre of zero, so each value must be a number of zero or more; ['
                    .$name.'] has '.var_export($value, true).' for ['.$axis.'].'
                );
            }
            $values[] = (float) $value;
        }
        $items[] = ['label' => $name, 'values' => $values, 'tone' => isset($given['tone']) ? (string) $given['tone'] : null, 'series' => $given['series'] ?? null];
    }
    if (count($items) > 4) {
        throw new \InvalidArgumentException(
            'ds::radar-chart draws at most 4 items, and was given '.count($items).'. Past four the shapes cover each other; '
            .'compare them in a grouped ds::bar-chart, or draw several radars side by side.'
        );
    }
    $nothing = $items === [];
    $multi = count($items) > 1;

    /*
     * The scale: one top for every axis, round and from the data unless
     * given, so the rings are values; or a top per axis, when the axes are
     * in different units, and then the rings are shares of each axis and
     * are not numbered. A value past its top is drawn at the edge, and said
     * as it is.
     */
    $rings = max(2, min(10, (int) $rings));
    $perAxis = is_iterable($max) && ! is_string($max);
    if ($perAxis) {
        $maxes = is_array($max) ? $max : iterator_to_array($max);
        $tops = [];
        foreach ($axes as $a => $axis) {
            $top = array_key_exists($axis, $maxes) ? $maxes[$axis] : (array_is_list($maxes) ? ($maxes[$a] ?? null) : null);
            if (! is_numeric($top) || (float) $top <= 0) {
                throw new \InvalidArgumentException('ds::radar-chart needs a max above zero for every axis; ['.$axis.'] has '.var_export($top, true).'.');
            }
            $tops[] = (float) $top;
        }
    } else {
        $shared = is_numeric($max) && (float) $max > 0
            ? (float) $max
            : ChartShapes::radarMax($nothing ? 0.0 : max(array_merge(...array_column($items, 'values'))), $rings);
        $tops = array_fill(0, $k, $shared);
    }

    $sizeKey = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';
    $r = ['sm' => 80, 'md' => 112, 'lg' => 144][$sizeKey];
    // Room beside the grid for the axis names, and above and below it.
    $side = 88;
    $end = 24;
    $cx = $side + $r;
    $cy = $end + $r;
    $width = 2 * $cx;
    $height = 2 * $cy;
    $angle = fn (int $a) => $a * 360 / $k;
    $recipe = Chart::recipe();

    foreach ($items as $s => &$item) {
        $item['slot'] = Chart::series($item['series']) ?? ($item['tone'] === null ? $s + 1 : null);
        $item['colour'] = Chart::colour($item['tone'], $item['slot']);
        $item['marker'] = ChartShapes::MARKERS[$s];
        $item['dash'] = ChartShapes::DASHES[$s];
        $item['radii'] = array_map(fn ($v, $a) => min(1.0, $v / $tops[$a]) * $r, $item['values'], array_keys($item['values']));
        $item['points'] = array_map(fn ($radius, $a) => Chart::polar($cx, $cy, $radius, $angle($a)), $item['radii'], array_keys($item['radii']));
        $item['texts'] = array_map(fn ($v, $a) => $f($v).($perAxis ? ' of '.$f($tops[$a]) : ''), $item['values'], array_keys($item['values']));
    }
    unset($item);

    /*
     * Each item's highest and lowest axis. On a shared scale that is the
     * raw value; with a top per axis the axes are in different units, so
     * they are ranked by their share of the top and said as "4.5 of 5".
     */
    $rank = function (array $item, ?string $lead) use ($axes, $format, $perAxis, $tops, $f) {
        if (! $perAxis) {
            return Chart::ranked($item['values'], $axes, $format, $lead);
        }
        $shares = array_map(fn ($v, $a) => $v / $tops[$a], $item['values'], array_keys($item['values']));
        $hi = array_search(max($shares), $shares, true);
        $lo = array_search(min($shares), $shares, true);
        $text = $shares[$hi] === $shares[$lo]
            ? 'every axis at '.ChartShapes::percent($shares[$hi]).' of its top'
            : 'highest '.$axes[$hi].' at '.$item['texts'][$hi].', lowest '.$axes[$lo].' at '.$item['texts'][$lo];

        return $lead !== null && trim($lead) !== '' ? trim($lead).': '.$text.'.' : ucfirst($text).'.';
    };
    $text = $summary !== null && trim((string) $summary) !== ''
        ? trim((string) $summary)
        : ($multi
            ? trim(($label !== null && trim((string) $label) !== '' ? trim((string) $label).'. ' : '')
                .collect($items)->map(fn ($it) => $rank($it, $it['label']))->implode(' '))
            : ($nothing ? Chart::ranked([], $axes, $format, $label) : $rank($items[0], $label ?? $items[0]['label'])));

    // Axis names outside the grid, growing away from it, cut to the room
    // there is on that side: the full name stays in a title.
    $labels = [];
    foreach ($axes as $a => $axis) {
        $at = ChartShapes::anchor($angle($a));
        [$x, $y] = Chart::polar($cx, $cy, $r + 10, $angle($a));
        $labels[] = $at + ['x' => $x, 'y' => $y, 'full' => $axis, 'text' => Chart::truncate($axis, $at['anchor'] === 'middle' ? 2 * $side : $side - 12)];
    }

    $grid = [];
    foreach (range(1, $rings) as $j) {
        $grid[] = ['r' => $r * $j / $rings, 'value' => $perAxis ? null : $f($tops[0] * $j / $rings)];
    }

    $legend = in_array($legend, ['beside', 'below', 'none'], true) ? $legend : 'beside';
    $legendItems = $multi && $legend !== 'none'
        ? collect($items)->map(fn ($it, $s) => ['name' => $it['label'], 'colour' => $it['colour'], 'series' => $s, 'marker' => ['shape' => $it['marker'], 'dash' => $it['dash']]])->all()
        : [];

    $interactive = (bool) $interactive && ! $nothing;
    $root = $attributes->class([
        'relative flex gap-x-6 gap-y-3 [direction:ltr]',
        'flex-wrap items-center' => $legend === 'beside',
        'flex-col items-start' => $legend !== 'beside',
    ]);
    if ($interactive) {
        $root = $root->merge(array_filter([
            'x-data' => '',
            'x-ds-chart' => '',
            'data-ds-chart-axis' => 'horizontal',
            'data-ds-chart-next' => $multi ? 'ArrowDown' : null,
            'data-ds-chart-toggles' => $multi ? '' : null,
        ], fn ($v) => $v !== null));
    }
@endphp

<div {{ $root }}>
    @if ($nothing)
        <x-ds::empty-state :title="(string) $empty" icon="chart-pie" :bare="true" class="w-full" />
    @else
        {{-- A fixed drawing, scaled down only in a box narrower than it. --}}
        <svg data-ds-chart-plot role="img" aria-label="{{ $text }}" width="{{ $width }}" height="{{ $height }}" viewBox="0 0 {{ $width }} {{ $height }}"
            class="block h-auto max-w-full shrink-0 overflow-visible">
            {{-- The grid: drawn for the eye, read through the summary. --}}
            <g aria-hidden="true">
                @foreach ($grid as $ring)
                    @if ($shape === 'circle')
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ Chart::n($ring['r']) }}" fill="none" stroke="currentColor" class="{{ $loop->last ? $recipe['zero'] : $recipe['grid'] }}" />
                    @else
                        <polygon points="{{ ChartShapes::polygon(array_fill(0, $k, $ring['r']), $cx, $cy) }}" fill="none" stroke="currentColor" stroke-linejoin="round" class="{{ $loop->last ? $recipe['zero'] : $recipe['grid'] }}" />
                    @endif
                @endforeach
                @foreach ($axes as $a => $axis)
                    @php [$x, $y] = Chart::polar($cx, $cy, $r, $angle($a)); @endphp
                    <line x1="{{ $cx }}" y1="{{ $cy }}" x2="{{ Chart::n($x) }}" y2="{{ Chart::n($y) }}" stroke="currentColor" class="{{ $recipe['grid'] }}" />
                @endforeach
                {{-- Numbered up the first axis only: one scale, read once.
                     Under the data, which matters more, and haloed in the
                     surface so the grid never strikes through a number. --}}
                @foreach ($grid as $ring)
                    @if ($ring['value'] !== null)
                        <text x="{{ $cx + 4 }}" y="{{ Chart::n($cy - $ring['r']) }}" dominant-baseline="central" stroke-width="3" stroke-linejoin="round" class="{{ $recipe['tick'] }} stroke-surface [paint-order:stroke]">{{ $ring['value'] }}</text>
                    @endif
                @endforeach
                @foreach ($labels as $at)
                    <text x="{{ Chart::n($at['x']) }}" y="{{ Chart::n($at['y']) }}" text-anchor="{{ $at['anchor'] }}" dominant-baseline="{{ $at['baseline'] }}" class="fill-current text-meta text-fg">@if ($at['text'] !== $at['full'])<title>{{ $at['full'] }}</title>@endif{{ $at['text'] }}</text>
                @endforeach
            </g>

            @foreach ($items as $s => $item)
                <g data-ds-chart-series="{{ $s }}" class="{{ $item['colour'] }} data-ds-off:hidden">
                    <polygon points="{{ ChartShapes::polygon($item['radii'], $cx, $cy) }}" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"
                        @if ($item['dash'] !== '') stroke-dasharray="{{ $item['dash'] }}" @endif
                        class="{{ $fill ? 'fill-current [fill-opacity:0.12]' : 'fill-none' }} pointer-events-none" aria-hidden="true" />
                    @foreach ($item['points'] as $a => $point)
                        <path d="{{ ChartShapes::marker($item['marker'], $point[0], $point[1], 7) }}" class="pointer-events-none fill-current stroke-surface" stroke-width="1.5" aria-hidden="true" />
                        @if ($interactive)
                            {{-- What the pointer and the keyboard reach: wider
                                 than the marker, and drawn only while read. --}}
                            <circle cx="{{ Chart::n($point[0]) }}" cy="{{ Chart::n($point[1]) }}" r="9"
                                class="fill-current [fill-opacity:0] data-ds-active:[fill-opacity:0.2] focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-focus-ring"
                                data-ds-chart-mark data-ds-at="{{ $a }}" data-ds-in="{{ $s }}"
                                data-ds-label="{{ $axes[$a] }}" data-ds-value="{{ $item['texts'][$a] }}"
                                data-ds-series="{{ $multi ? $item['label'] : '' }}" data-ds-slot="{{ $item['slot'] ?? '' }}"
                                aria-label="{{ $axes[$a] }}, {{ $item['label'] }}: {{ $item['texts'][$a] }}" />
                        @endif
                    @endforeach
                </g>
            @endforeach
        </svg>

        @if ($legendItems !== [])
            <div @class(['min-w-40 flex-1' => $legend === 'beside', 'w-full' => $legend === 'below'])>
                @include('ds::partials.chart-legend', ['items' => $legendItems, 'toggles' => $interactive, 'column' => $legend === 'beside'])
            </div>
        @endif

        @if ($table)
            @include('ds::partials.chart-table', [
                'caption' => $label !== null && trim((string) $label) !== '' ? $label : $text,
                'headers' => array_merge(['Axis'], array_column($items, 'label'), $perAxis ? ['Max'] : []),
                'rows' => array_map(fn ($axis, $a) => array_merge([$axis], array_map(fn ($it) => $f($it['values'][$a]), $items), $perAxis ? [$f($tops[$a])] : []), $axes, array_keys($axes)),
            ])
        @endif

        @if ($interactive)
            @include('ds::partials.chart-readout')
        @endif
    @endif
</div>
