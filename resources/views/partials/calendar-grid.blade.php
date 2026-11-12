{{--
    The month grid ds::calendar and ds::date-picker's popover share, one
    implementation: drawn here complete for the first months from
    Support\Calendar::grid(), then redrawn in place by dsCalendar, which
    rewrites the same cells (six rows a month, hidden past the month's own)
    and never adds an element or a class to them. Visual state travels as
    attributes the classes below read: data-selected, data-between,
    data-today, data-outside, aria-disabled, and data-tone on a marker.

    Expects $grid, $names, $size (sm | md | lg), $weekNumbers, $jump (month
    and year selects in the first header), $years, $phoneOne (months after
    the first hidden below sm) and $words.
--}}
@php
    $cellSize = match ($size) {
        'sm' => 'size-8 text-meta',
        'lg' => 'size-11 text-body',
        default => 'size-9 text-body',
    };
    $cols = $weekNumbers ? 'grid-cols-8' : 'grid-cols-7';
    $icon = 'rounded-control p-1.5 text-fg-muted motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter hover:bg-surface-subtle hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring';
    $select = 'rounded-control border border-border bg-surface py-1 ps-2 pe-7 text-body font-medium text-fg motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter hover:border-fg/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring';
    $count = count($grid['months']);
@endphp
{{-- One voice for every month in view, so moving announces once. --}}
<div class="sr-only" aria-live="polite" data-ds-cal-live></div>
<div class="flex flex-wrap items-start gap-x-6 gap-y-4" data-ds-cal-months>
    @foreach ($grid['months'] as $i => $month)
        <div data-ds-cal-month="{{ sprintf('%04d-%02d', $month['year'], $month['month']) }}" @class(['max-sm:hidden' => $phoneOne && $i > 0])>
            <div @class(['mb-2 flex items-center justify-between gap-2', 'min-h-9' => $jump])>
                @if ($i === 0)
                    <button type="button" data-ds-cal-prev aria-label="{{ $words['previous'] }}" class="{{ $icon }}">
                        <x-ds::icon name="chevron-left" variant="mini" size="4" class="rtl:-scale-x-100" />
                    </button>
                @else
                    <span class="size-7" aria-hidden="true"></span>
                @endif

                @if ($jump && $i === 0)
                    <span class="flex items-center gap-1.5">
                        <select data-ds-cal-jump="month" aria-label="{{ $words['month'] }}" class="{{ $select }}">
                            @foreach ($names['months'] as $m => $monthName)
                                <option value="{{ $m + 1 }}" @selected($m + 1 === $month['month'])>{{ $monthName }}</option>
                            @endforeach
                        </select>
                        <select data-ds-cal-jump="year" aria-label="{{ $words['year'] }}" class="{{ $select }} tabular-nums">
                            @foreach ($years as $year)
                                <option value="{{ $year }}" @selected($year === $month['year'])>{{ $year }}</option>
                            @endforeach
                        </select>
                    </span>
                @else
                    <span class="text-body font-medium text-fg" data-ds-cal-label>{{ $month['label'] }}</span>
                @endif

                @if ($i === $count - 1 || ($phoneOne && $i === 0))
                    {{-- With the later months hidden on a phone, the first
                         one carries next too, kept as a spacer from sm. --}}
                    <button type="button" data-ds-cal-next aria-label="{{ $words['next'] }}" @class([$icon, 'sm:invisible' => $i < $count - 1])>
                        <x-ds::icon name="chevron-right" variant="mini" size="4" class="rtl:-scale-x-100" />
                    </button>
                @else
                    <span class="size-7" aria-hidden="true"></span>
                @endif
            </div>

            <div role="grid" aria-label="{{ $month['label'] }}" data-ds-cal-grid class="flex flex-col gap-0.5">
                <div role="row" class="grid {{ $cols }} gap-0.5">
                    @if ($weekNumbers)
                        <div role="columnheader" class="py-1 text-center text-meta font-medium text-fg-muted">
                            <span aria-hidden="true">{{ $words['weekShort'] }}</span><span class="sr-only">{{ $words['week'] }}</span>
                        </div>
                    @endif
                    @foreach ($names['short'] as $c => $short)
                        <div role="columnheader" class="py-1 text-center text-meta font-medium text-fg-muted">
                            <span aria-hidden="true">{{ $short }}</span><span class="sr-only">{{ $names['long'][($c + $names['start']) % 7] }}</span>
                        </div>
                    @endforeach
                </div>

                @foreach ($month['rows'] as $row)
                    <div role="row" class="grid {{ $cols }} gap-0.5" data-ds-cal-row @if ($row['hidden']) hidden @endif>
                        @if ($weekNumbers)
                            <div role="rowheader" class="flex items-center justify-center text-meta text-fg-muted tabular-nums" data-ds-cal-week>{{ $row['week'] }}</div>
                        @endif
                        @foreach ($row['cells'] as $cell)
                            <div role="gridcell" class="flex justify-center" aria-selected="{{ $cell['selected'] ? 'true' : 'false' }}">
                                <button
                                    type="button"
                                    data-ds-cal-day
                                    data-day="{{ $cell['iso'] }}"
                                    tabindex="{{ $cell['iso'] === $grid['stop'] && ! $cell['outside'] ? '0' : '-1' }}"
                                    aria-label="{{ $cell['label'] }}"
                                    @if ($cell['hidden']) hidden @endif
                                    @if ($cell['outside']) data-outside @endif
                                    @if ($cell['today']) data-today aria-current="date" @endif
                                    @if ($cell['selected']) data-selected @endif
                                    @if ($cell['between']) data-between @endif
                                    @if ($cell['disabled']) aria-disabled="true" @endif
                                    class="relative {{ $cellSize }} rounded-control tabular-nums text-fg-body motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter hover:bg-surface-subtle focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus-ring data-outside:text-fg-muted data-today:font-semibold data-today:not-data-selected:text-fg data-between:bg-accent-wash data-between:text-fg aria-disabled:cursor-not-allowed aria-disabled:text-fg-subtle aria-disabled:hover:bg-transparent data-selected:bg-surface-inverse data-selected:font-medium data-selected:text-on-inverse data-selected:hover:bg-surface-inverse"
                                >
                                    <span data-ds-cal-num>{{ $cell['day'] }}</span>
                                    {{-- A dot in the day's tone, or a count. --}}
                                    <span
                                        data-ds-cal-mark
                                        @if ($cell['tone']) data-tone="{{ $cell['tone'] }}" @endif
                                        @if (! $cell['tone'] && ! $cell['count']) hidden @endif
                                        class="pointer-events-none absolute inset-x-0 bottom-0.5 mx-auto w-max text-[0.625rem] leading-none font-medium text-fg-muted in-data-selected:text-on-inverse data-tone:bottom-1 data-tone:size-1 data-tone:rounded-full data-[tone=neutral]:bg-fg-muted data-[tone=accent]:bg-accent data-[tone=success]:bg-success data-[tone=warning]:bg-warning data-[tone=danger]:bg-danger in-data-selected:data-tone:ring-1 in-data-selected:data-tone:ring-on-inverse"
                                    >{{ $cell['count'] }}</span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
