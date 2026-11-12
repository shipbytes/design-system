@props([
    /** Submitted field name. Posts a plain decimal, "1234.50", never the text shown. */
    'name',
    'label' => null,
    /** Guidance under the field. Replaced by `error`, never stacked with it. */
    'help' => null,
    'error' => null,
    /** The amount: "1234.5", 1234.5, or with `minor` 123450. Read as digits, never as a float. */
    'value' => null,
    /** ISO 4217 code: USD, INR, JPY. Sets the decimal places and the symbol. */
    'currency' => 'USD',
    /** How the amount is grouped and pointed, and where the symbol sits. Defaults to the app's locale. */
    'locale' => null,
    /** Bounds and step, in whole currency units ("10.00"), checked in the browser when the field is left. */
    'min' => null,
    'max' => null,
    'step' => null,
    /** Decimal places. Left out, the currency's own: 2, 0 for JPY, 3 for KWD. */
    'decimals' => null,
    /** symbol | code | none: what is shown beside the amount, on the side the locale puts it. */
    'symbol' => 'symbol',
    /** Currency codes to choose from: the symbol becomes a select, posting under `currencyName`. */
    'currencies' => null,
    /** With `currencies`: the field the chosen code posts under. Defaults to "{name}_currency". */
    'currencyName' => null,
    /** Accept an amount below zero. */
    'allowNegative' => false,
    /** Post integer minor units, "123450", instead of "1234.50". */
    'minor' => false,
    'disabled' => false,
    /** md: the input's own size. sm: 32px, for a table row. */
    'size' => 'md',
])

