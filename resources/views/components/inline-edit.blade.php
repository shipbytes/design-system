@props([
    /** The text as it stands. */
    'value' => null,
    /** Submitted field name, for a form around it or the one `action` draws. */
    'name' => null,
    /** Required: what the text is, "Title". Names the field, and the button as "Edit Title: Q3 roadmap". */
    'label' => null,
    /** Shown in place of the text while it is empty, "Add a title". */
    'placeholder' => null,
    /** A textarea: Enter is a new line, and Ctrl+Enter (Cmd+Enter on a Mac) saves. */
    'multiline' => false,
    /** md, or sm for a table cell: less room around the text. The text itself takes the size of where it sits. */
    'size' => 'md',
    /** enter: Enter saves, and so does leaving the field. blur: only leaving saves. buttons: Save and Cancel. */
    'submit' => 'enter',
    /** A URL: the field is drawn inside a real form posting there, and a save submits it. */
    'action' => null,
    /** With `action`: POST, or PUT, PATCH or DELETE (spoofed with _method), or GET. */
    'method' => 'POST',
    /** Rendered as saving: a spinner by the text. A host also sets data-ds-saving, or dsSaving from Alpine. */
    'saving' => false,
    /** A failed save's message. Rendered in edit mode, with the field marked invalid. */
    'error' => null,
    'disabled' => false,
])

@php
    if (blank($label)) {
        throw new \InvalidArgumentException(
            'ds::inline-edit needs a `label`: it names the field and the button that opens it ("Edit Title: Q3 roadmap"), and text that turns into a field with no name is a field nobody can find.'
        );
    }

    // Stable under wire:model, random otherwise: see Support\ControlId.
    $id = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label, $name);
    $field = \Shipbytes\BladeUi\Support\Field::recipe();

    $size = in_array($size, ['sm', 'md'], true) ? $size : 'md';
    $submit = in_array($submit, ['enter', 'blur', 'buttons'], true) ? $submit : 'enter';
    $text = (string) ($value ?? '');
    $multiline = (bool) $multiline;

    $verb = strtoupper((string) $method);
    $formMethod = $verb === 'GET' ? 'GET' : 'POST';
    $spoof = in_array($verb, ['PUT', 'PATCH', 'DELETE'], true) ? $verb : null;
    // A session is what a token is kept in; a host without one gets none.
    $token = $action !== null && $formMethod === 'POST' ? rescue(fn () => csrf_token(), null, false) : null;

    $isModel = fn ($v, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model');
    $model = $attributes->filter($isModel);
    $rest = $attributes->except(['class', 'id'])->filter(fn ($v, $key) => ! $isModel($v, $key));

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsInlineEdit', [
        'submit' => $submit,
        'multiline' => $multiline,
        'label' => (string) $label,
    ]);

    $pad = $size === 'sm' ? 'px-1 py-0' : 'px-1.5 py-0.5';
    $pull = $size === 'sm' ? '-mx-1' : '-mx-1.5';

    /*
     * The read state and the field share their box: the same padding, a
     * one-pixel border (transparent while reading), and the font of whatever
     * the text sits in, a heading or a table cell. So the words do not move
     * when the field opens, and the field is as tall as the line was.
     */
    $read = implode(' ', [
        'group/read flex max-w-full items-start gap-1.5 rounded-control border border-transparent text-start [font:inherit] text-inherit',
        $pad, $pull,
        $multiline ? 'w-[calc(100%+--spacing(3))]' : 'w-fit',
        'hover:bg-fg/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
        'disabled:cursor-not-allowed disabled:hover:bg-transparent',
        'motion-safe:transition-colors motion-safe:duration-hint-enter motion-safe:ease-enter',
    ]);

    $control = implode(' ', [
        'block max-w-full min-w-0 rounded-control border bg-surface text-fg [font:inherit] shadow-raised',
        'focus:outline-2 focus:outline-offset-2 focus:outline-focus-ring placeholder:text-fg-muted',
        'disabled:cursor-not-allowed disabled:bg-surface-subtle disabled:text-fg-subtle',
        $error ? 'border-danger' : 'border-border',
        $pad, $pull,
        // Grows with the text where the browser can; the script sizes it
        // where it cannot. Without the script it is a whole-width field.
        '[field-sizing:content]',
        $multiline ? 'w-[calc(100%+--spacing(3))] resize-y' : 'w-full in-data-ds-live:w-auto',
    ]);

    $fieldAttributes = new \Illuminate\View\ComponentAttributeBag(array_filter([
        'id' => $id,
        // Printed as given by the bag, so escaped here.
        'name' => $name !== null ? e($name) : null,
        'aria-label' => e((string) $label),
        'placeholder' => $placeholder !== null ? e($placeholder) : null,
        'aria-invalid' => $error ? 'true' : null,
        'aria-describedby' => "{$id}-error",
        'disabled' => $disabled ? true : null,
        'rows' => $multiline ? 3 : null,
        'class' => $control,
    ], fn ($v) => $v !== null && $v !== false));

    $editLabel = 'Edit '.$label.($text !== '' ? ': '.$text : '');

    // With the script, the buttons show while editing in `buttons` mode.
    // Without it, a form drawn here needs a way to send it: a Save, and
    // Cancel as the form's reset, which puts the text back with no script.
    $buttonsLive = $submit === 'buttons' ? 'true' : 'false';
    $buttonsStatic = $action !== null;
