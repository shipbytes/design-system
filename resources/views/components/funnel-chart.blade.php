@props([
    /**
     * The stages, in order: ['Visits' => 12400, 'Sign-ups' => 3100], or rows
     * with `label`, `value` and an optional `tone` or `series`. Every value
     * must be a number of zero or more; a stage larger than the one before
     * it is fine, and is drawn as it is.
     */
    'data' => [],
    /** What the funnel is, "Signups this month": the start of the generated summary and the table's caption. */
    'label' => null,
    /** How numbers read: see Support\Chart::format(). Rates are always percentages. */
    'format' => [],
    /** vertical | horizontal — stages stacked top to bottom, or left to right. */
    'orientation' => 'vertical',
    /** bars | funnel — each stage a centred bar, or bars joined stage to stage by trapezoids. */
    'shape' => 'bars',
    /** step | total | both | none — the drop from the stage before, written between stages; the share of the first, beside each value. */
    'showRate' => 'step',
    /** sm | md | lg — each stage's band (vertical), or the plot's height (horizontal). */
    'height' => 'md',
    /** neutral | accent | success | warning | danger — the hue the stages fade through. A row's own tone or series wins for that stage. */
    'tone' => 'accent',
    /** The text alternative, in place of the generated one. */
    'summary' => null,
    /** Also give the values and rates to assistive technology as a visually hidden table. */
    'table' => false,
    /** With the package's script: a readout on hover and focus, and every stage reachable by keyboard. */
    'interactive' => true,
    /** What the empty state says when there is nothing to draw. */
    'empty' => 'No data to show',
])

@use('Shipbytes\BladeUi\Support\Chart')
@use('Shipbytes\BladeUi\Support\ChartShapes')
@use('Shipbytes\BladeUi\Support\Ink')

