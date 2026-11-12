@props([
    /** Counts by day: ['2026-03-03' => 12, …]. Keys are dates (Y-m-d, or anything Carbon reads); a day not given is zero. */
    'data' => [],
    /** The first day shown. Default: `weeks` weeks back from `to`. */
    'from' => null,
    /** The last day shown. Default: today. */
    'to' => null,
    /** How many weeks to show when there is no `from`. 53 is a year, with the partial week at either end. */
    'weeks' => 53,
    /** 0 = Sunday, 1 = Monday, as on ds::date-picker. Which day a week starts on is regional. */
    'weekStartsOn' => 1,
    /**
     * How the counts become shades. A number from 2 to 5 (counting "none")
     * splits the days with anything into equal shares by count; a list such
     * as [1, 5, 10, 20] gives the lowest count of each level above none.
     */
    'levels' => 5,
    /** neutral | accent | success | warning | danger */
    'tone' => 'accent',
    /** 1 to 8 — the chart-series slot, when the heatmap is one of several. Wins over `tone`. */
    'series' => null,
    /** What the heatmap shows. The table's caption and the scrolling region's name. Required. */
    'label' => null,
    /** The thing counted, singular: "12 contributions on 3 March 2026". Or [singular, plural]. */
    'unit' => 'contribution',
    /** The month names above the weeks. */
    'showMonthLabels' => true,
    /** Mon, Wed and Fri down the side. */
    'showWeekdayLabels' => true,
    /** A link for each day that has a count, with {date} replaced by its Y-m-d: "/activity/{date}". */
    'href' => null,
    /** The sentence after the label in the caption, in place of the generated total and busiest day. */
    'summary' => null,
])

@use('Shipbytes\BladeUi\Support\Chart')
@use('Illuminate\Support\Carbon')

@php
    if ($label === null || trim((string) $label) === '') {
        throw new \InvalidArgumentException(
            'ds::activity-heatmap needs a label: it is the table\'s caption and the name of its scrolling '
            .'region, which a screen reader announces. Pass label="Contributions in the last year".'
        );
    }

    $locale = app()->getLocale();
    $weekStartsOn = (int) $weekStartsOn === 0 ? 0 : 1;

    $to = Carbon::parse($to ?? Carbon::today())->locale($locale)->startOfDay();
    $from = $from !== null
        ? Carbon::parse($from)->locale($locale)->startOfDay()
        : $to->copy()->subWeeks(max(1, (int) $weeks))->addDay();
    if ($from->greaterThan($to)) {
        [$from, $to] = [$to, $from];
    }

    // Counts by Y-m-d, inside the range. Two keys for one day add up.
    $counts = [];
    foreach (is_iterable($data) ? $data : [] as $day => $count) {
        try {
            $date = Carbon::parse($day)->startOfDay();
        } catch (\Throwable) {
            continue;
        }
        if ($date->betweenIncluded($from, $to) && is_numeric($count)) {
            $counts[$date->format('Y-m-d')] = ($counts[$date->format('Y-m-d')] ?? 0) + (float) $count;
        }
    }

    $thresholds = Chart::thresholds($counts, is_array($levels) ? $levels : (int) $levels);
    $steps = Chart::steps(is_array($levels) ? count($thresholds) + 1 : (int) $levels);

    [$one, $many] = is_array($unit) ? array_values($unit) + [1 => \Illuminate\Support\Str::plural((string) ($unit[0] ?? ''))] : [(string) $unit, \Illuminate\Support\Str::plural((string) $unit)];
    $noun = fn (float $n) => $n == 1 ? $one : $many;
    $says = fn (float $n, Carbon $day) => ($n > 0 ? Chart::format($n).' '.$noun($n) : 'No '.$many).' on '.$day->translatedFormat('j F Y');

    /*
     * Weeks as table ROWS, days of the week as columns, in the DOM: so a
     * screen reader reads it in time order, week by week, each day under
     * its weekday heading. CSS lays the rows out as columns (grid, flowing
     * by column), which is the picture people know. The roles are written
     * out because WebKit drops a table's semantics once it is display:
     * grid, and the explicit ones bring them back.
     */
    $start = $from->copy()->subDays(($from->dayOfWeek - $weekStartsOn + 7) % 7);
    $weekRows = [];
    for ($week = $start->copy(); $week->lessThanOrEqualTo($to); $week->addWeek()) {
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $week->copy()->addDays($i);
            if (! $day->betweenIncluded($from, $to)) {
                $days[] = null;
                continue;
            }
            $count = $counts[$day->format('Y-m-d')] ?? 0.0;
            $days[] = [
                'date' => $day,
                'count' => $count,
                'step' => $steps[Chart::level($count, $thresholds)] ?? 0,
                'says' => $says($count, $day),
            ];
        }
        $first = collect($days)->filter()->first()['date'];
        $weekRows[] = ['first' => $first, 'start' => $week->copy(), 'days' => $days, 'month' => null];
    }

    /*
     * A month's name over the first week that has its first day shown. Two
     * labels closer than three weeks would overprint each other, so the
     * earlier one gives way: it is the partial month at the start. A label
     * in the last two weeks would run past the table's edge and be cut off
     * by the scrolling box, so it gives way too.
     */
    $marks = [];
    foreach ($weekRows as $i => $row) {
        $previous = $i > 0 ? $weekRows[$i - 1]['first'] : null;
        if ($previous === null || $previous->month !== $row['first']->month || $previous->year !== $row['first']->year) {
            $marks[] = $i;
        }
    }
    foreach ($marks as $n => $i) {
        if (($marks[$n + 1] ?? count($weekRows)) - $i >= 3) {
            $weekRows[$i]['month'] = $weekRows[$i]['first']->translatedFormat('M');
        }
    }

    $weekdays = collect(range(0, 6))->map(function ($i) use ($start) {
        $day = $start->copy()->addDays($i);
        return ['long' => $day->translatedFormat('l'), 'short' => $day->translatedFormat('D'), 'shown' => in_array($day->dayOfWeekIso, [1, 3, 5], true)];
    });

    $total = array_sum($counts);
    $busiest = $counts === [] ? null : array_search(max($counts), $counts, true);
    $sentence = $summary !== null && trim((string) $summary) !== ''
        ? trim((string) $summary)
        : Chart::format($total).' '.$noun($total).' from '.$from->translatedFormat('j F Y').' to '.$to->translatedFormat('j F Y')
            .($busiest !== null && $counts[$busiest] > 0 ? '; the busiest day was '.Carbon::parse($busiest)->locale($locale)->translatedFormat('j F Y').', with '.Chart::format($counts[$busiest]) : '')
            .'.';

    $recipe = Chart::recipe()['heat'];
    $bothLabels = $showMonthLabels && $showWeekdayLabels;
    $captionId = 'ds-heatmap-'.substr(md5($label.$from->format('Ymd').$to->format('Ymd').$sentence), 0, 10);

    // The legend: one square per level in use, each titled with what it means.
    $legend = [];
    $bounds = array_values($thresholds);
    foreach ($steps as $level => $step) {
        $low = $level === 0 ? null : $bounds[$level - 1] ?? null;
        $high = $level === 0 ? null : (isset($bounds[$level]) ? $bounds[$level] - 1 : null);
        $legend[] = ['step' => $step, 'title' => match (true) {
            $level === 0 => 'No '.$many,
            $low === null => '',
            $high === null || $high < $low => Chart::format($low).'+ '.$many,
            $high == $low => Chart::format($low).' '.$noun($low),
            default => Chart::format($low).'–'.Chart::format($high).' '.$many,
        }];
    }

    $grid = implode(' ', [
        'grid w-max mr-auto grid-flow-col gap-0.75 [direction:ltr]',
        $showMonthLabels ? 'grid-rows-[auto_repeat(7,0.625rem)]' : 'grid-rows-[repeat(7,0.625rem)]',
        $showWeekdayLabels ? 'grid-cols-[auto] auto-cols-[0.625rem]' : 'auto-cols-[0.625rem]',
    ]);
    $cell = 'block size-2.5 rounded-[2px]';
