@props([
    /** Field name. Posts {name} (one day), {name}_start and {name}_end (range) or {name}[] (multiple). No name, nothing posts. */
    'name' => null,
    /** Names the calendar, shown above it. Without one, pass aria-label. */
    'label' => null,
    /** Y-m-d or a DateTimeInterface; [from, to] with `range`; a list with `multiple`. */
    'value' => null,
    /** Choose a start and an end. */
    'range' => false,
    /** Choose any number of days. */
    'multiple' => false,
    /** Earliest selectable day. */
    'min' => null,
    /** Latest selectable day. */
    'max' => null,
    /** Days that cannot be chosen: a list of dates. */
    'disabledDates' => [],
    /** Weekdays that cannot be chosen: 0 = Sunday … 6 = Saturday. */
    'disabledDays' => [],
    /** 1 to 3 months side by side; they stack when the container is narrow. */
    'months' => 1,
    /** The first visible month, Y-m. Defaults to the month of the value, or today's. */
    'month' => null,
    /** 0 = Sunday … 6 = Saturday. Defaults to the locale's. Named as on ds::date-picker. */
    'weekStartsOn' => null,
    /** Day and month names. Defaults to the app's locale. */
    'locale' => null,
    'showWeekNumbers' => false,
    'showOutsideDays' => false,
    /** The day marked as today, Y-m-d; now() in the app's timezone by default, never the browser's clock. false marks none. */
    'today' => null,
    /** Date => tone (neutral | accent | success | warning | danger) for a dot, or a count. */
    'events' => [],
    /** sm | md | lg: 32, 36 or 44px days. */
    'size' => 'md',
    /** Month and year selects in the header. */
    'jump' => true,
])

@php
    $calendar = \Shipbytes\BladeUi\Support\Calendar::class;
    $iso = fn ($date) => $calendar::iso($date);
    $locale = $locale ?? app()->getLocale();
    $mode = $range ? 'range' : ($multiple ? 'multiple' : 'single');
    $size = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';
    $count = max(1, min(3, (int) $months));
    $start = $calendar::weekStart($weekStartsOn, $locale);
    $names = $calendar::names($locale, $start) + ['start' => $start];
    $today = $today === false ? null : ($calendar::iso($today) ?? \Illuminate\Support\Carbon::now()->format('Y-m-d'));

    $chosen = array_values(array_filter(array_map($iso, match ($mode) {
        'single' => [$value],
        'range' => array_slice(array_pad((array) ($value ?? []), 2, null), 0, 2),
        default => is_iterable($value) ? [...$value] : [],
    })));
    if ($mode === 'multiple') {
        $chosen = array_values(array_unique($chosen));
        sort($chosen);
    }
    // A range's start and end post as given, but are drawn low to high.
    $selected = $mode === 'range' ? array_values(array_unique([min($chosen ?: [null]), max($chosen ?: [null])])) : $chosen;
    $selected = array_values(array_filter($selected));

    $cursor = is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month)
        ? "{$month}-01"
        : substr($selected[0] ?? $today ?? \Illuminate\Support\Carbon::now()->format('Y-m-d'), 0, 7).'-01';

    $words = [
        'previous' => 'Previous month',
        'next' => 'Next month',
        'month' => 'Month',
        'year' => 'Year',
        'week' => 'Week',
        'weekShort' => 'Wk',
        'event' => ':count event',
        'events' => ':count events',
        'marked' => 'marked',
        'date' => 'Date',
        'from' => 'From',
        'to' => 'To',
    ];

    $options = [
        'mode' => $mode,
        'name' => $name,
        'min' => $calendar::iso($min),
        'max' => $calendar::iso($max),
        'disabledDates' => array_values(array_filter(array_map($iso, (array) $disabledDates))),
        'disabledDays' => array_values(array_map('intval', (array) $disabledDays)),
        'weekStart' => $start,
        'today' => $today,
        'events' => (object) $calendar::events($events),
        'outside' => (bool) $showOutsideDays,
        'names' => ['long' => $names['long'], 'months' => $names['months']],
        'words' => $words,
    ];

    $grid = $calendar::grid([
        'cursor' => $cursor,
        'count' => $count,
        'weekStart' => $start,
        'names' => $names,
        'mode' => $mode,
        'value' => $selected,
        'min' => $options['min'],
        'max' => $options['max'],
        'disabledDates' => $options['disabledDates'],
        'disabledDays' => $options['disabledDays'],
        'today' => $today,
        'events' => (array) $options['events'],
        'outside' => (bool) $showOutsideDays,
        'words' => $words,
    ]);

    $cursorYear = (int) substr($cursor, 0, 4);
    $todayYear = (int) substr($today ?? $cursor, 0, 4);
    $years = range(
        min($cursorYear, $options['min'] ? (int) substr($options['min'], 0, 4) : $todayYear - 10),
        max($cursorYear, $options['max'] ? (int) substr($options['max'], 0, 4) : $todayYear + 10),
    );

    // Stable under wire:model, random otherwise: see Support\ControlId.
    $id = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label, $name ?? 'calendar');
    $isModel = fn ($v, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model');
    $model = $attributes->filter($isModel);
    $rest = $attributes->except(['class'])->filter(fn ($v, $key) => ! $isModel($v, $key));
    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsCalendar');
    $field = \Shipbytes\BladeUi\Support\Field::recipe();

    // What posts, as native date fields: the no-script calendar. dsCalendar
    // turns each into a hidden input on start and hides their row, so the
    // same inputs post either way. They also hold the value the script reads,
    // so they are there without a name too, unnamed and hidden.
    $posts = match ($mode) {
        'single' => [[$name, $chosen[0] ?? null, $words['date']]],
        'range' => [["{$name}_start", $selected[0] ?? null, $words['from']], ["{$name}_end", $selected[1] ?? null, $words['to']]],
        default => [...array_map(fn ($day) => ["{$name}[]", $day, $words['date']], $chosen), ["{$name}[]", null, $words['date']]],
    };
@endphp

<div
    x-data="{{ $state }}"
    x-modelable="dsValue"
    {{ $model }}
    {{ $rest->merge(['id' => $id]) }}
    role="group"
    @if ($label) aria-labelledby="{{ $id }}-label" @endif
    data-ds-calendar="{{ json_encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}"
    {{ $attributes->only('class')->merge(['class' => 'inline-block max-w-full']) }}
>
    @if ($label)
        <div id="{{ $id }}-label" class="mb-2 {{ $field['label'] }}">{{ $label }}</div>
    @endif

    @include('ds::partials.calendar-grid', ['weekNumbers' => (bool) $showWeekNumbers, 'phoneOne' => false])

    <div class="mt-3 flex flex-wrap gap-3" data-ds-cal-fallback @if ($name === null) hidden @endif>
            @foreach ($posts as $p => [$postName, $postValue, $postLabel])
                <label class="flex flex-col gap-1 text-meta font-medium text-fg-muted">
                    <span>{{ $postLabel }}</span>
                    <input
                        type="date"
                        @if ($name !== null) name="{{ $postName }}" @endif
                        value="{{ $postValue }}"
                        data-ds-cal-input
                        @if ($options['min']) min="{{ $options['min'] }}" @endif
                        @if ($options['max']) max="{{ $options['max'] }}" @endif
                        class="rounded-control border border-border bg-surface px-2 py-1 text-body text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
                    />
                </label>
            @endforeach
    </div>
</div>
