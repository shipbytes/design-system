@props([
    /** Submitted field name. The code posts as this ONE field; the boxes carry no name. */
    'name',
    /** How many characters, one box each. */
    'length' => 6,
    'label' => null,
    /** Guidance under the boxes. Replaced by `error`, never stacked with it. */
    'help' => null,
    /** Validation message. Styles every box and sets aria-invalid on each. */
    'error' => null,
    /** PIN mode: the boxes show dots rather than the characters. */
    'masked' => false,
    /** Digits only. False takes letters as well. */
    'numeric' => true,
    /** Focus the first empty box when the page loads. */
    'autofocus' => false,
    'disabled' => false,
    /** The code so far, rendered into the boxes before Alpine boots. */
    'value' => null,
])

@php
    // Stable under wire:model, random otherwise: see Support\ControlId.
    $id = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label, $name);
    $describedBy = $error ? "{$id}-error" : ($help ? "{$id}-help" : null);
    $field = \Shipbytes\BladeUi\Support\Field::recipe();

    $length = max(1, (int) $length);
    $chars = $value === null || $value === '' ? [] : mb_str_split(mb_substr((string) $value, 0, $length));
    $joined = implode('', $chars);

    // Autofocus lands where typing would continue: the first empty box, or the
    // last one when the code is already whole.
    $resume = min(count($chars), $length - 1);

    // "Digit 2 of 6" reads as a number to type; a code that takes letters is
    // characters, and calling them digits would send someone looking for a
    // number that is not there.
    $noun = $numeric ? 'Digit' : 'Character';

    $box = implode(' ', [
        $field['control']((bool) $error, false, (bool) $disabled, 'box'),
        'text-center font-medium tabular-nums',
    ]);

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsOtp', ['numeric' => (bool) $numeric]);

    /*
     * wire:model and x-model go on the root, beside x-modelable, which ties
     * them to the behaviour's dsValue in both directions: a code the host sets
     * is written into the boxes. On the hidden input they could only follow
     * the code; nothing a host set there reached the boxes.
     */
    $isModel = fn ($value, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model');
    $model = $attributes->filter($isModel);

    // Everything else the host passes, bar the class, goes to the hidden input
    // that posts. `complete` is dispatched from there, so a listener on the
    // component tag hears it.
    $posted = $attributes->except(['class', 'id'])->filter(fn ($value, $key) => ! $isModel($value, $key))->merge([
        'id' => $id,
        'name' => $name,
        'value' => $joined,
    ]);

    $group = new \Illuminate\View\ComponentAttributeBag(array_filter([
        'aria-labelledby' => $label ? "{$id}-label" : null,
    ]));
@endphp

<div x-data="{{ $state }}" x-modelable="dsValue" {{ $model }} {{ $attributes->only('class') }}>
    @if ($label)
        {{-- A label for the FIRST box, so pressing it starts the code; the group
             is named by the same element. --}}
        <label id="{{ $id }}-label" for="{{ $id }}-1" class="{{ $field['label'] }}">{{ $label }}</label>
    @endif

    <input type="hidden" {{ $posted }} data-ds-otp-value @disabled($disabled) />

    {{-- One row that narrows rather than wraps: a code broken over two lines
         reads as two codes. --}}
    <div role="group" {{ $group }} @class(['flex gap-2', 'mt-1.5' => (bool) $label])>
        @for ($i = 0; $i < $length; $i++)
            @php
                $boxAttributes = new \Illuminate\View\ComponentAttributeBag(array_filter([
                    'type' => $masked ? 'password' : 'text',
                    // One character wide, so the box's own minimum width does
                    // not stop the row narrowing on a phone.
                    'size' => 1,
                    'id' => "{$id}-".($i + 1),
                    'value' => $chars[$i] ?? '',
                    // Only the first box asks the browser for the code it was
                    // sent: offered in every box, the autofill chip would sit on
                    // whichever one is focused and fill from there.
                    'autocomplete' => $i === 0 ? 'one-time-code' : 'off',
                    'inputmode' => $numeric ? 'numeric' : null,
                    'autocapitalize' => 'off',
                    'autocorrect' => 'off',
                    'spellcheck' => 'false',
                    'aria-label' => "{$noun} ".($i + 1)." of {$length}",
                    'aria-invalid' => $error ? 'true' : null,
                    'aria-describedby' => $describedBy,
                    'autofocus' => $autofocus && ! $disabled && $i === $resume ? true : null,
                    'disabled' => $disabled ? true : null,
                    'class' => $box,
                ], fn ($v) => $v !== null && $v !== false));
            @endphp
            {{-- Square at the control's height, 44px below sm and 36px above,
                 until the row runs out of room. --}}
            <span class="w-11 min-w-0 sm:w-9">
                <span class="{{ $field['shell']((bool) $disabled) }}">
                    <input {{ $boxAttributes }} data-ds-otp-box />
                </span>
            </span>
        @endfor
    </div>

    @if ($error)
        <p id="{{ $id }}-error" class="{{ $field['error'] }}">
            <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @elseif ($help)
        <p id="{{ $id }}-help" class="{{ $field['help'] }}">{{ $help }}</p>
    @endif
</div>