@php
    $format = is_array($format) ? $format : [];
    $f = fn (float $v) => Chart::format($v, $format);

    $stages = [];
    foreach (Chart::rows(is_iterable($data) ? $data : []) as $row) {
        if (! is_numeric($row['value']) || (float) $row['value'] < 0) {
            throw new \InvalidArgumentException(
                'ds::funnel-chart draws how many reached each stage, so every value must be a number of zero or more; ['
                .$row['label'].'] is '.var_export($row['value'], true).'.'
            );
        }
        $stages[] = ['label' => $row['label'], 'value' => (float) $row['value'], 'tone' => $row['tone'], 'series' => $row['series']];
    }
    $n = count($stages);
    $top = $n === 0 ? 0.0 : max(array_column($stages, 'value'));
    $nothing = $top <= 0;

    $horizontal = $orientation === 'horizontal';
    $joined = $shape === 'funnel';
    $rate = in_array($showRate, ['step', 'total', 'both', 'none'], true) ? $showRate : 'step';
    $showStep = in_array($rate, ['step', 'both'], true);
    $showTotal = in_array($rate, ['total', 'both'], true);
    $sizeKey = in_array($height, ['sm', 'md', 'lg'], true) ? $height : 'md';
    $hue = Chart::tone(is_string($tone) ? $tone : null, 'accent');
    $recipe = Chart::recipe();

    /*
     * One hue fading stage by stage, from the hue itself to its lightest
     * step: the stages are one thing getting smaller, so they are not given
     * a series colour each. A row's own tone or series is the exception,
     * drawn at full strength.
     */
    $steps = ChartShapes::ramp($n);
    $rates = ChartShapes::rates(array_column($stages, 'value'));
    foreach ($stages as $i => &$stage) {
        $own = Chart::series($stage['series']) !== null || ($stage['tone'] !== null && in_array($stage['tone'], Chart::TONES, true));
        $stage['fill'] = $own ? 'fill-current '.Chart::colour($stage['tone'], $stage['series']) : $recipe['heatFill'][$steps[$i]];
        $stage['ink'] = $own ? Ink::label($stage['tone'], $stage['series']) : Ink::label($hue, null, $steps[$i]);
        $stage['slot'] = (string) (Chart::series($stage['series']) ?? '');
        $stage['frac'] = $nothing ? 0.0 : $stage['value'] / $top;
        $stage['valueText'] = $f($stage['value']);
        $stage['totalText'] = $rates[$i]['total'] === null ? '—' : ChartShapes::percent($rates[$i]['total']);
        $stage['stepText'] = $rates[$i]['step'] === null ? null : ChartShapes::percent($rates[$i]['step']);
        $stage['change'] = $rates[$i]['change'];
        // The second stage's step is its share of the first: said once.
        $stage['says'] = $stage['valueText']
            .($i > 0 && $stage['stepText'] !== null ? ', '.$stage['stepText'].' of '.$stages[$i - 1]['label'] : '')
            .($i > 1 || ($i === 1 && $stage['stepText'] === null) ? ', '.$stage['totalText'].' of '.$stages[0]['label'] : '');
        // The readout is one line where it can be: the step only; the share
        // of the first is in the name, the table and, with `total`, on screen.
        $stage['brief'] = $stage['valueText'].($stage['stepText'] !== null ? ' ('.$stage['stepText'].' of '.$stages[$i - 1]['label'].')' : '');
        $stage['printed'] = $stage['valueText'].($showTotal ? ' · '.$stage['totalText'] : '');
    }
    unset($stage);

    $text = $summary !== null && trim((string) $summary) !== ''
        ? trim((string) $summary)
        : ChartShapes::funnel(array_map(fn ($s) => ['label' => $s['label'], 'value' => $s['value']], $stages), $format, $label);

    if (! $nothing) {
        if (! $horizontal) {
            /*
             * Stages down the page, each a band; a bar centred in it, as wide
             * as its share of the largest stage. x in percent of the plot, y
             * in px, no viewBox: text keeps its size (bar-chart's frame). The
             * names in the left gutter; the value inside the bar while it
             * fits there, and beside it once it does not, the two swapped by
             * container query; the drop written in the gap between stages.
             */
            $band = ['sm' => 28, 'md' => 36, 'lg' => 44][$sizeKey];
            $gap = $showStep ? 24 : ($joined ? 12 : 6);
            $svgHeight = $n * $band + ($n - 1) * $gap;
            $nameRoom = min(120.0, max(array_map(fn ($s) => Chart::textWidth($s['label']), $stages)));
            $room = $nameRoom + 12;
            $gutter = Chart::gutter($room);

            // How far an outside value can reach past the plot's right edge
            // in the narrowest box planned for: that much room on the right.
            $narrowest = 256 - $room - 8;
            $rightPx = 8.0;
            foreach ($stages as &$stage) {
                $inside = Chart::textWidth($stage['printed']) + 16;
                $stage['inside'] = $stage['frac'] > 0 ? $inside / $stage['frac'] + $room + 8 : INF;
                $stage['insideHide'] = $stage['inside'] <= 256 ? '' : Chart::below($stage['inside']);
                $stage['outsideHide'] = $stage['inside'] <= 256 ? null : ($stage['inside'] > 1280 ? '' : Chart::above($stage['inside']));
                if ($stage['outsideHide'] !== null) {
                    $rightPx = max($rightPx, Chart::textWidth($stage['printed']) + 8 - (1 - $stage['frac']) / 2 * $narrowest);
                }
                $stage['name'] = Chart::truncate($stage['label'], $nameRoom);
            }
            unset($stage);
            $right = Chart::gutter($rightPx, 'right');
        } else {
            /*
             * Stages across, each a slot: a bar half its slot wide, as tall
             * as its share of the largest, centred on the middle line. The
             * value above the plot, the name (and the share of the first)
             * below it, the drop in the gap between two bars; each hidden by
             * container query once its slot is too narrow for it, which the
             * readout and the table then carry.
             */
            $plot = ['sm' => 128, 'md' => 192, 'lg' => 256][$sizeKey];
            $head = 24;
            $slot = 100 / $n;
            $bar = $slot * 0.5;
            $svgHeight = $head + $plot + ($showTotal ? 40 : 24);
            $cut = array_map(fn ($s) => Chart::truncate($s['label'], 96), $stages);
            $need = max(array_map(fn ($c) => Chart::textWidth($c), $cut)) + 8;
            $thin = Chart::thinning($n, $need, 0, $n);
            foreach ($stages as $i => &$stage) {
                $stage['name'] = $cut[$i];
                $stage['nameHide'] = Chart::below($thin[$i]);
                $stage['valueHide'] = ($w = (Chart::textWidth($stage['valueText']) + 8) * $n) <= 256 ? '' : Chart::below($w);
                $stage['totalHide'] = ($w = (Chart::textWidth($stage['totalText']) + 8) * $n) <= 256 ? '' : Chart::below($w);
                $stage['changeHide'] = $stage['change'] === null ? '' : (($w = (Chart::textWidth($stage['change']) + 6) * 2 * $n) <= 256 ? '' : Chart::below($w));
            }
            unset($stage);
            $gutter = Chart::gutter(16);
            $right = Chart::gutter(16, 'right');
        }
    }

    $interactive = (bool) $interactive && ! $nothing;
    $root = $attributes->class(['relative flex flex-col gap-3 [direction:ltr]', Chart::heat($hue)]);
    if ($interactive) {
        $root = $root->merge(['x-data' => '', 'x-ds-chart' => '', 'data-ds-chart-axis' => $horizontal ? 'horizontal' : 'vertical']);
    }
    $mark = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring data-ds-active:opacity-85';
