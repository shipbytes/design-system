@props([
    /**
     * One number, a share of `total` (100 unless given): 37. Or the parts:
     * ['Free' => 62, 'Pro' => 30], or rows with `label`, `value` and an
     * optional `tone` or `series`. Every value must be a number of zero or
     * more.
     */
    'data' => null,
    /** The whole the cells stand for: 100 for one number, the parts' sum for several, unless given. */
    'total' => null,
    /** Cells down (1 to 20). */
    'rows' => 10,
    /** Cells across (1 to 20). */
    'columns' => 10,
    /** What the whole is, "Seats taken": the start of the generated summary and the table's caption. */
    'label' => null,
    /** How numbers read: see Support\Chart::format(). */
    'format' => [],
    /** beside | below | none — the legend, with each part's value and share. */
    'legend' => 'beside',
    /** sm | md | lg — a cell 10, 14 or 18px across. */
    'size' => 'md',
    /** square | circle — the cell. */
    'shape' => 'square',
    /** neutral | accent | success | warning | danger — ONE number whose colour means something. Ignored with several parts. */
    'tone' => null,
    /** The text alternative, in place of the generated one. */
    'summary' => null,
    /** Also give the values to assistive technology as a visually hidden table. */
    'table' => false,
    /** With the package's script: a readout on hover and focus, and each part reachable by keyboard. */
    'interactive' => true,
    /** What the empty state says when there is nothing to draw. */
    'empty' => 'No data to show',
])

@use('Shipbytes\BladeUi\Support\Chart')

@php
    $format = is_array($format) ? $format : [];
    $f = fn (float $v) => Chart::format($v, $format);

    // One number is a share of a hundred unless said otherwise; several are
    // parts of their own sum, which `total` can make larger.
    $single = is_numeric($data);
    $given = $single ? [['label' => trim((string) $label) !== '' ? trim((string) $label) : 'Value', 'value' => $data, 'tone' => $tone !== null ? Chart::tone((string) $tone, 'accent') : null, 'series' => null]] : Chart::rows(is_iterable($data) ? $data : []);

    $parts = [];
    foreach ($given as $i => $row) {
        if (! is_numeric($row['value']) || (float) $row['value'] < 0) {
            throw new \InvalidArgumentException(
                'ds::waffle-chart draws shares of a whole, so every value must be a number of zero or more; ['
                .$row['label'].'] is '.var_export($row['value'], true).'. Use ds::bar-chart for values that can be negative.'
            );
        }
        $slot = Chart::series($row['series']) ?? ($row['tone'] === null ? $i + 1 : null);
        $parts[] = ['label' => $row['label'], 'value' => (float) $row['value'], 'colour' => Chart::colour($row['tone'], $slot), 'slot' => $slot === null ? '' : (string) $slot];
    }
    if (count($parts) > 8) {
        throw new \InvalidArgumentException(
            'ds::waffle-chart draws at most 8 parts, one per series colour, and was given '.count($parts).'. '
            .'Fold the smallest into an "Other" part (specs/chart-series.md).'
        );
    }

    $sum = array_sum(array_column($parts, 'value'));
    $whole = max($sum, is_numeric($total) ? (float) $total : ($single ? 100.0 : $sum));
    $rest = $whole - $sum;
    $nothing = $parts === [] || $whole <= 0;

    $down = max(1, min(20, (int) $rows));
    $across = max(1, min(20, (int) $columns));
    $count = $down * $across;

    $shares = $nothing ? [] : Chart::percentages(array_merge(array_column($parts, 'value'), $rest > 0 ? [$rest] : []));
    $pct = fn (int $share, float $v) => ($share === 0 && $v > 0 ? '<1' : $share).'%';
    $cells = $nothing ? ['parts' => [], 'empty' => 0] : Chart::cells(array_column($parts, 'value'), $count, $whole);

    foreach ($parts as $i => &$part) {
        $part['share'] = $shares[$i] ?? 0;
        $part['cells'] = $cells['parts'][$i] ?? 0;
        $part['says'] = $f($part['value']).' ('.$pct($part['share'], $part['value']).')';
    }
    unset($part);

    $legend = in_array($legend, ['beside', 'below', 'none'], true) ? $legend : 'beside';
    $lead = $label !== null && trim((string) $label) !== '' ? trim((string) $label).': ' : '';
    $generated = $single && ! $nothing
        ? $lead.$f($parts[0]['value']).' of '.$f($whole).' ('.$pct($parts[0]['share'], $parts[0]['value']).').'
        : Chart::whole(
            array_map(fn ($p) => ['label' => $p['label'], 'value' => $p['value']], $parts),
            array_map(fn ($p) => rtrim($pct($p['share'], $p['value']), '%'), $parts),
            $format, $label, $legend === 'none', $rest > 0 ? $whole : null,
        );
    $text = $summary !== null && trim((string) $summary) !== '' ? trim((string) $summary) : ucfirst($generated);

    $recipe = Chart::recipe();
    $sizeKey = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';
    $cell = ['sm' => 'size-2.5', 'md' => 'size-3.5', 'lg' => 'size-4.5'][$sizeKey];
    $round = $shape === 'circle' ? 'rounded-full' : 'rounded-[2px]';

    /*
     * The cells in reading order: each part's run, then the empty ones. The
     * grid places them, so a right-to-left page fills from the right with
     * nothing here knowing. A part's first cell is the mark the keyboard
     * reaches and the readout names; the rest point to it, so the pointer
     * reads the part over any of its cells.
     */
    $grid = [];
    foreach ($parts as $i => $part) {
        for ($c = 0; $c < $part['cells']; $c++) {
            $grid[] = ['part' => $i, 'first' => $c === 0, 'colour' => $part['colour']];
        }
    }
    for ($c = 0; $c < $cells['empty']; $c++) {
        $grid[] = ['part' => null, 'first' => false, 'colour' => $recipe['track']];
    }

    $items = collect($parts)->map(fn ($p) => [
        'name' => $p['label'],
        'colour' => $p['colour'],
        'series' => null,
        'detail' => $f($p['value']).' · '.$pct($p['share'], $p['value']),
    ])->all();

    $interactive = (bool) $interactive && ! $nothing;
    $root = $attributes->class([
        'relative flex gap-x-6 gap-y-4',
        'flex-wrap items-center' => $legend === 'beside',
        'flex-col items-start' => $legend !== 'beside',
    ]);
    if ($interactive) {
        $root = $root->merge(['x-data' => '', 'x-ds-chart' => '', 'data-ds-chart-axis' => 'both']);
    }
    $mark = 'focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-focus-ring data-ds-active:opacity-85';
