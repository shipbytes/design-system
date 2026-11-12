@props([
    /** Submitted field name. Each tag posts as one `name[]` value; `[]` is appended for you. */
    'name',
    'label' => null,
    /** Guidance under the field. Replaced by `error`, never stacked with it. */
    'help' => null,
    'error' => null,
    /** The tags already chosen, rendered as chips by PHP. */
    'value' => [],
    /** Shown in the text box. */
    'placeholder' => null,
    /** The most tags it takes. An entry past it stays in the box with a message. */
    'max' => null,
    /** Keys that commit what is typed: 'Enter', and any single character. A pasted line break always splits. */
    'separators' => ['Enter', ','],
    /** Refuse a tag already in the list, compared without case. */
    'unique' => true,
    /** 'email', or null for no check. Checked in the browser, for the reader; validate on the server too. */
    'validate' => null,
    'disabled' => false,
])

@php
    if (! in_array($validate, [null, 'email'], true)) {
        throw new \InvalidArgumentException(
            "ds::tag-input: validate=\"{$validate}\" is not a check it knows. It takes 'email', or null for none."
        );
    }

    $separators = array_values((array) $separators);
    foreach ($separators as $separator) {
        if ($separator !== 'Enter' && mb_strlen((string) $separator) !== 1) {
            throw new \InvalidArgumentException(
                "ds::tag-input: separator \"{$separator}\" is neither 'Enter' nor a single character."
            );
        }
    }

    // Stable under wire:model, random otherwise: see Support\ControlId.
    $id = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label, $name);
    $field = \Shipbytes\BladeUi\Support\Field::recipe();
    $messageId = $error ? "{$id}-error" : ($help ? "{$id}-help" : null);

    $tags = collect((array) $value)
        ->map(fn ($tag) => trim((string) $tag))
        ->filter(fn ($tag) => $tag !== '')
        ->values()
        ->all();

    /*
     * Every word the script shows or announces is written here, so it can be
     * changed with the view, and the script holds no copy of its own.
     */
    $messages = [
        'remove' => 'Remove :tag',
        'added' => 'Added :tag.',
        'removed' => 'Removed :tag.',
        'duplicate' => ':tag is already in the list.',
        'invalid' => ':tag is not an email address.',
        'max' => 'No more than :max can be added.',
    ];

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsTagInput', [
        'separators' => $separators,
        'unique' => (bool) $unique,
        'max' => $max === null ? null : (int) $max,
        'validate' => $validate,
        'messages' => $messages,
    ]);

    /*
     * The frame is the control's border, padding and text, laid out as a
     * wrapping row: chips and a borderless text box inside one field, the way
     * the combobox holds its chips. Its padding is the control's, so with no
     * chips it is exactly the height of ds::input.
     */
    $frame = implode(' ', [
        'relative flex w-full flex-wrap items-center gap-x-1.5 gap-y-1 rounded-control',
        $field['padding']('md'),
        $field['text'],
        'border',
        $field['border']((bool) $error, false),
        $field['tint']((bool) $disabled, false),
        $disabled ? 'cursor-not-allowed' : 'cursor-text',
    ]);

    $chip = 'inline-flex max-w-full items-center gap-1 rounded-chip bg-neutral-tint py-0.5 pr-1 pl-2 text-meta font-medium text-on-neutral-tint';
    $remove = 'shrink-0 rounded-chip p-0.5 opacity-60 hover:opacity-100 focus-visible:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-focus-ring disabled:cursor-not-allowed disabled:hover:opacity-60 motion-safe:transition-opacity motion-safe:duration-hint-enter motion-safe:ease-enter';

    $text = new \Illuminate\View\ComponentAttributeBag(array_filter([
        'type' => 'text',
        'id' => $id,
        'placeholder' => $placeholder,
        'autocomplete' => 'off',
        'inputmode' => $validate === 'email' ? 'email' : null,
        'autocapitalize' => $validate === 'email' ? 'off' : null,
        'spellcheck' => $validate === 'email' ? 'false' : null,
        'enterkeyhint' => in_array('Enter', $separators, true) ? 'enter' : null,
        'aria-describedby' => trim("{$id}-note ".($messageId ?? '')),
        'aria-invalid' => $error ? 'true' : null,
        'disabled' => $disabled ? true : null,
    ]));

    // wire:model and x-model go on the root, beside x-modelable, which ties
    // them to the behaviour's list of tags in both directions.
    $model = $attributes->filter(fn ($value, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model'));

    // Built here, not with conditionals inside the tag: see CLAUDE.md on
    // Livewire's markers.
    $list = new \Illuminate\View\ComponentAttributeBag(array_filter([
        'aria-labelledby' => $label ? "{$id}-label" : null,
        'hidden' => $tags ? null : true,
    ]));
@endphp

<div x-data="{{ $state }}" x-modelable="dsValue" {{ $model }} {{ $attributes->only('class') }}>
    @if ($label)
        <label id="{{ $id }}-label" for="{{ $id }}" class="{{ $field['label'] }}">{{ $label }}</label>
    @endif

    <span @class([$field['shell']((bool) $disabled), 'mt-1.5' => (bool) $label])>
        <span class="{{ $frame }}" data-ds-tag-field>
            {{-- A list, so a screen reader hears how many there are. Laid out
                 as `contents`, so the chips and the text box wrap as one row
                 and the box follows the last chip rather than starting a line
                 of its own. The explicit role keeps the list a list where a
                 browser drops the semantics of a `contents` element. Hidden
                 while empty. --}}
            <ul role="list" class="contents" {{ $list }} data-ds-tag-list>
                @foreach ($tags as $tag)
                    <li class="{{ $chip }}" data-ds-tag>
                        <span class="truncate" data-ds-tag-label>{{ $tag }}</span>
                        <button type="button" class="{{ $remove }}" aria-label="{{ str_replace(':tag', $tag, $messages['remove']) }}" data-ds-tag-remove @disabled($disabled)>
                            <x-ds::icon name="x-mark" variant="micro" size="3" />
                        </button>
                        {{-- The chip carries the value it posts, so the two
                             cannot drift apart. --}}
                        <input type="hidden" name="{{ $name }}[]" value="{{ $tag }}" data-ds-tag-value @disabled($disabled) />
                    </li>
                @endforeach
            </ul>

            <input {{ $text }} class="min-w-24 flex-1 border-0 bg-transparent p-0 {{ $field['text'] }} focus:outline-hidden disabled:cursor-not-allowed" data-ds-tag-text />
        </span>
    </span>

    {{-- One chip, for the script to copy. Its text and value are set with
         textContent and value, never as markup. --}}
    <template data-ds-tag-template>
        <li class="{{ $chip }}" data-ds-tag>
            <span class="truncate" data-ds-tag-label></span>
            <button type="button" class="{{ $remove }}" data-ds-tag-remove>
                <x-ds::icon name="x-mark" variant="micro" size="3" />
            </button>
            <input type="hidden" name="{{ $name }}[]" value="" data-ds-tag-value />
        </li>
    </template>

    {{-- Why the entry in the box was not added. Shown in the help line's place
         while it is there. --}}
    <p id="{{ $id }}-note" class="{{ $field['error'] }}" aria-live="polite" data-ds-tag-note hidden>
        <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
        <span data-ds-tag-note-text></span>
    </p>

    @if ($error)
        <p id="{{ $id }}-error" class="{{ $field['error'] }}">
            <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @elseif ($help)
        <p id="{{ $id }}-help" class="{{ $field['help'] }}" data-ds-tag-help>{{ $help }}</p>
    @endif

    {{-- Adds and removes, said once each. A chip appearing is silent otherwise. --}}
    <p class="sr-only" aria-live="polite" data-ds-tag-status></p>
</div>
