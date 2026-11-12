{{--
    ds::input with `expanding`: a search icon button that grows into the
    field. A partial, so an input without the prop renders byte for byte as
    it did (InputUnchangedTest).

    The field is the control and the only tab stop. Collapsed it is a square
    the size of an icon button, its text area hidden behind the icon; it is
    wide while it has focus or holds text, which is CSS (:has with :focus,
    and :placeholder-shown), so it works with no script at all. A press
    anywhere on the square lands in the field, which sits on top.

    The button under it, out of the tab order, is what a screen reader's
    browse mode finds and presses, and where Escape puts focus back: a
    focused button does not widen it, so the search is collapsed with focus
    still in it. With the script (dsSearch) the button focuses the field,
    Escape clears and then collapses, and `shortcut` opens it from anywhere.
--}}
@php
    // Stable under wire:model, random otherwise: see Support\ControlId.
    $id = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label);
    $field = \Shipbytes\BladeUi\Support\Field::recipe();
    $overlay = $mode === 'overlay';
    $readonly = $attributes->has('readonly') && ! in_array($attributes->get('readonly'), [false, null], true);
    $clearable = $clearable && ! $disabled && ! $readonly;
    $named = $label ?? $attributes->get('aria-label') ?? 'Search';

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsSearch', ['shortcut' => $shortcut ?: null]);

    // Wide while the field has focus or holds text. Not :focus-within, which
    // the button after Escape would hold open.
    $wide = 'has-[input:focus]:w-64 has-[input:not(:placeholder-shown)]:w-64';

    $box = implode(' ', [
        'flex w-11 max-w-[calc(100vw-2rem)] sm:w-9',
        $wide,
        // Every timing is motion-safe: a duration alone transitions `all`.
        'motion-safe:transition-[width] motion-safe:duration-popover-leave motion-safe:ease-leave',
        'motion-safe:has-[input:focus]:duration-popover-enter motion-safe:has-[input:focus]:ease-enter',
        // Overlay: anchored at the end of a square that keeps its place in
        // the row, growing over what is before it.
        $overlay ? 'absolute end-0 top-0 z-20' : 'relative',
    ]);

    $control = implode(' ', [
        $field['control']((bool) $error, $readonly, (bool) $disabled),
        // The icon's square at the start. Collapsed the field is exactly that
        // square, so nothing at the end; wide, room to the end, and the
        // clear button's square when it shows.
        'ps-[calc(--spacing(11)-2px)] pe-0 sm:ps-[calc(--spacing(9)-2px)] sm:pe-0 cursor-pointer focus:cursor-text',
        $clearable
            ? 'peer focus:pe-11 not-placeholder-shown:pe-11 sm:focus:pe-9 sm:not-placeholder-shown:pe-9'
            : 'focus:pe-3 not-placeholder-shown:pe-3',
    ]);

    $shared = $attributes->except(['class'])->merge(array_filter([
        'type' => 'search',
        'id' => $id,
        'class' => $control,
        'enterkeyhint' => 'search',
        'aria-invalid' => $error ? 'true' : null,
        // :placeholder-shown needs a placeholder to exist, as on clearable.
        'placeholder' => $attributes->get('placeholder', "\u{00A0}"),
        'data-ds-clearable' => $clearable ?: null,
    ], fn ($v) => $v !== null));
@endphp

<div x-data="{{ $state }}" data-ds-search {{ $attributes->only('class')->merge(['class' => $overlay ? 'relative size-11 shrink-0 sm:size-9' : 'w-fit shrink-0']) }}>
    @if ($label)
        <label for="{{ $id }}" class="sr-only">{{ $label }}</label>
    @endif

    <span class="{{ $box }}">
        <button
            type="button"
            tabindex="-1"
            aria-label="{{ $named }}"
            aria-controls="{{ $id }}"
            class="absolute inset-y-0 start-0 z-10 w-11 rounded-control focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring sm:w-9"
            data-ds-search-button
            @disabled($disabled)
        ></button>

        <span class="{{ $field['shell']($disabled || $readonly) }} z-10 min-w-0">
            <input {{ $shared }} @disabled($disabled) data-ds-search-input />

            @if ($clearable)
                <button
                    type="button"
                    class="absolute inset-y-0 end-0 z-10 flex w-11 items-center justify-center rounded-r-control text-fg-muted peer-placeholder-shown:hidden hover:text-fg focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus-ring sm:w-9"
                    x-data="{{ \Shipbytes\BladeUi\Support\Behaviour::data('dsClear') }}"
                    aria-controls="{{ $id }}"
                    aria-label="Clear {{ $named }}"
                >
                    <x-ds::icon name="x-mark" variant="mini" size="4" />
                </button>
            @endif

            <span class="pointer-events-none absolute inset-y-0 start-0 z-10 flex w-11 items-center justify-center text-fg-muted sm:w-9">
                <x-ds::icon :name="$icon ?? 'magnifying-glass'" size="5" class="sm:size-4" />
            </span>
        </span>
    </span>
</div>