@endphp

<div {{ $root }}>
    @if ($nothing)
        <x-ds::empty-state :title="(string) $empty" icon="squares-2x2" :bare="true" class="w-full" />
    @else
        {{-- One image, not a hundred announced cells. Columns from inline
             style: a class built from a number is invisible to Tailwind. --}}
        <div data-ds-chart-plot role="img" aria-label="{{ $text }}" class="grid shrink-0 gap-0.5" style="grid-template-columns: repeat({{ $across }}, max-content)">
            @foreach ($grid as $g)
                @if ($interactive && $g['first'])
                    @php $p = $parts[$g['part']]; @endphp
                    <span class="block {{ $cell }} {{ $round }} bg-current {{ $g['colour'] }} {{ $mark }}"
                        data-ds-chart-mark data-ds-at="{{ $g['part'] }}" data-ds-in="0"
                        data-ds-label="{{ $p['label'] }}" data-ds-value="{{ $p['says'] }}" data-ds-series="" data-ds-slot="{{ $p['slot'] }}"
                        aria-label="{{ $p['label'] }}: {{ $p['says'] }}"></span>
                @elseif ($interactive && $g['part'] !== null)
                    <span class="block {{ $cell }} {{ $round }} bg-current {{ $g['colour'] }}" data-ds-chart-piece="{{ $g['part'] }}"></span>
                @else
                    <span class="block {{ $cell }} {{ $round }} bg-current {{ $g['colour'] }}"></span>
                @endif
            @endforeach
        </div>

        @if ($legend !== 'none')
            <div @class(['min-w-40 flex-1' => $legend === 'beside', 'w-full max-w-xs' => $legend === 'below'])>
                @include('ds::partials.chart-legend', ['items' => $items, 'toggles' => false, 'column' => true])
            </div>
        @endif

        @if ($table)
            @include('ds::partials.chart-table', [
                'caption' => $label !== null && trim((string) $label) !== '' ? $label : $text,
                'headers' => ['Part', 'Value', 'Share'],
                'rows' => array_merge(
                    array_map(fn ($p) => [$p['label'], $f($p['value']), $pct($p['share'], $p['value'])], $parts),
                    [['Total', $f($whole), '100%']],
                ),
            ])
        @endif

        @if ($interactive)
            @include('ds::partials.chart-readout')
        @endif
    @endif
</div>
