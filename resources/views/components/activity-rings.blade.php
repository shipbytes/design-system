@props([
    /**
     * The rings, outermost first, at most four: rows with `label`, `value`,
     * `max` (the goal, default 100) and an optional `tone`, `series` or
     * `format` of their own. ['Move' => ['value' => 420, 'max' => 600], …]
     * works too.
     */
    'rings' => [],
    /** What the rings measure, "Today": the start of the generated summary and the table's caption. */
    'label' => null,
    /** sm | md | lg — 128, 160 or 208px across. */
    'size' => 'md',
    /** sm | md | lg — each ring's width, narrowed when four would not leave a hole. */
    'thickness' => 'md',
    /** beside | below | none — the legend, with each ring's value, goal and share. */
    'legend' => 'beside',
    /** How numbers read, for every ring without a format of its own: see Support\Chart::format(). */
    'format' => [],
    /** The text alternative, in place of the generated one. */
    'summary' => null,
    /** Also give the values to assistive technology as a visually hidden table. */
    'table' => false,
    /** With the package's script: a readout on hover and focus, and the rings reachable by keyboard. */
    'interactive' => true,
    /** What the empty state says when there is nothing to draw. */
    'empty' => 'No data to show',
])

@use('Shipbytes\BladeUi\Support\Chart')

@php
    $format = is_array($format) ? $format : [];

    /*
     * A ring is a goal and the progress toward it. Progress below nothing,
     * a goal of nothing and a word are all data the host should have
     * caught, so they throw, naming the ring; so does a fifth ring, which
     * would leave each too thin to read and no hole for the middle.
     */
    $given = Chart::rows(is_iterable($rings) ? $rings : [], ['value', 'max']);
    if (count($given) > 4) {
        throw new \InvalidArgumentException(
            'ds::activity-rings draws at most 4 rings, and was given '.count($given).'. '
            .'Past four each ring is too thin to read; use ds::bar-chart, or several sets of rings.'
        );
    }

    $list = [];
    foreach ($given as $i => $row) {
        $max = $row['max'] ?? 100;
        if (! is_numeric($row['value']) || (float) $row['value'] < 0) {
            throw new \InvalidArgumentException(
                'ds::activity-rings draws progress toward a goal, so every value must be a number of zero or more; ['
                .$row['label'].'] is '.var_export($row['value'], true).'.'
            );
        }
        if (! is_numeric($max) || (float) $max <= 0) {
            throw new \InvalidArgumentException(
                'ds::activity-rings needs a goal above zero for every ring; ['.$row['label'].'] has a max of '.var_export($max, true).'.'
            );
        }
        $ringFormat = array_merge($format, $row['format'] ?? []);
        $value = (float) $row['value'];
        $max = (float) $max;
        $share = (int) round($value / $max * 100);
        $slot = Chart::series($row['series']) ?? ($row['tone'] === null ? $i + 1 : null);
        $list[] = [
            'label' => $row['label'],
            'value' => $value,
            'max' => $max,
            'share' => $share,
            'colour' => Chart::colour($row['tone'], $slot),
            'slot' => $slot === null ? '' : (string) $slot,
            'value_text' => Chart::format($value, $ringFormat),
            'max_text' => Chart::format($max, $ringFormat),
        ];
    }
    $nothing = $list === [];

    $lead = $label !== null && trim((string) $label) !== '' ? trim((string) $label).': ' : '';
    $says = fn (array $r) => $r['value_text'].' of '.$r['max_text'].' ('.$r['share'].'%)';
    // Few enough to say every one, which is what the rings are for.
    $generated = $nothing ? '' : $lead.collect($list)->map(fn ($r) => $r['label'].' '.$says($r))->implode(', ').'.';
    $text = $summary !== null && trim((string) $summary) !== '' ? trim((string) $summary) : ucfirst($generated);

    /*
     * Outermost first, each ring on a faint track of its own colour. A ring
     * past its goal is drawn full and no further: a second lap would need
     * a cue only colour could give, so the real figure, 117%, is said in the
     * legend, the readout, the summary and the table instead.
     */
    $sizeKey = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';
    $nominal = ['sm' => 7, 'md' => 10, 'lg' => 13][in_array($thickness, ['sm', 'md', 'lg'], true) ? $thickness : 'md'];
    $geometry = Chart::rings(max(1, count($list)), $nominal);
    $width = $geometry['width'];
    foreach ($list as $i => &$ring) {
        $ring['r'] = $geometry['radii'][$i];
        $ring['arc'] = Chart::ring($ring['r'], $ring['value'] / $ring['max'], $width);
    }
    unset($ring);
    $hole = $nothing ? 0 : Chart::n(50 - (end($list)['r'] - $width / 2) + 1);

    $boxes = ['sm' => 'size-32', 'md' => 'size-40', 'lg' => 'size-52'];
    $legend = in_array($legend, ['beside', 'below', 'none'], true) ? $legend : 'beside';
    $items = collect($list)->map(fn ($r) => [
        'name' => $r['label'],
        'colour' => $r['colour'],
        'series' => null,
        'detail' => $r['value_text'].' / '.$r['max_text'].' · '.$r['share'].'%',
    ])->all();

    $interactive = (bool) $interactive && ! $nothing;
    $root = $attributes->class([
        'relative flex gap-x-6 gap-y-4 [direction:ltr]',
        'flex-wrap items-center' => $legend === 'beside',
        'flex-col items-center' => $legend !== 'beside',
    ]);
    if ($interactive) {
        $root = $root->merge(['x-data' => '', 'x-ds-chart' => '', 'data-ds-chart-axis' => 'both']);
    }
