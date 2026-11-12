@props([
    /** Submitted field name. Always posts HH:MM, 24-hour, or nothing. */
    'name',
    'label' => null,
    /** Guidance under the field. Replaced by `error`, never stacked with it. */
    'help' => null,
    'error' => null,
    /** HH:MM (24-hour; seconds are dropped), a DateTimeInterface, or null. */
    'value' => null,
    /** The earliest time that may be chosen, HH:MM. */
    'min' => null,
    /** The latest, HH:MM. Later than `min`: a range across midnight is not supported. */
    'max' => null,
    /** Minutes between the times in the list, and the steps a time must fall on, counted from `min`. */
    'step' => 15,
    /** 12 | 24: how a time is SHOWN. What posts is always 24-hour. */
    'format' => '24',
    /** Shown in the empty field once the script has taken it over. */
    'placeholder' => null,
    /** A ✕ in the field that empties it. Needs the package's script, as the rest does. */
    'clearable' => false,
    'disabled' => false,
    /** md: the input's own size. sm: 32px, for a table row. */
    'size' => 'md',
])

@php
    // Stable under wire:model, random otherwise: see Support\ControlId.
    $id = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label, $name);
    $describedBy = $error ? "{$id}-error" : ($help ? "{$id}-help" : null);
    $field = \Shipbytes\BladeUi\Support\Field::recipe();
    $listbox = \Shipbytes\BladeUi\Support\Listbox::recipe();

    $size = in_array($size, ['sm', 'md'], true) ? $size : 'md';
    $twelve = (string) $format === '12';

    /** HH:MM from what a host is likely to hold: "9:30", "09:30:00", a Carbon. Anything else is null. */
    $hhmm = function ($time): ?string {
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i');
        }
        if (! is_string($time) || ! preg_match('/^\s*(\d{1,2}):(\d{2})(?::\d{2}(?:\.\d+)?)?\s*$/', $time, $m)) {
            return null;
        }

        return (int) $m[1] < 24 && (int) $m[2] < 60 ? sprintf('%02d:%02d', $m[1], $m[2]) : null;
    };
    $minutes = fn (string $t): int => (int) substr($t, 0, 2) * 60 + (int) substr($t, 3, 2);
    $clock = fn (int $m): string => sprintf('%02d:%02d', intdiv($m, 60), $m % 60);

    // The words a 12-hour time ends in. English, as every string the package
    // speaks; the field still reads "21:30" typed in, whatever is shown.
    $meridiem = ['AM', 'PM'];

    // How a reader sees a time: "09:30", or "9:30 AM". The script writes
    // the same, so what PHP lists and what typing settles on agree.
    $shown = function (string $t) use ($twelve, $meridiem): string {
        if (! $twelve) {
            return $t;
        }
        [$h, $m] = [(int) substr($t, 0, 2), substr($t, 3, 2)];

        return (($h % 12) ?: 12).':'.$m.' '.$meridiem[$h >= 12 ? 1 : 0];
    };

    $current = $hhmm($value);
    $earliest = $hhmm($min);
    $latest = $hhmm($max);
    $every = is_numeric($step) && (int) $step > 0 ? (int) $step : 15;

    /*
     * The list, from PHP: every time from `min` (midnight without one) to
     * `max` (23:59) at `step`. A step under five minutes would list hundreds,
     * so the list then counts in the smallest multiple of it that is at
     * least five; typing still takes every allowed minute.
     */
    $listStep = $every >= 5 ? $every : (int) ceil(5 / $every) * $every;
    $from = $earliest !== null ? $minutes($earliest) : 0;
    $until = $latest !== null ? $minutes($latest) : 24 * 60 - 1;
    $times = [];
    for ($m = $from; $m <= $until; $m += $listStep) {
        $times[] = $clock($m);
    }

    $words = [
        'invalid' => $twelve ? 'Enter a time, like 9:30 AM.' : 'Enter a time, like 09:30.',
        'range' => match (true) {
            $earliest !== null && $latest !== null => 'Choose a time from '.$shown($earliest).' to '.$shown($latest).'.',
            $earliest !== null => 'Choose a time from '.$shown($earliest).' on.',
            $latest !== null => 'Choose a time up to '.$shown($latest).'.',
            default => '',
        },
        'step' => "Choose a time on a {$every}-minute step".($earliest !== null && $from % 60 !== 0 ? ' from '.$shown($earliest) : '').'.',
    ];

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsTimePicker', [
        'twelve' => $twelve,
        'meridiem' => $meridiem,
        'min' => $earliest,
        'max' => $latest,
        'step' => $every,
        'words' => $words,
    ]);

    $isModel = fn ($v, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model');
    $model = $attributes->filter($isModel);
    $rest = $attributes->except(['class', 'id'])->filter(fn ($v, $key) => ! $isModel($v, $key));

    $clearable = $clearable && ! $disabled;

    $control = implode(' ', [
        $field['control']((bool) $error, false, (bool) $disabled, $size),
        // Room for the clock, and for the ✕ beside it.
        $clearable ? 'peer pe-[4.5rem] sm:pe-16' : 'pe-11 sm:pe-9',
        'tabular-nums',
    ]);

    /*
     * Rendered as a native time field, which works in every browser with no
     * script at all: its own picker, its own validation of min, max and step,
     * and it posts HH:MM under `name`. Once dsTimePicker starts it becomes a
     * text field with a list, and `name` moves to the hidden input below,
     * which carries HH:MM however the time is shown.
     *
     * Each attribute the change touches is also bound to the scope, because a
     * Livewire morph rewrites attributes to the server's and would turn the
     * field back into a native one under the reader's text. `$data.` reads a
     * name without throwing when the scope is not there, so in a host with no
     * script every binding below leaves the native field as PHP drew it.
     */
    $nameJs = \Illuminate\Support\Js::from((string) $name);
    $listId = "{$id}-listbox";

    $inputAttributes = new \Illuminate\View\ComponentAttributeBag(array_filter([
        'type' => 'time',
        'id' => $id,
        'name' => e($name),
        'value' => $current,
        'min' => $earliest,
        'max' => $latest,
        // A native time field counts its step in seconds.
        'step' => $every * 60,
        'class' => $control,
        'aria-describedby' => $describedBy,
        'aria-invalid' => $error ? 'true' : null,
        'disabled' => $disabled ? true : null,
        // Values here are printed as given: see CLAUDE.md, "An attribute bag
        // built in PHP does not escape its values". Js::from escapes quotes.
        'x-bind:type' => "\$data.dsReady ? 'text' : 'time'",
        'x-bind:name' => "\$data.dsReady ? null : {$nameJs}",
        'x-bind:role' => "\$data.dsReady ? 'combobox' : null",
        'x-bind:aria-expanded' => "\$data.dsReady ? String(Boolean(\$data.dsOpen)) : null",
        'x-bind:aria-controls' => "\$data.dsReady ? '{$listId}' : null",
        'x-bind:aria-autocomplete' => "\$data.dsReady ? 'none' : null",
        'x-bind:autocomplete' => "\$data.dsReady ? 'off' : null",
        'x-bind:placeholder' => $placeholder !== null || $clearable
            // :placeholder-shown needs a placeholder to exist, as on ds::input.
            ? "\$data.dsReady ? ".\Illuminate\Support\Js::from($placeholder ?? "\u{00A0}")." : null"
            : null,
    ], fn ($v) => $v !== null && $v !== false));

    $hiddenAttributes = new \Illuminate\View\ComponentAttributeBag(array_filter([
        'type' => 'hidden',
        'value' => $current ?? '',
        'disabled' => $disabled ? true : null,
        'x-bind:name' => "\$data.dsReady ? {$nameJs} : null",
    ], fn ($v) => $v !== null && $v !== false));
@endphp

<div
    x-data="{{ $state }}"
    x-modelable="dsValue"
    {{ $model }}
    {{ $rest }}
    data-ds-time
    {{ $attributes->only('class')->merge(['class' => 'relative block w-full']) }}
>
    @if ($label)
        <label id="{{ $id }}-label" for="{{ $id }}" class="{{ $field['label'] }}">{{ $label }}</label>
    @endif

    <input {{ $hiddenAttributes }} data-ds-time-value />

    <span @class([$field['shell']((bool) $disabled), 'mt-1.5' => (bool) $label])>
        <input {{ $inputAttributes }} data-ds-time-input />

        {{-- The browser draws its own clock on a native time field, so this
             one shows only once the field is a text field. --}}
        <span hidden x-bind:hidden="! $data.dsReady" class="pointer-events-none absolute inset-y-0 end-3 flex items-center text-fg-muted sm:end-2.5" aria-hidden="true">
            <x-ds::icon name="clock" variant="mini" size="5" class="sm:size-4" />
        </span>

        @if ($clearable)
            {{-- dsClear, as on ds::input: it empties the field and fires
                 `change`, which the time picker takes as the reader's
                 answer. Shown while the field holds something. --}}
            <button
                type="button"
                hidden
                x-bind:hidden="! $data.dsReady"
                class="absolute inset-y-0 end-9 z-10 flex w-9 items-center justify-center rounded-control text-fg-muted peer-placeholder-shown:hidden hover:text-fg focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus-ring sm:end-7 sm:w-8"
                x-data="{{ \Shipbytes\BladeUi\Support\Behaviour::data('dsClear') }}"
                aria-controls="{{ $id }}"
                aria-label="{{ $label ? "Clear {$label}" : 'Clear' }}"
            >
                <x-ds::icon name="x-mark" variant="mini" size="4" />
            </button>
        @endif
    </span>

    <ul
        x-show="$data.dsOpen"
        x-cloak
        x-ds-anchor="{ open: Boolean($data.dsOpen), reference: $el.previousElementSibling, placement: 'bottom-start', offset: 4, width: 'reference' }"
        id="{{ $listId }}"
        @keydown.arrow-down.prevent="{{ $listbox['move'] }}"
        @keydown.arrow-up.prevent="{{ $listbox['move'] }}"
        @keydown.home.prevent="{{ $listbox['move'] }}"
        @keydown.end.prevent="{{ $listbox['move'] }}"
        x-transition:enter="motion-safe:transition motion-safe:duration-popover-enter motion-safe:ease-enter"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        role="listbox"
        @if ($label) aria-labelledby="{{ $id }}-label" @endif
        class="{{ $listbox['panel'] }}"
        data-ds-time-list
    >
        @foreach ($times as $time)
            <li
                role="option"
                id="{{ $id }}-{{ str_replace(':', '', $time) }}"
                tabindex="-1"
                data-value="{{ $time }}"
                aria-selected="{{ $time === $current ? 'true' : 'false' }}"
                class="group tabular-nums {{ $listbox['option'] }}"
            >
                <span>{{ $shown($time) }}</span>
                <x-ds::icon name="check" variant="mini" size="4" class="hidden shrink-0 text-accent group-aria-selected:block" />
            </li>
        @endforeach
    </ul>

    @if ($error)
        <p id="{{ $id }}-error" class="{{ $field['error'] }}">
            <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @elseif ($help)
        <p id="{{ $id }}-help" class="{{ $field['help'] }}">{{ $help }}</p>
    @endif
</div>
