@props([
    /** Submitted field name, shared by every chip. `[]` is added when `multiple`. Not needed for link chips. */
    'name' => null,
    /** What the chips choose between. The group's name; shown above the chips unless `hideLabel`. */
    'label',
    /** Kept as the group's accessible name, not shown: for a row whose meaning is plain from what is around it. */
    'hideLabel' => false,
    /** Any number chosen (checkboxes), rather than one (radios). */
    'multiple' => false,
    /** sm | md | lg, for every chip. */
    'size' => 'md',
])

@php
    /*
     * The chips have rendered by the time this view runs: a component's slot is
     * rendered before the component itself. So the group cannot hand them
     * anything but what @aware reads (name, multiple, size), and it learns
     * what they are by what they left in the slot: a marker on each chip, and
     * on each input a hook to put the host's binding on.
     */
    $html = (string) $slot;
    $inputs = str_contains($html, 'data-ds-chip="input"');
    $links = str_contains($html, 'data-ds-chip="link"');

    if ($inputs && $links) {
        throw new \InvalidArgumentException(
            "ds::chip-group \"{$label}\" mixes link chips (`href`) with chips that choose a value. "
            .'A group is one or the other: links filter by moving to a URL, inputs post with the form.'
        );
    }

    // wire:model and x-model belong on the inputs, which is where Livewire and
    // Alpine read a checkbox or radio group from; on the fieldset they would
    // bind nothing.
    $isModel = fn ($value, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model');
    $model = $attributes->filter($isModel);

    if ($model->getAttributes() !== [] && $links) {
        throw new \InvalidArgumentException("ds::chip-group \"{$label}\": link chips have no value to bind; drop the wire:model or x-model.");
    }

    $hook = 'data-ds-chip-input';
    $chips = new \Illuminate\Support\HtmlString(str_replace($hook, trim((string) $model), $html));

    // Stable under wire:model, random otherwise: see Support\ControlId.
    $labelId = \Shipbytes\BladeUi\Support\ControlId::for($attributes, $label, $name).'-label';
    $legend = $hideLabel ? 'sr-only' : 'mb-2 block text-meta font-medium text-fg-muted';
    $row = 'flex flex-wrap items-center gap-2';
@endphp

@if ($links)
    {{-- Links to filtered views are navigation: a nav named by the label, the
         current filter marked aria-current by the chip. --}}
    <nav {{ $attributes->merge(['aria-labelledby' => $labelId, 'class' => 'min-w-0']) }}>
        <span id="{{ $labelId }}" class="{{ $legend }}">{{ $label }}</span>
        <div class="{{ $row }}">{{ $chips }}</div>
    </nav>
@else
    {{-- A fieldset whose legend is the question, so a screen reader entering
         the group hears what the chips are for before the first one. --}}
    <fieldset {{ $attributes->filter(fn ($value, $key) => ! $isModel($value, $key))->merge(['class' => 'min-w-0']) }}>
        <legend class="{{ $legend }}">{{ $label }}</legend>
        <div class="{{ $row }}">{{ $chips }}</div>
    </fieldset>
@endif
