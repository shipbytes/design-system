@props([
    /**
     * Submitted field name. With `range`, this posts "{name}_start" and
     * "{name}_end" — two dates are two fields, and squeezing them into one
     * string means every consumer writing the same parser.
     */
    'name',
    'label' => null,
    /** Y-m-d, a DateTimeInterface, or null. With `range`, an array of two. */
    'value' => null,
    /** Pick a start and an end rather than one day. */
    'range' => false,
    /** Earliest selectable date. Y-m-d or a DateTimeInterface. */
    'min' => null,
    /** Latest selectable date. */
    'max' => null,
    'placeholder' => null,
    'help' => null,
    'error' => null,
    'disabled' => false,
    /** 0 = Sunday, 1 = Monday. Which day a week starts on is regional, not universal. */
    'weekStartsOn' => 1,
    /** 1 or 2 months side by side in the popover; one on a phone. */
    'months' => 1,
    /** With `range`: true for the built-in ranges, or label => [from, to] worked out by the host. */
    'presets' => false,
    /** With `range`: the fewest days a range may span, counted inclusively. */
    'minDays' => null,
    /** With `range`: the most. */
    'maxDays' => null,
    /** Apply and Cancel: picks are a draft until applied, and nothing closes the popover by itself. */
    'confirm' => false,
    /** The day marked as today and counted from by the presets, Y-m-d; now() in the app's timezone by default, never the browser's clock. */
    'today' => null,
])

