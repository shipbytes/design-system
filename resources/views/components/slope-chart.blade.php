@props([
    /**
     * The items, each at two points in time: rows with `label`, `from`, `to`
     * and an optional `tone` or `series`; or ['Oslo' => [12, 18], …]. Both
     * values must be numbers. Suits up to about ten items.
     */
    'data' => [],
    /** The first point in time, "2024": the left column's heading. Required. */
    'fromLabel' => null,
    /** The second, "2025": the right column's heading. Required. */
    'toLabel' => null,
    /** What the chart compares, "Price per night": the start of the generated summary and the table's caption. */
    'label' => null,
    /** How numbers read, the change included: see Support\Chart::format(). */
    'format' => [],
    /** The labels of the items to emphasise; the rest are drawn muted. A string or a list. */
    'highlight' => [],
    /** series | direction — a series colour per item (eight at most), or the tones for up, down and flat. */
    'colourBy' => 'series',
    /** sm | md | lg — the plot's height. */
    'height' => 'md',
    /** The text alternative, in place of the generated one. */
    'summary' => null,
    /** Also give the values to assistive technology as a visually hidden table. */
    'table' => false,
    /** With the package's script: a readout on hover and focus, and every point reachable by keyboard. */
    'interactive' => true,
    /** What the empty state says when there is nothing to draw. */
    'empty' => 'No data to show',
])

@use('Shipbytes\BladeUi\Support\Chart')

