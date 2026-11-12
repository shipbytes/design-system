@props([
    /**
     * Submitted field name. With `range` the two thumbs post as
     * "{name}[low]" and "{name}[high]", which PHP reads as one array.
     */
    'name',
    'label' => null,
    /** Guidance under the slider. Replaced by `error`, never stacked with it. */
    'help' => null,
    'error' => null,
    'min' => 0,
    'max' => 100,
    /** One arrow-key press. "any" moves by the pixel. */
    'step' => 1,
    /** A number; with `range`, [low, high] or ['low' => …, 'high' => …]. */
    'value' => null,
    /** Two thumbs on one track: a lower and an upper bound. */
    'range' => false,
    /** With `range` and the script: the closest the two thumbs may come, in the slider's units. */
    'minGap' => 0,
    /** Required with `range`: each thumb's own accessible name, "Minimum price". */
    'lowLabel' => null,
    'highLabel' => null,
    /** With `range`: post the thumbs under these names instead of name[low] and name[high]. */
    'lowName' => null,
    'highName' => null,
    /** value => label, drawn as ticks under the track. A list of numbers labels each with its formatted value. */
    'marks' => [],
    /** none | inline (beside the label) | tooltip (over the thumb while it is dragged or focused from the keyboard). */
    'showValue' => 'none',
    /** prefix, suffix, decimals (and grouping, compact, locale): Support\Chart::format()'s keys. */
    'format' => [],
    'disabled' => false,
    /** md: the height of a field, 44px below sm and 36px above. sm: 32px, for a table row or a toolbar. */
    'size' => 'md',
])

