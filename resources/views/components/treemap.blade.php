@props([
    /**
     * The parts: ['Photos' => 150, 'Video' => 96], or rows with `label`,
     * `value`, an optional `tone` or `series` and an optional `href`. A row
     * with `children` (rows of the same shape) is a group, its value the sum
     * of theirs: one level of nesting, no more.
     */
    'data' => [],
    /** What the whole is, "Storage by folder": the start of the generated summary and the list's caption. */
    'label' => null,
    /** How numbers read: see Support\Chart::format(). */
    'format' => [],
    /** sm | md | lg — 192, 256 or 320px tall. */
    'height' => 'md',
    /** The width the layout is shaped for, as a multiple of the height: about the box's own (2 is twice as wide as tall). */
    'ratio' => 2,
    /** series | value — a series colour per top-level part (eight at most), or one hue in four steps by value. */
    'colourBy' => 'series',
    /** value | percent | none — the second line of a tile's label. */
    'showValues' => 'value',
    /** The smallest tile that is labelled, in px, either way: smaller ones are reached by the readout and the list. */
    'minLabel' => 40,
    /** neutral | accent | success | warning | danger — the hue of colourBy="value". */
    'tone' => 'accent',
    /** The groups' names and totals (with children), or the steps of colourBy="value". */
    'legend' => true,
    /** The text alternative, in place of the generated one. */
    'summary' => null,
    /** Every tile in a visually hidden table, the ones too small to label included. */
    'table' => true,
    /** With the package's script: a readout on hover and focus, and every tile reachable by keyboard. */
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
    $byValue = $colourBy === 'value';
    $hue = Chart::tone(is_string($tone) ? $tone : null, 'accent');

    /*
     * Rows into tiles. A value that is not a number is a mistake and throws,
     * naming the row; one of zero or less is legal and has no area, so it is
     * left out of the picture and of the total the shares are taken from,
     * and listed in the table as not drawn. A group is the sum of its
     * children; a grandchild throws, because a second level would need a
     * second colour channel the treemap does not have.
     */
    $leafOf = function (array $row, string $where) {
        if (! is_numeric($row['value'])) {
            throw new \InvalidArgumentException(
                'ds::treemap draws every part as an area, so every value must be a number; ['.$where.'] is '
                .var_export($row['value'], true).'. Leave the part out, or give it 0.'
            );
        }

        return ['label' => $row['label'], 'value' => (float) $row['value']];
    };

    $groups = [];
    $i = 0;
    foreach (is_iterable($data) ? $data : [] as $key => $raw) {
        $raw = $raw instanceof \Traversable ? iterator_to_array($raw) : $raw;
        $row = Chart::rows([$key => $raw])[0];
        if (is_int($key)) {
            $row['label'] = is_array($raw) && isset($raw['label']) && trim((string) $raw['label']) !== '' ? (string) $raw['label'] : (string) ($i + 1);
        }
        $group = ['label' => $row['label'], 'tone' => $row['tone'], 'series' => $row['series'], 'children' => null, 'given' => $i];

        if (is_array($raw) && array_key_exists('children', $raw)) {
            $children = [];
            $c = 0;
            foreach (is_iterable($raw['children']) ? $raw['children'] : [] as $ckey => $craw) {
                $craw = $craw instanceof \Traversable ? iterator_to_array($craw) : $craw;
                if (is_array($craw) && array_key_exists('children', $craw)) {
                    throw new \InvalidArgumentException(
                        'ds::treemap nests one level: ['.$row['label'].'] has a child with children of its own. '
                        .'Fold the deeper level into its parent, or draw it as a treemap of its own.'
                    );
                }
                $crow = Chart::rows([$ckey => $craw])[0];
                if (is_int($ckey)) {
                    $crow['label'] = is_array($craw) && isset($craw['label']) && trim((string) $craw['label']) !== '' ? (string) $craw['label'] : (string) ($c + 1);
                }
                $children[] = $leafOf($crow, $row['label'].' › '.$crow['label']) + ['href' => is_array($craw) && isset($craw['href']) ? (string) $craw['href'] : null];
                $c++;
            }
            $group['children'] = $children;
            $group['value'] = array_sum(array_map(fn ($ch) => max(0.0, $ch['value']), $children));
        } else {
            $group += $leafOf($row, $row['label']);
            $group['href'] = is_array($raw) && isset($raw['href']) ? (string) $raw['href'] : null;
        }

        $groups[] = $group;
        $i++;
    }

    if (! $byValue && count($groups) > 8) {
        throw new \InvalidArgumentException(
            'ds::treemap colours each top-level part with a series colour, and there are 8; it was given '.count($groups).'. '
            .'Group them under at most 8 parents, fold the smallest into an "Other", or use colourBy="value" (specs/chart-series.md).'
        );
    }
    $nested = collect($groups)->contains(fn ($g) => $g['children'] !== null);

    // Colour by position among the parts as given, never by size: a part
    // keeps its slot when another is dropped for being empty.
    foreach ($groups as $g => &$group) {
        $group['slot'] = $byValue ? null : (Chart::series($group['series']) ?? ($group['tone'] === null ? $group['given'] + 1 : null));
        $group['colour'] = $byValue ? null : Chart::colour($group['tone'], $group['slot']);
        $group['ink'] = $byValue ? null : Ink::label($group['tone'], $group['slot']);
    }
    unset($group);

    $sizeKey = in_array($height, ['sm', 'md', 'lg'], true) ? $height : 'md';
    $h = ['sm' => 192, 'md' => 256, 'lg' => 320][$sizeKey];
    $aspect = is_numeric($ratio) && (float) $ratio > 0 ? max(0.5, min(6.0, (float) $ratio)) : 2.0;
    $w = $h * $aspect;

    /*
     * The layout is worked out for a box `ratio` times as wide as it is
     * tall, and drawn with x in percent and y in px: the chart takes its
     * container's width at a fixed height, so in a wider box every tile is
     * wider than it was shaped, never overlapping or short of the edge. The
     * groups first, then each group's children inside its rectangle.
     */
    $tops = ChartShapes::squarify(array_map(fn ($g) => $g['value'], $groups), $w, $h);
    $tiles = [];
    foreach ($tops as $g => $box) {
        $group = $groups[$g];
        $members = [];
        if ($group['children'] === null) {
            $members[] = ['label' => $group['label'], 'value' => $group['value'], 'href' => $group['href'], 'box' => $box];
        } else {
            foreach (ChartShapes::squarify(array_column($group['children'], 'value'), $box['width'], $box['height'], $box['x'], $box['y']) as $k => $inner) {
                $members[] = $group['children'][$k] + ['box' => $inner];
            }
        }
        foreach ($members as $member) {
            $tiles[] = $member + ['group' => $g, 'in' => $group['children'] !== null ? $group['label'] : null];
        }
    }
    $nothing = $tiles === [];

    $total = array_sum(array_column($tiles, 'value'));
    $shares = $nothing ? [] : Chart::percentages(array_column($tiles, 'value'));
    $pct = fn (int $share, float $v) => ($share === 0 && $v > 0 ? '<1' : $share).'%';
    $levels = $byValue && ! $nothing ? Chart::thresholds(array_column($tiles, 'value'), 5) : [];
    $recipe = Chart::recipe();
    $padding = 6;

    foreach ($tiles as $t => &$tile) {
        $group = $groups[$tile['group']];
        $tile['share'] = $pct($shares[$t], $tile['value']);
        $tile['says'] = $f($tile['value']).' ('.$tile['share'].')';
        $tile['name'] = $tile['label'].($tile['in'] !== null ? ', '.$tile['in'] : '');
        $step = $byValue ? max(1, Chart::level($tile['value'], $levels)) : 3;
        $tile['fill'] = $byValue ? $recipe['heatFill'][$step] : 'fill-current '.$group['colour'];
        $tile['ink'] = $byValue ? Ink::label($hue, null, $step) : $group['ink'];
        $tile['slot'] = $group['slot'] === null ? '' : (string) $group['slot'];

        /*
         * The label, measured at the shape the layout was made for: none
         * under `minLabel` either way; the name alone where there is room
         * for one line; then cut to the tile's width, and hidden by
         * container query while the chart is too narrow for what is left.
         * The tile clips its own text, so nothing ever reaches a neighbour.
         */
        $box = $tile['box'];
        $tile['text'] = null;
        if (min($box['width'], $box['height']) >= max(0, (float) $minLabel) && $box['height'] >= 24) {
            $name = Chart::truncate($tile['label'], $box['width'] - 2 * $padding);
            $second = match ($showValues) {
                'none' => null,
                'percent' => $tile['share'],
                default => $f($tile['value']),
            };
            $second = $box['height'] >= 40 ? $second : null;
            $needs = (max(Chart::textWidth($name), $second === null ? 0 : Chart::textWidth($second)) + 2 * $padding) * $w / $box['width'];
            if ($name !== '…' && ($name === $tile['label'] || mb_strlen(rtrim($name, '…')) >= 2) && $needs <= 1280) {
                $tile['text'] = ['name' => $name, 'second' => $second, 'hide' => $needs <= 256 ? '' : Chart::below($needs)];
            }
        }
    }
    unset($tile);

    $dropped = collect($groups)->flatMap(fn ($g) => $g['children'] === null
        ? ($g['value'] > 0 ? [] : [['label' => $g['label'], 'value' => $g['value'], 'in' => null]])
        : collect($g['children'])->filter(fn ($c) => $c['value'] <= 0)->map(fn ($c) => ['label' => $c['label'], 'value' => $c['value'], 'in' => $g['label']])->values()->all()
    )->all();

    $lead = $label !== null && trim((string) $label) !== '' ? trim((string) $label) : null;
    if ($nothing) {
        $generated = '';
    } else {
        // The gist, by the top-level parts the eye reads first; with groups,
        // how many tiles there are and the largest of them.
        $drawn = collect($tops)->keys()->map(fn ($g) => ['label' => $groups[$g]['label'], 'value' => $groups[$g]['value']])->all();
        $drawnShares = Chart::percentages(array_column($drawn, 'value'));
        $generated = Chart::whole($drawn, array_map(fn ($s, $p) => $s === 0 && $p['value'] > 0 ? '<1' : $s, $drawnShares, $drawn), $format, $lead);
        if ($nested) {
            $largest = collect($tiles)->sortByDesc('value')->first();
            $generated .= ' '.count($tiles).' tiles; the largest is '.$largest['label'].($largest['in'] !== null ? ' in '.$largest['in'] : '').' at '.$largest['share'].'.';
        }
        if ($dropped !== []) {
            $generated .= ' '.count($dropped).' of zero or less not drawn.';
        }
    }
    $text = $summary !== null && trim((string) $summary) !== '' ? trim((string) $summary) : $generated;

    $links = collect($tiles)->contains(fn ($t) => $t['href'] !== null);
    $legendItems = [];
    if ($legend && ! $byValue && $nested) {
        $groupShares = Chart::percentages(collect($tops)->keys()->map(fn ($g) => $groups[$g]['value'])->all());
        foreach (collect($tops)->keys()->values() as $n => $g) {
            $legendItems[] = ['name' => $groups[$g]['label'], 'colour' => $groups[$g]['colour'], 'series' => null,
                'detail' => $f($groups[$g]['value']).' · '.$pct($groupShares[$n], $groups[$g]['value'])];
        }
    }
    $ramp = [];
    if ($legend && $byValue && ! $nothing) {
        $bounds = array_values($levels);
        foreach (range(1, max(1, count($bounds))) as $level) {
            $low = $bounds[$level - 1] ?? null;
            $high = $bounds[$level] ?? null;
            $ramp[] = ['class' => $recipe['heat']['steps'][$level], 'title' => $low === null ? '' : ($high === null ? $f($low).' and up' : $f($low).' to under '.$f($high))];
        }
    }

    $interactive = (bool) $interactive && ! $nothing;
    $root = $attributes->class([
        'relative flex flex-col gap-3 [direction:ltr]',
        Chart::heat($hue) => $byValue,
    ]);
    if ($interactive) {
        $root = $root->merge(array_filter([
            'x-data' => '',
            'x-ds-chart' => '',
            'data-ds-chart-axis' => 'both',
            'data-ds-chart-links' => $links ? '' : null,
        ], fn ($v) => $v !== null));
    }
    /*
     * A tile's ring is drawn inside it, as strokes: its neighbours touch it
     * on every side and the plot clips its edges, and a CSS outline on an
     * svg group is placed by its stroke box, not its tile. So a focused
     * tile shows, from its edge in, the 1px gap, 2px of focus-ring and 2px
     * of surface: a ring that reads on any fill, its own hue included. Each
     * stroke is centred on the edge and the tile clips the outer half.
     */
    $focusable = fn (array $tile) => $interactive || $tile['href'] !== null;
@endphp

<div {{ $root }}>
    @if ($nothing)
        <x-ds::empty-state :title="(string) $empty" icon="squares-2x2" :bare="true" />
    @else
        <div class="@container">
            {{-- One image; with links, a group of them and the links. --}}
            <svg data-ds-chart-plot role="{{ $links ? 'group' : 'img' }}" aria-label="{{ $text }}" width="100%" height="{{ $h }}" class="block">
                @foreach ($tiles as $t => $tile)
                    @php
                        $box = $tile['box'];
                        $tag = $tile['href'] !== null ? 'a' : 'g';
                        // Built here, escaped by hand: a bag does not escape
                        // its values, and the labels and links are the host's.
                        $bag = new \Illuminate\View\ComponentAttributeBag(array_filter([
                            'href' => $tile['href'] !== null ? e($tile['href']) : null,
                            'role' => $links && $tile['href'] === null ? 'img' : null,
                            'aria-label' => $links || $interactive ? e($tile['name'].': '.$tile['says']) : null,
                            'class' => $focusable($tile) ? 'group outline-hidden data-ds-active:opacity-85' : null,
                        ] + ($interactive ? [
                            'data-ds-chart-mark' => '', 'data-ds-at' => (string) $t, 'data-ds-in' => '0', 'data-ds-label' => e($tile['label']),
                            'data-ds-value' => e($tile['says']), 'data-ds-series' => e($tile['in'] ?? ''), 'data-ds-slot' => $tile['slot'],
                        ] : []), fn ($v) => $v !== null));
                    @endphp
                    <{{ $tag }} {{ $bag }}>
                        <svg x="{{ Chart::n($box['x'] / $w * 100) }}%" y="{{ Chart::n($box['y']) }}" width="{{ Chart::n($box['width'] / $w * 100) }}%" height="{{ Chart::n($box['height']) }}" class="overflow-hidden">
                            <rect width="100%" height="100%" class="{{ $tile['fill'] }}" />
                            @if ($focusable($tile))
                                <rect data-ds-ring width="100%" height="100%" fill="none" stroke-width="10" class="stroke-transparent group-focus-visible:stroke-surface" />
                                <rect data-ds-ring width="100%" height="100%" fill="none" stroke-width="6" class="stroke-transparent group-focus-visible:stroke-focus-ring" />
                            @endif
                            <rect width="100%" height="100%" fill="none" stroke-width="2" class="stroke-surface" />
                            @if ($tile['text'] !== null)
                                <text x="{{ $padding }}" y="{{ $padding + 1 }}" dominant-baseline="hanging" aria-hidden="true" class="{{ trim('fill-current text-meta tabular-nums '.$tile['ink'].' '.$tile['text']['hide']) }}">@if ($tile['text']['name'] !== $tile['label'])<title>{{ $tile['label'] }}</title>@endif<tspan class="font-medium">{{ $tile['text']['name'] }}</tspan>@if ($tile['text']['second'] !== null)<tspan x="{{ $padding }}" dy="16">{{ $tile['text']['second'] }}</tspan>@endif</text>
                            @endif
                        </svg>
                    </{{ $tag }}>
                @endforeach
            </svg>
        </div>

        @if ($legendItems !== [])
            @include('ds::partials.chart-legend', ['items' => $legendItems, 'toggles' => false, 'column' => false])
        @endif
        @if ($ramp !== [])
            {{-- Every tile says its value in words, so the steps are for the eye. --}}
            <div class="flex items-center gap-1 text-meta text-fg-muted" aria-hidden="true">
                <span class="me-1">Less</span>
                @foreach ($ramp as $key)
                    <span class="size-3 rounded-[2px] {{ $key['class'] }}" title="{{ $key['title'] }}"></span>
                @endforeach
                <span class="ms-1">More</span>
            </div>
        @endif

        @if ($table)
            @include('ds::partials.chart-table', [
                'caption' => $lead ?? $text,
                'headers' => array_merge(['Part'], $nested ? ['Group'] : [], ['Value', 'Share']),
                'rows' => array_merge(
                    array_map(fn ($t) => array_merge([$t['label']], $nested ? [$t['in'] ?? '—'] : [], [$f($t['value']), $t['share']]), $tiles),
                    array_map(fn ($d) => array_merge([$d['label']], $nested ? [$d['in'] ?? '—'] : [], [$f($d['value']), 'not drawn']), $dropped),
                    [array_merge(['Total'], $nested ? [''] : [], [$f($total), '100%'])],
                ),
            ])
        @endif

        @if ($interactive)
            @include('ds::partials.chart-readout')
        @endif
    @endif
</div>