@endphp

<div {{ $root }}>
    @if ($nothing)
        <x-ds::empty-state :title="(string) $empty" icon="chart-pie" :bare="true" class="w-full" />
    @else
        {{-- The keyboard's ring goes round the whole set, as on a donut: an
             outline on one ring would be its circle's square box. Which ring
             is read shows in its track and in the readout. --}}
        <div class="relative shrink-0 rounded-full {{ $boxes[$sizeKey] }} has-focus-visible:outline-2 has-focus-visible:outline-offset-4 has-focus-visible:outline-focus-ring">
            <svg data-ds-chart-plot role="img" aria-label="{{ $text }}" viewBox="0 0 100 100" class="block size-full overflow-visible">
                @foreach ($list as $i => $ring)
                    <g class="{{ $ring['colour'] }}">
                        {{-- The track is the mark: the whole ring, so the
                             pointer reads it anywhere and a ring at zero is
                             still there to reach. --}}
                        <circle cx="50" cy="50" r="{{ Chart::n($ring['r']) }}" fill="none" stroke="currentColor" stroke-width="{{ Chart::n($width) }}"
                            class="{{ $interactive ? 'opacity-20 outline-none data-ds-active:opacity-40' : 'opacity-20' }}"
                            @if ($interactive)
                                data-ds-chart-mark data-ds-at="{{ $i }}" data-ds-in="0"
                                data-ds-label="{{ $ring['label'] }}" data-ds-value="{{ $says($ring) }}" data-ds-series="" data-ds-slot="{{ $ring['slot'] }}"
                                aria-label="{{ $ring['label'] }}: {{ $says($ring) }}"
                            @else
                                aria-hidden="true"
                            @endif
                        />
                        @if ($ring['arc'] !== null)
                            <circle cx="50" cy="50" r="{{ Chart::n($ring['r']) }}" fill="none" stroke="currentColor" stroke-width="{{ Chart::n($width) }}"
                                stroke-linecap="round" stroke-dasharray="{{ $ring['arc']['dash'] }}" stroke-dashoffset="{{ $ring['arc']['offset'] }}"
                                transform="rotate(-90 50 50)" class="pointer-events-none" aria-hidden="true" />
                        @endif
                    </g>
                @endforeach
            </svg>

            @isset($centre)
                <div class="pointer-events-none absolute flex flex-col items-center justify-center px-1 text-center leading-tight break-words" style="inset: {{ $hole }}%">
                    {{ $centre }}
                </div>
            @endisset
        </div>

        @if ($legend !== 'none')
            <div @class(['min-w-40 flex-1' => $legend === 'beside', 'w-full max-w-xs' => $legend === 'below'])>
                @include('ds::partials.chart-legend', ['items' => $items, 'toggles' => false, 'column' => true])
            </div>
        @endif

        @if ($table)
            @include('ds::partials.chart-table', [
                'caption' => $label !== null && trim((string) $label) !== '' ? $label : $text,
                'headers' => ['Ring', 'Value', 'Goal', 'Share'],
                'rows' => array_map(fn ($r) => [$r['label'], $r['value_text'], $r['max_text'], $r['share'].'%'], $list),
            ])
        @endif

        @if ($interactive)
            @include('ds::partials.chart-readout')
        @endif
    @endif
</div>