@php
    use Shipbytes\BladeUi\Support\Chart;

    // Stable under wire:model, random otherwise: see Support\ControlId.
    $id = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label, $name);
    $describedBy = $error ? "{$id}-error" : ($help ? "{$id}-help" : null);
    $field = \Shipbytes\BladeUi\Support\Field::recipe();

    $size = in_array($size, ['sm', 'md'], true) ? $size : 'md';
    $showValue = in_array($showValue, ['none', 'inline', 'tooltip'], true) ? $showValue : 'none';
    $range = (bool) $range;

    if ($range && (blank($lowLabel) || blank($highLabel))) {
        throw new \InvalidArgumentException(
            'ds::slider with `range` needs `lowLabel` and `highLabel`: each thumb is a control of its own, and "slider, slider" names neither.'
        );
    }

    $low = is_numeric($min) ? (float) $min : 0.0;
    $high = is_numeric($max) ? (float) $max : 100.0;

    if ($high <= $low) {
        throw new \InvalidArgumentException("ds::slider needs `max` above `min`; got min {$low}, max {$high}.");
    }

    $any = $step === 'any';
    $increment = ! $any && is_numeric($step) && (float) $step > 0 ? (float) $step : 1.0;

    // The value the browser will show: inside the bounds and on a step
    // counted from `min`, as a native range input sanitises it. Drawn from
    // the same number, so the fill and the thumb agree before any script.
    $snap = function ($v, float $fallback) use ($low, $high, $any, $increment): float {
        $v = is_numeric($v) ? (float) $v : $fallback;
        if (! $any) {
            $v = $low + round(($v - $low) / $increment) * $increment;
        }

        return round(max($low, min($high, $v)), 10);
    };

    // A number as an attribute: no exponent, no trailing zeros.
    $plain = fn (float $v): string => rtrim(rtrim(number_format($v, 10, '.', ''), '0'), '.') ?: '0';
    $ratio = fn (float $v): string => $plain(($v - $low) / ($high - $low));

    $text = fn (float $v): string => Chart::format($v, (array) $format);

    if ($range) {
        $given = is_array($value) ? $value : [];
        $pair = array_key_exists('low', $given) || array_key_exists('high', $given)
            ? [$given['low'] ?? null, $given['high'] ?? null]
            : array_values($given) + [null, null];
        $values = [$snap($pair[0], $low), $snap($pair[1], $high)];
        sort($values);
    } else {
        // A native range input with no value sits halfway.
        $values = [$low, $snap($value, $low + ($high - $low) / 2)];
    }

    $thumbs = $range
        ? [
            'low' => ['id' => "{$id}-low", 'name' => $lowName ?? "{$name}[low]", 'value' => $values[0], 'label' => $lowLabel],
            'high' => ['id' => "{$id}-high", 'name' => $highName ?? "{$name}[high]", 'value' => $values[1], 'label' => $highLabel],
        ]
        : ['high' => ['id' => $id, 'name' => $name, 'value' => $values[1], 'label' => null]];

    /*
     * Ticks: value => label, or a list of numbers, each labelled with its
     * formatted value. Outside the bounds a tick has nowhere to go and is
     * dropped. Placed by an inline inset, not a class, because the position
     * is data and a class built from it would never reach the scanner.
     */
    $ticks = collect((array) $marks)
        ->map(fn ($tickLabel, $tickValue) => array_is_list((array) $marks) && is_numeric($tickLabel)
            ? ['value' => (float) $tickLabel, 'label' => $text((float) $tickLabel)]
            : ['value' => is_numeric($tickValue) ? (float) $tickValue : null, 'label' => (string) $tickLabel])
        ->filter(fn ($tick) => $tick['value'] !== null && $tick['value'] >= $low && $tick['value'] <= $high)
        ->map(fn ($tick) => $tick + ['at' => $ratio($tick['value'])])
        ->values();

    $isModel = fn ($v, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model');
    $model = $attributes->filter($isModel);
    $rest = $attributes->except(['class', 'id'])->filter(fn ($v, $key) => ! $isModel($v, $key));

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsSlider', [
        'minGap' => is_numeric($minGap) ? max(0, (float) $minGap) : 0,
        'format' => Chart::formatOptions((array) $format),
    ]);

    // The thumb is 24px whatever its size, so it is always a 24px target;
    // the inset is how much of that is transparent around the visible disc.
    $root = $size === 'sm' ? '[--ds-slider-inset:4px]' : '[--ds-slider-inset:2px]';

    $body = implode(' ', [
        'relative',
        $size === 'sm' ? 'h-8' : 'h-11 sm:h-9',
        $label || $showValue === 'inline' ? 'mt-1.5' : '',
    ]);

    // Inset by half a thumb each side, which is where a native thumb's centre
    // starts and stops: the fill's ends land under the thumbs' centres.
    $track = implode(' ', [
        'pointer-events-none absolute inset-x-3 top-1/2 -translate-y-1/2 rounded-full bg-fg/10',
        $size === 'sm' ? 'h-1' : 'h-1.5',
    ]);

    $fill = implode(' ', [
        'absolute inset-y-0 start-[calc(var(--ds-slider-low)*100%)] end-[calc((1-var(--ds-slider-high))*100%)] rounded-full',
        match (true) {
            (bool) $disabled => 'bg-fg-subtle',
            (bool) $error => 'bg-danger',
            default => 'bg-surface-inverse',
        },
    ]);

    $input = implode(' ', [
        'absolute inset-x-0 top-1/2 h-6 w-full -translate-y-1/2 cursor-pointer disabled:cursor-not-allowed',
        // The low thumb comes to the top once it is past halfway, so two
        // thumbs parked together at the end can still be pulled apart.
        $range ? 'data-ds-slider-top:z-10' : '',
    ]);

    $tip = 'pointer-events-none absolute bottom-full z-20 mb-1 w-max -translate-x-1/2 rtl:translate-x-1/2 rounded-control bg-surface-inverse px-2 py-1 text-meta text-on-inverse tabular-nums shadow-float';
@endphp

<div
    x-data="{{ $state }}"
    x-modelable="dsValue"
    {{ $model }}
    {{ $rest }}
    data-ds-slider="{{ $range ? 'range' : 'single' }}"
    @if ($range) role="group" @if ($label) aria-labelledby="{{ $id }}-label" @endif @endif
    {{ $attributes->only('class')->merge(['class' => $root]) }}