@php
    /*
     * The calendar in the popover is partials/calendar-grid.blade.php, drawn
     * by Support\Calendar and redrawn by dsCalendar in the package's script:
     * the grid ds::calendar draws too. Without the script, native date fields
     * post the same names (see "Without the script" in specs/date-picker.md).
     */
    // Stable under wire:model, random otherwise: see Support\ControlId.
    $id = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label, $name);
    $describedBy = $error ? "{$id}-error" : ($help ? "{$id}-help" : null);

    /** Everything crossing into JS is Y-m-d: one format, no parsing. */
    $iso = fn ($date) => \Shipbytes\BladeUi\Support\Calendar::iso($date);

    $values = $range
        ? array_pad(array_map($iso, array_slice((array) ($value ?? []), 0, 2)), 2, null)
        : [$iso($value), null];

    $weekStartsOn = (int) $weekStartsOn === 0 ? 0 : 1;

    $field = implode(' ', [
        'flex w-full items-center justify-between gap-2 rounded-control border bg-surface',
        'px-[calc(--spacing(3.5)-1px)] py-[calc(--spacing(2.5)-1px)] text-left',
        'sm:px-[calc(--spacing(3)-1px)] sm:py-[calc(--spacing(1.5)-1px)]',
        'text-body-touch sm:text-body shadow-raised motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
        $error ? 'border-danger' : 'border-border',
        $disabled ? 'cursor-not-allowed bg-surface-subtle text-fg-subtle' : 'cursor-pointer hover:border-fg/20',
    ]);

    $startName = $range ? "{$name}_start" : $name;
    $calendar = \Shipbytes\BladeUi\Support\Calendar::class;
    $count = max(1, min(2, (int) $months));
    $todayIso = $calendar::iso($today) ?? \Illuminate\Support\Carbon::now()->format('Y-m-d');
    $names = $calendar::names(app()->getLocale(), $weekStartsOn) + ['start' => $weekStartsOn];
    $selected = array_values(array_filter($values));
    sort($selected);
    $presetList = $range && $presets ? $calendar::presetList($presets, $todayIso) : [];
    $mode = $range ? 'range' : 'single';

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
    ];

    $options = [
        'mode' => $mode,
        'name' => $name,
        'min' => $iso($min),
        'max' => $iso($max),
        'disabledDates' => [],
        'disabledDays' => [],
        'weekStart' => $weekStartsOn,
        'today' => $todayIso,
        'events' => (object) [],
        'outside' => false,
        'names' => ['long' => $names['long'], 'months' => $names['months']],
        'words' => $words,
        'minDays' => $range && is_numeric($minDays) ? max(1, (int) $minDays) : null,
        'maxDays' => $range && is_numeric($maxDays) ? max(1, (int) $maxDays) : null,
        'confirm' => (bool) $confirm,
        'popover' => true,
    ];

    $grid = $calendar::grid([
        'cursor' => substr($selected[0] ?? $todayIso, 0, 7).'-01',
        'count' => $count,
        'weekStart' => $weekStartsOn,
        'names' => $names,
        'mode' => $mode,
        'value' => $selected,
        'min' => $options['min'],
        'max' => $options['max'],
        'disabledDates' => [],
        'disabledDays' => [],
        'today' => $todayIso,
        'events' => [],
        'outside' => false,
        'words' => $words,
    ]);

    $model = $attributes->filter(fn ($v, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model'));
    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsCalendar');
    $placeholderText = $placeholder ?? ($range ? 'Choose a period' : 'Choose a date');
    $quiet = 'rounded-control px-2 py-1 text-meta font-medium motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring';
    $back = "dsOpen = false; \$el.closest('[x-data]').querySelector('[data-ds-date-trigger]').focus()";
@endphp

<div
    x-data="{{ $state }}"
    x-modelable="dsValue"
    {{ $model }}
    data-ds-calendar="{{ json_encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}"
    {{ $attributes->only('class')->merge(['class' => 'relative block w-full']) }}
>
    @if ($label)
        <label id="{{ $id }}-label" for="{{ $id }}" class="mb-1.5 block text-body font-medium text-fg">{{ $label }}</label>
    @endif

    {{-- Without the script these native date fields are the control and post
         the value; dsCalendar turns them into hidden inputs under the same
         names, hides this row and shows the trigger. readonly, not disabled,
         when the picker is: a disabled field would stop posting its value. --}}
    <div data-ds-cal-fallback class="flex items-center gap-2">
        @foreach ($range ? [[$startName, $values[0], 'start'], ["{$name}_end", $values[1], 'end']] : [[$startName, $values[0], null]] as [$postName, $postValue, $end])
            @if ($end === 'end')
                <span aria-hidden="true" class="text-fg-muted">&ndash;</span>
            @endif
            <input
                type="date"
                name="{{ $postName }}"
                value="{{ $postValue }}"
                data-ds-cal-input
                @if ($options['min']) min="{{ $options['min'] }}" @endif
                @if ($options['max']) max="{{ $options['max'] }}" @endif
                @if ($label) aria-labelledby="{{ $id }}-label{{ $end ? " {$id}-{$end}" : '' }}" @endif
                @readonly($disabled)
                @if ($error) aria-invalid="true" @endif
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                class="{{ $field }} min-w-0"
            />
            @if ($end)
                <span id="{{ $id }}-{{ $end }}" hidden>{{ $end === 'start' ? 'Start' : 'End' }}</span>
            @endif
        @endforeach
    </div>

    <button
        type="button"
        id="{{ $id }}"
        hidden
        x-bind:hidden="! $data.dsReady"
        data-ds-date-trigger
        class="{{ $field }}"
        @click="dsOpen = ! dsOpen"
        @keydown.escape.stop="dsOpen = false"
        @keydown.arrow-down.prevent="dsOpen = true"
        @disabled($disabled)
        aria-haspopup="dialog"
        :aria-expanded="dsOpen"
        @if ($label) aria-labelledby="{{ $id }}-label {{ $id }}" @endif
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
    >
        <span class="truncate" :class="dsSummary ? 'text-fg' : 'text-fg-muted'">
            <span x-text="dsSummary || @js($placeholderText)">{{ $placeholderText }}</span>
        </span>
        <x-ds::icon name="calendar" variant="mini" size="5" class="shrink-0 text-fg-muted sm:size-4" />
    </button>

    <div
        x-show="dsOpen"
        x-cloak
        x-ds-anchor="{ open: dsOpen, reference: $el.previousElementSibling, placement: 'bottom-start', offset: 4 }"
        @click.outside="dsOpen = false"
        @keydown.escape.stop="{{ $back }}"
        x-transition:enter="motion-safe:transition motion-safe:duration-popover-enter motion-safe:ease-enter"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        role="dialog"
        aria-modal="false"
        @if ($label) aria-labelledby="{{ $id }}-label" @endif
        class="absolute z-50 mt-1 w-max origin-top data-[side=top]:origin-bottom rounded-control border border-border bg-surface p-3 shadow-float"
    >
        <div class="flex flex-col gap-3 sm:flex-row">
            @if ($presetList)
                {{-- Above the grid on a phone, wrapping to its width; beside it from sm. --}}
                <div role="group" aria-label="Presets" class="flex flex-wrap gap-1 max-sm:max-w-66 sm:w-32 sm:flex-col sm:border-e sm:border-divider sm:pe-3">
                    @foreach ($presetList as $preset)
                        <button
                            type="button"
                            @if ($preset['key']) data-ds-cal-preset="{{ $preset['key'] }}" @else data-ds-cal-preset="" data-from="{{ $preset['from'] }}" data-to="{{ $preset['to'] }}" @endif
                            aria-pressed="{{ $range && count($selected) === 2 && $selected === [$preset['from'], $preset['to']] ? 'true' : 'false' }}"
                            class="{{ $quiet }} text-start text-fg-muted hover:bg-surface-subtle hover:text-fg aria-pressed:bg-surface-subtle aria-pressed:text-fg"
                        >{{ $preset['label'] }}</button>
                    @endforeach
                </div>
            @endif

            <div>
                @include('ds::partials.calendar-grid', ['size' => 'md', 'weekNumbers' => false, 'jump' => false, 'years' => [], 'phoneOne' => true])
            </div>
        </div>

        <div class="mt-2 flex items-center justify-between gap-2 border-t border-divider pt-2">
            <button type="button" data-ds-cal-clear class="{{ $quiet }} text-fg-muted hover:text-fg">Clear</button>

            @if ($confirm)
                <span class="flex items-center gap-1">
                    <button type="button" data-ds-cal-cancel class="{{ $quiet }} text-fg-muted hover:text-fg">Cancel</button>
                    <button type="button" data-ds-cal-apply class="{{ $quiet }} bg-surface-inverse text-on-inverse hover:bg-surface-inverse-hover">Apply</button>
                </span>
            @else
                <button type="button" @click="{{ $back }}" class="{{ $quiet }} text-fg hover:bg-surface-subtle">Done</button>
            @endif
        </div>
    </div>

    @if ($error)
        <p id="{{ $id }}-error" class="mt-1.5 flex items-start gap-1.5 text-meta text-danger">
            <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @elseif ($help)
        <p id="{{ $id }}-help" class="mt-1.5 text-meta text-fg-muted">{{ $help }}</p>
    @endif
</div>