@php
    $format = is_array($format) ? $format : [];
    $f = fn (float $v) => Chart::format($v, $format);

    // The two columns are the chart's axis: without their names it says
    // nothing about when, so a missing one is a mistake, not a default.
    if (trim((string) $fromLabel) === '' || trim((string) $toLabel) === '') {
        throw new \InvalidArgumentException(
            'ds::slope-chart needs fromLabel and toLabel, the two points in time its columns stand for ("2024", "2025").'
        );
    }
    $fromLabel = trim((string) $fromLabel);
    $toLabel = trim((string) $toLabel);

    $byDirection = $colourBy === 'direction';
    $wanted = collect(is_iterable($highlight) ? $highlight : (trim((string) $highlight) === '' ? [] : [$highlight]))->map(fn ($l) => (string) $l)->all();

    $items = [];
    foreach (Chart::rows(is_iterable($data) ? $data : [], ['from', 'to']) as $i => $row) {
        if (! is_numeric($row['from']) || ! is_numeric($row['to'])) {
            throw new \InvalidArgumentException(
                'ds::slope-chart draws every item at both points in time, so from and to must both be numbers; ['
                .$row['label'].'] has '.var_export($row['from'], true).' and '.var_export($row['to'], true).'. Leave out an item with a missing value.'
            );
        }
        $items[] = ['label' => $row['label'], 'from' => (float) $row['from'], 'to' => (float) $row['to'], 'tone' => $row['tone'], 'series' => $row['series']];
    }
    if (! $byDirection && count($items) > 8 && collect($items)->contains(fn ($it) => $it['tone'] === null && Chart::series($it['series']) === null)) {
        throw new \InvalidArgumentException(
            'ds::slope-chart colours each item with a series colour, and there are 8; it was given '.count($items).' items. '
            .'Use colourBy="direction", or fewer items (specs/chart-series.md).'
        );
    }
    $nothing = $items === [];

    $recipe = Chart::recipe();
    $tones = ['up' => 'success', 'down' => 'danger', 'flat' => 'neutral'];
    foreach ($items as $i => &$item) {
        $item['way'] = Chart::direction($item['from'], $item['to']);
        $slot = Chart::series($item['series']) ?? ($byDirection || $item['tone'] !== null ? null : $i + 1);
        $item['muted'] = $wanted !== [] && ! in_array($item['label'], $wanted, true);
        // A row's own tone or series wins; then the chart's rule. A muted
        // item steps back to the subtle foreground, whatever it would be.
        $item['colour'] = $item['muted'] ? 'text-fg-subtle' : Chart::colour($item['tone'] ?? ($byDirection ? $tones[$item['way']] : null), $slot);
        $item['slot'] = $slot === null || $item['muted'] ? '' : (string) $slot;
        $item['fromText'] = $f($item['from']);
        $item['toText'] = $f($item['to']);
        $item['change'] = Chart::change($item['from'], $item['to'], $format);
    }
    unset($item);

    $text = $summary !== null && trim((string) $summary) !== ''
        ? trim((string) $summary)
        : Chart::slope($items, $fromLabel, $toLabel, $format, $label);

    if (! $nothing) {
        /*
         * The frame of ds::bar-chart: no viewBox, so the text and the dots
         * keep their size; the two axes at 0 and 100% of the plot, the
         * labels in the padding beside it, sized from their estimated
         * widths. Each side's names are cut to what is left of the widest
         * gutter there is once its numbers have their room.
         */
        $top = 28;
        $plot = max(['sm' => 160, 'md' => 224, 'lg' => 288][in_array($height, ['sm', 'md', 'lg'], true) ? $height : 'md'], count($items) * 16);
        $values = array_merge(array_column($items, 'from'), array_column($items, 'to'));
        [$lo, $hi] = [min($values), max($values)];
        $y = fn (float $v) => $top + (1 - Chart::fraction($v, $lo, $hi)) * $plot;

        $leftNumbers = max(array_map(fn ($it) => Chart::textWidth($it['fromText']), $items));
        $rightNumbers = max(array_map(fn ($it) => Chart::textWidth($it['toText']) + 4 + Chart::textWidth($it['change']), $items));
        $leftCut = max(48.0, min(112.0, 192 - 16 - $leftNumbers));
        $rightCut = max(48.0, min(112.0, 192 - 16 - $rightNumbers));

        foreach ($items as &$item) {
            $item['y1'] = $y($item['from']);
            $item['y2'] = $y($item['to']);
            $item['left'] = Chart::truncate($item['label'], $leftCut);
            $item['right'] = Chart::truncate($item['label'], $rightCut);
        }
        unset($item);

        // Labels nudged apart where two items end close together, a line
        // of text each, kept inside the plot.
        $leftAt = Chart::spread(array_column($items, 'y1'), 16, $top, $top + $plot);
        $rightAt = Chart::spread(array_column($items, 'y2'), 16, $top, $top + $plot);

        $leftPx = max(array_map(fn ($it) => Chart::textWidth($it['left']) + 6 + Chart::textWidth($it['fromText']), $items)) + 12;
        $rightPx = max(array_map(fn ($it) => Chart::textWidth($it['toText']) + 4 + Chart::textWidth($it['change']) + 6 + Chart::textWidth($it['right']), $items)) + 12;
        $gutter = Chart::gutter(max($leftPx, Chart::textWidth($fromLabel) / 2 + 2));
        $right = Chart::gutter(max($rightPx, Chart::textWidth($toLabel) / 2 + 2), 'right');
        $svgHeight = $top + $plot + 8;
    }

    $interactive = (bool) $interactive && ! $nothing;
    $root = $attributes->class(['relative flex flex-col gap-3 [direction:ltr]']);
    if ($interactive) {
        $root = $root->merge([
            'x-data' => '',
            'x-ds-chart' => '',
            'data-ds-chart-axis' => 'horizontal',
            'data-ds-chart-next' => 'ArrowDown',
            'data-ds-chart-nearest' => '',
        ]);
    }
    $dot = 'fill-current stroke-surface'.($interactive ? ' focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-focus-ring' : '');
@endphp

