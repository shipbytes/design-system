@props([
    /** The question, which names the dialog. */
    'title' => 'Are you sure?',
    /** The consequence, read with the name on open: "The project and its 4 reports are deleted." */
    'description' => null,
    /** The confirm control's words. */
    'confirmLabel' => 'Delete',
    /** The safe choice's words. */
    'cancelLabel' => 'Cancel',
    /** danger | neutral: the confirm control is the danger button, or the primary one. */
    'tone' => 'danger',
    /**
     * Type to confirm: the confirm control stays disabled until the reader
     * types exactly this, the project's name say. Check it on the server too.
     */
    'confirmText' => null,
    /** The field's words; `:text` is where `confirmText` goes, in bold. */
    'confirmPrompt' => 'Type :text to confirm',
    /** false: the typed text may differ in letter case and in spaces around it. */
    'strict' => true,
    /**
     * After the confirm press, keep the card open with a spinner on the
     * control and refuse every later press, until it closes: the page going,
     * the row going, or the host closing it through `open`.
     */
    'loading' => false,
    /** The confirm control's type: submit inside a form, button for one it must not submit. */
    'type' => 'submit',
    /** As ds::popover: the preferred side, flipped when it lacks room. */
    'placement' => 'bottom-start',
    /** As ds::popover: sm | md | lg. */
    'width' => 'md',
    /** As ds::popover: an assignable Alpine reference holding the open state. */
    'open' => null,
])

@php
    $tone = $tone === 'neutral' ? 'neutral' : 'danger';
    $type = $type === 'button' ? 'button' : 'submit';
    $confirmText = is_string($confirmText) && $confirmText !== '' ? $confirmText : null;

    // Hashed from what it says, not random, for the reason ds::popover gives:
    // Livewire keys a morph by id.
    $base = 'ds-confirm-'.substr(md5($title.$description.$confirmLabel.$confirmText), 0, 8);
    $titleId = "{$base}-title";
    $descriptionId = $description ? "{$base}-description" : null;
    $fieldId = "{$base}-field";

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsConfirmDialog', [
        'text' => $confirmText,
        'strict' => (bool) $strict,
        'loading' => (bool) $loading,
    ]);

    /*
     * As on ds::confirm-button, everything but `class` lands on the confirm
     * control: wire:click, @confirm, a name and value. `class` places the
     * whole thing in the host's row.
     */
    $control = $attributes->except('class')->merge([
        'type' => $type,
        'data-ds-confirm-confirm' => true,
        'x-bind:disabled' => '$data.dsMatched !== true',
        'x-bind:aria-busy' => "\$data.dsBusy ? 'true' : null",
        'x-bind:aria-disabled' => "\$data.dsBusy ? 'true' : null",
        'x-bind:class' => "{ 'cursor-wait': \$data.dsBusy === true }",
    ]);

    $prompt = $confirmText === null ? null : str_replace(
        e(':text'),
        '<strong class="font-semibold text-fg">'.e($confirmText).'</strong>',
        e($confirmPrompt),
    );
@endphp

<x-ds::popover
    role="alertdialog"
    :labelledby="$titleId"
    :describedby="$descriptionId"
    :placement="$placement"
    :width="$width"
    :open="$open"
    :attributes="$attributes->only('class')"
>
    <x-slot:trigger>{{ $trigger ?? '' }}</x-slot:trigger>

    <div x-data="{{ $state }}" data-ds-confirm-dialog>
        <p id="{{ $titleId }}" class="text-body font-semibold text-fg">{{ $title }}</p>
        @if ($description)
            <p id="{{ $descriptionId }}" class="mt-1 text-body text-fg-body">{{ $description }}</p>
        @endif

        @if ($confirmText !== null)
            <div class="mt-3">
                <label for="{{ $fieldId }}" class="block text-meta text-fg-body">{!! $prompt !!}</label>
                {{-- No name: the field is a check on the reader, not a value
                     for the form. Nothing that guesses or corrects. --}}
                <x-ds::input
                    :id="$fieldId"
                    class="mt-1.5"
                    autocomplete="off"
                    autocapitalize="off"
                    autocorrect="off"
                    spellcheck="false"
                    data-ds-confirm-field
                    data-ds-autofocus
                />
            </div>
        @endif

        {{-- Focus lands on Cancel, the safe choice, unless there is a field
             to type in first. --}}
        <div class="mt-4 flex justify-end gap-2">
            @if ($confirmText === null)
                <x-ds::button variant="secondary" size="sm" data-ds-confirm-cancel data-ds-autofocus x-bind:disabled="$data.dsBusy === true">{{ $cancelLabel }}</x-ds::button>
            @else
                <x-ds::button variant="secondary" size="sm" data-ds-confirm-cancel x-bind:disabled="$data.dsBusy === true">{{ $cancelLabel }}</x-ds::button>
            @endif
            <x-ds::button :variant="$tone === 'danger' ? 'danger' : 'primary'" size="sm" :disabled="$confirmText !== null" :attributes="$control">
                <svg class="size-4 shrink-0 motion-safe:animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" aria-hidden="true" x-show="$data.dsBusy === true" style="display: none">
                    <path d="M12 3a9 9 0 1 0 9 9" />
                </svg>
                {{ $confirmLabel }}
            </x-ds::button>
        </div>
    </div>
</x-ds::popover>
