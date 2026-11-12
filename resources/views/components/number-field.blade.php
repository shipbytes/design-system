@props([
    /** Submitted field name. */
    'name',
    'label' => null,
    /** Guidance under the field. Replaced by `error`, never stacked with it. */
    'help' => null,
    'error' => null,
    'value' => null,
    'min' => null,
    'max' => null,
    /** The size of one press of a button or an arrow key. "any" steps by whole units. */
    'step' => 1,
    'disabled' => false,
    /** md: the input's own size, 44px below sm and 36px above. sm: 32px everywhere, for a table row. */
    'size' => 'md',
])

@php
    // Stable under wire:model, random otherwise: see Support\ControlId.
    $id = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label, $name);
    $describedBy = $error ? "{$id}-error" : ($help ? "{$id}-help" : null);
    $field = \Shipbytes\BladeUi\Support\Field::recipe();

    $size = in_array($size, ['sm', 'md'], true) ? $size : 'md';

    $number = fn ($v) => $v === null || $v === '' || ! is_numeric($v) ? null : (float) $v;
    [$current, $low, $high] = [$number($value), $number($min), $number($max)];

    // Rendered disabled at a bound by PHP, so the first paint is right and a
    // page whose script never runs does not offer a press that does nothing.
    $atMin = $disabled || ($current !== null && $low !== null && $current <= $low);
    $atMax = $disabled || ($current !== null && $high !== null && $current >= $high);

    /*
     * The keyboard a phone raises. iOS's numeric and decimal pads have no minus
     * key, so a field that can go below zero keeps type=number's own keyboard,
     * which does. A whole-number step gets the digit pad, a fractional one the
     * pad with a decimal point.
     */
    $whole = $step === 'any' ? false : floor((float) $step) === (float) $step;
    $inputmode = match (true) {
        $low === null || $low < 0 => null,
        $whole => 'numeric',
        default => 'decimal',
    };

    // Each button is as wide as the field is tall, and the value stops short
    // of both.
    $buttonWidth = $size === 'sm' ? 'w-8' : 'w-11 sm:w-9';
    $reserve = $size === 'sm' ? 'pr-16' : 'pr-[5.5rem] sm:pr-[4.5rem]';

    $control = implode(' ', [
        $field['control']((bool) $error, false, (bool) $disabled, $size),
        $reserve,
        'tabular-nums',
        // The browser's own spin buttons, which the two below replace.
        '[-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none',
    ]);

    $shared = $attributes->except(['class'])->merge([
        'type' => 'number',
        'id' => $id,
        'name' => $name,
        'value' => $value,
        'min' => $min,
        'max' => $max,
        'step' => $step,
        'inputmode' => $inputmode,
        'class' => $control,
        'aria-describedby' => $describedBy,
        'aria-invalid' => $error ? 'true' : null,
    ])->filter(fn ($v) => $v !== null);

    $button = implode(' ', [
        'flex items-center justify-center border-l border-border text-fg-muted select-none touch-manipulation',
        'hover:bg-fg/5 hover:text-fg disabled:cursor-not-allowed disabled:text-fg-subtle disabled:hover:bg-transparent',
        'last:rounded-r-[calc(var(--ds-radius-control)-1px)]',
        'motion-safe:transition-colors motion-safe:duration-hint-enter motion-safe:ease-enter',
        $buttonWidth,
    ]);

    $named = fn (string $verb) => $label ? "{$verb} {$label}" : $verb;

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsNumberField');
@endphp

<div x-data="{{ $state }}" {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="{{ $field['label'] }}">{{ $label }}</label>
    @endif

    <span @class([$field['shell']((bool) $disabled), 'mt-1.5' => (bool) $label])>
        <input {{ $shared }} @disabled($disabled) data-ds-number-input />

        {{-- Out of the tab order: a keyboard already has the arrow keys, and two
             more stops per field would double the Tab presses through a form.
             A screen reader still finds them, by the names below. --}}
        <span class="absolute inset-y-px right-px flex">
            <button type="button" tabindex="-1" class="{{ $button }}" aria-label="{{ $named('Decrease') }}" aria-controls="{{ $id }}" data-ds-step="down" @disabled($atMin)>
                <x-ds::icon name="minus" variant="mini" size="4" />
            </button>
            <button type="button" tabindex="-1" class="{{ $button }}" aria-label="{{ $named('Increase') }}" aria-controls="{{ $id }}" data-ds-step="up" @disabled($atMax)>
                <x-ds::icon name="plus" variant="mini" size="4" />
            </button>
        </span>
    </span>

    @if ($error)
        <p id="{{ $id }}-error" class="{{ $field['error'] }}">
            <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @elseif ($help)
        <p id="{{ $id }}-help" class="{{ $field['help'] }}">{{ $help }}</p>
    @endif
</div>