>
    @if ($label || $showValue === 'inline')
        <div class="flex items-baseline justify-between gap-3">
            @if ($label)
                {{-- With two thumbs this names the group, and a press on it
                     takes focus to the first thumb. --}}
                <label id="{{ $id }}-label" for="{{ $range ? "{$id}-low" : $id }}" class="{{ $field['label'] }}">{{ $label }}</label>
            @endif
            @if ($showValue === 'inline')
                {{-- Not a live region: each thumb already says its value as it
                     moves, and a second voice would talk over it. --}}
                <span class="ms-auto shrink-0 text-body tabular-nums text-fg-muted" data-ds-slider-output>
                    @if ($range)<span data-ds-slider-output-low>{{ $text($values[0]) }}</span> – @endif<span data-ds-slider-output-high>{{ $text($values[1]) }}</span>
                </span>
            @endif
        </div>
    @endif

    <div
        class="{{ $body }}"
        data-ds-slider-body
        style="--ds-slider-low: {{ $range ? $ratio($values[0]) : '0' }}; --ds-slider-high: {{ $ratio($values[1]) }}"
    >
        <div class="{{ $track }}" aria-hidden="true" data-ds-slider-track>
            <div class="{{ $fill }}" data-ds-slider-fill></div>
            @foreach ($ticks as $tick)
                <span class="absolute top-1/2 size-1 -translate-x-1/2 -translate-y-1/2 rtl:translate-x-1/2 rounded-full bg-fg-muted" style="inset-inline-start: calc({{ $tick['at'] }} * 100%)"></span>
            @endforeach
        </div>

        @foreach ($thumbs as $key => $thumb)
            @php
                $inputAttributes = new \Illuminate\View\ComponentAttributeBag(array_filter([
                    'type' => 'range',
                    'id' => $thumb['id'],
                    // Printed as given by the bag, so escaped here.
                    'name' => e($thumb['name']),
                    'min' => $plain($low),
                    'max' => $plain($high),
                    'step' => $any ? 'any' : $plain($increment),
                    'value' => $plain($thumb['value']),
                    'aria-label' => $thumb['label'] !== null ? e($thumb['label']) : null,
                    'aria-valuetext' => e($text($thumb['value'])),
                    'aria-describedby' => $describedBy,
                    'aria-invalid' => $error ? 'true' : null,
                    'data-ds-slider-top' => $key === 'low' && (float) $ratio($thumb['value']) > 0.5 ? true : null,
                    'disabled' => $disabled ? true : null,
                    'class' => $input,
                ], fn ($v) => $v !== null && $v !== false));
            @endphp
            <input {{ $inputAttributes }} data-ds-slider-input="{{ $key }}" />
            @if ($showValue === 'tooltip')
                {{-- Shown by the script while its thumb is dragged or has
                     keyboard focus; hidden without it, rather than left
                     showing a number the thumb has moved away from. --}}
                <span hidden aria-hidden="true" data-ds-slider-tip="{{ $key }}" class="{{ $tip }}" style="inset-inline-start: calc(0.75rem + (100% - 1.5rem) * var(--ds-slider-{{ $key }}))">{{ $text($thumb['value']) }}</span>
            @endif
        @endforeach
    </div>

    @if ($ticks->isNotEmpty())
        {{-- The labels are for the eye: each thumb's own aria-valuetext says
             where it is. --}}
        <div class="relative mx-3 h-5 text-meta text-fg-muted" aria-hidden="true">
            @foreach ($ticks as $tick)
                <span @class([
                    'absolute top-0 w-max',
                    '-translate-x-1/2 rtl:translate-x-1/2' => $tick['at'] !== '0' && $tick['at'] !== '1',
                    '-translate-x-2 rtl:translate-x-2' => $tick['at'] === '0',
                    '-translate-x-[calc(100%-0.5rem)] rtl:translate-x-[calc(100%-0.5rem)]' => $tick['at'] === '1',
                ]) style="inset-inline-start: calc({{ $tick['at'] }} * 100%)">{{ $tick['label'] }}</span>
            @endforeach
        </div>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" class="{{ $field['error'] }}">
            <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @elseif ($help)
        <p id="{{ $id }}-help" class="{{ $field['help'] }}">{{ $help }}</p>
    @endif
</div>
