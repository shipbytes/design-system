@props([
    /** Submitted field name, shared by every card. */
    'name',
    /** What the cards choose between. The group's legend. */
    'label',
    /** Guidance for the choice as a whole, under the legend. */
    'help' => null,
    /** Validation message for the choice. Replaces `help`. */
    'error' => null,
    /** 1 to 4 across on a wide screen. Every count collapses to one column on a phone. */
    'columns' => 1,
])

@php
    $columns = (int) $columns;
    if ($columns < 1 || $columns > 4) {
        throw new \InvalidArgumentException("ds::radio-cards: columns=\"{$columns}\" is out of range. It takes 1 to 4.");
    }

    // Written out, so a consumer's Tailwind sees every class. Two columns
    // from sm, and the wider counts only once there is room for them.
    $grid = [
        1 => 'grid-cols-1',
        2 => 'grid-cols-1 sm:grid-cols-2',
        3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
        4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
    ][$columns];

    $field = \Shipbytes\BladeUi\Support\Field::recipe();

    // Stable under wire:model, random otherwise: see Support\ControlId.
    $id = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label, $name);
    $describedBy = $error ? "{$id}-error" : ($help ? "{$id}-help" : null);
    $required = (bool) $attributes->get('required');

    /*
     * The cards have rendered by the time this view runs: a component's slot
     * is rendered before the component itself. Each radio left a hook in the
     * slot, swapped here for what belongs on every radio and that only the
     * group is given: the host's wire:model or x-model, and `required`, which
     * the browser checks on the radios, not on a fieldset.
     */
    $isModel = fn ($value, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model');
    $stamp = $attributes->filter($isModel)->merge($required ? ['required' => true] : []);
    $cards = new \Illuminate\Support\HtmlString(
        str_replace('data-ds-radio-card-input', trim((string) $stamp), (string) $slot)
    );

    $fieldset = $attributes
        ->filter(fn ($value, $key) => ! $isModel($value, $key) && $key !== 'required')
        ->merge(array_filter([
            'class' => 'min-w-0',
            'role' => 'radiogroup',
            'aria-labelledby' => "{$id}-label",
            'aria-describedby' => $describedBy,
            'aria-invalid' => $error ? 'true' : null,
            'aria-required' => $required ? 'true' : null,
        ], fn ($v) => $v !== null));
@endphp

{{-- A fieldset whose legend is the question, given the radiogroup role so
     aria-invalid and aria-required are valid on it. --}}
<fieldset {{ $fieldset }}>
    <legend id="{{ $id }}-label" class="{{ $field['label'] }}">{{ $label }}</legend>

    @if ($help && ! $error)
        <p id="{{ $id }}-help" class="mt-0.5 text-meta text-fg-muted">{{ $help }}</p>
    @endif

    <div class="mt-2.5 grid gap-3 {{ $grid }}">
        {{ $cards }}
    </div>

    @if ($error)
        <p id="{{ $id }}-error" class="{{ $field['error'] }}">
            <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @endif
</fieldset>
