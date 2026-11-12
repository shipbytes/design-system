@props([
    /** Submitted field name. Posts the shortcut as a string, "mod+shift+k", or '' for none. */
    'name',
    'label' => null,
    /** Guidance under the field. Replaced by `error`, never stacked with it. */
    'help' => null,
    'error' => null,
    /** The shortcut, "mod+shift+k". Normalised: "Shift+Mod+K" is the same. */
    'value' => null,
    /** Shown while there is no shortcut. */
    'placeholder' => 'Record shortcut',
    /** Record Cmd on a Mac and Ctrl elsewhere as "mod", so one value works on both. */
    'portable' => true,
    /** key: what the key types ("/" wherever it is). code: where it is, written as the US character. */
    'by' => 'key',
    /** Take a key with no Ctrl, Cmd or Alt: "k", "f2", "shift+k". */
    'allowBare' => false,
    /** Shortcuts refused, with a message. Defaults to the browser's own (Support\Shortcut::RESERVED). */
    'reserved' => null,
    /** Shortcuts already taken, ['mod+k' => 'Search']: warned "Already used by Search", and still kept. */
    'conflicts' => [],
    'disabled' => false,
    /** md: the input's own size. sm: 32px, for a table row. */
    'size' => 'md',
])

@php
    $value = \Shipbytes\BladeUi\Support\Shortcut::normalise(is_string($value) ? $value : null);
    $by = $by === 'code' ? 'code' : 'key';
    $size = in_array($size, ['sm', 'md'], true) ? $size : 'md';
    $reserved = array_values(array_map([\Shipbytes\BladeUi\Support\Shortcut::class, 'normalise'], $reserved ?? \Shipbytes\BladeUi\Support\Shortcut::RESERVED));
    $taken = [];
    foreach ((array) $conflicts as $combo => $use) {
        $taken[\Shipbytes\BladeUi\Support\Shortcut::normalise((string) $combo)] = (string) $use;
    }

    // Stable under wire:model, random otherwise: see Support\ControlId.
    $id = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label, $name);
    $field = \Shipbytes\BladeUi\Support\Field::recipe();
    $message = $error ? "{$id}-error" : ($help ? "{$id}-help" : null);

    // Every word the script shows or says is written here.
    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsShortcutInput', [
        'portable' => (bool) $portable,
        'by' => $by,
        'bare' => (bool) $allowBare,
        'reserved' => $reserved,
        'conflicts' => (object) $taken,
        'words' => [
            'recording' => 'Recording. Press the keys, or Escape to cancel.',
            'cancelled' => 'Recording cancelled.',
            'cleared' => 'Shortcut cleared.',
            'set' => 'Shortcut set to :keys.',
            'bare' => 'Hold Ctrl or Alt with the key.',
            'bareMac' => 'Hold Command, Control or Option with the key.',
            'reserved' => ':keys belongs to the browser. Choose another.',
            'conflict' => 'Already used by :use.',
        ],
    ]);

    $isModel = fn ($v, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model');
    $model = $attributes->filter($isModel);
    $rest = $attributes->except(['class', 'id'])->filter(fn ($v, $key) => ! $isModel($v, $key));

    /*
     * Without the script this text field is the control, holding the
     * normalised string. With it, it is hidden and still the one place the
     * value is held and the name posted, so a Livewire morph keeps both.
     */
    $text = $rest->merge((new \Illuminate\View\ComponentAttributeBag(array_filter([
        'type' => 'text',
        'id' => $id,
        'name' => $name,
        'value' => $value,
        'placeholder' => 'mod+shift+k',
        'autocomplete' => 'off',
        'spellcheck' => 'false',
        'aria-describedby' => $message,
        'aria-invalid' => $error ? 'true' : null,
        'disabled' => $disabled ? true : null,
        'x-bind:hidden' => '$data.dsReady',
        'class' => $field['control']((bool) $error, false, (bool) $disabled, $size).' font-mono',
    ], fn ($v) => $v !== null)))->getAttributes());

    $recorder = implode(' ', [
        $field['control']((bool) $error, false, (bool) $disabled, $size),
        // At least the field's height with only keys in it, which are a
        // little shorter than a line of text.
        'flex items-center gap-2 text-start',
        $size === 'sm' ? 'min-h-8' : 'min-h-11 sm:min-h-9',
        'pe-11 sm:pe-9 cursor-pointer disabled:cursor-not-allowed',
        'data-ds-recording:border-accent',
        'motion-safe:transition-colors motion-safe:duration-hint-enter motion-safe:ease-enter',
    ]);
@endphp

<div x-data="{{ $state }}" x-modelable="dsValue" {{ $model }} data-ds-shortcut-input {{ $attributes->only('class') }}>
    @if ($label)
        <label id="{{ $id }}-label" for="{{ $id }}" class="{{ $field['label'] }}">{{ $label }}</label>
    @endif

    <span @class([$field['shell']((bool) $disabled), 'mt-1.5' => (bool) $label])>
        <input {{ $text }} data-ds-shortcut-text />

        {{-- The recorder: a button, so it takes focus and Enter or Space as
             any button does, and lets Tab pass. Its name is the label and
             what it shows; the keys to press are its description. --}}
        <button
            type="button"
            hidden
            x-bind:hidden="! $data.dsReady"
            aria-labelledby="{{ $label ? "{$id}-label " : '' }}{{ $id }}-shown"
            aria-describedby="{{ trim("{$id}-how {$message}") }}"
            @if ($error) aria-invalid="true" @endif
            class="{{ $recorder }}"
            data-ds-shortcut-record
            @disabled($disabled)
        >
            <span id="{{ $id }}-shown" class="flex min-w-0 items-center">
                <span class="text-fg-muted" data-ds-shortcut-placeholder @if ($value !== '') hidden @endif>{{ $placeholder }}</span>
                <span class="text-accent" data-ds-shortcut-prompt hidden>Press keys…</span>
                <x-ds::kbd :shortcut="$value" data-ds-shortcut-keys />
            </span>
        </button>

        <button
            type="button"
            hidden
            x-bind:hidden="! $data.dsReady || ! $data.dsValue"
            aria-label="Clear {{ $label ?? 'shortcut' }}"
            class="absolute inset-y-0 end-0 z-10 flex w-11 items-center justify-center rounded-r-control text-fg-muted hover:text-fg focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus-ring sm:w-9"
            data-ds-shortcut-clear
            @disabled($disabled)
        >
            <x-ds::icon name="x-mark" variant="mini" size="4" />
        </button>
    </span>

    <span id="{{ $id }}-how" hidden>Press Enter to record, then the keys. Escape cancels; Backspace clears.</span>
    {{-- Said: recording, the keys taken, and why some are not. --}}
    <span class="sr-only" role="status" data-ds-shortcut-live></span>

    {{-- A shortcut refused, or one that is taken elsewhere. --}}
    <p class="{{ $field['help'] }} flex items-start gap-1.5" hidden data-ds-shortcut-note>
        <x-ds::icon name="exclamation-triangle" size="3.5" class="mt-0.5 shrink-0" />
        <span data-ds-shortcut-note-text></span>
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