@endphp

<div
    x-data="{{ $state }}"
    x-modelable="dsValue"
    {{ $model }}
    {{ $rest }}
    data-ds-inline-edit
    x-bind:data-ds-live="$data.dsReady ? '' : null"
    @if ($saving) data-ds-saving @endif
    @if ($error) data-ds-error="{{ $error }}" @endif
    {{ $attributes->only('class')->merge(['class' => 'group/inline min-w-0 max-w-full']) }}
>
    @if ($action !== null)
        <form method="{{ $formMethod }}" action="{{ $action }}" data-ds-inline-form>
            @if ($token)<input type="hidden" name="_token" value="{{ $token }}" autocomplete="off" />@endif
            @if ($spoof)<input type="hidden" name="_method" value="{{ $spoof }}" />@endif
    @endif

    {{-- The read state: the text as a button, which is how a keyboard gets
         to edit it. Hidden until the script runs, which is what makes it
         work. --}}
    <button
        type="button"
        hidden
        x-bind:hidden="! $data.dsReady || $data.dsEditing"
        class="{{ $read }}"
        aria-label="{{ $editLabel }}"
        data-ds-inline-read
        @disabled($disabled)
    >
        <span @class(['min-w-0 break-words data-ds-empty:text-fg-muted', 'whitespace-pre-wrap' => $multiline]) @if ($text === '') data-ds-empty @endif data-ds-inline-text data-ds-placeholder="{{ $placeholder }}">{{ $text !== '' ? $text : $placeholder }}</span>
        <span class="mt-[calc((1lh_-_1rem)/2)] flex shrink-0 text-fg-muted" aria-hidden="true">
            {{-- A pencil on hover and on keyboard focus, and always on a touch
                 screen, which has no hover. A spinner instead while saving. --}}
            <x-ds::icon name="pencil" variant="micro" size="4" class="opacity-0 group-hover/read:opacity-100 group-focus-visible/read:opacity-100 pointer-coarse:opacity-100 group-data-ds-saving/inline:hidden" />
            <svg class="hidden size-4 group-data-ds-saving/inline:block motion-safe:animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <path d="M12 3a9 9 0 1 0 9 9" />
            </svg>
        </span>
    </button>

    {{-- The field: always there and always editable without the script, so
         a page it never reaches can still change the text; with it, shown
         only while editing. --}}
    <div x-bind:hidden="$data.dsReady && ! $data.dsEditing" data-ds-inline-field class="flex max-w-full flex-wrap items-start gap-2">
        @if ($multiline)
            <textarea {{ $fieldAttributes }} data-ds-inline-input>{{ $text }}</textarea>
        @else
            <input type="text" value="{{ $text }}" {{ $fieldAttributes }} data-ds-inline-input />
        @endif

        <span
            @if (! $buttonsStatic) hidden @endif
            x-bind:hidden="$data.dsReady ? ! ($data.dsEditing && {{ $buttonsLive }}) : {{ $buttonsStatic ? 'false' : 'true' }}"
            class="flex shrink-0 items-center gap-1.5"
        >
            <x-ds::button size="sm" variant="primary" type="submit" x-bind:type="$data.dsReady ? 'button' : 'submit'" data-ds-inline-save :disabled="$disabled">Save</x-ds::button>
            <x-ds::button size="sm" variant="ghost" type="reset" x-bind:type="$data.dsReady ? 'button' : 'reset'" data-ds-inline-cancel :disabled="$disabled">Cancel</x-ds::button>
        </span>
    </div>

    <p id="{{ $id }}-error" class="{{ $field['error'] }}" @if (! $error) hidden @endif data-ds-inline-message>
        <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
        <span data-ds-inline-message-text>{{ $error }}</span>
    </p>

    @if ($action !== null)
        </form>
    @endif
</div>