@endphp

<div {{ $root }}>
    @if ($nothing)
        <x-ds::empty-state :title="(string) $empty" icon="funnel" :bare="true" />
    @else
        <div class="@container">
            <div class="{{ $gutter }} {{ $right }}">
                <svg data-ds-chart-plot role="img" aria-label="{{ $text }}" width="100%" height="{{ $svgHeight }}" class="block overflow-visible">
                    @if ($joined && $n > 1)
                        {{-- The trapezoids between stages, stretched across the
                             plot: a polygon takes no percentages, so they are
                             drawn in a 100-wide box scaled to it. --}}
                        <svg width="100%" height="{{ $svgHeight }}" viewBox="0 0 100 {{ $svgHeight }}" preserveAspectRatio="none" aria-hidden="true" class="overflow-visible">
                            @foreach (array_slice($stages, 0, -1) as $i => $stage)
                                @php
                                    $next = $stages[$i + 1];
                                    if ($horizontal) {
                                        $x0 = $i * $slot + $slot * 0.25 + $bar;
                                        $x1 = ($i + 1) * $slot + $slot * 0.25;
                                        $mid = $head + $plot / 2;
                                        $points = [[$x0, $mid - $stage['frac'] * $plot / 2], [$x1, $mid - $next['frac'] * $plot / 2], [$x1, $mid + $next['frac'] * $plot / 2], [$x0, $mid + $stage['frac'] * $plot / 2]];
                                    } else {
                                        $y0 = $i * ($band + $gap) + $band;
                                        $y1 = $y0 + $gap;
                                        $points = [[50 - $stage['frac'] * 50, $y0], [50 + $stage['frac'] * 50, $y0], [50 + $next['frac'] * 50, $y1], [50 - $next['frac'] * 50, $y1]];
                                    }
                                @endphp
                                <polygon points="{{ implode(' ', array_map(fn ($p) => Chart::n($p[0]).','.Chart::n($p[1]), $points)) }}" class="{{ $stage['fill'] }} opacity-30" />
                            @endforeach
                        </svg>
                    @endif

                    @foreach ($stages as $i => $stage)
                        @php
                            $geometry = $horizontal
                                ? ['x' => Chart::n($i * $slot + $slot * 0.25).'%', 'y' => Chart::n($head + (1 - $stage['frac']) * $plot / 2), 'width' => Chart::n($bar).'%', 'height' => Chart::n($stage['frac'] * $plot)]
                                : ['x' => Chart::n(50 - $stage['frac'] * 50).'%', 'y' => Chart::n($i * ($band + $gap)), 'width' => Chart::n($stage['frac'] * 100).'%', 'height' => $band];
                        @endphp
                        <rect x="{{ $geometry['x'] }}" y="{{ $geometry['y'] }}" width="{{ $geometry['width'] }}" height="{{ $geometry['height'] }}"
                            class="{{ trim($stage['fill'].($interactive ? ' '.$mark : '')) }}"
                            @if ($interactive)
                                data-ds-chart-mark data-ds-at="{{ $i }}" data-ds-in="0"
                                data-ds-label="{{ $stage['label'] }}" data-ds-value="{{ $stage['brief'] }}" data-ds-series="" data-ds-slot="{{ $stage['slot'] }}"
                                aria-label="{{ $stage['label'] }}: {{ $stage['says'] }}"
                            @endif
                        />
                    @endforeach

                    {{-- The words: drawn for the eye, read through the summary. --}}
                    <g aria-hidden="true" class="text-meta tabular-nums">
                        @foreach ($stages as $i => $stage)
                            @if (! $horizontal)
                                @php $mid = $i * ($band + $gap) + $band / 2; @endphp
                                <text x="-8" y="{{ Chart::n($mid) }}" text-anchor="end" dominant-baseline="central" class="fill-current text-fg">@if ($stage['name'] !== $stage['label'])<title>{{ $stage['label'] }}</title>@endif{{ $stage['name'] }}</text>
                                @if ($stage['insideHide'] !== 'hidden')
                                    <text x="50%" y="{{ Chart::n($mid) }}" text-anchor="middle" dominant-baseline="central" class="{{ trim('fill-current '.$stage['ink'].' '.$stage['insideHide']) }}"><tspan class="font-medium">{{ $stage['valueText'] }}</tspan>@if ($showTotal)<tspan> · {{ $stage['totalText'] }}</tspan>@endif</text>
                                @endif
                                @if ($stage['outsideHide'] !== null)
                                    <text x="{{ Chart::n(50 + $stage['frac'] * 50) }}%" dx="8" y="{{ Chart::n($mid) }}" dominant-baseline="central" class="{{ trim('fill-current text-fg '.$stage['outsideHide']) }}"><tspan class="font-medium">{{ $stage['valueText'] }}</tspan>@if ($showTotal)<tspan class="fill-current text-fg-muted"> · {{ $stage['totalText'] }}</tspan>@endif</text>
                                @endif
                                @if ($showStep && $stage['change'] !== null)
                                    <text x="50%" y="{{ Chart::n($i * ($band + $gap) - $gap / 2) }}" text-anchor="middle" dominant-baseline="central" class="fill-current font-medium text-fg">{{ $stage['change'] }}</text>
                                @endif
                            @else
                                @php $centre = Chart::n(($i + 0.5) * $slot).'%'; @endphp
                                <text x="{{ $centre }}" y="12" text-anchor="middle" dominant-baseline="central" class="{{ trim('fill-current font-medium text-fg '.$stage['valueHide']) }}">{{ $stage['valueText'] }}</text>
                                <text x="{{ $centre }}" y="{{ $head + $plot + 8 }}" text-anchor="middle" dominant-baseline="hanging" class="{{ trim('fill-current text-fg '.$stage['nameHide']) }}">@if ($stage['name'] !== $stage['label'])<title>{{ $stage['label'] }}</title>@endif{{ $stage['name'] }}</text>
                                @if ($showTotal)
                                    <text x="{{ $centre }}" y="{{ $head + $plot + 24 }}" text-anchor="middle" dominant-baseline="hanging" class="{{ trim('fill-current text-fg-muted '.$stage['totalHide']) }}">{{ $stage['totalText'] }}</text>
                                @endif
                                @if ($showStep && $stage['change'] !== null)
                                    <text x="{{ Chart::n($i * $slot) }}%" y="{{ Chart::n($head + $plot / 2) }}" text-anchor="middle" dominant-baseline="central" class="{{ trim('fill-current font-medium text-fg '.$stage['changeHide']) }}">{{ $stage['change'] }}</text>
                                @endif
                            @endif
                        @endforeach
                    </g>
                </svg>
            </div>
        </div>

        @if ($table)
            @include('ds::partials.chart-table', [
                'caption' => $label !== null && trim((string) $label) !== '' ? $label : $text,
                'headers' => ['Stage', 'Value', 'Of the stage before', 'Of the first'],
                'rows' => array_map(fn ($s) => [$s['label'], $s['valueText'], $s['stepText'] ?? '—', $s['totalText']], $stages),
            ])
        @endif

        @if ($interactive)
            @include('ds::partials.chart-readout')
        @endif
    @endif
</div>