<div {{ $root }}>
    @if ($nothing)
        <x-ds::empty-state :title="(string) $empty" icon="arrow-trending-up" :bare="true" />
    @else
        <div class="{{ $gutter }} {{ $right }}">
            <svg data-ds-chart-plot role="img" aria-label="{{ $text }}" width="100%" height="{{ $svgHeight }}" class="block overflow-visible">
                {{-- The two columns: drawn for the eye, read through the summary. --}}
                <g aria-hidden="true">
                    <text x="0" y="10" text-anchor="middle" dominant-baseline="central" class="{{ $recipe['value'] }}">{{ $fromLabel }}</text>
                    <text x="100%" y="10" text-anchor="middle" dominant-baseline="central" class="{{ $recipe['value'] }}">{{ $toLabel }}</text>
                    <line x1="0" x2="0" y1="{{ $top }}" y2="{{ $top + $plot }}" stroke="currentColor" shape-rendering="crispEdges" class="{{ $recipe['grid'] }}" />
                    <line x1="100%" x2="100%" y1="{{ $top }}" y2="{{ $top + $plot }}" stroke="currentColor" shape-rendering="crispEdges" class="{{ $recipe['grid'] }}" />
                </g>

                {{-- The muted lines first, so an emphasised one is never drawn
                     under one that has stepped back. The points come after,
                     in the order given, which is the keyboard's order. --}}
                <g aria-hidden="true">
                    @foreach (collect($items)->sortBy(fn ($it) => $it['muted'] ? 0 : 1) as $item)
                        <line x1="0" x2="100%" y1="{{ Chart::n($item['y1']) }}" y2="{{ Chart::n($item['y2']) }}" stroke="currentColor"
                            stroke-width="{{ $item['muted'] ? '1.5' : '2' }}" stroke-linecap="round" class="{{ $item['colour'] }}" />
                    @endforeach
                </g>
                @foreach ($items as $i => $item)
                    <g class="{{ $item['colour'] }}">
                        @foreach ([['at' => 0, 'x' => '0', 'y' => $item['y1'], 'when' => $fromLabel, 'says' => $item['fromText']], ['at' => 1, 'x' => '100%', 'y' => $item['y2'], 'when' => $toLabel, 'says' => $item['toText'].' ('.$item['change'].')']] as $end)
                            <circle cx="{{ $end['x'] }}" cy="{{ Chart::n($end['y']) }}" r="4" stroke-width="2" class="{{ $dot }}"
                                @if ($interactive)
                                    data-ds-chart-mark data-ds-at="{{ $end['at'] }}" data-ds-in="{{ $i }}"
                                    data-ds-label="{{ $item['label'] }}" data-ds-value="{{ $end['says'] }}" data-ds-series="{{ $end['when'] }}" data-ds-slot="{{ $item['slot'] }}"
                                    aria-label="{{ $item['label'] }}, {{ $end['when'] }}: {{ $end['says'] }}"
                                @else
                                    aria-hidden="true"
                                @endif
                            />
                        @endforeach
                    </g>
                @endforeach

                {{-- The labels in the body colour, never the series colour,
                     which is too faint to read as text; the change is written
                     out, so up and down never rest on colour. --}}
                <g aria-hidden="true" class="text-meta tabular-nums">
                    @foreach ($items as $i => $item)
                        <text x="-10" y="{{ Chart::n($leftAt[$i]) }}" text-anchor="end" dominant-baseline="central" @class(['fill-current', 'text-fg-muted' => $item['muted'], 'text-fg' => ! $item['muted']])>@if ($item['left'] !== $item['label'])<title>{{ $item['label'] }}</title>@endif<tspan>{{ $item['left'] }}</tspan><tspan dx="6" class="font-medium">{{ $item['fromText'] }}</tspan></text>
                        <text x="100%" dx="10" y="{{ Chart::n($rightAt[$i]) }}" dominant-baseline="central" @class(['fill-current', 'text-fg-muted' => $item['muted'], 'text-fg' => ! $item['muted']])>@if ($item['right'] !== $item['label'])<title>{{ $item['label'] }}</title>@endif<tspan class="font-medium">{{ $item['toText'] }}</tspan><tspan dx="4" class="fill-current text-fg-muted">{{ $item['change'] }}</tspan><tspan dx="6">{{ $item['right'] }}</tspan></text>
                    @endforeach
                </g>
            </svg>
        </div>

        @if ($table)
            @include('ds::partials.chart-table', [
                'caption' => $label !== null && trim((string) $label) !== '' ? $label : $text,
                'headers' => ['Item', $fromLabel, $toLabel, 'Change'],
                'rows' => array_map(fn ($it) => [$it['label'], $it['fromText'], $it['toText'], $it['change']], $items),
            ])
        @endif

        @if ($interactive)
            @include('ds::partials.chart-readout')
        @endif
    @endif
</div>