@php
    // Stable under wire:model, random otherwise: see Support\ControlId.
    $id = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label, $name);
    $describedBy = $error ? "{$id}-error" : ($help ? "{$id}-help" : null);
    $field = \Shipbytes\BladeUi\Support\Field::recipe();
    $size = in_array($size, ['sm', 'md'], true) ? $size : 'md';
    $symbol = in_array($symbol, ['symbol', 'code', 'none'], true) ? $symbol : 'symbol';

    $code = strtoupper((string) $currency);
    $choices = [];
    foreach ((array) ($currencies ?? []) as $key => $text) {
        $choices[strtoupper(is_string($key) ? $key : (string) $text)] = (string) $text;
    }
    if ($choices !== [] && ! array_key_exists($code, $choices)) {
        $choices = [$code => $code] + $choices;
    }
    $currencyName ??= "{$name}_currency";

    $places = fn (string $c) => $decimals !== null ? max(0, (int) $decimals) : \Shipbytes\BladeUi\Support\Money::decimals($c);
    $at = $places($code);

    $machine = \Shipbytes\BladeUi\Support\Money::machine($value, $code, $at, (bool) $minor, (bool) $minor);
    $shown = \Shipbytes\BladeUi\Support\Money::format($value, $code, $locale, $at, (bool) $minor);
    // The bounds as the browser compares them: whole units, as digits.
    $bound = fn ($v) => $v === null || $v === '' ? null : \Shipbytes\BladeUi\Support\Money::machine($v, $code, max($at, strlen(explode('.', (string) $v)[1] ?? '')));
    [$low, $high, $every] = [$bound($min), $bound($max), $bound($step)];
    $plain = fn ($v) => $v === null ? '' : \Shipbytes\BladeUi\Support\Money::format($v, $code, $locale, max($at, strlen(explode('.', $v)[1] ?? '')));

    ['prefix' => $prefix, 'suffix' => $suffix] = $choices ? ['prefix' => '', 'suffix' => ''] : \Shipbytes\BladeUi\Support\Money::affix($code, $locale, $symbol);

    // Every word the script shows is written here, so it changes with the
    // view and the script holds no copy of its own.
    $words = array_filter([
        'min' => $low !== null ? "Enter at least {$plain($low)}." : null,
        'max' => $high !== null ? "Enter no more than {$plain($high)}." : null,
        'step' => $every !== null ? "Enter a multiple of {$plain($every)}." : null,
    ]);

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsMoneyInput', [
        'locale' => \Shipbytes\BladeUi\Support\Money::bcp47($locale),
        'decimals' => $at,
        'fixed' => $decimals !== null,
        'negative' => (bool) $allowNegative,
        'minor' => (bool) $minor,
        'min' => $low,
        'max' => $high,
        'step' => $every,
        'words' => $words,
    ]);

    $isModel = fn ($v, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model');
    $model = $attributes->filter($isModel);
    $rest = $attributes->except(['class', 'id'])->filter(fn ($v, $key) => ! $isModel($v, $key));

    /*
     * Without the script this is a plain text field named `name`, posting
     * what was typed, which the server validates. With it, `name` moves to
     * the hidden input, which always holds the amount as digits. Each move
     * is a binding to the scope as on ds::time-picker, so a Livewire morph,
     * which writes the server's attributes back, keeps it. `$data.` reads a
     * name without throwing in a host with no script.
     */
    $nameJs = \Illuminate\Support\Js::from((string) $name);
    $currencyId = "{$id}-currency";

    $text = new \Illuminate\View\ComponentAttributeBag(array_filter([
        'type' => 'text',
        'id' => $id,
        'name' => $name,
        'value' => $shown,
        'inputmode' => 'decimal',
        // Digits, separators and spaces, and a minus when one is allowed: a
        // browser with no script still says what it takes.
        'pattern' => $allowNegative ? "[\\-−]?[\\d.,\\s'’]+" : "[\\d.,\\s'’]+",
        'autocomplete' => 'off',
        'aria-describedby' => trim(($choices ? '' : $currencyId).' '.($describedBy ?? '')) ?: null,
        'aria-invalid' => $error ? 'true' : null,
        'disabled' => $disabled ? true : null,
        'x-bind:name' => "\$data.dsReady ? null : {$nameJs}",
        'class' => "min-w-0 flex-1 border-0 bg-transparent p-0 tabular-nums {$field['text']} focus:outline-hidden disabled:cursor-not-allowed",
    ], fn ($v) => $v !== null));
    $text = $rest->merge($text->getAttributes());

    $hidden = new \Illuminate\View\ComponentAttributeBag(array_filter([
        'type' => 'hidden',
        'value' => e($machine),
        'disabled' => $disabled ? true : null,
        'x-bind:name' => "\$data.dsReady ? {$nameJs} : null",
    ], fn ($v) => $v !== null));

    // The control's border, padding and text, laid out as a row: the
    // symbol or the currency select, then a borderless text box, as the tag
    // input lays out its chips. Its padding is the control's, so it is
    // exactly the height of ds::input.
    $frame = implode(' ', [
        'relative flex w-full items-center gap-2 rounded-control',
        $field['padding']($size),
        $field['text'],
        'border',
        $field['border']((bool) $error, false),
        $field['tint']((bool) $disabled, false),
        $disabled ? 'cursor-not-allowed' : 'cursor-text',
    ]);
    $affix = 'pointer-events-none shrink-0 text-fg-muted select-none';
@endphp

<div x-data="{{ $state }}" x-modelable="dsValue" {{ $model }} data-ds-money {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="{{ $field['label'] }}">{{ $label }}</label>
    @endif

    <input {{ $hidden }} data-ds-money-value />

    <span @class([$field['shell']((bool) $disabled), 'mt-1.5' => (bool) $label])>
        <span class="{{ $frame }}" data-ds-money-frame>
            @if ($choices)
                {{-- A native select, so the code posts with or without the
                     script, and the script reads each currency's places
                     from its option. --}}
                <span class="relative -my-1 -ms-1 flex shrink-0 items-center border-e border-border pe-1">
                    <select
                        name="{{ $currencyName }}"
                        aria-label="Currency"
                        class="cursor-pointer appearance-none rounded-chip bg-transparent py-1 ps-1 pe-6 font-medium text-fg focus:outline-hidden disabled:cursor-not-allowed"
                        data-ds-money-currency
                        @disabled($disabled)
                    >
                        @foreach ($choices as $choice => $choiceLabel)
                            <option value="{{ $choice }}" data-ds-decimals="{{ $places($choice) }}" @selected($choice === $code)>{{ $choiceLabel }}</option>
                        @endforeach
                    </select>
                    <x-ds::icon name="chevron-down" variant="mini" size="4" class="pointer-events-none absolute end-1.5 text-fg-muted" />
                </span>
            @elseif ($prefix !== '')
                <span class="{{ $affix }}" aria-hidden="true">{{ $prefix }}</span>
            @endif

            <input {{ $text }} data-ds-money-input />

            @if (! $choices && $suffix !== '')
                <span class="{{ $affix }}" aria-hidden="true">{{ $suffix }}</span>
            @endif
        </span>
    </span>

    {{-- The symbol is drawn for the eye; the code is what is said. --}}
    @unless ($choices)
        <span id="{{ $currencyId }}" class="sr-only">{{ $code }}</span>
    @endunless

    @if ($error)
        <p id="{{ $id }}-error" class="{{ $field['error'] }}">
            <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @elseif ($help)
        <p id="{{ $id }}-help" class="{{ $field['help'] }}">{{ $help }}</p>
    @endif
</div>