@endphp

<div {{ $attributes->merge(['class' => 'flex max-w-full flex-col gap-2 '.Chart::heat($tone, $series)]) }}>
    {{-- Scrolls inside itself on a narrow screen, starting at the most recent
         week: right to left on the box puts its scroll origin at the right
         edge, with no script, and the table inside reads left to right again.
         mr-auto keeps a table that fits on the left. Focusable, so a keyboard
         can scroll it where the browser does not make it so. --}}
    <div role="region" aria-labelledby="{{ $captionId }}" tabindex="0"
        class="overflow-x-auto rounded-control p-1 [direction:rtl] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
        <table role="table" class="{{ $grid }}">
            <caption id="{{ $captionId }}" class="sr-only">{{ $label }}. {{ $sentence }}</caption>
            <thead class="contents">
                <tr role="row" class="contents">
                    <th scope="col" role="columnheader" @class(['p-0', 'sr-only' => ! $bothLabels])><span class="sr-only">Week</span></th>
                    @foreach ($weekdays as $weekday)
                        <th scope="col" role="columnheader" @class([
                            'p-0 pe-1.5 text-start align-middle text-meta leading-2.5 font-normal text-fg-muted',
                            'sr-only' => ! $showWeekdayLabels,
                        ])>
                            <span class="sr-only">{{ $weekday['long'] }}</span>
                            <span aria-hidden="true" @class(['invisible' => ! $weekday['shown']])>{{ $weekday['short'] }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="contents">
                @foreach ($weekRows as $row)
                    <tr role="row" class="contents">
                        <th scope="row" role="rowheader" @class(['relative h-4 p-0 font-normal', 'sr-only' => ! $showMonthLabels])>
                            <span class="sr-only">Week of {{ $row['start']->translatedFormat('j F Y') }}</span>
                            @if ($row['month'] !== null && $showMonthLabels)
                                <span aria-hidden="true" class="absolute start-0 top-0 whitespace-nowrap text-meta text-fg-muted">{{ $row['month'] }}</span>
                            @endif
                        </th>
                        @foreach ($row['days'] as $day)
                            @if ($day === null)
                                <td role="cell" class="p-0"></td>
                            @elseif ($href !== null && $day['count'] > 0)
                                <td role="cell" class="p-0">
                                    <a href="{{ str_replace('{date}', $day['date']->format('Y-m-d'), $href) }}" title="{{ $day['says'] }}"
                                        class="{{ $cell }} {{ $recipe['steps'][$day['step']] }} focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-focus-ring"><span class="sr-only">{{ $day['says'] }}</span></a>
                                </td>
                            @else
                                <td role="cell" class="p-0" title="{{ $day['says'] }}">
                                    <span class="{{ $cell }} {{ $recipe['steps'][$day['step']] }}"></span><span class="sr-only">{{ $day['says'] }}</span>
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Every cell says its count in words, so the legend is for the eye:
         hidden from assistive technology, which would hear it as noise. --}}
    <div class="flex items-center justify-end gap-1 text-meta text-fg-muted" aria-hidden="true">
        <span class="me-1">Less</span>
        @foreach ($legend as $key)
            <span class="{{ $cell }} {{ $recipe['steps'][$key['step']] }}" title="{{ $key['title'] }}"></span>
        @endforeach
        <span class="ms-1">More</span>
    </div>
</div>
