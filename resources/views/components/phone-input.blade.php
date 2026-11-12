@props([
    /** Submitted field name. Posts E.164, "+919876543210"; without the script, `name[country]` and `name[number]`. */
    'name',
    'label' => null,
    /** Guidance under the field. Replaced by `error`, never stacked with it. */
    'help' => null,
    'error' => null,
    /** E.164, "+919876543210". A number with no "+" is read as national, in `country`. */
    'value' => null,
    /** ISO code of the country chosen when `value` does not say. Defaults to the app locale's region, else US. */
    'country' => null,
    /** ISO codes to offer, in place of every country. */
    'countries' => null,
    /** ISO codes pinned to the top of the list. */
    'preferred' => [],
    /** With the script: the chosen country's ISO code posts under this name too. */
    'countryName' => null,
    /** Show each country's flag emoji beside its code. */
    'flags' => false,
    'placeholder' => null,
    'disabled' => false,
    /** md: the input's own size. sm: 32px, for a table row. */
    'size' => 'md',
    /** The picker's accessible name, and the word before the country on its button. */
    'countryLabel' => 'Country',
    'searchPlaceholder' => 'Search countries',
    'emptyText' => 'No countries match',
])

@php
    $all = \Shipbytes\BladeUi\Support\Phone::countries();

    $wanted = $countries === null ? array_keys($all) : array_map('strtoupper', (array) $countries);
    $unknown = array_diff($wanted, array_keys($all));
    if ($unknown) {
        throw new \InvalidArgumentException('ds::phone-input does not know the country code(s) '.implode(', ', $unknown).'. See resources/data/countries.php.');
    }

    // The list's order: the preferred ones as given, then the rest by name.
    $pinned = array_values(array_intersect(array_map('strtoupper', (array) $preferred), $wanted));
    $rest = array_values(array_diff($wanted, $pinned));
    usort($rest, fn ($a, $b) => strcoll($all[$a]['name'], $all[$b]['name']) ?: strcmp($a, $b));
    $in = array_merge($pinned, $rest);

    $region = strtoupper((string) (preg_split('/[-_]/', (string) app()->getLocale())[1] ?? ''));
    $default = strtoupper((string) ($country ?? ''));
    $default = in_array($default, $in, true) ? $default : (in_array($region, $in, true) ? $region : (in_array('US', $in, true) ? 'US' : $in[0]));

    ['country' => $current, 'national' => $national] = \Shipbytes\BladeUi\Support\Phone::parse(is_string($value) ? $value : null, $in, $default);
    $chosen = $all[$current];
    $e164 = \Shipbytes\BladeUi\Support\Phone::e164($current, $national);
    $shown = \Shipbytes\BladeUi\Support\Phone::mask($national, $chosen['mask']);

    // Stable under wire:model, random otherwise: see Support\ControlId.
    $id = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label, $name);
    $describedBy = trim("{$id}-note ".($error ? "{$id}-error" : ($help ? "{$id}-help" : '')));
    $field = \Shipbytes\BladeUi\Support\Field::recipe();
    $listbox = \Shipbytes\BladeUi\Support\Listbox::recipe();
    $size = in_array($size, ['sm', 'md'], true) ? $size : 'md';
    $searchable = count($in) > 6;
    $listId = "{$id}-countries";

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsPhoneInput', [
        'country' => $default,
        'primary' => array_values(\Shipbytes\BladeUi\Support\Phone::PRIMARY),
        'words' => [
            'short' => 'This number looks too short for :country.',
            'long' => 'This number looks too long for :country.',
        ],
    ]);

    $isModel = fn ($v, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model');
    $model = $attributes->filter($isModel);
    $others = $attributes->except(['class', 'id'])->filter(fn ($v, $key) => ! $isModel($v, $key));

    /*
     * Without the script the form posts what a person picked and typed, as
     * two fields: name[country] from the native select and name[number]
     * from the tel field. With it, `name` moves to the hidden input, which
     * holds E.164, and the select posts under `countryName` or not at all.
     * Each name is a binding to the scope, as on ds::time-picker, so a
     * Livewire morph keeps it.
     */
    $js = fn ($v) => \Illuminate\Support\Js::from($v);
    $countryField = "{$name}[country]";

    $number = $others->merge((new \Illuminate\View\ComponentAttributeBag(array_filter([
        'type' => 'tel',
        'id' => $id,
        'name' => "{$name}[number]",
        'value' => $shown,
        'inputmode' => 'tel',
        'autocomplete' => 'tel-national',
        'placeholder' => $placeholder,
        'aria-describedby' => $describedBy,
        'aria-invalid' => $error ? 'true' : null,
        'disabled' => $disabled ? true : null,
        'x-bind:name' => "\$data.dsReady ? null : {$js("{$name}[number]")}",
        'class' => "min-w-0 flex-1 border-0 bg-transparent p-0 tabular-nums {$field['text']} focus:outline-hidden disabled:cursor-not-allowed",
    ], fn ($v) => $v !== null)))->getAttributes());

    $hidden = new \Illuminate\View\ComponentAttributeBag(array_filter([
        'type' => 'hidden',
        'value' => e($e164),
        'disabled' => $disabled ? true : null,
        'x-bind:name' => "\$data.dsReady ? {$js((string) $name)} : null",
    ], fn ($v) => $v !== null));

    $frame = implode(' ', [
        'relative flex w-full items-center gap-2 rounded-control',
        $field['padding']($size),
        $field['text'],
        'border',
        $field['border']((bool) $error, false),
        $field['tint']((bool) $disabled, false),
    ]);
    $segment = 'relative -my-1 -ms-1 flex shrink-0 items-center border-e border-border pe-1';
    $trigger = 'flex items-center gap-1.5 rounded-chip px-1 py-1 font-medium text-fg hover:bg-fg/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring disabled:cursor-not-allowed disabled:hover:bg-transparent motion-safe:transition-colors motion-safe:duration-hint-enter motion-safe:ease-enter';
@endphp

<div x-data="{{ $state }}" x-modelable="dsValue" {{ $model }} data-ds-phone {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="{{ $field['label'] }}">{{ $label }}</label>
    @endif

    <input {{ $hidden }} data-ds-phone-value />

    <span @class([$field['shell']((bool) $disabled), 'mt-1.5' => (bool) $label])>
        <span class="{{ $frame }}">
            {{-- Without the script, a native select of dial codes. With it,
                 hidden, and still the one place the country is held. --}}
            <span class="{{ $segment }}" x-bind:hidden="$data.dsReady">
                <select
                    name="{{ $countryField }}"
                    x-bind:name="$data.dsReady ? {{ $countryName ? $js((string) $countryName) : 'null' }} : {{ $js($countryField) }}"
                    aria-label="{{ $countryLabel }}"
                    autocomplete="tel-country-code"
                    class="max-w-24 cursor-pointer appearance-none truncate rounded-chip bg-transparent py-1 ps-1 pe-6 font-medium text-fg focus:outline-hidden disabled:cursor-not-allowed"
                    data-ds-phone-select
                    @disabled($disabled)
                >
                    @foreach ($in as $iso)
                        @php $c = $all[$iso]; @endphp
                        <option
                            value="{{ $iso }}"
                            data-ds-name="{{ $c['name'] }}"
                            data-ds-dial="{{ $c['dial'] }}"
                            data-ds-mask="{{ $c['mask'] }}"
                            data-ds-min="{{ $c['min'] }}"
                            data-ds-max="{{ $c['max'] }}"
                            @if ($c['zero']) data-ds-zero @endif
                            @if ($flags) data-ds-flag="{{ \Shipbytes\BladeUi\Support\Phone::flag($iso) }}" @endif
                            @selected($iso === $current)
                        >{{ $flags ? \Shipbytes\BladeUi\Support\Phone::flag($iso).' ' : '' }}{{ $c['name'] }} (+{{ $c['dial'] }})</option>
                    @endforeach
                </select>
                <x-ds::icon name="chevron-down" variant="mini" size="4" class="pointer-events-none absolute end-1.5 text-fg-muted" />
            </span>

            <span class="{{ $segment }}" hidden x-bind:hidden="! $data.dsReady">
                <x-ds::popover :label="$countryLabel" placement="bottom-start" width="md">
                    <x-slot:trigger>
                        <button type="button" aria-haspopup="dialog" class="{{ $trigger }}" data-ds-phone-trigger @disabled($disabled)>
                            <span class="sr-only">{{ $countryLabel }}: <span data-ds-phone-name>{{ $chosen['name'] }}</span>,</span>
                            @if ($flags)
                                <span aria-hidden="true" data-ds-phone-flag>{{ \Shipbytes\BladeUi\Support\Phone::flag($current) }}</span>
                            @endif
                            <span aria-hidden="true" data-ds-phone-iso>{{ $current }}</span>
                            <span class="font-normal text-fg-muted tabular-nums" data-ds-phone-dial>+{{ $chosen['dial'] }}</span>
                            <x-ds::icon name="chevron-up-down" variant="mini" size="4" class="text-fg-muted" />
                        </button>
                    </x-slot:trigger>

                    {{-- The surface's padding is for prose; a list runs to its edges. --}}
                    <div class="-m-4">
                        @if ($searchable)
                            <div class="border-b border-divider p-2">
                                <span class="{{ $field['shell'](false) }}">
                                    <input
                                        type="search"
                                        aria-label="{{ $searchPlaceholder }}"
                                        aria-controls="{{ $listId }}"
                                        placeholder="{{ $searchPlaceholder }}"
                                        autocomplete="off"
                                        data-ds-phone-search
                                        data-ds-autofocus
                                        x-on:change.stop
                                        class="{{ $field['control'](false, false, false, 'sm') }}"
                                    />
                                </span>
                            </div>
                        @endif
                        {{-- Filled from the select's options when it first
                             opens: the countries are sent once, not twice. --}}
                        <ul role="listbox" id="{{ $listId }}" aria-label="{{ $countryLabel }}" class="flex max-h-72 flex-col overflow-y-auto py-1" data-ds-phone-list></ul>
                        <div role="status">
                            <p class="{{ $listbox['empty'] }}" hidden data-ds-phone-empty>{{ $emptyText }}</p>
                        </div>
                    </div>
                </x-ds::popover>
            </span>

            <input {{ $number }} data-ds-phone-input />
        </span>
    </span>

    {{-- One country, for the script to copy; its text is set with textContent. --}}
    <template data-ds-phone-template>
        <li role="none" class="flex flex-col">
            <div role="option" tabindex="-1" aria-selected="false" data-ds-phone-option class="group/country {{ $listbox['option'] }}">
                <span class="flex min-w-0 items-center gap-2">
                    <span aria-hidden="true" class="empty:hidden" data-ds-phone-flag></span>
                    <span class="truncate" data-ds-phone-name></span>
                    <span class="text-fg-muted tabular-nums" data-ds-phone-dial></span>
                </span>
                <x-ds::icon name="check" variant="mini" size="4" class="invisible shrink-0 text-accent group-aria-selected/country:visible" />
            </div>
        </li>
    </template>

    {{-- A number clearly too short or long for the country, said on leaving it. --}}
    <p id="{{ $id }}-note" class="{{ $field['error'] }}" aria-live="polite" hidden data-ds-phone-note>
        <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
        <span data-ds-phone-note-text></span>
    </p>

    @if ($error)
        <p id="{{ $id }}-error" class="{{ $field['error'] }}">
            <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @elseif ($help)
        <p id="{{ $id }}-help" class="{{ $field['help'] }}">{{ $help }}</p>
    @endif
</div>
