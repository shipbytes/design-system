@props([
    /** input | select | textarea */
    'as' => 'input',
    'type' => 'text',
    /** Label text. Omit only when an adjacent visible label already names the field. */
    'label' => null,
    /** Guidance under the field. Replaced by `error` — never stacked with it. */
    'help' => null,
    /** Validation message. Its presence styles the control and sets aria-invalid. */
    'error' => null,
    /** Heroicon name rendered inside the control, before the value. */
    'icon' => null,
    /** Rows for a textarea. */
    'rows' => 3,
    'disabled' => false,
    /** A button that shows and hides the value. type="password" only; needs Alpine and the package's script. */
    'reveal' => false,
])

@php
    // Stable under wire:model, random otherwise: see Support\ControlId.
    $id = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label);
    $describedBy = $error ? "{$id}-error" : ($help ? "{$id}-help" : null);

    // `readonly` stays an attribute, not a prop, so it reaches the element and
    // the browser keeps the field focusable and submitted; it is read here only
    // to look the part. Without this a readonly field looks editable and does
    // nothing when typed into. A native select ignores the attribute, so it
    // does not get the look either.
    $readonly = $as !== 'select'
        && $attributes->has('readonly')
        && ! in_array($attributes->get('readonly'), [false, null], true);

    // The shell, the control and the messages are the field recipe, shared
    // with every component that has to look like this one: see
    // resources/views/partials/field.php.
    $field = \Shipbytes\BladeUi\Support\Field::recipe();

    $shell = $field['shell']($disabled || $readonly);

    $control = implode(' ', [
        $field['control']((bool) $error, $readonly, (bool) $disabled),
        $icon ? 'pl-9 sm:pl-9' : '',
        $as === 'select' ? 'pr-9 sm:pr-8' : '',
    ]);

    // Appended rather than listed above, so a field without it renders the
    // exact string it always has: see InputUnchangedTest.
    $revealable = $reveal && $as === 'input' && $type === 'password';
    if ($revealable) {
        $control .= ' pr-11 sm:pr-9';
    }

    $shared = $attributes->except(['class'])->merge([
        'id' => $id,
        'class' => $control,
        'aria-describedby' => $describedBy,
        'aria-invalid' => $error ? 'true' : null,
    ])->filter(fn ($v) => $v !== null);
@endphp

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="{{ $field['label'] }}">{{ $label }}</label>
    @endif

    <span @class([$shell, 'mt-1.5' => (bool) $label])>
        @if ($icon)
            <span class="pointer-events-none absolute inset-y-0 left-3 z-10 flex items-center text-fg-muted">
                <x-ds::icon :name="$icon" />
            </span>
        @endif

        @if ($as === 'textarea')
            <textarea {{ $shared }} rows="{{ $rows }}" @disabled($disabled)>{{ $slot }}</textarea>
        @elseif ($as === 'select')
            <select {{ $shared }} @disabled($disabled)>{{ $slot }}</select>
            {{-- pointer-events-none so a click on the caret still opens the select --}}
            <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-fg-muted">
                {{-- 20px below sm, 16px above: the caret is a touch target on a
                     phone and a hint on a desktop. Two breakpoints, not two
                     conflicting sizes — size-5 and sm:size-4 do not fight. --}}
                <x-ds::icon name="chevron-down" variant="mini" size="5" class="sm:size-4" />
            </span>
        @elseif ($revealable)
            <input type="password" {{ $shared }} @disabled($disabled) />
            {{-- A toggle button with a name that does not change: aria-pressed
                 says whether the password is showing, and a name that ALSO
                 flipped would be read as "Hide password, pressed", which is
                 two answers to one question. The icons swap off aria-pressed
                 in CSS, so the first paint is right before the script runs. --}}
            <button
                type="button"
                class="group absolute inset-y-0 right-0 z-10 flex w-11 items-center justify-center rounded-r-control text-fg-muted hover:text-fg focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus-ring disabled:cursor-not-allowed disabled:text-fg-subtle disabled:hover:text-fg-subtle sm:w-9"
                x-data="{{ \Shipbytes\BladeUi\Support\Behaviour::data('dsPassword') }}"
                aria-controls="{{ $id }}"
                aria-pressed="false"
                aria-label="Show password"
                @disabled($disabled)
            >
                <x-ds::icon name="eye" class="group-aria-pressed:hidden" />
                <x-ds::icon name="eye-slash" class="hidden group-aria-pressed:block" />
            </button>
        @else
            <input type="{{ $type }}" {{ $shared }} @disabled($disabled) />
        @endif
    </span>

    @if ($error)
        <p id="{{ $id }}-error" class="{{ $field['error'] }}">
            <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @elseif ($help)
        {{-- Help is REPLACED by the error, never stacked with it: two lines of
             guidance under one field is one line too many. --}}
        <p id="{{ $id }}-help" class="{{ $field['help'] }}">{{ $help }}</p>
    @endif
</div>
